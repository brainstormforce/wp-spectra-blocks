<?php
/**
 * Update Plugin Setting ability.
 *
 * Updates a single Spectra Blocks plugin setting by key.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * UpdatePluginSetting ability class.
 *
 * @since 1.0.0
 */
class UpdatePluginSetting extends AbstractAbility {

	/**
	 * Allowed setting keys that can be updated.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	private const ALLOWED_KEYS = array(
		'spectra_blocks_enable_templates_button',
		'spectra_blocks_enable_on_page_css_button',
		'spectra_blocks_enable_block_condition',
		'spectra_blocks_enable_masonry_gallery',
		'spectra_blocks_enable_quick_action_sidebar',
		'spectra_blocks_enable_animations_extension',
		'spectra_blocks_enable_gbs_extension',
		'spectra_blocks_enable_block_responsive',
		'spectra_blocks_load_select_font_globally',
		'spectra_blocks_load_gfonts_locally',
		'spectra_blocks_collapse_panels',
		'spectra_blocks_copy_paste',
		'spectra_blocks_preload_local_fonts',
		'spectra_blocks_visibility_mode',
		'spectra_blocks_blocks_editor_spacing',
		'spectra_blocks_load_font_awesome_5',
		'spectra_blocks_auto_block_recovery',
		'spectra_blocks_analytics_optin',
	);

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/update-plugin-setting';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Update Plugin Setting', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Updates a single Spectra Blocks plugin setting by key. Use spectra-blocks/get-plugin-settings to discover available keys and current values.', 'spectra-blocks' );
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
			'required'   => array( 'key', 'value' ),
			'properties' => array(
				'key'   => array(
					'type'        => 'string',
					'description' => __( 'The setting key to update.', 'spectra-blocks' ),
					'enum'        => self::ALLOWED_KEYS,
				),
				'value' => array(
					'description' => __( 'The new value for the setting.', 'spectra-blocks' ),
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
				'success' => array( 'type' => 'boolean' ),
				'key'     => array( 'type' => 'string' ),
				'value'   => array( 'description' => __( 'The new value that was set.', 'spectra-blocks' ) ),
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
			__( 'You do not have permission to update plugin settings.', 'spectra-blocks' ),
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
		if ( empty( $params['key'] ) || ! array_key_exists( 'value', $params ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'Both key and value parameters are required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$key = sanitize_text_field( $params['key'] );

		if ( ! in_array( $key, self::ALLOWED_KEYS, true ) ) {
			return new WP_Error(
				'spectra_blocks_invalid_param',
				/* translators: %s: setting key */
				sprintf( __( 'The setting key "%s" is not allowed.', 'spectra-blocks' ), $key ),
				array( 'status' => 400 )
			);
		}

		$value = $params['value'];

		\Spectra_Blocks_Admin_Helper::update_admin_settings_option( $key, $value );

		return array(
			'success' => true,
			'key'     => $key,
			'value'   => $value,
		);
	}
}
