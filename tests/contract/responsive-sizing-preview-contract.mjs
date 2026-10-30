// Contract test for the per-device EDITOR preview of the sizing attributes.
// Run: `npm run test:contract` (plain node, no deps).
//
// The front end is covered by PHPUnit (`SizingPerBandTest`, and Pro's
// `Test_Mega_Menu_Icon_Size_Per_Band`). The editor canvas is not, and it fails
// in a way no PHP assertion can see.
//
// Each of these four attributes used to be painted inline by the block's own
// `render.js`, and an inline declaration outranks a stylesheet rule of any
// specificity. So the bands `getResponsivePreviewCss()` emits only win while
// three things hold together:
//
//   1. the block CALLS `getResponsivePreviewCss()` and renders the `<style>`,
//   2. it passes a producer that covers the sizing keys, and
//   3. the base inline paint comes from that SAME producer.
//
// Break (3) alone — paint base one way, derive bands another — and the canvas
// disagrees with itself at exactly one breakpoint, which is the kind of thing
// that ships. Nothing else in the suite notices, because the PHP tests never
// load `render.js` and the editor has no fixture.
//
// Pinned as a source contract for the same reason the sibling contracts are:
// node's ESM resolver cannot follow this repo's extensionless, aliased imports,
// and there is no JS unit harness to hang a real test off.

import { readFileSync, existsSync } from 'node:fs';
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

// [ label, render.js, block name, producer, the vars/properties it must carry ]
const BLOCKS = [
	[
		'counter',
		resolve( free, 'src/blocks/counter/render.js' ),
		'spectra/counter',
		'getBarSizeStyles',
		[ '--spectra-counter-bar-height', '--spectra-counter-bar-border-radius' ],
	],
	[
		'counter-child-progress-bar',
		resolve( free, 'src/blocks/counter-child-progress-bar/render.js' ),
		'spectra/counter-child-progress-bar',
		'getBarSizeStyles',
		[ '--spectra-counter-bar-height', '--spectra-counter-bar-border-radius' ],
	],
	[
		'popup-builder',
		resolve( free, 'src/blocks/popup-builder/render.js' ),
		'spectra/popup-builder',
		'getCloseIconSizeStyles',
		[ '--spectra-close-icon-size' ],
	],
	[
		'mega-menu (pro)',
		resolve( pro, 'src/blocks/mega-menu/render.js' ),
		'spectra-pro/mega-menu',
		'getIconSizeStyles',
		[ 'iconDimension' ],
	],
];

for ( const [ label, file, blockName, producer, carries ] of BLOCKS ) {
	if ( ! existsSync( file ) ) {
		// Pro is a sibling checkout and is not present in the free plugin's CI.
		check( `${ label }: render.js present`, label.includes( 'pro' ), `missing ${ file }` );
		continue;
	}

	const src = readFileSync( file, 'utf8' );

	check(
		`${ label }: calls getResponsivePreviewCss for ${ blockName }`,
		src.includes( 'getResponsivePreviewCss' ) && src.includes( `blockName: '${ blockName }'` ),
		'the canvas has no per-device preview without it'
	);

	check(
		`${ label }: renders the band <style>`,
		/\{\s*responsivePreviewCss\s*&&\s*<style>\{\s*responsivePreviewCss\s*\}<\/style>\s*\}/.test( src ),
		'the CSS is computed but never reaches the document'
	);

	check(
		`${ label }: registers ${ producer } as a producer`,
		new RegExp( `producers:\\s*\\[[^\\]]*${ producer }` ).test( src ),
		'the bands cannot derive the sizing without it'
	);

	// The whole point of a named producer: base and bands share one derivation.
	// One occurrence is the definition, so anything under three means the base
	// paint or the producer list has stopped using it.
	const uses = ( src.match( new RegExp( producer, 'g' ) ) || [] ).length;
	check(
		`${ label }: ${ producer } feeds both the inline base paint and the bands`,
		uses >= 3,
		`found ${ uses } references; expected the definition, the producer list and the inline paint`
	);

	for ( const token of carries ) {
		check(
			`${ label }: ${ producer } still carries ${ token }`,
			src.includes( token ),
			'the attribute is registered as responsive but nothing previews it'
		);
	}

	// Trap: re-adding a literal inline paint silently re-breaks every band.
	for ( const token of carries.filter( ( t ) => t.startsWith( '--' ) ) ) {
		const literal = new RegExp( `'${ token }'\\s*:\\s*(?!.*${ producer })`, 'g' );
		const hits = ( src.match( literal ) || [] ).filter(
			( line ) => ! line.includes( producer )
		);
		check(
			`${ label }: ${ token } is not painted outside ${ producer }`,
			hits.length <= 1,
			`${ hits.length } literal assignments; base and bands must share one derivation`
		);
	}
}

if ( failures.length ) {
	console.error( `FAIL  responsive sizing preview contract\n\n  - ${ failures.join( '\n  - ' ) }\n` );
	process.exit( 1 );
}

console.log( `ok    responsive sizing preview wired for ${ BLOCKS.length } blocks` );
