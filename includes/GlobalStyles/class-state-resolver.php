<?php
/**
 * Single source of truth for user-class STATE resolution.
 *
 * A stored class `state` key is one of:
 *   - `default` / ''                          → no media, no suffix
 *   - a named pseudo (`hover`, `before`, …)   → selector suffix only
 *   - a responsive breakpoint (`md`, `lg`, …) → `@media` wrapper, no suffix
 *   - a breakpoint + pseudo (`md_hover`)      → `@media` wrapper + suffix
 *   - an already-formatted state (`:x`, `[open]`, `.is-active`) → verbatim
 *     suffix (shape-validated — see {@see StateResolver::suffix})
 *
 * Both class renderers resolve states through this class — the site-wide
 * option renderer ({@see Engine::render_user_classes}) and the per-page /
 * imported payload renderer ({@see GenCssRenderer}) — so the breakpoint table
 * and the pseudo table exist in exactly ONE place and the two paths can never
 * drift.
 *
 * @package Spectra\GlobalStyles
 * @since   1.0.0
 */

namespace SpectraBlocks\GlobalStyles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class StateResolver.
 *
 * @since 1.0.0
 */
final class StateResolver {

	/**
	 * Responsive breakpoint slug → mobile-first `min-width` media condition.
	 * SSOT for the GBS breakpoint stops (Tailwind-parity).
	 *
	 * @since 1.0.0
	 * @var array<string,string>
	 */
	public const BREAKPOINTS = array(
		'sm'  => '(min-width: 640px)',
		'md'  => '(min-width: 768px)',
		'lg'  => '(min-width: 1024px)',
		'xl'  => '(min-width: 1280px)',
		'2xl' => '(min-width: 1536px)',
	);

	/**
	 * Named state → CSS selector suffix. SSOT for the pseudo vocabulary.
	 *
	 * @since 1.0.0
	 * @var array<string,string>
	 */
	public const PSEUDO = array(
		'hover'         => ':hover',
		'focus'         => ':focus',
		'focus-visible' => ':focus-visible',
		'focus-within'  => ':focus-within',
		'active'        => ':active',
		'visited'       => ':visited',
		'disabled'      => ':disabled',
		'checked'       => ':checked',
		'before'        => '::before',
		'after'         => '::after',
		'first-letter'  => '::first-letter',
		'first-line'    => '::first-line',
		'placeholder'   => '::placeholder',
		'marker'        => '::marker',
		'selection'     => '::selection',
	);

	/**
	 * Resolve a stored state key to its `{ media, suffix }` descriptor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $state Stored bucket key (`default`, `hover`, `md`, `md_hover`, `[open]`, …).
	 * @return array{media:string, suffix:string} Empty `media` = base rule (no `@media`).
	 */
	public static function resolve( string $state ): array {
		if ( '' === $state || 'default' === $state ) {
			return array(
				'media'  => '',
				'suffix' => '',
			);
		}

		// A responsive breakpoint prefix with an optional underscore-separated pseudo
		// state such as md, md_hover or lg_focus-visible.
		$underscore = strpos( $state, '_' );
		$prefix     = false === $underscore ? $state : substr( $state, 0, $underscore );
		$remainder  = false === $underscore ? '' : substr( $state, $underscore + 1 );

		if ( isset( self::BREAKPOINTS[ $prefix ] ) ) {
			return array(
				'media'  => self::BREAKPOINTS[ $prefix ],
				'suffix' => '' === $remainder ? '' : self::suffix( $remainder ),
			);
		}

		return array(
			'media'  => '',
			'suffix' => self::suffix( $state ),
		);
	}

	/**
	 * Named / raw state → selector suffix. A known pseudo maps to its token; an
	 * already-formatted state — pseudo (`:x`), attribute (`[open]`), or class
	 * tail (`.is-active`, incl. combinations like `.is-active:hover`) — rides
	 * verbatim; anything else is dropped ('') rather than emitted as a garbage
	 * tail. Class tails are how imported compound selectors keep their authored
	 * cascade: `.tab-panel.is-active` must render on the SAME lane (and with the
	 * same specificity lift) as its `.tab-panel` base — split across lanes, the
	 * base's lift inverts the source cascade and state UIs (tabs/sliders/
	 * accordions) render their hidden state (E2E 2026-07-10).
	 *
	 * Verbatim tails are shape-validated (class/pseudo/attr segments only; no
	 * braces, angle brackets, or stray tokens) so a stored state key can never
	 * break out of the selector position in the emitted stylesheet. `/` and `*`
	 * are excluded too — a segment body that allowed them could smuggle a raw
	 * CSS comment delimiter (slash-star / star-slash), which the tokenizer
	 * honors anywhere in the sheet regardless of selector context, letting one
	 * stored state key open a comment a second one closes elsewhere on the
	 * page. This also
	 * closes the pre-existing hole where any `:`/`[`-prefixed key rode verbatim
	 * unvalidated.
	 *
	 * @since 1.0.0
	 *
	 * @param string $state State key without a responsive prefix.
	 * @return string Selector suffix, or '' to drop.
	 */
	public static function suffix( string $state ): string {
		if ( isset( self::PSEUDO[ $state ] ) ) {
			return self::PSEUDO[ $state ];
		}
		$first = $state[0] ?? '';
		if ( ':' === $first || '[' === $first || '.' === $first ) {
			return 1 === preg_match(
				'/^(?:\.[A-Za-z0-9_-]+|:{1,2}[a-zA-Z-]+(?:\([^()<>{}\/*]*\))?|\[[^\]<>{}\/*]*\])+$/',
				$state
			) ? $state : '';
		}
		return '';
	}

	/**
	 * Ascending sort key for a media condition.
	 *
	 * Three bands, in emission order:
	 *   1. `min-width` — the mobile-first ladder, ascending by px.
	 *   2. `max-width` — desktop-first overrides, DESCENDING by px, so the
	 *      narrower breakpoint emits later and wins the equal-specificity tie.
	 *   3. everything else (`prefers-color-scheme`, custom) — last.
	 *
	 * Band 2 used to collapse into `PHP_INT_MAX` alongside band 3, so two
	 * `max-width` breakpoints tied and fell back to stored key order: written in
	 * the wrong order, the narrower one lost. That is the same source-order bug
	 * this class exists to fix, one breakpoint deeper.
	 *
	 * @since 1.0.0
	 *
	 * @param string $media Media condition string.
	 * @return int Sort key.
	 */
	public static function media_order( string $media ): int {
		if ( preg_match( '/min-width:\s*(\d+)/', $media, $m ) ) {
			return (int) $m[1];
		}

		if ( preg_match( '/max-width:\s*(\d+)/', $media, $m ) ) {
			// Descending within the band, and offset so the whole band still sorts
			// after every min-width: a wider max-width is a weaker override.
			return PHP_INT_MAX - 1 - (int) $m[1];
		}

		return PHP_INT_MAX;
	}
}
