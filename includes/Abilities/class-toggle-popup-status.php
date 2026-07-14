<?php
/**
 * Toggle Popup Status ability.
 *
 * Enables or disables a Spectra popup by ID.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * TogglePopupStatus ability class.
 *
 * @since 1.0.0
 */
class TogglePopupStatus extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/toggle-popup-status';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Toggle Popup Status', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Enables or disables a Spectra popup by its ID. An enabled popup will display on the frontend according to its display rules.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-content';
	}

	/**
	 * Get ability annotations for REST discovery.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_annotations(): array {
		return array(
			'readonly'    => false,
			'destructive' => false,
			'idempotent'  => true,
		);
	}

	/**
	 * Get the input schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'popup_id', 'enabled' ),
			'properties' => array(
				'popup_id' => array(
					'type'        => 'integer',
					'description' => __( 'The popup post ID.', 'spectra-blocks' ),
				),
				'enabled'  => array(
					'type'        => 'boolean',
					'description' => __( 'True to enable the popup, false to disable it.', 'spectra-blocks' ),
				),
			),
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'popup_id' => array( 'type' => 'integer' ),
				'enabled'  => array( 'type' => 'boolean' ),
				'title'    => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Check if the current user has permission.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'spectra_blocks_rest_forbidden',
			__( 'You do not have permission to manage popups.', 'spectra-blocks' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array|WP_Error Result or error.
	 */
	public function execute( array $params ) {
		if ( empty( $params['popup_id'] ) || ! isset( $params['enabled'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'Both popup_id and enabled parameters are required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$popup_id = absint( $params['popup_id'] );
		$enabled  = (bool) $params['enabled'];
		$post     = get_post( $popup_id );

		if ( ! $post || 'spectra-blocks-popup' !== $post->post_type ) {
			return new WP_Error(
				'spectra_blocks_not_found',
				__( 'The specified popup does not exist.', 'spectra-blocks' ),
				array( 'status' => 404 )
			);
		}

		update_post_meta( $popup_id, 'spectra-blocks-popup-enabled', $enabled );

		return array(
			'popup_id' => $popup_id,
			'enabled'  => $enabled,
			'title'    => $post->post_title,
		);
	}
}
