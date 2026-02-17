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
 * @since 0.0.1
 */
class AssetLoader {

	use Singleton;

	/**
	 * Initializes the asset loader by setting up necessary components.
	 *
	 * @since 0.0.1
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
	 * @since 0.0.1
	 *
	 * @return void
	 */
	private function init_font_manager() {
		( FontManager::instance() )->init();
	}

	/**
	 * Load utility functions for Gutenberg Templates integration.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	private function load_gt_utils() {
		if ( ! function_exists( 'spectra_get_v3_blocks_css_for_preview' ) ) {
			require_once SPECTRA_DIR . 'includes/utils.php';
		}
	}

	/**
	 * Register all the styles from the '/src/styles' directory.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function enqueue_common_style_assets() {
		$css_path  = SPECTRA_DIR . 'build/styles/';
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
				plugins_url( 'build/styles/' . trim( $style_type, '/' ) . '/' . basename( $css_file ), SPECTRA_FILE ),
				array(),
				SPECTRA_VER
			);
		}
	}

	/**
	 * Register all the assets needed only in the editor.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// Load the common editor styles.
		$css_file = SPECTRA_DIR . 'build/styles/editor.css';

		// Create the handle for the common editor styles.
		$handle = 'spectra-editor';

		// Register the common editor styles.
		wp_register_style(
			$handle,
			plugins_url( 'build/styles/editor.css', SPECTRA_FILE ),
			array(),
			filemtime( $css_file )
		);

		// Enqueue the common editor styles.
		wp_enqueue_style( $handle );

		// Enqueue the common assets.
		$this->enqueue_common_style_assets();
	}

	/**
	 * Register the Swiper assets.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function register_block_assets() {
		// Register Swiper assets that can be used by blocks.
		wp_register_style(
			'swiper-style',
			SPECTRA_URL . 'assets/css/swiper-bundle.min.css',
			array(),
			'11.0.5'
		);

		wp_register_script(
			'swiper-script',
			SPECTRA_URL . 'assets/js/swiper-bundle.min.js',
			array(),
			'11.0.5',
			true
		);

		wp_register_script(
			'modal-script',
			SPECTRA_URL . 'assets/js/modal-script.js',
			array(),
			SPECTRA_VER,
			true
		);
	}

	/**
	 * Enqueue the frontend assets for the slider block.
	 *
	 * @since 0.0.1
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
	 * @since 0.0.1
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
	 * @since 0.0.1
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
	 * @since 0.0.1
	 *
	 * @param int $post_id Optional. Post ID to generate CSS for. If 0, generates CSS for all blocks.
	 * @return string Generated CSS content.
	 */
	public static function get_v3_css( $post_id = 0 ) {
		// Ensure utils are loaded.
		if ( ! function_exists( 'spectra_get_v3_blocks_css_for_preview' ) ) {
			require_once SPECTRA_DIR . 'includes/utils.php';
		}
		
		return spectra_get_v3_blocks_css_for_preview( $post_id );
	}

	/**
	 * Create v3 blocks CSS stylesheet for Gutenberg Templates.
	 *
	 * @since 0.0.1
	 *
	 * @param int $post_id Optional. Post ID to generate CSS for.
	 * @return bool True on success, false on failure.
	 */
	public static function create_v3_stylesheet( $post_id = 0 ) {
		$v3_block_styles = self::get_v3_css( $post_id );
		
		if ( empty( $v3_block_styles ) || ! is_string( $v3_block_styles ) ) {
			return false;
		}
		
		if ( ! class_exists( 'Spectra_Helper' ) || ! function_exists( 'spectra_filesystem' ) ) {
			return false;
		}
		
		$upload_dir = Spectra_Helper::get_uag_upload_dir_path();
		if ( empty( $upload_dir ) ) {
			return false;
		}
		
		$filename      = $post_id > 0 ? "spectra-v3-blocks-{$post_id}.css" : 'spectra-v3-blocks.css';
		$v3_cache_path = $upload_dir . $filename;
		
		$wp_filesystem = spectra_filesystem();
		if ( ! $wp_filesystem ) {
			return false;
		}
		
		return false !== $wp_filesystem->put_contents( $v3_cache_path, $v3_block_styles, FS_CHMOD_FILE );
	}
}
