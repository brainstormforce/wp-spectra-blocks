<?php
/**
 * Common Settings Data Query.
 *
 * @package spectra-blocks
 */

namespace SpectraBlocksAdmin\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Request;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;
use SpectraBlocksAdmin\Api\Api_Base;
use SpectraBlocksAdmin\Inc\Admin_Helper;
use SpectraBlocksAdmin\Inc\Admin_Learn;

/**
 * Class Admin_Query.
 */
class Common_Settings extends Api_Base {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '/admin/commonsettings/';

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class object.
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init Hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {

		$namespace = $this->get_api_namespace();

		register_rest_route(
			$namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_common_settings' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Register learn chapters route.
		register_rest_route(
			$namespace,
			$this->rest_base . 'get-learn-chapters',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_learn_chapters' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		// Register save learn progress route.
		register_rest_route(
			$namespace,
			$this->rest_base . 'update-learn-progress',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_learn_progress' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'chapterId' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'stepId'    => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'completed' => array(
							'required'          => true,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * Get learn chapters data.
	 *
	 * @param WP_REST_Request $_request Full details about the request (unused; required by REST API callback signature).
	 * @return array Learn chapters data.
	 *
	 * @since 3.0.0
	 */
	public function get_learn_chapters( $_request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Use Admin_Learn helper to get chapters with progress.
		return Admin_Learn::get_learn_chapters();
	}

	/**
	 * Save learn progress.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @since 3.0.0
	 */
	public function save_learn_progress( $request ) {
		$chapter_id = $request->get_param( 'chapterId' );
		$step_id    = $request->get_param( 'stepId' );
		$completed  = $request->get_param( 'completed' );

		// Get current progress.
		$user_id        = get_current_user_id();
		$saved_progress = get_user_meta( $user_id, 'spectra_learn_progress', true );
		if ( ! is_array( $saved_progress ) ) {
			$saved_progress = array();
		}

		// Initialize chapter array if it doesn't exist.
		if ( ! isset( $saved_progress[ $chapter_id ] ) || ! is_array( $saved_progress[ $chapter_id ] ) ) {
			$saved_progress[ $chapter_id ] = array();
		}

		// Update progress for this step in nested format.
		$saved_progress[ $chapter_id ][ $step_id ] = (bool) $completed;

		// Save to user meta.
		update_user_meta( $user_id, 'spectra_learn_progress', $saved_progress );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Progress saved successfully.', 'spectra-blocks' ),
			),
			200
		);
	}

	/**
	 * Get common settings.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 */
	public function get_common_settings( $request ) {

		$options = Admin_Helper::get_options();

		return $options;
	}

	/**
	 * Check whether a given request has permission to read notes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'spectra_blocks_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'spectra-blocks' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}
}
