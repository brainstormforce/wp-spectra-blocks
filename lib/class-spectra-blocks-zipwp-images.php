<?php
/**
 * ZipWP Images library loader for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Spectra_Blocks_Zipwp_Images' ) ) :

	/**
	 * Loads the latest ZipWP Images library available in the environment.
	 *
	 * @since 1.0.0
	 */
	class Spectra_Blocks_Zipwp_Images {

		/**
		 * Singleton instance.
		 *
		 * @var Spectra_Blocks_Zipwp_Images|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return Spectra_Blocks_Zipwp_Images
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
			add_action( 'init', array( $this, 'load' ) );
		}

		/**
		 * Check for the latest version of the ZipWP Images library.
		 *
		 * @return void
		 */
		public function version_check() {
			$file = realpath( __DIR__ . '/zipwp-images/version.json' );

			if ( ! is_file( $file ) ) {
				return;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$file_data = json_decode( file_get_contents( $file ), true );

			global $zipwp_images_version, $zipwp_images_init;

			$path    = realpath( __DIR__ . '/zipwp-images/zipwp-images.php' );
			$version = isset( $file_data['zipwp-images'] ) ? $file_data['zipwp-images'] : 0;

			if ( null === $zipwp_images_version ) {
				$zipwp_images_version = '1.0.0';
			}

			if ( version_compare( $version, $zipwp_images_version, '>=' ) ) {
				$zipwp_images_version = $version;
				$zipwp_images_init    = $path;
			}
		}

		/**
		 * Load the latest ZipWP Images library.
		 *
		 * @return void
		 */
		public function load() {
			global $zipwp_images_init;
			if ( ! empty( $zipwp_images_init ) && is_file( realpath( $zipwp_images_init ) ) ) {
				include_once realpath( $zipwp_images_init );
			}
		}
	}

	Spectra_Blocks_Zipwp_Images::get_instance();

endif;
