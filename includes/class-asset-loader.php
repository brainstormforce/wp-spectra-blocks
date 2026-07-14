<?php
/**
 * Class to manage Spectra Blocks assets.
 *
 * @package Spectra
 */

namespace SpectraBlocks;

use SpectraBlocks\FontManager;
use SpectraBlocks\Traits\Singleton;
use SpectraBlocks\Helpers\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class to manage Spectra Blocks assets.
 *
 * @since 3.0.0
 */
class AssetLoader {

	use Singleton;

	/**
	 * Body class added to zip-built (imported) pages on the frontend + admin.
	 *
	 * @var string
	 */
	const ZIP_BUILDER_BODY_CLASS = 'spectra-page-zip-builder';

	/**
	 * Marker meta the ERA importer sets on every page it writes — the
	 * explicit source of truth for "this page is imported" (drives the
	 * zip-builder body class, frontend + editor). Detection previously
	 * inferred importedness from the free engine's V2 per-page CSS payload
	 * (`GenCssOrphanStripper::read_page_payload`), but that store is
	 * DEPRECATED (GBS store is SSOT; page scope = class index + global
	 * definitions), so the inference never fired on current-flow imports
	 * (measured 2026-07-02: body class absent on a fresh import). An
	 * explicit marker survives any future CSS-storage refactor.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const IMPORTED_MARKER_META_KEY = '_zipai_imported';

	/**
	 * Initializes the asset loader by setting up necessary components.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function init() {
		$this->init_font_manager();
		// Register third-party handles early so block.json style deps resolve in all contexts (FSE, REST preview, etc.).
		add_action( 'init', array( $this, 'register_block_assets' ) );
		// Enqueue the common style assets on the frontend and editor as this is the only way to ensure that the styles are loaded in the editor and on the frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_common_style_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'handle_frontend_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_extensions_frontend_assets' ) );

		// Tag zip-built (imported) pages with a body class on both the frontend
		// and the admin editor screen, so page-level CSS can scope overrides to
		// imported content (see src/styles/blocks/common.scss).
		add_filter( 'body_class', array( $this, 'add_zip_builder_body_class' ) );
		add_filter( 'admin_body_class', array( $this, 'add_zip_builder_admin_body_class' ) );

		// Import-marker meta (the body-class detector's source of truth) —
		// registered so the importer can set it over REST at page-write time.
		add_action( 'init', array( $this, 'register_import_marker_meta' ) );

		// Load utility functions for GT integration.
		$this->load_gt_utils();
	}

	/**
	 * Frontend: append the zip-builder marker to the <body> class list on
	 * imported (zip-built) singular views.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $classes Existing body classes.
	 * @return array<int, string> Possibly-extended body classes.
	 */
	public function add_zip_builder_body_class( $classes ) {
		$post_id = is_singular() ? get_queried_object_id() : 0;

		if ( $post_id && self::is_zip_built_page( (int) $post_id ) ) {
			$classes[] = self::ZIP_BUILDER_BODY_CLASS;
		}

		return $classes;
	}

	/**
	 * Admin: append the zip-builder marker to the admin <body> class on the
	 * post-edit screen for an imported page. `admin_body_class` passes a
	 * space-joined string (not an array), so we concatenate.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated admin body classes.
	 * @return string Possibly-extended admin body classes.
	 */
	public function add_zip_builder_admin_body_class( $classes ) {
		$post_id = isset( $GLOBALS['post']->ID ) ? (int) $GLOBALS['post']->ID : 0;

		if ( ! $post_id ) {
			// Display-only body class — no state change, so no nonce needed.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;
		}

		if ( $post_id && self::is_zip_built_page( $post_id ) ) {
			$classes .= ' ' . self::ZIP_BUILDER_BODY_CLASS;
		}

		return $classes;
	}

	/**
	 * Whether a post was produced by the zip builder — the importer sets
	 * {@see self::IMPORTED_MARKER_META_KEY} on every page it writes, and
	 * that marker is the sole detection signal (the old per-page Gen CSS
	 * payload inference is retired with its deprecated store). Cached per
	 * request.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool Whether the page is zip-built.
	 */
	private static function is_zip_built_page( int $post_id ): bool {
		static $cache = array();

		if ( ! isset( $cache[ $post_id ] ) ) {
			// Explicit importer-set marker (see IMPORTED_MARKER_META_KEY doc).
			// The previous detector inferred importedness from the deprecated
			// V2 per-page CSS payload and never fired on current-flow imports.
			$cache[ $post_id ] = (bool) get_post_meta( $post_id, self::IMPORTED_MARKER_META_KEY, true );
		}

		return $cache[ $post_id ];
	}

