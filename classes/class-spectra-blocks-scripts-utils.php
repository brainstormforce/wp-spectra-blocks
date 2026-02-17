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
	 * Enqueue Gutenberg block assets for both frontend + backend.
	 *
	 * @since 0.0.1
	 */
	public static function enqueue_blocks_dependency_both() {

		$blocks       = Spectra_Block_Module::get_blocks_info();
		$saved_blocks = Spectra_Admin_Helper::get_admin_settings_option( '_uagb_blocks', array() );
		$block_assets = Spectra_Block_Module::get_block_dependencies();

		foreach ( $blocks as $slug => $value ) {
			$_slug = str_replace( 'uagb/', '', $slug );

			if ( ! ( isset( $saved_blocks[ $_slug ] ) && 'disabled' === $saved_blocks[ $_slug ] ) ) {

				if ( 'cf7-styler' === $_slug ) {
					if ( ! wp_script_is( 'contact-form-7', 'enqueued' ) ) {
						wp_enqueue_script( 'contact-form-7' );
					}

					if ( ! wp_script_is( ' wpcf7-admin', 'enqueued' ) ) {
						wp_enqueue_script( ' wpcf7-admin' );
					}
				}
				foreach ( $block_assets as $handle => $asset ) {

					if ( isset( $asset['type'] ) ) {

						if ( 'js' === $asset['type'] ) {

							// Scripts.
							wp_register_script(
								$handle, // Handle.
								$asset['src'],
								$asset['dep'],
								SPECTRA_VER,
								true
							);

							$skip_editor = isset( $asset['skipEditor'] ) ? $asset['skipEditor'] : false;

							if ( is_admin() && false === $skip_editor ) {
								wp_enqueue_script( $handle );
							}
						} elseif ( 'css' === $asset['type'] ) {

							// Styles.
							wp_register_style(
								$handle, // Handle.
								$asset['src'],
								$asset['dep'],
								SPECTRA_VER
							);

							if ( is_admin() ) {
								wp_enqueue_style( $handle );
							}
						}
					}
				}
			}
		}

		$spectra_masonry_ajax_nonce = wp_create_nonce( 'spectra_masonry_ajax_nonce' );
		wp_localize_script(
			'uagb-post-js',
			'spectra_data',
			array(
				'ajax_url'                => admin_url( 'admin-ajax.php' ),
				'spectra_masonry_ajax_nonce' => $spectra_masonry_ajax_nonce,
			)
		);

		$spectra_forms_ajax_nonce = wp_create_nonce( 'spectra_forms_ajax_nonce' );
		wp_localize_script(
			'uagb-forms-js',
			'spectra_forms_data',
			array(
				'ajax_url'              => admin_url( 'admin-ajax.php' ),
				'spectra_forms_ajax_nonce' => $spectra_forms_ajax_nonce,
			)
		);

		$spectra_image_gallery_masonry_ajax_nonce         = wp_create_nonce( 'spectra_image_gallery_masonry_ajax_nonce' );
		$spectra_image_gallery_grid_pagination_ajax_nonce = wp_create_nonce( 'spectra_image_gallery_grid_pagination_ajax_nonce' );
		wp_localize_script(
			'uagb-image-gallery-js',
			'spectra_image_gallery',
			array(
				'ajax_url'                              => admin_url( 'admin-ajax.php' ),
				'spectra_image_gallery_masonry_ajax_nonce' => $spectra_image_gallery_masonry_ajax_nonce,
				'spectra_image_gallery_grid_pagination_ajax_nonce' => $spectra_image_gallery_grid_pagination_ajax_nonce,
			)
		);

		wp_localize_script(
			'uagb-countdown-js',
			'spectra_countdown_data',
			array(
				'site_name_slug' => sanitize_title( get_bloginfo( 'name' ) ),
			)
		);

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
		} else {
			wp_enqueue_style(
				'uagb-block-css', // Handle.
				SPECTRA_URL . 'dist/style-blocks.css', // Block style CSS.
				array(),
				SPECTRA_VER
			);
		}
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
