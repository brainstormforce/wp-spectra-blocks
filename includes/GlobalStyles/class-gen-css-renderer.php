<?php
/**
 * Renders the per-page imported-CSS payload (the `spectra_blocks_pro_gs_user_css`
 * post meta, schema v1) into a CSS string at enqueue time.
 *
 * The stored payload is PAGE-AGNOSTIC (no page id, no `!important`). Page
 * isolation comes from the per-post enqueue (the meta loads only on its own
 * post), so the renderer needs no page id in selectors. It applies a
 * CONTEXT-aware scope instead:
 *   - frontend: root `body`; classes `[class].{class}.{class}` (compound on the
 *     `[class]` attribute, class repeated for a (0,3,0) specificity lift —
 *     matches the gs-* element directly, with or without data-spectra-id);
 *     other selectors `body <…>`.
 *   - editor:   root `body.editor-styles-wrapper, div.editor-styles-wrapper`;
 *     classes/selectors descend from `.editor-styles-wrapper`.
 * Declarations are emitted CLEAN (no `!important`).
 *
 * Pure function of (payload, post_id, is_editor) — no side effects, cacheable.
 *
 * @package Spectra\GlobalStyles
 * @since   1.0.0
 */

namespace SpectraBlocks\GlobalStyles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GenCssRenderer.
 *
 * @since 1.0.0
 */
class GenCssRenderer {

	/**
	 * How many times the class token is repeated in a rendered class selector,
	 * to lift its specificity. Frontend `[class].{class}.{class}` = (0,3,0):
	 * it BEATS the static container defaults (`padding:10px` at (0,1,0),
	 * `width:100%` at (0,2,0)) and `body …` wrapperStyles, while staying BELOW
	 * the block-attribute CSS at (0,4,0) (the `ResponsiveControls` tripled
	 * selector `.wp-block-…×3[data-spectra-id]`) so a block's OWN padding/width
	 * still wins. Single source of truth — `Engine::render_user_classes()` reads
	 * this same value so per-page (meta) and site-wide (option) classes never
	 * drift in specificity.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public const SELECTOR_CLASS_REPEAT = 2;

	/**
	 * Build the repeated class-token portion of a selector
	 * (`.{class}` × {@see SELECTOR_CLASS_REPEAT}) for the specificity lift above.
	 * Callers prepend the scope prefix/joiner and append any state suffix.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Bare class name (no leading dot).
	 * @return string e.g. `.gs-foo.gs-foo`.
	 */
	public static function class_token( string $class_name ): string {
		return str_repeat( '.' . $class_name, self::SELECTOR_CLASS_REPEAT );
	}

