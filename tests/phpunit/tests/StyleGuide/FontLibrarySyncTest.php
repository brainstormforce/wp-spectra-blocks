<?php
/**
 * Tests for the Font Library → user global-styles sync.
 *
 * @package Spectra\Tests\StyleGuide
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\StyleGuide;

use SpectraBlocks\StyleGuide\Engine;
use SpectraBlocks\StyleGuide\GlobalStylesBridge;
use WP_UnitTestCase;

/**
 * FontLibrarySyncTest test case.
 *
 * @since 1.0.10
 */
class FontLibrarySyncTest extends WP_UnitTestCase {

	/**
	 * The newest family is activated past one default page of posts (100).
	 *
	 * @return void
	 */
	public function test_the_newest_family_is_synced_past_a_hundred() {
		// Each face save re-syncs the whole Library; the call below is the one under test.
		remove_all_actions( 'save_post_wp_font_family' );
		remove_all_actions( 'save_post_wp_font_face' );
		remove_all_actions( 'save_post_wp_global_styles' );

		// The theme's user global-styles post, made here: WP 6.6 does not create one for a classic test theme.
		$styles_id = wp_insert_post(
			array(
				'post_type'    => 'wp_global_styles',
				'post_status'  => 'publish',
				'post_name'    => 'wp-global-styles-' . rawurlencode( get_stylesheet() ),
				'post_content' => '{"version": 2, "isGlobalStylesUserThemeJSON": true}',
			)
		);
		wp_set_object_terms( $styles_id, get_stylesheet(), 'wp_theme' );

		for ( $i = 1; $i <= 101; $i++ ) {
			$family_id = wp_insert_post(
				array(
					'post_type'    => 'wp_font_family',
					'post_status'  => 'publish',
					'post_name'    => "sync-family-{$i}",
					'post_content' => wp_slash( wp_json_encode( array( 'fontFamily' => "\"Sync Family {$i}\", sans-serif" ) ) ),
				)
			);
			wp_insert_post(
				array(
					'post_type'    => 'wp_font_face',
					'post_status'  => 'publish',
					'post_parent'  => $family_id,
					'post_content' => wp_slash( wp_json_encode( array( 'src' => "https://example.org/sync-family-{$i}.woff2" ) ) ),
				)
			);
		}

		( new GlobalStylesBridge( Engine::get_instance() ) )->sync_font_library_families();

		$content = json_decode( get_post( $styles_id )->post_content, true );
		$this->assertContains( 'sync-family-101', array_column( $content['settings']['typography']['fontFamilies']['custom'] ?? array(), 'slug' ) );
	}
}
