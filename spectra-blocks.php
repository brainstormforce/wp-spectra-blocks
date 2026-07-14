<?php
/**
 * Plugin Name: Spectra Blocks
 * Plugin URI: https://wpspectra.com
 * Author: Brainstorm Force
 * Author URI: https://www.brainstormforce.com
 * Version: 1.0.0
 * Description: A fresh, clean Gutenberg block plugin built on Spectra V3 with modern standards.
 * Text Domain: spectra-blocks
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.6
 * Tested up to: 7.0
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
define( 'SPECTRA_BLOCKS_VER', '1.0.0' );
define( 'SPECTRA_BLOCKS_SLUG', 'spectra-blocks' );
define( 'SPECTRA_BLOCKS_ZIP_AI_PLUGIN_SLUG', 'zip-ai' );
define( 'SPECTRA_BLOCKS_ZIP_AI_PLUGIN_FILE', 'zip-ai/zip-ai.php' );
define( 'SPECTRA_BLOCKS_ZIP_AI_PLUGIN_URL', 'https://downloads.wordpress.org/plugin/zip-ai.zip' );
define( 'SPECTRA_BLOCKS_ZIPWP_MIDDLEWARE', 'https://app.zipwp.com/auth/' );
define( 'SPECTRA_BLOCKS_ZIPWP_CREDIT_SERVER', 'https://credits.startertemplates.com/api/' );

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

// Conflict guard: UAGB's spectra-v3 (spectra-3-base branch) registers the same
// Spectra\ PHP namespace and block names. Running both simultaneously causes
// duplicate block registration fatal errors. Show an admin notice instead.
if ( defined( 'SPECTRA_3_FILE' ) ) {
	add_action( 'admin_notices', 'spectra_blocks_fail_uagb_conflict' );
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

/**
 * Admin notice: conflict with UAGB spectra-v3.
 */
function spectra_blocks_fail_uagb_conflict() {
	$message = esc_html__( 'Spectra Blocks cannot run alongside the Spectra V3 blocks bundled in UAGB (spectra-3-base). Both plugins register the same PHP namespace and block names. Please deactivate one of them.', 'spectra-blocks' );
	printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( wpautop( $message ) ) );
}
