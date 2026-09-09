<?php
/**
 * Pre-7.1 precedence between a `style` viewport state and the legacy store.
 *
 * On 7.1 a `@tablet` / `@mobile` state in `style` can only have been written
 * deliberately — core's own panels write it — so when the store is hydrated
 * from `style` the state outranks whatever the store already held.
 *
 * Below 7.1 that premise fails. Core has no viewport states for the editor to
 * write: every per-device edit lands in the legacy `lg` / `md` / `sm` buckets,
 * so a state can only be a BLOCK VARIATION's default — Popup Builder's Info Bar
 * ships `@tablet` and `@mobile` layout, width, spacing and height — or content
 * authored on a newer site. Letting those outrank the store meant the
 * variation's default won: a banner given 50 / 100 / 150px across the devices
 * stored `md: 100px` and `sm: 150px`, then rendered the variation's 50px in all
 * three bands, so per-device height did nothing on the front end below 7.1
 * while working on 7.1.
 *
 * There, then, a state fills GAPS only: the store wins where it spoke, and a
 * state still supplies anything the store never covered, which keeps
 * 7.1-authored content rendering on an older site.
 *
 * It is deliberately removable, and answers `false` on 7.1 so it changes
 * nothing there. Removing pre-7.1 support is the same two steps as the rest of
 * this folder:
 *
 *   1. delete this folder
 *   2. delete the guarded `StorePrecedence::state_fills_gaps_only()` call in
 *      `ResponsiveControls::hydrate_store_from_style()`
 *   3. delete the now-stale `use` import at the top of that class
 *
 * The editor half lives in
 * `src/extensions/responsive-controls/legacy/classic-editor/style-state-fallback.js`
 * and goes with that folder, on the same trigger.
 *
 * **Removal condition:** the plugin's minimum supported WordPress is 7.1. That
 * is a different trigger from `../Legacy/`, which serves pre-1.0.6 CONTENT.
 *
 * @package SpectraBlocks\Extensions\ResponsiveControls\Pre71
 * @since 1.0.7
 */

namespace SpectraBlocks\Extensions\ResponsiveControls\Pre71;

use SpectraBlocks\Extensions\ResponsiveControls\ViewportSupport;

/**
 * Decides whether a viewport state may outrank the legacy store.
 *
 * @since 1.0.7
 */
class StorePrecedence {

	/**
	 * Whether a `style` viewport state must fill gaps instead of overriding.
	 *
	 * @since 1.0.7
	 * @return bool True below 7.1, where a state is never the editor's doing.
	 */
	public static function state_fills_gaps_only() {
		return ! ViewportSupport::renders_states();
	}
}
