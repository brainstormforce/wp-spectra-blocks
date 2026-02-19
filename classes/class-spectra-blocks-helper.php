<?php
/**
 * Spectra Blocks Helper.
 *
 * Global helper class for asset, filesystem, and block list utilities.
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Spectra_Blocks_Helper' ) ) {

	/**
	 * Class Spectra_Blocks_Helper.
	 */
	class Spectra_Blocks_Helper {

		/**
		 * Block list, keyed by block name (spectra/block-name).
		 *
		 * @var array
		 */
		public static $block_list = array();

		/**
		 * Whether file generation is enabled or disabled.
		 *
		 * @var string 'enabled' or 'disabled'
		 */
		public static $file_generation = 'disabled';

		/**
		 * Initialize the helper — populates static properties.
		 * Called on the `init` action.
		 */
		public static function init() {
			self::$block_list     = self::get_blocks_info();
			self::$file_generation = self::allow_file_generation();
		}

		/**
		 * Build block info array by scanning build/blocks directories for block.json.
		 *
		 * @return array Keyed by block name.
		 */
		public static function get_blocks_info() {
			$blocks     = array();
			$build_dir  = SPECTRA_BLOCKS_DIR . 'build/blocks/';

			if ( ! is_dir( $build_dir ) ) {
				return $blocks;
			}

			$dirs = glob( $build_dir . '*', GLOB_ONLYDIR );
			if ( ! is_array( $dirs ) ) {
				return $blocks;
			}

			foreach ( $dirs as $dir ) {
				$block_json = $dir . '/block.json';
				if ( ! file_exists( $block_json ) ) {
					continue;
				}

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$data = json_decode( file_get_contents( $block_json ), true );
				if ( empty( $data['name'] ) ) {
					continue;
				}

				$blocks[ $data['name'] ] = array(
					'title'       => isset( $data['title'] ) ? $data['title'] : basename( $dir ),
					'description' => isset( $data['description'] ) ? $data['description'] : '',
					'category'    => isset( $data['category'] ) ? $data['category'] : 'spectra-blocks',
					'default'     => true,
				);
			}

			return $blocks;
		}

		/**
		 * Check if a specific block is an "old user" (less than V3).
		 * Since spectra-blocks is a fresh V3-only plugin, always returns false.
		 *
		 * @return bool
		 */
		public static function is_old_user_less_than_v3() {
			return false;
		}

		/**
		 * Get the upload directory path for Spectra Blocks CSS files.
		 *
		 * @return string Absolute path (with trailing slash).
		 */
		public static function get_upload_dir_path() {
			return Spectra_Blocks_Upload::get_dir();
		}

		/**
		 * Check whether CSS file generation is enabled.
		 *
		 * @return string 'enabled' or 'disabled'.
		 */
		public static function allow_file_generation() {
			return apply_filters(
				'spectra_blocks_allow_file_generation',
				get_option( '_spectra_blocks_allow_file_generation', 'disabled' )
			);
		}

		/**
		 * Delete the Spectra Blocks asset upload directory and its contents.
		 *
		 * @return void
		 */
		public static function delete_asset_dir() {
			$upload_dir = self::get_upload_dir_path();

			if ( ! is_dir( $upload_dir ) ) {
				return;
			}

			$files = glob( $upload_dir . '*' );
			if ( is_array( $files ) ) {
				foreach ( $files as $file ) {
					if ( is_file( $file ) ) {
						wp_delete_file( $file );
					}
				}
			}
		}

		/**
		 * Remove a specific file.
		 *
		 * @param string $file_path Absolute path to the file.
		 * @return void
		 */
		public static function remove_file( $file_path ) {
			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}
		}
	}
}
