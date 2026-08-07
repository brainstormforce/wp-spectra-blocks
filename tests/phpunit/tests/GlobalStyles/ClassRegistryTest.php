<?php
/**
 * Tests for ClassRegistry — utility class generation and SG token integration.
 *
 * Covers static generators (display, border, sizing, layout, filters, line-height,
 * font-weight, text-style, shadow, opacity, overflow, position, visibility, cursor,
 * list-style), dynamic generators (spacing, fonts), color class generation with
 * mocked SG config, shade mapping, neutral gap-fills, opacity variants, classSlug
 * derivation, and the public merge/cache/output APIs.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\ClassRegistry;
use WP_UnitTestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * ClassRegistryTest test case.
 *
 * @since x.x.x
 */
class ClassRegistryTest extends WP_UnitTestCase {

	/**
	 * Reset caches before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		ClassRegistry::invalidate_cache();
	}

	// ─────────────────────────────────────────────────────────────
	// STATIC UTILITY GENERATORS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Test display classes contain expected entries.
	 */
	public function test_display_classes_contain_expected_entries(): void {
		$classes = ClassRegistry::get_display_classes();

		$expected = array( 'block', 'flex', 'grid', 'hidden', 'inline', 'inline-block', 'inline-flex', 'inline-grid', 'contents' );
		foreach ( $expected as $name ) {
			$this->assertArrayHasKey( $name, $classes, "Missing display class: {$name}" );
			$this->assertStringContainsString( 'display:', $classes[ $name ]['css'] );
			$this->assertSame( 'display', $classes[ $name ]['category'] );
		}
	}

	/**
	 * Test border classes contain rounded, border-width, and border-style entries.
	 */
	public function test_border_classes_contain_expected_entries(): void {
		$classes = ClassRegistry::get_border_classes();

		// Rounded variants.
		$this->assertArrayHasKey( 'rounded-none', $classes );
		$this->assertArrayHasKey( 'rounded-md', $classes );
		$this->assertArrayHasKey( 'rounded-full', $classes );

		// Border widths.
		$this->assertArrayHasKey( 'border-0', $classes );
		$this->assertArrayHasKey( 'border-2', $classes );

		// Border styles.
		$this->assertArrayHasKey( 'border-solid', $classes );
		$this->assertArrayHasKey( 'border-dashed', $classes );

		foreach ( $classes as $data ) {
			$this->assertSame( 'border', $data['category'] );
		}
	}

	/**
	 * Test sizing classes contain width, height, and aspect-ratio entries.
	 */
	public function test_sizing_classes_spot_checks(): void {
		$classes = ClassRegistry::get_sizing_classes();

		$this->assertArrayHasKey( 'w-full', $classes );
		$this->assertSame( 'inline-size: 100%;', $classes['w-full']['css'] );

		$this->assertArrayHasKey( 'h-screen', $classes );
		$this->assertSame( 'block-size: 100vh;', $classes['h-screen']['css'] );

		$this->assertArrayHasKey( 'aspect-video', $classes );
		$this->assertSame( 'aspect-ratio: 16/9;', $classes['aspect-video']['css'] );

		$this->assertArrayHasKey( 'object-cover', $classes );
		$this->assertSame( 'object-fit: cover;', $classes['object-cover']['css'] );
	}

	/**
	 * Test layout classes contain flex, grid, and z-index entries.
	 */
	public function test_layout_classes_spot_checks(): void {
		$classes = ClassRegistry::get_layout_classes();

		// Flex direction.
		$this->assertArrayHasKey( 'flex-row', $classes );
		$this->assertSame( 'flex-direction: row;', $classes['flex-row']['css'] );

		// Justify content.
		$this->assertArrayHasKey( 'justify-center', $classes );
		$this->assertSame( 'justify-content: center;', $classes['justify-center']['css'] );

		// Grid columns.
		$this->assertArrayHasKey( 'grid-cols-3', $classes );

		// Z-index.
		$this->assertArrayHasKey( 'z-10', $classes );
		$this->assertSame( 'z-index: 10;', $classes['z-10']['css'] );
	}

	/**
	 * Test filter classes contain backdrop-blur entries.
	 */
	public function test_filter_classes_contain_backdrop_blur(): void {
		$classes = ClassRegistry::get_filter_classes();

		$this->assertArrayHasKey( 'backdrop-blur-sm', $classes );
		$this->assertArrayHasKey( 'backdrop-blur-md', $classes );
		$this->assertSame( 'filters', $classes['backdrop-blur-sm']['category'] );
	}

	/**
	 * Test line-height classes have expected values.
	 */
	public function test_line_height_classes(): void {
		$classes = ClassRegistry::get_line_height_classes();

		$this->assertArrayHasKey( 'leading-none', $classes );
		$this->assertSame( 'line-height: 1;', $classes['leading-none']['css'] );

		$this->assertArrayHasKey( 'leading-relaxed', $classes );
		$this->assertSame( 'line-height: 1.625;', $classes['leading-relaxed']['css'] );

		// 6 named (none, tight, snug, normal, relaxed, loose) + 8 numeric = 14 total.
		$this->assertCount( 14, $classes );
	}

	/**
	 * Test numeric leading classes (leading-3 through leading-10).
	 */
	public function test_line_height_numeric(): void {
		$classes = ClassRegistry::get_line_height_classes();

		$this->assertArrayHasKey( 'leading-3', $classes );
		$this->assertSame( 'line-height: 0.75rem;', $classes['leading-3']['css'] );

		$this->assertArrayHasKey( 'leading-8', $classes );
		$this->assertSame( 'line-height: 2rem;', $classes['leading-8']['css'] );

		$this->assertArrayHasKey( 'leading-10', $classes );
		$this->assertSame( 'line-height: 2.5rem;', $classes['leading-10']['css'] );
	}

