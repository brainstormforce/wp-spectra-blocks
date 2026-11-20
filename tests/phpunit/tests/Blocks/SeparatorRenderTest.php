<?php
/**
 * The separator names itself, and says when the owner has given it a line of its own.
 *
 * A separator ZIP AI's converter builds from a marked `<hr>` wears the page's own
 * classes and `spectra-no-line`: the page's rule draws the line until the owner gives
 * the separator a setting, at any device. `spectra-separator-styled` marks that hand-over
 * (converted/_separator.scss). The wrapper is a `<div>`, so it carries `role="separator"`.
 *
 * @package Spectra\Tests\Blocks
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Blocks;

use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * SeparatorRenderTest test case.
 *
 * @since 1.0.16
 */
class SeparatorRenderTest extends WP_UnitTestCase {

	/**
	 * A separator as the editor saves it.
	 *
	 * @since 1.0.16
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 * @return string Rendered HTML.
	 */
	private function render_separator( array $attrs ): string {
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'spectra/separator' ),
			'spectra/separator is not registered — run `npm run build`.'
		);

		return do_blocks( '<!-- wp:spectra/separator ' . wp_json_encode( (object) $attrs ) . ' /-->' );
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_the_wrapper_names_itself_a_separator(): void {
		$html = $this->render_separator( array() );

		$this->assertMatchesRegularExpression( '#<div[^>]*\brole="separator"#', $html );
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_a_converted_separator_with_no_setting_keeps_the_page_rule(): void {
		$html = $this->render_separator( array( 'className' => 'divider spectra-no-padding spectra-no-line' ) );

		$this->assertStringContainsString( 'spectra-no-line', $html );
		$this->assertStringNotContainsString( 'spectra-separator-styled', $html );
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_a_setting_of_its_own_hands_the_line_to_spectra(): void {
		$html = $this->render_separator( array( 'className' => 'divider spectra-no-line', 'separatorColor' => '#c0392b' ) );

		$this->assertStringContainsString( 'spectra-separator-styled', $html );
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_a_setting_at_one_device_hands_the_line_to_spectra(): void {
		$html = $this->render_separator(
			array(
				'className' => 'divider spectra-no-line',
				'style'     => array( '@tablet' => array( 'separatorHeight' => '6px' ) ),
			)
		);

		$this->assertStringContainsString( 'spectra-separator-styled', $html );
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_an_empty_setting_is_no_setting(): void {
		$html = $this->render_separator( array( 'className' => 'divider spectra-no-line', 'separatorHeight' => '' ) );

		$this->assertStringNotContainsString( 'spectra-separator-styled', $html );
	}
}
