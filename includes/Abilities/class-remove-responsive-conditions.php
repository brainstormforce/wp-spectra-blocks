<?php
/**
 * Remove Responsive Conditions ability.
 *
 * Removes responsive visibility conditions from a block.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * RemoveResponsiveConditions ability class.
 *
 * @since 1.0.0
 */
class RemoveResponsiveConditions extends AbstractAbility {

	/**
	 * Responsive condition attribute keys.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	private const CONDITION_KEYS = array(
		'UAGHideDesktop',
		'UAGHideMob',
		'UAGHideTab',
		'UAGDisplayConditions',
		'UAGUserRole',
		'UAGBrowser',
		'UAGSystem',
		'UAGDay',
		'UAGLoggedIn',
		'UAGLoggedOut',
		'UAGResponsiveConditions',
	);

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/remove-responsive-conditions';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Remove Responsive Conditions', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Removes all responsive visibility conditions from a block, making it visible on all devices and to all users.', 'spectra-blocks' );
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
	 * @return array
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success'    => array( 'type' => 'boolean' ),
				'block_name' => array( 'type' => 'string' ),
				'removed'    => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
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

		$block_name = $all_blocks[ $raw_index ]['blockName'];
		$removed    = array();
		$attrs      = $all_blocks[ $raw_index ]['attrs'] ?? array();

		foreach ( self::CONDITION_KEYS as $key ) {
			if ( array_key_exists( $key, $attrs ) ) {
				unset( $attrs[ $key ] );
				$removed[] = $key;
			}
		}

		$all_blocks[ $raw_index ]['attrs'] = $attrs;

		$result = $this->update_post_blocks( $post_id, $all_blocks );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'    => true,
			'block_name' => $block_name,
			'removed'    => $removed,
		);
	}
}
