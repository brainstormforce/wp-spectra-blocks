<?php
/**
 * Get Block Activation Status ability.
 *
 * Returns all Spectra blocks with their active/inactive status.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * GetBlockActivationStatus ability class.
 *
 * @since 1.0.0
 */
class GetBlockActivationStatus extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/get-block-activation-status';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Get Block Activation Status', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Returns all Spectra blocks with their active or inactive status. Use with toggle-block-activation to manage which blocks are available in the editor.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-configuration';
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
			'properties' => new \stdClass(),
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
				'blocks'         => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'name'      => array(
								'type'        => 'string',
								'description' => __( 'Block name (e.g. spectra/container).', 'spectra-blocks' ),
							),
							'is_active' => array(
								'type'        => 'boolean',
								'description' => __( 'Whether the block is currently active.', 'spectra-blocks' ),
							),
						),
					),
				),
				'total'          => array( 'type' => 'integer' ),
				'active_count'   => array( 'type' => 'integer' ),
				'inactive_count' => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Check if the current user has permission.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'spectra_blocks_rest_forbidden',
			__( 'You do not have permission to view block settings.', 'spectra-blocks' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array Block activation data.
	 */
	public function execute( array $params ): array {
		$block_options = \Spectra_Blocks_Admin_Helper::get_block_options();

		$blocks         = array();
		$active_count   = 0;
		$inactive_count = 0;

		foreach ( $block_options as $name => $data ) {
			$is_active = ! empty( $data['is_activate'] );

			$blocks[] = array(
				'name'      => $name,
				'is_active' => $is_active,
			);

			if ( $is_active ) {
				++$active_count;
			} else {
				++$inactive_count;
			}
		}

		// Sort alphabetically by name.
		usort(
			$blocks,
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		return array(
			'blocks'         => $blocks,
			'total'          => count( $blocks ),
			'active_count'   => $active_count,
			'inactive_count' => $inactive_count,
		);
	}
}
