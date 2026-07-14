<?php
/**
 * Add Google Font ability.
 *
 * Adds a Google Font to the selected fonts list.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * AddGoogleFont ability class.
 *
 * @since 1.0.0
 */
class AddGoogleFont extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/add-google-font';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Add Google Font', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Adds a Google Font to the Spectra selected fonts list. Use spectra-blocks/list-available-google-fonts to discover valid font families.', 'spectra-blocks' );
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
			'required'   => array( 'family' ),
			'properties' => array(
				'family' => array(
					'type'        => 'string',
					'description' => __( 'The Google Font family name (e.g. "Roboto", "Open Sans").', 'spectra-blocks' ),
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
					'description' => __( 'Total number of selected fonts after adding.', 'spectra-blocks' ),
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

		// Check if font is already selected.
		foreach ( $selected as $font ) {
			if ( isset( $font['label'] ) && $font['label'] === $family ) {
				return new WP_Error(
					'spectra_blocks_already_exists',
					/* translators: %s: font family name */
					sprintf( __( 'The font "%s" is already in the selected fonts list.', 'spectra-blocks' ), $family ),
					array( 'status' => 400 )
				);
			}
		}

		$slug = sanitize_title( $family );

		$selected[] = array(
			'value' => $slug,
			'label' => $family,
		);

		update_option( 'spectra_blocks_global_fonts', $selected );

		// Clear font cache so the new font is picked up.
		delete_transient( 'spectra_google_fonts_cache' );

		return array(
			'success' => true,
			'family'  => $family,
			'count'   => count( $selected ),
		);
	}
}
