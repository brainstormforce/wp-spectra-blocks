<?php
/**
 * Regression guard for JIT selector escaping.
 *
 * Every non-word character in a class token must be backslash-escaped in the
 * compiled selector. Covers bracket utilities, decimal fractions, variant
 * stacks with `:` separators, negative brackets, and shape-aware color
 * brackets with `#`. Surfaced by GIT-106-layout-gaps.md #1 — orphan escapes
 * meant compiled rules never matched the markup.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\JitCompiler;
use WP_UnitTestCase;

/**
 * JitSelectorEscapeTest test case.
 *
 * @since x.x.x
 */
class JitSelectorEscapeTest extends WP_UnitTestCase {

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
	 * Plain bracket utility escapes `[` and `]`.
	 *
	 * @return void
	 */
	public function test_plain_bracket_utility_escapes_brackets(): void {
		$css = JitCompiler::compile( array( 'text-[56px]' ) );

		$this->assertStringContainsString( '.text-\\[56px\\]', $css );
	}

	/**
	 * Decimal fractions in bracket values escape the `.` character.
	 *
	 * @return void
	 */
	public function test_decimal_fraction_escapes_dot(): void {
		$css = JitCompiler::compile( array( 'grid-cols-[1.2fr_1fr]' ) );

		$this->assertStringContainsString( '.grid-cols-\\[1\\.2fr_1fr\\]', $css );
	}

	/**
	 * Shape-aware `text-[#hex]` escapes the `#` character in the selector.
	 *
	 * @return void
	 */
	public function test_hex_color_escapes_hash(): void {
		$css = JitCompiler::compile( array( 'text-[#ff0066]' ) );

		$this->assertStringContainsString( '.text-\\[\\#ff0066\\]', $css );
	}

	/**
	 * Negative bracket value escapes both `[` and the leading `-`... wait,
	 * `-` is a word char. It's preserved unescaped.
	 *
	 * @return void
	 */
	public function test_negative_bracket_value_escapes_only_brackets(): void {
		$css = JitCompiler::compile( array( 'mt-[-4px]' ) );

		$this->assertStringContainsString( '.mt-\\[-4px\\]', $css );
	}

	/**
	 * Responsive-variant token escapes the `:` between variant and base.
	 *
	 * @return void
	 */
	public function test_responsive_variant_escapes_colon(): void {
		$css = JitCompiler::compile( array( 'md:text-[32px]' ) );

		$this->assertStringContainsString( '.md\\:text-\\[32px\\]', $css );
	}

	/**
	 * Hover variant escapes variant-separator `:` and leaves pseudo `:hover` unescaped.
	 *
	 * @return void
	 */
	public function test_hover_variant_escapes_separator_keeps_pseudo(): void {
		$css = JitCompiler::compile( array( 'hover:p-[4px]' ) );

		$this->assertStringContainsString( '.hover\\:p-\\[4px\\]:hover', $css );
	}

	/**
	 * Combined responsive + hover escapes both separator colons, pseudo colon stays.
	 *
	 * @return void
	 */
	public function test_combined_variant_stack_escapes_separators(): void {
		$css = JitCompiler::compile( array( 'md:hover:mt-[-4px]' ) );

		$this->assertStringContainsString( '.md\\:hover\\:mt-\\[-4px\\]:hover', $css );
	}

	/**
	 * Percentage suffix escapes `%`.
	 *
	 * @return void
	 */
	public function test_percent_suffix_escapes(): void {
		$css = JitCompiler::compile( array( 'w-[50%]' ) );

		$this->assertStringContainsString( '.w-\\[50\\%\\]', $css );
	}

	/**
	 * Compiled selector rewrites a fixture post's className attributes intact.
	 *
	 * Exercises the same round-trip ERA relies on: post content → token harvest
	 * → compile → CSS rule that matches the markup's escaped selector.
	 *
	 * @return void
	 */
	public function test_fixture_post_every_bracket_pattern_escapes(): void {
		$content = '<!-- wp:spectra/container {"className":"text-[56px] md:text-[32px] hover:p-[4px] mt-[-4px] text-[#ff0066] grid-cols-[1.2fr_1fr] w-[50%] md:hover:mt-[-4px]"} --><div class="wp-block"></div><!-- /wp:spectra/container -->';

		$class_strings = JitCompiler::collect_class_strings_from_content( $content );
		$css           = JitCompiler::compile( $class_strings );

		$this->assertStringContainsString( '.text-\\[56px\\]', $css );
		$this->assertStringContainsString( '.md\\:text-\\[32px\\]', $css );
		$this->assertStringContainsString( '.hover\\:p-\\[4px\\]:hover', $css );
		$this->assertStringContainsString( '.mt-\\[-4px\\]', $css );
		$this->assertStringContainsString( '.text-\\[\\#ff0066\\]', $css );
		$this->assertStringContainsString( '.grid-cols-\\[1\\.2fr_1fr\\]', $css );
		$this->assertStringContainsString( '.w-\\[50\\%\\]', $css );
		$this->assertStringContainsString( '.md\\:hover\\:mt-\\[-4px\\]:hover', $css );
	}
}
