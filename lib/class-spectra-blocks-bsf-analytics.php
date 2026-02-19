<?php
/**
 * BSF Analytics library loader for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BSF_Analytics_Loader' ) ) {
	require_once __DIR__ . '/bsf-analytics/class-bsf-analytics-loader.php';
}

if ( class_exists( 'BSF_Analytics_Loader' ) && is_callable( 'BSF_Analytics_Loader::get_instance' ) ) {
	$spectra_blocks_bsf_analytics = BSF_Analytics_Loader::get_instance();

	$spectra_blocks_bsf_analytics->set_entity(
		array(
			'spectra-blocks' => array(
				'product_name'        => 'Spectra Blocks',
				'path'                => __DIR__ . '/bsf-analytics',
				'author'              => 'Spectra Blocks by Brainstorm Force',
				'time_to_display'     => '+24 hours',
				'deactivation_survey' => apply_filters(
					'spectra_blocks_deactivation_survey_data',
					array(
						array(
							'id'                => 'deactivation-survey-spectra-blocks',
							'popup_logo'        => esc_url( SPECTRA_BLOCKS_URL . 'assets/images/logos/spectra.svg' ),
							'plugin_slug'       => 'spectra-blocks',
							'popup_title'       => 'Quick Feedback',
							'support_url'       => 'https://wpspectra.com/contact/',
							'popup_description' => 'If you have a moment, please share why you are deactivating Spectra Blocks:',
							'show_on_screens'   => array( 'plugins' ),
							'plugin_version'    => SPECTRA_BLOCKS_VER,
						),
					)
				),
				'hide_optin_checkbox' => true,
			),
		)
	);
}
