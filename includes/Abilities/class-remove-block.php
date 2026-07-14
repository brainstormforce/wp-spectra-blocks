<?php
/**
 * Remove Block ability.
 *
 * Removes a block from a post by index.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * RemoveBlock ability class.
 *
 * @since 1.0.0
 */
class RemoveBlock extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/remove-block';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Remove Block', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Removes a block from a post by its index. Use spectra-blocks/get-post-content to find block indices.', 'spectra-blocks' );
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
			'destructive' => true,
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
			'required'   => array( 'post_id', 'block_index' ),
			'properties' => array(
				'post_id'     => array(
					'type'        => 'integer',
					'description' => __( 'The post ID containing the block.', 'spectra-blocks' ),
				),
				'block_index' => array(
					'type'        => 'integer',
					'description' => __( 'The 0-based block index to remove.', 'spectra-blocks' ),
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
				'success'          => array( 'type' => 'boolean' ),
				'removed_block'    => array( 'type' => 'string' ),
				'blocks_remaining' => array( 'type' => 'integer' ),
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
		if ( empty( $params['post_id'] ) || ! isset( $params['block_index'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The post_id and block_index parameters are required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$post_id     = absint( $params['post_id'] );
		$block_index = intval( $params['block_index'] );

		$post = $this->get_validated_post( $post_id );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$all_blocks = parse_blocks( $post->post_content );
		$raw_index  = $this->find_block_raw_index( $all_blocks, $block_index );

		if ( is_wp_error( $raw_index ) ) {
			return $raw_index;
		}

		$removed_name = $all_blocks[ $raw_index ]['blockName'];
		array_splice( $all_blocks, $raw_index, 1 );

		$result = $this->update_post_blocks( $post_id, $all_blocks );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Count remaining meaningful blocks.
		$remaining = count(
			array_filter( $all_blocks, fn( $b ) => ! empty( $b['blockName'] ) )
		);

		return array(
			'success'          => true,
			'removed_block'    => $removed_name,
			'blocks_remaining' => $remaining,
		);
	}
}
