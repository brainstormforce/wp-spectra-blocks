<?php
/**
 * Apply Image Mask ability.
 *
 * Applies an image mask to a block.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * ApplyImageMask ability class.
 *
 * @since 1.0.0
 */
class ApplyImageMask extends AbstractAbility {

	/**
	 * Valid predefined mask shapes.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	private const VALID_SHAPES = array(
		'blob1',
		'blob2',
		'blob3',
		'blob4',
		'circle',
		'diamond',
		'hexagon',
		'rounded',
		'custom',
		'none',
	);

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/apply-image-mask';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Apply Image Mask', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Applies an image mask shape to a core/image block. Available shapes: blob1, blob2, blob3, blob4, circle, diamond, hexagon, rounded, custom, none.', 'spectra-blocks' );
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
			'required'   => array( 'post_id', 'block_index', 'shape' ),
			'properties' => array(
				'post_id'     => array(
					'type'        => 'integer',
					'description' => __( 'The post ID containing the block.', 'spectra-blocks' ),
				),
				'block_index' => array(
					'type'        => 'integer',
					'description' => __( 'The 0-based block index.', 'spectra-blocks' ),
				),
				'shape'       => array(
					'type'        => 'string',
					'description' => __( 'The mask shape to apply.', 'spectra-blocks' ),
					'enum'        => self::VALID_SHAPES,
				),
				'size'        => array(
					'type'        => 'string',
					'description' => __( 'CSS mask-size value. Default "contain".', 'spectra-blocks' ),
					'default'     => 'contain',
				),
				'position_x'  => array(
					'type'        => 'number',
					'description' => __( 'Horizontal position (0-1). Default 0.5.', 'spectra-blocks' ),
					'default'     => 0.5,
				),
				'position_y'  => array(
					'type'        => 'number',
					'description' => __( 'Vertical position (0-1). Default 0.5.', 'spectra-blocks' ),
					'default'     => 0.5,
				),
				'repeat'      => array(
					'type'        => 'string',
					'description' => __( 'CSS mask-repeat value. Default "no-repeat".', 'spectra-blocks' ),
					'default'     => 'no-repeat',
				),
				'custom_url'  => array(
					'type'        => 'string',
					'description' => __( 'URL for custom mask image. Required when shape is "custom".', 'spectra-blocks' ),
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
				'success'       => array( 'type' => 'boolean' ),
				'block_name'    => array( 'type' => 'string' ),
				'mask_settings' => array( 'type' => 'object' ),
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
		if ( empty( $params['post_id'] ) || ! isset( $params['block_index'] ) || empty( $params['shape'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The post_id, block_index, and shape parameters are required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$shape = sanitize_text_field( $params['shape'] );

		if ( ! in_array( $shape, self::VALID_SHAPES, true ) ) {
			return new WP_Error(
				'spectra_blocks_invalid_param',
				/* translators: %s: shape name */
				sprintf( __( 'Invalid mask shape "%s".', 'spectra-blocks' ), $shape ),
				array( 'status' => 400 )
			);
		}

		if ( 'custom' === $shape && empty( $params['custom_url'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The custom_url parameter is required when shape is "custom".', 'spectra-blocks' ),
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

		$mask_value = array(
			'shape'    => $shape,
			'size'     => sanitize_text_field( $params['size'] ?? 'contain' ),
			'position' => array(
				'x' => floatval( $params['position_x'] ?? 0.5 ),
				'y' => floatval( $params['position_y'] ?? 0.5 ),
			),
			'repeat'   => sanitize_text_field( $params['repeat'] ?? 'no-repeat' ),
		);

		if ( 'custom' === $shape ) {
			$custom_url = esc_url_raw( $params['custom_url'] );
			$site_host  = wp_parse_url( home_url(), PHP_URL_HOST );
			$url_host   = wp_parse_url( $custom_url, PHP_URL_HOST );

			if ( ! $url_host || strtolower( $url_host ) !== strtolower( $site_host ) ) {
				return new WP_Error(
					'spectra_blocks_invalid_param',
					__( 'The custom mask URL must be a local media attachment from this site.', 'spectra-blocks' ),
					array( 'status' => 400 )
				);
			}

			$mask_value['image'] = array( 'url' => $custom_url );
		}

		$all_blocks[ $raw_index ]['attrs'] = array_merge(
			$all_blocks[ $raw_index ]['attrs'] ?? array(),
			array( 'spectraMask' => $mask_value )
		);

		$result = $this->update_post_blocks( $post_id, $all_blocks );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'       => true,
			'block_name'    => $block_name,
			'mask_settings' => $mask_value,
		);
	}
}
