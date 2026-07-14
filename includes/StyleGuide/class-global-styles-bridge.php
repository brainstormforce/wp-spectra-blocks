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
	 * Used to emit --ast-global-color-{N} CSS aliases that point to our
	 * --spectra-{shade_key} custom properties, so Astra-based patterns and
	 * imported content render correctly on any FSE theme without content changes.
	 *
	 * Astra default index semantics:
	 *   0 = Primary (brand accent)
	 *   1 = Secondary (link hover / accent variant)
	 *   2 = Heading text
	 *   3 = Body text
	 *   4 = Light background (section bg / surface)
	 *   5 = White / page background
	 *   6 = Border / outline
	 *   7 = Dark background (footer, dark sections)
	 *   8 = Extra (varies)
	 *
	 * @since 1.0.0
	 * @var array<int, string>
	 */
	const ASTRA_SHADE_MAP = array(
		0 => 'chromatic1-7',
		1 => 'chromatic1-5',
		2 => 'neutral-7',
		3 => 'neutral-5',
		4 => 'neutral-1',
		5 => 'neutral-0',
		6 => 'neutral-7',
		7 => 'neutral-2',
		8 => 'neutral-7',
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

		// Theme typography override (opt-in; ALL themes, incl. Astra/Spectra One —
		// typography has no dedicated per-theme path): remap the theme's own
		// font-family presets to the Style Guide fonts, and force heading elements onto
		// the Style Guide heading font. Priority 21 (after inject_palette).
		add_filter( 'wp_theme_json_data_theme', array( $this, 'override_theme_typography' ), 21 );

		// Theme spacing + shadow override (opt-in; ALL themes). Remap the theme's
		// spacing scale onto the Style Guide scale, and register the Style Guide shadow
		// presets. Priority 22 (Phase C).
		add_filter( 'wp_theme_json_data_theme', array( $this, 'override_theme_spacing_shadow' ), 22 );

		// Normalize sg-* palette names in the user layer (saved global styles).
		// User-layer names take precedence over theme-layer, so stale "Sg-*" names
		// stored in wp_global_styles must be fixed here.
		add_filter( 'wp_theme_json_data_user', array( $this, 'normalize_user_palette_names' ), 20 );

		// Option-only mode: overwrite stale Spectra-managed colours in the user
		// layer with the current computed values so the option is authoritative
		// without leaving any slug undefined. Runs after normalize (21 > 20).
		add_filter( 'wp_theme_json_data_user', array( $this, 'maybe_override_managed_user_palette' ), 21 );

		// Enqueue the CSS variables on the frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_css' ), 5 );

		// Enqueue in the block editor.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_css' ) );

		// A5: self-heal the wp_global_styles REST entity the editor loads. The entity
		// is returned raw (bypassing the wp_theme_json_data_* filters), so a stale /
		// imported palette or font pin stored there would shadow the Style Guide values
		// in the editor. Strip the managed presets so the theme-layer Style Guide wins.
		add_filter( 'rest_request_after_callbacks', array( $this, 'heal_global_styles_entity' ), 10, 3 );

		// Inject Astra compat CSS directly into the editor iframe via block_editor_settings_all.
		// enqueue_block_editor_assets only reaches the admin <head>, not the iframe canvas.
		add_filter( 'block_editor_settings_all', array( $this, 'inject_astra_compat_editor_styles' ) );

		// Inject scheme variable mapping CSS into the editor iframe.
		add_filter( 'block_editor_settings_all', array( $this, 'inject_scheme_editor_styles' ) );

		// Inject token CSS variables (including legacy mappings) into the editor iframe.
		add_filter( 'block_editor_settings_all', array( $this, 'inject_token_editor_styles' ) );

		// Align the Astra global-color picker swatches with the colour that actually
		// gets applied. Runs late (priority 20) so it has the final, fully-merged
		// palette to rewrite.
		add_filter( 'block_editor_settings_all', array( $this, 'align_astra_palette_swatches' ), 20 );

		// Auto-add .spectra-dark-scheme class to blocks with dark background schemes.
		add_filter( 'render_block', array( $this, 'maybe_add_dark_scheme_class' ), 10, 2 );

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
		// Ensure tokens are computed — this filter can fire before 'init',
		// before Engine::maybe_compute() has had a chance to run.
		$this->engine->maybe_compute();

		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return $theme_json;
		}

		$spectra_palette = $tokens->get_wp_palette();

		if ( empty( $spectra_palette ) ) {
			return $theme_json;
		}

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

			// Update existing theme palette entries that match semantic slugs.
			// This ensures "primary", "heading", "body" etc. get the computed shade value.
			$updated_existing = array();
			foreach ( $existing_palette as $entry ) {
				if ( isset( $semantic_slugs[ $entry['slug'] ] ) ) {
					$entry['color'] = $semantic_slugs[ $entry['slug'] ];
					$entry['name']  = TokenRegistry::format_slug_label( $entry['slug'] );
					unset( $semantic_slugs[ $entry['slug'] ] );
				}
				$updated_existing[] = $entry;
			}

			// Add any semantic colors that weren't already in the theme palette.
			foreach ( $semantic_slugs as $slug => $hex ) {
				$updated_existing[] = array(
					'slug'  => $slug,
					'color' => $hex,
					'name'  => TokenRegistry::format_slug_label( $slug ),
				);
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
	 * Override the active theme's typography presets with the Style Guide fonts.
	 *
	 * Generic-theme (non-Astra/Spectra-One) opt-in. Two parts:
	 *   1. Remap the theme's `fontFamilies` preset values → the Style Guide body font
	 *      (heading-designated slugs → the heading font). Rewriting the theme.json
	 *      preset makes BOTH the font-family dropdown and `--wp--preset--font-family--*`
	 *      adopt the Style Guide, since both derive from theme.json.
	 *   2. Force the theme's heading element(s) onto the Style Guide heading font, so
	 *      headings use the display face even when the theme ships a single family.
	 *
	 * Hooked to wp_theme_json_data_theme at priority 21 (after inject_palette).
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Theme_JSON_Data $theme_json The theme JSON data object.
	 * @return \WP_Theme_JSON_Data Modified theme JSON data.
	 */
	public function override_theme_typography( $theme_json ) {
		if ( ! ThemeStyleCompat::should_override_typography() ) {
			return $theme_json;
		}

		$config = $this->engine->get_config();
		$data   = $theme_json->get_data();

		// Part 1 — remap the theme's font-family presets.
		$families = array();
		if ( isset( $data['settings']['typography']['fontFamilies']['theme'] ) && is_array( $data['settings']['typography']['fontFamilies']['theme'] ) ) {
			$families = $data['settings']['typography']['fontFamilies']['theme'];
		}

		$updated_families = $families;
		if ( ! empty( $families ) ) {
			$font_slugs = array();
			foreach ( $families as $entry ) {
				if ( isset( $entry['slug'] ) ) {
					$font_slugs[] = $entry['slug'];
				}
			}

			$ff_overrides = ThemeStyleCompat::resolve_font_family_overrides( $font_slugs, $config );
			if ( ! empty( $ff_overrides ) ) {
				foreach ( $updated_families as &$ff_entry ) {
					if ( isset( $ff_entry['slug'], $ff_overrides[ $ff_entry['slug'] ] ) ) {
						$ff_entry['fontFamily'] = $ff_overrides[ $ff_entry['slug'] ];
					}
				}
				unset( $ff_entry );
			}
		}

		// Part 2 — force headings onto the Style Guide heading font.
		$heading_stack = ThemeStyleCompat::get_heading_font_stack( $config );

		$new_data = array(
			'version'  => 2,
			'settings' => array(
				'typography' => array(
					'fontFamilies' => array(
						'theme' => $updated_families,
					),
				),
			),
		);

		if ( '' !== $heading_stack ) {
			$new_data['styles'] = array(
				'elements' => array(
					'heading' => array(
						'typography' => array(
							'fontFamily' => $heading_stack,
						),
					),
				),
			);
		}

		return $theme_json->update_with( $new_data );
	}

	/**
	 * Override the active theme's spacing scale and shadow presets with the Style
	 * Guide's (Phase C). Opt-in, all themes.
	 *
	 *   1. Spacing — remap each theme `spacingSizes` step onto the Style Guide scale
	 *      (proportional, count/naming-agnostic). Rewriting theme.json makes BOTH the
	 *      spacing picker and `--wp--preset--spacing--*` adopt the Style Guide.
	 *   2. Shadow — register the Style Guide shadow presets (adds them to the picker +
	 *      `--wp--preset--shadow--*`; replaces any the theme defines).
	 *
	 * Hooked to wp_theme_json_data_theme at priority 22.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Theme_JSON_Data $theme_json The theme JSON data object.
	 * @return \WP_Theme_JSON_Data Modified theme JSON data.
	 */
	public function override_theme_spacing_shadow( $theme_json ) {
		$do_spacing = ThemeStyleCompat::should_override_spacing();
		$do_shadow  = ThemeStyleCompat::should_override_shadow();

		if ( ! $do_spacing && ! $do_shadow ) {
			return $theme_json;
		}

		$tokens = $this->engine->get_token_registry();
		if ( null === $tokens ) {
			return $theme_json;
		}

		$config       = $this->engine->get_config();
		$data         = $theme_json->get_data();
		$new_settings = array();

		// Part 1 — spacing.
		if ( $do_spacing ) {
			$sizes = array();
			if ( isset( $data['settings']['spacing']['spacingSizes']['theme'] ) && is_array( $data['settings']['spacing']['spacingSizes']['theme'] ) ) {
				$sizes = $data['settings']['spacing']['spacingSizes']['theme'];
			}

			if ( ! empty( $sizes ) ) {
				$spacing_slugs = array();
				foreach ( $sizes as $entry ) {
					if ( isset( $entry['slug'] ) ) {
						$spacing_slugs[] = $entry['slug'];
					}
				}

				$overrides = ThemeStyleCompat::resolve_spacing_overrides( $spacing_slugs, $tokens );
				if ( ! empty( $overrides ) ) {
					foreach ( $sizes as &$size_entry ) {
						if ( isset( $size_entry['slug'], $overrides[ $size_entry['slug'] ] ) ) {
							$size_entry['size'] = $overrides[ $size_entry['slug'] ];
						}
					}
					unset( $size_entry );

					$new_settings['spacing'] = array( 'spacingSizes' => array( 'theme' => $sizes ) );
				}
			}
		}

		// Part 2 — shadow.
		if ( $do_shadow ) {
			$presets = ThemeStyleCompat::get_shadow_presets( $config );
			if ( ! empty( $presets ) ) {
				$new_settings['shadow'] = array( 'presets' => array( 'theme' => $presets ) );
			}
		}

		if ( empty( $new_settings ) ) {
			return $theme_json;
		}

		return $theme_json->update_with(
			array(
				'version'  => 2,
				'settings' => $new_settings,
			)
		);
	}

	/**
	 * A5 — self-heal the user global-styles REST entity the block editor loads.
	 *
	 * The editor hydrates global styles via `getEntityRecord('root','globalStyles')`
	 * → `GET /wp/v2/global-styles/{id}`, whose controller returns the stored
	 * `settings`/`styles` VERBATIM — it never runs the `wp_theme_json_data_*` filters,
	 * so `maybe_override_managed_user_palette()` (and the theme-layer palette/typography
	 * overrides) don't reach it. A stale or imported palette / font pin stored in that
	 * record therefore shadows the Style Guide values in the editor (the post-698 bug).
	 *
	 * We strip the managed presets from the RESPONSE (not the stored post), so the
	 * editor falls back to the theme layer — which the Style Guide already controls.
	 * Non-destructive and self-healing for future imports; retires the manual scrub.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $response The REST response (WP_REST_Response|WP_Error|mixed).
	 * @param array<string, mixed> $handler  The matched route handler (unused).
	 * @param \WP_REST_Request     $request  The request.
	 * @return mixed The (possibly modified) response.
	 */
	public function heal_global_styles_entity( $response, $handler, $request ) {
		unset( $handler );

		if ( ! ( $response instanceof \WP_REST_Response ) || ! ( $request instanceof \WP_REST_Request ) ) {
			return $response;
		}
		if ( 'GET' !== $request->get_method() ) {
			return $response;
		}
		// The USER global-styles entity: /wp/v2/global-styles/{id}. Exclude the theme
		// defaults route (/global-styles/themes/{stylesheet}).
		if ( ! preg_match( '#/global-styles/\d+$#', (string) $request->get_route() ) ) {
			return $response;
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) ) {
			return $response;
		}

		$changed = false;

		// Colour is owned by the Style Guide (theme layer) on every theme — drop any
		// stored palette so a stale/imported one can't shadow it in the editor.
		// Core returns `settings` as an empty stdClass when the record has none
		// (fresh site / non-user theme.json); guard the type first — isset() on an
		// object's array-access still fatals in PHP 8.
		if ( is_array( $data['settings'] ?? null ) && isset( $data['settings']['color']['palette'] ) ) {
			unset( $data['settings']['color']['palette'] );
			$changed = true;
		}

		// Typography: when the override is on, drop stored font-family pins so the
		// theme-layer Style Guide fonts apply.
		if ( ThemeStyleCompat::should_override_typography() && is_array( $data['settings'] ?? null ) ) {
			if ( isset( $data['settings']['typography']['fontFamilies'] ) ) {
				unset( $data['settings']['typography']['fontFamilies'] );
				$changed = true;
			}
			if ( isset( $data['styles'] ) && is_array( $data['styles'] ) ) {
				$stripped = $this->strip_font_family_pins( $data['styles'] );
				if ( $stripped !== $data['styles'] ) {
					$data['styles'] = $stripped;
					$changed        = true;
				}
			}
		}

		if ( $changed ) {
			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * Recursively remove `fontFamily` keys from a styles tree (typography pins),
	 * leaving other typography (size/weight/line-height) intact.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $styles A styles (sub)tree.
	 * @return array<string, mixed> The styles tree without fontFamily pins.
	 */
	private function strip_font_family_pins( array $styles ): array {
		foreach ( $styles as $key => $value ) {
			if ( 'fontFamily' === $key ) {
				unset( $styles[ $key ] );
				continue;
			}
			if ( is_array( $value ) ) {
				$styles[ $key ] = $this->strip_font_family_pins( $value );
			}
		}
		return $styles;
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

		if ( empty( $palette ) ) {
			return $theme_json;
		}

		// Build slug → current Spectra colour, mirroring inject_palette(): the
		// shade palette (get_wp_palette) plus every semantic_map slug resolved to
		// its shade value.
		$this->engine->maybe_compute();
		$tokens = $this->engine->get_token_registry();
		if ( null === $tokens ) {
			return $theme_json;
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
			return $theme_json;
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
		// set, generically — no per-slug special-casing.
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

		if ( ! $changed ) {
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

		// Inject shadow / spacing / font-family WP preset vars (bypasses the user
		// layer the same way, guaranteeing the era-contract vars resolve).
		$extra_css = $this->get_sg_extra_preset_css();
		if ( ! empty( $extra_css ) ) {
			$css = str_replace( "\n}\n", "\n" . $extra_css . "\n}\n", $css );
		}

		// Register a dummy handle and add inline CSS.
		wp_register_style( 'spectra-style-guide-tokens', false, array(), SPECTRA_BLOCKS_VER );
		wp_enqueue_style( 'spectra-style-guide-tokens' );
		wp_add_inline_style( 'spectra-style-guide-tokens', $css );

		// Scheme variable mapping CSS — maps each [data-spectra-scheme="key"]
		// to its 5 CSS custom properties. Must come before scheme-override.css
		// which consumes these variables.
		$scheme_css = $tokens->get_scheme_css();
		if ( ! empty( $scheme_css ) ) {
			wp_add_inline_style( 'spectra-style-guide-tokens', $scheme_css );
		}

		// Self-hosted @font-face fallback: ensures selected fonts render on the frontend
		// even before WordPress generates @font-face CSS from the saved Global Styles entity.
		$font_face_css = $this->get_font_face_css( $this->get_active_font_slugs() );
		if ( ! empty( $font_face_css ) ) {
			wp_add_inline_style( 'spectra-style-guide-tokens', $font_face_css );
		}

		// Also enqueue the scheme override CSS if it exists.
		$scheme_css_path = SPECTRA_BLOCKS_DIR . 'assets/css/scheme-override.css';
		if ( file_exists( $scheme_css_path ) ) {
			wp_enqueue_style(
				'spectra-scheme-override',
				SPECTRA_BLOCKS_URL . 'assets/css/scheme-override.css',
				array( 'spectra-style-guide-tokens' ),
				SPECTRA_BLOCKS_VER
			);
		}

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

		// Inject shadow / spacing / font-family WP preset vars.
		$extra_css = $this->get_sg_extra_preset_css();
		if ( ! empty( $extra_css ) ) {
			$css = str_replace( "\n}\n", "\n" . $extra_css . "\n}\n", $css );
		}

		// Wrap for editor iframe scope.
		$editor_css = $css;

		wp_register_style( 'spectra-style-guide-tokens-editor', false, array(), SPECTRA_BLOCKS_VER );
		wp_enqueue_style( 'spectra-style-guide-tokens-editor' );
		wp_add_inline_style( 'spectra-style-guide-tokens-editor', $editor_css );

		// Scheme variable mapping CSS for editor.
		$scheme_css = $tokens->get_scheme_css();
		if ( ! empty( $scheme_css ) ) {
			wp_add_inline_style( 'spectra-style-guide-tokens-editor', $scheme_css );
		}

		// Self-hosted @font-face in the editor: ensures selected fonts render in the
		// block editor even before WordPress regenerates @font-face CSS from Global Styles.
		$font_face_css = $this->get_font_face_css( $this->get_active_font_slugs() );
		if ( ! empty( $font_face_css ) ) {
			wp_add_inline_style( 'spectra-style-guide-tokens-editor', $font_face_css );
		}

		// Scheme override CSS in editor.
		$scheme_css_path = SPECTRA_BLOCKS_DIR . 'assets/css/scheme-override.css';
		if ( file_exists( $scheme_css_path ) ) {
			wp_enqueue_style(
				'spectra-scheme-override-editor',
				SPECTRA_BLOCKS_URL . 'assets/css/scheme-override.css',
				array( 'spectra-style-guide-tokens-editor' ),
				SPECTRA_BLOCKS_VER
			);
		}

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

		// The set of sg-* slugs to emit = every sg-* in the map, plus any sg-*
		// that only exists as an explicit override (import-pinned, not in the map).
		$sg_slugs = array();
		foreach ( array_keys( $semantic_map ) as $slug ) {
			if ( 0 === strpos( $slug, 'sg-' ) ) {
				$sg_slugs[ $slug ] = true;
			}
		}
		foreach ( array_keys( $overrides ) as $slug ) {
			if ( 0 === strpos( $slug, 'sg-' ) ) {
				$sg_slugs[ $slug ] = true;
			}
		}

		$lines   = array();
		$lines[] = '';
		$lines[] = "\t/* sg-* WP preset color aliases */";

		foreach ( array_keys( $sg_slugs ) as $slug ) {
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
	 * Emit shadow, spacing (gutter) and font-family WP preset variables on :root.
	 *
	 * The Style Guide owns colours in theme.json, but the theme supplies (and the
	 * user layer shadows) the type / spacing / shadow scales. These era-contract
	 * vars otherwise have no reliable producer, so we emit them directly on :root —
	 * the same cascade-wins mechanism used for the sg-* colour aliases.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS custom-property lines, or '' when nothing to emit.
	 */
	private function get_sg_extra_preset_css() {
		$tokens = $this->engine->get_token_registry();
		if ( null === $tokens ) {
			return '';
		}

		$config  = $this->engine->get_config();
		$presets = isset( $config['presets'] ) && is_array( $config['presets'] ) ? $config['presets'] : array();
		$lines   = array();

		// Shadow presets (8), driven by shadowDepth.
		$depth = isset( $presets['shadowDepth'] ) ? (string) $presets['shadowDepth'] : 'subtle';
		foreach ( TokenRegistry::get_era_shadow_presets( $depth ) as $name => $value ) {
			$lines[] = sprintf( "\t--wp--preset--shadow--%s: %s;", sanitize_key( $name ), $value );
		}

		// Spacing: gutter (density-scaled, mirrors the space-sm step).
		$gutter = $tokens->get( 'space-sm' );
		if ( null === $gutter || '' === $gutter ) {
			$gutter = '1.5rem';
		}
		$lines[] = sprintf( "\t--wp--preset--spacing--gutter: %s;", (string) $gutter );

		// Spacing scale: only override the theme's tuned scale when the user has
		// picked a NON-default density, so default sites render unchanged. The base
		// mirrors the era-contract spacing fallbacks; the density factor scales it.
		$density = isset( $presets['spacingDensity'] ) ? (string) $presets['spacingDensity'] : 'default';
		if ( 'default' !== $density ) {
			// Reuse the SSOT density multipliers so preset spacing can't diverge
			// from the --spectra-space-* tokens register_ui_tokens() scales.
			$factor    = TokenRegistry::get_spacing_multiplier( $density );
			$era_space = array(
				'xxx-small' => 0.25,
				'xx-small'  => 0.5,
				'x-small'   => 1.0,
				'small'     => 1.5,
				'medium'    => 2.0,
				'large'     => 3.0,
				'x-large'   => 4.0,
				'xx-large'  => 6.0,
			);
			foreach ( $era_space as $slug => $base ) {
				$lines[] = sprintf( "\t--wp--preset--spacing--%s: %srem;", sanitize_key( $slug ), $this->format_number( $base * $factor ) );
			}
		}

		// Font-size scale: only override the theme's tuned scale when the user has
		// picked a NON-regular type scale (the Style Guide "Size" control writes
		// typography.typeScale), so default sites render unchanged. Base values
		// mirror the era-contract font-size fallbacks; the factor multiplies them.
		$typography = isset( $config['typography'] ) && is_array( $config['typography'] ) ? $config['typography'] : array();
		$type_scale = ( isset( $typography['typeScale'] ) && is_string( $typography['typeScale'] ) ) ? $typography['typeScale'] : 'regular';
		if ( 'regular' !== $type_scale && 'default' !== $type_scale ) {
			$type_factors = array(
				'small' => 0.85,
				'large' => 1.2,
			);
			$tf           = isset( $type_factors[ $type_scale ] ) ? $type_factors[ $type_scale ] : 1.0;
			$tf_str       = $this->format_number( $tf );
			$era_type     = array(
				'x-small'    => 'clamp(12px, 1.5vw, 14px)',
				'small'      => 'clamp(14px, 1.6vw, 16px)',
				'medium'     => 'clamp(16px, 1.8vw, 18px)',
				'large'      => 'clamp(20px, 2.2vw, 22px)',
				'x-large'    => 'clamp(24px, 3vw, 28px)',
				'xx-large'   => 'clamp(28px, 3.6vw, 36px)',
				'xxx-large'  => 'clamp(32px, 3.6vw, 44px)',
				'xxxx-large' => 'clamp(40px, 6.6vw, 56px)',
			);
			foreach ( $era_type as $slug => $clamp ) {
				$lines[] = sprintf( "\t--wp--preset--font-size--%s: calc(%s * %s);", sanitize_key( $slug ), $clamp, $tf_str );
			}
		}

		// Font families: body + display from the typography pairing ($typography
		// resolved above).
		$body_stack = $this->build_font_stack( isset( $typography['body'] ) ? $typography['body'] : array() );
		$disp_stack = $this->build_font_stack( isset( $typography['heading'] ) ? $typography['heading'] : array() );
		if ( '' !== $body_stack ) {
			$lines[] = sprintf( "\t--wp--preset--font-family--body: %s;", $body_stack );
		}
		if ( '' !== $disp_stack ) {
			$lines[] = sprintf( "\t--wp--preset--font-family--display: %s;", $disp_stack );
		}

		// Note: $lines always has at least the unconditional gutter preset above.
		array_unshift( $lines, '', "\t/* Style Guide shadow / spacing / font-family presets */" );
		return implode( "\n", $lines );
	}

	/**
	 * Format a float as a compact CSS number (trim trailing zeros and point).
	 *
	 * @since 1.0.0
	 *
	 * @param float $n Number.
	 * @return string e.g. "1.5", "0.375", "9".
	 */
	private function format_number( float $n ): string {
		return rtrim( rtrim( number_format( $n, 4, '.', '' ), '0' ), '.' );
	}

	/**
	 * Build a CSS font-family stack from a typography entry, safely.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $entry Typography entry ({ name, slug }).
	 * @return string A sanitized `'Name', generic` stack, or '' when unavailable.
	 */
	private function build_font_stack( $entry ) {
		if ( ! is_array( $entry ) || empty( $entry['name'] ) ) {
			return '';
		}
		// Font names are proper nouns; strip anything that isn't a letter, digit,
		// space or hyphen to prevent CSS injection through the stored value.
		$name = trim( (string) preg_replace( '/[^A-Za-z0-9 \-]/', '', (string) $entry['name'] ) );
		if ( '' === $name ) {
			return '';
		}
		$slug    = isset( $entry['slug'] ) ? sanitize_key( (string) $entry['slug'] ) : '';
		$serifs  = array( 'playfair-display', 'cormorant-garamond', 'lora', 'dm-serif-display', 'fraunces' );
		$generic = in_array( $slug, $serifs, true ) ? 'serif' : 'sans-serif';
		return sprintf( "'%s', %s", $name, $generic );
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
		$tokens  = $this->engine->get_token_registry();
		$config  = $this->engine->get_config();
		$schemes = array();

		if ( null !== $tokens ) {
			$schemes = $tokens->get_schemes();
		}

		return array(
			'config'   => $config,
			'tokens'   => null !== $tokens ? $tokens->get_all() : array(),
			'schemes'  => $schemes,
			'palette'  => null !== $tokens ? $tokens->get_wp_palette() : array(),
			'nonce'    => wp_create_nonce( 'spectra_style_guide' ),
			'rest_url' => rest_url( 'spectra-blocks/v1/style-guide' ),
			'fontsUrl' => SPECTRA_BLOCKS_URL . 'assets/fonts/',
		);
	}

	/**
	 * Font file map for self-hosted fonts.
	 *
	 * Maps font slug to an array of font-face entries.
	 * Each entry: [ fontFamily, fontStyle, fontWeight, file (relative to assets/fonts/) ]
	 *
	 * Mirrors FONT_FACE_DATA in font-face-data.js.
	 *
	 * @since 1.0.0
	 * @var array<string, list<array{string, string, string, string}>>
	 */
	const FONT_MAP = array(
		'inter'              => array(
			array( 'Inter', 'normal', '400', 'inter/inter-latin-400-normal.woff2' ),
			array( 'Inter', 'normal', '500', 'inter/inter-latin-500-normal.woff2' ),
			array( 'Inter', 'normal', '600', 'inter/inter-latin-600-normal.woff2' ),
			array( 'Inter', 'normal', '700', 'inter/inter-latin-700-normal.woff2' ),
		),
		'space-grotesk'      => array(
			array( 'Space Grotesk', 'normal', '400', 'space-grotesk/space-grotesk-latin-400-normal.woff2' ),
			array( 'Space Grotesk', 'normal', '500', 'space-grotesk/space-grotesk-latin-500-normal.woff2' ),
			array( 'Space Grotesk', 'normal', '600', 'space-grotesk/space-grotesk-latin-600-normal.woff2' ),
			array( 'Space Grotesk', 'normal', '700', 'space-grotesk/space-grotesk-latin-700-normal.woff2' ),
		),
		'dm-sans'            => array(
			array( 'DM Sans', 'normal', '400', 'dm-sans/dm-sans-latin-400-normal.woff2' ),
			array( 'DM Sans', 'normal', '500', 'dm-sans/dm-sans-latin-500-normal.woff2' ),
			array( 'DM Sans', 'normal', '600', 'dm-sans/dm-sans-latin-600-normal.woff2' ),
			array( 'DM Sans', 'normal', '700', 'dm-sans/dm-sans-latin-700-normal.woff2' ),
		),
		'manrope'            => array(
			array( 'Manrope', 'normal', '400', 'manrope/manrope-latin-400-normal.woff2' ),
			array( 'Manrope', 'normal', '500', 'manrope/manrope-latin-500-normal.woff2' ),
			array( 'Manrope', 'normal', '600', 'manrope/manrope-latin-600-normal.woff2' ),
			array( 'Manrope', 'normal', '700', 'manrope/manrope-latin-700-normal.woff2' ),
			array( 'Manrope', 'normal', '800', 'manrope/manrope-latin-800-normal.woff2' ),
		),
		'plus-jakarta-sans'  => array(
			array( 'Plus Jakarta Sans', 'normal', '400', 'plus-jakarta-sans/plus-jakarta-sans-latin-400-normal.woff2' ),
			array( 'Plus Jakarta Sans', 'normal', '500', 'plus-jakarta-sans/plus-jakarta-sans-latin-500-normal.woff2' ),
			array( 'Plus Jakarta Sans', 'normal', '600', 'plus-jakarta-sans/plus-jakarta-sans-latin-600-normal.woff2' ),
			array( 'Plus Jakarta Sans', 'normal', '700', 'plus-jakarta-sans/plus-jakarta-sans-latin-700-normal.woff2' ),
			array( 'Plus Jakarta Sans', 'normal', '800', 'plus-jakarta-sans/plus-jakarta-sans-latin-800-normal.woff2' ),
		),
		'outfit'             => array(
			array( 'Outfit', 'normal', '400', 'outfit/outfit-latin-400-normal.woff2' ),
			array( 'Outfit', 'normal', '500', 'outfit/outfit-latin-500-normal.woff2' ),
			array( 'Outfit', 'normal', '600', 'outfit/outfit-latin-600-normal.woff2' ),
			array( 'Outfit', 'normal', '700', 'outfit/outfit-latin-700-normal.woff2' ),
			array( 'Outfit', 'normal', '800', 'outfit/outfit-latin-800-normal.woff2' ),
		),
		'sora'               => array(
			array( 'Sora', 'normal', '400', 'sora/sora-latin-400-normal.woff2' ),
			array( 'Sora', 'normal', '500', 'sora/sora-latin-500-normal.woff2' ),
			array( 'Sora', 'normal', '600', 'sora/sora-latin-600-normal.woff2' ),
			array( 'Sora', 'normal', '700', 'sora/sora-latin-700-normal.woff2' ),
		),
		'playfair-display'   => array(
			array( 'Playfair Display', 'normal', '400', 'playfair-display/playfair-display-latin-400-normal.woff2' ),
			array( 'Playfair Display', 'normal', '500', 'playfair-display/playfair-display-latin-500-normal.woff2' ),
			array( 'Playfair Display', 'normal', '700', 'playfair-display/playfair-display-latin-700-normal.woff2' ),
		),
		'cormorant-garamond' => array(
			array( 'Cormorant Garamond', 'normal', '400', 'cormorant-garamond/cormorant-garamond-latin-400-normal.woff2' ),
			array( 'Cormorant Garamond', 'normal', '500', 'cormorant-garamond/cormorant-garamond-latin-500-normal.woff2' ),
			array( 'Cormorant Garamond', 'normal', '600', 'cormorant-garamond/cormorant-garamond-latin-600-normal.woff2' ),
			array( 'Cormorant Garamond', 'normal', '700', 'cormorant-garamond/cormorant-garamond-latin-700-normal.woff2' ),
		),
		'lora'               => array(
			array( 'Lora', 'normal', '400', 'lora/lora-latin-400-normal.woff2' ),
			array( 'Lora', 'normal', '500', 'lora/lora-latin-500-normal.woff2' ),
			array( 'Lora', 'normal', '600', 'lora/lora-latin-600-normal.woff2' ),
			array( 'Lora', 'normal', '700', 'lora/lora-latin-700-normal.woff2' ),
		),
		'dm-serif-display'   => array(
			array( 'DM Serif Display', 'normal', '400', 'dm-serif-display/dm-serif-display-latin-400-normal.woff2' ),
		),
		'fraunces'           => array(
			array( 'Fraunces', 'normal', '400', 'fraunces/fraunces-latin-400-normal.woff2' ),
			array( 'Fraunces', 'normal', '500', 'fraunces/fraunces-latin-500-normal.woff2' ),
			array( 'Fraunces', 'normal', '600', 'fraunces/fraunces-latin-600-normal.woff2' ),
			array( 'Fraunces', 'normal', '700', 'fraunces/fraunces-latin-700-normal.woff2' ),
		),
		'rubik'              => array(
			array( 'Rubik', 'normal', '400', 'rubik/rubik-latin-400-normal.woff2' ),
			array( 'Rubik', 'normal', '500', 'rubik/rubik-latin-500-normal.woff2' ),
			array( 'Rubik', 'normal', '600', 'rubik/rubik-latin-600-normal.woff2' ),
			array( 'Rubik', 'normal', '700', 'rubik/rubik-latin-700-normal.woff2' ),
			array( 'Rubik', 'normal', '800', 'rubik/rubik-latin-800-normal.woff2' ),
		),
	);

	/**
	 * Get the font slugs currently active in the saved Global Styles.
	 *
	 * Reads the database-persisted Global Styles post to determine which
	 * font families are in use. Only slugs with bundled woff2 files (present
	 * in FONT_MAP) are returned.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] Font slugs (e.g. ['space-grotesk', 'dm-sans']).
	 */
	private function get_active_font_slugs(): array {
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
			return array();
		}

		$content = json_decode( $posts[0]->post_content, true );
		if ( ! is_array( $content ) ) {
			return array();
		}

		$font_families = $content['settings']['typography']['fontFamilies']['theme'] ?? array();
		$slugs         = array();

		foreach ( $font_families as $family ) {
			$slug = $family['slug'] ?? '';
			if ( $slug && isset( self::FONT_MAP[ $slug ] ) ) {
				$slugs[] = $slug;
			}
		}

		return array_unique( $slugs );
	}

	/**
	 * Generate @font-face CSS for the given font slugs.
	 *
	 * Used as a PHP-side safety net: WordPress auto-generates @font-face CSS
	 * from fontFace entries in the Global Styles entity, but only after those
	 * entries are saved. This fallback ensures fonts render on both the
	 * frontend and in the editor even before a re-save.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $slugs Font slugs to generate @font-face for.
	 * @return string @font-face CSS block, or empty string.
	 */
	private function get_font_face_css( array $slugs ): string {
		if ( empty( $slugs ) ) {
			return '';
		}

		// Normalize the fonts base URL to a protocol-relative URL so fetches
		// from the block-editor's `blob:` iframe canvas don't fail with a
		// cross-scheme block when the admin request and the front-end
		// request use different protocols (a common dev-environment
		// mismatch — admin under HTTPS, site under HTTP, or vice versa).
		// Protocol-relative URLs inherit the document's scheme, which
		// matches the iframe's effective origin and lets the woff2 fetch
		// proceed under the same-origin policy.
		$fonts_url = preg_replace( '#^https?:#', '', SPECTRA_BLOCKS_URL ) . 'assets/fonts/';
		$rules     = array();

		foreach ( $slugs as $slug ) {
			$faces = self::FONT_MAP[ $slug ] ?? array();
			foreach ( $faces as $face ) {
				list( $family, $style, $weight, $file ) = $face;
				$rules[]                                = sprintf(
					"@font-face {\n\tfont-family: '%s';\n\tfont-style: %s;\n\tfont-weight: %s;\n\tfont-display: swap;\n\tsrc: url('%s') format('woff2');\n}",
					esc_attr( $family ),
					esc_attr( $style ),
					esc_attr( $weight ),
					esc_url( $fonts_url . $file )
				);
			}
		}

		return implode( "\n", $rules );
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
			return;
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
	 * Inject scheme variable mapping CSS into the editor iframe.
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
	public function inject_scheme_editor_styles( $settings ) {
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

		// Inject scheme variable mapping CSS (data-spectra-scheme vars).
		$scheme_css = $tokens->get_scheme_css();
		if ( ! empty( $scheme_css ) ) {
			$settings['styles'][] = array( 'css' => $scheme_css );
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

	/**
	 * Auto-add .spectra-dark-scheme class to blocks with dark background schemes.
	 *
	 * When a block's rendered HTML contains data-spectra-scheme="..." pointing
	 * to a dark-background scheme, this filter injects the .spectra-dark-scheme
	 * class so that dark-mode overrides in scheme-override.css activate
	 * (inverted form inputs, checkboxes, tags, etc.).
	 *
	 * NOTE on isDark naming: isDark=true means light bg (dark text).
	 * isDark=false means dark bg (light text). The get_dark_scheme_keys()
	 * method handles this inversion internally.
	 *
	 * @since 3.2.0
	 *
	 * @param string               $block_content Rendered block HTML.
	 * @param array<string, mixed> $block         Parsed block data.
	 * @return string Modified HTML with dark scheme class where needed.
	 */
	public function maybe_add_dark_scheme_class( $block_content, $block ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Quick bail — skip regex for the 99% of blocks without scheme attributes.
		if ( false === strpos( $block_content, 'data-spectra-scheme' ) ) {
			return $block_content;
		}

		// Extract scheme key from the attribute.
		if ( ! preg_match( '/data-spectra-scheme="([^"]+)"/', $block_content, $matches ) ) {
			return $block_content;
		}

		$scheme_key = $matches[1];

		// Build dark keys lookup once per request (O(1) after first call).
		static $dark_keys = null;
		if ( null === $dark_keys ) {
			$tokens    = $this->engine->get_token_registry();
			$dark_keys = ( null !== $tokens ) ? $tokens->get_dark_scheme_keys() : array();
		}

		// Not a dark scheme — no class needed.
		if ( ! isset( $dark_keys[ $scheme_key ] ) ) {
			return $block_content;
		}

		// Already has the class — skip.
		if ( false !== strpos( $block_content, 'spectra-dark-scheme' ) ) {
			return $block_content;
		}

		// Inject .spectra-dark-scheme into the opening tag that contains the attribute.
		$block_content = preg_replace_callback(
			'/(<[^>]*data-spectra-scheme="' . preg_quote( $scheme_key, '/' ) . '"[^>]*>)/',
			function ( $m ) {
				$tag = $m[1];

				// Has a class attribute? Append to it.
				if ( preg_match( '/class="([^"]*)"/', $tag ) ) {
					return preg_replace( '/class="([^"]*)"/', 'class="$1 spectra-dark-scheme"', $tag, 1 );
				}

				// No class attribute? Add one before data-spectra-scheme.
				return str_replace( 'data-spectra-scheme=', 'class="spectra-dark-scheme" data-spectra-scheme=', $tag );
			},
			$block_content,
			1 // Only first occurrence.
		);

		return $block_content;
	}
}
