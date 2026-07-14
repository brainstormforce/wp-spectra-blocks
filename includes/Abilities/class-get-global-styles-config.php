<?php
/**
 * Get Global Styles Config ability.
 *
 * Reads the current Global Styles configuration (system variables, user CSS,
 * block defaults). Theme-agnostic — no Spectra One dependency.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Get Global Styles Config ability.
 *
 * @since 1.0.0
 */
class GetGlobalStylesConfig extends AbstractAbility {

	/**
	 * Get the MCP annotations.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_annotations(): array {
		return array(
			'readonly'    => true,
			'destructive' => false,
			'idempotent'  => true,
		);
	}

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/get-global-styles-config';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Get Global Styles Config', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Read the current Spectra Global Styles configuration including system variables (colors, spacing, font sizes), user CSS, and block defaults.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-configuration';
	}

	/**
	 * Only site admins may read the site-wide global styles config.
	 *
	 * @since 1.0.0
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'spectra_blocks_rest_forbidden',
			__( 'You do not have permission to perform this action.', 'spectra-blocks' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Input schema.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'sections' => array(
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
						'enum' => array( 'system_variables', 'user_css', 'block_defaults', 'all' ),
					),
					'default'     => array( 'all' ),
					'description' => __( 'Which sections to return.', 'spectra-blocks' ),
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Output schema.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'system_variables'       => array( 'type' => 'object' ),
				'user_css'               => array( 'type' => 'string' ),
				'block_defaults'         => array( 'type' => 'object' ),
				'block_defaults_enabled' => array( 'type' => 'boolean' ),
			),
		);
	}

	/**
	 * Execute.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $params Validated input.
	 * @return array<string, mixed>|WP_Error
	 */
	public function execute( array $params ) {
		$sections    = isset( $params['sections'] ) ? (array) $params['sections'] : array( 'all' );
		$include_all = in_array( 'all', $sections, true );

		$result = array();

		if ( $include_all || in_array( 'system_variables', $sections, true ) ) {
			$vars                       = get_option( 'spectra_pro_gs_system_variables', array() );
			$result['system_variables'] = is_array( $vars ) ? $vars : array();
		}

		if ( $include_all || in_array( 'user_css', $sections, true ) ) {
			$css                = get_option( 'spectra_pro_gs_user_css', '' );
			$result['user_css'] = is_string( $css ) ? $css : '';
		}

		if ( $include_all || in_array( 'block_defaults', $sections, true ) ) {
			$defaults                 = get_option( 'spectra_pro_gs_block_defaults', array() );
			$result['block_defaults'] = is_array( $defaults ) ? $defaults : array();
		}

		$enabled                          = get_option( 'spectra_pro_gs_block_defaults_enabled', false );
		$result['block_defaults_enabled'] = ! empty( $enabled );

		return $result;
	}
}
