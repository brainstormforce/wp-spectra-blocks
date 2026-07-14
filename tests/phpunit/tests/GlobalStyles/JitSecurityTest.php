<?php
/**
 * Security tests for the JIT class compiler.
 *
 * Verifies that per-utility bracket values go through strict sanitization —
 * `javascript:`, `expression()`, `behavior:`, script tags, and `var(...)`
 * references are all rejected. Full-property `[prop:value]` brackets are
 * retired and produce no CSS.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\JitCompiler;
use WP_UnitTestCase;

/**
 * JitSecurityTest test case.
 *
 * @since x.x.x
 */
class JitSecurityTest extends WP_UnitTestCase {

	/**
	 * Reset memoization between tests.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		JitCompiler::reset_memo();
	}

	/**
	 * `javascript:` URL payloads in bracket values are stripped.
	 *
	 * @return void
	 */
	public function test_javascript_url_is_stripped(): void {
		$css = JitCompiler::compile( array( 'bg-[url(javascript:alert(1))]' ) );

		$this->assertStringNotContainsString( 'javascript:', $css );
	}

	/**
	 * IE-era `expression()` is stripped.
	 *
	 * @return void
	 */
	public function test_expression_is_stripped(): void {
		$css = JitCompiler::compile( array( 'w-[expression(alert(1))]' ) );

		$this->assertStringNotContainsString( 'expression(', $css );
	}

	/**
	 * `vbscript:` URLs are rejected.
	 *
	 * @return void
	 */
	public function test_vbscript_is_stripped(): void {
		$css = JitCompiler::compile( array( 'bg-[url(vbscript:msgbox)]' ) );

		$this->assertStringNotContainsString( 'vbscript:', $css );
	}

	/**
	 * Unclosed brackets are rejected entirely.
	 *
	 * @return void
	 */
	public function test_unclosed_bracket_rejected(): void {
		$css = JitCompiler::compile( array( 'p-[10px' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * Variant chains beyond the max-variant guard are rejected.
	 *
	 * @return void
	 */
	public function test_excessive_variants_rejected(): void {
		$token = 'md:hover:focus:active:before:visited:text-[#ff0000]';

		$css = JitCompiler::compile( array( $token ) );

		$this->assertSame( '', $css );
	}

	/**
	 * `<script>` tags inside bracket values are neutralized.
	 *
	 * @return void
	 */
	public function test_script_tag_stripped(): void {
		$css = JitCompiler::compile( array( 'bg-[url("<script>alert(1)</script>")]' ) );

		$this->assertStringNotContainsString( '<script', $css );
		$this->assertStringNotContainsString( '</script', $css );
	}

	/**
	 * Extremely long bracket values are clipped by the sanitizer.
	 *
	 * @return void
	 */
	public function test_oversized_value_truncated(): void {
		$big = str_repeat( 'a', 3000 );

		$css = JitCompiler::compile( array( 'p-[' . $big . ']' ) );

		// Nothing should contain the full 3000-char payload unmodified.
		$this->assertStringNotContainsString( $big, $css );
	}

	/**
	 * `var(...)` references are rejected by the strict sanitizer path.
	 *
	 * @return void
	 */
	public function test_var_reference_is_rejected(): void {
		$css = JitCompiler::compile( array( 'p-[var(--foo)]' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * `var(...)` inside a larger value (e.g. calc) is also rejected.
	 *
	 * @return void
	 */
	public function test_var_inside_calc_is_rejected(): void {
		$css = JitCompiler::compile( array( 'w-[calc(100%_-_var(--pad))]' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * Full-property `[prop:value]` brackets produce no CSS (retired grammar).
	 *
	 * @return void
	 */
	public function test_full_property_bracket_is_retired(): void {
		$css = JitCompiler::compile( array( '[color:red]', '[padding:10px]', '[background:#fff]' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * `<script>` tags inside a token are not emitted even after stripping.
	 *
	 * @return void
	 */
	public function test_inline_event_handler_rejected(): void {
		$css = JitCompiler::compile( array( 'bg-[url(x)_onload="alert(1)"]' ) );

		$this->assertStringNotContainsString( 'onload', $css );
	}
}
