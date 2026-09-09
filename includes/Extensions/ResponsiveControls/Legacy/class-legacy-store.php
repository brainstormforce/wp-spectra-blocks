<?php
/**
 * Legacy responsive-store support.
 *
 * Everything in this folder exists only to read content written before the store
 * adopted WordPress core's viewport vocabulary. It is deliberately removable:
 * the one current-path caller (`ResponsiveControls::normalize_render_attributes()`)
 * is guarded by `class_exists`, so removing legacy support is two steps and
 * nothing else (plus deleting the now-stale `use` import in the main class).
 *
 *   1. delete this folder
 *   2. delete the `LegacyStore` line from `ResponsiveControls::init()`
 *   3. delete `tests/phpunit/tests/Extensions/ResponsiveControls/Legacy/`, whose
 *      path mirrors this one so the tests go in the same step
 *
 * The editor half lives in `src/extensions/responsive-controls/legacy/` and is
 * removed the same way — delete the folder and its one `import './legacy';`.
 *
 * The `lg` / `md` / `sm` aliases this class used to republish for Spectra
 * Blocks Pro are gone: Pro reads canonical keys through its own
 * `ResponsiveControls::device_bucket()`, with a legacy fallback of its own.
 *
 * @package SpectraBlocks\Extensions\ResponsiveControls\Legacy
 * @since 1.0.7
 */

namespace SpectraBlocks\Extensions\ResponsiveControls\Legacy;

/**
 * Reads pre-1.0.6 responsive stores and presents them in the current vocabulary.
 *
 * @since 1.0.7
 */
class LegacyStore {

	/**
	 * Legacy device keys mapped to the canonical viewport keys.
	 *
	 * Spectra stored breakpoints as `lg` / `md` / `sm` up to and including 1.0.5.
	 *
	 * Keep in sync with `LEGACY_DEVICE_MAP` in
	 * `src/extensions/responsive-controls/legacy/constants.js`.
	 *
	 * @var array<string, string>
	 * @since 1.0.7
	 */
	const DEVICE_MAP = array(
		'lg' => 'base',
		'md' => '@tablet',
		'sm' => '@mobile',
	);

	/**
	 * Whether a block belongs to the responsive-controls system at all.
	 *
	 * These filters run for every block on the page; a third-party block that
	 * happens to carry a `responsiveControls` attribute must not have it
	 * rewritten. Mirrors the prefixes and the one explicit inclusion the main
	 * extension allows.
	 *
	 * @since 1.0.7
	 * @param array<string, mixed> $block Block data.
	 * @return bool True for blocks the responsive system owns.
	 */
	private static function is_spectra_block( $block ) {
		$name = $block['blockName'] ?? '';

		if ( ! is_string( $name ) || '' === $name ) {
			return false;
		}

		return 0 === strpos( $name, 'spectra/' )
			|| 0 === strpos( $name, 'spectra-pro/' )
			|| 'core/image' === $name;
	}

	/**
	 * Register the legacy hooks.
	 *
	 * The rename runs at priority 3, ahead of the current path's own
	 * `render_block_data` work, so everything downstream only ever sees one
	 * vocabulary.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public static function init() {
		add_filter( 'render_block_data', array( __CLASS__, 'normalize_device_keys' ), 3, 1 );
	}

	/**
	 * Rewrite legacy `lg` / `md` / `sm` keys into the canonical viewport keys.
	 *
	 * Content is normalised as it is read rather than migrated in the database, so
	 * existing posts render correctly without being re-saved and downgrading stays
	 * safe. Where a block somehow carries both shapes for one breakpoint the
	 * canonical value wins, because it is the one the current editor wrote.
	 *
	 * @since 1.0.7
	 * @param array<string, mixed> $block Block data.
	 * @return array<string, mixed> Block data with canonical device keys.
	 */
	public static function normalize_device_keys( $block ) {
		if ( ! self::is_spectra_block( $block ) ) {
			return $block;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		$store = isset( $attrs['responsiveControls'] ) && is_array( $attrs['responsiveControls'] )
			? $attrs['responsiveControls']
			: array();

		if ( empty( $store ) ) {
			return $block;
		}

		$changed = false;

		foreach ( self::DEVICE_MAP as $legacy => $canonical ) {
			if ( ! array_key_exists( $legacy, $store ) ) {
				continue;
			}

			$legacy_value    = is_array( $store[ $legacy ] ) ? $store[ $legacy ] : array();
			$canonical_value = isset( $store[ $canonical ] ) && is_array( $store[ $canonical ] )
				? $store[ $canonical ]
				: array();

			$store[ $canonical ] = array_replace_recursive( $legacy_value, $canonical_value );
			unset( $store[ $legacy ] );
			$changed = true;
		}

		/*
		 * Bake the legacy cascade. The old generator resolved mobile as
		 * `sm -> md -> lg`, so a tablet value applied on phones whenever mobile
		 * was unset. The current generator follows core's model — each viewport
		 * over base only — which would change how that content renders. Copying
		 * the tablet bucket under the mobile one (mobile wins where both are
		 * set) preserves the authored rendering while the data itself moves to
		 * core semantics. Only blocks that actually carried legacy keys are
		 * baked; content authored since is already core-shaped.
		 */
		if ( $changed && isset( $store['@tablet'] ) && is_array( $store['@tablet'] ) && ! empty( $store['@tablet'] ) ) {
			$mobile           = isset( $store['@mobile'] ) && is_array( $store['@mobile'] ) ? $store['@mobile'] : array();
			$store['@mobile'] = array_replace_recursive( $store['@tablet'], $mobile );
		}

		if ( $changed ) {
			$attrs['responsiveControls'] = $store;
			$block['attrs']              = $attrs;
		}

		return $block;
	}
}
