<?php
/**
 * Create Buttons ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateButtons ability class.
 *
 * @since 1.0.0
 */
class CreateButtons extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-buttons';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Buttons', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra buttons group with one or more button children. Each button can have custom text, link URL, and link target.', 'spectra-blocks' );
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
			'required'   => array( 'buttons' ),
			'properties' => array_merge(
				array(
					'buttons' => array(
						'type'        => 'array',
						'description' => __( 'Array of button configurations.', 'spectra-blocks' ),
						'items'       => array(
							'type'       => 'object',
							'required'   => array( 'text' ),
							'properties' => array(
								'text'       => array(
									'type'        => 'string',
									'description' => __( 'Button label text.', 'spectra-blocks' ),
								),
								'linkURL'    => array(
									'type'        => 'string',
									'description' => __( 'Button link URL.', 'spectra-blocks' ),
								),
								'linkTarget' => array(
									'type'        => 'string',
									'description' => __( 'Link target: _self or _blank.', 'spectra-blocks' ),
									'enum'        => array( '_self', '_blank' ),
									'default'     => '_self',
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
		if ( empty( $params['buttons'] ) || ! is_array( $params['buttons'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The buttons parameter is required and must be an array.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$children = '';

		foreach ( $params['buttons'] as $button ) {
			if ( empty( $button['text'] ) ) {
				continue;
			}

			$btn_attrs = array(
				'text' => sanitize_text_field( $button['text'] ),
			);

			if ( ! empty( $button['linkURL'] ) ) {
				$btn_attrs['linkURL'] = esc_url_raw( $button['linkURL'] );
			}

			if ( ! empty( $button['linkTarget'] ) ) {
				$target                  = sanitize_text_field( $button['linkTarget'] );
				$btn_attrs['linkTarget'] = in_array( $target, array( '_self', '_blank' ), true ) ? $target : '_self';
			}

			$btn_attrs_json = ' ' . wp_json_encode( $btn_attrs );
			$children      .= '<!-- wp:spectra/button' . $btn_attrs_json . ' /-->' . "\n";
		}

		if ( empty( $children ) ) {
			return new WP_Error(
				'spectra_blocks_invalid_param',
				__( 'At least one button with text is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$block_markup = '<!-- wp:spectra/buttons -->'
			. "\n" . $children
			. '<!-- /wp:spectra/buttons -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
