// Contract smoke test for the imported-baseline sheet (the ERA import render
// contract). Run: `npm run test:contract` (plain node, no deps).
//
// Every form the build creates renders with SureForms styling disabled (the zip-ai
// bridge answers `srfm_disable_default_styles`; SureForms then enqueues none of its
// frontend stylesheets and stamps `.srfm-styling-none` on the container) and wears
// the source form's own classes (the bridge adds the class map stored on the form
// post as `_zipai_form_classes` to the elements SureForms renders). The page's own
// GBS rules lay the form out and paint it; A19 of the sheet carries ONLY the
// constant structure: SureForms' wrappers made transparent inside a dressed form,
// a plain column for a form the bridge has not dressed, the box model, the hidden
// proxies, the tickbox, the TomSelect internals, the state toggles.
//
// Guards the whack-a-mole classes that stay invisible to the geometry sweep:
//   1. A structural rule gets deleted/renamed in src.
//   2. build/ goes stale vs src (served CSS != checkout).
//   3. The COUPLING breaks — SureForms renames a wrapper, a state class or the
//      marker A19 keys on, or drops the filter the bridge answers — so the rules
//      silently stop matching the rendered DOM.
//
// Failure here means the IMPORT RENDER CONTRACT is broken even if every unit test
// in block-converter and every corpus geometry sweep is green.