	/**
	 * Render the payload to a CSS string for the given post id.
	 *
	 * INPUT — the schema-v1 payload (page-agnostic: no page id, no `!important`):
	 *
	 *   array(
	 *     'v'             => '1',
	 *     'imports'       => array( 'https://fonts.googleapis.com/css2?family=Lato' ),
	 *     'scopeVars'     => array( '--wp--style--global--content-size' => '1164px',
	 *                               '--wp--style--global--wide-size'    => '1280px' ),
	 *     'rootStyles'    => array( 'font-family' => 'DM Sans', 'color' => '#111', '--primary' => '#b36b2c' ),
	 *     'presetLock'    => array( '--wp--preset--color--primary' => '#b36b2c' ),
	 *     'variables'     => array( '--brand' => '#b36b2c' ),
	 *     'classes'       => array( 'gs-link' => array( 'default' => array( 'color' => 'var(--heading)' ),
	 *                                                    'hover'   => array( 'color' => '#b36b2c' ) ) ),
	 *     'wrapperStyles' => array( '.wp-block-spectra-icon svg' => array( 'width' => '1em' ) ),
	 *     'mediaQuery'    => array( '(max-width: 960px)' => array(
	 *                                  'classes'       => array( 'gs-x' => array( 'default' => array( 'gap' => '1rem' ) ) ),
	 *                                  'wrapperStyles' => array( '.x a' => array( 'display' => 'none' ) ) ) ),
	 *   )
	 *
	 * How each bucket is handled — scope depends on context (frontend vs editor):
	 *   imports       → `@import url("…");`  (first, verbatim)
	 *   scopeVars     → `<root> { … }`  (editor uses the WIDE content-size value)
	 *   rootStyles    → `<root> { … }`
	 *   presetLock    → `<root> { … }`
	 *   variables     → `<root> { … }`  (user custom CSS vars from `/custom-vars`)
	 *   classes       → frontend `[class].{class}{suffix} { … }` (COMPOUND on the `[class]` attribute — matches any element carrying the class);
	 *                   editor   `.editor-styles-wrapper .{class}{suffix} { … }` (descendant)
	 *                   (state → suffix via PSEUDO; a raw state like `[open]` is appended verbatim)
	 *   wrapperStyles → `<sel-prefix> {selector} { … }`
	 *   mediaQuery    → each wrapped in `@media {query} { …classes…  …wrapperStyles… }`
	 * where  `<root>`       = `body` (frontend) | `body.editor-styles-wrapper, div.editor-styles-wrapper` (editor)
	 *        `<sel-prefix>` = `body` (frontend) | `.editor-styles-wrapper` (editor)
	 * Page isolation comes from the per-post enqueue, so NO page id appears in any
	 * selector. Declarations are emitted CLEAN (no `!important`).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $payload   The decoded `spectra_blocks_pro_gs_user_css` meta (schema v1).
	 * @param int                 $post_id   Live post id (guard only; selectors carry no id).
	 * @param bool                $is_editor True in the block editor (admin) → editor scope.
	 * @param bool                $sitewide  True to render the site-wide scope (no per-post id guard).
	 * @return string CSS, or '' when there is nothing to render.
	 */
	public static function render( array $payload, int $post_id, bool $is_editor = false, bool $sitewide = false ): string {
		// Per-post render requires a real post id (page isolation comes from the
		// per-post enqueue). Site-wide render ($sitewide = true) is enqueued once
		// for the whole site, so there is no post id to gate on — the selectors
		// are page-agnostic either way, so the same scope logic applies.
		if ( ! $sitewide && $post_id <= 0 ) {
			return '';
		}

		// Per-page CSS is enqueued ONLY on its own post, so page isolation comes
		// from the enqueue — selectors need no page id. Context decides the scope:
		//
		// FRONTEND  root → `body`; classes → `[class].{class}` (COMPOUND on the
		// `[class]` attribute — present on ANY element that carries a
		// class, so the rule matches the gs-* element directly whether
		// or not it has data-spectra-id: core blocks + inline elements
		// included); other selectors → `body <selector>`.
		// EDITOR    root → `body.editor-styles-wrapper, div.editor-styles-wrapper`;
		// classes/selectors descend from `.editor-styles-wrapper` (the
		// canvas wrapper is an ancestor of everything).
		if ( $is_editor ) {
			$root_scope   = 'body.editor-styles-wrapper, div.editor-styles-wrapper';
			$class_prefix = '.editor-styles-wrapper';
			$class_joiner = ' ';
			$sel_prefix   = '.editor-styles-wrapper';
		} else {
			$root_scope   = 'body';
			$class_prefix = '[class]';
			$class_joiner = '';
			$sel_prefix   = 'body';
		}

		$parts = array();

		// 0. @import (web fonts) — MUST be first in the stylesheet.
		foreach ( self::string_list( $payload['imports'] ?? array() ) as $url ) {
			$parts[] = '@import url("' . $url . '");';
		}

		// 1. Content-width vars (synthetic) on the root. The editor uses the WIDE
		// value so the canvas isn't squished below the source's mobile breakpoint.
		$scope_vars = self::assoc( $payload['scopeVars'] ?? array() );
		if ( array() !== $scope_vars ) {
			$vars    = $is_editor ? self::editor_vars( $scope_vars ) : $scope_vars;
			$parts[] = $root_scope . ' { ' . self::vars_to_string( $vars ) . ' }';
		}

		// 2. Source root styling — the `:root`/`body` token graph AND base body
		// declarations (font, color, background, margin, …) on the root.
		$root_styles = self::assoc( $payload['rootStyles'] ?? array() );
		if ( array() !== $root_styles ) {
			$parts[] = $root_scope . ' { ' . self::decls_to_string( $root_styles ) . ' }';
		}

		// 3. Source preset lock — pins the source palette on the root.
		$preset_lock = self::assoc( $payload['presetLock'] ?? array() );
		if ( array() !== $preset_lock ) {
			$parts[] = $root_scope . ' { ' . self::vars_to_string( $preset_lock ) . ' }';
		}

		// 3b. User custom variables (the `/custom-vars` `variables` bucket) on the
		// root. Without this the vars authored in the GBS editor only appear via
		// the editor's live JS injection (liveVars.js `refreshCustomVarsCSS`) —
		// they never rendered on the front end or on a fresh editor load.
		$custom_vars = self::assoc( $payload['variables'] ?? array() );
		if ( array() !== $custom_vars ) {
			$parts[] = $root_scope . ' { ' . self::vars_to_string( $custom_vars ) . ' }';
		}

		// 4. Class rules (base + responsive breakpoint states) + 5. wrapper rules (base).
		$rendered = self::render_classes( self::assoc( $payload['classes'] ?? array() ), $class_prefix, $class_joiner );
		$parts[]  = $rendered['base'];
		$parts[]  = self::render_wrappers( self::assoc( $payload['wrapperStyles'] ?? array() ), $sel_prefix );

		// 6. Responsive: breakpoint-state rules (from the class lane) and the
		// payload's mediaQuery bucket (off-grid / wrapper-level) fold into ONE
		// map keyed by media condition, emitted mobile-first (ascending min-width).
		$by_media = $rendered['media'];
		foreach ( self::assoc( $payload['mediaQuery'] ?? array() ) as $query => $buckets ) {
			if ( ! is_string( $query ) || '' === $query || ! is_array( $buckets ) ) {
				continue;
			}
			$inner  = self::render_classes( self::assoc( $buckets['classes'] ?? array() ), $class_prefix, $class_joiner )['base'];
			$inner .= self::render_wrappers( self::assoc( $buckets['wrapperStyles'] ?? array() ), $sel_prefix );
			$inner  = trim( $inner );
			if ( '' !== $inner ) {
				$by_media[ $query ] = isset( $by_media[ $query ] ) ? trim( $by_media[ $query ] ) . "\n" . $inner : $inner;
			}
		}
		uksort(
			$by_media,
			static function ( string $a, string $b ): int {
				return StateResolver::media_order( $a ) <=> StateResolver::media_order( $b );
			}
		);
		foreach ( $by_media as $query => $rules ) {
			$rules = trim( $rules );
			if ( '' !== $rules ) {
				$parts[] = '@media ' . $query . " {\n" . $rules . "\n}";
			}
		}

		return trim( implode( "\n", array_filter( array_map( 'trim', $parts ) ) ) );
	}

