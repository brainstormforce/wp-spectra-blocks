/**
 * Per-breakpoint storage inside WordPress core's `style` attribute.
 *
 * Breakpoint values live in `style` rather than in a Spectra-owned attribute:
 *
 *     style: {
 *         typography: { fontSize: '30px' },        // base - applies everywhere
 *         size: '24px',                            // Spectra-only, sits flat
 *         '@tablet': { typography: { fontSize: '25px' }, size: '20px' },
 *         '@mobile': { typography: { fontSize: '18px' }, size: '16px' },
 *     }
 *
 * `style` is registered on every block through block supports — verified across
 * all 86 Spectra blocks — and core already understands `@tablet` / `@mobile` on
 * 7.1+. One object means the editor writes to one place, core renders what it
 * recognises, and Spectra renders the rest from the same source.
 *
 * Core ignores keys it does not recognise rather than rejecting them, so
 * Spectra-only values ride along inside the same state objects. Verified on
 * 7.1-RC4: they survive parse, re-serialise byte-identically, and persist
 * through `wp_insert_post`.
 *
 * Two shapes meet here. Inside a state, shared style groups nest by name
 * (`typography`, `spacing`, `border`, `shadow`, `layout`) while block-specific
 * attributes sit flat (`size`, `gap`, `minWidth`, …). The bucket shape used by
 * the rest of the extension keeps the groups under `.style`, so these helpers
 * translate between the two.
 *
 * @since 1.0.7
 */

/**
 * Internal dependencies.
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import { BUCKET_TOP_LEVEL_STYLE_KEYS, STATE_KEYS } from './constants';

/**
 * Read one breakpoint out of the legacy `responsiveControls` store.
 *
 * Answered by the Legacy folder through a filter, so nothing on the current
 * path imports from it. Once legacy support is deleted the filter has no
 * implementation and this returns `{}`, which every caller merges as a no-op.
 *
 * @since 1.0.7
 * @param {Object} responsiveControls The legacy store, if the block still has one.
 * @param {string} device             Store device key: `base`, `@tablet` or `@mobile`.
 * @return {Object} The bucket, or an empty object.
 */
export const readLegacyBucket = ( responsiveControls, device ) => {
	const bucket = applyFilters(
		'spectra.responsive-controls.legacy-bucket',
		{},
		responsiveControls,
		device
	);

	// Filters take input from strangers; every caller relies on a plain object.
	return isPlainObject( bucket ) ? bucket : {};
};

/**
 * Store device keys mapped to their location inside `style`.
 *
 * An empty string means the ROOT of `style`, which is where the base layer lives.
 * That is core's own arrangement — its device map is
 * `{ Desktop: 'default', Tablet: '@tablet', Mobile: '@mobile' }`, so Desktop IS the
 * root and there is no `@desktop` state. Core never reads or writes one.
 *
 * Spectra used to keep the base in a `@desktop` key of its own, because the root
 * was the surface the editor projected the selected device into and a base stored
 * there would be overwritten on every device switch. That projection is gone, so
 * the root is free to be what core already treats it as.
 *
 * Keep in sync with `ResponsiveControls::DEVICE_TO_STYLE_STATE`.
 *
 * @since 1.0.7
 * @type {Object}
 */
export const DEVICE_TO_STYLE_STATE = Object.freeze( {
	'base': '',
	'@tablet': '@tablet',
	'@mobile': '@mobile',
} );

/**
 * Read the object a device's values live in: a state, or the root of `style`.
 *
 * @since 1.0.7
 * @param {Object} style  The block's `style` attribute.
 * @param {string} device Store device key.
 * @return {Object|undefined} The state object, or `style` itself for the base.
 */
const stateSource = ( style, device ) => {
	const state = DEVICE_TO_STYLE_STATE[ device ];

	return '' === state ? style : style?.[ state ];
};

/**
 * Layout properties that belong to WordPress core, not to this extension.
 *
 * `style[ state ].layout` is shared. Core reads these six keys out of it as CHILD
 * layout — how a block sits inside its parent's grid or flex container — while
 * Spectra writes the parent layout into the same object. See
 * `wp_get_layout_child_values()` and `wp_get_layout_container_values()`, which
 * split it the same way from core's side.
 *
 * Replacing the object wholesale therefore destroyed them: a grid child with a
 * per-breakpoint `columnSpan` lost it the moment Spectra wrote that state.
 *
 * @since 1.0.7
 * @type {Array}
 */
export const CORE_CHILD_LAYOUT_KEYS = Object.freeze( [
	'selfStretch',
	'flexSize',
	'columnStart',
	'columnSpan',
	'rowStart',
	'rowSpan',
] );

