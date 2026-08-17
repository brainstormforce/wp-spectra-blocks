<?php
/**
 * Astra Palette Adapter — reads/writes Astra's hybrid global color palette.
 *
 * Astra's `theme.json` only forwards to `--ast-global-color-N`; the real colors
 * live in the `astra-settings` option under `global-color-palette` as a 9-slot
 * indexed array per palette (`palette_1..palette_4`). This adapter speaks the
 * shared slug convention `ast-global-color-{N}` and translates it to/from the
 * active palette's integer slots, then triggers Astra's own CSS regeneration.
 *
 * Astra keeps the palette in TWO options and its own save writes both, so we do
 * too: `astra-settings['global-color-palette']` (what the CSS compiles from) and
 * the standalone `astra-color-palettes` option (what the Customizer colour-picker
 * control displays). Writing only the former updates the site but leaves the
 * Customizer swatches stale — see {@see AstraPaletteAdapter::mirror_to_color_palettes()}.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.4
 */

namespace SpectraBlocks\StyleGuide\Sync\Astra;

use SpectraBlocks\StyleGuide\TokenRegistry;
use SpectraBlocks\StyleGuide\Sync\ColorSyncAdapter;
use SpectraBlocks\StyleGuide\Sync\SyncOrchestrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AstraPaletteAdapter
 *
 * @since 1.0.4
 */
class AstraPaletteAdapter implements ColorSyncAdapter {

	/**
	 * Slug prefix mapping to Astra's `--ast-global-color-{N}` slots.
	 *
	 * @since 1.0.4
	 * @var string
	 */
	const SLUG_PREFIX = 'ast-global-color-';

	/**
	 * Astra semantic slot => Style Guide token that fills it.
	 *
	 * The mapping is 1:1 — each managed Astra slot takes a DISTINCT Style Guide
	 * token — so every slot can round-trip independently in the two-way sync (two
	 * slots sharing a token could not both be reversed).
	 *
	 * ALL NINE Astra slots are mapped, so nothing is left theme-owned. The mapping
	 * is expressed against Astra's ROLE, never a raw slot number:
	 *   Brand                → Primary      Secondary Background → Surface
	 *   Alternate Brand      → Secondary    Alternate Background → Outline
	 *   Headings             → Heading      Subtle Background    → Neutral
	 *   Text                 → Body         Other Supporting     → Accent
	 *   Primary Background   → Background
	 *
	 * Every slot whose INDEX Astra moves is resolved through the compatibility flag
	 * ({@see semantic_index()}), so a Style Guide colour always fills the same Astra
	 * ROLE on every install. The slot NUMBER differs between a reorganized and a
	 * legacy site — Astra swaps 4/5 and 6/7 — and that difference is inherent to
	 * Astra and cannot be removed; only role correctness can be guaranteed.
	 *
	 * These were briefly PINNED to fixed numbers to keep the slot index identical
	 * across sites. Do not reintroduce that: because Astra swaps the NAMES of 6 and
	 * 7 too, pinning made a legacy site fill Subtle background with Outline and
	 * Alternate background with Neutral — inverted from a reorganized site, which is
	 * precisely the cross-site inconsistency the pinning was meant to fix.
	 *
	 * @since 1.0.4
	 * @var array<string, string>
	 */
	const SEMANTIC_TOKENS = array(
		'brand'        => 'primary', // Slot 0 — Primary brand (two-way).
		'alt-brand'    => 'secondary', // Slot 1 — Secondary brand (two-way).
		'headings'     => 'neutral-7',    // Slot 2 — Headings.
		'text'         => 'neutral-5',    // Slot 3 — Body text.
		'primary-bg'   => 'neutral-0',    // Primary background   (flag-aware: 4/5).
		'secondary-bg' => 'neutral-1',    // Secondary background (flag-aware: 5/4).
		'alternate-bg' => 'neutral-2',    // Alternate background (flag-aware: 6/7).
		'subtle-bg'    => 'neutral-4',    // Subtle background    (flag-aware: 7/6).
		'other-supp'   => 'accent',       // Other supporting     (fixed: 8).
	);

