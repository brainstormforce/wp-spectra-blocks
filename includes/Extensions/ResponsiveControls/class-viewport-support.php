<?php
/**
 * What the running WordPress can do with per-viewport block styles.
 *
 * The responsive system has two implementations. Which one a site gets is
 * decided here, in one place, so that PHP and the editor can never disagree
 * about it:
 *
 *   - WordPress WITHOUT viewport states — the values live in Spectra's own
 *     `responsiveControls` attribute, keyed `lg` / `md` / `sm`, and Spectra
 *     renders every breakpoint itself. This is what shipped up to 1.0.6 and it
 *     is left exactly as it was.
 *   - WordPress WITH viewport states (7.1 and later) — the values live in
 *     core's `style` attribute, keyed `base` / `@tablet` / `@mobile`, core
 *     renders what it owns and Spectra renders the rest from the same source.
 *
 * GATE ON CAPABILITY, NEVER ON THE VERSION NUMBER. The two are not the same
 * thing, and the difference is observable rather than theoretical:
 *
 *   - A 7.0.4 install can carry `wp-includes/block-supports/states.php` on disk
 *     from an interrupted update while `wp_render_block_states_support()` is
 *     never loaded — measured on a local 7.0.4 site, where reading the file
 *     said "supported" and the runtime said otherwise.
 *   - The Gutenberg plugin can supply viewport states on a WordPress older than
 *     7.1, where a version check would wrongly route a site that is perfectly
 *     able to use the new path.
 *
 * Both capabilities are required. The new path needs core to STORE and RENDER
 * viewport states and to RESOLVE the breakpoints those states band at; a
 * WordPress with only one of the two would run the new path half-served, which
 * is worse than running the old one.
 *
 * @package SpectraBlocks\Extensions\ResponsiveControls
 * @since 1.0.7
 */

namespace SpectraBlocks\Extensions\ResponsiveControls;

/**
 * Capability detection for core's per-viewport block styles.
 *
 * @since 1.0.7
 */
class ViewportSupport {

	/**
	 * Memoised answer for the request.
	 *
	 * Capabilities cannot change mid-request — a function either exists by the
	 * time anything asks or it does not — so this is resolved once. `null` means
	 * "not yet asked".
	 *
	 * @var bool|null
	 * @since 1.0.7
	 */
	private static $has_viewport_states = null;

	/**
	 * Whether core renders `style['@tablet']` / `style['@mobile']` itself.
	 *
	 * Added in WordPress 7.1 as `block-supports/states.php`. When this is true,
	 * writing a per-viewport value into core's `style` attribute is enough for
	 * core to emit the properties it owns, and Spectra only has to emit the
	 * rest.
	 *
	 * @since 1.0.7
	 * @return bool True when core renders viewport states.
	 */
	public static function renders_states() {
		return function_exists( 'wp_render_block_states_support' );
	}

	/**
	 * Whether core can tell us the breakpoints its viewport states band at.
	 *
	 * `WP_Theme_JSON::get_viewport_media_queries()` is the single source both
	 * halves of a block must agree on. Without it there is nothing to agree
	 * with, and Spectra bands on its own historical values instead.
	 *
	 * @since 1.0.7
	 * @return bool True when core resolves viewport breakpoints.
	 */
	public static function resolves_breakpoints() {
		return is_callable( array( '\WP_Theme_JSON', 'get_viewport_media_queries' ) );
	}

	/**
	 * Whether this site gets the viewport-state implementation.
	 *
	 * The one question the rest of the extension asks. Everything version-
	 * specific — which attributes are registered, where an edit is written,
	 * which renderer emits per-breakpoint CSS, whether the migration may run —
	 * hangs off this single answer, so the two implementations can never be
	 * half-loaded at once.
	 *
	 * @since 1.0.7
	 * @return bool True to use the viewport-state path, false for the legacy path.
	 */
	public static function has_viewport_states() {
		if ( null === self::$has_viewport_states ) {
			self::$has_viewport_states = self::renders_states() && self::resolves_breakpoints();
		}

		return self::$has_viewport_states;
	}

	/**
	 * The capability answers, shaped for the editor.
	 *
	 * Exported through `spectra_blocks_info.viewport_support` so the editor
	 * decides from the same measurement PHP used, rather than parsing
	 * `wp_version` and reaching its own conclusion. A JS-side version check can
	 * only ever be a fallback for the case where this data is missing.
	 *
	 * @since 1.0.7
	 * @return array<string, bool> Capability flags.
	 */
	public static function to_array() {
		return array(
			'hasViewportStates'   => self::has_viewport_states(),
			'rendersStates'       => self::renders_states(),
			'resolvesBreakpoints' => self::resolves_breakpoints(),
		);
	}

	/**
	 * Forget the memoised answer.
	 *
	 * Only for tests, which need to exercise both implementations inside one
	 * process. Nothing on a request path should call this.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public static function reset_cache() {
		self::$has_viewport_states = null;
	}
}
