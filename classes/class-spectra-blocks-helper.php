<?php
/**
 * Spectra Blocks Helper.
 *
 * Global helper class for block list utilities.
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
		 * Initialize the helper — populates static properties.
		 * Called on the `init` action.
		 */
		public static function init() {
			self::$block_list = self::get_blocks_info();
		}

		/**
		 * Build block info array by scanning build/blocks directories for block.json.
		 *
		 * @return array Keyed by block name.
		 */
		public static function get_blocks_info() {
			$blocks    = array();
			$build_dir = SPECTRA_BLOCKS_DIR . 'build/blocks/';

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
	}
}
