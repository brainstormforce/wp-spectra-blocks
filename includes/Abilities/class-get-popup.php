<?php
/**
 * Get Popup ability.
 *
 * Returns full details for a single Spectra popup.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * GetPopup ability class.
 *
 * @since 1.0.0
 */
class GetPopup extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/get-popup';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Get Spectra Popup Details', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Returns full details for a single Spectra popup including title, content, type, enabled status, and repetition setting.', 'spectra-blocks' );
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
			'readonly'    => true,
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
			'required'   => array( 'popup_id' ),
			'properties' => array(
				'popup_id' => array(
					'type'        => 'integer',
					'description' => __( 'The popup post ID.', 'spectra-blocks' ),
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
				'id'         => array( 'type' => 'integer' ),
				'title'      => array( 'type' => 'string' ),
				'content'    => array(
					'type'        => 'string',
					'description' => __( 'The popup block content.', 'spectra-blocks' ),
				),
				'type'       => array( 'type' => 'string' ),
				'enabled'    => array( 'type' => 'boolean' ),
				'repetition' => array( 'type' => 'number' ),
				'status'     => array( 'type' => 'string' ),
				'date'       => array( 'type' => 'string' ),
				'modified'   => array( 'type' => 'string' ),
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
	 * @return array|WP_Error Popup details or error.
	 */
	public function execute( array $params ) {
		if ( empty( $params['popup_id'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The popup_id parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$popup_id = absint( $params['popup_id'] );
		$post     = get_post( $popup_id );

		if ( ! $post || 'spectra-blocks-popup' !== $post->post_type ) {
			return new WP_Error(
				'spectra_blocks_not_found',
				__( 'The specified popup does not exist.', 'spectra-blocks' ),
				array( 'status' => 404 )
			);
		}

		return array(
			'id'         => $post->ID,
			'title'      => $post->post_title,
			'content'    => $post->post_content,
			'type'       => get_post_meta( $post->ID, 'spectra-blocks-popup-type', true ) ? get_post_meta( $post->ID, 'spectra-blocks-popup-type', true ) : 'unset',
			'enabled'    => (bool) get_post_meta( $post->ID, 'spectra-blocks-popup-enabled', true ),
			'repetition' => (float) get_post_meta( $post->ID, 'spectra-blocks-popup-repetition', true ),
			'status'     => $post->post_status,
			'date'       => $post->post_date,
			'modified'   => $post->post_modified,
		);
	}
}
