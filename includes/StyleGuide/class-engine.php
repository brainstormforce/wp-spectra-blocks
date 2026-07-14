<?php
/**
 * Style Guide Engine — orchestrator that runs the full color pipeline.
 *
 * Wires together ShadeGenerator → ContrastEngine → SchemeComputer → TokenRegistry
 * and connects to GlobalStylesBridge for WordPress integration.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.0
 */

namespace SpectraBlocks\StyleGuide;

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
	 * Mapping from Astra global color slug strings to Style Guide sg-* slugs.
	 *
	 * Used by rewrite_astra_color_attrs() to rewrite block attribute values
	 * (textColor, backgroundColor, borderColor, and var:preset|color|* style
	 * references) from Astra palette slugs to the sg-* equivalents at render
	 * time. No database changes — purely a runtime filter.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const ASTRA_TO_SG_SLUG = array(
		'ast-global-color-0' => 'sg-accent',
		'ast-global-color-1' => 'sg-secondary',
		'ast-global-color-2' => 'sg-heading',
		'ast-global-color-3' => 'sg-body',
		'ast-global-color-4' => 'sg-surface',
		'ast-global-color-5' => 'sg-background',
		'ast-global-color-6' => 'sg-heading',
		'ast-global-color-7' => 'sg-border',
		'ast-global-color-8' => 'sg-heading',
	);

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
	 * @since 1.0.0
	 * @return bool True when a non-empty Style Guide config is stored.
	 */
	private function has_saved_style_guide(): bool {
		$saved = get_option( self::OPTION_KEY, array() );
		return is_array( $saved ) && ! empty( $saved );
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

		// Rewrite direct palette slug attributes.
		$slug_keys = array( 'textColor', 'backgroundColor', 'borderColor' );
		foreach ( $slug_keys as $key ) {
			if ( isset( $attrs[ $key ] ) && isset( self::ASTRA_TO_SG_SLUG[ $attrs[ $key ] ] ) ) {
				$attrs[ $key ] = self::ASTRA_TO_SG_SLUG[ $attrs[ $key ] ];
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
				foreach ( self::ASTRA_TO_SG_SLUG as $astra_slug => $sg_slug ) {
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
		// Quick bail: no sg- reference in the output.
		if ( false === strpos( $block_content, 'sg-' ) ) {
			return $block_content;
		}

		// Replace raw sg-* slugs in Spectra CSS variables.
		// Matches: --spectra-text-color: sg-heading  →  --spectra-text-color: var(--wp--preset--color--sg-heading)
		// Matches: --spectra-background-color: sg-surface  →  ...var(--wp--preset--color--sg-surface).
		$block_content = preg_replace(
			'/(--spectra-[a-z-]+(?:-color)?)\s*:\s*(sg-[a-z]+)\s*(;|")/',
			'$1: var(--wp--preset--color--$2)$3',
			$block_content
		) ?? $block_content;

		return $block_content;
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
		$this->token_registry = null;
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

		/**
		 * Chromatic color config.
		 *
		 * @var array<int, array{hex?: string, name?: string, isMain?: bool}> $chromatics
		 */
		$chromatics = isset( $config['chromatics'] ) && is_array( $config['chromatics'] ) ? $config['chromatics'] : array();
		/**
		 * Neutral tint settings.
		 *
		 * @var array{tintIndex?: int|null, tintStrength?: float} $neutral_config
		 */
		$neutral_config = isset( $config['neutral'] ) && is_array( $config['neutral'] ) ? $config['neutral'] : array();
		/**
		 * UI styling preset selections.
		 *
		 * @var array<string, string> $presets
		 */
		$presets = isset( $config['presets'] ) && is_array( $config['presets'] ) ? $config['presets'] : array();

		// Determine neutral base.
		$tint_index    = isset( $neutral_config['tintIndex'] ) ? $neutral_config['tintIndex'] : null;
		$tint_strength = isset( $neutral_config['tintStrength'] ) ? (float) $neutral_config['tintStrength'] : 0.05;

		if ( null !== $tint_index && isset( $chromatics[ $tint_index ]['hex'] ) ) {
			$neutral_base = ShadeGenerator::tint_neutral_oklch( $chromatics[ $tint_index ]['hex'], $tint_strength );
		} else {
			$neutral_base = '#000000';
		}

		// --- Layer 1: Generate shades ---

		// Shade generation options from presets.
		$shade_options = array(
			'shadeProfile' => isset( $presets['shadeProfile'] ) ? $presets['shadeProfile'] : 'balanced',
			'saturation'   => isset( $presets['saturation'] ) ? $presets['saturation'] : 'balanced',
		);

		// Neutral shades (8).
		$neutral_shades = ShadeGenerator::generate_neutral_shades( $neutral_base, $shade_options );
		foreach ( $neutral_shades as $index => $hex ) {
			$tokens->set( "neutral-{$index}", $hex );
		}

		// Chromatic shades (7 per color).
		$chromatic_count = 0;
		foreach ( $chromatics as $c_index => $chromatic ) {
			if ( empty( $chromatic['hex'] ) ) {
				continue;
			}

			$hex = sanitize_hex_color( $chromatic['hex'] );
			if ( ! $hex ) {
				continue;
			}

			$shades = ShadeGenerator::generate_chromatic_shades( $hex, $shade_options );
			foreach ( $shades as $s_index => $shade_hex ) {
				$tokens->set( "chromatic{$c_index}-{$s_index}", $shade_hex );
			}

			// Dark shades (indices 8-11) via black-mixing.
			$dark_shades = ShadeGenerator::generate_chromatic_dark_shades( $hex );
			foreach ( $dark_shades as $ds_index => $dark_hex ) {
				$tokens->set( "chromatic{$c_index}-{$ds_index}", $dark_hex );
			}

			// Store color name for palette labels.
			$name = isset( $chromatic['name'] ) ? $chromatic['name'] : "Color {$c_index}";
			$tokens->set_color_name( "chromatic{$c_index}", $name );

			$chromatic_count = max( $chromatic_count, $c_index );
		}

		// Constants.
		$tokens->set( 'white', '#ffffff' );
		$tokens->set( 'transparent', 'transparent' );

		// Opacity tokens (16 total).
		$neutral_7_hex = $tokens->get( 'neutral-7' );
		if ( null !== $neutral_7_hex ) {
			$opacity_tokens = ShadeGenerator::generate_opacity_tokens( $neutral_7_hex, '#ffffff' );

			foreach ( $opacity_tokens['dark'] as $pct => $rgba ) {
				$tokens->set( "opacity-dark-{$pct}", $rgba );
			}
			foreach ( $opacity_tokens['light'] as $pct => $rgba ) {
				$tokens->set( "opacity-light-{$pct}", $rgba );
			}
		}

		// --- Layer 2 + 3: Compute schemes ---

		// Find the main accent key.
		$main_accent_key = 'chromatic1-4'; // Default: first chromatic, base shade.
		foreach ( $chromatics as $c_index => $chromatic ) {
			if ( ! empty( $chromatic['isMain'] ) ) {
				$main_accent_key = "chromatic{$c_index}-4";
				break;
			}
		}

		$schemes = SchemeComputer::compute_all_schemes( $tokens, $main_accent_key, $chromatic_count );
		$tokens->set_schemes( $schemes );

		// --- Layer 4: Register UI styling tokens ---
		$tokens->register_ui_tokens( $presets );

		// Cache the result.
		$this->token_registry = $tokens;

		// Preview computes (persist = false) must not pollute the cached tokens
		// used by the front-end / editor render path.
		if ( $persist ) {
			wp_cache_set( self::CACHE_KEY, $tokens, self::CACHE_GROUP );
		}
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
	 * Default status chromatics (indices 4-7) — SSOT for the Success / Error / Info
	 * / Warning brand colours, shared by get_config()'s back-fill and
	 * get_default_config()'s hardcoded fallback so the two can't drift.
	 *
	 * @since 1.0.0
	 * @var array<int, array{hex: string, name: string}>
	 */
	private static $status_chromatic_defaults = array(
		4 => array(
			'hex'  => '#10b981',
			'name' => 'Success',
		),
		5 => array(
			'hex'  => '#ef4444',
			'name' => 'Error',
		),
		6 => array(
			'hex'  => '#8b5cf6',
			'name' => 'Info',
		),
		7 => array(
			'hex'  => '#d97706',
			'name' => 'Warning',
		),
	);

	/**
	 * Get the stored Style Guide configuration.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Configuration array.
	 */
	public function get_config() {
		$config = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $config ) || empty( $config ) ) {
			$config = $this->get_default_config();
		}

		// Ensure semantic_map always has defaults (merges saved keys over defaults).
		// Semantic map defaults.
		// 'secondary' = hover/active variant of primary (shade-5 = 10% white mix).
		// This ensures button hover is always a lighter version of button bg,
		// not a completely different brand color.
		$default_semantic_map = array(
			'primary'       => 'chromatic1-7', // Button bg, link text (base hex).
			'secondary'     => 'chromatic1-5', // Button hover, link hover (lighter primary).
			'tertiary'      => 'chromatic1-2', // Light tint sections.
			'quaternary'    => 'chromatic2-2', // Light tint sections (second brand color).
			'heading'       => 'neutral-7',    // h1-h6 text (darkest neutral).
			'body'          => 'neutral-5',    // Body text (mid-dark neutral).
			'background'    => 'neutral-0',    // Page bg, button text inverse (white).
			'foreground'    => 'chromatic1-7', // Matches primary for theme compat.
			'surface'       => 'neutral-1',    // Card/panel backgrounds.
			'outline'       => 'neutral-2',    // Borders, separators, dividers.
			'neutral'       => 'neutral-4',    // Muted/secondary text.

			// Extended era-contract roles. 'accent' is the DISTINCT Accent brand
			// colour (chromatic3), not a shade of primary; the four status roles map
			// to their named chromatics so var(--wp--preset--color--{role}) resolves.
			// Chromatics: 3=Accent, 4=Success, 5=Error, 6=Info, 7=Warning.
			'accent'        => 'chromatic3-7', // Accent / highlight brand colour.
			'success'       => 'chromatic4-7', // Success (green).
			'error'         => 'chromatic5-7', // Error (red).
			'info'          => 'chromatic6-7', // Info (violet).
			'warning'       => 'chromatic7-7', // Warning (amber).

			// sg-* semantic slugs: canonical targets for Astra color migration.
			// These parallel the plain-slug entries above and enable pattern
			// compatibility on Spectra One and other non-Astra FSE themes.
			'sg-accent'     => 'chromatic1-7', // Primary brand color.
			'sg-secondary'  => 'chromatic1-5', // Secondary / hover variant.
			'sg-heading'    => 'neutral-7',    // Heading text.
			'sg-body'       => 'neutral-5',    // Body text.
			'sg-surface'    => 'neutral-1',    // Card / panel backgrounds.
			'sg-background' => 'neutral-0',    // Page / section backgrounds.
			'sg-border'     => 'neutral-2',    // Borders and separators.
			'sg-neutral'    => 'neutral-6',    // Dark section / footer backgrounds.
			'sg-muted'      => 'neutral-4',    // Muted / secondary text.
		);

		if ( ! isset( $config['semantic_map'] ) || ! is_array( $config['semantic_map'] ) ) {
			$config['semantic_map'] = $default_semantic_map;
		} else {
			$config['semantic_map'] = array_merge( $default_semantic_map, $config['semantic_map'] );
		}

		// Ensure the status chromatics (4-7) always exist so the era-contract status
		// roles (success/error/warning/info) resolve even on configs that only saved
		// the brand chromatics (1-3). The `+` union keeps existing indices and fills
		// only the missing status ones (array_merge would renumber integer keys).
		$config['chromatics'] = ( isset( $config['chromatics'] ) && is_array( $config['chromatics'] ) )
			? $config['chromatics'] + self::$status_chromatic_defaults
			: self::$status_chromatic_defaults;

		return $config;
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

		$result = update_option( self::OPTION_KEY, $config, false );

		/**
		 * Fires after the Style Guide configuration has been saved.
		 *
		 * Downstream systems (e.g., Global Styles ClassRegistry) listen to
		 * invalidate derived caches so their dynamic tokens (colors, spacing,
		 * fonts) recompute against the new config.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $config The saved configuration.
		 */
		do_action( 'spectra_style_guide_config_saved', $config );

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
		$defaults_file = SPECTRA_BLOCKS_DIR . 'data/style-guide-defaults.json';

		if ( file_exists( $defaults_file ) ) {
			$json = file_get_contents( $defaults_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( is_string( $json ) ) {
				$data = json_decode( $json, true );

				if ( is_array( $data ) ) {
					return $data;
				}
			}
		}

		// Hardcoded fallback.
		return array(
			'version'      => 1,
			// Brand chromatics (1-3); status chromatics (4-7) come from the shared
			// SSOT const so they can't drift from get_config()'s back-fill.
			'chromatics'   => array(
				1 => array(
					'hex'    => '#6431f6',
					'name'   => 'Primary',
					'isMain' => true,
				),
				2 => array(
					'hex'  => '#7345f7',
					'name' => 'Secondary',
				),
				3 => array(
					'hex'  => '#f59e0b',
					'name' => 'Accent',
				),
			) + self::$status_chromatic_defaults,
			'neutral'      => array(
				'tintIndex'    => 1,
				'tintStrength' => 0.05,
			),
			'presets'      => array(
				'buttonStyle'    => 'solid',
				'inputStyle'     => 'boxed',
				'cardStyle'      => 'flat',
				'roundness'      => 'default',
				'shadowDepth'    => 'subtle',
				'spacingDensity' => 'default',
				'typographyPair' => 'inter-inter',
				'badgeStyle'     => 'subtle',
				'imageTreatment' => 'natural',
				'hoverIntensity' => 'subtle',
				'backgroundFeel' => 'clean',
				'motionStyle'    => 'subtle',
			),
			// Semantic map: maps theme semantic color slugs to Spectra shade keys.
			// Chromatic shade indices: 1=lightest(95% white) ... 7=base(0% white).
			// Neutral shade indices: 0=white ... 7=darkest.
			// 'secondary' = hover/active of primary (not a separate brand color).
			'semantic_map' => array(
				'primary'       => 'chromatic1-7', // Button bg, link text (base hex).
				'secondary'     => 'chromatic1-5', // Button hover, link hover (lighter primary).
				'tertiary'      => 'chromatic1-2', // Light tint section backgrounds.
				'quaternary'    => 'chromatic2-2', // Light tint sections (second brand color).
				'heading'       => 'neutral-7',    // h1-h6 text (darkest neutral).
				'body'          => 'neutral-5',    // Body text (mid-dark neutral).
				'background'    => 'neutral-0',    // Page bg, button text inverse (white).
				'foreground'    => 'chromatic1-7', // Matches primary for theme compat.
				'surface'       => 'neutral-1',    // Card/panel backgrounds.
				'outline'       => 'neutral-2',    // Borders, separators, dividers.
				'neutral'       => 'neutral-4',    // Muted/secondary text.

				// Extended era-contract roles (mirror get_config()'s default map).
				'accent'        => 'chromatic3-7', // Accent / highlight brand colour.
				'success'       => 'chromatic4-7', // Success (green).
				'error'         => 'chromatic5-7', // Error (red).
				'info'          => 'chromatic6-7', // Info (violet).
				'warning'       => 'chromatic7-7', // Warning (amber).

				// sg-* semantic slugs: canonical targets for Astra color migration.
				'sg-accent'     => 'chromatic1-7',
				'sg-secondary'  => 'chromatic1-5',
				'sg-heading'    => 'neutral-7',
				'sg-body'       => 'neutral-5',
				'sg-surface'    => 'neutral-1',
				'sg-background' => 'neutral-0',
				'sg-border'     => 'neutral-2',
				'sg-neutral'    => 'neutral-6',
				'sg-muted'      => 'neutral-4',
			),
		);
	}

	/**
	 * Get a specific scheme by its key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Scheme key (e.g., 'chromatic1-4').
	 * @return array<string, mixed>|null Scheme object or null.
	 */
	public function get_scheme( $key ) {
		if ( null === $this->token_registry ) {
			$this->maybe_compute();
		}

		if ( null === $this->token_registry ) {
			return null;
		}

		return $this->token_registry->get_scheme( $key );
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
	 * REST callback: get current config.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_config() {
		return rest_ensure_response( $this->get_config() );
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
				'schemes' => $this->token_registry ? $this->token_registry->get_schemes() : array(),
				'palette' => $this->token_registry ? $this->token_registry->get_wp_palette() : array(),
			)
		);
	}

	/**
	 * Build a merged, sanitized config from a REST request body.
	 *
	 * Shared by save (persist) and preview (compute-only) so both apply the
	 * exact same sanitization: free chromatics 1-3 + semantic_map here, and the
	 * Pro fields (neutral, presets, extra chromatics, typography) via the
	 * `spectra_style_guide_config_before_save` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $body    Raw request body.
	 * @param \WP_REST_Request    $request The REST request (passed to the filter).
	 * @return array<string,mixed> Merged config.
	 */
	private function build_config_from_request( $body, $request ) {
		// Start from the existing saved config.
		$config = $this->get_config();

		// Free tier: primary (1), secondary (2), and accent (3) color hex + name.
		if ( isset( $body['chromatics'] ) && is_array( $body['chromatics'] ) ) {
			foreach ( array( 1, 2, 3 ) as $index ) {
				if ( ! isset( $body['chromatics'][ $index ] ) ) {
					continue;
				}
				$chromatic = $body['chromatics'][ $index ];
				if ( isset( $chromatic['hex'] ) ) {
					$hex = sanitize_hex_color( $chromatic['hex'] );
					if ( $hex ) {
						$config['chromatics'][ $index ]['hex'] = $hex;
					}
				}
				if ( isset( $chromatic['name'] ) ) {
					$name = sanitize_text_field( $chromatic['name'] );
					if ( '' !== $name ) {
						$config['chromatics'][ $index ]['name'] = $name;
					}
				}
			}
		}

		// Free tier: persist a caller-supplied semantic_map (slug => shade-token
		// key, e.g. "secondary" => "chromatic2-7"). Values are token keys, never
		// raw CSS; sanitize both sides and drop malformed entries.
		if ( isset( $body['semantic_map'] ) && is_array( $body['semantic_map'] ) ) {
			$semantic_map = array();
			foreach ( $body['semantic_map'] as $slug => $shade_key ) {
				if ( ! is_string( $slug ) || ! is_string( $shade_key ) ) {
					continue;
				}
				$clean_slug = sanitize_key( $slug );
				$clean_key  = preg_replace( '/[^a-z0-9-]/', '', strtolower( $shade_key ) );
				if ( '' !== $clean_slug && '' !== $clean_key ) {
					$semantic_map[ $clean_slug ] = $clean_key;
				}
			}
			if ( ! empty( $semantic_map ) ) {
				$config['semantic_map'] = $semantic_map;
			}
		}

		// Free tier: explicit per-slug colour overrides (slug => hex). These win
		// over the shade-derived semantic value at render — used for imported
		// source colours whose semantic role the shade derivation would recompute
		// incorrectly (e.g. a brand dark accent bound to `quaternary` that would
		// otherwise become a primary tint). Hex only; malformed entries dropped.
		// Sent as a full map (replace), so an empty map clears prior overrides.
		if ( isset( $body['semantic_overrides'] ) && is_array( $body['semantic_overrides'] ) ) {
			$semantic_overrides = array();
			foreach ( $body['semantic_overrides'] as $slug => $hex ) {
				if ( ! is_string( $slug ) || ! is_string( $hex ) ) {
					continue;
				}
				$clean_slug = sanitize_key( $slug );
				$clean_hex  = sanitize_hex_color( $hex );
				if ( '' !== $clean_slug && $clean_hex ) {
					$semantic_overrides[ $clean_slug ] = $clean_hex;
				}
			}
			$config['semantic_overrides'] = $semantic_overrides;
		}

		/**
		 * Filters the Style Guide config before it is saved.
		 *
		 * Pro hooks here to merge additional fields (neutral, presets,
		 * extra chromatics) from the request into the config. Without Pro
		 * the filter is a no-op and only the 3 free color hex values are written.
		 *
		 * @since 1.0.0
		 *
		 * @param array            $config  Config to be saved (base + free fields applied).
		 * @param array            $body    Raw request body.
		 * @param \WP_REST_Request $request The original REST request.
		 */
		$config = apply_filters( 'spectra_style_guide_config_before_save', $config, $body, $request );
		if ( ! is_array( $config ) ) {
			$config = array();
		}

		$config['version'] = 1;

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
				'tokens'  => $this->token_registry ? $this->token_registry->get_all() : array(),
				'schemes' => $this->token_registry ? $this->token_registry->get_schemes() : array(),
				'palette' => $this->token_registry ? $this->token_registry->get_wp_palette() : array(),
				'css'     => $this->token_registry ? $this->token_registry->get_css_string() : '',
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
				'tokens'  => $this->token_registry ? $this->token_registry->get_all() : array(),
				'schemes' => $this->token_registry ? $this->token_registry->get_schemes() : array(),
				'palette' => $this->token_registry ? $this->token_registry->get_wp_palette() : array(),
				'css'     => $this->token_registry ? $this->token_registry->get_css_string() : '',
			)
		);
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
