<?php
/**
 * The legacy store's vocabulary contract.
 *
 * Content saved before the move to core's viewport vocabulary carries
 * `lg` / `md` / `sm`, and must render without being re-saved — that is
 * `normalize_device_keys()`, which rewrites those keys forward into
 * `base` / `@tablet` / `@mobile`. It is silent when it breaks: a rename
 * returns an empty array rather than an error, so per-device styling simply
 * stops applying and nothing in the logs says so — which is why the contract
 * is asserted here rather than left to manual checking.
 *
 * The cascade bake is included for the same reason. The pre-7.1 generator
 * resolved mobile as `sm -> md -> lg`, so a tablet value applied on phones
 * whenever mobile was unset; the current generator follows core's model, where
 * each viewport falls back only to base. Migrated content therefore has its
 * tablet bucket copied under mobile, and dropping that step would silently
 * restyle every phone view on existing sites.
 *
 * REMOVAL — this file's path mirrors the class it covers
 * (`includes/Extensions/ResponsiveControls/Legacy/`) so that deleting legacy
 * support deletes its tests in the same step. Nothing else here is legacy-only;
 * the parity test two directories up covers the current path and stays.
 *
 * @package Spectra\Tests
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\Extensions\ResponsiveControls\Legacy;

use SpectraBlocks\Extensions\ResponsiveControls\Legacy\LegacyStore;
use WP_UnitTestCase;

/**
 * LegacyStoreVocabularyTest test case.
 *
 * @since x.x.x
 */
class LegacyStoreVocabularyTest extends WP_UnitTestCase {

	/**
	 * Build a block array in the shape `render_block_data` passes around.
	 *
	 * @since x.x.x
	 * @param array<string, mixed> $store      The `responsiveControls` store.
	 * @param string               $block_name Optional. Block name. Defaults to the container.
	 * @return array<string, mixed> Block data.
	 */
	private function block( array $store, string $block_name = 'spectra/container' ): array {
		return array(
			'blockName' => $block_name,
			'attrs'     => array(
				'spectraId'          => 'test-id',
				'responsiveControls' => $store,
			),
		);
	}

	/**
	 * Read the store back out of a filtered block.
	 *
	 * @since x.x.x
	 * @param array<string, mixed> $block Block data.
	 * @return array<string, mixed> The store.
	 */
	private function store( array $block ): array {
		return $block['attrs']['responsiveControls'] ?? array();
	}

	/**
	 * The three legacy keys map onto core's vocabulary.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_legacy_keys_are_rewritten_to_core_vocabulary() {
		$result = LegacyStore::normalize_device_keys(
			$this->block(
				array(
					'lg' => array( 'minHeight' => '500px' ),
					'md' => array( 'minHeight' => '300px' ),
					'sm' => array( 'minHeight' => '200px' ),
				)
			)
		);

		$store = $this->store( $result );

		$this->assertSame( '500px', $store['base']['minHeight'] ?? null, 'lg must become base.' );
		$this->assertSame( '300px', $store['@tablet']['minHeight'] ?? null, 'md must become @tablet.' );
		$this->assertSame( '200px', $store['@mobile']['minHeight'] ?? null, 'sm must become @mobile.' );

		$this->assertArrayNotHasKey( 'lg', $store, 'The legacy key must not survive normalisation.' );
		$this->assertArrayNotHasKey( 'md', $store, 'The legacy key must not survive normalisation.' );
		$this->assertArrayNotHasKey( 'sm', $store, 'The legacy key must not survive normalisation.' );
	}

	/**
	 * Where a breakpoint carries both shapes, the canonical value wins.
	 *
	 * It is the one the current editor wrote, so it is the newer intent.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_canonical_value_wins_over_legacy_for_the_same_breakpoint() {
		$result = LegacyStore::normalize_device_keys(
			$this->block(
				array(
					'md'      => array( 'minHeight' => '300px' ),
					'@tablet' => array( 'minHeight' => '360px' ),
				)
			)
		);

		$this->assertSame(
			'360px',
			$this->store( $result )['@tablet']['minHeight'] ?? null,
			'The canonical bucket is the current editor output and must win.'
		);
	}

	/**
	 * Migrated content keeps the pre-7.1 mobile cascade.
	 *
	 * Tablet is copied under mobile so a tablet-only value still applies on
	 * phones, as it did before the move to core's per-viewport model.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_tablet_cascades_into_mobile_for_migrated_content() {
		$result = LegacyStore::normalize_device_keys(
			$this->block(
				array(
					'lg' => array( 'minHeight' => '500px' ),
					'md' => array( 'minHeight' => '300px' ),
				)
			)
		);

		$this->assertSame(
			'300px',
			$this->store( $result )['@mobile']['minHeight'] ?? null,
			'A tablet value must still reach phones on migrated content.'
		);
	}

	/**
	 * An explicit mobile value still beats the cascaded tablet one.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_explicit_mobile_beats_the_cascaded_tablet_value() {
		$result = LegacyStore::normalize_device_keys(
			$this->block(
				array(
					'md' => array( 'minHeight' => '300px' ),
					'sm' => array( 'minHeight' => '180px' ),
				)
			)
		);

		$this->assertSame(
			'180px',
			$this->store( $result )['@mobile']['minHeight'] ?? null,
			'Mobile is authored, not inherited, when it is set.'
		);
	}

	/**
	 * Content authored after the move is left alone.
	 *
	 * The cascade bake must not touch it — core's model is per-viewport over
	 * base, and baking tablet into mobile here would invent a value the author
	 * never set.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_core_shaped_content_is_not_cascaded() {
		$store = array(
			'base'    => array( 'minHeight' => '500px' ),
			'@tablet' => array( 'minHeight' => '300px' ),
		);

		$result = LegacyStore::normalize_device_keys( $this->block( $store ) );

		$this->assertArrayNotHasKey(
			'@mobile',
			$this->store( $result ),
			'Nothing may be invented for a viewport the author left unset.'
		);
	}

	/**
	 * Blocks belonging to other plugins are never touched.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_foreign_blocks_are_left_untouched() {
		$store = array( 'lg' => array( 'minHeight' => '500px' ) );

		$result = LegacyStore::normalize_device_keys( $this->block( $store, 'core/paragraph' ) );

		$this->assertSame(
			$store,
			$this->store( $result ),
			'Only Spectra blocks and core/image are in scope.'
		);
	}

	/**
	 * Pro blocks ARE in scope — they share the store.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_pro_blocks_are_in_scope() {
		$result = LegacyStore::normalize_device_keys(
			$this->block( array( 'md' => array( 'gap' => '12px' ) ), 'spectra-pro/form-button' )
		);

		$this->assertSame(
			'12px',
			$this->store( $result )['@tablet']['gap'] ?? null,
			'Pro blocks must be normalised alongside free ones.'
		);
	}

	/**
	 * A block with no store is returned unchanged.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function test_a_block_without_a_store_is_untouched() {
		$block = array(
			'blockName' => 'spectra/container',
			'attrs'     => array( 'spectraId' => 'test-id' ),
		);

		$this->assertSame( $block, LegacyStore::normalize_device_keys( $block ) );
	}

}
