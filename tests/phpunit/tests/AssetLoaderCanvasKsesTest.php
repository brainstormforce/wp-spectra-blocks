<?php
/**
 * The `<canvas>` KSES allowance must be SCOPED to the content block's own call.
 *
 * WP's `post` KSES context strips `<canvas>`, which kills a JS-drawn canvas an
 * imported page relies on. The allowance therefore has to exist — but it used to
 * be registered on `wp_kses_allowed_html` for the whole request, so every other
 * `post`-context `wp_kses_post()` on that page (comment text, widget text, any
 * third party's) silently got a wider allow-list too.
 *
 * Both halves are asserted here. Only checking that canvas survives would pass
 * just as well with the old request-wide filter — the leak is the point.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests;

use SpectraBlocks\AssetLoader;
use WP_UnitTestCase;

/**
 * AssetLoaderCanvasKsesTest test case.
 *
 * @since x.x.x
 */
class AssetLoaderCanvasKsesTest extends WP_UnitTestCase {

	/**
	 * Markup the content block would hand to KSES.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const CANVAS_HTML = '<p>chart</p><canvas id="c" width="300" height="150"></canvas>';

	/**
	 * Put a zip-built page on the singular frontend query the filter gates on.
	 *
	 * @since x.x.x
	 *
	 * @param bool $imported Whether to set the importer marker.
	 * @return int Post ID.
	 */
	private function go_to_page( bool $imported ): int {
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		if ( $imported ) {
			update_post_meta( $post_id, AssetLoader::IMPORTED_MARKER_META_KEY, true );
		}
		$this->go_to( get_permalink( $post_id ) );

		return $post_id;
	}

	/**
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_canvas_survives_only_inside_the_wrapped_call(): void {
		$this->go_to_page( true );

		$wrapped = AssetLoader::with_canvas_allowed( self::CANVAS_HTML );
		$this->assertStringContainsString( '<canvas', $wrapped, 'the content block must keep its canvas' );
		$this->assertStringContainsString( 'width="300"', $wrapped, 'canvas geometry attributes must survive' );

		// THE SCOPING GUARANTEE: an unrelated wp_kses_post() on the very same
		// request — a comment, a widget, another plugin — must be unaffected.
		// This is the assertion the old request-wide filter would fail.
		$this->assertStringNotContainsString(
			'<canvas',
			wp_kses_post( self::CANVAS_HTML ),
			'the allowance leaked outside the content block'
		);
	}

	/**
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_canvas_is_stripped_on_a_page_the_importer_did_not_write(): void {
		$this->go_to_page( false );

		$this->assertStringNotContainsString(
			'<canvas',
			AssetLoader::with_canvas_allowed( self::CANVAS_HTML ),
			'a non-imported page must keep the WP-standard allow-list'
		);
	}

	/**
	 * The filter must not stay registered after the wrapped call returns, even
	 * across repeated use — a leaked `add_filter` is exactly the failure mode
	 * the wrapper exists to prevent, and it would only show on the second call.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function test_the_filter_is_removed_again_after_each_call(): void {
		$this->go_to_page( true );

		AssetLoader::with_canvas_allowed( self::CANVAS_HTML );
		AssetLoader::with_canvas_allowed( self::CANVAS_HTML );

		$this->assertFalse(
			has_filter( 'wp_kses_allowed_html', array( AssetLoader::class, 'allow_canvas_on_zip_built_pages' ) ),
			'the canvas filter outlived the call that added it'
		);
		$this->assertStringNotContainsString( '<canvas', wp_kses_post( self::CANVAS_HTML ) );
	}
}
