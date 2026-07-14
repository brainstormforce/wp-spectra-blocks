<?php
/**
 * Tests for Tailwind v4 parity utility presets + container-query variants.
 *
 * Covers the families added via ClassRegistry::get_parity_v4_classes() and
 * the `@{size}:` container-query / `starting:` variants wired into the
 * JIT compiler.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\ClassRegistry;
use SpectraBlocks\GlobalStyles\JitCompiler;
use WP_UnitTestCase;

/**
 * ParityV4Test test case.
 *
 * @since x.x.x
 */
class ParityV4Test extends WP_UnitTestCase {

	/**
	 * Reset caches before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		ClassRegistry::invalidate_cache();
		JitCompiler::reset_memo();
	}

	// ---------------------------------------------------------------------
	// Registry — representative v4 utility entries
	// ---------------------------------------------------------------------

	/**
	 * Expected v4 parity classes.
	 *
	 * Each entry: [ class_name, substring expected in its CSS ].
	 *
	 * @return array<int, array{0: string, 1: string}>
	 */
	public function provide_parity_v4_classes(): array {
		return array(
			// Logical spacing.
			array( 'ps-4', 'padding-inline-start: 1rem;' ),
			array( 'pe-6', 'padding-inline-end: 1.5rem;' ),
			array( 'ms-2', 'margin-inline-start: 0.5rem;' ),
			array( 'me-px', 'margin-inline-end: 1px;' ),

			// Logical inset.
			array( 'start-4', 'inset-inline-start: 1rem;' ),
			array( 'end-auto', 'inset-inline-end: auto;' ),
			array( 'end-full', 'inset-inline-end: 100%;' ),

			// Logical border widths.
			array( 'border-s-2', 'border-inline-start-width: 2px;' ),
			array( 'border-e', 'border-inline-end-width: 1px;' ),

			// Logical border radius.
			array( 'rounded-s-lg', 'border-start-start-radius: 0.5rem;' ),
			array( 'rounded-ss-xl', 'border-start-start-radius: 0.75rem;' ),
			array( 'rounded-ee-full', 'border-end-end-radius: 9999px;' ),

			// Dynamic viewport units.
			array( 'h-dvh', 'height: 100dvh;' ),
			array( 'min-h-svh', 'min-height: 100svh;' ),
			array( 'max-w-lvw', 'max-width: 100lvw;' ),
			array( 'size-dvh', 'width: 100dvh; height: 100dvh;' ),

			// Text-shadow.
			array( 'text-shadow-sm', 'text-shadow: 0 1px 2px' ),
			array( 'text-shadow-2xl', 'text-shadow: 0 6px 16px' ),
			array( 'text-shadow-none', 'text-shadow: none;' ),

			// size-* shorthand. NOTE: `size-full` is intentionally NOT registered
			// — it collides with WordPress core's reserved `.size-full` image
			// class (see ClassRegistry). Use `w-full h-full` / `size-[100%]`.
			array( 'size-4', 'width: 1rem; height: 1rem;' ),
			array( 'size-fit', 'width: fit-content; height: fit-content;' ),
			array( 'size-1/2', 'width: 50%; height: 50%;' ),

			// inset-ring-*.
			array( 'inset-ring-2', 'box-shadow: inset 0 0 0 2px currentColor;' ),
			array( 'inset-ring', 'box-shadow: inset 0 0 0 1px currentColor;' ),

			// field-sizing-*.
			array( 'field-sizing-content', 'field-sizing: content;' ),
			array( 'field-sizing-fixed', 'field-sizing: fixed;' ),

			// 3D transforms (non-composing presets).
			array( 'perspective-1000', 'perspective: 1000px;' ),
			array( 'perspective-none', 'perspective: none;' ),
			array( 'perspective-origin-top-right', 'perspective-origin: top right;' ),
			array( 'transform-3d', 'transform-style: preserve-3d;' ),
			array( 'transform-flat', 'transform-style: flat;' ),
			array( 'backface-hidden', 'backface-visibility: hidden;' ),

			// Gradients v4.
			array( 'bg-linear-to-r', 'linear-gradient(to right' ),
			array( 'bg-linear-45', 'linear-gradient(45deg' ),
			array( 'bg-radial', 'radial-gradient(var(--tw-gradient-stops));' ),
			array( 'bg-conic', 'conic-gradient(var(--tw-gradient-stops));' ),

			// color-scheme.
			array( 'scheme-dark', 'color-scheme: dark;' ),
			array( 'scheme-light-dark', 'color-scheme: light dark;' ),
			array( 'scheme-only-light', 'color-scheme: only light;' ),

			// Transition behavior + interpolate-size.
			array( 'transition-discrete', 'transition-behavior: allow-discrete;' ),
			array( 'interpolate-size-allow-keywords', 'interpolate-size: allow-keywords;' ),

			// Container-query utility.
			array( '@container', 'container-type: inline-size;' ),
			array( 'container-type-size', 'container-type: size;' ),
		);
	}

