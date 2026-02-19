<?php
/**
 * UTM Analytics library loader for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Spectra_Blocks_Utm_Analytics' ) ) :

	/**
	 * Loads the latest UTM Analytics library available in the environment.
	 *
	 * @since 1.0.0
	 */
	class Spectra_Blocks_Utm_Analytics {

		/**
		 * Singleton instance.
		 *
		 * @var Spectra_Blocks_Utm_Analytics|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return Spectra_Blocks_Utm_Analytics
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			$this->version_check();
			add_action( 'init', array( $this, 'load' ), 999 );
		}

		/**
		 * Check for the latest version of the UTM Analytics library.
		 *
		 * @return void
		 */
		public function version_check() {
			$file = realpath( __DIR__ . '/utm-analytics/version.json' );

			if ( ! is_file( $file ) ) {
				return;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$file_data = json_decode( file_get_contents( $file ), true );

			global $utm_analytics_version, $utm_analytics_init;

			$path    = realpath( __DIR__ . '/utm-analytics/bsf-utm-analytics.php' );
			$version = isset( $file_data['bsf-utm-analytics'] ) ? $file_data['bsf-utm-analytics'] : 0;

			if ( null === $utm_analytics_version ) {
				$utm_analytics_version = '0.0.1';
			}

			if ( version_compare( $version, $utm_analytics_version, '>=' ) ) {
				$utm_analytics_version = $version;
				$utm_analytics_init    = $path;
			}
		}

		/**
		 * Load the latest UTM Analytics library.
		 *
		 * @return void
		 */
		public function load() {
			global $utm_analytics_init;
			if ( ! empty( $utm_analytics_init ) && is_file( realpath( $utm_analytics_init ) ) ) {
				include_once realpath( $utm_analytics_init );
			}
		}
	}

	Spectra_Blocks_Utm_Analytics::get_instance();

endif;
