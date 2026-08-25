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
	 * Marker the block-converter stamps on every container it emits, meaning
	 * "the source owns this block's spacing". SSOT: block-converter's
	 * `markers.ts`. Travels WITH the block, so it reaches converter-built
	 * sections on pages the importer never wrote.
	 */
	const NO_BLOCK_GAP_MARKER = 'spectra-no-block-gap';

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

		// NOTE: the `<canvas>` KSES allowance is NOT registered here. It widens
		// the allow-list for every `post`-context `wp_kses_post()` call on the
		// request — comment text, widget text, any third party's — when only the
		// content block's own call needs it. {@see self::with_canvas_allowed()}
		// wraps that one call instead.

		// Import-marker meta (the body-class detector's source of truth) —
		// registered so the importer can set it over REST at page-write time.
		add_action( 'init', array( $this, 'register_import_marker_meta' ) );

		// Imported pages own ALL their spacing (the converter bakes the source's
		// cascade into gs-* classes; undeclared axes must stay on the UA
		// baseline the source rendered with). Core's layout support injects
		// `is-layout-*` / `wp-container-*` classes whose global blockGap rules
		// (`:root :where(.is-layout-flow) > *` at (0,1,0)) then re-margin every
		// child — a channel that provably CANNOT be neutralized with a
		// constant-value counter-rule, because authored reset lanes span
		// (0,0,1) `body *` … (0,1,2) `body .card p`, overlapping every altitude
		// a counter could take (measured both ways, 2026-08-16: zeroing cost
		// UA-reliant sources their paragraph margins; `revert` re-inflated
		// reset-based sources). Stripping the classes kills the injection at
		// its source: the gap rules simply never match. Named block filters run
		// AFTER core's generic `render_block` layout filter, so the classes
		// exist by the time this runs; render-time, so already-imported pages
		// heal without re-import. Scoped to spectra/container — the imported
		// tree's structural mass — leaving core/post-content's constrained
		// centering untouched.
		add_filter( 'render_block_spectra/container', array( $this, 'strip_layout_classes_on_imported_pages' ), 20, 2 );

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
	 * Frontend: allow the inert `<canvas>` tag through `wp_kses_post` while
	 * rendering an imported (zip-built) singular view. WordPress' `post` KSES
	 * context drops `<canvas>`, so the content block's `wp_kses_post( $text )`
	 * strips a JS-drawn canvas (e.g. an imported hero visualization) before the
	 * block's `spectraCustomJS` can draw on it. `<canvas>` is inert — no `src`,
	 * no script, and `on*` handlers are stripped by KSES regardless — so this
	 * only lets the element survive, adding no script-execution surface.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>|mixed $tags    Allowed tags for this context.
	 * @param string                     $context KSES context (e.g. `post`).
	 * @return array<string, mixed>|mixed Possibly-extended tags.
	 */
	public static function allow_canvas_on_zip_built_pages( $tags, $context ) {
		if ( 'post' !== $context || ! is_array( $tags ) ) {
			return $tags;
		}

		// Frontend singular imported views only — the block editor / REST
		// render never runs the drawing script, so a canvas would be blank.
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_singular() ) {
			return $tags;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id || ! self::is_zip_built_page( (int) $post_id ) ) {
			return $tags;
		}

		$tags['canvas'] = array(
			'id'          => true,
			'class'       => true,
			'style'       => true,
			'width'       => true,
			'height'      => true,
			'role'        => true,
			'aria-label'  => true,
			'aria-hidden' => true,
		);

		return $tags;
	}

	/**
	 * Run `wp_kses_post()` on imported block text with `<canvas>` permitted.
	 *
	 * The allowance is added and removed around THIS call only. Registering the
	 * filter for the whole request widened the allow-list for every other
	 * `post`-context `wp_kses_post()` on the page too — comments, widgets,
	 * anything a third party sanitises — which is far more surface than the one
	 * block that needs it.
	 *
	 * @since 1.0.4
	 *
	 * @param string $text Raw block text.
	 * @return string Sanitised HTML.
	 */
	public static function with_canvas_allowed( string $text ): string {
		$allow = array( self::class, 'allow_canvas_on_zip_built_pages' );

		add_filter( 'wp_kses_allowed_html', $allow, 10, 2 );
		$out = wp_kses_post( $text );
		remove_filter( 'wp_kses_allowed_html', $allow, 10 );

		return $out;
	}

	/**
	 * The layout type WordPress will actually render for a block, resolved the
	 * same way core does in `wp_render_layout_support_flag()`
	 * (wp-includes/block-supports/layout.php): the block's own `attrs.layout`
	 * when present, otherwise the block type's registered
	 * `supports.layout.default` — which for `spectra/container` is FLEX. An
	 * absent attribute is therefore NOT flow, and treating it as flow stripped
	 * the layout off every container that never wrote one.
	 *
	 * `inherit`/`contentSize` force `constrained`, mirroring core, and a layout
	 * array carrying no `type` falls to core's `default` (flow) classname.
	 *
	 * @since 1.0.6
	 *
	 * @param array<string,mixed> $block Parsed block.
	 * @return string Resolved layout type ('default' when flow).
	 */
	private static function resolved_layout_type( $block ): string {
		$attrs  = is_array( $block ) && isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$layout = isset( $attrs['layout'] ) && is_array( $attrs['layout'] ) ? $attrs['layout'] : null;

		if ( null === $layout ) {
			$name       = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';
			$registered = '' !== $name ? \WP_Block_Type_Registry::get_instance()->get_registered( $name ) : null;
			$supports   = ( $registered instanceof \WP_Block_Type ) && is_array( $registered->supports ) ? $registered->supports : array();
			$fallback   = $supports['layout']['default'] ?? ( $supports['__experimentalLayout']['default'] ?? array() );
			$layout     = is_array( $fallback ) ? $fallback : array();
		}

		if ( ! empty( $layout['inherit'] ) || ! empty( $layout['contentSize'] ) ) {
			return 'constrained';
		}

		return isset( $layout['type'] ) && is_string( $layout['type'] ) ? $layout['type'] : 'default';
	}

	/**
	 * Frontend render: remove core layout-support classes from an imported
	 * page's container markup, so the global blockGap margin/gap rules those
	 * classes key on never apply. See the hook registration in {@see init()}
	 * for the full why (the counter-rule impossibility measurement).
	 *
	 * Removes `is-layout-{type}`, `wp-block-…-is-layout-{type}` and generated
	 * `wp-container-…` tokens from the FIRST tag only — that is the wrapper
	 * core decorated; inner markup is the block's own children, each filtered
	 * on its own render. Editor and non-imported pages are untouched, and so is
	 * any container whose layout is flex/grid/constrained (see the body: those
	 * classes ARE that container's layout).
	 *
	 * Known scope limit: importedness is decided from the QUERIED post, so a
	 * flow container rendered from a shared template part or a query-loop item
	 * is treated as imported while an imported page is being viewed. The blast
	 * radius is one channel — core's flow blockGap margin — and the layout gate
	 * above keeps every flex/grid/constrained part intact.
	 *
	 * @since 1.0.6
	 *
	 * @param string              $block_content Rendered block HTML.
	 * @param array<string,mixed> $block         Parsed block (attrs decide the layout gate).
	 * @return string Block HTML with flow layout classes stripped on imported pages.
	 */
	public function strip_layout_classes_on_imported_pages( $block_content, $block = array() ) {
		if ( is_admin() || ! is_string( $block_content ) || '' === $block_content ) {
			return $block_content;
		}

		// TWO independent reasons to strip, either sufficient:
		//
		// 1. The PAGE is imported — the original gate. Whole-page fact, so it
		// needs the singular query context.
		// 2. This CONTAINER carries the converter's `spectra-no-block-gap`
		// marker. A BLOCK-level fact that travels with the block, which is
		// what reaches converter-built sections on pages the importer never
		// wrote: the vibe editor inserts them into editor-created drafts, so
		// reason 1 never fires and core re-margined them (measured
		// 2026-08-21: `:root :where(.is-layout-flow) > *` put 17.81px on a
		// flex button row's second button).
		//
		// Deliberately NOT a stylesheet counter-rule. Killing the injection at
		// its source is the mechanism this file already proves; a constant-value
		// counter cannot work here (see the filter registration comment), and a
		// `revert` counter is worse still — at (0,1,0) it also outranks the
		// theme's own top-level element styles, which core emits UNWRAPPED at
		// (0,0,1) (`class-wp-theme-json.php`, `$element_only_selector`), so it
		// would roll a themed h2/p back to the UA value on every marked section.
		if ( ! self::has_no_block_gap_marker( $block ) && ! self::is_imported_singular() ) {
			return $block_content;
		}

		// FLOW containers only. The blockGap injection this strip exists to kill
		// is the flow channel — core's global `:where(.is-layout-flow) > *
		// { margin-block-start: … }`, which is keyed on the CLASS and therefore
		// cannot be prevented through block attributes; removing the class is the
		// only lever. But `spectra/container` takes its display/gap/direction from
		// core layout support (`supports.layout.default` in block.json is FLEX; the
		// block ships no `display:flex` of its own), so stripping these classes off
		// a flex or grid container would delete its layout outright — a row would
		// collapse to a stack and the authored gap would vanish. Converter output
		// is mostly flow, but NOT always: `content.ts` emits a flex row precisely
		// when the source has no flex of its own (so no gs-* class supplies
		// `display`), and the anchor-button/navigation transforms emit flex too.
		//
		// The layout type is therefore resolved exactly as core resolves it in
		// `wp_render_layout_support_flag()` (wp-includes/block-supports/layout.php):
		// the block's own `attrs.layout` when present, otherwise the block type's
		// registered `supports.layout.default` — an ABSENT attribute means FLEX for
		// this block, not flow. Reading absent as flow stripped the layout off every
		// container that never wrote the attribute.
		$layout_type = self::resolved_layout_type( $block );
		if ( 'default' !== $layout_type && 'flow' !== $layout_type ) {
			return $block_content;
		}

		$processor = new \WP_HTML_Tag_Processor( $block_content );
		if ( ! $processor->next_tag() ) {
			return $block_content;
		}

		// `class_list()` normalises the tokens and handles tab/newline/multi-space
		// separators a manual explode does not. Materialised first: removing while
		// iterating the live attribute is not a supported traversal.
		$tokens = array();
		foreach ( $processor->class_list() as $token ) {
			$tokens[] = $token;
		}
		foreach ( $tokens as $token ) {
			if (
				0 === strpos( $token, 'is-layout-' ) ||
				0 === strpos( $token, 'wp-container-' ) ||
				false !== strpos( $token, '-is-layout-' )
			) {
				$processor->remove_class( $token );
			}
		}

		return $processor->get_updated_html();
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
	/**
	 * Whether the CURRENT request is a singular view of an imported page.
	 *
	 * @return bool True when the queried object is a zip-built page.
	 */
	private static function is_imported_singular(): bool {
		if ( ! is_singular() ) {
			return false;
		}
		$post_id = (int) get_queried_object_id();
		return $post_id > 0 && self::is_zip_built_page( $post_id );
	}

	/**
	 * Whether this container carries the converter's no-block-gap marker.
	 *
	 * The converter stamps `spectra-no-block-gap` unconditionally on every
	 * container it emits — "the source owns this block's spacing". Read from the
	 * block's OWN attributes, not the rendered class attribute: the marker is an
	 * authoring fact, and reading attrs cannot be confused by a class some later
	 * filter added. Matched on the whitespace-delimited token so a longer class
	 * that merely starts with the same prefix cannot pass.
	 *
	 * @param array<string,mixed> $block Parsed block.
	 * @return bool True when the marker is present.
	 */
	private static function has_no_block_gap_marker( $block ): bool {
		if ( ! is_array( $block ) || ! isset( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) {
			return false;
		}
		$class_name = $block['attrs']['className'] ?? '';
		if ( ! is_string( $class_name ) || '' === $class_name ) {
			return false;
		}
		$tokens = preg_split( '/\s+/', $class_name );
		return is_array( $tokens ) && in_array( self::NO_BLOCK_GAP_MARKER, $tokens, true );
	}

	/**
	 * Whether a post was written by the importer.
	 *
	 * Result is memoised per post id — the strip filter runs once per container,
	 * and a page carries many.
	 *
	 * @since 1.0.6
	 *
	 * @param int $post_id Post to test.
	 * @return bool True when the importer marked this post.
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

		// Run-scoped identity stamps the importer writes alongside the marker.
		// Protected (underscore) keys are silently DROPPED by REST unless
		// registered with show_in_rest, which would disable the importer's
		// reclaim/dedup probes entirely. Subtype registration is inert when the
		// post type is absent (e.g. SureForms not installed), so no guards.
		$identity_keys = array(
			'page'           => array( '_zipai_import_id' ),
			'sureforms_form' => array( '_zipai_import_id', '_zipai_form_hash' ),
			'wp_navigation'  => array( '_zipai_nav_hash' ),
		);
		foreach ( $identity_keys as $post_type => $keys ) {
			foreach ( $keys as $key ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'          => 'string',
						'single'        => true,
						'default'       => '',
						'show_in_rest'  => true,
						'auth_callback' => static function ( $allowed, $meta_key, $post_id ) {
							return current_user_can( 'edit_post', $post_id );
						},
					)
				);
			}
		}
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
