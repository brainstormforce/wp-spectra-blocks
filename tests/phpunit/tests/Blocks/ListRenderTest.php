<?php
/**
 * The list says when the owner has given it an icon setting of its own.
 *
 * A list ZIP AI's converter builds from a marked `<ul>` wears the page's own classes and
 * `spectra-no-list-icon`: the page's classes draw each item's marker until the owner gives
 * the list an icon setting (numbers, an icon, its size at any device, its rotation or RTL
 * flip). `spectra-list-styled` marks that hand-over (converted/_list.scss). An icon setting
 * on one item hands over that item's marker alone: its icon wears `spectra-list-icon-own`.
 * Whatever draws it, the icon is the item's marker, hidden from screen readers.
 *
 * @package Spectra\Tests\Blocks
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Blocks;

use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * ListRenderTest test case.
 *
 * @since 1.0.16
 */
class ListRenderTest extends WP_UnitTestCase {

	/**
	 * A converted list of two items as the converter writes it, with the given attributes.
	 *
	 * @since 1.0.16
	 *
	 * @param array<string, mixed> $attrs      List attributes.
	 * @param array<string, mixed> $icon_attrs The first item's icon attributes.
	 * @return string Rendered HTML.
	 */
	private function render_list( array $attrs, array $icon_attrs = array() ): string {
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'spectra/list' ),
			'spectra/list is not registered — run `npm run build`.'
		);

		$item = static function ( int $index, array $icon ): string {
			return '<!-- wp:spectra/list-child-item ' . wp_json_encode( array( 'index' => $index, 'className' => 'check-list__item' ) ) . ' -->'
				. '<!-- wp:spectra/list-child-icon ' . wp_json_encode( (object) ( array( 'itemIndex' => $index ) + $icon ) ) . ' /-->'
				. '<!-- wp:spectra/content ' . wp_json_encode( array( 'tagName' => 'span', 'text' => 'Item ' . $index ) ) . ' /-->'
				. '<!-- /wp:spectra/list-child-item -->';
		};

		return do_blocks(
			'<!-- wp:spectra/list ' . wp_json_encode( array( 'className' => 'check-list spectra-no-list-icon' ) + $attrs ) . ' -->'
			. $item( 1, $icon_attrs ) . $item( 2, array() )
			. '<!-- /wp:spectra/list -->'
		);
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_a_converted_list_with_no_icon_setting_keeps_the_page_marker(): void {
		$html = $this->render_list( array() );

		$this->assertStringContainsString( 'spectra-no-list-icon', $html );
		$this->assertStringNotContainsString( 'spectra-list-styled', $html );
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_an_icon_of_its_own_hands_the_marker_to_spectra(): void {
		$this->assertStringContainsString( 'spectra-list-styled', $this->render_list( array( 'icon' => 'check' ) ) );
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_the_default_icon_is_no_setting(): void {
		$this->assertStringNotContainsString( 'spectra-list-styled', $this->render_list( array( 'icon' => 'circle' ) ) );
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_numbers_hand_the_marker_to_spectra(): void {
		$this->assertStringContainsString( 'spectra-list-styled', $this->render_list( array( 'listType' => 'ordered' ) ) );
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_an_icon_size_at_one_device_hands_the_marker_to_spectra(): void {
		$html = $this->render_list( array( 'style' => array( '@tablet' => array( 'iconSize' => '20px' ) ) ) );

		$this->assertStringContainsString( 'spectra-list-styled', $html );
	}

	/**
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_an_icon_on_one_item_hands_over_that_items_marker_alone(): void {
		$html = $this->render_list( array(), array( 'icon' => 'star' ) );

		$this->assertStringNotContainsString( 'spectra-list-styled', $html );
		$this->assertSame( 1, substr_count( $html, 'spectra-list-icon-own' ) );
	}

	/**
	 * An item's own size, rotation or RTL flip is an icon setting too; its default circle is not.
	 *
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_an_items_own_icon_setting_marks_that_item(): void {
		$this->assertStringContainsString( 'spectra-list-icon-own', $this->render_list( array(), array( 'iconSize' => '20px' ) ) );
		$this->assertStringContainsString( 'spectra-list-icon-own', $this->render_list( array(), array( 'rotation' => 45 ) ) );
		$this->assertStringContainsString( 'spectra-list-icon-own', $this->render_list( array(), array( 'flipForRTL' => true ) ) );
		$this->assertStringNotContainsString( 'spectra-list-icon-own', $this->render_list( array(), array( 'icon' => 'circle' ) ) );
		$this->assertStringNotContainsString( 'spectra-list-icon-own', $this->render_list( array() ) );
	}

	/**
	 * The List's own setting styles every item; no item claims it as its own.
	 *
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_the_lists_icon_is_no_items_own(): void {
		$html = $this->render_list( array( 'icon' => 'check' ) );

		$this->assertStringContainsString( 'spectra-list-styled', $html );
		$this->assertStringNotContainsString( 'spectra-list-icon-own', $html );
	}

	/**
	 * A list's icon is its marker: a screen reader reads the item's words, not an image.
	 *
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_a_named_icon_is_hidden_from_screen_readers(): void {
		$this->assertMatchesRegularExpression( '/<svg[^>]*\baria-hidden="true"/', $this->render_list( array( 'icon' => 'check' ) ) );
	}

	/**
	 * The converter carries a page's CSS icon onto the list as its SVG.
	 *
	 * @since 1.0.16
	 *
	 * @return void
	 */
	public function test_an_svg_icon_is_hidden_from_screen_readers(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m5 12 5 5L20 7"/></svg>';

		$this->assertMatchesRegularExpression( '/<svg[^>]*\baria-hidden="true"/', $this->render_list( array( 'icon' => $svg ) ) );
	}
}
