<?php
/**
 * Tests for the Sticky Container per-device setting.
 *
 * @package Spectra\Tests\Extensions
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions;

use SpectraBlocks\Extensions\ResponsiveControls;
use SpectraBlocks\Extensions\StickyContainer;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * StickyContainerDevicesTest test case.
 *
 * @since 1.0.16
 */
class StickyContainerDevicesTest extends WP_UnitTestCase {

	/**
	 * Render a container with the given stickyContainer attribute through the render_block filter.
	 *
	 * @param array<string, mixed> $sticky_container Stored stickyContainer attribute.
	 * @return string Filtered HTML.
	 */
	private function render( array $sticky_container ): string {
		return StickyContainer::instance()->add_sticky_styles_to_blocks(
			'<div class="wp-block-spectra-container"><p>Inner</p></div>',
			array(
				'blockName'    => 'spectra/container',
				'attrs'        => array( 'stickyContainer' => $sticky_container ),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * The container wrapper, positioned for reading attributes by name.
	 *
	 * @param string $html Rendered HTML.
	 * @return WP_HTML_Tag_Processor
	 */
	private function wrapper( string $html ): WP_HTML_Tag_Processor {
		$processor = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $processor->next_tag( 'div' ) );

		return $processor;
	}

	/**
	 * The device bands "Hide on Desktop / Tablet / Mobile" switch at.
	 *
	 * @return array<string, string>
	 */
	private function bands(): array {
		return ResponsiveControls::instance()->get_device_media_queries();
	}

	/**
	 * A container saved before the device settings existed keeps sticking everywhere, with unchanged markup.
	 */
	public function test_container_without_device_keys_sticks_everywhere() {
		$wrapper = $this->wrapper(
			$this->render(
				array(
					'stickyEnabled' => true,
					'stickAt'       => 'top',
					'offset'        => '0px',
				)
			)
		);

		$this->assertTrue( $wrapper->has_class( 'spectra-sticky-container' ) );
		$this->assertNull( $wrapper->get_attribute( 'data-spectra-sticky-media' ) );
	}

	/**
	 * All three devices on is the same as the default: no media query.
	 */
	public function test_all_devices_on_adds_no_media_query() {
		$wrapper = $this->wrapper(
			$this->render(
				array(
					'stickyEnabled'   => true,
					'stickyOnDesktop' => true,
					'stickyOnTablet'  => true,
					'stickyOnMobile'  => true,
				)
			)
		);

		$this->assertTrue( $wrapper->has_class( 'spectra-sticky-container' ) );
		$this->assertNull( $wrapper->get_attribute( 'data-spectra-sticky-media' ) );
	}

	/**
	 * The reported case: sticky on desktop and tablet, not on mobile.
	 */
	public function test_mobile_off_limits_sticky_to_desktop_and_tablet_bands() {
		$bands   = $this->bands();
		$wrapper = $this->wrapper(
			$this->render(
				array(
					'stickyEnabled'  => true,
					'stickyOnMobile' => false,
				)
			)
		);

		$this->assertTrue( $wrapper->has_class( 'spectra-sticky-container' ) );
		$this->assertSame(
			$bands['@desktop'] . ', ' . $bands['@tablet'],
			$wrapper->get_attribute( 'data-spectra-sticky-media' )
		);
	}

	/**
	 * Sticky on mobile only uses the mobile band alone.
	 */
	public function test_mobile_only_uses_the_mobile_band() {
		$wrapper = $this->wrapper(
			$this->render(
				array(
					'stickyEnabled'   => true,
					'stickyOnDesktop' => false,
					'stickyOnTablet'  => false,
				)
			)
		);

		$this->assertSame( $this->bands()['@mobile'], $wrapper->get_attribute( 'data-spectra-sticky-media' ) );
	}

	/**
	 * Every device off: the container is not made sticky at all.
	 */
	public function test_no_device_on_leaves_container_untouched() {
		$content = '<div class="wp-block-spectra-container"><p>Inner</p></div>';
		$html    = $this->render(
			array(
				'stickyEnabled'   => true,
				'stickyOnDesktop' => false,
				'stickyOnTablet'  => false,
				'stickyOnMobile'  => false,
			)
		);

		$this->assertSame( $content, $html );
	}

	/**
	 * Sticky turned off still wins over the device settings.
	 */
	public function test_sticky_disabled_ignores_device_settings() {
		$wrapper = $this->wrapper(
			$this->render(
				array(
					'stickyEnabled'  => false,
					'stickyOnMobile' => false,
				)
			)
		);

		$this->assertFalse( $wrapper->has_class( 'spectra-sticky-container' ) );
		$this->assertNull( $wrapper->get_attribute( 'data-spectra-sticky-media' ) );
	}
}
