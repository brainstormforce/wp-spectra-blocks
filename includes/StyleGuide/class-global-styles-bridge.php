<?php
/**
 * Global Styles Bridge — writes computed tokens to native WordPress Global Styles.
 *
 * Uses wp_theme_json_data filters so any FSE theme automatically picks up
 * the Spectra color palette without theme-specific code.
 *
 * @package Spectra\StyleGuide
 * @since   3.1.0
 */

namespace SpectraBlocks\StyleGuide;

use SpectraBlocks\Helpers\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GlobalStylesBridge
 *
 * @since 3.1.0
 */
class GlobalStylesBridge {

	/**
	 * Mapping from Astra global color indices to Spectra shade token keys.
	 *
	 * Used for the editor-side surfaces only: the --ast-global-color-{N} CSS
	 * aliases (imported Astra content on FSE themes) and the block-editor swatch
	 * alignment. Kept in sync with AstraPaletteAdapter's semantic → token map so
	 * the editor swatches match what the adapter actually pushes to Astra.
	 *
	 * NOTE: this static map assumes Astra's REORGANIZED (4.8.9+) slot ordering.
	 * The authoritative push (AstraPaletteAdapter::resolve_patch) resolves the
	 * background indices from the live compatibility flag; this const does not.
	 *
	 * Astra slot semantics (reorganized ordering):
	 *   0 = Brand              5 = Secondary Background
	 *   1 = Alternate Brand    6 = Alternate Background
	 *   2 = Headings           7 = Subtle Background
	 *   3 = Text               8 = Other Supporting
	 *   4 = Primary Background
	 *
	 * @since 1.0.0
	 * @var array<int, string>
	 */
	const ASTRA_SHADE_MAP = array(
		0 => 'primary',
		1 => 'secondary',
		2 => 'neutral-7',
		3 => 'neutral-5',
		4 => 'neutral-0',
		5 => 'neutral-1',
		6 => 'neutral-2',
		// Slots 7 (Subtle Background) and 8 (Other Supporting) are unmanaged —
		// their old tokens (the interpolated neutral-3/neutral-6) are no longer
		// generated. Astra keeps its own values for them.
	);

	/**
	 * The Engine instance.
	 *
	 * @since 3.1.0
	 * @var Engine
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param Engine $engine The Style Guide engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 3.1.0
	 * @return void
	 */
	public function init(): void {
		// Inject palette into the theme layer so it merges with (not replaces) theme colors.
		add_filter( 'wp_theme_json_data_theme', array( $this, 'inject_palette' ), 20 );

		// Normalize sg-* palette names in the user layer (saved global styles).
		// User-layer names take precedence over theme-layer, so stale "Sg-*" names
		// stored in wp_global_styles must be fixed here.
		add_filter( 'wp_theme_json_data_user', array( $this, 'normalize_user_palette_names' ), 20 );

		// Option-only mode: overwrite stale Spectra-managed colours in the user
		// layer with the current computed values so the option is authoritative
		// without leaving any slug undefined. Runs after normalize (21 > 20).
		add_filter( 'wp_theme_json_data_user', array( $this, 'maybe_override_managed_user_palette' ), 21 );

		// The block editor merges the theme and USER global-styles REST entities
		// CLIENT-SIDE, and the user entity is served from the raw post content —
		// wp_theme_json_data_user never applies to it. Overlay the same managed
		// palette there so the editor picker matches the server-rendered palette.
		//
		// Hook `rest_request_after_callbacks`, NOT `rest_post_dispatch`: the editor
		// PRELOADS global styles via `rest_do_request()` (see rest_preload_api_request),
		// which runs inside dispatch() and never fires `rest_post_dispatch` — so an
		// overlay hooked there is skipped for the preloaded data the editor actually
		// boots with, and the picker showed no Style Guide colours until a manual
		// save. `rest_request_after_callbacks` fires for BOTH the preload and live
		// serve_request paths, so the overlay reaches the data the editor really uses.
		add_filter( 'rest_request_after_callbacks', array( $this, 'overlay_rest_user_global_styles' ), 10, 3 );

		// sg-* alias slugs are render-compat only, never pickable swatches — strip
		// them from the picker's data sources (11 = after the overlay above). The
		// render path keeps them: core still derives .has-sg-*-color rules from
		// the server-side theme.json palette. Same filter as the overlay so it also
		// covers the editor's preloaded request.
		add_filter( 'rest_request_after_callbacks', array( $this, 'strip_sg_alias_swatches_from_rest' ), 11, 3 );

		// Theme color sync (both directions, all supported theme classes) is owned
		// by the SyncOrchestrator: it resolves the active theme's role->slug mapping
		// and routes through the FSE / Astra store adapters, so sync works for any
		// curated or auto-derivable theme rather than only Spectra One + Astra.
		( new Sync\SyncOrchestrator( $this->engine ) )->register();

		// LEGACY one-time migration: strip Spectra colours that OLDER builds persisted
		// into `wp_global_styles`. The push no longer writes there, so this only heals
		// pre-existing data, once. Safe to delete this line together with
		// class-palette-cleanup.php once every site has upgraded past this build.
		( new Sync\PaletteCleanup( $this->engine ) )->register();

		// Enqueue the CSS variables on the frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_css' ), 5 );

		// LAST in <head>, deliberately. See print_page_palette().
		add_action( 'wp_head', array( $this, 'print_page_palette' ), 100 );

		// Enqueue in the block editor.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_css' ) );

		// Inject Astra compat CSS directly into the editor iframe via block_editor_settings_all.
		// enqueue_block_editor_assets only reaches the admin <head>, not the iframe canvas.
		add_filter( 'block_editor_settings_all', array( $this, 'inject_astra_compat_editor_styles' ) );

		// Inject the Style Guide token CSS into the editor iframe.
		add_filter( 'block_editor_settings_all', array( $this, 'inject_sg_editor_styles' ) );

		// Inject token CSS variables (including legacy mappings) into the editor iframe.
		add_filter( 'block_editor_settings_all', array( $this, 'inject_token_editor_styles' ) );

		// Align the Astra global-color picker swatches with the colour that actually
		// gets applied. Runs late (priority 20) so it has the final, fully-merged
		// palette to rewrite.
		add_filter( 'block_editor_settings_all', array( $this, 'align_astra_palette_swatches' ), 20 );

		// Surfacing the Style Guide palette in the editor colour picker is a Pro
		// capability. When Pro is inactive, strip the Style-Guide-managed swatches
		// from the picker (priority 21, after the align pass) WITHOUT touching the
		// colour variables — content that already uses a Style Guide colour keeps
		// rendering because the --wp--preset--color--* vars are still emitted.
		add_filter( 'block_editor_settings_all', array( $this, 'remove_style_guide_swatches_when_pro_inactive' ), 21 );

		// sg-* alias slugs are render-compat only — also strip them from the
		// server-built editor settings (widgets/customizer editors and the legacy
		// flat `colors` list read by older picker components). Priority 22 = after
		// the Pro-inactive strip, which already removes them when Pro is off.
		add_filter( 'block_editor_settings_all', array( $this, 'remove_sg_alias_swatches' ), 22 );

		// Font Library ACTIVATION (A21): installed wp_font_family/wp_font_face
		// CPTs emit no @font-face by themselves — core's resolver only reads
		// families present in global-styles settings. This bridge is the ONE
		// sanctioned wp_global_styles writer (D5 policy), so the sync lives
		// here, hook-driven: it fires on Library writes from ANY producer
		// (importer install-fonts ability, Site Editor uploads), which also
		// covers dev-door imports that never run the StyleGuide apply. The
		// face hook is required — faces are child posts created AFTER their
		// family, so a family-save-only sync would capture zero faces.
		add_action( 'save_post_wp_font_family', array( $this, 'sync_font_library_families' ), 20 );
		add_action( 'save_post_wp_font_face', array( $this, 'sync_font_library_families' ), 20 );

		// Imported-chrome activation (classic themes): the importer's font
		// install is idempotent — on re-imports it saves nothing, so the
		// save_post hooks above never fire and a pre-existing Library never
		// reaches this theme's user global-styles post (measured live on
		// Astra: families+faces installed, zero faces printed). The moment
		// the theme starts rendering imported chrome is exactly when its
		// fonts must resolve — sync then, idempotently.
		add_action( 'add_option_zipai_chrome_mode', array( $this, 'sync_font_library_families' ), 20 );
		add_action( 'update_option_zipai_chrome_mode', array( $this, 'sync_font_library_families' ), 20 );
	}

