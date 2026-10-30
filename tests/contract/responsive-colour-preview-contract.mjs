// Contract test for the per-device EDITOR preview of every banded COLOUR.
// Run: `npm run test:contract` (plain node, no deps).
//
// The front end is covered by PHPUnit — `ColourPerBandTest` here and
// `Test_Colour_Per_Band` in Pro, both data-driven from the registrations. The
// editor canvas is not, and it fails in a way no PHP assertion can see: the
// PHP tests never load `render.js`, and there is no editor fixture.
//
// THE BUG THIS EXISTS FOR. `getResponsivePreviewCss()` re-runs the block's own
// producers over each band's attributes. A producer only bands a value if it
// DERIVES it from the `attrs` it is handed. Several blocks build their
// `useSpectraStyles` config with the value captured from the enclosing scope:
//
//     { key: 'textColor', value: textColor }     // captured — every band = base
//     { key: 'textColor' }                       // read from attrs — bands work
//
// The first shape looks correct, paints the base correctly, passes every PHP
// test, and silently previews the desktop colour at every breakpoint. Three
// blocks in B3b had it. Nothing else catches it.
//
// So: for every block registered with `format_colour`, this asserts the editor
// can actually derive that attribute per band — either through
// `colourVarStyles`, or through a config entry that reads from `attrs`.
//
// Registrations are parsed out of the PHP rather than duplicated here. A list
// in this file would be one more thing to keep in sync, and would pass happily
// while the real one drifted.

import { readFileSync, existsSync, readdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname( fileURLToPath( import.meta.url ) );
const free = resolve( here, '..', '..' );
const pro = resolve( free, '..', 'spectra-blocks-pro' );

const failures = [];
const check = ( name, pass, detail = '' ) => {
	if ( ! pass ) {
		failures.push( `${ name }${ detail ? `\n    ${ detail }` : '' }` );
	}
};

/**
 * Every `block => [ attribute ]` pair registered with the colour formatter.
 *
 * @param {string} php Source of a file holding a block => attributes array.
 * @return {Object} Block name to attribute names.
 */
const colourRegistrations = ( php ) => {
	const out = {};
	// Each block key, then every attribute in its body that names the formatter.
	const blockRe = /'((?:spectra|spectra-pro)\/[a-z0-9-]+)'\s*=>\s*array\(/g;
	let m;
	while ( ( m = blockRe.exec( php ) ) ) {
		// Walk to the matching close paren so nested arrays do not end it early.
		let i = blockRe.lastIndex;
		let depth = 1;
		while ( i < php.length && depth > 0 ) {
			if ( php[ i ] === '(' ) {
				depth++;
			} else if ( php[ i ] === ')' ) {
				depth--;
			}
			i++;
		}
		const body = php.slice( blockRe.lastIndex, i );
		const attrRe = /'([A-Za-z][A-Za-z0-9]*)'\s*=>\s*array\(([^()]*)\)/g;
		let a;
		while ( ( a = attrRe.exec( body ) ) ) {
			if ( a[ 2 ].includes( "'format_colour'" ) ) {
				( out[ m[ 1 ] ] ||= [] ).push( a[ 1 ] );
			}
		}
	}
	return out;
};

/**
 * The DIRECTORY a block lives in, which is not always its name.
 *
 * Six blocks register a name that differs from their folder — the modal family
 * (`spectra/modal-child-button` lives in `modal-child-trigger-button/`) and
 * `spectra-pro/register-user-name` in `register-username/`. Splitting the block
 * name on `/` and using the second half therefore looks in a folder that does
 * not exist, and reports a wired block as unwired.
 *
 * @param {string} root      Plugin root.
 * @param {string} blockName Registered block name.
 * @return {string} The folder name.
 */
const blockDir = ( root, blockName ) => {
	const guess = blockName.split( '/' )[ 1 ];
	if ( existsSync( resolve( root, 'src/blocks', guess, 'block.json' ) ) ) {
		const declared = JSON.parse(
			readFileSync( resolve( root, 'src/blocks', guess, 'block.json' ), 'utf8' )
		).name;
		if ( declared === blockName ) {
			return guess;
		}
	}
	for ( const entry of readdirSync( resolve( root, 'src/blocks' ), { withFileTypes: true } ) ) {
		if ( ! entry.isDirectory() ) {
			continue;
		}
		const meta = resolve( root, 'src/blocks', entry.name, 'block.json' );
		if ( ! existsSync( meta ) ) {
			continue;
		}
		try {
			if ( JSON.parse( readFileSync( meta, 'utf8' ) ).name === blockName ) {
				return entry.name;
			}
		} catch ( e ) {
			// A block.json that does not parse cannot be the one we want.
		}
	}
	return guess;
};

const sources = [
	[ free, resolve( free, 'includes/Extensions/ResponsiveControls/class-responsive-attribute-css.php' ) ],
	[ pro, resolve( pro, 'includes/Extensions/ResponsiveControls.php' ) ],
];

let checked = 0;

for ( const [ root, phpPath ] of sources ) {
	if ( ! existsSync( phpPath ) ) {
		// Pro is a sibling checkout and is absent from the free plugin's CI.
		check( `${ phpPath } present`, root === pro, `missing ${ phpPath }` );
		continue;
	}

	const registrations = colourRegistrations( readFileSync( phpPath, 'utf8' ) );

	for ( const [ blockName, attributes ] of Object.entries( registrations ) ) {
		const renderPath = resolve( root, 'src/blocks', blockDir( root, blockName ), 'render.js' );

		if ( ! existsSync( renderPath ) ) {
			check( `${ blockName }: render.js present`, false, `missing ${ renderPath }` );
			continue;
		}

		const src = readFileSync( renderPath, 'utf8' );

		check(
			`${ blockName }: bands in the editor at all`,
			src.includes( 'getResponsivePreviewCss' ) && src.includes( `blockName: '${ blockName }'` ),
			'the front end bands this colour but the canvas shows one value at every breakpoint'
		);

		check(
			`${ blockName }: renders the band <style>`,
			/\{\s*responsivePreviewCss\s*&&\s*<style>\{\s*responsivePreviewCss\s*\}<\/style>\s*\}/.test( src ),
			'the CSS is computed but never reaches the document'
		);

		for ( const attribute of attributes ) {
			checked++;

			// Derivable through the shared helper …
			const viaHelper = new RegExp(
				`colourVarStyles\\([^)]*\\b${ attribute }\\s*:`
			).test( src.replace( /\s+/g, ' ' ) );

			// … or through a config entry that READS FROM ATTRS rather than
			// capturing the value, paired with an attrs-driven producer.
			const entry = new RegExp(
				`key:\\s*'${ attribute }'\\s*(,\\s*(?!value\\s*:)[^}]*)?\\}`
			).test( src );
			const attrsDriven = /buildSpectraStyles\(\s*attrs\b/.test( src );

			check(
				`${ blockName }: ${ attribute } is derivable per band in the editor`,
				viaHelper || ( entry && attrsDriven ),
				'its producer would return the BASE value for every band — add it to a ' +
					'colourVarStyles() map, or drop the captured `value:` from its config entry'
			);
		}
	}
}

check( 'some colour registrations were found', checked > 0, 'the parser matched nothing — it has drifted from the PHP' );

if ( failures.length ) {
	console.error( `FAIL  responsive colour preview contract\n\n  - ${ failures.join( '\n  - ' ) }\n` );
	process.exit( 1 );
}

console.log( `ok    responsive colour preview wired for ${ checked } banded colours` );
