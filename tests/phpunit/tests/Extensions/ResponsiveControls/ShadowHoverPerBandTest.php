<?php
/**
 * Hover shadow renders per band, and legacy content keeps rendering.
 *
 * `shadowHover` is an OBJECT attribute — `{x, y, blur, spread, color}` — that
 * each block flattens into a CSS shadow string before painting it inline as
 * `--spectra-shadow-hover`. Two things follow.
 *
 * First, banding it needs a formatter: the generator has to perform the same
 * object-to-string conversion per breakpoint, or a band would emit the raw
 * array. `format_shadow()` exists for that and is asserted here directly,
 * because a silent formatting difference between the controller and the
 * generator would show as a shadow that renders on desktop and vanishes on
 * mobile.
 *
 * Second, the base. The value is painted INLINE today, and an inline
 * declaration outranks a stylesheet rule of any specificity, so the paint has
 * to move onto the generator for the bands to mean anything. The generator only
 * runs for blocks carrying per-device data, so a post saved before this change —
 * value on the ROOT attribute, no `style` entry — would lose its shadow
 * entirely. `backward_compatibility_block_attributes()` bridges exactly that,
 * but only for attributes present in `ATTR_DEFINITIONS`.
 *
 * `test_legacy_root_attribute_still_paints()` is written to pass BEFORE and
 * AFTER: it asserts the shadow reaches the page at all, by either route. It is
 * the regression guard, not a description of the new mechanism.
 *
 * Scope note: only the two blocks that expose a Shadow Hover control —
 * `spectra/button` and `spectra-pro/form-button`. The Pro half is registered
 * through `spectra_blocks_responsive_attr_definitions`, so it is covered by the
 * Pro suite rather than here.
 *
 * @package Spectra\Tests
 * @since   1.0.13
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * ShadowHoverPerBandTest test case.
 *
 * @since 1.0.13
 */
class ShadowHoverPerBandTest extends WP_UnitTestCase {

	/**
	 * A shadow object, so the cases read as values rather than literals.
	 *
	 * @since 1.0.13
	 * @param int    $y     Vertical offset.
	 * @param string $color Shadow colour.
	 * @return array<string, mixed> The shadow object.
	 */
	private function shadow( int $y, string $color ): array {
		return array(
			'x'      => 0,
			'y'      => $y,
			'blur'   => 8,
			'spread' => 0,
			'color'  => $color,
		);
	}

	/**
	 * Render a button and return what it painted, split by band.
	 *
	 * @since 1.0.13
	 * @param array<string, mixed> $attrs Block attributes, minus `spectraId`.
	 * @return array{markup: string, base: string, tablet: string, mobile: string}
	 */
	private function paint( array $attrs ): array {
		$id = 'sh' . wp_generate_password( 6, false );
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );

		$attrs['spectraId'] = $id;
		$attrs['text']      = 'Button';

		/*
		 * Self-closing, and inside its parent. `spectra/button` declares
		 * `parent: [ "spectra/buttons" ]` and renders from `view.php`, so the
		 * open/close form with placeholder inner HTML renders NOTHING — measured,
		 * zero bytes — and every assertion here would have passed or failed for
		 * reasons unrelated to the shadow.
		 */
		$markup = do_blocks(
			'<!-- wp:spectra/buttons {"spectraId":"' . $id . 'p"} -->'
			. '<!-- wp:spectra/button ' . wp_json_encode( $attrs ) . ' /-->'
			. '<!-- /wp:spectra/buttons -->'
		);

		$css = (string) preg_replace( '/\s+/', ' ', implode( '', (array) ( wp_styles()->get_data( 'spectra-responsive-styles', 'after' ) ?: array() ) ) );

		// Band queries come from the resolver, never a literal — below 7.1 the
		// plugin emits its own fallback bands and a hardcoded query matches none.
		$queries = ResponsiveControls::instance()->get_media_queries();
		$t       = isset( $queries['@tablet'] ) ? strpos( $css, '@media ' . $queries['@tablet'] ) : false;
		$m       = isset( $queries['@mobile'] ) ? strpos( $css, '@media ' . $queries['@mobile'] ) : false;