	/**
	 * Test font weight classes contain all 9 weights.
	 */
	public function test_font_weight_classes(): void {
		$classes = ClassRegistry::get_font_weight_classes();

		$expected = array(
			'font-thin'       => '100',
			'font-extralight' => '200',
			'font-light'      => '300',
			'font-normal'     => '400',
			'font-medium'     => '500',
			'font-semibold'   => '600',
			'font-bold'       => '700',
			'font-extrabold'  => '800',
			'font-black'      => '900',
		);

		$this->assertCount( 9, $classes );

		foreach ( $expected as $name => $weight ) {
			$this->assertArrayHasKey( $name, $classes, "Missing font weight class: {$name}" );
			$this->assertSame( "font-weight: {$weight};", $classes[ $name ]['css'] );
			$this->assertSame( 'font-weight', $classes[ $name ]['category'] );
		}
	}

	/**
	 * Test text alignment classes.
	 */
	public function test_text_align_classes(): void {
		$classes = ClassRegistry::get_text_style_classes();

		$align_classes = array( 'text-left', 'text-center', 'text-right', 'text-justify', 'text-start', 'text-end' );
		foreach ( $align_classes as $name ) {
			$this->assertArrayHasKey( $name, $classes, "Missing text align class: {$name}" );
			$this->assertStringContainsString( 'text-align:', $classes[ $name ]['css'] );
			$this->assertSame( 'text-align', $classes[ $name ]['category'] );
		}
	}

	/**
	 * Test text style classes (transform, decoration, wrap, whitespace, tracking).
	 */
	public function test_text_style_classes(): void {
		$classes = ClassRegistry::get_text_style_classes();

		// Transform.
		$this->assertArrayHasKey( 'uppercase', $classes );
		$this->assertSame( 'text-transform: uppercase;', $classes['uppercase']['css'] );
		$this->assertSame( 'text-style', $classes['uppercase']['category'] );

		// Decoration.
		$this->assertArrayHasKey( 'underline', $classes );
		$this->assertSame( 'text-decoration-line: underline;', $classes['underline']['css'] );

		// Wrap.
		$this->assertArrayHasKey( 'text-nowrap', $classes );
		$this->assertSame( 'text-wrap: nowrap;', $classes['text-nowrap']['css'] );

		// Whitespace.
		$this->assertArrayHasKey( 'whitespace-nowrap', $classes );
		$this->assertSame( 'white-space: nowrap;', $classes['whitespace-nowrap']['css'] );

		// Tracking.
		$this->assertArrayHasKey( 'tracking-tight', $classes );
		$this->assertSame( 'letter-spacing: -0.025em;', $classes['tracking-tight']['css'] );
		$this->assertSame( 'tracking', $classes['tracking-tight']['category'] );
	}

	/**
	 * Test shadow classes contain all 8 variants.
	 */
	public function test_shadow_classes(): void {
		$classes = ClassRegistry::get_shadow_classes();

		$expected = array( 'shadow-2xs', 'shadow-xs', 'shadow-sm', 'shadow', 'shadow-md', 'shadow-lg', 'shadow-xl', 'shadow-2xl', 'shadow-inner', 'shadow-none' );
		// 10 outer shadows + 9 inset-shadow variants = 19 total.
		$this->assertCount( 19, $classes );

		foreach ( $expected as $name ) {
			$this->assertArrayHasKey( $name, $classes, "Missing shadow class: {$name}" );
			$this->assertStringContainsString( 'box-shadow:', $classes[ $name ]['css'] );
			$this->assertSame( 'shadow', $classes[ $name ]['category'] );
		}
	}

	/**
	 * Test opacity classes from 0 to 100 in steps of 5.
	 */
	public function test_opacity_classes(): void {
		$classes = ClassRegistry::get_opacity_classes();

		$this->assertCount( 21, $classes );

		$this->assertArrayHasKey( 'opacity-0', $classes );
		$this->assertSame( 'opacity: 0;', $classes['opacity-0']['css'] );

		$this->assertArrayHasKey( 'opacity-50', $classes );
		$this->assertSame( 'opacity: 0.5;', $classes['opacity-50']['css'] );

		$this->assertArrayHasKey( 'opacity-100', $classes );
		$this->assertSame( 'opacity: 1;', $classes['opacity-100']['css'] );

		$this->assertSame( 'opacity', $classes['opacity-50']['category'] );
	}

	/**
	 * Test overflow classes (base + x + y variants).
	 */
	public function test_overflow_classes(): void {
		$classes = ClassRegistry::get_overflow_classes();

		$this->assertCount( 15, $classes );

		$this->assertArrayHasKey( 'overflow-hidden', $classes );
		$this->assertSame( 'overflow: hidden;', $classes['overflow-hidden']['css'] );

		$this->assertArrayHasKey( 'overflow-x-auto', $classes );
		$this->assertSame( 'overflow-x: auto;', $classes['overflow-x-auto']['css'] );

		$this->assertArrayHasKey( 'overflow-y-scroll', $classes );
		$this->assertSame( 'overflow-y: scroll;', $classes['overflow-y-scroll']['css'] );

		$this->assertSame( 'overflow', $classes['overflow-hidden']['category'] );
	}

