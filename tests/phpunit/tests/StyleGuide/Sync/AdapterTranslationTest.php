<?php
/**
 * Tests for the store adapters' slug<->store translation logic.
 *
 * Covers the pure parsing/translation surface (Astra slot parsing + hook-payload
 * reading, FSE theme-entry extraction). The full DB read/write paths are exercised
 * by live integration checks, not here.
 *
 * @package Spectra\Tests\StyleGuide\Sync
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\StyleGuide\Sync;

use SpectraBlocks\StyleGuide\Sync\Astra\AstraPaletteAdapter;
use SpectraBlocks\StyleGuide\Sync\FseGlobalStylesAdapter;
use WP_UnitTestCase;
use ReflectionMethod;

/**
 * AdapterTranslationTest test case.
 *
 * @since x.x.x
 */
class AdapterTranslationTest extends WP_UnitTestCase {

	/**
	 * Astra slug -> slot index parsing.
	 *
	 * @return void
	 */
	public function test_astra_slot_index(): void {
		$this->assertSame( 0, AstraPaletteAdapter::slot_index( 'ast-global-color-0' ) );
		$this->assertSame( 6, AstraPaletteAdapter::slot_index( 'ast-global-color-6' ) );
		$this->assertNull( AstraPaletteAdapter::slot_index( 'primary' ) );
		$this->assertNull( AstraPaletteAdapter::slot_index( 'ast-global-color-x' ) );
	}

	/**
	 * read_from parses an astra-settings payload into ast-global-color-N => hex.
	 *
	 * @return void
	 */
	public function test_astra_read_from(): void {
		$adapter  = new AstraPaletteAdapter();
		$settings = array(
			'global-color-palette' => array(
				'currentPalette' => 'palette_1',
				'palettes'       => array(
					'palette_1' => array( '#111111', '#222222', '#333333' ),
				),
			),
		);
		$out = $adapter->read_from( $settings );
		$this->assertSame( '#111111', $out['ast-global-color-0'] );
		$this->assertSame( '#333333', $out['ast-global-color-2'] );
	}

	/**
	 * read_from honors the active palette selection.
	 *
	 * @return void
	 */
	public function test_astra_read_from_current_palette(): void {
		$adapter  = new AstraPaletteAdapter();
		$settings = array(
			'global-color-palette' => array(
				'currentPalette' => 'palette_2',
				'palettes'       => array(
					'palette_1' => array( '#aaaaaa' ),
					'palette_2' => array( '#00ff00' ),
				),
			),
		);
		$out = $adapter->read_from( $settings );
		$this->assertSame( '#00ff00', $out['ast-global-color-0'] );
	}

	/**
	 * read_from is safe on malformed / missing input.
	 *
	 * @return void
	 */
	public function test_astra_read_from_malformed(): void {
		$adapter = new AstraPaletteAdapter();
		$this->assertSame( array(), $adapter->read_from( null ) );
		$this->assertSame( array(), $adapter->read_from( 'nonsense' ) );
		$this->assertSame( array(), $adapter->read_from( array( 'other-key' => 1 ) ) );
	}

	/**
	 * The write-in-progress guard defaults to false.
	 *
	 * @return void
	 */
	public function test_astra_is_writing_default(): void {
		$this->assertFalse( AstraPaletteAdapter::is_writing() );
	}

	/**
	 * FSE adapter extracts the theme-origin palette entries from post content.
	 *
	 * @return void
	 */
	public function test_fse_extract_theme_entries(): void {
		$ref = new ReflectionMethod( FseGlobalStylesAdapter::class, 'extract_theme_entries' );
		$ref->setAccessible( true );
		$adapter = new FseGlobalStylesAdapter();

		$content = array(
			'settings' => array(
				'color' => array(
					'palette' => array(
						'theme' => array(
							array(
								'slug'  => 'primary',
								'color' => '#123456',
							),
						),
					),
				),
			),
		);
		$entries = $ref->invoke( $adapter, $content );
		$this->assertCount( 1, $entries );
		$this->assertSame( 'primary', $entries[0]['slug'] );

		// Missing palette -> empty, no notice.
		$this->assertSame( array(), $ref->invoke( $adapter, array() ) );
	}
}
