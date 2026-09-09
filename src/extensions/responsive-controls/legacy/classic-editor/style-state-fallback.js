/**
 * `style` viewport states, read as legacy breakpoints, for the pre-7.1 editor.
 *
 * WHY
 *
 * A block variation is authored once, in the shape the CURRENT editor writes.
 * Popup Builder's Info Bar declares its whole responsive design as WordPress
 * 7.1 viewport states — `style['@tablet']` and `style['@mobile']` carrying the
 * layout, width, spacing and height each viewport wants — and carries no
 * `responsiveControls` at all. On 7.1 core reads those states and the bar
 * stacks into one column on a phone.
 *
 * Below 7.1 the front end already agrees with that: `ResponsiveControls`
 * hydrates its store from `style` before a block renders, so the bands come out
 * right. The EDITOR did not. Its projection reads `responsiveControls` only, so
 * the Info Bar kept its three-column base layout at every device and the
 * countdown, heading and button were crushed side by side in the Mobile
 * preview — an editor that disagreed with the site it was previewing.
 *
 * WHAT THIS DOES
 *
 * Reads the same states the server reads, in the same bucket shape, with the
 * same precedence: a legacy `lg` / `md` / `sm` value WINS, and a state only
 * fills what the store never covered. Below 7.1 that ordering is the honest
 * one — core has no viewport states for this editor to write, so every
 * per-device edit lands in the legacy buckets, and a state can only be a
 * variation's default or content authored on a newer site.
 *
 * Nothing here is persisted. The augmented store is used to PROJECT the
 * previewed device onto the block's live attributes; saved content keeps
 * whatever it already had until the user edits something.
 *
 * @since 1.0.7
 */

/**
 * Internal dependencies.
 */
import { readBucketFromStyle, mergeBuckets } from '../../utils/style-store';
import { BUCKET_TOP_LEVEL_STYLE_KEYS } from '../../utils/constants';
import { getBlockResponsiveKeys } from './helpers';

/**
 * Legacy breakpoint keys against the store device names `style` is read by.
 *
 * @since 1.0.7
 * @type {Array}
 */
const BREAKPOINT_TO_STORE_DEVICE = Object.freeze( [
	[ 'lg', 'base' ],
	[ 'md', '@tablet' ],
	[ 'sm', '@mobile' ],
] );

const isPlainObject = ( value ) =>
	typeof value === 'object' && value !== null && ! Array.isArray( value );

/**
 * Layout properties that belong to WordPress core, not to this extension.
 *
 * `layout` is shared: core reads these six keys out of it as CHILD layout — how
 * a block sits inside its parent — while the container layout (`type`,
 * `columnCount`, `orientation`, …) lives in the same object. Twins of this list
 * exist in `utils/style-store.js` and `ResponsiveControls::CORE_CHILD_LAYOUT_KEYS`;
 * keep the three in sync.
 *
 * @since 1.0.7
 * @type {Array}
 */
const CORE_CHILD_LAYOUT_KEYS = Object.freeze( [
	'selfStretch',
	'flexSize',
	'columnStart',
	'columnSpan',
	'rowStart',
	'rowSpan',
] );

/**
 * Merge two buckets, with the second winning, keeping both halves of `layout`.
 *
 * `mergeBuckets()` replaces a top-level key wholesale, which is right for every
 * key but this one. The two halves of `layout` arrive from different places: a
 * variation puts the container layout on the block's `layout` ATTRIBUTE and the
 * child layout in `style.layout`, so replacing meant the Info Bar's countdown
 * kept only `{ selfStretch: 'fit', flexSize: null }` and lost
 * `{ type: 'grid', columnCount: 4 }` — its four units stacked down the bar at
 * Desktop instead of sitting in a row. Merged one level down, both survive.
 *
 * @since 1.0.7
 * @param {Object} base     Lower-priority bucket.
 * @param {Object} override Higher-priority bucket.
 * @return {Object} Merged bucket.
 */
const mergeLayoutAware = ( base, override ) => {
	const merged = mergeBuckets( base, override );

	if ( isPlainObject( base?.layout ) && isPlainObject( override?.layout ) ) {
		merged.layout = { ...base.layout, ...override.layout };
	}

	return merged;
};

/**
 * Split a bucket's `layout` the way core splits it, and the way PHP does.
 *
 * The container half stays on the bucket, the child half moves under `.style`,
 * which is where the projection expects each to be (`PROPERTIES_TO_MERGE` lists
 * `layout` and `style.layout` separately). `hydrate_layout_group()` performs the
 * same split server-side.
 *
 * @since 1.0.7
 * @param {Object} bucket Bucket to normalise, not modified.
 * @return {Object} The bucket, with `layout` split.
 */
