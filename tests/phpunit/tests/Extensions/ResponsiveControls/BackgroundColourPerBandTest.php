<?php
/**
 * Per-device background colour — B3a, the mechanism proof for the colour batch.
 *
 * Colour is 329 of the remaining attributes and they share one shape, so this
 * case pins the shape down rather than covering one block.
 *
 * WHY THIS TESTS THE GENERATOR AND NOT A BLOCK. Of the seventeen blocks whose
 * `backgroundColor` control is already reachable, not one in the FREE plugin has
 * the simple shape: `spectra/tabs` publishes the value as context and a child
 * paints it, and container, popup-builder, slider and slider-child each paint
 * through a `::before` whose selector combines the colour class with the image,
 * gradient and video classes. The nine simple ones are all Pro, and are covered
 * by `Test_Background_Colour_Per_Band` there. The formatter lives here, so the
 * mechanism is proved here — driven through the public filter, against the real
 * `generate_css()`, with no block in the way.
 *
 * WHAT MAKES COLOUR DIFFERENT FROM THE LENGTHS IN B2.
 *
 * 1. The Style Engine silently DROPS any value containing a colour function.
 *    Asserted directly in `test_the_style_engine_still_drops_rgb()` rather than
 *    taken on trust, because the whole batch is shaped around it and a future
 *    WordPress release may fix it.
 *
 * 2. The paint is CLASS-GATED. `backgroundColor` normally reaches the page as
 *    `--spectra-background-color` plus a `spectra-background-color` class, read
 *    back by `.spectra-background-color { … }` — 149 such selectors across the
 *    two plugins. Registering an attribute as responsive UNSETS it from the root
 *    before `render_block`, so the controller stops emitting the class and the
 *    rule stops matching. The value would reach the page and never be read.
 *
 * @package Spectra\Tests
 * @since   1.0.13
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS;
use WP_UnitTestCase;

/**
 * BackgroundColourPerBandTest test case.
 *
 * @since 1.0.13
 */
class BackgroundColourPerBandTest extends WP_UnitTestCase {

	/**
	 * A block name that exists only for this case.
	 *
	 * Deliberately not a real block: this proves the FORMATTER, and a real block
	 * would drag its own painters, context and stylesheet into the assertions.
	 *
	 * @since 1.0.13
	 * @var string
	 */
	private const BLOCK = 'spectra/test-bg-colour';

	/**
	 * Register the painter through the public filter.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_filter(
			'spectra_blocks_responsive_attr_definitions',
			static function ( $defs ) {
				$defs[ self::BLOCK ] = array(
					'backgroundColor' => array(
						'property'  => 'background-color',
						'formatter' => 'format_colour',
					),
					'textColor'       => array(
						'property'  => 'color',
						'formatter' => 'format_colour',
					),
				);
				return $defs;
			}
		);
	}

	/**
	 * Generate the CSS one band would emit for a colour.
	 *
	 * @since 1.0.13
	 * @param mixed $colour The stored colour.
	 * @return string The generated CSS.
	 */
	private function css_for( $colour ): string {
		return (string) ResponsiveAttributeCSS::generate_css(
			self::BLOCK,
			array( 'backgroundColor' => $colour ),
			'.sel',
			'.sel',
			array( 'backgroundColor' => $colour )
		);
	}

