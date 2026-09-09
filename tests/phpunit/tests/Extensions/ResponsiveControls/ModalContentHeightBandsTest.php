<?php
/**
 * A popup's Content Height "Auto" at one breakpoint is an explicit reset.
 *
 * `contentHeight` and `containerHeight` are per-device keys of
 * `spectra/modal-popup-content`. The painter used to emit `height => ''` for a
 * band set to Auto; the style engine drops empty declarations, so that band
 * contributed no height and the base band's fixed height kept applying — a
 * popup set to Custom 500px on Desktop and Auto on Mobile stayed 500px tall on
 * phones. The band now emits `height: auto`.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * ModalContentHeightBandsTest test case.
 *
 * @since x.x.x
 */
class ModalContentHeightBandsTest extends WP_UnitTestCase {

	/**
	 * The `spectraId` of the block rendered by the current test.
	 *
	 * @since x.x.x
	 * @var string
	 */
	private $id = '';

	/**
	 * Render a popup-content block and return the CSS keyed by band.
	 *
	 * @since x.x.x
	 * Each call uses a fresh `spectraId`: the extension emits a block's CSS once
	 * per id per request, so a reused id renders nothing the second time.
	 *
	 * @since x.x.x
	 * @param array<string, mixed> $style The block's `style` attribute.
	 * @return array{base: string, tablet: string, mobile: string} CSS per band.
	 */
	private function bands_for( array $style ): array {
		$this->id = 'mch' . wp_generate_password( 6, false );
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );
		do_blocks( '<!-- wp:spectra/modal-popup-content ' . wp_json_encode( array( 'spectraId' => $this->id, 'style' => $style ) ) . ' --><div data-spectra-id="' . $this->id . '" class="wp-block-spectra-modal-popup-content"></div><!-- /wp:spectra/modal-popup-content -->' );
		$css = preg_replace( '/\s+/', ' ', implode( '', (array) ( wp_styles()->get_data( 'spectra-responsive-styles', 'after' ) ?: array() ) ) );

		/*
		 * The band queries come from the resolver, never from a literal.
		 *
		 * These were WordPress 7.1's range-syntax strings, which exist only
		 * where core declares `settings.viewport`. On 6.6 the plugin emits its
		 * own fallback bands — `(max-width: 480px)`, `(min-width: 480.02px) and
		 * (max-width: 782px)` — so neither literal was found, both bands came
		 * back empty and every per-band assertion read `null`. Asking the
		 * resolver keeps the split correct on whatever bands the site actually
		 * renders, which is what the rest of the suite does.
		 */
		$queries   = ResponsiveControls::instance()->get_media_queries();
		$tablet_at = isset( $queries['@tablet'] ) ? strpos( $css, '@media ' . $queries['@tablet'] ) : false;
		$mobile_at = isset( $queries['@mobile'] ) ? strpos( $css, '@media ' . $queries['@mobile'] ) : false;

		return array(
			'base'   => substr( $css, 0, false === $tablet_at ? strlen( $css ) : $tablet_at ),
			'tablet' => false === $tablet_at ? '' : substr( $css, $tablet_at, ( false === $mobile_at ? strlen( $css ) : $mobile_at ) - $tablet_at ),
			'mobile' => false === $mobile_at ? '' : substr( $css, $mobile_at ),
		);
	}

	/**
	 * The `height` declaration a band emits for the popup, or null.
	 *
	 * @since x.x.x
	 * @param string $band_css One band's CSS.
	 * @return string|null The value of `height`, null when the band emits none.
	 */
	private function height_in( string $band_css ): ?string {
		return preg_match( "/\\[data-spectra-id='{$this->id}'\\]\\{[^}]*?[^-]height:\\s*([^;}]+)/", $band_css, $m ) ? trim( $m[1] ) : null;
	}

	/**
	 * Custom on Desktop, Auto on Mobile: the mobile band resets the height.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_auto_at_a_breakpoint_emits_an_explicit_reset() {
		$bands = $this->bands_for(
			array(
				'contentHeight'   => 'custom',
				'containerHeight' => '500px',
				'@mobile'         => array( 'contentHeight' => 'auto' ),
			)
		);

		$this->assertSame( '500px', $this->height_in( $bands['base'] ), 'Desktop keeps its fixed height.' );
		$this->assertSame( 'auto', $this->height_in( $bands['mobile'] ), 'The mobile band must say auto, or the fixed height keeps applying.' );
	}

	/**
	 * Auto on Desktop, Custom on Tablet: the tablet band carries the fixed height.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_custom_at_a_breakpoint_emits_its_height() {
		$bands = $this->bands_for(
			array(
				'contentHeight' => 'auto',
				'@tablet'       => array(
					'contentHeight'   => 'custom',
					'containerHeight' => '300px',
				),
			)
		);

		$this->assertSame( 'auto', $this->height_in( $bands['base'] ) );
		$this->assertSame( '300px', $this->height_in( $bands['tablet'] ) );
	}

	/**
	 * A band that says nothing inherits the base height — no reset is emitted for it.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_an_unset_band_inherits_the_fixed_height() {
		$bands = $this->bands_for(
			array(
				'contentHeight'   => 'custom',
				'containerHeight' => '500px',
				'@tablet'         => array( 'containerWidth' => '400px' ),
			)
		);

		$this->assertSame( '500px', $this->height_in( $bands['tablet'] ), 'Tablet inherits Custom 500px from base.' );
		$this->assertStringContainsString( 'width:400px', $bands['tablet'] );
	}
}
