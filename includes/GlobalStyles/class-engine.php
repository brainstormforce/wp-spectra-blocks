<?php
/**
 * Global Styles Engine — headless CSS output for ERA-built sites.
 *
 * Outputs utility-class CSS from the ClassRegistry plus any user-defined
 * classes, variables, and keyframes stored in the shared option.
 *
 * When Pro (spectra-blocks-pro) is active, Pro's own GlobalStyles extension
 * owns the full pipeline (post-meta filtering, block defaults, preview
 * builder, editor inspector). This engine yields to Pro in that case and
 * only provides the ClassRegistry for Pro to consume — no duplicate CSS.
 *
 * When Pro is absent (ERA sites), this engine is solely responsible for
 * rendering the utility-class CSS that ERA baked into block markup.
 *
 * @package Spectra\GlobalStyles
 * @since   1.0.0
 */

namespace SpectraBlocks\GlobalStyles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Engine.
 *
 * @since 1.0.0
 */
class Engine {

	/**
	 * Option key holding custom classes, variables, and keyframes (site-wide).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_KEY_USER_CSS = 'spectra_blocks_pro_gs_user_css';

	/**
	 * REST API namespace.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const REST_NAMESPACE = 'spectra-blocks/v1';

	/**
	 * Cache group shared with the Style Guide engine.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CACHE_GROUP = 'spectra_blocks';

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Engine|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Engine
	 */
	public static function get_instance(): Engine {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Bootstrap the engine.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init(): void {
		$instance = self::get_instance();

		// REST endpoints are always registered — Pro and ERA both consume them.
		add_action( 'rest_api_init', array( $instance, 'register_rest_routes' ) );

		// Invalidate dynamic class cache + JIT version stamp when Style Guide config changes.
		add_action( 'spectra_style_guide_config_saved', array( ClassRegistry::class, 'invalidate_cache' ) );
		add_action( 'spectra_style_guide_config_saved', array( JitCache::class, 'bump_version' ) );

		// Bump JIT version stamp when custom classes/variables/keyframes change.
		// (The region-keyed V2 site-wide write also targets this option, so the
		// same hooks cover it.).
		add_action( 'update_option_' . self::OPTION_KEY_USER_CSS, array( JitCache::class, 'bump_version' ) );
		add_action( 'add_option_' . self::OPTION_KEY_USER_CSS, array( JitCache::class, 'bump_version' ) );

		// Scrub orphan `.gc-spectra-*` selectors from the per-page Gen CSS post
		// meta on write. The class tokens are dropped from HTML server-side but
		// can leak through into the stored CSS, producing dead rules.
		GenCssOrphanStripper::register();

		// Per-post compile trigger on save.
		add_action( 'save_post', array( $instance, 'compile_post_on_save' ), 20, 3 );

		// Per-page imported CSS — renders the `spectra_blocks_pro_gs_user_css`
		// post meta (the region-keyed body payload, written by zip-ai's
		// `spectra/set-page-custom-css` ability). Priority 100 — ONE TICK AFTER
		// the utility/JIT enqueues (99): the page payload must REGISTER after
		// `spectra-gs-utility-classes` exists so its dependency pin resolves, and
		// the page sheet prints after the sitewide class surface — page owns
		// (0,3,0) ties.
		add_action( 'enqueue_block_assets', array( $instance, 'enqueue_gen_custom_css_for_current_post' ), 100 );

		// Region-keyed V2 site-wide NON-CLASS render (rootStyles/wrapperStyles/…
		// from the GBS option `spectra_blocks_pro_gs_user_css`; classes render via
		// the utility stylesheet). On by default; the
		// `spectra_blocks_render_gen_sitewide` filter can disable it.
		add_action( 'enqueue_block_assets', array( $instance, 'enqueue_gen_sitewide_css' ), 99 );

		// Hand the (page-agnostic) site-wide chrome CSS to the GBS editor JS so it
		// can re-inject it into the canvas after Site Editor SPA navigation, which
		// drops the enqueue_block_assets seed above. Priority 20 runs AFTER the
		// extension manager (default 10) registers the gbs-editor script handle.
		add_action( 'enqueue_block_editor_assets', array( $instance, 'localize_sitewide_editor_css' ), 20 );

		// Print-order pin: WP core enqueues the theme's `global-styles` inside
		// the same `wp_enqueue_scripts` pass that fires `enqueue_block_assets`,
		// and the relative order is registration-order trivia — observed live:
		// our sheets printed BEFORE theme global styles, so equal-specificity
		// rules lost by source order (imported body typography fell to the
		// theme's). Pin deterministically: after everything is enqueued, make
		// every `spectra-gen-*` / `spectra-gs-*` handle DEPEND on
		// `global-styles`, so the dependency resolver always prints ours after
		// the theme. Frontend only — the editor scopes via descendants and has
		// its own print path.
		add_action( 'wp_enqueue_scripts', array( $instance, 'pin_styles_after_theme_globals' ), PHP_INT_MAX );

		// If Pro is present, Pro's GlobalStyles extension owns the stylesheet output.
		// Free's engine yields to avoid duplicate CSS.
		//
		// GBS V1→V2 consolidation (behind `spectra_blocks_gbs_unified_render`,
		// default false): when the flag is ON, free STOPS yielding so
		// `GenCssRenderer` becomes the single class renderer on Pro sites too
		// (Pro's `generate_gs_stylesheet` then trims to block-defaults only).
		// Flag OFF (default) = current behaviour exactly. NOTE: the flag-on path
		// is not complete until the Pro-side trim + legacy read-compat land — do
		// NOT enable it until then. See GBS-V1-V2-CONSOLIDATION-ROADMAP.
		if ( class_exists( '\\SpectraBlocksPro\\Extensions\\GlobalStyles' ) && ! self::is_unified_render() ) {
			return;
		}

		// Pro-less path (ERA sites): emit utility-class CSS directly on frontend + editor.
		// `enqueue_block_assets` fires for both frontend and the block editor iframe, so
		// a single hook covers every render context where JIT output is needed.
		// High priority (99) so utility + JIT stylesheets enqueue AFTER WP core's
		// `global-styles-inline-css` (theme.json output). Source order matters:
		// at equal specificity, the later rule wins — and `:root :where(...)`
		// theme.json selectors share 0,1,0 specificity with our `.bg-[#hex]`
		// utility class, so we must emit after to win cleanly.
		add_action( 'enqueue_block_assets', array( $instance, 'enqueue_stylesheet' ), 99 );
		add_action( 'enqueue_block_assets', array( $instance, 'enqueue_jit_for_current_post' ), 99 );

		/**
		 * Fires after the Global Styles engine has finished initialization.
		 *
		 * @since 1.0.0
		 *
		 * @param Engine $instance The initialized engine instance.
		 */
		do_action( 'spectra_global_styles_engine_loaded', $instance );
	}

	/**
	 * Whether the unified GBS render path (V1→V2 consolidation) is active.
	 *
	 * When true, the free `GenCssRenderer` is the single class renderer even
	 * with Pro active — the free engine stops yielding and Pro's
	 * `generate_gs_stylesheet` trims to block-defaults only. Default false =
	 * legacy behaviour (Pro owns the utility sheet; free yields). Both plugins
	 * read THIS method so the flag has a single source of truth. Staged rollout;
	 * see GBS-V1-V2-CONSOLIDATION-ROADMAP. Not complete until the Pro trim +
	 * legacy read-compat land — keep it off until then.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_unified_render(): bool {
		/**
		 * Filters whether the unified (V2-only) GBS class render path is active.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $enabled Whether unified rendering is on. Default false.
		 */
		return (bool) apply_filters( 'spectra_blocks_gbs_unified_render', false );
	}

