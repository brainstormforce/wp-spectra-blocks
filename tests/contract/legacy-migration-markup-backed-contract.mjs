// Contract test for the legacy migration's treatment of MARKUP-BACKED blocks.
// Run: `npm run test:contract` (plain node, no deps).
//
// Guards the fix for #908. `core/image` is the only block in scope whose `save()`
// serialises attributes into post markup (`utils/constants.js` SUPPORTED_BLOCKS;
// everything else is `spectra/*` and server-rendered). WordPress validates stored
// HTML against `save( attributes-after-filters )`, so rewriting one of those
// attributes during `blocks.getBlockAttributes` IS a validation failure — and on
// 7.1 the base layer is also what the front end renders, because
// `paints_core_image_dimensions()` is false there and the inline width stands.
//
// The rule: a markup-backed block keeps its BASE layer and its legacy store,
// and only its narrower viewport states are migrated - into core's NESTED
// `dimensions` shape. Three routes led into the original bug and they sit within
// a few lines of each other, so a fix for one naturally misses the others:
//
//   A  base bucket has no value, a narrower one does  -> root must NOT be cleared
//   B  base bucket holds a DIFFERENT value            -> root must NOT be replaced
//   C  root unset, `style` base holds the value       -> root must NOT be filled
//
// The store must also survive untouched. Below 7.1 it is what RENDERS — there
// `remove_core_image_inline_dimensions()` runs, the inline dimensions are
// stripped and the store paints them — so emptying it on 7.1 would silently
// change those sites on the next save and break downgrade safety.
//
// Route C belongs to `mirrorBaseValuesToAttributes`, guarded at its filter
// registration, so it is pinned as a source contract rather than behaviourally.
//
// The module is evaluated in a VM with its imports stubbed: node's ESM resolver
// cannot follow this repo's extensionless imports, and there is no JS unit
// harness to hang a normal test off. The logic under test is the real source.

import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const here = dirname( fileURLToPath( import.meta.url ) );
const plugin = resolve( here, '..', '..' );
const rc = resolve( plugin, 'src/extensions/responsive-controls' );

const failures = [];
const check = ( name, pass, detail = '' ) => {
	if ( ! pass ) {
		failures.push( `${ name }${ detail ? `\n    ${ detail }` : '' }` );
	}
};

// --- load the real module, imports stubbed ------------------------------------

const ALLOWED_PREFIXES = [ 'spectra/', 'spectra-pro/' ];

const stubs = {
	applyFilters: ( hook, value ) => value,
	BLOCK_RESPONSIVE_KEYS: {
		'core/image': [ 'width', 'height', 'aspectRatio', 'scale' ],
		'spectra/content': [ 'enableTextShadow', 'textShadowColor', 'textShadowBlur' ],
	},
	BUCKET_TOP_LEVEL_STYLE_KEYS: [ 'layout', 'fontSize', 'fontFamily', 'borderColor' ],
	ROOT_ATTRIBUTE_PRESET_REFS: {
		fontSize: { path: 'typography.fontSize', preset: 'font-size' },
		fontFamily: { path: 'typography.fontFamily', preset: 'font-family' },
		borderColor: { path: 'border.color', preset: 'color' },
	},
	STATE_KEYS: [ 'spacing', 'border', 'typography', 'shadow', 'layout', 'fontSize', 'fontFamily', 'borderColor' ],
	LEGACY_DEVICE_MAP: { lg: 'base', md: '@tablet', sm: '@mobile' },
	DEVICE_TO_STYLE_STATE: { 'base': '', '@tablet': '@tablet', '@mobile': '@mobile' },
	savesAttributesToMarkup: ( name ) =>
		! ALLOWED_PREFIXES.some( ( prefix ) => !! name && name.startsWith( prefix ) ),
};

const source = readFileSync( resolve( rc, 'legacy/migrate-legacy.js' ), 'utf8' );
const evaluatable = source
	.replace( /^import[\s\S]*?from\s+'[^']+';$/gm, '' )
	.replace( /^export const/gm, 'const' )
	.concat( '\n;globalThis.__migrate = migrateLegacyResponsiveStore;' );

const sandbox = { ...stubs, globalThis: {} };
sandbox.globalThis = sandbox;
runInNewContext( evaluatable, sandbox );
const migrate = sandbox.__migrate;

check( 'module evaluates and exports migrateLegacyResponsiveStore', typeof migrate === 'function' );

