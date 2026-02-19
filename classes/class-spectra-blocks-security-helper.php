<?php
/**
 * Security helper for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides security utility methods.
 */
class Spectra_Blocks_Security_Helper {

	/**
	 * Log a security event to the PHP error log.
	 *
	 * @param string $message  Security event message.
	 * @param array  $context  Additional context data.
	 * @return void
	 */
	public static function log_security_event( $message, $context = array() ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$log_entry = sprintf(
				'[Spectra Blocks Security] %s | Context: %s',
				$message,
				wp_json_encode( $context )
			);
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $log_entry );
		}
	}

	/**
	 * Verify a nonce and die on failure.
	 *
	 * @param string $nonce  The nonce to verify.
	 * @param string $action The nonce action.
	 * @return void
	 */
	public static function verify_nonce( $nonce, $action ) {
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			self::log_security_event( 'Nonce verification failed', array( 'action' => $action ) );
			wp_die( esc_html__( 'Security check failed.', 'spectra-blocks' ) );
		}
	}
}