	/**
	 * Guard: true while this adapter is writing the Astra option, so the
	 * `update_option_astra-settings` it fires can't re-trigger a pull.
	 *
	 * @since 1.0.4
	 * @var bool
	 */
	private static $writing = false;

	/**
	 * Whether a programmatic Astra write is in progress.
	 *
	 * @since 1.0.4
	 *
	 * @return bool
	 */
	public static function is_writing(): bool {
		return self::$writing;
	}

	/**
	 * Register the Astra-specific reverse-sync hook. Called by the orchestrator for
	 * each adapter that provides it, so the `astra-settings` option name lives here in
	 * the Astra module rather than being hardcoded in core.
	 *
	 * @since 1.0.4
	 *
	 * @param SyncOrchestrator $orchestrator The sync orchestrator.
	 * @return void
	 */
	public function register_reverse_hooks( SyncOrchestrator $orchestrator ): void {
		add_action( 'update_option_astra-settings', array( $orchestrator, 'pull_from_astra' ), 10, 2 );
	}

	/**
	 * The Astra slot index for a slug, or null if the slug is not an Astra slug.
	 *
	 * @since 1.0.4
	 *
	 * @param string $slug e.g. `ast-global-color-3`.
	 * @return int|null
	 */
	public static function slot_index( string $slug ): ?int {
		if ( 0 !== strpos( $slug, self::SLUG_PREFIX ) ) {
			return null;
		}
		$n = substr( $slug, strlen( self::SLUG_PREFIX ) );
		return ctype_digit( $n ) ? (int) $n : null;
	}

	/**
	 * Whether Astra is active (its option API is available).
	 *
	 * @since 1.0.4
	 *
	 * @return bool
	 */
	public function is_supported(): bool {
		return function_exists( 'astra_get_option' ) && function_exists( 'astra_update_option' );
	}

	/**
	 * Human-readable label.
	 *
	 * @since 1.0.4
	 *
	 * @return string
	 */
	public function label(): string {
		return 'Astra Global Colors (astra-settings)';
	}

	/**
	 * Read the ACTIVE Astra palette as `ast-global-color-{N}` => hex.
	 *
	 * @since 1.0.4
	 *
	 * @return array<string, string>
	 */
	public function read(): array {
		if ( ! $this->is_supported() ) {
			return array();
		}
		return self::slots_to_slugs( $this->active_slots( astra_get_option( 'global-color-palette' ) ) );
	}

	/**
	 * The ACTIVE palette slots from a given `global-color-palette` value.
	 *
	 * @since 1.0.4
	 *
	 * @param mixed $palette The `global-color-palette` option value.
	 * @return array<int, string> index => color.
	 */
	private function active_slots( $palette ): array {
		if ( ! is_array( $palette ) ) {
			return array();
		}
		// Astra's ACTIVE global colours live in the flat `palette` array — that is
		// what Astra compiles into --ast-global-color-*. Fall back to the named
		// `palettes[currentPalette]` shape only if the flat array is absent.
		$slots = ( isset( $palette['palette'] ) && is_array( $palette['palette'] ) )
			? $palette['palette']
			: array();
		if ( empty( $slots ) ) {
			$current = isset( $palette['currentPalette'] ) ? (string) $palette['currentPalette'] : 'palette_1';
			$slots   = ( isset( $palette['palettes'][ $current ] ) && is_array( $palette['palettes'][ $current ] ) )
				? $palette['palettes'][ $current ]
				: array();
		}

		$out = array();
		foreach ( $slots as $i => $color ) {
			if ( is_numeric( $i ) ) {
				$out[ (int) $i ] = (string) $color;
			}
		}
		return $out;
	}

