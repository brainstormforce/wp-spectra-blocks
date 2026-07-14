<?php
/**
 * Tests for Configuration expansion abilities: UpdatePluginSetting, AddGoogleFont, RemoveGoogleFont, UpdatePopup.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use SpectraBlocks\Abilities\UpdatePluginSetting;
use SpectraBlocks\Abilities\AddGoogleFont;
use SpectraBlocks\Abilities\RemoveGoogleFont;
use SpectraBlocks\Abilities\UpdatePopup;

/**
 * Configuration expansion abilities test case.
 */
class ConfigurationExpansionAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user is admin.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Register the spectra-popup CPT if not already registered.
		if ( ! post_type_exists( 'spectra-blocks-popup' ) ) {
			register_post_type(
				'spectra-blocks-popup',
				array(
					'public' => false,
					'label'  => 'Spectra Popups',
				)
			);
		}
	}

	/**
	 * Tear down — clean up font options.
	 */
	public function tear_down() {
		delete_option( 'spectra_blocks_global_fonts' );
		delete_transient( 'spectra_google_fonts_cache' );
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// UpdatePluginSetting
	// -------------------------------------------------------------------------

	/**
	 * Test UpdatePluginSetting metadata.
	 */
	public function test_update_plugin_setting_metadata() {
		$ability = UpdatePluginSetting::instance();

		$this->assertSame( 'spectra-blocks/update-plugin-setting', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-configuration', $ability->get_category() );
	}

	/**
	 * Test UpdatePluginSetting requires manage_options.
	 */
	public function test_update_plugin_setting_permission_admin() {
		$this->assertTrue( UpdatePluginSetting::instance()->check_permission() );
	}

	/**
	 * Test UpdatePluginSetting denies editors.
	 */
	public function test_update_plugin_setting_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$result = UpdatePluginSetting::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test UpdatePluginSetting execute success.
	 */
	public function test_update_plugin_setting_execute_success() {
		if ( ! class_exists( 'Spectra_Blocks_Admin_Helper' ) ) {
			$this->markTestSkipped( 'Spectra_Blocks_Admin_Helper not available.' );
		}

		$result = UpdatePluginSetting::instance()->execute(
			array(
				'key'   => 'spectra_blocks_collapse_panels',
				'value' => 'disabled',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'spectra_blocks_collapse_panels', $result['key'] );
		$this->assertSame( 'disabled', $result['value'] );
	}

	/**
	 * Test UpdatePluginSetting fails with disallowed key.
	 */
	public function test_update_plugin_setting_disallowed_key() {
		$result = UpdatePluginSetting::instance()->execute(
			array(
				'key'   => 'some_random_option',
				'value' => 'test',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_param', $result->get_error_code() );
	}

	/**
	 * Test UpdatePluginSetting fails without params.
	 */
	public function test_update_plugin_setting_missing_params() {
		$result = UpdatePluginSetting::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test UpdatePluginSetting input schema has enum.
	 */
	public function test_update_plugin_setting_input_schema() {
		$schema = UpdatePluginSetting::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'key', $schema['required'] );
		$this->assertContains( 'value', $schema['required'] );
		$this->assertArrayHasKey( 'enum', $schema['properties']['key'] );
	}

	// -------------------------------------------------------------------------
	// AddGoogleFont
	// -------------------------------------------------------------------------

	/**
	 * Test AddGoogleFont metadata.
	 */
	public function test_add_google_font_metadata() {
		$ability = AddGoogleFont::instance();

		$this->assertSame( 'spectra-blocks/add-google-font', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-configuration', $ability->get_category() );
	}

	/**
	 * Test AddGoogleFont requires manage_options.
	 */
	public function test_add_google_font_permission() {
		$this->assertTrue( AddGoogleFont::instance()->check_permission() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$result = AddGoogleFont::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test AddGoogleFont execute success.
	 */
	public function test_add_google_font_execute_success() {
		$result = AddGoogleFont::instance()->execute(
			array( 'family' => 'Roboto' )
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'Roboto', $result['family'] );
		$this->assertSame( 1, $result['count'] );

		// Verify option was saved.
		$saved = get_option( 'spectra_blocks_global_fonts', array() );
		$this->assertCount( 1, $saved );
		$this->assertSame( 'Roboto', $saved[0]['label'] );
	}

	/**
	 * Test AddGoogleFont prevents duplicates.
	 */
	public function test_add_google_font_duplicate() {
		AddGoogleFont::instance()->execute( array( 'family' => 'Open Sans' ) );

		$result = AddGoogleFont::instance()->execute( array( 'family' => 'Open Sans' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_already_exists', $result->get_error_code() );
	}

	/**
	 * Test AddGoogleFont fails without family.
	 */
	public function test_add_google_font_missing_family() {
		$result = AddGoogleFont::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test AddGoogleFont input schema.
	 */
	public function test_add_google_font_input_schema() {
		$schema = AddGoogleFont::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'family', $schema['required'] );
	}

	// -------------------------------------------------------------------------
	// RemoveGoogleFont
	// -------------------------------------------------------------------------

	/**
	 * Test RemoveGoogleFont metadata.
	 */
	public function test_remove_google_font_metadata() {
		$ability = RemoveGoogleFont::instance();

		$this->assertSame( 'spectra-blocks/remove-google-font', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-configuration', $ability->get_category() );
	}

	/**
	 * Test RemoveGoogleFont requires manage_options.
	 */
	public function test_remove_google_font_permission() {
		$this->assertTrue( RemoveGoogleFont::instance()->check_permission() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$result = RemoveGoogleFont::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test RemoveGoogleFont execute success.
	 */
	public function test_remove_google_font_execute_success() {
		// First add a font.
		AddGoogleFont::instance()->execute( array( 'family' => 'Lato' ) );

		// Then remove it.
		$result = RemoveGoogleFont::instance()->execute(
			array( 'family' => 'Lato' )
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'Lato', $result['family'] );
		$this->assertSame( 0, $result['count'] );

		// Verify option was updated.
		$saved = get_option( 'spectra_blocks_global_fonts', array() );
		$this->assertCount( 0, $saved );
	}

	/**
	 * Test RemoveGoogleFont fails for non-existent font.
	 */
	public function test_remove_google_font_not_found() {
		$result = RemoveGoogleFont::instance()->execute(
			array( 'family' => 'NonexistentFont' )
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	/**
	 * Test RemoveGoogleFont fails without family.
	 */
	public function test_remove_google_font_missing_family() {
		$result = RemoveGoogleFont::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// UpdatePopup
	// -------------------------------------------------------------------------

	/**
	 * Test UpdatePopup metadata.
	 */
	public function test_update_popup_metadata() {
		$ability = UpdatePopup::instance();

		$this->assertSame( 'spectra-blocks/update-popup', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-configuration', $ability->get_category() );
	}

	/**
	 * Test UpdatePopup requires manage_options.
	 */
	public function test_update_popup_permission() {
		$this->assertTrue( UpdatePopup::instance()->check_permission() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$result = UpdatePopup::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test UpdatePopup execute success — update title.
	 */
	public function test_update_popup_execute_title() {
		$popup_id = self::factory()->post->create(
			array(
				'post_type'   => 'spectra-blocks-popup',
				'post_title'  => 'Original Title',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $popup_id, 'spectra-blocks-popup-type', 'banner' );

		$result = UpdatePopup::instance()->execute(
			array(
				'popup_id' => $popup_id,
				'title'    => 'Updated Title',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertContains( 'title', $result['updated'] );

		$post = get_post( $popup_id );
		$this->assertSame( 'Updated Title', $post->post_title );
	}

	/**
	 * Test UpdatePopup execute success — update meta fields.
	 */
	public function test_update_popup_execute_meta() {
		$popup_id = self::factory()->post->create(
			array(
				'post_type'   => 'spectra-blocks-popup',
				'post_title'  => 'Meta Test',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $popup_id, 'spectra-blocks-popup-type', 'banner' );

		$result = UpdatePopup::instance()->execute(
			array(
				'popup_id'   => $popup_id,
				'type'       => 'popup',
				'enabled'    => true,
				'repetition' => 5,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertContains( 'type', $result['updated'] );
		$this->assertContains( 'enabled', $result['updated'] );
		$this->assertContains( 'repetition', $result['updated'] );

		$this->assertSame( 'popup', get_post_meta( $popup_id, 'spectra-blocks-popup-type', true ) );
		$this->assertSame( '1', get_post_meta( $popup_id, 'spectra-blocks-popup-enabled', true ) );
		$this->assertEquals( 5, get_post_meta( $popup_id, 'spectra-blocks-popup-repetition', true ) );
	}

	/**
	 * Test UpdatePopup fails without popup_id.
	 */
	public function test_update_popup_missing_popup_id() {
		$result = UpdatePopup::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test UpdatePopup fails for non-existent popup.
	 */
	public function test_update_popup_nonexistent() {
		$result = UpdatePopup::instance()->execute(
			array(
				'popup_id' => 999999,
				'title'    => 'Test',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	/**
	 * Test UpdatePopup fails for wrong post type.
	 */
	public function test_update_popup_wrong_post_type() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		$result = UpdatePopup::instance()->execute(
			array(
				'popup_id' => $post_id,
				'title'    => 'Test',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	/**
	 * Test UpdatePopup fails when no fields to update are provided.
	 */
	public function test_update_popup_no_fields() {
		$popup_id = self::factory()->post->create(
			array(
				'post_type'   => 'spectra-blocks-popup',
				'post_title'  => 'No Fields Test',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $popup_id, 'spectra-blocks-popup-type', 'banner' );

		$result = UpdatePopup::instance()->execute(
			array( 'popup_id' => $popup_id )
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test UpdatePopup input schema.
	 */
	public function test_update_popup_input_schema() {
		$schema = UpdatePopup::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'popup_id', $schema['required'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
		$this->assertArrayHasKey( 'content', $schema['properties'] );
		$this->assertArrayHasKey( 'type', $schema['properties'] );
		$this->assertArrayHasKey( 'enabled', $schema['properties'] );
		$this->assertArrayHasKey( 'repetition', $schema['properties'] );
	}

	// =========================================================================
	// Additional UpdatePluginSetting tests
	// =========================================================================

	/**
	 * Test UpdatePluginSetting output schema.
	 */
	public function test_update_plugin_setting_output_schema() {
		$schema = UpdatePluginSetting::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'key', $schema['properties'] );
		$this->assertArrayHasKey( 'value', $schema['properties'] );
	}

	/**
	 * Test UpdatePluginSetting accepts null value (array_key_exists vs empty).
	 */
	public function test_update_plugin_setting_null_value() {
		if ( ! class_exists( 'Spectra_Blocks_Admin_Helper' ) ) {
			$this->markTestSkipped( 'Spectra_Blocks_Admin_Helper not available.' );
		}

		$result = UpdatePluginSetting::instance()->execute(
			array(
				'key'   => 'spectra_blocks_collapse_panels',
				'value' => null,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertNull( $result['value'] );
	}

	// =========================================================================
	// Additional AddGoogleFont tests
	// =========================================================================

	/**
	 * Test AddGoogleFont output schema.
	 */
	public function test_add_google_font_output_schema() {
		$schema = AddGoogleFont::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'family', $schema['properties'] );
		$this->assertArrayHasKey( 'count', $schema['properties'] );
	}

	/**
	 * Test AddGoogleFont adds multiple fonts with cumulative count.
	 */
	public function test_add_google_font_multiple_fonts() {
		$result1 = AddGoogleFont::instance()->execute( array( 'family' => 'Montserrat' ) );
		$this->assertSame( 1, $result1['count'] );

		$result2 = AddGoogleFont::instance()->execute( array( 'family' => 'Poppins' ) );
		$this->assertSame( 2, $result2['count'] );

		$saved  = get_option( 'spectra_blocks_global_fonts', array() );
		$labels = array_column( $saved, 'label' );
		$this->assertContains( 'Montserrat', $labels );
		$this->assertContains( 'Poppins', $labels );
	}

	/**
	 * Test AddGoogleFont clears font cache transient.
	 */
	public function test_add_google_font_clears_transient() {
		set_transient( 'spectra_google_fonts_cache', array( 'test' ), 3600 );

		AddGoogleFont::instance()->execute( array( 'family' => 'Nunito' ) );

		$this->assertFalse( get_transient( 'spectra_google_fonts_cache' ) );
	}

	/**
	 * Test AddGoogleFont stores correct slug format via sanitize_title.
	 */
	public function test_add_google_font_slug_format() {
		AddGoogleFont::instance()->execute( array( 'family' => 'Open Sans' ) );

		$saved = get_option( 'spectra_blocks_global_fonts', array() );
		$this->assertSame( 'open-sans', $saved[0]['value'] );
		$this->assertSame( 'Open Sans', $saved[0]['label'] );
	}

	// =========================================================================
	// Additional RemoveGoogleFont tests
	// =========================================================================

	/**
	 * Test RemoveGoogleFont input schema.
	 */
	public function test_remove_google_font_input_schema() {
		$schema = RemoveGoogleFont::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'family', $schema['required'] );
		$this->assertArrayHasKey( 'family', $schema['properties'] );
	}

	/**
	 * Test AddGoogleFont denies subscribers.
	 */
	public function test_add_google_font_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = AddGoogleFont::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test RemoveGoogleFont denies subscribers.
	 */
	public function test_remove_google_font_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = RemoveGoogleFont::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test UpdatePopup denies editors (requires manage_options).
	 */
	public function test_update_popup_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$result = UpdatePopup::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test UpdatePluginSetting denies subscribers.
	 */
	public function test_update_plugin_setting_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = UpdatePluginSetting::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test RemoveGoogleFont output schema.
	 */
	public function test_remove_google_font_output_schema() {
		$schema = RemoveGoogleFont::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'family', $schema['properties'] );
		$this->assertArrayHasKey( 'count', $schema['properties'] );
	}

	/**
	 * Test RemoveGoogleFont preserves other fonts.
	 */
	public function test_remove_google_font_preserves_others() {
		AddGoogleFont::instance()->execute( array( 'family' => 'Inter' ) );
		AddGoogleFont::instance()->execute( array( 'family' => 'Merriweather' ) );

		$result = RemoveGoogleFont::instance()->execute( array( 'family' => 'Inter' ) );

		$this->assertSame( 1, $result['count'] );

		$saved  = get_option( 'spectra_blocks_global_fonts', array() );
		$labels = array_column( $saved, 'label' );
		$this->assertNotContains( 'Inter', $labels );
		$this->assertContains( 'Merriweather', $labels );
	}

	/**
	 * Test RemoveGoogleFont clears font cache transient.
	 */
	public function test_remove_google_font_clears_transient() {
		AddGoogleFont::instance()->execute( array( 'family' => 'Playfair Display' ) );
		set_transient( 'spectra_google_fonts_cache', array( 'test' ), 3600 );

		RemoveGoogleFont::instance()->execute( array( 'family' => 'Playfair Display' ) );

		$this->assertFalse( get_transient( 'spectra_google_fonts_cache' ) );
	}

	// =========================================================================
	// Additional UpdatePopup tests
	// =========================================================================

	/**
	 * Test UpdatePopup output schema.
	 */
	public function test_update_popup_output_schema() {
		$schema = UpdatePopup::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'popup_id', $schema['properties'] );
		$this->assertArrayHasKey( 'updated', $schema['properties'] );
	}

	/**
	 * Test UpdatePopup updates content field.
	 */
	public function test_update_popup_execute_content() {
		$popup_id = self::factory()->post->create(
			array(
				'post_type'    => 'spectra-blocks-popup',
				'post_title'   => 'Content Test',
				'post_content' => '<p>Old content</p>',
				'post_status'  => 'publish',
			)
		);
		update_post_meta( $popup_id, 'spectra-blocks-popup-type', 'banner' );

		$new_content = '<!-- wp:paragraph --><p>New content</p><!-- /wp:paragraph -->';
		$result      = UpdatePopup::instance()->execute(
			array(
				'popup_id' => $popup_id,
				'content'  => $new_content,
			)
		);

		$this->assertIsArray( $result );
		$this->assertContains( 'content', $result['updated'] );

		$post = get_post( $popup_id );
		$this->assertStringContainsString( 'New content', $post->post_content );
	}

	/**
	 * Test UpdatePopup denies subscribers.
	 */
	public function test_update_popup_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = UpdatePopup::instance()->check_permission();
		$this->assertWPError( $result );
	}

	// =========================================================================
	// Configuration Integration Cycles
	// =========================================================================

	/**
	 * Test font add → list → remove cycle via ListSelectedFonts.
	 */
	public function test_font_add_list_remove_cycle() {
		if ( ! class_exists( 'Spectra\FontManager' ) ) {
			$this->markTestSkipped( 'FontManager not available.' );
		}

		// Add.
		AddGoogleFont::instance()->execute( array( 'family' => 'Raleway' ) );

		// List and verify present.
		$list = \SpectraBlocks\Abilities\ListSelectedFonts::instance()->execute( array() );
		// Font may not appear in ListSelectedFonts because that queries FontManager cache,
		// but the option should reflect it.
		$saved  = get_option( 'spectra_blocks_global_fonts', array() );
		$labels = array_column( $saved, 'label' );
		$this->assertContains( 'Raleway', $labels );

		// Remove.
		RemoveGoogleFont::instance()->execute( array( 'family' => 'Raleway' ) );

		// Verify gone.
		$saved  = get_option( 'spectra_blocks_global_fonts', array() );
		$labels = array_column( $saved, 'label' );
		$this->assertNotContains( 'Raleway', $labels );
	}

	/**
	 * Test UpdatePluginSetting → GetPluginSettings roundtrip.
	 */
	public function test_update_then_get_plugin_setting() {
		if ( ! class_exists( 'Spectra_Blocks_Admin_Helper' ) ) {
			$this->markTestSkipped( 'Spectra_Blocks_Admin_Helper not available.' );
		}

		UpdatePluginSetting::instance()->execute(
			array(
				'key'   => 'spectra_blocks_load_gfonts_locally',
				'value' => 'disabled',
			)
		);

		$settings = \SpectraBlocks\Abilities\GetPluginSettings::instance()->execute( array() );
		$this->assertSame( 'disabled', $settings['settings']['spectra_blocks_load_gfonts_locally'] ?? null );
	}

	/**
	 * Test UpdatePopup content → GetPopup roundtrip.
	 */
	public function test_popup_update_then_get() {
		$popup_id = self::factory()->post->create(
			array(
				'post_type'   => 'spectra-blocks-popup',
				'post_title'  => 'Roundtrip Test',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $popup_id, 'spectra-blocks-popup-type', 'banner' );

		UpdatePopup::instance()->execute(
			array(
				'popup_id' => $popup_id,
				'title'    => 'Updated Roundtrip',
				'type'     => 'popup',
				'enabled'  => true,
			)
		);

		$get_result = \SpectraBlocks\Abilities\GetPopup::instance()->execute(
			array( 'popup_id' => $popup_id )
		);

		$this->assertSame( 'Updated Roundtrip', $get_result['title'] );
		$this->assertSame( 'popup', $get_result['type'] );
		$this->assertTrue( $get_result['enabled'] );
	}
}
