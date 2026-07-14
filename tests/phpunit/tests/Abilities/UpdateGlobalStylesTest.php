<?php
/**
 * Tests for the UpdateGlobalStyles ability.
 *
 * Moved from spectra-blocks-pro — the GBS engine is free and theme-agnostic.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use SpectraBlocks\Abilities\UpdateGlobalStyles;

/**
 * UpdateGlobalStyles test case.
 */
class UpdateGlobalStylesTest extends WP_UnitTestCase {

	/**
	 * Ability instance.
	 *
	 * @var UpdateGlobalStyles
	 */
	private $ability;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		$this->ability = UpdateGlobalStyles::instance();

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		delete_option( 'spectra_pro_gs_system_variables' );
		delete_option( 'spectra_pro_gs_block_defaults_enabled' );
	}

	/**
	 * Tear down.
	 */
	public function tear_down() {
		delete_option( 'spectra_pro_gs_system_variables' );
		delete_option( 'spectra_pro_gs_block_defaults_enabled' );
		parent::tear_down();
	}

	/**
	 * Test update colors.
	 */
	public function test_update_colors() {
		$result = $this->ability->execute(
			array(
				'colors' => array(
					'primary'   => '#ff0000',
					'secondary' => '#00ff00',
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertContains( 'colors', $result['updated_sections'] );

		$vars = get_option( 'spectra_pro_gs_system_variables' );
		$this->assertSame( '#ff0000', $vars['colors']['primary'] );
		$this->assertSame( '#00ff00', $vars['colors']['secondary'] );
	}

	/**
	 * Test partial update preserves existing values.
	 */
	public function test_partial_update_preserves() {
		update_option(
			'spectra_pro_gs_system_variables',
			array(
				'colors'  => array(
					'primary'   => '#111111',
					'secondary' => '#222222',
				),
				'spacing' => array( 'base' => '16px' ),
			)
		);

		$this->ability->execute(
			array(
				'colors' => array( 'primary' => '#333333' ),
			)
		);

		$vars = get_option( 'spectra_pro_gs_system_variables' );
		$this->assertSame( '#333333', $vars['colors']['primary'] );
		$this->assertSame( '#222222', $vars['colors']['secondary'] );
		$this->assertSame( '16px', $vars['spacing']['base'] );
	}

	/**
	 * Test update spacing.
	 */
	public function test_update_spacing() {
		$result = $this->ability->execute(
			array(
				'spacing' => array( 'base' => '20px' ),
			)
		);

		$this->assertContains( 'spacing', $result['updated_sections'] );
	}

	/**
	 * Test update fontsize.
	 */
	public function test_update_fontsize() {
		$result = $this->ability->execute(
			array(
				'fontsize' => array(
					'base'    => '16px',
					'heading' => '32px',
				),
			)
		);

		$this->assertContains( 'fontsize', $result['updated_sections'] );
	}

	/**
	 * Test toggle block defaults.
	 */
	public function test_toggle_block_defaults() {
		$result = $this->ability->execute(
			array(
				'block_defaults_enabled' => true,
			)
		);

		$this->assertContains( 'block_defaults_enabled', $result['updated_sections'] );
		$this->assertSame( '1', get_option( 'spectra_pro_gs_block_defaults_enabled' ) );
	}

	/**
	 * Test empty input updates nothing.
	 */
	public function test_empty_input() {
		$result = $this->ability->execute( array() );

		$this->assertSame( array(), $result['updated_sections'] );
	}

	/**
	 * Test requires manage_options capability (WP_Error for lower roles).
	 */
	public function test_requires_manage_options() {
		$editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );

		$result = $this->ability->check_permission();
		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test toggle block defaults off.
	 */
	public function test_toggle_block_defaults_off() {
		update_option( 'spectra_pro_gs_block_defaults_enabled', '1' );

		$this->ability->execute( array( 'block_defaults_enabled' => false ) );

		$this->assertSame( '', get_option( 'spectra_pro_gs_block_defaults_enabled' ) );
	}

	/**
	 * Test transient is cleared after update.
	 */
	public function test_transient_cleared() {
		set_transient( 'spectra_pro_gs_variables', 'cached_data', 3600 );

		$this->ability->execute(
			array(
				'colors' => array( 'test' => '#000000' ),
			)
		);

		$this->assertFalse( get_transient( 'spectra_pro_gs_variables' ) );
	}

	/**
	 * Test output shape includes expected keys.
	 */
	public function test_output_shape() {
		$result = $this->ability->execute(
			array(
				'colors' => array( 'test' => '#fff' ),
			)
		);

		$this->assertArrayHasKey( 'updated_sections', $result );
		$this->assertArrayHasKey( 'system_variables', $result );
		$this->assertIsArray( $result['updated_sections'] );
		$this->assertIsArray( $result['system_variables'] );
	}
}
