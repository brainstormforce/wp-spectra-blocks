<?php
/**
 * Remove Google Font ability.
 *
 * Removes a Google Font from the selected fonts list.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * RemoveGoogleFont ability class.
 *
 * @since 1.0.0
 */
class RemoveGoogleFont extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/remove-google-font';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Remove Google Font', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Removes a Google Font from the Spectra selected fonts list.', 'spectra-blocks' );
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
			'required'   => array( 'family' ),
			'properties' => array(
				'family' => array(
					'type'        => 'string',
					'description' => __( 'The Google Font family name to remove (e.g. "Roboto").', 'spectra-blocks' ),
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
				'success' => array( 'type' => 'boolean' ),
				'family'  => array( 'type' => 'string' ),
				'count'   => array(
					'type'        => 'integer',
					'description' => __( 'Total number of selected fonts after removal.', 'spectra-blocks' ),
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
			__( 'You do not have permission to manage fonts.', 'spectra-blocks' ),
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
		if ( empty( $params['family'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The family parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$family = sanitize_text_field( $params['family'] );

		$selected = get_option( 'spectra_blocks_global_fonts', array() );

		if ( ! is_array( $selected ) ) {
			$selected = array();
		}

		$found    = false;
		$filtered = array();

		foreach ( $selected as $font ) {
			if ( isset( $font['label'] ) && $font['label'] === $family ) {
				$found = true;
				continue;
			}
			$filtered[] = $font;
		}

		if ( ! $found ) {
			return new WP_Error(
				'spectra_blocks_not_found',
				/* translators: %s: font family name */
				sprintf( __( 'The font "%s" is not in the selected fonts list.', 'spectra-blocks' ), $family ),
				array( 'status' => 404 )
			);
		}

		update_option( 'spectra_blocks_global_fonts', $filtered );

		// Clear font cache.
		delete_transient( 'spectra_google_fonts_cache' );

		return array(
			'success' => true,
			'family'  => $family,
			'count'   => count( $filtered ),
		);
	}
}
