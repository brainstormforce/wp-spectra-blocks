<?php
/**
 * Create Content ability.
 *
 * Creates a Spectra content/text block with customizable text, tag, and alignment.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateContent ability class.
 *
 * @since 1.0.0
 */
class CreateContent extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-content';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Text Content Block', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra text/content block with customizable text, HTML tag, and alignment. Supports paragraphs, headings, and other text elements.', 'spectra-blocks' );
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
			'required'   => array( 'text' ),
			'properties' => array_merge(
				array(
					'text'      => array(
						'type'        => 'string',
						'description' => __( 'The text content. Supports basic HTML (bold, italic, links).', 'spectra-blocks' ),
					),
					'tagName'   => array(
						'type'        => 'string',
						'description' => __( 'HTML tag for the text element.', 'spectra-blocks' ),
						'enum'        => array( 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'div' ),
						'default'     => 'p',
					),
					'textAlign' => array(
						'type'        => 'string',
						'description' => __( 'Text alignment.', 'spectra-blocks' ),
						'enum'        => array( 'left', 'center', 'right', 'justify' ),
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
	 * @return array|WP_Error Block markup or error.
	 */
	public function execute( array $params ) {
		if ( empty( $params['text'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The text parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$text     = wp_kses_post( $params['text'] );
		$tag_name = isset( $params['tagName'] ) ? sanitize_text_field( $params['tagName'] ) : 'p';

		$allowed_tags = array( 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'div' );
		if ( ! in_array( $tag_name, $allowed_tags, true ) ) {
			$tag_name = 'p';
		}

		$attrs = array(
			'tagName' => $tag_name,
			'text'    => $text,
		);

		if ( ! empty( $params['textAlign'] ) ) {
			$align          = sanitize_text_field( $params['textAlign'] );
			$allowed_aligns = array( 'left', 'center', 'right', 'justify' );
			if ( in_array( $align, $allowed_aligns, true ) ) {
				$attrs['style'] = array( 'typography' => array( 'textAlign' => $align ) );
			}
		}

		$attrs_json   = wp_json_encode( $attrs );
		$block_markup = "<!-- wp:spectra/content {$attrs_json} -->\n<{$tag_name} class=\"wp-block-spectra-content\">{$text}</{$tag_name}>\n<!-- /wp:spectra/content -->";

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
