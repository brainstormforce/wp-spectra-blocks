<?php
/**
 * Move Block ability.
 *
 * Moves a block to a different position within a post.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * MoveBlock ability class.
 *
 * @since 1.0.0
 */
class MoveBlock extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/move-block';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Move Block', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Moves a block to a different position within a post. Use spectra-blocks/get-post-content to find block indices.', 'spectra-blocks' );
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
			'required'   => array( 'post_id', 'from_index', 'to_index' ),
			'properties' => array(
				'post_id'    => array(
					'type'        => 'integer',
					'description' => __( 'The post ID containing the block.', 'spectra-blocks' ),
				),
				'from_index' => array(
					'type'        => 'integer',
					'description' => __( 'The current 0-based block index to move.', 'spectra-blocks' ),
				),
				'to_index'   => array(
					'type'        => 'integer',
					'description' => __( 'The target 0-based block index to move to.', 'spectra-blocks' ),
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
				'success'    => array( 'type' => 'boolean' ),
				'block_name' => array( 'type' => 'string' ),
				'from_index' => array( 'type' => 'integer' ),
				'to_index'   => array( 'type' => 'integer' ),
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
		if ( empty( $params['post_id'] ) || ! isset( $params['from_index'] ) || ! isset( $params['to_index'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The post_id, from_index, and to_index parameters are required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$post_id    = absint( $params['post_id'] );
		$from_index = intval( $params['from_index'] );
		$to_index   = intval( $params['to_index'] );

		if ( $from_index === $to_index ) {
			return new WP_Error(
				'spectra_blocks_invalid_param',
				__( 'The from_index and to_index must be different.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$post = $this->get_validated_post( $post_id );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$all_blocks = parse_blocks( $post->post_content );
		$from_raw   = $this->find_block_raw_index( $all_blocks, $from_index );

		if ( is_wp_error( $from_raw ) ) {
			return $from_raw;
		}

		$to_raw = $this->find_block_raw_index( $all_blocks, $to_index );

		if ( is_wp_error( $to_raw ) ) {
			return $to_raw;
		}

		$block_name = $all_blocks[ $from_raw ]['blockName'];

		// Extract the block.
		$block = $all_blocks[ $from_raw ];
		array_splice( $all_blocks, $from_raw, 1 );

		// Insert at new position.
		array_splice( $all_blocks, $to_raw, 0, array( $block ) );

		$result = $this->update_post_blocks( $post_id, $all_blocks );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'    => true,
			'block_name' => $block_name,
			'from_index' => $from_index,
			'to_index'   => $to_index,
		);
	}
}
