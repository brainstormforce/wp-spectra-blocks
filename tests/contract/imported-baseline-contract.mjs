// Contract smoke test for the imported-baseline sheet (the ERA import render
// contract). Run: `npm run test:contract` (plain node, no deps).
//
// Since PR #2925 every imported SureForms form is created with its own styling
// DISABLED (the importer sets `disable_default_styles`; SureForms then drops its
// four frontend stylesheets + the derived CSS-var block and stamps
// `.srfm-styling-none` on the container). So this file no longer pins skin VARS —
// it provides the constant LAYOUT SKELETON the dropped stylesheets used to carry
// (the flex track, the width presets, the box model), gated on `.srfm-styling-none`.
// The per-form APPEARANCE is painted directly onto the `.srfm-*` elements by the
// block-converter overlay (`formOverlay.ts`).
//
// Guards the whack-a-mole classes that stay invisible to the geometry sweep:
//   1. A skeleton clause gets deleted/renamed in src.
//   2. build/ goes stale vs src (served CSS != checkout).
//   3. The COUPLING breaks — SureForms renames the `.srfm-styling-none` marker or
//      the `.srfm-block-width-*` classes our skeleton keys on, or drops the
//      `disableDefaultStyles` ability the importer relies on — so the skeleton's
//      selectors silently stop matching the rendered DOM.
//
// Failure here means the IMPORT RENDER CONTRACT is broken even if every unit test
// in block-converter and every corpus geometry sweep is green.

import { readFileSync, existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname( fileURLToPath( import.meta.url ) );
const plugin = resolve( here, '..', '..' );

// Canonical form: strip comments, then whitespace around punctuation, so a pin
// matches both the pretty SCSS src and the minified build (value-internal spaces,
// e.g. `calc(50% - 8px)` and descendant combinators, are kept as single spaces).
const canon = ( s ) =>
	s
		.replace( /\/\/[^\n]*/g, '' )
		.replace( /\/\*[\s\S]*?\*\//g, '' )
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
// 1. Skeleton clauses that MUST exist in src AND build (the de-skinned layout
//    the dropped SureForms stylesheets used to provide). Each documents the
//    defect its absence reproduces. Checked as { selector, decls } — the rule
//    BODY is extracted, then each decl looked up inside it, so the check is
//    immune to the minifier's declaration reordering / vendor-prefixing /
//    selector-list merging.
// ---------------------------------------------------------------------------
const PINS = [
	// Field track — absent: fields don't lay out as a row; paired fields never sit side by side.
	{ sel: ':where(.srfm-styling-none .srfm-form)', decls: [ 'display:flex', 'flex-wrap:wrap', 'row-gap:16px', 'column-gap:16px' ] },
	// box-sizing — absent: padded fields overflow their flex column.
	{ sel: ':where(.srfm-styling-none *)', decls: [ 'box-sizing:border-box' ] },
	// Input fills its block — absent: inputs shrink to content width.
	{ sel: ':where(.srfm-styling-none .srfm-input-common:not(.srfm-input-checkbox):not(.srfm-input-gdpr))', decls: [ 'width:100%', 'max-width:100%' ] },
	// Width presets (committer patches the `fieldWidth` attr → these classes) — absent: row pairing dead.
	// The gap is subtracted via `--srfm-column-gap-between-blocks` (set by the overlay to the
	// source form's actual gap) so columns stay exact; checked by substring to survive minifier
	// spacing of the calc().
	{ sel: ':where(.srfm-styling-none .srfm-block-width-50)', decls: [ 'calc(50%', 'var(--srfm-column-gap-between-blocks' ] },
	{ sel: ':where(.srfm-styling-none .srfm-block-width-33-33)', decls: [ 'calc(33.33%', 'var(--srfm-column-gap-between-blocks' ] },
	{ sel: ':where(.srfm-styling-none .srfm-block-width-100)', decls: [ 'flex:0 1 100%' ] },
	// Flex items default to min-width:auto — absent: a focused dropdown with a long value
	// grows past its width preset and wraps to its own row, breaking the paired layout.
	{ sel: ':where(.srfm-styling-none .srfm-block)', decls: [ 'min-width:0' ] },
	// Submit container full-width — absent: the `justify` full-width submit resolves to content width.
	{ sel: ':where(.srfm-styling-none .srfm-submit-container)', decls: [ 'width:100%' ] },
	// Required asterisk suppressed — absent: a `*` the source (novalidate) never showed.
	{ sel: ':where(.srfm-styling-none .srfm-required)', decls: [ 'display:none' ] },
	// Validation messages hidden until error — absent: every required field shows
	// "This field is required." on first paint (the hiding lived in the dropped skin).
	{ sel: ':where(.srfm-styling-none .srfm-error-message)', decls: [ 'display:none' ] },
	// Other always-in-markup, hidden-by-default SureForms elements — absent: they paint
	// on page load. The success box is the worst (a post-submit confirmation on load).
	{ sel: ':where(.srfm-styling-none .srfm-success-box)', decls: [ 'display:none' ] },
	// …but the `.srfm-active` reveal MUST exist too, or a successful submit hides the
	// form and shows nothing (empty card). SureForms' JS adds `.srfm-active` on submit.
	{ sel: ':where(.srfm-styling-none .srfm-success-box.srfm-active)', decls: [ 'display:flex' ] },
	{ sel: ':where(.srfm-styling-none .srfm-loader)', decls: [ 'display:none' ] },
	{ sel: ':where(.srfm-styling-none .srfm-hidden-block)', decls: [ 'display:none' ] },
	// Symbol-holder <svg> collapse — absent: the offscreen <symbol> svg renders at the
	// UA 300×150 default and injects a huge empty gap into every checkbox/gdpr field.
	{ sel: ':where(.srfm-styling-none .srfm-inline-svg)', decls: [ 'position:absolute', 'width:0', 'height:0' ] },
	// Checkbox/gdpr box layout + the drawn box — absent: no visible checkbox (the real
	// input is screen-reader-text; the box + layout lived in the dropped skin).
	{ sel: ':where(.srfm-styling-none .srfm-cbx)', decls: [ 'display:flex' ] },
	{ sel: ':where(.srfm-styling-none .srfm-cbx .srfm-span-wrap:first-child)', decls: [ 'width:18px', 'border-radius:4px' ] },
	// Hide the REAL checkbox/gdpr input (we draw our own box) — absent: a checked native
	// checkbox paints a stray second box at the top-left (WP-core screen-reader-text is
	// not reliably present on imported pages).
	{ sel: ':where(.srfm-styling-none .srfm-input-checkbox)', decls: [ 'position:absolute', 'overflow:hidden' ] },
	// Multi-choice as a native list — absent: radio/checkbox groups collapse (the card
	// layout is gone) and SureForms's now-static SVG indicators show alongside the input.
	{ sel: ':where(.srfm-styling-none .srfm-multi-choice-block .srfm-block-wrap)', decls: [ 'display:flex', 'flex-wrap:wrap' ] },
	{ sel: ':where(.srfm-styling-none .srfm-multi-choice-single .srfm-icon-container)', decls: [ 'display:none' ] },
	// Dropdown (TomSelect single-select) internals — absent: a selected dropdown shows a
	// stray remove "×" and the search-input cursor overlapping the value.
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common.single .item .remove)', decls: [ 'display:none' ] },
	// Search input collapses ONLY once a value is selected (item then supplies the line
	// box); empty keeps it so the placeholder shows + the height matches text inputs.
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common.single.has-items .ts-control input[id^=tomselect-])', decls: [ 'height:0' ] },
	// Right-side clear "×" revealed on selection (native SureForms), left of the caret.
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common.has-items .clear-button)', decls: [ 'visibility:visible' ] },
	// …and pinned to the RIGHT — absent: TomSelect's `--ts-pr-caret` puts the "×" on the
	// LEFT, overlapping the selected value.
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common .clear-button)', decls: [ 'right:2.25em', 'left:auto' ] },
	// Dropdown stays ONE line at the input height — absent: the caret wraps to a 2nd
	// line once the value is long, making the box taller than its sibling fields.
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common .ts-control)', decls: [ 'flex-wrap:nowrap', 'align-items:center' ] },
	// TomSelect's input defaults to 13px/normal — inherit font-size+line-height so a
	// dropdown's box matches the sibling text inputs' height (else it renders shorter).
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common .ts-control input)', decls: [ 'font-size:inherit' ] },
	{ sel: ':where(.srfm-styling-none .srfm-dropdown-common .ts-dropdown-icon)', decls: [ 'position:absolute' ] },
];