	/**
	 * Emit the full utility-class stylesheet as inline CSS.
	 *
	 * Outputs every class from ClassRegistry plus any user-defined classes,
	 * variables, and keyframes. No per-post filtering in free — Pro layers
	 * that optimization on top via its own stylesheet generator.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_stylesheet(): void {
		$css = $this->build_stylesheet_css();

		if ( '' === $css ) {
			return;
		}

		$handle = 'spectra-gs-utility-classes';
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters -- Inline-only stylesheet; no src/version needed.
		wp_register_style( $handle, false, array(), null );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, $css );
	}

	/**
	 * Enqueue JIT-compiled CSS for the currently-resolved post.
	 *
	 * Uses a dedicated `spectra-gs-dynamic-styles` handle so the static
	 * utility stylesheet stays cacheable per site while per-post JIT output
	 * bumps only when its post changes. Both are wrapped in
	 * `@layer utilities` so block-default non-layered rules (e.g.
	 * `.wp-block-spectra-container { display: flex }`) cannot beat the
	 * utility cascade on specificity alone.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_jit_for_current_post(): void {
		$post_id = $this->resolve_current_post_id();
		if ( $post_id <= 0 ) {
			return;
		}

		$css = JitCache::get_for_post( $post_id );
		if ( '' === $css ) {
			return;
		}

		$handle = 'spectra-gs-dynamic-styles';
		if ( ! wp_style_is( $handle, 'registered' ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters -- Inline-only stylesheet; no src/version needed.
			wp_register_style( $handle, false, array( 'spectra-gs-utility-classes' ), null );
			wp_enqueue_style( $handle );
		}

		// Emit unlayered: block-default rules (e.g. `.wp-block-spectra-container
		// { padding: 10px }`) are unlayered, and @layer rules lose to unlayered
		// rules at equal specificity. Utilities must also be unlayered so they
		// win by source order (dynamic styles enqueue after block style-index).
		wp_add_inline_style( $handle, $css );
	}

	/**
	 * Emit the per-page imported CSS for the currently-resolved post.
	 *
	 * The CSS lives in the `spectra_blocks_pro_gs_user_css` post meta (written by the
	 * import pipeline's `spectra/set-page-custom-css` ability in zip-ai). Emitted
	 * as a standalone inline `<style>` via a false-src handle so an `@import` at
	 * position 0 is valid CSS — in both frontend and the block-editor iframe.
	 *
	 * The stored value is the schema-v1 structured payload (an array); it is
	 * rendered to CSS for the LIVE post id via {@see GenCssRenderer}. Reads go
	 * through {@see GenCssOrphanStripper::read_page_payload()} — the one reader.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_gen_custom_css_for_current_post(): void {
		$post_id = $this->resolve_current_post_id();
		if ( $post_id <= 0 ) {
			return;
		}

		// phpcs:disable Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar -- Illustrative payload shape, not dead code.
		// `$stored` is the schema-v1 payload — a PAGE-AGNOSTIC array (no page id,
		// no `!important`). Example of what get_post_meta() returns here:
		//
		// array(
		// 'v'             => '1',
		// 'imports'       => array( 'https://fonts.googleapis.com/css2?family=Lato' ),
		// 'scopeVars'     => array( '--wp--style--global--content-size' => '1164px',
		// '--wp--style--global--wide-size'    => '1280px' ),
		// 'rootStyles'    => array( 'font-family' => 'DM Sans', 'background' => '#fbf6ec',
		// '--primary' => '#b36b2c' ),         // body/:root rule
		// 'presetLock'    => array( '--wp--preset--color--primary' => '#b36b2c' ),
		// 'classes'       => array(
		// 'gs-992735-link' => array(                                  // className (no dot)
		// 'default' => array( 'color' => 'var(--heading)' ),       // state → declarations
		// 'hover'   => array( 'color' => '#b36b2c' ),
		// ),
		// 'tdrx-faq-item'  => array( '[open]' => array( 'background' => '#fff' ) ), // raw-tail state
		// ),
		// 'wrapperStyles' => array(                                       // full selector → declarations
		// '.wp-block-spectra-icon svg'        => array( 'width' => '1em', 'height' => '1em' ),
		// ':is(.wp-element-button, .wp-block-button__link)' => array( 'line-height' => 'normal' ),
		// ),
		// 'mediaQuery'    => array(                                       // free-form media string
		// '(max-width: 960px)' => array(
		// 'classes'       => array( 'tdrx-hdr-nav' => array( 'default' => array( 'gap' => '1.5rem' ) ) ),
		// 'wrapperStyles' => array( '.tdrx-hdr-nav a:not(.tdrx-hdr-cta)' => array( 'display' => 'none' ) ),
		// ),
		// ),
		// )
		// phpcs:enable Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar
		//
		// GenCssRenderer::render() applies the context-aware scope (frontend
		// `body` / `[class].class`; editor `.editor-styles-wrapper …`)
		// and emits the CSS string (see that method for the per-bucket rules).
		$stored = GenCssOrphanStripper::read_page_payload( $post_id );
		if ( null === $stored ) {
			return;
		}
		$css = GenCssRenderer::render( $stored, (int) $post_id, is_admin() );
		if ( '' === $css ) {
			return;
		}

		// In the editor the SaaS-generated propagation rule
		// [class*="gc-spectra"] > .block-editor-inner-blocks > .block-editor-block-list__layout { ... }
		// has specificity 0,3,0 and overrides the WordPress wp-container-* layout
		// classes, collapsing flex containers. Strip it in admin context — WP's own
		// wp-container-* system provides the correct flex-direction/alignment there.
		if ( is_admin() ) {
			$css = preg_replace( '/[^{}]*\.block-editor-inner-blocks[^{}]*\{[^{}]*\}/i', '', (string) $css );

			// Strip gc-spectra-* `display:grid` rules: on the frontend the wrapper
			// directly holds child blocks so the grid works; in the editor WP injects
			// `.block-editor-inner-blocks`, squeezing content to 1/N width. WP's own
			// `is-layout-grid` covers the editor.
			$css = preg_replace( '/body\s+[^{}]*\.gc-spectra[^{}]*\{[^{}]*display\s*:\s*grid[^{}]*\}/i', '', (string) $css );
		}

		$handle = 'spectra-gen-custom-css-' . $post_id;
		// DEPENDENCY IS LOAD-BEARING: the per-page payload must print AFTER
		// every other (0,3,0) surface it can tie with — page owns ties by
		// contract:
		// - `spectra-gs-utility-classes` (sitewide option's classes;
		// `[class].x.x` shape — live: the sitewide button-reset cluster
		// printed after the page sheet and zeroed `.embr-faq-q` padding);
		// - `spectra-responsive-styles` (per-block responsive CSS; its
		// block-default rules use a TRIPLED `.block.block.block` shape =
		// (0,3,0) — live: google-map's 400px height default printed
		// after the page sheet and beat the source's `height: 100%`
		// class rule, +20px on the visit section at 390px).
		$deps = array();
		foreach ( array( 'spectra-gs-utility-classes', 'spectra-responsive-styles' ) as $tie_surface ) {
			if ( wp_style_is( $tie_surface, 'registered' ) || wp_style_is( $tie_surface, 'enqueued' ) ) {
				$deps[] = $tie_surface;
			}
		}
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters -- Inline-only stylesheet; no src/version needed.
		wp_register_style( $handle, false, $deps, null );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, (string) $css );
	}

	/**
	 * Make every enqueued `spectra-gen-*` / `spectra-gs-*` style depend on the
	 * theme's `global-styles` handle so the dependency resolver prints ours
	 * AFTER theme.json output — deterministic source order instead of hook
	 * trivia. No-op when the theme has no `global-styles` (classic themes).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function pin_styles_after_theme_globals(): void {
		if ( ! wp_style_is( 'global-styles', 'registered' ) && ! wp_style_is( 'global-styles', 'enqueued' ) ) {
			return;
		}

		$styles = wp_styles();
		foreach ( $styles->queue as $handle ) {
			$handle = (string) $handle;
			if ( 0 !== strpos( $handle, 'spectra-gen-' ) && 0 !== strpos( $handle, 'spectra-gs-' ) ) {
				continue;
			}
			$registered = $styles->registered[ $handle ] ?? null;
			if ( null === $registered || in_array( 'global-styles', (array) $registered->deps, true ) ) {
				continue;
			}
			$registered->deps[] = 'global-styles';
		}
	}

	/**
	 * Region-keyed V2: render the import's site-wide NON-CLASS buckets
	 * (`rootStyles` / `wrapperStyles` / `scopeVars` / `presetLock` / `imports` /
	 * `mediaQuery`) from the GBS option (`OPTION_KEY_USER_CSS`) into an inline
	 * stylesheet on every page. The site-wide `classes` + `keyframes` in that
	 * option are already rendered by `enqueue_stylesheet`/`render_user_classes`,
	 * so they are excluded here to avoid double output.
	 *
	 * On by default; the `spectra_blocks_render_gen_sitewide` filter can disable it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_gen_sitewide_css(): void {
		$css = $this->build_sitewide_css( is_admin() );
		if ( '' === trim( $css ) ) {
			return;
		}

		$handle = 'spectra-gen-sitewide-css';
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters -- Inline-only stylesheet; no src/version needed.
		wp_register_style( $handle, false, array(), null );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, $css );
	}

	/**
	 * Build the site-wide (chrome) GBS CSS string from the user-CSS option.
	 *
	 * Shared by the front-end/canvas enqueue ({@see enqueue_gen_sitewide_css})
	 * and the editor-canvas localization ({@see localize_sitewide_editor_css}).
	 * The selectors are page-agnostic — the shared chrome (header/footer +
	 * site-wide classes) applies wherever it renders.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_editor Render with editor scope (`.editor-styles-wrapper`)
	 *                        when true; front-end scope (`body` / `[class]`) when false.
	 * @return string The rendered CSS, or '' when nothing to render / disabled.
	 */
	private function build_sitewide_css( bool $is_editor ): string {
		/**
		 * Gate the region-keyed V2 site-wide (non-class) render. Default true.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $enabled Whether to render the site-wide gen CSS.
		 */
		if ( ! apply_filters( 'spectra_blocks_render_gen_sitewide', true ) ) {
			return '';
		}

		$option = get_option( self::OPTION_KEY_USER_CSS, array() );
		if ( ! is_array( $option ) ) {
			return '';
		}

		// Whether Pro owns the utility-class stylesheet. This MUST mirror the
		// yield condition in init() (the `class_exists( … Pro … GlobalStyles )`
		// early return that skips registering `enqueue_stylesheet`).
		//
		// REGRESSION FIX 2026-06-17: Phase 2 (commit 37efa93) folded the separate
		// site-wide option into OPTION_KEY_USER_CSS and made this method render
		// ONLY the non-class buckets, on the assumption that `enqueue_stylesheet`
		// renders the option's `classes`/`keyframes`. That assumption is false
		// when Pro is active: init() yields and never registers
		// `enqueue_stylesheet`, and Pro's GlobalStyles extension renders a
		// DIFFERENT option (`spectra_pro_gs_user_css`, no "blocks"), not this
		// canonical key — so the site-wide chrome `classes`/`keyframes` rendered
		// nowhere on Pro sites. When Pro owns the utility sheet we therefore
		// render `classes` here (via GenCssRenderer, exactly as the per-page meta
		// path does) and append `keyframes`. On Pro-less (ERA) sites we keep
		// excluding them so `enqueue_stylesheet` stays the sole renderer.
		//
		// Unified render (V1→V2): when the flag is ON, init() no longer yields —
		// free's `enqueue_stylesheet` renders the classes on Pro sites too — so we
		// must NOT also render them here (that would double-emit). Treat it like
		// the Pro-less case.
		$pro_owns_utility_sheet = class_exists( '\\SpectraBlocksPro\\Extensions\\GlobalStyles' ) && ! self::is_unified_render();

		// Build the payload of import buckets. `keyframes` is not part of
		// GenCssRenderer's schema, so it is rendered separately below. `variables`
		// carries the user's custom CSS variables (the `/custom-vars` bucket) — it
		// is site-wide and rendered on the root by GenCssRenderer.
		$buckets = array( 'rootStyles', 'wrapperStyles', 'scopeVars', 'presetLock', 'variables', 'imports', 'mediaQuery' );
		if ( $pro_owns_utility_sheet ) {
			$buckets[] = 'classes';
		}

		$payload = array( 'v' => '1' );
		foreach ( $buckets as $bucket ) {
			if ( ! empty( $option[ $bucket ] ) && is_array( $option[ $bucket ] ) ) {
				$payload[ $bucket ] = $option[ $bucket ];
			}
		}

		// Style Guide owns the colour palette: when a Style Guide config is saved,
		// drop the import `presetLock`'s `--wp--preset--color--<slug>` entries for the
		// slugs the Style Guide manages, so its palette (theme.json `:root`) is the
		// single source of truth on both the front end and the editor canvas. The
		// import pins those slugs on `body` / `body.editor-styles-wrapper`, which
		// would otherwise beat the Style Guide's inherited `:root` values. Import-only
		// colour slugs (e.g. white/transparent) and the spacing/font-size preset locks
		// are left intact; with no saved Style Guide the imported design remains the
		// sole palette source and the lock is untouched.
		if ( ! empty( $payload['presetLock'] ) && is_array( $payload['presetLock'] ) ) {
			$payload['presetLock'] = $this->strip_style_guide_color_locks( $payload['presetLock'] );
			if ( empty( $payload['presetLock'] ) ) {
				unset( $payload['presetLock'] );
			}
		}

		// Site-wide scope ($sitewide = true): no post-id gate; page-agnostic
		// selectors apply on every page that renders the shared chrome.
		$css = count( $payload ) > 1 ? GenCssRenderer::render( $payload, 0, $is_editor, true ) : '';

		// Render the option's keyframes only when Pro owns the utility sheet
		// (otherwise `enqueue_stylesheet` already emits them — avoid double output).
		if ( $pro_owns_utility_sheet && ! empty( $option['keyframes'] ) && is_array( $option['keyframes'] ) ) {
			$keyframes_css = $this->render_keyframes( $option['keyframes'] );
			if ( '' !== $keyframes_css ) {
				$css .= ( '' === $css ? '' : "\n" ) . $keyframes_css;
			}
		}

		return (string) $css;
	}

