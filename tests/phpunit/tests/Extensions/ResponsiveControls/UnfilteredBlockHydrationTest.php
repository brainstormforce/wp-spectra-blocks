<?php
/**
 * A block that never passed through `render_block_data` still paints per band.
 *
 * WHY THIS EXISTS
 *
 * The responsive store is built on `render_block_data`, by
 * `build_store_from_style()` and `backward_compatibility_block_attributes()`.
 * Core applies that filter in exactly two places: `render_block()`, for a block
 * rendered from the post's own content, and the inner-block loop inside
 * `WP_Block::render()`.
 *
 * A block whose PARENT renders it by calling `$inner_block->render()` directly
 * passes through neither — that call applies the filter to the inner block's
 * OWN children, never to the inner block itself. `core/navigation` does this in
 * `get_markup_for_inner_block()` and `get_template_part_blocks_html()`.
 *
 * Measured on WordPress 7.1.2: `spectra-pro/mega-menu` inside a navigation
 * reached the renderer with an empty store, so it got no `data-spectra-id` and
 * no CSS. The editor saved `style['@tablet'].iconSize` and nothing painted it.
 *
 * `do_blocks()` always applies `render_block_data`, so the bypass cannot be
 * reproduced through it. These tests call the render filter directly, which is
 * precisely the shape navigation hands it.
 *
 * @package Spectra\Tests
 * @since   1.0.13
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls;

use SpectraBlocks\Extensions\ResponsiveControls;
use WP_UnitTestCase;

/**
 * UnfilteredBlockHydrationTest test case.
 *
 * @since 1.0.13
 */
class UnfilteredBlockHydrationTest extends WP_UnitTestCase {

	/**
	 * Render a block through the filter alone, bypassing `render_block_data`.
	 *
	 * @since 1.0.13
	 * @param array<string, mixed> $attrs Block attributes, including `spectraId`.
	 * @return array{markup: string, css: string}
	 */
	private function render_unfiltered( array $attrs ): array {
		wp_styles()->add_data( 'spectra-responsive-styles', 'after', array() );

		$markup = ResponsiveControls::instance()->process_block_and_add_responsive_styles(
			'<div class="wp-block-spectra-counter-child-progress-bar"></div>',
			array(
				'blockName' => 'spectra/counter-child-progress-bar',
				'attrs'     => $attrs,
			)
		);

		$css = (string) preg_replace(
			'/\s+/',
			' ',
			implode( '', (array) ( wp_styles()->get_data( 'spectra-responsive-styles', 'after' ) ?: array() ) )
		);

		return array(
			'markup' => $markup,
			'css'    => $css,
		);
	}

	/**
	 * Per-device values in `style` survive the missing filter.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_style_states_paint_without_render_block_data() {
		$id = 'ub' . wp_generate_password( 6, false );

		$out = $this->render_unfiltered(
			array(
				'spectraId' => $id,
				'style'     => array(
					'barHeight' => '10px',
					'@tablet'   => array( 'barHeight' => '20px' ),
					'@mobile'   => array( 'barHeight' => '30px' ),
				),
			)
		);

		// Without the id on the element every generated selector matches nothing.
		$this->assertStringContainsString(
			'data-spectra-id="' . $id . '"',
			$out['markup'],
			'The block was not given its id, so no generated rule can match it.'
		);

		foreach ( array( '10px', '20px', '30px' ) as $value ) {
			$this->assertStringContainsString(
				'--spectra-counter-bar-height:' . $value,
				$out['css'],
				sprintf( 'The %s band was not painted.', $value )
			);
		}

		// Each value belongs to its own band, not three copies of one.
		$queries = ResponsiveControls::instance()->get_media_queries();
		$tablet  = strpos( $out['css'], '@media ' . ( $queries['@tablet'] ?? '@@none' ) );
		$this->assertNotFalse( $tablet, 'No tablet media query was emitted.' );
		$this->assertStringContainsString(
			'--spectra-counter-bar-height:10px',
			substr( $out['css'], 0, $tablet ),
			'The base value did not land before the first media query.'
		);
	}

	/**
	 * A root attribute with no `style` is bridged the same way.
	 *
	 * This is the legacy shape: content saved before per-device values moved
	 * into `style` carries the value on the root attribute alone.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_legacy_root_attribute_paints_without_render_block_data() {
		$id = 'ub' . wp_generate_password( 6, false );

		$out = $this->render_unfiltered(
			array(
				'spectraId' => $id,
				'barHeight' => '42px',
			)
		);

		$this->assertStringContainsString( 'data-spectra-id="' . $id . '"', $out['markup'] );
		$this->assertStringContainsString( '--spectra-counter-bar-height:42px', $out['css'] );
	}

	/**
	 * A block with nothing responsive is still left alone.
	 *
	 * The fallback must not invent an id or a stylesheet for every block that
	 * reaches the renderer — that was the point of the guard it sits in front of.
	 *
	 * @since 1.0.13
	 * @return void
	 */
	public function test_block_without_responsive_data_is_untouched() {
		$out = $this->render_unfiltered( array( 'spectraId' => 'ub' . wp_generate_password( 6, false ) ) );

		$this->assertStringNotContainsString( 'data-spectra-id', $out['markup'] );
		$this->assertStringNotContainsString( '--spectra-counter-bar-height', $out['css'] );
	}
}
