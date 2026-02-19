<?php
/**
 * Spectra Blocks Admin Helper.
 *
 * Global admin helper class — settings CRUD, block list, and admin utilities.
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Spectra_Blocks_Admin_Helper' ) ) {

	/**
	 * Class Spectra_Blocks_Admin_Helper.
	 */
	final class Spectra_Blocks_Admin_Helper {

		/**
		 * Singleton instance.
		 *
		 * @var self|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		// -------------------------------------------------------------------------
		// Settings CRUD
		// -------------------------------------------------------------------------

		/**
		 * Get an option value.
		 *
		 * @param string $key              Full option key.
		 * @param mixed  $fallback          Default value.
		 * @param bool   $network_override Use network option on multisite.
		 * @return mixed
		 */
		public static function get_admin_settings_option( $key, $fallback = false, $network_override = false ) {
			if ( $network_override && is_multisite() ) {
				return get_site_option( $key, $fallback );
			}
			return get_option( $key, $fallback );
		}

		/**
		 * Update an option value.
		 *
		 * @param string $key              Full option key.
		 * @param mixed  $value            Value to store.
		 * @param bool   $network_override Use network option on multisite.
		 * @return bool
		 */
		public static function update_admin_settings_option( $key, $value, $network_override = false ) {
			if ( $network_override && is_multisite() ) {
				return update_site_option( $key, $value );
			}
			return update_option( $key, $value );
		}

		/**
		 * Delete an option.
		 *
		 * @param string $key              Full option key.
		 * @param bool   $network_override Use network option on multisite.
		 * @return void
		 */
		public static function delete_admin_settings_option( $key, $network_override = false ) {
			if ( $network_override && is_multisite() ) {
				delete_site_option( $key );
			} else {
				delete_option( $key );
			}
		}

		// -------------------------------------------------------------------------
		// Shareable / merged settings data
		// -------------------------------------------------------------------------

		/**
		 * Get shareable admin settings data (used when merging option sets).
		 *
		 * @return array
		 */
		public static function get_admin_settings_shareable_data() {
			$zip_ai_modules = array();
			if ( class_exists( '\ZipAI\Classes\Module' ) ) {
				$zip_ai_modules = \ZipAI\Classes\Module::get_all_modules();
			}

			return array(
				'spectra_blocks_beta'                      => self::get_admin_settings_option( 'spectra_blocks_beta', 'no' ),
				'_spectra_blocks_allow_file_generation'    => self::get_admin_settings_option( '_spectra_blocks_allow_file_generation', 'enabled' ),
				'spectra_blocks_enable_templates_button'   => self::get_admin_settings_option( 'spectra_blocks_enable_templates_button', 'yes' ),
				'spectra_blocks_enable_on_page_css_button' => self::get_admin_settings_option( 'spectra_blocks_enable_on_page_css_button', 'yes' ),
				'spectra_blocks_enable_block_condition'    => self::get_admin_settings_option( 'spectra_blocks_enable_block_condition', 'disabled' ),
				'spectra_blocks_enable_masonry_gallery'    => self::get_admin_settings_option( 'spectra_blocks_enable_masonry_gallery', 'enabled' ),
				'spectra_blocks_enable_quick_action_sidebar' => self::get_admin_settings_option( 'spectra_blocks_enable_quick_action_sidebar', 'enabled' ),
				'spectra_blocks_enable_animations_extension' => self::get_admin_settings_option( 'spectra_blocks_enable_animations_extension', 'enabled' ),
				'spectra_blocks_enable_gbs_extension'      => self::get_admin_settings_option( 'spectra_blocks_enable_gbs_extension', 'enabled' ),
				'spectra_blocks_enable_block_responsive'   => self::get_admin_settings_option( 'spectra_blocks_enable_block_responsive', 'enabled' ),
				'spectra_blocks_select_font_globally'      => self::get_admin_settings_option( 'spectra_blocks_select_font_globally', array() ),
				'spectra_blocks_load_select_font_globally' => self::get_admin_settings_option( 'spectra_blocks_load_select_font_globally', 'disabled' ),
				'spectra_blocks_load_gfonts_locally'       => self::get_admin_settings_option( 'spectra_blocks_load_gfonts_locally', 'disabled' ),
				'spectra_blocks_collapse_panels'           => self::get_admin_settings_option( 'spectra_blocks_collapse_panels', 'enabled' ),
				'spectra_blocks_copy_paste'                => self::get_admin_settings_option( 'spectra_blocks_copy_paste', 'enabled' ),
				'spectra_blocks_preload_local_fonts'       => self::get_admin_settings_option( 'spectra_blocks_preload_local_fonts', 'disabled' ),
				'spectra_blocks_visibility_mode'           => self::get_admin_settings_option( 'spectra_blocks_visibility_mode', 'disabled' ),
				'spectra_blocks_container_global_padding'  => self::get_admin_settings_option( 'spectra_blocks_container_global_padding', 'default' ),
				'spectra_blocks_container_global_elements_gap' => self::get_admin_settings_option( 'spectra_blocks_container_global_elements_gap', 20 ),
				'spectra_blocks_btn_inherit_from_theme'    => self::get_admin_settings_option( 'spectra_blocks_btn_inherit_from_theme', 'disabled' ),
				'spectra_blocks_blocks_editor_spacing'     => apply_filters( 'spectra_blocks_default_editor_spacing', self::get_admin_settings_option( 'spectra_blocks_blocks_editor_spacing', 0 ) ),
				'spectra_blocks_load_font_awesome_5'       => self::get_admin_settings_option( 'spectra_blocks_load_font_awesome_5' ),
				'spectra_blocks_auto_block_recovery'       => self::get_admin_settings_option( 'spectra_blocks_auto_block_recovery' ),
				'spectra_blocks_analytics_optin'           => self::get_admin_settings_option( 'spectra_blocks_analytics_optin', 'no' ),
				'wp_is_block_theme'                        => self::is_block_theme(),
				'zip_ai_modules'                           => $zip_ai_modules,
			);
		}

		// -------------------------------------------------------------------------
		// Block list
		// -------------------------------------------------------------------------

		/**
		 * Get block options with activation status merged in.
		 *
		 * @return array Keyed by block name (spectra/block-name).
		 */
		public static function get_block_options() {
			$blocks       = Spectra_Blocks_Helper::$block_list;
			$saved_blocks = self::get_admin_settings_option( '_spectra_blocks_blocks' );

			if ( is_array( $blocks ) ) {
				foreach ( $blocks as $slug => $data ) {
					$_slug = str_replace( 'spectra/', '', $slug );

					if ( isset( $saved_blocks[ $_slug ] ) ) {
						$blocks[ $slug ]['is_activate'] = 'disabled' !== $saved_blocks[ $_slug ];
					} else {
						$blocks[ $slug ]['is_activate'] = isset( $data['default'] ) ? $data['default'] : true;
					}
				}
			}

			return is_array( $blocks ) ? $blocks : array();
		}

		// -------------------------------------------------------------------------
		// URL / Theme utilities
		// -------------------------------------------------------------------------

		/**
		 * Build a Spectra Pro URL with UTM parameters.
		 *
		 * @param string $path     URL path (e.g. '/pricing/').
		 * @param string $source   UTM source.
		 * @param string $medium   UTM medium.
		 * @param string $campaign UTM campaign.
		 * @return string
		 */
		public static function get_spectra_pro_url( $path, $source = '', $medium = '', $campaign = '' ) {
			$base_url = defined( 'SPECTRA_BLOCKS_URI' ) ? SPECTRA_BLOCKS_URI : trailingslashit( 'https://wpspectra.com/' );
			$url      = trailingslashit( esc_url( $base_url . ltrim( $path, '/' ) ) );

			if ( class_exists( '\BSF_UTM_Analytics\Inc\Utils' ) && is_callable( '\BSF_UTM_Analytics\Inc\Utils::get_utm_ready_link' ) ) {
				$url = \BSF_UTM_Analytics\Inc\Utils::get_utm_ready_link( $url, 'spectra-blocks' );
			} elseif ( ! empty( $source ) ) {
					$url = add_query_arg( 'utm_source', sanitize_text_field( $source ), $url );
			}

			if ( ! empty( $medium ) ) {
				$url = add_query_arg( 'utm_medium', sanitize_text_field( $medium ), $url );
			}

			if ( ! empty( $campaign ) ) {
				$url = add_query_arg( 'utm_campaign', sanitize_text_field( $campaign ), $url );
			}

			return apply_filters( 'spectra_blocks_get_pro_url', $url );
		}

		/**
		 * Check whether the active theme is a block (FSE) theme.
		 *
		 * @return bool
		 */
		public static function is_block_theme() {
			return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
		}

		/**
		 * Get the user's pricing region country code.
		 *
		 * @return string 'IN' for India, 'US' for all others.
		 */
		public static function get_user_country_code() {
			$country_code = 'US';

			if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
				$country_code = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) );
			}

			// Map to pricing regions.
			if ( 'IN' === $country_code ) {
				return 'IN';
			}

			return 'US';
		}

		// -------------------------------------------------------------------------
		// Rollback
		// -------------------------------------------------------------------------

		/**
		 * Get available rollback versions from the WordPress.org plugin API.
		 *
		 * @return array
		 */
		public function get_rollback_versions() {
			$transient_key     = 'spectra_blocks_rollback_versions_' . SPECTRA_BLOCKS_VER;
			$rollback_versions = get_transient( $transient_key );

			if ( ! empty( $rollback_versions ) ) {
				return $rollback_versions;
			}

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$plugin_information = plugins_api(
				'plugin_information',
				array(
					'slug' => 'spectra-blocks',
				)
			);

			if ( is_wp_error( $plugin_information ) || empty( $plugin_information->versions ) || ! is_array( $plugin_information->versions ) ) {
				return array();
			}

			krsort( $plugin_information->versions );

			$rollback_versions = array();
			$max_versions      = 10;

			foreach ( $plugin_information->versions as $version => $download_link ) {
				if ( preg_match( '/(trunk|beta|rc|dev)/i', strtolower( $version ) ) ) {
					continue;
				}

				if ( version_compare( $version, SPECTRA_BLOCKS_VER, '>=' ) ) {
					continue;
				}

				$rollback_versions[] = $version;
			}

			usort( $rollback_versions, array( __CLASS__, 'sort_rollback_versions' ) );
			$rollback_versions = array_slice( $rollback_versions, 0, $max_versions, true );

			set_transient( $transient_key, $rollback_versions, WEEK_IN_SECONDS );

			return $rollback_versions;
		}

		/**
		 * Sort rollback versions descending.
		 *
		 * @param string $prev Previous version.
		 * @param string $next Next version.
		 * @return int
		 */
		public static function sort_rollback_versions( $prev, $next ) {
			if ( version_compare( $prev, $next, '==' ) ) {
				return 0;
			}
			return version_compare( $prev, $next, '>' ) ? -1 : 1;
		}

		// -------------------------------------------------------------------------
		// CSS Asset
		// -------------------------------------------------------------------------

		/**
		 * Create the combined stylesheet for enabled blocks.
		 * For V3 (spectra-blocks), CSS is handled via block.json registered stylesheets.
		 * This method is a no-op placeholder to satisfy call sites carried over from V2.
		 *
		 * @return void
		 */
		public static function create_specific_stylesheet() {
			// V3 blocks use block.json stylesheets — no combined stylesheet needed.
			do_action( 'spectra_blocks_create_specific_stylesheet' );
		}
	}
}