	/**
	 * Test position classes (keywords + inset + directional).
	 */
	public function test_position_classes(): void {
		$classes = ClassRegistry::get_position_classes();

		// Keywords. `absolute` and `fixed` also emit `width: auto` to override
		// Spectra's container full-width default so positioned elements shrink to fit.
		$keyword_css = array(
			'static'   => 'position: static;',
			'relative' => 'position: relative;',
			'absolute' => 'position: absolute; width: auto;',
			'fixed'    => 'position: fixed; width: auto;',
			'sticky'   => 'position: sticky;',
		);
		foreach ( $keyword_css as $keyword => $expected_css ) {
			$this->assertArrayHasKey( $keyword, $classes, "Missing position keyword: {$keyword}" );
			$this->assertSame( $expected_css, $classes[ $keyword ]['css'] );
		}

		// Inset.
		$this->assertArrayHasKey( 'inset-0', $classes );
		$this->assertSame( 'inset: 0;', $classes['inset-0']['css'] );

		$this->assertArrayHasKey( 'inset-x-0', $classes );
		$this->assertSame( 'right: 0; left: 0;', $classes['inset-x-0']['css'] );

		// Directional.
		$this->assertArrayHasKey( 'top-0', $classes );
		$this->assertSame( 'top: 0;', $classes['top-0']['css'] );

		$this->assertArrayHasKey( 'left-4', $classes );
		$this->assertSame( 'left: 1rem;', $classes['left-4']['css'] );

		$this->assertArrayHasKey( 'right-1/2', $classes );
		$this->assertSame( 'right: 50%;', $classes['right-1/2']['css'] );

		$this->assertSame( 'position', $classes['absolute']['category'] );
	}

	/**
	 * Test visibility classes.
	 */
	public function test_visibility_classes(): void {
		$classes = ClassRegistry::get_visibility_classes();

		$this->assertCount( 3, $classes );
		$this->assertArrayHasKey( 'visible', $classes );
		$this->assertSame( 'visibility: visible;', $classes['visible']['css'] );
		$this->assertArrayHasKey( 'invisible', $classes );
		$this->assertSame( 'visibility: hidden;', $classes['invisible']['css'] );
		$this->assertArrayHasKey( 'collapse', $classes );
		$this->assertSame( 'visibility', $classes['visible']['category'] );
	}

	/**
	 * Test cursor classes.
	 */
	public function test_cursor_classes(): void {
		$classes = ClassRegistry::get_cursor_classes();

		$this->assertCount( 9, $classes );

		$expected = array( 'cursor-auto', 'cursor-default', 'cursor-pointer', 'cursor-wait', 'cursor-text', 'cursor-move', 'cursor-not-allowed', 'cursor-grab', 'cursor-grabbing' );
		foreach ( $expected as $name ) {
			$this->assertArrayHasKey( $name, $classes, "Missing cursor class: {$name}" );
			$this->assertStringContainsString( 'cursor:', $classes[ $name ]['css'] );
		}

		$this->assertSame( 'cursor', $classes['cursor-pointer']['category'] );
	}

	/**
	 * Test list style classes.
	 */
	public function test_list_classes(): void {
		$classes = ClassRegistry::get_list_classes();

		$this->assertCount( 5, $classes );

		$this->assertArrayHasKey( 'list-none', $classes );
		$this->assertSame( 'list-style-type: none;', $classes['list-none']['css'] );

		$this->assertArrayHasKey( 'list-disc', $classes );
		$this->assertArrayHasKey( 'list-decimal', $classes );
		$this->assertArrayHasKey( 'list-inside', $classes );
		$this->assertSame( 'list-style-position: inside;', $classes['list-inside']['css'] );

		$this->assertSame( 'list-style', $classes['list-none']['category'] );
	}

	/**
	 * Test sizing rem scale for both width and height.
	 */
	public function test_sizing_rem_scale(): void {
		$classes = ClassRegistry::get_sizing_classes();

		// Width rem scale.
		$this->assertArrayHasKey( 'w-0', $classes );
		$this->assertSame( 'inline-size: 0;', $classes['w-0']['css'] );

		$this->assertArrayHasKey( 'w-4', $classes );
		$this->assertSame( 'inline-size: 1rem;', $classes['w-4']['css'] );

		$this->assertArrayHasKey( 'w-96', $classes );
		$this->assertSame( 'inline-size: 24rem;', $classes['w-96']['css'] );

		// Height rem scale.
		$this->assertArrayHasKey( 'h-0', $classes );
		$this->assertSame( 'block-size: 0;', $classes['h-0']['css'] );

		$this->assertArrayHasKey( 'h-4', $classes );
		$this->assertSame( 'block-size: 1rem;', $classes['h-4']['css'] );

		$this->assertArrayHasKey( 'h-96', $classes );
		$this->assertSame( 'block-size: 24rem;', $classes['h-96']['css'] );
	}

	/**
	 * Test max-width token classes.
	 */
	public function test_sizing_max_width_tokens(): void {
		$classes = ClassRegistry::get_sizing_classes();

		$tokens = array(
			'max-w-xs'    => '20rem',
			'max-w-sm'    => '24rem',
			'max-w-md'    => '28rem',
			'max-w-lg'    => '32rem',
			'max-w-xl'    => '36rem',
			'max-w-2xl'   => '42rem',
			'max-w-3xl'   => '48rem',
			'max-w-4xl'   => '56rem',
			'max-w-5xl'   => '64rem',
			'max-w-6xl'   => '72rem',
			'max-w-7xl'   => '80rem',
			'max-w-prose' => '65ch',
		);

		foreach ( $tokens as $name => $value ) {
			$this->assertArrayHasKey( $name, $classes, "Missing max-width token: {$name}" );
			$this->assertSame( "max-inline-size: {$value};", $classes[ $name ]['css'] );
		}

		// Min-width helpers.
		$this->assertArrayHasKey( 'min-w-0', $classes );
		$this->assertArrayHasKey( 'min-w-fit', $classes );
		$this->assertArrayHasKey( 'min-h-0', $classes );
		$this->assertArrayHasKey( 'min-h-fit', $classes );
	}

