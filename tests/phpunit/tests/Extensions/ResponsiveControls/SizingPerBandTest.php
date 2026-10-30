<?php
/**
 * Progress-bar and close-icon sizing render per band, and legacy content keeps
 * rendering.
 *
 * These are plain lengths, which makes them the simplest shape this extension
 * handles — and a useful contrast with the shadows, whose commit explains why
 * they were not. Here:
 *
 *   - no hook CLASS is involved. `barHeight` and `barBorderRadius` already pass
 *     `class_name => null`, and `closeIconSize`'s class is emitted but appears
 *     in no selector — `style.scss` reads the variable from a plain `svg`
 *     descendant. So losing the class when the root attribute is stripped costs
 *     nothing.
 *   - no colour is involved, so the Style Engine's habit of dropping custom
 *     property values containing `rgb()` does not apply and the ordinary
 *     `declarations` path is safe.
 *
 * What DOES apply is the base. All three are painted inline today, and an inline
 * declaration outranks a stylesheet rule of any specificity, so the paint has to
 * move onto the generator for the bands to mean anything. The generator only
 * runs for blocks carrying per-device data, so a post saved before this change —
 * value on the ROOT attribute, no `style` entry — would lose its sizing
 * entirely. `backward_compatibility_block_attributes()` bridges that, but only
 * for attributes present in `ATTR_DEFINITIONS`.
 *
 * `test_legacy_root_attribute_still_paints()` is written to pass BEFORE and
 * AFTER: it asserts the value reaches the page at all, by either route.
 *
 * @package Spectra\Tests
 * @since   1.0.13
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * SizingPerBandTest test case.
 *
 * @since 1.0.13
 */
class SizingPerBandTest extends WP_UnitTestCase {

	/**
	 * Render a block and return what it painted, split by band.
	 *
	 * `spectra/counter-child-progress-bar` declares
	 * `parent: [ "spectra/counter", … ]`, so it is wrapped when asked for.
	 *
	 * @since 1.0.13
	 * @param string               $block_name Block name.
	 * @param array<string, mixed> $attrs      Block attributes, minus `spectraId`.
	 * @param string               $wrapper    Optional parent block name.
	 * @return array{markup: string, base: string, tablet: string, mobile: string}
	 */
	private function paint( string $block_name, array $attrs, string $wrapper = '' ): array {
		$id = 'sz' . wp_generate_password( 6, false );
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );

