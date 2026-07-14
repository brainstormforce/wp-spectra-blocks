<?php
/**
 * Generic theme style-preset compatibility resolver.
 *
 * Maps an active theme's own style presets (color, typography, …) to Style Guide
 * values so the theme adopts the design system across every surface. Overriding the
 * theme.json preset makes BOTH the editor picker/swatch and the generated
 * --wp--preset--{family}--{slug} variable follow the Style Guide (both derive from
 * theme.json).
 *
 * Themes with a dedicated compatibility layer (Astra, Spectra One) are handled by
 * their own code and are excluded here; this fills the gap for every OTHER (generic
 * FSE) theme, opt-in behind a flag until the heuristics are proven.
 *
 * Families implemented: color, font-family. Spacing / shadow follow the same shape.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.0
 */

namespace SpectraBlocks\StyleGuide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ThemeStyleCompat
 *
 * @since 1.0.0
 */
class ThemeStyleCompat {

	/**
	 * Themes that already have a dedicated compatibility layer and must NOT be
	 * double-handled here.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	const DEDICATED_THEMES = array( 'astra', 'spectra-one' );

	/**
	 * Explicit per-theme colour palette-slug => Style Guide token-key maps.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	const COLOR_REGISTRY = array(
		'twentytwentyfive' => array(
			'base'     => 'neutral-0',
			'contrast' => 'neutral-7',
			'accent-1' => 'chromatic1-7',
			'accent-2' => 'chromatic1-5',
			'accent-3' => 'chromatic2-7',
			'accent-4' => 'chromatic3-7',
			'accent-5' => 'neutral-5',
			'accent-6' => 'neutral-2',
		),
	);

	/**
	 * Ordered colour name-heuristic patterns for unknown themes: slug regex => token
	 * key. `accent-N` is handled separately in {@see self::color_heuristic()}.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const COLOR_HEURISTICS = array(
		'/^(base|background|bg|page|canvas)$/'  => 'neutral-0',
		'/(contrast|foreground|heading|title)/' => 'neutral-7',
		'/^(body|text|content)$/'               => 'neutral-5',
		'/^tertiary$/'                          => 'chromatic1-2',
		'/^quaternary$/'                        => 'chromatic2-2',
		'/(surface|card|panel)/'                => 'neutral-1',
		'/(border|outline|divider|stroke)/'     => 'neutral-2',
		'/(muted|subtle|neutral)/'              => 'neutral-4',
		'/^(primary|accent)$/'                  => 'chromatic1-7',
		'/^secondary$/'                         => 'chromatic1-5',
	);

	/**
	 * Optional per-theme font-family designation: theme => list of family slugs to
	 * treat as the HEADING font (mapped to the Style Guide heading pairing). Every
	 * other theme family slug defaults to the Style Guide BODY font.
	 *
	 * @since 1.0.0
	 * @var array<string, string[]>
	 */
	const FONT_FAMILY_HEADING_SLUGS = array(
		// Empty by default; extend per theme, keyed by stylesheet slug, with a list of
		// heading family slugs. Every other family slug falls back to the body font.
	);