	/**
	 * Test layout flex grow/shrink and order classes.
	 */
	public function test_layout_flex_grow_shrink(): void {
		$classes = ClassRegistry::get_layout_classes();

		// Flex shorthand.
		$this->assertArrayHasKey( 'flex-1', $classes );
		$this->assertSame( 'flex: 1 1 0%;', $classes['flex-1']['css'] );

		$this->assertArrayHasKey( 'flex-auto', $classes );
		$this->assertSame( 'flex: 1 1 auto;', $classes['flex-auto']['css'] );

		$this->assertArrayHasKey( 'flex-none', $classes );
		$this->assertSame( 'flex: none;', $classes['flex-none']['css'] );

		// Grow / Shrink.
		$this->assertArrayHasKey( 'grow', $classes );
		$this->assertSame( 'flex-grow: 1;', $classes['grow']['css'] );

		$this->assertArrayHasKey( 'shrink-0', $classes );
		$this->assertSame( 'flex-shrink: 0;', $classes['shrink-0']['css'] );

		// Order.
		$this->assertArrayHasKey( 'order-first', $classes );
		$this->assertSame( 'order: -9999;', $classes['order-first']['css'] );

		$this->assertArrayHasKey( 'order-1', $classes );
		$this->assertSame( 'order: 1;', $classes['order-1']['css'] );

		$this->assertArrayHasKey( 'order-12', $classes );
		$this->assertSame( 'order: 12;', $classes['order-12']['css'] );
	}

	// ─────────────────────────────────────────────────────────────
	// SPACING CLASSES
	// ─────────────────────────────────────────────────────────────

	/**
	 * Test SG token spacing classes use var(--spectra-space-*).
	 */
	public function test_spacing_sg_tokens(): void {
		$classes = ClassRegistry::get_spacing_classes();

		// p-xs through p-2xl should use SG tokens.
		$sg_sizes = array( 'xs', 'sm', 'md', 'lg', 'xl', '2xl' );
		foreach ( $sg_sizes as $size ) {
			$key = "p-{$size}";
			$this->assertArrayHasKey( $key, $classes, "Missing SG spacing class: {$key}" );
			$this->assertStringContainsString( "var(--spectra-space-{$size})", $classes[ $key ]['css'] );
		}
	}

	/**
	 * Test fixed Tailwind rem spacing classes.
	 */
	public function test_spacing_fixed_rem_scale(): void {
		$classes = ClassRegistry::get_spacing_classes();

		// p-4 = padding: 1rem;.
		$this->assertArrayHasKey( 'p-4', $classes );
		$this->assertSame( 'padding: 1rem;', $classes['p-4']['css'] );

		// p-0 = padding: 0;.
		$this->assertArrayHasKey( 'p-0', $classes );
		$this->assertSame( 'padding: 0;', $classes['p-0']['css'] );

		// p-px = padding: 1px;.
		$this->assertArrayHasKey( 'p-px', $classes );
		$this->assertSame( 'padding: 1px;', $classes['p-px']['css'] );

		// m-8 = margin: 2rem;.
		$this->assertArrayHasKey( 'm-8', $classes );
		$this->assertSame( 'margin: 2rem;', $classes['m-8']['css'] );
	}

	/**
	 * Test that all spacing directions are generated.
	 */
	public function test_spacing_all_directions(): void {
		$classes = ClassRegistry::get_spacing_classes();

		// Padding: p, pt, pr, pb, pl, px, py.
		$padding_prefixes = array( 'p', 'pt', 'pr', 'pb', 'pl', 'px', 'py' );
		foreach ( $padding_prefixes as $prefix ) {
			$this->assertArrayHasKey( "{$prefix}-4", $classes, "Missing padding direction: {$prefix}-4" );
		}

		// Margin: m, mt, mr, mb, ml, mx, my.
		$margin_prefixes = array( 'm', 'mt', 'mr', 'mb', 'ml', 'mx', 'my' );
		foreach ( $margin_prefixes as $prefix ) {
			$this->assertArrayHasKey( "{$prefix}-4", $classes, "Missing margin direction: {$prefix}-4" );
		}

		// Gap: gap, gap-x, gap-y.
		$gap_prefixes = array( 'gap', 'gap-x', 'gap-y' );
		foreach ( $gap_prefixes as $prefix ) {
			$this->assertArrayHasKey( "{$prefix}-4", $classes, "Missing gap direction: {$prefix}-4" );
		}

		// Verify multi-property: px-4 sets both padding-right and padding-left.
		$this->assertStringContainsString( 'padding-right: 1rem;', $classes['px-4']['css'] );
		$this->assertStringContainsString( 'padding-left: 1rem;', $classes['px-4']['css'] );
	}

	// ─────────────────────────────────────────────────────────────
	// FONT CLASSES
	// ─────────────────────────────────────────────────────────────

	/**
	 * Test heading font classes use SG heading tokens.
	 */
	public function test_font_heading_classes(): void {
		$classes = ClassRegistry::get_font_classes();

		for ( $i = 1; $i <= 6; $i++ ) {
			$key = "text-heading-{$i}";
			$this->assertArrayHasKey( $key, $classes );
			$this->assertSame( "font-size: var(--spectra-heading-{$i});", $classes[ $key ]['css'] );
			$this->assertSame( 'typography', $classes[ $key ]['category'] );
		}
	}

