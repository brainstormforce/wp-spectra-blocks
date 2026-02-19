<?php
/**
 * Class to manage Spectra Blocks assets.
 *
 * @package Spectra
 */

namespace Spectra;

use Spectra\FontManager;
use Spectra\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class to manage Spectra Blocks assets.
 *
 * @since 3.0.0
 */
class AssetLoader {

	use Singleton;

	/**
	 * Initializes the asset loader by setting up necessary components.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function init() {
		$this->init_font_manager();
		// Enqueue the common style assets on the frontend and editor as this is the only way to ensure that the styles are loaded in the editor and on the frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_common_style_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'handle_frontend_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_extensions_frontend_assets' ) );

		// Load utility functions for GT integration.
		$this->load_gt_utils();
	}

	/**
	 * Initializes the Spectra Font Manager.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function init_font_manager() {
		( FontManager::instance() )->init();
	}

	/**
	 * Load utility functions for Gutenberg Templates integration.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function load_gt_utils() {
		if ( ! function_exists( 'spectra_blocks_get_v3_blocks_css_for_preview' ) ) {
			require_once SPECTRA_BLOCKS_DIR . 'includes/utils.php';
		}
	}

	/**
	 * Register all the styles from the '/src/styles' directory.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function enqueue_common_style_assets() {
		$css_path  = SPECTRA_BLOCKS_DIR . 'build/styles/';
		$css_files = glob( $css_path . '**/*.css' ) ?? array();

