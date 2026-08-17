<?php
/**
 * Theme Color Mapping — an immutable role → slug translation for one theme.
 *
 * The only theme-specific knowledge in the sync system. Produced by
 * {@see MappingResolver} (curated profile → auto-derive → stored override) and
 * consumed by the adapters to translate canonical {@see ColorRoles} to/from the
 * active theme's own palette slugs.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.4
 */

namespace SpectraBlocks\StyleGuide\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ThemeColorMapping
 *
 * Value object: `role => slug` (slug nullable) with a reverse `slug => role[]`
 * lookup. A `null` slug means "this theme has no color for that role" — the
 * sync skips it, it is never an error.
 *
 * @since 1.0.4
 */
class ThemeColorMapping {

	/**
	 * Forward map: role => slug|null.
	 *
	 * @since 1.0.4
	 * @var array<string, string|null>
	 */
	private $map;

	/**
	 * Reverse map: slug => role[] (a slug may serve more than one role).
	 *
	 * @since 1.0.4
	 * @var array<string, string[]>
	 */
	private $inverse;

	/**
	 * Constructor.
	 *
	 * Unknown roles are dropped; blank slugs are normalized to null so callers
	 * can treat "unmapped" uniformly.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, string|null> $map role => slug|null.
	 */
	public function __construct( array $map ) {
		$this->map     = array();
		$this->inverse = array();

		foreach ( $map as $role => $slug ) {
			if ( ! ColorRoles::is_valid( (string) $role ) ) {
				continue;
			}
			$slug = ( null === $slug || '' === $slug ) ? null : (string) $slug;

			$this->map[ $role ] = $slug;
			if ( null !== $slug ) {
				$this->inverse[ $slug ][] = (string) $role;
			}
		}
	}

	/**
	 * The theme slug a role maps to, or null when this theme has no such color.
	 *
	 * @since 1.0.4
	 *
	 * @param string $role Canonical role.
	 * @return string|null
	 */
	public function slug_for( string $role ): ?string {
		return $this->map[ $role ] ?? null;
	}

	/**
	 * Whether a role maps to a real slug in this theme.
	 *
	 * @since 1.0.4
	 *
	 * @param string $role Canonical role.
	 * @return bool
	 */
	public function has_role( string $role ): bool {
		return null !== ( $this->map[ $role ] ?? null );
	}

	/**
	 * All roles a slug serves (reverse lookup). Empty if the slug is unmapped.
	 *
	 * @since 1.0.4
	 *
	 * @param string $slug Theme palette slug.
	 * @return string[]
	 */
	public function roles_for( string $slug ): array {
		return $this->inverse[ $slug ] ?? array();
	}

	/**
	 * The single role a slug serves, or null when unmapped OR ambiguous
	 * (serves more than one role). Ambiguous slugs are intentionally not
	 * resolved here — reverse sync skips them until a priority policy is set.
	 *
	 * @since 1.0.4
	 *
	 * @param string $slug Theme palette slug.
	 * @return string|null
	 */
	public function unambiguous_role_for( string $slug ): ?string {
		$roles = $this->roles_for( $slug );
		return 1 === count( $roles ) ? $roles[0] : null;
	}

	/**
	 * Whether a slug serves more than one role (reverse sync is unsafe).
	 *
	 * @since 1.0.4
	 *
	 * @param string $slug Theme palette slug.
	 * @return bool
	 */
	public function is_ambiguous( string $slug ): bool {
		return count( $this->roles_for( $slug ) ) > 1;
	}

	/**
	 * The single BRAND role a slug serves, or null.
	 *
	 * This is what reverse sync uses: only brand roles are two-way, so a slug
	 * shared between a brand role and a push-only role (e.g. Spectra One
	 * `primary`, which also backs links) resolves cleanly to the brand role.
	 * Returns null when zero — or more than one — brand roles claim the slug
	 * (a genuine brand collision stays push-only until a priority policy exists).
	 *
	 * @since 1.0.4
	 *
	 * @param string $slug Theme palette slug.
	 * @return string|null
	 */
	public function brand_role_for( string $slug ): ?string {
		$brand = array();
		foreach ( $this->roles_for( $slug ) as $role ) {
			if ( ColorRoles::is_brand( $role ) ) {
				$brand[] = $role;
			}
		}
		return 1 === count( $brand ) ? $brand[0] : null;
	}

	/**
	 * Roles that map to a real slug (non-null), in canonical order.
	 *
	 * @since 1.0.4
	 *
	 * @return string[]
	 */
	public function mapped_roles(): array {
		$out = array();
		foreach ( ColorRoles::all() as $role ) {
			if ( $this->has_role( $role ) ) {
				$out[] = $role;
			}
		}
		return $out;
	}

	/**
	 * The raw forward map (role => slug|null), for inspection / persistence.
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, string|null>
	 */
	public function to_array(): array {
		return $this->map;
	}
}
