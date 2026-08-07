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
		 * Sentinel string returned in place of a stored secret value.
		 *
		 * The admin dashboard receives this sentinel (instead of the real secret)
		 * when reading options that hold API keys. On save, a handler that
		 * receives the sentinel must interpret it as "user did not re-enter the
		 * secret" and skip the write.
		 *
		 * @since 1.0.0
		 */
		const SECRET_MASK = '****';

		/**
		 * Return a masked placeholder for a stored secret.
		 *
		 * Returns the sentinel (four asterisks) when a non-empty value is stored,
		 * or an empty string when no value is stored. The raw secret is never
		 * returned; use {@see get_admin_settings_option()} directly on the write
		 * path when the true value is needed.
		 *
		 * @param mixed $raw The raw value read from storage.
		 * @return string Sentinel or empty string.
		 * @since 1.0.0
		 */
		public static function mask_secret_value( $raw ) {
			return ( is_string( $raw ) && '' !== $raw ) ? self::SECRET_MASK : '';
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
				'spectra_blocks_enable_templates_button' => self::get_admin_settings_option( 'spectra_blocks_enable_templates_button', 'yes' ),
				'spectra_blocks_enable_animations_extension' => self::get_admin_settings_option( 'spectra_blocks_enable_animations_extension', 'enabled' ),
				'spectra_blocks_enable_gbs_extension'    => self::get_admin_settings_option( 'spectra_blocks_enable_gbs_extension', 'enabled' ),
				'spectra_blocks_enable_block_responsive' => self::get_admin_settings_option( 'spectra_blocks_enable_block_responsive', 'enabled' ),
				'spectra_blocks_analytics_optin'         => self::get_admin_settings_option( 'spectra_blocks_analytics_optin', 'no' ),
				'wp_is_block_theme'                      => self::is_block_theme(),
				'zip_ai_modules'                         => $zip_ai_modules,
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

			if ( ! empty( $source ) ) {
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

		/**
		 * Fetch previous stable plugin versions from wp.org (cached for one week).
		 *
		 * @since 1.0.1
		 * @return array
		 */
		public static function get_rollback_versions() {
			$rollback_versions = get_transient( 'spectra_blocks_rollback_versions_' . SPECTRA_BLOCKS_VER );

			if ( ! empty( $rollback_versions ) ) {
				return $rollback_versions;
			}

			$max_versions = 10;

			$response = wp_remote_get(
				add_query_arg(
					array(
						'action'                    => 'plugin_information',
						'request[slug]'             => 'spectra-blocks',
						'request[fields][versions]' => '1',
					),
					'https://api.wordpress.org/plugins/info/1.2/'
				),
				array( 'timeout' => 15 )
			);

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return array();
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $body['versions'] ) || ! is_array( $body['versions'] ) ) {
				return array();
			}

			$versions = $body['versions'];
			krsort( $versions );

			$rollback_versions = array();

			foreach ( $versions as $version => $download_link ) {
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

			if ( ! empty( $rollback_versions ) ) {
				set_transient( 'spectra_blocks_rollback_versions_' . SPECTRA_BLOCKS_VER, $rollback_versions, WEEK_IN_SECONDS );
			}

			return $rollback_versions;
		}

		/**
		 * Sort rollback versions descending.
		 *
		 * @since 1.0.1
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

		/**
		 * Return rollback versions as label/value pairs for JS consumption.
		 *
		 * @since 1.0.1
		 * @return array
		 */
		public static function get_rollback_versions_options() {
			$versions = self::get_rollback_versions();
			$options  = array();
			foreach ( $versions as $version ) {
				$options[] = array(
					'label' => $version,
					'value' => $version,
				);
			}
			return $options;
		}
	}
}