	/**
	 * Register the import-marker meta so the ERA importer can set it over
	 * REST when it writes a page. Boolean, single, protected key — the
	 * auth callback gates writes to users who can edit the post, which is
	 * the capability the importer's application password already carries.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_import_marker_meta() {
		register_post_meta(
			'page',
			self::IMPORTED_MARKER_META_KEY,
			array(
				'type'          => 'boolean',
				'description'   => __( 'Set by the ERA importer on imported pages; drives the zip-builder body class.', 'spectra-blocks' ),
				'single'        => true,
				'default'       => false,
				'show_in_rest'  => true,
				'auth_callback' => static function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
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
		if ( ! function_exists( 'spectra_get_v3_blocks_css_for_preview' ) ) {
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
			// Namespace = the built sheet's directory under build/styles/ (e.g.
			// 'blocks', 'extensions', 'components'). Kept intact for the file URL.
			$relative_path = str_replace( $css_path, '', $css_file );
			$style_type    = trim( dirname( $relative_path ), '/' );

			// Handle = plugin slug + namespace + file. The `blocks` namespace is
			// IMPLICIT — the slug is already `spectra-blocks`, so re-appending it
			// yields a redundant `spectra-blocks-blocks-*` handle no consumer uses.
			// Block sheets therefore register as `spectra-blocks-<name>`, the handle
			// every block.json `style` dep and the imported-baseline `global-styles`
			// guard below rely on. Namespaced sheets keep their segment
			// (`spectra-blocks-<ns>-<name>`), so a file move never silently breaks a
			// consumer by mangling its handle.
			$namespace = ( 'blocks' === $style_type ) ? '' : $style_type . '-';
			$handle    = 'spectra-blocks-' . $namespace . basename( $css_file, '.css' );

			// The imported-content contract sheet must PRINT after theme.json
			// output (its clauses win same-tier ties by declared order, not
			// enqueue luck — see src/styles/blocks/imported-baseline.scss).
			// Conditional: classic themes have no `global-styles` handle, and
			// a dependency on an unregistered handle would drop the sheet.
			$deps = array();
			if ( 'spectra-blocks-imported-baseline' === $handle
				&& ( wp_style_is( 'global-styles', 'registered' ) || wp_style_is( 'global-styles', 'enqueued' ) ) ) {
				$deps[] = 'global-styles';
			}

			// Register the style.
			wp_register_style(
				$handle,
				plugins_url( 'build/styles/' . trim( $style_type, '/' ) . '/' . basename( $css_file ), SPECTRA_BLOCKS_FILE ),
				$deps,
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
		// Register Swiper assets so the editor has access to the global Swiper object.
		$this->register_block_assets();

		// Load the common editor styles.
		$css_file = SPECTRA_BLOCKS_DIR . 'build/styles/editor.css';

		// Create the handle for the common editor styles.
		$handle = 'spectra-blocks-editor';

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

		// Localize editor data for block JS.
		$this->localize_editor_data();
	}

	/**
	 * Localize spectra_blocks_info data for block editor scripts.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function localize_editor_data() {
		$spectra_pro_status = 'inactive';
		if ( defined( 'SPECTRA_BLOCKS_PRO_VER' ) ) {
			$spectra_pro_status = 'active';
		}

		$icon_chunks = Core::backend_load_font_awesome_icons();
		$all_icons   = array_merge( ...$icon_chunks );

		$localize = array(
			'plugin_url'         => SPECTRA_BLOCKS_URL,
			'is_rtl'             => is_rtl() ? '1' : '',
			'spectra_pro_status' => $spectra_pro_status,
			'current_post_id'    => get_the_ID(),
			'home_url'           => home_url(),
			'ajax_url'           => admin_url( 'admin-ajax.php' ),
			'tablet_breakpoint'  => 1024,
			'mobile_breakpoint'  => 767,
			'wp_version'         => get_bloginfo( 'version' ),
			'uagb_svg_icons'     => $all_icons,
		);

		wp_add_inline_script(
			'wp-blocks',
			'var spectra_blocks_info = ' . wp_json_encode( $localize ) . ';',
			'before'
		);

		// Set wp.UAGBSvgIcons (array of icon name keys) and wp.uagb_icon_category_list
		// required by the icon-picker component.
		// array_merge() re-indexes integer-like keys ('0', '1'...) to PHP integers.
		// array_keys() would return those as integers, becoming JS numbers in wp.UAGBSvgIcons.
		// Number icons (e.g. '0') would then be falsy in JS, breaking icon || 'star' fallback.
		// Cast all keys to strings so icon names like "0" stay truthy in JavaScript.
		$icon_keys  = array_map( 'strval', array_keys( $all_icons ) );
		$categories = array();
		foreach ( $all_icons as $icon_data ) {
			if ( ! empty( $icon_data['custom_categories'] ) && is_array( $icon_data['custom_categories'] ) ) {
				foreach ( $icon_data['custom_categories'] as $cat_slug ) {
					if ( ! isset( $categories[ $cat_slug ] ) ) {
						$categories[ $cat_slug ] = array(
							'slug'  => $cat_slug,
							'title' => ucwords( str_replace( '-', ' ', $cat_slug ) ),
						);
					}
				}
			}
		}

		wp_add_inline_script(
			'wp-blocks',
			'wp.UAGBSvgIcons = ' . wp_json_encode( $icon_keys ) . '; ' .
			'wp.uagb_icon_category_list = ' . wp_json_encode( array_values( $categories ) ) . ';',
			'before'
		);
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
			'spectra-blocks-swiper-style',
			SPECTRA_BLOCKS_URL . 'assets/css/swiper-bundle.min.css',
			array(),
			'12.1.3'
		);

		wp_register_script(
			'spectra-blocks-swiper-script',
			SPECTRA_BLOCKS_URL . 'assets/js/swiper-bundle.min.js',
			array(),
			'12.1.3',
			true
		);
		// Swiper 12 declares `const Swiper = ...` at the script's top level, which
		// is reachable only by bare-identifier lookup from other classic scripts.
		// ES modules (the slider block's editor + view bundles emitted with
		// --experimental-modules) cannot see that binding, so they read
		// `window.Swiper` and find `undefined` → `new Swiper(...)` throws
		// "is not a constructor" and the block's editor render crashes with the
		// "encountered an error" boundary. Pin Swiper onto `window` so it is
		// reachable from both module and classic contexts.
		wp_add_inline_script(
			'spectra-blocks-swiper-script',
			'window.Swiper = window.Swiper || (typeof Swiper !== "undefined" ? Swiper : undefined);',
			'after'
		);

		wp_register_script(
			'spectra-blocks-modal-script',
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
			wp_enqueue_style( 'spectra-blocks-swiper-style' );
			wp_enqueue_script( 'spectra-blocks-swiper-script' );
			wp_enqueue_script( 'spectra-blocks-modal-script' );
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
		wp_enqueue_style( 'spectra-blocks-extensions-image-mask' );
		wp_enqueue_style( 'spectra-blocks-extensions-z-index' );
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
		if ( ! function_exists( 'spectra_get_v3_blocks_css_for_preview' ) ) {
			require_once SPECTRA_BLOCKS_DIR . 'includes/utils.php';
		}

		return spectra_get_v3_blocks_css_for_preview( $post_id );
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
		$v3_block_styles = self::get_v3_css( $post_id );

		if ( empty( $v3_block_styles ) || ! is_string( $v3_block_styles ) ) {
			return false;
		}

		$upload_dir = self::get_upload_dir_path();
		if ( empty( $upload_dir ) ) {
			return false;
		}

		$filename      = $post_id > 0 ? "spectra-blocks-{$post_id}.css" : 'spectra-blocks.css';
		$v3_cache_path = $upload_dir . $filename;

		$wp_filesystem = self::get_filesystem();
		if ( ! $wp_filesystem ) {
			return false;
		}

		return false !== $wp_filesystem->put_contents( $v3_cache_path, $v3_block_styles, FS_CHMOD_FILE );
	}

	/**
	 * Get the Spectra Blocks upload directory path.
	 *
	 * @since 1.0.0
	 *
	 * @return string Upload directory path with trailing slash, or empty string on failure.
	 */
	private static function get_upload_dir_path() {
		$wp_upload_dir = wp_upload_dir( null, false );

		if ( empty( $wp_upload_dir['basedir'] ) ) {
			return '';
		}

		$dir = trailingslashit( $wp_upload_dir['basedir'] ) . 'spectra-blocks/';

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		return $dir;
	}

	/**
	 * Get the WP_Filesystem instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_Filesystem_Base|false Filesystem instance or false on failure.
	 */
	private static function get_filesystem() {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem ? $wp_filesystem : false;
	}
}
