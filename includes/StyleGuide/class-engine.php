<?php
/**
 * Style Guide Engine — orchestrator that runs the color pipeline.
 *
 * Resolves the nine stored colours (plus the fixed status seeds) into the
 * TokenRegistry and connects to GlobalStylesBridge for WordPress integration.
 * Nothing is auto-generated: no shade ramps, schemes, or opacity tokens.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.0
 */

namespace SpectraBlocks\StyleGuide;

use SpectraBlocks\StyleGuide\Sync\SpectraOne\SpectraOneCompat;
use SpectraBlocks\StyleGuide\Sync\MappingResolver;
use SpectraBlocks\StyleGuide\Sync\ColorRoles;
use SpectraBlocks\StyleGuide\Sync\FseGlobalStylesAdapter;
use SpectraBlocks\StyleGuide\Sync\Astra\AstraPaletteAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Engine
 *
 * @since 1.0.0
 */
class Engine {

	/**
	 * WordPress option key for the Style Guide configuration.
	 *
	 * Intentionally retains the pro prefix for backward compatibility.
	 * Renaming this would silently reset all existing Pro site configurations.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_KEY = 'spectra_blocks_pro_style_guide';

	/**
	 * Cache group for computed tokens.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CACHE_GROUP = 'spectra_blocks';

	/**
	 * Cache key for computed tokens.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CACHE_KEY = 'spectra_style_guide_tokens';

	/**
	 * Style Guide shade token => its `sg-*` palette alias.
	 *
	 * The layout-independent half of the Astra slug rewrite: which alias stands for
	 * a given token. {@see self::astra_to_sg_slug()} composes it with the adapter's
	 * flag-aware slot map to get the Astra-slug => sg-slug table that
	 * {@see self::rewrite_astra_color_attrs()} applies.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const TOKEN_TO_SG_SLUG = array(
		'primary'   => 'primary',
		'secondary' => 'secondary',
		'neutral-7' => 'sg-heading',
		'neutral-5' => 'sg-body',
		'neutral-0' => 'sg-background',
		'neutral-1' => 'sg-surface',
		'neutral-2' => 'sg-border',
		'neutral-4' => 'sg-muted',
		'accent'    => 'sg-accent',
	);

	/**
	 * Astra palette slug => Style Guide `sg-*` alias slug, for the ACTIVE layout.
	 *
	 * Each Astra slot maps to the sg-* alias of the SAME Style Guide token the sync
	 * writes into that slot, so the rewritten colour always matches the slot's real
	 * value. The slot INDEX comes from {@see AstraPaletteAdapter::shade_map()} rather
	 * than a hardcoded table: Astra's background slots move with the 4.8.9 reorganize
	 * flag, so a fixed table rewrote legacy installs to the wrong colour (background
	 * and surface swapped, and the slot holding outline left unrewritten).
	 *
	 * Slots not covered by the sync (Subtle Background / Other Supporting) are
	 * absent, so `ast-global-color-7/8` stay unrewritten and resolve to Astra's OWN
	 * values instead of being force-mapped onto an unrelated Style Guide colour
	 * (which made distinct slots render alike).
	 *
	 * @since 1.0.5
	 *
	 * @return array<string, string> Astra slug => sg-* slug.
	 */
	public static function astra_to_sg_slug(): array {
		// Memoized: the caller is `rewrite_astra_color_attrs()` on `render_block_data`,
		// i.e. once per BLOCK, and building this map costs an Astra option read plus a
		// filter dispatch. This replaced a plain class constant, so without the memo a
		// content-heavy page pays that repeatedly for a value that cannot change —
		// Astra's slot layout is fixed by a stored compatibility flag that only moves
		// on a theme upgrade, never mid-request. {@see self::flush_caches()} clears it
		// so tests and any consumer that does change the flag can force a rebuild.
		if ( null !== self::$astra_to_sg_slug ) {
			return self::$astra_to_sg_slug;
		}

		$map = array();
		foreach ( AstraPaletteAdapter::shade_map() as $index => $token ) {
			if ( isset( self::TOKEN_TO_SG_SLUG[ $token ] ) ) {
				$map[ AstraPaletteAdapter::SLUG_PREFIX . $index ] = self::TOKEN_TO_SG_SLUG[ $token ];
			}
		}

		self::$astra_to_sg_slug = $map;
		return $map;
	}

	/**
	 * Memoized Astra slug => `sg-*` slug map ({@see self::astra_to_sg_slug()}), or
	 * null when not yet built. Static because the render-path caller is a filter on
	 * every block, not an instance method.
	 *
	 * @since 1.0.5
	 * @var array<string, string>|null
	 */
	private static $astra_to_sg_slug = null;

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var Engine|null
	 */
	private static $instance = null;

	/**
	 * Computed token registry.
	 *
	 * @since 1.0.0
	 * @var TokenRegistry|null
	 */
	private $token_registry = null;

	/**
	 * Global Styles Bridge.
	 *
	 * @since 1.0.0
	 * @var GlobalStylesBridge|null
	 */
	private $bridge = null;


