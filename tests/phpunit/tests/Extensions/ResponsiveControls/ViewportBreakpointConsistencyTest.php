<?php
/**
 * Every breakpoint the plugin acts on comes from one resolver.
 *
 * The per-device CSS bands come from `ResponsiveControls::resolve_viewport_bands()`
 * — WordPress's `settings.viewport` where it exists, the plugin's fallback
 * below 7.1. Some consumers cannot take a media query: Swiper's `breakpoints`
 * want `min-width` numbers, the editor gets `tablet_breakpoint` /
 * `mobile_breakpoint` as integers. Those used to be hardcoded to Spectra's
 * historical 768 / 1024 while the CSS banded at core's 480 / 782, so a
 * carousel changed slides-per-view at a width where nothing else changed.
 *
 * `get_viewport_breakpoint_pixels()` and `get_viewport_min_widths()` are the
 * numeric face of the same resolver. This file pins that they agree with the
 * media queries, follow a theme's own viewport, and that no source file has
 * quietly reintroduced a literal band.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * ViewportBreakpointConsistencyTest test case.
 *
 * @since x.x.x
 */
class ViewportBreakpointConsistencyTest extends WP_UnitTestCase {

	/**
	 * Theme viewport injected by the current test, if any.
	 *
	 * @since x.x.x
	 * @var array<string, string>|null
	 */
	private $theme_viewport = null;

