<?php
/**
 * A container's left/right margin applies only inside another block.
 *
 * A top-level container is placed by its alignment and the theme, so the editor
 * offers it top/bottom margin only and the front end drops a left/right value.
 * Inside another block (a Group, Row, Columns and so on) the margin is the
 * author's: it is emitted, and `spectra-has-side-margin` stops common.scss
 * centring the container over it. A synced pattern's top level counts as top
 * level, as it does in the editor.
 *
 * @package Spectra\Tests
 * @since   1.0.13
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_HTML_Tag_Processor;
use WP_UnitTestCase;

/**
 * ContainerSideMarginTest test case.
 *
 * @since 1.0.13
 */
class ContainerSideMarginTest extends WP_UnitTestCase {

	/**
	 * Markup for a full-width root container.
	 *
	 * @since 1.0.13
	 * @param string               $id     The container's spectraId.
	 * @param array<string, mixed> $margin The margin, as the editor saves it.
	 * @return string Block markup.
	 */
	private function container( string $id, array $margin ): string {
		$attrs = array(
			'spectraId'         => $id,
			'variationSelected' => true,
			'isBlockRootParent' => true,
			'align'             => 'full',
			'style'             => array( 'spacing' => array( 'margin' => $margin ) ),
		);

		return '<!-- wp:spectra/container ' . wp_json_encode( $attrs ) . ' --><!-- wp:paragraph --><p>x</p><!-- /wp:paragraph --><!-- /wp:spectra/container -->';
	}

	/**
	 * Wrap markup in a core Group laid out as a row.
	 *
	 * @since 1.0.13
	 * @param string $inner The markup to wrap.
	 * @return string Block markup.
	 */
	private function in_row( string $inner ): string {
		return '<!-- wp:group {"layout":{"type":"flex"}} --><div class="wp-block-group">' . $inner . '</div><!-- /wp:group -->';
	}

	/**
	 * Render markup as a new request and return its HTML and responsive CSS.
	 *
	 * @since 1.0.13
	 * @param string $markup Block markup.
	 * @return array{html: string, css: string}
	 */
	private function render( string $markup ): array {
		// Each render stands for a new request: clear the once-per-request guard.
		$guard = new \ReflectionProperty( ResponsiveControls::class, 'inline_css_added' );
		$guard->setAccessible( true );
		$guard->setValue( ResponsiveControls::instance(), array() );
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );

		$html = do_blocks( $markup );

		return array(
			'html' => $html,
			'css'  => (string) implode( '', (array) ( wp_styles()->get_data( 'spectra-responsive-styles', 'after' ) ?: array() ) ),
		);
	}

	/**
	 * Count the left/right margin declarations emitted for one container.
	 *
	 * @since 1.0.13
	 * @param string $css The responsive CSS.
	 * @param string $id  The container's spectraId.
	 * @return int Number of left/right margin declarations.
	 */
	private function side_margin_rules( string $css, string $id ): int {
		return (int) preg_match_all( "/\\[data-spectra-id='{$id}'\\][^{]*\\{[^}]*margin-(?:left|right):\\s*40px/", $css );
	}

	/**
	 * Whether the container's wrapper carries `spectra-has-side-margin`.
	 *
	 * @since 1.0.13
	 * @param string $html The rendered HTML.
	 * @param string $id   The container's spectraId.
	 * @return bool True when the class is present.
	 */
	private function has_side_margin_class( string $html, string $id ): bool {
		$tags = new WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag() ) {
			if ( $id === $tags->get_attribute( 'data-spectra-id' ) ) {
				return (bool) $tags->has_class( 'spectra-has-side-margin' );
			}
		}

		$this->fail( "Container {$id} was not rendered." );
	}

	/**
	 * A top-level container drops its left/right margin.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_top_level_container_drops_side_margin() {
		$id     = 'csm' . wp_generate_password( 6, false );
		$result = $this->render( $this->container( $id, array( 'left' => '40px', 'right' => '40px' ) ) );

		$this->assertSame( 0, $this->side_margin_rules( $result['css'], $id ) );
		$this->assertFalse( $this->has_side_margin_class( $result['html'], $id ) );
	}

	/**
	 * Inside another block the margin is emitted and the centring is lifted.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_container_inside_a_block_keeps_side_margin() {
		$id     = 'csm' . wp_generate_password( 6, false );
		$result = $this->render( $this->in_row( $this->container( $id, array( 'left' => '40px', 'right' => '40px' ) ) ) );

		$this->assertGreaterThan( 0, $this->side_margin_rules( $result['css'], $id ) );
		$this->assertTrue( $this->has_side_margin_class( $result['html'], $id ) );
	}

	/**
	 * Without a left/right margin a container inside a block stays centred.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_container_inside_a_block_without_side_margin_has_no_class() {
		$id     = 'csm' . wp_generate_password( 6, false );
		$result = $this->render( $this->in_row( $this->container( $id, array( 'top' => '40px' ) ) ) );

		$this->assertFalse( $this->has_side_margin_class( $result['html'], $id ) );
	}

	/**
	 * A synced pattern's top level counts as top level, as in the editor.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_synced_pattern_top_level_counts_as_top_level() {
		$id      = 'csm' . wp_generate_password( 6, false );
		$pattern = self::factory()->post->create(
			array(
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_content' => $this->container( $id, array( 'left' => '40px', 'right' => '40px' ) ),
			)
		);
		$result  = $this->render( '<!-- wp:block {"ref":' . $pattern . '} /-->' );

		$this->assertSame( 0, $this->side_margin_rules( $result['css'], $id ) );
		$this->assertFalse( $this->has_side_margin_class( $result['html'], $id ) );
	}

	/**
	 * Moving a container into a block must not serve the CSS cached at the top level.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_parent_change_invalidates_cached_css() {
		$id        = 'csm' . wp_generate_password( 6, false );
		$container = $this->container( $id, array( 'left' => '40px', 'right' => '40px' ) );

		$this->assertSame( 0, $this->side_margin_rules( $this->render( $container )['css'], $id ), 'Top level caches CSS without side margins.' );
		$this->assertGreaterThan( 0, $this->side_margin_rules( $this->render( $this->in_row( $container ) )['css'], $id ), 'Inside a block the side margins return.' );
		$this->assertSame( 0, $this->side_margin_rules( $this->render( $container )['css'], $id ), 'Back at the top level they are dropped again.' );
	}
}
