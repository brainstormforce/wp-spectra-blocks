<?php
/**
 * Get Block Config ability.
 *
 * Returns the full block.json configuration for a specified Spectra block.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * GetBlockConfig ability class.
 *
 * @since 1.0.0
 */
class GetBlockConfig extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/get-block-config';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Get Spectra Block Configuration', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Returns the full block.json configuration for a specified Spectra block, including attributes, supports, and metadata.', 'spectra-blocks' );
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
			'required'   => array( 'block_name' ),
			'properties' => array(
				'block_name' => array(
					'type'        => 'string',
					'description' => __( 'The block name, e.g. "spectra/container" or "spectra-pro/mega-menu". The spectra/ prefix is added automatically if no namespace is given.', 'spectra-blocks' ),
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
				'block_name' => array( 'type' => 'string' ),
				'config'     => array(
					'type'        => 'object',
					'description' => __( 'The full block.json configuration.', 'spectra-blocks' ),
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
	 * @return array|WP_Error Block config or error.
	 */
	public function execute( array $params ) {
		if ( empty( $params['block_name'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The block_name parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$block_name = sanitize_text_field( $params['block_name'] );

		// Auto-add spectra/ prefix when no namespace is given.
		if ( strpos( $block_name, '/' ) === false ) {
			$block_name = 'spectra/' . $block_name;
		}

		// Resolve the block.json path based on namespace.
		if ( strpos( $block_name, 'spectra-pro/' ) === 0 ) {
			$pro_dir    = $this->get_pro_blocks_dir();
			$slug       = str_replace( 'spectra-pro/', '', $block_name );
			$block_json = $pro_dir ? $pro_dir . $slug . '/block.json' : '';
		} elseif ( strpos( $block_name, 'spectra/' ) === 0 ) {
			$slug       = str_replace( 'spectra/', '', $block_name );
			$block_json = $this->get_blocks_dir() . $slug . '/block.json';
		} else {
			return new WP_Error(
				'spectra_blocks_invalid_block',
				__( 'Block name must use the spectra/ or spectra-pro/ namespace.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		if ( ! $block_json || ! file_exists( $block_json ) ) {
			return new WP_Error(
				'spectra_blocks_not_found',
				/* translators: %s: block name */
				sprintf( __( 'Block "%s" not found.', 'spectra-blocks' ), $block_name ),
				array( 'status' => 404 )
			);
		}

		$config = wp_json_file_decode( $block_json, array( 'associative' => true ) );

		if ( ! $config ) {
			return new WP_Error(
				'spectra_blocks_invalid_config',
				__( 'Failed to parse block configuration.', 'spectra-blocks' ),
				array( 'status' => 500 )
			);
		}

		return array(
			'block_name' => $block_name,
			'config'     => $config,
		);
	}
}
