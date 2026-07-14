<?php
/**
 * Tests for the JIT class compiler.
 *
 * Covers per-utility arbitrary bracket parsing, variant stacking (responsive +
 * state + pseudo), CSS selector escaping, content auto-injection, and filter
 * hooks. Full-property `[prop:value]` brackets are intentionally unsupported.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\JitCompiler;
use WP_UnitTestCase;

/**
 * JitCompilerTest test case.
 *
 * @since x.x.x
 */
class JitCompilerTest extends WP_UnitTestCase {

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
	 * Per-utility bracket compiles to an escaped-bracket selector.
	 *
	 * @return void
	 */
	public function test_per_utility_bracket_compiles_with_escaped_selector(): void {
		$css = JitCompiler::compile( array( 'max-h-[80vh]' ) );

		$this->assertStringContainsString( '.max-h-\\[80vh\\]', $css );
		$this->assertStringContainsString( 'max-height: 80vh;', $css );
	}

	/**
	 * Shorthand prefixes (`px`) emit multiple CSS declarations.
	 *
	 * @return void
	 */
	public function test_shorthand_prefix_emits_multiple_declarations(): void {
		$css = JitCompiler::compile( array( 'px-[71px]' ) );

		$this->assertStringContainsString( 'padding-left: 71px;', $css );
		$this->assertStringContainsString( 'padding-right: 71px;', $css );
	}

	/**
	 * Shape-aware `text-[#hex]` maps to `color`, not `font-size`.
	 *
	 * @return void
	 */
	public function test_text_bracket_hex_maps_to_color(): void {
		$css = JitCompiler::compile( array( 'text-[#ff0000]' ) );

		$this->assertStringContainsString( 'color: #ff0000;', $css );
		$this->assertStringNotContainsString( 'font-size:', $css );
	}

	/**
	 * Shape-aware `text-[16px]` maps to `font-size`, not `color`.
	 *
	 * @return void
	 */
	public function test_text_bracket_length_maps_to_font_size(): void {
		$css = JitCompiler::compile( array( 'text-[16px]' ) );

		$this->assertStringContainsString( 'font-size: 16px;', $css );
		$this->assertStringNotContainsString( 'color:', $css );
	}

	/**
	 * Shape-aware `bg-[#hex]` maps to `background-color`.
	 *
	 * @return void
	 */
	public function test_bg_bracket_hex_maps_to_background_color(): void {
		$css = JitCompiler::compile( array( 'bg-[#112233]' ) );

		$this->assertStringContainsString( 'background-color: #112233;', $css );
	}

	/**
	 * Underscores in bracket values decode to spaces (Tailwind convention).
	 *
	 * @return void
	 */
	public function test_underscore_in_bracket_becomes_space(): void {
		$css = JitCompiler::compile( array( 'p-[10px_20px]' ) );

		$this->assertStringContainsString( 'padding: 10px 20px;', $css );
	}

	/**
	 * Escaped underscore `\_` preserves a literal underscore.
	 *
	 * @return void
	 */
	public function test_escaped_underscore_preserved_literal(): void {
		$css = JitCompiler::compile( array( 'bg-[url(/a\\_b.jpg)]' ) );

		$this->assertStringContainsString( 'background-image: url(/a_b.jpg);', $css );
	}

	/**
	 * md: wraps the rule in a mobile-first upward min-width media query.
	 *
	 * @return void
	 */
	public function test_md_variant_wraps_in_min_width_media_query(): void {
		$css = JitCompiler::compile( array( 'md:p-[10px]' ) );

		$this->assertStringContainsString( '@media (min-width: 768px)', $css );
		$this->assertStringNotContainsString( 'max-width', $css );
		$this->assertStringContainsString( 'padding: 10px;', $css );
	}

	/**
	 * sm: wraps the rule in `(min-width: 640px)` — Tailwind-parity mobile-first.
	 *
	 * @return void
	 */
	public function test_sm_variant_uses_min_width_640(): void {
		$css = JitCompiler::compile( array( 'sm:p-[4px]' ) );

		$this->assertStringContainsString( '@media (min-width: 640px)', $css );
		$this->assertStringNotContainsString( 'max-width', $css );
	}

	/**
	 * lg: wraps the rule in `(min-width: 1024px)`.
	 *
	 * @return void
	 */
	public function test_lg_variant_uses_min_width_1024(): void {
		$css = JitCompiler::compile( array( 'lg:p-[16px]' ) );

		$this->assertStringContainsString( '@media (min-width: 1024px)', $css );
	}

	/**
	 * xl: wraps the rule in `(min-width: 1280px)`.
	 *
	 * @return void
	 */
	public function test_xl_variant_uses_min_width_1280(): void {
		$css = JitCompiler::compile( array( 'xl:p-[20px]' ) );

		$this->assertStringContainsString( '@media (min-width: 1280px)', $css );
	}