const splitLayout = ( bucket ) => {
	if ( ! isPlainObject( bucket?.layout ) ) {
		return bucket;
	}

	const container = {};
	const child = {};

	Object.keys( bucket.layout ).forEach( ( key ) => {
		if ( CORE_CHILD_LAYOUT_KEYS.includes( key ) ) {
			child[ key ] = bucket.layout[ key ];
			return;
		}

		container[ key ] = bucket.layout[ key ];
	} );

	const next = { ...bucket };

	if ( 0 === Object.keys( container ).length ) {
		delete next.layout;
	} else {
		next.layout = container;
	}

	if ( 0 !== Object.keys( child ).length ) {
		next.style = { ...( next.style || {} ), layout: { ...( next.style?.layout || {} ), ...child } };
	}

	return next;
};

/**
 * The desktop layer the block already shows, in bucket shape.
 *
 * `readBucketFromStyle()` covers the base groups that live inside `style`, and
 * these sit BESIDE it: `layout` is core's own attribute, and a block's flat
 * keys are plain attributes too. Without them the desktop bucket describes only
 * half the block, and the projection reads that as "desktop declares nothing
 * here" — `cleanupStaleNestedKeys()` then removes the very keys it could not
 * see. That is what stripped the Info Bar's three-column `layout` on the way
 * back to Desktop, flattening the bar that had just been fixed on Mobile.
 *
 * Lowest priority of the three layers, so it only ever restores what the other
 * two never mention.
 *
 * @since 1.0.7
 * @param {Object} attributes The block's attributes.
 * @param {Array}  flatKeys   Block-specific attribute names that sit flat.
 * @return {Object} Bucket for the desktop layer.
 */
const bucketFromRootAttributes = ( attributes, flatKeys ) => {
	const bucket = {};

	BUCKET_TOP_LEVEL_STYLE_KEYS.forEach( ( key ) => {
		if ( undefined !== attributes?.[ key ] ) {
			bucket[ key ] = attributes[ key ];
		}
	} );

	flatKeys.forEach( ( key ) => {
		if ( undefined !== attributes?.[ key ] ) {
			bucket[ key ] = attributes[ key ];
		}
	} );

	return bucket;
};

/**
 * The block's store, with anything only `style` knows filled in.
 *
 * Three layers per breakpoint, lowest first:
 *
 *   1. the block's own attributes — desktop only, and only what `style` misses
 *   2. the matching `style` viewport state
 *   3. the legacy `lg` / `md` / `sm` bucket, which always wins
 *
 * @since 1.0.7
 * @param {Object} attributes  The block's attributes.
 * @param {string} blockName   The block name.
 * @param {Object} loadedAttrs The attributes as first seen, for the desktop
 *                             layer. The live ones are a projection of
 *                             whichever device is previewed, so reading the
 *                             desktop layer from them re-applied Mobile's
 *                             value on the way back: the Info Bar returned to
 *                             Desktop as ONE column. Defaults to `attributes`.
 * @return {Object} A store keyed `lg` / `md` / `sm`. The original when `style`
 *                  has nothing to add, so the common case allocates nothing.
 */
export const storeWithStyleStates = ( attributes, blockName, loadedAttrs ) => {
	const store = isPlainObject( attributes?.responsiveControls )
		? attributes.responsiveControls
		: {};

	if ( ! isPlainObject( attributes?.style ) ) {
		return store;
	}

	const flatKeys = getBlockResponsiveKeys( blockName, true );
	const desktopSource = isPlainObject( loadedAttrs ) ? loadedAttrs : attributes;
	let augmented = null;

	BREAKPOINT_TO_STORE_DEVICE.forEach( ( [ breakpoint, device ] ) => {
		const fromStyle = readBucketFromStyle( attributes.style, device, flatKeys );
		const isDesktop = 'lg' === breakpoint;
		const base = isDesktop
			? mergeLayoutAware( bucketFromRootAttributes( desktopSource, flatKeys ), fromStyle )
			: fromStyle;

		if ( ! base || 0 === Object.keys( base ).length ) {
			return;
		}

		augmented = augmented || { ...store };
		// The store last: what the pre-7.1 editor wrote outranks everything.
		augmented[ breakpoint ] = splitLayout( mergeLayoutAware( base, store[ breakpoint ] ) );
	} );

	return augmented || store;
};