		$attrs['spectraId'] = $id;
		$block              = '<!-- wp:' . $block_name . ' ' . wp_json_encode( $attrs ) . ' /-->';
		$markup             = do_blocks(
			$wrapper
				? '<!-- wp:' . $wrapper . ' {"spectraId":"' . $id . 'p","counterStyle":"bar"} -->' . $block . '<!-- /wp:' . $wrapper . ' -->'
				: $block
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
	 * The three attributes, with the block and variable each one paints.
	 *
	 * @since 1.0.13
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
	 */
	public function sizing_provider(): array {
		/*
		 * Stored and rendered values are given separately on purpose: they are not
		 * the same thing. `barHeight` stores a full CSS length, `barBorderRadius`
		 * stores an integer and `closeIconSize` a bare numeric string, and each
		 * block appends its own unit. Conflating the two produced `30pxpx` and a
		 * test that failed for a reason unrelated to the feature.
		 *
		 * label => [ block, wrapper, attribute, css var, stored base, rendered base,
		 *            stored mobile, rendered mobile ]
		 */
		return array(
			'bar height'        => array( 'spectra/counter-child-progress-bar', 'spectra/counter', 'barHeight', '--spectra-counter-bar-height', '48px', '48px', '10px', '10px' ),
			'bar border radius' => array( 'spectra/counter-child-progress-bar', 'spectra/counter', 'barBorderRadius', '--spectra-counter-bar-border-radius', 9, '9px', 3, '3px' ),
			'close icon size'   => array( 'spectra/popup-builder', '', 'closeIconSize', '--spectra-close-icon-size', '30', '30px', '14', '14px' ),
		);
	}

	/**
	 * THE GUARD — legacy content still reaches the page.
	 *
	 * A post saved before this change stores the value on the root attribute with
	 * no `style` entry. Asserted against the whole output — inline style OR
	 * generated rule — so it holds whichever route paints it.
	 *
	 * @since 1.0.13
	 * @dataProvider sizing_provider
	 * @param string $block   Block name.
	 * @param string $wrapper Parent block, or ''.
	 * @param string $attr    Attribute name.
	 * @param string     $css_var  The custom property it paints.
	 * @param string|int $stored   The value as the block stores it.
	 * @param string     $rendered The value as it must appear in CSS.
	 * @return void
	 */
	public function test_legacy_root_attribute_still_paints( string $block, string $wrapper, string $attr, string $css_var, $stored, string $rendered ) {
		$out = $this->paint( $block, array( $attr => $stored ), $wrapper );
		$all = $out['markup'] . $out['base'] . $out['tablet'] . $out['mobile'];

		$this->assertMatchesRegularExpression(
			'/' . preg_quote( $css_var, '/' ) . '\s*:\s*' . preg_quote( $rendered, '/' ) . '/',
			$all,
			"A pre-existing post stores {$attr} on the root attribute only; it must still paint {$css_var}."
		);
	}

	/**
	 * A per-device value paints its own band and does not leak into the base.
	 *
	 * @since 1.0.13
	 * @dataProvider sizing_provider
	 * @param string $block   Block name.
	 * @param string $wrapper Parent block, or ''.
	 * @param string $attr    Attribute name.
	 * @param string     $css_var         The custom property it paints.
	 * @param string|int $stored          Base value as stored.
	 * @param string     $rendered        Base value as rendered.
	 * @param string|int $stored_mobile   Mobile value as stored.
	 * @param string     $rendered_mobile Mobile value as rendered.
	 * @return void
	 */
	public function test_sizing_paints_per_band( string $block, string $wrapper, string $attr, string $css_var, $stored, string $rendered, $stored_mobile, string $rendered_mobile ) {
		$out = $this->paint(
			$block,
			array(
				'style' => array(
					$attr     => $stored,
					'@mobile' => array( $attr => $stored_mobile ),
				),
			),
			$wrapper
		);

		$this->assertMatchesRegularExpression(
			'/' . preg_quote( $css_var, '/' ) . '\s*:\s*' . preg_quote( $rendered, '/' ) . '/',
			$out['base'],
			'Base must paint the authored desktop value.'
		);
		$this->assertMatchesRegularExpression(
			'/' . preg_quote( $css_var, '/' ) . '\s*:\s*' . preg_quote( $rendered_mobile, '/' ) . '/',
			$out['mobile'],
			'Mobile must override the base.'
		);
		$this->assertStringNotContainsString(
			$css_var . ':' . $rendered_mobile,
			$out['base'],
			'The mobile value must not leak into the base.'
		);
	}

	/**
	 * The block no longer writes these into its inline style attribute.
	 *
	 * The regression guard for the controller change: an inline base outranks
	 * every band rule, so if inline paint returns the per-device values stop
	 * working without any CSS assertion failing.
	 *
	 * The ROOT attribute deliberately — that is what paints inline today.
	 *
	 * @since 1.0.13
	 * @dataProvider sizing_provider
	 * @param string $block   Block name.
	 * @param string $wrapper Parent block, or ''.
	 * @param string $attr    Attribute name.
	 * @param string     $css_var The custom property it paints.
	 * @param string|int $stored  The value as the block stores it.
	 * @return void
	 */
	public function test_sizing_is_not_painted_inline( string $block, string $wrapper, string $attr, string $css_var, $stored ) {
		$out = $this->paint( $block, array( $attr => $stored ), $wrapper );

		$this->assertDoesNotMatchRegularExpression(
			'/style="[^"]*' . preg_quote( $css_var, '/' ) . '\s*:/',
			$out['markup'],
			'An inline value would beat the band rules at any specificity.'
		);
	}
}
