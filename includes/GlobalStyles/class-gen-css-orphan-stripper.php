<?php
/**
 * Orphan-selector stripper for the per-page Gen CSS bucket.
 *
 * Post-processes the CSS written to the `spectra_blocks_pro_gs_user_css` post
 * meta. Strips rules whose selector list reduces to only `.gc-spectra-*` class
 * tokens — those tokens are dropped from rendered HTML by the Laravel-side
 * ClassnameValidator, so their CSS rules would otherwise sit in the stylesheet
 * without ever matching a DOM element.
 *
 * Registered once via {@see self::register()}, which hooks the post-meta
 * sanitize filter `sanitize_post_meta_spectra_blocks_pro_gs_user_css`.
 *
 * @package Spectra\GlobalStyles
 * @since   1.0.0
 */

namespace SpectraBlocks\GlobalStyles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GenCssOrphanStripper.
 *
 * @since 1.0.0
 */
class GenCssOrphanStripper {

	/**
	 * Post-meta key for the per-page Gen CSS bucket.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const META_KEY = 'spectra_blocks_pro_gs_user_css';

	/**
	 * Read the per-page Gen CSS payload for a post — the ONE reader both the
	 * enqueue path and the asset-loader presence check go through.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>|null The schema-v1 payload, or null when none.
	 */
	public static function read_page_payload( int $post_id ): ?array {
		if ( $post_id <= 0 ) {
			return null;
		}

		$stored = get_post_meta( $post_id, self::META_KEY, true );
		if ( is_array( $stored ) && array() !== $stored ) {
			return $stored;
		}

		return null;
	}

	/**
	 * Register the post-meta sanitize filter.
	 *
	 * `sanitize_meta()` fires `sanitize_post_meta_{key}` on every
	 * add_post_meta / update_post_meta for the key, so orphan rules are
	 * stripped before the value is persisted.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'sanitize_post_meta_' . self::META_KEY, array( self::class, 'strip_orphans_meta' ), 10, 1 );
	}

	/**
	 * Filter callback for `sanitize_post_meta_{META_KEY}`.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The meta value about to be stored.
	 * @return mixed Cleaned CSS string, or the value untouched when not a
	 *               non-empty string.
	 */
	public static function strip_orphans_meta( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}

