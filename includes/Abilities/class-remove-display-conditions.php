<?php
/**
 * Remove Display Conditions ability.
 *
 * Removes display condition settings from a block in a post.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * RemoveDisplayConditions ability class.
 *
 * @since 1.0.0
 */
class RemoveDisplayConditions extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/remove-display-conditions';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Remove Display Conditions from Block', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Removes all display conditions from a block in a post, restoring it to always-visible.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-extensions';
	}

	/**
	 * Get ability annotations for REST discovery.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
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
	 * @return array<string, mixed>
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
					'description' => __( 'The 0-based block index.', 'spectra-blocks' ),
				),
			),
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success'    => array( 'type' => 'boolean' ),
				'block_name' => array( 'type' => 'string' ),
				'removed'    => array(
					'type'        => 'boolean',
					'description' => __( 'Whether a displayConditions attribute was present and removed.', 'spectra-blocks' ),
				),
			),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $params Input parameters.
	 * @return array<string, mixed>|WP_Error Result or error.
	 */
	public function execute( array $params ) {
		if ( empty( $params['post_id'] ) || ! isset( $params['block_index'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The post_id and block_index parameters are required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$post_id     = absint( is_scalar( $params['post_id'] ) ? (int) $params['post_id'] : 0 );
		$block_index = is_scalar( $params['block_index'] ) ? (int) $params['block_index'] : 0;

		$post = $this->get_validated_post( $post_id );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$all_blocks = parse_blocks( $post->post_content );
		$raw_index  = $this->find_block_raw_index( $all_blocks, $block_index );

		if ( is_wp_error( $raw_index ) ) {
			return $raw_index;
		}

		$block_name     = $all_blocks[ $raw_index ]['blockName'];
		$attrs          = $all_blocks[ $raw_index ]['attrs'] ?? array();
		$had_conditions = array_key_exists( 'displayConditions', $attrs );

		unset( $attrs['displayConditions'] );

		$all_blocks[ $raw_index ]['attrs'] = $attrs;

		$result = $this->update_post_blocks( $post_id, $all_blocks );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'    => true,
			'block_name' => $block_name,
			'removed'    => $had_conditions,
		);
	}
}
