<?php
/**
 * The viewport bands the front-end scripts receive are the ones the CSS was built with.
 *
 * Every per-device decision the front end makes in CSS comes out of one
 * resolver (`get_device_media_queries()`): banded attributes, the `uag-hide-*`
 * classes, orientation reverse. Scripts that decide "which device is this" —
 * the responsive video switch here, the popup builder, motion effects and the
 * video switch in Pro — used to compare `window.innerWidth` against numbers of
 * their own. Wherever those numbers differed from the resolver's, CSS and JS
 * disagreed about the device, and on WordPress 7.1 they differed by default.
 *
 * The fix hands scripts the resolver's own media queries as
 * `window.spectraBlocksViewportBands`, so `matchMedia()` on them agrees with
 * the stylesheet by construction. This file pins that contract: the payload
 * is the resolver's output, keyed the way the scripts read it, and it is
 * published through a registered handle scripts can depend on.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * ViewportBandsScriptTest test case.
 *
 * @since x.x.x
 */
class ViewportBandsScriptTest extends WP_UnitTestCase {

	/**
	 * Deregister the handle so each test starts clean.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function tear_down() {
		// Both handles, or the video script stays registered with a dependency
		// the next test no longer has — and WordPress 6.9.1+ flags that.
		wp_dequeue_script( 'spectra-responsive-videos' );
		wp_deregister_script( 'spectra-responsive-videos' );
		wp_deregister_script( ResponsiveControls::VIEWPORT_BANDS_HANDLE );
		parent::tear_down();
	}

	/**
	 * The payload is the resolver's bands under the keys the scripts read.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_payload_mirrors_the_css_resolver() {
		$controls = ResponsiveControls::instance();
		$bands    = $controls->get_viewport_bands_for_script();
		$css      = $controls->get_device_media_queries();

		$this->assertSame( array( 'desktop', 'tablet', 'mobile' ), array_keys( $bands ), 'Scripts read exactly these three keys.' );

		$this->assertSame( $css['@desktop'], $bands['desktop'], 'The desktop query must be the one the CSS uses.' );
		$this->assertSame( $css['@tablet'], $bands['tablet'], 'The tablet query must be the one the CSS uses.' );
		$this->assertSame( $css['@mobile'], $bands['mobile'], 'The mobile query must be the one the CSS uses.' );

		foreach ( $bands as $device => $query ) {
			$this->assertNotSame( '', $query, "The {$device} band must never be empty — an empty query would match nothing and the script would fall back silently." );
			$this->assertStringNotContainsString( '@media', $query, 'Scripts pass the query to matchMedia(), which takes it without the at-rule.' );
		}
	}

	/**
	 * The bands are published through a registered handle with the JSON inline.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_handle_is_registered_with_the_bands_inline() {
		$controls = ResponsiveControls::instance();
		$controls->register_viewport_bands_script();

		$this->assertTrue( wp_script_is( ResponsiveControls::VIEWPORT_BANDS_HANDLE, 'registered' ), 'Dependants need a registered handle to list.' );

		$inline = wp_scripts()->get_inline_script_data( ResponsiveControls::VIEWPORT_BANDS_HANDLE, 'before' );

		$this->assertStringContainsString( 'window.spectraBlocksViewportBands', $inline, 'The data is the script; it must be the inline payload.' );
		$this->assertStringContainsString( wp_json_encode( $controls->get_viewport_bands_for_script() ), $inline, 'The inline payload must be the resolver output, unchanged.' );
	}

	/**
	 * Registering twice does not duplicate the payload.
	 *
	 * Several enqueue paths may ask for the handle; the second call must be a
	 * no-op rather than appending a second inline script.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_registering_twice_is_idempotent() {
		$controls = ResponsiveControls::instance();
		$controls->register_viewport_bands_script();
		$controls->register_viewport_bands_script();

		$inline = wp_scripts()->get_inline_script_data( ResponsiveControls::VIEWPORT_BANDS_HANDLE, 'before' );

		$this->assertSame( 1, substr_count( $inline, 'window.spectraBlocksViewportBands' ), 'One payload, however many times registration runs.' );
	}

	/**
	 * The free responsive video script depends on the bands handle.
	 *
	 * Without the dependency the inline payload would not print for a page whose
	 * only per-device script is the video switch, and it would silently fall
	 * back to its own breakpoints.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_video_script_depends_on_the_bands() {
		$post_id = self::factory()->post->create( array( 'post_content' => '<!-- wp:spectra/container {"spectraId":"bands"} /-->' ) );
		$this->go_to( get_permalink( $post_id ) );

		$controls = ResponsiveControls::instance();
		$controls->register_viewport_bands_script();
		$controls->enqueue_responsive_videos_script();

		$this->assertTrue( wp_script_is( 'spectra-responsive-videos', 'enqueued' ), 'The fixture must enqueue the video script.' );
		$this->assertContains(
			ResponsiveControls::VIEWPORT_BANDS_HANDLE,
			wp_scripts()->registered['spectra-responsive-videos']->deps,
			'The video script must list the bands as a dependency.'
		);
	}
}
