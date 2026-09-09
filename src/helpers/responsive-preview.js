/**
 * Per-device preview CSS for the editor canvas.
 *
 * WHY THIS EXISTS
 *
 * Most of Spectra's responsive values are painted from a block's ATTRIBUTES —
 * `useSpectraStyles()` turns them into CSS custom properties which the block
 * applies inline. Nothing else paints them: the generator that produces banded
 * CSS runs on `render_block`, so it only ever emits for the front end, and
 * core's own state CSS covers only the properties core owns (typography,
 * spacing, border, shadow, layout).
 *
 * That was invisible while the editor's attributes followed the previewed
 * device. It stopped being invisible once the write router began routing by
 * device AND core's "Responsive styles" mode: with the mode off an edit belongs
 * to the base layer, so the attributes hold base and every device painted base.
 * The panel wants base — it is what an edit changes — and the canvas wants the
 * device's value. One attributes object cannot answer both, so the canvas is
 * served from CSS.
 *
 * A survey of `BLOCK_RESPONSIVE_KEYS` against each block's `render.js` found 80
 * of 88 responsive flat keys, across 19 blocks, in that position — container
 * sizes and overlays, slider and post metrics, separator dimensions, icon sizes,
 * text shadow, backgrounds. Hence one mechanism here rather than a per-property
 * fix repeated 80 times.
 *
 * HOW
 *
 * The same producers the block already paints with are run again per band, over
 * attributes whose flat keys come from that band's state. Only declarations that
 * DIFFER from base are emitted, so a band inherits by omission exactly as the
 * cascade intends.
 *
 * Reusing the block's own producers is the whole point: the front end already
 * has its own attribute-to-CSS map in `ResponsiveAttributeCSS`, with sixteen
 * formatters. A second map in JS would be a standing invitation for the editor
 * to disagree with the site, so this derives the bands from the SAME function
 * that derives the base.
 *
 * `!important` is required rather than defensive. The base values are applied
 * INLINE by the block, and an inline declaration beats a stylesheet rule of any
 * specificity, so the bands have to out-rank it.
 *
 * @since 1.0.7
 */

/**
 * Internal dependencies.
 */
import { applyFilters } from '@wordpress/hooks';

import { convertToKebabCase } from '@spectra-helpers';
import { BLOCK_RESPONSIVE_KEYS } from '../extensions/responsive-controls/utils/constants';

/**
 * The responsive flat keys a block declares.
 *
 * Read from `constants` rather than through `getBlockResponsiveKeys()` in
 * `utils/helpers`, deliberately: that module imports the extension's entry point
 * (`getResetInProgress` from '..'), so importing it from a BLOCK closes a cycle
 * and the bundle throws `Cannot access 'B' before initialization` on every block
 * — measured, 77 times on one editor load. `constants` imports nothing but
 * WordPress packages, so it is safe to reach from either side.
 *
 * The Pro filter is applied here too, so Pro-registered keys band like the rest.
 *
 * @since 1.0.7
 * @param {string} blockName The block name.
 * @return {Array} The block's responsive flat keys.
 */
const resolveFlatKeys = ( blockName ) =>
	applyFilters(
		'spectra.responsive-controls.block-responsive-keys',
		BLOCK_RESPONSIVE_KEYS[ blockName ] || [],
		blockName
	);

/**
 * Serialise one style-object entry as a CSS declaration.
 *
 * Producers return React style objects, so property names arrive camelCased
 * (`minHeight`) while custom properties arrive already hyphenated
 * (`--spectra-background-image`) and must be left alone.
 *
 * @since 1.0.7
 * @param {string} prop  The style-object key.
 * @param {*}      value The value.
 * @return {string} A `prop:value !important;` declaration.
 */
const declaration = ( prop, value ) => {
	const property = prop.startsWith( '--' ) ? prop : convertToKebabCase( prop );

	return `${ property }:${ value } !important;`;
};

