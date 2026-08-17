<?php
/**
 * ColorModel — the v2 colour SSOT (registry).
 *
 * The v2 Style Guide stores only nine user-selectable colours (`config['colors']`,
 * slug => hex). Everything else — the brand shade ramps, the full neutral ramp,
 * the status colours, and the slug→token semantic map — is *generated* from those
 * nine plus the constants declared here. This class is that single source of
 * truth; it holds no state and is safe to read anywhere.
 *
 * It replaces the previously-stored `semantic_map` (now a code constant) and the
 * index-keyed `chromatics`/`neutral`-tint model (now the slug-keyed `colors`).
 *
 * @package Spectra\StyleGuide
 * @since   1.0.4
 */

namespace SpectraBlocks\StyleGuide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ColorModel
 *
 * @since 1.0.4
 */
class ColorModel {

	/**
	 * The nine user-selectable core roles — the ONLY colours stored in
	 * `config['colors']`. Each declares how it feeds the generated token set:
	 *   - brand   → seeds chromatic ramp N; the role resolves to `chromaticN-7`.
	 *   - neutral → is an anchor at ramp `stop`; the role resolves to `neutral-{stop}`.
	 *
	 * @since 1.0.4
	 * @var array<string, array{kind: string, token: string, chromatic?: int, stop?: int}>
	 */
	const CORE_ROLES = array(
		'primary'    => array(
			'kind'      => 'brand',
			'chromatic' => 1,
			'token'     => 'primary',
		),
		'secondary'  => array(
			'kind'      => 'brand',
			'chromatic' => 2,
			'token'     => 'secondary',
		),
		'accent'     => array(
			'kind'      => 'brand',
			'chromatic' => 3,
			'token'     => 'accent',
		),
		'heading'    => array(
			'kind'  => 'neutral',
			'stop'  => 7,
			'token' => 'neutral-7',
		),
		'body'       => array(
			'kind'  => 'neutral',
			'stop'  => 5,
			'token' => 'neutral-5',
		),
		'neutral'    => array(
			'kind'  => 'neutral',
			'stop'  => 4,
			'token' => 'neutral-4',
		),
		'outline'    => array(
			'kind'  => 'neutral',
			'stop'  => 2,
			'token' => 'neutral-2',
		),
		'surface'    => array(
			'kind'  => 'neutral',
			'stop'  => 1,
			'token' => 'neutral-1',
		),
		'background' => array(
			'kind'  => 'neutral',
			'stop'  => 0,
			'token' => 'neutral-0',
		),
		// Standalone: neither a brand ramp seed nor a neutral stop, so it carries its
		// own token rather than borrowing one. Foreground is the colour that sits ON a
		// coloured surface (button labels, icons on a filled background). It is STORED
		// rather than derived so it can round-trip with themes that expose a matching
		// palette slug — Spectra One ships `foreground`, and while this was a derived
		// variable a Style Guide save silently repainted that swatch.
		'foreground' => array(
			'kind'  => 'standalone',
			'token' => 'foreground',
		),
	);

	/**
	 * Status colours — fixed, brand-agnostic defaults. Generated (chromatics 4-7),
	 * not stored. No forward UI writes these today (see audit §3.4).
	 *
	 * @since 1.0.4
	 * @var array<string, array{hex: string, chromatic: int, token: string}>
	 */
	const STATUS_COLORS = array(
		'success' => array(
			'hex'       => '#10b981',
			'chromatic' => 4,
			'token'     => 'success',
		),
		'error'   => array(
			'hex'       => '#ef4444',
			'chromatic' => 5,
			'token'     => 'error',
		),
		'info'    => array(
			'hex'       => '#8b5cf6',
			'chromatic' => 6,
			'token'     => 'info',
		),
		'warning' => array(
			'hex'       => '#d97706',
			'chromatic' => 7,
			'token'     => 'warning',
		),
	);

	/**
	 * Chromatic index (1-7) → semantic token slug. The single source of truth for
	 * the seven brand/status seed token names, shared by the token emitter
	 * ({@see Engine::compute()}) and the utility-class generator
	 * ({@see \Spectra\GlobalStyles\ClassRegistry}) so the emitted CSS variable and
	 * the `var(--spectra-…)` reference in class bodies can never drift.
	 *
	 * Replaces the legacy `chromaticN-7` keys: brand 1-3 = primary/secondary/accent,
	 * status 4-7 = success/error/info/warning.
	 *
	 * @since 1.0.4
	 * @var array<int, string>
	 */
	const CHROMATIC_SLUG = array(
		1 => 'primary',
		2 => 'secondary',
		3 => 'accent',
		4 => 'success',
		5 => 'error',
		6 => 'info',
		7 => 'warning',
	);

	/**
	 * The semantic token slug for a chromatic index (1-7), or '' when out of range.
	 *
	 * @since 1.0.4
	 *
	 * @param int $index Chromatic index.
	 * @return string Token slug (e.g. `primary`, `success`).
	 */
	public static function chromatic_token( $index ) {
		return self::CHROMATIC_SLUG[ $index ] ?? '';
	}

	/**
	 * Brand colour defaults for a fresh install (the 3 seeds). Neutral defaults are
	 * NOT hardcoded — `Engine::get_default_config()` derives them once from these so
	 * a fresh site matches the legacy tinted-neutral ramp exactly.
	 *
	 * @since 1.0.4
	 * @var array<string, string>
	 */
	const DEFAULT_BRAND = array(
		'primary'   => '#6431f6',
		'secondary' => '#7345f7',
		'accent'    => '#f59e0b',
	);