import { readFileSync, existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname( fileURLToPath( import.meta.url ) );
const plugin = resolve( here, '..', '..' );

// Canonical form: strip comments, quotes (the minifier drops them where it can) and
// whitespace around punctuation, so a pin matches both the pretty SCSS src and the
// minified build (value-internal spaces, e.g. `8px 16px` and descendant
// combinators, are kept as single spaces). `::after` → `:after` and `0 !important`
// → `0!important` (the minifier's spellings). Pins go through the same function
// before they are looked up.
const canon = ( s ) =>
	s
		.replace( /\/\/[^\n]*/g, '' )
		.replace( /\/\*[\s\S]*?\*\//g, '' )
		.replace( /"/g, '' )
		.replace( /::/g, ':' )
		.replace( /\s*!/g, '!' )
		.replace( /\s*([{};,])\s*/g, '$1' )
		.replace( /\s*:\s*/g, ':' )
		.replace( /;}/g, '}' ) // drop the trailing semicolon src keeps but the minifier strips
		.replace( /\s+/g, ' ' )
		.trim();
const read = ( p ) => canon( readFileSync( p, 'utf8' ) );

let failures = 0;
const fail = ( msg ) => { failures++; console.error( `FAIL  ${msg}` ); };
const ok = ( msg ) => console.log( `ok    ${msg}` );

// ---------------------------------------------------------------------------
// 1. Structural rules that MUST exist in src AND build. Each documents the
//    defect its absence reproduces. Checked as { selector, decls } — the rule
//    BODY is extracted, then each decl looked up inside it, so the check is
//    immune to the minifier's declaration reordering / vendor-prefixing /
//    selector-list merging. A selector is matched WHOLE (it must end at `{` or
//    `,`), so a pin never resolves to a longer selector that merely contains it.
// ---------------------------------------------------------------------------
const GS = ':is([class^="gs-"], [class*=" gs-"])';
const NO_GS = ':not([class^="gs-"]):not([class*=" gs-"])';
const PINS = [
	// Wrappers transparent inside a dressed form — absent: the source's grid / flex
	// row sees SureForms' wrapper instead of the label / input / button.
	{ sel: `:where(.srfm-styling-none .srfm-form${GS} .srfm-block-wrap)`, decls: [ 'display:contents' ] },
	{ sel: `:where(.srfm-styling-none .srfm-form${GS} .srfm-input-content)`, decls: [ 'display:contents' ] },
	{ sel: `:where(.srfm-styling-none .srfm-form${GS} .srfm-submit-container)`, decls: [ 'display:contents' ] },
	{ sel: `:where(.srfm-styling-none .srfm-form${GS} .srfm-submit-container .wp-block-button)`, decls: [ 'display:contents' ] },
	// A form the bridge has not dressed — absent: raw block flow, no gap between rows.
	{ sel: `:where(.srfm-styling-none .srfm-form${NO_GS})`, decls: [ 'display:flex', 'flex-direction:column', 'row-gap:16px' ] },
	// Email row's inner wrapper as a flex column — absent: the row's gap and the
	// `order` rules never reach an email field (hint renders above the control).
	{ sel: ':where(.srfm-styling-none .srfm-email-block)', decls: [ 'display:flex', 'flex-direction:column', 'gap:inherit', 'min-width:0' ] },
	{ sel: ':where(.srfm-styling-none .srfm-email-confirm-block)', decls: [ 'display:flex', 'flex-direction:column', 'gap:inherit', 'min-width:0' ] },
	// Hint after the control, error wrap last — absent: SureForms' print order (hint
	// BEFORE the control) shows.
	{ sel: ':where(.srfm-styling-none .srfm-description)', decls: [ 'order:1' ] },
	{ sel: ':where(.srfm-styling-none .srfm-error-wrap)', decls: [ 'order:2' ] },
	// Error wrap hidden until its field errors — absent: an empty box takes a gap on
	// every row. The counter guard keeps a textarea's character counter visible.
	{ sel: ':where(.srfm-styling-none .srfm-block:not(.srfm-error):not(.srfm-valid-email-error) .srfm-error-wrap:not(:has(.srfm-char-counter-wrap)))', decls: [ 'display:none' ] },
	// …and the confirm-email reveal (its state class sits on the confirm block, not
	// the `.srfm-block`) — absent: a confirm mismatch shows no message.
	{ sel: ':where(.srfm-styling-none) :where(.srfm-email-confirm-block.srfm-error) .srfm-error-wrap', decls: [ 'display:block' ] },
	// box-sizing — absent: padded fields overflow their column.
	{ sel: ':where(.srfm-styling-none *)', decls: [ 'box-sizing:border-box' ] },
	// Input fills its block — absent: inputs shrink to content width.
	{ sel: ':where(.srfm-styling-none .srfm-input-common:not(.srfm-input-checkbox):not(.srfm-input-gdpr))', decls: [ 'width:100%', 'max-width:100%' ] },
	// Grid / flex items default to min-width:auto — absent: a focused dropdown with a
	// long value grows past its track and drops below its row-mate.
	{ sel: ':where(.srfm-styling-none .srfm-block)', decls: [ 'min-width:0' ] },
	// fieldset as a flex column, no UA box — absent: dropdown / multi-choice rows keep
	// the UA border + padding and the `order` rules are inert inside them.
	{ sel: ':where(.srfm-styling-none fieldset)', decls: [ 'display:flex', 'flex-direction:column', 'gap:inherit', 'min-width:0' ] },
	// The legend sits outside the fieldset's content box — absent: it sits flush on
	// its control while text rows get the row's gap.
	{ sel: ':where(.srfm-styling-none .srfm-block-legend)', decls: [ 'display:block', 'margin-block-end:8px' ] },
	// Required asterisk suppressed — absent: a `*` the source (novalidate) never showed.
	{ sel: ':where(.srfm-styling-none .srfm-required)', decls: [ 'display:none' ] },
	// Validation messages hidden until error — absent: every required field shows
	// "This field is required." on first paint. The colour honours the importer's
	// `--error` role under a namespaced override.
	{ sel: ':where(.srfm-styling-none .srfm-error-message)', decls: [ 'display:none', 'color:var(--srfm-imported-error-color,var(--error,#dc2626))' ] },
	// Other always-in-markup, hidden-by-default SureForms elements — absent: they paint
	// on page load. The success box is the worst (a post-submit confirmation on load).
	{ sel: ':where(.srfm-styling-none .srfm-success-box)', decls: [ 'display:none' ] },
	// …but the `.srfm-active` reveal MUST exist too, or a successful submit hides the
	// form and shows nothing (empty card). SureForms' JS adds `.srfm-active` on submit.
	{ sel: ':where(.srfm-styling-none .srfm-success-box.srfm-active)', decls: [ 'display:flex' ] },
	{ sel: ':where(.srfm-styling-none .srfm-loader)', decls: [ 'display:none' ] },
	{ sel: ':where(.srfm-styling-none .srfm-hidden-block)', decls: [ 'display:none' ] },
	// `.srfm-hide` (a page-break form's submit container) at (0,2,1) — absent or back
	// inside `:where()`: the container's `display:contents` ties it and order decides.
	{ sel: ':where(.srfm-styling-none) .srfm-hide', decls: [ 'display:none' ] },
	// Symbol-holder <svg> collapse — absent: the offscreen <symbol> svg renders at the
	// UA 300×150 default and injects a huge empty gap into every checkbox/gdpr field.
	{ sel: ':where(.srfm-styling-none .srfm-inline-svg)', decls: [ 'position:absolute', 'width:0', 'height:0' ] },
	// Checkbox/gdpr box layout + the drawn box — absent: no visible checkbox (the real
	// input is hidden; the box + layout lived in SureForms' skin).
	{ sel: ':where(.srfm-styling-none .srfm-cbx)', decls: [ 'display:flex' ] },
	{ sel: ':where(.srfm-styling-none .srfm-cbx .srfm-span-wrap:first-child)', decls: [ 'width:18px', 'border-radius:4px' ] },
	// Hide the REAL checkbox/gdpr input (we draw our own box) — absent: a checked native
	// checkbox paints a stray second box at the top-left (WP-core screen-reader-text is
	// not reliably present on imported pages).
	{ sel: ':where(.srfm-styling-none .srfm-input-checkbox)', decls: [ 'position:absolute', 'overflow:hidden' ] },
	// Multi-choice option grid as a box at (0,2,1) — absent or back inside `:where()`:
	// the transparent-wrapper rule ties it and order decides whether radio/checkbox
	// groups keep their option grid at all.
	{ sel: ':where(.srfm-styling-none) :where(.srfm-multi-choice-block) .srfm-block-wrap', decls: [ 'display:flex', 'flex-wrap:wrap' ] },
	{ sel: ':where(.srfm-styling-none) :where(.srfm-multi-choice-block) .srfm-vertical-layout', decls: [ 'flex-direction:column' ] },
	{ sel: ':where(.srfm-styling-none .srfm-multi-choice-single .srfm-icon-container)', decls: [ 'display:none' ] },
	// TomSelect's own box zeroed ONLY under a source-styled wrapper (a `gs-` PREFIX, so
	// a source class like `listings-select` never trips it) — absent: a second box
	// inside the source's; substring-matched: a select the source never styled loses
	// its fallback box and both carets. `padding-right` is TomSelect's "×" reserve and
	// is NOT zeroed — only the other three sides.
	{ sel: `:where(.srfm-styling-none .ts-wrapper${GS} .ts-control)`, decls: [ 'border:0 !important', 'padding-top:0 !important', 'padding-left:0 !important', 'background:transparent !important' ] },
	{ sel: `:where(.srfm-styling-none .ts-wrapper${GS} .ts-control)::after`, decls: [ 'display:none !important' ] },
	{ sel: `:where(.srfm-styling-none .ts-wrapper${GS} .ts-dropdown-icon)`, decls: [ 'display:none !important' ] },
	// Dropdown (TomSelect single-select) internals — absent: a selected dropdown shows a
	// stray remove "×" and the search-input cursor overlapping the value.
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common.single .item .remove)', decls: [ 'display:none' ] },
	// Search input collapses ONLY once a value is selected (item then supplies the line
	// box); empty keeps it so the placeholder shows + the height matches text inputs.
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common.single.has-items .ts-control input[id^="tomselect-"])', decls: [ 'height:0' ] },
	// Right-side clear "×" revealed on selection (native SureForms), left of the caret.
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common.has-items .clear-button)', decls: [ 'visibility:visible' ] },
	// …and pinned to the RIGHT — absent: TomSelect's `--ts-pr-caret` puts the "×" on the
	// LEFT, overlapping the selected value.
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common .clear-button)', decls: [ 'right:2.25em', 'left:auto' ] },
	// Dropdown stays ONE line at the input height, in the wrapper's font — absent: the
	// caret wraps to a 2nd line once the value is long, and the box renders shorter
	// than its sibling inputs (TomSelect's own 13px/18px).
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common .ts-control)', decls: [ 'flex-wrap:nowrap', 'align-items:center', 'font-size:inherit', 'line-height:inherit', 'color:inherit' ] },
	// TomSelect's input defaults to 13px/normal — inherit font-size+line-height so a
	// dropdown's box matches the sibling text inputs' height (else it renders shorter).
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common .ts-control input)', decls: [ 'font-size:inherit' ] },
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common .ts-dropdown-icon)', decls: [ 'position:absolute' ] },
	// The open menu is trapped in the wrapper's stacking context the moment a source
	// field class paints it (backdrop-filter/transform/opacity). Lifting the WRAPPER is
	// the usability floor — absent, the options paint behind the next block's controls.
	{ sel: ':where(.srfm-styling-none) .ts-wrapper.dropdown-active', decls: [ 'position:relative', 'z-index:20' ] },
	// A17 heading revert must cover the axes the HOST's global-styles user layer sets at the
	// same (0,0,1) tier — absent: imported headings render the host site's uppercase/
	// capitalize + per-heading letter-spacing (measured 2026-08-16: 125/142 headings across
	// an 8-template benchmark wore the host's text-transform).
	{ sel: ':where(.entry-content,.wp-block-template-part) h6', decls: [ 'text-transform:revert', 'letter-spacing:revert' ] },
	// A16 button revert must cover the theme's `.wp-element-button` padding — absent: a
	// converted text link that never declared padding inflates 21px → 53px tall and
	// re-centers its grid row (consulting benchmark, 2026-08-16).
	{ sel: ':where(.wp-element-button,.wp-block-button__link)', decls: [ 'padding:revert' ] },
];

