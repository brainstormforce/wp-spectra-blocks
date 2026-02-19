<?php
/**
 * Zip AI library loader for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Spectra_Blocks_Zip_AI' ) ) :

	/**
	 * Loads the latest Zip AI library available in the environment.
	 *
	 * @since 1.0.0
	 */
	class Spectra_Blocks_Zip_AI {

		/**
		 * Singleton instance.
		 *
		 * @var Spectra_Blocks_Zip_AI|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return Spectra_Blocks_Zip_AI
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
			add_action( 'plugins_loaded', array( $this, 'load' ), 15 );
		}

		/**
		 * Check for the latest version of the Zip AI library.
		 *
		 * @return void
		 */
		public function version_check() {
			$file = realpath( __DIR__ . '/zip-ai/version.json' );

			if ( ! is_file( $file ) ) {
				return;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$file_data = json_decode( file_get_contents( $file ), true );

			global $zip_ai_version, $zip_ai_path;

			$path    = realpath( __DIR__ . '/zip-ai/zip-ai.php' );
			$version = isset( $file_data['zip-ai'] ) ? $file_data['zip-ai'] : 0;

			if ( null === $zip_ai_version ) {
				$zip_ai_version = '1.0.0';
			}

			if ( version_compare( $version, $zip_ai_version, '>' ) ) {
				$zip_ai_version = $version;
				$zip_ai_path    = $path;
			}
		}

		/**
		 * Load the latest Zip AI library.
		 *
		 * @return void
		 */
		public function load() {
			global $zip_ai_path;
			if ( ! is_null( $zip_ai_path ) && is_file( realpath( $zip_ai_path ) ) ) {
				include_once realpath( $zip_ai_path );
			}
		}
	}

	Spectra_Blocks_Zip_AI::get_instance();

endif;
