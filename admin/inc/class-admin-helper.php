<?php
/**
 * Spectra Blocks Admin Helper.
 *
 * @package Spectra_Blocks
 */

namespace SpectraBlocksAdmin\Inc;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ZipAI\Classes\Module as Zip_Ai_Module;

/**
 * Class Admin_Helper.
 */
class Admin_Helper {

	/**
	 * Common.
	 *
	 * @var object instance
	 */
	public static $common = null;

	/**
	 * Options.
	 *
	 * @var object instance
	 */
	public static $options = null;

	/**
	 * Get Common settings.
	 *
	 * @return array.
	 */
	public static function get_common_settings() {

		$theme_data          = \WP_Theme_JSON_Resolver::get_theme_data();
		$theme_settings      = $theme_data->get_settings();
		$theme_font_families = isset( $theme_settings['typography']['fontFamilies']['theme'] ) && is_array( $theme_settings['typography']['fontFamilies']['theme'] ) ? $theme_settings['typography']['fontFamilies']['theme'] : array();

		// Prepare to get the Zip AI Co-pilot modules.
		$zip_ai_modules = array();

		// If the Zip AI Helper is available, get the required modules and their states.
		if ( class_exists( '\ZipAI\Classes\Module' ) ) {
			$zip_ai_modules = Zip_Ai_Module::get_all_modules();
		}

		$options = array(
			'blocks_activation_and_deactivation' => self::get_blocks(),
			'enable_templates_button'            => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_templates_button', 'yes' ),
			'enable_block_responsive'            => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_block_responsive', 'enabled' ),
			'enable_dynamic_content'             => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_dynamic_content', 'enabled' ),
			'enable_animations_extension'        => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_animations_extension', 'enabled' ),
			'enable_gbs_extension'               => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_gbs_extension', 'enabled' ),
			'select_font_globally'               => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_select_font_globally', array() ),
			'load_select_font_globally'          => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_load_select_font_globally', 'disabled' ),
			'load_fse_font_globally'             => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_load_fse_font_globally', 'disabled' ),
			'load_gfonts_locally'                => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_load_gfonts_locally', 'disabled' ),
			'preload_local_fonts'                => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_preload_local_fonts', 'disabled' ),
			'btn_inherit_from_theme'             => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_btn_inherit_from_theme', 'disabled' ),
			'social'                             => self::get_social_settings_with_masked_secret(),
			'dynamic_content_mode'               => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_dynamic_content_mode', 'popup' ),
			'visibility_mode'                    => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_visibility_mode', 'disabled' ),
			'visibility_page'                    => self::get_visibility_page(),
			'recaptcha_site_key_v2'              => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_recaptcha_site_key_v2', '' ),
			'recaptcha_secret_key_v2'            => \Spectra_Blocks_Admin_Helper::mask_secret_value( \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_recaptcha_secret_key_v2', '' ) ),
			'recaptcha_site_key_v3'              => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_recaptcha_site_key_v3', '' ),
			'recaptcha_secret_key_v3'            => \Spectra_Blocks_Admin_Helper::mask_secret_value( \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_recaptcha_secret_key_v3', '' ) ),
			'spectra_global_fse_fonts'           => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_global_fse_fonts', array() ),
			'theme_fonts'                        => $theme_font_families,
			'zip_ai_modules'                     => $zip_ai_modules,
			'enable_bsf_analytics_option'        => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_analytics_optin', 'no' ),
			'spectra_blocks_disable_css_cache'   => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_disable_css_cache', 'disabled' ),
			'enable_abilities'                   => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_abilities', 'disabled' ),
			'enable_edit_abilities'              => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_edit_abilities', 'enabled' ),
			'enable_mcp_server'                  => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_mcp_server', 'disabled' ),
		);

		return $options;
	}

	/**
	 * Fetch the stored social-settings array with the Facebook App Secret
	 * replaced by a masked sentinel.
	 *
	 * Called from the admin dashboard payload builder so the raw secret is
	 * never included in the localized script or REST response. The paired
	 * save handler preserves the stored secret when the incoming value
	 * equals the sentinel.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_social_settings_with_masked_secret() {
		$social = \Spectra_Blocks_Admin_Helper::get_admin_settings_option(
			'spectra_blocks_social',
			array(
				'socialRegister'    => false,
				'googleClientId'    => '',
				'facebookAppId'     => '',
				'facebookAppSecret' => '',
			)
		);

		if ( is_array( $social ) && isset( $social['facebookAppSecret'] ) ) {
			$social['facebookAppSecret'] = \Spectra_Blocks_Admin_Helper::mask_secret_value( $social['facebookAppSecret'] );
		}

		return is_array( $social ) ? $social : array();
	}

	/**
	 * Get Visibility Page
	 *
	 * @since 1.0.0
	 * @return boolean|array
	 */
	public static function get_visibility_page() {
		$page_id = \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_visibility_page', '' );

		if ( $page_id ) {
			return array(
				'value' => $page_id,
				'label' => \get_the_title( $page_id ),
			);
		}
		return false;
	}

	/**
	 * Get blocks.
	 */
	public static function get_blocks() {
		// Get all blocks.
		$list_blocks    = \Spectra_Blocks_Helper::$block_list;
		$default_blocks = array();

		// Set all extension to enabled.
		foreach ( $list_blocks as $slug => $value ) {
			$_slug                    = str_replace( 'spectra/', '', $slug );
			$default_blocks[ $_slug ] = $_slug;
		}

		// Escape attrs.
		$default_blocks = array_map( 'esc_attr', $default_blocks );
		$saved_blocks   = \Spectra_Blocks_Admin_Helper::get_admin_settings_option( '_spectra_blocks_blocks', array() );

		return wp_parse_args( $saved_blocks, $default_blocks );
	}

	/**
	 * Get options.
	 */
	public static function get_options() {

		$general_settings          = self::get_common_settings();
		$shareable_common_settings = \Spectra_Blocks_Admin_Helper::get_admin_settings_shareable_data();
		$options                   = array_merge( $general_settings, $shareable_common_settings );
		$options                   = apply_filters( 'spectra_blocks_global_data_options', $options );

		return $options;
	}
}
