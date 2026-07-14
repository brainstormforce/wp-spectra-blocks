<?php
/**
 * Create Separator ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * CreateSeparator ability class.
 *
 * @since 1.0.0
 */
class CreateSeparator extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-separator';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Separator', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra separator/divider block with customizable style, width, and color.', 'spectra-blocks' );
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
			'properties' => array_merge(
				array(
					'separatorStyle' => array(
						'type'        => 'string',
						'description' => __( 'Separator style: solid, dashed, dotted, or double.', 'spectra-blocks' ),
						'enum'        => array( 'solid', 'dashed', 'dotted', 'double' ),
						'default'     => 'solid',
					),
					'separatorWidth' => array(
						'type'        => 'integer',
						'description' => __( 'Separator width as a percentage (1-100).', 'spectra-blocks' ),
						'default'     => 100,
					),
					'separatorColor' => array(
						'type'        => 'string',
						'description' => __( 'Separator color as a CSS color value.', 'spectra-blocks' ),
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
	 * @return array|\WP_Error
	 */
	public function execute( array $params ) {
		$attrs = array();

		if ( ! empty( $params['separatorStyle'] ) ) {
			$style                   = sanitize_text_field( $params['separatorStyle'] );
			$allowed_styles          = array( 'solid', 'dashed', 'dotted', 'double' );
			$attrs['separatorStyle'] = in_array( $style, $allowed_styles, true ) ? $style : 'solid';
		}

		if ( isset( $params['separatorWidth'] ) ) {
			$attrs['separatorWidth'] = absint( $params['separatorWidth'] );
		}

		if ( ! empty( $params['separatorColor'] ) ) {
			$attrs['separatorColor'] = sanitize_text_field( $params['separatorColor'] );
		}

		$attrs_json   = ! empty( $attrs ) ? ' ' . wp_json_encode( $attrs ) : '';
		$block_markup = '<!-- wp:spectra/separator' . $attrs_json . ' /-->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
