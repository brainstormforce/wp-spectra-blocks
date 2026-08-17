<?php
/**
 * Mapping Resolver — produces the role → slug {@see ThemeColorMapping} for a theme.
 *
 * Resolution order (first hit wins):
 *   1. Curated profile   — hand-verified table keyed by the theme stylesheet.
 *   2. Auto-derive       — infer role → slug from the theme's own `styles` usage
 *                          (FSE only; added in a later phase — currently a no-op).
 *   3. Stored / manual   — a persisted override (manual mapping UI; later phase).
 *
 * The result is filterable so the stored/manual tier and third parties can
 * override without touching this class.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.4
 */

namespace SpectraBlocks\StyleGuide\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MappingResolver
 *
 * @since 1.0.4
 */
class MappingResolver {

	/**
	 * Curated per-theme profiles: stylesheet => ( role => slug|null ).
	 *
	 * Hand-verified against each theme's palette + `styles` usage. Only slugs
	 * that exist in the theme's palette are listed; a role absent here (or set to
	 * null) is simply not synced for that theme.
	 *
	 * Brand roles (primary/secondary/accent) are the only two-way roles, so each
	 * brand role is deliberately given a DISTINCT slug per theme to keep reverse
	 * sync unambiguous.
	 *
	 * @since 1.0.4
	 * @var array<string, array<string, string|null>>
	 */
	const CURATED = array(
		// Spectra One — native slugs map 1:1. `link` is intentionally omitted:
		// the theme's own styles already route links through `primary`, so
		// mapping it would double-book the `primary` slug.
		'spectra-one'      => array(
			ColorRoles::PRIMARY         => 'primary',
			// The `secondary` slug now carries the Style Guide's distinct Secondary
			// brand (chromatic2) — semantic_map + liveVars are aligned to
			// chromatic2-7 so push, runtime overlay and live preview all agree.
			ColorRoles::SECONDARY       => 'secondary',
			// `accent` is injected by Spectra and already resolves to chromatic3-7
			// (the SG Accent brand) in semantic_map + liveVars, so mapping it here
			// simply brings it into the two-way sync — no repoint needed.
			ColorRoles::ACCENT          => 'accent',
			ColorRoles::PAGE_BACKGROUND => 'background',
			ColorRoles::SURFACE         => 'surface',
			ColorRoles::BODY_TEXT       => 'body',
			ColorRoles::HEADING_TEXT    => 'heading',
			ColorRoles::BORDER          => 'outline',
			ColorRoles::MUTED           => 'neutral',
			// The theme ships its own `foreground` swatch. Mapping it makes the pair
			// two-way, so the Style Guide inherits the theme's value instead of
			// repainting it on the first save.
			ColorRoles::FOREGROUND      => 'foreground',
		),

		// Twenty Twenty-Five — `contrast` is dual-use (body text AND button bg).
		// Brand roles map to the decorative `accent-*` colors (the theme's actual
		// brand-ish hues) so a brand push does NOT recolor body text. `contrast`
		// is left as body-text (push-only); the button/action tint is not synced.
		'twentytwentyfive' => array(
			ColorRoles::PRIMARY         => 'accent-3', // #503AA8 — most saturated / brand-like.
			ColorRoles::SECONDARY       => 'accent-2', // #F6CFF4.
			ColorRoles::ACCENT          => 'accent-1', // #FFEE58.
			ColorRoles::PAGE_BACKGROUND => 'base',
			ColorRoles::SURFACE         => 'accent-5', // #FBFAF3 off-white.
			ColorRoles::BODY_TEXT       => 'contrast',
			ColorRoles::BORDER          => 'accent-4', // #686868.
			ColorRoles::HEADING_TEXT    => null,
			ColorRoles::LINK            => null,
			ColorRoles::MUTED           => null,
			ColorRoles::FOREGROUND      => null,
		),

		// Astra — hybrid, index-based (`--ast-global-color-{N}`, slugs
		// `ast-global-color-{N}`). Slot semantics per Astra's own model:
		// 0 Primary, 1 Secondary, 2 Heading, 3 Body, 4 Section bg, 5 Page bg,
		// 6 Border, 7 Dark bg, 8 Extra. Brand roles map to the true brand slots
		// (0/1); accent has no Astra slot. Slots 7/8 are left theme-controlled
		// (no matching role) and preserved on write.
		// Astra: PUSH (all 9 slots, flag-aware) is owned by
		// AstraPaletteAdapter::resolve_patch(); this profile only drives the
		// brand-only REVERSE sync, so it maps just the two brand slots (0 = Brand,
		// 1 = Alternate Brand — indices that are never swapped by the reorganize
		// flag). Everything else is push-only and intentionally unmapped here.
		'astra'            => array(
			ColorRoles::PRIMARY         => 'ast-global-color-0',
			ColorRoles::SECONDARY       => 'ast-global-color-1',
			ColorRoles::ACCENT          => null,
			ColorRoles::HEADING_TEXT    => null,
			ColorRoles::BODY_TEXT       => null,
			ColorRoles::SURFACE         => null,
			ColorRoles::PAGE_BACKGROUND => null,
			ColorRoles::BORDER          => null,
			ColorRoles::LINK            => null,
			ColorRoles::MUTED           => null,
			// No Astra global-colour slot corresponds to it.
			ColorRoles::FOREGROUND      => null,
		),
	);