	/**
	 * Test body text size classes use SG text tokens.
	 */
	public function test_font_body_classes(): void {
		$classes = ClassRegistry::get_font_classes();

		// Each entry: [ token, fallback, line-height ] — matches the implementation's
		// Tailwind-parity paired font-size + line-height output.
		$expected = array(
			'text-xs'   => array( 'text-xs', '0.75rem', '1rem' ),
			'text-sm'   => array( 'text-sm', '0.875rem', '1.25rem' ),
			'text-base' => array( 'text-md', '1rem', '1.5rem' ),
			'text-lg'   => array( 'text-lg', '1.125rem', '1.75rem' ),
			'text-xl'   => array( 'text-xl', '1.25rem', '1.75rem' ),
			'text-2xl'  => array( 'text-2xl', '1.5rem', '2rem' ),
		);

		foreach ( $expected as $class_name => list( $token, $fallback, $lh ) ) {
			$this->assertArrayHasKey( $class_name, $classes );
			$this->assertSame(
				"font-size: var(--spectra-{$token}, {$fallback}); line-height: {$lh};",
				$classes[ $class_name ]['css']
			);
		}
	}

	// ─────────────────────────────────────────────────────────────
	// COLOR CLASSES (without SG Engine)
	// ─────────────────────────────────────────────────────────────

	/**
	 * Test common color classes (white, black, transparent) are always generated.
	 */
	public function test_common_color_classes(): void {
		$classes = ClassRegistry::get_color_classes();

		// White.
		$this->assertArrayHasKey( 'bg-white', $classes );
		$this->assertSame( 'background: #ffffff;', $classes['bg-white']['css'] );
		$this->assertArrayHasKey( 'text-white', $classes );
		$this->assertSame( 'color: #ffffff; -webkit-text-fill-color: #ffffff;', $classes['text-white']['css'] );
		$this->assertArrayHasKey( 'border-white', $classes );

		// Black.
		$this->assertArrayHasKey( 'bg-black', $classes );
		$this->assertSame( 'background: #000000;', $classes['bg-black']['css'] );

		// Transparent.
		$this->assertArrayHasKey( 'bg-transparent', $classes );
		$this->assertSame( 'background: transparent;', $classes['bg-transparent']['css'] );

		// Border position variants.
		$this->assertArrayHasKey( 'border-t-white', $classes );
		$this->assertArrayHasKey( 'border-x-black', $classes );
		$this->assertArrayHasKey( 'border-y-transparent', $classes );
	}

	// ─────────────────────────────────────────────────────────────
	// COLOR CLASSES (with mocked SG config via reflection)
	// ─────────────────────────────────────────────────────────────

	/**
	 * Test chromatic color generation with a mocked SG config.
	 */
	public function test_chromatic_color_classes_with_mock_config(): void {
		$mock_config = array(
			'chromatics' => array(
				1 => array(
					'hex'  => '#6431f6',
					'name' => 'Primary',
				),
				2 => array(
					'hex'  => '#7345f7',
					'name' => 'Secondary',
				),
			),
		);

		ClassRegistry::invalidate_cache();
		$classes = $this->call_color_classes_with_config( $mock_config );

		// Chromatic 1 → slug 'primary', shade 600 (the seed, SG index 7).
		$this->assertArrayHasKey( 'bg-primary-600', $classes );
		$this->assertSame( 'background: var(--spectra-primary);', $classes['bg-primary-600']['css'] );

		$this->assertArrayHasKey( 'text-primary-600', $classes );
		$this->assertSame( 'color: var(--spectra-primary); -webkit-text-fill-color: var(--spectra-primary);', $classes['text-primary-600']['css'] );

		// Chromatic 2 → slug 'secondary', same single seed shade.
		$this->assertArrayHasKey( 'border-secondary-600', $classes );
		$this->assertSame( 'border-color: var(--spectra-secondary);', $classes['border-secondary-600']['css'] );
	}

	/**
	 * Test shade mapping: only the seed shade (600 ← chromaticN-7) exists —
	 * ramps are no longer generated.
	 */
	public function test_chromatic_shade_mapping(): void {
		$mock_config = array(
			'chromatics' => array(
				1 => array(
					'hex'  => '#3b82f6',
					'name' => 'Primary',
				),
			),
		);

		$classes = $this->call_color_classes_with_config( $mock_config );

		$this->assertArrayHasKey( 'bg-primary-600', $classes );

		// Ramp shades must NOT be emitted (their vars no longer exist).
		$removed_shades = array( '50', '100', '200', '300', '400', '500', '700', '800', '900', '950' );
		foreach ( $removed_shades as $shade ) {
			$this->assertArrayNotHasKey( "bg-primary-{$shade}", $classes, "Removed chromatic shade emitted: bg-primary-{$shade}" );
		}
	}

	/**
	 * Test neutral (base) shade mapping: 6 stored stops + 1 gap-fill = 7 slots.
	 * The interpolated stops (old base-300/base-800 and the 400/900 gap-fills)
	 * are no longer generated.
	 */
	public function test_neutral_shade_mapping(): void {
		// Even without chromatics, neutrals should generate.
		$classes = $this->call_color_classes_with_config( array( 'chromatics' => array() ) );

		// Direct mappings — the six stored neutral roles.
		$direct_shades = array( '50', '100', '200', '500', '600', '950' );
		foreach ( $direct_shades as $shade ) {
			$this->assertArrayHasKey( "bg-base-{$shade}", $classes, "Missing neutral direct shade: bg-base-{$shade}" );
		}

		// Gap-fill: 700 ← neutral-5 (the only remaining fill).
		$this->assertArrayHasKey( 'bg-base-700', $classes );
		$this->assertSame( 'background: var(--spectra-neutral-5);', $classes['bg-base-700']['css'] );

		// Removed slots (interpolated neutral-3/neutral-6 based).
		foreach ( array( '300', '400', '800', '900' ) as $shade ) {
			$this->assertArrayNotHasKey( "bg-base-{$shade}", $classes, "Removed neutral shade emitted: bg-base-{$shade}" );
		}

		// Total base bg-* classes: 7 slots.
		$base_bg_count = count(
			array_filter(
				array_keys( $classes ),
				function ( $k ) {
					return 0 === strpos( $k, 'bg-base-' ) && false === strpos( $k, '/' );
				}
			)
		);
		$this->assertSame( 7, $base_bg_count );
	}

