<?php
/**
 * A popup rendered through the pipeline knows which popup it is.
 *
 * Popups are rendered outside any loop for the popup post — from
 * `wp_enqueue_scripts` when display rules deliver them, from a shortcode when
 * placed explicitly — so inside the block's controller `get_the_ID()` is the
 * page being viewed. The controller's own detection reads the popup from the
 * block's `popupId` attribute, which the editor writes; content without that
 * attribute (REST, abilities, hand-written) fell back to the page id. Its
 * repetition and cookie settings were then read from the page's meta, and two
 * such popups on one page shared a DOM id.
 *
 * `PopupBuilder::render_popup_content()` now publishes the popup being
 * rendered and the controller reads it before falling back. This file pins
 * both delivery paths and that the published id does not leak past the render.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Blocks;

use SpectraBlocks\Blocks\PopupBuilder;
use WP_UnitTestCase;

/**
 * PopupBuilderRenderingPopupIdTest test case.
 *
 * @since x.x.x
 */
class PopupBuilderRenderingPopupIdTest extends WP_UnitTestCase {

	/**
	 * Popup content with NO `popupId` attribute — the case that fell back to the page.
	 *
	 * @since x.x.x
	 */
	private const CONTENT = '<!-- wp:spectra/popup-builder {"variationSelected":true,"variantType":"popup","spectraId":"popup-id-test"} --><!-- wp:paragraph --><p>Popup</p><!-- /wp:paragraph --><!-- /wp:spectra/popup-builder -->';

	/**
	 * Create a published, enabled popup carrying the fixture content.
	 *
	 * @since x.x.x
	 * @return int Popup post ID.
	 */
	private function create_popup(): int {
		$popup_id = self::factory()->post->create(
			array(
				'post_type'    => 'spectra-blocks-popup',
				'post_status'  => 'publish',
				'post_content' => self::CONTENT,
			)
		);
		update_post_meta( $popup_id, 'spectra-blocks-popup-enabled', 1 );
		update_post_meta( $popup_id, 'spectra-blocks-popup-repetition', 3 );

		return $popup_id;
	}

	/**
	 * The shortcode path renders the popup under its own id, not the page's.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_shortcode_render_uses_the_popup_id() {
		$popup_id = $this->create_popup();
		$page_id  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $page_id ) );

		$html = do_shortcode( '[spectra_blocks_popup id="' . $popup_id . '"]' );

		$this->assertStringContainsString( 'data-popup-id="' . $popup_id . '"', $html, 'The wrapper must carry the popup id.' );
		$this->assertStringContainsString( 'id="spectra-popup-builder-' . $popup_id . '"', $html, 'The DOM id must be the popup id.' );
		$this->assertStringNotContainsString( 'data-popup-id="' . $page_id . '"', $html, 'The page id must not leak into the popup markup.' );
		$this->assertStringContainsString( 'data-repetition="3"', $html, 'Repetition must be read from the popup meta, not the page.' );
	}

	/**
	 * The display-rule path renders the popup under its own id, not the page's.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_display_rule_render_uses_the_popup_id() {
		$popup_id = $this->create_popup();
		$page_id  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $page_id ) );

		$builder = PopupBuilder::instance();
		$builder->enqueue_popup_scripts_for_post();

		$this->assertContains( $popup_id, $builder->get_popup_ids(), 'The fixture popup must be selected for this page.' );

		ob_start();
		$builder->generate_popup_shortcode();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'data-popup-id="' . $popup_id . '"', $html );
		$this->assertStringContainsString( 'id="spectra-popup-builder-' . $popup_id . '"', $html );
		$this->assertStringNotContainsString( 'spectra-popup-builder-' . $page_id . '"', $html, 'The page id must not be used as the DOM id.' );
	}

	/**
	 * The published id is scoped to the render — nothing leaks afterwards.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_rendering_popup_id_does_not_leak() {
		$popup_id = $this->create_popup();

		$this->assertSame( 0, PopupBuilder::get_rendering_popup_id(), 'Nothing is being rendered before the shortcode.' );
		do_shortcode( '[spectra_blocks_popup id="' . $popup_id . '"]' );
		$this->assertSame( 0, PopupBuilder::get_rendering_popup_id(), 'The id must be cleared once the render returns.' );
	}

	/**
	 * An explicit `popupId` attribute still wins over the pipeline's id.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_explicit_attribute_still_wins() {
		$popup_id  = $this->create_popup();
		$other_id  = $this->create_popup();
		$with_attr = str_replace( '"spectraId":"popup-id-test"', '"spectraId":"popup-id-test","popupId":"popup-' . $other_id . '"', self::CONTENT );
		wp_update_post(
			array(
				'ID'           => $popup_id,
				'post_content' => $with_attr,
			)
		);

		$html = do_shortcode( '[spectra_blocks_popup id="' . $popup_id . '"]' );

		$this->assertStringContainsString( 'data-popup-id="' . $other_id . '"', $html, 'The attribute the editor wrote is the first source, as before.' );
	}
}