	/**
	 * Get singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Engine
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the engine and all subsystems.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init(): void {
		$instance = self::get_instance();

		// Initialize GlobalStylesBridge (required in free).
		$instance->bridge = new GlobalStylesBridge( $instance );
		$instance->bridge->init();

		// Theme compatibility layers (only activate for matching themes).
		$spectra_one_compat = new SpectraOneCompat( $instance );
		$spectra_one_compat->init();

		// Global Styles backward-compatibility layer.
		$gs_compat = new GlobalStylesCompat( $instance );
		$gs_compat->init();

		// Register REST API routes.
		add_action( 'rest_api_init', array( $instance, 'register_rest_routes' ) );

		// Compute tokens on init (cached).
		add_action( 'init', array( $instance, 'maybe_compute' ), 20 );

		// Lock down the page-level Style Guide meta ({@see self::page_config()}).
		add_action( 'init', array( $instance, 'register_page_meta' ), 5 );
		add_filter( 'is_protected_meta', array( $instance, 'protect_page_meta' ), 10, 2 );

		// Invalidate caches when the design-system option changes. In option-only
		// mode no wp_global_styles post is saved, so WP's cached global-styles
		// stylesheet (and our token cache) would otherwise keep serving the old
		// palette until the next save — the front end/editor wouldn't reflect the
		// saved colours on reload.
		add_action( 'update_option_' . self::OPTION_KEY, array( $instance, 'flush_caches' ) );
		add_action( 'add_option_' . self::OPTION_KEY, array( $instance, 'flush_caches' ) );

		// Rewrite Astra color attribute slugs to sg-* at render time.
		// This ensures blocks with ast-global-color-N in textColor/backgroundColor
		// attributes resolve to the correct WP palette color on any theme.
		add_filter( 'render_block_data', array( $instance, 'rewrite_astra_color_attrs' ), 10, 1 );

		// Fix sg-* slug resolution in rendered HTML output.
		// The free plugin's BlockAttributes outputs raw "sg-heading" instead of
		// "var(--wp--preset--color--sg-heading)" in inline styles. This filter
		// does a string replacement on the final HTML to fix it.
		add_filter( 'render_block', array( $instance, 'fix_sg_slug_in_html' ), 10, 2 );

		// Swap hardcoded spacing values with token references on sg-* containers.
		// Runs at render time — saved content is never modified.
		add_filter( 'render_block_data', array( $instance, 'rewrite_spacing_tokens' ), 11, 1 );

		/**
		 * Fires after the Style Guide engine has been fully initialized.
		 *
		 * Pro and other extensions should hook here to layer additional
		 * functionality on top of the free engine.
		 *
		 * @since 1.0.0
		 *
		 * @param Engine $instance The initialized engine instance.
		 */
		do_action( 'spectra_style_guide_engine_loaded', $instance );
	}

	/**
	 * Whether the user has SAVED a Style Guide.
	 *
	 * `get_config()` falls back to the default palette (never empty), so the
	 * raw option is the only reliable signal of user intent. Colour/appearance
	 * overrides are gated on this so an untouched site is never restyled.
	 *
	 * Public so the sync layer (SyncOrchestrator, SpectraOneCompat, the
	 * GlobalStylesBridge injectors) can gate every outward theme write/override
	 * on the same signal — an unsaved site must never have its theme rewritten.
	 *
	 * @since 1.0.0
	 * @return bool True when a usable (v2 `colors`-shaped) Style Guide config is stored.
	 */
	public function has_saved_style_guide(): bool {
		$saved = get_option( self::OPTION_KEY, array() );

		// Require the v2 `colors` map — the same shape {@see get_stored_config()}
		// accepts. A legacy/malformed option must not count as saved: config reads
		// would fall back to theme-inherited defaults while the outward overrides
		// gated here fire anyway, re-entering the engine through the
		// wp_theme_json_data_theme filter.
		return is_array( $saved ) && isset( $saved['colors'] ) && is_array( $saved['colors'] ) && ! empty( $saved['colors'] );
	}

	/**
	 * Rewrite Astra global color slugs in block attributes at render time.
	 *
	 * Intercepts parsed block data before WordPress renders it and rewrites any
	 * "ast-global-color-N" palette slug references to their sg-* equivalents.
	 * This covers:
	 *   - Direct slug attributes: textColor, backgroundColor, borderColor
	 *   - Style object references: var:preset|color|ast-global-color-N
	 *
	 * No database changes are made. The filter is idempotent.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $parsed_block The parsed block data.
	 * @return array<string, mixed> Modified (or unchanged) parsed block data.
	 */
	public function rewrite_astra_color_attrs( $parsed_block ) {
		// Only remap the theme's Astra colour slugs onto the Style Guide palette
		// when a Style Guide has actually been SAVED. With nothing saved, leave the
		// theme's own colours untouched so a site the user never styled renders
		// exactly as its theme intends. Mirrors the variable-alias guards in
		// GlobalStylesBridge::get_astra_compat_css()/inject_astra_compat_editor_styles().
		if ( ! $this->has_saved_style_guide() ) {
			return $parsed_block;
		}

		if ( empty( $parsed_block['attrs'] ) ) {
			return $parsed_block;
		}

		/* @var array<string, mixed> $attrs */
		$attrs    = $parsed_block['attrs'];
		$modified = false;

		$astra_to_sg = self::astra_to_sg_slug();

		// Rewrite direct palette slug attributes.
		$slug_keys = array( 'textColor', 'backgroundColor', 'borderColor' );
		foreach ( $slug_keys as $key ) {
			if ( isset( $attrs[ $key ] ) && isset( $astra_to_sg[ $attrs[ $key ] ] ) ) {
				$attrs[ $key ] = $astra_to_sg[ $attrs[ $key ] ];
				$modified      = true;
			}
		}

		// Rewrite var:preset|color|ast-global-color-N inside the style object.
		if ( isset( $attrs['style'] ) && is_array( $attrs['style'] ) ) {
			$json = wp_json_encode( $attrs['style'] );

			if ( false !== $json ) {
				$original_json = $json;

				$search  = array();
				$replace = array();
				foreach ( $astra_to_sg as $astra_slug => $sg_slug ) {
					$search[]  = 'var:preset|color|' . $astra_slug;
					$replace[] = 'var:preset|color|' . $sg_slug;
				}

				$json = str_replace( $search, $replace, $json );

				if ( $json !== $original_json ) {
					$attrs['style'] = json_decode( $json, true );
					$modified       = true;
				}
			}
		}

		if ( $modified ) {
			$parsed_block['attrs'] = $attrs;
		}

		return $parsed_block;
	}

	/**
	 * Fix sg-* slug resolution in rendered block HTML.
	 *
	 * The free plugin's BlockAttributes outputs raw sg-* slugs as CSS values
	 * in inline styles (e.g. "--spectra-text-color: sg-heading" instead of
	 * "--spectra-text-color: var(--wp--preset--color--sg-heading)").
	 *
	 * This filter runs on the final rendered HTML and converts raw sg-*
	 * values to proper var() references. Combined with get_sg_preset_css()
	 * which ensures --wp--preset--color--sg-* vars exist, this gives
	 * complete sg-* slug support without modifying the free plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $block_content Rendered block HTML.
	 * @param array<string, mixed> $block         Parsed block data.
	 * @return string Modified HTML with fixed sg-* references.
	 */
	public function fix_sg_slug_in_html( $block_content, $block ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Cheap bail: a bare managed slug can only ride on a Spectra CSS var.
		if ( false === strpos( $block_content, '--spectra-' ) ) {
			return $block_content;
		}

		// Convert every bare MANAGED slug in a Spectra colour var to its WP preset
		// reference, e.g. `--spectra-text-color: primary` → `... var(--wp--preset--color--primary)`.
		// Previously this only handled `sg-*`, but the Astra-slug rewrite
		// (rewrite_astra_color_attrs) maps slots 0/1 to the semantic slugs
		// `primary`/`secondary`. Left bare, `color: var(--spectra-text-color)`
		// resolved to `color: primary` (invalid) and the colour silently dropped on
		// the FRONT END — while the editor, which never runs this render filter,
		// kept the working `ast-global-color-*` value.
		//
		// The slug set is the FULL managed palette: the semantic_map keys (a code
		// constant) PLUS the user's `custom_colors`. Without Pro none of these are
		// registered WP palette slugs, so convert_wordpress_preset() leaves them
		// bare and this is the only place they get resolved. The value alternation
		// is the gate — a real CSS keyword like `red` is not a managed slug, so it
		// is never rewritten.
		static $pattern = null;
		if ( null === $pattern ) {
			$config = $this->get_config();
			$slugs  = array_keys( ColorModel::semantic_map() );
			if ( isset( $config['custom_colors'] ) && is_array( $config['custom_colors'] ) ) {
				$slugs = array_merge( $slugs, array_keys( $config['custom_colors'] ) );
			}
			$slugs = array_values( array_unique( array_filter( $slugs, 'is_string' ) ) );
			// Longest-first so a slug can't be shadowed by a shorter one that is its prefix.
			usort(
				$slugs,
				static function ( $a, $b ) {
					return strlen( $b ) - strlen( $a );
				}
			);
			$alt     = implode( '|', array_map( static fn( $s ) => preg_quote( $s, '/' ), $slugs ) );
			$pattern = '/(--spectra-[a-z-]+(?:-color)?)\s*:\s*(' . $alt . ')\s*(;|")/';
		}

		return preg_replace(
			$pattern,
			'$1: var(--wp--preset--color--$2)$3',
			$block_content
		) ?? $block_content;
	}

	/**
	 * Flush computed-token + WordPress theme.json caches.
	 *
	 * Called when the Style Guide option changes. Drops our token cache and, in
	 * option-only mode, invalidates WordPress's cached global-styles stylesheet
	 * (normally cleared by a wp_global_styles post save, which option-only mode
	 * no longer performs) so the new palette renders on the next request.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function flush_caches(): void {
		$this->token_registry   = null;
		self::$astra_to_sg_slug = null;
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );

		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
		}
	}

	/**
	 * Compute tokens if not already cached.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function maybe_compute(): void {
		if ( null !== $this->token_registry ) {
			return;
		}

		// Try cache first.
		$cached = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );

		if ( false !== $cached && $cached instanceof TokenRegistry ) {
			$this->token_registry = $cached;
			return;
		}

		$this->compute();
	}

	/**
	 * Run the full color pipeline: shades → APCA → schemes → tokens.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed>|null $config  Config to compute from. Defaults to the saved config.
	 * @param bool                     $persist Whether to cache the result (false for previews).
	 * @return void
	 */
	public function compute( $config = null, bool $persist = true ): void {
		$config = ( is_array( $config ) && ! empty( $config ) ) ? $config : $this->get_config();
		$tokens = new TokenRegistry();

		// The nine stored colours are the source of truth, and the ONLY colours the
		// pipeline emits (plus the fixed status seeds). Nothing is auto-generated:
		// no shade ramps, no interpolated neutrals, no opacity tokens, no schemes —
		// every token below corresponds to a colour the Style Guide UI exposes.
		$colors     = $this->colors_from_config( $config );
		$chromatics = $this->chromatics_from_colors( $colors );

		// Neutral stops — the six stored neutral roles at their ramp positions
		// (0/1/2/4/5/7; stops 3 and 6 are no longer generated).
		foreach ( $this->compute_neutral_shades( $config ) as $index => $hex ) {
			$tokens->set( "neutral-{$index}", $hex );
		}

		// Chromatic seeds — one token per colour, keyed by its SEMANTIC slug
		// (brand 1-3 = primary/secondary/accent, status 4-7 = success/error/info/
		// warning). Emitted as `--spectra-<slug>`; the legacy `chromaticN-7` names
		// are gone.
		foreach ( $chromatics as $c_index => $chromatic ) {
			if ( empty( $chromatic['hex'] ) ) {
				continue;
			}

			$hex = sanitize_hex_color( $chromatic['hex'] );
			if ( ! $hex ) {
				continue;
			}

			$slug = ColorModel::chromatic_token( $c_index );
			if ( '' === $slug ) {
				continue;
			}

			$tokens->set( $slug, $hex );
		}

		// Standalone roles — stored colours with no ramp position (foreground), so the
		// hex is set straight onto the role's own token.
		foreach ( ColorModel::standalone_map() as $slug => $token ) {
			$hex = sanitize_hex_color( $colors[ $slug ] ?? '' );
			if ( $hex ) {
				$tokens->set( $token, $hex );
			}
		}

		// Constants.
		$tokens->set( 'white', '#ffffff' );
		$tokens->set( 'transparent', 'transparent' );

		// Cache the result.
		$this->token_registry = $tokens;

		// Preview computes (persist = false) must not pollute the cached tokens
		// used by the front-end / editor render path.
		if ( $persist ) {
			wp_cache_set( self::CACHE_KEY, $tokens, self::CACHE_GROUP );
		}
	}

	/**
	 * The neutral stops for a config — the six stored neutral roles placed at
	 * their ramp positions (0/1/2/4/5/7). No interpolation: stops 3 and 6 are
	 * not generated.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, mixed> $config Config array.
	 * @return array<int, string> stop => hex.
	 */
	private function compute_neutral_shades( array $config ): array {
		$colors = $this->colors_from_config( $config );
		$stops  = array();
		foreach ( ColorModel::neutral_anchor_map() as $stop => $slug ) {
			$stops[ $stop ] = $colors[ $slug ];
		}
		return $stops;
	}

	/**
	 * Resolve the nine v2 colours from a config, filling any missing slug from the
	 * defaults so compute() always has a complete palette.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, mixed> $config Config array.
	 * @return array<string, string> slug => hex for all nine core roles.
	 */
	private function colors_from_config( array $config ): array {
		$stored   = isset( $config['colors'] ) && is_array( $config['colors'] ) ? $config['colors'] : array();
		$defaults = $this->default_colors();
		$out      = array();
		foreach ( ColorModel::core_slugs() as $slug ) {
			// Guard rather than cast — a page config comes straight from post meta.
			$raw          = $stored[ $slug ] ?? null;
			$hex          = is_string( $raw ) ? sanitize_hex_color( $raw ) : '';
			$out[ $slug ] = $hex ? $hex : $defaults[ $slug ];
		}
		return $out;
	}

	/**
	 * Build the internal index-keyed chromatic map the shade pipeline expects:
	 * brand chromatics 1-3 from the stored colours + fixed status chromatics 4-7.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, string> $colors slug => hex (nine core roles).
	 * @return array<int, array{hex: string, name: string}> index => chromatic.
	 */
	private function chromatics_from_colors( array $colors ): array {
		$chromatics = array();
		foreach ( ColorModel::brand_chromatic_map() as $slug => $index ) {
			$chromatics[ $index ] = array(
				'hex'  => $colors[ $slug ],
				'name' => ucfirst( $slug ),
			);
		}
		foreach ( ColorModel::STATUS_COLORS as $slug => $def ) {
			$chromatics[ $def['chromatic'] ] = array(
				'hex'  => $def['hex'],
				'name' => ucfirst( $slug ),
			);
		}
		return $chromatics;
	}

	/**
	 * The default nine colours for a fresh install — delegates to
	 * {@see ColorModel::default_colors()}, the single PHP source for every colour
	 * default (brand, neutral and status).
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, string> slug => hex (nine core roles).
	 */
	private function default_colors(): array {
		return ColorModel::default_colors();
	}

	/**
	 * The unsaved-state palette: the literal defaults, with each mapped role
	 * overwritten by the ACTIVE THEME's colour, so a site that never saved a
	 * Style Guide inherits its theme's palette instead of showing Spectra's
	 * hardcoded defaults.
	 *
	 * Only roles the per-theme mapping registers AND the theme actually defines
	 * are inherited; every other role keeps its {@see ColorModel::default_colors()}
	 * literal (no extra fallback). Reuses the same role → theme-slug → hex chain the
	 * reverse sync uses ({@see Sync\SyncOrchestrator::pull_from_theme()}), run once
	 * to seed defaults rather than to persist.
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, string> slug => hex for all nine core roles.
	 */
	private function inherited_default_colors(): array {
		$colors = ColorModel::default_colors();

		// Re-entrancy guard: the FSE adapter read below calls
		// WP_Theme_JSON_Resolver::get_theme_data(), which fires the
		// wp_theme_json_data_theme filter before the resolver's static cache
		// exists — and our own callbacks on that filter (inject_palette et al.)
		// re-enter the engine, landing back here in an infinite loop. Answer the
		// nested call with the literal defaults instead.
		static $reading = false;
		if ( $reading ) {
			return $colors;
		}
		$reading = true;

		// Read the active theme's palette from whichever store adapter supports it
		// (FSE global styles on block themes, Astra settings when Astra is active).
		$astra   = new AstraPaletteAdapter();
		$by_slug = array();
		foreach ( array( new FseGlobalStylesAdapter(), $astra ) as $adapter ) {
			if ( $adapter->is_supported() ) {
				$by_slug += $adapter->read();
			}
		}

		$reading = false;

		if ( empty( $by_slug ) ) {
			return $colors;
		}

		// Astra: resolve through the ADAPTER's own slot map, not the curated
		// MappingResolver profile. The profile cannot serve here for two reasons.
		// First, it registers only the two brand slots, so the five neutral roles
		// (heading/body/background/surface/outline) would stay on Spectra's literals
		// — and the user's FIRST save would then push those literals over Astra's own
		// headings/text/backgrounds. Second, its slugs are fixed indices, while
		// Astra's background slots move with the 4.8.9 reorganize flag, so a legacy
		// install would inherit the wrong slots.
		// {@see AstraPaletteAdapter::shade_map()} is the same source of truth the
		// push uses, so what the Style Guide inherits is exactly what it writes back:
		// leave a colour untouched in the editor and its save is a no-op for Astra.
		//
		// Runs BEFORE the generic pass so the mapping stays overridable: the resolved
		// role map is filterable ({@see MappingResolver::for_active_theme()}, the
		// documented extension point for the stored/manual tier and third parties),
		// and applying this after it would silently beat any Astra rows a filter set.
		// The curated profile's own two brand rows resolve to these same slots, so the
		// generic pass simply re-affirms them when nothing filters the mapping.
		if ( $astra->is_supported() ) {
			foreach ( $astra->reverse_map() as $index => $token ) {
				$slug = AstraPaletteAdapter::SLUG_PREFIX . $index;
				if ( ! isset( $by_slug[ $slug ] ) ) {
					continue;
				}
				$core = ColorModel::slug_for_token( (string) $token );
				if ( null === $core ) {
					continue;
				}
				$hex = sanitize_hex_color( (string) $by_slug[ $slug ] );
				if ( $hex ) {
					$colors[ $core ] = $hex;
				}
			}
		}

		// Land each registered role's theme colour on the core slug it owns — only
		// when the theme actually defines that slug.
		$mapping = MappingResolver::for_active_theme();
		foreach ( $mapping->mapped_roles() as $role ) {
			$slug  = $mapping->slug_for( $role );
			$token = ColorRoles::SG_TOKEN[ $role ] ?? null;
			if ( null === $slug || null === $token || ! isset( $by_slug[ $slug ] ) ) {
				continue;
			}
			$core = ColorModel::slug_for_token( $token );
			if ( null === $core ) {
				continue;
			}
			$hex = sanitize_hex_color( (string) $by_slug[ $slug ] );
			if ( $hex ) {
				$colors[ $core ] = $hex;
			}
		}

		return $colors;
	}

	/**
	 * Get the computed token registry.
	 *
	 * @since 1.0.0
	 *
	 * @return TokenRegistry|null
	 */
	public function get_token_registry() {
		return $this->token_registry;
	}

	/**
	 * Get the Global Styles Bridge instance.
	 *
	 * @since 1.0.0
	 *
	 * @return GlobalStylesBridge|null
	 */
	public function get_bridge() {
		return $this->bridge;
	}

	/**
	 * The colour-palette slugs the Style Guide owns.
	 *
	 * The managed set is the shade palette ({@see TokenRegistry::get_wp_palette})
	 * plus every `semantic_map` slug that resolves to a shade value — the exact
	 * set that {@see GlobalStylesBridge::maybe_override_managed_user_palette}
	 * enforces on the theme.json `:root` layer. Consumers use it to defer to the
	 * Style Guide for these `--wp--preset--color--<slug>` keys (e.g. dropping an
	 * import's `presetLock` entries so the Style Guide palette wins).
	 *
	 * @since 1.0.0
	 *
	 * @return string[] Slugs without the `--wp--preset--color--` prefix.
	 */
	public function get_managed_color_slugs(): array {
		$this->maybe_compute();
		$tokens = $this->get_token_registry();
		if ( null === $tokens ) {
			return array();
		}

		$slugs = array();
		foreach ( $tokens->get_wp_palette() as $entry ) {
			// 'slug' is always present per get_wp_palette()'s return shape.
			$slugs[] = $entry['slug'];
		}

		$config = $this->get_config();
		if ( isset( $config['semantic_map'] ) && is_array( $config['semantic_map'] ) ) {
			foreach ( $config['semantic_map'] as $semantic_slug => $shade_key ) {
				// Only claim a semantic slug the Style Guide can actually resolve,
				// so an unresolvable mapping is left to whatever else defines it.
				if ( null !== $tokens->get( $shade_key ) ) {
					$slugs[] = $semantic_slug;
				}
			}
		}

		// Explicit per-slug overrides are Style-Guide-owned regardless of the map:
		// they pin an exact hex, so the Style Guide always resolves them. Claim every
		// override slug — including import-pinned slugs absent from semantic_map —
		// so their presetLock entries are dropped and the Style Guide value wins.
		if ( isset( $config['semantic_overrides'] ) && is_array( $config['semantic_overrides'] ) ) {
			foreach ( array_keys( $config['semantic_overrides'] ) as $override_slug ) {
				if ( is_string( $override_slug ) && '' !== $override_slug ) {
					$slugs[] = $override_slug;
				}
			}
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Get the stored Style Guide configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>|null $config A v2 config to resolve instead of the
	 *                                          stored one (see {@see self::page_config()}).
	 * @return array<string, mixed> Configuration array.
	 */
	public function get_config( ?array $config = null ) {
		$config = $config ?? $this->get_stored_config();

		// Transitional compatibility: expose DERIVED legacy keys so readers not yet
		// migrated to ColorModel (the theme sync adapters, the bridge's semantic
		// resolution, theme-style-compat) keep working. Derived from the v2 colours on
		// every read and NEVER persisted ({@see save_config} strips them).
		$colors                 = $this->colors_from_config( $config );
		$custom_colors          = ( isset( $config['custom_colors'] ) && is_array( $config['custom_colors'] ) ) ? $config['custom_colors'] : array();
		$config['semantic_map'] = ColorModel::semantic_map();
		$config['chromatics']   = $this->chromatics_from_colors( $colors );
		// The extended "More color variables" (foreground / surface-2 / overlay) are
		// DERIVED from the palette by client-side colour math in the editor; compute
		// the same defaults here so they resolve identically server-side when NOT
		// pinned (i.e. after a reset). A pinned custom_colors entry still wins — it is
		// merged last.
		$config['semantic_overrides'] = array_merge(
			$this->derived_var_defaults( $colors ),
			$this->custom_colors_as_overrides( $custom_colors )
		);

		return $config;
	}

	/**
	 * The raw stored v2 config (canonical keys only), or the v2 default when the
	 * stored option is absent or not v2-shaped. No derived/legacy keys.
	 *
	 * Accepts the stored config only when it has a `colors` map. Anything else — an
	 * empty option or a legacy v1 config — falls back to the default. No migration.
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, mixed> Canonical v2 config.
	 */
	private function get_stored_config(): array {
		$config = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $config ) || ! isset( $config['colors'] ) || ! is_array( $config['colors'] ) ) {
			return $this->get_default_config();
		}

		if ( ! isset( $config['custom_colors'] ) || ! is_array( $config['custom_colors'] ) ) {
			$config['custom_colors'] = array();
		}

		// The Style Guide is colours-only. Drop any legacy `presets` (UI styling) or
		// `typography` keys a pre-existing stored config may still carry, so they never
		// flow into the pipeline. Nothing consumes them anymore.
		unset( $config['presets'], $config['typography'] );

		$config['version'] = 2;

		return $config;
	}

	/**
	 * A PAGE's own Style Guide, when it has one — otherwise null.
	 *
	 * Post meta under the SAME key and shape as the site option (the convention the
	 * GBS store already uses), so this stays the ONLY branch: everything downstream
	 * works on a page palette unchanged. A standalone HTML import parks its palette
	 * here rather than repainting the site.
	 *
	 * Front end only — editing surfaces resolve the SITE guide.
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, mixed>|null
	 */
	private function page_config(): ?array {
		// The SAME allow-list `register_page_meta()` registers against and
		// `check_page_scope()` writes through. Reading a wider set than those cover
		// would trust a row that never passed the sanitize or auth callback.
		if ( ! is_singular( array( 'post', 'page' ) ) ) {
			return null;
		}
		$stored = get_post_meta( get_queried_object_id(), self::OPTION_KEY, true );
		return is_array( $stored ) && ! empty( $stored['colors'] ) ? $stored : null;
	}

	/**
	 * The queried page's own palette as sanitized `slug => hex`, or an empty array
	 * when it has none. Resolved from the config alone — NEVER the token registry,
	 * which is cached site-wide, so a page palette there would leak onto every other
	 * page under a persistent object cache.
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, string> slug => hex.
	 */
	private function page_preset_map(): array {
		$page = $this->page_config();
		if ( null === $page ) {
			return array();
		}

		$config = $this->get_config( $page );
		$colors = $this->colors_from_config( $config );

		// Same resolution as GlobalStylesBridge::get_sg_preset_css(): each semantic
		// slug through its shade token, then the overrides on top.
		$map = array();
		foreach ( ColorModel::semantic_map() as $slug => $token ) {
			$core = ColorModel::slug_for_token( $token );
			if ( null !== $core ) {
				$map[ $slug ] = $colors[ $core ];
			} elseif ( isset( ColorModel::STATUS_COLORS[ $token ] ) ) {
				$map[ $slug ] = ColorModel::STATUS_COLORS[ $token ]['hex'];
			}
		}
		if ( isset( $config['semantic_overrides'] ) && is_array( $config['semantic_overrides'] ) ) {
			$map = array_merge( $map, $config['semantic_overrides'] );
		}

		$out = array();
		foreach ( $map as $slug => $hex ) {
			// Guard rather than cast: this resolves post meta, and a non-string there
			// would raise an `Array to string conversion` warning — or, for an
			// unserialized object, a fatal — from inside `wp_head`.
			if ( ! is_string( $hex ) ) {
				continue;
			}
			$clean_slug = sanitize_key( (string) $slug );
			$clean_hex  = sanitize_hex_color( $hex );
			if ( '' !== $clean_slug && $clean_hex ) {
				$out[ $clean_slug ] = $clean_hex;
			}
		}

		return $out;
	}

	/**
	 * The `--wp--preset--color--*` block for the queried page's own Style Guide, or
	 * '' when it has none.
	 *
	 * @since 1.0.4
	 *
	 * @return string CSS, or ''.
	 */
	public function page_preset_css(): string {
		$decls = array();
		foreach ( $this->page_preset_map() as $slug => $hex ) {
			$decls[] = '--wp--preset--color--' . $slug . ':' . $hex;
		}

		return empty( $decls ) ? '' : ':root{' . implode( ';', $decls ) . '}';
	}

	/**
	 * The palette slugs the queried page's OWN Style Guide resolves — empty when it
	 * has none.
	 *
	 * The page-scoped GBS `presetLock` must defer to these the same way it defers to
	 * the site guide's, and it must do so even on a site whose guide was never saved:
	 * the lock renders on `body` and would otherwise beat the `:root` block
	 * {@see GlobalStylesBridge::print_page_palette()} emits for this page.
	 *
	 * @since 1.0.4
	 *
	 * @return string[] Slugs without the `--wp--preset--color--` prefix.
	 */
	public function page_managed_color_slugs(): array {
		return array_keys( $this->page_preset_map() );
	}

	/**
	 * Register the page-level Style Guide meta.
	 *
	 * The key deliberately matches {@see self::OPTION_KEY} — the page meta holds the
	 * identical v2 shape, and the import pipeline writes it under that name. Since it
	 * therefore carries no `_` prefix, {@see self::protect_page_meta()} marks it
	 * protected so the classic Custom Fields box neither lists nor writes it.
	 *
	 * @since 1.0.4
	 * @return void
	 */
	public function register_page_meta(): void {
		foreach ( array( 'post', 'page' ) as $post_type ) {
			register_post_meta(
				$post_type,
				self::OPTION_KEY,
				array(
					'type'              => 'object',
					'single'            => true,
					// Never writable over the posts REST API. The ONLY writer is
					// `style-guide/config`, which gates on `edit_theme_options` PLUS
					// `edit_post` and sanitizes every hex.
					'show_in_rest'      => false,
					'sanitize_callback' => array( $this, 'sanitize_page_meta' ),
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', (int) $post_id );
					},
				)
			);
		}
	}

	/**
	 * Hide the page-level Style Guide meta from the Custom Fields UI.
	 *
	 * @since 1.0.4
	 *
	 * @param bool   $is_protected Whether the key is protected.
	 * @param string $meta_key     Meta key.
	 * @return bool
	 */
	public function protect_page_meta( $is_protected, $meta_key ) {
		return self::OPTION_KEY === $meta_key ? true : $is_protected;
	}

	/**
	 * Sanitize a page-level Style Guide before it is stored.
	 *
	 * @since 1.0.4
	 *
	 * @param mixed $value Incoming meta value.
	 * @return array<string, mixed> Canonical, sanitized v2 config.
	 */
	public function sanitize_page_meta( $value ): array {
		return $this->canonical_config( is_array( $value ) ? $value : array() );
	}

	/**
	 * Map the `custom_colors` layer ({slug: {hex, name}}) to the slug => hex shape the
	 * transitional `semantic_overrides` readers expect. A `custom_colors` slug that
	 * matches a generated preset slug thereby overrides it (old semantic_overrides
	 * behaviour); a new slug adds a colour.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, mixed> $custom_colors Custom colours.
	 * @return array<string, string> slug => hex.
	 */
	private function custom_colors_as_overrides( array $custom_colors ): array {
		$out = array();
		foreach ( $custom_colors as $slug => $def ) {
			if ( ! is_string( $slug ) ) {
				continue;
			}
			$raw = is_array( $def ) && isset( $def['hex'] ) ? $def['hex'] : $def;
			if ( ! is_string( $raw ) ) {
				continue;
			}
			$hex = sanitize_hex_color( $raw );
			if ( '' !== $slug && $hex ) {
				$out[ $slug ] = $hex;
			}
		}
		return $out;
	}

	/**
	 * Default values for the extended "More color variables", derived from the
	 * palette with the SAME formulas the editor's `derivedAuto` uses (see the Pro
	 * plugin's `ColorsSection`/`colorMath.js`). Kept in sync so a variable that is
	 * not pinned in `custom_colors` renders identically in the editor preview and
	 * on the front end.
	 *
	 *  - foreground : white when white meets 4.5:1 on Primary, else the heading colour.
	 *  - surface-2  : Primary mixed 90% toward the background.
	 *  - overlay    : heading mixed 20% toward black.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, string> $colors The nine core role colours (slug => hex).
	 * @return array<string, string> slug => hex.
	 */
	private function derived_var_defaults( array $colors ): array {
		$primary    = ColorMath::normalize( (string) ( $colors['primary'] ?? '' ), '#6431f6' );
		$background = ColorMath::normalize( (string) ( $colors['background'] ?? '' ), '#ffffff' );
		$heading    = ColorMath::normalize( (string) ( $colors['heading'] ?? '' ), '#111111' );

		// `foreground` is NOT listed here any more. It is a stored core role, so its
		// value comes from `colors['foreground']` via the semantic map. Leaving it in
		// this layer made the override win over the map on every read — including on
		// themes that ship their own `foreground` swatch, which a Style Guide save
		// then silently repainted. The contrast formula it used lives on as the
		// editor's AUTO/Reset derivation for the role.
		return array(
			'surface-2' => ColorMath::mix( $primary, $background, 0.9 ),
			'overlay'   => ColorMath::mix( $heading, '#000000', 0.2 ),
		);
	}

	/**
	 * Reduce a config to the canonical v2 keys, dropping the derived/legacy ones
	 * get_config() adds. Shared by the site option and the page meta so both hold
	 * the identical shape.
	 *
	 * Sanitizes as well as shapes: this is the one gate every stored config passes
	 * through, so a slug or hex that reaches `wp_head` can only ever be one that
	 * `sanitize_key()`/`sanitize_hex_color()` accepted.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, mixed> $config Configuration array.
	 * @return array<string, mixed> Canonical v2 config.
	 */
	private function canonical_config( array $config ): array {
		$raw_colors = isset( $config['colors'] ) && is_array( $config['colors'] ) ? $config['colors'] : array();
		$colors     = array();
		foreach ( $raw_colors as $slug => $hex ) {
			$clean_slug = sanitize_key( (string) $slug );
			$clean_hex  = is_string( $hex ) ? sanitize_hex_color( $hex ) : null;
			if ( '' !== $clean_slug && $clean_hex ) {
				$colors[ $clean_slug ] = $clean_hex;
			}
		}

		return array(
			'version'       => 2,
			'colors'        => $colors,
			'custom_colors' => $this->sanitize_custom_colors(
				isset( $config['custom_colors'] ) && is_array( $config['custom_colors'] ) ? $config['custom_colors'] : array()
			),
		);
	}

	/**
	 * Sanitize the `custom_colors` layer to `slug => { hex, name }`.
	 *
	 * Hex required, name optional, malformed entries dropped. Shared by the REST
	 * merge and {@see self::canonical_config()} so a colour is sanitized identically
	 * whether it arrives over the route or straight through `update_post_meta()`.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string|int, mixed> $custom_colors Raw custom colours.
	 * @return array<string, array<string, string>> slug => { hex, name }.
	 */
	private function sanitize_custom_colors( array $custom_colors ): array {
		$out = array();
		foreach ( $custom_colors as $slug => $def ) {
			$clean_slug = sanitize_key( (string) $slug );
			$raw_hex    = is_array( $def ) && isset( $def['hex'] ) ? $def['hex'] : $def;
			$hex        = is_string( $raw_hex ) ? sanitize_hex_color( $raw_hex ) : null;
			if ( '' === $clean_slug || ! $hex ) {
				continue;
			}

			$entry = array( 'hex' => $hex );
			if ( is_array( $def ) && isset( $def['name'] ) && is_string( $def['name'] ) ) {
				$name = sanitize_text_field( $def['name'] );
				if ( '' !== $name ) {
					$entry['name'] = $name;
				}
			}
			$out[ $clean_slug ] = $entry;
		}

		return $out;
	}

	/**
	 * Save the Style Guide configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config Configuration array.
	 * @return bool True on success.
	 */
	public function save_config( $config ) {
		// Invalidate cache.
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
		$this->token_registry = null;

		$clean = $this->canonical_config( $config );

		$result = update_option( self::OPTION_KEY, $clean, false );

		/**
		 * Fires after the Style Guide configuration has been saved.
		 *
		 * Downstream systems (e.g., Global Styles ClassRegistry) listen to
		 * invalidate derived caches so their dynamic tokens (colors, spacing,
		 * fonts) recompute against the new config.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $config The saved configuration (v2 canonical +
		 *                                      derived legacy keys, freshly read).
		 */
		do_action( 'spectra_style_guide_config_saved', $this->get_config() );

		return $result;
	}

	/**
	 * Get the default Style Guide configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Default config.
	 */
	public function get_default_config() {
		// v2 default — the nine colours + empty custom_colors. The colours INHERIT the
		// active theme's palette (for the roles the mapper registers), falling back to
		// the frozen literals for everything else, so an unsaved site's editor opens on
		// the theme's own colours. The slug→token semantic map is a code constant
		// ({@see ColorModel}), not stored. Typography and UI-styling presets were removed,
		// so the Style Guide is colours-only.
		return array(
			'version'       => 2,
			'colors'        => $this->inherited_default_colors(),
			'custom_colors' => array(),
		);
	}

	/**
	 * The nine core colours currently in effect (slug => hex).
	 *
	 * Reads the canonical stored config — or the theme-inherited default on an
	 * unsaved site — so this is exactly what a save would round-trip and what the
	 * front end renders. Never empty. Programmatic accessor for the
	 * `spectra-blocks/get-active-colors` ability.
	 *
	 * @since 1.0.6
	 *
	 * @return array<string, string> slug => hex (nine core roles).
	 */
	public function active_colors(): array {
		return $this->colors_from_config( $this->get_stored_config() );
	}

	/**
	 * Ready-to-inject live-preview CSS for a (partial) colour map, WITHOUT
	 * persisting. Merges the map over the current palette, computes tokens with
	 * `persist = false`, builds the two-layer preview CSS (identical to
	 * {@see self::rest_preview}'s `css_full`), and restores the live token
	 * registry so a later reader in the same request sees the site's own tokens.
	 *
	 * @since 1.0.6
	 *
	 * @param array<string, string> $colors Partial slug => hex map (nine core roles).
	 * @return string Combined preview CSS, or '' when tokens are unavailable.
	 */
	public function preview_css_for( array $colors ): string {
		$registry = $this->token_registry;

		$config = $this->build_config_from_request( array( 'colors' => $colors ), new \WP_REST_Request() );
		$this->compute( $config, false );

		$css = $this->build_preview_css( ColorModel::semantic_map(), $this->preview_overrides( $config ) );

		$this->token_registry = $registry;

		return $css;
	}

	/**
	 * Apply a (partial) colour map to the SITE Style Guide and recompute — the
	 * same merge/persist/compute pipeline as a site-scoped POST /style-guide/config.
	 * A partial `$colors` map merges over the current palette; unknown slugs are
	 * ignored. An explicit `$custom_colors` map FULL-REPLACES the custom layer
	 * (pass an empty array to clear it, null to leave it untouched) — the same
	 * contract the REST route uses. Returns the resulting core colours plus
	 * ready-to-inject preview CSS. Programmatic writer for the
	 * `spectra-blocks/set-active-colors` ability.
	 *
	 * @since 1.0.6
	 *
	 * @param array<string, string>                  $colors        Partial slug => hex map (core roles).
	 * @param array<string, array{hex: string}>|null $custom_colors Optional full-replace custom layer.
	 * @return array{colors: array<string, string>, preview_css: string}
	 */
	public function apply_colors( array $colors, ?array $custom_colors = null ): array {
		$body = array( 'colors' => $colors );
		if ( null !== $custom_colors ) {
			$body['custom_colors'] = $custom_colors;
		}

		$config = $this->build_config_from_request( $body, new \WP_REST_Request() );
		$this->save_config( $config );
		$this->compute();

		return array(
			'colors'      => $this->colors_from_config( $config ),
			'preview_css' => $this->build_preview_css( ColorModel::semantic_map(), $this->preview_overrides( $config ) ),
		);
	}

	/**
	 * Register REST API routes for the Style Guide.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'spectra-blocks/v1',
			'/style-guide/config',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rest_get_config' ),
					'permission_callback' => array( $this, 'rest_permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rest_save_config' ),
					'permission_callback' => array( $this, 'rest_permission_check' ),
					'args'                => array(
						'post_id'       => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Save into this page\'s own Style Guide instead of the site-wide one.', 'spectra-blocks' ),
						),
						'reset'         => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'With post_id, delete that page\'s Style Guide so it inherits the site-wide one again.', 'spectra-blocks' ),
						),
						'colors'        => array(
							'type'        => 'object',
							'description' => __( 'The nine core roles as slug => hex. Partial maps merge.', 'spectra-blocks' ),
						),
						'custom_colors' => array(
							'type'        => 'object',
							'description' => __( 'Additional colours as slug => { hex, name }. Replaces the whole layer.', 'spectra-blocks' ),
						),
					),
				),
			)
		);

		register_rest_route(
			'spectra-blocks/v1',
			'/style-guide/compute',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_computed' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		register_rest_route(
			'spectra-blocks/v1',
			'/style-guide/preview',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_preview' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);
	}

	/**
	 * REST permission check — must be able to edit theme options.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|\WP_Error
	 */
	public function rest_permission_check() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage the Style Guide.', 'spectra-blocks' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Guard a page-scoped write.
	 *
	 * `rest_permission_check()` only asserts `edit_theme_options`, which says nothing
	 * about a specific post — so without this any holder of that cap (a multisite
	 * site admin, a custom "Designer" role, or an import acting on a remote-chosen
	 * id) could write a palette into someone else's draft, a revision, an attachment,
	 * a `wp_template_part` or a `wp_block`.
	 *
	 * @since 1.0.4
	 *
	 * Shared with the Global Styles page-scoped writers, which have the same shape
	 * and the same exposure — a larger one, in fact, since they store arbitrary
	 * per-page CSS rather than a colour palette.
	 *
	 * @since 1.0.4
	 *
	 * @param int $post_id Target post.
	 * @return true|\WP_Error
	 */
	public static function check_page_scope( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return new \WP_Error(
				'invalid_post',
				__( 'Unknown post.', 'spectra-blocks' ),
				array( 'status' => 404 )
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You cannot edit this post.', 'spectra-blocks' ),
				array( 'status' => 403 )
			);
		}

		// The same allow-list `register_page_meta()` registers against — a page
		// palette is only ever read for a singular post or page.
		if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return new \WP_Error(
				'invalid_post_type',
				__( 'Unsupported post type.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * REST callback: get current config.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_config() {
		$config = $this->get_config();

		// Server truth for the editor's on-load gates. `get_config()` falls back to
		// the inherited/default palette, so the payload alone can't tell a SAVED
		// guide from an unsaved one — and an unsaved site must never be restyled
		// (the same rule every server-side injector applies). `pro` mirrors the
		// swatch gates (inject_palette et al.). Response-only keys — save_config()
		// persists canonical keys and never stores these.
		$config['saved'] = $this->has_saved_style_guide();
		$config['pro']   = defined( 'SPECTRA_BLOCKS_PRO_VER' );

		return rest_ensure_response( $config );
	}

	/**
	 * REST callback: save config and recompute.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_save_config( $request ) {
		$body = $request->get_json_params();

		if ( empty( $body ) || ! is_array( $body ) ) {
			return new \WP_Error(
				'invalid_config',
				__( 'Invalid configuration data.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		// PAGE-scoped save — same `post_id` convention as `global-styles/save`. Stores
		// the SAME v2 shape in that page's meta ({@see self::page_config()}); the site
		// guide, its caches and the theme sync are all untouched.
		$requested_post = $request->get_param( 'post_id' );
		$post_id        = is_numeric( $requested_post ) ? (int) $requested_post : 0;
		if ( $post_id > 0 ) {
			$allowed = self::check_page_scope( $post_id );
			if ( is_wp_error( $allowed ) ) {
				return $allowed;
			}

			// An explicit reset releases the page back to the site guide. The merge
			// below only ever ADDS roles — and canonical_config() always emits a
			// `colors` bucket — so without this a written page could never inherit again.
			if ( $request->get_param( 'reset' ) ) {
				delete_post_meta( $post_id, self::OPTION_KEY );
				return rest_ensure_response(
					array(
						'success' => true,
						'config'  => $this->get_config(),
					)
				);
			}

			// Merge onto the PAGE's own config, never the site's — a role the page
			// does not send must not inherit the site's colour (measured: an imported
			// terracotta page picked up the site's burgundy surface and neutral).
			$stored = get_post_meta( $post_id, self::OPTION_KEY, true );
			$config = $this->build_config_from_request(
				$body,
				$request,
				is_array( $stored ) && ! empty( $stored['colors'] ) ? $stored : $this->get_default_config()
			);
			$clean  = $this->canonical_config( $config );
			$saved  = update_post_meta( $post_id, self::OPTION_KEY, $clean );

			// update_post_meta() returns false BOTH when the write fails and when the
			// stored value is already identical, so an unchanged re-save is a success.
			$stored_now = get_post_meta( $post_id, self::OPTION_KEY, true );

			return rest_ensure_response(
				array(
					'success' => false !== $saved || $stored_now === $clean,
					'config'  => $this->get_config( $config ),
				)
			);
		}

		// Build the merged, sanitized config from the request (shared with preview).
		$config = $this->build_config_from_request( $body, $request );

		$this->save_config( $config );
		$this->compute();

		// Option-only: the design system is the single source of truth in the
		// option. The palette renders via the runtime theme-layer injection
		// (GlobalStylesBridge::inject_palette) and the user layer is overridden at
		// render (maybe_override_managed_user_palette) — no wp_global_styles write.

		return rest_ensure_response(
			array(
				'success' => true,
				'config'  => $this->get_config(),
				'tokens'  => $this->token_registry ? $this->token_registry->get_all() : array(),
				'palette' => $this->token_registry ? $this->token_registry->get_wp_palette() : array(),
			)
		);
	}

	/**
	 * Build a merged, sanitized config from a REST request body.
	 *
	 * Shared by save (persist) and preview (compute-only) so both apply the
	 * exact same sanitization.
	 *
	 * Field ownership (v2):
	 * - The FREE plugin owns all colour storage — the nine core `colors` and the
	 *   `custom_colors` layer are sanitized here. (This replaces the old v1 split
	 *   where free wrote only the three brand hexes and Pro merged the rest;
	 *   `semantic_map`/`semantic_overrides`/`chromatics` are no longer stored —
	 *   they are derived on read in {@see Engine::get_config()}.)
	 * - PRO merges only typography + UI-styling presets, via the
	 *   `spectra_style_guide_config_before_save` filter.
	 *
	 * So without Pro the filter is a no-op and ONLY typography + UI presets are
	 * lost — every colour (9 core + custom) still persists.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed>      $body    Raw request body.
	 * @param \WP_REST_Request         $request The REST request (passed to the filter).
	 * @param array<string,mixed>|null $base    Config to merge onto; defaults to the
	 *                                          site's. A page-scoped save passes its own.
	 * @return array<string,mixed> Merged config.
	 */
	private function build_config_from_request( $body, $request, ?array $base = null ) {
		// Start from the raw stored v2 config (canonical keys only — no derived keys).
		$config = $base ?? $this->get_stored_config();

		// The nine core colours (slug => hex). Only known core slugs are accepted;
		// unknown slugs belong in custom_colors. A partial map merges over the current.
		if ( isset( $body['colors'] ) && is_array( $body['colors'] ) ) {
			if ( ! isset( $config['colors'] ) || ! is_array( $config['colors'] ) ) {
				$config['colors'] = array();
			}
			foreach ( ColorModel::core_slugs() as $slug ) {
				$raw = $body['colors'][ $slug ] ?? null;
				if ( ! is_string( $raw ) ) {
					continue;
				}
				$hex = sanitize_hex_color( $raw );
				if ( $hex ) {
					$config['colors'][ $slug ] = $hex;
				}
			}
		}

		// The custom_colors layer (slug => { hex, name }). Full-replace map — an empty
		// map clears it. Absorbs the old semantic_overrides + the user "Add colour"
		// feature: a slug matching a generated preset slug overrides it; a new slug
		// adds a colour. Hex required; name optional; malformed entries dropped.
		if ( isset( $body['custom_colors'] ) && is_array( $body['custom_colors'] ) ) {
			$config['custom_colors'] = $this->sanitize_custom_colors( $body['custom_colors'] );
		}

		/**
		 * Filters the Style Guide config before it is saved.
		 *
		 * Kept as an extension point for Pro-only Style Guide fields. The Style Guide
		 * is colours-only (Typography and UI Styling were removed), so no Pro handler
		 * is registered today. Only the canonical v2 keys are persisted ({@see
		 * save_config}); any extra keys a filter adds are stripped, so the stored
		 * option stays minimal.
		 *
		 * @since 1.0.0
		 *
		 * @param array            $config  Config to be saved (v2 colours applied).
		 * @param array            $body    Raw request body.
		 * @param \WP_REST_Request $request The original REST request.
		 */
		$config = apply_filters( 'spectra_style_guide_config_before_save', $config, $body, $request );
		if ( ! is_array( $config ) ) {
			$config = array();
		}

		$config['version'] = 2;

		return $config;
	}

	/**
	 * REST callback: get computed tokens and schemes.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_computed() {
		$this->maybe_compute();

		return rest_ensure_response(
			array(
				'tokens'          => $this->token_registry ? $this->token_registry->get_all() : array(),
				'palette'         => $this->token_registry ? $this->token_registry->get_wp_palette() : array(),
				'css'             => $this->token_registry ? $this->token_registry->get_css_string() : '',
				// The editor's live preview mirrors the Astra aliases the bridge emits,
				// so it needs the SAME slot map — which is layout-dependent and only
				// resolvable server-side ({@see GlobalStylesBridge::astra_shade_map()}).
				// Shipping it here keeps the client from hardcoding a copy that is wrong
				// on legacy Astra installs.
				'astra_shade_map' => GlobalStylesBridge::astra_shade_map(),
			)
		);
	}

	/**
	 * REST callback: compute tokens for a supplied config WITHOUT persisting.
	 *
	 * Powers the live canvas preview in the editor — the client POSTs its draft
	 * config and gets back computed tokens/schemes/palette/css. Nothing is saved
	 * to the option, the object cache, or `wp_global_styles`.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_preview( $request ) {
		$body = $request->get_json_params();

		if ( empty( $body ) || ! is_array( $body ) ) {
			return new \WP_Error(
				'invalid_config',
				__( 'Invalid configuration data.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$config = $this->build_config_from_request( $body, $request );

		// Compute without persisting to the option cache.
		$this->compute( $config, false );

		return rest_ensure_response(
			array(
				'tokens'          => $this->token_registry ? $this->token_registry->get_all() : array(),
				'palette'         => $this->token_registry ? $this->token_registry->get_wp_palette() : array(),
				'css'             => $this->token_registry ? $this->token_registry->get_css_string() : '',
				// Ready-to-inject combined CSS for a live editor preview (Layer A
				// token ramps + Layer B --wp--preset--color--*), so a consumer can
				// inject ONE string with no client-side token math. Overrides carry
				// the derived tones (surface-2 / overlay) PLUS the custom_colors pins,
				// matching what the front end renders — see preview_overrides().
				'css_full'        => $this->build_preview_css( ColorModel::semantic_map(), $this->preview_overrides( $config ) ),
				// Layout-aware Astra slot map — see rest_get_computed(). The preview
				// path needs it too: the editor mirrors the Astra aliases live while
				// the user drags a colour, before anything is saved.
				'astra_shade_map' => GlobalStylesBridge::astra_shade_map(),
			)
		);
	}

	/**
	 * The full semantic-override map for a config: the DERIVED tones
	 * ({@see self::derived_var_defaults} — surface-2 / overlay) PLUS the
	 * `custom_colors` pins. This mirrors what the live front end resolves via
	 * {@see GlobalStylesBridge::managed_preset_map} (semantic_map ∪ derived ∪ pins),
	 * so preview CSS built with it matches exactly what the site renders — the pins
	 * alone left `surface-2` / `overlay` out of the preview.
	 *
	 * Shared by {@see self::preview_css_for}, {@see self::apply_colors} and
	 * {@see self::rest_preview}, which all want the identical override set.
	 *
	 * @since 1.0.6
	 *
	 * @param array<string, mixed> $config Canonical config (colors + custom_colors).
	 * @return array<string, string> slug => hex.
	 */
	private function preview_overrides( array $config ): array {
		return array_merge(
			$this->derived_var_defaults( $this->colors_from_config( $config ) ),
			$this->custom_colors_as_overrides(
				isset( $config['custom_colors'] ) && is_array( $config['custom_colors'] ) ? $config['custom_colors'] : array()
			)
		);
	}

	/**
	 * Build ready-to-inject preview CSS for the CURRENTLY COMPUTED tokens.
	 *
	 * Combines the two layers the live editor canvas needs into ONE string, so a
	 * consumer (e.g. the ERA palette picker in the zip-ai plugin) can inject it
	 * with no client-side token math or semantic-map mirror:
	 *   - Layer A: the :root --spectra-* token ramps ({@see TokenRegistry::get_css_string}).
	 *   - Layer B: --wp--preset--color--<slug> for every shade slug
	 *     ({@see TokenRegistry::get_wp_palette}) PLUS every semantic slug resolved
	 *     from $semantic_map through the computed tokens, with explicit $overrides
	 *     winning. Mirrors the Pro sidebar's client-side buildPresetPaletteCSS(),
	 *     but uses the server's own semantic_map as the single source of truth.
	 *
	 * MUST be called AFTER compute() — reads the live token_registry.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, string> $semantic_map slug => shade-token key (e.g. "primary" => "chromatic1-7").
	 * @param array<string, string> $overrides    slug => explicit hex (wins over the derived value).
	 * @return string CSS string, or '' when tokens are unavailable.
	 */
	private function build_preview_css( array $semantic_map, array $overrides ): string {
		if ( ! $this->token_registry ) {
			return '';
		}

		$layer_a = $this->token_registry->get_css_string();

		$map = array();

		// Shade slugs (spectra-chromatic*, spectra-neutral*, spectra-white).
		foreach ( $this->token_registry->get_wp_palette() as $entry ) {
			if ( ! empty( $entry['slug'] ) && ! empty( $entry['color'] ) ) {
				$map[ $entry['slug'] ] = $entry['color'];
			}
		}

		// Semantic slugs (primary, secondary, heading, accent, sg-*, status …)
		// resolved from the semantic_map through the computed tokens.
		$tokens = $this->token_registry->get_all();
		foreach ( $semantic_map as $slug => $token_key ) {
			if ( isset( $tokens[ $token_key ] ) ) {
				$map[ $slug ] = $tokens[ $token_key ];
			}
		}

		// Explicit per-slug overrides win over the shade-derived value.
		foreach ( $overrides as $slug => $hex ) {
			if ( '' !== $hex ) {
				$map[ $slug ] = $hex;
			}
		}

		// Astra theme compat: mirror the TWO var families GlobalStylesBridge emits
		// on apply (inject_astra_compat_editor_styles + get_astra_compat_css) so
		// Astra-driven blocks (--wp--preset--color--ast-global-color-N via the
		// has-ast-global-color-N-* classes, and the raw --ast-global-color-N alias)
		// preview too. Uses the bridge's astra_shade_map() as the single source.
		$ast_decls = array();
		foreach ( GlobalStylesBridge::astra_shade_map() as $index => $shade_key ) {
			if ( ! isset( $tokens[ $shade_key ] ) ) {
				continue;
			}
			$hex                                 = $tokens[ $shade_key ];
			$map[ 'ast-global-color-' . $index ] = $hex;                                              // --wp--preset--color--ast-global-color-N.
			$ast_decls[]                         = '--ast-global-color-' . $index . ':' . $hex;       // Astra's own alias.
		}

		if ( empty( $map ) && empty( $ast_decls ) ) {
			return $layer_a;
		}

		$decls = array();
		foreach ( $map as $slug => $hex ) {
			$decls[] = '--wp--preset--color--' . $slug . ':' . $hex;
		}
		$decls = array_merge( $decls, $ast_decls );

		return $layer_a . ':root,.editor-styles-wrapper{' . implode( ';', $decls ) . '}';
	}

	/**
	 * Rewrite hardcoded spacing values with token references on sg-* containers.
	 *
	 * At render time, containers with sg-section or sg-card CSS class get their
	 * inline padding and gap values replaced with Style Guide token references.
	 * Saved post content is never modified — this is a pure render-time filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $parsed_block The parsed block data.
	 * @return array<string, mixed> Modified (or unchanged) parsed block data.
	 */
	public function rewrite_spacing_tokens( $parsed_block ) {
		// Only target Spectra container blocks.
		if ( empty( $parsed_block['blockName'] ) || 'spectra/container' !== $parsed_block['blockName'] ) {
			return $parsed_block;
		}

		if ( empty( $parsed_block['attrs'] ) ) {
			return $parsed_block;
		}

		/* @var array<string, mixed> $attrs */
		$attrs     = $parsed_block['attrs'];
		$classname = isset( $attrs['className'] ) && is_string( $attrs['className'] ) ? $attrs['className'] : '';

		// Determine which token set to apply.
		// `sg-card` (visual treatment only — border, shadow, bg) does NOT auto-pad anymore.
		// Authors opt into the 48px card padding via the explicit `sg-card-padded` class so
		// LLM- or pattern-authored Tailwind padding utilities (e.g. `p-6` on inner content)
		// are not double-stacked underneath an auto-injected outer padding.
		$is_section     = false !== strpos( $classname, 'sg-section' );
		$is_card_padded = false !== strpos( $classname, 'sg-card-padded' );

		if ( ! $is_section && ! $is_card_padded ) {
			return $parsed_block;
		}

		// Container padding lives under the native Gutenberg spacing schema,
		// keyed per breakpoint under `responsiveControls`:
		// $attrs['responsiveControls'][ 'lg' | 'md' | 'sm' ]['style']['spacing']['padding'][ 'top' | 'right' | 'bottom' | 'left' ]
		// Section tokens target vertical padding only so horizontal layout
		// (max-widths, auto margins) stays under author control.
		if ( $is_section ) {
			$sides = array( 'top', 'bottom' );
			$token = 'var(--spectra-section-padding-y)';
		} else {
			// $is_card_padded — explicit opt-in for the 48px card padding token.
			$sides = array( 'top', 'right', 'bottom', 'left' );
			$token = 'var(--spectra-card-padding)';
		}

		$devices = array( 'lg', 'md', 'sm' );

		if ( ! isset( $attrs['responsiveControls'] ) || ! is_array( $attrs['responsiveControls'] ) ) {
			$attrs['responsiveControls'] = array();
		}

		foreach ( $devices as $device ) {
			if ( ! isset( $attrs['responsiveControls'][ $device ] ) || ! is_array( $attrs['responsiveControls'][ $device ] ) ) {
				$attrs['responsiveControls'][ $device ] = array();
			}
			if ( ! isset( $attrs['responsiveControls'][ $device ]['style'] ) || ! is_array( $attrs['responsiveControls'][ $device ]['style'] ) ) {
				$attrs['responsiveControls'][ $device ]['style'] = array();
			}
			if ( ! isset( $attrs['responsiveControls'][ $device ]['style']['spacing'] ) || ! is_array( $attrs['responsiveControls'][ $device ]['style']['spacing'] ) ) {
				$attrs['responsiveControls'][ $device ]['style']['spacing'] = array();
			}

			$spacing = $attrs['responsiveControls'][ $device ]['style']['spacing'];

			// Shorthand padding (single string) hides per-side values. Discard
			// it so we can write an explicit per-side array and stay authoritative.
			if ( isset( $spacing['padding'] ) && ! is_array( $spacing['padding'] ) ) {
				unset( $spacing['padding'] );
			}
			if ( ! isset( $spacing['padding'] ) || ! is_array( $spacing['padding'] ) ) {
				$spacing['padding'] = array();
			}

			foreach ( $sides as $side ) {
				$spacing['padding'][ $side ] = $token;
			}

			$attrs['responsiveControls'][ $device ]['style']['spacing'] = $spacing;
		}

		$parsed_block['attrs'] = $attrs;

		return $parsed_block;
	}
}