		return array(
			'markup' => $markup,
			'base'   => substr( $css, 0, false === $t ? strlen( $css ) : $t ),
			'tablet' => false === $t ? '' : substr( $css, $t, ( false === $m ? strlen( $css ) : $m ) - $t ),
			'mobile' => false === $m ? '' : substr( $css, $m ),
		);
	}

	/**
	 * THE GUARD — legacy content still reaches the page.
	 *
	 * A post saved before this change stores the shadow object on the root
	 * attribute with no `style` entry. Asserted against the whole output —
	 * inline style OR generated rule — so it holds whichever route paints it.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_legacy_root_attribute_still_paints() {
		$out = $this->paint( array( 'shadowHover' => $this->shadow( 4, 'rgb(1, 2, 3)' ) ) );
		$all = $out['markup'] . $out['base'] . $out['tablet'] . $out['mobile'];

		/*
		 * A `box-shadow` DECLARATION, not merely the colour somewhere on the
		 * page. An earlier version of this assertion checked only that the value
		 * appeared, and passed while the shadow did not render at all: the value
		 * was emitted into a custom property whose class-gated rule never
		 * matched, because registering the attribute as responsive strips it
		 * from the root and the controller can no longer emit the hook class.
		 */
		$this->assertMatchesRegularExpression(
			'/box-shadow\s*:[^;}]*rgb\(1, 2, 3\)/',
			$all,
			'A pre-existing post stores shadowHover on the root attribute only. '
			. 'It must still produce a box-shadow declaration that actually applies.'
		);
	}

	/**
	 * A shadow with no colour paints nothing, before or after.
	 *
	 * Both the controller and `render.js` bail when the colour is empty, because
	 * a shadow with no colour is not a shadow. The generator has to agree, or an
	 * enabled-but-uncoloured shadow would start painting a band that the base
	 * never had.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_shadow_without_colour_paints_nothing() {
		$out = $this->paint( array( 'shadowHover' => $this->shadow( 4, '' ) ) );

		$this->assertStringNotContainsString( '--spectra-shadow-hover', $out['markup'] );
		$this->assertStringNotContainsString( '--spectra-shadow-hover', $out['base'] );
	}

	/**
	 * A per-device shadow paints its own band.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_shadow_paints_per_band() {
		$out = $this->paint(
			array(
				'style' => array(
					'shadowHover' => $this->shadow( 4, 'rgb(10, 20, 30)' ),
					'@mobile'     => array( 'shadowHover' => $this->shadow( 2, 'rgb(40, 50, 60)' ) ),
				),
			)
		);

		$this->assertMatchesRegularExpression( '/box-shadow\s*:[^;}]*rgb\(10, 20, 30\)/', $out['base'], 'Base must paint the authored desktop shadow.' );
		$this->assertMatchesRegularExpression( '/box-shadow\s*:[^;}]*rgb\(40, 50, 60\)/', $out['mobile'], 'Mobile must override the base.' );
		$this->assertStringNotContainsString( 'rgb(40, 50, 60)', $out['base'], 'The mobile shadow must not leak into the base.' );
	}

	/**
	 * The band emits a CSS shadow string, not the raw object.
	 *
	 * The formatter is the whole reason this attribute needs one. Asserting the
	 * shape directly catches the case where a band emits `Array` or a JSON blob
	 * and the rule is silently dropped by the browser.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_band_emits_a_css_shadow_string() {
		$out = $this->paint(
			array(
				'style' => array(
					'@mobile' => array( 'shadowHover' => $this->shadow( 2, 'rgb(7, 8, 9)' ) ),
				),
			)
		);

		$this->assertMatchesRegularExpression(
			'/box-shadow\s*:\s*0px\s+2px\s+8px\s+0px\s+rgb\(7, 8, 9\)/',
			$out['mobile'],
			'The band must carry the flattened shadow string, matching the controller exactly.'
		);
	}

	/**
	 * Container: both shadows band, and they use their own custom properties.
	 *
	 * Container stores an already-flattened CSS STRING rather than the object
	 * form the Button uses, and reads it back through
	 * `--spectra-box-shadow` / `--spectra-box-shadow-hover`. One formatter serves
	 * both, so this covers the string path and the `:hover` state in one case.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_container_shadows_band_with_their_own_variables() {
		$id = 'cs' . wp_generate_password( 6, false );
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );

		do_blocks(
			'<!-- wp:spectra/container ' . wp_json_encode(
				array(
					'spectraId' => $id,
					'style'     => array(
						'boxShadow'      => '0px 4px 8px 0px rgb(1, 1, 1)',
						'boxShadowHover' => '0px 8px 16px 0px rgb(2, 2, 2)',
						'@mobile'        => array( 'boxShadow' => '0px 1px 2px 0px rgb(3, 3, 3)' ),
					),
				)
			) . ' --><div data-spectra-id="' . $id . '" class="wp-block-spectra-container"></div><!-- /wp:spectra/container -->'
		);

		$css     = (string) preg_replace( '/\s+/', ' ', implode( '', (array) ( wp_styles()->get_data( 'spectra-responsive-styles', 'after' ) ?: array() ) ) );
		$queries = ResponsiveControls::instance()->get_media_queries();
		$m       = isset( $queries['@mobile'] ) ? strpos( $css, '@media ' . $queries['@mobile'] ) : false;
		$base    = substr( $css, 0, false === $m ? strlen( $css ) : $m );
		$mobile  = false === $m ? '' : substr( $css, $m );

		$this->assertMatchesRegularExpression( '/box-shadow\s*:\s*0px 4px 8px 0px rgb\(1, 1, 1\)/', $base, 'Base shadow must paint.' );
		$this->assertMatchesRegularExpression( '/:hover\{box-shadow\s*:\s*0px 8px 16px 0px rgb\(2, 2, 2\)/', $base, 'Hover shadow must paint on :hover.' );
		$this->assertStringContainsString( 'rgb(3, 3, 3)', $mobile, 'Mobile must override the base shadow.' );
		$this->assertStringNotContainsString( 'rgb(3, 3, 3)', $base, 'The mobile shadow must not leak into the base.' );
	}

	/**
	 * The block no longer writes the shadow into its inline style attribute.
	 *
	 * The regression guard for the controller change: an inline base outranks
	 * every band rule, so if inline paint returns the per-device values stop
	 * working without any CSS assertion failing.
	 *
	 * The ROOT attribute deliberately — that is what paints inline today.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_shadow_is_not_painted_inline() {
		$out = $this->paint( array( 'shadowHover' => $this->shadow( 4, 'rgb(99, 98, 97)' ) ) );

		$this->assertDoesNotMatchRegularExpression(
			'/style="[^"]*rgb\(99, 98, 97\)/i',
			$out['markup'],
			'An inline hover shadow would beat the band rules at any specificity.'
		);
	}
}
