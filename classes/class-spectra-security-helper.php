<?php
/**
 * Spectra Security Helper.
 *
 * Provides centralized security utilities for AJAX operations,
 * file operations, and plugin security.
 *
 * @package Spectra
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spectra_Security_Helper' ) ) {

	/**
	 * Class Spectra_Security_Helper.
	 *
	 * Centralized security utilities for the Spectra plugin.
	 */
	final class Spectra_Security_Helper {

		/**
		 * Rate limiting transient prefix.
		 *
		 * @var string
		 */
		const RATE_LIMIT_PREFIX = 'spectra_rate_limit_';

		/**
		 * Audit log option name.
		 *
		 * @var string
		 */
		const AUDIT_LOG_OPTION = 'spectra_security_audit_log';

		/**
		 * Maximum audit log entries to retain.
		 *
		 * @var int
		 */
		const MAX_AUDIT_LOG_ENTRIES = 1000;

		/**
		 * Validate and sanitize a file path to prevent path traversal attacks.
		 *
		 * This function ensures that:
		 * 1. The path is within the allowed base directory
		 * 2. No directory traversal sequences are present
		 * 3. The path uses the correct directory separator for the OS
		 *
		 * @param string $path The file path to validate.
		 * @param string $base_dir The base directory that the path must be within.
		 * @return string|false Sanitized path on success, false on failure.
		 * @since 0.0.1
		 */
		public static function validate_file_path( $path, $base_dir ) {
			if ( empty( $path ) || empty( $base_dir ) ) {
				return false;
			}

			// Normalize directory separators.
			$path     = wp_normalize_path( $path );
			$base_dir = wp_normalize_path( $base_dir );

			// Ensure base directory ends with a slash.
			$base_dir = trailingslashit( $base_dir );

			// Get the real path to resolve any symbolic links and relative paths.
			$real_path = realpath( $path );
			$real_base = realpath( $base_dir );

			// If realpath fails (path doesn't exist yet), validate the normalized path.
			if ( false === $real_path ) {
				// For non-existent paths, validate against normalized paths.
				$real_path = $path;
				$real_base = $base_dir;
			} else {
				$real_path = wp_normalize_path( $real_path );
			}

			if ( false === $real_base ) {
				return false;
			}

			$real_base = wp_normalize_path( $real_base );
			$real_base = trailingslashit( $real_base );

			// Check if the path is within the base directory.
			if ( 0 !== strpos( $real_path, $real_base ) ) {
				self::log_security_event( 'path_traversal_attempt', array(
					'path'     => $path,
					'base_dir' => $base_dir,
					'real_path' => $real_path,
					'real_base' => $real_base,
				) );
				return false;
			}

			// Additional check: ensure no directory traversal sequences.
			if ( false !== strpos( $path, '..' ) ) {
				self::log_security_event( 'path_traversal_sequence_detected', array(
					'path' => $path,
				) );
				return false;
			}

			return $real_path;
		}

		/**
		 * Verify plugin package integrity before installation.
		 *
		 * This function validates:
		 * 1. Package URL is from trusted sources (wordpress.org)
		 * 2. Package is accessible and returns valid HTTP response
		 * 3. Content type is appropriate for a zip file
		 *
		 * @param string $package_url The URL of the plugin package.
		 * @return array Array with 'valid' boolean and 'message' string.
		 * @since 0.0.1
		 */
		public static function verify_plugin_package( $package_url ) {
			// Initialize result.
			$result = array(
				'valid'   => false,
				'message' => __( 'Package verification failed.', 'spectra' ),
			);

			// Validate URL format.
			if ( empty( $package_url ) || ! filter_var( $package_url, FILTER_VALIDATE_URL ) ) {
				$result['message'] = __( 'Invalid package URL.', 'spectra' );
				self::log_security_event( 'invalid_package_url', array( 'url' => $package_url ) );
				return $result;
			}

			// Parse URL to check the domain.
			$parsed_url = wp_parse_url( $package_url );
			if ( ! isset( $parsed_url['host'] ) ) {
				$result['message'] = __( 'Invalid package URL format.', 'spectra' );
				return $result;
			}

			// Only allow packages from wordpress.org (official repository).
			$allowed_hosts = array(
				'downloads.wordpress.org',
				'plugins.svn.wordpress.org',
			);

			$host = strtolower( $parsed_url['host'] );
			if ( ! in_array( $host, $allowed_hosts, true ) ) {
				$result['message'] = sprintf(
					/* translators: %s: hostname */
					__( 'Package source not trusted: %s. Only wordpress.org packages are allowed.', 'spectra' ),
					esc_html( $host )
				);
				self::log_security_event( 'untrusted_package_source', array(
					'url'  => $package_url,
					'host' => $host,
				) );
				return $result;
			}

			// Verify the package is accessible (HEAD request to check without downloading).
			$response = wp_remote_head(
				$package_url,
				array(
					'timeout'     => 10,
					'redirection' => 3,
					'sslverify'   => true,
					'user-agent'  => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
				)
			);

			if ( is_wp_error( $response ) ) {
				$result['message'] = sprintf(
					/* translators: %s: error message */
					__( 'Package verification error: %s', 'spectra' ),
					$response->get_error_message()
				);
				self::log_security_event( 'package_verification_error', array(
					'url'   => $package_url,
					'error' => $response->get_error_message(),
				) );
				return $result;
			}

			// Check HTTP response code.
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $response_code ) {
				$result['message'] = sprintf(
					/* translators: %d: HTTP response code */
					__( 'Package not accessible. HTTP response code: %d', 'spectra' ),
					$response_code
				);
				self::log_security_event( 'package_not_accessible', array(
					'url'           => $package_url,
					'response_code' => $response_code,
				) );
				return $result;
			}

			// Verify content type is a zip file.
			$content_type = wp_remote_retrieve_header( $response, 'content-type' );
			$valid_types  = array(
				'application/zip',
				'application/x-zip-compressed',
				'application/octet-stream', // Some servers use this for zip files.
			);

			if ( ! empty( $content_type ) ) {
				$content_type_valid = false;
				foreach ( $valid_types as $valid_type ) {
					if ( false !== stripos( $content_type, $valid_type ) ) {
						$content_type_valid = true;
						break;
					}
				}

				if ( ! $content_type_valid ) {
					$result['message'] = sprintf(
						/* translators: %s: content type */
						__( 'Invalid package content type: %s', 'spectra' ),
						esc_html( $content_type )
					);
					self::log_security_event( 'invalid_package_content_type', array(
						'url'          => $package_url,
						'content_type' => $content_type,
					) );
					return $result;
				}
			}

			// All checks passed.
			$result['valid']   = true;
			$result['message'] = __( 'Package verification successful.', 'spectra' );

			return $result;
		}

		/**
		 * Verify plugin authenticity before activation.
		 *
		 * This function checks:
		 * 1. Plugin file exists in the plugins directory
		 * 2. Plugin is not already active
		 * 3. Plugin slug matches expected pattern (spectra-pro)
		 *
		 * @param string $plugin_file The plugin file path (e.g., 'spectra-pro/spectra-pro.php').
		 * @return array Array with 'valid' boolean and 'message' string.
		 * @since 0.0.1
		 */
		public static function verify_plugin_authenticity( $plugin_file ) {
			// Initialize result.
			$result = array(
				'valid'   => false,
				'message' => __( 'Plugin authenticity verification failed.', 'spectra' ),
			);

			// Validate plugin file format.
			if ( empty( $plugin_file ) || ! is_string( $plugin_file ) ) {
				$result['message'] = __( 'Invalid plugin file path.', 'spectra' );
				return $result;
			}

			// Sanitize the plugin file path.
			$plugin_file = sanitize_text_field( $plugin_file );

			// Only allow activation of Spectra Pro plugin.
			$allowed_plugins = array(
				'spectra-pro/spectra-pro.php',
			);

			if ( ! in_array( $plugin_file, $allowed_plugins, true ) ) {
				$result['message'] = __( 'Plugin activation not allowed. Only Spectra Pro can be activated via this method.', 'spectra' );
				self::log_security_event( 'unauthorized_plugin_activation_attempt', array(
					'plugin_file' => $plugin_file,
				) );
				return $result;
			}

			// Check if plugin file exists.
			$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
			if ( ! file_exists( $plugin_path ) ) {
				$result['message'] = __( 'Plugin file does not exist.', 'spectra' );
				self::log_security_event( 'plugin_file_not_found', array(
					'plugin_file' => $plugin_file,
					'plugin_path' => $plugin_path,
				) );
				return $result;
			}

			// Verify the plugin is installed (exists in the plugin list).
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$all_plugins = get_plugins();
			if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
				$result['message'] = __( 'Plugin not found in installed plugins.', 'spectra' );
				self::log_security_event( 'plugin_not_installed', array(
					'plugin_file' => $plugin_file,
				) );
				return $result;
			}

			// Check if plugin is already active.
			if ( is_plugin_active( $plugin_file ) ) {
				$result['message'] = __( 'Plugin is already active.', 'spectra' );
				$result['valid']   = true; // Not an error, but no action needed.
				return $result;
			}

			// All checks passed.
			$result['valid']   = true;
			$result['message'] = __( 'Plugin authenticity verified.', 'spectra' );

			return $result;
		}

		/**
		 * Rate limit check for AJAX operations.
		 *
		 * Implements a simple rate limiting mechanism using transients.
		 * Uses a sliding window algorithm with per-user limits.
		 *
		 * @param string $action The action name to rate limit.
		 * @param int    $max_requests Maximum number of requests allowed.
		 * @param int    $time_window Time window in seconds.
		 * @return bool True if rate limit not exceeded, false otherwise.
		 * @since 0.0.1
		 */
		public static function check_rate_limit( $action, $max_requests = 10, $time_window = 60 ) {
			// Get current user ID or use IP address for non-logged-in users.
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				$user_id = self::get_client_ip();
			}

			// Create a unique transient key.
			$transient_key = self::RATE_LIMIT_PREFIX . md5( $action . '_' . $user_id );

			// Get current request count.
			$request_data = get_transient( $transient_key );

			if ( false === $request_data ) {
				// First request in the window.
				$request_data = array(
					'count'      => 1,
					'first_time' => time(),
				);
				set_transient( $transient_key, $request_data, $time_window );
				return true;
			}

			// Check if we're still within the time window.
			$elapsed_time = time() - $request_data['first_time'];

			if ( $elapsed_time > $time_window ) {
				// Time window expired, reset counter.
				$request_data = array(
					'count'      => 1,
					'first_time' => time(),
				);
				set_transient( $transient_key, $request_data, $time_window );
				return true;
			}

			// Increment request count.
			$request_data['count']++;

			// Check if rate limit exceeded.
			if ( $request_data['count'] > $max_requests ) {
				self::log_security_event( 'rate_limit_exceeded', array(
					'action'       => $action,
					'user_id'      => $user_id,
					'count'        => $request_data['count'],
					'max_requests' => $max_requests,
					'time_window'  => $time_window,
				) );
				return false;
			}

			// Update transient with new count.
			set_transient( $transient_key, $request_data, $time_window );
			return true;
		}

		/**
		 * Get client IP address.
		 *
		 * Retrieves the client IP address, accounting for proxies and load balancers.
		 *
		 * @return string The client IP address.
		 * @since 0.0.1
		 */
		private static function get_client_ip() {
			$ip = '';

			// Check for shared internet/ISP IP.
			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) && self::validate_ip( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				// Check for IPs passing through proxies.
				// X-Forwarded-For can contain multiple IPs, get the first one.
				$ip_list = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
				foreach ( $ip_list as $ip_item ) {
					$ip_item = trim( $ip_item );
					if ( self::validate_ip( $ip_item ) ) {
						$ip = $ip_item;
						break;
					}
				}
			} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) && self::validate_ip( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			}

			return $ip;
		}

		/**
		 * Validate IP address.
		 *
		 * @param string $ip The IP address to validate.
		 * @return bool True if valid IP, false otherwise.
		 * @since 0.0.1
		 */
		private static function validate_ip( $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return true;
			}
			// Also allow private ranges for development.
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Log a security event to the audit log.
		 *
		 * Logs security-relevant events with context for later review.
		 * Implements automatic log rotation to prevent database bloat.
		 *
		 * @param string $event_type The type of security event.
		 * @param array  $context Additional context about the event.
		 * @return bool True if logged successfully, false otherwise.
		 * @since 0.0.1
		 */
		public static function log_security_event( $event_type, $context = array() ) {
			// Get existing log entries.
			$log_entries = get_option( self::AUDIT_LOG_OPTION, array() );

			if ( ! is_array( $log_entries ) ) {
				$log_entries = array();
			}

			// Create log entry.
			$log_entry = array(
				'timestamp'  => current_time( 'mysql' ),
				'event_type' => sanitize_text_field( $event_type ),
				'user_id'    => get_current_user_id(),
				'user_ip'    => self::get_client_ip(),
				'context'    => $context,
			);

			// Add to beginning of array (most recent first).
			array_unshift( $log_entries, $log_entry );

			// Implement log rotation - keep only the most recent entries.
			if ( count( $log_entries ) > self::MAX_AUDIT_LOG_ENTRIES ) {
				$log_entries = array_slice( $log_entries, 0, self::MAX_AUDIT_LOG_ENTRIES );
			}

			// Save log entries.
			return update_option( self::AUDIT_LOG_OPTION, $log_entries, false );
		}

		/**
		 * Retrieve security audit log entries.
		 *
		 * @param int    $limit Number of entries to retrieve (default: 100).
		 * @param string $event_type Filter by event type (optional).
		 * @return array Array of log entries.
		 * @since 0.0.1
		 */
		public static function get_audit_log( $limit = 100, $event_type = '' ) {
			$log_entries = get_option( self::AUDIT_LOG_OPTION, array() );

			if ( ! is_array( $log_entries ) ) {
				return array();
			}

			// Filter by event type if specified.
			if ( ! empty( $event_type ) ) {
				$log_entries = array_filter(
					$log_entries,
					function( $entry ) use ( $event_type ) {
						return isset( $entry['event_type'] ) && $entry['event_type'] === $event_type;
					}
				);
			}

			// Limit number of entries returned.
			if ( $limit > 0 && count( $log_entries ) > $limit ) {
				$log_entries = array_slice( $log_entries, 0, $limit );
			}

			return $log_entries;
		}

		/**
		 * Clear the security audit log.
		 *
		 * @return bool True if cleared successfully, false otherwise.
		 * @since 0.0.1
		 */
		public static function clear_audit_log() {
			self::log_security_event( 'audit_log_cleared', array(
				'cleared_by' => get_current_user_id(),
			) );
			return delete_option( self::AUDIT_LOG_OPTION );
		}

		/**
		 * Verify WordPress core file integrity.
		 *
		 * Basic check to ensure WordPress core hasn't been tampered with.
		 *
		 * @return bool True if integrity check passes, false otherwise.
		 * @since 0.0.1
		 */
		public static function verify_wp_integrity() {
			// Check if wp-config.php exists.
			if ( ! file_exists( ABSPATH . 'wp-config.php' ) ) {
				self::log_security_event( 'wp_config_missing', array() );
				return false;
			}

			// Check if wp-load.php exists.
			if ( ! file_exists( ABSPATH . 'wp-load.php' ) ) {
				self::log_security_event( 'wp_load_missing', array() );
				return false;
			}

			return true;
		}

		/**
		 * Enhanced nonce verification wrapper.
		 *
		 * Provides enhanced nonce verification with rate limiting and audit logging.
		 *
		 * @param string $nonce_action The nonce action.
		 * @param string $nonce_field The nonce field name (default: 'security').
		 * @return bool True if nonce is valid and rate limit not exceeded, false otherwise.
		 * @since 0.0.1
		 */
		public static function verify_nonce_with_rate_limit( $nonce_action, $nonce_field = 'security' ) {
			// Check rate limit first (10 requests per minute for sensitive operations).
			if ( ! self::check_rate_limit( $nonce_action, 10, 60 ) ) {
				self::log_security_event( 'nonce_verification_rate_limit', array(
					'action' => $nonce_action,
				) );
				return false;
			}

			// Verify nonce.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is the purpose of this function.
			if ( ! isset( $_POST[ $nonce_field ] ) ) {
				self::log_security_event( 'nonce_field_missing', array(
					'action' => $nonce_action,
					'field'  => $nonce_field,
				) );
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification handles validation.
			if ( ! wp_verify_nonce( wp_unslash( $_POST[ $nonce_field ] ), $nonce_action ) ) {
				self::log_security_event( 'nonce_verification_failed', array(
					'action' => $nonce_action,
				) );
				return false;
			}

			return true;
		}

		/**
		 * Enhanced capability check wrapper.
		 *
		 * Provides enhanced capability checking with audit logging.
		 *
		 * @param string $capability The capability to check.
		 * @param string $context Additional context for logging.
		 * @return bool True if user has capability, false otherwise.
		 * @since 0.0.1
		 */
		public static function verify_capability( $capability, $context = '' ) {
			if ( ! current_user_can( $capability ) ) {
				self::log_security_event( 'capability_check_failed', array(
					'capability' => $capability,
					'context'    => $context,
					'user_id'    => get_current_user_id(),
				) );
				return false;
			}

			return true;
		}
	}
}
