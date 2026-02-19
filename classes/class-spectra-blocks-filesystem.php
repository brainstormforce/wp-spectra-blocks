<?php
/**
 * Filesystem helper for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns an initialized WP_Filesystem instance.
 *
 * @return WP_Filesystem_Base|null WP_Filesystem instance or null on failure.
 */
function spectra_blocks_filesystem() {
	global $wp_filesystem;

	if ( empty( $wp_filesystem ) ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
	}

	return $wp_filesystem;
}