		return self::strip_orphan_selectors( $value );
	}

	/**
	 * Strip CSS rules whose selector list reduces entirely to
	 * `.gc-spectra-*` tokens.
	 *
	 * The parser walks the CSS brace-by-brace rather than pulling in a full
	 * CSS parser dependency — adequate for the structured output emitted by
	 * the Gen CSS pipeline, which is always `selector { decls }` with no
	 * nesting besides `@media` / `@supports` wrappers.
	 *
	 * Selectors containing any non-`.gc-spectra-` token are preserved in full
	 * (we never rewrite individual tokens — a selector with even one live
	 * token is kept intact). At-rule blocks (`@media {...}`) are recursed
	 * into so orphan rules inside responsive breakpoints are stripped too.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css Raw CSS source.
	 * @return string Cleaned CSS. Returns the original string if no orphans
	 *                were found.
	 */
	public static function strip_orphan_selectors( string $css ): string {
		if ( '' === $css ) {
			return $css;
		}

		// Fast-path: if no `.gc-spectra` token is present there is nothing to strip.
		if ( false === strpos( $css, '.gc-spectra' ) ) {
			return $css;
		}

		$length = strlen( $css );
		$output = '';
		$i      = 0;

		while ( $i < $length ) {
			// Skip leading whitespace but preserve it in output for readability.
			$ws_start = $i;
			while ( $i < $length && ctype_space( $css[ $i ] ) ) {
				++$i;
			}
			$output .= substr( $css, $ws_start, $i - $ws_start );

			if ( $i >= $length ) {
				break;
			}

			// Handle /* comments */ verbatim.
			if ( '/' === $css[ $i ] && $i + 1 < $length && '*' === $css[ $i + 1 ] ) {
				$end = strpos( $css, '*/', $i + 2 );
				if ( false === $end ) {
					$output .= substr( $css, $i );
					break;
				}
				$output .= substr( $css, $i, $end - $i + 2 );
				$i       = $end + 2;
				continue;
			}

			// At-rule: preserve prelude, recurse into body.
			if ( '@' === $css[ $i ] ) {
				$brace = self::find_top_level_brace( $css, $i );
				if ( null === $brace ) {
					// Statement at-rule (e.g. `@import ...;`) — copy to semicolon.
					$semi = strpos( $css, ';', $i );
					if ( false === $semi ) {
						$output .= substr( $css, $i );
						break;
					}
					$output .= substr( $css, $i, $semi - $i + 1 );
					$i       = $semi + 1;
					continue;
				}
				$end = self::find_matching_brace( $css, $brace );
				if ( null === $end ) {
					$output .= substr( $css, $i );
					break;
				}

				$prelude      = substr( $css, $i, $brace - $i );
				$inner        = substr( $css, $brace + 1, $end - $brace - 1 );
				$cleaned_body = self::strip_orphan_selectors( $inner );

				// Drop the at-rule entirely when its body is now empty.
				if ( '' === trim( $cleaned_body ) ) {
					$i = $end + 1;
					continue;
				}

				$output .= $prelude . '{' . $cleaned_body . '}';
				$i       = $end + 1;
				continue;
			}

			// Regular rule: find `{...}`.
			$brace = self::find_top_level_brace( $css, $i );
			if ( null === $brace ) {
				// Trailing text — preserve and stop.
				$output .= substr( $css, $i );
				break;
			}

			$end = self::find_matching_brace( $css, $brace );
			if ( null === $end ) {
				$output .= substr( $css, $i );
				break;
			}

			$selector_list = trim( substr( $css, $i, $brace - $i ) );
			$body          = substr( $css, $brace, $end - $brace + 1 );

			if ( self::selector_list_is_orphan_only( $selector_list ) ) {
				// Drop the rule entirely.
				$i = $end + 1;
				continue;
			}

			$output .= $selector_list . $body;
			$i       = $end + 1;
		}

		return $output;
	}

	/**
	 * Return the index of the next `{` at the current nesting level, or null
	 * when none is found before end-of-string / a `;` at the same level.
	 *
	 * Treats strings and comments as opaque so braces inside them don't
	 * confuse the scanner.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css   Source.
	 * @param int    $start Offset to scan from.
	 * @return int|null
	 */
	private static function find_top_level_brace( string $css, int $start ): ?int {
		$length = strlen( $css );
		$i      = $start;

		while ( $i < $length ) {
			$ch = $css[ $i ];

			if ( '{' === $ch ) {
				return $i;
			}
			if ( ';' === $ch ) {
				return null;
			}
			if ( '"' === $ch || "'" === $ch ) {
				$i = self::skip_string( $css, $i );
				continue;
			}
			if ( '/' === $ch && $i + 1 < $length && '*' === $css[ $i + 1 ] ) {
				$end = strpos( $css, '*/', $i + 2 );
				$i   = ( false === $end ) ? $length : $end + 2;
				continue;
			}
			++$i;
		}

		return null;
	}

	/**
	 * Return the index of the `}` matching the `{` at `$open_index`, honouring
	 * nested braces, strings, and comments.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css        Source.
	 * @param int    $open_index Index of the opening brace.
	 * @return int|null
	 */
	private static function find_matching_brace( string $css, int $open_index ): ?int {
		$length = strlen( $css );
		$depth  = 0;
		$i      = $open_index;

		while ( $i < $length ) {
			$ch = $css[ $i ];

			if ( '"' === $ch || "'" === $ch ) {
				$i = self::skip_string( $css, $i );
				continue;
			}
			if ( '/' === $ch && $i + 1 < $length && '*' === $css[ $i + 1 ] ) {
				$end = strpos( $css, '*/', $i + 2 );
				$i   = ( false === $end ) ? $length : $end + 2;
				continue;
			}
			if ( '{' === $ch ) {
				++$depth;
			} elseif ( '}' === $ch ) {
				--$depth;
				if ( 0 === $depth ) {
					return $i;
				}
			}
			++$i;
		}

		return null;
	}

	/**
	 * Return the index just past the closing quote of the string starting at
	 * `$index`. Handles `\"` / `\'` escape sequences.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css   Source.
	 * @param int    $index Index of opening quote.
	 * @return int Position after the closing quote, or end-of-string when the
	 *             string is unterminated.
	 */
	private static function skip_string( string $css, int $index ): int {
		$quote  = $css[ $index ];
		$length = strlen( $css );
		$i      = $index + 1;

		while ( $i < $length ) {
			$ch = $css[ $i ];
			if ( '\\' === $ch ) {
				$i += 2;
				continue;
			}
			if ( $ch === $quote ) {
				return $i + 1;
			}
			++$i;
		}

		return $length;
	}

	/**
	 * Return true when every selector in the comma-separated selector list
	 * reduces to legacy `.gc-spectra-*` tokens only.
	 *
	 * A selector is considered orphan-only when every class token it contains
	 * starts with `.gc-spectra-` AND it contains no element, ID, attribute,
	 * or pseudo target that could still match a DOM node on its own. This is
	 * the conservative interpretation: we keep anything ambiguous.
	 *
	 * @since 1.0.0
	 *
	 * @param string $selector_list Raw selector list (may contain commas).
	 * @return bool
	 */
	private static function selector_list_is_orphan_only( string $selector_list ): bool {
		$selector_list = trim( $selector_list );
		if ( '' === $selector_list ) {
			return false;
		}

		// Split on commas not inside parentheses / brackets (e.g. :is(a,b)).
		$selectors = self::split_selector_list( $selector_list );
		if ( empty( $selectors ) ) {
			return false;
		}

		foreach ( $selectors as $selector ) {
			if ( ! self::selector_is_orphan_only( $selector ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Split a selector list on top-level commas.
	 *
	 * @since 1.0.0
	 *
	 * @param string $selector_list Selector list.
	 * @return array<int, string>
	 */
	private static function split_selector_list( string $selector_list ): array {
		$parts   = array();
		$depth   = 0;
		$current = '';
		$length  = strlen( $selector_list );

		for ( $i = 0; $i < $length; $i++ ) {
			$ch = $selector_list[ $i ];

			if ( '(' === $ch || '[' === $ch ) {
				++$depth;
			} elseif ( ')' === $ch || ']' === $ch ) {
				--$depth;
			}

			if ( ',' === $ch && 0 === $depth ) {
				$trimmed = trim( $current );
				if ( '' !== $trimmed ) {
					$parts[] = $trimmed;
				}
				$current = '';
				continue;
			}

			$current .= $ch;
		}

		$trimmed = trim( $current );
		if ( '' !== $trimmed ) {
			$parts[] = $trimmed;
		}

		return $parts;
	}

	/**
	 * Determine whether a single (comma-free) selector is fully orphaned.
	 *
	 * Rules:
	 *   - Any class token that does NOT start with `.gc-spectra-` → keep.
	 *   - Any id (`#foo`), element (`div`, `a`), attribute (`[data-x]`),
	 *     or pseudo (`:hover`, `::before`) target whose subject compound
	 *     has no class anchor we recognize → keep (conservative).
	 *   - Every class token starts with `.gc-spectra-` AND the selector is
	 *     a pure class selector chain → strip.
	 *
	 * @since 1.0.0
	 *
	 * @param string $selector Single selector.
	 * @return bool
	 */
	private static function selector_is_orphan_only( string $selector ): bool {
		$selector = trim( $selector );
		if ( '' === $selector ) {
			return false;
		}

		// Extract class tokens (.foo, .gc-spectra-bar). Bracket-escaped class
		// names (`.text-\[\#ff0000\]`) also start with `.` — handled the same.
		if ( ! preg_match_all( '/\.[-_a-zA-Z0-9\\\\\[\]\#]+/', $selector, $matches ) ) {
			return false;
		}

		$classes = $matches[0];
		if ( empty( $classes ) ) {
			return false;
		}

		foreach ( $classes as $class_token ) {
			if ( 0 !== strpos( $class_token, '.gc-spectra-' ) ) {
				return false;
			}
		}

		// Strip class tokens, pseudo-classes/elements, and descendant/
		// combinator whitespace from the selector. Anything left (element
		// names, id, attribute selectors) means the selector could still
		// match a live DOM node and must be preserved.
		$residue = preg_replace( '/\.[-_a-zA-Z0-9\\\\\[\]\#]+/', '', $selector );
		if ( ! is_string( $residue ) ) {
			return false;
		}

		// Drop `::pseudo-element` then `:pseudo-class(...)` / `:pseudo-class`
		// — pseudos attach to a compound selector and never match alone.
		$residue = preg_replace( '/::[a-zA-Z][a-zA-Z0-9-]*/', '', $residue );
		$residue = is_string( $residue ) ? preg_replace( '/:[a-zA-Z][a-zA-Z0-9-]*(?:\([^\)]*\))?/', '', $residue ) : '';
		if ( ! is_string( $residue ) ) {
			return false;
		}

		// Collapse combinators / whitespace.
		$residue = trim( preg_replace( '/[\s>+~*&]+/', '', $residue ) ?? '' );

		return '' === $residue;
	}
}
