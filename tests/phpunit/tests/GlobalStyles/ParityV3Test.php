<?php
/**
 * Tests for Tailwind v3 parity utility presets added via
 * ClassRegistry::get_parity_v3_classes().
 *
 * Spot-checks representative classes from each new family are present in
 * the merged registry with non-empty CSS values.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\ClassRegistry;
use WP_UnitTestCase;

/**
 * ParityV3Test test case.
 *
 * @since x.x.x
 */
class ParityV3Test extends WP_UnitTestCase {

	/**
	 * Reset caches before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		ClassRegistry::invalidate_cache();
	}

	/**
	 * Expected parity classes grouped by family.
	 *
	 * Each entry: [ class_name, substring expected to appear in its CSS ].
	 *
	 * @return array<int, array{0: string, 1: string}>
	 */
	public function provide_parity_classes(): array {
		return array(
			// Text decoration.
			array( 'underline-offset-4', 'text-underline-offset: 4px;' ),
			array( 'underline-offset-auto', 'text-underline-offset: auto;' ),
			array( 'decoration-wavy', 'text-decoration-style: wavy;' ),
			array( 'decoration-2', 'text-decoration-thickness: 2px;' ),
			array( 'decoration-from-font', 'text-decoration-thickness: from-font;' ),

			// Vertical align.
			array( 'align-baseline', 'vertical-align: baseline;' ),
			array( 'align-super', 'vertical-align: super;' ),

			// Tables.
			array( 'table-fixed', 'table-layout: fixed;' ),
			array( 'border-collapse', 'border-collapse: collapse;' ),
			array( 'caption-top', 'caption-side: top;' ),
			array( 'border-spacing-4', 'border-spacing: 1rem;' ),

			// Screen readers.
			array( 'sr-only', 'position: absolute;' ),
			array( 'not-sr-only', 'position: static;' ),

			// Appearance.
			array( 'appearance-none', 'appearance: none;' ),

			// Resize.
			array( 'resize-y', 'resize: vertical;' ),
			array( 'resize', 'resize: both;' ),

			// Scroll.
			array( 'scroll-smooth', 'scroll-behavior: smooth;' ),
			array( 'snap-x', 'scroll-snap-type: x mandatory;' ),
			array( 'snap-center', 'scroll-snap-align: center;' ),
			array( 'snap-always', 'scroll-snap-stop: always;' ),
			array( 'scroll-m-4', 'scroll-margin: 1rem;' ),
			array( 'scroll-pt-2', 'scroll-padding-top: 0.5rem;' ),
			array( 'overscroll-contain', 'overscroll-behavior: contain;' ),
			array( 'overscroll-x-none', 'overscroll-behavior-x: none;' ),

			// Touch.
			array( 'touch-manipulation', 'touch-action: manipulation;' ),
			array( 'touch-pan-y', 'touch-action: pan-y;' ),

			// User select / pointer / will-change.
			array( 'select-none', 'user-select: none;' ),
			array( 'pointer-events-auto', 'pointer-events: auto;' ),
			array( 'will-change-transform', 'will-change: transform;' ),
			array( 'will-change-scroll', 'will-change: scroll-position;' ),

			// Text overflow / word break / font smoothing.
			array( 'text-ellipsis', 'text-overflow: ellipsis;' ),
			array( 'break-words', 'overflow-wrap: break-word;' ),
			array( 'break-all', 'word-break: break-all;' ),
			array( 'antialiased', '-webkit-font-smoothing: antialiased;' ),

			// Font variant numeric.
			array( 'tabular-nums', 'font-variant-numeric: tabular-nums;' ),
			array( 'diagonal-fractions', 'font-variant-numeric: diagonal-fractions;' ),

			// Backdrop filters.
			array( 'backdrop-brightness-50', 'brightness(50%)' ),
			array( 'backdrop-contrast-125', 'contrast(125%)' ),
			array( 'backdrop-grayscale', 'grayscale(100%)' ),
			array( 'backdrop-hue-rotate-90', 'hue-rotate(90deg)' ),
			array( '-backdrop-hue-rotate-30', 'hue-rotate(-30deg)' ),
			array( 'backdrop-invert', 'invert(100%)' ),
			array( 'backdrop-opacity-50', 'opacity(50%)' ),
			array( 'backdrop-saturate-150', 'saturate(150%)' ),
			array( 'backdrop-sepia', 'sepia(100%)' ),

			// Columns.
			array( 'columns-3', 'columns: 3;' ),
			array( 'columns-auto', 'columns: auto;' ),
			array( 'columns-md', 'columns: 28rem;' ),
			array( 'break-before-page', 'break-before: page;' ),
			array( 'break-after-column', 'break-after: column;' ),
			array( 'break-inside-avoid', 'break-inside: avoid;' ),

			// Grid completeness.
			array( 'grid-flow-col', 'grid-auto-flow: column;' ),
			array( 'grid-flow-row-dense', 'grid-auto-flow: row dense;' ),
			array( 'auto-cols-fr', 'grid-auto-columns: minmax(0, 1fr);' ),
			array( 'auto-rows-max', 'grid-auto-rows: max-content;' ),
			array( 'justify-self-auto', 'justify-self: auto;' ),
			array( 'place-items-center', 'place-items: center;' ),
			array( 'place-content-between', 'place-content: space-between;' ),
			array( 'place-self-stretch', 'place-self: stretch;' ),

			// Divide style.
			array( 'divide-dashed', 'border-style: dashed;' ),
			array( 'divide-none', 'border-style: none;' ),
		);
	}

	/**
	 * Each representative parity class exists in the merged registry with
	 * the expected CSS substring, required metadata fields, and non-empty
	 * css value.
	 *
	 * @dataProvider provide_parity_classes
	 *
	 * @param string $class_name    Class name to look up.
	 * @param string $css_substring Expected substring in the CSS.
	 * @return void
	 */
	public function test_parity_class_exists_with_expected_css( string $class_name, string $css_substring ): void {
		$all = ClassRegistry::get_all_classes();

		$this->assertArrayHasKey( $class_name, $all, "Missing parity class: {$class_name}" );

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
	 * get_parity_v3_classes() returns a non-empty associative array and
	 * every entry satisfies the registry entry shape.
	 *
	 * @return void
	 */
	public function test_get_parity_v3_classes_shape(): void {
		$classes = ClassRegistry::get_parity_v3_classes();

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

	/**
	 * Divide style utilities use the same `&` nesting pattern as existing
	 * divide-x/divide-y utilities (cascade-equivalent across the family).
	 *
	 * @return void
	 */
	public function test_divide_style_uses_sibling_selector_pattern(): void {
		$all = ClassRegistry::get_all_classes();

		$this->assertArrayHasKey( 'divide-solid', $all );
		$this->assertStringContainsString( '& > :not([hidden]) ~ :not([hidden])', $all['divide-solid']['css'] );
		$this->assertStringContainsString( 'border-style: solid;', $all['divide-solid']['css'] );
	}

	/**
	 * Backdrop filter utilities emit both standard and -webkit- prefixed
	 * declarations for Safari support.
	 *
	 * @return void
	 */
	public function test_backdrop_filter_emits_webkit_prefix(): void {
		$all = ClassRegistry::get_all_classes();

		$this->assertArrayHasKey( 'backdrop-brightness-100', $all );
		$css = $all['backdrop-brightness-100']['css'];
		$this->assertStringContainsString( 'backdrop-filter:', $css );
		$this->assertStringContainsString( '-webkit-backdrop-filter:', $css );
	}
}
