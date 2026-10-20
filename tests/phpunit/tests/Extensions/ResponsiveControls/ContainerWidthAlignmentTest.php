<?php
/**
 * A container's Width applies only at the default alignment.
 *
 * The editor ignores `width` for wide and full containers
 * (getContainerSizeStyles() in render.js), but the front end kept emitting it,
 * so a 500px container switched to Full rendered 500px wide. The CSS cache must
 * also follow the alignment: the store does not change when only the alignment
 * does, and the CSS cached at the old alignment kept being served. A full
 * container also ignores its Max Width in the editor, so the front end does too.
 *
 * @package Spectra\Tests
 * @since   1.0.12
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * ContainerWidthAlignmentTest test case.
 *
 * @since 1.0.12
 */
class ContainerWidthAlignmentTest extends WP_UnitTestCase {

	/**
	 * Render a root container and return the responsive CSS it emitted.
	 *
	 * @since 1.0.12
	 * @param string               $id    The container's spectraId.
	 * @param string               $align The alignment, or '' for the default.
	 * @param array<string, string> $sizes Size attributes, as the editor saves them.
	 * @return string Responsive CSS for the request.
	 */
	private function render_container( string $id, string $align, array $sizes = array( 'width' => '500px' ) ): string {
		// Each render stands for a new request: clear the once-per-request guard.
		$guard = new \ReflectionProperty( ResponsiveControls::class, 'inline_css_added' );
		$guard->setAccessible( true );
		$guard->setValue( ResponsiveControls::instance(), array() );
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );

		$attrs = array_merge(
			array(
				'spectraId'         => $id,
				'variationSelected' => true,
				'isBlockRootParent' => true,
				'style'             => $sizes,
			),
			$sizes
		);
		if ( '' !== $align ) {
			$attrs['align'] = $align;
		}

		do_blocks( '<!-- wp:spectra/container ' . wp_json_encode( $attrs ) . ' --><!-- wp:paragraph --><p>x</p><!-- /wp:paragraph --><!-- /wp:spectra/container -->' );

		return (string) implode( '', (array) ( wp_styles()->get_data( 'spectra-responsive-styles', 'after' ) ?: array() ) );
	}

	/**
	 * Count the `width` declarations emitted for one container.
	 *
	 * @since 1.0.12
	 * @param string $css The responsive CSS.
	 * @param string $id  The container's spectraId.
	 * @return int Number of rules that set `width`.
	 */
	private function width_rules( string $css, string $id ): int {
		return (int) preg_match_all( "/\\[data-spectra-id='{$id}'\\][^{]*\\{[^}]*(?<![-\\w])width:\\s*500px/", $css );
	}

	/**
	 * Count the `max-width` declarations emitted for one container.
	 *
	 * @since 1.0.12
	 * @param string $css The responsive CSS.
	 * @param string $id  The container's spectraId.
	 * @return int Number of rules that set `max-width`.
	 */
	private function max_width_rules( string $css, string $id ): int {
		return (int) preg_match_all( "/\\[data-spectra-id='{$id}'\\][^{]*\\{[^}]*max-width:\\s*800px/", $css );
	}

	/**
	 * Full drops its Max Width, as the editor does; wide and default keep it.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_only_full_drops_max_width() {
		foreach ( array( '' => true, 'wide' => true, 'full' => false ) as $align => $keeps ) {
			$id    = 'cwa' . wp_generate_password( 6, false );
			$count = $this->max_width_rules( $this->render_container( $id, $align, array( 'maxWidth' => '800px' ) ), $id );
			$label = '' === $align ? 'default' : $align;

			if ( $keeps ) {
				$this->assertGreaterThan( 0, $count, "{$label} must keep max-width." );
			} else {
				$this->assertSame( 0, $count, "{$label} must not emit max-width." );
			}
		}
	}

	/**
	 * The default alignment keeps its width.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_default_alignment_keeps_width() {
		$id = 'cwa' . wp_generate_password( 6, false );

		$this->assertGreaterThan( 0, $this->width_rules( $this->render_container( $id, '' ), $id ) );
	}

	/**
	 * Wide and full containers take their width from the alignment.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_wide_and_full_drop_width() {
		foreach ( array( 'wide', 'full' ) as $align ) {
			$id = 'cwa' . wp_generate_password( 6, false );

			$this->assertSame( 0, $this->width_rules( $this->render_container( $id, $align ), $id ), "{$align} must not emit width." );
		}
	}

	/**
	 * Changing only the alignment must not serve the CSS cached at the old one.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_alignment_change_invalidates_cached_css() {
		$id = 'cwa' . wp_generate_password( 6, false );

		$this->assertGreaterThan( 0, $this->width_rules( $this->render_container( $id, '' ), $id ), 'Default alignment caches the width.' );
		$this->assertSame( 0, $this->width_rules( $this->render_container( $id, 'full' ), $id ), 'Switching to Full must drop the cached width.' );
		$this->assertSame( 0, $this->width_rules( $this->render_container( $id, 'wide' ), $id ), 'Switching to Wide must drop the cached width.' );
		$this->assertGreaterThan( 0, $this->width_rules( $this->render_container( $id, '' ), $id ), 'Switching back restores the width.' );
	}

	/**
	 * The cached Max Width follows the alignment the same way.
	 *
	 * @since 1.0.12
	 * @return void
	 */
	public function test_alignment_change_invalidates_cached_max_width() {
		$id    = 'cwa' . wp_generate_password( 6, false );
		$sizes = array( 'maxWidth' => '800px' );

		$this->assertGreaterThan( 0, $this->max_width_rules( $this->render_container( $id, '', $sizes ), $id ), 'Default alignment caches the max-width.' );
		$this->assertSame( 0, $this->max_width_rules( $this->render_container( $id, 'full', $sizes ), $id ), 'Switching to Full must drop the cached max-width.' );
		$this->assertGreaterThan( 0, $this->max_width_rules( $this->render_container( $id, 'wide', $sizes ), $id ), 'Wide keeps the max-width.' );
		$this->assertGreaterThan( 0, $this->max_width_rules( $this->render_container( $id, '', $sizes ), $id ), 'Switching back keeps the max-width.' );
	}
}
