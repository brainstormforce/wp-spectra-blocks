<?php
/**
 * A breakpoint set to no image says so.
 *
 * The base band's `background-image: url(…)` is unbanded and keeps applying
 * until a narrower band overrides the property. A band whose Background Type
 * is None or Video used to emit only the video-wrapper hide and the
 * colour/gradient variable resets — never `background-image`, so "remove the
 * background on tablet" left the desktop image painting at 768 px, and 7.0.4
 * content whose cascade baked `@mobile { type: none }` showed the desktop
 * image on phones. Every block that uses the shared Background component was
 * affected: Container, Slider, Slider Child, Modal Popup Content, Popup
 * Builder.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * BackgroundNoneResetTest test case.
 *
 * @since x.x.x
 */
class BackgroundNoneResetTest extends WP_UnitTestCase {

	/**
	 * Render a block and return its CSS split into the three bands.
	 *
	 * Each call uses a fresh `spectraId` — the extension emits a block's CSS
	 * once per id per request.
	 *
	 * @since x.x.x
	 * @param string               $block Block name.
	 * @param array<string, mixed> $attrs Block attributes (without spectraId).
	 * @param string               $inner Save markup, `%s` for the id.
	 * @return array{id: string, base: string, tablet: string, mobile: string} Bands.
	 */
	private function bands( string $block, array $attrs, string $inner ): array {
		$id                 = 'bg' . wp_generate_password( 6, false );
		$attrs['spectraId'] = $id;
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );
		do_blocks( "<!-- wp:{$block} " . wp_json_encode( $attrs ) . ' -->' . sprintf( $inner, $id ) . "<!-- /wp:{$block} -->" );
		$css = (string) preg_replace( '/\s+/', ' ', implode( '', (array) ( wp_styles()->get_data( 'spectra-responsive-styles', 'after' ) ?: array() ) ) );

		/*
		 * The band queries come from the resolver, never from a literal.
		 *
		 * WordPress 7.1's range syntax exists only where core declares
		 * `settings.viewport`; on 6.6 the plugin emits its own fallback bands,
		 * so a hardcoded literal matched nothing, every band came back empty
		 * and the assertions read the base CSS instead of the band's.
		 */
		$queries = ResponsiveControls::instance()->get_media_queries();
		$t       = isset( $queries['@tablet'] ) ? strpos( $css, '@media ' . $queries['@tablet'] ) : false;
		$m       = isset( $queries['@mobile'] ) ? strpos( $css, '@media ' . $queries['@mobile'] ) : false;

