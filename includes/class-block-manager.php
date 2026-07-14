<?php
/**
 * Class to manage Spectra Blocks.
 *
 * @package Spectra
 */

namespace SpectraBlocks;

use RuntimeException;
use SpectraBlocks\Blocks\Modal;
use SpectraBlocks\Blocks\Countdown;
use SpectraBlocks\Blocks\PopupBuilder;
use SpectraBlocks\Helpers\Core;
use SpectraBlocks\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class to manage Spectra Blocks.
 *
 * @since 3.0.0
 */
class BlockManager {

	use Singleton;

	/**
	 * Initialize the block manager by registering all block types and
	 * adding a block category.
	 *
	 * @since 3.0.0
	 */
	public function init() {
		$this->init_block();
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 9999999 );
		add_filter( 'block_type_metadata_settings', array( $this, 'configure_block_controller_settings' ), 11, 2 );

		( Countdown::instance() )->init();
		add_action( 'init', array( PopupBuilder::instance(), 'register_popup_cpt' ) );
		add_action( 'init', array( PopupBuilder::instance(), 'register_popup_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( PopupBuilder::instance(), 'enqueue_popup_scripts_for_post' ), 1 );
		add_action( 'admin_enqueue_scripts', array( PopupBuilder::instance(), 'popup_toggle_scripts' ) );
		add_action( 'wp_ajax_spectra_blocks_update_popup_status', array( PopupBuilder::instance(), 'update_popup_status' ) );
		add_filter( 'manage_edit-spectra-blocks-popup_columns', array( PopupBuilder::instance(), 'add_popup_columns' ) );
		add_action( 'manage_spectra-blocks-popup_posts_custom_column', array( PopupBuilder::instance(), 'render_popup_column' ), 10, 2 );

		// Always hook manage_spectra-popup_posts_custom_column so that spectra-popup rows
		// that appear inside the spectra-blocks-popup list get their columns rendered.
		// WordPress fires the action based on each row's post_type, not the list's CPT.
		// When UAGB is active we skip the manage_edit-spectra-popup_columns filter so we
		// don't duplicate the "Enable/Disable" column on UAGB's own Spectra Classic list.
		if ( ! defined( 'UAGB_VER' ) ) {
			add_filter( 'manage_edit-spectra-popup_columns', array( PopupBuilder::instance(), 'add_popup_columns' ) );
		}
		add_action( 'manage_spectra-popup_posts_custom_column', array( PopupBuilder::instance(), 'render_popup_column' ), 10, 2 );
		add_action( 'pre_get_posts', array( PopupBuilder::instance(), 'include_beta_popups_in_admin_list' ) );
	}

	/**
	 * Initializes all extensions by calling their init() method.
	 *
	 * This method is used to trigger the initialization of all extensions.
	 * when the extension manager is initialized.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function init_block() {
		( Modal::instance() )->init();
	}

	/**
	 * Registers all block types defined in block.json files located within the build/blocks directory.
	 *
	 * Utilizes the WordPress function `register_block_type_from_metadata` to register each block
	 * by its metadata specified in the block.json file.
	 *
	 * @since 3.0.0
	 *
	 * @throws RuntimeException If the blocks directory is invalid or inaccessible.
	 */
	public function register_blocks() {
		$blocks_dir = SPECTRA_BLOCKS_DIR . 'build/blocks/';

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
	 * Adds a custom block category named "Spectra 3" and appends it to the list of existing categories.
	 *
	 * @since 3.0.0
	 *
	 * @param array $categories The list of registered block categories.
	 * @return array The updated list of block categories.
	 */
	public function add_block_category( $categories ) {
		$slugs = wp_list_pluck( $categories, 'slug' );

		if ( ! in_array( 'spectra-blocks', $slugs, true ) ) {
			array_unshift( $categories, $this->get_spectra_block_category() );
		}

		if ( ! in_array( 'spectra-blocks-inner', $slugs, true ) ) {
			$categories[] = $this->get_spectra_inner_block_category();
		}

		return $categories;
	}

	/**
	 * Configures block settings to use a controller-based rendering pattern if available.
	 *
	 * @since 3.0.0
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

		$settings['render_callback'] = function ( $attributes, $content, $block ) use ( $controller_path, $metadata ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
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
	 * Retrieves the block category for Spectra 3 blocks.
	 *
	 * @since 3.0.0
	 *
	 * @return array The block category configuration.
	 */
	private function get_spectra_block_category() {
		return array(
			'slug'  => 'spectra-blocks',
			'title' => __( 'Spectra Blocks', 'spectra-blocks' ),
			'icon'  => '',
		);
	}

	/**
	 * Retrieves the block category for Spectra inner/child blocks.
	 *
	 * @since 0.0.9
	 *
	 * @return array The inner block category configuration.
	 */
	private function get_spectra_inner_block_category() {
		return array(
			'slug'  => 'spectra-blocks-inner',
			'title' => __( 'Spectra Inner Blocks', 'spectra-blocks' ),
			'icon'  => '',
		);
	}

	/**
	 * Resolves the path to the block's controller file.
	 *
	 * @since 3.0.0
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
	 * @since 3.0.0
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
	 * @since 3.0.0
	 *
	 * @param string $path The asset path.
	 * @return string The cleaned path.
	 */
	private function remove_asset_path_prefix( $path ) {
		return str_replace( 'file:./', '', $path );
	}
}
