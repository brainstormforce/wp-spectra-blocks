<?php
/**
 * Core's child-layout "Width" (Fit / Fill / Fixed) changes cleanly between bands.
 *
 * A child of a flex layout stores its Width choice in `style.layout` as
 * `selfStretch` (+ `flexSize` for Fixed), per device on 7.1. Each choice sets
 * BOTH flex properties, but a band used to emit only the one its own choice
 * needed — `flex-basis` for Fixed, `flex-grow` for Fill, nothing for Fit — so a
 * breakpoint that changed the choice inherited the wider band's other
 * property: Fixed 120px on Desktop and Fit on Mobile still rendered 120px on
 * phones. Every block whose parent allows sizing on children (Icons, Buttons,
 * Container, Countdown, Counter, List, Post, Slider, Tabs) paints through the
 * same generator.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * ChildLayoutWidthPerBandTest test case.
 *
 * @since x.x.x
 */
class ChildLayoutWidthPerBandTest extends WP_UnitTestCase {

	/**
	 * Render an icon inside Icons and return its flex declarations per band.
	 *
	 * @since x.x.x
	 * @param array<string, mixed> $style The icon's `style` attribute.
	 * @return array{base: string, tablet: string, mobile: string} Declarations.
	 */
	private function flex_per_band( array $style ): array {
		$id = 'cw' . wp_generate_password( 6, false );
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );
		do_blocks( '<!-- wp:spectra/icons {"spectraId":"' . $id . 'p"} --><div data-spectra-id="' . $id . 'p" class="wp-block-spectra-icons"><!-- wp:spectra/icon ' . wp_json_encode( array( 'spectraId' => $id, 'style' => $style ) ) . ' --><div data-spectra-id="' . $id . '" class="wp-block-spectra-icon"></div><!-- /wp:spectra/icon --></div><!-- /wp:spectra/icons -->' );
		$css = (string) preg_replace( '/\s+/', ' ', implode( '', (array) ( wp_styles()->get_data( 'spectra-responsive-styles', 'after' ) ?: array() ) ) );

		/*
		 * The band queries come from the resolver, never from a literal.
		 *
		 * WordPress 7.1's range syntax exists only where core declares
		 * `settings.viewport`; on 6.6 the plugin emits its own fallback bands,
		 * so a hardcoded literal matched nothing and every band came back empty.
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
			preg_match( "/\\[data-spectra-id='{$id}'\\]\\)\\{([^}]*flex[^}]*)\\}/", $seg, $mm );
			$out[ $band ] = isset( $mm[1] ) ? trim( $mm[1] ) : '';
		}

		return $out;
	}

	/**
	 * Fixed → Fill → Fit across the three bands: each band states both properties.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_each_choice_resets_the_other_flex_property() {
		$flex = $this->flex_per_band(
			array(
				'layout'  => array(
					'selfStretch' => 'fixed',
					'flexSize'    => '120px',
				),
				'@tablet' => array( 'layout' => array( 'selfStretch' => 'fill' ) ),
				'@mobile' => array( 'layout' => array( 'selfStretch' => 'fit' ) ),
			)
		);

		$this->assertSame( 'flex-basis:120px;flex-grow:0;flex-shrink:1;', $flex['base'] );
		$this->assertSame( 'flex-grow:1;flex-shrink:1;flex-basis:auto;', $flex['tablet'], 'Fill must drop the fixed basis.' );
		$this->assertSame( 'flex-grow:0;flex-shrink:1;flex-basis:auto;', $flex['mobile'], 'Fit must reset both — it used to emit nothing and inherit 120px.' );
	}

	/**
	 * WordPress 7.1's Width control writes `fixedNoShrink` for "Fixed"; it must paint.
	 *
	 * Only the older `fixed` value was recognised, so a width fixed in the 7.1
	 * editor rendered as Fit on the front end — the report that started this.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_fixed_no_shrink_paints_a_fixed_width() {
		$flex = $this->flex_per_band(
			array(
				'@tablet' => array(
					'layout' => array(
						'selfStretch' => 'fixedNoShrink',
						'flexSize'    => '80px',
					),
				),
			)
		);

		$this->assertSame( 'flex-basis:80px;flex-grow:0;flex-shrink:0;', $flex['tablet'] );
	}

	/**
	 * A band that says nothing about Width inherits the desktop choice.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_an_unset_band_inherits() {
		$flex = $this->flex_per_band(
			array(
				'layout'  => array(
					'selfStretch' => 'fixed',
					'flexSize'    => '120px',
				),
				'@tablet' => array( 'size' => '20px' ),
			)
		);

		$this->assertSame( 'flex-basis:120px;flex-grow:0;flex-shrink:1;', $flex['base'] );
		// The generator resolves an unset band over base, so the tablet band either
		// says nothing or repeats the desktop choice — never something else.
		$this->assertContains( $flex['tablet'], array( '', 'flex-basis:120px;flex-grow:0;flex-shrink:1;' ), 'Tablet did not choose a Width; the desktop choice applies.' );
	}

	/**
	 * Fixed set only on Tablet: desktop and mobile untouched, tablet gets the basis.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_fixed_on_one_band_only() {
		$flex = $this->flex_per_band(
			array(
				'@tablet' => array(
					'layout' => array(
						'selfStretch' => 'fixed',
						'flexSize'    => '80px',
					),
				),
			)
		);

		$this->assertSame( '', $flex['base'] );
		$this->assertSame( 'flex-basis:80px;flex-grow:0;flex-shrink:1;', $flex['tablet'] );
		$this->assertSame( '', $flex['mobile'] );
	}
}
