<?php
/**
 * Tests for the ThemeColorMapping value object.
 *
 * @package Spectra\Tests\StyleGuide\Sync
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\StyleGuide\Sync;

use SpectraBlocks\StyleGuide\Sync\ColorRoles;
use SpectraBlocks\StyleGuide\Sync\ThemeColorMapping;
use WP_UnitTestCase;

/**
 * ThemeColorMappingTest test case.
 *
 * @since x.x.x
 */
class ThemeColorMappingTest extends WP_UnitTestCase {

	/**
	 * Forward lookup and null/blank normalization.
	 *
	 * @return void
	 */
	public function test_forward_and_null(): void {
		$m = new ThemeColorMapping(
			array(
				ColorRoles::PRIMARY      => 'primary',
				ColorRoles::HEADING_TEXT => null,
				ColorRoles::BODY_TEXT    => '',
			)
		);
		$this->assertSame( 'primary', $m->slug_for( ColorRoles::PRIMARY ) );
		$this->assertTrue( $m->has_role( ColorRoles::PRIMARY ) );
		$this->assertNull( $m->slug_for( ColorRoles::HEADING_TEXT ) );
		$this->assertFalse( $m->has_role( ColorRoles::HEADING_TEXT ) );
		$this->assertFalse( $m->has_role( ColorRoles::BODY_TEXT ), 'blank slug treated as unmapped' );
	}

	/**
	 * Unknown roles are dropped.
	 *
	 * @return void
	 */
	public function test_unknown_role_dropped(): void {
		$m = new ThemeColorMapping( array( 'bogus' => 'x' ) );
		$this->assertSame( array(), $m->to_array() );
	}

	/**
	 * Inverse lookup and ambiguity.
	 *
	 * @return void
	 */
	public function test_inverse_and_ambiguity(): void {
		// `contrast` serves both a brand (primary) and a push-only (body-text) role.
		$m = new ThemeColorMapping(
			array(
				ColorRoles::PRIMARY   => 'contrast',
				ColorRoles::BODY_TEXT => 'contrast',
				ColorRoles::SURFACE   => 'base',
			)
		);
		$this->assertSame( array( ColorRoles::PRIMARY, ColorRoles::BODY_TEXT ), $m->roles_for( 'contrast' ) );
		$this->assertTrue( $m->is_ambiguous( 'contrast' ) );
		$this->assertNull( $m->unambiguous_role_for( 'contrast' ), 'ambiguous slug has no single role' );
		$this->assertSame( ColorRoles::SURFACE, $m->unambiguous_role_for( 'base' ) );
	}

	/**
	 * brand_role_for resolves a slug shared between a brand and a push-only role.
	 *
	 * @return void
	 */
	public function test_brand_role_for(): void {
		// primary + link both back the `primary` slug -> brand wins for reverse.
		$m = new ThemeColorMapping(
			array(
				ColorRoles::PRIMARY => 'primary',
				ColorRoles::LINK    => 'primary',
				ColorRoles::MUTED   => 'neutral',
			)
		);
		$this->assertSame( ColorRoles::PRIMARY, $m->brand_role_for( 'primary' ) );
		$this->assertNull( $m->brand_role_for( 'neutral' ), 'push-only slug has no brand role' );

		// Two brand roles on one slug is a genuine collision -> null (push-only).
		$c = new ThemeColorMapping(
			array(
				ColorRoles::PRIMARY   => 'brand',
				ColorRoles::SECONDARY => 'brand',
			)
		);
		$this->assertNull( $c->brand_role_for( 'brand' ) );
	}

	/**
	 * mapped_roles returns only non-null roles, in canonical order.
	 *
	 * @return void
	 */
	public function test_mapped_roles_order(): void {
		$m = new ThemeColorMapping(
			array(
				ColorRoles::BODY_TEXT => 'body',
				ColorRoles::PRIMARY   => 'primary',
				ColorRoles::ACCENT    => null,
			)
		);
		$this->assertSame( array( ColorRoles::PRIMARY, ColorRoles::BODY_TEXT ), $m->mapped_roles() );
	}
}
