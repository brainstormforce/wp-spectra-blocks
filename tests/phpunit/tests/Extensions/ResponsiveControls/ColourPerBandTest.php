<?php
/**
 * Every colour attribute the free plugin bands, checked against its own definition.
 *
 * B3b. `BackgroundColourPerBandTest` pins the mechanism and explains why colour
 * cannot go through the ordinary `property` path; this one is DATA-DRIVEN. It
 * reads `ATTR_DEFINITIONS` through the public filter and asserts that every
 * entry using `format_colour` emits the property it declares, on the element it
 * declares. A colour added later is covered the moment it is added.
 *
 * That matters because the property is NOT predictable from the attribute name.
 * Measured across this batch: `textColor` paints `color` on nine blocks and
 * `fill` on `modal-child-popup-close-icon`; `iconColor` paints `fill` on a
 * `.spectra-button__icon` CHILD, not on the block. Every definition in the batch
 * was read out of the block's own `style.scss` by hand — an attempt to derive
 * them mechanically got `spectra/button :: iconColor` wrong, putting it on the
 * block instead of the icon, which is why the SELECTOR is asserted here and not
 * only the declaration.
 *
 * @package Spectra\Tests
 * @since   1.0.13
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS;
use WP_UnitTestCase;

/**
 * ColourPerBandTest test case.
 *
 * @since 1.0.13
 */
class ColourPerBandTest extends WP_UnitTestCase {

	/**
	 * Every attribute registered with the colour formatter.
	 *
	 * Read from the definitions rather than listed here: a copy would be one
	 * more thing to keep in sync and would pass while the real list drifted.
	 *
	 * @since 1.0.13
	 * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
	 */
	public function colour_provider(): array {
		$out = array();

		foreach ( ResponsiveAttributeCSS::ATTR_DEFINITIONS as $block => $attributes ) {
			foreach ( $attributes as $attribute => $definition ) {
				if ( ( $definition['formatter'] ?? '' ) !== 'format_colour' ) {
					continue;
				}
				$out[ "{$block} :: {$attribute}" ] = array( $block, $attribute, $definition );
			}
		}

		return $out;
	}

	/**
	 * The provider found something.
	 *
	 * Without this, a provider that silently returned nothing would leave the
	 * whole case green while covering not one attribute.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_the_provider_is_not_empty() {
		$this->assertNotEmpty( $this->colour_provider(), 'No colour attributes are registered — this case would be vacuous.' );
	}

	/**
	 * Generate the CSS one band emits for a colour.
	 *
	 * @since 1.0.13
	 * @param string $block  Block name.
	 * @param string $attr   Attribute name.
	 * @param mixed  $colour The stored colour.
	 * @return string The generated CSS.
	 */
	private function css_for( string $block, string $attr, $colour ): string {
		return (string) ResponsiveAttributeCSS::generate_css(
			$block,
			array( $attr => $colour ),
			'.S',
			'.S',
			array( $attr => $colour )
		);
	}

	/**
	 * Each definition emits the property it declares, on the element it declares.
	 *
	 * @since 1.0.13
	 * @dataProvider colour_provider
	 * @param string               $block      Block name.
	 * @param string               $attribute  Attribute name.
	 * @param array<string, mixed> $definition Its definition.
	 * @return void
	 */
	public function test_emits_the_declared_property_on_the_declared_element( string $block, string $attribute, array $definition ) {
		$property = $definition['property'] ?? '';

		/*
		 * `state` is part of the selector, not a separate thing: the formatter
		 * appends it after `selector`, so a hover colour lands on `.S:hover` and
		 * an active one on `.S[data-is-active='true']`. Asserting the base
		 * selector alone would fail every state colour — and, worse, would pass
		 * a state colour that had silently lost its state.
		 */
		$selector = ( $definition['selector'] ?? '' ) . ( $definition['state'] ?? '' );

		$this->assertNotSame( '', $property, "{$block} :: {$attribute} must declare a property; the formatter emits nothing without one." );

		$css = $this->css_for( $block, $attribute, '#112233' );

		/*
		 * A comma list becomes ONE RULE PER PART, each separately scoped — the
		 * formatter splits it so `:focus, :hover` cannot emit `.S:focus, :hover`,
		 * whose second half would match every hovered element on the page. The
		 * expectation has to model the same split.
		 *
		 * Asserted against the RAW css, never a space-stripped copy: stripping
		 * spaces turns `.S .x` into `.S.x`, a different selector — a descendant
		 * versus one element carrying both classes.
		 */
		$combinator = ' ' === substr( $selector, 0, 1 ) ? ' ' : '';
		$parts      = array_filter( array_map( 'trim', explode( ',', $selector ) ), 'strlen' );
		$parts      = $parts ? $parts : array( '' );

		foreach ( $parts as $part ) {
			$expected = '.S' . ( '' === $part ? '' : $combinator . $part );

			$this->assertStringContainsString(
				$expected . '{',
				$css,
				"{$block} :: {$attribute} must paint on '{$expected}'."
			);
			$this->assertMatchesRegularExpression(
				'/' . preg_quote( $expected, '/' ) . '\{[^}]*' . preg_quote( $property, '/' ) . ':#112233/',
				$css,
				"{$block} :: {$attribute} must emit '{$property}' inside the rule for '{$expected}'."
			);
		}
	}

	/**
	 * THE ONE THAT MATTERS — `rgb()` survives for every registered colour.
	 *
	 * @since 1.0.13
	 * @dataProvider colour_provider
	 * @param string               $block      Block name.
	 * @param string               $attribute  Attribute name.
	 * @param array<string, mixed> $definition Its definition.
	 * @return void
	 */
	public function test_rgb_survives( string $block, string $attribute, array $definition ) {
		$property = $definition['property'] ?? '';
		$value    = 'opacity' === $property ? '0.5' : 'rgb(10, 20, 30)';

		$this->assertStringContainsString(
			$property . ':' . $value,
			$this->css_for( $block, $attribute, $value ),
			"{$block} :: {$attribute} must emit an rgb() colour; if it does not, the Style Engine ate it."
		);
	}

	/**
	 * The editor declares every attribute the renderer paints.
	 *
	 * A painter with no editor key is capability nobody can reach; the reverse
	 * is worse, and both are caught by `ResponsiveKeyParityTest` in aggregate.
	 * This pins it per attribute so a failure names the one that broke.
	 *
	 * @since 1.0.13
	 * @dataProvider colour_provider
	 * @param string $block     Block name.
	 * @param string $attribute Attribute name.
	 * @return void
	 */
	public function test_the_renderer_knows_the_attribute( string $block, string $attribute ) {
		$this->assertContains(
			$attribute,
			ResponsiveAttributeCSS::get_responsive_attributes( $block ),
			"{$block} must declare {$attribute} as a banded attribute."
		);
	}
}
