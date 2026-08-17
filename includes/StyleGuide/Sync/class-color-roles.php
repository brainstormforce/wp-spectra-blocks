<?php
/**
 * Color Roles — the canonical, theme-independent vocabulary for color sync.
 *
 * The Style Guide and every theme speak different slug languages (Spectra One
 * `primary`/`body`, Twenty Twenty-Five `base`/`contrast`, Astra
 * `ast-global-color-N`). They share zero slug names, so the sync never maps a
 * variable to a variable — both sides map to this shared set of semantic ROLES.
 *
 * A per-theme mapping ({@see ThemeColorMapping}) translates a role to that
 * theme's slug; this class owns the role list itself and the two-way / push-only
 * classification.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.4
 */

namespace SpectraBlocks\StyleGuide\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ColorRoles
 *
 * Canonical role constants + brand / push-only classification.
 *
 * @since 1.0.4
 */
class ColorRoles {

	/**
	 * Brand roles — sync BOTH ways.
	 *
	 * These map cleanly to the Style Guide's three brand chromatics, so a
	 * theme-side edit can be reflected back into the Style Guide (Model B: the
	 * edited color re-seeds the chromatic base and the whole ramp regenerates).
	 */
	const PRIMARY   = 'primary';
	const SECONDARY = 'secondary';
	const ACCENT    = 'accent';

	/**
	 * Neutral / semantic roles — PUSH-ONLY (Style Guide → theme).
	 *
	 * Derived shades and semantics have no clean 1:1 inverse into the Style
	 * Guide's generative color model, so a theme-side edit of these is not synced
	 * back (yet). Extended after each case is verified.
	 */
	const PAGE_BACKGROUND = 'page-background';
	const SURFACE         = 'surface';
	const BODY_TEXT       = 'body-text';
	const HEADING_TEXT    = 'heading-text';
	const LINK            = 'link';
	const BORDER          = 'border';
	const MUTED           = 'muted';
	const FOREGROUND      = 'foreground';

	/**
	 * Brand roles (two-way sync).
	 *
	 * @since 1.0.4
	 * @var string[]
	 */
	const BRAND = array(
		self::PRIMARY,
		self::SECONDARY,
		self::ACCENT,
	);

	/**
	 * Neutral / semantic roles (push-only).
	 *
	 * @since 1.0.4
	 * @var string[]
	 */
	const NEUTRAL = array(
		self::PAGE_BACKGROUND,
		self::SURFACE,
		self::BODY_TEXT,
		self::HEADING_TEXT,
		self::LINK,
		self::BORDER,
		self::MUTED,
		self::FOREGROUND,
	);

	/**
	 * Role → Style Guide token key that SOURCES its color on push (SG → theme).
	 *
	 * Theme-independent: the canonical Style Guide shade each role is drawn from.
	 * Uses the TokenRegistry key format (e.g. `primary`, `neutral-0`), the
	 * same one {@see \SpectraBlocks\StyleGuide\GlobalStylesBridge::astra_shade_map()}
	 * resolves. Brand keys equal `chromatics[N].hex` exactly.
	 *
	 * @since 1.0.4
	 * @var array<string, string>
	 */
	const SG_TOKEN = array(
		self::PRIMARY         => 'primary',
		self::SECONDARY       => 'secondary',
		self::ACCENT          => 'accent',
		self::PAGE_BACKGROUND => 'neutral-0',
		self::SURFACE         => 'neutral-1',
		self::BODY_TEXT       => 'neutral-5',
		self::HEADING_TEXT    => 'neutral-7',
		self::LINK            => 'primary',
		self::BORDER          => 'neutral-2',
		self::MUTED           => 'neutral-4',
		self::FOREGROUND      => 'foreground',
	);

	/**
	 * All canonical roles.
	 *
	 * @since 1.0.4
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array_merge( self::BRAND, self::NEUTRAL );
	}

	/**
	 * Brand roles (two-way).
	 *
	 * @since 1.0.4
	 *
	 * @return string[]
	 */
	public static function brand_roles(): array {
		return self::BRAND;
	}

	/**
	 * Whether a role is a brand role (i.e. eligible for reverse / two-way sync).
	 *
	 * @since 1.0.4
	 *
	 * @param string $role Role constant.
	 * @return bool
	 */
	public static function is_brand( string $role ): bool {
		return in_array( $role, self::BRAND, true );
	}

	/**
	 * Whether a string is a known canonical role.
	 *
	 * @since 1.0.4
	 *
	 * @param string $role Candidate role.
	 * @return bool
	 */
	public static function is_valid( string $role ): bool {
		return in_array( $role, self::all(), true );
	}

	/**
	 * The Style Guide token key that sources a role's color, or null if unknown.
	 *
	 * @since 1.0.4
	 *
	 * @param string $role Role constant.
	 * @return string|null
	 */
	public static function sg_token( string $role ): ?string {
		return self::SG_TOKEN[ $role ] ?? null;
	}
}
