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
			'accent-1' => 'primary',
			// accent-2 (a primary tint in TT25) is unmanaged — tint shades are no
			// longer generated, so the theme keeps its own value for that slug.
			'accent-3' => 'secondary',
			'accent-4' => 'accent',
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
		'/^(base|background|bg|page|canvas)$/' => 'neutral-0',
		// Exact `foreground` now has a role of its own, so it takes the stored value
		// rather than the heading colour. Must precede the heading pattern below,
		// which is unanchored and would otherwise swallow it.
		'/^foreground$/'                       => 'foreground',
		'/(contrast|heading|title)/'           => 'neutral-7',
		'/^(body|text|content)$/'              => 'neutral-5',
		'/(surface|card|panel)/'               => 'neutral-1',
		'/(border|outline|divider|stroke)/'    => 'neutral-2',
		'/(muted|subtle|neutral)/'             => 'neutral-4',
		'/^(primary|accent)$/'                 => 'primary',
		'/^secondary$/'                        => 'secondary',
	);

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
		// Note: base-4 previously mapped to the interpolated neutral-3, which is no
		// longer generated — it now falls back to neutral-2.
		if ( preg_match( '/^base-(\d+)$/', $s, $m ) ) {
			$by_index = array(
				2 => 'neutral-1',
				3 => 'neutral-2',
				4 => 'neutral-2',
			);
			return $by_index[ (int) $m[1] ] ?? 'neutral-1';
		}

		// Graded dark "contrast" variants: contrast-2 -> a step lighter than contrast, etc.
		// Note: contrast-2 previously mapped to the interpolated neutral-6 — now neutral-5.
		if ( preg_match( '/^contrast-(\d+)$/', $s, $m ) ) {
			$by_index = array(
				2 => 'neutral-5',
				3 => 'neutral-5',
				4 => 'neutral-4',
			);
			return $by_index[ (int) $m[1] ] ?? 'neutral-5';
		}

		if ( preg_match( '/^accent-(\d+)$/', $s, $m ) ) {
			// accent-2 (a brand tint) is unmanaged — tint shades are no longer
			// generated, so that slug keeps the theme's own value.
			$by_index = array(
				1 => 'primary',
				3 => 'secondary',
				4 => 'accent',
				5 => 'neutral-5',
				6 => 'neutral-2',
			);
			$n        = (int) $m[1];
			if ( 2 === $n ) {
				return null;
			}
			return $by_index[ $n ] ?? 'primary';
		}

		foreach ( self::COLOR_HEURISTICS as $pattern => $token ) {
			if ( preg_match( $pattern, $s ) ) {
				return $token;
			}
		}

		return null;
	}
}