		return array(
			'id'     => $id,
			'base'   => substr( $css, 0, false === $t ? strlen( $css ) : $t ),
			'tablet' => false === $t ? '' : substr( $css, $t, ( false === $m ? strlen( $css ) : $m ) - $t ),
			'mobile' => false === $m ? '' : substr( $css, $m ),
		);
	}

	/**
	 * The background-image values a band declares for the block, in order.
	 *
	 * @since x.x.x
	 * @param string $band One band's CSS.
	 * @param string $id   The block's spectraId.
	 * @return array<int, string> Values, e.g. `url(…)` or `none`.
	 */
	private function images_in( string $band, string $id ): array {
		preg_match_all( "/[^{}]*'{$id}'[^{}]*\\{[^}]*background-image:\\s*([^;}]+)/", $band, $m );

		return array_map( 'trim', $m[1] );
	}

	/**
	 * Container: image on Desktop, None on Tablet, Video on Mobile.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_container_none_and_video_bands_reset_the_image() {
		$b = $this->bands(
			'spectra/container',
			array(
				'style' => array(
					'background' => array(
						'type'  => 'image',
						'media' => array(
							'id'  => 5,
							'url' => 'http://x/a.png',
						),
					),
					'@tablet'    => array( 'background' => array( 'type' => 'none' ) ),
					'@mobile'    => array(
						'background' => array(
							'type'  => 'video',
							'media' => array(
								'id'  => 7,
								'url' => 'http://x/v.mp4',
							),
						),
					),
				),
			),
			'<div data-spectra-id="%s" class="wp-block-spectra-container"></div>'
		);

		$this->assertSame( array( 'url(http://x/a.png)' ), $this->images_in( $b['base'], $b['id'] ) );
		$this->assertSame( array( 'none' ), $this->images_in( $b['tablet'], $b['id'] ), 'None on Tablet must remove the desktop image.' );
		$this->assertSame( array( 'none' ), $this->images_in( $b['mobile'], $b['id'] ), 'Video on Mobile must remove the desktop image behind it.' );
	}

	/**
	 * The reset lands on the same selector the image rule used, so it wins on order.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_reset_uses_the_image_rules_selector() {
		$b = $this->bands(
			'spectra/container',
			array(
				'style' => array(
					'background' => array(
						'type'  => 'image',
						'media' => array( 'url' => 'http://x/a.png' ),
					),
					'@tablet'    => array( 'background' => array( 'type' => 'none' ) ),
				),
			),
			'<div data-spectra-id="%s" class="wp-block-spectra-container"></div>'
		);

		preg_match( "/([^{}]*'{$b['id']}'[^{}]*)\\{[^}]*background-image: url/", $b['base'], $img );
		preg_match( "/([^{}]*'{$b['id']}'[^{}]*)\\{[^}]*background-image: none/", $b['tablet'], $reset );

		$this->assertNotEmpty( $img[1] ?? '' );
		$this->assertSame( trim( preg_replace( '/^.*\{ /', '', $img[1] ) ), trim( preg_replace( '/^.*\{ /', '', $reset[1] ) ), 'Same selector, so the band overrides by source order without needing more specificity.' );
	}

	/**
	 * A band that stores no background inherits — no reset is emitted for it.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_an_unset_band_does_not_reset() {
		$b = $this->bands(
			'spectra/container',
			array(
				'style' => array(
					'background' => array(
						'type'  => 'image',
						'media' => array( 'url' => 'http://x/a.png' ),
					),
					'@tablet'    => array( 'minHeight' => '300px' ),
				),
			),
			'<div data-spectra-id="%s" class="wp-block-spectra-container"></div>'
		);

		$this->assertNotContains( 'none', $this->images_in( $b['tablet'], $b['id'] ), 'Tablet said nothing about its background; it must keep the desktop image.' );
	}

	/**
	 * Slider and Modal Popup Content share the component and the fix.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_slider_and_modal_content_reset_too() {
		$style = array(
			'background' => array(
				'type'  => 'image',
				'media' => array( 'url' => 'http://x/a.png' ),
			),
			'@mobile'    => array( 'background' => array( 'type' => 'none' ) ),
		);

		$slider = $this->bands( 'spectra/slider', array( 'style' => $style ), '<div data-spectra-id="%s" class="wp-block-spectra-slider"></div>' );
		$modal  = $this->bands( 'spectra/modal-popup-content', array( 'style' => $style ), '<div data-spectra-id="%s" class="wp-block-spectra-modal-popup-content"></div>' );

		$this->assertSame( array( 'none' ), $this->images_in( $slider['mobile'], $slider['id'] ), 'Slider' );
		$this->assertSame( array( 'none' ), $this->images_in( $modal['mobile'], $modal['id'] ), 'Modal Popup Content' );
	}

	/**
	 * Legacy 7.0.4 content: tablet None baked into mobile by the cascade (BACK 2).
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_legacy_cascaded_none_resets_on_mobile() {
		$b = $this->bands(
			'spectra/container',
			array(
				'responsiveControls' => array(
					'lg' => array(
						'background' => array(
							'type'  => 'image',
							'media' => array( 'url' => 'http://x/a.png' ),
						),
					),
					'md' => array( 'background' => array( 'type' => 'none' ) ),
				),
			),
			'<div data-spectra-id="%s" class="wp-block-spectra-container"></div>'
		);

		$this->assertSame( array( 'none' ), $this->images_in( $b['tablet'], $b['id'] ) );
		$this->assertSame( array( 'none' ), $this->images_in( $b['mobile'], $b['id'] ), 'The cascade copies tablet None to mobile; the generator must express it.' );
	}
}