/**
 * Pick just the responsive flat keys out of a `style` layer.
 *
 * @since 1.0.7
 * @param {Object} layer    A `style` root or one of its viewport states.
 * @param {Array}  flatKeys The block's responsive flat keys.
 * @return {Object} The keys present in that layer.
 */
const pickFlatKeys = ( layer, flatKeys ) => {
	const picked = {};

	if ( ! layer || typeof layer !== 'object' ) {
		return picked;
	}

	flatKeys.forEach( ( key ) => {
		if ( undefined !== layer[ key ] ) {
			picked[ key ] = layer[ key ];
		}
	} );

	return picked;
};

/**
 * Merge a band's flat keys over the base attributes.
 *
 * Scalars replace; plain objects merge one level, matching PHP's per-property
 * fallback for `background`.
 *
 * @since 1.0.7
 * @param {Object} baseAttrs The base attributes.
 * @param {Object} bandKeys  The band's own flat keys.
 * @return {Object} The band's effective attributes.
 */
const mergeBandOverBase = ( baseAttrs, bandKeys ) => {
	const merged = { ...baseAttrs };

	Object.entries( bandKeys ).forEach( ( [ key, value ] ) => {
		const base = baseAttrs[ key ];
		const isObject = ( v ) => v && 'object' === typeof v && ! Array.isArray( v );
		merged[ key ] = isObject( value ) && isObject( base ) ? { ...base, ...value } : value;
	} );

	return merged;
};

/**
 * A child's `style` with a parent's per-device value filled in where the child
 * has none of its own.
 *
 * Parents such as Tabs, Accordion and List republish a responsive key through
 * `providesContext`. Block context carries the parent's ROOT attribute, which
 * is the routing scratch — the last-edited device's value — so an inheriting
 * child previewed that one value at every breakpoint while the front end
 * rendered the parent's bands correctly. The parent also publishes its `style`;
 * from it the child can inherit per band: for base and every state where the
 * child stores nothing for `key`, the parent's value for `parentKey` at that
 * layer is filled in, and the child's own values are left alone.
 *
 * The result is for PREVIEW only — it is never written back.
 *
 * @since 1.0.7
 * @param {Object} style       The child's `style` attribute.
 * @param {string} key         The child's flat key (e.g. `size`).
 * @param {Object} parentStyle The parent's `style` attribute, from context.
 * @param {string} parentKey   The parent's flat key (e.g. `size` or `iconSize`).
 * @return {Object} A `style` object for the preview.
 */
export const inheritResponsiveKey = ( style, key, parentStyle, parentKey ) => {
	const isObject = ( v ) => v && 'object' === typeof v && ! Array.isArray( v );
	if ( ! isObject( parentStyle ) ) {
		return style;
	}

	const next = isObject( style ) ? { ...style } : {};
	const queries = window?.spectra_blocks_info?.viewport_media_queries || {};

	if ( undefined === next[ key ] && undefined !== parentStyle[ parentKey ] ) {
		next[ key ] = parentStyle[ parentKey ];
	}

	Object.keys( queries ).forEach( ( state ) => {
		if ( ! state || ! isObject( parentStyle[ state ] ) || undefined === parentStyle[ state ][ parentKey ] ) {
			return;
		}
		const childState = isObject( next[ state ] ) ? next[ state ] : {};
		if ( undefined === childState[ key ] ) {
			next[ state ] = { ...childState, [ key ]: parentStyle[ state ][ parentKey ] };
		}
	} );

	return next;
};

/**
 * The value a child inherits from its parent for the device being previewed.
 *
 * Resolves like the front end: the parent's state for this device, then the
 * parent's base in `style`, then the context value (the root attribute) for
 * content that never stored a per-device value.
 *
 * @since 1.0.7
 * @param {Object} parentStyle The parent's `style` attribute, from context.
 * @param {string} parentKey   The parent's flat key.
 * @param {string} deviceType  Core device name, e.g. `Tablet`.
 * @param {*}      rootValue   The parent's root attribute, from context.
 * @return {*} The inherited value for the previewed device.
 */