const isPlainObject = ( value ) =>
	typeof value === 'object' && value !== null && ! Array.isArray( value );

/**
 * Read one breakpoint out of `style`, in the bucket shape the extension uses.
 *
 * @since 1.0.7
 * @param {Object} style    The block's `style` attribute.
 * @param {string} device   Store device key: `base`, `@tablet` or `@mobile`.
 * @param {Array}  flatKeys Block-specific attribute names that sit flat.
 * @return {Object} Bucket with style groups under `.style` and flat keys beside it.
 */
export const readBucketFromStyle = ( style, device, flatKeys = [] ) => {
	if ( ! isPlainObject( style ) ) {
		return {};
	}

	const source = stateSource( style, device );

	if ( ! isPlainObject( source ) ) {
		return {};
	}

	const bucket = {};

	STATE_KEYS.forEach( ( group ) => {
		if ( undefined === source[ group ] ) {
			return;
		}

		if ( BUCKET_TOP_LEVEL_STYLE_KEYS.includes( group ) ) {
			bucket[ group ] = source[ group ];
			return;
		}

		bucket.style = bucket.style || {};
		bucket.style[ group ] = source[ group ];
	} );

	flatKeys.forEach( ( key ) => {
		if ( undefined !== source[ key ] ) {
			bucket[ key ] = source[ key ];
		}
	} );

	return bucket;
};

/**
 * Write one breakpoint's bucket back into `style`.
 *
 * Only the keys this extension owns are touched. Anything else already in the
 * state — colour, or a property core added that Spectra does not track — is
 * left exactly as it was.
 *
 * @since 1.0.7
 * @param {Object} style    The block's current `style` attribute.
 * @param {string} device   Store device key: `base`, `@tablet` or `@mobile`.
 * @param {Object} bucket   Bucket to write, in the extension's shape.
 * @param {Array}  flatKeys Block-specific attribute names that sit flat.
 * @return {Object} A new `style` object.
 */
export const writeBucketToStyle = ( style, device, bucket, flatKeys = [] ) => {
	const next = isPlainObject( style ) ? { ...style } : {};
	const state = DEVICE_TO_STYLE_STATE[ device ];
	const isRoot = '' === state;
	// The base layer is the root of `style` itself; the narrower devices are states.
	const target = isRoot
		? next
		: { ...( isPlainObject( next[ state ] ) ? next[ state ] : {} ) };
	const groups = isPlainObject( bucket?.style ) ? bucket.style : {};

	STATE_KEYS.forEach( ( group ) => {
		const value = BUCKET_TOP_LEVEL_STYLE_KEYS.includes( group )
			? bucket?.[ group ]
			: groups[ group ];

		/*
		 * `layout` is shared with core, so it is merged rather than replaced: the
		 * child-layout keys core owns are carried across, and are all that survives
		 * when this extension has no layout of its own to write.
		 */
		if ( 'layout' === group ) {
			const preserved = {};

			CORE_CHILD_LAYOUT_KEYS.forEach( ( key ) => {
				if ( undefined !== target[ group ]?.[ key ] ) {
					preserved[ key ] = target[ group ][ key ];
				}
			} );

			const merged = { ...preserved, ...( isPlainObject( value ) ? value : {} ) };

			if ( Object.keys( merged ).length ) {
				target[ group ] = merged;
			} else {
				delete target[ group ];
			}

			return;
		}

		if ( undefined === value ) {
			delete target[ group ];
			return;
		}

		target[ group ] = value;
	} );

	flatKeys.forEach( ( key ) => {
		if ( undefined === bucket?.[ key ] ) {
			delete target[ key ];
			return;
		}

		target[ key ] = bucket[ key ];
	} );

	if ( isRoot ) {
		// `target` IS `next`, already mutated in place — a base layer is never
		// dropped wholesale the way an empty state is, because the root also holds
		// properties this extension does not track.
		return next;
	}

	if ( Object.keys( target ).length ) {
		next[ state ] = target;
	} else {
		delete next[ state ];
	}

	return next;
};


/**
 * Merge two buckets, with the second winning.
 *
 * Style groups are merged one level down so that a group held only by the
 * legacy store survives alongside a group `style` declares.
 *
 * @since 1.0.7
 * @param {Object} base     Lower-priority bucket.
 * @param {Object} override Higher-priority bucket.
 * @return {Object} Merged bucket.
 */
export const mergeBuckets = ( base, override ) => {
	const merged = { ...( base || {} ), ...( override || {} ) };

	if ( isPlainObject( base?.style ) || isPlainObject( override?.style ) ) {
		merged.style = { ...( base?.style || {} ), ...( override?.style || {} ) };
	}

	return merged;
};

