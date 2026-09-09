<?php
/**
 * The below-7.1 viewport bands never overlap.
 *
 * WordPress older than 7.1 has no `WP_Theme_JSON::get_viewport_media_queries()`,
 * so the plugin builds the three device bands itself, in classic
 * `min-width` / `max-width` form. An earlier build let neighbouring bands share
 * their edge — `(max-width: 480px)` next to `(min-width: 480px)` — on the
 * reasoning that overlap was harmless because the later band's property wins.
 * That is true for merged property rules and false for rules keyed on a device
 * class with `!important`, which target different selectors and simply both
 * apply: at exactly 782 px "hide on desktop" and "hide on tablet" blocks were
 * both hidden, and a desktop `orientationReverse` reversed inside the tablet
 * band. Core's own 7.1 ranges (`480px < width <= 782px`) cannot do this.
 *
 * This file pins that every lower edge sits above the neighbouring upper edge,
 * for the defaults, a theme's own pixel values, `em` values, and the single
 * breakpoint shapes core also accepts.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use ReflectionMethod;
use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * FallbackBandEdgesTest test case.
 *
 * @since x.x.x
 */
class FallbackBandEdgesTest extends WP_UnitTestCase {

	/**
	 * Run the fallback for a viewport value.
	 *
	 * @since x.x.x
	 * @param mixed $viewport Raw `settings.viewport` value.
	 * @return array<string, string> Bands keyed by state.
	 */
	private function bands( $viewport ): array {
		$method = new ReflectionMethod( ResponsiveControls::class, 'viewport_bands_fallback' );
		$method->setAccessible( true );

		return $method->invoke( ResponsiveControls::instance(), $viewport, true );
	}

	/**
	 * Read the `min-width` / `max-width` pixel numbers out of a band.
	 *
	 * @since x.x.x
	 * @param string $band A media condition.
	 * @return array{min: float|null, max: float|null} The edges in px.
	 */
	private function edges( string $band ): array {
		$min = preg_match( '/min-width:\s*([\d.]+)px/', $band, $m ) ? (float) $m[1] : null;
		$max = preg_match( '/max-width:\s*([\d.]+)px/', $band, $m ) ? (float) $m[1] : null;

		return array(
			'min' => $min,
			'max' => $max,
		);
	}

	/**
	 * With no theme viewport the three default bands are disjoint.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_default_bands_do_not_share_an_edge() {
		$bands = $this->bands( null );

		$this->assertSame( '(max-width: 480px)', $bands['@mobile'] );
		$this->assertSame( '(min-width: 480.02px) and (max-width: 782px)', $bands['@tablet'] );
		$this->assertSame( '(min-width: 782.02px)', $bands['@desktop'] );
	}

	/**
	 * No integer width satisfies two bands at once.
	 *
	 * 480 and 782 are the widths that used to match two bands; 481 and 783 are
	 * the first widths of the next band up. Every one must match exactly one.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_each_breakpoint_width_matches_exactly_one_band() {
		$bands = $this->bands( null );

		foreach ( array( 479, 480, 481, 782, 783 ) as $width ) {
			$matching = array();

			foreach ( $bands as $state => $band ) {
				$edges = $this->edges( $band );
				$above = null === $edges['min'] || $width >= $edges['min'];
				$below = null === $edges['max'] || $width <= $edges['max'];

				if ( $above && $below ) {
					$matching[] = $state;
				}
			}

			$this->assertCount( 1, $matching, "{$width}px must sit in exactly one band, got: " . implode( ', ', $matching ) );
		}
	}

	/**
	 * A theme's own pixel breakpoints keep the step.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_theme_pixel_breakpoints_are_disjoint() {
		$bands = $this->bands(
			array(
				'mobile' => '600px',
				'tablet' => '1000px',
			)
		);

		$this->assertSame( '(max-width: 600px)', $bands['@mobile'] );
		$this->assertSame( '(min-width: 600.02px) and (max-width: 1000px)', $bands['@tablet'] );
		$this->assertSame( '(min-width: 1000.02px)', $bands['@desktop'] );
	}

	/**
	 * Fractional theme values are formatted without trailing zeros.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_fractional_pixel_breakpoints_format_cleanly() {
		$bands = $this->bands(
			array(
				'mobile' => '767.98px',
				'tablet' => '1023.98px',
			)
		);

		$this->assertSame( '(min-width: 768px) and (max-width: 1023.98px)', $bands['@tablet'], 'Spectra\'s historical .98 bounds step up to the whole pixel.' );
		$this->assertSame( '(min-width: 1024px)', $bands['@desktop'] );
	}

	/**
	 * `em` / `rem` breakpoints keep their unit; the step goes through `calc()`.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_relative_units_step_through_calc() {
		$bands = $this->bands(
			array(
				'mobile' => '30em',
				'tablet' => '48rem',
			)
		);

		$this->assertSame( '(max-width: 30em)', $bands['@mobile'] );
		$this->assertSame( '(min-width: calc(30em + 0.02px)) and (max-width: 48rem)', $bands['@tablet'] );
		$this->assertSame( '(min-width: calc(48rem + 0.02px))', $bands['@desktop'] );
	}

	/**
	 * A theme declaring a single breakpoint yields two disjoint bands.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_single_breakpoint_shapes_are_disjoint() {
		$mobile_only = $this->bands( array( 'mobile' => '500px' ) );
		$this->assertSame( array( '@mobile', '@desktop' ), array_keys( $mobile_only ) );
		$this->assertSame( '(min-width: 500.02px)', $mobile_only['@desktop'] );

		$tablet_only = $this->bands( array( 'tablet' => '900px' ) );
		$this->assertSame( array( '@tablet', '@desktop' ), array_keys( $tablet_only ) );
		$this->assertSame( '(max-width: 900px)', $tablet_only['@tablet'] );
		$this->assertSame( '(min-width: 900.02px)', $tablet_only['@desktop'] );
	}
}
