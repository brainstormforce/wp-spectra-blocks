<?php
/**
 * Plugin Name: Spectra plugin
 * Plugin URI: https://wpspectra.com
 * Author: Brainstorm Force
 * Author URI: https://www.brainstormforce.com
 * Version: 1.0.0
 * Description: Spectra provides powerful Gutenberg blocks to build beautiful websites faster.
 * Text Domain: spectra
 * Domain Path: /languages
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define plugin constants.
 */
define( 'SPECTRA_FILE', __FILE__ );
define( 'SPECTRA_DIR', plugin_dir_path( SPECTRA_FILE ) );
define( 'SPECTRA_URL', plugins_url( '/', SPECTRA_FILE ) );
define( 'SPECTRA_VER', '1.0.0' );
define( 'SPECTRA_PLUGIN_NAME', 'Spectra' );

/**
 * Check PHP version requirement.
 */
if ( ! version_compare( PHP_VERSION, '7.4', '>=' ) ) {
	add_action( 'admin_notices', 'spectra_fail_php_version' );
	return;
}

/**
 * Check WordPress version requirement.
 */
if ( ! version_compare( get_bloginfo( 'version' ), '6.6', '>=' ) ) {
	add_action( 'admin_notices', 'spectra_fail_wp_version' );
	return;
}

/**
 * Spectra admin notice for minimum PHP version.
 *
 * @since 1.0.0
 * @return void
 */
function spectra_fail_php_version() {
	/* translators: %s: PHP version */
	$message      = sprintf( esc_html__( 'Spectra requires PHP version %s+, plugin is currently NOT RUNNING.', 'spectra' ), '7.4' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

/**
 * Spectra admin notice for minimum WordPress version.
 *
 * @since 1.0.0
 * @return void
 */
function spectra_fail_wp_version() {
	/* translators: %s: WordPress version */
	$message      = sprintf( esc_html__( 'Spectra requires WordPress version %s+. Because you are using an earlier version, the plugin is currently NOT RUNNING.', 'spectra' ), '6.6' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

/**
 * Include the autoloader.
 */
$autoload_file = SPECTRA_DIR . 'includes/autoload.php';

if ( file_exists( $autoload_file ) ) {
	require_once $autoload_file;
} else {
	wp_die( esc_html__( 'Required file missing. Plugin cannot be initialized.', 'spectra' ) );
}

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function spectra_init() {
	( \Spectra\BlockManager::instance() )->init();
	( \Spectra\AssetLoader::instance() )->init();
	( \Spectra\ExtensionManager::instance() )->init();
}
add_action( 'plugins_loaded', 'spectra_init' );
