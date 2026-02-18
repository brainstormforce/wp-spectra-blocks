<?php
/**
 * Plugin Name: Spectra Blocks
 * Plugin URI: https://www.brainstormforce.com
 * Author: Brainstorm Force
 * Author URI: https://www.brainstormforce.com
 * Version: 0.0.1
 * Description: The Spectra extends the Gutenberg functionality with several unique and feature-rich blocks that help build websites faster.
 * Text Domain: spectra-blocks
 * Domain Path: /languages
 *
 * @package Spectra
 */

define( 'SPECTRA_FILE', __FILE__ );
define( 'SPECTRA_ROOT', dirname( plugin_basename( SPECTRA_FILE ) ) );
define( 'SPECTRA_PLUGIN_NAME', 'Spectra' );
define( 'SPECTRA_PLUGIN_SHORT_NAME', 'Spectra' );
define( 'SPECTRA_BLOCKS_PRO_PLUGIN_URL', 'https://wpspectra.com/pro' );

if ( ! version_compare( PHP_VERSION, '5.6', '>=' ) ) {
	add_action( 'admin_notices', 'spectra_blocks_fail_php_version' );
} elseif ( ! version_compare( get_bloginfo( 'version' ), '4.7', '>=' ) ) {
	add_action( 'admin_notices', 'spectra_blocks_fail_wp_version' );
} else {
	require_once 'classes/class-spectra-blocks-loader.php';
}

/**
 * Spectra admin notice for minimum PHP version.
 *
 * Warning when the site doesn't have the minimum required PHP version.
 *
 * @since 0.0.1
 *
 * @return void
 */
function spectra_blocks_fail_php_version() {
	/* translators: %s: PHP version */
	$message      = sprintf( esc_html__( 'Spectra requires PHP version %s+, plugin is currently NOT RUNNING.', 'spectra' ), '5.6' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}


/**
 * Spectra admin notice for minimum WordPress version.
 *
 * Warning when the site doesn't have the minimum required WordPress version.
 *
 * @since 0.0.1
 *
 * @return void
 */
function spectra_blocks_fail_wp_version() {
	/* translators: %s: WordPress version */
	$message      = sprintf( esc_html__( 'Spectra requires WordPress version %s+. Because you are using an earlier version, the plugin is currently NOT RUNNING.', 'spectra' ), '4.7' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}
