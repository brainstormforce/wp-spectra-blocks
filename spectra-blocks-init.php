<?php
/**
 * Load the Spectra Blocks Requirements.
 *
 * @package SpectraBlocks
 */

use SpectraBlocks\AssetLoader;
use SpectraBlocks\BlockManager;
use SpectraBlocks\ExtensionManager;
use SpectraBlocks\AnalyticsManager;
use SpectraBlocks\AbilitiesManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Include the autoloaders safely.
 */
$spectra_blocks_autoload_file     = SPECTRA_BLOCKS_DIR . 'includes/autoload.php';
$spectra_blocks_composer_autoload = SPECTRA_BLOCKS_DIR . 'vendor/autoload.php';

if ( file_exists( $spectra_blocks_autoload_file ) ) {
	require_once $spectra_blocks_autoload_file;
} else {
	wp_die( esc_html__( 'Required file missing. Plugin cannot be initialized.', 'spectra-blocks' ) ); // Stop execution with a message.
}

if ( file_exists( $spectra_blocks_composer_autoload ) ) {
	require_once $spectra_blocks_composer_autoload;
}

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function spectra_blocks_init() {
	( BlockManager::instance() )->init();
	( AssetLoader::instance() )->init();
	( ExtensionManager::instance() )->init();
	( AnalyticsManager::instance() )->init();
	( AbilitiesManager::instance() )->init();
}

// Call directly since this file is loaded inside a plugins_loaded callback.
spectra_blocks_init();

/**
 * Enable SVG uploads for Spectra Blocks with server-side sanitization.
 */
add_action(
	'init',
	function () {
		// Enable SVG uploads for users with unfiltered_html capability.
		add_filter(
			'upload_mimes',
			function ( $mimes ) {
				if ( current_user_can( 'unfiltered_html' ) ) {
					$mimes['svg'] = 'image/svg+xml';
				}
				return $mimes;
			}
		);

		// Fix WordPress SVG detection issues.
		add_filter(
			'wp_check_filetype_and_ext',
			function ( $data, $file, $filename, $mimes ) {
				if ( ! current_user_can( 'unfiltered_html' ) ) {
					return $data;
				}

				$filetype = wp_check_filetype( $filename, $mimes );

				if ( 'svg' === $filetype['ext'] ) {
					$data['ext']  = 'svg';
					$data['type'] = 'image/svg+xml';
				}

				return $data;
			},
			10,
			4
		);

		// SVG upload sanitization using enshrined/svg-sanitize.
		add_filter(
			'wp_handle_upload_prefilter',
			function ( $file ) {
				if ( 'image/svg+xml' !== $file['type'] ) {
					return $file;
				}

				$svg_content = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( empty( $svg_content ) ) {
					$file['error'] = __( 'Invalid SVG file.', 'spectra-blocks' );
					return $file;
				}

				// Sanitize using enshrined/svg-sanitize to strip scripts and external entity refs.
				// If the sanitizer is unavailable (e.g. vendor/ not installed), refuse the upload
				// rather than let a potentially malicious SVG through untouched.
				if ( ! class_exists( '\enshrined\svgSanitize\Sanitizer' ) ) {
					$file['error'] = __( 'SVG upload is unavailable: the SVG sanitizer library is missing. Please contact the site administrator.', 'spectra-blocks' );
					return $file;
				}

				$sanitizer = new \enshrined\svgSanitize\Sanitizer();
				$clean_svg = $sanitizer->sanitize( $svg_content );
				if ( false === $clean_svg || empty( $clean_svg ) ) {
					$file['error'] = __( 'SVG file failed security check.', 'spectra-blocks' );
					return $file;
				}
				file_put_contents( $file['tmp_name'], $clean_svg ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

				return $file;
			},
			10,
			1
		);
	}
);
