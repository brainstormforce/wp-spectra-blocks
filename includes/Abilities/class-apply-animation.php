<?php
/**
 * Apply Animation ability.
 *
 * Applies animation settings to a block in a post.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * ApplyAnimation ability class.
 *
 * @since 1.0.0
 */
class ApplyAnimation extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/apply-animation';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Apply Animation to Block', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Applies animation settings (type, duration, delay, easing, repeat) to a block in a post. Requires the animations extension to be enabled.', 'spectra-blocks' );
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
			'required'   => array( 'post_id', 'block_index', 'type' ),
			'properties' => array(
				'post_id'                   => array(
					'type'        => 'integer',
					'description' => __( 'The post ID containing the block.', 'spectra-blocks' ),
				),
				'block_index'               => array(
					'type'        => 'integer',
					'description' => __( 'The 0-based block index.', 'spectra-blocks' ),
				),
				'type'                      => array(
					'type'        => 'string',
					'description' => __( 'Animation type (e.g. "fadeIn", "slideInUp", "zoomIn").', 'spectra-blocks' ),
				),
				'duration'                  => array(
					'type'        => 'integer',
					'description' => __( 'Animation duration in milliseconds. Default 1000.', 'spectra-blocks' ),
					'default'     => 1000,
				),
				'delay'                     => array(
					'type'        => 'integer',
					'description' => __( 'Animation delay in milliseconds. Default 0.', 'spectra-blocks' ),
					'default'     => 0,
				),
				'easing'                    => array(
					'type'        => 'string',
					'description' => __( 'Animation easing function. Default "ease".', 'spectra-blocks' ),
					'default'     => 'ease',
				),
				'repeat'                    => array(
					'type'        => 'string',
					'description' => __( 'Animation repeat behavior.', 'spectra-blocks' ),
				),
				'delay_interval'            => array(
					'type'        => 'integer',
					'description' => __( 'Delay interval for staggered animations.', 'spectra-blocks' ),
				),
				'do_not_apply_to_container' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to skip applying animation to the container wrapper.', 'spectra-blocks' ),
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
				'animation_settings' => array( 'type' => 'object' ),
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
		if ( empty( $params['post_id'] ) || ! isset( $params['block_index'] ) || empty( $params['type'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The post_id, block_index, and type parameters are required.', 'spectra-blocks' ),
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

		$animation_attrs = array(
			'UAGAnimationType' => sanitize_text_field( $params['type'] ),
		);

		if ( isset( $params['duration'] ) ) {
			$animation_attrs['UAGAnimationTime'] = absint( $params['duration'] );
		}

		if ( isset( $params['delay'] ) ) {
			$animation_attrs['UAGAnimationDelay'] = absint( $params['delay'] );
		}

		if ( ! empty( $params['easing'] ) ) {
			$animation_attrs['UAGAnimationEasing'] = sanitize_text_field( $params['easing'] );
		}

		if ( ! empty( $params['repeat'] ) ) {
			$animation_attrs['UAGAnimationRepeat'] = sanitize_text_field( $params['repeat'] );
		}

		if ( isset( $params['delay_interval'] ) ) {
			$animation_attrs['UAGAnimationDelayInterval'] = absint( $params['delay_interval'] );
		}

		if ( isset( $params['do_not_apply_to_container'] ) ) {
			$animation_attrs['UAGAnimationDoNotApplyToContainer'] = (bool) $params['do_not_apply_to_container'];
		}

		$block_name                        = $all_blocks[ $raw_index ]['blockName'];
		$all_blocks[ $raw_index ]['attrs'] = array_merge(
			$all_blocks[ $raw_index ]['attrs'] ?? array(),
			$animation_attrs
		);

		$result = $this->update_post_blocks( $post_id, $all_blocks );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'            => true,
			'block_name'         => $block_name,
			'animation_settings' => $animation_attrs,
		);
	}
}
