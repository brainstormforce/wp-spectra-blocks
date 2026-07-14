<?php
/**
 * Apply Responsive Conditions ability.
 *
 * Sets responsive visibility rules on a block.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * ApplyResponsiveConditions ability class.
 *
 * @since 1.0.0
 */
class ApplyResponsiveConditions extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/apply-responsive-conditions';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Apply Responsive Conditions', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Sets responsive visibility rules on a block including device visibility, user role conditions, browser/OS targeting, and day-of-week conditions.', 'spectra-blocks' );
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
				'post_id'            => array(
					'type'        => 'integer',
					'description' => __( 'The post ID containing the block.', 'spectra-blocks' ),
				),
				'block_index'        => array(
					'type'        => 'integer',
					'description' => __( 'The 0-based block index.', 'spectra-blocks' ),
				),
				'hide_desktop'       => array(
					'type'        => 'boolean',
					'description' => __( 'Hide this block on desktop.', 'spectra-blocks' ),
				),
				'hide_tablet'        => array(
					'type'        => 'boolean',
					'description' => __( 'Hide this block on tablet.', 'spectra-blocks' ),
				),
				'hide_mobile'        => array(
					'type'        => 'boolean',
					'description' => __( 'Hide this block on mobile.', 'spectra-blocks' ),
				),
				'display_conditions' => array(
					'type'        => 'string',
					'description' => __( 'Display condition mode.', 'spectra-blocks' ),
				),
				'user_role'          => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'User roles that can see this block.', 'spectra-blocks' ),
				),
				'browser'            => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Browsers to target.', 'spectra-blocks' ),
				),
				'os'                 => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Operating systems to target.', 'spectra-blocks' ),
				),
				'day'                => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Days of the week to show the block.', 'spectra-blocks' ),
				),
				'logged_in'          => array(
					'type'        => 'boolean',
					'description' => __( 'Show only to logged-in users.', 'spectra-blocks' ),
				),
				'logged_out'         => array(
					'type'        => 'boolean',
					'description' => __( 'Show only to logged-out users.', 'spectra-blocks' ),
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
				'conditions_applied' => array( 'type' => 'object' ),
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

		$conditions = array();
		$param_map  = array(
			'hide_desktop'       => 'UAGHideDesktop',
			'hide_tablet'        => 'UAGHideTab',
			'hide_mobile'        => 'UAGHideMob',
			'display_conditions' => 'UAGDisplayConditions',
			'user_role'          => 'UAGUserRole',
			'browser'            => 'UAGBrowser',
			'os'                 => 'UAGSystem',
			'day'                => 'UAGDay',
			'logged_in'          => 'UAGLoggedIn',
			'logged_out'         => 'UAGLoggedOut',
		);

		$array_fields = array( 'user_role', 'browser', 'os', 'day' );

		foreach ( $param_map as $param_key => $attr_key ) {
			if ( array_key_exists( $param_key, $params ) ) {
				if ( in_array( $param_key, $array_fields, true ) ) {
					$conditions[ $attr_key ] = array_map( 'sanitize_text_field', (array) $params[ $param_key ] );
				} elseif ( is_string( $params[ $param_key ] ) ) {
					$conditions[ $attr_key ] = sanitize_text_field( $params[ $param_key ] );
				} else {
					$conditions[ $attr_key ] = $params[ $param_key ];
				}
			}
		}

		// Enable responsive conditions flag.
		$conditions['UAGResponsiveConditions'] = true;

		$block_name                        = $all_blocks[ $raw_index ]['blockName'];
		$all_blocks[ $raw_index ]['attrs'] = array_merge(
			$all_blocks[ $raw_index ]['attrs'] ?? array(),
			$conditions
		);

		$result = $this->update_post_blocks( $post_id, $all_blocks );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'            => true,
			'block_name'         => $block_name,
			'conditions_applied' => $conditions,
		);
	}
}
