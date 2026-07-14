<?php
/**
 * Generate Page Layout ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * GeneratePageLayout ability class.
 *
 * Accepts a structured array of blocks (with optional nesting) and serializes
 * them into valid Gutenberg block markup that can be inserted into a post.
 *
 * @since 1.0.0
 */
class GeneratePageLayout extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/generate-page-layout';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Generate Page Layout', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Generates a full page layout from a structured array of block definitions. Supports nested blocks (e.g. containers with inner blocks) and serializes them into valid Gutenberg block markup.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-layout';
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
			'required'   => array( 'blocks' ),
			'properties' => array_merge(
				array(
					'blocks' => array(
						'type'        => 'array',
						'description' => __( 'Array of block definitions to serialize. Each block has a blockName, optional attributes, optional innerHTML, and optional innerBlocks array for nesting.', 'spectra-blocks' ),
						'items'       => array(
							'type'       => 'object',
							'required'   => array( 'blockName' ),
							'properties' => array(
								'blockName'   => array(
									'type'        => 'string',
									'description' => __( 'The block name (e.g. "spectra/container", "core/paragraph").', 'spectra-blocks' ),
								),
								'attrs'       => array(
									'type'        => 'object',
									'description' => __( 'Block attributes as key-value pairs.', 'spectra-blocks' ),
								),
								'innerHTML'   => array(
									'type'        => 'string',
									'description' => __( 'Static HTML content for the block (used by blocks like core/paragraph).', 'spectra-blocks' ),
								),
								'innerBlocks' => array(
									'type'        => 'array',
									'description' => __( 'Nested child blocks (same structure, recursive).', 'spectra-blocks' ),
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
		if ( empty( $params['blocks'] ) || ! is_array( $params['blocks'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The blocks parameter is required and must be an array.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$markup = $this->serialize_blocks( $params['blocks'] );

		if ( is_wp_error( $markup ) ) {
			return $markup;
		}

		return $this->maybe_insert_and_return( $markup, $params );
	}

	/**
	 * Recursively serialize an array of block definitions into block markup.
	 *
	 * @since 1.0.0
	 *
	 * @param array $blocks Array of block definitions.
	 * @param int   $depth  Current recursion depth (safety limit).
	 * @return string|WP_Error Serialized block markup or error.
	 */
	private function serialize_blocks( array $blocks, int $depth = 0 ) {
		// Prevent infinite recursion.
		if ( $depth > 10 ) {
			return new WP_Error(
				'spectra_blocks_depth_exceeded',
				__( 'Maximum block nesting depth exceeded (10 levels).', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$output = '';

		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			$serialized = $this->serialize_single_block( $block, $depth );

			if ( is_wp_error( $serialized ) ) {
				return $serialized;
			}

			$output .= $serialized . "\n\n";
		}

		return rtrim( $output );
	}

	/**
	 * Serialize a single block definition into block comment markup.
	 *
	 * @since 1.0.0
	 *
	 * @param array $block Block definition.
	 * @param int   $depth Current recursion depth.
	 * @return string|WP_Error Serialized block markup or error.
	 */
	private function serialize_single_block( array $block, int $depth ) {
		$block_name = sanitize_text_field( $block['blockName'] );
		$attrs      = ! empty( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$inner_html = isset( $block['innerHTML'] ) ? wp_kses_post( $block['innerHTML'] ) : '';
		$has_inner  = ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] );

		$attrs_json = ! empty( $attrs ) ? ' ' . wp_json_encode( $attrs ) : '';

		// Self-closing block (no inner content and no inner blocks).
		if ( ! $has_inner && empty( $inner_html ) ) {
			return '<!-- wp:' . $block_name . $attrs_json . ' /-->';
		}

		// Block with inner content.
		$inner_content = $inner_html;

		if ( $has_inner ) {
			$nested = $this->serialize_blocks( $block['innerBlocks'], $depth + 1 );

			if ( is_wp_error( $nested ) ) {
				return $nested;
			}

			$inner_content = $inner_html . ( ! empty( $inner_html ) ? "\n" : '' ) . $nested;
		}

		return '<!-- wp:' . $block_name . $attrs_json . ' -->'
			. "\n" . $inner_content . "\n"
			. '<!-- /wp:' . $block_name . ' -->';
	}
}
