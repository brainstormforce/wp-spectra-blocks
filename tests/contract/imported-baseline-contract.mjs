// Contract smoke test for the imported-baseline sheet (the ERA import
// render contract). Run: `npm run test:contract` (plain node, no deps).
//
// Guards three whack-a-mole classes measured live on 2026-07-03 — all were
// silent cross-file var-coupling breaks invisible to the geometry sweep:
//   1. A contract clause gets deleted/renamed in src (gray-skin class).
//   2. build/ goes stale vs src (served CSS ≠ checkout — D10 class).
//   3. A pinned var loses its CONSUMER (pro skin / SureForms rename their
//      token, the pin becomes a no-op — focus-ring class), or the pro skin
//      grows a new var channel nothing defines.
//
// Failure here means the IMPORT RENDER CONTRACT is broken even if every
// unit test in block-converter and every corpus geometry sweep is green.

import { readFileSync, existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname( fileURLToPath( import.meta.url ) );
const plugin = resolve( here, '..', '..' );
const norm = ( s ) => s.replace( /\s+/g, ' ' );
const read = ( p ) => norm( readFileSync( p, 'utf8' ) );

let failures = 0;
const fail = ( msg ) => { failures++; console.error( `FAIL  ${msg}` ); };
const ok = ( msg ) => console.log( `ok    ${msg}` );

// ---------------------------------------------------------------------------
// 1. Contract declarations that MUST exist in src AND build (exact pins).
//    Every entry documents a measured defect its absence reproduces.
// ---------------------------------------------------------------------------
const PINS = [
  // A19 base field skin — absent: srfm derived gray skin (rgb(241,241,240))
  ['--srfm-color-input-background: Field'],
  ['--srfm-color-input-text: FieldText'],
  ['--spectra-input-bg: Field'],
  ['--spectra-input-text: FieldText'],
  // A19 dropdown menu — absent: near-black menu, unreadable options
  ['--srfm-expandable-menu-background: Field'],
  ['--srfm-dropdown-option-text-color: FieldText'],
  ['--srfm-dropdown-option-selected-text: FieldText'],
  ['--srfm-dropdown-placeholder-color: GrayText'],
  ['--srfm-dropdown-icon-color: GrayText'],
  ['--srfm-dropdown-menu-border-color: color-mix(in srgb, FieldText 18%, Field)'],
  ['--srfm-dropdown-option-background-hover: color-mix(in srgb, FieldText 8%, Field)'],
  ['--srfm-dropdown-option-background-selected: color-mix(in srgb, FieldText 12%, Field)'],
  ['--srfm-dropdown-option-selected-icon: FieldText'],
  // A19 focus ring — absent: pro skin ring reads currentColor → "doubled
  // black border" on focus instead of the platform ring the source shows
  ['--spectra-focus-ring-style: auto'],
  ['--spectra-focus-ring-width: 1px'],
  ['--spectra-focus-ring-color: -webkit-focus-ring-color'],
  ['--spectra-focus-ring-offset: 0px'],
  // A19 focus glow — absent: 3px srfm-scheme halo on checkbox/multi-choice/
  // number/richtext focus (no silent source renders a focus halo)
  ['--srfm-color-input-border-focus-glow: transparent'],
  // A19 selected/label/prefix
  ['--srfm-color-input-selected: color-mix(in srgb, FieldText 12%, Field)'],
  ['--srfm-color-input-label: currentColor'],
  ['--srfm-color-input-prefix: GrayText'],
].map( ( [d] ) => d );

const srcPath = resolve( plugin, 'src/styles/blocks/imported-baseline.scss' );
const buildPath = resolve( plugin, 'build/styles/blocks/imported-baseline.css' );
const src = read( srcPath );
const build = existsSync( buildPath ) ? read( buildPath ) : null;

for ( const pin of PINS ) {
  if ( !src.includes( pin ) ) {fail( `src missing contract pin: ${pin}` );}
  else if ( build !== null && !build.includes( pin ) ) {fail( `build STALE — missing: ${pin} (rebuild imported-baseline)` );}
}
if ( build === null ) {fail( `build sheet absent: ${buildPath}` );}
if ( !src.includes( ':where(.srfm-required)' ) ) {fail( 'src missing the required-asterisk suppression clause' );}
if ( failures === 0 ) {ok( `all ${PINS.length} contract pins present in src + build` );}

// ---------------------------------------------------------------------------
// 2. Coupling: every pinned var must still have a CONSUMER. A pin whose
//    consumer renamed its token is a silent no-op — exactly the class the
//    geometry sweep cannot see. Consumers live in sibling plugins, so this
//    section requires the full playground checkout; it fails LOUDLY rather
//    than skipping silently.
// ---------------------------------------------------------------------------
const proTokens = resolve( plugin, '../spectra-blocks-pro/assets/css/component-tokens.css' );
const srfmDir = resolve( plugin, '../sureforms/assets/css/minified' );
const srfmSheets = ['blocks/default/frontend.min.css', 'frontend/form.min.css', 'common.min.css']
  .map( ( f ) => resolve( srfmDir, f ) );

if ( !existsSync( proTokens ) || !srfmSheets.every( existsSync ) ) {
  fail( 'sibling plugins (spectra-blocks-pro / sureforms) not found — coupling check NOT run' );
} else {
  const consumers = [read( proTokens ), ...srfmSheets.map( read )].join( '\n' );
  const pinnedNames = PINS.map( ( p ) => p.split( ':' )[0].trim() );
  for ( const name of pinnedNames ) {
    if ( !consumers.includes( `var(${name}` ) ) {fail( `pinned var has NO consumer anymore (rename upstream?): ${name}` );}
  }

  // Reverse: every --spectra-* var the pro skin's SureForms section consumes
  // must be defined by the contract OR emitted per-form by the overlay
  // (block-converter formOverlay.ts — keep this list in sync with its pins).
  const OVERLAY_EMITTED = [
    '--spectra-input-bg', '--spectra-input-text', '--spectra-input-placeholder',
    '--spectra-input-border-color', '--spectra-input-border-width', '--spectra-input-border-style',
    '--spectra-input-focus-border', '--spectra-input-focus-shadow',
  ];
  const pro = read( proTokens );
  const consumed = [...new Set( [...pro.matchAll( /var\((--spectra-(?:input|focus-ring)-[a-z-]+)/g )].map( ( m ) => m[1] ) )];
  for ( const name of consumed ) {
    const defined = pinnedNames.includes( name ) || OVERLAY_EMITTED.includes( name )
      || pro.includes( `${name}:` ); // pro defines its own defaults for some
    if ( !defined ) {fail( `pro skin consumes ${name} but nothing defines it (new channel? add a pin or overlay emission)` );}
  }
  if ( failures === 0 ) {ok( `coupling intact: ${pinnedNames.length} pins consumed, ${consumed.length} pro channels defined` );}
}

process.exit( failures === 0 ? 0 : 1 );