	/**
	 * Inject the Spectra color palette into WordPress theme.json data.
	 *
	 * Hooked to wp_theme_json_data_theme at priority 20.
	 *
	 * @since 3.1.0
	 *
	 * @param \WP_Theme_JSON_Data $theme_json The theme JSON data object.
	 * @return \WP_Theme_JSON_Data Modified theme JSON data.
	 */
	public function inject_palette( $theme_json ) {
		// Style Guide palette swatches are a Pro feature. Without Pro the theme's
		// palette must reach every colour picker (block inspector AND the Site
		// Editor's Styles → Palette, which reads theme_json directly and never
		// passes through block_editor_settings_all) untouched. Checked inside the
		// callback — not at registration — so the answer never depends on whether
		// Pro's main file loaded before or after ours.
		if ( ! Core::is_pro_active() ) {
			return $theme_json;
		}

		// Only inject the Style Guide's semantic palette once the user has SAVED one.
		// With nothing saved the engine resolves to inherited/default colours, and
		// injecting those would override the theme's own slugs (e.g. `primary`,
		// `heading`) — a site the user never styled must render as its theme intends.
		if ( ! $this->engine->has_saved_style_guide() ) {
			return $theme_json;
		}

		// Ensure tokens are computed — this filter can fire before 'init',
		// before Engine::maybe_compute() has had a chance to run.
		$this->engine->maybe_compute();

		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return $theme_json;
		}

		// Raw-token palette entries — intentionally empty since shade tokens are
		// no longer published as presets; the semantic layer below is the palette.
		$spectra_palette = $tokens->get_wp_palette();

		// Get existing theme data.
		$data = $theme_json->get_data();

		// Merge Spectra palette with existing theme palette.
		$existing_palette = array();

		if ( isset( $data['settings']['color']['palette']['theme'] ) && is_array( $data['settings']['color']['palette']['theme'] ) ) {
			$existing_palette = $data['settings']['color']['palette']['theme'];
		}

		// Remove any existing Spectra indexed entries to avoid duplicates.
		$existing_palette = array_filter(
			$existing_palette,
			function ( $entry ) {
				return 0 !== strpos( $entry['slug'], TokenRegistry::PREFIX . '-' );
			}
		);

		// ── Semantic layer: resolve theme semantic colors from shade map ──
		$config = $this->engine->get_config();
		/* @var array<string, string> $semantic_map */
		$semantic_map = isset( $config['semantic_map'] ) && is_array( $config['semantic_map'] ) ? $config['semantic_map'] : array();

		if ( ! empty( $semantic_map ) ) {
			// Build a lookup: semantic slug → hex value (resolved from shade key).
			$semantic_slugs = array();
			foreach ( $semantic_map as $slug => $shade_key ) {
				$hex = $tokens->get( $shade_key );
				if ( null !== $hex ) {
					$semantic_slugs[ $slug ] = $hex;
				}
			}

			// Explicit per-slug overrides win over the shade-derived value.
			foreach ( $this->get_semantic_overrides() as $slug => $hex ) {
				$semantic_slugs[ $slug ] = $hex;
			}

			// Recolour ONLY the theme's existing palette entries that match a semantic
			// slug (primary/heading/body/…) — re-tint the roles the theme already has.
			// We deliberately do NOT append semantic slugs the theme lacks (accent,
			// status, the sg-* aliases, or — on Astra — the whole set): appending
			// floods the picker with extra swatches AND WP persists the injected theme
			// palette into wp_global_styles when the user saves Site Editor → Styles →
			// Palette, bloating the stored post. Recolour-only keeps the theme's own
			// palette intact.
			$updated_existing = array();
			foreach ( $existing_palette as $entry ) {
				if ( isset( $entry['slug'], $semantic_slugs[ $entry['slug'] ] ) ) {
					$entry['color'] = $semantic_slugs[ $entry['slug'] ];
					$entry['name']  = TokenRegistry::format_slug_label( $entry['slug'] );
				}
				$updated_existing[] = $entry;
			}

			$existing_palette = $updated_existing;
		}

		// ── Generic theme colour override (opt-in; non-Astra/Spectra-One) ──
		// Overrides the ACTIVE theme's own palette slugs (e.g. base/contrast/accent-N)
		// with the mapped Style Guide value. Overriding the theme.json palette here
		// makes BOTH the editor picker swatch and the generated
		// --wp--preset--color--{slug} adopt the design system, since both derive from
		// theme.json. Astra and Spectra One are excluded — they keep their dedicated
		// compat layers (ThemeStyleCompat::DEDICATED_THEMES).
		if ( ThemeStyleCompat::should_override_color() ) {
			$theme_slugs = array();
			foreach ( $existing_palette as $palette_entry ) {
				if ( isset( $palette_entry['slug'] ) ) {
					$theme_slugs[] = $palette_entry['slug'];
				}
			}

			$theme_overrides = ThemeStyleCompat::resolve_color_overrides( $theme_slugs, $tokens, $config );

			if ( ! empty( $theme_overrides ) ) {
				foreach ( $existing_palette as &$tc_entry ) {
					if ( isset( $tc_entry['slug'], $theme_overrides[ $tc_entry['slug'] ] ) ) {
						$tc_entry['color'] = $theme_overrides[ $tc_entry['slug'] ];
					}
				}
				unset( $tc_entry );
			}
		}

		// Merge: existing/updated theme colors first, then Spectra indexed colors.
		$merged_palette = array_merge( array_values( $existing_palette ), $spectra_palette );

		// Build the update payload — PALETTE ONLY.
		//
		// We do NOT inject element styles (button, heading, link, etc.) here.
		// The theme's theme.json already assigns elements to semantic colors:
		// button.bg = var(--wp--preset--color--primary)
		// heading.color = var(--wp--preset--color--heading)
		//
		// Our job is to update what those semantic colors RESOLVE to.
		// The theme handles the assignment. We handle the values.
		// User FSE edits override both (user layer > theme layer).
		//
		// This is the Relume-equivalent approach:
		// Relume: static CSS references vars → vars change → elements update
		// Spectra: theme.json references semantic slugs → we update slug values → elements update.
		$new_data = array(
			'version'  => 2,
			'settings' => array(
				'color' => array(
					'palette' => array(
						'theme' => $merged_palette,
					),
				),
			),
		);

