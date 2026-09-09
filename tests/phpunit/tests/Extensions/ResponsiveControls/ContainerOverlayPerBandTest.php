<?php
/**
 * The container's overlay image is painted per band, not per element.
 *
 * The stylesheet paints the overlay with
 * `.has-container-overlay:not(.has-video-background)::after`, and the
 * controller emits those classes once per ELEMENT from the union of every
 * band's background type. A video at any one breakpoint therefore carried
 * `.has-video-background` at every width and the overlay never painted — not
 * even at breakpoints whose background is an image. The generator now emits
 * the overlay rules in each non-video band and hides the pseudo-element in
 * video bands, so the class union no longer decides.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * ContainerOverlayPerBandTest test case.
 *
 * @since x.x.x
 */
class ContainerOverlayPerBandTest extends WP_UnitTestCase {

	/**
	 * Render a container and return its own `::after` rules per band.
	 *
	 * @since x.x.x
	 * @param array<string, mixed> $style The `style` attribute.
	 * @return array{base: array<int, string>, tablet: array<int, string>, mobile: array<int, string>} Rule bodies.
	 */
	private function after_rules( array $style ): array {
		$id = 'ov' . wp_generate_password( 6, false );
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );
		do_blocks( '<!-- wp:spectra/container ' . wp_json_encode( array( 'spectraId' => $id, 'style' => $style ) ) . ' --><div data-spectra-id="' . $id . '" class="wp-block-spectra-container"></div><!-- /wp:spectra/container -->' );
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
		$segments = array(
			'base'   => substr( $css, 0, false === $t ? strlen( $css ) : $t ),
			'tablet' => false === $t ? '' : substr( $css, $t, ( false === $m ? strlen( $css ) : $m ) - $t ),
			'mobile' => false === $m ? '' : substr( $css, $m ),
		);

		$out = array();
		foreach ( $segments as $band => $seg ) {
			preg_match_all( "/[^{}]*'{$id}'\\]::after\\{([^}]*)\\}/", $seg, $mm );
			$out[ $band ] = array_map( 'trim', $mm[1] );
		}

		return $out;
	}

	/**
	 * Image + overlay on Desktop, video on Tablet: Desktop still paints its overlay.
	 *
	 * The report's Bug 9 case. Before, no band painted the overlay at all.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_video_on_one_band_does_not_kill_the_overlay_elsewhere() {
		$rules = $this->after_rules(
			array(
				'background'   => array(
					'type'  => 'image',
					'media' => array( 'url' => 'http://x/a.png' ),
				),
				'overlayType'  => 'image',
				'overlayImage' => array( 'url' => 'http://x/o.png' ),
				'@tablet'      => array(
					'background' => array(
						'type'  => 'video',
						'media' => array( 'url' => 'http://x/v.mp4' ),
					),
				),
			)
		);

		$this->assertCount( 1, $rules['base'] );
		$this->assertStringContainsString( 'background-image: var(--spectra-overlay-image, none)', $rules['base'][0], 'Desktop paints the overlay from its own band.' );
		$this->assertSame( array( 'display: none;' ), $rules['tablet'], 'The video band hides the overlay pseudo-element.' );
		$this->assertCount( 1, $rules['mobile'] );
		$this->assertStringContainsString( 'background-image: var(--spectra-overlay-image, none)', $rules['mobile'][0], 'Mobile inherits the image and paints the overlay.' );
	}

	/**
	 * With no overlay anywhere nothing extra is emitted.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_no_overlay_means_no_overlay_rules() {
		$rules = $this->after_rules(
			array(
				'background' => array(
					'type'  => 'image',
					'media' => array( 'url' => 'http://x/a.png' ),
				),
				'@tablet'    => array( 'background' => array( 'type' => 'none' ) ),
			)
		);

		$this->assertSame( array(), $rules['base'] );
		$this->assertSame( array(), $rules['tablet'] );
		$this->assertSame( array(), $rules['mobile'] );
	}

	/**
	 * An overlay set only on Tablet is painted by every non-video band; the
	 * per-band variables decide where it is visible.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_overlay_on_one_band_is_carried_by_the_variables() {
		$rules = $this->after_rules(
			array(
				'background' => array(
					'type'  => 'image',
					'media' => array( 'url' => 'http://x/a.png' ),
				),
				'@tablet'    => array(
					'overlayType'  => 'image',
					'overlayImage' => array( 'url' => 'http://x/o.png' ),
				),
			)
		);

		foreach ( array( 'base', 'tablet', 'mobile' ) as $band ) {
			$this->assertCount( 1, $rules[ $band ], "{$band} carries the overlay rule; its variables say whether it shows." );
			$this->assertStringContainsString( 'opacity: var(--spectra-overlay-opacity-value, 0)', $rules[ $band ][0] );
		}
	}
}