	/**
	 * Resolve the mapping for the active theme.
	 *
	 * @since 1.0.4
	 *
	 * @return ThemeColorMapping
	 */
	public static function for_active_theme(): ThemeColorMapping {
		return self::for_theme( (string) get_stylesheet() );
	}

	/**
	 * Resolve the mapping for a given theme stylesheet.
	 *
	 * @since 1.0.4
	 *
	 * @param string $stylesheet Theme stylesheet (directory) slug.
	 * @return ThemeColorMapping
	 */
	public static function for_theme( string $stylesheet ): ThemeColorMapping {
		// Tier 1: curated profile for this exact stylesheet.
		$map = self::CURATED[ $stylesheet ] ?? array();

		// Tier 1b: child theme -> fall back to the PARENT's curated profile. A
		// child inherits the parent's palette slugs, so the hand-verified parent
		// map is more precise than deriving the child.
		if ( empty( $map ) && function_exists( 'wp_get_theme' ) ) {
			$parent = (string) wp_get_theme( $stylesheet )->get_template();
			if ( '' !== $parent && $parent !== $stylesheet && isset( self::CURATED[ $parent ] ) ) {
				$map = self::CURATED[ $parent ];
			}
		}

		// Tier 2: auto-derive from the theme's styles usage (active theme only;
		// an unlisted theme with no derivable data yields an empty mapping, which
		// simply means sync is unavailable — never an error).
		if ( empty( $map ) ) {
			$map = self::auto_derive( $stylesheet );
		}

		/**
		 * Filter the resolved role => slug map for a theme.
		 *
		 * The stored/manual override tier and third parties hook here to add or
		 * correct a mapping without editing curated data.
		 *
		 * @since 1.0.4
		 *
		 * @param array<string, string|null> $map        role => slug|null.
		 * @param string                      $stylesheet Theme stylesheet slug.
		 */
		$map = apply_filters( 'spectra_style_guide_theme_color_mapping', $map, $stylesheet );

		return new ThemeColorMapping( is_array( $map ) ? $map : array() );
	}

	/**
	 * Whether a hand-verified curated profile exists for a theme.
	 *
	 * @since 1.0.4
	 *
	 * @param string $stylesheet Theme stylesheet slug.
	 * @return bool
	 */
	public static function has_curated( string $stylesheet ): bool {
		return isset( self::CURATED[ $stylesheet ] );
	}

	/**
	 * Where a role's slug is READ from in the theme's `styles` tree.
	 *
	 * `primary` is derived separately (button background) because it is a brand
	 * role and must be proven distinct from the text/background slugs first.
	 *
	 * @since 1.0.4
	 * @var array<string, string[]>
	 */
	const DERIVE_PATHS = array(
		ColorRoles::PAGE_BACKGROUND => array( 'color', 'background' ),
		ColorRoles::BODY_TEXT       => array( 'color', 'text' ),
		ColorRoles::HEADING_TEXT    => array( 'elements', 'heading', 'color', 'text' ),
		ColorRoles::LINK            => array( 'elements', 'link', 'color', 'text' ),
		ColorRoles::BORDER          => array( 'blocks', 'core/separator', 'color', 'background' ),
	);

	/**
	 * In-request memo of derived maps, keyed by stylesheet.
	 *
	 * @since 1.0.4
	 * @var array<string, array<string, string|null>>
	 */
	private static $derive_memo = array();

	/**
	 * Auto-derive role => slug from the ACTIVE theme's `styles` usage.
	 *
	 * Reads which palette slug each visual role uses (page background, body text,
	 * heading, link, separator/border) and infers `primary` from the button
	 * background — but only when that slug is distinct from every push-only slug,
	 * so a brand push can never recolor body text. Each slug is assigned to at
	 * most one role. `secondary`/`accent` are not usage-inferable and stay null.
	 *
	 * Only the active theme can be derived (its theme.json is the loaded one);
	 * any other stylesheet yields an empty map.
	 *
	 * @since 1.0.4
	 *
	 * @param string $stylesheet Theme stylesheet slug.
	 * @return array<string, string|null>
	 */
	private static function auto_derive( string $stylesheet ): array {
		if ( isset( self::$derive_memo[ $stylesheet ] ) ) {
			return self::$derive_memo[ $stylesheet ];
		}

		$map = array();
		if ( (string) get_stylesheet() === $stylesheet && class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			$raw     = \WP_Theme_JSON_Resolver::get_theme_data()->get_raw_data();
			$styles  = ( isset( $raw['styles'] ) && is_array( $raw['styles'] ) ) ? $raw['styles'] : array();
			$palette = self::theme_palette_slugs( $raw );
			$map     = self::derive_from_styles( $styles, $palette );
		}

		self::$derive_memo[ $stylesheet ] = $map;
		return $map;
	}