// The declaration body of the rule whose selector list contains `sel` — from the
// first `{` after `sel` to its matching `}`. Null when the selector is absent.
const ruleBody = ( css, sel ) => {
	const at = css.indexOf( sel );
	if ( at === -1 ) { return null; }
	const open = css.indexOf( '{', at );
	const close = css.indexOf( '}', open );
	return open === -1 || close === -1 ? null : css.slice( open + 1, close );
};

const check = ( label, css, isBuild ) => {
	for ( const { sel, decls } of PINS ) {
		const body = ruleBody( css, sel );
		if ( body === null ) {
			fail( `${label} missing skeleton rule: ${sel}${isBuild ? ' (rebuild imported-baseline)' : ''}` );
			continue;
		}
		for ( const d of decls ) {
			if ( ! body.includes( d ) ) {
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
if ( failures === 0 ) { ok( `all ${PINS.length} skeleton clauses present in src + build` ); }

// ---------------------------------------------------------------------------
// 2. Coupling: the skeleton keys on SureForms's rendered DOM. If SureForms
//    renames the marker / width classes, or drops the ability the importer sets,
//    the skeleton stops matching silently. Requires the full playground checkout;
//    fails LOUDLY rather than skipping.
// ---------------------------------------------------------------------------
const srfm = resolve( plugin, '../sureforms' );
const srfmProbes = [
	[ 'inc/generate-form-markup.php', 'srfm-styling-none', 'the `.srfm-styling-none` marker our skeleton is gated on' ],
	[ 'inc/fields/base.php', 'srfm-block-width-', 'the `.srfm-block-width-*` classes our width presets key on' ],
	[ 'inc/abilities/forms/create-form.php', 'disableDefaultStyles', 'the `disableDefaultStyles` ability the importer sets to disable the skin' ],
];

if ( ! existsSync( srfm ) ) {
	fail( 'sibling plugin (sureforms) not found — coupling check NOT run' );
} else {
	for ( const [ rel, needle, why ] of srfmProbes ) {
		const p = resolve( srfm, rel );
		if ( ! existsSync( p ) ) { fail( `sureforms ${rel} not found — cannot verify ${why}` ); continue; }
		if ( ! readFileSync( p, 'utf8' ).includes( needle ) ) {
			fail( `sureforms ${rel} no longer emits "${needle}" — ${why} (skeleton would stop matching)` );
		}
	}
	if ( failures === 0 ) { ok( `coupling intact: SureForms still emits the marker, width classes + disable ability` ); }
}

process.exit( failures === 0 ? 0 : 1 );
