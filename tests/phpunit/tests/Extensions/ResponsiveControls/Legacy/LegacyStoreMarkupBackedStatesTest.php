<?php
/**
 * Rendering a markup-backed block's legacy breakpoints through core's states.
 *
 * A block whose `save()` serialises its attributes — `core/image` is the only
 * one in scope — keeps its base layer exactly as authored, because the root
 * attribute IS the saved markup and rewriting it fails block validation (#908).
 * That leaves its narrower breakpoints with no renderer on 7.1: this extension
 * stops painting image dimensions there and core has no state to band, so the
 * tablet and mobile sizes sit in the store and nothing reads them.
 *
 * `hydrate_markup_backed_states()` lifts those two buckets into core's own
 * state shape at render. The shape is the contract worth pinning: core keeps a
 * `core/image`'s per-viewport dimensions NESTED, under
 * `style['@tablet']['dimensions']['width']`, and reads nothing from a flat
 * `style['@tablet']['width']`. Writing it flat is silent — no error, no CSS,
 * the size simply never applies, which is what shipped in 1.0.7.
 *
 * The editor half does the same on parse; this covers the render half, which is
 * what an upgraded site depends on before any post has been re-saved.
 *
 * REMOVAL — this file's path mirrors the class it covers
 * (`includes/Extensions/ResponsiveControls/Legacy/`) so that deleting legacy
 * support deletes its tests in the same step.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls\Legacy;

use SpectraBlocks\Extensions\ResponsiveControls\Legacy\LegacyStore;
use SpectraBlocks\Extensions\ResponsiveControls\ViewportSupport;
use WP_UnitTestCase;

/**
 * LegacyStoreMarkupBackedStatesTest test case.
 *
 * @since x.x.x
 */
class LegacyStoreMarkupBackedStatesTest extends WP_UnitTestCase {

	/**
	 * Skip the whole case where core does not render viewport states.
	 *
	 * Below 7.1 the inline dimensions are stripped and this extension paints
	 * every breakpoint from the store, so the method deliberately does nothing
	 * and there is no contract to assert.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		if ( ! ViewportSupport::renders_states() ) {
			$this->markTestSkipped( 'Core does not render viewport states on this WordPress version.' );
		}
	}

	/**
	 * Build a block array in the shape `render_block_data` passes around.
	 *
	 * @since x.x.x
	 * @param array<string, mixed>      $store      The `responsiveControls` store.
	 * @param array<string, mixed>|null $style      Optional. An authored `style` attribute.
	 * @param string                    $block_name Optional. Block name. Defaults to the core image.
	 * @return array<string, mixed> Block data.
	 */
	private function block( array $store, ?array $style = null, string $block_name = 'core/image' ): array {
		$attrs = array( 'responsiveControls' => $store );

		if ( null !== $style ) {
			$attrs['style'] = $style;
		}

		return array(
			'blockName' => $block_name,
			'attrs'     => $attrs,
		);
	}

	/**
	 * Read the `style` attribute back out of a filtered block.
	 *
	 * @since x.x.x
	 * @param array<string, mixed> $block Block data.
	 * @return array<string, mixed> The style attribute.
	 */
	private function style( array $block ): array {
		return $block['attrs']['style'] ?? array();
	}

	/**
	 * The narrower buckets land under `dimensions`, which is what core reads.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_narrower_buckets_are_written_under_dimensions() {
		$style = $this->style(
			LegacyStore::hydrate_markup_backed_states(
				$this->block(
					array(
						'@tablet' => array( 'width' => '400px' ),
						'@mobile' => array( 'width' => '200px' ),
					)
				)
			)
		);

		$this->assertSame( '400px', $style['@tablet']['dimensions']['width'] ?? null, 'Tablet must land under dimensions.' );
		$this->assertSame( '200px', $style['@mobile']['dimensions']['width'] ?? null, 'Mobile must land under dimensions.' );

		$this->assertArrayNotHasKey( 'width', $style['@tablet'], 'A flat state key renders nothing and must not be written.' );
	}

	/**
	 * The base layer is never written.
	 *
	 * The root attribute is the saved markup; rewriting it is #908.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_the_base_bucket_is_never_written() {
		$style = $this->style(
			LegacyStore::hydrate_markup_backed_states(
				$this->block( array( 'base' => array( 'width' => '600px' ) ) )
			)
		);

		$this->assertSame( array(), $style, 'A base-only store must leave the style attribute alone.' );
	}

	/**
	 * An authored state wins; the store only fills gaps.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_an_authored_state_is_not_overwritten() {
		$style = $this->style(
			LegacyStore::hydrate_markup_backed_states(
				$this->block(
					array( '@tablet' => array( 'width' => '400px' ) ),
					array( '@tablet' => array( 'dimensions' => array( 'width' => '999px' ) ) )
				)
			)
		);

		$this->assertSame( '999px', $style['@tablet']['dimensions']['width'] ?? null, 'The authored state must win.' );
	}

	/**
	 * `scale` is core's object-fit control and is stored as `objectFit`.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_scale_is_written_as_object_fit() {
		$style = $this->style(
			LegacyStore::hydrate_markup_backed_states(
				$this->block(
					array(
						'@tablet' => array(
							'scale'       => 'cover',
							'aspectRatio' => '16/9',
						),
					)
				)
			)
		);

		$this->assertSame( 'cover', $style['@tablet']['dimensions']['objectFit'] ?? null, 'scale must be written as objectFit.' );
		$this->assertSame( '16/9', $style['@tablet']['dimensions']['aspectRatio'] ?? null, 'aspectRatio passes through by name.' );
	}

	/**
	 * A server-rendered Spectra block is left alone.
	 *
	 * Its markup carries none of its attributes, so it is migrated on parse in
	 * the ordinary way and has no need of this.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_a_spectra_block_is_untouched() {
		$style = $this->style(
			LegacyStore::hydrate_markup_backed_states(
				$this->block(
					array( '@tablet' => array( 'textShadowBlur' => '4px' ) ),
					null,
					'spectra/content'
				)
			)
		);

		$this->assertSame( array(), $style, 'A Spectra block must not be touched here.' );
	}

	/**
	 * A block with no legacy store is returned unchanged.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_a_block_without_a_store_is_unchanged() {
		$style = $this->style( LegacyStore::hydrate_markup_backed_states( $this->block( array() ) ) );

		$this->assertSame( array(), $style, 'No store means nothing to write.' );
	}

	/**
	 * Running twice writes the same thing once.
	 *
	 * The filter can run again on a re-render; the second pass finds the state
	 * already present and leaves it.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_it_is_idempotent() {
		$once  = LegacyStore::hydrate_markup_backed_states( $this->block( array( '@tablet' => array( 'width' => '400px' ) ) ) );
		$twice = LegacyStore::hydrate_markup_backed_states( $once );

		$this->assertSame( $this->style( $once ), $this->style( $twice ), 'A second pass must change nothing.' );
	}
}