	/**
	 * Each representative v4 parity class exists in the merged registry with
	 * the expected CSS substring, required metadata fields, and non-empty
	 * css value.
	 *
	 * @dataProvider provide_parity_v4_classes
	 *
	 * @param string $class_name    Class name to look up.
	 * @param string $css_substring Expected substring in the CSS.
	 * @return void
	 */
	public function test_parity_v4_class_exists_with_expected_css( string $class_name, string $css_substring ): void {
		$all = ClassRegistry::get_all_classes();

		$this->assertArrayHasKey( $class_name, $all, "Missing v4 parity class: {$class_name}" );

		$entry = $all[ $class_name ];

		$this->assertArrayHasKey( 'css', $entry );
		$this->assertArrayHasKey( 'title', $entry );
		$this->assertArrayHasKey( 'description', $entry );
		$this->assertArrayHasKey( 'category', $entry );
		$this->assertArrayHasKey( 'tags', $entry );

		$this->assertNotEmpty( $entry['css'], "Empty css for {$class_name}" );
		$this->assertIsArray( $entry['tags'] );

		$this->assertStringContainsString(
			$css_substring,
			$entry['css'],
			"CSS for {$class_name} did not contain expected substring"
		);
	}

	/**
	 * get_parity_v4_classes() returns a non-empty associative array and
	 * every entry satisfies the registry entry shape.
	 *
	 * @return void
	 */
	public function test_get_parity_v4_classes_shape(): void {
		$classes = ClassRegistry::get_parity_v4_classes();

		$this->assertIsArray( $classes );
		$this->assertNotEmpty( $classes );

		foreach ( $classes as $name => $data ) {
			$this->assertIsString( $name );
			$this->assertArrayHasKey( 'css', $data, "Entry {$name} missing css" );
			$this->assertArrayHasKey( 'title', $data, "Entry {$name} missing title" );
			$this->assertArrayHasKey( 'description', $data, "Entry {$name} missing description" );
			$this->assertArrayHasKey( 'category', $data, "Entry {$name} missing category" );
			$this->assertArrayHasKey( 'tags', $data, "Entry {$name} missing tags" );
			$this->assertIsArray( $data['tags'] );
			$this->assertNotEmpty( $data['css'], "Entry {$name} has empty css" );
		}
	}

	// ---------------------------------------------------------------------
	// Container-query variants
	// ---------------------------------------------------------------------

	/**
	 * `@md:` wraps the rule in `@container (min-width: 28rem)`.
	 *
	 * @return void
	 */
	public function test_container_md_variant_wraps_in_container_at_rule(): void {
		$css = JitCompiler::compile( array( '@md:p-[1rem]' ) );

		$this->assertStringContainsString( '@container (min-width: 28rem)', $css );
		$this->assertStringContainsString( 'padding: 1rem;', $css );
	}

	/**
	 * `@sm:` resolves to the 24rem breakpoint.
	 *
	 * @return void
	 */
	public function test_container_sm_variant_uses_24rem_breakpoint(): void {
		$css = JitCompiler::compile( array( '@sm:text-[#123456]' ) );

		$this->assertStringContainsString( '@container (min-width: 24rem)', $css );
	}

