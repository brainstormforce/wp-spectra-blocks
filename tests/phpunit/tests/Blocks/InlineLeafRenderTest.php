<?php
/**
 * An inline leaf's line holds only its leaves' text and declared spaces.
 *
 * @package Spectra\Tests\Blocks
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Blocks;

use SpectraBlocks\AssetLoader;
use SpectraBlocks\Blocks\InlineLeaf;
use WP_UnitTestCase;

/**
 * InlineLeafRenderTest test case.
 *
 * @since 1.0.10
 */
class InlineLeafRenderTest extends WP_UnitTestCase {

	/**
	 * A serialized nested `spectra/content` leaf.
	 *
	 * @since 1.0.10
	 *
	 * @param string $tag   Tag name.
	 * @param string $text  Text.
	 * @param array  $attrs Extra attributes.
	 * @return string
	 */
	private function leaf( string $tag, string $text, array $attrs = array() ): string {
		return '<!-- wp:spectra/content ' . wp_json_encode( array( 'tagName' => $tag, 'text' => $text, 'isRootBlock' => false ) + $attrs ) . ' /-->';
	}

	/**
	 * A converter `spectra/container` (it carries the no-block-gap marker), one
	 * line break between blocks as the converter writes.
	 *
	 * @since 1.0.10
	 *
	 * @param string   $tag      The container's htmlTag.
	 * @param string[] $children Serialized children.
	 * @param string   $class    Optional extra className.
	 * @param bool     $marked   Whether it carries the converter marker.
	 * @return string
	 */
	private function container( string $tag, array $children, string $class = '', bool $marked = true ): string {
		$class_name = trim( ( $marked ? AssetLoader::NO_BLOCK_GAP_MARKER . ' ' : '' ) . $class );
		return '<!-- wp:spectra/container ' . wp_json_encode( array( 'htmlTag' => $tag ) + ( $class_name ? array( 'className' => $class_name ) : array() ) ) . " -->\n" . implode( "\n", $children ) . "\n<!-- /wp:spectra/container -->";
	}

	/**
	 * The measured lines ("$ 49 /mo", "Save 20% !") read as their source; a declared space sits outside the tag.
	 *
	 * @since 1.0.10
	 *
	 * @return void
	 */
	public function test_a_line_of_inline_leaves_reads_as_its_source(): void {
		$price = do_blocks( $this->container( 'button', array( $this->leaf( 'span', '$' ), $this->leaf( 'strong', '49' ), $this->leaf( 'span', '/mo' ) ) ) );
		$this->assertSame( '$49/mo', wp_strip_all_tags( $price ) );
		$save = do_blocks( $this->container( 'button', array( $this->leaf( 'span', 'Save', array( 'spaceAfter' => true ) ), $this->leaf( 'span', '20%' ), $this->leaf( 'span', '!' ) ) ) );
		$this->assertSame( 'Save 20%!', wp_strip_all_tags( $save ) );
		$this->assertMatchesRegularExpression( '#Save</span> <span#', $save );
	}

	/**
	 * Beside no inline leaf the line break stays (the converter relies on it).
	 *
	 * @since 1.0.10
	 *
	 * @return void
	 */
	public function test_whitespace_stays_between_blocks_that_are_not_inline_leaves(): void {
		$box  = $this->container( 'span', array( $this->leaf( 'p', 'x' ) ), 'box' );
		$html = do_blocks( $this->container( 'div', array( $this->leaf( 'span', 'Next' ), $box, $box ) ) );
		$this->assertMatchesRegularExpression( '#Next</span><span\b#', $html );
		$this->assertMatchesRegularExpression( '#</span>\s*\n\s*<span\b[^>]*\bbox\b#', $html );
	}

	/**
	 * One top-level pass reaches nested containers (WordPress 6.6 / 6.7).
	 *
	 * @since 1.0.10
	 *
	 * @return void
	 */
	public function test_the_top_level_pass_reaches_nested_containers(): void {
		$leaf   = $this->leaf( 'span', 'A' );
		$markup = "<!-- wp:group -->\n<div class=\"wp-block-group\">" . $this->container( 'div', array( $this->container( 'button', array( $leaf, $leaf ) ) ) ) . "</div>\n<!-- /wp:group -->";
		$top    = InlineLeaf::drop_whitespace_beside_inline_leaves( parse_blocks( $markup )[0] );
		$this->assertSame( array( "\n", null, '', null, "\n" ), $top['innerBlocks'][0]['innerBlocks'][0]['innerContent'] );
	}

	/**
	 * Hand-built content (no converter marker, not an imported page) renders as
	 * before: the line break between leaves stays, and the leaf keeps its own whitespace.
	 *
	 * @since 1.0.10
	 *
	 * @return void
	 */
	public function test_hand_built_leaves_keep_their_whitespace(): void {
		$html = do_blocks( $this->container( 'div', array( $this->leaf( 'span', 'Call' ), $this->leaf( 'span', 'us' ) ), '', false ) );
		$this->assertMatchesRegularExpression( '#Call\s*</span>\s*\n\s*<span\b#', $html );
		$this->assertSame( 'Call us', trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( $html ) ) ) );
	}

	/**
	 * An imported page scopes every container, marked or not.
	 *
	 * @since 1.0.10
	 *
	 * @return void
	 */
	public function test_an_imported_page_scopes_unmarked_containers(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, AssetLoader::IMPORTED_MARKER_META_KEY, '1' );
		$this->go_to( get_permalink( $post_id ) );

		$html = do_blocks( $this->container( 'button', array( $this->leaf( 'span', '$' ), $this->leaf( 'strong', '49' ) ), '', false ) );
		$this->assertSame( '$49', wp_strip_all_tags( $html ) );
	}

	/**
	 * Core's per-inner-block calls are no-ops: the top-level pass already did the work.
	 *
	 * @since 1.0.10
	 *
	 * @return void
	 */
	public function test_nested_filter_calls_are_skipped(): void {
		$leaf  = $this->leaf( 'span', 'A' );
		$block = parse_blocks( $this->container( 'button', array( $leaf, $leaf ) ) )[0];
		$this->assertSame( $block, InlineLeaf::drop_whitespace_beside_inline_leaves( $block, $block, new \stdClass() ) );
	}

	/**
	 * Only a nested phrasing leaf is inline; block-level and root leaves keep their output.
	 *
	 * @since 1.0.10
	 *
	 * @return void
	 */
	public function test_is_inline_reads_nesting_and_tag(): void {
		$this->assertTrue( InlineLeaf::is_inline( array( 'tagName' => 'span', 'isRootBlock' => false ) ) );
		$this->assertFalse( InlineLeaf::is_inline( array( 'tagName' => 'span' ) ) );
		$this->assertFalse( InlineLeaf::is_inline( array( 'tagName' => 'p', 'isRootBlock' => false ) ) );
	}
}
