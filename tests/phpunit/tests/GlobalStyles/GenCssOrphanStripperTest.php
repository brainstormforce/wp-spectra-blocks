<?php
/**
 * Tests for the per-page Gen CSS orphan-selector stripper.
 *
 * CSS rules written to the `spectra_blocks_pro_gs_user_css` post meta whose
 * selectors reduce to `.gc-spectra-*` tokens must be stripped, while rules with
 * live selectors (or mixed selector lists) must pass through intact.
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\GenCssOrphanStripper;
use WP_UnitTestCase;

/**
 * GenCssOrphanStripperTest test case.
 *
 * @since x.x.x
 */
class GenCssOrphanStripperTest extends WP_UnitTestCase {

	/**
	 * Rules whose selector is entirely `.gc-spectra-*` are removed.
	 *
	 * @return void
	 */
	public function test_strips_pure_orphan_rule(): void {
		$css = '.gc-spectra-hero { color: red; } .keep { color: blue; }';
		$out = GenCssOrphanStripper::strip_orphan_selectors( $css );

		$this->assertStringNotContainsString( '.gc-spectra-hero', $out );
		$this->assertStringContainsString( '.keep', $out );
	}

	/**
	 * Wildcard-suffix orphans (`.gc-spectra-gallery-item-3`) are stripped too.
	 *
	 * @return void
	 */
	public function test_strips_suffixed_orphan_rule(): void {
		$css = '.gc-spectra-gallery-item-3 { opacity: 0.5; }';
		$out = GenCssOrphanStripper::strip_orphan_selectors( $css );

		$this->assertSame( '', trim( $out ) );
	}

	/**
	 * Selector lists mixing orphan + live tokens are preserved verbatim
	 * because the live portion can still match a DOM node.
	 *
	 * @return void
	 */
	public function test_preserves_mixed_selector_list(): void {
		$css = '.gc-spectra-hero, .real-class { color: red; }';
		$out = GenCssOrphanStripper::strip_orphan_selectors( $css );

		$this->assertStringContainsString( '.real-class', $out );
		$this->assertStringContainsString( 'color: red', $out );
	}

	/**
	 * Descendant selectors chained with an orphan class but anchored on a live
	 * element (e.g. `.gc-spectra-hero h1`) are also preserved — `h1` on its own
	 * would still match.
	 *
	 * @return void
	 */
	public function test_preserves_orphan_with_live_descendant(): void {
		$css = '.gc-spectra-hero h1 { font-size: 3rem; }';
		$out = GenCssOrphanStripper::strip_orphan_selectors( $css );

		$this->assertStringContainsString( 'h1', $out );
		$this->assertStringContainsString( 'font-size', $out );
	}

	/**
	 * Orphan rules nested inside `@media` queries are stripped, and the wrapper
	 * is dropped when the media block becomes empty.
	 *
	 * @return void
	 */
	public function test_strips_orphan_inside_media_query(): void {
		$css = '@media (max-width: 768px) { .gc-spectra-hero { padding: 1rem; } }';
		$out = GenCssOrphanStripper::strip_orphan_selectors( $css );

		$this->assertSame( '', trim( $out ) );
	}

	/**
	 * A media query keeps its live rules even when one orphan rule is removed.
	 *
	 * @return void
	 */
	public function test_preserves_media_query_with_live_rules(): void {
		$css = '@media (max-width: 768px) { .gc-spectra-hero { padding: 1rem; } .real { margin: 0; } }';
		$out = GenCssOrphanStripper::strip_orphan_selectors( $css );

		$this->assertStringContainsString( '@media', $out );
		$this->assertStringContainsString( '.real', $out );
		$this->assertStringNotContainsString( '.gc-spectra-hero', $out );
	}

	/**
	 * Fast-path: CSS with no `.gc-spectra` tokens is returned unchanged
	 * (same string instance).
	 *
	 * @return void
	 */
	public function test_returns_input_when_no_orphans(): void {
		$css = '.keep { color: red; } .also-keep { color: blue; }';
		$out = GenCssOrphanStripper::strip_orphan_selectors( $css );

		$this->assertSame( $css, $out );
	}

	/**
	 * Empty input returns empty string.
	 *
	 * @return void
	 */
	public function test_handles_empty_input(): void {
		$this->assertSame( '', GenCssOrphanStripper::strip_orphan_selectors( '' ) );
	}

	/**
	 * The `strip_orphans_meta` callback (post-meta write path) cleans orphan
	 * rules from the `spectra_blocks_pro_gs_user_css` meta value.
	 *
	 * @return void
	 */
	public function test_meta_filter_strips_orphans(): void {
		$css = '.gc-spectra-hero { color: red; } .keep { color: blue; }';
		$out = GenCssOrphanStripper::strip_orphans_meta( $css );

		$this->assertStringNotContainsString( '.gc-spectra-hero', $out );
		$this->assertStringContainsString( '.keep', $out );
	}

	/**
	 * Non-string meta values bypass the stripper.
	 *
	 * @return void
	 */
	public function test_meta_filter_passes_through_non_string_values(): void {
		$arr = array( 'raw' => '.gc-spectra-hero { color: red; }' );
		$out = GenCssOrphanStripper::strip_orphans_meta( $arr );

		$this->assertSame( $arr, $out );
	}

	/**
	 * Pseudo-class selectors attached to an orphan root are stripped too.
	 *
	 * @return void
	 */
	public function test_strips_orphan_with_pseudo(): void {
		$css = '.gc-spectra-btn:hover { background: red; }';
		$out = GenCssOrphanStripper::strip_orphan_selectors( $css );

		$this->assertSame( '', trim( $out ) );
	}

	/**
	 * Selectors combining multiple orphan classes reduce to orphan-only and
	 * are stripped.
	 *
	 * @return void
	 */
	public function test_strips_multi_orphan_compound(): void {
		$css = '.gc-spectra-hero.gc-spectra-featured { color: gold; }';
		$out = GenCssOrphanStripper::strip_orphan_selectors( $css );

		$this->assertSame( '', trim( $out ) );
	}
}
