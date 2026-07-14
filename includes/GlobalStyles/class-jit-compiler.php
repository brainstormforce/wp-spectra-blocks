<?php
/**
 * Tailwind-style JIT class compiler for Global Styles.
 *
 * Parses className tokens emitted by SpectraGen (ERA) and resolves them to
 * CSS rules. Three token families are supported:
 *
 *   1. Known utility classes — looked up in {@see ClassRegistry}.
 *   2. Per-utility arbitrary bracket values — `{prefix}-[{value}]` (Tailwind
 *      JIT per-utility syntax). The prefix is mapped to a single CSS property
 *      (or a small set of properties for shorthands like `px`) via
 *      {@see self::PREFIX_MAP}; the bracket value is strictly sanitized and
 *      `var(...)` references are rejected. Examples: `max-h-[80vh]`,
 *      `px-[71px]`, `text-[#ff0000]`, `rounded-[12px]`.
 *   3. Variant-prefixed tokens — `responsive:state:pseudo:class` in canonical
 *      order; e.g. `md:hover:scale-105`, `md:hover:translate-y-[-4px]`.
 *
 * Breakpoints are Tailwind-parity mobile-first (upward-cascading):
 *   sm:  (min-width:640px)
 *   md:  (min-width:768px)
 *   lg:  (min-width:1024px)
 *   xl:  (min-width:1280px)
 *   2xl: (min-width:1536px)
 *
 * Pseudo-elements (`before:`, `after:`) auto-inject `content:''` when the
 * author has not supplied their own content declaration.
 *
 * Full-property brackets (`[property:value]`) compile through
 * `resolve_tw_arbitrary_property()`, gated by an explicit
 * {@see self::ARBITRARY_PROPERTY_ALLOWLIST}. Properties outside the
 * allowlist (`content`, `cursor`, `background-image`, `--*`, etc.) are
 * rejected to avoid URL injection / variable bleed.
 *
 * All compiled values pass through {@see Sanitizer} in strict mode for
 * property allowlisting, `var(...)` rejection, and script/URL hardening.
 *
 * @package Spectra\GlobalStyles
 * @since   1.0.0
 */

namespace SpectraBlocks\GlobalStyles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JitCompiler.
 *
 * @since 1.0.0
 */
class JitCompiler {

	/**
	 * Responsive variant → media query.
	 *
	 * Tailwind-parity mobile-first: each breakpoint opens an upward-cascading
	 * min-width window. `md:text-5xl` applies at 768px and every larger width.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const RESPONSIVE_VARIANTS = array(
		'sm'  => '(min-width: 640px)',
		'md'  => '(min-width: 768px)',
		'lg'  => '(min-width: 1024px)',
		'xl'  => '(min-width: 1280px)',
		'2xl' => '(min-width: 1536px)',
	);

	/**
	 * State variants → pseudo-class suffix.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const STATE_VARIANTS = array(
		'hover'                => ':hover',
		'focus'                => ':focus',
		'focus-visible'        => ':focus-visible',
		'focus-within'         => ':focus-within',
		'active'               => ':active',
		'disabled'             => ':disabled',
		'visited'              => ':visited',
		'checked'              => ':checked',
		// Structural pseudo-classes (Tailwind v4 parity).
		'first'                => ':first-child',
		'last'                 => ':last-child',
		'only'                 => ':only-child',
		'odd'                  => ':nth-child(odd)',
		'even'                 => ':nth-child(even)',
		'first-of-type'        => ':first-of-type',
		'last-of-type'         => ':last-of-type',
		'only-of-type'         => ':only-of-type',
		'empty'                => ':empty',
		// Form-state pseudo-classes (Tailwind v4 parity).
		'required'             => ':required',
		'valid'                => ':valid',
		'invalid'              => ':invalid',
		'user-valid'           => ':user-valid',
		'user-invalid'         => ':user-invalid',
		'read-only'            => ':read-only',
		'placeholder-shown'    => ':placeholder-shown',
		'default'              => ':default',
		'indeterminate'        => ':indeterminate',
		'autofill'             => ':autofill, :-webkit-autofill',
		// Interaction / structural.
		'target'               => ':target',
		// `open` matches both the HTML `[open]` attribute and the new `:open`
		// pseudo-class (details/dialog). Comma-separated union guarantees
		// coverage on current browsers that don't implement `:open` yet.
		'open'                 => '[open], :open',
		// ARIA-state variants. Runtime-set ARIA attribute selectors chain onto
		// the class selector exactly like pseudo-class suffixes do, so these
		// flow through the same selector-build path.
		'aria-selected'        => '[aria-selected="true"]',
		'aria-expanded'        => '[aria-expanded="true"]',
		'aria-checked'         => '[aria-checked="true"]',
		'aria-disabled'        => '[aria-disabled="true"]',
		'aria-pressed'         => '[aria-pressed="true"]',
		'aria-current'         => '[aria-current="page"]',
		'aria-busy'            => '[aria-busy="true"]',
		'aria-grabbed'         => '[aria-grabbed="true"]',
		'aria-hidden'          => '[aria-hidden="true"]',
		'aria-invalid'         => '[aria-invalid="true"]',
		'aria-modal'           => '[aria-modal="true"]',
		'aria-multiline'       => '[aria-multiline="true"]',
		'aria-multiselectable' => '[aria-multiselectable="true"]',
		'aria-readonly'        => '[aria-readonly="true"]',
		'aria-required'        => '[aria-required="true"]',
	);

	/**
	 * Pseudo-element variants → selector suffix.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const PSEUDO_ELEMENT_VARIANTS = array(
		'before'          => '::before',
		'after'           => '::after',
		'placeholder'     => '::placeholder',
		'selection'       => '::selection',
		'marker'          => '::marker',
		'first-line'      => '::first-line',
		'first-letter'    => '::first-letter',
		// v4 — <details> accordion-style animation target.
		'details-content' => '::details-content',
	);

	/**
	 * Default data-attribute bare-form variants.
	 *
	 * Tailwind v4 promotes common data-* attributes to first-class variant
	 * prefixes (e.g. `data-open:`, `data-selected:`). Each key is the variant
	 * name (without the `data-` prefix); the value is the attribute selector
	 * suffix to append to the class selector.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const DATA_VARIANTS_DEFAULT = array(
		'open'     => '[data-open="true"]',
		'closed'   => '[data-closed="true"]',
		'selected' => '[data-selected="true"]',
		'checked'  => '[data-checked="true"]',
		'active'   => '[data-active="true"]',
		'disabled' => '[data-disabled="true"]',
		'hover'    => '[data-hover="true"]',
		'focus'    => '[data-focus="true"]',
		'pressed'  => '[data-pressed="true"]',
		'expanded' => '[data-expanded="true"]',
	);

	/**
	 * Motion / print / forced-colors @media wrappers (Tailwind v4 parity).
	 *
	 * Keys are the variant prefix; values are the @media condition string that
	 * would be wrapped with `@media ` at emit time.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const MEDIA_FEATURE_VARIANTS = array(
		'motion-safe'        => '(prefers-reduced-motion: no-preference)',
		'motion-reduce'      => '(prefers-reduced-motion: reduce)',
		'print'              => 'print',
		'forced-colors'      => '(forced-colors: active)',
		// Tailwind v4 pointer-class media features.
		// @since 1.0.0.
		'pointer-fine'       => '(pointer: fine)',
		'pointer-coarse'     => '(pointer: coarse)',
		'pointer-none'       => '(pointer: none)',
		'any-pointer-fine'   => '(any-pointer: fine)',
		'any-pointer-coarse' => '(any-pointer: coarse)',
		'any-pointer-none'   => '(any-pointer: none)',
	);

	/**
	 * Ancestor-selector variants (Tailwind v4 parity).
	 *
	 * Each entry provides the ancestor selector that gets prepended to the
	 * compiled class selector (descendant combinator — whitespace). `dark:`,
	 * `rtl:`, `ltr:` are implemented as ancestor selectors; the `.dark`
	 * toggle and `[dir]` attribute are set externally.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const ANCESTOR_VARIANTS = array(
		'dark' => '.dark',
		'rtl'  => '[dir="rtl"]',
		'ltr'  => '[dir="ltr"]',
	);

	/**
	 * Container-query variant breakpoints (Tailwind v4 `@sm:`, `@md:`, …).
	 *
	 * Keys are the variant prefix AFTER the leading `@`; values are the
	 * min-width portion of the resulting `@container (min-width: Nrem)` at-rule.
	 * Authors pair these with a `@container` utility on the ancestor to scope
	 * the inline-size query; a bare `@container` (unscoped) also matches.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const CONTAINER_QUERY_BREAKPOINTS = array(
		'sm'  => '24rem',
		'md'  => '28rem',
		'lg'  => '32rem',
		'xl'  => '36rem',
		'2xl' => '42rem',
		'3xl' => '48rem',
		'4xl' => '56rem',
		'5xl' => '64rem',
		'6xl' => '72rem',
		'7xl' => '80rem',
	);

	/**
	 * Max bracket segments per token (guards against adversarial inputs).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const MAX_VARIANTS_PER_TOKEN = 5;

	/**
	 * Max class-token length (guards against adversarial oversized inputs).
	 *
	 * The bracket *value* is already capped by {@see Sanitizer::sanitize_css_value},
	 * but the *selector* is built from the raw token — so an oversized payload
	 * would otherwise survive in the emitted selector. Tokens longer than this
	 * are skipped entirely before any selector is built.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const MAX_TOKEN_LENGTH = 2000;

	/**
	 * GBS grammar version. Folded into the metadata ETag so constant-only
	 * grammar changes bust SaaS-side caches.
	 *
	 * Version 2 (2026-06-12): `banned_attrs` REMOVED from the export. That block
	 * was emission POLICY (which attrs the conversion pipeline may emit,
	 * on which namespaces, with which third-party exemptions) hand-typed
	 * into this plugin purely to be exported — nothing in the plugin
	 * consumed it, and it spoke for other plugins' namespaces (`srfm/`).
	 * Policy is owned by the pipeline (`config/spectra-contract.json` +
	 * StrictAttrValidator in the SaaS repo, byte-pinned into the brain).
	 * This export carries GRAMMAR only — facts derived from constants the
	 * compiler itself consumes (variant maps, arbitrary-value allowlist),
	 * so the export can never drift from actual rendering behavior.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const CONTRACT_VERSION = 2;

	/**
	 * Export the machine-readable styling GRAMMAR: the variant vocabulary
	 * (derived from the live compile maps — never re-typed) and the
	 * arbitrary-value rules (the same constants {@see self::compile()}
	 * enforces). Consumed by the SaaS converter + brain via the
	 * `/global-styles/metadata` endpoint (`contract` key). Emission
	 * policy (banned attrs / scopes / exemptions) deliberately does NOT
	 * live here — see the CONTRACT_VERSION docblock.
	 *
	 * @since 1.0.0
	 *
	 * @return array{version:int, variants:array{responsive:array<int,string>, state:array<int,string>, pseudo_element:array<int,string>}, arbitrary:array{property_allowlist:array<int,string>, max_variants_per_token:int}}
	 */
	public static function export_contract(): array {
		return array(
			'version'   => self::CONTRACT_VERSION,
			'variants'  => array(
				'responsive'     => array_keys( self::RESPONSIVE_VARIANTS ),
				'state'          => array_keys( self::STATE_VARIANTS ),
				'pseudo_element' => array_keys( self::PSEUDO_ELEMENT_VARIANTS ),
			),
			'arbitrary' => array(
				'property_allowlist'     => self::ARBITRARY_PROPERTY_ALLOWLIST,
				'max_variants_per_token' => self::MAX_VARIANTS_PER_TOKEN,
			),
		);
	}

	/**
	 * Per-utility bracket prefix → CSS property mapping.
	 *
	 * The key is the Tailwind utility prefix; the value is either a single CSS
	 * property string or an array of property strings (for shorthands like
	 * `px` which emits `padding-left` + `padding-right`).
	 *
	 * `text` and `bg` are shape-aware at compile time: a color-shaped value
	 * maps to `color` / `background-color`; a length-shaped value on `text`
	 * maps to `font-size`. See {@see self::resolve_per_utility_bracket()}.
	 *
	 * @since 1.0.0
	 * @var array<string, string|array<int, string>>
	 */
	const PREFIX_MAP = array(
		// Padding.
		'p'                   => 'padding',
		'px'                  => array( 'padding-left', 'padding-right' ),
		'py'                  => array( 'padding-top', 'padding-bottom' ),
		'pt'                  => 'padding-top',
		'pr'                  => 'padding-right',
		'pb'                  => 'padding-bottom',
		'pl'                  => 'padding-left',
		// Margin.
		'm'                   => 'margin',
		'mx'                  => array( 'margin-left', 'margin-right' ),
		'my'                  => array( 'margin-top', 'margin-bottom' ),
		'mt'                  => 'margin-top',
		'mr'                  => 'margin-right',
		'mb'                  => 'margin-bottom',
		'ml'                  => 'margin-left',
		// Gap.
		'gap'                 => 'gap',
		'gap-x'               => 'column-gap',
		'gap-y'               => 'row-gap',
		// Sizing.
		'w'                   => 'width',
		'h'                   => 'height',
		'min-w'               => 'min-width',
		'min-h'               => 'min-height',
		'max-w'               => 'max-width',
		'max-h'               => 'max-height',
		'size'                => array( 'width', 'height' ),
		// Aspect ratio. `aspect-square` / `aspect-video` live in the known-class
		// registry (emitted as named utilities). This prefix entry enables the
		// arbitrary bracket form `aspect-[4/5]` / `aspect-[3/4]` / `aspect-[0.8]`
		// via resolve_per_utility_bracket. The bracket body passes the sanitizer
		// because `/` is in the allow-list; selector escaping CSS-escapes `/` and
		// `[`/`]` in the class name but the declaration value preserves the slash
		// literal.
		'aspect'              => 'aspect-ratio',
		// Position.
		'top'                 => 'top',
		'right'               => 'right',
		'bottom'              => 'bottom',
		'left'                => 'left',
		'inset'               => 'inset',
		'inset-x'             => array( 'left', 'right' ),
		'inset-y'             => array( 'top', 'bottom' ),
		'z'                   => 'z-index',
		// Border radius.
		'rounded'             => 'border-radius',
		'rounded-t'           => array( 'border-top-left-radius', 'border-top-right-radius' ),
		'rounded-r'           => array( 'border-top-right-radius', 'border-bottom-right-radius' ),
		'rounded-b'           => array( 'border-bottom-left-radius', 'border-bottom-right-radius' ),
		'rounded-l'           => array( 'border-top-left-radius', 'border-bottom-left-radius' ),
		'rounded-tl'          => 'border-top-left-radius',
		'rounded-tr'          => 'border-top-right-radius',
		'rounded-br'          => 'border-bottom-right-radius',
		'rounded-bl'          => 'border-bottom-left-radius',
		// SVG paint utilities. Shape-aware so bracket forms dispatch correctly:
		// `fill-[#ff0000]`    → fill color
		// `fill-[none]`       → fill keyword (rare, but legal)
		// `stroke-[#2b5d82]`  → stroke color
		// `stroke-[3px]`      → stroke-width (length)
		// `stroke-[4]`        → stroke-width (unitless numeric)
		// Keyword forms (`fill-none`, `stroke-none`) and the compact numeric
		// stroke widths (`stroke-{0|1|2|4|8}`) are handled by
		// `resolve_tw_svg_paint` before bracket resolution kicks in.
		'fill'                => '__shape:fill',
		'stroke'              => '__shape:stroke',
		// Border width / color (shape-aware: color value → border-color; length → border-width).
		'border'              => '__shape:border',
		'border-t'            => 'border-top-width',
		'border-r'            => 'border-right-width',
		'border-b'            => 'border-bottom-width',
		'border-l'            => 'border-left-width',
		'border-x'            => array( 'border-left-width', 'border-right-width' ),
		'border-y'            => array( 'border-top-width', 'border-bottom-width' ),
		// Outline — outline-offset entry placed BEFORE 'outline' so bracket form
		// `outline-offset-[Npx]` matches the longer prefix. resolve_per_utility_bracket
		// uses the first `-[` position to split, so the prefix for `outline-offset-[2px]`
		// is `outline-offset` (longer key present in PREFIX_MAP → direct dispatch).
		// Generic `outline-[...]` dispatches via shape-aware (color → outline-color,
		// length → outline-width).
		'outline-offset'      => 'outline-offset',
		'outline'             => '__shape:outline',
		// Typography.
		'leading'             => 'line-height',
		'tracking'            => 'letter-spacing',
		'indent'              => 'text-indent',
		// Font family — bracket value is the CSS font-family list.
		// Example: `font-[Georgia,_serif]` → `font-family: Georgia, serif`.
		'font'                => 'font-family',
		// Colors / typography shape-aware keys (resolved dynamically).
		'text'                => '__shape:text',
		'bg'                  => '__shape:bg',
		// Flex / grid.
		'basis'               => 'flex-basis',
		'grow'                => 'flex-grow',
		'shrink'              => 'flex-shrink',
		'order'               => 'order',
		'grid-cols'           => 'grid-template-columns',
		'grid-rows'           => 'grid-template-rows',
		'col-start'           => 'grid-column-start',
		'col-end'             => 'grid-column-end',
		'row-start'           => 'grid-row-start',
		'row-end'             => 'grid-row-end',
		// Effects.
		'opacity'             => 'opacity',
		'shadow'              => 'box-shadow',
		// Transitions.
		'duration'            => 'transition-duration',
		'delay'               => 'transition-delay',
		'ease'                => 'transition-timing-function',
		// Clipping / masking — unblocks decorative shapes (hexagons, blobs, etc.).
		// Values travel through Sanitizer::sanitize_css_value so `url(...)` and
		// keyword forms are accepted; polygon/inset/circle/ellipse functions are
		// recognized by the math-fn branch of the sanitizer.
		'clip-path'           => 'clip-path',
		'mask-image'          => 'mask-image',
		// Tailwind v4 mask sub-properties — bracket forms only (no keyword
		// presets in the registry to keep opcache footprint flat). Authors use
		// `mask-clip-[border-box]`, `mask-size-[200px_100%]`, etc.
		// @since 1.0.0.
		'mask-clip'           => 'mask-clip',
		'mask-origin'         => 'mask-origin',
		'mask-position'       => 'mask-position',
		'mask-repeat'         => 'mask-repeat',
		'mask-size'           => 'mask-size',
		'mask-mode'           => 'mask-mode',
		'mask-composite'      => 'mask-composite',
		'mask-type'           => 'mask-type',
		// Blend modes (Tailwind v4) — `mix-blend-[multiply]`, `bg-blend-[screen]`.
		// @since 1.0.0.
		'mix-blend'           => 'mix-blend-mode',
		'bg-blend'            => 'background-blend-mode',
		// Background clip (Tailwind v4 `bg-clip-text` etc.) — bracket form
		// resolves via this entry. The keyword `bg-clip-text` itself is the
		// bracket form `bg-clip-[text]` once invoked.
		// @since 1.0.0.
		'bg-clip'             => 'background-clip',
		// Performance / accessibility (Tailwind v4).
		// @since 1.0.0.
		'content-visibility'  => 'content-visibility',
		'isolation'           => 'isolation',
		'forced-color-adjust' => 'forced-color-adjust',
		// Typography (Tailwind v4).
		// @since 1.0.0.
		'font-stretch'        => 'font-stretch',
		// Container queries — `container-name-[card]` etc. Keyword presets
		// were intentionally deferred (see ClassRegistry).
		// @since 1.0.0.
		'container-name'      => 'container-name',
		// Multi-column layout.
		'columns'             => 'columns',
		// Generated content — enables bracket forms like `before:content-['']`
		// and `before:content-[attr(data-label)]`. The sanitizer allows `attr(...)`
		// via the math-fn branch; quoted strings pass through intact.
		'content'             => 'content',
		// Hints and color controls.
		'will-change'         => 'will-change',
		'accent'              => 'accent-color',
		'caret'               => 'caret-color',
		// 3D transforms — pairs with existing rotate/scale/translate/skew composition.
		'perspective'         => 'perspective',
	);

