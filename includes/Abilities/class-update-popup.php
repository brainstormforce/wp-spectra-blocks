<?php
/**
 * Update Popup ability.
 *
 * Updates an existing popup's content, settings, or metadata.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * UpdatePopup ability class.
 *
 * @since 1.0.0
 */
class UpdatePopup extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/update-popup';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Update Spectra Popup', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Updates an existing Spectra popup\'s title, content, type, enabled status, or repetition setting.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-configuration';
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
			'required'   => array( 'popup_id' ),
			'properties' => array(
				'popup_id'   => array(
					'type'        => 'integer',
					'description' => __( 'The popup post ID to update.', 'spectra-blocks' ),
				),
				'title'      => array(
					'type'        => 'string',
					'description' => __( 'New popup title.', 'spectra-blocks' ),
				),
				'content'    => array(
					'type'        => 'string',
					'description' => __( 'New popup block content (serialized block markup).', 'spectra-blocks' ),
				),
				'type'       => array(
					'type'        => 'string',
					'description' => __( 'Popup type.', 'spectra-blocks' ),
				),
				'enabled'    => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the popup is enabled.', 'spectra-blocks' ),
				),
				'repetition' => array(
					'type'        => 'number',
					'description' => __( 'Popup repetition setting.', 'spectra-blocks' ),
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
				'success'  => array( 'type' => 'boolean' ),
				'popup_id' => array( 'type' => 'integer' ),
				'updated'  => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'List of fields that were updated.', 'spectra-blocks' ),
				),
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

		$updated  = array();
		$post_arr = array( 'ID' => $popup_id );

		if ( isset( $params['title'] ) ) {
			$post_arr['post_title'] = sanitize_text_field( $params['title'] );
			$updated[]              = 'title';
		}

		if ( isset( $params['content'] ) ) {
			$post_arr['post_content'] = wp_slash( $params['content'] );
			$updated[]                = 'content';
		}

		// Update post if title or content changed.
		if ( count( $post_arr ) > 1 ) {
			$result = wp_update_post( $post_arr, true );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Update meta fields.
		if ( isset( $params['type'] ) ) {
			update_post_meta( $popup_id, 'spectra-blocks-popup-type', sanitize_text_field( $params['type'] ) );
			$updated[] = 'type';
		}

		if ( isset( $params['enabled'] ) ) {
			update_post_meta( $popup_id, 'spectra-blocks-popup-enabled', (bool) $params['enabled'] ? '1' : '' );
			$updated[] = 'enabled';
		}

		if ( isset( $params['repetition'] ) ) {
			update_post_meta( $popup_id, 'spectra-blocks-popup-repetition', floatval( $params['repetition'] ) );
			$updated[] = 'repetition';
		}

		if ( empty( $updated ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'At least one field to update must be provided (title, content, type, enabled, or repetition).', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		return array(
			'success'  => true,
			'popup_id' => $popup_id,
			'updated'  => $updated,
		);
	}
}
