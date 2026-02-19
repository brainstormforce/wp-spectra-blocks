<?php
/**
 * NPS Survey library loader for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Spectra_Blocks_Nps_Survey' ) ) :

	/**
	 * Loads the latest NPS Survey library available in the environment.
	 *
	 * @since 1.0.0
	 */
	class Spectra_Blocks_Nps_Survey {

		/**
		 * Singleton instance.
		 *
		 * @var Spectra_Blocks_Nps_Survey|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return Spectra_Blocks_Nps_Survey
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
		 * Check for the latest version of the NPS Survey library.
		 *
		 * @return void
		 */
		public function version_check() {
			$file = realpath( __DIR__ . '/nps-survey/version.json' );

			if ( ! is_file( $file ) ) {
				return;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$file_data = json_decode( file_get_contents( $file ), true );

			global $nps_survey_version, $nps_survey_init;

			$path    = realpath( __DIR__ . '/nps-survey/nps-survey.php' );
			$version = isset( $file_data['nps-survey'] ) ? $file_data['nps-survey'] : 0;

			if ( null === $nps_survey_version ) {
				$nps_survey_version = '1.0.0';
			}

			if ( version_compare( $version, $nps_survey_version, '>=' ) ) {
				$nps_survey_version = $version;
				$nps_survey_init    = $path;
			}
		}

		/**
		 * Load the latest NPS Survey library.
		 *
		 * @return void
		 */
		public function load() {
			global $nps_survey_init;
			if ( ! empty( $nps_survey_init ) && is_file( realpath( $nps_survey_init ) ) ) {
				include_once realpath( $nps_survey_init );
			}
		}
	}

	Spectra_Blocks_Nps_Survey::get_instance();

endif;