	/**
	 * Test opacity variants use color-mix().
	 */
	public function test_opacity_variants(): void {
		$mock_config = array(
			'chromatics' => array(
				1 => array(
					'hex'  => '#3b82f6',
					'name' => 'Primary',
				),
			),
		);

		$classes = $this->call_color_classes_with_config( $mock_config );

		// bg-primary-600/50 should use color-mix at 50%.
		$this->assertArrayHasKey( 'bg-primary-600/50', $classes );
		$this->assertStringContainsString( 'color-mix(in srgb,', $classes['bg-primary-600/50']['css'] );
		$this->assertStringContainsString( '50%', $classes['bg-primary-600/50']['css'] );
		$this->assertStringContainsString( 'transparent', $classes['bg-primary-600/50']['css'] );

		// All 9 opacity steps should exist.
		$opacity_steps = array( 10, 20, 30, 40, 50, 60, 70, 80, 90 );
		foreach ( $opacity_steps as $pct ) {
			$this->assertArrayHasKey( "bg-primary-600/{$pct}", $classes, "Missing opacity class: bg-primary-600/{$pct}" );
		}
	}

	/**
	 * Test classSlug determines family prefix.
	 */
	public function test_class_slug_determines_prefix(): void {
		$mock_config = array(
			'chromatics' => array(
				3 => array(
					'hex'       => '#10b981',
					'name'      => 'Success',
					'classSlug' => 'brand',
				),
			),
		);

		$classes = $this->call_color_classes_with_config( $mock_config );

		// Should use classSlug 'brand', not the name-derived 'success'.
		$this->assertArrayHasKey( 'bg-brand-600', $classes );
		$this->assertArrayNotHasKey( 'bg-success-600', $classes );
	}

	/**
	 * Test classSlug fallback for custom chromatics uses kebab-cased name.
	 */
	public function test_class_slug_fallback_to_name(): void {
		$mock_config = array(
			'chromatics' => array(
				4 => array(
					'hex'  => '#f59e0b',
					'name' => 'Warm Accent',
				),
			),
		);

		$classes = $this->call_color_classes_with_config( $mock_config );

		// sanitize_title('Warm Accent') = 'warm-accent'.
		$this->assertArrayHasKey( 'bg-warm-accent-600', $classes );
	}

	/**
	 * Test default slugs: index 1 = 'primary', index 2 = 'secondary'.
	 */
	public function test_default_slugs_for_first_two(): void {
		$mock_config = array(
			'chromatics' => array(
				1 => array(
					'hex'  => '#000000',
					'name' => 'My Main Color',
				),
				2 => array(
					'hex'  => '#111111',
					'name' => 'My Other Color',
				),
			),
		);

		$classes = $this->call_color_classes_with_config( $mock_config );

		// Index 1 always uses 'primary' regardless of name.
		$this->assertArrayHasKey( 'bg-primary-600', $classes );
		$this->assertArrayNotHasKey( 'bg-my-main-color-600', $classes );

		// Index 2 always uses 'secondary' regardless of name.
		$this->assertArrayHasKey( 'bg-secondary-600', $classes );
		$this->assertArrayNotHasKey( 'bg-my-other-color-600', $classes );
	}

	/**
	 * Test only configured chromatics generate classes.
	 */
	public function test_single_chromatic_only(): void {
		$mock_config = array(
			'chromatics' => array(
				1 => array(
					'hex'  => '#3b82f6',
					'name' => 'Primary',
				),
			),
		);

		$classes = $this->call_color_classes_with_config( $mock_config );

		$this->assertArrayHasKey( 'bg-primary-600', $classes );
		$this->assertArrayNotHasKey( 'bg-secondary-600', $classes );
	}

	// ─────────────────────────────────────────────────────────────
	// GRADIENT STOPS (from-/via-/to-)
	// ─────────────────────────────────────────────────────────────

	/**
	 * `bg-gradient-to-*` references the shared `--tw-gradient-stops` custom prop.
	 *
	 * @return void
	 */
	public function test_bg_gradient_directions_reference_stops_var(): void {
		$extended = ClassRegistry::get_extended_classes();

		$this->assertArrayHasKey( 'bg-gradient-to-t', $extended );
		$this->assertStringContainsString( 'linear-gradient(to top,', $extended['bg-gradient-to-t']['css'] );
		$this->assertStringContainsString( 'var(--tw-gradient-stops,', $extended['bg-gradient-to-t']['css'] );
		$this->assertStringContainsString( 'var(--tw-gradient-from, transparent)', $extended['bg-gradient-to-t']['css'] );
		$this->assertStringContainsString( 'var(--tw-gradient-to, transparent)', $extended['bg-gradient-to-t']['css'] );
	}

	/**
	 * `from-{palette}` sets both `--tw-gradient-from` and a 2-stop `--tw-gradient-stops`.
	 *
	 * @return void
	 */
	public function test_from_palette_writes_from_var_and_stops(): void {
		$mock_config = array(
			'chromatics' => array(
				1 => array(
					'hex'  => '#6431f6',
					'name' => 'Primary',
				),
			),
		);

		$classes = $this->call_color_classes_with_config( $mock_config );

		$this->assertArrayHasKey( 'from-primary-600', $classes );
		$css = $classes['from-primary-600']['css'];
		$this->assertStringContainsString( '--tw-gradient-from: var(--spectra-primary);', $css );
		$this->assertStringContainsString( '--tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);', $css );
	}