		return $theme_json->update_with( $new_data );
	}

	/**
	 * Normalize Spectra palette entry names in the WordPress user (global styles) layer.
	 *
	 * User-layer data takes precedence over theme-layer data. Two classes of stale
	 * names are fixed here:
	 *
	 * 1. sg-* semantic entries saved with auto-generated "Sg-accent" style names
	 *    (from a previous sync where ucfirst() was applied to the slug).
	 * 2. spectra-* shade entries saved with the literal 6-character sequence "\u00b7"
	 *    instead of the actual middle-dot character "·" (a PHP string escaping bug
	 *    that was fixed; previously saved user-layer data still holds the literal).
	 *
	 * Hooked to wp_theme_json_data_user at priority 20.
	 *
	 * @since 3.2.0
	 *
	 * @param \WP_Theme_JSON_Data $theme_json The user JSON data object.
	 * @return \WP_Theme_JSON_Data Modified user JSON data.
	 */
	public function normalize_user_palette_names( $theme_json ) {
		$data    = $theme_json->get_data();
		$palette = isset( $data['settings']['color']['palette']['theme'] )
			? (array) $data['settings']['color']['palette']['theme']
			: array();

		if ( empty( $palette ) ) {
			return $theme_json;
		}

		$updated = false;
		foreach ( $palette as &$entry ) {
			if ( ! isset( $entry['slug'], $entry['name'] ) ) {
				continue;
			}

			// Fix sg-* semantic names (e.g. "Sg-accent" → "Accent").
			if ( 0 === strpos( $entry['slug'], 'sg-' ) ) {
				$clean_name = TokenRegistry::format_slug_label( $entry['slug'] );
				if ( $entry['name'] !== $clean_name ) {
					$entry['name'] = $clean_name;
					$updated       = true;
				}
				continue;
			}

			// Fix spectra-* shade names: replace corrupted middle-dot variants with actual "·".
			// Two legacy forms: "u00b7" (no backslash, kses stripped it) and "\u00b7" (backslash kept).
			if ( 0 === strpos( $entry['slug'], 'spectra-' ) ) {
				$clean = str_replace( array( '\u00b7', 'u00b7' ), '·', $entry['name'] );
				if ( $clean !== $entry['name'] ) {
					$entry['name'] = $clean;
					$updated       = true;
				}
			}
		}
		unset( $entry );

		if ( ! $updated ) {
			return $theme_json;
		}

		return $theme_json->update_with(
			array(
				'version'  => 2,
				'settings' => array(
					'color' => array(
						'palette' => array(
							'theme' => $palette,
						),
					),
				),
			)
		);
	}

	/**
	 * Override Spectra-managed palette colors in the user layer (option-only mode).
	 *
	 * The user-layer palette (stored in wp_global_styles) takes precedence over
	 * the theme layer, so a stale Spectra palette left there by a prior sync or
	 * import would override the runtime theme-layer injection — e.g. a stale
	 * `primary` making `--wp--preset--color--primary` render the old colour.
	 *
	 * Rather than REMOVE those entries (which would leave a slug undefined when
	 * the theme layer doesn't also provide it), we OVERWRITE each managed slug's
	 * colour with the current computed value. That keeps every slug defined and
	 * makes the option the sole authority. Runtime only — the stored post is
	 * untouched, so disabling the mode restores the prior behaviour.
	 *
	 * Hooked to wp_theme_json_data_user at priority 21 (after normalize).
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Theme_JSON_Data $theme_json The user-layer theme.json data.
	 * @return \WP_Theme_JSON_Data
	 */
	public function maybe_override_managed_user_palette( $theme_json ) {
		$data    = $theme_json->get_data();
		$palette = isset( $data['settings']['color']['palette']['theme'] )
			? (array) $data['settings']['color']['palette']['theme']
			: array();

		$updated = $this->overlay_managed_palette( $palette );
		if ( null === $updated ) {
			return $theme_json;
		}

		return $theme_json->update_with(
			array(
				'version'  => 2,
				'settings' => array(
					'color' => array(
						'palette' => array(
							'theme' => $updated,
						),
					),
				),
			)
		);
	}

	/**
	 * The active theme's OWN palette as picker entries (slug/color/name), read from
	 * the effective colour store — Astra's `astra-settings` slots on Astra, the FSE
	 * user/theme global-styles palette on a block theme.
	 *
	 * Used to seed an empty user-layer palette in {@see overlay_managed_palette} so
	 * the theme's swatches survive before the Style Guide colours are added. Only
	 * ever called from that method, i.e. after the Pro + saved-guide gate — Style
	 * Guide colours are therefore never injected while Pro is inactive.
	 *
	 * @since x.x.x
	 *
	 * @return array<int, array<string, string>> Palette entries (empty when none).
	 */
	private function seed_palette_from_theme(): array {
		// Display names must come from the theme's OWN palette definition — Astra's
		// "Brand" / "Alternate Brand" / …, a block theme's "Primary" / … — NOT the
		// slug-derived label (which would render Astra's slots as "Ast Global Color 0").
		// The resolver carries the correct names (with `var(--…)` colours); the adapter
		// carries the resolved hex. Combine: name from the resolver, colour from the
		// adapter, falling back to the slug label only when the theme names none.
		$names = array();
		if ( class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			$settings      = \WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
			$theme_palette = ( isset( $settings['color']['palette']['theme'] ) && is_array( $settings['color']['palette']['theme'] ) ) ? $settings['color']['palette']['theme'] : array();
			foreach ( $theme_palette as $entry ) {
				if ( is_array( $entry ) && isset( $entry['slug'], $entry['name'] ) && is_string( $entry['slug'] ) && is_string( $entry['name'] ) ) {
					$names[ $entry['slug'] ] = $entry['name'];
				}
			}
		}

		$adapters = array(
			new Sync\Astra\AstraPaletteAdapter(),
			new Sync\FseGlobalStylesAdapter(),
		);
		foreach ( $adapters as $adapter ) {
			if ( ! $adapter->is_supported() ) {
				continue;
			}
			$out = array();
			foreach ( $adapter->read() as $slug => $hex ) {
				if ( is_string( $slug ) && '' !== $slug && is_string( $hex ) && '' !== $hex ) {
					$out[] = array(
						'slug'  => $slug,
						'color' => $hex,
						'name'  => $names[ $slug ] ?? TokenRegistry::format_slug_label( $slug ),
					);
				}
			}
			return $out;
		}
		return array();
	}

	/**
	 * Overlay the Spectra-managed colours onto a user-layer `theme` palette.
	 *
	 * Recolours every managed slug already present to its CURRENT computed value
	 * and appends managed slugs that are missing, so the user layer stays a
	 * superset of the managed set (status colours, sg-*, newly added roles).
	 * Shared by the server-side user-layer filter
	 * ({@see maybe_override_managed_user_palette}) and the REST response overlay
	 * ({@see overlay_rest_user_global_styles}) so both render paths agree.
	 *
	 * Style Guide colours are added ONLY when Pro is active (the guard below).
	 *
	 * @since x.x.x
	 *
	 * @param array<int, array<string, string>> $palette User-layer `theme` palette entries.
	 * @return array<int, array<string, string>>|null Updated palette, or null when
	 *                                                gated off / empty / unchanged.
	 */
	private function overlay_managed_palette( array $palette ) {
		// Rendering the Style Guide palette is a Pro capability, and only once a Style
		// Guide is saved (an unsaved site keeps its own colours). Gating here Pro-gates
		// BOTH runtime overlays that call this — the server-side user-layer filter
		// (maybe_override_managed_user_palette) and the REST picker overlay — so the
		// Style Guide colours inject at runtime only when Pro is active.
		if ( ! Core::is_pro_active() || ! $this->engine->has_saved_style_guide() ) {
			return null;
		}

		// When nothing has been saved in the Site Editor the user-layer palette is
		// empty. WP MERGES global styles by REPLACING the theme palette with the user
		// palette, so injecting the Style Guide colours onto an empty user layer would
		// wipe the theme's own swatches. Seed from the active theme's resolved palette
		// first, so the theme's colours survive and the SG colours are ADDED to them.
		if ( empty( $palette ) ) {
			$palette = $this->seed_palette_from_theme();
			if ( empty( $palette ) ) {
				return null;
			}
		}

		// Build slug → current Spectra colour, mirroring inject_palette(): the
		// shade palette (get_wp_palette) plus every semantic_map slug resolved to
		// its shade value.
		$this->engine->maybe_compute();
		$tokens = $this->engine->get_token_registry();
		if ( null === $tokens ) {
			return null;
		}

		$managed = array();
		foreach ( $tokens->get_wp_palette() as $managed_entry ) {
			// 'slug' and 'color' are always present per get_wp_palette()'s return shape.
			$managed[ $managed_entry['slug'] ] = $managed_entry['color'];
		}

		$config = $this->engine->get_config();
		if ( isset( $config['semantic_map'] ) && is_array( $config['semantic_map'] ) ) {
			foreach ( $config['semantic_map'] as $semantic_slug => $shade_key ) {
				$hex = $tokens->get( $shade_key );
				if ( null !== $hex ) {
					$managed[ $semantic_slug ] = $hex;
				}
			}
		}

		// Explicit per-slug overrides win over the shade-derived value.
		foreach ( $this->get_semantic_overrides() as $slug => $hex ) {
			$managed[ $slug ] = $hex;
		}

		if ( empty( $managed ) ) {
			return null;
		}

		$changed = false;
		foreach ( $palette as &$entry ) {
			if ( isset( $entry['slug'], $managed[ $entry['slug'] ] ) && $entry['color'] !== $managed[ $entry['slug'] ] ) {
				$entry['color'] = $managed[ $entry['slug'] ];
				$changed        = true;
			}
		}
		unset( $entry );

		// Add managed slugs missing from the (possibly stale) user-layer palette.
		// The user layer shadows the theme layer, so newly introduced roles/shades
		// (status colours, added chromatics) would never surface if we only recolour
		// existing entries. Appending keeps the user palette a superset of the managed
		// set, generically — no per-slug special-casing. This includes the sg-*
		// aliases: the SERVER-side merge derives the .has-sg-*-color preset rules
		// from this palette, so they must stay. The picker never shows them — the
		// REST and editor-settings strips remove them from every picker surface.
		$existing_slugs = array();
		foreach ( $palette as $entry ) {
			if ( isset( $entry['slug'] ) ) {
				$existing_slugs[ $entry['slug'] ] = true;
			}
		}
		foreach ( $managed as $slug => $hex ) {
			if ( ! isset( $existing_slugs[ $slug ] ) ) {
				$palette[] = array(
					'slug'  => $slug,
					'color' => $hex,
					'name'  => TokenRegistry::format_slug_label( $slug ),
				);
				$changed   = true;
			}
		}

		// Order the theme's OWN palette (in theme.json order) first, then the
		// Spectra-added colours the theme lacks (accent, status, custom, sg-*). The
		// recolour/append above leaves the theme's trailing extras (e.g. Spectra
		// One's tertiary/quaternary) ahead of the appended core roles, scrambling
		// the Site Editor picker into "tertiary, quaternary, …, primary, secondary".
		// Reordering restores "theme colours first, Style Guide colours after".
		$ordered = $this->order_palette_theme_first( $palette );
		if ( $ordered !== $palette ) {
			$palette = $ordered;
			$changed = true;
		}

		return $changed ? $palette : null;
	}

	/**
	 * Reorder a palette so the active theme's own slugs (in theme.json order) come
	 * first, then every other (Spectra-added) entry in its existing relative order.
	 *
	 * A stable sort keyed on each slug's theme.json index; slugs absent from
	 * theme.json (accent, status colours, custom colours, sg-* aliases) sort last.
	 * Themes with no readable theme.json palette (e.g. Astra) yield an empty order
	 * and the palette is returned unchanged.
	 *
	 * @since x.x.x
	 *
	 * @param array<int, array<string, string>> $palette Palette entries.
	 * @return array<int, array<string, string>> Reordered palette.
	 */
	private function order_palette_theme_first( array $palette ): array {
		$order = $this->theme_palette_slug_order();
		if ( empty( $order ) ) {
			return $palette;
		}
		$rank = array_flip( $order );

		$indexed = array();
		foreach ( $palette as $i => $entry ) {
			$slug      = isset( $entry['slug'] ) ? (string) $entry['slug'] : '';
			$indexed[] = array(
				'entry' => $entry,
				'theme' => isset( $rank[ $slug ] ) ? $rank[ $slug ] : PHP_INT_MAX,
				'orig'  => $i,
			);
		}

		usort(
			$indexed,
			static function ( $a, $b ) {
				return ( $a['theme'] !== $b['theme'] ) ? ( $a['theme'] <=> $b['theme'] ) : ( $a['orig'] <=> $b['orig'] );
			}
		);

		return array_map(
			static function ( $x ) {
				return $x['entry'];
			},
			$indexed
		);
	}

	/**
	 * The active theme's palette slugs in theme.json declaration order.
	 *
	 * Read from the RAW theme.json files (parent then child; child-only slugs
	 * appended) rather than the resolver, so the Style Guide's own runtime palette
	 * filters can't perturb the order. Statically cached per request.
	 *
	 * @since x.x.x
	 *
	 * @return array<int, string> Ordered slugs (empty when the theme declares none).
	 */
	private function theme_palette_slug_order(): array {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$order = array();
		$dirs  = array_unique( array( get_template_directory(), get_stylesheet_directory() ) );
		foreach ( $dirs as $dir ) {
			$file = $dir . '/theme.json';
			if ( ! is_readable( $file ) ) {
				continue;
			}
			$data = wp_json_file_decode( $file, array( 'associative' => true ) );
			if ( ! is_array( $data ) ) {
				continue;
			}
			$settings = ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) ? $data['settings'] : array();
			$color    = ( isset( $settings['color'] ) && is_array( $settings['color'] ) ) ? $settings['color'] : array();
			$palette  = ( isset( $color['palette'] ) && is_array( $color['palette'] ) ) ? $color['palette'] : array();
			foreach ( $palette as $entry ) {
				if ( is_array( $entry ) && isset( $entry['slug'] ) && is_string( $entry['slug'] ) && ! in_array( $entry['slug'], $order, true ) ) {
					$order[] = $entry['slug'];
				}
			}
		}

		$cache = $order;
		return $cache;
	}

	/**
	 * Apply the managed-palette overlay to the REST user global-styles response.
	 *
	 * The block editor does NOT use the server-merged theme.json: it fetches the
	 * theme layer (`/wp/v2/global-styles/themes/{stylesheet}`, which our
	 * `wp_theme_json_data_theme` filters cover) and the USER entity
	 * (`/wp/v2/global-styles/{id}`) separately, then merges them CLIENT-SIDE —
	 * and the user entity is served from the raw post content, so
	 * `wp_theme_json_data_user` never touches it. A pushed `palette.theme` in the
	 * user post therefore wholesale-replaces the runtime theme palette in the
	 * editor's merge: the picker loses the runtime-only swatches (status colours,
	 * sg-*) and shows stale colours for derived roles (e.g. `foreground`).
	 *
	 * WP core applies no `rest_prepare_wp_global_styles` filter (checked on 7.0), so
	 * this hooks `rest_request_after_callbacks`, which fires inside dispatch() for
	 * BOTH the editor's preloaded request (`rest_do_request` via
	 * rest_preload_api_request) and a live REST call — unlike `rest_post_dispatch`,
	 * which is skipped during preload, leaving the picker empty until a manual save.
	 * Applies the same overlay the server-side render path gets from
	 * {@see maybe_override_managed_user_palette}. Runtime only: the stored post is
	 * untouched.
	 *
	 * @since x.x.x
	 *
	 * @param \WP_HTTP_Response|\WP_Error $result  Result to send to the client.
	 * @param array|mixed                 $handler Route handler (unused).
	 * @param \WP_REST_Request            $request Request used to generate the response.
	 * @return \WP_HTTP_Response|\WP_Error
	 */
	public function overlay_rest_user_global_styles( $result, $handler, $request ) {
		unset( $handler );

		// Style Guide swatches in the editor pickers are a Pro capability
		// (mirrors inject_palette). Without Pro this overlay must not run: the
		// editor's CLIENT-SIDE entity merge bypasses block_editor_settings_all,
		// so remove_style_guide_swatches_when_pro_inactive() can't strip what
		// gets appended here — the managed swatches would leak into the picker
		// (seen on Astra with Pro deactivated).
		if ( ! Core::is_pro_active() ) {
			return $result;
		}

		// Only the single user global-styles entity route (`/wp/v2/global-styles/<id>`);
		// the `/themes/…` and `/revisions` variants are already covered or read-only.
		if ( ! $result instanceof \WP_REST_Response || ! preg_match( '#^/wp/v2/global-styles/\d+$#', (string) $request->get_route() ) ) {
			return $result;
		}

		$data = $result->get_data();
		if ( ! is_array( $data ) ) {
			return $result;
		}

		// Descend one level at a time with an is_array guard at each step: WP
		// serializes an EMPTY settings/color/palette as a stdClass (`{}`, not `[]`),
		// so a chained `$data['settings']['color']…` array access would fatal
		// ("Cannot use object of type stdClass as array") on a theme whose user
		// global-styles entity has no saved palette yet (seen on a fresh Astra).
		$settings = ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) ? $data['settings'] : null;
		$color    = ( null !== $settings && isset( $settings['color'] ) && is_array( $settings['color'] ) ) ? $settings['color'] : null;
		$palette  = ( null !== $color && isset( $color['palette'] ) && is_array( $color['palette'] ) ) ? $color['palette'] : null;
		// A fresh user entity has NO palette node at all (empty envelope). Treat that
		// as an empty palette rather than bailing, so overlay_managed_palette() still
		// runs and SEEDS the theme's colours + injects the Style Guide swatches — else
		// nothing Spectra shows in the picker until the user saves global styles once.
		$theme = ( null !== $palette && isset( $palette['theme'] ) && is_array( $palette['theme'] ) ) ? $palette['theme'] : array();

		$updated = $this->overlay_managed_palette( $theme );
		if ( null !== $updated ) {
			// Rebuild the nested structure as arrays — an empty envelope serialises
			// settings/color/palette as stdClass (or omits them), so casting preserves
			// any sibling settings (typography, etc.) while adding the palette.
			$settings_arr          = isset( $data['settings'] ) ? (array) $data['settings'] : array();
			$color_arr             = isset( $settings_arr['color'] ) ? (array) $settings_arr['color'] : array();
			$palette_arr           = isset( $color_arr['palette'] ) ? (array) $color_arr['palette'] : array();
			$palette_arr['theme']  = $updated;
			$color_arr['palette']  = $palette_arr;
			$settings_arr['color'] = $color_arr;
			$data['settings']      = $settings_arr;
			$result->set_data( $data );
		}

		return $result;
	}

	/**
	 * Strip sg-* alias swatches from the global-styles REST responses.
	 *
	 * The sg-* slugs are render-time compat aliases (the Astra
	 * ast-global-color-N rewrite and legacy Style Guide content), not pickable
	 * roles: every one duplicates a semantic swatch under a second name, so on
	 * Astra the picker showed each colour up to three times ("Text" +
	 * "Body" + "Body (sg-body)"). They must STAY in the server-side theme.json
	 * palette — core derives the .has-sg-*-color preset rules from it — but the
	 * editor picker builds its palette from the global-styles REST entities
	 * merged client-side, so stripping them HERE removes the duplicate swatches
	 * without touching the render path.
	 *
	 * Covers both entities the client merge reads: the theme variant
	 * (`/wp/v2/global-styles/themes/{stylesheet}`) and the user entity
	 * (`/wp/v2/global-styles/<id>`). Hooked to `rest_request_after_callbacks` (like
	 * the overlay) so it also covers the editor's preloaded request, and runs after
	 * the managed-palette overlay.
	 *
	 * @since x.x.x
	 *
	 * @param \WP_HTTP_Response|\WP_Error $result  Result to send to the client.
	 * @param array|mixed                 $handler Route handler (unused).
	 * @param \WP_REST_Request            $request Request used to generate the response.
	 * @return \WP_HTTP_Response|\WP_Error
	 */
	public function strip_sg_alias_swatches_from_rest( $result, $handler, $request ) {
		unset( $handler );

		$route = (string) $request->get_route();
		if ( ! $result instanceof \WP_REST_Response
			|| ! preg_match( '#^/wp/v2/global-styles/(\d+|themes/[^/]+)$#', $route ) ) {
			return $result;
		}

		$data = $result->get_data();
		if ( ! is_array( $data ) ) {
			return $result;
		}

		// stdClass-safe descent (empty nodes serialize as `{}` — see the overlay).
		$settings = ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) ? $data['settings'] : null;
		$color    = ( null !== $settings && isset( $settings['color'] ) && is_array( $settings['color'] ) ) ? $settings['color'] : null;
		$palette  = ( null !== $color && isset( $color['palette'] ) && is_array( $color['palette'] ) ) ? $color['palette'] : null;
		$theme    = ( null !== $palette && isset( $palette['theme'] ) && is_array( $palette['theme'] ) ) ? $palette['theme'] : null;
		if ( null === $theme ) {
			return $result;
		}

		$stripped = $this->strip_picker_duplicate_entries( $theme );

		if ( null !== $stripped ) {
			$data['settings']['color']['palette']['theme'] = $stripped;
			$result->set_data( $data );
		}

		return $result;
	}

	/**
	 * Remove non-pickable duplicates from a picker-facing palette list.
	 *
	 * Two rules, applied to PICKER surfaces only (the render-path palette is
	 * never filtered — it is what core derives the preset vars/classes from):
	 *  1. sg-* alias slugs — render-time compat aliases, never pickable roles;
	 *     each duplicates a semantic swatch under a second name.
	 *  2. Same-COLOUR duplicates — when a synced colour appears under BOTH the
	 *     theme's own slug and a Spectra-injected slug (e.g. Astra's
	 *     `ast-global-color-0` "Brand" and the Style Guide's `primary`, sharing
	 *     one hex), the THEME's swatch is kept and the Spectra duplicate is
	 *     dropped — the mapped colour "merges into" the theme swatch. Names differ
	 *     ("Brand" vs "Primary"), so a name match can't catch these; the theme slug
	 *     is identified from theme.json. Colours with no theme-native counterpart
	 *     (accent, status, foreground, custom vars) are unique and always kept, and
	 *     non-hex values (`var(--…)`, `transparent`) are never deduped.
	 *
	 * @since x.x.x
	 *
	 * @param array<int, mixed> $palette Palette entries (from decoded REST/theme.json data).
	 * @return array<int, mixed>|null Filtered entries, or null when unchanged.
	 */
	private function strip_picker_duplicate_entries( array $palette ) {
		$theme_native = array();
		foreach ( $this->theme_palette_slug_order() as $native_slug ) {
			$theme_native[ $native_slug ] = true;
		}

		// The colours the theme's OWN palette occupies. A Spectra-injected swatch
		// sharing one of these is the mapped/synced duplicate (e.g. Astra's
		// `ast-global-color-0` vs `primary`) and is dropped. Collected up front so a
		// theme colour that appears LATER in the list still shields an earlier Spectra
		// entry from it.
		$theme_colors = array();
		foreach ( $palette as $entry ) {
			if ( is_array( $entry ) && isset( $entry['slug'], $entry['color'] ) && is_string( $entry['slug'] ) && isset( $theme_native[ $entry['slug'] ] ) ) {
				$hex = $this->normalize_hex_key( (string) $entry['color'] );
				if ( '' !== $hex ) {
					$theme_colors[ $hex ] = true;
				}
			}
		}

		$kept = array();
		foreach ( $palette as $entry ) {
			$slug = ( is_array( $entry ) && isset( $entry['slug'] ) && is_string( $entry['slug'] ) ) ? $entry['slug'] : '';

			// Rule 1: drop sg-* aliases outright.
			if ( '' !== $slug && 0 === strpos( $slug, 'sg-' ) ) {
				continue;
			}

			// Rule 2: NEVER drop the theme's OWN colours. Every theme.json role stays —
			// including two distinct roles that happen to share a hex (e.g. `heading`
			// and `foreground` both resolving dark), which the old colour-only dedup
			// wrongly collapsed into one, losing the second role's swatch.
			if ( isset( $theme_native[ $slug ] ) ) {
				$kept[] = $entry;
				continue;
			}

			// Rule 3: drop a Spectra-injected swatch ONLY when it duplicates a THEME
			// colour (the mapped/synced case). Spectra colours the theme has no
			// counterpart for (accent, status, custom) are unique and always kept;
			// non-hex values carry no comparable colour and pass through.
			$hex = ( is_array( $entry ) && isset( $entry['color'] ) ) ? $this->normalize_hex_key( (string) $entry['color'] ) : '';
			if ( '' !== $hex && isset( $theme_colors[ $hex ] ) ) {
				continue;
			}

			$kept[] = $entry;
		}

		return count( $kept ) !== count( $palette ) ? array_values( $kept ) : null;
	}

	/**
	 * Normalise a colour string to a comparable `#rrggbb` key, or '' when it is not
	 * a plain hex (CSS `var(--…)`, `transparent`, `currentColor`, empty).
	 *
	 * @since x.x.x
	 *
	 * @param string $color Raw colour value.
	 * @return string Lower-case `#rrggbb`, or '' when not a comparable hex.
	 */
	private function normalize_hex_key( string $color ): string {
		$color = strtolower( trim( $color ) );
		if ( 1 === preg_match( '/^#([0-9a-f]{6})$/', $color ) ) {
			return $color;
		}
		if ( 1 === preg_match( '/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $color, $m ) ) {
			return '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
		}
		return '';
	}

	/**
	 * Explicit per-slug semantic colour overrides from the config.
	 *
	 * `config['semantic_overrides']` is a `slug => hex` map that pins a semantic
	 * colour to an exact value, winning over the `semantic_map` shade derivation.
	 * It exists for imported source colours whose semantic role the derivation
	 * would recompute incorrectly (e.g. a source brand dark accent bound to
	 * `quaternary` that Spectra would otherwise derive as a light primary tint).
	 * Values are pre-sanitized on save (hex only); malformed entries are skipped
	 * here too as defence in depth.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Map of semantic slug => hex.
	 */
	private function get_semantic_overrides(): array {
		$config    = $this->engine->get_config();
		$overrides = isset( $config['semantic_overrides'] ) && is_array( $config['semantic_overrides'] )
			? $config['semantic_overrides']
			: array();

		$clean = array();
		foreach ( $overrides as $slug => $hex ) {
			if ( is_string( $slug ) && '' !== $slug && is_string( $hex ) && '' !== $hex ) {
				$clean[ $slug ] = $hex;
			}
		}
		return $clean;
	}

	/**
	 * Enqueue the CSS custom properties stylesheet on the frontend.
	 *
	 * @since 3.1.0
	 * @return void
	 */
	public function enqueue_frontend_css(): void {
		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return;
		}

		$css = $tokens->get_css_string();

		if ( empty( $css ) ) {
			return;
		}

		// Inject Astra global color aliases into the :root block.
		$astra_css = $this->get_astra_compat_css();
		if ( ! empty( $astra_css ) ) {
			$css = str_replace( "\n}\n", "\n" . $astra_css . "\n}\n", $css );
		}

		// Inject sg-* WP preset color vars (bypasses theme.json user layer override).
		$sg_css = $this->get_sg_preset_css();
		if ( ! empty( $sg_css ) ) {
			$css = str_replace( "\n}\n", "\n" . $sg_css . "\n}\n", $css );
		}

		// Register a dummy handle and add inline CSS.
		wp_register_style( 'spectra-style-guide-tokens', false, array(), SPECTRA_BLOCKS_VER );
		wp_enqueue_style( 'spectra-style-guide-tokens' );
		wp_add_inline_style( 'spectra-style-guide-tokens', $css );

		// Component token CSS — styles sg-card, sg-btn-primary, etc. using tokens.
		$component_css_path = SPECTRA_BLOCKS_DIR . 'assets/css/component-tokens.css';
		if ( file_exists( $component_css_path ) ) {
			wp_enqueue_style(
				'spectra-component-tokens',
				SPECTRA_BLOCKS_URL . 'assets/css/component-tokens.css',
				array( 'spectra-style-guide-tokens' ),
				SPECTRA_BLOCKS_VER
			);
		}
	}

	/**
	 * Print the queried page's OWN palette ({@see Engine::page_config()}) as a
	 * `:root` block overriding the site's preset vars, for this page only.
	 *
	 * `wp_head` priority 100, NOT `enqueue_frontend_css`: the two `:root` blocks tie
	 * on specificity so source order decides, and core prints
	 * `global-styles-inline-css` AFTER our token stylesheet (measured on a real page
	 * at byte 41233 vs ours at 27979) — the site won and this did nothing.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function print_page_palette(): void {
		$css = $this->engine->page_preset_css();
		if ( '' === $css ) {
			return;
		}
		printf(
			"<style id='spectra-page-palette'>%s</style>\n",
			$css // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- slug + hex, sanitized in page_preset_css().
		);
	}

	/**
	 * Enqueue the CSS custom properties in the block editor.
	 *
	 * @since 3.1.0
	 * @return void
	 */
	public function enqueue_editor_css(): void {
		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return;
		}

		$css = $tokens->get_css_string();

		if ( empty( $css ) ) {
			return;
		}

		// Inject Astra global color aliases into the :root block.
		$astra_css = $this->get_astra_compat_css();
		if ( ! empty( $astra_css ) ) {
			$css = str_replace( "\n}\n", "\n" . $astra_css . "\n}\n", $css );
		}

		// Inject sg-* WP preset color vars.
		$sg_css = $this->get_sg_preset_css();
		if ( ! empty( $sg_css ) ) {
			$css = str_replace( "\n}\n", "\n" . $sg_css . "\n}\n", $css );
		}

		// Wrap for editor iframe scope.
		$editor_css = $css;

		wp_register_style( 'spectra-style-guide-tokens-editor', false, array(), SPECTRA_BLOCKS_VER );
		wp_enqueue_style( 'spectra-style-guide-tokens-editor' );
		wp_add_inline_style( 'spectra-style-guide-tokens-editor', $editor_css );

		// Component token CSS in editor.
		$component_css_path = SPECTRA_BLOCKS_DIR . 'assets/css/component-tokens.css';
		if ( file_exists( $component_css_path ) ) {
			wp_enqueue_style(
				'spectra-component-tokens-editor',
				SPECTRA_BLOCKS_URL . 'assets/css/component-tokens.css',
				array( 'spectra-style-guide-tokens-editor' ),
				SPECTRA_BLOCKS_VER
			);
		}
	}

	/**
	 * Generate Astra global color CSS variable alias declarations.
	 *
	 * Maps --ast-global-color-{0..8} to the corresponding --spectra-{shade_key}
	 * custom properties. Returns inline CSS lines (without a :root wrapper) to
	 * be injected into the existing :root block in get_css_string().
	 *
	 * The --spectra-* vars are guaranteed to exist because we emit them ourselves
	 * via get_css_string() in the same request — no dependency on
	 * wp_theme_json or WP preset CSS generation.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS lines or empty string if tokens are not available.
	 */
	private function get_astra_compat_css() {
		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return '';
		}

		// Only remap the theme's own global colours onto the Style Guide palette
		// when a Style Guide has actually been SAVED. `get_config()` falls back to
		// the engine's DEFAULT palette (so it's never empty) — check the RAW saved
		// option instead. With nothing saved, leave the theme's colours intact:
		// don't replace colours the user never saved.
		$saved_config = get_option( Engine::OPTION_KEY, array() );
		if ( ! is_array( $saved_config ) || empty( $saved_config ) ) {
			return '';
		}

		$lines   = array();
		$lines[] = '';
		$lines[] = "\t/* Astra global color compatibility aliases */";

		foreach ( self::ASTRA_SHADE_MAP as $index => $shade_key ) {
			$hex = $tokens->get( $shade_key );
			if ( null !== $hex ) {
				// Fall back to the token's resolved hex so the alias never becomes
				// invalid (which would break the colour) if the `--spectra-*` custom
				// property isn't present in this context's CSS for any reason.
				$lines[] = sprintf(
					"\t--ast-global-color-%d: var(--%s-%s, %s);",
					$index,
					TokenRegistry::PREFIX,
					esc_attr( $shade_key ),
					esc_attr( $hex )
				);
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * Generate sg-* WP preset color CSS variables.
	 *
	 * The theme.json user layer (saved Global Styles) can override the theme
	 * palette and strip out sg-* entries that we inject via wp_theme_json_data_theme.
	 * This method generates inline --wp--preset--color--sg-* vars that bypass
	 * theme.json entirely, guaranteeing sg-* slugs always resolve.
	 *
	 * @since 3.2.0
	 *
	 * @return string CSS lines for :root injection, or empty string.
	 */
	private function get_sg_preset_css() {
		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return '';
		}

		$config       = $this->engine->get_config();
		$semantic_map = isset( $config['semantic_map'] ) && is_array( $config['semantic_map'] ) ? $config['semantic_map'] : array();
		$overrides    = $this->get_semantic_overrides();

		// Which managed slugs to emit as inline `--wp--preset--color--*` vars.
		//
		// Always: the sg-* aliases (Spectra-only slugs the theme never defines).
		//
		// Additionally, in FREE mode with a saved Style Guide: the full SEMANTIC
		// palette (primary, secondary, heading, …). Those normally reach the front
		// end via inject_palette() → theme.json, but that is Pro-gated — so without
		// Pro the semantic `--wp--preset--color--*` vars were missing and any content
		// bound to them (e.g. a Spectra block whose Astra slug was rewritten to
		// `primary`) rendered with no colour. Emitting them here as inline :root vars
		// is the free fallback: it defines the variables WITHOUT adding any picker
		// swatch. Gated on a saved guide so an untouched site is never restyled, and
		// skipped when Pro is active since inject_palette() already covers them.
		$emit_semantic = ! Core::is_pro_active() && $this->engine->has_saved_style_guide();

		$emit_slugs = array();
		foreach ( array_keys( $semantic_map ) as $slug ) {
			if ( $emit_semantic || 0 === strpos( $slug, 'sg-' ) ) {
				$emit_slugs[ $slug ] = true;
			}
		}
		foreach ( array_keys( $overrides ) as $slug ) {
			if ( $emit_semantic || 0 === strpos( $slug, 'sg-' ) ) {
				$emit_slugs[ $slug ] = true;
			}
		}

		$lines   = array();
		$lines[] = '';
		$lines[] = "\t/* Style Guide WP preset color vars */";

		foreach ( array_keys( $emit_slugs ) as $slug ) {
			// Explicit override wins over the shade-derived value (mirrors
			// inject_palette); fall back to the semantic_map shade token.
			$hex = isset( $overrides[ $slug ] )
				? $overrides[ $slug ]
				: ( isset( $semantic_map[ $slug ] ) ? $tokens->get( $semantic_map[ $slug ] ) : null );

			if ( null !== $hex && '' !== $hex ) {
				$lines[] = sprintf(
					"\t--wp--preset--color--%s: %s;",
					esc_attr( $slug ),
					esc_attr( $hex )
				);
			}
		}

		return count( $lines ) > 2 ? implode( "\n", $lines ) : '';
	}

	/**
	 * Inject Astra color compat CSS directly into the block editor iframe.
	 *
	 * The block editor renders static blocks client-side using raw database
	 * attributes (e.g. textColor: "ast-global-color-0"). WordPress generates
	 * CSS like:
	 *   .has-ast-global-color-0-background-color { background-color: var(--wp--preset--color--ast-global-color-0) !important; }
	 *
	 * Without this injection, --wp--preset--color--ast-global-color-* does not
	 * exist in the iframe and block backgrounds render transparent.
	 *
	 * block_editor_settings_all['styles'] is the official WordPress mechanism
	 * for injecting CSS into the editor iframe canvas (used by core themes).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $settings Block editor settings.
	 * @return array<string, mixed> Modified settings.
	 */
	public function inject_astra_compat_editor_styles( $settings ) {
		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return $settings;
		}

		// Only remap the theme's global colours onto the Style Guide palette when a
		// Style Guide has actually been SAVED (raw option — get_config() falls back
		// to defaults). With nothing saved, leave the theme's own colours intact:
		// alias the WP preset var to Astra's OWN value (so editor previews still
		// resolve) instead of the default token palette. Mirrors the frontend guard
		// in get_astra_compat_css().
		$saved_config = get_option( Engine::OPTION_KEY, array() );
		$has_saved    = is_array( $saved_config ) && ! empty( $saved_config );

		$root_lines    = array( ':root {' );
		$utility_lines = array();

		foreach ( self::ASTRA_SHADE_MAP as $index => $shade_key ) {
			$hex = $tokens->get( $shade_key );

			if ( null === $hex ) {
				continue;
			}

			if ( $has_saved ) {
				// Saved Style Guide: remap the theme colour onto the SG palette.
				$hex_safe     = esc_attr( $hex );
				$root_lines[] = sprintf( "\t--wp--preset--color--ast-global-color-%d: %s;", $index, $hex_safe );
				$root_lines[] = sprintf( "\t--ast-global-color-%d: %s;", $index, $hex_safe );
			} else {
				// Nothing saved: keep the theme's own colour — only alias the WP
				// preset var to Astra's own value so previews resolve. Do NOT
				// override `--ast-global-color-%d`.
				$root_lines[] = sprintf( "\t--wp--preset--color--ast-global-color-%d: var(--ast-global-color-%d);", $index, $index );
			}

			// WP-style utility classes.
			// WP only generates these for palette-registered slugs. Since
			// ast-global-color-* are not registered (to avoid polluting the
			// color picker), we emit the classes ourselves so block editor
			// previews render block backgrounds/text/borders correctly.
			$var             = sprintf( 'var(--wp--preset--color--ast-global-color-%d)', $index );
			$utility_lines[] = sprintf( '.has-ast-global-color-%d-color { color: %s !important; }', $index, $var );
			$utility_lines[] = sprintf( '.has-ast-global-color-%d-background-color { background-color: %s !important; }', $index, $var );
			$utility_lines[] = sprintf( '.has-ast-global-color-%d-border-color { border-color: %s !important; }', $index, $var );
		}

		$root_lines[] = '}';

		// Astra's block-editor CSS clamps root-level containers to the theme
		// content width with a high-specificity (0,5,0) selector that also matches
		// an alignfull root container, overriding core's
		// `.is-root-container > .alignfull { max-width: none }` and stopping
		// full-bleed containers from breaking out in the editor. Re-assert full
		// bleed for alignfull root containers. WP scopes CSS injected here under
		// `.editor-styles-wrapper`, so Astra's `.ast-separate-container` ancestor
		// context can't be mirrored reliably; `!important` is the robust win and
		// matches core's intent — alignfull is never max-width-clamped. `none`
		// (not `100%`) is required: `100%` would re-clamp the block to the content
		// box and defeat the edge-to-edge breakout.
		$utility_lines[] = '.block-editor-block-list__layout.is-root-container > .spectra-is-root-container.alignfull { max-width: none !important; }';

		$css = implode( "\n", array_merge( $root_lines, array( '' ), $utility_lines ) );

		if ( ! isset( $settings['styles'] ) || ! is_array( $settings['styles'] ) ) {
			$settings['styles'] = array();
		}

		$settings['styles'][] = array( 'css' => $css );

		return $settings;
	}

	/**
	 * Get the localized data to pass to JS for the editor.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, mixed> Data for wp_localize_script.
	 */
	public function get_editor_data() {
		$tokens = $this->engine->get_token_registry();
		$config = $this->engine->get_config();

		return array(
			'config'   => $config,
			'tokens'   => null !== $tokens ? $tokens->get_all() : array(),
			'palette'  => null !== $tokens ? $tokens->get_wp_palette() : array(),
			'nonce'    => wp_create_nonce( 'spectra_style_guide' ),
			'rest_url' => rest_url( 'spectra-blocks/v1/style-guide' ),
		);
	}

	/**
	 * Sync Font Library families into the user global-styles layer (A21).
	 *
	 * Library CPTs alone emit NO @font-face: wp_print_font_faces resolves
	 * fonts from wp_get_global_settings()['typography']['fontFamilies'], so
	 * an installed family renders only once it exists in the merged settings.
	 * This sync mirrors what the Site Editor's Font Library UI writes on
	 * "activate": every published wp_font_family (+ its wp_font_face children)
	 * merged into `settings.typography.fontFamilies.custom` of the user
	 * wp_global_styles post, replace-by-slug (re-runs converge; entries for
	 * families no longer in the Library are left untouched — deactivation is
	 * out of scope for the import flow).
	 *
	 * Fired from save_post_{wp_font_family,wp_font_face} — every face save
	 * re-syncs its whole family set, so producer write-order doesn't matter.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function sync_font_library_families(): void {
		$families = get_posts(
			array(
				'post_type'      => 'wp_font_family',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		if ( empty( $families ) ) {
			return;
		}

		$library_entries = array();
		foreach ( $families as $family_post ) {
			$settings = json_decode( $family_post->post_content, true );
			$settings = is_array( $settings ) ? $settings : array();

			$entry = array(
				'fontFamily' => isset( $settings['fontFamily'] ) && is_string( $settings['fontFamily'] ) ? $settings['fontFamily'] : $family_post->post_title,
				'name'       => $family_post->post_title,
				'slug'       => $family_post->post_name,
			);

			$face_posts = get_posts(
				array(
					'post_type'      => 'wp_font_face',
					'post_status'    => 'publish',
					'post_parent'    => $family_post->ID,
					'posts_per_page' => 50,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);

			$faces = array();
			foreach ( $face_posts as $face_post ) {
				$face = json_decode( $face_post->post_content, true );
				if ( ! is_array( $face ) || empty( $face['src'] ) ) {
					continue;
				}
				// Core accepts string or array src; normalize to array (the
				// editor's own activation writes arrays).
				$face['src'] = is_array( $face['src'] ) ? array_values( $face['src'] ) : array( $face['src'] );
				$faces[]     = $face;
			}

			// A family with no usable faces emits nothing — skip it so the
			// activation layer never references font files that don't exist.
			if ( empty( $faces ) ) {
				continue;
			}
			$entry['fontFace'] = $faces;

			$library_entries[ $entry['slug'] ] = $entry;
		}

		if ( empty( $library_entries ) ) {
			return;
		}

		$query = new \WP_Query(
			array(
				'post_type'              => 'wp_global_styles',
				'posts_per_page'         => 1,
				'post_status'            => array( 'publish', 'auto-draft' ),
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query'              => array(
					array(
						'taxonomy' => 'wp_theme',
						'field'    => 'name',
						'terms'    => get_stylesheet(),
					),
				),
			)
		);
		$posts = $query->posts;

		if ( empty( $posts ) ) {
			// Classic themes have no user global-styles post until something
			// creates one (the Site Editor does on block themes, masking
			// this bail). Use core's get-or-create so Font Library
			// activation works on ANY theme — measured live on Astra:
			// families + faces installed, ZERO faces printed, because this
			// sync silently returned here.
			$user_post_id = class_exists( '\WP_Theme_JSON_Resolver' )
				? \WP_Theme_JSON_Resolver::get_user_global_styles_post_id()
				: 0;
			$user_post    = $user_post_id ? get_post( $user_post_id ) : null;
			if ( ! $user_post instanceof \WP_Post ) {
				return;
			}
			$posts = array( $user_post );
		}

		$post    = $posts[0];
		$content = json_decode( $post->post_content, true );
		if ( ! is_array( $content ) ) {
			$content = array();
		}

		// Merge replace-by-slug: Library entries win over stale same-slug
		// entries; foreign custom entries (user uploads we didn't produce)
		// are preserved.
		$existing = $content['settings']['typography']['fontFamilies']['custom'] ?? array();
		$merged   = array();
		if ( is_array( $existing ) ) {
			foreach ( $existing as $family ) {
				$slug = isset( $family['slug'] ) ? (string) $family['slug'] : '';
				if ( '' !== $slug && ! isset( $library_entries[ $slug ] ) ) {
					$merged[] = $family;
				}
			}
		}
		foreach ( $library_entries as $entry ) {
			$merged[] = $entry;
		}

		$content['settings']['typography']['fontFamilies']['custom'] = $merged;

		// Use $wpdb->update() directly — content_save_pre would mangle the JSON
		// unicode escapes; a raw update keeps the stored theme.json intact.
		global $wpdb;
		$encoded = wp_json_encode( $content, JSON_UNESCAPED_UNICODE );
		if ( false === $encoded ) {
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- intentional bypass of wp_update_post to avoid content_save_pre filter chain.
		$wpdb->update(
			$wpdb->posts,
			array( 'post_content' => $encoded ),
			array( 'ID' => $post->ID ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $post->ID );
	}

	/**
	 * Inject the Style Guide token CSS into the editor iframe.
	 *
	 * The block editor renders blocks inside an iframe canvas.
	 * enqueue_block_editor_assets only reaches the admin <head>, not the iframe.
	 * block_editor_settings_all['styles'] is the official mechanism for iframe CSS.
	 *
	 * @since 3.2.0
	 *
	 * @param array<string, mixed> $settings Block editor settings.
	 * @return array<string, mixed> Modified settings.
	 */
	public function inject_sg_editor_styles( $settings ) {
		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return $settings;
		}

		if ( ! isset( $settings['styles'] ) || ! is_array( $settings['styles'] ) ) {
			$settings['styles'] = array();
		}

		// Inject main SG token CSS into the editor canvas iframe.
		// enqueue_block_editor_assets only reaches the admin <head> — block_editor_settings_all
		// styles[] is the official mechanism for reaching the editor iframe canvas.
		$token_css = $tokens->get_css_string();
		if ( ! empty( $token_css ) ) {
			// Append Astra compat aliases and sg-* preset vars into the same :root block.
			$astra_css = $this->get_astra_compat_css();
			if ( ! empty( $astra_css ) ) {
				$token_css = str_replace( "\n}\n", "\n" . $astra_css . "\n}\n", $token_css );
			}
			$sg_css = $this->get_sg_preset_css();
			if ( ! empty( $sg_css ) ) {
				$token_css = str_replace( "\n}\n", "\n" . $sg_css . "\n}\n", $token_css );
			}
			$settings['styles'][] = array( 'css' => $token_css );
		}

		return $settings;
	}

	/**
	 * Align the Astra global-color picker swatches with the colour that actually
	 * renders when they are applied.
	 *
	 * Astra registers its ast-global-color-{N} palette entries with a CSS-variable
	 * value (`var(--ast-global-color-N)`), so the swatch shown in the picker
	 * resolves that variable in the sidebar DOM — where Astra's own stylesheet wins,
	 * showing Astra's colour. But those slugs are routed through the Style Guide
	 * palette when a block uses them (rewritten to sg-* at render, aliased to the
	 * mapped shade token in the canvas), so the applied colour is the Style Guide's,
	 * not Astra's. That split is why the swatch and the applied colour disagree.
	 *
	 * Overwriting each swatch's value with the resolved ASTRA_SHADE_MAP hex makes the
	 * picker show the colour that will actually be applied. Runs on the final,
	 * fully-merged block-editor settings so it is the last word regardless of how the
	 * theme registered its palette.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $settings Block editor settings.
	 * @return array<string, mixed> Modified settings.
	 */
	public function align_astra_palette_swatches( $settings ) {
		// Previewing Style Guide colours in the editor picker is a Pro capability.
		// With Pro inactive, leave Astra's own global-colour swatches at the theme's
		// values so the picker does not surface the Style Guide palette. The colours
		// are still APPLIED to content (the render-time rewrite + --ast-global-color-*
		// aliases in get_astra_compat_css() are free), so only the picker preview
		// differs — mirrors remove_style_guide_swatches_when_pro_inactive().
		if ( ! defined( 'SPECTRA_BLOCKS_PRO_VER' ) ) {
			return $settings;
		}

		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return $settings;
		}

		// Only remap the swatches when a Style Guide has actually been SAVED.
		// The applied colour for ast-global-color-* is the theme's own until a
		// Style Guide is saved (the render-time slug rewrite in
		// Engine::rewrite_astra_color_attrs() and the --ast-global-color-*
		// aliases in get_astra_compat_css()/inject_astra_compat_editor_styles()
		// are all gated on a saved guide). Remapping the swatch to the Style
		// Guide palette here without that gate makes the picker preview a colour
		// that will NOT be applied — the swatch/applied mismatch on an untouched
		// site. With nothing saved, leave the theme's own swatches intact so the
		// picker matches what actually renders. Mirrors the guards in
		// get_astra_compat_css() / inject_astra_compat_editor_styles().
		$saved_config = get_option( Engine::OPTION_KEY, array() );
		if ( ! is_array( $saved_config ) || empty( $saved_config ) ) {
			return $settings;
		}

		$astra_remap = array();
		foreach ( self::ASTRA_SHADE_MAP as $index => $shade_key ) {
			$hex = $tokens->get( $shade_key );
			if ( null !== $hex ) {
				$astra_remap[ 'ast-global-color-' . $index ] = $hex;
			}
		}

		if ( empty( $astra_remap ) ) {
			return $settings;
		}

		// The block colour UI (useMultipleOriginColorsAndGradients) reads the
		// origin-keyed palette under __experimentalFeatures.color.palette.theme.
		// Each nesting level is is_array-guarded so the chain is a proven array
		// (not mixed) when iterated by reference below.
		if ( isset( $settings['__experimentalFeatures'] ) && is_array( $settings['__experimentalFeatures'] )
			&& isset( $settings['__experimentalFeatures']['color'] ) && is_array( $settings['__experimentalFeatures']['color'] )
			&& isset( $settings['__experimentalFeatures']['color']['palette'] ) && is_array( $settings['__experimentalFeatures']['color']['palette'] )
			&& isset( $settings['__experimentalFeatures']['color']['palette']['theme'] ) && is_array( $settings['__experimentalFeatures']['color']['palette']['theme'] ) ) {
			foreach ( $settings['__experimentalFeatures']['color']['palette']['theme'] as &$feature_entry ) {
				if ( isset( $feature_entry['slug'], $astra_remap[ $feature_entry['slug'] ] ) ) {
					$feature_entry['color'] = $astra_remap[ $feature_entry['slug'] ];
				}
			}
			unset( $feature_entry );
		}

		// Legacy flat palette (settings.colors) used by older/classic colour controls.
		if ( isset( $settings['colors'] ) && is_array( $settings['colors'] ) ) {
			foreach ( $settings['colors'] as &$legacy_entry ) {
				if ( isset( $legacy_entry['slug'], $astra_remap[ $legacy_entry['slug'] ] ) ) {
					$legacy_entry['color'] = $astra_remap[ $legacy_entry['slug'] ];
				}
			}
			unset( $legacy_entry );
		}

		return $settings;
	}

	/**
	 * Remove the Style-Guide-managed swatches from the editor colour picker when
	 * Pro is inactive.
	 *
	 * Surfacing the Style Guide palette in the picker is a Pro capability, so with
	 * Pro deactivated the Style Guide colours must not appear as pickable swatches.
	 * This ONLY filters the editor picker palette (the `__experimentalFeatures`
	 * colour settings the block colour UI reads, plus the legacy flat `colors`
	 * list) — it does NOT touch `inject_palette()` or any `--wp--preset--color--*`
	 * variable, so a block that already uses a Style Guide colour keeps rendering
	 * it (the variable still resolves; only its swatch is hidden).
	 *
	 * Hooked to block_editor_settings_all at priority 21 (after
	 * align_astra_palette_swatches at 20). A no-op while Pro is active, or before a
	 * Style Guide has been saved.
	 *
	 * @since x.x.x
	 *
	 * @param array<string, mixed> $settings Block editor settings.
	 * @return array<string, mixed> Filtered settings.
	 */
	public function remove_style_guide_swatches_when_pro_inactive( $settings ) {
		// Pro active — the picker keeps the Style Guide swatches.
		if ( Core::is_pro_active() ) {
			return $settings;
		}

		// Nothing to strip until a Style Guide has been saved.
		if ( ! $this->engine->has_saved_style_guide() ) {
			return $settings;
		}

		$managed = $this->engine->get_managed_color_slugs();
		if ( empty( $managed ) ) {
			return $settings;
		}
		$managed = array_flip( $managed );

		// Drop managed slugs from every origin the block colour UI reads
		// (useMultipleOriginColorsAndGradients): theme / default / custom. Each
		// nesting level is is_array-guarded so the chain is a proven array (not
		// mixed) when reassigned by origin below.
		if ( isset( $settings['__experimentalFeatures'] ) && is_array( $settings['__experimentalFeatures'] )
			&& isset( $settings['__experimentalFeatures']['color'] ) && is_array( $settings['__experimentalFeatures']['color'] )
			&& isset( $settings['__experimentalFeatures']['color']['palette'] ) && is_array( $settings['__experimentalFeatures']['color']['palette'] ) ) {
			foreach ( array( 'theme', 'default', 'custom' ) as $origin ) {
				if ( ! isset( $settings['__experimentalFeatures']['color']['palette'][ $origin ] ) || ! is_array( $settings['__experimentalFeatures']['color']['palette'][ $origin ] ) ) {
					continue;
				}
				$settings['__experimentalFeatures']['color']['palette'][ $origin ] = array_values(
					array_filter(
						$settings['__experimentalFeatures']['color']['palette'][ $origin ],
						static function ( $entry ) use ( $managed ) {
							return ! ( isset( $entry['slug'] ) && isset( $managed[ $entry['slug'] ] ) );
						}
					)
				);
			}
		}

		// Legacy flat palette (settings.colors) used by older/classic colour controls.
		if ( isset( $settings['colors'] ) && is_array( $settings['colors'] ) ) {
			$settings['colors'] = array_values(
				array_filter(
					$settings['colors'],
					static function ( $entry ) use ( $managed ) {
						return ! ( isset( $entry['slug'] ) && isset( $managed[ $entry['slug'] ] ) );
					}
				)
			);
		}

		return $settings;
	}

	/**
	 * Strip sg-* alias swatches from the server-built block editor settings.
	 *
	 * Companion to {@see strip_sg_alias_swatches_from_rest()} for the picker
	 * surfaces that read the SERVER-built settings instead of the client-merged
	 * global-styles entities: the widgets/customizer editors and the legacy flat
	 * `settings.colors` list. sg-* slugs are render-time compat aliases, not
	 * pickable roles — each one duplicates a semantic swatch under a second name.
	 * The render path is untouched: .has-sg-*-color rules and the
	 * --wp--preset--color--sg-* variables keep being emitted.
	 *
	 * Hooked to block_editor_settings_all at priority 22 (after the Pro-inactive
	 * strip, which already removes every managed swatch when Pro is off).
	 *
	 * @since x.x.x
	 *
	 * @param array<string, mixed> $settings Block editor settings.
	 * @return array<string, mixed>
	 */
	public function remove_sg_alias_swatches( $settings ) {
		if ( isset( $settings['__experimentalFeatures'] ) && is_array( $settings['__experimentalFeatures'] )
			&& isset( $settings['__experimentalFeatures']['color'] ) && is_array( $settings['__experimentalFeatures']['color'] )
			&& isset( $settings['__experimentalFeatures']['color']['palette'] ) && is_array( $settings['__experimentalFeatures']['color']['palette'] ) ) {
			foreach ( array( 'theme', 'default', 'custom' ) as $origin ) {
				if ( ! isset( $settings['__experimentalFeatures']['color']['palette'][ $origin ] ) || ! is_array( $settings['__experimentalFeatures']['color']['palette'][ $origin ] ) ) {
					continue;
				}
				$filtered = $this->strip_picker_duplicate_entries( $settings['__experimentalFeatures']['color']['palette'][ $origin ] );
				if ( null !== $filtered ) {
					$settings['__experimentalFeatures']['color']['palette'][ $origin ] = $filtered;
				}
			}
		}

		if ( isset( $settings['colors'] ) && is_array( $settings['colors'] ) ) {
			$filtered = $this->strip_picker_duplicate_entries( $settings['colors'] );
			if ( null !== $filtered ) {
				$settings['colors'] = $filtered;
			}
		}

		return $settings;
	}

	/**
	 * Inject Style Guide token CSS variables into the editor iframe.
	 *
	 * Component-tokens.css is enqueued via enqueue_block_editor_assets and reaches
	 * the editor iframe, but the token variable definitions (:root { --spectra-btn-text: ... })
	 * are added via wp_add_inline_style() which only reaches the admin <head>.
	 * This filter ensures the variables are also available inside the iframe canvas
	 * so that rules like color:var(--spectra-btn-text) resolve correctly.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $settings Block editor settings.
	 * @return array<string, mixed> Modified settings.
	 */
	public function inject_token_editor_styles( $settings ) {
		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return $settings;
		}

		$css = $tokens->get_css_string_with_legacy();

		if ( empty( $css ) ) {
			return $settings;
		}

		if ( ! isset( $settings['styles'] ) || ! is_array( $settings['styles'] ) ) {
			$settings['styles'] = array();
		}

		$settings['styles'][] = array( 'css' => $css );

		return $settings;
	}
}
