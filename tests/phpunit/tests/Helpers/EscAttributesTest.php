<?php
/**
 * Tests for spectra_blocks_esc_attributes().
 *
 * Block views print prebuilt attribute strings inside a start tag. They used to
 * go through `wp_kses_data()`, which on WordPress 7.2 turns `&quot;` and
 * `&#039;` back into quotes, ending attribute values early. The helper re-reads
 * the string with the HTML API and escapes each value instead, so values must
 * survive exactly on every WordPress version.
 *
 * @package Spectra\Tests\Helpers
 * @since   1.0.12
 */

namespace SpectraBlocks\Tests\Helpers;

use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * EscAttributesTest test case.
 *
 * @since 1.0.12
 */
class EscAttributesTest extends WP_UnitTestCase {

	/**
	 * Every attribute of a start tag built from an attribute string, in order.
	 *
	 * @since 1.0.12
	 * @param string $attributes Attributes as printed inside a start tag.
	 * @return array<string, string|true> Attribute name => decoded value.
	 */
	private function parse( string $attributes ): array {
		$processor = new WP_HTML_Tag_Processor( "<div {$attributes}>" );
		$processor->next_tag();

		$parsed = array();
		foreach ( (array) $processor->get_attribute_names_with_prefix( '' ) as $name ) {
			$parsed[ $name ] = $processor->get_attribute( $name );
		}

		return $parsed;
	}

	/**
	 * Attribute strings Spectra's views print, with values that contain quotes.
	 *
	 * @since 1.0.12
	 * @return array<string, array{string}> Attribute strings.
	 */
	public function quoted_values(): array {
		return array(
			'container video data'  => array( 'class="wp-block-spectra-container" data-responsive-videos="' . esc_attr( wp_json_encode( array( 'base' => 'http://x/v.mp4', '@tablet' => '' ) ) ) . '" data-orientation="horizontal"' ),
			'quoted font in style'  => array( 'class="wp-block-spectra-button" style="font-family:&quot;DM Serif Display&quot;, serif"' ),
			'interactivity context' => array( 'data-wp-interactive="spectra/tabs" ' . wp_interactivity_data_wp_context( array( 'label' => "It's <b>here</b> & now" ) ) ),
			'url with ampersand'    => array( 'href="https://x.test/?a=1&amp;b=2" target="_blank" rel="noopener"' ),
			'boolean attribute'     => array( 'class="x" hidden data-a="1"' ),
		);
	}

	/**
	 * Every value comes back exactly, in the original order.
	 *
	 * @since 1.0.12
	 * @dataProvider quoted_values
	 * @param string $attributes An attribute string.
	 * @return void
	 */
	public function test_values_round_trip_in_order( string $attributes ) {
		$this->assertSame( $this->parse( $attributes ), $this->parse( spectra_blocks_esc_attributes( $attributes ) ) );
	}

	/**
	 * The quotes inside a value stay encoded, so they cannot end the value.
	 *
	 * This is the WordPress 7.2 regression: `wp_kses_data()` printed
	 * `data-responsive-videos="{"base":…}"`, which a browser reads as `{`.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_quotes_inside_values_stay_encoded() {
		$escaped = spectra_blocks_esc_attributes( 'data-responsive-videos="{&quot;base&quot;:&quot;http:\/\/x\/v.mp4&quot;}"' );

		$this->assertSame( 'data-responsive-videos="{&quot;base&quot;:&quot;http:\/\/x\/v.mp4&quot;}"', $escaped );
	}

	/**
	 * A URL attribute with an unsafe scheme is dropped; the rest is kept.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_unsafe_url_is_dropped() {
		$this->assertSame( array( 'class' => 'x' ), $this->parse( spectra_blocks_esc_attributes( 'href="javascript:alert(1)" class="x"' ) ) );
	}

	/**
	 * Empty or non-string input prints nothing.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_empty_input_prints_nothing() {
		$this->assertSame( '', spectra_blocks_esc_attributes( '' ) );
		$this->assertSame( '', spectra_blocks_esc_attributes( '   ' ) );
		$this->assertSame( '', spectra_blocks_esc_attributes( null ) );
	}

	/**
	 * No block view prints an attribute string through `wp_kses_data()` again.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_views_do_not_kses_attribute_strings() {
		$offenders = array();
		$views     = glob( dirname( __DIR__, 4 ) . '/src/blocks/*/view.php' );

		$this->assertNotEmpty( $views, 'The block views were not found.' );

		foreach ( $views as $view ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local plugin file in a test.
			if ( preg_match( '/wp_kses_data\(\s*(\$[a-z_]*attributes|\$data\[\'wrapper_attributes\'\]|wp_interactivity_data_wp_context\()/', (string) file_get_contents( $view ) ) ) {
				$offenders[] = basename( dirname( $view ) );
			}
		}

		$this->assertSame( array(), $offenders, 'Print attribute strings with spectra_blocks_esc_attributes(), not wp_kses_data().' );
	}
}
