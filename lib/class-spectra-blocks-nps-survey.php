<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Spectra_Nps_Survey' ) ) :

	/**
	 * Admin
	 */
	class Spectra_Nps_Survey {
		/**
		 * Instance
		 *
		 * @since 0.0.1
		 * @var (Object) Spectra_Nps_Survey
		 */
		private static $instance = null;

		/**
		 * Get Instance
		 *
		 * @since 0.0.1
		 *
		 * @return object Class object.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @since 0.0.1
		 */
		private function __construct() {
			$this->version_check();
			add_action( 'init', array( $this, 'load' ), 999 );
		}

		/**
		 * Version Check
		 *
		 * @return void
		 */
		public function version_check() {

			$file = realpath( dirname( __DIR__ ) . '/lib/nps-survey/version.json' );

			// Is file exist?
			if ( is_file( $file ) ) {
				
                $file_data = json_decode( file_get_contents( $file ), true );
				
				global $nps_survey_version, $nps_survey_init;
				
                $path = realpath( dirname( __DIR__ ) . '/lib/nps-survey/nps-survey.php' );
				
                $version = isset( $file_data['nps-survey'] ) ? $file_data['nps-survey'] : 0;

				if ( null === $nps_survey_version ) {
					$nps_survey_version = '1.0.0';
				}

				// Compare versions.
				if ( version_compare( $version, $nps_survey_version, '>=' ) ) {
					$nps_survey_version = $version;
					$nps_survey_init = $path;
				}
			}
		}

		/**
		 * Load latest plugin
		 *
		 * @return void
		 */
		public function load() {
			global $nps_survey_version, $nps_survey_init;
			if ( is_file( realpath( $nps_survey_init ) ) ) {
				include_once realpath( $nps_survey_init );
			}
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Spectra_Nps_Survey::get_instance();

endif;