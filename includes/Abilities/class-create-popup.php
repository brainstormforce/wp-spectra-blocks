<?php
/**
 * Create Popup ability.
 *
 * Creates a new Spectra popup with title, variant type, and display settings.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreatePopup ability class.
 *
 * @since 1.0.0
 */
class CreatePopup extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-popup';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Spectra Popup', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a new Spectra popup with a title, variant type (popup or banner), enabled status, and repetition setting. Returns the new popup ID and details.', 'spectra-blocks' );
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
	 * Get the input schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'title' ),
			'properties' => array(
				'title'      => array(
					'type'        => 'string',
					'description' => __( 'The popup title.', 'spectra-blocks' ),
				),
				'type'       => array(
					'type'        => 'string',
					'description' => __( 'The popup variant type.', 'spectra-blocks' ),
					'enum'        => array( 'popup', 'banner' ),
					'default'     => 'banner',
				),
				'enabled'    => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the popup is enabled on the frontend. Default false.', 'spectra-blocks' ),
					'default'     => false,
				),
				'repetition' => array(
					'type'        => 'integer',
					'description' => __( 'How many times the popup shows per visitor. Use -1 for infinite. Default 1.', 'spectra-blocks' ),
					'default'     => 1,
				),
				'content'    => array(
					'type'        => 'string',
					'description' => __( 'Optional block markup content for the popup body.', 'spectra-blocks' ),
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
				'popup_id'   => array( 'type' => 'integer' ),
				'title'      => array( 'type' => 'string' ),
				'type'       => array( 'type' => 'string' ),
				'enabled'    => array( 'type' => 'boolean' ),
				'repetition' => array( 'type' => 'integer' ),
				'edit_url'   => array(
					'type'        => 'string',
					'description' => __( 'URL to edit the popup in the WordPress admin.', 'spectra-blocks' ),
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
			__( 'You do not have permission to create popups.', 'spectra-blocks' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array|WP_Error Created popup data or error.
	 */
	public function execute( array $params ) {
		if ( empty( $params['title'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The title parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$title      = sanitize_text_field( $params['title'] );
		$type       = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'banner';
		$enabled    = ! empty( $params['enabled'] );
		$repetition = isset( $params['repetition'] ) ? intval( $params['repetition'] ) : 1;
		$content    = isset( $params['content'] ) ? wp_kses_post( $params['content'] ) : '';

		// Validate type.
		$allowed_types = array( 'popup', 'banner' );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			$type = 'banner';
		}

		// Wrap content in popup-builder block if raw content provided.
		if ( ! empty( $content ) && strpos( $content, 'wp:spectra/popup-builder' ) === false ) {
			$popup_attrs = wp_json_encode( array( 'variantType' => $type ) );
			$content     = "<!-- wp:spectra/popup-builder {$popup_attrs} -->\n{$content}\n<!-- /wp:spectra/popup-builder -->";
		}

		$post_data = array(
			'post_title'   => $title,
			'post_type'    => 'spectra-blocks-popup',
			'post_status'  => 'publish',
			'post_content' => $content,
		);

		$popup_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $popup_id ) ) {
			return $popup_id;
		}

		// Set popup meta.
		update_post_meta( $popup_id, 'spectra-blocks-popup-type', $type );
		update_post_meta( $popup_id, 'spectra-blocks-popup-enabled', $enabled );
		update_post_meta( $popup_id, 'spectra-blocks-popup-repetition', $repetition );

		return array(
			'popup_id'   => $popup_id,
			'title'      => $title,
			'type'       => $type,
			'enabled'    => $enabled,
			'repetition' => $repetition,
			'edit_url'   => get_edit_post_link( $popup_id, 'raw' ),
		);
	}
}
