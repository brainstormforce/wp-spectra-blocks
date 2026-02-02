<?php
/**
 * Class to manage Spectra Blocks.
 *
 * @package Spectra
 */

namespace Spectra;

use Spectra\Helpers\Core;
use Spectra\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class to manage Spectra Blocks.
 *
 * @since 1.0.0
 */
class BlockManager {

	use Singleton;

	/**
	 * Initialize the block manager by registering all block types and
	 * adding a block category.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 9999999 );
		add_filter( 'block_type_metadata_settings', array( $this, 'configure_block_controller_settings' ), 11, 2 );
	}

	/**
	 * Registers all block types defined in block.json files located within the build/blocks directory.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_blocks() {
		$blocks_dir = SPECTRA_DIR . 'build/blocks/';

		if ( ! is_dir( $blocks_dir ) || ! is_readable( $blocks_dir ) ) {
			// Skip block registration if build directory doesn't exist (development mode).
			return;
		}

		$block_files = glob( $blocks_dir . '**/block.json' );

		if ( false === $block_files ) {
			return;
		}

		if ( ! empty( $block_files ) ) {
			foreach ( $block_files as $block_file ) {
				register_block_type_from_metadata( $block_file );
			}
		}
	}

	/**
	 * Adds a custom block category named "Spectra" and appends it to the list of existing categories.
	 *
	 * @since 1.0.0
	 *
	 * @param array $categories The list of registered block categories.
	 * @return array The updated list of block categories.
	 */
	public function add_block_category( $categories ) {
		$slugs = wp_list_pluck( $categories, 'slug' );

		if ( ! in_array( 'spectra', $slugs, true ) ) {
			array_unshift( $categories, $this->get_spectra_block_category() );
		}

		return $categories;
	}

	/**
	 * Configures block settings to use a controller-based rendering pattern if available.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings The block settings from metadata.
	 * @param array $metadata The block metadata from block.json.
	 * @return array Updated block settings.
	 */
	public function configure_block_controller_settings( $settings, $metadata ) {
		if ( ! isset( $metadata['file'] ) || ! Core::is_spectra_block( $metadata ) ) {
			return $settings;
		}

		$controller_path = $this->resolve_controller_path( $metadata['file'] );

		if ( ! $controller_path ) {
			return $settings;
		}

		$settings['render_callback'] = function ( $attributes, $content, $block ) use ( $controller_path, $metadata ) {
			// Include the controller and validate its output.
			$view = include $controller_path;

			if ( ! $view ) {
				return ''; // Early return if controller returns nothing.
			}

			// Check if $view is a file path or direct content.
			if ( ! is_string( $view ) || strpos( $view, 'file:' ) !== 0 ) {
				return $view; // Direct content output.
			}

			$template_path = $this->resolve_template_path( $metadata['file'], $view );

			if ( ! $template_path ) {
				return ''; // Avoid errors if the template is invalid.
			}

			ob_start();
			require $template_path;
			return ob_get_clean();
		};

		return $settings;
	}

	/**
	 * Private methods are here.
	 *
	 * These methods are all used internally by the class and should not be
	 * accessed directly.
	 */

	/**
	 * Retrieves the block category for Spectra blocks.
	 *
	 * @since 1.0.0
	 *
	 * @return array The block category configuration.
	 */
	private function get_spectra_block_category() {
		return array(
			'slug'  => 'spectra',
			'title' => __( 'Spectra', 'spectra' ),
			'icon'  => '',
		);
	}

	/**
	 * Resolves the path to the block's controller file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $metadata_file The path to the block.json file.
	 * @return string|null The resolved controller path or null if invalid.
	 */
	private function resolve_controller_path( $metadata_file ) {
		$base_dir        = dirname( $metadata_file );
		$controller_path = wp_normalize_path( $base_dir . '/controller.php' );

		return file_exists( $controller_path ) ? realpath( $controller_path ) : null;
	}

	/**
	 * Resolves the template path from the view directive.
	 *
	 * @since 1.0.0
	 *
	 * @param string $metadata_file The path to the block.json file.
	 * @param string $view The view directive from the controller.
	 * @return string|null The resolved template path or null if invalid.
	 */
	private function resolve_template_path( $metadata_file, $view ) {
		$base_dir      = dirname( $metadata_file );
		$template_path = wp_normalize_path( $base_dir . '/' . $this->remove_asset_path_prefix( $view ) );

		return file_exists( $template_path ) ? realpath( $template_path ) : null;
	}

	/**
	 * Removes the 'file:./' prefix from asset paths.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path The asset path.
	 * @return string The cleaned path.
	 */
	private function remove_asset_path_prefix( $path ) {
		return str_replace( 'file:./', '', $path );
	}
}
