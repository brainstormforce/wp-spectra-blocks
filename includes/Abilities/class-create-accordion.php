<?php
/**
 * Create Accordion ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateAccordion ability class.
 *
 * @since 1.0.0
 */
class CreateAccordion extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-accordion';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Accordion', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra accordion/FAQ block with collapsible question-answer items. Each item has a header and a details section.', 'spectra-blocks' );
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
					'items'           => array(
						'type'        => 'array',
						'description' => __( 'Array of accordion items with question and answer.', 'spectra-blocks' ),
						'items'       => array(
							'type'       => 'object',
							'required'   => array( 'question', 'answer' ),
							'properties' => array(
								'question' => array(
									'type'        => 'string',
									'description' => __( 'The accordion header/question text.', 'spectra-blocks' ),
								),
								'answer'   => array(
									'type'        => 'string',
									'description' => __( 'The accordion body/answer content.', 'spectra-blocks' ),
								),
							),
						),
					),
					'activeAccordion' => array(
						'type'        => 'integer',
						'description' => __( 'Index of the initially open accordion item (0-based). Use -1 for all closed.', 'spectra-blocks' ),
						'default'     => 0,
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

		$accordion_attrs = array();
		$active_index    = isset( $params['activeAccordion'] ) ? intval( $params['activeAccordion'] ) : 0;

		if ( -1 !== $active_index ) {
			$accordion_attrs['activeAccordion'] = $active_index;
		}

		$children = '';
		$index    = 0;

		foreach ( $params['items'] as $item ) {
			if ( empty( $item['question'] ) || empty( $item['answer'] ) ) {
				continue;
			}

			$question = sanitize_text_field( $item['question'] );
			$answer   = wp_kses_post( $item['answer'] );

			$item_attrs = array();
			if ( $index === $active_index ) {
				$item_attrs['openByDefault'] = true;
			}

			// Build the accordion item structure:
			// accordion-child-item > accordion-child-header > accordion-child-header-content
			// accordion-child-item > accordion-child-details > paragraph.
			$header_content = '<!-- wp:spectra/accordion-child-header-content '
				. wp_json_encode( array( 'text' => $question ) ) . ' /-->';

			$header_icon = '<!-- wp:spectra/accordion-child-header-icon /-->';

			$header = '<!-- wp:spectra/accordion-child-header -->'
				. "\n" . $header_content . "\n" . $header_icon . "\n"
				. '<!-- /wp:spectra/accordion-child-header -->';

			$details_content = '<!-- wp:paragraph -->'
				. "\n" . '<p>' . $answer . '</p>' . "\n"
				. '<!-- /wp:paragraph -->';

			$details = '<!-- wp:spectra/accordion-child-details -->'
				. "\n" . $details_content . "\n"
				. '<!-- /wp:spectra/accordion-child-details -->';

			$item_attrs_json = ! empty( $item_attrs ) ? ' ' . wp_json_encode( $item_attrs ) : '';
			$children       .= '<!-- wp:spectra/accordion-child-item' . $item_attrs_json . ' -->'
				. "\n" . $header . "\n" . $details . "\n"
				. '<!-- /wp:spectra/accordion-child-item -->' . "\n";

			++$index;
		}

		if ( empty( $children ) ) {
			return new WP_Error(
				'spectra_blocks_invalid_param',
				__( 'At least one accordion item with question and answer is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$accordion_attrs_json = ! empty( $accordion_attrs ) ? ' ' . wp_json_encode( $accordion_attrs ) : '';
		$block_markup         = '<!-- wp:spectra/accordion' . $accordion_attrs_json . ' -->'
			. "\n" . $children
			. '<!-- /wp:spectra/accordion -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
