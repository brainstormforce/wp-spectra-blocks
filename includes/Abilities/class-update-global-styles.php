<?php
/**
 * Update Global Styles ability.
 *
 * Writes the GBS system variables (colors / spacing / font sizes) + the
 * block-defaults toggle. Storage is theme-agnostic — the GBS engine syncs it
 * into the active FSE theme — so it works on any block theme with no Spectra
 * One dependency. (Spectra Pro only *visualizes* these settings.)
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Update Global Styles ability.
 *
 * @since 1.0.0
 */
class UpdateGlobalStyles extends AbstractAbility {

	/**
	 * Get the MCP annotations.
	 *
	 * @since 1.0.0
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
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/update-global-styles';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Update Global Styles', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Update Spectra Global Styles settings. Partially merge colors, spacing, and font size variables. Toggle block defaults. Only provided fields are updated — existing values are preserved.', 'spectra-blocks' );
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
	 * Only site admins may change site-wide global styles.
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
				'colors'                 => array(
					'type'        => 'object',
					'description' => __( 'Color variables to set or merge (key-value pairs).', 'spectra-blocks' ),
				),
				'spacing'                => array(
					'type'        => 'object',
					'description' => __( 'Spacing variables to set or merge (key-value pairs).', 'spectra-blocks' ),
				),
				'fontsize'               => array(
					'type'        => 'object',
					'description' => __( 'Font size variables to set or merge (key-value pairs).', 'spectra-blocks' ),
				),
				'block_defaults_enabled' => array(
					'type'        => 'boolean',
					'description' => __( 'Enable or disable block defaults.', 'spectra-blocks' ),
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
				'updated_sections' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'system_variables' => array( 'type' => 'object' ),
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
		$updated_sections = array();
		$vars             = get_option( 'spectra_pro_gs_system_variables', array() );
		if ( ! is_array( $vars ) ) {
			$vars = array();
		}

		if ( ! empty( $params['colors'] ) && is_array( $params['colors'] ) ) {
			if ( ! isset( $vars['colors'] ) || ! is_array( $vars['colors'] ) ) {
				$vars['colors'] = array();
			}
			$vars['colors']     = array_merge( $vars['colors'], $this->sanitize_values( $params['colors'] ) );
			$updated_sections[] = 'colors';
		}

		if ( ! empty( $params['spacing'] ) && is_array( $params['spacing'] ) ) {
			if ( ! isset( $vars['spacing'] ) || ! is_array( $vars['spacing'] ) ) {
				$vars['spacing'] = array();
			}
			$vars['spacing']    = array_merge( $vars['spacing'], $this->sanitize_values( $params['spacing'] ) );
			$updated_sections[] = 'spacing';
		}

		if ( ! empty( $params['fontsize'] ) && is_array( $params['fontsize'] ) ) {
			if ( ! isset( $vars['fontsize'] ) || ! is_array( $vars['fontsize'] ) ) {
				$vars['fontsize'] = array();
			}
			$vars['fontsize']   = array_merge( $vars['fontsize'], $this->sanitize_values( $params['fontsize'] ) );
			$updated_sections[] = 'fontsize';
		}

		if ( ! empty( $updated_sections ) ) {
			update_option( 'spectra_pro_gs_system_variables', $vars );
			delete_transient( 'spectra_pro_gs_variables' );
		}

		if ( isset( $params['block_defaults_enabled'] ) ) {
			update_option( 'spectra_pro_gs_block_defaults_enabled', ! empty( $params['block_defaults_enabled'] ) ? '1' : '' );
			$updated_sections[] = 'block_defaults_enabled';
		}

		return array(
			'updated_sections' => $updated_sections,
			'system_variables' => $vars,
		);
	}

	/**
	 * Sanitize an associative array of values.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $values Key-value pairs.
	 * @return array<string, mixed>
	 */
	private function sanitize_values( array $values ) {
		$sanitized = array();
		foreach ( $values as $key => $value ) {
			$sanitized[ sanitize_text_field( (string) $key ) ] = sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
		}
		return $sanitized;
	}
}