// The declaration body of the rule whose selector list contains `sel` as a WHOLE
// selector (the match must end at `{` or `,`) — from that rule's `{` to its `}`.
// Null when no rule carries the selector.
const ruleBody = ( css, sel ) => {
	let at = css.indexOf( sel );
	while ( at !== -1 ) {
		const end = css[ at + sel.length ];
		if ( end === '{' || end === ',' ) {
			const open = css.indexOf( '{', at );
			return css.slice( open + 1, css.indexOf( '}', open ) );
		}
		at = css.indexOf( sel, at + 1 );
	}
	return null;
};

const check = ( label, css, isBuild ) => {
	for ( const { sel, decls } of PINS ) {
		const body = ruleBody( css, canon( sel ) );
		if ( body === null ) {
			fail( `${label} missing rule: ${sel}${isBuild ? ' (rebuild imported-baseline)' : ''}` );
			continue;
		}
		for ( const d of decls ) {
			if ( ! body.includes( canon( d ) ) ) {
				fail( `${label} rule ${sel} missing "${d}"${isBuild ? ' (STALE build — rebuild imported-baseline)' : ''}` );
			}
		}
	}
};

const srcPath = resolve( plugin, 'src/styles/blocks/imported-baseline.scss' );
const buildPath = resolve( plugin, 'build/styles/blocks/imported-baseline.css' );
const src = read( srcPath );
const build = existsSync( buildPath ) ? read( buildPath ) : null;