	/**
	 * Drop the import `presetLock`'s colour locks for slugs the Style Guide owns.
	 *
	 * A region-keyed import writes a `presetLock` bucket that pins
	 * `--wp--preset--color--<slug>` to the imported palette. Rendered on `body`
	 * (front end) / `body.editor-styles-wrapper` (editor) those direct values beat
	 * the Style Guide palette, which is set on `:root` and only inherited down —
	 * so the Style Guide could never change those slugs. When a Style Guide config
	 * is saved the Style Guide is authoritative for its managed slugs, so their
	 * lock entries are removed here; every other `presetLock` entry (import-only
	 * colours, spacing, font sizes) is preserved. With no saved Style Guide the
	 * imported design is the sole palette source and the bucket is returned as-is.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $preset_lock The `presetLock` bucket.
	 * @return array<string, string> The filtered bucket.
	 */
	private function strip_style_guide_color_locks( array $preset_lock ): array {
		if ( ! class_exists( '\\SpectraBlocks\\StyleGuide\\Engine' ) ) {
			return $preset_lock;
		}

		// Defer to the Style Guide only when it has a saved config; otherwise the
		// imported design owns the palette. Check the raw option (get_config()
		// merges defaults, so it is never truly empty).
		if ( empty( get_option( \SpectraBlocks\StyleGuide\Engine::OPTION_KEY ) ) ) {
			return $preset_lock;
		}

		$slugs = \SpectraBlocks\StyleGuide\Engine::get_instance()->get_managed_color_slugs();
		foreach ( $slugs as $slug ) {
			unset( $preset_lock[ '--wp--preset--color--' . $slug ] );
		}

		return $preset_lock;
	}

