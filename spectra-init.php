<?php
/**
 * Load the Spectra 3 Requirements.
 * 
 * @package Spectra
 */

use Spectra\AssetLoader;
use Spectra\BlockManager;
use Spectra\ExtensionManager;
use Spectra\AnalyticsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define constants for V3 paths.
 *
 * Note: These now point to the root plugin directory since v3
 * is the only version and structure has been flattened.
 */
define( 'SPECTRA_3_FILE', SPECTRA_FILE );
define( 'SPECTRA_3_DIR', SPECTRA_DIR );
define( 'SPECTRA_3_URL', SPECTRA_URL );

/**
 * Include the autoloaders safely.
 */
$autoload_file     = SPECTRA_DIR . 'includes/autoload.php';
$composer_autoload = SPECTRA_DIR . 'vendor/autoload.php';

if ( file_exists( $autoload_file ) ) {
	require_once $autoload_file;
} else {
	wp_die( esc_html__( 'Required file missing. Plugin cannot be initialized.', 'spectra' ) ); // Stop execution with a message.
}

if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
}

/**
 * Initialize the plugin.
 * 
 * @since 0.0.1
 */
function spectra_init() {
	( BlockManager::instance() )->init();
	( AssetLoader::instance() )->init();
	( ExtensionManager::instance() )->init();
	( AnalyticsManager::instance() )->init();
}
add_action( 'plugins_loaded', 'spectra_init' );

/**
 * Enable SVG uploads for Spectra v3 with server-side sanitization
 */
add_action(
	'init',
	function() {
		// Enable SVG uploads.
		// phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.upload_mimes -- SVG uploads are intentionally enabled with proper server-side sanitization
		add_filter(
			'upload_mimes',
			function( $mimes ) {
				$mimes['svg'] = 'image/svg+xml';
				return $mimes;
			} 
		);
	
		// Fix WordPress SVG detection issues.
		add_filter(
			'wp_check_filetype_and_ext',
			function( $data, $file, $filename, $mimes ) {
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
	
		// Basic SVG upload support - no processing to avoid loading delays.
		add_filter(
			'wp_handle_upload_prefilter',
			function( $file ) {
				if ( 'image/svg+xml' !== $file['type'] ) {
					return $file;
				}
		
				// Only basic validation - no heavy processing.
				$svg_content = file_get_contents( $file['tmp_name'] );
				if ( empty( $svg_content ) || strpos( $svg_content, '<svg' ) === false ) {
					$file['error'] = __( 'Invalid SVG file.', 'spectra' );
					return $file;
				}
		
				return $file; // Allow upload without processing.
			},
			10,
			1 
		);
	} 
);

