<?php
/**
 * Tests for the ColorRoles canonical vocabulary.
 *
 * @package Spectra\Tests\StyleGuide\Sync
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\StyleGuide\Sync;

use SpectraBlocks\StyleGuide\Sync\ColorRoles;
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
		$this->assertCount( 10, ColorRoles::all() );
		$this->assertCount( 3, ColorRoles::brand_roles() );
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