export const resolveInheritedResponsiveValue = ( parentStyle, parentKey, deviceType, rootValue ) => {
	const isObject = ( v ) => v && 'object' === typeof v && ! Array.isArray( v );
	if ( ! isObject( parentStyle ) ) {
		return rootValue;
	}

	const state = { Mobile: '@mobile', Tablet: '@tablet' }[ deviceType ];
	if ( state && isObject( parentStyle[ state ] ) && undefined !== parentStyle[ state ][ parentKey ] ) {
		return parentStyle[ state ][ parentKey ];
	}
	if ( undefined !== parentStyle[ parentKey ] ) {
		return parentStyle[ parentKey ];
	}

	/*
	 * No value at this device and none at base. The root attribute is the
	 * routing scratch — whatever device was edited last — so once ANY device
	 * has authored the key it must not stand in for base: Tablet 2 and Mobile 1
	 * typed with Desktop left at its default made Desktop show Mobile's 1. Let
	 * the caller's default apply instead. When no device ever authored the key
	 * the root is the only copy there is (content that never had per-device
	 * values) and still counts.
	 */
	const queries = window?.spectra_blocks_info?.viewport_media_queries || {};
	const authoredInAState = Object.keys( queries ).some(
		( s ) => s && isObject( parentStyle[ s ] ) && undefined !== parentStyle[ s ][ parentKey ]
	);

	return authoredInAState ? undefined : rootValue;
};

/**
 * Normalise a producer's return into selector-keyed style objects.
 *
 * A producer may return either shape:
 *
 *   { minHeight: '250px' }                          → the block element
 *   [ { selector: ' svg.spectra-icon', styles: … } ] → a descendant
 *
 * The second exists because many responsive values are painted on a CHILD, not
 * on the block: an icon's `size` lands on its `svg`, a separator's dimensions on
 * the rule element, a slider's arrow metrics on the arrows. Those cannot be
 * banded by declaring on the block element, so a producer says where its
 * declarations belong and the selector is appended to the block's scope.
 *
 * @since 1.0.7
 * @param {Object|Array} produced A producer's return value.
 * @return {Object} Style objects keyed by selector suffix ('' = the block itself).
 */
const bySelector = ( produced ) => {
	if ( ! produced ) {
		return {};
	}

	if ( ! Array.isArray( produced ) ) {
		return { '': produced };
	}

	return produced.reduce( ( acc, entry ) => {
		const selector = entry?.selector || '';
		const styles = entry?.styles || {};

		return { ...acc, [ selector ]: { ...( acc[ selector ] || {} ), ...styles } };
	}, {} );
};

/**
 * Run every producer over one set of attributes, grouped by selector.
 *
 * @since 1.0.7
 * @param {Array}  producers Functions of `( attributes ) => styleObject|Array`.
 * @param {Object} attrs     The attributes to derive from.
 * @return {Object} Style objects keyed by selector suffix.
 */
const derive = ( producers, attrs ) =>
	producers.reduce( ( acc, produce ) => {
		let produced;

		try {
			produced = produce( attrs );
		} catch ( e ) {
			// A producer that cannot read these attributes contributes nothing,
			// which loses a band rather than breaking the editor.
			return acc;
		}

		const groups = bySelector( produced );

		Object.entries( groups ).forEach( ( [ selector, styles ] ) => {
			acc[ selector ] = { ...( acc[ selector ] || {} ), ...styles };
		} );

		return acc;
	}, {} );

/**
 * Square dimensions for an icon painted on a child element.
 *
 * Several blocks paint an icon the same way — `width` and `height` on a child
 * `svg`, from one size attribute — but each resolves its own fallback chain, and
 * two of them inherit a size from their parent block's context
 * (`accordion-child-header-icon`, `list-child-icon`). So the caller resolves the
 * value and this only shapes it, which keeps the fallback where it belongs and
 * still avoids five copies of the same selector-and-styles boilerplate.
 *
 * ` svg` rather than a class: measured in the editor, `RenderSVG` leaves these
 * elements without one. The front end's own map uses ` svg.spectra-icon`, which
 * matches nothing here.
 *
 * @since 1.0.7
 * @param {string} dimension The resolved size, fallbacks already applied.
 * @param {string} selector  Override when a block has more than one svg.
 * @return {Array} Selector-scoped style entries.
 */