	/**
	 * `via-{palette}` overrides `--tw-gradient-stops` with the 3-stop form.
	 *
	 * @return void
	 */
	public function test_via_palette_writes_three_stop_override(): void {
		$mock_config = array(
			'chromatics' => array(
				1 => array(
					'hex'  => '#6431f6',
					'name' => 'Primary',
				),
			),
		);

		$classes = $this->call_color_classes_with_config( $mock_config );

		$this->assertArrayHasKey( 'via-primary-600', $classes );
		$css = $classes['via-primary-600']['css'];
		$this->assertStringContainsString( '--tw-gradient-via: var(--spectra-primary);', $css );
		$this->assertStringContainsString( 'var(--tw-gradient-from, transparent), var(--tw-gradient-via), var(--tw-gradient-to, transparent)', $css );
	}

	/**
	 * `to-{palette}` writes only `--tw-gradient-to`.
	 *
	 * @return void
	 */
	public function test_to_palette_writes_to_var_only(): void {
		$mock_config = array(
			'chromatics' => array(
				1 => array(
					'hex'  => '#6431f6',
					'name' => 'Primary',
				),
			),
		);

		$classes = $this->call_color_classes_with_config( $mock_config );

		$this->assertArrayHasKey( 'to-primary-600', $classes );
		$css = $classes['to-primary-600']['css'];
		$this->assertStringContainsString( '--tw-gradient-to: var(--spectra-primary);', $css );
		$this->assertStringNotContainsString( '--tw-gradient-from', $css );
		$this->assertStringNotContainsString( '--tw-gradient-stops', $css );
	}

	/**
	 * Common colors (white/black/transparent) also participate as gradient stops.
	 *
	 * @return void
	 */
	public function test_common_colors_as_gradient_stops(): void {
		$classes = $this->call_color_classes_with_config( array( 'chromatics' => array() ) );

		$this->assertArrayHasKey( 'from-white', $classes );
		$this->assertStringContainsString( '--tw-gradient-from: #ffffff;', $classes['from-white']['css'] );

		$this->assertArrayHasKey( 'to-black', $classes );
		$this->assertStringContainsString( '--tw-gradient-to: #000000;', $classes['to-black']['css'] );

		$this->assertArrayHasKey( 'via-transparent', $classes );
		$this->assertStringContainsString( '--tw-gradient-via: transparent;', $classes['via-transparent']['css'] );
	}

	/**
	 * Base (neutral) shades generate gradient stop utilities.
	 *
	 * @return void
	 */
	public function test_base_palette_participates_in_gradient_stops(): void {
		$classes = $this->call_color_classes_with_config( array( 'chromatics' => array() ) );

		$this->assertArrayHasKey( 'from-base-50', $classes );
		$this->assertStringContainsString( '--tw-gradient-from: var(--spectra-neutral-0);', $classes['from-base-50']['css'] );

		$this->assertArrayHasKey( 'to-base-700', $classes );
		// Base-700 is gap-filled from neutral-5.
		$this->assertStringContainsString( '--tw-gradient-to: var(--spectra-neutral-5);', $classes['to-base-700']['css'] );
	}

	/**
	 * Emission order: every `from-*` precedes every `via-*` which precedes every `to-*`.
	 *
	 * Source order drives cascade when specificity is equal. Via's 3-stop
	 * override of `--tw-gradient-stops` must land AFTER from's 2-stop
	 * definition for mixed-class elements to resolve with three stops.
	 *
	 * @return void
	 */
	public function test_gradient_stop_emission_order_from_via_to(): void {
		$classes = $this->call_color_classes_with_config( array( 'chromatics' => array() ) );
		$names   = array_keys( $classes );

		$last_from = -1;
		$first_via = PHP_INT_MAX;
		$last_via  = -1;
		$first_to  = PHP_INT_MAX;

		foreach ( $names as $i => $name ) {
			if ( 0 === strpos( $name, 'from-' ) ) {
				$last_from = $i;
			} elseif ( 0 === strpos( $name, 'via-' ) ) {
				$first_via = min( $first_via, $i );
				$last_via  = $i;
			} elseif ( 0 === strpos( $name, 'to-' ) ) {
				$first_to = min( $first_to, $i );
			}
		}

		$this->assertGreaterThan( 0, $last_from );
		$this->assertLessThan( $first_via, $last_from );
		$this->assertLessThan( $first_to, $last_via );
	}

	// ─────────────────────────────────────────────────────────────
	// PUBLIC API — MERGE, FLAT, CHEATSHEET, GROUPED
	// ─────────────────────────────────────────────────────────────

	/**
	 * Test get_all_classes() returns non-empty merged result.
	 */
	public function test_get_all_classes_returns_merged(): void {
		$all = ClassRegistry::get_all_classes();

		$this->assertIsArray( $all );
		$this->assertNotEmpty( $all );

		// Should contain static classes.
		$this->assertArrayHasKey( 'block', $all );
		$this->assertArrayHasKey( 'rounded-md', $all );

		// Should contain dynamic classes (at least spacing/fonts).
		$this->assertArrayHasKey( 'p-4', $all );
		$this->assertArrayHasKey( 'text-heading-1', $all );
	}

	/**
	 * Test get_flat_classes() returns name => CSS string pairs.
	 */
	public function test_get_flat_classes(): void {
		$flat = ClassRegistry::get_flat_classes();

		$this->assertIsArray( $flat );
		$this->assertNotEmpty( $flat );

		// Every value should be a string (CSS).
		foreach ( $flat as $name => $css ) {
			$this->assertIsString( $css, "Flat class value for '{$name}' is not a string" );
			$this->assertNotEmpty( $css );
		}

		// Spot check.
		$this->assertSame( 'display: flex;', $flat['flex'] );
	}