	/**
	 * THE PREMISE — the Style Engine really does drop colour functions.
	 *
	 * The entire batch is shaped around this, so it is asserted rather than
	 * assumed. If WordPress fixes it, this fails and the `style_attr` bypass can
	 * be reconsidered — which is exactly the signal wanted.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_the_style_engine_still_drops_rgb() {
		$rule = static fn( $v ) => wp_style_engine_get_stylesheet_from_css_rules(
			array( array( 'selector' => '.x', 'declarations' => array( 'background-color' => $v ) ) )
		);

		$this->assertNotSame( '', $rule( '#123456' ), 'A hex colour is emitted.' );
		$this->assertNotSame( '', $rule( 'red' ), 'A named colour is emitted.' );
		$this->assertSame( '', $rule( 'rgb(10, 20, 30)' ), 'rgb() is dropped — this is why the batch uses style_attr.' );
		$this->assertSame( '', $rule( 'rgba(1, 2, 3, .5)' ), 'rgba() is dropped too.' );
	}

	/**
	 * THE ONE THAT MATTERS — an `rgb()` colour survives to the page.
	 *
	 * Written with `rgb()` rather than a hex on purpose: a hex passes whether or
	 * not the engine is bypassed, so it would prove nothing. This fails against
	 * the `declarations` path and passes only against `style_attr`.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_rgb_survives() {
		$this->assertMatchesRegularExpression(
			'/background-color\s*:\s*rgb\(\s*10,\s*20,\s*30\s*\)/',
			$this->css_for( 'rgb(10, 20, 30)' ),
			'An rgb() colour must reach the page. If this fails, the Style Engine ate it.'
		);
		$this->assertMatchesRegularExpression(
			'/background-color\s*:\s*rgba\(\s*4,\s*5,\s*6,\s*0?\.5\s*\)/',
			$this->css_for( 'rgba(4, 5, 6, 0.5)' ),
			'An rgba() colour must reach the page.'
		);
	}

	/**
	 * A real property, not the class-gated custom property.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_emits_a_real_property_not_a_variable() {
		$css = $this->css_for( '#112233' );

		$this->assertStringContainsString( 'background-color:#112233', str_replace( ' ', '', $css ), 'The rule must carry a background-color declaration.' );
		$this->assertStringContainsString(
			'.sel{',
			str_replace( ' ', '', $css ),
			'The rule must be scoped to the block, once. A formatter returning the full selector produces `.sel .sel`, because emission prepends it to whatever the rule carries.'
		);
		$this->assertStringNotContainsString(
			'--spectra-background-color',
			$css,
			'A custom property alone is not enough: the class that reads it is no longer emitted.'
		);
	}

	/**
	 * Hex, named and preset-var colours all still work.
	 *
	 * The bypass must not regress the values that already worked.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_other_colour_forms_still_work() {
		foreach ( array( '#112233', 'red', 'var(--wp--preset--color--vivid-red)' ) as $colour ) {
			$this->assertStringContainsString(
				'background-color:' . $colour,
				str_replace( ' ', '', $this->css_for( $colour ) ),
				"The colour form {$colour} must still be emitted."
			);
		}
	}

	/**
	 * Nothing stored emits no rule, so the cascade handles inheritance.
	 *
	 * A band that emitted a default would override a base the author set.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_empty_emits_nothing() {
		foreach ( array( '', '   ', null, 123 ) as $empty ) {
			$this->assertStringNotContainsString( 'background-color', $this->css_for( $empty ), 'An unset colour must emit no rule.' );
		}
	}

	/**
	 * THE GUARD — a post saved before this change still gets its colour.
	 *
	 * Registering an attribute as responsive unsets it from the root before
	 * render, and the generator only emits for blocks carrying per-device data.
	 * A post saved earlier has the colour on the ROOT attribute and no `style`
	 * entry, so without the bridge it would lose its background entirely.
	 *
	 * `backward_compatibility_block_attributes()` maps the root attribute onto
	 * the base layer, but only for attributes present in the definitions — which
	 * is why the painter and the registration have to land in the same change.
	 * Asserted against the bridge directly rather than through a render: the
	 * eight blocks that use this are inner blocks that need a parent and a query
	 * to render at all.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_legacy_root_attribute_is_bridged_to_the_base_layer() {
		$bridged = ResponsiveControls::instance()->backward_compatibility_block_attributes(
			array(
				'blockName' => self::BLOCK,
				'attrs'     => array( 'backgroundColor' => 'rgb(1, 2, 3)' ),
			)
		);

		$store = $bridged['attrs']['responsiveControls'] ?? array();

		$this->assertSame(
			'rgb(1, 2, 3)',
			$store['base']['backgroundColor'] ?? null,
			'A colour stored only on the root attribute must be bridged onto the base layer, or pre-existing posts lose their background.'
		);

		// And the bridge is selective, so the assertion above means something:
		// it copies REGISTERED attributes, not whatever the block happens to hold.
		$unregistered = ResponsiveControls::instance()->backward_compatibility_block_attributes(
			array(
				'blockName' => self::BLOCK,
				'attrs'     => array( 'someAttributeNobodyRegistered' => 'x' ),
			)
		);

		$this->assertArrayNotHasKey(
			'someAttributeNobodyRegistered',
			$unregistered['attrs']['responsiveControls']['base'] ?? array(),
			'The bridge must copy only registered attributes.'
		);
	}

	/**
	 * A value that could close the declaration and open another is refused.
	 *
	 * `style_attr` is emitted raw, so this is the one place the bypass costs
	 * something and it has to be paid for explicitly.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_rule_injection_is_refused() {
		foreach (
			array(
				'red;}body{display:none',
				'red; background-image: url(//evil)',
				'red/*x*/',
				'<script>',
			) as $hostile
		) {
			$this->assertStringNotContainsString(
				'background-color',
				$this->css_for( $hostile ),
				"A colour containing CSS punctuation must be refused: {$hostile}"
			);
		}
	}
}
