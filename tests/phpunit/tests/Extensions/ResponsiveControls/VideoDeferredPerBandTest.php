<?php
/**
 * A background video is fetched only for bands that show it.
 *
 * The `<video>` is rendered once for the block and hidden per band by CSS, so
 * it exists at widths whose background is an image or none. With `autoplay`
 * the browser fetched and decoded the file at every one of those widths — a
 * phone visitor paid for a desktop-only video. Where any band resolves to
 * something other than a video the element now renders without `autoplay`
 * and with `preload="none"`, the per-device data marks the non-video bands
 * explicitly, and `responsive-videos.js` starts or stops it for the band in
 * view. Where every band is a video nothing changes.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Helpers\Renderer;
use WP_UnitTestCase;

/**
 * VideoDeferredPerBandTest test case.
 *
 * @since x.x.x
 */
class VideoDeferredPerBandTest extends WP_UnitTestCase {

	/**
	 * Render a container and return the wrapper's video data and the video tag.
	 *
	 * @since x.x.x
	 * @param array<string, mixed> $style The `style` attribute.
	 * @return array{data: array<string, string>|null, video: string} Parsed `data-responsive-videos` and the `<video …>` tag.
	 */
	private function render( array $style ): array {
		$id   = 'vd' . wp_generate_password( 6, false );
		$html = do_blocks( '<!-- wp:spectra/container ' . wp_json_encode( array( 'spectraId' => $id, 'style' => $style ) ) . ' --><div data-spectra-id="' . $id . '" class="wp-block-spectra-container"></div><!-- /wp:spectra/container -->' );

		preg_match( '/data-responsive-videos="([^"]*)"/', $html, $d );
		preg_match( '/<video[^>]*>/', $html, $v );

		return array(
			'data'  => isset( $d[1] ) ? json_decode( html_entity_decode( $d[1] ), true ) : null,
			'video' => $v[0] ?? '',
		);
	}

	/**
	 * Video on Desktop, None on Tablet: no autoplay, no preload, tablet marked as no video.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_a_band_without_video_defers_the_element() {
		$r = $this->render(
			array(
				'background' => array(
					'type'  => 'video',
					'media' => array( 'url' => 'http://x/v.mp4' ),
				),
				'@tablet'    => array( 'background' => array( 'type' => 'none' ) ),
			)
		);

		$this->assertStringNotContainsString( 'autoPlay', $r['video'], 'The browser must not start (and fetch) the video on its own.' );
		$this->assertStringContainsString( 'preload="none"', $r['video'] );
		$this->assertStringContainsString( 'data-spectra-deferred="1"', $r['video'] );
		$this->assertSame(
			array(
				'base'    => 'http://x/v.mp4',
				'@tablet' => '',
			),
			$r['data'],
			'Tablet is marked explicitly so the script does not fall back to the desktop video there.'
		);
	}

	/**
	 * Video everywhere: unchanged — autoplay stays, nothing deferred.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_video_on_every_band_keeps_autoplay() {
		$r = $this->render(
			array(
				'background' => array(
					'type'  => 'video',
					'media' => array( 'url' => 'http://x/v.mp4' ),
				),
			)
		);

		$this->assertStringContainsString( 'autoPlay', $r['video'] );
		$this->assertStringNotContainsString( 'preload', $r['video'] );
		$this->assertSame( array( 'base' => 'http://x/v.mp4' ), $r['data'] );
	}

	/**
	 * The helper resolves each band over base, like the CSS does.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_video_on_every_band_resolves_over_base() {
		$video = array(
			'type'  => 'video',
			'media' => array( 'url' => 'http://x/v.mp4' ),
		);

		$this->assertTrue( Renderer::video_on_every_band( array( 'background' => $video ) ), 'Root video, no store: every band inherits it.' );
		$this->assertTrue( Renderer::video_on_every_band( array( 'responsiveControls' => array( 'base' => array( 'background' => $video ) ) ) ) );
		$this->assertFalse( Renderer::video_on_every_band( array( 'responsiveControls' => array( 'base' => array( 'background' => $video ), '@mobile' => array( 'background' => array( 'type' => 'image' ) ) ) ) ) );
		$this->assertFalse( Renderer::video_on_every_band( array( 'responsiveControls' => array( '@tablet' => array( 'background' => $video ) ) ) ), 'A tablet-only video leaves base and mobile without one.' );
		$this->assertFalse( Renderer::video_on_every_band( array() ) );
	}
}