	/**
	 * Read `ast-global-color-{N}` => hex from a full `astra-settings` array
	 * (as handed to the `update_option_astra-settings` hook).
	 *
	 * @since 1.0.4
	 *
	 * @param mixed $astra_settings The astra-settings option value.
	 * @return array<string, string>
	 */
	public function read_from( $astra_settings ): array {
		$palette = is_array( $astra_settings ) ? ( $astra_settings['global-color-palette'] ?? null ) : null;
		return self::slots_to_slugs( $this->active_slots( $palette ) );
	}

	/**
	 * Convert index => color to `ast-global-color-{N}` => color.
	 *
	 * @since 1.0.4
	 *
	 * @param array<int, string> $slots index => color.
	 * @return array<string, string>
	 */
	private static function slots_to_slugs( array $slots ): array {
		$out = array();
		foreach ( $slots as $i => $color ) {
			$out[ self::SLUG_PREFIX . $i ] = $color;
		}
		return $out;
	}

	/**
	 * Patch `ast-global-color-{N}` slugs into the ACTIVE Astra palette, preserving
	 * unmapped slots, then regenerate Astra's cached CSS.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, string> $patch slug => hex.
	 * @return bool
	 */
	public function write( array $patch ): bool {
		if ( ! $this->is_supported() || empty( $patch ) ) {
			return false;
		}

		$palette = astra_get_option( 'global-color-palette' );
		if ( ! is_array( $palette ) ) {
			$palette = array();
		}

		// Build the TARGET active slots: current flat `palette` array overlaid with
		// the patch. This is the desired state for BOTH Astra option stores.
		$slots = $this->active_slots( $palette );
		foreach ( $patch as $slug => $hex ) {
			$index = self::slot_index( (string) $slug );
			if ( null === $index ) {
				continue;
			}
			$slots[ $index ] = (string) $hex;
		}

		$current_palette = isset( $palette['currentPalette'] ) ? (string) $palette['currentPalette'] : 'palette_1';

		// Does `astra-settings['global-color-palette']` (CSS source) need updating?
		$settings_changed = $this->slots_differ( $this->active_slots( $palette ), $slots );

		$prev          = self::$writing;
		self::$writing = true;

		if ( $settings_changed ) {
			// Write the flat `palette` array (what Astra renders) and mirror it into
			// the active named palette so the palette-switcher UI stays consistent.
			$palette['palette']                      = $slots;
			$palette['palettes'][ $current_palette ] = $slots;
			astra_update_option( 'global-color-palette', $palette );
		}

		// Reconcile the separate `astra-color-palettes` option that backs the
		// Customizer's colour-picker control INDEPENDENTLY of the above. The pickers
		// read astra-color-palettes (via astra_get_palette_colors → get_option), not
		// global-color-palette, so a stale astra-color-palettes leaves the swatches
		// wrong even when global-color-palette is already correct. Reconciling it on
		// its own also self-heals any pre-existing divergence, not just fresh edits.
		$palettes_changed = $this->mirror_to_color_palettes( $slots, $current_palette );

		self::$writing = $prev;

		if ( ! $settings_changed && ! $palettes_changed ) {
			return false; // Both stores already in sync.
		}

		if ( function_exists( 'astra_clear_all_assets_cache' ) ) {
			astra_clear_all_assets_cache();
		}

		return true;
	}