	/**
	 * 2xl: wraps the rule in `(min-width: 1536px)`.
	 *
	 * @return void
	 */
	public function test_2xl_variant_uses_min_width_1536(): void {
		$css = JitCompiler::compile( array( '2xl:p-[24px]' ) );

		$this->assertStringContainsString( '@media (min-width: 1536px)', $css );
	}

	/**
	 * Hover variant appends a pseudo-class to the selector.
	 *
	 * @return void
	 */
	public function test_hover_variant_appends_pseudo_class(): void {
		$css = JitCompiler::compile( array( 'hover:text-[#0000ff]' ) );

		$this->assertStringContainsString( ':hover', $css );
		$this->assertStringContainsString( 'color: #0000ff;', $css );
	}

	/**
	 * Before variant auto-injects content:'' when omitted.
	 *
	 * @return void
	 */
	public function test_before_variant_injects_content(): void {
		$css = JitCompiler::compile( array( 'before:text-[#ff0000]' ) );

		$this->assertStringContainsString( '::before', $css );
		$this->assertStringContainsString( "content: ''", $css );
	}

	/**
	 * Combined `md:hover:mt-[-4px]` produces mobile-first media-wrapped hover.
	 *
	 * @return void
	 */
	public function test_combined_responsive_state_variant(): void {
		$css = JitCompiler::compile( array( 'md:hover:mt-[-4px]' ) );

		$this->assertStringContainsString( '@media (min-width: 768px)', $css );
		$this->assertStringNotContainsString( 'max-width', $css );
		$this->assertStringContainsString( ':hover', $css );
		$this->assertStringContainsString( 'margin-top: -4px;', $css );
	}

