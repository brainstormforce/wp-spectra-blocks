<?php
/**
 * Tests for the ColorRoles canonical vocabulary.
 *
 * @package Spectra\Tests\StyleGuide\Sync
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\StyleGuide\Sync;

use SpectraBlocks\StyleGuide\ColorModel;
use SpectraBlocks\StyleGuide\Sync\ColorRoles;
use SpectraBlocks\StyleGuide\Sync\MappingResolver;
use WP_UnitTestCase;

/**
 * ColorRolesTest test case.
 *
 * @since x.x.x
 */
class ColorRolesTest extends WP_UnitTestCase {

	/**
	 * all() returns the 3 brand + 7 neutral roles.
	 *
	 * @return void
	 */
	public function test_all_roles(): void {
		$this->assertCount( 11, ColorRoles::all() );
		$this->assertCount( 3, ColorRoles::brand_roles() );
	}

	/**
	 * Foreground is a first-class role sourced from its OWN stored colour.
	 *
	 * It used to be a derived variable that the override layer recomputed on every
	 * read, which meant a theme shipping a `foreground` palette swatch (Spectra One)
	 * had it silently repainted on the first Style Guide save. Pinning the role and
	 * its token here is what keeps that from regressing.
	 *
	 * @return void
	 */
	public function test_foreground_is_a_stored_role(): void {
		$this->assertTrue( ColorRoles::is_valid( ColorRoles::FOREGROUND ) );
		$this->assertFalse( ColorRoles::is_brand( ColorRoles::FOREGROUND ) );

		// Its own token, not a neutral stop borrowed from another role.
		$this->assertSame( 'foreground', ColorRoles::sg_token( ColorRoles::FOREGROUND ) );
		$this->assertSame( 'foreground', ColorModel::slug_for_token( 'foreground' ) );
		$this->assertTrue( ColorModel::is_core_slug( 'foreground' ) );
		$this->assertArrayHasKey( 'foreground', ColorModel::default_colors() );

		// The semantic slug resolves to that token rather than to `neutral-7`.
		$this->assertSame( 'foreground', ColorModel::semantic_map()['foreground'] );
	}

	/**
	 * Spectra One maps the role to its native swatch; Astra has no such slot.
	 *
	 * @return void
	 */
	public function test_foreground_theme_mappings(): void {
		$this->assertSame(
			'foreground',
			MappingResolver::for_theme( 'spectra-one' )->slug_for( ColorRoles::FOREGROUND )
		);
		$this->assertNull( MappingResolver::for_theme( 'astra' )->slug_for( ColorRoles::FOREGROUND ) );
	}

	/**
	 * Brand vs push-only classification.
	 *
	 * @return void
	 */
	public function test_is_brand(): void {
		$this->assertTrue( ColorRoles::is_brand( ColorRoles::PRIMARY ) );
		$this->assertTrue( ColorRoles::is_brand( ColorRoles::SECONDARY ) );
		$this->assertTrue( ColorRoles::is_brand( ColorRoles::ACCENT ) );
		$this->assertFalse( ColorRoles::is_brand( ColorRoles::BODY_TEXT ) );
		$this->assertFalse( ColorRoles::is_brand( ColorRoles::PAGE_BACKGROUND ) );
	}

	/**
	 * Validity check.
	 *
	 * @return void
	 */
	public function test_is_valid(): void {
		$this->assertTrue( ColorRoles::is_valid( ColorRoles::MUTED ) );
		$this->assertFalse( ColorRoles::is_valid( 'not-a-role' ) );
	}

	/**
	 * Every role has a Style Guide source token; brand tokens match the chromatic.
	 *
	 * @return void
	 */
	public function test_sg_token(): void {
		foreach ( ColorRoles::all() as $role ) {
			$this->assertNotNull( ColorRoles::sg_token( $role ), "role {$role} has an SG token" );
		}
		$this->assertSame( 'primary', ColorRoles::sg_token( ColorRoles::PRIMARY ) );
		$this->assertSame( 'secondary', ColorRoles::sg_token( ColorRoles::SECONDARY ) );
		$this->assertSame( 'accent', ColorRoles::sg_token( ColorRoles::ACCENT ) );
		$this->assertNull( ColorRoles::sg_token( 'not-a-role' ) );
	}
}
