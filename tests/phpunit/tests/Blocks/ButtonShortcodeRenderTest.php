<?php
/**
 * The Spectra Button expands a shortcode in its label, matching the core Button.
 *
 * The Spectra Button is a dynamic block: its label lives in the block-comment
 * attribute JSON. The FSE template pipeline runs `do_shortcode` BEFORE
 * `do_blocks` and never again, so a shortcode in a Spectra Button placed in a
 * template/template part used to reach the frontend as raw text, while the core
 * Button (static inner HTML) was expanded by that early pass. The controller now
 * runs `do_shortcode` on the label in that context so the two match - but skips it
 * while `the_content` is running, where the priority-11 pass already expands the
 * label, so post-content output stays identical to the core Button (no double
 * texturize, no escaped-shortcode execution).
 *
 * @package Spectra\Tests\Blocks
 * @since   1.0.12
 */

namespace SpectraBlocks\Tests\Blocks;

use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * ButtonShortcodeRenderTest test case.
 *
 * @since 1.0.12
 */
class ButtonShortcodeRenderTest extends WP_UnitTestCase {

	/**
	 * Register the fixture shortcodes.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		add_shortcode( 'sb852_phone', static fn() => '992' );
		// Emits an anchor so the anchor-strip kses parity can be asserted.
		add_shortcode( 'sb852_link', static fn() => '<a href="tel:992">Call</a>' );
		// Output carries texturizable characters (straight quotes, apostrophe, dash)
		// so the `wptexturize` parity in post content can be asserted.
		add_shortcode( 'sb852_text', static fn() => 'Call +31 - 20 "now" it\'s' );
	}

	/**
	 * Remove the fixture shortcodes.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function tearDown(): void {
		remove_shortcode( 'sb852_phone' );
		remove_shortcode( 'sb852_link' );
		remove_shortcode( 'sb852_text' );
		parent::tearDown();
	}

	/**
	 * Render a Spectra Button with the given text attribute.
	 *
	 * @since 1.0.12
	 * @param string $text The `text` attribute.
	 * @return string Rendered HTML.
	 */
	private function render( string $text ): string {
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'spectra/button' ),
			'spectra/button is not registered - run `npm run build`.'
		);

		return (string) render_block(
			array(
				'blockName'    => 'spectra/button',
				'attrs'        => array( 'text' => $text ),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * Render a Spectra Button through the `the_content` filter (post-content path).
	 *
	 * Unlike render(), this exercises the full `do_blocks` (9) -> `wptexturize`
	 * (10) -> `do_shortcode` (11) pipeline, which is where the label must be left
	 * for the priority-11 pass to keep parity with the core Button and a paragraph.
	 *
	 * @since 1.0.12
	 * @param string $text The `text` attribute.
	 * @return string Rendered HTML.
	 */
	private function render_in_content( string $text ): string {
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'spectra/button' ),
			'spectra/button is not registered - run `npm run build`.'
		);

		$markup = '<!-- wp:spectra/button ' . wp_json_encode( array( 'text' => $text ) ) . ' /-->';

		return (string) apply_filters( 'the_content', $markup );
	}

	/**
	 * A shortcode in the label is expanded, not printed raw.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_shortcode_label_is_expanded(): void {
		$html = $this->render( '[sb852_phone]' );

		$this->assertStringContainsString( '<span class="spectra-button__link">992</span>', $html );
		$this->assertStringNotContainsString( '[sb852_phone]', $html );
	}

	/**
	 * Plain text with no shortcode is rendered verbatim (no regression).
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_plain_label_is_unchanged(): void {
		$html = $this->render( 'Call us' );

		$this->assertStringContainsString( '<span class="spectra-button__link">Call us</span>', $html );
	}

	/**
	 * Shortcode OUTPUT is sanitised: an anchor it emits is stripped, same as any
	 * other label (the button already wraps the label in its own <a>).
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_shortcode_output_is_sanitised(): void {
		$html = $this->render( '[sb852_link]' );

		$this->assertStringContainsString( 'Call', $html );
		// The shortcode-emitted <a> must not survive inside the button label.
		$this->assertStringNotContainsString( 'href="tel:992"', $html );
	}

	/**
	 * In post content the label is still expanded (by the_content priority 11),
	 * so a content Button keeps rendering the shortcode value.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_shortcode_label_is_expanded_in_post_content(): void {
		$html = $this->render_in_content( '[sb852_phone]' );

		$this->assertStringContainsString( '992', $html );
		$this->assertStringNotContainsString( '[sb852_phone]', $html );
	}

	/**
	 * In post content the label must NOT be expanded inside the render callback.
	 *
	 * Doing so would expand at priority 9 and let `wptexturize` (10) curl the
	 * output into entities, diverging from the core Button and a paragraph (whose
	 * shortcode survives untexturized until `do_shortcode` at 11). The guard keeps
	 * the straight characters. Regression for issue #852.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_shortcode_output_is_not_texturized_in_post_content(): void {
		$html = $this->render_in_content( '[sb852_text]' );

		// Straight quote, apostrophe and spaced hyphen survive verbatim.
		$this->assertStringContainsString( 'Call +31 - 20 "now" it\'s', $html );
		// None of the texturize entities leak in.
		$this->assertStringNotContainsString( '&#8211;', $html ); // en dash.
		$this->assertStringNotContainsString( '&#8220;', $html ); // left double quote.
		$this->assertStringNotContainsString( '&#8217;', $html ); // right single quote.
	}

	/**
	 * In post content an escaped shortcode `[[..]]` prints literally and is NOT
	 * executed - matching the core Button and a paragraph. Without the guard the
	 * render callback would unescape it to `[..]`, which the_content priority 11
	 * then runs. Regression for issue #852.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_escaped_shortcode_is_not_executed_in_post_content(): void {
		$html = $this->render_in_content( '[[sb852_phone]]' );

		$this->assertStringContainsString( '[sb852_phone]', $html );
		$this->assertStringNotContainsString( '992', $html );
	}
}
