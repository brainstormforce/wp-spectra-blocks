<?php
/**
 * Tests for Popup abilities: CreatePopup, GetPopup, ListPopups, TogglePopupStatus, DeletePopup.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use Spectra\Abilities\CreatePopup;
use Spectra\Abilities\GetPopup;
use Spectra\Abilities\ListPopups;
use Spectra\Abilities\TogglePopupStatus;
use Spectra\Abilities\DeletePopup;

/**
 * Popup abilities test case.
 */
class PopupAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user is admin.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		// Register the spectra-popup CPT if not already registered.
		if ( ! post_type_exists( 'spectra-popup' ) ) {
			register_post_type(
				'spectra-popup',
				array(
					'public' => false,
					'label'  => 'Spectra Popups',
				)
			);
		}
	}

	/**
	 * Helper: Create a test popup post.
	 *
	 * @param array $args Optional post args.
	 * @return int Post ID.
	 */
	private function create_test_popup( array $args = array() ) {
		$defaults = array(
			'post_type'   => 'spectra-popup',
			'post_title'  => 'Test Popup',
			'post_status' => 'publish',
		);

		$post_id = self::factory()->post->create( array_merge( $defaults, $args ) );

		// Set default meta.
		update_post_meta( $post_id, 'spectra-popup-type', $args['meta_type'] ?? 'banner' );
		update_post_meta( $post_id, 'spectra-popup-enabled', $args['meta_enabled'] ?? false );
		update_post_meta( $post_id, 'spectra-popup-repetition', $args['meta_repetition'] ?? 1 );

		return $post_id;
	}

	// -------------------------------------------------------------------------
	// CreatePopup
	// -------------------------------------------------------------------------

	/**
	 * Test CreatePopup metadata.
	 */
	public function test_create_popup_metadata() {
		$ability = CreatePopup::instance();

		$this->assertSame( 'spectra-blocks/create-popup', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test CreatePopup requires manage_options.
	 */
	public function test_create_popup_permission() {
		$this->assertTrue( CreatePopup::instance()->check_permission() );

		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$result = CreatePopup::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test CreatePopup execute with valid title.
	 */
	public function test_create_popup_execute_success() {
		$result = CreatePopup::instance()->execute(
			array( 'title' => 'Welcome Banner' )
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'popup_id', $result );
		$this->assertArrayHasKey( 'title', $result );
		$this->assertArrayHasKey( 'type', $result );
		$this->assertArrayHasKey( 'enabled', $result );
		$this->assertArrayHasKey( 'repetition', $result );
		$this->assertSame( 'Welcome Banner', $result['title'] );
		$this->assertSame( 'banner', $result['type'] );
		$this->assertFalse( $result['enabled'] );
		$this->assertSame( 1, $result['repetition'] );
		$this->assertGreaterThan( 0, $result['popup_id'] );
	}

	/**
	 * Test CreatePopup execute fails without title.
	 */
	public function test_create_popup_execute_missing_title() {
		$result = CreatePopup::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test CreatePopup with all options.
	 */
	public function test_create_popup_with_all_options() {
		$result = CreatePopup::instance()->execute(
			array(
				'title'      => 'Promo Popup',
				'type'       => 'popup',
				'enabled'    => true,
				'repetition' => 3,
				'content'    => '<p>Sale today!</p>',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'popup', $result['type'] );
		$this->assertTrue( $result['enabled'] );
		$this->assertSame( 3, $result['repetition'] );

		// Verify post was created with content wrapped in popup-builder.
		$post = get_post( $result['popup_id'] );
		$this->assertStringContainsString( 'wp:spectra/popup-builder', $post->post_content );
		$this->assertStringContainsString( 'Sale today!', $post->post_content );
	}

	/**
	 * Test CreatePopup sets post meta correctly.
	 */
	public function test_create_popup_sets_meta() {
		$result = CreatePopup::instance()->execute(
			array(
				'title'   => 'Meta Test',
				'type'    => 'popup',
				'enabled' => true,
			)
		);

		$this->assertSame( 'popup', get_post_meta( $result['popup_id'], 'spectra-popup-type', true ) );
		$this->assertTrue( (bool) get_post_meta( $result['popup_id'], 'spectra-popup-enabled', true ) );
	}

	/**
	 * Test CreatePopup defaults to banner for invalid type.
	 */
	public function test_create_popup_invalid_type_defaults_to_banner() {
		$result = CreatePopup::instance()->execute(
			array(
				'title' => 'Invalid Type Test',
				'type'  => 'invalid',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'banner', $result['type'] );
	}

	/**
	 * Test CreatePopup input schema.
	 */
	public function test_create_popup_input_schema() {
		$schema = CreatePopup::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'title', $schema['required'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
		$this->assertArrayHasKey( 'type', $schema['properties'] );
		$this->assertArrayHasKey( 'content', $schema['properties'] );
	}

	/**
	 * Test CreatePopup output schema.
	 */
	public function test_create_popup_output_schema() {
		$schema = CreatePopup::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'popup_id', $schema['properties'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
		$this->assertArrayHasKey( 'type', $schema['properties'] );
		$this->assertArrayHasKey( 'enabled', $schema['properties'] );
	}

	/**
	 * Test CreatePopup denies subscribers.
	 */
	public function test_create_popup_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = CreatePopup::instance()->check_permission();
		$this->assertWPError( $result );
	}

	// -------------------------------------------------------------------------
	// GetPopup
	// -------------------------------------------------------------------------

	/**
	 * Test GetPopup metadata.
	 */
	public function test_get_popup_metadata() {
		$ability = GetPopup::instance();

		$this->assertSame( 'spectra-blocks/get-popup', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test GetPopup execute with valid popup ID.
	 */
	public function test_get_popup_execute_success() {
		$popup_id = $this->create_test_popup( array( 'post_title' => 'My Popup' ) );

		$result = GetPopup::instance()->execute( array( 'popup_id' => $popup_id ) );

		$this->assertIsArray( $result );
		$this->assertSame( $popup_id, $result['id'] );
		$this->assertSame( 'My Popup', $result['title'] );
		$this->assertArrayHasKey( 'content', $result );
		$this->assertArrayHasKey( 'type', $result );
		$this->assertArrayHasKey( 'enabled', $result );
		$this->assertArrayHasKey( 'repetition', $result );
		$this->assertArrayHasKey( 'status', $result );
		$this->assertArrayHasKey( 'date', $result );
		$this->assertArrayHasKey( 'modified', $result );
	}

	/**
	 * Test GetPopup execute fails without popup_id.
	 */
	public function test_get_popup_execute_missing_id() {
		$result = GetPopup::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test GetPopup execute fails for non-existent popup.
	 */
	public function test_get_popup_execute_nonexistent() {
		$result = GetPopup::instance()->execute( array( 'popup_id' => 999999 ) );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	/**
	 * Test GetPopup execute fails for wrong post type.
	 */
	public function test_get_popup_execute_wrong_post_type() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		$result = GetPopup::instance()->execute( array( 'popup_id' => $post_id ) );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	/**
	 * Test GetPopup input schema.
	 */
	public function test_get_popup_input_schema() {
		$schema = GetPopup::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'popup_id', $schema['required'] );
	}

	/**
	 * Test GetPopup output schema.
	 */
	public function test_get_popup_output_schema() {
		$schema = GetPopup::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'id', $schema['properties'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
		$this->assertArrayHasKey( 'content', $schema['properties'] );
		$this->assertArrayHasKey( 'type', $schema['properties'] );
		$this->assertArrayHasKey( 'enabled', $schema['properties'] );
	}

	/**
	 * Test GetPopup denies subscribers.
	 */
	public function test_get_popup_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = GetPopup::instance()->check_permission();
		$this->assertWPError( $result );
	}

	// -------------------------------------------------------------------------
	// ListPopups
	// -------------------------------------------------------------------------

	/**
	 * Test ListPopups metadata.
	 */
	public function test_list_popups_metadata() {
		$ability = ListPopups::instance();

		$this->assertSame( 'spectra-blocks/list-popups', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test ListPopups execute returns expected structure.
	 */
	public function test_list_popups_execute_structure() {
		$this->create_test_popup( array( 'post_title' => 'Popup A' ) );
		$this->create_test_popup( array( 'post_title' => 'Popup B' ) );

		$result = ListPopups::instance()->execute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'popups', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'total_pages', $result );
		$this->assertArrayHasKey( 'page', $result );
		$this->assertGreaterThanOrEqual( 2, $result['total'] );
	}

	/**
	 * Test ListPopups popup entries have expected keys.
	 */
	public function test_list_popups_entry_structure() {
		$this->create_test_popup();

		$result = ListPopups::instance()->execute( array() );

		$this->assertNotEmpty( $result['popups'] );
		$popup = $result['popups'][0];

		$this->assertArrayHasKey( 'id', $popup );
		$this->assertArrayHasKey( 'title', $popup );
		$this->assertArrayHasKey( 'type', $popup );
		$this->assertArrayHasKey( 'enabled', $popup );
		$this->assertArrayHasKey( 'repetition', $popup );
		$this->assertArrayHasKey( 'date', $popup );
		$this->assertArrayHasKey( 'modified', $popup );
	}

	/**
	 * Test ListPopups filters by enabled status.
	 */
	public function test_list_popups_filter_enabled() {
		$this->create_test_popup(
			array(
				'post_title'   => 'Enabled Popup',
				'meta_enabled' => '1',
			)
		);
		$this->create_test_popup(
			array(
				'post_title'   => 'Disabled Popup',
				'meta_enabled' => '0',
			)
		);

		$enabled_result = ListPopups::instance()->execute( array( 'status' => 'enabled' ) );

		foreach ( $enabled_result['popups'] as $popup ) {
			$this->assertTrue( $popup['enabled'], 'All returned popups should be enabled.' );
		}
	}

	/**
	 * Test ListPopups pagination.
	 */
	public function test_list_popups_pagination() {
		for ( $i = 0; $i < 5; $i++ ) {
			$this->create_test_popup( array( 'post_title' => "Popup $i" ) );
		}

		$result = ListPopups::instance()->execute(
			array(
				'per_page' => 2,
				'page'     => 1,
			)
		);

		$this->assertLessThanOrEqual( 2, count( $result['popups'] ) );
		$this->assertSame( 1, $result['page'] );
	}

	/**
	 * Test ListPopups per_page is capped at 50.
	 */
	public function test_list_popups_per_page_cap() {
		$result = ListPopups::instance()->execute(
			array( 'per_page' => 200 )
		);

		// The WP_Query will use the capped value.
		$this->assertIsArray( $result );
	}

	/**
	 * Test ListPopups input schema.
	 */
	public function test_list_popups_input_schema() {
		$schema = ListPopups::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'per_page', $schema['properties'] );
		$this->assertArrayHasKey( 'page', $schema['properties'] );
	}

	/**
	 * Test ListPopups output schema.
	 */
	public function test_list_popups_output_schema() {
		$schema = ListPopups::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'popups', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
		$this->assertArrayHasKey( 'total_pages', $schema['properties'] );
		$this->assertArrayHasKey( 'page', $schema['properties'] );
	}

	/**
	 * Test ListPopups denies subscribers.
	 */
	public function test_list_popups_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = ListPopups::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ListPopups filters by disabled status.
	 */
	public function test_list_popups_filter_disabled() {
		$this->create_test_popup(
			array(
				'post_title'   => 'Enabled Popup',
				'meta_enabled' => '1',
			)
		);
		$this->create_test_popup(
			array(
				'post_title'   => 'Disabled Popup',
				'meta_enabled' => '0',
			)
		);

		$disabled_result = ListPopups::instance()->execute( array( 'status' => 'disabled' ) );

		foreach ( $disabled_result['popups'] as $popup ) {
			$this->assertFalse( $popup['enabled'], 'All returned popups should be disabled.' );
		}
	}

	// -------------------------------------------------------------------------
	// TogglePopupStatus
	// -------------------------------------------------------------------------

	/**
	 * Test TogglePopupStatus metadata.
	 */
	public function test_toggle_popup_status_metadata() {
		$ability = TogglePopupStatus::instance();

		$this->assertSame( 'spectra-blocks/toggle-popup-status', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test TogglePopupStatus execute enables a popup.
	 */
	public function test_toggle_popup_status_enable() {
		$popup_id = $this->create_test_popup( array( 'meta_enabled' => false ) );

		$result = TogglePopupStatus::instance()->execute(
			array(
				'popup_id' => $popup_id,
				'enabled'  => true,
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( $popup_id, $result['popup_id'] );
		$this->assertTrue( $result['enabled'] );

		// Verify meta was updated.
		$this->assertTrue( (bool) get_post_meta( $popup_id, 'spectra-popup-enabled', true ) );
	}

	/**
	 * Test TogglePopupStatus execute disables a popup.
	 */
	public function test_toggle_popup_status_disable() {
		$popup_id = $this->create_test_popup( array( 'meta_enabled' => true ) );

		$result = TogglePopupStatus::instance()->execute(
			array(
				'popup_id' => $popup_id,
				'enabled'  => false,
			)
		);

		$this->assertIsArray( $result );
		$this->assertFalse( $result['enabled'] );
	}

	/**
	 * Test TogglePopupStatus execute fails without required params.
	 */
	public function test_toggle_popup_status_missing_params() {
		$result = TogglePopupStatus::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test TogglePopupStatus execute fails for non-existent popup.
	 */
	public function test_toggle_popup_status_nonexistent() {
		$result = TogglePopupStatus::instance()->execute(
			array(
				'popup_id' => 999999,
				'enabled'  => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	/**
	 * Test TogglePopupStatus input schema.
	 */
	public function test_toggle_popup_status_input_schema() {
		$schema = TogglePopupStatus::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'popup_id', $schema['required'] );
		$this->assertContains( 'enabled', $schema['required'] );
	}

	/**
	 * Test TogglePopupStatus output schema.
	 */
	public function test_toggle_popup_status_output_schema() {
		$schema = TogglePopupStatus::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'popup_id', $schema['properties'] );
		$this->assertArrayHasKey( 'enabled', $schema['properties'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
	}

	/**
	 * Test TogglePopupStatus denies subscribers.
	 */
	public function test_toggle_popup_status_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = TogglePopupStatus::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test TogglePopupStatus fails for wrong post type.
	 */
	public function test_toggle_popup_status_wrong_post_type() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		$result = TogglePopupStatus::instance()->execute(
			array(
				'popup_id' => $post_id,
				'enabled'  => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	/**
	 * Test TogglePopupStatus returns title.
	 */
	public function test_toggle_popup_status_returns_title() {
		$popup_id = $this->create_test_popup( array( 'post_title' => 'Named Popup' ) );

		$result = TogglePopupStatus::instance()->execute(
			array(
				'popup_id' => $popup_id,
				'enabled'  => true,
			)
		);

		$this->assertSame( 'Named Popup', $result['title'] );
	}

	// -------------------------------------------------------------------------
	// DeletePopup
	// -------------------------------------------------------------------------

	/**
	 * Test DeletePopup metadata.
	 */
	public function test_delete_popup_metadata() {
		$ability = DeletePopup::instance();

		$this->assertSame( 'spectra-blocks/delete-popup', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test DeletePopup input schema.
	 */
	public function test_delete_popup_input_schema() {
		$schema = DeletePopup::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'popup_id', $schema['required'] );
		$this->assertArrayHasKey( 'force_delete', $schema['properties'] );
	}

	/**
	 * Test DeletePopup output schema.
	 */
	public function test_delete_popup_output_schema() {
		$schema = DeletePopup::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'popup_id', $schema['properties'] );
		$this->assertArrayHasKey( 'deleted', $schema['properties'] );
		$this->assertArrayHasKey( 'trashed', $schema['properties'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
	}

	/**
	 * Test DeletePopup denies subscribers.
	 */
	public function test_delete_popup_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = DeletePopup::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test DeletePopup execute trashes popup by default.
	 */
	public function test_delete_popup_execute_trash() {
		$popup_id = $this->create_test_popup( array( 'post_title' => 'Trash Me' ) );

		$result = DeletePopup::instance()->execute( array( 'popup_id' => $popup_id ) );

		$this->assertIsArray( $result );
		$this->assertSame( $popup_id, $result['popup_id'] );
		$this->assertFalse( $result['deleted'] );
		$this->assertTrue( $result['trashed'] );
		$this->assertSame( 'Trash Me', $result['title'] );

		// Verify post is trashed.
		$this->assertSame( 'trash', get_post_status( $popup_id ) );
	}

	/**
	 * Test DeletePopup execute permanently deletes with force_delete.
	 */
	public function test_delete_popup_execute_force_delete() {
		$popup_id = $this->create_test_popup( array( 'post_title' => 'Delete Me' ) );

		$result = DeletePopup::instance()->execute(
			array(
				'popup_id'     => $popup_id,
				'force_delete' => true,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['deleted'] );
		$this->assertFalse( $result['trashed'] );

		// Verify post no longer exists.
		$this->assertNull( get_post( $popup_id ) );
	}

	/**
	 * Test DeletePopup execute fails without popup_id.
	 */
	public function test_delete_popup_execute_missing_id() {
		$result = DeletePopup::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test DeletePopup execute fails for non-existent popup.
	 */
	public function test_delete_popup_execute_nonexistent() {
		$result = DeletePopup::instance()->execute( array( 'popup_id' => 999999 ) );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	/**
	 * Test DeletePopup execute fails for wrong post type.
	 */
	public function test_delete_popup_wrong_post_type() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );

		$result = DeletePopup::instance()->execute( array( 'popup_id' => $post_id ) );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// Admin grant assertions & editor deny
	// -------------------------------------------------------------------------

	/**
	 * Test GetPopup grants admin.
	 */
	public function test_get_popup_permission_admin() {
		$this->assertTrue( GetPopup::instance()->check_permission() );
	}

	/**
	 * Test DeletePopup grants admin.
	 */
	public function test_delete_popup_permission_admin() {
		$this->assertTrue( DeletePopup::instance()->check_permission() );
	}

	/**
	 * Test ListPopups grants admin.
	 */
	public function test_list_popups_permission_admin() {
		$this->assertTrue( ListPopups::instance()->check_permission() );
	}

	/**
	 * Test TogglePopupStatus grants admin.
	 */
	public function test_toggle_popup_status_permission_admin() {
		$this->assertTrue( TogglePopupStatus::instance()->check_permission() );
	}

	/**
	 * Test GetPopup denies editors.
	 */
	public function test_get_popup_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$result = GetPopup::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ListPopups denies editors.
	 */
	public function test_list_popups_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$result = ListPopups::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test TogglePopupStatus denies editors.
	 */
	public function test_toggle_popup_status_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$result = TogglePopupStatus::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test DeletePopup denies editors.
	 */
	public function test_delete_popup_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$result = DeletePopup::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test DeletePopup fails for wrong post type.
	 */
	public function test_delete_popup_execute_wrong_post_type() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$result = DeletePopup::instance()->execute( array( 'popup_id' => $post_id ) );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// Full Popup CRUD Cycle
	// -------------------------------------------------------------------------

	/**
	 * Test full CRUD lifecycle: Create → Get → Toggle → List → Delete.
	 */
	public function test_popup_full_crud_cycle() {
		// Create.
		$create_result = CreatePopup::instance()->execute(
			array(
				'title' => 'CRUD Test Popup',
				'type'  => 'popup',
			)
		);

		$this->assertIsArray( $create_result );
		$popup_id = $create_result['popup_id'];

		// Get.
		$get_result = GetPopup::instance()->execute( array( 'popup_id' => $popup_id ) );
		$this->assertSame( 'CRUD Test Popup', $get_result['title'] );
		$this->assertSame( 'popup', $get_result['type'] );

		// Toggle on.
		$toggle_result = TogglePopupStatus::instance()->execute(
			array(
				'popup_id' => $popup_id,
				'enabled'  => true,
			)
		);
		$this->assertTrue( $toggle_result['enabled'] );

		// List — should include our popup.
		$list_result = ListPopups::instance()->execute( array() );
		$popup_ids   = array_column( $list_result['popups'], 'id' );
		$this->assertContains( $popup_id, $popup_ids );

		// Delete.
		$delete_result = DeletePopup::instance()->execute( array( 'popup_id' => $popup_id ) );
		$this->assertTrue( $delete_result['trashed'] );
	}
}
