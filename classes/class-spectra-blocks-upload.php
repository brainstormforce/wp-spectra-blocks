<?php
/**
 * Upload directory helper for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides the upload directory path for CSS cache files.
 */
class Spectra_Blocks_Upload {

	/**
	 * Get the upload directory path for Spectra Blocks CSS files.
	 *
	 * @return string Absolute path to upload directory (with trailing slash).
	 */
	public static function get_dir() {
		$upload_dir = wp_upload_dir();
		$path       = trailingslashit( $upload_dir['basedir'] ) . 'spectra-blocks/';
		wp_mkdir_p( $path );
		return $path;
	}

	/**
	 * Get the upload directory URL for Spectra Blocks CSS files.
	 *
	 * @return string URL to upload directory (with trailing slash).
	 */
	public static function get_url() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['baseurl'] ) . 'spectra-blocks/';
	}
}