	/**
	 * Render `className → state → declarations` into scoped class rules.
	 *
	 * The selector is `{prefix}{joiner}{classToken}{stateSuffix}`, where
	 * `{classToken}` repeats the class per {@see class_token} for the (0,3,0)
	 * specificity lift:
	 *   - frontend → prefix `[class]`, joiner `''` →
	 *     `[class].{class}.{class}` (COMPOUND on the `[class]` attribute — matches
	 *     any element carrying the class, with or without data-spectra-id);
	 *   - editor   → prefix `.editor-styles-wrapper`, joiner `' '` →
	 *     `.editor-styles-wrapper .{class}.{class}` (DESCENDANT — the canvas
	 *     wrapper is an ancestor of everything).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $classes className → state → {prop:value}.
	 * @param string              $prefix  Scope prefix.
	 * @param string              $joiner  '' (compound) or ' ' (descendant).
	 * @return array{base:string, media:array<string,string>} Base (non-responsive)
	 *         rules, plus a `media condition => rules` map for breakpoint states.
	 */
	private static function render_classes( array $classes, string $prefix, string $joiner ): array {
		$base  = '';
		$media = array();
		foreach ( $classes as $class_name => $states ) {
			if ( ! is_string( $class_name ) || '' === $class_name || ! is_array( $states ) ) {
				continue;
			}
			foreach ( $states as $state => $decls ) {
				$decls = self::assoc( $decls );
				if ( array() === $decls ) {
					continue;
				}
				$body = self::decls_to_string( $decls );
				if ( '' === $body ) {
					continue;
				}
				$resolved = StateResolver::resolve( (string) $state );
				$rule     = $prefix . $joiner . self::class_token( $class_name ) . $resolved['suffix'] . ' { ' . $body . ' }' . "\n";
				if ( '' === $resolved['media'] ) {
					$base .= $rule;
				} else {
					$media[ $resolved['media'] ] = ( $media[ $resolved['media'] ] ?? '' ) . $rule;
				}
			}
		}

		return array(
			'base'  => $base,
			'media' => $media,
		);
	}