// --- A: a base bucket with no value must not clear the authored root ----------
{
	const before = {
		width: '792px',
		height: 'auto',
		responsiveControls: { lg: {}, md: { width: '400px' } },
	};
	const after = migrate( before, 'core/image' );

	check(
		'A: core/image keeps its authored root width',
		after.width === '792px',
		`expected '792px', got ${ JSON.stringify( after.width ) }`
	);
	check(
		'A: the legacy store survives untouched — below 7.1 it is what renders',
		after.responsiveControls?.md?.width === '400px',
		`expected the md bucket intact, got ${ JSON.stringify( after.responsiveControls ) }`
	);
	check(
		'A: the tablet value migrates into the NESTED dimensions shape',
		after.style?.[ '@tablet' ]?.dimensions?.width === '400px',
		`expected '400px' at style['@tablet'].dimensions.width, got ${ JSON.stringify( after.style?.[ '@tablet' ] ) }`
	);
	check(
		'A: and NOT into a flat state key, which core ignores for images',
		after.style?.[ '@tablet' ]?.width === undefined,
		`a flat style['@tablet'].width renders nothing on 7.1; got ${ JSON.stringify( after.style?.[ '@tablet' ]?.width ) }`
	);
	check(
		'A: mobile inherits the tablet value (legacy cascade)',
		after.style?.[ '@mobile' ]?.dimensions?.width === '400px',
		`expected the baked mobile value, got ${ JSON.stringify( after.style?.[ '@mobile' ] ) }`
	);
	check(
		'A: the base layer is still untouched',
		after.style?.dimensions === undefined && after.style?.width === undefined,
		`expected no base dimensions, got ${ JSON.stringify( after.style ) }`
	);
}

// --- B: a differing base bucket must not replace the authored root -----------
{
	const before = {
		width: '200px',
		responsiveControls: { lg: { width: '500px' }, md: { width: '300px' } },
	};
	const after = migrate( before, 'core/image' );

	check(
		'B: core/image keeps the root the markup carries',
		after.width === '200px',
		`expected '200px', got ${ JSON.stringify( after.width ) } — a silent resize`
	);
	check(
		'B: the base bucket is not lifted into the style root either',
		after.style?.width === undefined,
		`expected undefined, got ${ JSON.stringify( after.style?.width ) }`
	);
	check(
		'B: the lg bucket is not destroyed',
		after.responsiveControls?.lg?.width === '500px',
		`expected '500px' still in the store, got ${ JSON.stringify( after.responsiveControls ) }`
	);
	check(
		'B: the base layer is not written either',
		after.style?.dimensions === undefined && after.style?.width === undefined,
		`the root attribute stays authoritative; got ${ JSON.stringify( after.style ) }`
	);
	check(
		'B: the narrower bucket still migrates',
		after.style?.[ '@tablet' ]?.dimensions?.width === '300px',
		`expected '300px' at the tablet state, got ${ JSON.stringify( after.style?.[ '@tablet' ] ) }`
	);
}

// --- the server-rendered path must be unchanged -------------------------------
{
	const before = {
		enableTextShadow: true,
		responsiveControls: { lg: { enableTextShadow: false }, md: { enableTextShadow: true } },
	};
	const after = migrate( before, 'spectra/content' );

	check(
		'spectra/*: the base bucket still wins at the root, as before',
		after.enableTextShadow === false,
		`expected false, got ${ JSON.stringify( after.enableTextShadow ) }`
	);
}

// --- idempotence --------------------------------------------------------------
{
	const once = migrate( { width: '200px', responsiveControls: { md: { width: '300px' } } }, 'core/image' );
	const twice = migrate( once, 'core/image' );

	check( 'second pass is a no-op on width', twice.width === '200px' );
	check(
		'second pass leaves the store alone',
		twice.responsiveControls?.md?.width === '300px'
	);
	check(
		'second pass leaves the migrated state alone',
		twice.style?.[ '@tablet' ]?.dimensions?.width === '300px'
	);
}

// --- C: the mirror filter stays guarded (source contract) ---------------------
{
	const entry = readFileSync( resolve( rc, 'index.js' ), 'utf8' );
	const block = entry.slice( entry.indexOf( 'mirror-base-values' ) );

	check(
		'C: mirror-base-values is gated on savesAttributesToMarkup',
		/savesAttributesToMarkup\(/.test( block.slice( 0, 900 ) ),
		'the guard that stops the mirror filling a root attribute the markup never had is gone'
	);
}

// --- report -------------------------------------------------------------------

if ( failures.length ) {
	console.error( `\n✗ legacy-migration-markup-backed-contract: ${ failures.length } failure(s)\n` );
	failures.forEach( ( f ) => console.error( `  - ${ f }` ) );
	process.exit( 1 );
}

console.log( '✓ legacy-migration-markup-backed-contract: all checks passed' );