	/**
	 * The semantic slug → shade-token map. Formerly stored per-site as
	 * `config['semantic_map']`; now a code constant (the new UI never remaps).
	 * Read by the bridge, the theme adapters, and the preview CSS.
	 *
	 * Covers: the 9 core roles, the extended roles (tertiary/quaternary/foreground),
	 * the status roles, and the `sg-*` Astra-compat family.
	 *
	 * @since 1.0.4
	 * @var array<string, string>
	 */
	const SEMANTIC_MAP = array(
		// Core roles (the 10).
		'primary'       => 'primary',
		'secondary'     => 'secondary',
		'accent'        => 'accent',
		'heading'       => 'neutral-7',
		'body'          => 'neutral-5',
		'background'    => 'neutral-0',
		'surface'       => 'neutral-1',
		'outline'       => 'neutral-2',
		'neutral'       => 'neutral-4',
		// Resolves to its OWN token now that it is a stored role. It previously
		// pointed at `neutral-7` while the derived-variable layer overrode it with a
		// contrast-derived value, so the slug resolved to neither — and on a theme
		// that ships a `foreground` swatch (Spectra One) that value silently replaced
		// the theme's own.
		'foreground'    => 'foreground',
		// Status.
		'success'       => 'success',
		'error'         => 'error',
		'info'          => 'info',
		'warning'       => 'warning',
		// sg-* Astra-compat family.
		'sg-accent'     => 'accent',
		'sg-heading'    => 'neutral-7',
		'sg-body'       => 'neutral-5',
		'sg-surface'    => 'neutral-1',
		'sg-background' => 'neutral-0',
		'sg-border'     => 'neutral-2',
		'sg-muted'      => 'neutral-4',
	);

	/**
	 * The nine core role slugs, in display order.
	 *
	 * @since 1.0.4
	 *
	 * @return string[] Slugs.
	 */
	public static function core_slugs() {
		return array_keys( self::CORE_ROLES );
	}

	/**
	 * Is this slug one of the nine core roles?
	 *
	 * @since 1.0.4
	 *
	 * @param string $slug Slug.
	 * @return bool True when core.
	 */
	public static function is_core_slug( $slug ) {
		return isset( self::CORE_ROLES[ $slug ] );
	}

	/**
	 * Brand role slug → chromatic index (1-3).
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, int> Map.
	 */
	public static function brand_chromatic_map() {
		$map = array();
		foreach ( self::CORE_ROLES as $slug => $def ) {
			if ( 'brand' === $def['kind'] ) {
				$map[ $slug ] = $def['chromatic'];
			}
		}
		return $map;
	}

	/**
	 * Standalone role slug → its token name.
	 *
	 * Roles that are neither a brand ramp seed nor a neutral stop, so the emitter has
	 * no ramp to place them on and sets their token directly from the stored hex.
	 *
	 * @since 1.0.5
	 *
	 * @return array<string, string> slug => token.
	 */
	public static function standalone_map() {
		$map = array();
		foreach ( self::CORE_ROLES as $slug => $def ) {
			if ( 'standalone' === $def['kind'] ) {
				$map[ $slug ] = $def['token'];
			}
		}
		return $map;
	}

	/**
	 * Neutral ramp stop (0-7) → role slug, for the six neutral anchors.
	 *
	 * @since 1.0.4
	 *
	 * @return array<int, string> Map (keys 0,1,2,4,5,7).
	 */
	public static function neutral_anchor_map() {
		$map = array();
		foreach ( self::CORE_ROLES as $slug => $def ) {
			if ( 'neutral' === $def['kind'] ) {
				$map[ $def['stop'] ] = $slug;
			}
		}
		return $map;
	}

	/**
	 * The semantic slug → token map (replaces the stored `semantic_map`).
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, string> Map.
	 */
	public static function semantic_map() {
		return self::SEMANTIC_MAP;
	}

	/**
	 * The core role slug that owns a given shade token, or null when no core role
	 * maps to it. Used by the reverse sync to turn a theme-side token edit back
	 * into a `colors[slug]` write.
	 *
	 * @since 1.0.4
	 *
	 * @param string $token Shade token, e.g. `neutral-5` or `chromatic1-7`.
	 * @return string|null Core role slug, or null.
	 */
	public static function slug_for_token( $token ) {
		foreach ( self::CORE_ROLES as $slug => $def ) {
			if ( $def['token'] === $token ) {
				return $slug;
			}
		}
		return null;
	}

	/**
	 * The default nine colours — THE single PHP source for every colour default.
	 *
	 * Brand seeds come from {@see self::DEFAULT_BRAND}. The six neutral defaults
	 * are fixed literals — the exact output of the retired OKLCH tinted-ramp
	 * derivation (primary hue at 0.05 strength), frozen so a fresh site keeps the
	 * same default look without any generation code. Status colour defaults live
	 * in {@see self::STATUS_COLORS}. Anything needing a default colour must read
	 * it from this class.
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, string> slug => hex for all ten core roles.
	 */
	public static function default_colors() {
		return self::DEFAULT_BRAND + array(
			'background' => '#ffffff',
			'surface'    => '#f0f1f1',
			'outline'    => '#d4d5d8',
			'neutral'    => '#767884',
			'body'       => '#464757',
			'heading'    => '#09081b',
			// White clears 4.5:1 on the default Primary, so this literal equals what
			// the editor's AUTO rule derives for a fresh install — the role became
			// stored without changing the colour any existing site renders.
			'foreground' => '#ffffff',
		);
	}
}
