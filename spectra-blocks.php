<?php
/**
 * Plugin Name: Spectra Blocks
 * Plugin URI: https://wpspectra.com
 * Author: Brainstorm Force
 * Author URI: https://www.brainstormforce.com
 * Version: 0.0.5
 * Description: A fresh, clean Gutenberg block plugin built on Spectra V3 with modern standards.
 * Text Domain: spectra-blocks
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.6
 * Tested up to: 6.9
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'SPECTRA_BLOCKS_FILE', __FILE__ );
define( 'SPECTRA_BLOCKS_DIR', plugin_dir_path( SPECTRA_BLOCKS_FILE ) );
define( 'SPECTRA_BLOCKS_URL', plugins_url( '/', SPECTRA_BLOCKS_FILE ) );
define( 'SPECTRA_BLOCKS_VER', '0.0.5' );
define( 'SPECTRA_BLOCKS_SLUG', 'spectra-blocks' );

// PHP version check.
if ( ! version_compare( PHP_VERSION, '8.1', '>=' ) ) {
	add_action( 'admin_notices', 'spectra_blocks_fail_php_version' );
	return;
}

// WP version check.
if ( ! version_compare( get_bloginfo( 'version' ), '6.6', '>=' ) ) {
	add_action( 'admin_notices', 'spectra_blocks_fail_wp_version' );
	return;
}

require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-loader.php';

/**
 * Admin notice: PHP version too low.
 */
function spectra_blocks_fail_php_version() {
	/* translators: %s: PHP version */
	$message = sprintf( esc_html__( 'Spectra Blocks requires PHP version %s+. The plugin is currently NOT RUNNING.', 'spectra-blocks' ), '8.1' );
	printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( wpautop( $message ) ) );
}

/**
 * Admin notice: WP version too low.
 */
function spectra_blocks_fail_wp_version() {
	/* translators: %s: WordPress version */
	$message = sprintf( esc_html__( 'Spectra Blocks requires WordPress version %s+. The plugin is currently NOT RUNNING.', 'spectra-blocks' ), '6.6' );
	printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( wpautop( $message ) ) );
}