	/**
	 * Filter-family prefix map — function name keyed by Tailwind utility prefix.
	 *
	 * Tailwind's filter utilities wrap the bracket value in a CSS filter
	 * function and emit a `filter: ...` declaration. e.g.:
	 *   `blur-[100px]`        → `filter: blur(100px);`
	 *   `brightness-[1.2]`    → `filter: brightness(1.2);`
	 *   `hue-rotate-[45deg]`  → `filter: hue-rotate(45deg);`
	 *   `drop-shadow-[0_2px_4px_#000]` → `filter: drop-shadow(0 2px 4px #000);`
	 *
	 * The named scale (`blur-3xl`, `brightness-50`, etc.) is registered in
	 * {@see ClassRegistry} as static utilities. This map covers the bracket
	 * (arbitrary-value) form only.
	 *
	 * Backdrop variants emit `backdrop-filter:` instead of `filter:`.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const FILTER_FN_MAP = array(
		'blur'        => 'blur',
		'brightness'  => 'brightness',
		'contrast'    => 'contrast',
		'grayscale'   => 'grayscale',
		'hue-rotate'  => 'hue-rotate',
		'invert'      => 'invert',
		'saturate'    => 'saturate',
		'sepia'       => 'sepia',
		'drop-shadow' => 'drop-shadow',
	);

	/**
	 * Backdrop-filter prefix map. Same shape as {@see self::FILTER_FN_MAP}
	 * but emits `backdrop-filter:` (and the legacy `-webkit-backdrop-filter:`
	 * counterpart for Safari).
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const BACKDROP_FILTER_FN_MAP = array(
		'backdrop-blur'       => 'blur',
		'backdrop-brightness' => 'brightness',
		'backdrop-contrast'   => 'contrast',
		'backdrop-grayscale'  => 'grayscale',
		'backdrop-hue-rotate' => 'hue-rotate',
		'backdrop-invert'     => 'invert',
		'backdrop-saturate'   => 'saturate',
		'backdrop-sepia'      => 'sepia',
		'backdrop-opacity'    => 'opacity',
	);

	/**
	 * In-memory memoization of compiled tokens within a request.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $memo = array();

	/**
	 * Reset in-memory memoization (tests + cache invalidation).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function reset_memo(): void {
		self::$memo = array();
	}

	/**
	 * Returns the per-utility bracket prefix → CSS property map.
	 *
	 * Exposed for external consumers (SaaS mirror, tests, tooling) that need
	 * the authoritative list of bracket-capable utility prefixes without
	 * depending on the class constant directly.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string|array<int, string>>
	 */
	public static function get_prefix_map(): array {
		return self::PREFIX_MAP;
	}

	/**
	 * Compile a set of className strings into a single CSS stylesheet.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $class_strings Raw className attribute values.
	 * @return string Compiled CSS (may be empty).
	 */
	public static function compile( array $class_strings ): string {
		$tokens = self::collect_tokens( $class_strings );

		if ( empty( $tokens ) ) {
			return '';
		}

		/**
		 * Filters the class tokens prior to compilation.
		 *
		 * Pro (or third-party code) can rewrite tokens — e.g. alias expansion,
		 * deprecated-token migration — before CSS resolution runs.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int, string> $tokens Deduplicated class tokens.
		 */
		$tokens = apply_filters( 'spectra_gs_jit_before_compile', $tokens );

		if ( ! is_array( $tokens ) ) {
			return '';
		}

		$known = self::get_known_classes();
		// Buckets keyed by `"<media>||<supports>||<container>||<starting>"` so
		// tokens that share the same at-rule wrapper(s) merge into one output
		// block. Each of the four at-rule families composes independently.
		$by_wrapper = array();
		$base_rule  = array();

		foreach ( $tokens as $token ) {
			if ( ! is_string( $token ) || '' === $token ) {
				continue;
			}

			$rule = self::compile_token( $token, $known );
			if ( null === $rule ) {
				// Never silent (SaaS remediation plan 1C/Phase-3B; audit
				// CSS-4/AR-1): an unresolved token paints NOTHING on the
				// frontend, and without a log a SaaS-side "valid" class is
				// an undiagnosable no-op. Counted + sampled (1-in-10) so a
				// page full of typos doesn't flood the log; gated on
				// WP_DEBUG by default, filterable for prod telemetry.
				static $unresolved_count = 0;
				++$unresolved_count;
				$should_log = apply_filters(
					'spectra_gs_jit_log_unresolved_tokens',
					defined( 'WP_DEBUG' ) && WP_DEBUG
				);
				if ( $should_log && 1 === ( $unresolved_count % 10 ) ) {
					error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						sprintf(
							'[spectra-gs-jit] unresolved class token "%s" produced no CSS (sampled 1/10; %d unresolved so far this request)',
							$token,
							$unresolved_count
						)
					);
				}
				continue;
			}

			$media     = isset( $rule['media'] ) ? (string) $rule['media'] : '';
			$supports  = isset( $rule['supports'] ) ? (string) $rule['supports'] : '';
			$container = isset( $rule['container'] ) ? (string) $rule['container'] : '';
			$starting  = ! empty( $rule['starting'] );

			if ( '' === $media && '' === $supports && '' === $container && ! $starting ) {
				$base_rule[] = $rule['css'];
				continue;
			}

			$key = $media . '||' . $supports . '||' . $container . '||' . ( $starting ? '1' : '0' );
			if ( ! isset( $by_wrapper[ $key ] ) ) {
				$by_wrapper[ $key ] = array(
					'media'     => $media,
					'supports'  => $supports,
					'container' => $container,
					'starting'  => $starting,
					'rules'     => array(),
				);
			}
			$by_wrapper[ $key ]['rules'][] = $rule['css'];
		}

		$parts = array();

		if ( ! empty( $base_rule ) ) {
			$parts[] = implode( "\n", $base_rule );
		}

		// Sort buckets by ascending media min-width so mobile-first responsive
		// variants cascade correctly: lg: rules emit AFTER md: rules in the
		// stylesheet, so at viewports matching both lg: and md: media queries
		// the lg: rule wins on source-order tie-break (same specificity).
		// Without this sort, buckets emit in first-encounter order which can
		// place md: after lg: in the stylesheet — md: would then win at wide
		// viewports and large-screen typography would silently not apply.
		//
		// Buckets without a `(min-width: …)` media (e.g. supports-only or
		// container-only) sort to position 0 so they emit before any breakpoint
		// rules.
		//
		// @since 1.0.0.
		uasort(
			$by_wrapper,
			static function ( $a, $b ): int {
				$aMin = self::extract_min_width( (string) ( $a['media'] ?? '' ) );
				$bMin = self::extract_min_width( (string) ( $b['media'] ?? '' ) );
				return $aMin <=> $bMin;
			}
		);

		foreach ( $by_wrapper as $bucket ) {
			$inner = implode( "\n", $bucket['rules'] );
			// Wrap innermost → outermost so the emitted nesting reads:
			// @media { @supports { @container { @starting-style { rules } } } }
			// Each wrapper is optional; only the ones set on this bucket are
			// emitted. `@starting-style` is the most inner so animation
			// starting values apply only inside whatever conditional set
			// their parent wrappers.
			if ( $bucket['starting'] ) {
				$inner = "@starting-style {\n" . $inner . "\n}";
			}
			if ( '' !== $bucket['container'] ) {
				// Empty container body = `@container { … }` (unscoped, no size).
				$prefix = '' === $bucket['container'] ? '@container' : ( '@container ' . $bucket['container'] );
				$inner  = $prefix . " {\n" . $inner . "\n}";
			}
			if ( '' !== $bucket['supports'] ) {
				$inner = '@supports ' . $bucket['supports'] . " {\n" . $inner . "\n}";
			}
			if ( '' !== $bucket['media'] ) {
				$inner = '@media ' . $bucket['media'] . " {\n" . $inner . "\n}";
			}
			$parts[] = $inner;
		}

		$compiled = implode( "\n\n", $parts );

		/**
		 * Filters the final compiled JIT CSS before it is cached/emitted.
		 *
		 * @since 1.0.0
		 *
		 * @param string             $compiled Compiled CSS string.
		 * @param array<int, string> $tokens   Source tokens (post-filter).
		 */
		$filtered = apply_filters( 'spectra_gs_jit_compiled_css', $compiled, $tokens );