export const iconDimensionStyles = ( dimension, selector = ' svg' ) => {
	if ( ! dimension ) {
		return [];
	}

	return [ { selector, styles: { width: dimension, height: dimension } } ];
};

/**
 * Banded CSS for a block's per-device values.
 *
 * @since 1.0.7
 * @param {Object} props            Options.
 * @param {string} props.clientId   The block's client id, used to scope the rules.
 * @param {Object} props.attributes The block's attributes.
 * @param {string} props.blockName  The block name, used to resolve its keys.
 * @param {Array}  props.producers  Functions of `( attributes ) => styleObject`.
 * @return {string} CSS text, empty when there is nothing to emit.
 */
export const getResponsivePreviewCss = ( { clientId, attributes, blockName, producers = [] } ) => {
	const style = attributes?.style;
	const flatKeys = resolveFlatKeys( blockName );

	if ( ! clientId || ! style || ! flatKeys.length || ! producers.length ) {
		return '';
	}

	/*
	 * Bands come from the resolved viewport queries, NOT from
	 * `tablet_breakpoint` / `mobile_breakpoint`, which are Spectra's historical
	 * 1024 / 767 and would band the preview where the site does not.
	 */

	const queries = window?.spectra_blocks_info?.viewport_media_queries || {};

	// Base is what the inline paint already carries; bands are compared to it.
	const baseAttrs = { ...attributes, ...pickFlatKeys( style, flatKeys ) };

	/*
	 * `attributes` here is the DEVICE-RESOLVED view (the state's value at a
	 * narrow device, the root scratch otherwise), not the base. For a key the
	 * root of `style` does not hold, that value leaked into the base and the
	 * band compared equal to itself — a Tablet-only gap emitted no tablet rule
	 * while a Tablet-only column count did, purely because base happened to
	 * store columns. Where some state authored the key and base did not, the
	 * base has no value: leave it to the producer's default, as the front end
	 * does.
	 */
	flatKeys.forEach( ( key ) => {
		if ( undefined !== style[ key ] ) {
			return;
		}
		const authoredInAState = Object.keys( queries ).some(
			( state ) => state && style[ state ] && undefined !== style[ state ][ key ]
		);
		if ( authoredInAState ) {
			baseAttrs[ key ] = undefined;
		}
	} );
	const baseGroups = derive( producers, baseAttrs );

	let css = '';

	Object.entries( queries ).forEach( ( [ state, query ] ) => {
		// `base` has no query — it is the inline paint, already applied.
		if ( ! query || ! style[ state ] ) {
			return;
		}

		// Object-valued keys (`background`) merge per property over base — the
		// same resolution PHP applies when it hydrates the store — so a band that
		// stores only `backgroundSize` previews the base image at that size
		// instead of previewing no image at all.
		const bandAttrs = mergeBandOverBase( baseAttrs, pickFlatKeys( style[ state ], flatKeys ) );
		const bandGroups = derive( producers, bandAttrs );

		let rules = '';

		Object.entries( bandGroups ).forEach( ( [ selector, styles ] ) => {
			const base = baseGroups[ selector ] || {};

			const declarations = Object.entries( styles )
				// Emit only what this band changes, so inheritance stays the
				// cascade's job.
				.filter( ( [ prop, value ] ) => value !== base[ prop ] )
				.map( ( [ prop, value ] ) => declaration( prop, value ) )
				.join( '' );

			if ( declarations ) {
				rules += `[data-block="${ clientId }"]${ selector }{${ declarations }}`;
			}
		} );

		if ( rules ) {
			css += `@media ${ query }{${ rules }}`;
		}
	} );

	return css;
};
