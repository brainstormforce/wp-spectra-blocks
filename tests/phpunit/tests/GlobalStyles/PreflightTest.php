<?php
/**
 * Tests for Tailwind v4-parity preflight rules emitted by the Global Styles engine.
 *
 * Every preflight rule must be scoped to `.spectra-is-root-container` so the
 * WP theme's non-Spectra UI is untouched, and wrapped in `:where(...)` so the
 * scope adds zero specificity (utility classes must still win at 0,1,0).
 *
 * @package Spectra\Tests\GlobalStyles
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\GlobalStyles;

use SpectraBlocks\GlobalStyles\Engine;
use WP_UnitTestCase;
use ReflectionClass;

/**
 * PreflightTest test case.
 *
 * @since x.x.x
 */
class PreflightTest extends WP_UnitTestCase {

	/**
	 * Cached preflight CSS output.
	 *
	 * @since x.x.x
	 * @var string
	 */
	private string $css = '';

	/**
	 * Build the preflight CSS once per test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$instance   = Engine::get_instance();
		$reflection = new ReflectionClass( $instance );
		$method     = $reflection->getMethod( 'build_preflight_css' );
		$method->setAccessible( true );
		$this->css = (string) $method->invoke( $instance );
	}

	/**
	 * Every preflight rule must be scoped to `.spectra-is-root-container`.
	 * Nothing may leak globally.
	 *
	 * @return void
	 */
	public function test_every_rule_is_scoped(): void {
		$this->assertNotSame( '', $this->css );

		// Split on `}` — each slice is one selector+declaration block.
		// An unscoped rule would start with an element/pseudo-class that is
		// NOT preceded by `:where(.spectra-is-root-container)`.
		$blocks = array_filter( array_map( 'trim', explode( '}', $this->css ) ) );

		foreach ( $blocks as $block ) {
			$this->assertStringContainsString(
				'.spectra-is-root-container',
				$block,
				'Preflight rule is not scoped to Spectra root container: ' . $block
			);
		}
	}

	/**
	 * Box-sizing reset must apply to all descendants + pseudo-elements.
	 *
	 * @return void
	 */
	public function test_box_sizing_reset(): void {
		$this->assertStringContainsString( 'box-sizing:border-box;', $this->css );
		$this->assertStringContainsString( ':where(.spectra-is-root-container) *', $this->css );
		$this->assertStringContainsString( ':where(.spectra-is-root-container) ::before', $this->css );
		$this->assertStringContainsString( ':where(.spectra-is-root-container) ::after', $this->css );
	}

	/**
	 * Border reset: width 0, style solid, color currentColor.
	 *
	 * @return void
	 */
	public function test_border_reset(): void {
		$this->assertStringContainsString( 'border-width:0;', $this->css );
		$this->assertStringContainsString( 'border-style:solid;', $this->css );
		$this->assertStringContainsString( 'border-color:currentColor;', $this->css );
	}

	/**
	 * No heading reset — h1..h6 must NOT be zeroed to inherit, otherwise theme
	 * heading styles collapse inside a Spectra root container on Pro-less
	 * sites. Utility classes win on their own specificity without it.
	 *
	 * @return void
	 */
	public function test_no_heading_reset(): void {
		$this->assertStringNotContainsString( 'font-size:inherit;font-weight:inherit;color:inherit;', $this->css );
	}

	/**
	 * No anchor reset — unstyled links must keep the theme's link colour /
	 * decoration (consistently in editor and front end), not inherit the
	 * surrounding text colour. Utility/author link styles still win.
	 *
	 * @return void
	 */
	public function test_no_anchor_reset(): void {
		$this->assertStringNotContainsString( ' a{color:inherit;', $this->css );
	}

	/**
	 * Media elements: display:block + vertical-align:middle + responsive sizing.
	 *
	 * @return void
	 */
	public function test_media_elements_reset(): void {
		$this->assertStringContainsString( ' img,', $this->css );
		$this->assertStringContainsString( ' svg,', $this->css );
		$this->assertStringContainsString( ' video,', $this->css );
		$this->assertStringContainsString( ' iframe', $this->css );
		$this->assertStringContainsString( 'display:block;vertical-align:middle;', $this->css );
		$this->assertStringContainsString( 'max-width:100%;height:auto;', $this->css );
	}

	/**
	 * List and heading/paragraph margin strip.
	 *
	 * @return void
	 */
	public function test_margin_strip(): void {
		$this->assertStringContainsString( ' blockquote,', $this->css );
		$this->assertStringContainsString( ' p,', $this->css );
		$this->assertStringContainsString( ' pre,', $this->css );
		$this->assertStringContainsString( ' span{margin:0;}', $this->css );
		$this->assertStringContainsString( ' ol,', $this->css );
		$this->assertStringContainsString( ' ul,', $this->css );
		$this->assertStringContainsString( 'list-style:none;margin:0;padding:0;', $this->css );
	}

	/**
	 * Form controls inherit font + color; buttons get pointer cursor.
	 *
	 * @return void
	 */
	public function test_form_control_reset(): void {
		$this->assertStringContainsString( ' button,', $this->css );
		$this->assertStringContainsString( ' input,', $this->css );
		$this->assertStringContainsString( ' select,', $this->css );
		$this->assertStringContainsString( ' textarea', $this->css );
		$this->assertStringContainsString( 'font-family:inherit', $this->css );
		$this->assertStringContainsString( 'cursor:pointer;', $this->css );
		$this->assertStringContainsString( ':disabled{cursor:default;}', $this->css );
	}

	/**
	 * `[hidden]` display:none with `until-found` exception.
	 *
	 * @return void
	 */
	public function test_hidden_attribute(): void {
		$this->assertStringContainsString( '[hidden]:where(:not([hidden="until-found"])){display:none;}', $this->css );
	}

	/**
	 * Tables collapse borders and inherit color.
	 *
	 * @return void
	 */
	public function test_table_reset(): void {
		$this->assertStringContainsString( ' table{', $this->css );
		$this->assertStringContainsString( 'border-collapse:collapse;', $this->css );
	}

	/**
	 * Placeholder color + opacity normalized.
	 *
	 * @return void
	 */
	public function test_placeholder_normalized(): void {
		$this->assertStringContainsString( '::placeholder', $this->css );
		$this->assertStringContainsString( 'opacity:1', $this->css );
	}

	/**
	 * `--tw-content` seed for before/after pseudo-elements.
	 *
	 * @return void
	 */
	public function test_tw_content_seed(): void {
		$this->assertStringContainsString( '--tw-content:""', $this->css );
	}

	/**
	 * Preflight must be included in the full stylesheet output.
	 *
	 * @return void
	 */
	public function test_preflight_is_included_in_stylesheet(): void {
		$instance   = Engine::get_instance();
		$reflection = new ReflectionClass( $instance );
		$method     = $reflection->getMethod( 'build_stylesheet_css' );
		$method->setAccessible( true );
		$full = (string) $method->invoke( $instance );

		$this->assertStringContainsString( 'box-sizing:border-box;', $full );
		$this->assertStringContainsString( 'border-color:currentColor;', $full );
	}
}
