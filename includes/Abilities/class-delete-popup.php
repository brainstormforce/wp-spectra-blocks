<?php
/**
 * Delete Popup ability.
 *
 * Deletes a Spectra popup by ID (moves to trash by default).
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * DeletePopup ability class.
 *
 * @since 1.0.0
 */
class DeletePopup extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/delete-popup';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Delete Spectra Popup', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Deletes a Spectra popup by ID. By default moves to trash; set force_delete to true for permanent deletion.', 'spectra-blocks' );
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
			'destructive' => true,
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
			'required'   => array( 'popup_id' ),
			'properties' => array(
				'popup_id'     => array(
					'type'        => 'integer',
					'description' => __( 'The popup post ID to delete.', 'spectra-blocks' ),
				),
				'force_delete' => array(
					'type'        => 'boolean',
					'description' => __( 'True to permanently delete instead of trashing. Default false.', 'spectra-blocks' ),
					'default'     => false,
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
				'deleted'  => array( 'type' => 'boolean' ),
				'trashed'  => array( 'type' => 'boolean' ),
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
			__( 'You do not have permission to delete popups.', 'spectra-blocks' ),
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
		if ( empty( $params['popup_id'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The popup_id parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$popup_id     = absint( $params['popup_id'] );
		$force_delete = ! empty( $params['force_delete'] );
		$post         = get_post( $popup_id );

		if ( ! $post || 'spectra-blocks-popup' !== $post->post_type ) {
			return new WP_Error(
				'spectra_blocks_not_found',
				__( 'The specified popup does not exist.', 'spectra-blocks' ),
				array( 'status' => 404 )
			);
		}

		$title = $post->post_title;

		if ( $force_delete ) {
			$result = wp_delete_post( $popup_id, true );
		} else {
			$result = wp_trash_post( $popup_id );
		}

		if ( ! $result ) {
			return new WP_Error(
				'spectra_blocks_delete_failed',
				__( 'Failed to delete the popup.', 'spectra-blocks' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'popup_id' => $popup_id,
			'deleted'  => $force_delete,
			'trashed'  => ! $force_delete,
			'title'    => $title,
		);
	}
}
