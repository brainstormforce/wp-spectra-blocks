<?php
/**
 * Tests for the GetGlobalStylesConfig ability.
 *
 * Moved from spectra-blocks-pro — the GBS engine is free and theme-agnostic.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use SpectraBlocks\Abilities\GetGlobalStylesConfig;

/**
 * GetGlobalStylesConfig test case.
 */
class GetGlobalStylesConfigTest extends WP_UnitTestCase {

	/**
	 * Ability instance.
	 *
	 * @var GetGlobalStylesConfig
	 */
	private $ability;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		$this->ability = GetGlobalStylesConfig::instance();

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
	}

	/**
	 * Test returns all sections by default.
	 */
	public function test_returns_all_sections() {
		$result = $this->ability->execute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'system_variables', $result );
		$this->assertArrayHasKey( 'user_css', $result );
		$this->assertArrayHasKey( 'block_defaults', $result );
		$this->assertArrayHasKey( 'block_defaults_enabled', $result );
	}

	/**
	 * Test returns specific section.
	 */
	public function test_returns_specific_section() {
		$result = $this->ability->execute(
			array(
				'sections' => array( 'user_css' ),
			)
		);

		$this->assertArrayHasKey( 'user_css', $result );
		$this->assertArrayNotHasKey( 'system_variables', $result );
	}

	/**
	 * Test reads saved option values.
	 */
	public function test_reads_saved_values() {
		update_option( 'spectra_pro_gs_user_css', 'body { color: red; }' );

		$result = $this->ability->execute(
			array(
				'sections' => array( 'user_css' ),
			)
		);

		$this->assertSame( 'body { color: red; }', $result['user_css'] );

		delete_option( 'spectra_pro_gs_user_css' );
	}

	/**
	 * Test defaults when options not set.
	 */
	public function test_defaults_when_empty() {
		delete_option( 'spectra_pro_gs_system_variables' );
		delete_option( 'spectra_pro_gs_user_css' );
		delete_option( 'spectra_pro_gs_block_defaults' );

		$result = $this->ability->execute( array() );

		$this->assertIsArray( $result['system_variables'] );
		$this->assertSame( '', $result['user_css'] );
		$this->assertIsArray( $result['block_defaults'] );
		$this->assertFalse( $result['block_defaults_enabled'] );
	}

	/**
	 * Test sections=['all'] returns all sections.
	 */
	public function test_all_sections_keyword() {
		$result = $this->ability->execute( array( 'sections' => array( 'all' ) ) );

		$this->assertArrayHasKey( 'system_variables', $result );
		$this->assertArrayHasKey( 'user_css', $result );
		$this->assertArrayHasKey( 'block_defaults', $result );
		$this->assertArrayHasKey( 'block_defaults_enabled', $result );
	}
}