		foreach ( $css_files as $css_file ) {
			// Get the parent directory name relative to built styles directory. For example, 'components'.
			$relative_path = str_replace( $css_path, '', $css_file );
			$style_type    = dirname( $relative_path );

			// Extract the file name without the extension and prepend with 'spectra-' and the directory name.
			$handle = 'spectra-' . trim( $style_type, '/' ) . '-' . basename( $css_file, '.css' );

			// Register the style.
			wp_register_style(
				$handle,
				plugins_url( 'build/styles/' . trim( $style_type, '/' ) . '/' . basename( $css_file ), SPECTRA_BLOCKS_FILE ),
				array(),
				SPECTRA_BLOCKS_VER
			);
		}
	}

	/**
	 * Register all the assets needed only in the editor.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// Load the common editor styles.
		$css_file = SPECTRA_BLOCKS_DIR . 'build/styles/editor.css';

		// Create the handle for the common editor styles.
		$handle = 'spectra-editor';

		// Register the common editor styles.
		wp_register_style(
			$handle,
			plugins_url( 'build/styles/editor.css', SPECTRA_BLOCKS_FILE ),
			array(),
			filemtime( $css_file )
		);

		// Enqueue the common editor styles.
		wp_enqueue_style( $handle );

		// Enqueue the common assets.
		$this->enqueue_common_style_assets();

		// Localize plugin data for block editor JS.
		$this->localize_editor_data();
	}

	/**
	 * Localize plugin configuration data for the block editor.
	 *
	 * Sets the global `spectra_blocks_info` JS object with plugin URL, RTL status,
	 * SVG icons, and other data required by block editor components.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	private function localize_editor_data() {
		// Determine Spectra Pro plugin status.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed_plugins  = get_plugins();
		$spectra_pro_status = 'not-installed';
		if ( isset( $installed_plugins['spectra-pro/spectra-pro.php'] ) ) {
			$spectra_pro_status = is_plugin_active( 'spectra-pro/spectra-pro.php' ) ? 'active' : 'inactive';
		}

		// Load and merge SVG icon data from icon chunk files.
		$icon_chunks  = \Spectra\Helpers\Core::backend_load_font_awesome_icons();
		$merged_icons = array();
		if ( is_array( $icon_chunks ) ) {
			foreach ( $icon_chunks as $chunk ) {
				if ( is_array( $chunk ) ) {
					$merged_icons = array_merge( $merged_icons, $chunk );
				}
			}
		}

		// Build the editor vars array.
		$editor_vars = array(
			'plugin_url'               => SPECTRA_BLOCKS_URL,
			'is_rtl'                   => is_rtl() ? '1' : '0',
			'spectra_pro_status'       => $spectra_pro_status,
			'current_post_id'          => (int) get_the_ID(),
			'spectra_blocks_svg_icons' => $merged_icons,
		);

		// Compute the icon category list from custom_categories per icon.
		$categories_map = array();
		foreach ( $merged_icons as $icon_data ) {
			if ( ! empty( $icon_data['custom_categories'] ) && is_array( $icon_data['custom_categories'] ) ) {
				foreach ( $icon_data['custom_categories'] as $cat ) {
					if ( ! isset( $categories_map[ $cat ] ) ) {
						$categories_map[ $cat ] = array(
							'value' => $cat,
							'label' => ucwords( str_replace( '-', ' ', $cat ) ),
						);
					}
				}
			}
		}
		$icon_category_list = array_values( $categories_map );

		// Output the global variables as an inline script before wp-blocks runs.
		$js  = 'var spectra_blocks_info = ' . wp_json_encode( $editor_vars ) . ';' . "\n";
		$js .= 'window.spectraBlocksSvgIcons = Object.keys( spectra_blocks_info.spectra_blocks_svg_icons || {} );' . "\n";
		$js .= 'window.spectraBlocksIconCategoryList = ' . wp_json_encode( $icon_category_list ) . ';' . "\n";

		wp_add_inline_script( 'wp-blocks', $js, 'before' );
	}

	/**
	 * Register the Swiper assets.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_block_assets() {
		// Register Swiper assets that can be used by blocks.
		wp_register_style(
			'swiper-style',
			SPECTRA_BLOCKS_URL . 'assets/css/swiper-bundle.min.css',
			array(),
			'11.0.5'
		);

		wp_register_script(
			'swiper-script',
			SPECTRA_BLOCKS_URL . 'assets/js/swiper-bundle.min.js',
			array(),
			'11.0.5',
			true
		);

		wp_register_script(
			'modal-script',
			SPECTRA_BLOCKS_URL . 'assets/js/modal-script.js',
			array(),
			SPECTRA_BLOCKS_VER,
			true
		);
	}

	/**
	 * Enqueue the frontend assets for the slider block.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Only enqueue if slider block is present.
		if ( has_block( 'spectra/slider' ) ) {
			wp_enqueue_style( 'swiper-style' );
			wp_enqueue_script( 'swiper-script' );
			wp_enqueue_script( 'modal-script' );
		}
	}

	/**
	 * Enqueue frontend assets for extensions.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function enqueue_extensions_frontend_assets() {
		wp_enqueue_style( 'spectra-extensions-image-mask' );
		wp_enqueue_style( 'spectra-extensions-z-index' );
	}

	/**
	 * Handle all frontend asset registration and enqueuing.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function handle_frontend_assets() {
		$this->register_block_assets();
		$this->enqueue_frontend_assets();
	}

	/**
	 * Get v3 blocks CSS for a specific post or all blocks.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id Optional. Post ID to generate CSS for. If 0, generates CSS for all blocks.
	 * @return string Generated CSS content.
	 */
	public static function get_v3_css( $post_id = 0 ) {
		// Ensure utils are loaded.
		if ( ! function_exists( 'spectra_blocks_get_v3_blocks_css_for_preview' ) ) {
			require_once SPECTRA_BLOCKS_DIR . 'includes/utils.php';
		}

		return spectra_blocks_get_v3_blocks_css_for_preview( $post_id );
	}

	/**
	 * Create v3 blocks CSS stylesheet for Gutenberg Templates.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id Optional. Post ID to generate CSS for.
	 * @return bool True on success, false on failure.
	 */
	public static function create_v3_stylesheet( $post_id = 0 ) {
		// CSS stylesheet creation - handled by WP native filesystem.
		$v3_block_styles = self::get_v3_css( $post_id );

		if ( empty( $v3_block_styles ) || ! is_string( $v3_block_styles ) ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return false;
		}

		$cache_dir = trailingslashit( $upload_dir['basedir'] ) . 'spectra-blocks/';
		wp_mkdir_p( $cache_dir );

		$filename = $post_id > 0 ? "spectra-blocks-{$post_id}.css" : 'spectra-blocks.css';
		$filepath = $cache_dir . $filename;

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return false !== $wp_filesystem->put_contents( $filepath, $v3_block_styles, FS_CHMOD_FILE );
	}
}
