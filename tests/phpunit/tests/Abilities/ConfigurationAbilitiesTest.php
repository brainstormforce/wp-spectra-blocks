<?php
/**
 * Tests for Configuration abilities.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use Spectra\Abilities\ToggleBlockActivation;
use Spectra\Abilities\GetBlockActivationStatus;
use Spectra\Abilities\GetPluginSettings;
use Spectra\Abilities\ListSelectedFonts;
use Spectra\Abilities\ListAvailableGoogleFonts;

/**
 * Configuration abilities test case.
 */
class ConfigurationAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user is admin.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
	}

	// -------------------------------------------------------------------------
	// ToggleBlockActivation
	// -------------------------------------------------------------------------

	/**
	 * Test ToggleBlockActivation metadata.
	 */
	public function test_toggle_block_activation_metadata() {
		$ability = ToggleBlockActivation::instance();

		$this->assertSame( 'spectra-blocks/toggle-block-activation', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-configuration', $ability->get_category() );
	}

	/**
	 * Test ToggleBlockActivation requires manage_options.
	 */
	public function test_toggle_block_activation_permission_admin() {
		$this->assertTrue( ToggleBlockActivation::instance()->check_permission() );
	}

	/**
	 * Test ToggleBlockActivation denies editors.
	 */
	public function test_toggle_block_activation_permission_editor() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$result = ToggleBlockActivation::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ToggleBlockActivation execute fails without params.
	 */
	public function test_toggle_block_activation_execute_missing_params() {
		$result = ToggleBlockActivation::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test ToggleBlockActivation execute fails for non-registered block.
	 */
	public function test_toggle_block_activation_execute_nonexistent_block() {
		$result = ToggleBlockActivation::instance()->execute(
			array(
				'block_name' => 'nonexistent-block-xyz',
				'active'     => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	/**
	 * Test ToggleBlockActivation strips spectra/ prefix from block name.
	 */
	public function test_toggle_block_activation_strips_prefix() {
		// Register a test block.
		register_block_type( 'spectra/test-toggle-block', array() );

		$result = ToggleBlockActivation::instance()->execute(
			array(
				'block_name' => 'spectra/test-toggle-block',
				'active'     => false,
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'test-toggle-block', $result['block_name'] );

		unregister_block_type( 'spectra/test-toggle-block' );
	}

	/**
	 * Test ToggleBlockActivation updates option.
	 */
	public function test_toggle_block_activation_updates_option() {
		register_block_type( 'spectra/test-activation-block', array() );

		ToggleBlockActivation::instance()->execute(
			array(
				'block_name' => 'test-activation-block',
				'active'     => false,
			)
		);

		$saved = get_option( '_spectra_blocks_blocks', array() );
		$this->assertSame( 'no', $saved['test-activation-block'] );

		// Toggle back on.
		ToggleBlockActivation::instance()->execute(
			array(
				'block_name' => 'test-activation-block',
				'active'     => true,
			)
		);

		$saved = get_option( '_spectra_blocks_blocks', array() );
		$this->assertSame( 'yes', $saved['test-activation-block'] );

		unregister_block_type( 'spectra/test-activation-block' );
	}

	// -------------------------------------------------------------------------
	// GetBlockActivationStatus
	// -------------------------------------------------------------------------

	/**
	 * Test GetBlockActivationStatus metadata.
	 */
	public function test_get_block_activation_status_metadata() {
		$ability = GetBlockActivationStatus::instance();

		$this->assertSame( 'spectra-blocks/get-block-activation-status', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-configuration', $ability->get_category() );
	}

	/**
	 * Test ToggleBlockActivation input schema.
	 */
	public function test_toggle_block_activation_input_schema() {
		$schema = ToggleBlockActivation::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'block_name', $schema['required'] );
		$this->assertContains( 'active', $schema['required'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'active', $schema['properties'] );
	}

	/**
	 * Test ToggleBlockActivation output schema.
	 */
	public function test_toggle_block_activation_output_schema() {
		$schema = ToggleBlockActivation::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'active', $schema['properties'] );
	}

	/**
	 * Test GetBlockActivationStatus requires manage_options.
	 */
	public function test_get_block_activation_status_permission() {
		$this->assertTrue( GetBlockActivationStatus::instance()->check_permission() );

		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$result = GetBlockActivationStatus::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test GetBlockActivationStatus execute returns expected structure.
	 */
	public function test_get_block_activation_status_execute() {
		if ( ! class_exists( 'Spectra_Blocks_Admin_Helper' ) || ! method_exists( 'Spectra_Blocks_Admin_Helper', 'get_block_options' ) ) {
			$this->markTestSkipped( 'Spectra_Blocks_Admin_Helper not available.' );
		}

		$result = GetBlockActivationStatus::instance()->execute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'blocks', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'active_count', $result );
		$this->assertArrayHasKey( 'inactive_count', $result );
		$this->assertIsArray( $result['blocks'] );
		$this->assertSame( $result['active_count'] + $result['inactive_count'], $result['total'] );
	}

	// -------------------------------------------------------------------------
	// GetPluginSettings
	// -------------------------------------------------------------------------

	/**
	 * Test GetPluginSettings metadata.
	 */
	public function test_get_plugin_settings_metadata() {
		$ability = GetPluginSettings::instance();

		$this->assertSame( 'spectra-blocks/get-plugin-settings', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-configuration', $ability->get_category() );
	}

	/**
	 * Test GetPluginSettings requires manage_options.
	 */
	public function test_get_plugin_settings_permission() {
		$this->assertTrue( GetPluginSettings::instance()->check_permission() );

		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$result = GetPluginSettings::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test GetPluginSettings execute returns settings array.
	 */
	public function test_get_plugin_settings_execute() {
		if ( ! class_exists( 'Spectra_Blocks_Admin_Helper' ) || ! method_exists( 'Spectra_Blocks_Admin_Helper', 'get_admin_settings_shareable_data' ) ) {
			$this->markTestSkipped( 'Spectra_Blocks_Admin_Helper not available.' );
		}

		$result = GetPluginSettings::instance()->execute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'settings', $result );
	}

	/**
	 * Test GetPluginSettings has empty input schema.
	 */
	public function test_get_plugin_settings_empty_input_schema() {
		$schema = GetPluginSettings::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertInstanceOf( stdClass::class, $schema['properties'] );
	}

	/**
	 * Test GetBlockActivationStatus input schema.
	 */
	public function test_get_block_activation_status_input_schema() {
		$schema = GetBlockActivationStatus::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
	}

	/**
	 * Test GetBlockActivationStatus output schema.
	 */
	public function test_get_block_activation_status_output_schema() {
		$schema = GetBlockActivationStatus::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'blocks', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
		$this->assertArrayHasKey( 'active_count', $schema['properties'] );
		$this->assertArrayHasKey( 'inactive_count', $schema['properties'] );
	}

	/**
	 * Test GetPluginSettings output schema.
	 */
	public function test_get_plugin_settings_output_schema() {
		$schema = GetPluginSettings::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'settings', $schema['properties'] );
	}

	// -------------------------------------------------------------------------
	// ListSelectedFonts
	// -------------------------------------------------------------------------

	/**
	 * Test ListSelectedFonts metadata.
	 */
	public function test_list_selected_fonts_metadata() {
		$ability = ListSelectedFonts::instance();

		$this->assertSame( 'spectra-blocks/list-selected-fonts', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-configuration', $ability->get_category() );
	}

	/**
	 * Test ListSelectedFonts execute returns expected structure.
	 */
	public function test_list_selected_fonts_execute() {
		if ( ! class_exists( 'Spectra\FontManager' ) ) {
			$this->markTestSkipped( 'FontManager not available.' );
		}

		$result = ListSelectedFonts::instance()->execute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'fonts', $result );
		$this->assertArrayHasKey( 'count', $result );
		$this->assertArrayHasKey( 'load_locally', $result );
		$this->assertArrayHasKey( 'global_loading', $result );
		$this->assertIsArray( $result['fonts'] );
		$this->assertSame( count( $result['fonts'] ), $result['count'] );
	}

	/**
	 * Test ListSelectedFonts requires manage_options.
	 */
	public function test_list_selected_fonts_permission_admin() {
		$this->assertTrue( ListSelectedFonts::instance()->check_permission() );
	}

	/**
	 * Test ListSelectedFonts denies subscribers.
	 */
	public function test_list_selected_fonts_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = ListSelectedFonts::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ListSelectedFonts input schema.
	 */
	public function test_list_selected_fonts_input_schema() {
		$schema = ListSelectedFonts::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
	}

	/**
	 * Test ListSelectedFonts output schema.
	 */
	public function test_list_selected_fonts_output_schema() {
		$schema = ListSelectedFonts::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'fonts', $schema['properties'] );
		$this->assertArrayHasKey( 'count', $schema['properties'] );
	}

	// -------------------------------------------------------------------------
	// ListAvailableGoogleFonts
	// -------------------------------------------------------------------------

	/**
	 * Test ListAvailableGoogleFonts metadata.
	 */
	public function test_list_available_google_fonts_metadata() {
		$ability = ListAvailableGoogleFonts::instance();

		$this->assertSame( 'spectra-blocks/list-available-google-fonts', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-configuration', $ability->get_category() );
	}

	/**
	 * Test ListAvailableGoogleFonts execute returns expected structure.
	 */
	public function test_list_available_google_fonts_execute() {
		if ( ! class_exists( 'Spectra\FontManager' ) || ! method_exists( 'Spectra\FontManager', 'get_google_font_families' ) ) {
			$this->markTestSkipped( 'FontManager::get_google_font_families not available.' );
		}

		$result = ListAvailableGoogleFonts::instance()->execute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'fonts', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'total_pages', $result );
		$this->assertArrayHasKey( 'page', $result );
		$this->assertSame( 1, $result['page'] );
	}

	/**
	 * Test ListAvailableGoogleFonts pagination.
	 */
	public function test_list_available_google_fonts_pagination() {
		if ( ! class_exists( 'Spectra\FontManager' ) || ! method_exists( 'Spectra\FontManager', 'get_google_font_families' ) ) {
			$this->markTestSkipped( 'FontManager::get_google_font_families not available.' );
		}

		$result = ListAvailableGoogleFonts::instance()->execute(
			array(
				'page'     => 2,
				'per_page' => 10,
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 2, $result['page'] );
		$this->assertLessThanOrEqual( 10, count( $result['fonts'] ) );
	}

	/**
	 * Test ListAvailableGoogleFonts requires manage_options.
	 */
	public function test_list_available_google_fonts_permission_admin() {
		$this->assertTrue( ListAvailableGoogleFonts::instance()->check_permission() );
	}

	/**
	 * Test ListAvailableGoogleFonts denies subscribers.
	 */
	public function test_list_available_google_fonts_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = ListAvailableGoogleFonts::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ListAvailableGoogleFonts output schema.
	 */
	public function test_list_available_google_fonts_output_schema() {
		$schema = ListAvailableGoogleFonts::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'fonts', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
		$this->assertArrayHasKey( 'total_pages', $schema['properties'] );
		$this->assertArrayHasKey( 'page', $schema['properties'] );
	}

	/**
	 * Test ListAvailableGoogleFonts input schema.
	 */
	public function test_list_available_google_fonts_input_schema() {
		$schema = ListAvailableGoogleFonts::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'search', $schema['properties'] );
		$this->assertArrayHasKey( 'page', $schema['properties'] );
		$this->assertArrayHasKey( 'per_page', $schema['properties'] );
	}

	/**
	 * Test GetBlockActivationStatus denies subscribers.
	 */
	public function test_get_block_activation_status_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = GetBlockActivationStatus::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ListAvailableGoogleFonts search filter returns results.
	 */
	public function test_list_available_google_fonts_search_filter() {
		if ( ! class_exists( 'Spectra\FontManager' ) || ! method_exists( 'Spectra\FontManager', 'get_google_font_families' ) ) {
			$this->markTestSkipped( 'FontManager::get_google_font_families not available.' );
		}

		$result = ListAvailableGoogleFonts::instance()->execute(
			array( 'search' => 'Roboto' )
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'fonts', $result );
		// The search parameter should be accepted and return a valid response.
		$this->assertLessThanOrEqual( $result['total'], count( $result['fonts'] ) + ( ( $result['page'] - 1 ) * 50 ) );
	}

	/**
	 * Test ListAvailableGoogleFonts grants editors (uses edit_posts).
	 */
	public function test_list_available_google_fonts_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertTrue( ListAvailableGoogleFonts::instance()->check_permission() );
	}

	/**
	 * Test ToggleBlockActivation denies subscribers.
	 */
	public function test_toggle_block_activation_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = ToggleBlockActivation::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test GetPluginSettings denies subscribers.
	 */
	public function test_get_plugin_settings_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = GetPluginSettings::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ListSelectedFonts grants editors (uses edit_posts).
	 */
	public function test_list_selected_fonts_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertTrue( ListSelectedFonts::instance()->check_permission() );
	}

	/**
	 * Test ListAvailableGoogleFonts per_page is capped at 100.
	 */
	public function test_list_available_google_fonts_per_page_cap() {
		if ( ! class_exists( 'Spectra\FontManager' ) || ! method_exists( 'Spectra\FontManager', 'get_google_font_families' ) ) {
			$this->markTestSkipped( 'FontManager::get_google_font_families not available.' );
		}

		$result = ListAvailableGoogleFonts::instance()->execute(
			array( 'per_page' => 500 )
		);

		$this->assertIsArray( $result );
		$this->assertLessThanOrEqual( 100, count( $result['fonts'] ) );
	}
}
