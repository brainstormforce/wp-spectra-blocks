<?php
/**
 * Create List ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateList ability class.
 *
 * @since 1.0.0
 */
class CreateList extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-list';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create List', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra list block with customizable items. Supports ordered and unordered list types.', 'spectra-blocks' );
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
			'required'   => array( 'items' ),
			'properties' => array_merge(
				array(
					'items'    => array(
						'type'        => 'array',
						'description' => __( 'Array of list item objects.', 'spectra-blocks' ),
						'items'       => array(
							'type'       => 'object',
							'required'   => array( 'text' ),
							'properties' => array(
								'text' => array(
									'type'        => 'string',
									'description' => __( 'The list item text content.', 'spectra-blocks' ),
								),
							),
						),
					),
					'listType' => array(
						'type'        => 'string',
						'description' => __( 'List type: unordered or ordered.', 'spectra-blocks' ),
						'enum'        => array( 'unordered', 'ordered' ),
						'default'     => 'unordered',
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
		if ( empty( $params['items'] ) || ! is_array( $params['items'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The items parameter is required and must be an array.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$list_attrs = array();

		if ( ! empty( $params['listType'] ) ) {
			$type = sanitize_text_field( $params['listType'] );
			if ( in_array( $type, array( 'unordered', 'ordered' ), true ) ) {
				$list_attrs['listType'] = $type;
			}
		}

		$children = '';
		$index    = 0;

		foreach ( $params['items'] as $item ) {
			if ( empty( $item['text'] ) ) {
				continue;
			}

			$item_attrs = array(
				'index' => $index,
			);

			$item_text = sanitize_text_field( $item['text'] );

			// Each list-child-item wraps a content block for the text.
			$content_block = '<!-- wp:spectra/content {"text":"' . esc_attr( $item_text ) . '"} /-->';

			$item_attrs_json = ' ' . wp_json_encode( $item_attrs );
			$children       .= '<!-- wp:spectra/list-child-item' . $item_attrs_json . ' -->'
				. "\n" . $content_block . "\n"
				. '<!-- /wp:spectra/list-child-item -->' . "\n";

			++$index;
		}

		if ( empty( $children ) ) {
			return new WP_Error(
				'spectra_blocks_invalid_param',
				__( 'At least one list item with text is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$list_attrs_json = ! empty( $list_attrs ) ? ' ' . wp_json_encode( $list_attrs ) : '';
		$block_markup    = '<!-- wp:spectra/list' . $list_attrs_json . ' -->'
			. "\n" . $children
			. '<!-- /wp:spectra/list -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