	/**
	 * Hand the editor-scoped site-wide (chrome) CSS to the GBS editor JS.
	 *
	 * The front-end/canvas enqueue ({@see enqueue_gen_sitewide_css}) seeds the
	 * INITIAL editor canvas, but the Site Editor swaps the canvas on SPA
	 * navigation without re-running PHP, so the chrome (header/footer) would
	 * drop until a manual refresh. The chrome is page-agnostic, so rather than
	 * re-fetch it over REST we render it ONCE here and expose it as a global the
	 * GBS editor re-injects into the canvas on navigation — no round-trip.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function localize_sitewide_editor_css(): void {
		$css = $this->build_sitewide_css( true );
		if ( '' === trim( $css ) ) {
			return;
		}

		wp_add_inline_script(
			'spectra-3-extension-gbs-editor-editor',
			'window.spectraBlocksGenSitewideCss = ' . wp_json_encode( $css ) . ';',
			'before'
		);
	}

	/**
	 * Compile + cache JIT CSS for a post on save.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update.
	 * @return void
	 */
	public function compile_post_on_save( int $post_id, $post = null, bool $update = false ): void {
		unset( $post, $update );

		if ( $post_id <= 0 ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		JitCache::rebuild( (int) $post_id );
	}

	/**
	 * Resolve the post ID that CSS should be enqueued for.
	 *
	 * Handles frontend singular, editor context, and block-preview iframes.
	 *
	 * @since 1.0.0
	 * @return int Post ID, or 0 if none can be resolved.
	 */
	private function resolve_current_post_id(): int {
		$id = (int) get_the_ID();
		if ( $id > 0 ) {
			return $id;
		}

		if ( is_singular() ) {
			$queried = get_queried_object_id();
			if ( $queried > 0 ) {
				return (int) $queried;
			}
		}

		// Site Editor (site-editor.php?postId=987&postType=page) edits a page
		// that is NOT the main-query/global post — it rides in the request as
		// `postId`. Resolve it (validated to a real, viewable singular post) so
		// the per-page payload loads in the FSE canvas, same as the classic
		// editor. Checked before `global $post` because in the Site Editor that
		// global can be the template, not the page being edited.
		if ( is_admin() && isset( $_GET['postId'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only asset enqueue, no state change.
			$requested = absint( wp_unslash( $_GET['postId'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $requested > 0 && in_array( get_post_type( $requested ), array( 'page', 'post' ), true ) ) {
				return $requested;
			}
		}

		global $post;
		if ( $post instanceof \WP_Post ) {
			return (int) $post->ID;
		}

		return 0;
	}

	/**
	 * Build the stylesheet CSS string.
	 *
	 * Structure: `@layer utilities { <class-registry> <user-classes>
	 * <keyframes> }` — a single cascade layer so block-default non-layered
	 * rules lose to utility output regardless of specificity/order.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function build_stylesheet_css(): string {
		$parts = array();

		$user_css = get_option( self::OPTION_KEY_USER_CSS, array() );
		if ( ! is_array( $user_css ) ) {
			$user_css = array();
		}

		$layer_parts = array();

		// Tailwind v4 preflight — scoped to `.spectra-is-root-container` so the
		// WP theme's non-Spectra UI is untouched. Every rule is wrapped in
		// `:where(.spectra-is-root-container)` which contributes zero
		// specificity, so utility classes (specificity 0,1,0) still win over
		// element-targeted resets. See build_preflight_css() for the full rule
		// list and inline rationale per rule group.
		$layer_parts[] = $this->build_preflight_css();

		$utility_css = $this->render_utility_classes();
		if ( '' !== $utility_css ) {
			$layer_parts[] = $utility_css;
		}

		$user_classes_css = $this->render_user_classes( isset( $user_css['classes'] ) && is_array( $user_css['classes'] ) ? $user_css['classes'] : array() );
		if ( '' !== $user_classes_css ) {
			$layer_parts[] = $user_classes_css;
		}

		$keyframes_css = $this->render_keyframes( isset( $user_css['keyframes'] ) && is_array( $user_css['keyframes'] ) ? $user_css['keyframes'] : array() );
		if ( '' !== $keyframes_css ) {
			$layer_parts[] = $keyframes_css;
		}

		if ( ! empty( $layer_parts ) ) {
			// Unlayered (see enqueue_jit_for_current_post() for rationale).
			$parts[] = implode( "\n\n", $layer_parts );
		}

		return implode( "\n\n", $parts );
	}

	/**
	 * Build the Tailwind v4-parity preflight CSS block.
	 *
	 * Most rules are scoped via `:where(.spectra-is-root-container)` so the
	 * scope adds zero specificity — utility classes (`:root .mt-4 { ... }`
	 * at (0,2,0)) still win over element-targeted resets (0,0,1).
	 *
	 * EXCEPTION: the `margin: 0` reset for headings / paragraph / figure /
	 * list elements (`$resetScope`) uses the bare `.spectra-is-root-container`
	 * selector. Block themes (e.g. spectra-one) emit element rules like
	 * `h3 { margin-top: var(--wp--preset--spacing--medium) }` from theme.json
	 * at specificity (0,0,1). With `:where()` collapsing the Spectra reset to
	 * (0,0,1), the two rules tie on specificity and the theme wins on source
	 * order — a 36 px gap appears above every heading inside a Spectra root
	 * container. Dropping `:where()` raises the reset to (0,1,1), which
	 * beats the theme element selector decisively. Tailwind GBS utilities
	 * still ship with a `:root` prefix → effective specificity (0,2,0)+,
	 * so author-applied `mt-*` / `mb-*` / etc. continue to override the
	 * margin reset.
	 *
	 * Intentional omissions (Tailwind ships these globally; we cannot):
	 * - `html, :host { line-height: 1.5; font-family: ... }` — targets the
	 *   document root; setting body line-height would stomp themes.
	 * - `body { margin: 0 }` — WP themes typically set this themselves.
	 *
	 * @since 1.0.0
	 *
	 * @return string Full preflight CSS block, no trailing newline.
	 */
	private function build_preflight_css(): string {
		$scope      = ':where(.spectra-is-root-container)';
		$resetScope = '.spectra-is-root-container';

		$rules = array();

		// Box-sizing + border reset. Border defaults to `0 solid currentColor`
		// so `.border` (border-width:1px) works without revealing the
		// browser's initial `medium` border-width on unstyled sides.
		$rules[] = $scope . ',' . $scope . ' *,' . $scope . ' ::before,' . $scope . ' ::after,' . $scope . ' ::backdrop{box-sizing:border-box;border-width:0;border-style:solid;border-color:currentColor;}';

		// Seed `--tw-content` so `content-[…]` and before/after variants
		// compose cleanly when used in any order.
		$rules[] = $scope . ' ::before,' . $scope . ' ::after{--tw-content:"";}';

		// NOTE: No h1..h6 font-size/weight/color reset. Zeroing those to
		// `inherit` collapses theme heading styles inside a Spectra root
		// container on Pro-less sites (headings render at body size/weight
		// until a utility overrides), breaking themes/content that rely on
		// the theme's `h1..h6` defaults. Utility classes still win via their
		// own specificity, so the reset is unnecessary.

		// Typographic resets.
		//
		// NOTE: No `a { color: inherit; text-decoration: inherit }` reset.
		// Zeroing anchors makes unstyled content links inherit the surrounding
		// text colour instead of the theme's link colour — and only on the
		// front end (this preflight is emitted for the front-end render but is
		// not applied in the editor canvas), so a link looked theme-coloured in
		// the editor but text-coloured on the front end. Dropping the reset lets
		// unstyled links fall back to the theme default (colour + decoration)
		// consistently in BOTH contexts. Utility/author link styles still win
		// via their own specificity, so the reset is unnecessary.
		$rules[] = $scope . ' b,' . $scope . ' strong{font-weight:bolder;}';
		$rules[] = $scope . ' small{font-size:80%;}';
		$rules[] = $scope . ' sub,' . $scope . ' sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline;}';
		$rules[] = $scope . ' sub{bottom:-0.25em;}';
		$rules[] = $scope . ' sup{top:-0.5em;}';
		$rules[] = $scope . ' abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted;}';

		// Monospace font family for code-ish elements.
		$rules[] = $scope . ' code,' . $scope . ' kbd,' . $scope . ' samp,' . $scope . ' pre{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;font-feature-settings:normal;font-variation-settings:normal;font-size:1em;}';

		// HR.
		$rules[] = $scope . ' hr{height:0;color:inherit;border-top-width:1px;}';

		// Tables.
		$rules[] = $scope . ' table{text-indent:0;border-color:inherit;border-collapse:collapse;}';

		// Margin strip — blocks/headings/paragraph/list spacing is authored
		// via utilities, not inherited from the theme. Uses bare
		// `.spectra-is-root-container` (not `:where()`) so the (0,1,1)
		// specificity beats theme.json element rules like
		// `h3 { margin-top: var(--wp--preset--spacing--medium) }` (0,0,1).
		// See doc-block above for the full rationale.
		$rules[] = $resetScope . ' blockquote,' . $resetScope . ' dl,' . $resetScope . ' dd,' . $resetScope . ' h1,' . $resetScope . ' h2,' . $resetScope . ' h3,' . $resetScope . ' h4,' . $resetScope . ' h5,' . $resetScope . ' h6,' . $resetScope . ' hr,' . $resetScope . ' figure,' . $resetScope . ' p,' . $resetScope . ' pre,' . $resetScope . ' span{margin:0;}';

		// Lists.
		$rules[] = $scope . ' ol,' . $scope . ' ul,' . $scope . ' menu{list-style:none;margin:0;padding:0;}';
		$rules[] = $scope . ' fieldset{margin:0;padding:0;}';
		$rules[] = $scope . ' legend{padding:0;}';

		// Media — block + max-width:100% matches Tailwind + most theme intent.
		$rules[] = $scope . ' img,' . $scope . ' svg,' . $scope . ' video,' . $scope . ' canvas,' . $scope . ' audio,' . $scope . ' iframe,' . $scope . ' embed,' . $scope . ' object{display:block;vertical-align:middle;}';
		$rules[] = $scope . ' img,' . $scope . ' video{max-width:100%;height:auto;}';

		// Form control font/color/margin inheritance.
		$rules[] = $scope . ' button,' . $scope . ' input,' . $scope . ' optgroup,' . $scope . ' select,' . $scope . ' textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0;}';
		$rules[] = $scope . ' button,' . $scope . ' select{text-transform:none;}';
		$rules[] = $scope . ' button,' . $scope . ' input:where([type="button"]),' . $scope . ' input:where([type="reset"]),' . $scope . ' input:where([type="submit"]){-webkit-appearance:button;background-color:transparent;background-image:none;}';
		$rules[] = $scope . ' :-moz-focusring{outline:auto;}';
		$rules[] = $scope . ' :-moz-ui-invalid{box-shadow:none;}';
		$rules[] = $scope . ' progress{vertical-align:baseline;}';
		$rules[] = $scope . ' ::-webkit-inner-spin-button,' . $scope . ' ::-webkit-outer-spin-button{height:auto;}';
		$rules[] = $scope . ' [type="search"]{-webkit-appearance:textfield;outline-offset:-2px;}';
		$rules[] = $scope . ' ::-webkit-search-decoration{-webkit-appearance:none;}';
		$rules[] = $scope . ' ::-webkit-file-upload-button{-webkit-appearance:button;font:inherit;}';
		$rules[] = $scope . ' summary{display:list-item;}';
		$rules[] = $scope . ' dialog{padding:0;}';
		$rules[] = $scope . ' textarea{resize:vertical;}';
		$rules[] = $scope . ' input::placeholder,' . $scope . ' textarea::placeholder{opacity:1;color:#9ca3af;}';

		// Cursors.
		$rules[] = $scope . ' button,' . $scope . ' [role="button"]{cursor:pointer;}';
		$rules[] = $scope . ' :disabled{cursor:default;}';

		// [hidden] — respect `hidden="until-found"`.
		$rules[] = $scope . ' [hidden]:where(:not([hidden="until-found"])){display:none;}';

		return implode( '', $rules );
	}

	/**
	 * Render the full ClassRegistry as CSS rules.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function render_utility_classes(): string {
		$all = ClassRegistry::get_all_classes();

		if ( ! is_array( $all ) || empty( $all ) ) {
			return '';
		}

		$rules     = array();
		$keyframes = array();

		foreach ( $all as $class_name => $entry ) {
			if ( ! is_string( $class_name ) || ! is_array( $entry ) ) {
				continue;
			}

			$declaration = $entry['css'] ?? '';
			if ( ! is_string( $declaration ) || '' === $declaration ) {
				continue;
			}

			$escaped = \SpectraBlocks\GlobalStyles\JitCompiler::escape_selector( $class_name );

			// `:root ` prefix for specificity boost; see JitCompiler::compile_token.
			// `&` in the declaration is a nesting placeholder — expand to the class selector.
			if ( false !== strpos( $declaration, '&' ) ) {
				$rules[] = str_replace( '&', ':root .' . $escaped, $declaration );
			} else {
				$rules[] = ':root .' . $escaped . ' { ' . $declaration . ' }';
			}

			if ( ! empty( $entry['keyframes'] ) && is_string( $entry['keyframes'] ) ) {
				$keyframes[ $entry['keyframes'] ] = true;
			}
		}

		if ( ! empty( $keyframes ) ) {
			$rules = array_merge( array_keys( $keyframes ), $rules );
		}

		return implode( "\n", $rules );
	}

	/**
	 * Render user-defined custom classes.
	 *
	 * Expected structure (Spectra canonical flat-object — what the admin UI
	 * writes and what the ZipWP SaaS ships via `ClusterPayload::fromJson`):
	 *
	 *   [ 'my-class' => [
	 *       'default'  => [ 'color' => 'red' ],
	 *       'hover'    => [ 'color' => 'darkred' ],
	 *       'before'   => [ 'content' => '""' ],
	 *       'sm'       => [ ... ],  // @media (min-width: 640px)
	 *       'md'       => [ ... ],  // @media (min-width: 768px)
	 *       'lg'       => [ ... ],  // @media (min-width: 1024px)
	 *       'xl'       => [ ... ],  // @media (min-width: 1280px)
	 *       '2xl'      => [ ... ],  // @media (min-width: 1536px)
	 *       'md_hover' => [ ... ],  // md + :hover
	 *       'lg_focus' => [ ... ],  // lg + :focus
	 *   ] ]
	 *
	 * Keys matching a Tailwind-parity breakpoint (`sm`/`md`/`lg`/`xl`/`2xl`)
	 * are emitted inside the matching mobile-first `@media (min-width: …)`
	 * wrapper; an underscore-separated suffix (`md_hover`, `lg_focus`,
	 * `md_before`) provides the pseudo-class/element suffix.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $classes Custom class definitions.
	 * @return string
	 */
	private function render_user_classes( array $classes ): string {
		if ( empty( $classes ) ) {
			return '';
		}

		// Scope each user class with the SAME scope scheme as the per-page
		// GenCssRenderer, so gs-* classes (rendered here from the global option)
		// and per-page classes (rendered there from the post meta) share one
		// selector shape and cascade predictably on the same page. The global
		// option stores only classes (+ keyframes) — there is no rootStyles /
		// wrapperStyles bucket here — so of GenCssRenderer's four scope vars
		// (root_scope / class_prefix / class_joiner / sel_prefix) only the class
		// pair applies; `root_scope` / `sel_prefix` have nothing to render.
		// frontend → class_prefix `[class]` + joiner `''`
		// → `[class].{class}` (COMPOUND on the `[class]` attribute,
		// present on ANY element carrying a class; matches the gs-*
		// element directly whether or not it has data-spectra-id, so
		// core blocks + inline elements are styled too);
		// editor   → class_prefix `.editor-styles-wrapper` + joiner `' '`
		// → `.editor-styles-wrapper .{class}` (DESCENDANT — the canvas
		// wrapper is an ancestor of everything).
		// `enqueue_block_assets` fires in both contexts; discriminate via is_admin().
		if ( is_admin() ) {
			$class_prefix = '.editor-styles-wrapper';
			$class_joiner = ' ';
		} else {
			$class_prefix = '[class]';
			$class_joiner = '';
		}

		$base_rules = array();
		$by_media   = array();

		$raw_blocks = array();

		foreach ( $classes as $class_name => $types ) {
			if ( ! is_string( $class_name ) ) {
				continue;
			}

			// New raw-CSS format: value is a plain string of CSS.
			if ( is_string( $types ) ) {
				$trimmed = trim( $types );
				if ( '' !== $trimmed ) {
					$raw_blocks[] = $trimmed;
				}
				continue;
			}

			if ( ! is_array( $types ) ) {
				continue;
			}

			foreach ( $types as $type => $declarations ) {
				if ( ! is_array( $declarations ) ) {
					continue;
				}

				$css = $this->declarations_to_string( $declarations );
				if ( '' === $css ) {
					continue;
				}

				$resolved = StateResolver::resolve( (string) $type );
				// Repeat the class token (via the shared GenCssRenderer helper) to
				// lift specificity to (0,3,0) — kept in lockstep with the per-page
				// renderer so option classes and meta classes never drift.
				$selector = $class_prefix . $class_joiner . GenCssRenderer::class_token( $class_name ) . $resolved['suffix'];
				$rule     = $selector . ' { ' . $css . ' }';

				if ( '' === $resolved['media'] ) {
					$base_rules[] = $rule;
					continue;
				}

				if ( ! isset( $by_media[ $resolved['media'] ] ) ) {
					$by_media[ $resolved['media'] ] = array();
				}
				$by_media[ $resolved['media'] ][] = $rule;
			}
		}

		$parts = array();
		if ( ! empty( $base_rules ) ) {
			$parts[] = implode( "\n", $base_rules );
		}
		foreach ( $by_media as $media => $rules ) {
			$parts[] = '@media ' . $media . " {\n" . implode( "\n", $rules ) . "\n}";
		}
		if ( ! empty( $raw_blocks ) ) {
			$parts[] = implode( "\n\n", $raw_blocks );
		}

		return implode( "\n\n", $parts );
	}


	/**
	 * Render `@keyframes` rules from user-defined keyframe definitions.
	 *
	 * Accepts either the `{css, meta}` dict persisted by the REST controller
	 * or the raw-CSS string form accepted by the bulk endpoint's direct
	 * payload. Anything else is ignored.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $keyframes Keyframe name => definition.
	 * @return string
	 */
	private function render_keyframes( array $keyframes ): string {
		if ( empty( $keyframes ) ) {
			return '';
		}

		$rules = array();
		foreach ( $keyframes as $name => $definition ) {
			if ( ! is_string( $name ) ) {
				continue;
			}

			$body = '';
			if ( is_string( $definition ) ) {
				$body = $definition;
			} elseif ( is_array( $definition ) && isset( $definition['css'] ) && is_string( $definition['css'] ) ) {
				$body = $definition['css'];
			}

			if ( '' === $body ) {
				continue;
			}

			$rules[] = '@keyframes ' . $name . ' { ' . $body . ' }';
		}

		return implode( "\n", $rules );
	}

	/**
	 * Convert a flat-object declaration map to a CSS string.
	 *
	 * Expects the canonical shape `[ 'color' => 'red', 'margin' => '10px' ]` —
	 * the form Spectra persists and the admin UI / SaaS write.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int|string, mixed> $declarations Declarations.
	 * @return string
	 */
	private function declarations_to_string( array $declarations ): string {
		$parts = array();
		foreach ( $declarations as $property => $value ) {
			if ( ! is_string( $property ) || ! is_string( $value ) ) {
				continue;
			}
			$property = trim( $property );
			$value    = trim( $value );
			if ( '' === $property || '' === $value ) {
				continue;
			}
			$parts[] = $property . ': ' . $value . ';';
		}
		return implode( ' ', $parts );
	}

	/**
	 * Register REST API routes for the CRUD endpoints.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_rest_routes(): void {
		$controller = new RestController();
		$controller->register_routes();

		$system_sizes = new SystemSizesEndpoint();
		$system_sizes->register_routes();
	}
}
