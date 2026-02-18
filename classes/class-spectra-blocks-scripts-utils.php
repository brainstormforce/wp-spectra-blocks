<?php
/**
 * Spectra Scripts Utils.
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Spectra_Scripts_Utils.
 */
final class Spectra_Scripts_Utils {

	/**
	 * Legacy v2 block dependency enqueuing.
	 *
	 * V2 blocks have been removed from spectra-blocks. This method is kept as a
	 * no-op stub because it is still called from editor_assets() and
	 * class-spectra-blocks-post-assets.php.  V3 blocks register their own
	 * dependencies via block.json metadata.
	 *
	 * @since 0.0.1
	 */
	public static function enqueue_blocks_dependency_both() {
		// No-op — legacy v2 block dependencies removed.
	}

	/**
	 * Enqueue block styles.
	 *
	 * @since 0.0.1
	 */
	public static function enqueue_blocks_styles() {

		$wp_upload_dir = Spectra_Helper::get_uag_upload_dir_path();

		if ( file_exists( $wp_upload_dir . 'custom-style-blocks.css' ) ) {

			$wp_upload_url = Spectra_Helper::get_uag_upload_url_path();

			wp_enqueue_style(
				'uagb-block-css', // Handle.
				$wp_upload_url . 'custom-style-blocks.css', // Block style CSS.
				array(),
				SPECTRA_VER
			);
		}
		// Legacy dist/style-blocks.css fallback removed — the dist/ directory
		// no longer exists.  V3 blocks provide their own styles via block.json.
	}

	/**
	 * Enqueue block rtl styles.
	 *
	 * @since 0.0.1
	 */
	public static function enqueue_blocks_rtl_styles() {
		if ( is_rtl() ) {
			wp_enqueue_style(
				'uagb-style-rtl', // Handle.
				SPECTRA_URL . 'assets/css/style-blocks-rtl.min.css', // RTL style CSS.
				array(),
				SPECTRA_VER
			);
		}
	}

	/**
	 * Get folder name by post id.
	 *
	 * @param int $post_id post id.
	 * @since 0.0.1
	 */
	public static function get_asset_folder_name( $post_id ) {

		$folder_name = 0;

		if ( ! empty( $post_id ) ) {
			$folder_name = absint( round( $post_id, -3 ) );
		}

		return $folder_name;
	}

	/**
	 * Returns an array of paths for the CSS and JS assets
	 * of the current post.
	 *
	 * @param  string $type    Gets the CSS\JS type.
	 * @param  int    $post_id Post ID.
	 * @since 0.0.1
	 * @return array
	 */
	public static function get_asset_info( $type, $post_id ) {

		$uploads_dir = Spectra_Helper::get_upload_dir();
		$folder_name = self::get_asset_folder_name( $post_id );
		$file_name   = get_post_meta( $post_id, '_uag_' . $type . '_file_name', true );
		$path        = $type;
		$url         = $type . '_url';

		$info = array(
			$path => '',
			$url  => '',
		);

		if ( ! empty( $file_name ) ) {
			$info[ $path ] = $uploads_dir['path'] . 'assets/' . $folder_name . '/' . $file_name;
			$info[ $url ]  = $uploads_dir['url'] . 'assets/' . $folder_name . '/' . $file_name;
		}

		return $info;
	}

	/**
	 * Get JS url from to assets.
	 *
	 * @since 0.0.1
	 *
	 * @param string $file_name File name.
	 *
	 * @return string JS url.
	 */
	public static function get_js_url( $file_name ) {
		return SPECTRA_URL . 'assets/js/' . $file_name . SPECTRA_JS_EXT;
	}
}
