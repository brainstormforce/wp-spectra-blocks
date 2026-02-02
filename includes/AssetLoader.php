<?php
/**
 * Class to manage Spectra Blocks assets.
 *
 * @package Spectra
 */

namespace Spectra;

use Spectra\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class to manage Spectra Blocks assets.
 *
 * @since 1.0.0
 */
class AssetLoader {

	use Singleton;

	/**
	 * Initializes the asset loader by setting up necessary components.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		// Register common block styles early (before blocks are registered).
		add_action( 'init', array( $this, 'register_common_block_styles' ), 5 );

		// Enqueue the common style assets on the frontend and editor.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_style_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );

		// Enqueue block assets on frontend when blocks are used.
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
	}

	/**
	 * Register common block styles that blocks depend on.
	 *
	 * This must run before blocks are registered so the style handle is available.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_common_block_styles() {
		$common_css = SPECTRA_DIR . 'build/styles/blocks/common.css';

		if ( file_exists( $common_css ) ) {
			wp_register_style(
				'spectra-blocks-common',
				plugins_url( 'build/styles/blocks/common.css', SPECTRA_FILE ),
				array(),
				SPECTRA_VER
			);
		}
	}

	/**
	 * Register all the styles from the '/build/styles' directory.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_style_assets() {
		$css_path  = SPECTRA_DIR . 'build/styles/';
		$css_files = glob( $css_path . '**/*.css' ) ?? array();

		foreach ( $css_files as $css_file ) {
			// Skip RTL files - WordPress handles these automatically.
			if ( strpos( $css_file, '-rtl.css' ) !== false ) {
				continue;
			}

			// Get the parent directory name relative to built styles directory.
			$relative_path = str_replace( $css_path, '', $css_file );
			$style_type    = dirname( $relative_path );

			// Extract the file name without the extension and prepend with 'spectra-'.
			$handle = 'spectra-' . trim( $style_type, '/' ) . '-' . basename( $css_file, '.css' );

			// Skip if already registered (e.g., spectra-blocks-common).
			if ( wp_style_is( $handle, 'registered' ) ) {
				continue;
			}

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
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// Load the common editor styles.
		$css_file = SPECTRA_DIR . 'build/styles/editor.css';

		if ( ! file_exists( $css_file ) ) {
			return;
		}

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

		// Register style assets in editor too.
		$this->register_style_assets();

		// Enqueue component styles needed in the editor.
		$this->enqueue_editor_component_styles();

		// Localize script data for block editor.
		$this->localize_editor_data();
	}

	/**
	 * Enqueue component styles needed in the block editor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function enqueue_editor_component_styles() {
		$components_dir = SPECTRA_DIR . 'build/styles/components/';

		if ( ! is_dir( $components_dir ) ) {
			return;
		}

		$component_styles = glob( $components_dir . '*.css' );

		if ( empty( $component_styles ) ) {
			return;
		}

		foreach ( $component_styles as $css_file ) {
			// Skip RTL files - WordPress handles these automatically.
			if ( strpos( $css_file, '-rtl.css' ) !== false ) {
				continue;
			}

			$filename = basename( $css_file, '.css' );
			$handle   = 'spectra-components-' . $filename;

			// Register and enqueue the component style.
			wp_register_style(
				$handle,
				plugins_url( 'build/styles/components/' . basename( $css_file ), SPECTRA_FILE ),
				array(),
				filemtime( $css_file )
			);

			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Localize script data for the block editor.
	 *
	 * Provides necessary data for block components like variation picker.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function localize_editor_data() {
		// Get a handle that's already enqueued in the editor.
		// We'll use wp-blocks as it's a core dependency.
		$handle = 'wp-blocks';

		// Localize the script data.
		wp_localize_script(
			$handle,
			'spectra_blocks_info',
			array(
				'spectra_url' => SPECTRA_URL,
				'version'     => SPECTRA_VER,
				'is_rtl'      => is_rtl(),
			)
		);
	}

	/**
	 * Enqueue block assets on frontend when blocks are used.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_block_assets() {
		// Only enqueue on frontend, not in editor.
		if ( is_admin() ) {
			return;
		}

		// The block styles are automatically enqueued by WordPress when blocks are used.
		// This hook is here for any additional frontend assets that may be needed.
	}
}
