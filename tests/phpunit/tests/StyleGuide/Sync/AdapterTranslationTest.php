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
	 * The slot map follows Astra's compatibility flag, on BOTH layouts.
	 *
	 * Astra moves four background slots when `astra_4_8_9_compatibility()` is false,
	 * and its own resolver is the contract we mirror:
	 *   $primary_color = $reorganize ? color-4 : color-5
	 *   $subtle_color  = $reorganize ? color-7 : color-6
	 *
	 * A hardcoded map is therefore wrong on every pre-4.8.9 install — which is how
	 * Outline previously landed on a slot the push never wrote. Both layouts are
	 * asserted because the reorganized one alone cannot catch that class of drift.
	 *
	 * @return void
	 */
	public function test_shade_map_follows_the_compat_flag(): void {
		// Drive `semantic_index()` directly: it takes the resolved flag as an
		// argument, so both layouts are reachable without Astra loaded. Going
		// through shade_map() would only ever exercise the reorganized branch here,
		// because uses_reorganized_slots() defaults to true when Astra is absent —
		// which is exactly the half that never regressed.
		$index = new ReflectionMethod( AstraPaletteAdapter::class, 'semantic_index' );
		$index->setAccessible( true );
		$slot = static fn( string $semantic, bool $reorganize ) => $index->invoke( null, $semantic, $reorganize );

		// Fixed slots — never moved by the flag.
		foreach ( array( true, false ) as $reorganize ) {
			$this->assertSame( 0, $slot( 'brand', $reorganize ) );
			$this->assertSame( 1, $slot( 'alt-brand', $reorganize ) );
			$this->assertSame( 2, $slot( 'headings', $reorganize ) );
			$this->assertSame( 3, $slot( 'text', $reorganize ) );
		}

		// Backgrounds swap with the flag — mirrors Astra's own resolver.
		$this->assertSame( 4, $slot( 'primary-bg', true ), 'reorganized: page background is slot 4' );
		$this->assertSame( 5, $slot( 'secondary-bg', true ), 'reorganized: surface is slot 5' );
		$this->assertSame( 5, $slot( 'primary-bg', false ), 'legacy: page background is slot 5' );
		$this->assertSame( 4, $slot( 'secondary-bg', false ), 'legacy: surface is slot 4' );
	}

	/**
	 * With Astra absent the map defaults to the REORGANIZED layout.
	 *
	 * The render-time aliases are emitted on any theme, so a merely-missing Astra
	 * class must not hand them the legacy slot order.
	 *
	 * @return void
	 */
	public function test_shade_map_defaults_to_reorganized_without_astra(): void {
		$this->assertFalse( class_exists( '\Astra_Dynamic_CSS' ), 'guard: Astra is not loaded in the test env' );

		$map = AstraPaletteAdapter::shade_map();
		$this->assertSame( 'neutral-0', $map[4] );
		$this->assertSame( 'neutral-1', $map[5] );
		$this->assertSame( 'neutral-2', $map[6] );
		$this->assertSame( 'neutral-4', $map[7] );
		$this->assertSame( 'accent', $map[8] );
	}

	/**
	 * Outline is PINNED to slot 6 on every install, both layouts.
	 *
	 * Product decision: the slot NUMBER is kept stable across sites, so Outline does
	 * not follow Astra's reorganize flag the way the page/surface backgrounds do.
	 * The consequence is deliberate and asserted here so it cannot be "corrected"
	 * silently: on a legacy install slot 6 is Astra's SUBTLE background, so the
	 * number is stable while the Astra role it fills is not.
	 *
	 * The push and the render aliases both resolve through this one method, so this
	 * also guards the failure that shipped in 1.0.4 — a hardcoded render map beside
	 * a flag-aware push, which wrote one slot and painted another.
	 *
	 * @return void
	 */
	public function test_every_colour_fills_the_same_astra_role_on_both_layouts(): void {
		$index = new ReflectionMethod( AstraPaletteAdapter::class, 'semantic_index' );
		$index->setAccessible( true );

		// Astra's own resolver: role => [reorganized slot, legacy slot].
		$roles = array(
			'primary-bg'   => array( 4, 5 ), // Primary background   -> Background.
			'secondary-bg' => array( 5, 4 ), // Secondary background -> Surface.
			'alternate-bg' => array( 6, 7 ), // Alternate background -> Outline.
			'subtle-bg'    => array( 7, 6 ), // Subtle background    -> Neutral.
			'other-supp'   => array( 8, 8 ), // Other supporting     -> Accent (never moves).
		);

		foreach ( $roles as $semantic => list( $reorg_slot, $legacy_slot ) ) {
			$this->assertSame( $reorg_slot, $index->invoke( null, $semantic, true ), "$semantic (reorganized)" );
			$this->assertSame( $legacy_slot, $index->invoke( null, $semantic, false ), "$semantic (legacy)" );
		}

		// The token each role carries is layout-independent — that is what makes the
		// ROLE consistent across sites even though the slot NUMBER differs.
		$this->assertSame( 'neutral-2', AstraPaletteAdapter::SEMANTIC_TOKENS['alternate-bg'] );
		$this->assertSame( 'neutral-4', AstraPaletteAdapter::SEMANTIC_TOKENS['subtle-bg'] );
		$this->assertSame( 'accent', AstraPaletteAdapter::SEMANTIC_TOKENS['other-supp'] );
	}

	/**
	 * Regression guard: Outline must never land on Astra's SUBTLE background.
	 *
	 * Pinning slots 6/7 to fixed numbers inverted the roles on legacy installs —
	 * Outline filled Subtle background and Neutral filled Alternate background,
	 * mirrored from a reorganized site. This asserts the pair against Astra's own
	 * resolver on both layouts so that cannot come back.
	 *
	 * @return void
	 */
	public function test_outline_and_neutral_are_never_swapped(): void {
		$index = new ReflectionMethod( AstraPaletteAdapter::class, 'semantic_index' );
		$index->setAccessible( true );

		foreach ( array( true => array( 6, 7 ), false => array( 7, 6 ) ) as $reorganize => list( $alternate, $subtle ) ) {
			$layout = $reorganize ? 'reorganized' : 'legacy';
			$this->assertSame( $alternate, $index->invoke( null, 'alternate-bg', (bool) $reorganize ), "Outline fills Alternate background ($layout)" );
			$this->assertSame( $subtle, $index->invoke( null, 'subtle-bg', (bool) $reorganize ), "Neutral fills Subtle background ($layout)" );
			$this->assertNotSame(
				$index->invoke( null, 'alternate-bg', (bool) $reorganize ),
				$index->invoke( null, 'subtle-bg', (bool) $reorganize ),
				"the two never collide ($layout)"
			);
		}
	}

	/**
	 * All nine Astra slots are mapped, and each takes a DISTINCT Style Guide token.
	 *
	 * The 1:1 rule is what makes the reverse sync unambiguous — two slots sharing a
	 * token could not both be pulled back. Asserted on both layouts because the
	 * flag-aware slots (4/5) and the pinned ones (6/7/8) are resolved by different
	 * branches, and a collision could appear in one layout only.
	 *
	 * @return void
	 */
	public function test_every_astra_slot_maps_to_a_distinct_token(): void {
		$index = new ReflectionMethod( AstraPaletteAdapter::class, 'semantic_index' );
		$index->setAccessible( true );

		foreach ( array( true, false ) as $reorganize ) {
			$layout = $reorganize ? 'reorganized' : 'legacy';

			$slots = array();
			foreach ( AstraPaletteAdapter::SEMANTIC_TOKENS as $semantic => $token ) {
				$slot = $index->invoke( null, $semantic, $reorganize );
				$this->assertNotNull( $slot, "$semantic resolves to a slot ($layout)" );
				$slots[ $slot ] = $token;
			}

			// Sort before comparing: on the legacy layout primary-bg resolves to 5 and
			// secondary-bg to 4, so declaration order inserts the keys 5-then-4. Only
			// the SET of slots matters here, not the order they were filled in.
			ksort( $slots );
			$this->assertCount( 9, $slots, "all nine Astra slots are filled ($layout)" );
			$this->assertSame( range( 0, 8 ), array_keys( $slots ), "slots 0-8 with no gaps ($layout)" );
			$this->assertSame(
				count( $slots ),
				count( array_unique( $slots ) ),
				"no two slots share a Style Guide token ($layout)"
			);
		}
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
