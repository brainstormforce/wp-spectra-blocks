<?php
/**
 * Single source of truth for user-class STATE resolution.
 *
 * A stored class `state` key is one of:
 *   - `default` / ''                          → no media, no suffix
 *   - a named pseudo (`hover`, `before`, …)   → selector suffix only
 *   - a responsive breakpoint (`md`, `lg`, …) → `@media` wrapper, no suffix
 *   - a breakpoint + pseudo (`md_hover`)      → `@media` wrapper + suffix
 *   - an already-formatted / attribute state (`:x`, `[open]`) → verbatim suffix
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
	 * already-formatted pseudo (`:x`) or attribute selector (`[open]`) rides
	 * verbatim; anything else is dropped ('') rather than emitted as a garbage tail.
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
		if ( ':' === $first || '[' === $first ) {
			return $state;
		}
		return '';
	}

	/**
	 * Ascending sort key for a media condition: its `min-width` px, or `PHP_INT_MAX`
	 * for a condition without a `min-width` (max-width / custom) so it emits LAST,
	 * after the mobile-first min-width ladder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $media Media condition string.
	 * @return int Sort key.
	 */
	public static function media_order( string $media ): int {
		return preg_match( '/min-width:\s*(\d+)/', $media, $m ) ? (int) $m[1] : PHP_INT_MAX;
	}
}
