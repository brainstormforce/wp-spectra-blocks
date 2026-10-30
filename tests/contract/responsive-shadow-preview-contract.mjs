// Contract test: the editor canvas must show a per-device shadow.
// Run: `npm run test:contract` (plain node, no deps).
//
// Box Shadow and Shadow Hover are per-device. The front end is handled by
// `ResponsiveAttributeCSS::format_shadow()`; the editor CANVAS is a separate
// path — `getResponsivePreviewCss()` re-runs each block's own producers against
// a band's merge of the attributes and emits only what DIFFERS from base.
//
// The trap this guards is specific and it already happened once. Both blocks
// pass `( attrs ) => buildSpectraStyles( attrs, config ).style` as a producer,
// which looks like it derives from the band — but `config` is built once from
// the block's attributes and bakes an explicit `value`, and in
// `hooks/index.js` an explicit `value` WINS over the attribute:
//
//     const finalValue = value !== undefined ? value : attrValue;
//
// So every band re-derived the base, compared equal to it, and emitted nothing:
// the shadow rendered correctly on the site and never changed in the editor.
//
// The fix is a dedicated producer per block that reads the band's own `attrs`.
// This pins that as a source contract — it cannot be verified behaviourally
// here, because the producers import WordPress packages this runner has no way
// to resolve.

import { readFileSync } from 'node:fs';

let failures = 0;

const check = ( label, condition ) => {
	if ( condition ) {
		console.log( `ok    ${ label }` );
	} else {
		console.log( `FAIL  ${ label }` );
		failures++;
	}
};

const cases = [
	{
		file: 'src/blocks/button/render.js',
		producer: 'getShadowHoverStyles',
		reads: 'attrs?.shadowHover',
	},
	{
		file: 'src/blocks/container/render.js',
		producer: 'getBoxShadowStyles',
		reads: 'attrs?.boxShadow',
	},
];

for ( const { file, producer, reads } of cases ) {
	const src = readFileSync( new URL( `../../${ file }`, import.meta.url ), 'utf8' );

	check(
		`${ file }: defines ${ producer }()`,
		new RegExp( `export const ${ producer }\\s*=\\s*\\(\\s*attrs\\s*\\)` ).test( src )
	);

	check(
		`${ file }: ${ producer }() derives from the band's own attrs, not a captured config`,
		src.includes( reads )
	);

	// The producer has to be REGISTERED, or it never runs.
	const producersBlock = src.slice( src.indexOf( 'producers:' ) );
	check(
		`${ file }: ${ producer } is registered in the producers array`,
		producersBlock.slice( 0, 400 ).includes( producer )
	);
}

// The front end emits `box-shadow` directly rather than a custom property,
// because the hook class the old rule needed is no longer emitted once the
// attribute is responsive. The canvas must match, or the two disagree.
for ( const { file } of cases ) {
	const src = readFileSync( new URL( `../../${ file }`, import.meta.url ), 'utf8' );
	const producerSrc = src.slice( src.indexOf( 'export const get' ), src.indexOf( 'producers:' ) );
	check(
		`${ file }: the preview emits boxShadow, matching format_shadow()`,
		producerSrc.includes( 'boxShadow:' )
	);
}

console.log(
	failures
		? `\n${ failures } contract failure(s)`
		: '\nresponsive-shadow-preview-contract: all checks passed'
);

process.exit( failures ? 1 : 0 );
