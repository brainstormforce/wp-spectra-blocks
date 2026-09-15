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
use SpectraBlocks\Extensions\ResponsiveControls;
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

			/*
			 * Re-sync the attributes the responsive extension rewrote.
			 *
			 * A TOP-LEVEL block is fine: `render_block()` applies `render_block_data`
			 * before constructing `WP_Block`, so the callback receives the filtered
			 * attributes. An INNER block is not. `WP_Block::render()` applies the
			 * filter to `$inner_block->parsed_block` (`class-wp-block.php:614`) when
			 * the `WP_Block` object already exists, and `WP_Block::__construct()` has
			 * by then called `refresh_context_dependents()`, which for any block with
			 * inner blocks AND `providesContext` reads `$this->attributes` to build
			 * the child context. That first read freezes the attributes from the
			 * UNFILTERED parsed block, and core's follow-up
			 * `refresh_parsed_block_dependents()` rebuilds only `inner_blocks`,
			 * `inner_html` and `inner_content` — never `$this->attributes`.
			 * Core gap: https://core.trac.wordpress.org/ticket/51612
			 *
			 * So a nested Post block reached its controller with no
			 * `responsiveControls` store AND with the root `slidesPerView` that
			 * `remove_conflicting_core_attributes()` strips for a top-level block.
			 * Both halves pushed the same way: every breakpoint fell back to the root
			 * value, so the carousel showed one slide count at every width.
			 *
			 * Scoped to the keys `ResponsiveControls` owns rather than rebuilding the
			 * whole attribute set from `parsed_block['attrs']`. Rebuilding would also
			 * hand nested blocks the Style Guide colour/spacing rewrites and Pro's
			 * dynamic-content injections, which are broken when nested in the same
			 * way — but fixing those silently changes how existing pages render, so
			 * they want their own change and their own QA. This fixes the reported
			 * bug and leaves every other filter exactly as it behaves today.
			 *
			 * The comparison is against `prepare_attributes_for_render( $parsed )` —
			 * exactly what a TOP-LEVEL block would have received — not against the
			 * raw parsed attributes. Raw is wrong in both directions: it omits the
			 * block.json defaults, so unsetting every owned key it lacks deletes
			 * defaulted values the filters never touched, and it cannot distinguish
			 * "the filter removed this" from "this was never set". Comparing against
			 * the prepared set removes only what the filters actually removed and
			 * restores the defaults for the rest.
			 *
			 * A key missing there is unset rather than left alone: the extension
			 * expresses itself by removing keys as well as rewriting them, and an
			 * overwrite-only merge leaves the stale root value in place — which
			 * pinned the desktop band while tablet and mobile came good.
			 *
			 * Skipped when the block carries binding metadata. Core merges
			 * binding-computed values into the attributes just before this callback
			 * (`class-wp-block.php:569`), and those live only on the object, not on
			 * `parsed_block`, so copying over them would discard them. No Spectra
			 * block is binding-supported today — `get_block_bindings_supported_attributes()`
			 * lists core blocks only — but it is filterable, so this stays a guard
			 * rather than an assumption.
			 *
			 * `\WP_Block` is deliberately root-qualified — this file is namespaced,
			 * and an unqualified `WP_Block` resolves to `SpectraBlocks\WP_Block`,
			 * which does not exist, so the guard would silently never match.
			 */
			if (
				$block instanceof \WP_Block
				&& is_string( $block->name )
				&& isset( $block->parsed_block['attrs'] )
				&& is_array( $block->parsed_block['attrs'] )
				&& empty( $block->parsed_block['attrs']['metadata']['bindings'] )
			) {
				$filtered_attrs = $block->block_type->prepare_attributes_for_render( $block->parsed_block['attrs'] );

				// Narrowed with `instanceof` because the Singleton accessor is typed
				// `object`, matching the existing call site in `class-asset-loader.php`.
				$responsive_controls = ResponsiveControls::instance();

				if ( $responsive_controls instanceof ResponsiveControls ) {
					foreach ( $responsive_controls->get_owned_attribute_keys( $block->name ) as $owned_key ) {
						if ( array_key_exists( $owned_key, $filtered_attrs ) ) {
							$attributes[ $owned_key ] = $filtered_attrs[ $owned_key ];
						} else {
							unset( $attributes[ $owned_key ] );
						}
					}
				}
			}

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