	/**
	 * Set of palette slugs defined by the theme (for validating derived slugs).
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, mixed> $raw theme.json raw data.
	 * @return array<string, true>
	 */
	private static function theme_palette_slugs( array $raw ): array {
		$settings = ( isset( $raw['settings'] ) && is_array( $raw['settings'] ) ) ? $raw['settings'] : array();
		$color    = ( isset( $settings['color'] ) && is_array( $settings['color'] ) ) ? $settings['color'] : array();
		$palette  = ( isset( $color['palette'] ) && is_array( $color['palette'] ) ) ? $color['palette'] : array();
		$entries  = ( isset( $palette['theme'] ) && is_array( $palette['theme'] ) )
			? $palette['theme']
			: ( isset( $palette[0] ) && is_array( $palette ) ? $palette : array() );

		$slugs = array();
		foreach ( $entries as $entry ) {
			if ( is_array( $entry ) && isset( $entry['slug'] ) && is_string( $entry['slug'] ) ) {
				$slugs[ $entry['slug'] ] = true;
			}
		}
		return $slugs;
	}

	/**
	 * Build the derived role => slug map from a styles tree.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, mixed> $styles  theme.json `styles` tree.
	 * @param array<string, true>  $palette Valid theme palette slugs.
	 * @return array<string, string|null>
	 */
	private static function derive_from_styles( array $styles, array $palette ): array {
		// Raw usage: role => slug (validated to exist in the palette).
		$raw = array();
		foreach ( self::DERIVE_PATHS as $role => $path ) {
			$slug = self::preset_slug( self::dig( $styles, $path ) );
			if ( null !== $slug && isset( $palette[ $slug ] ) ) {
				$raw[ $role ] = $slug;
			}
		}

		// Brand: button background. Accept only if the slug is NOT already used by
		// a role whose Style Guide source color differs — i.e. sharing with `link`
		// (same source as primary) is fine, but sharing with body-text/background
		// (different color) would tint them on push, so primary is skipped there.
		$button = self::preset_slug( self::dig( $styles, array( 'elements', 'button', 'color', 'background' ) ) );
		if ( null !== $button && isset( $palette[ $button ] ) ) {
			$primary_token = ColorRoles::sg_token( ColorRoles::PRIMARY );
			$conflict      = false;
			foreach ( $raw as $role => $slug ) {
				if ( $slug === $button && ColorRoles::sg_token( $role ) !== $primary_token ) {
					$conflict = true;
					break;
				}
			}
			if ( ! $conflict ) {
				$raw[ ColorRoles::PRIMARY ] = $button;
			}
		}

		// Dedupe: each slug to at most one role (priority order below).
		$priority = array(
			ColorRoles::PRIMARY,
			ColorRoles::PAGE_BACKGROUND,
			ColorRoles::BODY_TEXT,
			ColorRoles::HEADING_TEXT,
			ColorRoles::BORDER,
			ColorRoles::LINK,
		);
		$out      = array();
		$seen     = array();
		foreach ( $priority as $role ) {
			if ( ! isset( $raw[ $role ] ) ) {
				continue;
			}
			$slug = $raw[ $role ];
			if ( ! isset( $seen[ $slug ] ) ) {
				$out[ $role ]  = $slug;
				$seen[ $slug ] = true;
			}
		}
		return $out;
	}

	/**
	 * Read a nested value from an array by path.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, mixed> $arr  Source array.
	 * @param string[]             $path Key path.
	 * @return mixed|null
	 */
	private static function dig( array $arr, array $path ) {
		$node = $arr;
		foreach ( $path as $key ) {
			if ( ! is_array( $node ) || ! isset( $node[ $key ] ) ) {
				return null;
			}
			$node = $node[ $key ];
		}
		return $node;
	}

	/**
	 * Extract a preset color slug from a styles value, or null.
	 *
	 * Handles both theme.json syntaxes:
	 *   - v2 CSS:   `var(--wp--preset--color--base)`
	 *   - v3 short: `var:preset|color|base`
	 * Literal colors / non-preset references (`currentColor`, `#fff`) yield null.
	 *
	 * @since 1.0.4
	 *
	 * @param mixed $value Styles value.
	 * @return string|null
	 */
	private static function preset_slug( $value ): ?string {
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		if ( preg_match( '/var\(--wp--preset--color--([a-z0-9-]+)\)/i', $value, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '/var:preset\|color\|([a-z0-9_-]+)/i', $value, $m ) ) {
			return $m[1];
		}
		return null;
	}
}
