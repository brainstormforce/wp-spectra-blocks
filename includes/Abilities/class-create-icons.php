<?php
/**
 * Create Icons ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateIcons ability class.
 *
 * @since 1.0.0
 */
class CreateIcons extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-icons';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Icons', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra icons group with one or more icon children. Each icon can have a custom SVG name, link, and accessibility label.', 'spectra-blocks' );
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
			'required'   => array( 'icons' ),
			'properties' => array_merge(
				array(
					'icons' => array(
						'type'        => 'array',
						'description' => __( 'Array of icon configurations.', 'spectra-blocks' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'icon'               => array(
									'type'        => 'string',
									'description' => __( 'Icon name/identifier from the Spectra icon library.', 'spectra-blocks' ),
								),
								'linkURL'            => array(
									'type'        => 'string',
									'description' => __( 'Optional link URL for the icon.', 'spectra-blocks' ),
								),
								'accessibilityLabel' => array(
									'type'        => 'string',
									'description' => __( 'Accessible label text for the icon.', 'spectra-blocks' ),
								),
							),
						),
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
		if ( empty( $params['icons'] ) || ! is_array( $params['icons'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The icons parameter is required and must be an array.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$children = '';

		foreach ( $params['icons'] as $icon ) {
			$icon_attrs = array();

			if ( ! empty( $icon['icon'] ) ) {
				$icon_attrs['icon'] = sanitize_text_field( $icon['icon'] );
			}

			if ( ! empty( $icon['linkURL'] ) ) {
				$icon_attrs['linkURL'] = esc_url_raw( $icon['linkURL'] );
			}

			if ( ! empty( $icon['accessibilityLabel'] ) ) {
				$icon_attrs['accessibilityMode']  = 'linked';
				$icon_attrs['accessibilityLabel'] = sanitize_text_field( $icon['accessibilityLabel'] );
			}

			$icon_attrs_json = ! empty( $icon_attrs ) ? ' ' . wp_json_encode( $icon_attrs ) : '';
			$children       .= '<!-- wp:spectra/icon' . $icon_attrs_json . ' /-->' . "\n";
		}

		if ( empty( $children ) ) {
			return new WP_Error(
				'spectra_blocks_invalid_param',
				__( 'At least one icon is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$block_markup = '<!-- wp:spectra/icons -->'
			. "\n" . $children
			. '<!-- /wp:spectra/icons -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