	/**
	 * Arbitrary container-query size `@[32rem]:`.
	 *
	 * @return void
	 */
	public function test_container_arbitrary_size_variant(): void {
		$css = JitCompiler::compile( array( '@[32rem]:block' ) );

		$this->assertStringContainsString( '@container (min-width: 32rem)', $css );
	}

	/**
	 * Named container: `@md/sidebar:` → `@container sidebar (min-width: 28rem)`.
	 *
	 * @return void
	 */
	public function test_container_named_size_variant(): void {
		$css = JitCompiler::compile( array( '@md/sidebar:flex' ) );

		$this->assertStringContainsString( '@container sidebar (min-width: 28rem)', $css );
	}

	/**
	 * `starting:` wraps the rule in `@starting-style`.
	 *
	 * @return void
	 */
	public function test_starting_variant_wraps_in_starting_style(): void {
		$css = JitCompiler::compile( array( 'starting:opacity-0' ) );

		$this->assertStringContainsString( '@starting-style', $css );
	}

	/**
	 * Container-query variants can be disabled via filter — the token is
	 * silently dropped instead of emitting CSS.
	 *
	 * @return void
	 */
	public function test_container_queries_disabled_filter_drops_tokens(): void {
		add_filter( 'spectra_blocks_gbs_container_queries', '__return_false' );
		JitCompiler::reset_memo();

		$css = JitCompiler::compile( array( '@md:p-[1rem]' ) );

		$this->assertStringNotContainsString( '@container', $css );

		remove_filter( 'spectra_blocks_gbs_container_queries', '__return_false' );
	}

	// ---------------------------------------------------------------------
	// 3D transforms compose with 2D transforms (integration check)
	// ---------------------------------------------------------------------

	/**
	 * `rotate-x-45` stores the angle in `--tw-rotate-x` and emits the
	 * composed transform string (including the 3D slot with identity
	 * fallbacks for unset siblings).
	 *
	 * @return void
	 */
	public function test_rotate_x_emits_axis_var_and_composed_transform(): void {
		$css = JitCompiler::compile( array( 'rotate-x-45' ) );

		$this->assertStringContainsString( '--tw-rotate-x: 45deg', $css );
		$this->assertStringContainsString( 'rotateX(var(--tw-rotate-x, 0))', $css );
	}

	/**
	 * `translate-z-8` stores the length in `--tw-translate-z`.
	 *
	 * @return void
	 */
	public function test_translate_z_spacing_scale(): void {
		$css = JitCompiler::compile( array( 'translate-z-8' ) );

		$this->assertStringContainsString( '--tw-translate-z: 2rem', $css );
		$this->assertStringContainsString( 'translateZ(var(--tw-translate-z, 0))', $css );
	}

	/**
	 * `scale-z-150` stores the unitless multiplier in `--tw-scale-z`.
	 *
	 * @return void
	 */
	public function test_scale_z_uses_scale_var(): void {
		$css = JitCompiler::compile( array( 'scale-z-150' ) );

		$this->assertStringContainsString( '--tw-scale-z: 1.5', $css );
		$this->assertStringContainsString( 'scaleZ(var(--tw-scale-z, 1))', $css );
	}

	// ---------------------------------------------------------------------
	// Ring default color — v4 vs v3 filter
	// ---------------------------------------------------------------------

	/**
	 * Default (v4) ring color fallback is `currentColor`.
	 *
	 * @return void
	 */
	public function test_ring_default_color_is_currentColor(): void {
		$css = JitCompiler::compile( array( 'ring-2' ) );

		$this->assertStringContainsString( 'var(--tw-ring-color, currentColor)', $css );
	}

	/**
	 * With `spectra_blocks_ring_v3_compat` filter true, ring default reverts
	 * to the v3 blue-500/50 color.
	 *
	 * @return void
	 */
	public function test_ring_v3_compat_filter_restores_blue_default(): void {
		add_filter( 'spectra_blocks_ring_v3_compat', '__return_true' );
		JitCompiler::reset_memo();

		$css = JitCompiler::compile( array( 'ring-2' ) );

		$this->assertStringContainsString( 'rgb(59 130 246 / 0.5)', $css );

		remove_filter( 'spectra_blocks_ring_v3_compat', '__return_true' );
	}
}