	/**
	 * Unknown token without a recognized prefix is rejected.
	 *
	 * @return void
	 */
	public function test_unknown_token_is_rejected(): void {
		$css = JitCompiler::compile( array( 'totally-not-a-real-class-xyz' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * Full-property `[prop:value]` brackets are retired and produce no CSS.
	 *
	 * @return void
	 */
	public function test_full_property_bracket_is_rejected(): void {
		$css = JitCompiler::compile( array( '[color:red]' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * Bracket with an unknown prefix emits no CSS.
	 *
	 * @return void
	 */
	public function test_unknown_bracket_prefix_is_rejected(): void {
		$css = JitCompiler::compile( array( 'nonsense-[12px]' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * Duplicate tokens are deduplicated before compile.
	 *
	 * @return void
	 */
	public function test_duplicate_tokens_deduplicated(): void {
		$tokens = JitCompiler::collect_tokens( array( 'a b a c', 'b d' ) );
		sort( $tokens );

		$this->assertSame( array( 'a', 'b', 'c', 'd' ), $tokens );
	}

	/**
	 * Class strings are collected from a block's className attribute.
	 *
	 * @return void
	 */
	public function test_collect_from_content_reads_block_className(): void {
		$content = '<!-- wp:spectra/container {"className":"flex gap-[8px]"} -->
<div class="wp-block">child</div>
<!-- /wp:spectra/container -->';

		$strings = JitCompiler::collect_class_strings_from_content( $content );

		$this->assertContains( 'flex gap-[8px]', $strings );
		$this->assertContains( 'wp-block', $strings );
	}

	/**
	 * Two responsive variants on one token are rejected.
	 *
	 * @return void
	 */
	public function test_two_responsive_variants_rejected(): void {
		$css = JitCompiler::compile( array( 'md:sm:text-[#ff0000]' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * Escape helper safely backslashes non-word chars.
	 *
	 * @return void
	 */
	public function test_escape_selector_preserves_words_escapes_symbols(): void {
		$escaped = JitCompiler::escape_selector( 'max-h-[80vh]' );

		$this->assertSame( 'max-h-\\[80vh\\]', $escaped );
	}

	/**
	 * Filter `spectra_gs_jit_before_compile` can drop tokens.
	 *
	 * @return void
	 */
	public function test_before_compile_filter_drops_tokens(): void {
		add_filter(
			'spectra_gs_jit_before_compile',
			static function () {
				return array();
			}
		);

		$css = JitCompiler::compile( array( 'max-h-[80vh]' ) );

		remove_all_filters( 'spectra_gs_jit_before_compile' );

		$this->assertSame( '', $css );
	}

	/**
	 * Filter `spectra_gs_jit_variant_prefixes` can register custom variants.
	 *
	 * @return void
	 */
	public function test_variant_prefix_filter_registers_custom_state(): void {
		add_filter(
			'spectra_gs_jit_variant_prefixes',
			static function ( $suffix, $variant ) {
				return 'custom-focus' === $variant ? ':is(:focus)' : $suffix;
			},
			10,
			2
		);

		$css = JitCompiler::compile( array( 'custom-focus:text-[#ffffff]' ) );

		remove_all_filters( 'spectra_gs_jit_variant_prefixes' );

		$this->assertStringContainsString( ':is(:focus)', $css );
		$this->assertStringContainsString( 'color: #ffffff;', $css );
	}

	/**
	 * `get_prefix_map()` exposes the constant for external consumers.
	 *
	 * @return void
	 */
	public function test_prefix_map_exposes_known_prefixes(): void {
		$map = JitCompiler::get_prefix_map();

		$this->assertIsArray( $map );
		$this->assertArrayHasKey( 'p', $map );
		$this->assertArrayHasKey( 'max-h', $map );
		$this->assertArrayHasKey( 'text', $map );
	}

	/**
	 * Alpha-slash on a registered color token wraps the value in `color-mix()`.
	 *
	 * @return void
	 */
	public function test_alpha_slash_on_registered_color_emits_color_mix(): void {
		$css = JitCompiler::compile( array( 'bg-white/50' ) );

		$this->assertStringContainsString( 'color-mix(in srgb,', $css );
		$this->assertStringContainsString( '#ffffff 50%', $css );
		$this->assertStringContainsString( ', transparent)', $css );
	}

	/**
	 * Alpha-slash on a bracket color wraps the bracket value in `color-mix()`.
	 *
	 * @return void
	 */
	public function test_alpha_slash_on_bracket_color_emits_color_mix(): void {
		$css = JitCompiler::compile( array( 'bg-[#ff0066]/25' ) );

		$this->assertStringContainsString( '#ff0066 25%', $css );
		$this->assertStringContainsString( 'color-mix(in srgb,', $css );
	}

	/**
	 * Alpha-slash on a non-color prefix (e.g. `p`) is rejected.
	 *
	 * @return void
	 */
	public function test_alpha_slash_on_non_color_prefix_rejected(): void {
		$css = JitCompiler::compile( array( 'p-[10px]/50' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * Alpha-slash with out-of-range opacity (>100) is rejected.
	 *
	 * @return void
	 */
	public function test_alpha_slash_out_of_range_rejected(): void {
		$css = JitCompiler::compile( array( 'bg-white/150' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * Alpha-slash with non-numeric opacity is rejected.
	 *
	 * @return void
	 */
	public function test_alpha_slash_non_numeric_rejected(): void {
		$css = JitCompiler::compile( array( 'bg-white/abc' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * Alpha-slash combines with state variants: `hover:bg-white/50`.
	 *
	 * @return void
	 */
	public function test_alpha_slash_combines_with_hover_variant(): void {
		$css = JitCompiler::compile( array( 'hover:bg-white/50' ) );

		$this->assertStringContainsString( ':hover', $css );
		$this->assertStringContainsString( 'color-mix(in srgb,', $css );
		$this->assertStringContainsString( '#ffffff 50%', $css );
	}

	/**
	 * Alpha-slash combines with responsive variant: `md:text-white/80`.
	 *
	 * @return void
	 */
	public function test_alpha_slash_combines_with_responsive_variant(): void {
		$css = JitCompiler::compile( array( 'md:text-white/80' ) );

		$this->assertStringContainsString( '@media (min-width: 768px)', $css );
		$this->assertStringContainsString( 'color-mix(in srgb,', $css );
		$this->assertStringContainsString( '#ffffff 80%', $css );
	}

	/**
	 * Alpha-slash on a non-color utility (e.g. `text-xl` font-size) is rejected.
	 *
	 * Wrapping a length in color-mix() would yield invalid CSS, so the whole
	 * token bails out rather than emit a half-formed rule.
	 *
	 * @return void
	 */
	public function test_alpha_slash_rejects_non_color_property(): void {
		$css = JitCompiler::compile( array( 'text-xl/50' ) );

		$this->assertSame( '', $css );
	}

	/**
	 * Gradient direction + stop utilities compile together to a workable triple.
	 *
	 * @return void
	 */
	public function test_gradient_direction_with_stops_compiles(): void {
		$css = JitCompiler::compile( array( 'bg-gradient-to-r', 'from-white', 'to-black' ) );

		$this->assertStringContainsString( '.bg-gradient-to-r', $css );
		$this->assertStringContainsString( 'linear-gradient(to right,', $css );
		$this->assertStringContainsString( '.from-white', $css );
		$this->assertStringContainsString( '--tw-gradient-from: #ffffff;', $css );
		$this->assertStringContainsString( '.to-black', $css );
		$this->assertStringContainsString( '--tw-gradient-to: #000000;', $css );
	}
}