	/**
	 * Whether two index => color slot maps differ (case-insensitive on hex).
	 *
	 * @since 1.0.4
	 *
	 * @param array<int, string> $a First slot map.
	 * @param array<int, string> $b Second slot map.
	 * @return bool True if they differ.
	 */
	private function slots_differ( array $a, array $b ): bool {
		if ( count( $a ) !== count( $b ) ) {
			return true;
		}
		foreach ( $b as $index => $color ) {
			if ( ! isset( $a[ $index ] ) || strtolower( (string) $a[ $index ] ) !== strtolower( (string) $color ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Mirror the active slots into the standalone `astra-color-palettes` option,
	 * the store the Customizer colour-picker control (`ast-color-palette`, read via
	 * {@see astra_get_palette_colors()}) displays from. Kept index-for-index with
	 * `global-color-palette['palette']`, matching Astra's own updater.
	 *
	 * @since 1.0.4
	 *
	 * @param array<int, string> $slots           index => color for the active palette.
	 * @param string             $current_palette Active palette id (e.g. `palette_1`).
	 * @return bool True if the option was changed, false if already in sync.
	 */
	private function mirror_to_color_palettes( array $slots, string $current_palette ): bool {
		$data = get_option( 'astra-color-palettes', array() );
		if ( ( empty( $data ) || ! is_array( $data ) ) && class_exists( '\Astra_Global_Palette' ) ) {
			$data = \Astra_Global_Palette::get_default_color_palette();
		}
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		if ( ! isset( $data['palettes'] ) || ! is_array( $data['palettes'] ) ) {
			$data['palettes'] = array();
		}

		// Start from the existing named palette (preserve any slots we don't map),
		// then overlay the target active slots index-for-index.
		$existing = ( isset( $data['palettes'][ $current_palette ] ) && is_array( $data['palettes'][ $current_palette ] ) )
			? $data['palettes'][ $current_palette ]
			: array();

		$changed = false;
		foreach ( $slots as $index => $color ) {
			if ( ! isset( $existing[ $index ] ) || strtolower( (string) $existing[ $index ] ) !== strtolower( (string) $color ) ) {
				$existing[ $index ] = $color;
				$changed            = true;
			}
		}
		if ( ! $changed ) {
			return false; // Already reflects the target slots.
		}

		$data['palettes'][ $current_palette ] = $existing;
		update_option( 'astra-color-palettes', $data );
		return true;
	}

	/**
	 * Resolve an Astra semantic slot to its slot index for the ACTIVE Astra
	 * version. Brand/text and Other Supporting are fixed by Astra; the four
	 * background slots are swapped in PAIRS by the `astra_4_8_9_compatibility()`
	 * reorganize flag, and this mirrors Astra's own resolver verbatim
	 * (class-gutenberg-editor-css.php):
	 *
	 *   $primary_color   = $reorganize ? color-4 : color-5
	 *   $secondary_color = $reorganize ? color-5 : color-4
	 *   $alternate_color = $reorganize ? color-6 : color-7
	 *   $subtle_color    = $reorganize ? color-7 : color-6
	 *
	 * Following the flag for ALL FOUR is what keeps a Style Guide colour on the same
	 * Astra ROLE everywhere: Outline always fills Alternate background, Neutral
	 * always fills Subtle background. The slot NUMBER differs between a reorganized
	 * and a legacy site, which is inherent to Astra and cannot be avoided.
	 *
	 * Do NOT pin these to fixed numbers. That was tried to keep the index identical
	 * across sites, and it inverted the roles on legacy installs — Outline landed on
	 * Subtle background and Neutral on Alternate background, the exact cross-site
	 * mismatch the pinning was meant to remove.
	 *
	 * @since 1.0.4
	 *
	 * @param string $semantic   Semantic key from SEMANTIC_TOKENS.
	 * @param bool   $reorganize Whether Astra uses the post-4.8.9 slot order
	 *                           ({@see self::uses_reorganized_slots()}). Passed in so
	 *                           a whole map costs one flag lookup, not one per slot.
	 * @return int|null Slot index, or null if unknown.
	 */
	private static function semantic_index( string $semantic, bool $reorganize ): ?int {
		$fixed = array(
			'brand'      => 0,
			'alt-brand'  => 1,
			'headings'   => 2,
			'text'       => 3,
			'other-supp' => 8, // Astra never moves this one.
		);
		if ( isset( $fixed[ $semantic ] ) ) {
			return $fixed[ $semantic ];
		}

		$backgrounds = $reorganize
			? array(
				'primary-bg'   => 4,
				'secondary-bg' => 5,
				'alternate-bg' => 6,
				'subtle-bg'    => 7,
			)
			: array(
				'primary-bg'   => 5,
				'secondary-bg' => 4,
				'alternate-bg' => 7,
				'subtle-bg'    => 6,
			);

		return $backgrounds[ $semantic ] ?? null;
	}

	/**
	 * Whether Astra places the four background colours in its REORGANIZED
	 * (post-4.8.9) slot order.
	 *
	 * Defaults to TRUE and only reports the legacy order when Astra is present AND
	 * explicitly sets the compatibility flag. The slot map is now read on non-Astra
	 * themes too (the render-time `--ast-global-color-*` aliases in
	 * {@see \SpectraBlocks\StyleGuide\GlobalStylesBridge} are emitted regardless of
	 * the active theme), and there the modern layout is the right assumption —
	 * deriving `false` from a merely-absent Astra class would silently hand those
	 * consumers the legacy slot order.
	 *
	 * @since 1.0.5
	 *
	 * @return bool True for the 4.8.9+ layout.
	 */
	private static function uses_reorganized_slots(): bool {
		return ! class_exists( '\Astra_Dynamic_CSS' )
			|| ! method_exists( '\Astra_Dynamic_CSS', 'astra_4_8_9_compatibility' )
			|| \Astra_Dynamic_CSS::astra_4_8_9_compatibility();
	}

	/**
	 * Build the full Astra push patch (`ast-global-color-{N}` => hex) from the
	 * Style Guide tokens, using the semantic → token map with flag-aware indices.
	 *
	 * @since 1.0.4
	 *
	 * @param TokenRegistry $tokens The computed Style Guide token registry.
	 * @return array<string, string> slug => hex.
	 */
	public function resolve_patch( TokenRegistry $tokens ): array {
		if ( ! $this->is_supported() ) {
			return array();
		}
		$patch = array();
		foreach ( self::shade_map() as $index => $token_key ) {
			$hex = $tokens->get( $token_key );
			if ( is_string( $hex ) && '' !== $hex ) {
				$patch[ self::SLUG_PREFIX . $index ] = $hex;
			}
		}
		return $patch;
	}

	/**
	 * Astra slot INDEX => Style Guide token key, for the ACTIVE Astra layout.
	 *
	 * THE single source of truth for "which Astra slot carries which Style Guide
	 * colour". Everything that speaks both languages must resolve through this —
	 * the push ({@see resolve_patch}), the reverse sync ({@see reverse_map}), the
	 * unsaved-state inheritance ({@see \SpectraBlocks\StyleGuide\Engine::inherited_default_colors()})
	 * and the render-time aliases
	 * ({@see \SpectraBlocks\StyleGuide\GlobalStylesBridge::astra_shade_map()}) —
	 * so no consumer can drift from what the sync actually writes.
	 *
	 * Static because most consumers only need the mapping, not an adapter instance.
	 * Flag-aware: {@see semantic_index()} swaps the background indices on installs
	 * that predate Astra's 4.8.9 palette reorganization, so a hardcoded copy of this
	 * map is wrong on every legacy site.
	 *
	 * @since 1.0.4
	 *
	 * @return array<int, string> slot index => SG token key (e.g. 3 => 'neutral-5').
	 */
	public static function shade_map(): array {
		$reorganize = self::uses_reorganized_slots();

		$map = array();
		foreach ( self::SEMANTIC_TOKENS as $semantic => $token_key ) {
			$index = self::semantic_index( $semantic, $reorganize );
			if ( null !== $index ) {
				$map[ $index ] = $token_key;
			}
		}
		ksort( $map );
		return $map;
	}

	/**
	 * The reverse map: Astra slot INDEX => Style Guide token key.
	 *
	 * The inverse of the push ({@see resolve_patch}) — both read {@see shade_map()},
	 * so push and pull can never drift. Consumed by the reverse sync to turn a
	 * changed Astra slot back into the Style Guide token it should update.
	 *
	 * @since 1.0.5
	 *
	 * @return array<int, string> slot index => SG token key (e.g. 3 => 'neutral-5').
	 */
	public function reverse_map(): array {
		return self::shade_map();
	}
}