	/**
	 * Remove the theme viewport override and its caches.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function tear_down() {
		$this->theme_viewport = null;
		remove_filter( 'wp_theme_json_data_theme', array( $this, 'inject_theme_viewport' ) );
		$this->reset_global_settings_cache();
		parent::tear_down();
	}

	/**
	 * Clear every cache between the theme.json data and `wp_get_global_settings()`.
	 *
	 * @since x.x.x
	 * @return void
	 */
	private function reset_global_settings_cache() {
		if ( class_exists( 'WP_Theme_JSON_Resolver' ) && method_exists( 'WP_Theme_JSON_Resolver', 'clean_cached_data' ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}
		wp_cache_flush();
	}

	/**
	 * Filter callback: declare `settings.viewport` as if the theme had.
	 *
	 * @since x.x.x
	 * @param \WP_Theme_JSON_Data $theme_json The theme's data.
	 * @return \WP_Theme_JSON_Data Data with the viewport set.
	 */
	public function inject_theme_viewport( $theme_json ) {
		if ( null === $this->theme_viewport ) {
			return $theme_json;
		}

		return $theme_json->update_with(
			array(
				'version'  => 3,
				'settings' => array( 'viewport' => $this->theme_viewport ),
			)
		);
	}

	/**
	 * Point the site at a theme viewport for the rest of the test.
	 *
	 * @since x.x.x
	 * @param array<string, string> $viewport Mobile / tablet upper bounds.
	 * @return void
	 */
	private function with_theme_viewport( array $viewport ) {
		// Below WordPress 7.1 `settings.viewport` is not a theme.json setting at
		// all — the schema drops it and the resolver falls back to core's 480 /
		// 782 by design (the below-7.1 contract). A theme viewport cannot be
		// followed there, so the tests that inject one only apply on 7.1+.
		if ( ! is_callable( array( '\WP_Theme_JSON', 'get_viewport_media_queries' ) ) ) {
			$this->markTestSkipped( 'A theme viewport is only honoured where WordPress core defines settings.viewport (7.1+); below that the resolver uses the fixed defaults.' );
		}

		$this->theme_viewport = $viewport;
		add_filter( 'wp_theme_json_data_theme', array( $this, 'inject_theme_viewport' ) );
		$this->reset_global_settings_cache();
	}

	/**
	 * With nothing declared, the numbers are WordPress core's defaults.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_defaults_are_core_breakpoints() {
		$controls = ResponsiveControls::instance();

		$this->assertSame( array( 'mobile' => 480.0, 'tablet' => 782.0 ), $controls->get_viewport_breakpoint_pixels() );
		$this->assertSame( array( 'mobile' => 0, 'tablet' => 481, 'desktop' => 783 ), $controls->get_viewport_min_widths() );
	}

	/**
	 * The numbers describe the same widths as the media queries.
	 *
	 * The tablet query must stop matching at exactly the pixel the mobile
	 * upper bound names, and the desktop band must start one whole pixel past
	 * the tablet bound — otherwise a Swiper key and the CSS band it is meant
	 * to shadow would disagree at the boundary.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_numbers_agree_with_the_media_queries() {
		$controls = ResponsiveControls::instance();
		$pixels   = $controls->get_viewport_breakpoint_pixels();
		$queries  = $controls->get_device_media_queries();

		$this->assertStringContainsString( (string) (int) $pixels['mobile'], $queries['@mobile'], 'The mobile query must name the mobile bound.' );
		$this->assertStringContainsString( (string) (int) $pixels['tablet'], $queries['@tablet'], 'The tablet query must name the tablet bound.' );
		$this->assertStringContainsString( (string) (int) $pixels['tablet'], $queries['@desktop'], 'The desktop query must start at the tablet bound.' );
	}

	/**
	 * A theme's own breakpoints drive the numbers too — including Spectra's old ones.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_theme_viewport_is_followed() {
		$this->with_theme_viewport(
			array(
				'mobile' => '767.98px',
				'tablet' => '1023.98px',
			)
		);

		$controls = ResponsiveControls::instance();

		$this->assertSame( array( 'mobile' => 767.98, 'tablet' => 1023.98 ), $controls->get_viewport_breakpoint_pixels() );
		$this->assertSame( array( 'mobile' => 0, 'tablet' => 768, 'desktop' => 1024 ), $controls->get_viewport_min_widths(), 'Whole-pixel min widths above fractional bounds.' );
	}

	/**
	 * A nonsensical theme viewport (tablet not above mobile) falls back sanely.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_inverted_theme_viewport_falls_back_for_tablet() {
		$this->with_theme_viewport(
			array(
				'mobile' => '900px',
				'tablet' => '600px',
			)
		);

		$pixels = ResponsiveControls::instance()->get_viewport_breakpoint_pixels();

		$this->assertSame( 900.0, $pixels['mobile'] );
		$this->assertSame( 782.0, $pixels['tablet'], 'An inverted tablet bound yields the default rather than a band that ends before it starts.' );
	}

	/**
	 * No block SCSS or carousel controller carries Spectra's old literal bands.
	 *
	 * Static SCSS cannot follow the resolver, so it is pinned to core's
	 * defaults instead; the controllers must take their Swiper keys from the
	 * resolver. Either drifting back to 768 / 1024 is what this catches.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_sources_carry_no_historical_bands() {
		$root     = dirname( __DIR__, 5 );
		$offences = array();

		foreach ( glob( $root . '/src/blocks/*/style.scss' ) as $file ) {
			$source = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading plugin source in a test.

			if ( preg_match( '/@media[^{]*\b(767\.98|1023\.98|768|1024)px/', $source ) ) {
				$offences[] = str_replace( $root . '/', '', $file );
			}
		}

		foreach ( array( '/src/blocks/slider/controller.php', '/src/blocks/post/controller.php' ) as $file ) {
			$source = (string) file_get_contents( $root . $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading plugin source in a test.

			if ( preg_match( "/^\s*'?(768|1024)'?\s*=>/m", $source ) ) {
				$offences[] = ltrim( $file, '/' );
			}

			$this->assertStringContainsString( 'get_viewport_min_widths()', $source, "{$file} must take its Swiper breakpoints from the resolver." );
		}

		$this->assertSame( array(), $offences, "Historical 768/1024 bands found where the resolver's bands belong:\n- " . implode( "\n- ", $offences ) );
	}
}