		return is_string( $filtered ) ? $filtered : '';
	}

	/**
	 * Compile a single class token to a CSS rule descriptor.
	 *
	 * Returns ['media' => string, 'supports' => string, 'css' => string] on
	 * success, or null if the token is unresolvable (unknown utility) or
	 * sanitized away. Callers that pre-date the `supports` field should
	 * default-empty-string it when absent.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $token Class token (may contain variants).
	 * @param array<string, string> $known Known utility class map.
	 * @return array{media:string, supports:string, container:string, starting:bool, css:string}|null
	 */
	public static function compile_token( string $token, array $known ) {
		if ( isset( self::$memo[ $token ] ) ) {
			$cached = self::$memo[ $token ];
			return '' === $cached ? null : json_decode( $cached, true );
		}

		// Oversized tokens are skipped before any selector is built — the bracket
		// value is capped downstream by the sanitizer, but the selector is derived
		// from the raw token, so an adversarial payload would otherwise survive there.
		if ( strlen( $token ) > self::MAX_TOKEN_LENGTH ) {
			self::$memo[ $token ] = '';
			return null;
		}

		$parsed = self::parse_token( $token );
		if ( null === $parsed ) {
			self::$memo[ $token ] = '';
			return null;
		}

		$declarations = self::resolve_declarations( $parsed['base'], $known );
		if ( '' === $declarations ) {
			self::$memo[ $token ] = '';
			return null;
		}

		// Detect the LOW_PRIORITY sentinel from fallback resolvers and strip
		// it — the selector for this token will be wrapped in `:where()` below
		// so customizerCss / author overrides at normal class specificity win.
		$low_priority = false;
		if ( 0 === strpos( $declarations, self::LOW_PRIORITY_MARKER ) ) {
			$low_priority = true;
			$declarations = substr( $declarations, strlen( self::LOW_PRIORITY_MARKER ) );
		}

		if ( '' !== $parsed['pseudo_element'] ) {
			$declarations = self::maybe_inject_content( $declarations );
		}

		// Tailwind `!important` modifier — applied after all resolvers so it
		// covers every path uniformly (PREFIX_MAP, arbitrary-property,
		// transform/ring/gradient var-composition, known-class registry).
		// See `apply_important_to_declarations()` for the per-declaration
		// splitter + idempotent injection.
		if ( ! empty( $parsed['important'] ) ) {
			$declarations = self::apply_important_to_declarations( $declarations );
		}

		// Specificity ladder: `:root .1.0.0.x.x` gives 0,0,5,1 — enough to beat
		// Spectra's own class-tripled block defenders (e.g.
		// `.wp-block-spectra-container.wp-block-spectra-container.wp-block-spectra-container[data-spectra-id="..."]`
		// which computes to 0,0,4,0). Without this, utility classes on
		// container blocks (like `absolute`, `h-[600px]`, `position: fixed`)
		// lose the cascade to Spectra's hardcoded `position: relative` /
		// `height: auto` / `padding: ...` locks, even at higher source order.
		// Five repetitions is the minimum that reliably wins against the
		// 0,0,4,0 defender.
		//
		// EXCEPT when the declaration came from a low-priority resolver (e.g.
		// the `animate-<ident>` fallback): those wrap in `:where()` so author
		// overrides at normal class specificity take precedence.
		$escaped = self::escape_selector( $token );

		// Build the class-selector core (without the `:root ` head). For the
		// low-priority path we emit a single `:where(.cls)` and leave the
		// head empty; ancestor variants, when used with low-priority, prepend
		// directly with a space combinator.
		if ( $low_priority ) {
			$head = '';
			$core = ':where(.' . $escaped . ')';
		} else {
			$head = ':root';
			$core = '.' . $escaped . '.' . $escaped . '.' . $escaped . '.' . $escaped . '.' . $escaped;
		}

		foreach ( $parsed['states'] as $state ) {
			$core .= $state;
		}
		if ( '' !== $parsed['pseudo_element'] ) {
			$core .= $parsed['pseudo_element'];
		}
		if ( '' !== $parsed['descendant_suffix'] ) {
			$core .= $parsed['descendant_suffix'];
		}

		// Arbitrary-selector templates — `[&…]:` variants. Each template has
		// `&` replaced by the full compiled class-selector core. Multiple
		// templates stack by progressive substitution.
		if ( ! empty( $parsed['arbitrary_templates'] ) ) {
			foreach ( $parsed['arbitrary_templates'] as $template ) {
				$core = str_replace( '&', $core, $template );
			}
		}

		// Ancestor selectors (`dark:`, `rtl:`, `ltr:`, `group-*:`) and peer
		// sibling selectors (`peer-*:`) compose BETWEEN `:root` and the class
		// selector. This matches Tailwind's semantics (`.dark .utility`) while
		// preserving the plugin's `:root` specificity anchor. Peers emit
		// their sibling combinator instead of a descendant combinator.
		$mid = '';
		foreach ( $parsed['ancestors'] as $ancestor ) {
			$mid .= ' ' . $ancestor;
		}
		foreach ( $parsed['peers'] as $peer ) {
			$mid .= ' ' . $peer . ' ~';
		}

		if ( '' === $head ) {
			// Low-priority path: `.ancestor :where(.cls)` — no leading space.
			$selector = ltrim( $mid ) . ( '' === $mid ? '' : ' ' ) . $core;
		} else {
			$selector = $head . $mid . ' ' . $core;
		}

		$css = $selector . ' { ' . $declarations . ' }';

		$result = array(
			'media'     => $parsed['media'],
			'supports'  => $parsed['supports'],
			'container' => isset( $parsed['container'] ) ? (string) $parsed['container'] : '',
			'starting'  => ! empty( $parsed['starting'] ),
			'css'       => $css,
		);

		self::$memo[ $token ] = (string) wp_json_encode( $result );
		return $result;
	}

	/**
	 * Parse a class token into its variant components and base class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Raw class token.
	 * @return array{media:string, supports:string, container:string, starting:bool, states:array<int,string>, pseudo_element:string, base:string, important:bool, ancestors:array<int,string>, peers:array<int,string>, descendant_suffix:string, arbitrary_templates:array<int,string>}|null
	 */
	private static function parse_token( string $token ) {
		$segments = self::split_variants( $token );
		if ( null === $segments || empty( $segments ) ) {
			return null;
		}

		$base = array_pop( $segments );
		if ( ! is_string( $base ) || '' === $base ) {
			return null;
		}

		// Tailwind `!important` modifier. v3 form is leading `!` (e.g. `!w-full`);
		// v4 form is trailing `!` (e.g. `w-full!`). Support both. Strip before
		// resolver dispatch so all downstream class-matching stays unchanged;
		// the flag is reapplied per-declaration at emission time.
		$important = false;
		if ( strlen( $base ) > 1 ) {
			if ( '!' === $base[0] ) {
				$base      = substr( $base, 1 );
				$important = true;
			} elseif ( '!' === $base[ strlen( $base ) - 1 ] ) {
				$base      = substr( $base, 0, -1 );
				$important = true;
			}
		}
		if ( '' === $base ) {
			return null;
		}

		$media               = '';
		$states              = array();
		$pseudo_element      = '';
		$ancestors           = array();
		$peers               = array();
		$descendant_suffix   = '';
		$arbitrary_templates = array();
		$supports            = '';
		$container           = '';
		$starting            = false;

		// Feature flag: container-query variants + `@container` utilities.
		// When disabled the compiler silently skips tokens carrying `@` prefixes
		// so a site that flips the flag off returns to v3 behaviour cleanly.
		$container_queries_enabled = (bool) apply_filters( 'spectra_blocks_gbs_container_queries', true );

		foreach ( $segments as $variant ) {
			// `starting:` → wraps the rule in `@starting-style { … }`. No
			// parameters; single-instance per token.
			if ( 'starting' === $variant ) {
				if ( $starting ) {
					return null;
				}
				$starting = true;
				continue;
			}

			// Container-query variants — `@sm:`, `@md:`, …, `@[32rem]:`,
			// `@sm/name:`, `@container/name:` (named, no size).
			if ( '' !== $variant && '@' === $variant[0] ) {
				if ( ! $container_queries_enabled ) {
					return null;
				}
				if ( '' !== $container ) {
					return null;
				}
				$resolved = self::parse_container_query_variant( $variant );
				if ( null === $resolved ) {
					return null;
				}
				$container = $resolved;
				continue;
			}

			if ( isset( self::RESPONSIVE_VARIANTS[ $variant ] ) ) {
				if ( '' !== $media ) {
					return null; // Two responsive variants on one token — reject.
				}
				$media = self::RESPONSIVE_VARIANTS[ $variant ];
				continue;
			}

			// Arbitrary breakpoints — Tailwind v3.2+ form `min-[Npx]:` / `max-[Npx]:`.
			// Value flows through Sanitizer::sanitize_css_value (same trust boundary
			// as every other bracket). Rejects stacked responsive variants the same
			// as named breakpoints above.
			if ( preg_match( '/^(min|max)-\[([^\]]+)\]$/', $variant, $m ) ) {
				if ( '' !== $media ) {
					return null;
				}
				$clean = Sanitizer::sanitize_css_value( $m[2], true );
				if ( '' === $clean ) {
					return null;
				}
				$dim   = 'min' === $m[1] ? 'min-width' : 'max-width';
				$media = '(' . $dim . ': ' . $clean . ')';
				continue;
			}

			// Motion / print / forced-colors @media wrappers. These stack into
			// the single `$media` slot; two media-condition variants on one
			// token is rejected the same way two responsives are.
			if ( isset( self::MEDIA_FEATURE_VARIANTS[ $variant ] ) ) {
				if ( '' !== $media ) {
					return null;
				}
				$media = self::MEDIA_FEATURE_VARIANTS[ $variant ];
				continue;
			}

			// `@supports (…)` wrapper — `supports-[display:grid]:utility`.
			if ( 0 === strpos( $variant, 'supports-[' ) && ']' === substr( $variant, -1 ) ) {
				if ( '' !== $supports ) {
					return null;
				}
				$raw   = substr( $variant, strlen( 'supports-[' ), -1 );
				$clean = self::sanitize_variant_selector( $raw );
				if ( '' === $clean ) {
					return null;
				}
				// Preserve `_`→` ` decoding for readable tokens.
				$supports = '(' . self::decode_bracket_value( $clean ) . ')';
				continue;
			}

			if ( isset( self::STATE_VARIANTS[ $variant ] ) ) {
				$states[] = self::STATE_VARIANTS[ $variant ];
				continue;
			}

			if ( isset( self::PSEUDO_ELEMENT_VARIANTS[ $variant ] ) ) {
				if ( '' !== $pseudo_element ) {
					return null; // Two pseudo-elements on one token — reject.
				}
				$pseudo_element = self::PSEUDO_ELEMENT_VARIANTS[ $variant ];
				continue;
			}

			// Ancestor selectors — `dark:`, `rtl:`, `ltr:`.
			if ( isset( self::ANCESTOR_VARIANTS[ $variant ] ) ) {
				$ancestors[] = self::ANCESTOR_VARIANTS[ $variant ];
				continue;
			}

			// Descendant variants — `*:` (direct child), `**:` (all descendants).
			if ( '*' === $variant ) {
				if ( '' !== $descendant_suffix ) {
					return null;
				}
				$descendant_suffix = ' > *';
				continue;
			}
			if ( '**' === $variant ) {
				if ( '' !== $descendant_suffix ) {
					return null;
				}
				$descendant_suffix = ' *';
				continue;
			}

			// group-*, peer-* — ancestor / sibling with optional named group.
			$group_peer = self::parse_group_peer_variant( $variant );
			if ( null !== $group_peer ) {
				if ( 'group' === $group_peer['kind'] ) {
					$ancestors[] = $group_peer['selector'];
				} else {
					// peer — sibling combinator; stored alongside ancestors
					// and emitted with ` ~ ` separator. A separate bucket so
					// the selector builder can distinguish.
					$peers[] = $group_peer['selector'];
				}
				continue;
			}

			// nth-[…], nth-last-[…], nth-of-type-[…], nth-last-of-type-[…].
			$nth = self::parse_nth_variant( $variant );
			if ( null !== $nth ) {
				$states[] = $nth;
				continue;
			}

			// has-[…], not-[…] (bracket + bare forms).
			$has_not = self::parse_has_not_variant( $variant );
			if ( null !== $has_not ) {
				$states[] = $has_not;
				continue;
			}

			// data-[key=value] / data-<bare> bracket + bare forms.
			$data = self::parse_data_variant( $variant );
			if ( null !== $data ) {
				$states[] = $data;
				continue;
			}

			// aria-[key=value] bracket form (bare aria-* names are already
			// in STATE_VARIANTS and matched above).
			if ( 0 === strpos( $variant, 'aria-[' ) && ']' === substr( $variant, -1 ) ) {
				$raw  = substr( $variant, strlen( 'aria-[' ), -1 );
				$attr = self::build_attribute_selector_from_pair( $raw, 'aria-' );
				if ( '' === $attr ) {
					return null;
				}
				$states[] = $attr;
				continue;
			}

			// Arbitrary-selector variant `[&…]:` — `&` is the compiled class
			// selector; the builder replaces it at emit time.
			if ( '' !== $variant && '[' === $variant[0] && ']' === substr( $variant, -1 ) && false !== strpos( $variant, '&' ) ) {
				$body  = substr( $variant, 1, -1 );
				$clean = self::sanitize_variant_selector( $body );
				if ( '' === $clean || false === strpos( $clean, '&' ) ) {
					return null;
				}
				// Decode underscores like other bracket bodies so authors can
				// write `[&_p]:` for descendant-paragraph.
				$arbitrary_templates[] = self::decode_bracket_value( $clean );
				continue;
			}

			/**
			 * Filters unknown variant prefix handling.
			 *
			 * Return a string like ":custom" or "::custom" to be appended to
			 * the selector; or null to reject the token.
			 *
			 * @since 1.0.0
			 *
			 * @param string|null $suffix  Resolved suffix or null to reject.
			 * @param string      $variant Unknown variant name.
			 */
			$custom = apply_filters( 'spectra_gs_jit_variant_prefixes', null, $variant );
			if ( is_string( $custom ) && '' !== $custom ) {
				$states[] = $custom;
				continue;
			}

			return null; // Unknown variant — reject token.
		}

		return array(
			'media'               => $media,
			'supports'            => $supports,
			'container'           => $container,
			'starting'            => $starting,
			'states'              => $states,
			'pseudo_element'      => $pseudo_element,
			'base'                => $base,
			'important'           => $important,
			'ancestors'           => $ancestors,
			'peers'               => $peers,
			'descendant_suffix'   => $descendant_suffix,
			'arbitrary_templates' => $arbitrary_templates,
		);
	}

	/**
	 * Resolve an `@…` container-query variant to its `@container …` at-rule body.
	 *
	 * Accepted forms:
	 *   `@sm:`              → `(min-width: 24rem)`
	 *   `@md:`…`@7xl:`      → per CONTAINER_QUERY_BREAKPOINTS
	 *   `@[32rem]:`         → `(min-width: 32rem)` (arbitrary)
	 *   `@sm/name:`         → `name (min-width: 24rem)` (named container)
	 *   `@container/name:`  → `name` (named, no size)
	 *
	 * Returns the body to place after `@container ` (the caller concats the
	 * `@container ` prefix at emission time).
	 *
	 * @since 1.0.0
	 *
	 * @param string $variant Raw variant segment, leading `@` included.
	 * @return string|null Body string (e.g. `(min-width: 24rem)`) or null on reject.
	 */
	private static function parse_container_query_variant( string $variant ) {
		// Strip leading `@`.
		$body = substr( $variant, 1 );
		if ( '' === $body ) {
			return null;
		}

		// Split optional `/name` suffix.
		$name  = '';
		$slash = strrpos( $body, '/' );
		if ( false !== $slash ) {
			$name = substr( $body, $slash + 1 );
			$body = substr( $body, 0, $slash );
			if ( '' === $name || ! preg_match( '/^[a-zA-Z][a-zA-Z0-9_-]*$/', $name ) ) {
				return null;
			}
		}

		// Unscoped / named-only — `@container:` or `@container/name:` with no size.
		if ( 'container' === $body ) {
			return '' === $name ? '' : $name;
		}

		// Arbitrary bracket form `@[32rem]` / `@[100ch]` etc.
		if ( '' !== $body && '[' === $body[0] && ']' === substr( $body, -1 ) ) {
			$raw   = substr( $body, 1, -1 );
			$clean = Sanitizer::sanitize_css_value( self::decode_bracket_value( $raw ), true );
			if ( '' === $clean ) {
				return null;
			}
			$condition = '(min-width: ' . $clean . ')';
			return '' === $name ? $condition : ( $name . ' ' . $condition );
		}

		// Named breakpoint.
		if ( ! isset( self::CONTAINER_QUERY_BREAKPOINTS[ $body ] ) ) {
			return null;
		}
		$condition = '(min-width: ' . self::CONTAINER_QUERY_BREAKPOINTS[ $body ] . ')';
		return '' === $name ? $condition : ( $name . ' ' . $condition );
	}

	/**
	 * Parse a `group-…` or `peer-…` variant (with optional `/name` suffix).
	 *
	 * Returns `['kind' => 'group'|'peer', 'selector' => '.group:hover']` on
	 * success, or null if not a group/peer variant or malformed.
	 *
	 * `group-hover:`          → `.group:hover`
	 * `group-hover/card:`     → `.group\/card:hover`
	 * `peer-checked:`         → `.peer:checked`
	 * `peer-checked/input:`   → `.peer\/input:checked`
	 *
	 * Only state keys present in `STATE_VARIANTS` and starting with `:` (i.e.
	 * true pseudo-classes, not attribute selectors) are accepted as the event
	 * portion; attribute-suffix states would produce invalid chained selectors
	 * in the ancestor position.
	 *
	 * @since 1.0.0
	 *
	 * @param string $variant Raw variant segment.
	 * @return array{kind:string, selector:string}|null
	 */
	private static function parse_group_peer_variant( string $variant ) {
		if ( 0 === strpos( $variant, 'group-' ) ) {
			$kind = 'group';
			$tail = substr( $variant, 6 );
		} elseif ( 0 === strpos( $variant, 'peer-' ) ) {
			$kind = 'peer';
			$tail = substr( $variant, 5 );
		} else {
			return null;
		}

		if ( '' === $tail ) {
			return null;
		}

		// Optional `/name` suffix.
		$name        = '';
		$slash_index = strrpos( $tail, '/' );
		if ( false !== $slash_index ) {
			$name = substr( $tail, $slash_index + 1 );
			$tail = substr( $tail, 0, $slash_index );
			if ( '' === $name || ! preg_match( '/^[a-zA-Z][a-zA-Z0-9_-]*$/', $name ) ) {
				return null;
			}
		}

		if ( ! isset( self::STATE_VARIANTS[ $tail ] ) ) {
			return null;
		}

		$state = self::STATE_VARIANTS[ $tail ];
		// Only pseudo-class tails compose cleanly onto `.group` / `.peer`.
		// Attribute-selector tails (`[aria-*]`) also chain safely since they
		// start with `[`. Reject anything else (e.g. comma-unioned states).
		if ( '' === $state || ( ':' !== $state[0] && '[' !== $state[0] ) ) {
			return null;
		}
		// Union selectors like `:autofill, :-webkit-autofill` and `open`'s
		// `[open], :open` don't chain cleanly in ancestor position.
		if ( false !== strpos( $state, ',' ) ) {
			return null;
		}

		$class = '' === $name ? ( '.' . $kind ) : ( '.' . $kind . '\\/' . $name );

		return array(
			'kind'     => $kind,
			'selector' => $class . $state,
		);
	}

	/**
	 * Parse `nth-[…]`, `nth-last-[…]`, `nth-of-type-[…]`, `nth-last-of-type-[…]`.
	 *
	 * Returns the resulting `:nth-…()` pseudo-class string, or null.
	 *
	 * @since 1.0.0
	 *
	 * @param string $variant Raw variant segment.
	 * @return string|null
	 */
	private static function parse_nth_variant( string $variant ) {
		static $prefixes = array(
			'nth-last-of-type-' => ':nth-last-of-type',
			'nth-last-'         => ':nth-last-child',
			'nth-of-type-'      => ':nth-of-type',
			'nth-'              => ':nth-child',
		);

		foreach ( $prefixes as $prefix => $pseudo ) {
			if ( 0 !== strpos( $variant, $prefix ) ) {
				continue;
			}
			$suffix = substr( $variant, strlen( $prefix ) );
			if ( '' === $suffix || '[' !== $suffix[0] || ']' !== substr( $suffix, -1 ) ) {
				return null;
			}
			$raw = substr( $suffix, 1, -1 );
			if ( '' === $raw ) {
				return null;
			}
			$decoded = self::decode_bracket_value( $raw );
			$normal  = strtolower( trim( $decoded ) );
			// Accept: plain integer, `odd`, `even`, or an+b with digits/n/+/-/space.
			if ( ! preg_match( '/^(odd|even|[\d\s+\-n]+)$/', $normal ) ) {
				return null;
			}
			// Collapse internal whitespace.
			$normal = preg_replace( '/\s+/', '', $normal );
			if ( ! is_string( $normal ) || '' === $normal ) {
				return null;
			}
			return $pseudo . '(' . $normal . ')';
		}

		return null;
	}

	/**
	 * Parse `has-[…]`, `not-[…]`, `has-<bare>`, `not-<bare>` variants.
	 *
	 * `has-[ul]:`            → `:has(ul)`
	 * `not-[.disabled]:`     → `:not(.disabled)`
	 * `not-hover:`           → `:not(:hover)` (re-uses STATE_VARIANTS)
	 * `has-hover:`           → `:has(:hover)`
	 *
	 * @since 1.0.0
	 *
	 * @param string $variant Raw variant segment.
	 * @return string|null
	 */
	private static function parse_has_not_variant( string $variant ) {
		static $funcs = array(
			'has-' => ':has',
			'not-' => ':not',
		);

		foreach ( $funcs as $prefix => $pseudo ) {
			if ( 0 !== strpos( $variant, $prefix ) ) {
				continue;
			}
			$tail = substr( $variant, strlen( $prefix ) );
			if ( '' === $tail ) {
				return null;
			}

			// Bracket form.
			if ( '[' === $tail[0] && ']' === substr( $tail, -1 ) ) {
				$raw   = substr( $tail, 1, -1 );
				$clean = self::sanitize_variant_selector( $raw );
				if ( '' === $clean ) {
					return null;
				}
				$decoded = self::decode_bracket_value( $clean );
				return $pseudo . '(' . $decoded . ')';
			}

			// Bare form — reuse STATE_VARIANTS (pseudo-classes only; not
			// attribute-suffix entries like `aria-*`).
			if ( isset( self::STATE_VARIANTS[ $tail ] ) ) {
				$state = self::STATE_VARIANTS[ $tail ];
				if ( '' !== $state && ':' === $state[0] && false === strpos( $state, ',' ) ) {
					return $pseudo . '(' . $state . ')';
				}
			}

			return null;
		}

		return null;
	}

	/**
	 * Parse `data-[…]` bracket and `data-<bare>` forms.
	 *
	 * The bare-form map defaults to `DATA_VARIANTS_DEFAULT` and can be
	 * extended via the `spectra_gs_jit_data_variants` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $variant Raw variant segment.
	 * @return string|null
	 */
	private static function parse_data_variant( string $variant ) {
		if ( 0 !== strpos( $variant, 'data-' ) ) {
			return null;
		}

		$tail = substr( $variant, 5 );
		if ( '' === $tail ) {
			return null;
		}

		// Bracket form: data-[key=value] / data-[open] / data-[size="lg"].
		if ( '[' === $tail[0] && ']' === substr( $tail, -1 ) ) {
			$body = substr( $tail, 1, -1 );
			return self::build_attribute_selector_from_pair( $body, 'data-' );
		}

		/**
		 * Filters the bare-form data-* variant map.
		 *
		 * Return a map of `name => attribute-selector-string`. Entries merge
		 * over `DATA_VARIANTS_DEFAULT` (callee values win).
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $map Default data-variant map.
		 */
		$map = apply_filters( 'spectra_gs_jit_data_variants', self::DATA_VARIANTS_DEFAULT );
		if ( ! is_array( $map ) ) {
			$map = self::DATA_VARIANTS_DEFAULT;
		}

		if ( isset( $map[ $tail ] ) && is_string( $map[ $tail ] ) && '' !== $map[ $tail ] ) {
			// Defensive: sanitize the mapped selector string.
			$clean = self::sanitize_variant_selector( $map[ $tail ] );
			if ( '' !== $clean ) {
				return $clean;
			}
		}

		return null;
	}

	/**
	 * Build an `[attr-…="…"]` selector from a `key=value` or bare `key` pair.
	 *
	 * Used by both `data-[…]` and `aria-[…]` bracket variants. The `$prefix`
	 * argument is the attribute prefix (`data-` or `aria-`).
	 *
	 * Accepted forms:
	 *   `key`             → `[prefix-key]`
	 *   `key=value`       → `[prefix-key="value"]`
	 *   `key="value"`     → `[prefix-key="value"]`
	 *
	 * The key is restricted to a conservative identifier charset; the value
	 * runs through `sanitize_variant_selector` and has its surrounding quotes
	 * normalized to double-quotes for the emitted selector.
	 *
	 * @since 1.0.0
	 *
	 * @param string $body   Raw bracket body (no surrounding brackets).
	 * @param string $prefix Attribute prefix (`data-` or `aria-`).
	 * @return string Empty string on reject.
	 */
	private static function build_attribute_selector_from_pair( string $body, string $prefix ): string {
		$clean = self::sanitize_variant_selector( $body );
		if ( '' === $clean ) {
			return '';
		}

		$eq = strpos( $clean, '=' );
		if ( false === $eq ) {
			$key = trim( $clean );
			if ( ! preg_match( '/^[a-zA-Z][a-zA-Z0-9_-]*$/', $key ) ) {
				return '';
			}
			return '[' . $prefix . strtolower( $key ) . ']';
		}

		$key   = trim( substr( $clean, 0, $eq ) );
		$value = trim( substr( $clean, $eq + 1 ) );

		if ( ! preg_match( '/^[a-zA-Z][a-zA-Z0-9_-]*$/', $key ) ) {
			return '';
		}

		// Strip surrounding quotes if present.
		if ( strlen( $value ) >= 2 ) {
			$first = $value[0];
			$last  = $value[ strlen( $value ) - 1 ];
			if ( ( '"' === $first || "'" === $first ) && $first === $last ) {
				$value = substr( $value, 1, -1 );
			}
		}
		if ( '' === $value ) {
			return '';
		}
		// Restricted value charset: identifiers, digits, dash, underscore,
		// dot, space, colon, slash. Keeps attribute-value selectors safe.
		if ( ! preg_match( '/^[a-zA-Z0-9_\-\.\s:\/]+$/', $value ) ) {
			return '';
		}

		return '[' . $prefix . strtolower( $key ) . '="' . $value . '"]';
	}

	/**
	 * Sanitize a raw bracket-variant selector payload.
	 *
	 * Used by `supports-[…]:`, `[&…]:`, `has-[…]:`, `not-[…]:`, `nth-[…]:`,
	 * `data-[…]:`, and `aria-[…]:`. Rejects rule-terminator punctuation,
	 * comment sequences, script tags, `url(`, `expression(`, and
	 * `javascript:`/`data:` URIs. Clips to 200 chars.
	 *
	 * Mirrors the contract described in `Sanitizer::sanitize_css_value`'s
	 * dangerous-pattern list but is stricter (also rejects `{`, `}`, `;`,
	 * comments) because the output is dropped directly into a selector.
	 *
	 * @since 1.0.0
	 *
	 * @param string $raw Raw bracket body.
	 * @return string Sanitized value, or empty on reject.
	 */
	private static function sanitize_variant_selector( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw || strlen( $raw ) > 200 ) {
			return '';
		}
		if ( false !== strpos( $raw, chr( 0 ) ) ) {
			return '';
		}

		$forbidden = array( '{', '}', ';', '/*', '*/', '<script', '</script', ':has(script', 'url(', 'expression(', 'javascript:', 'data:', 'vbscript:', 'behavior:' );
		$lower     = strtolower( $raw );
		foreach ( $forbidden as $needle ) {
			if ( false !== strpos( $lower, $needle ) ) {
				return '';
			}
		}

		if ( preg_match( '/on\w+\s*=/i', $raw ) ) {
			return '';
		}
		if ( preg_match( '/<\s*\/?\s*[a-z]/i', $raw ) ) {
			return '';
		}

		return $raw;
	}

	/**
	 * Split a token on variant colons while respecting bracket contents.
	 *
	 * `md:hover:[transform:scale(1.05)]` → ['md', 'hover', '[transform:scale(1.05)]']
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Raw token.
	 * @return array<int, string>|null
	 */
	private static function split_variants( string $token ) {
		$parts    = array();
		$buffer   = '';
		$depth    = 0;
		$length   = strlen( $token );
		$variants = 0;

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $token[ $i ];

			if ( '[' === $char ) {
				++$depth;
				$buffer .= $char;
				continue;
			}

			if ( ']' === $char ) {
				if ( $depth <= 0 ) {
					return null;
				}
				--$depth;
				$buffer .= $char;
				continue;
			}

			if ( ':' === $char && 0 === $depth ) {
				if ( '' === $buffer ) {
					return null;
				}
				$parts[] = $buffer;
				$buffer  = '';
				++$variants;
				if ( $variants > self::MAX_VARIANTS_PER_TOKEN ) {
					return null;
				}
				continue;
			}

			$buffer .= $char;
		}

		if ( 0 !== $depth || '' === $buffer ) {
			return null;
		}

		$parts[] = $buffer;
		return $parts;
	}

	/**
	 * Resolve a base class to its CSS declaration string.
	 *
	 * Resolution order:
	 *   1. Registered utility class lookup.
	 *   2. Tailwind v4 gradient utilities (`bg-linear-*`, `bg-radial*`,
	 *      `bg-conic-*`, `from-*`, `via-*`, `to-*`, `bg-clip-*`).
	 *   3. Per-utility bracket (`{prefix}-[{value}]`).
	 *   4. Alpha-slash modifier (`{color-prefix}-{body}/{0-100}`).
	 *
	 * @since 1.0.0
	 *
	 * @param string                $base  Base class (no variants).
	 * @param array<string, string> $known Known utility class map.
	 * @return string Sanitized declaration string (empty on failure).
	 */
	private static function resolve_declarations( string $base, array $known ): string {
		// Transform utilities are resolved before known-class lookup so the
		// var-composed transform slots take precedence over any legacy
		// monolithic `transform: rotate(...)` entries in ClassRegistry. Without
		// this ordering, a utility like `rotate-12 scale-95` would resolve to
		// two conflicting `transform:` declarations and the later one wins —
		// breaking composition. The router returns null (not '') for non-
		// transform tokens, so unrelated classes fall through untouched.
		$tw_transform = self::resolve_tw_transform( $base );
		if ( null !== $tw_transform && '' !== $tw_transform ) {
			return $tw_transform;
		}

		// Ring utilities resolved before known-class so composed box-shadow
		// emission takes precedence over any future static ring entries, and
		// so compositional tokens (ring-2, ring-inset, ring-offset-N, bracket
		// forms) always route through the variable-composition path.
		$tw_ring = self::resolve_tw_ring( $base );
		if ( null !== $tw_ring && '' !== $tw_ring ) {
			return self::sanitize_declaration_block( $tw_ring );
		}

		if ( isset( $known[ $base ] ) ) {
			return self::sanitize_declaration_block( $known[ $base ] );
		}

		$tw_gradient = self::resolve_tw_gradient( $base );
		if ( '' !== $tw_gradient ) {
			return $tw_gradient;
		}

		$tw_inset = self::resolve_tw_inset( $base );
		if ( null !== $tw_inset && '' !== $tw_inset ) {
			return $tw_inset;
		}

		$tw_svg_paint = self::resolve_tw_svg_paint( $base );
		if ( null !== $tw_svg_paint && '' !== $tw_svg_paint ) {
			return $tw_svg_paint;
		}

		$per_util = self::resolve_per_utility_bracket( $base );
		if ( '' !== $per_util ) {
			return $per_util;
		}

		// Bare arbitrary-property form — `[property:value]` as a class on its own
		// (e.g. `[clip-path:polygon(...)]`, `[mask-image:url(...)]`). Tailwind
		// supports this out of the box; gated here by a property allow-list so
		// the LLM's styling vocabulary stays bounded but not brittle.
		$tw_arb = self::resolve_tw_arbitrary_property( $base );
		if ( null !== $tw_arb && '' !== $tw_arb ) {
			return $tw_arb;
		}

		// Fallback for `animate-<ident>` when `<ident>` isn't in ClassRegistry.
		// Emits `animation: <ident> 0.6s ease-out both` so any user-registered
		// `@keyframes <ident>` (via the plugin's Global Styles bulk endpoint)
		// automatically pairs with its `animate-<ident>` class. Bounded to
		// kebab / camelCase identifiers so we never accept arbitrary tokens.
		// If `<ident>` has no matching keyframe registered, the browser no-ops
		// the animation cleanly — no layout fallout.
		//
		// Returned with the LOW_PRIORITY sentinel so `compile_token` emits
		// the rule inside `:where()` (zero specificity). A per-page
		// `.animate-<ident> { animation: <ident> 40s ... }` in customizerCss
		// (specificity 0,1,0) then wins cleanly when the author authored a
		// custom shorthand via `config.animation`. Without the sentinel, the
		// 5-class-repeat emission (0,5,0) would stomp the author's override.
		if ( 0 === strpos( $base, 'animate-' ) ) {
			$ident = substr( $base, 8 );
			if ( '' !== $ident && preg_match( '/^[a-zA-Z][a-zA-Z0-9_-]*$/', $ident ) ) {
				return self::LOW_PRIORITY_MARKER . 'animation: ' . $ident . ' 0.6s ease-out both;';
			}
		}

		return self::resolve_alpha_slash( $base, $known );
	}

	/**
	 * Sentinel prefix on a declaration string: tells `compile_token` to
	 * emit the rule inside `:where(.<selector>)` instead of the default
	 * 5-class-repeat specificity ladder. Used for the `animate-<ident>`
	 * fallback so author-configured `.animate-<ident>` overrides win.
	 *
	 * Non-printable + structured so it cannot appear inside legitimate
	 * sanitized CSS values.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const LOW_PRIORITY_MARKER = "\x00LOW_PRIO\x00";

	/**
	 * Allow-list of CSS properties settable via the bare `[property:value]` form.
	 *
	 * Scoped to visual/layout properties the LLM realistically needs. Excluded:
	 * `content` / `cursor` / `background-image` / `list-style-image` (URL injection
	 * risk), `all` / `--*` / unknown identifiers. These go through their named
	 * prefix if needed, where shape-aware sanitation applies.
	 *
	 * @since 1.0.0
	 * @var array<int, string>
	 */
	const ARBITRARY_PROPERTY_ALLOWLIST = array(
		'clip-path',
		'-webkit-clip-path',
		'mask',
		'mask-image',
		'mask-size',
		'mask-position',
		'mask-repeat',
		'-webkit-mask',
		'-webkit-mask-image',
		'mix-blend-mode',
		'background-blend-mode',
		'isolation',
		'perspective',
		'perspective-origin',
		'transform-origin',
		'transform-style',
		'backface-visibility',
		'will-change',
		'accent-color',
		'caret-color',
		'touch-action',
		'pointer-events',
		'user-select',
		'-webkit-user-select',
		'resize',
		'scroll-behavior',
		'scroll-snap-type',
		'scroll-snap-align',
		'scroll-snap-stop',
		'scroll-margin',
		'scroll-padding',
		'overscroll-behavior',
		'hyphens',
		'word-spacing',
		'word-break',
		// Vertical / sideways text labels (e.g. "Scroll" indicators on
		// hero sections). The bracket form `[writing-mode:vertical-lr]`
		// is the canonical Tailwind v4 way to express this — there's no
		// per-utility prefix in Tailwind for writing-mode. Sanitizer +
		// declaration emit gate validate the value as a CSS keyword
		// before compilation.
		'writing-mode',
		'overflow-wrap',
		'text-wrap',
		'text-shadow',
		'font-feature-settings',
		'font-variant-numeric',
		'font-variant-caps',
		'font-variant-ligatures',
		'font-variation-settings',
		'letter-spacing',
		'line-height',
		'text-indent',
		'text-decoration',
		'text-decoration-color',
		'text-decoration-style',
		'text-decoration-thickness',
		'text-underline-offset',
		'columns',
		'column-count',
		'column-gap',
		'column-rule',
		'break-before',
		'break-after',
		'break-inside',
		'box-decoration-break',
		'aspect-ratio',
		'object-position',
		'place-content',
		'place-items',
		'place-self',
		'filter',
		'backdrop-filter',
		'-webkit-backdrop-filter',
		// Animation properties — unblocks LLM-authored staging patterns like
		// `[animation-delay:0.5s]` and `[animation-duration:16s]`. The named
		// `animate-*` presets live in ClassRegistry; brackets fill the gaps
		// for staging/composition that aren't expressible as static classes.
		'animation',
		'animation-delay',
		'animation-duration',
		'animation-timing-function',
		'animation-iteration-count',
		'animation-direction',
		'animation-fill-mode',
		'animation-play-state',
		'animation-composition',
		// Mask compositing — compound mask effects (e.g. border-gradient
		// glow rings) need `[mask-composite:exclude]` on the pseudo-element.
		'mask-composite',
		'-webkit-mask-composite',
		// Transform — compound transforms like `[transform:rotate(45deg)_scale(1.1)]`
		// when the LLM authors composite effects the preset `rotate-*` / `scale-*`
		// utilities don't express.
		'transform',
	);

	/**
	 * Resolve the bare `[property:value]` arbitrary-property form.
	 *
	 * Only properties in `ARBITRARY_PROPERTY_ALLOWLIST` are emitted; the value
	 * travels through `Sanitizer::sanitize_css_value` (the same sanitizer used
	 * by every bracket resolver), so `url()`, unbalanced brackets, JS protocols,
	 * `\`, `<`, `>`, and other injection vectors are stripped.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Class token to resolve (pre-variant-stripped).
	 * @return string|null Declarations, or null if the token isn't this shape.
	 */
	private static function resolve_tw_arbitrary_property( string $token ) {
		if ( '' === $token || '[' !== $token[0] || ']' !== substr( $token, -1 ) ) {
			return null;
		}

		$body = substr( $token, 1, -1 );
		if ( ! is_string( $body ) || '' === $body ) {
			return null;
		}

		$colon = strpos( $body, ':' );
		if ( false === $colon || 0 === $colon || strlen( $body ) - 1 === $colon ) {
			return null;
		}

		$property = strtolower( substr( $body, 0, $colon ) );
		$raw      = substr( $body, $colon + 1 );

		if ( ! in_array( $property, self::ARBITRARY_PROPERTY_ALLOWLIST, true ) ) {
			return null;
		}

		$value = self::decode_bracket_value( $raw );
		$clean = Sanitizer::sanitize_css_value( $value, true );
		if ( '' === $clean ) {
			return null;
		}

		$clean_property = Sanitizer::sanitize_css_property( $property );
		if ( '' === $clean_property ) {
			return null;
		}

		return $clean_property . ': ' . $clean . ';';
	}

	/**
	 * Tailwind stroke-width values in pixels. Mirrors the default Tailwind
	 * scale — these are the only non-bracket numeric stroke widths emitted.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const TW_STROKE_WIDTHS = array(
		'0' => '0',
		'1' => '1px',
		'2' => '2px',
		'4' => '4px',
		'8' => '8px',
	);

	/**
	 * Resolve non-bracket SVG paint utilities — keyword fill/stroke and the
	 * compact Tailwind stroke-width scale (`stroke-0` through `stroke-8`).
	 *
	 * Palette-driven `fill-{slug}-{shade}` / `stroke-{slug}-{shade}` forms are
	 * served by the known-class registry; bracket forms (`stroke-[3px]`,
	 * `fill-[#hex]`) are served by the shape-aware bracket resolver. This
	 * function fills the gap that those two paths don't cover: the paint
	 * keywords and the plain numeric widths.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base Base class token (no variants).
	 * @return string|null Declarations string on hit, `null` on miss.
	 */
	private static function resolve_tw_svg_paint( string $base ) {
		if ( '' === $base ) {
			return null;
		}

		// Paint keywords — `fill-none` / `stroke-none` / `-transparent` / `-current`.
		static $keyword_suffixes = array(
			'none'        => 'none',
			'transparent' => 'transparent',
			'current'     => 'currentColor',
			'inherit'     => 'inherit',
		);

		foreach ( array( 'fill', 'stroke' ) as $property ) {
			$prefix = $property . '-';
			if ( 0 !== strpos( $base, $prefix ) ) {
				continue;
			}
			$suffix = substr( $base, strlen( $prefix ) );
			if ( isset( $keyword_suffixes[ $suffix ] ) ) {
				return $property . ': ' . $keyword_suffixes[ $suffix ] . ';';
			}
		}

		// Numeric stroke-width — `stroke-0 | stroke-1 | stroke-2 | stroke-4 | stroke-8`.
		if ( 0 === strpos( $base, 'stroke-' ) ) {
			$suffix = substr( $base, 7 );
			if ( isset( self::TW_STROKE_WIDTHS[ $suffix ] ) ) {
				return 'stroke-width: ' . self::TW_STROKE_WIDTHS[ $suffix ] . ';';
			}
		}

		return null;
	}

	/**
	 * Tailwind v4-style gradient direction keywords.
	 *
	 * Keys are the suffix after `bg-linear-to-`; values are the CSS
	 * `linear-gradient` direction phrase.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const TW_GRADIENT_DIRECTIONS = array(
		't'  => 'to top',
		'tr' => 'to top right',
		'r'  => 'to right',
		'br' => 'to bottom right',
		'b'  => 'to bottom',
		'bl' => 'to bottom left',
		'l'  => 'to left',
		'tl' => 'to top left',
	);

	/**
	 * `bg-clip-*` static utilities.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const BG_CLIP_UTILITIES = array(
		'bg-clip-text'    => 'background-clip: text; -webkit-background-clip: text;',
		'bg-clip-border'  => 'background-clip: border-box;',
		'bg-clip-padding' => 'background-clip: padding-box;',
		'bg-clip-content' => 'background-clip: content-box;',
	);

	/**
	 * Resolve Tailwind v4 gradient and background-clip utilities.
	 *
	 * Handles (in order of check):
	 *   - `bg-clip-{text|border|padding|content}` — sets `background-clip`.
	 *   - `bg-linear-to-{t|tr|r|br|b|bl|l|tl}` — keyword direction.
	 *   - `bg-linear-[{angle}]` — arbitrary angle (e.g. `135deg`, `0.25turn`).
	 *   - `bg-linear-{0..360}` — numeric angle in degrees.
	 *   - `bg-radial` / `bg-radial-[{shape}]` — radial gradient.
	 *   - `bg-conic-{0..360}` / `bg-conic-[{angle}]` — conic gradient.
	 *   - `from-[{color}]` / `via-[{color}]` / `to-[{color}]` — arbitrary
	 *     stop colours composing Tailwind's `--tw-gradient-stops` var scaffold.
	 *
	 * Palette-slug stop utilities (`from-primary-600`, `via-base-50`, etc.) are
	 * served by {@see ClassRegistry::get_color_classes()} against the legacy
	 * `--gs-gradient-*` var family, which drives the v3 `bg-gradient-to-*`
	 * cascade. The v4 direction utilities emitted here read
	 * `--tw-gradient-stops`, so palette slugs are not interchangeable across
	 * the two tracks — use bracket colours (`from-[#08263b]`) with `bg-linear-*`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base Base class token (no variants).
	 * @return string Sanitized declaration string, or empty on failure.
	 */
	private static function resolve_tw_gradient( string $base ): string {
		if ( '' === $base ) {
			return '';
		}

		// Static `bg-clip-*` utilities.
		if ( isset( self::BG_CLIP_UTILITIES[ $base ] ) ) {
			return self::BG_CLIP_UTILITIES[ $base ];
		}

		// Gradient stops: from-[color] / via-[color] / to-[color].
		if ( 0 === strpos( $base, 'from-[' ) || 0 === strpos( $base, 'via-[' ) || 0 === strpos( $base, 'to-[' ) ) {
			return self::resolve_tw_gradient_stop_bracket( $base );
		}

		// Direction utilities — `bg-linear-*`, `bg-radial*`, `bg-conic-*`.
		if ( 0 === strpos( $base, 'bg-linear-' ) ) {
			return self::resolve_tw_bg_linear( substr( $base, strlen( 'bg-linear-' ) ) );
		}

		if ( 'bg-radial' === $base ) {
			return 'background-image: radial-gradient(var(--tw-gradient-stops));';
		}

		if ( 0 === strpos( $base, 'bg-radial-[' ) && ']' === substr( $base, -1 ) ) {
			$raw   = substr( $base, strlen( 'bg-radial-[' ), -1 );
			$shape = self::sanitize_gradient_arbitrary( $raw );
			if ( '' === $shape ) {
				return '';
			}
			return 'background-image: radial-gradient(' . $shape . ', var(--tw-gradient-stops));';
		}

		if ( 0 === strpos( $base, 'bg-conic-' ) ) {
			return self::resolve_tw_bg_conic( substr( $base, strlen( 'bg-conic-' ) ) );
		}

		return '';
	}

	/**
	 * Resolve the tail of a `bg-linear-*` token to a `background-image` decl.
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix Portion of the token after `bg-linear-`.
	 * @return string Declaration string, or empty on failure.
	 */
	private static function resolve_tw_bg_linear( string $suffix ): string {
		if ( '' === $suffix ) {
			return '';
		}

		// Keyword direction: bg-linear-to-{t|tr|r|br|b|bl|l|tl}.
		if ( 0 === strpos( $suffix, 'to-' ) ) {
			$dir_key = substr( $suffix, 3 );
			if ( isset( self::TW_GRADIENT_DIRECTIONS[ $dir_key ] ) ) {
				return 'background-image: linear-gradient(' . self::TW_GRADIENT_DIRECTIONS[ $dir_key ] . ', var(--tw-gradient-stops));';
			}
			return '';
		}

		// Arbitrary angle: bg-linear-[135deg].
		if ( '[' === $suffix[0] && ']' === substr( $suffix, -1 ) ) {
			$raw   = substr( $suffix, 1, -1 );
			$angle = self::sanitize_gradient_arbitrary( $raw );
			if ( '' === $angle ) {
				return '';
			}
			return 'background-image: linear-gradient(' . $angle . ', var(--tw-gradient-stops));';
		}

		// Numeric degrees: bg-linear-135.
		if ( preg_match( '/^\d{1,3}$/', $suffix ) ) {
			$deg = (int) $suffix;
			if ( $deg < 0 || $deg > 360 ) {
				return '';
			}
			return 'background-image: linear-gradient(' . $deg . 'deg, var(--tw-gradient-stops));';
		}

		return '';
	}

	/**
	 * Resolve the tail of a `bg-conic-*` token to a `background-image` decl.
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix Portion of the token after `bg-conic-`.
	 * @return string Declaration string, or empty on failure.
	 */
	private static function resolve_tw_bg_conic( string $suffix ): string {
		if ( '' === $suffix ) {
			return '';
		}

		// Arbitrary: bg-conic-[from_45deg_at_50%_50%].
		if ( '[' === $suffix[0] && ']' === substr( $suffix, -1 ) ) {
			$raw   = substr( $suffix, 1, -1 );
			$value = self::sanitize_gradient_arbitrary( $raw );
			if ( '' === $value ) {
				return '';
			}
			return 'background-image: conic-gradient(' . $value . ', var(--tw-gradient-stops));';
		}

		// Numeric starting angle: bg-conic-45.
		if ( preg_match( '/^\d{1,3}$/', $suffix ) ) {
			$deg = (int) $suffix;
			if ( $deg < 0 || $deg > 360 ) {
				return '';
			}
			return 'background-image: conic-gradient(from ' . $deg . 'deg, var(--tw-gradient-stops));';
		}

		return '';
	}

	/**
	 * Resolve an arbitrary-bracket gradient stop token.
	 *
	 * Accepted forms: `from-[{color}]`, `via-[{color}]`, `to-[{color}]` — where
	 * `{color}` is any Sanitizer-accepted CSS colour value (hex, rgb/hsl/hwb/
	 * oklch/..., named keyword). The emitted declarations intentionally set
	 * `--tw-gradient-from/via/to` and `--tw-gradient-stops` to match the
	 * composition expected by `bg-linear-*` / `bg-radial*` / `bg-conic-*`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base Token of the form `{from|via|to}-[{value}]`.
	 * @return string Declaration string, or empty on failure.
	 */
	private static function resolve_tw_gradient_stop_bracket( string $base ): string {
		if ( ']' !== substr( $base, -1 ) ) {
			return '';
		}

		$open = strpos( $base, '-[' );
		if ( false === $open ) {
			return '';
		}

		$prefix = substr( $base, 0, $open );
		$raw    = substr( $base, $open + 2, -1 );

		if ( '' === $raw || false !== strpos( $raw, '[' ) || false !== strpos( $raw, ']' ) ) {
			return '';
		}

		if ( 'from' !== $prefix && 'via' !== $prefix && 'to' !== $prefix ) {
			return '';
		}

		$value = self::decode_bracket_value( $raw );
		$color = Sanitizer::sanitize_css_value( $value, true );
		if ( '' === $color ) {
			return '';
		}

		// Require the bracket content to be a colour. Prevents `from-[10px]` or
		// `from-[var(--x)]` (the latter also rejected by strict sanitization).
		if ( ! self::looks_like_color( $color ) ) {
			return '';
		}

		$transparent_fallback = 'rgb(255 255 255 / 0)';

		if ( 'from' === $prefix ) {
			return '--tw-gradient-from: ' . $color . '; '
				. '--tw-gradient-to: ' . $transparent_fallback . '; '
				. '--tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);';
		}

		if ( 'via' === $prefix ) {
			return '--tw-gradient-via: ' . $color . '; '
				. '--tw-gradient-to: ' . $transparent_fallback . '; '
				. '--tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-via), var(--tw-gradient-to);';
		}

		// to-*.
		return '--tw-gradient-to: ' . $color . ';';
	}

	/**
	 * Sanitize an arbitrary bracket value used inside a gradient function.
	 *
	 * Applies Tailwind underscore decoding and strict value sanitization.
	 * Strict mode rejects `var(...)` references — callers that need a var
	 * in the final output should compose it outside this helper.
	 *
	 * @since 1.0.0
	 *
	 * @param string $raw Raw bracket body.
	 * @return string Sanitized value, or empty on failure.
	 */
	private static function sanitize_gradient_arbitrary( string $raw ): string {
		if ( '' === $raw || false !== strpos( $raw, '[' ) || false !== strpos( $raw, ']' ) ) {
			return '';
		}
		$decoded = self::decode_bracket_value( $raw );
		return Sanitizer::sanitize_css_value( $decoded, true );
	}

	/**
	 * Tailwind spacing scale (keys → rem/px values).
	 *
	 * Mirrors the fixed scale generated in `ClassRegistry::get_spacing_classes()`
	 * so `translate-x-{n}` produces the same pixel values as `p-{n}` / `m-{n}`.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const TW_SPACING_SCALE = array(
		'0'   => '0',
		'px'  => '1px',
		'0.5' => '0.125rem',
		'1'   => '0.25rem',
		'1.5' => '0.375rem',
		'2'   => '0.5rem',
		'2.5' => '0.625rem',
		'3'   => '0.75rem',
		'3.5' => '0.875rem',
		'4'   => '1rem',
		'5'   => '1.25rem',
		'6'   => '1.5rem',
		'7'   => '1.75rem',
		'8'   => '2rem',
		'9'   => '2.25rem',
		'10'  => '2.5rem',
		'11'  => '2.75rem',
		'12'  => '3rem',
		'14'  => '3.5rem',
		'16'  => '4rem',
		'20'  => '5rem',
		'24'  => '6rem',
		'28'  => '7rem',
		'32'  => '8rem',
		'36'  => '9rem',
		'40'  => '10rem',
		'44'  => '11rem',
		'48'  => '12rem',
		'52'  => '13rem',
		'56'  => '14rem',
		'60'  => '15rem',
		'64'  => '16rem',
		'72'  => '18rem',
		'80'  => '20rem',
		'96'  => '24rem',
	);

	/**
	 * Named rotate token → degree value (plain-number utilities only).
	 *
	 * @since 1.0.0
	 * @var array<string, int>
	 */
	const TW_ROTATE_SCALE = array(
		'0'   => 0,
		'1'   => 1,
		'2'   => 2,
		'3'   => 3,
		'6'   => 6,
		'12'  => 12,
		'45'  => 45,
		'90'  => 90,
		'180' => 180,
	);

	/**
	 * Named skew token → degree value.
	 *
	 * @since 1.0.0
	 * @var array<string, int>
	 */
	const TW_SKEW_SCALE = array(
		'0'  => 0,
		'1'  => 1,
		'2'  => 2,
		'3'  => 3,
		'6'  => 6,
		'12' => 12,
	);

	/**
	 * Named `scale-*` token → unitless multiplier.
	 *
	 * Tailwind: `scale-{N}` where N is a percent integer → multiplier = N/100.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const TW_SCALE_SCALE = array(
		'0'   => '0',
		'50'  => '0.5',
		'75'  => '0.75',
		'90'  => '0.9',
		'95'  => '0.95',
		'100' => '1',
		'105' => '1.05',
		'110' => '1.1',
		'125' => '1.25',
		'150' => '1.5',
	);

	/**
	 * `origin-*` keyword utilities → transform-origin value.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const TW_TRANSFORM_ORIGIN = array(
		'origin-center'       => 'center',
		'origin-top'          => 'top',
		'origin-top-right'    => 'top right',
		'origin-right'        => 'right',
		'origin-bottom-right' => 'bottom right',
		'origin-bottom'       => 'bottom',
		'origin-bottom-left'  => 'bottom left',
		'origin-left'         => 'left',
		'origin-top-left'     => 'top left',
	);

	/**
	 * Composed `transform` value that every rotate/scale/translate/skew rule emits.
	 *
	 * Each slot falls back to its identity value so a single utility produces a
	 * valid transform even when sibling slots are unset (GBS ships no preflight
	 * to pre-seed the custom properties).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function tw_transform_composed(): string {
		// 3D slots (`rotate-x-*`, `rotate-y-*`, `rotate-z-*`, `translate-z-*`,
		// `scale-z-*`) compose on the same element by stacking their identity
		// fallbacks into the single `transform` declaration. Authors using only
		// 2D utilities get the same render as before — every 3D slot resolves
		// to its identity function (`rotateX(0)`, `scaleZ(1)`, etc.).
		return 'translate(var(--tw-translate-x, 0), var(--tw-translate-y, 0)) '
			. 'translateZ(var(--tw-translate-z, 0)) '
			. 'rotate(var(--tw-rotate, 0)) '
			. 'rotateX(var(--tw-rotate-x, 0)) '
			. 'rotateY(var(--tw-rotate-y, 0)) '
			. 'rotateZ(var(--tw-rotate-z, 0)) '
			. 'skewX(var(--tw-skew-x, 0)) '
			. 'skewY(var(--tw-skew-y, 0)) '
			. 'scaleX(var(--tw-scale-x, 1)) '
			. 'scaleY(var(--tw-scale-y, 1)) '
			. 'scaleZ(var(--tw-scale-z, 1))';
	}

	/**
	 * Append the composed `transform` declaration to a list of variable sets.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $vars Ordered list of `--tw-*: value;` fragments.
	 * @return string Final declaration block.
	 */
	private static function tw_transform_emit( array $vars ): string {
		$vars[] = 'transform: ' . self::tw_transform_composed() . ';';
		return implode( ' ', $vars );
	}

	/**
	 * Resolve Tailwind 2D transform utilities.
	 *
	 * Handles:
	 *   - `rotate-{n}`, `-rotate-{n}`, `rotate-[{val}]`, `-rotate-[{val}]`
	 *   - `scale-{n}`, `-scale-{n}`, `scale-[{val}]`
	 *   - `scale-x-*`, `scale-y-*` (axis-only)
	 *   - `translate-x-*`, `translate-y-*` (spacing scale + fractions + arbitrary)
	 *   - `skew-x-*`, `skew-y-*` (degrees + arbitrary)
	 *   - `origin-*` (transform-origin keywords + arbitrary)
	 *
	 * Every non-origin utility emits the full composed `transform: ...` value so
	 * stacking works regardless of source order or which sibling utilities are
	 * present (GBS has no preflight to seed the `--tw-*` custom properties).
	 *
	 * @since 1.0.0
	 *
	 * @param string $base Base class token (no variants).
	 * @return string|null Declaration block, or null when the token is not a transform utility.
	 */
	private static function resolve_tw_transform( string $base ) {
		if ( '' === $base ) {
			return null;
		}

		// transform-origin — no composition.
		if ( 0 === strpos( $base, 'origin-' ) || 'origin' === $base ) {
			return self::resolve_tw_origin( $base );
		}

		// 3D rotate axes must match before the generic 2D `rotate-` prefix so
		// `rotate-x-12`/`rotate-y-12`/`rotate-z-12` aren't swallowed by the
		// 2D branch. Negatives follow the same ordering.
		if ( 'rotate-x-' === substr( $base, 0, 9 ) ) {
			return self::resolve_tw_rotate_axis( substr( $base, 9 ), 'x', false );
		}
		if ( 'rotate-y-' === substr( $base, 0, 9 ) ) {
			return self::resolve_tw_rotate_axis( substr( $base, 9 ), 'y', false );
		}
		if ( 'rotate-z-' === substr( $base, 0, 9 ) ) {
			return self::resolve_tw_rotate_axis( substr( $base, 9 ), 'z', false );
		}
		if ( '-rotate-x-' === substr( $base, 0, 10 ) ) {
			return self::resolve_tw_rotate_axis( substr( $base, 10 ), 'x', true );
		}
		if ( '-rotate-y-' === substr( $base, 0, 10 ) ) {
			return self::resolve_tw_rotate_axis( substr( $base, 10 ), 'y', true );
		}
		if ( '-rotate-z-' === substr( $base, 0, 10 ) ) {
			return self::resolve_tw_rotate_axis( substr( $base, 10 ), 'z', true );
		}

		// rotate-* and -rotate-*.
		if ( 'rotate-' === substr( $base, 0, 7 ) ) {
			return self::resolve_tw_rotate( substr( $base, 7 ), false );
		}
		if ( '-rotate-' === substr( $base, 0, 8 ) ) {
			return self::resolve_tw_rotate( substr( $base, 8 ), true );
		}

		// scale-z-* / -scale-z-* (3D).
		if ( 'scale-z-' === substr( $base, 0, 8 ) ) {
			return self::resolve_tw_scale_z( substr( $base, 8 ), false );
		}
		if ( '-scale-z-' === substr( $base, 0, 9 ) ) {
			return self::resolve_tw_scale_z( substr( $base, 9 ), true );
		}

		// translate-z-* / -translate-z-* (3D).
		if ( 'translate-z-' === substr( $base, 0, 12 ) ) {
			return self::resolve_tw_translate_z( substr( $base, 12 ), false );
		}
		if ( '-translate-z-' === substr( $base, 0, 13 ) ) {
			return self::resolve_tw_translate_z( substr( $base, 13 ), true );
		}

		// scale-x-* / scale-y-* / -scale-x-* / -scale-y-*.
		if ( 'scale-x-' === substr( $base, 0, 8 ) ) {
			return self::resolve_tw_scale_axis( substr( $base, 8 ), 'x', false );
		}
		if ( 'scale-y-' === substr( $base, 0, 8 ) ) {
			return self::resolve_tw_scale_axis( substr( $base, 8 ), 'y', false );
		}
		if ( '-scale-x-' === substr( $base, 0, 9 ) ) {
			return self::resolve_tw_scale_axis( substr( $base, 9 ), 'x', true );
		}
		if ( '-scale-y-' === substr( $base, 0, 9 ) ) {
			return self::resolve_tw_scale_axis( substr( $base, 9 ), 'y', true );
		}

		// scale-* / -scale-* (both axes).
		if ( 'scale-' === substr( $base, 0, 6 ) ) {
			return self::resolve_tw_scale_both( substr( $base, 6 ), false );
		}
		if ( '-scale-' === substr( $base, 0, 7 ) ) {
			return self::resolve_tw_scale_both( substr( $base, 7 ), true );
		}

		// translate-x-* / translate-y-* / -translate-x-* / -translate-y-*.
		if ( 'translate-x-' === substr( $base, 0, 12 ) ) {
			return self::resolve_tw_translate( substr( $base, 12 ), 'x', false );
		}
		if ( 'translate-y-' === substr( $base, 0, 12 ) ) {
			return self::resolve_tw_translate( substr( $base, 12 ), 'y', false );
		}
		if ( '-translate-x-' === substr( $base, 0, 13 ) ) {
			return self::resolve_tw_translate( substr( $base, 13 ), 'x', true );
		}
		if ( '-translate-y-' === substr( $base, 0, 13 ) ) {
			return self::resolve_tw_translate( substr( $base, 13 ), 'y', true );
		}

		// skew-x-* / skew-y-* / -skew-x-* / -skew-y-*.
		if ( 'skew-x-' === substr( $base, 0, 7 ) ) {
			return self::resolve_tw_skew( substr( $base, 7 ), 'x', false );
		}
		if ( 'skew-y-' === substr( $base, 0, 7 ) ) {
			return self::resolve_tw_skew( substr( $base, 7 ), 'y', false );
		}
		if ( '-skew-x-' === substr( $base, 0, 8 ) ) {
			return self::resolve_tw_skew( substr( $base, 8 ), 'x', true );
		}
		if ( '-skew-y-' === substr( $base, 0, 8 ) ) {
			return self::resolve_tw_skew( substr( $base, 8 ), 'y', true );
		}

		return null;
	}

	/**
	 * Resolve `origin-*` utilities.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base Full token (e.g. `origin-top-right`, `origin-[30%_60%]`).
	 * @return string|null
	 */
	private static function resolve_tw_origin( string $base ) {
		if ( isset( self::TW_TRANSFORM_ORIGIN[ $base ] ) ) {
			return 'transform-origin: ' . self::TW_TRANSFORM_ORIGIN[ $base ] . ';';
		}

		if ( 0 === strpos( $base, 'origin-[' ) && ']' === substr( $base, -1 ) ) {
			$raw   = substr( $base, strlen( 'origin-[' ), -1 );
			$value = self::sanitize_gradient_arbitrary( $raw );
			if ( '' === $value ) {
				return null;
			}
			return 'transform-origin: ' . $value . ';';
		}

		return null;
	}

	/**
	 * Resolve the suffix of a `rotate-*` token.
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix   Portion after `rotate-` / `-rotate-`.
	 * @param bool   $negative Whether the token carried the leading `-`.
	 * @return string|null
	 */
	private static function resolve_tw_rotate( string $suffix, bool $negative ) {
		if ( '' === $suffix ) {
			return null;
		}

		$angle = null;

		if ( '[' === $suffix[0] && ']' === substr( $suffix, -1 ) ) {
			$raw   = substr( $suffix, 1, -1 );
			$clean = self::sanitize_gradient_arbitrary( $raw );
			if ( '' === $clean ) {
				return null;
			}
			// Must look like an angle: Ndeg|Nrad|Nturn|Ngrad.
			if ( ! preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:deg|rad|turn|grad)$/i', $clean ) ) {
				return null;
			}
			$angle = $negative ? self::tw_negate_angle( $clean ) : $clean;
		} elseif ( isset( self::TW_ROTATE_SCALE[ $suffix ] ) ) {
			$deg   = self::TW_ROTATE_SCALE[ $suffix ];
			$angle = ( $negative && 0 !== $deg ) ? ( '-' . $deg . 'deg' ) : ( $deg . 'deg' );
		}

		if ( null === $angle ) {
			return null;
		}

		return self::tw_transform_emit( array( '--tw-rotate: ' . $angle . ';' ) );
	}

	/**
	 * Resolve `scale-*` / `-scale-*` (both axes).
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix   Portion after `scale-` / `-scale-`.
	 * @param bool   $negative Whether the token carried the leading `-`.
	 * @return string|null
	 */
	private static function resolve_tw_scale_both( string $suffix, bool $negative ) {
		$value = self::resolve_tw_scale_value( $suffix, $negative );
		if ( null === $value ) {
			return null;
		}
		return self::tw_transform_emit(
			array(
				'--tw-scale-x: ' . $value . ';',
				'--tw-scale-y: ' . $value . ';',
			)
		);
	}

	/**
	 * Resolve `scale-x-*` / `scale-y-*` (single axis).
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix   Portion after `scale-{x|y}-`.
	 * @param string $axis     `x` or `y`.
	 * @param bool   $negative Whether the token carried the leading `-`.
	 * @return string|null
	 */
	private static function resolve_tw_scale_axis( string $suffix, string $axis, bool $negative ) {
		$value = self::resolve_tw_scale_value( $suffix, $negative );
		if ( null === $value ) {
			return null;
		}
		return self::tw_transform_emit( array( '--tw-scale-' . $axis . ': ' . $value . ';' ) );
	}

	/**
	 * Resolve a scale suffix to a unitless multiplier string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix   Suffix portion (named key or bracket value).
	 * @param bool   $negative Whether to negate the final value.
	 * @return string|null
	 */
	private static function resolve_tw_scale_value( string $suffix, bool $negative ) {
		if ( '' === $suffix ) {
			return null;
		}

		if ( '[' === $suffix[0] && ']' === substr( $suffix, -1 ) ) {
			$raw   = substr( $suffix, 1, -1 );
			$clean = self::sanitize_gradient_arbitrary( $raw );
			if ( '' === $clean ) {
				return null;
			}
			// Unitless number (int or decimal), optional leading `-`.
			if ( ! preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)$/', $clean ) ) {
				return null;
			}
			return $negative ? self::tw_negate_number( $clean ) : $clean;
		}

		if ( ! isset( self::TW_SCALE_SCALE[ $suffix ] ) ) {
			return null;
		}

		$value = self::TW_SCALE_SCALE[ $suffix ];
		if ( $negative && '0' !== $value ) {
			$value = '-' . $value;
		}
		return $value;
	}

	/**
	 * Resolve `translate-{x|y}-*` / `-translate-{x|y}-*`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix   Portion after `translate-{x|y}-`.
	 * @param string $axis     `x` or `y`.
	 * @param bool   $negative Whether the token carried the leading `-`.
	 * @return string|null
	 */
	private static function resolve_tw_translate( string $suffix, string $axis, bool $negative ) {
		if ( '' === $suffix ) {
			return null;
		}

		$value = null;

		// Arbitrary bracket value.
		if ( '[' === $suffix[0] && ']' === substr( $suffix, -1 ) ) {
			$raw   = substr( $suffix, 1, -1 );
			$clean = self::sanitize_gradient_arbitrary( $raw );
			if ( '' === $clean ) {
				return null;
			}
			$value = $negative ? self::tw_negate_length( $clean ) : $clean;
		} elseif ( 'full' === $suffix ) {
			$value = $negative ? '-100%' : '100%';
		} elseif ( preg_match( '#^(\d+)/(\d+)$#', $suffix, $m ) ) {
			$den = (int) $m[2];
			if ( 0 === $den ) {
				return null;
			}
			$pct = ( (int) $m[1] ) / $den * 100;
			// Round to 6 decimals, trim trailing zeroes.
			$pct_str = rtrim( rtrim( sprintf( '%.6F', $pct ), '0' ), '.' );
			if ( '' === $pct_str ) {
				$pct_str = '0';
			}
			$value = $negative && '0' !== $pct_str ? '-' . $pct_str . '%' : $pct_str . '%';
		} elseif ( isset( self::TW_SPACING_SCALE[ $suffix ] ) ) {
			$rem = self::TW_SPACING_SCALE[ $suffix ];
			if ( '0' === $rem ) {
				$value = '0';
			} else {
				$value = $negative ? '-' . $rem : $rem;
			}
		}

		if ( null === $value ) {
			return null;
		}

		// Use the CSS individual `translate` property rather than folding
		// translate into the composed `transform: translate(...) ...`
		// shorthand. `translate` composes independently with `transform`,
		// so animations that set `transform: scale(...)` in @keyframes no
		// longer wipe positioning translates (e.g. the
		// `-translate-x-1/2 -translate-y-1/2` centering idiom survives an
		// `animate-pulse-ring` whose keyframes animate `transform: scale`).
		// The CSS spec applies `translate` before `transform`, so final
		// composition order matches the previous behavior exactly.
		//
		// Axis values are still stored in `--tw-translate-x/y` custom
		// properties so sibling `translate-x-*` and `translate-y-*`
		// utilities compose on the same element (the `translate:` shorthand
		// reads both vars with zero fallbacks for the unset axis).
		return 'translate: var(--tw-translate-x, 0) var(--tw-translate-y, 0); '
			. '--tw-translate-' . $axis . ': ' . $value . ';';
	}

	/**
	 * Resolve `skew-{x|y}-*` / `-skew-{x|y}-*`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix   Portion after `skew-{x|y}-`.
	 * @param string $axis     `x` or `y`.
	 * @param bool   $negative Whether the token carried the leading `-`.
	 * @return string|null
	 */
	private static function resolve_tw_skew( string $suffix, string $axis, bool $negative ) {
		if ( '' === $suffix ) {
			return null;
		}

		$angle = null;

		if ( '[' === $suffix[0] && ']' === substr( $suffix, -1 ) ) {
			$raw   = substr( $suffix, 1, -1 );
			$clean = self::sanitize_gradient_arbitrary( $raw );
			if ( '' === $clean ) {
				return null;
			}
			if ( ! preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:deg|rad|turn|grad)$/i', $clean ) ) {
				return null;
			}
			$angle = $negative ? self::tw_negate_angle( $clean ) : $clean;
		} elseif ( isset( self::TW_SKEW_SCALE[ $suffix ] ) ) {
			$deg   = self::TW_SKEW_SCALE[ $suffix ];
			$angle = ( $negative && 0 !== $deg ) ? ( '-' . $deg . 'deg' ) : ( $deg . 'deg' );
		}

		if ( null === $angle ) {
			return null;
		}

		return self::tw_transform_emit( array( '--tw-skew-' . $axis . ': ' . $angle . ';' ) );
	}

	/**
	 * Resolve `rotate-{x|y|z}-*` / `-rotate-{x|y|z}-*` (3D rotate axes).
	 *
	 * Same numeric scale and arbitrary-bracket contract as the 2D rotate; the
	 * resulting angle is stored in the per-axis `--tw-rotate-{axis}` custom
	 * property so it composes with sibling transforms through
	 * `tw_transform_composed()`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix   Portion after `rotate-{x|y|z}-`.
	 * @param string $axis     `x`, `y`, or `z`.
	 * @param bool   $negative Whether the token carried the leading `-`.
	 * @return string|null
	 */
	private static function resolve_tw_rotate_axis( string $suffix, string $axis, bool $negative ) {
		if ( '' === $suffix ) {
			return null;
		}

		$angle = null;

		if ( '[' === $suffix[0] && ']' === substr( $suffix, -1 ) ) {
			$raw   = substr( $suffix, 1, -1 );
			$clean = self::sanitize_gradient_arbitrary( $raw );
			if ( '' === $clean ) {
				return null;
			}
			if ( ! preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:deg|rad|turn|grad)$/i', $clean ) ) {
				return null;
			}
			$angle = $negative ? self::tw_negate_angle( $clean ) : $clean;
		} elseif ( isset( self::TW_ROTATE_SCALE[ $suffix ] ) ) {
			$deg   = self::TW_ROTATE_SCALE[ $suffix ];
			$angle = ( $negative && 0 !== $deg ) ? ( '-' . $deg . 'deg' ) : ( $deg . 'deg' );
		}

		if ( null === $angle ) {
			return null;
		}

		return self::tw_transform_emit( array( '--tw-rotate-' . $axis . ': ' . $angle . ';' ) );
	}

	/**
	 * Resolve `scale-z-*` / `-scale-z-*` (3D depth axis).
	 *
	 * Reuses the unitless numeric scale from `resolve_tw_scale_value` and
	 * stores the result in `--tw-scale-z`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix   Portion after `scale-z-` / `-scale-z-`.
	 * @param bool   $negative Whether the token carried the leading `-`.
	 * @return string|null
	 */
	private static function resolve_tw_scale_z( string $suffix, bool $negative ) {
		$value = self::resolve_tw_scale_value( $suffix, $negative );
		if ( null === $value ) {
			return null;
		}
		return self::tw_transform_emit( array( '--tw-scale-z: ' . $value . ';' ) );
	}

	/**
	 * Resolve `translate-z-*` / `-translate-z-*`.
	 *
	 * Only accepts spacing-scale keys and arbitrary lengths — no fractional
	 * percentage form because `translateZ(%)` is invalid CSS.
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix   Portion after `translate-z-` / `-translate-z-`.
	 * @param bool   $negative Whether the token carried the leading `-`.
	 * @return string|null
	 */
	private static function resolve_tw_translate_z( string $suffix, bool $negative ) {
		if ( '' === $suffix ) {
			return null;
		}

		$value = null;

		if ( '[' === $suffix[0] && ']' === substr( $suffix, -1 ) ) {
			$raw   = substr( $suffix, 1, -1 );
			$clean = self::sanitize_gradient_arbitrary( $raw );
			if ( '' === $clean ) {
				return null;
			}
			// translateZ cannot take % — reject percentage arbitrary values.
			if ( false !== strpos( $clean, '%' ) ) {
				return null;
			}
			$value = $negative ? self::tw_negate_length( $clean ) : $clean;
		} elseif ( isset( self::TW_SPACING_SCALE[ $suffix ] ) ) {
			$rem = self::TW_SPACING_SCALE[ $suffix ];
			if ( '0' === $rem ) {
				$value = '0';
			} else {
				$value = $negative ? '-' . $rem : $rem;
			}
		}

		if ( null === $value ) {
			return null;
		}

		return self::tw_transform_emit( array( '--tw-translate-z: ' . $value . ';' ) );
	}

	/**
	 * Negate an angle value (`45deg` → `-45deg`, `-45deg` → `45deg`).
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Angle with unit.
	 * @return string
	 */
	private static function tw_negate_angle( string $value ): string {
		if ( '0' === $value ) {
			return $value;
		}
		if ( '-' === $value[0] ) {
			return substr( $value, 1 );
		}
		// Avoid `-0deg`.
		if ( preg_match( '/^0*(?:\.0+)?(deg|rad|turn|grad)$/i', $value ) ) {
			return $value;
		}
		return '-' . $value;
	}

	/**
	 * Negate a bare number string (`1.1` → `-1.1`, `-1.1` → `1.1`).
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Numeric string.
	 * @return string
	 */
	private static function tw_negate_number( string $value ): string {
		if ( '-' === $value[0] ) {
			return substr( $value, 1 );
		}
		if ( preg_match( '/^0*(?:\.0+)?$/', $value ) ) {
			return $value;
		}
		return '-' . $value;
	}

	/**
	 * Negate a length-ish value (`8px` → `-8px`, `-8px` → `8px`, `1rem` → `-1rem`).
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Length or percentage.
	 * @return string
	 */
	private static function tw_negate_length( string $value ): string {
		if ( '0' === $value ) {
			return $value;
		}
		if ( '-' === $value[0] ) {
			return substr( $value, 1 );
		}
		return '-' . $value;
	}

	/**
	 * Default ring color (matches Tailwind's `ring-blue-500`).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const TW_RING_DEFAULT_COLOR = 'rgb(59 130 246 / 0.5)';

	/**
	 * Composed three-layer `box-shadow` declaration emitted by every ring or
	 * shadow utility. Each --tw-* slot falls back to `0 0 #0000` when unset
	 * (Spectra has no preflight), so a single utility renders correctly.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function tw_ring_composed_box_shadow(): string {
		return 'box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow, 0 0 #0000);';
	}

	/**
	 * Build the `--tw-ring-shadow` declaration for a given ring width in px.
	 *
	 * Uses `var(--tw-ring-inset,)` with an empty fallback so an adjacent
	 * `.ring-inset` toggle flips the resulting shadow to inset without
	 * requiring the inset var to be pre-seeded. Standards-compliant per
	 * CSS Custom Properties spec.
	 *
	 * @since 1.0.0
	 *
	 * @param string $width_px Ring width with `px` unit (e.g. `2px`).
	 * @return string Single declaration ending in `;`.
	 */
	private static function tw_ring_shadow_decl( string $width_px ): string {
		/**
		 * Filters whether to preserve the Tailwind v3 default ring color.
		 *
		 * Tailwind v4 changed the default from `rgb(59 130 246 / 0.5)` (the
		 * `blue-500/50` legacy look) to `currentColor`. Return true to pin
		 * the old v3 color for sites that relied on that specific tint.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $v3_compat Whether to emit the v3 ring color default.
		 */
		$v3_compat = (bool) apply_filters( 'spectra_blocks_ring_v3_compat', false );
		$default   = $v3_compat ? self::TW_RING_DEFAULT_COLOR : 'currentColor';

		return '--tw-ring-shadow: var(--tw-ring-inset,) 0 0 0 calc(' . $width_px . ' + var(--tw-ring-offset-width, 0px)) var(--tw-ring-color, ' . $default . ');';
	}

	/**
	 * Emit a full ring width utility block: the `--tw-ring-shadow` variable
	 * plus the composed three-layer `box-shadow` so the ring renders.
	 *
	 * @since 1.0.0
	 *
	 * @param string $width_px Ring width with `px` unit.
	 * @return string Declaration block.
	 */
	private static function tw_ring_emit_width( string $width_px ): string {
		return self::tw_ring_shadow_decl( $width_px ) . ' ' . self::tw_ring_composed_box_shadow();
	}

	/**
	 * Emit a full ring-offset utility block: sets `--tw-ring-offset-width`
	 * and the offset-shadow variable, then re-emits the composed box-shadow.
	 *
	 * @since 1.0.0
	 *
	 * @param string $width_px Offset width with `px` unit.
	 * @return string Declaration block.
	 */
	private static function tw_ring_offset_emit( string $width_px ): string {
		return '--tw-ring-offset-width: ' . $width_px . '; '
			. '--tw-ring-offset-shadow: 0 0 0 ' . $width_px . ' var(--tw-ring-offset-color, #fff); '
			. self::tw_ring_composed_box_shadow();
	}

	/**
	 * Resolve Tailwind ring utilities.
	 *
	 * Handles:
	 *   - `ring`               → 1px ring (default)
	 *   - `ring-{0|1|2|4|8}`   → Npx ring width
	 *   - `ring-inset`         → `--tw-ring-inset: inset`
	 *   - `ring-offset-{N}`    → Npx ring offset width
	 *   - `ring-[{length}]`    → arbitrary ring width (px/rem/em/%)
	 *   - `ring-[{color}]`     → arbitrary ring color
	 *
	 * Palette-form `ring-{slug}-{shade}` is registered via known-class
	 * entries in ClassRegistry — not resolved here.
	 *
	 * Every width utility emits the composed `box-shadow: var(...)` so the
	 * ring is visible even without a preflight.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base Base class token (no variants).
	 * @return string|null Declaration block, or null when not a ring token.
	 */
	private static function resolve_tw_ring( string $base ) {
		if ( '' === $base || 'ring' !== substr( $base, 0, 4 ) ) {
			return null;
		}

		// Bare `ring` — Tailwind v4 default 1px.
		if ( 'ring' === $base ) {
			return self::tw_ring_emit_width( '1px' );
		}

		if ( 'ring-inset' === $base ) {
			// Re-emit composed box-shadow so the ring renders when `ring-inset`
			// appears before any `ring-N` in the source order.
			return '--tw-ring-inset: inset; ' . self::tw_ring_composed_box_shadow();
		}

		// ring-{N} numeric scale.
		if ( preg_match( '/^ring-(\d+)$/', $base, $m ) ) {
			return self::tw_ring_emit_width( $m[1] . 'px' );
		}

		// ring-offset-{N} numeric scale.
		if ( preg_match( '/^ring-offset-(\d+)$/', $base, $m ) ) {
			return self::tw_ring_offset_emit( $m[1] . 'px' );
		}

		// ring-[value] / ring-offset-[value] arbitrary bracket.
		if ( 0 === strpos( $base, 'ring-offset-[' ) && ']' === substr( $base, -1 ) ) {
			$raw   = substr( $base, strlen( 'ring-offset-[' ), -1 );
			$value = Sanitizer::sanitize_css_value( self::decode_bracket_value( $raw ), true );
			if ( '' === $value ) {
				return null;
			}
			if ( self::looks_like_color( $value ) ) {
				return '--tw-ring-offset-color: ' . $value . ';';
			}
			if ( preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|%)$/i', $value ) ) {
				return self::tw_ring_offset_emit( $value );
			}
			return null;
		}

		if ( 0 === strpos( $base, 'ring-[' ) && ']' === substr( $base, -1 ) ) {
			$raw   = substr( $base, strlen( 'ring-[' ), -1 );
			$value = Sanitizer::sanitize_css_value( self::decode_bracket_value( $raw ), true );
			if ( '' === $value ) {
				return null;
			}
			if ( self::looks_like_color( $value ) ) {
				// Color-only token — set the ring-color var. Ring width comes
				// from a separate `ring-N` utility in the same class list.
				return '--tw-ring-color: ' . $value . ';';
			}
			if ( preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|%)$/i', $value ) ) {
				return self::tw_ring_emit_width( $value );
			}
			return null;
		}

		return null;
	}

	/**
	 * Resolve Tailwind scale-form positional utilities.
	 *
	 * Handles the seven prefix families — `top`, `right`, `bottom`, `left`,
	 * `inset`, `inset-x`, `inset-y` — in their scale form (`top-6`, `-top-4`,
	 * `inset-x-8`, etc.). The bracket form (`top-[20px]`) is served by
	 * {@see self::resolve_per_utility_bracket()} and is intentionally skipped
	 * here so the bracket router gets a chance to run.
	 *
	 * Static utilities (`inset-0`) continue to resolve through the known-class
	 * lookup in {@see self::resolve_declarations()}; this resolver fires only
	 * when no known-class entry matches.
	 *
	 * Accepted values:
	 *   - Tailwind spacing scale keys from {@see self::TW_SPACING_SCALE}
	 *     (`0`, `px`, `0.5`…`96`) — rem-scaled.
	 *   - Fractional percentages (`1/2`, `1/3`, `2/3`, `1/4`, `2/4`, `3/4`).
	 *   - Keywords `full` (`100%`) and `auto`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base Base class token (no variants, no brackets).
	 * @return string|null Declaration block (e.g. `top: 1.5rem`, `left: 1.5rem; right: 1.5rem`), or null on miss.
	 */
	private static function resolve_tw_inset( string $base ): ?string {
		if ( '' === $base ) {
			return null;
		}

		$negative = false;
		$rest     = $base;
		if ( '-' === $rest[0] ) {
			$negative = true;
			$rest     = substr( $rest, 1 );
		}

		// Order matters: longer prefixes first so `inset-x-` is not eaten by `inset-`.
		static $prefixes = array(
			'inset-x-' => array( 'left', 'right' ),
			'inset-y-' => array( 'top', 'bottom' ),
			'inset-'   => array( 'inset' ),
			'top-'     => array( 'top' ),
			'right-'   => array( 'right' ),
			'bottom-'  => array( 'bottom' ),
			'left-'    => array( 'left' ),
		);

		$suffix = null;
		$props  = null;
		foreach ( $prefixes as $prefix => $mapped_props ) {
			if ( 0 === strpos( $rest, $prefix ) ) {
				$suffix = substr( $rest, strlen( $prefix ) );
				$props  = $mapped_props;
				break;
			}
		}

		if ( null === $suffix || '' === $suffix ) {
			return null;
		}

		// Bracket form is served by resolve_per_utility_bracket — do not swallow it here.
		if ( '[' === $suffix[0] ) {
			return null;
		}

		$value = self::tw_inset_value( $suffix, $negative );
		if ( null === $value ) {
			return null;
		}

		$decls = array();
		foreach ( $props as $prop ) {
			$decls[] = $prop . ': ' . $value;
		}

		return implode( '; ', $decls );
	}

	/**
	 * Resolve the tail of a scale-form inset token to a CSS value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $suffix   Portion after the prefix (e.g. `6`, `1/2`, `full`, `auto`, `px`).
	 * @param bool   $negative Whether the original token had a leading `-`.
	 * @return string|null CSS value (e.g. `1.5rem`, `50%`, `auto`), or null on miss.
	 */
	private static function tw_inset_value( string $suffix, bool $negative ): ?string {
		// `auto` — negation is meaningless but harmless; emit as-is.
		if ( 'auto' === $suffix ) {
			return 'auto';
		}

		// `full` — 100%.
		if ( 'full' === $suffix ) {
			return $negative ? '-100%' : '100%';
		}

		// Fractional percentages such as one-half, one-third, two-thirds and the quarter steps.
		if ( preg_match( '#^(\d+)/(\d+)$#', $suffix, $m ) ) {
			$den = (int) $m[2];
			if ( 0 === $den ) {
				return null;
			}
			$pct     = ( (int) $m[1] ) / $den * 100;
			$pct_str = rtrim( rtrim( sprintf( '%.6F', $pct ), '0' ), '.' );
			if ( '' === $pct_str ) {
				$pct_str = '0';
			}
			if ( '0' === $pct_str ) {
				return '0';
			}
			return $negative ? '-' . $pct_str . '%' : $pct_str . '%';
		}

		// Spacing scale keys (the px keyword plus the numeric steps).
		if ( isset( self::TW_SPACING_SCALE[ $suffix ] ) ) {
			$val = self::TW_SPACING_SCALE[ $suffix ];
			if ( '0' === $val ) {
				return '0';
			}
			return $negative ? self::tw_negate_length( $val ) : $val;
		}

		return null;
	}

	/**
	 * Color-prefix whitelist for the alpha-slash grammar.
	 *
	 * Tokens matching `{prefix}-{body}/{0-100}` where `{prefix}` is in this
	 * list resolve their `{prefix}-{body}` part the same way a plain token
	 * would (registered lookup or per-utility bracket), then every color value
	 * in the resolved declaration is wrapped in a `color-mix()` that mixes the
	 * color with transparent at the requested opacity.
	 *
	 * @since 1.0.0
	 * @var array<int, string>
	 */
	const ALPHA_SLASH_PREFIXES = array(
		'bg',
		'text',
		'border',
		'border-t',
		'border-r',
		'border-b',
		'border-l',
		'border-x',
		'border-y',
		'overlay',
		'outline',
		'fill',
		'stroke',
		'ring',
		'divide',
		// Gradient stops — authorable with alpha-slash (e.g. `from-white/10`,
		// `via-primary-500/50`, `to-black/30`). The stop utility emits its color
		// into `--tw-gradient-(from|via|to)`; alpha-slash wraps those custom
		// properties in `color-mix(...)` via `is_color_property` recognition.
		'from',
		'via',
		'to',
	);

	/**
	 * Resolve a `{color-prefix}-{body}/{0-100}` alpha-slash token.
	 *
	 * Returns the declaration block with every color value wrapped in
	 * `color-mix(in srgb, {color} {opacity}%, transparent)`. Non-color values
	 * (e.g. the `px` part of a border shorthand) pass through untouched — the
	 * prefix whitelist ensures only color-producing utilities reach this path.
	 *
	 * @since 1.0.0
	 *
	 * @param string                $base  Base class (no variants).
	 * @param array<string, string> $known Known utility class map.
	 * @return string Declaration string, or empty on failure.
	 */
	private static function resolve_alpha_slash( string $base, array $known ): string {
		$slash = self::find_alpha_slash_position( $base );
		if ( -1 === $slash ) {
			return '';
		}

		$base_class  = substr( $base, 0, $slash );
		$opacity_raw = substr( $base, $slash + 1 );

		if ( '' === $base_class || '' === $opacity_raw ) {
			return '';
		}

		// Resolve opacity in three forms:
		// 1. Integer percentage:  `/40`           → 40%
		// 2. Bracket integer:     `/[40]`         → 40%
		// 3. Bracket decimal 0-1: `/[0.04]`       → 4%
		//
		// Decimal brackets fill the gap below the named scale's `/5` step
		// (extreme low-alpha overlays like `border-white/[0.04]` need this).
		// Bracket integers normalise to the same path so `/[40]` and `/40`
		// behave identically.
		//
		// @since 1.0.0.
		if ( preg_match( '/^\d{1,3}$/', $opacity_raw ) ) {
			$opacity = (int) $opacity_raw;
		} elseif ( preg_match( '/^\[(\d{1,3})\]$/', $opacity_raw, $m ) ) {
			$opacity = (int) $m[1];
		} elseif ( preg_match( '/^\[(0?\.\d+|1(?:\.0+)?)\]$/', $opacity_raw, $m ) ) {
			$opacity = (int) round( ( (float) $m[1] ) * 100 );
		} else {
			return '';
		}

		if ( $opacity < 0 || $opacity > 100 ) {
			return '';
		}

		if ( ! self::has_color_prefix( $base_class ) ) {
			return '';
		}

		$decl = isset( $known[ $base_class ] )
			? self::sanitize_declaration_block( $known[ $base_class ] )
			: self::resolve_per_utility_bracket( $base_class );

		if ( '' === $decl ) {
			return '';
		}

		return self::wrap_declaration_with_alpha( $decl, $opacity );
	}

	/**
	 * Extract the numeric `min-width` value from a media-condition string for
	 * cascade-order sorting. Returns 0 when no `min-width` is present so
	 * non-breakpoint buckets (supports-only, etc.) sort before breakpoint
	 * buckets.
	 *
	 * Recognised inputs:
	 *   `(min-width: 768px)`         → 768
	 *   `(min-width: 64em)`          → 64 * 16 = 1024
	 *   `(min-width: 48rem)`         → 48 * 16 = 768
	 *   `screen and (min-width: …)`  → same as above
	 *   `(min-width: 0)` or no match → 0
	 *
	 * @since 1.0.0
	 *
	 * @param string $media Media condition text.
	 * @return float Pixel-equivalent min-width for sort ordering.
	 */
	private static function extract_min_width( string $media ): float {
		if ( '' === $media ) {
			return 0.0;
		}
		if ( preg_match( '/min-width\s*:\s*(\d+(?:\.\d+)?)(px|em|rem)?/i', $media, $m ) !== 1 ) {
			return 0.0;
		}
		$value = (float) $m[1];
		$unit  = isset( $m[2] ) ? strtolower( $m[2] ) : 'px';
		if ( 'em' === $unit || 'rem' === $unit ) {
			return $value * 16.0;
		}
		return $value;
	}

	/**
	 * Find the last `/` in a token that is not inside a `[...]` bracket.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Token to scan.
	 * @return int Byte offset of the separator, or -1 if no such slash exists.
	 */
	private static function find_alpha_slash_position( string $token ): int {
		$depth  = 0;
		$last   = -1;
		$length = strlen( $token );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $token[ $i ];
			if ( '[' === $char ) {
				++$depth;
				continue;
			}
			if ( ']' === $char ) {
				--$depth;
				continue;
			}
			if ( '/' === $char && 0 === $depth ) {
				$last = $i;
			}
		}

		return $last;
	}

	/**
	 * Whether the token starts with one of the color-affecting prefixes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base Base class (no variants, no opacity suffix).
	 * @return bool
	 */
	private static function has_color_prefix( string $base ): bool {
		foreach ( self::ALPHA_SLASH_PREFIXES as $prefix ) {
			if ( $base === $prefix || 0 === strpos( $base, $prefix . '-' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Wrap every declaration value in a `color-mix(...)` at the given opacity.
	 *
	 * Rejects the entire alpha-slash resolution if any property in the block
	 * is non-color (e.g. `font-size`, `line-height`) — wrapping a length in
	 * `color-mix()` yields invalid CSS. The caller falls back to emitting
	 * nothing for the whole token.
	 *
	 * @since 1.0.0
	 *
	 * @param string $decl    Declaration block (one or more `property: value;` pairs).
	 * @param int    $opacity Opacity in the range 0-100.
	 * @return string
	 */
	private static function wrap_declaration_with_alpha( string $decl, int $opacity ): string {
		$pairs = array_filter( array_map( 'trim', explode( ';', $decl ) ) );
		$out   = array();

		foreach ( $pairs as $pair ) {
			$colon = strpos( $pair, ':' );
			if ( false === $colon ) {
				continue;
			}
			$property = trim( substr( $pair, 0, $colon ) );
			$value    = trim( substr( $pair, $colon + 1 ) );
			if ( '' === $property || '' === $value ) {
				continue;
			}
			// Compositional pass-through declarations (shape-building `var(...)`
			// expressions that reference OTHER color vars) carry no direct color
			// value — just re-emit them as-is so the dependent color-vars still
			// drive the final gradient. Without this, `from-white/10` dropped
			// the whole declaration block because `--tw-gradient-stops` isn't a
			// color-property and `is_color_property` rejected the block.
			if ( self::is_color_var_composition( $property, $value ) ) {
				$out[] = $property . ': ' . $value . ';';
				continue;
			}
			if ( ! self::is_color_property( $property ) ) {
				return '';
			}
			$out[] = $property . ': color-mix(in srgb, ' . $value . ' ' . $opacity . '%, transparent);';
		}

		return implode( ' ', $out );
	}

	/**
	 * Whether a CSS property accepts a color value (and therefore alpha-slash).
	 *
	 * Custom properties in the gradient-var family (`--tw-gradient-from`,
	 * `--tw-gradient-via`, `--tw-gradient-to`) are color-valued too — an
	 * alpha-slash on a `from-*` / `via-*` / `to-*` utility should wrap
	 * those vars the same way it wraps `color` / `background-color`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property CSS property name.
	 * @return bool
	 */
	private static function is_color_property( string $property ): bool {
		if ( (bool) preg_match( '/^--tw-gradient-(from|via|to)$/', $property ) ) {
			return true;
		}
		// Every `text-*` color utility emits both `color` and
		// `-webkit-text-fill-color` (so gradient-clipped text works on
		// WebKit). Alpha-slash must wrap both — otherwise `text-white/50`
		// drops the whole declaration block and the element renders at the
		// inherited / base color instead of the authored opacity.
		return (bool) preg_match(
			'/^(color|-webkit-text-fill-color|background(?:-color)?|outline-color|fill|stroke|border(?:-(?:top|right|bottom|left|block|inline)(?:-(?:start|end))?)?-color)$/',
			$property
		);
	}

	/**
	 * Whether a declaration is a var-composition pass-through — a custom
	 * property that binds MULTIPLE `var(...)` references into a compositional
	 * chain (e.g. `--tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent)`).
	 *
	 * Pass-throughs don't carry a concrete color to wrap in `color-mix()`,
	 * but dropping them would break the compositional chain downstream.
	 * A single-var reference like `--tw-gradient-from: var(--spectra-chromatic1-6)`
	 * is NOT a pass-through — it's a color-value-via-var and MUST be wrapped
	 * by alpha-slash. Hence the `,` requirement in the pattern.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The CSS custom-property name (must start with `--`).
	 * @param string $value    The declaration value to inspect.
	 * @return bool True when the value is a comma-joined composition of 2+ var() references.
	 */
	private static function is_color_var_composition( string $property, string $value ): bool {
		if ( 0 !== strpos( $property, '--' ) ) {
			return false;
		}
		// Comma-joined list of 2+ `var(...)` references (composition), no
		// other tokens in the value.
		return (bool) preg_match( '/^\s*var\s*\([^)]+\)\s*(?:,\s*var\s*\([^)]+\)\s*)+$/', $value );
	}

	/**
	 * Compile a per-utility arbitrary-value bracket token.
	 *
	 * Matches `{prefix}-[{value}]` where `{prefix}` is a key in
	 * {@see self::PREFIX_MAP} and `{value}` is the arbitrary CSS value.
	 * Underscores in the value become spaces (Tailwind convention); a `\_`
	 * escape preserves a literal underscore. Values are strict-sanitized, so
	 * any `var(...)` reference is rejected.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Base token (without variant prefixes).
	 * @return string Sanitized declaration string, or empty on failure.
	 */
	private static function resolve_per_utility_bracket( string $token ): string {
		if ( '' === $token ) {
			return '';
		}

		// Tailwind v4 paren-var shortcut — `{prefix}-(--my-var)` is sugar for
		// `{prefix}-[var(--my-var)]`. Rewrite to the bracket form so the rest
		// of this function handles emission. The variable name is constrained
		// to a CSS-ident shape and the rewritten value is a static `var(...)`
		// the JIT inserted itself, so it bypasses the strict sanitizer's
		// `var(...)` rejection (which exists to block author-supplied
		// arbitrary `var(...)` calls).
		// @since 1.0.0.
		if ( ')' === substr( $token, -1 ) && false !== strpos( $token, '-(--' ) ) {
			$paren_open = strpos( $token, '-(' );
			if ( false !== $paren_open && $paren_open > 0 ) {
				$paren_prefix = substr( $token, 0, $paren_open );
				$paren_body   = substr( $token, $paren_open + 2, -1 );
				if (
					is_string( $paren_prefix ) && '' !== $paren_prefix
					&& is_string( $paren_body ) && '' !== $paren_body
					&& 1 === preg_match( '/^--[a-zA-Z_][a-zA-Z0-9_-]*$/', $paren_body )
				) {
					return self::resolve_paren_var( $paren_prefix, $paren_body );
				}
			}
		}

		if ( ']' !== substr( $token, -1 ) ) {
			return '';
		}

		$open = strpos( $token, '-[' );
		if ( false === $open || 0 === $open ) {
			return '';
		}

		$prefix = substr( $token, 0, $open );
		$raw    = substr( $token, $open + 2, -1 );

		if ( ! is_string( $prefix ) || '' === $prefix || ! is_string( $raw ) || '' === $raw ) {
			return '';
		}

		// Reject malformed tokens where the bracket body contains an unpaired `[`
		// or `]` (e.g. `p-[10px]-[20px]`). Legitimate Tailwind bracket values
		// never contain these characters.
		if ( false !== strpos( $raw, '[' ) || false !== strpos( $raw, ']' ) ) {
			return '';
		}

		// Reject CSS-injection characters in arbitrary bracket values.
		// `;`, `{`, `}`, `/*` have no legitimate use inside a Tailwind arbitrary
		// value — they only appear when an author is trying to escape the
		// declaration block. Mirrors the variant-selector sanitizer contract
		// at `sanitize_variant_selector()`.
		if ( preg_match( '#[{};]|/\*#', $raw ) ) {
			return '';
		}

		// Negative bracket forms — `-mt-[110px]`, `-ml-[100px]`, `-top-[8px]`.
		// The leading `-` is the Tailwind negation sigil and is NOT part of
		// the prefix map key. Strip it, look up the positive prefix, and
		// negate the length value via `tw_negate_length`. Only length-shaped
		// values are negated safely — if the value isn't a length (e.g. a
		// color, keyword), we bail so we never emit invalid CSS like
		// `border-color: -red`.
		$negate = false;
		if ( '' !== $prefix && '-' === $prefix[0] && isset( self::PREFIX_MAP[ substr( $prefix, 1 ) ] ) ) {
			$prefix = substr( $prefix, 1 );
			$negate = true;
		}

		// Filter / backdrop-filter family — bracket value is wrapped in the
		// filter-fn (e.g. `blur-[100px]` → `filter: blur(100px);`). Resolved
		// before PREFIX_MAP lookup because the same prefix family is intentionally
		// absent from PREFIX_MAP (filter functions are not single-property maps).
		if ( isset( self::FILTER_FN_MAP[ $prefix ] ) || isset( self::BACKDROP_FILTER_FN_MAP[ $prefix ] ) ) {
			$value = self::decode_bracket_value( $raw );
			$clean = Sanitizer::sanitize_css_value( $value, true );
			if ( '' === $clean ) {
				return '';
			}
			if ( isset( self::FILTER_FN_MAP[ $prefix ] ) ) {
				$fn = self::FILTER_FN_MAP[ $prefix ];
				return 'filter: ' . $fn . '(' . $clean . ');';
			}
			$fn   = self::BACKDROP_FILTER_FN_MAP[ $prefix ];
			$decl = $fn . '(' . $clean . ')';
			return 'backdrop-filter: ' . $decl . '; -webkit-backdrop-filter: ' . $decl . ';';
		}

		if ( ! isset( self::PREFIX_MAP[ $prefix ] ) ) {
			return '';
		}

		$mapped = self::PREFIX_MAP[ $prefix ];
		$value  = self::decode_bracket_value( $raw );
		$clean  = Sanitizer::sanitize_css_value( $value, true );

		if ( '' === $clean ) {
			return '';
		}

		if ( $negate ) {
			// Only negate values that look like a length / number with optional
			// unit. Reject keywords / colors / functions so we never emit
			// malformed CSS.
			if ( ! preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|%|vh|vw|vmin|vmax|ch|ex|pt|pc|in|mm|cm|q)?$/i', $clean ) ) {
				return '';
			}
			$clean = self::tw_negate_length( $clean );
		}

		if ( is_string( $mapped ) && 0 === strpos( $mapped, '__shape:' ) ) {
			$shape    = substr( $mapped, 8 );
			$resolved = self::resolve_shape_aware_prefix( $shape, $clean );
			if ( null === $resolved ) {
				return '';
			}
			$mapped = $resolved;
		}

		$properties = is_array( $mapped ) ? $mapped : array( $mapped );
		$parts      = array();

		foreach ( $properties as $property ) {
			if ( ! is_string( $property ) ) {
				continue;
			}

			$clean_property = Sanitizer::sanitize_css_property( $property );
			if ( '' === $clean_property ) {
				continue;
			}

			$parts[] = $clean_property . ': ' . $clean . ';';
		}

		return implode( ' ', $parts );
	}

	/**
	 * Resolve the Tailwind v4 paren-var shortcut — `{prefix}-(--name)` →
	 * `{property}: var(--name);`. The variable name is pre-validated by
	 * the caller against `--[a-zA-Z_][a-zA-Z0-9_-]*`, so the emitted value
	 * is a static `var(--name)` literal — no author-supplied content lands
	 * in the declaration. Negative prefixes are not supported (a `var(...)`
	 * value isn't a length we can negate at compile time).
	 *
	 * @since 1.0.0
	 *
	 * @param string $prefix   Tailwind utility prefix (e.g. `bg`, `text`, `mt`).
	 * @param string $var_name Validated CSS custom-property name including `--`.
	 * @return string Sanitized declaration string, or empty on failure.
	 */
	private static function resolve_paren_var( string $prefix, string $var_name ): string {
		// Filter / backdrop-filter family — wrap the var() in the filter fn.
		if ( isset( self::FILTER_FN_MAP[ $prefix ] ) || isset( self::BACKDROP_FILTER_FN_MAP[ $prefix ] ) ) {
			$value_expr = 'var(' . $var_name . ')';
			if ( isset( self::FILTER_FN_MAP[ $prefix ] ) ) {
				$fn = self::FILTER_FN_MAP[ $prefix ];
				return 'filter: ' . $fn . '(' . $value_expr . ');';
			}
			$fn   = self::BACKDROP_FILTER_FN_MAP[ $prefix ];
			$decl = $fn . '(' . $value_expr . ')';
			return 'backdrop-filter: ' . $decl . '; -webkit-backdrop-filter: ' . $decl . ';';
		}

		if ( ! isset( self::PREFIX_MAP[ $prefix ] ) ) {
			return '';
		}

		$mapped     = self::PREFIX_MAP[ $prefix ];
		$value_expr = 'var(' . $var_name . ')';

		// Shape-aware prefixes (`text`, `bg`, `border`, `outline`, `fill`,
		// `stroke`) cannot be statically resolved from a `var(...)` value —
		// the CSS engine doesn't know if the variable holds a color or a
		// length. Tailwind v4 resolves this by routing each shape-aware
		// prefix to its DEFAULT longhand: `text-(--c)` → `color`,
		// `bg-(--c)` → `background-color`, `border-(--c)` → `border-color`,
		// `outline-(--c)` → `outline-color`, `fill-(--c)` → `fill`,
		// `stroke-(--c)` → `stroke`.
		if ( is_string( $mapped ) && 0 === strpos( $mapped, '__shape:' ) ) {
			$shape         = substr( $mapped, 8 );
			$default_props = array(
				'text'    => 'color',
				'bg'      => 'background-color',
				'border'  => 'border-color',
				'outline' => 'outline-color',
				'fill'    => 'fill',
				'stroke'  => 'stroke',
			);
			if ( ! isset( $default_props[ $shape ] ) ) {
				return '';
			}
			$mapped = $default_props[ $shape ];
		}

		$properties = is_array( $mapped ) ? $mapped : array( $mapped );
		$parts      = array();

		foreach ( $properties as $property ) {
			if ( ! is_string( $property ) ) {
				continue;
			}
			$clean_property = Sanitizer::sanitize_css_property( $property );
			if ( '' === $clean_property ) {
				continue;
			}
			$parts[] = $clean_property . ': ' . $value_expr . ';';
		}

		return implode( ' ', $parts );
	}

	/**
	 * Disambiguate a shape-aware prefix (`text`, `bg`) by inspecting the value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $shape Shape key: `text` or `bg`.
	 * @param string $value Cleaned CSS value.
	 * @return string|null Resolved CSS property, or null if the shape cannot be resolved.
	 */
	private static function resolve_shape_aware_prefix( string $shape, string $value ) {
		$is_color = self::looks_like_color( $value );
		$is_url   = (bool) preg_match( '/^url\s*\(/i', $value );

		// Math function values (clamp/calc/min/max) resolve to a length-like
		// shape. Used below to let `text-[clamp(...)]` map to font-size and
		// `border-[calc(...)]` map to border-width.
		$is_math_fn = (bool) preg_match( '/^(?:clamp|calc|min|max)\s*\(/i', $value );
		// Gradient values resolve to a background-image shape.
		$is_gradient = (bool) preg_match( '/^(?:linear|radial|conic|repeating-linear|repeating-radial|repeating-conic)-gradient\s*\(/i', $value );

		if ( 'text' === $shape ) {
			if ( $is_color ) {
				return 'color';
			}
			if ( $is_math_fn || preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|%|vh|vw|vmin|vmax|ch|ex|pt|pc|in|mm|cm|q)?$/i', $value ) ) {
				return 'font-size';
			}
			return null;
		}

		if ( 'bg' === $shape ) {
			// `bg-[<value>]` narrows to the LONGHAND property that matches
			// the value's shape. Emitting the `background:` shorthand resets
			// `background-clip` to `border-box` per CSS spec, which silently
			// destroys any paired `background-clip: text` / `bg-clip-text` /
			// `.sg-text-gradient` — breaking text-gradient recipes.
			//
			// Pure gradient / pure url → `background-image` (longhand)
			// Pure color               → `background-color` (longhand)
			// Compound (multi-token)   → no rule emitted (current behavior;
			// compound bracket values are not a
			// supported constituency — neither
			// Tailwind nor this JIT resolves them)
			if ( $is_url || $is_gradient ) {
				return 'background-image';
			}
			if ( $is_color ) {
				return 'background-color';
			}
			return null;
		}

		if ( 'border' === $shape ) {
			if ( $is_color ) {
				return 'border-color';
			}
			if ( $is_math_fn || preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|%|vh|vw|vmin|vmax|ch|ex|pt|pc|in|mm|cm|q)?$/i', $value ) ) {
				return 'border-width';
			}
			return null;
		}

		if ( 'outline' === $shape ) {
			if ( $is_color ) {
				return 'outline-color';
			}
			if ( $is_math_fn || preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|%|vh|vw|vmin|vmax|ch|ex|pt|pc|in|mm|cm|q)?$/i', $value ) ) {
				return 'outline-width';
			}
			return null;
		}

		if ( 'fill' === $shape ) {
			// `fill` only takes paint values — a color, a paint keyword, or a url(#id).
			// Width has no meaning here; unitless numerics in brackets are rejected.
			if ( $is_color ) {
				return 'fill';
			}
			$lower = strtolower( $value );
			if ( 'none' === $lower || 'transparent' === $lower || 'currentcolor' === $lower || 'inherit' === $lower ) {
				return 'fill';
			}
			if ( $is_url ) {
				return 'fill';
			}
			return null;
		}

		if ( 'stroke' === $shape ) {
			// `stroke` is overloaded — a paint value (color/keyword/url) OR a
			// stroke-width length. Color wins when the value looks like a color;
			// otherwise a length (incl. unitless number) maps to stroke-width.
			if ( $is_color ) {
				return 'stroke';
			}
			$lower = strtolower( $value );
			if ( 'none' === $lower || 'transparent' === $lower || 'currentcolor' === $lower || 'inherit' === $lower ) {
				return 'stroke';
			}
			if ( $is_url ) {
				return 'stroke';
			}
			// Length / unitless number → stroke-width. Accepts `2`, `2px`, `0.5`,
			// `calc(...)`, etc. Matches the same length/math regex the other
			// shape-aware cases use, plus a bare integer fallback.
			if ( $is_math_fn || preg_match( '/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:px|rem|em|%|vh|vw|vmin|vmax|ch|ex|pt|pc|in|mm|cm|q)?$/i', $value ) ) {
				return 'stroke-width';
			}
			return null;
		}

		return null;
	}

	/**
	 * Heuristically determine whether a CSS value is a color.
	 *
	 * Recognizes hex, rgb/rgba/hsl/hsla/hwb/lab/lch/oklab/oklch/color() functions,
	 * and the common color keywords. Used to disambiguate `text-[...]` / `bg-[...]`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Cleaned CSS value.
	 * @return bool
	 */
	private static function looks_like_color( string $value ): bool {
		if ( '' === $value ) {
			return false;
		}

		if ( '#' === $value[0] ) {
			return (bool) preg_match( '/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value );
		}

		if ( preg_match( '/^(?:rgb|rgba|hsl|hsla|hwb|lab|lch|oklab|oklch|color)\s*\(/i', $value ) ) {
			return true;
		}

		$keywords = array( 'transparent', 'currentcolor', 'inherit', 'initial', 'unset', 'revert', 'revert-layer', 'black', 'white' );
		if ( in_array( strtolower( $value ), $keywords, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Decode Tailwind-style bracket value conventions.
	 *
	 * Rules: `_` → space, `\_` → literal underscore.
	 *
	 * @since 1.0.0
	 *
	 * @param string $raw Raw bracket value.
	 * @return string
	 */
	private static function decode_bracket_value( string $raw ): string {
		$placeholder = "\x00JITESCAPED\x00";
		$with_guard  = str_replace( '\\_', $placeholder, $raw );
		$spaced      = str_replace( '_', ' ', $with_guard );
		return str_replace( $placeholder, '_', $spaced );
	}

	/**
	 * Sanitize a declaration block string by splitting on `;` and hardening each.
	 *
	 * Used for registry-sourced declarations (already mostly safe) and as a
	 * defense-in-depth layer over external filter output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $declarations Raw `prop: val; prop: val` string.
	 * @return string
	 */
	private static function sanitize_declaration_block( string $declarations ): string {
		$declarations = trim( $declarations );
		if ( '' === $declarations ) {
			return '';
		}

		$parts     = array_filter( array_map( 'trim', explode( ';', $declarations ) ) );
		$sanitized = array();

		foreach ( $parts as $part ) {
			$colon = strpos( $part, ':' );
			if ( false === $colon || 0 === $colon ) {
				continue;
			}

			$property = trim( substr( $part, 0, $colon ) );
			$value    = trim( substr( $part, $colon + 1 ) );

			$clean_property = Sanitizer::sanitize_css_property( $property );
			$clean_value    = Sanitizer::sanitize_css_value( $value );

			if ( '' === $clean_property || '' === $clean_value ) {
				continue;
			}

			$sanitized[] = $clean_property . ': ' . $clean_value . ';';
		}

		return implode( ' ', $sanitized );
	}

	/**
	 * Apply `!important` to every declaration in a compiled block.
	 *
	 * Splits on `;`, trims each part, strips any existing trailing
	 * `!important` (case-insensitive — defensive, in case a resolver
	 * already appended one), and re-emits with a canonical ` !important`.
	 * Reuses the same shape as `sanitize_declaration_block()` so it
	 * handles every resolver-path output uniformly: single-prop, multi-
	 * prop (e.g. `px-*` left+right), arbitrary-property, transform/ring
	 * var-composed, gradient, known-class.
	 *
	 * Invariant: output is `prop: val !important;` terminated per part.
	 *
	 * @since 1.0.0
	 *
	 * @param string $declarations Already-compiled declaration block.
	 * @return string
	 */
	private static function apply_important_to_declarations( string $declarations ): string {
		$declarations = trim( $declarations );
		if ( '' === $declarations ) {
			return '';
		}

		$parts = array_filter( array_map( 'trim', explode( ';', $declarations ) ) );
		$out   = array();

		foreach ( $parts as $part ) {
			$colon = strpos( $part, ':' );
			if ( false === $colon || 0 === $colon ) {
				continue;
			}

			// Defensive: strip any existing `!important` so we never emit
			// `!important !important;`. Case-insensitive per spec.
			$part = preg_replace( '/\s*!important\s*$/i', '', $part );
			if ( ! is_string( $part ) || '' === trim( $part ) ) {
				continue;
			}

			$out[] = rtrim( $part ) . ' !important;';
		}

		return implode( ' ', $out );
	}

	/**
	 * Inject `content:''` into pseudo-element declarations when absent.
	 *
	 * @since 1.0.0
	 *
	 * @param string $declarations Declaration block.
	 * @return string
	 */
	private static function maybe_inject_content( string $declarations ): string {
		if ( false !== stripos( $declarations, 'content:' ) || false !== stripos( $declarations, 'content :' ) ) {
			return $declarations;
		}
		return "content: ''; " . $declarations;
	}

	/**
	 * Escape a class token for use as a CSS selector.
	 *
	 * Implements the safe subset of CSS.escape: every non-word character is
	 * backslash-escaped, preserving Tailwind bracket syntax like
	 * `\[transform\:scale\(1\.05\)\]`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Raw class token.
	 * @return string
	 */
	public static function escape_selector( string $token ): string {
		$result = '';
		$length = strlen( $token );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $token[ $i ];
			if ( preg_match( '/[A-Za-z0-9_-]/', $char ) ) {
				$result .= $char;
			} else {
				$result .= '\\' . $char;
			}
		}

		return $result;
	}

	/**
	 * Collect unique class tokens from a list of className attribute strings.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $class_strings Raw className attribute values.
	 * @return array<int, string>
	 */
	public static function collect_tokens( array $class_strings ): array {
		$seen = array();

		foreach ( $class_strings as $class_string ) {
			if ( ! is_string( $class_string ) || '' === $class_string ) {
				continue;
			}

			$tokens = preg_split( '/\s+/', trim( $class_string ) );
			if ( ! is_array( $tokens ) ) {
				continue;
			}

			foreach ( $tokens as $token ) {
				if ( '' === $token ) {
					continue;
				}
				$seen[ $token ] = true;
			}
		}

		return array_keys( $seen );
	}

	/**
	 * Known utility class map, filterable for Pro extensions.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	private static function get_known_classes(): array {
		$flat = ClassRegistry::get_flat_classes();

		if ( ! is_array( $flat ) ) {
			$flat = array();
		}

		/**
		 * Filters the known utility class map used for JIT resolution.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $flat Class name → CSS declarations.
		 */
		$filtered = apply_filters( 'spectra_gs_jit_known_classes', $flat );

		return is_array( $filtered ) ? $filtered : $flat;
	}

	/**
	 * Extract every className string from a post's rendered block tree.
	 *
	 * Walks parsed blocks recursively — reads the `className` attribute and
	 * any inline `class="..."` in the rendered inner HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Post content (raw block markup).
	 * @return array<int, string>
	 */
	public static function collect_class_strings_from_content( string $content ): array {
		if ( '' === $content ) {
			return array();
		}

		$blocks  = parse_blocks( $content );
		$strings = array();
		self::walk_blocks_for_classes( $blocks, $strings );

		if ( preg_match_all( '/class\s*=\s*"([^"]*)"/i', $content, $matches ) ) {
			foreach ( $matches[1] as $attr ) {
				$strings[] = (string) $attr;
			}
		}

		if ( preg_match_all( "/class\\s*=\\s*'([^']*)'/i", $content, $matches ) ) {
			foreach ( $matches[1] as $attr ) {
				$strings[] = (string) $attr;
			}
		}

		return $strings;
	}

	/**
	 * Recursive helper for {@see collect_class_strings_from_content()}.
	 *
	 * Reads each block's top-level `className` attribute directly, then scans
	 * every string-valued attr (e.g. `text`, `description`, `content` — any
	 * field that may embed inline HTML) for `class="..."` occurrences. This
	 * catches utility classes on `<span>` wrappers inside rich-text attrs
	 * that the raw-content regex in `collect_class_strings_from_content()`
	 * otherwise mis-captures due to JSON-escaped quotes inside block comment
	 * payloads (which produces trailing-backslash tokens the bracket parser
	 * rejects).
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, mixed>> $blocks  Parsed block list.
	 * @param array<int, string>               $strings Accumulator (by-ref).
	 * @return void
	 */
	private static function walk_blocks_for_classes( array $blocks, array &$strings ): void {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
				foreach ( $block['attrs'] as $key => $value ) {
					if ( 'className' === $key && is_string( $value ) ) {
						$strings[] = $value;
						continue;
					}

					if ( is_string( $value ) && false !== strpos( $value, 'class=' ) ) {
						if ( preg_match_all( '/class\s*=\s*"([^"]*)"/i', $value, $matches ) ) {
							foreach ( $matches[1] as $attr ) {
								$strings[] = (string) $attr;
							}
						}
						if ( preg_match_all( "/class\\s*=\\s*'([^']*)'/i", $value, $matches ) ) {
							foreach ( $matches[1] as $attr ) {
								$strings[] = (string) $attr;
							}
						}
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::walk_blocks_for_classes( $block['innerBlocks'], $strings );
			}
		}
	}
}
