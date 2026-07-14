<?php
/**
 * Create Google Map ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateGoogleMap ability class.
 *
 * @since 1.0.0
 */
class CreateGoogleMap extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-google-map';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Google Map', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra Google Map embed block with a specified address, zoom level, and dimensions.', 'spectra-blocks' );
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
			'required'   => array( 'address' ),
			'properties' => array_merge(
				array(
					'address'  => array(
						'type'        => 'string',
						'description' => __( 'The address or location to display on the map.', 'spectra-blocks' ),
					),
					'zoom'     => array(
						'type'        => 'integer',
						'description' => __( 'Map zoom level (1-22).', 'spectra-blocks' ),
						'default'     => 12,
					),
					'height'   => array(
						'type'        => 'integer',
						'description' => __( 'Map height in pixels.', 'spectra-blocks' ),
						'default'     => 300,
					),
					'language' => array(
						'type'        => 'string',
						'description' => __( 'Map language code (e.g. "en", "es", "fr").', 'spectra-blocks' ),
					),
				),
				$this->get_post_insertion_schema()
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
		return $this->get_block_markup_output_schema();
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array|WP_Error
	 */
	public function execute( array $params ) {
		if ( empty( $params['address'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The address parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$attrs = array(
			'address' => sanitize_text_field( $params['address'] ),
		);

		if ( isset( $params['zoom'] ) ) {
			$zoom          = absint( $params['zoom'] );
			$attrs['zoom'] = min( max( $zoom, 1 ), 22 );
		}

		if ( isset( $params['height'] ) ) {
			$attrs['height'] = absint( $params['height'] );
		}

		if ( ! empty( $params['language'] ) ) {
			$attrs['language'] = sanitize_text_field( $params['language'] );
		}

		$attrs_json   = ' ' . wp_json_encode( $attrs );
		$block_markup = '<!-- wp:spectra/google-map' . $attrs_json . ' /-->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