	/**
	 * Font-family slugs that should resolve to a serif generic fallback.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	const SERIF_SLUGS = array( 'playfair-display', 'cormorant-garamond', 'lora', 'dm-serif-display', 'fraunces' );

	/**
	 * Whether the style override is enabled at all (option + filter), independent of
	 * theme or family.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function flag_enabled(): bool {
		$theme   = get_stylesheet();
		$enabled = (bool) get_option( 'spectra_blocks_theme_color_override', false );

		/**
		 * Filter whether the theme style override is enabled.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $enabled Whether the override runs.
		 * @param string $theme   The active theme stylesheet slug.
		 */
		return (bool) apply_filters( 'spectra_blocks_theme_color_override_enabled', $enabled, $theme );
	}

	/**
	 * Whether to override the active theme's COLOUR palette.
	 *
	 * Excludes themes with a dedicated colour path (Astra aliases, Spectra One
	 * compat) so we don't double-handle colour.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function should_override_color(): bool {
		if ( in_array( get_stylesheet(), self::DEDICATED_THEMES, true ) ) {
			return false;
		}
		return self::flag_enabled();
	}

	/**
	 * Whether to override the active theme's TYPOGRAPHY.
	 *
	 * Applies to EVERY theme (including Astra & Spectra One): unlike colour, there is
	 * no dedicated per-theme typography path, so nothing else makes the theme adopt
	 * the Style Guide fonts. Effective on FSE themes that expose theme.json
	 * `fontFamilies`; a no-op on themes that don't.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function should_override_typography(): bool {
		return self::flag_enabled();
	}

	/**
	 * Whether to override the active theme's SPACING scale. All themes.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function should_override_spacing(): bool {
		return self::flag_enabled();
	}

	/**
	 * Whether to override the active theme's SHADOW presets. All themes.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function should_override_shadow(): bool {
		return self::flag_enabled();
	}

	/* ───────────────────────────── Spacing family ───────────────────────────── */

	/**
	 * The Style Guide spacing scale, smallest → largest.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	const SPACING_SCALE = array( 'space-xs', 'space-sm', 'space-md', 'space-lg', 'space-xl', 'space-2xl' );

	/**
	 * Resolve theme spacing slug => Style Guide size overrides.
	 *
	 * Themes expose different-sized, differently-named spacing scales (named like
	 * `x-small…xx-large` or numeric like `20…80`), always listed smallest→largest.
	 * We map each theme step PROPORTIONALLY onto the Style Guide scale (first→space-xs,
	 * last→space-2xl), so it is count- and naming-agnostic across any theme.
	 *
	 * @since 1.0.0
	 *
	 * @param string[]      $theme_spacing_slugs Ordered theme spacing slugs.
	 * @param TokenRegistry $tokens              The computed token registry.
	 * @return array<string, string> slug => size value.
	 */
	public static function resolve_spacing_overrides( array $theme_spacing_slugs, TokenRegistry $tokens ): array {
		$slugs = array_values( $theme_spacing_slugs );
		$count = count( $slugs );
		if ( 0 === $count ) {
			return array();
		}

		$scale = self::SPACING_SCALE;
		$last  = count( $scale ) - 1;

		$out = array();
		foreach ( $slugs as $i => $slug ) {
			$idx   = ( 1 === $count ) ? $last : (int) round( $i * $last / ( $count - 1 ) );
			$value = $tokens->get( $scale[ $idx ] );
			if ( null !== $value && '' !== $value ) {
				$out[ $slug ] = $value;
			}
		}

		return $out;
	}

	/* ───────────────────────────── Shadow family ────────────────────────────── */

	/**
	 * The Style Guide shadow presets as theme.json shadow entries.
	 *
	 * The Style Guide owns the shadow scale (driven by presets.shadowDepth). Themes
	 * rarely ship their own shadow presets, so this both ADDS the Style Guide presets
	 * (surfacing them in the shadow picker + `--wp--preset--shadow--*`) and, when a
	 * theme does define presets, replaces them.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config The Style Guide config.
	 * @return list<array{slug:string, name:string, shadow:string}>
	 */
	public static function get_shadow_presets( array $config ): array {
		$presets = isset( $config['presets'] ) && is_array( $config['presets'] ) ? $config['presets'] : array();
		$depth   = ( isset( $presets['shadowDepth'] ) && is_string( $presets['shadowDepth'] ) ) ? $presets['shadowDepth'] : 'subtle';

		$out = array();
		foreach ( TokenRegistry::get_era_shadow_presets( $depth ) as $slug => $value ) {
			$out[] = array(
				'slug'   => (string) $slug,
				'name'   => ucwords( str_replace( '-', ' ', (string) $slug ) ),
				'shadow' => (string) $value,
			);
		}

		return $out;
	}

	/* ─────────────────────────── Colour family ─────────────────────────── */

	/**
	 * Resolve palette slug => hex colour overrides for the active theme.
	 *
	 * @since 1.0.0
	 *
	 * @param string[]             $palette_slugs The theme's registered palette slugs.
	 * @param TokenRegistry        $tokens        The computed token registry.
	 * @param array<string, mixed> $config The Style Guide config (for semantic_overrides).
	 * @return array<string, string> slug => hex.
	 */
	public static function resolve_color_overrides( array $palette_slugs, TokenRegistry $tokens, array $config ): array {
		$map = self::get_active_theme_color_map( $palette_slugs );

		if ( empty( $map ) ) {
			return array();
		}

		$semantic_overrides = ( isset( $config['semantic_overrides'] ) && is_array( $config['semantic_overrides'] ) )
			? $config['semantic_overrides']
			: array();

		$out = array();
		foreach ( $map as $slug => $token_key ) {
			if ( isset( $semantic_overrides[ $slug ] ) && '' !== $semantic_overrides[ $slug ] ) {
				$out[ $slug ] = $semantic_overrides[ $slug ];
				continue;
			}

			$hex = $tokens->get( $token_key );
			if ( null !== $hex && '' !== $hex ) {
				$out[ $slug ] = $hex;
			}
		}

		return $out;
	}

	/**
	 * Build the colour slug => token-key map for the active theme.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $palette_slugs The theme's registered palette slugs.
	 * @return array<string, string> slug => token key.
	 */
	private static function get_active_theme_color_map( array $palette_slugs ): array {
		$theme = get_stylesheet();

		/**
		 * Filter the per-theme colour palette-slug => token-key registry.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array<string, string>> $registry The colour registry.
		 */
		$registry = apply_filters( 'spectra_blocks_theme_color_map', self::COLOR_REGISTRY );
		if ( ! is_array( $registry ) ) {
			$registry = self::COLOR_REGISTRY;
		}

		if ( isset( $registry[ $theme ] ) && is_array( $registry[ $theme ] ) ) {
			return array_intersect_key( $registry[ $theme ], array_flip( $palette_slugs ) );
		}

		$map     = array();
		$skipped = array();
		foreach ( $palette_slugs as $slug ) {
			$token = self::color_heuristic( (string) $slug );
			if ( null !== $token ) {
				$map[ $slug ] = $token;
			} else {
				$skipped[] = $slug;
			}
		}

		if ( ! empty( $skipped ) ) {
			/**
			 * Fires with the palette slugs the heuristics could not map.
			 *
			 * @since 1.0.0
			 *
			 * @param string[] $skipped Unmapped slugs.
			 * @param string   $theme   Active theme stylesheet slug.
			 */
			do_action( 'spectra_blocks_theme_color_unmapped_slugs', $skipped, $theme );
		}

		return $map;
	}

	/**
	 * Resolve a single colour slug to a token key via heuristics.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Palette slug.
	 * @return string|null Token key, or null when no heuristic matches.
	 */
	private static function color_heuristic( string $slug ) {
		$s = strtolower( $slug );

		// Graded light "base" variants: base-2 -> next-lightest neutral, etc.
		if ( preg_match( '/^base-(\d+)$/', $s, $m ) ) {
			$by_index = array(
				2 => 'neutral-1',
				3 => 'neutral-2',
				4 => 'neutral-3',
			);
			return $by_index[ (int) $m[1] ] ?? 'neutral-1';
		}

		// Graded dark "contrast" variants: contrast-2 -> a step lighter than contrast, etc.
		if ( preg_match( '/^contrast-(\d+)$/', $s, $m ) ) {
			$by_index = array(
				2 => 'neutral-6',
				3 => 'neutral-5',
				4 => 'neutral-4',
			);
			return $by_index[ (int) $m[1] ] ?? 'neutral-6';
		}

		if ( preg_match( '/^accent-(\d+)$/', $s, $m ) ) {
			$by_index = array(
				1 => 'chromatic1-7',
				2 => 'chromatic1-5',
				3 => 'chromatic2-7',
				4 => 'chromatic3-7',
				5 => 'neutral-5',
				6 => 'neutral-2',
			);
			$n        = (int) $m[1];
			return $by_index[ $n ] ?? 'chromatic1-7';
		}

		foreach ( self::COLOR_HEURISTICS as $pattern => $token ) {
			if ( preg_match( $pattern, $s ) ) {
				return $token;
			}
		}

		return null;
	}

	/* ───────────────────────── Font-family family ───────────────────────── */

	/**
	 * Resolve theme font-family slug => CSS font-stack overrides.
	 *
	 * Every theme family slug maps to the Style Guide BODY font, except slugs the
	 * per-theme registry designates as headings, which map to the Style Guide HEADING
	 * font. (Headings are additionally forced via the theme's heading element styles.)
	 *
	 * @since 1.0.0
	 *
	 * @param string[]             $theme_font_slugs The theme's registered font-family slugs.
	 * @param array<string, mixed> $config The Style Guide config.
	 * @return array<string, string> slug => font stack.
	 */
	public static function resolve_font_family_overrides( array $theme_font_slugs, array $config ): array {
		$body    = self::get_body_font_stack( $config );
		$heading = self::get_heading_font_stack( $config );

		if ( '' === $body && '' === $heading ) {
			return array();
		}

		$theme = get_stylesheet();
		/**
		 * Per-theme heading-family registry (widened from the currently-empty constant).
		 *
		 * @var array<string, string[]> $heading_registry
		 */
		$heading_registry = self::FONT_FAMILY_HEADING_SLUGS;
		$heading_slugs    = isset( $heading_registry[ $theme ] ) ? $heading_registry[ $theme ] : array();

		$out = array();
		foreach ( $theme_font_slugs as $slug ) {
			if ( in_array( $slug, $heading_slugs, true ) ) {
				if ( '' !== $heading ) {
					$out[ $slug ] = $heading;
				}
			} elseif ( '' !== $body ) {
				$out[ $slug ] = $body;
			}
		}

		return $out;
	}

	/**
	 * The Style Guide body font stack.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config The Style Guide config.
	 * @return string CSS font stack, or '' when unset.
	 */
	public static function get_body_font_stack( array $config ): string {
		$typo = isset( $config['typography'] ) && is_array( $config['typography'] ) ? $config['typography'] : array();
		return self::build_font_stack( isset( $typo['body'] ) && is_array( $typo['body'] ) ? $typo['body'] : array() );
	}

	/**
	 * The Style Guide heading font stack.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $config The Style Guide config.
	 * @return string CSS font stack, or '' when unset.
	 */
	public static function get_heading_font_stack( array $config ): string {
		$typo = isset( $config['typography'] ) && is_array( $config['typography'] ) ? $config['typography'] : array();
		return self::build_font_stack( isset( $typo['heading'] ) && is_array( $typo['heading'] ) ? $typo['heading'] : array() );
	}

	/**
	 * Build a CSS font stack from a { name, slug } pairing.
	 *
	 * Mirrors GlobalStylesBridge::build_font_stack so stacks never diverge.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $entry { name, slug }.
	 * @return string e.g. "'DM Sans', sans-serif", or '' when invalid.
	 */
	private static function build_font_stack( array $entry ): string {
		if ( empty( $entry['name'] ) || ! is_scalar( $entry['name'] ) ) {
			return '';
		}
		$name = trim( (string) preg_replace( '/[^A-Za-z0-9 \-]/', '', (string) $entry['name'] ) );
		if ( '' === $name ) {
			return '';
		}
		$slug    = ( isset( $entry['slug'] ) && is_scalar( $entry['slug'] ) ) ? sanitize_key( (string) $entry['slug'] ) : '';
		$generic = in_array( $slug, self::SERIF_SLUGS, true ) ? 'serif' : 'sans-serif';
		return sprintf( "'%s', %s", $name, $generic );
	}
}