	/**
	 * Render `fullSelector → declarations` into scoped wrapper rules.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $wrapper_styles selector → {prop:value}.
	 * @param string              $page_scope     Scope prefix (descendant).
	 * @return string
	 */
	private static function render_wrappers( array $wrapper_styles, string $page_scope ): string {
		$out = '';
		foreach ( $wrapper_styles as $selector => $decls ) {
			if ( ! is_string( $selector ) || '' === $selector ) {
				continue;
			}
			// Skip bare element-tag resets (`button`, `img`, `svg`, `video`, …)
			// captured from the source's Tailwind "Preflight" base layer. Emitted
			// site-wide as `body button {…}` / `.editor-styles-wrapper button {…}`,
			// they strip default chrome (border/padding/background) off EVERY
			// button and media element on the site — native Spectra/core blocks
			// included — not just the imported page. The gs-* utility classes
			// already carry all the styling the imported design needs, so these
			// blanket element resets are redundant and harmful. Only bare tag
			// selectors are dropped; class/compound/descendant wrapper selectors
			// (e.g. `.tdrx-hdr-nav a`) still render.
			if ( self::is_bare_tag_selector( $selector ) ) {
				continue;
			}
			$decls = self::assoc( $decls );
			$body  = self::decls_to_string( $decls );
			if ( '' !== $body ) {
				$out .= $page_scope . ' ' . $selector . ' { ' . $body . ' }' . "\n";
			}
		}

		return $out;
	}

	/**
	 * Whether a wrapper-style selector targets only bare HTML element tags — a
	 * single tag (`button`) or a comma-list of tags (`img, svg, video`) — with
	 * no class, id, attribute, pseudo, or combinator. Such selectors come from
	 * the source's Preflight/base reset and must not be applied site-wide.
	 *
	 * @since 1.0.0
	 *
	 * @param string $selector Wrapper-style selector.
	 * @return bool True when every comma-part is a lone tag name.
	 */
	private static function is_bare_tag_selector( string $selector ): bool {
		foreach ( explode( ',', $selector ) as $part ) {
			if ( 1 !== preg_match( '/^[a-z][a-z0-9]*$/i', trim( $part ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Flat declarations → `prop: value;` string. Emitted CLEAN — no `!important`
	 * (the per-page CSS has never used it; the scope + source order win the
	 * cascade). `!important`, if ever needed, is a deliberate future policy.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,string> $decls property => value.
	 * @return string
	 */
	private static function decls_to_string( array $decls ): string {
		$out = '';
		foreach ( $decls as $prop => $value ) {
			if ( ! is_string( $prop ) || ! is_string( $value ) || '' === $prop || '' === $value ) {
				continue;
			}
			$out .= $prop . ': ' . $value . '; ';
		}

		return trim( $out );
	}

	/**
	 * `--var: value;` string for a CSS-variable map (clean, no `!important`).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $vars property => value (non-string values are skipped).
	 * @return string
	 */
	private static function vars_to_string( array $vars ): string {
		$out = '';
		foreach ( $vars as $prop => $value ) {
			if ( ! is_string( $prop ) || ! is_string( $value ) || '' === $prop || '' === $value ) {
				continue;
			}
			$out .= $prop . ': ' . $value . '; ';
		}

		return trim( $out );
	}

	/**
	 * Editor variant of the scope vars: the editor canvas clips block layout to
	 * `--wp--style--global--content-size`, so use the WIDE value there to avoid
	 * squishing imported sections below their mobile breakpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,string> $vars Front-end scope vars.
	 * @return array<string,string>
	 */
	private static function editor_vars( array $vars ): array {
		if ( isset( $vars['--wp--style--global--content-size'], $vars['--wp--style--global--wide-size'] ) ) {
			$vars['--wp--style--global--content-size'] = $vars['--wp--style--global--wide-size'];
		}

		return $vars;
	}

	/**
	 * Coerce to an associative array (defensive against malformed payloads).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return array<string,mixed>
	 */
	private static function assoc( $value ): array {
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Coerce to a list of non-empty strings.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string[]
	 */
	private static function string_list( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$value,
				static function ( $item ) {
					return is_string( $item ) && '' !== $item;
				}
			)
		);
	}
}
