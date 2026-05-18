<?php
/**
 * Spectra Blocks — NPS Survey wrapper.
 *
 * Bootstraps the bundled nps-survey library.
 *
 * @since x.x.x
 * @package Spectra_Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'NPS_SURVEY_FILE' ) ) {
	$nps_survey_path = realpath( __DIR__ . '/nps-survey/nps-survey.php' );
	if ( $nps_survey_path && is_file( $nps_survey_path ) ) {
		require_once $nps_survey_path;
	}
}
