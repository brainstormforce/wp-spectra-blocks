<?php
/**
 * Toggle Block Activation ability.
 *
 * Activates or deactivates a specific Spectra block.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * ToggleBlockActivation ability class.
 *
 * @since 1.0.0
 */
class ToggleBlockActivation extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/toggle-block-activation';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Toggle Block Activation', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Activates or deactivates a specific Spectra block by its slug. Use spectra-blocks/list-available-blocks to discover valid block names.', 'spectra-blocks' );
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
			'required'   => array( 'block_name', 'active' ),
			'properties' => array(
				'block_name' => array(
					'type'        => 'string',
					'description' => __( 'The block slug without namespace (e.g. "container", "accordion").', 'spectra-blocks' ),
				),
				'active'     => array(
					'type'        => 'boolean',
					'description' => __( 'True to activate the block, false to deactivate it.', 'spectra-blocks' ),
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
				'block_name' => array(
					'type'        => 'string',
					'description' => __( 'The block slug that was toggled.', 'spectra-blocks' ),
				),
				'active'     => array(
					'type'        => 'boolean',
					'description' => __( 'The new activation state.', 'spectra-blocks' ),
				),
			),
		);
	}

	/**
	 * Check if the current user has permission.
	 *
	 * Requires manage_options since this modifies plugin configuration.
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
			__( 'You do not have permission to manage block settings.', 'spectra-blocks' ),
			array( 'status' => 403 )
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
		if ( empty( $params['block_name'] ) || ! isset( $params['active'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'Both block_name and active parameters are required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$block_name = sanitize_text_field( $params['block_name'] );
		$active     = (bool) $params['active'];

		// Strip namespace prefix if provided.
		$block_name = str_replace( 'spectra/', '', $block_name );

		// Validate block exists in the registry.
		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( 'spectra/' . $block_name );

		if ( ! $block_type ) {
			return new WP_Error(
				'spectra_blocks_not_found',
				/* translators: %s: block name */
				sprintf( __( 'Block "spectra/%s" is not registered.', 'spectra-blocks' ), $block_name ),
				array( 'status' => 404 )
			);
		}

		// Get current block activation state.
		$saved_blocks = get_option( '_spectra_blocks_blocks', array() );

		if ( ! is_array( $saved_blocks ) ) {
			$saved_blocks = array();
		}

		// Update activation state.
		$saved_blocks[ $block_name ] = $active ? 'yes' : 'no';

		update_option( '_spectra_blocks_blocks', $saved_blocks );

		return array(
			'block_name' => $block_name,
			'active'     => $active,
		);
	}
}
