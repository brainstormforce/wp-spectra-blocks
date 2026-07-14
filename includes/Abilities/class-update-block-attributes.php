<?php
/**
 * Update Block Attributes ability.
 *
 * Modifies attributes of an existing block in a post.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * UpdateBlockAttributes ability class.
 *
 * @since 1.0.0
 */
class UpdateBlockAttributes extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/update-block-attributes';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Update Block Attributes', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Modifies attributes of an existing block in a post, identified by its block index. Use spectra-blocks/get-post-content to discover block indices.', 'spectra-blocks' );
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
			'required'   => array( 'post_id', 'block_index', 'attributes' ),
			'properties' => array(
				'post_id'     => array(
					'type'        => 'integer',
					'description' => __( 'The post ID containing the block.', 'spectra-blocks' ),
				),
				'block_index' => array(
					'type'        => 'integer',
					'description' => __( 'The 0-based block index (skipping empty blocks). Use get-post-content to find indices.', 'spectra-blocks' ),
				),
				'attributes'  => array(
					'type'        => 'object',
					'description' => __( 'Key-value pairs of attributes to merge into the block.', 'spectra-blocks' ),
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
				'success'            => array( 'type' => 'boolean' ),
				'block_name'         => array( 'type' => 'string' ),
				'updated_attributes' => array( 'type' => 'object' ),
			),
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
		if ( empty( $params['post_id'] ) || ! isset( $params['block_index'] ) || empty( $params['attributes'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The post_id, block_index, and attributes parameters are required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$post_id     = absint( $params['post_id'] );
		$block_index = intval( $params['block_index'] );
		$new_attrs   = (array) $params['attributes'];

		$post = $this->get_validated_post( $post_id );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$all_blocks = parse_blocks( $post->post_content );
		$raw_index  = $this->find_block_raw_index( $all_blocks, $block_index );

		if ( is_wp_error( $raw_index ) ) {
			return $raw_index;
		}

		$block_name                        = $all_blocks[ $raw_index ]['blockName'];
		$all_blocks[ $raw_index ]['attrs'] = array_merge(
			$all_blocks[ $raw_index ]['attrs'] ?? array(),
			$new_attrs
		);

		$result = $this->update_post_blocks( $post_id, $all_blocks );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'            => true,
			'block_name'         => $block_name,
			'updated_attributes' => $all_blocks[ $raw_index ]['attrs'],
		);
	}
}
