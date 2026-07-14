<?php
/**
 * Get Post Content ability.
 *
 * Parses and returns all blocks from a post with their attributes, inner blocks, and positions.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * GetPostContent ability class.
 *
 * @since 1.0.0
 */
class GetPostContent extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/get-post-content';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Get Post Content', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Parses and returns all blocks from a post with their attributes, inner blocks, and positions. Optionally filter by block type.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-discovery';
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
			'readonly'    => true,
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
			'required'   => array( 'post_id' ),
			'properties' => array(
				'post_id'    => array(
					'type'        => 'integer',
					'description' => __( 'The post ID to read blocks from.', 'spectra-blocks' ),
				),
				'block_name' => array(
					'type'        => 'string',
					'description' => __( 'Optional block type to filter by (e.g. "spectra/container").', 'spectra-blocks' ),
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
				'blocks' => array(
					'type'        => 'array',
					'description' => __( 'Array of parsed blocks.', 'spectra-blocks' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'index'       => array( 'type' => 'integer' ),
							'name'        => array( 'type' => 'string' ),
							'attributes'  => array( 'type' => 'object' ),
							'innerBlocks' => array( 'type' => 'array' ),
							'innerHTML'   => array( 'type' => 'string' ),
						),
					),
				),
				'count'  => array(
					'type'        => 'integer',
					'description' => __( 'Total number of blocks returned.', 'spectra-blocks' ),
				),
			),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array|WP_Error Parsed blocks or error.
	 */
	public function execute( array $params ) {
		if ( empty( $params['post_id'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The post_id parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$parsed = $this->get_parsed_blocks( absint( $params['post_id'] ) );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$blocks = $parsed['blocks'];

		// Filter by block name if provided.
		if ( ! empty( $params['block_name'] ) ) {
			$filter_name = sanitize_text_field( $params['block_name'] );
			$blocks      = array_values(
				array_filter(
					$blocks,
					fn( $block ) => $block['blockName'] === $filter_name
				)
			);
		}

		// Format output.
		$output = array();
		foreach ( $blocks as $block ) {
			$output[] = array(
				'index'       => $block['index'],
				'name'        => $block['blockName'],
				'attributes'  => $block['attrs'],
				'innerBlocks' => $block['innerBlocks'],
				'innerHTML'   => $block['innerHTML'],
			);
		}

		return array(
			'blocks' => $output,
			'count'  => count( $output ),
		);
	}
}
