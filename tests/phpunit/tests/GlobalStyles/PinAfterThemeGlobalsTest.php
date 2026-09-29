<?php
/**
 * Tests for Engine::pin_styles_after_theme_globals().
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\AssetLoader;
use SpectraBlocks\GlobalStyles\Engine;
use WP_UnitTestCase;

/**
 * PinAfterThemeGlobalsTest test case.
 *
 * @since 1.0.10
 */
class PinAfterThemeGlobalsTest extends WP_UnitTestCase {

	/**
	 * Drop the registry this test filled.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$GLOBALS['wp_styles'] = null;
		parent::tearDown();
	}

	/**
	 * Register the given handles, each printing a comment of its own name.
	 *
	 * @param string[] $handles Style handles.
	 * @return void
	 */
	private function register( array $handles ): void {
		$GLOBALS['wp_styles'] = null;
		foreach ( $handles as $handle ) {
			wp_register_style( $handle, false );
			wp_add_inline_style( $handle, "/*{$handle}*/" );
		}
	}

	/**
	 * View an imported page.
	 *
	 * @return void
	 */
	private function go_to_imported_page(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, AssetLoader::IMPORTED_MARKER_META_KEY, '1' );
		$this->go_to( get_permalink( $post_id ) );
	}

	/**
	 * The element-tier reverts print after the theme's global-styles slot and
	 * before every page GBS sheet (classic theme, block assets on demand).
	 *
	 * @return void
	 */
	public function test_reverts_print_between_the_theme_slot_and_the_gbs_sheets() {
		$reverts = 'spectra-blocks-imported-baseline-elements';
		$this->register( array( 'wp-global-styles-placeholder', $reverts, 'spectra-gs-dynamic-styles' ) );
		wp_enqueue_style( 'spectra-gs-dynamic-styles' );
		wp_enqueue_style( 'wp-global-styles-placeholder' );
		$this->go_to_imported_page();

		Engine::get_instance()->pin_styles_after_theme_globals();

		$this->assertContains( $reverts, wp_styles()->registered['spectra-gs-dynamic-styles']->deps );
		$this->assertContains( 'wp-global-styles-placeholder', wp_styles()->registered[ $reverts ]->deps );
		$this->assertTrue( wp_style_is( $reverts, 'enqueued' ) );
		$head = get_echo( array( wp_styles(), 'do_items' ) );
		$this->assertLessThan( strpos( $head, "/*{$reverts}*/" ), strpos( $head, '/*wp-global-styles-placeholder*/' ) );
		$this->assertLessThan( strpos( $head, '/*spectra-gs-dynamic-styles*/' ), strpos( $head, "/*{$reverts}*/" ) );
	}

	/**
	 * A page the importer never wrote does not load the reverts; GBS sheets pin
	 * to the theme slot as before.
	 *
	 * @return void
	 */
	public function test_reverts_skip_a_non_imported_page() {
		$reverts = 'spectra-blocks-imported-baseline-elements';
		$this->register( array( 'global-styles', $reverts, 'spectra-gs-dynamic-styles' ) );
		wp_enqueue_style( 'spectra-gs-dynamic-styles' );
		$this->go_to( get_permalink( self::factory()->post->create() ) );

		Engine::get_instance()->pin_styles_after_theme_globals();

		$this->assertFalse( wp_style_is( $reverts, 'enqueued' ) );
		$this->assertContains( 'global-styles', wp_styles()->registered['spectra-gs-dynamic-styles']->deps );
	}

	/**
	 * With no global-styles slot at all, an imported page still loads the
	 * reverts (without a dependency that would drop them).
	 *
	 * @return void
	 */
	public function test_reverts_load_without_a_theme_slot() {
		$reverts = 'spectra-blocks-imported-baseline-elements';
		$this->register( array( $reverts ) );
		$this->go_to_imported_page();

		Engine::get_instance()->pin_styles_after_theme_globals();

		$this->assertTrue( wp_style_is( $reverts, 'enqueued' ) );
		$this->assertSame( array(), wp_styles()->registered[ $reverts ]->deps );
	}
}