check( 'src', src, false );
if ( build === null ) { fail( `build sheet absent: ${buildPath}` ); }
else { check( 'build', build, true ); }
if ( failures === 0 ) { ok( `all ${PINS.length} structural rules present in src + build` ); }

// ---------------------------------------------------------------------------
// 2. Coupling: A19 keys on SureForms's rendered DOM and state classes. If
//    SureForms renames one, or drops the filter the bridge answers, the rules
//    stop matching silently. Requires the SureForms plugin as a sibling
//    directory (`../sureforms`); fails LOUDLY rather than skipping. The email
//    wrappers are probed in the validator, which keys on the same class names
//    the markup assembles from the field slug.
// ---------------------------------------------------------------------------
const srfm = resolve( plugin, '../sureforms' );
const srfmProbes = [
	[ 'inc/form-styling.php', 'srfm_disable_default_styles', 'the filter the bridge answers to switch the skin off' ],
	[ 'inc/generate-form-markup.php', 'srfm-styling-none', 'the `.srfm-styling-none` marker every rule is gated on' ],
	[ 'inc/generate-form-markup.php', 'srfm-submit-container', 'the submit wrapper made transparent' ],
	[ 'inc/fields/input-markup.php', 'srfm-block-wrap', 'the control wrapper made transparent' ],
	[ 'inc/fields/number-markup.php', 'srfm-input-content', 'the number field\'s inner wrapper made transparent' ],
	[ 'inc/fields/multichoice-markup.php', 'srfm-vertical-layout', 'the vertical option-grid class' ],
	[ 'inc/fields/textarea-markup.php', 'srfm-char-counter-wrap', 'the counter that keeps a textarea\'s error wrap visible' ],
	[ 'assets/js/minified/validation.min.js', '.srfm-email-block', 'the email row\'s inner wrapper' ],
	[ 'assets/js/minified/validation.min.js', '.srfm-email-confirm-block', 'the confirm-email wrapper that carries its own error state' ],
	[ 'assets/js/minified/validation.min.js', 'srfm-valid-email-error', 'the email-format error state class' ],
	[ 'assets/js/minified/frontend.min.js', '"srfm-error"', 'the field error state class `toggleErrorState` sets' ],
	[ 'assets/js/minified/deps/tom-select.min.js', 'dropdown-active', 'the open-menu state class the dropdown z-index lift keys on' ],
];

if ( ! existsSync( srfm ) ) {
	fail( 'sibling plugin (sureforms) not found — coupling check NOT run' );
} else {
	for ( const [ rel, needle, why ] of srfmProbes ) {
		const p = resolve( srfm, rel );
		if ( ! existsSync( p ) ) { fail( `sureforms ${rel} not found — cannot verify ${why}` ); continue; }
		if ( ! readFileSync( p, 'utf8' ).includes( needle ) ) {
			fail( `sureforms ${rel} no longer emits "${needle}" — ${why} (A19 would stop matching)` );
		}
	}
	if ( failures === 0 ) { ok( `coupling intact: SureForms still emits the ${srfmProbes.length} anchors A19 keys on` ); }
}

process.exit( failures === 0 ? 0 : 1 );