	/**
	 * Test get_cheatsheet_data() returns flat indexed array.
	 */
	public function test_get_cheatsheet_data(): void {
		$data = ClassRegistry::get_cheatsheet_data();

		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );

		// Should be numerically indexed.
		$this->assertArrayHasKey( 0, $data );

		// Each entry should have required fields.
		$first = $data[0];
		$this->assertArrayHasKey( 'name', $first );
		$this->assertArrayHasKey( 'css', $first );
		$this->assertArrayHasKey( 'category', $first );
		$this->assertArrayHasKey( 'tags', $first );
	}

	/**
	 * Test get_grouped_options_for_editor() returns React Select format.
	 */
	public function test_get_grouped_options_for_editor(): void {
		$groups = ClassRegistry::get_grouped_options_for_editor();

		$this->assertIsArray( $groups );
		$this->assertNotEmpty( $groups );

		// Each group should have 'label' and 'options'.
		foreach ( $groups as $group ) {
			$this->assertArrayHasKey( 'label', $group );
			$this->assertArrayHasKey( 'options', $group );
			$this->assertIsArray( $group['options'] );

			// Each option should have 'value' and 'label'.
			if ( ! empty( $group['options'] ) ) {
				$this->assertArrayHasKey( 'value', $group['options'][0] );
				$this->assertArrayHasKey( 'label', $group['options'][0] );
			}
		}
	}

	/**
	 * Test each class entry has required fields (css, title, description, category, tags).
	 */
	public function test_all_classes_have_required_fields(): void {
		$all = ClassRegistry::get_all_classes();

		foreach ( $all as $name => $data ) {
			$this->assertArrayHasKey( 'css', $data, "Class '{$name}' missing 'css' field" );
			$this->assertArrayHasKey( 'title', $data, "Class '{$name}' missing 'title' field" );
			$this->assertArrayHasKey( 'description', $data, "Class '{$name}' missing 'description' field" );
			$this->assertArrayHasKey( 'category', $data, "Class '{$name}' missing 'category' field" );
			$this->assertArrayHasKey( 'tags', $data, "Class '{$name}' missing 'tags' field" );
			$this->assertIsArray( $data['tags'], "Class '{$name}' tags should be an array" );
		}
	}

	/**
	 * Test invalidate_cache() clears in-memory cache.
	 */
	public function test_invalidate_cache(): void {
		// Populate cache.
		$first = ClassRegistry::get_all_classes();
		$this->assertNotEmpty( $first );

		// Invalidate.
		ClassRegistry::invalidate_cache();

		// Should regenerate (not return stale data).
		$second = ClassRegistry::get_all_classes();
		$this->assertNotEmpty( $second );

		// Content should be the same (same config).
		$this->assertSame( count( $first ), count( $second ) );
	}

	// ─────────────────────────────────────────────────────────────
	// PRIVATE HELPERS (via reflection)
	// ─────────────────────────────────────────────────────────────

	/**
	 * Test get_chromatic_slug() with various inputs.
	 */
	public function test_get_chromatic_slug(): void {
		$method = $this->get_private_method( 'get_chromatic_slug' );

		// Index 1 without classSlug → 'primary'.
		$this->assertSame( 'primary', $method->invoke( null, 1, array( 'name' => 'My Main' ) ) );

		// Index 2 without classSlug → 'secondary'.
		$this->assertSame( 'secondary', $method->invoke( null, 2, array( 'name' => 'Other' ) ) );

		// Index 3 with name → kebab-cased name.
		$this->assertSame( 'accent', $method->invoke( null, 3, array( 'name' => 'Accent' ) ) );

		// Index 4 with classSlug → uses classSlug.
		$this->assertSame(
			'brand',
			$method->invoke(
				null,
				4,
				array(
					'name'      => 'Something',
					'classSlug' => 'brand',
				)
			)
		);

		// classSlug overrides default for index 1.
		$this->assertSame(
			'custom',
			$method->invoke(
				null,
				1,
				array(
					'name'      => 'Primary',
					'classSlug' => 'custom',
				)
			)
		);

		// Index 5 without name → fallback.
		$this->assertSame( 'color-5', $method->invoke( null, 5, array() ) );
	}

	// ─────────────────────────────────────────────────────────────
	// HELPERS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Call get_color_classes() with a mocked SG config via reflection.
	 *
	 * Temporarily overrides the get_sg_config() return value by setting
	 * the result of the parent call to use a mock config array.
	 *
	 * @param array $config Mock SG config.
	 * @return array Generated color classes.
	 */
	private function call_color_classes_with_config( array $config ): array {
		// Anonymous subclass overrides get_sg_config() to return mock data.
		// get_color_classes() uses static::get_sg_config() for late-static binding.
		$testable = new class() extends ClassRegistry {
			/** @var array */
			public static $test_config = array();

			/**
			 * Override get_sg_config to return test data.
			 *
			 * @since x.x.x
			 *
			 * @return array
			 */
			protected static function get_sg_config(): array {
				return self::$test_config;
			}
		};

		$class_name               = get_class( $testable );
		$class_name::$test_config = $config;

		return $class_name::get_color_classes();
	}

	/**
	 * Get a private/protected method for testing.
	 *
	 * @param string $method_name Method name.
	 * @return ReflectionMethod
	 */
	private function get_private_method( string $method_name ): ReflectionMethod {
		$reflection = new ReflectionClass( ClassRegistry::class );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method;
	}
}
