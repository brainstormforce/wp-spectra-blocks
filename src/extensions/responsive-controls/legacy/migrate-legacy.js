/**
 * One-way migration of the legacy responsive store into core's `style` attribute.
 *
 * Breakpoint values used to live in a Spectra-owned `responsiveControls`
 * attribute, keyed `lg` / `md` / `sm`. They now live in WordPress core's
 * `style`, keyed the way core keys them:
 *
 *     responsiveControls: { lg: {...}, md: {...}, sm: {...} }
 *          ->  style: { ...base, '@tablet': {...}, '@mobile': {...} }
 *
 * The migration runs on `blocks.getBlockAttributes`, so it happens as a block is
 * parsed and before anything renders. That matters for more than storage: core's
 * own Typography, Spacing and Border panels read `attributes.style`, so until the
 * values are lifted across, a block saved by an older version shows its base
 * value at every device and the inspector looks empty on Tablet and Mobile.
 *
 * Nothing is written to the database here. Parsing is not a change, so the post
 * is not marked dirty; the migrated shape is persisted the next time the user
 * saves the post for their own reasons.
 *
 * Precedence follows the layer, and legacy content is why:
 *
 *   - At base the store wins. Before per-device states existed, core's controls
 *     wrote the value for whichever device was selected straight into the root of
 *     `style`, so on old content that root is a scratch value — frequently the
 *     tablet number — while the real desktop value sits in the store.
 *   - Inside a state `style` wins. `style['@tablet']` can only have been written
 *     by core's own per-viewport control, so it is the authored value, while the
 *     store's copy is the base echoed in by Spectra's device switcher.
 *
 * Mirrors `ResponsiveControls::hydrate_store_from_style()` on the PHP side, which
 * performs the same reconciliation at render for content that has not been
 * re-saved yet.
 *
 * @since 1.0.7
 */

/**
 * External dependencies.
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import {
	BLOCK_RESPONSIVE_KEYS,
	BUCKET_TOP_LEVEL_STYLE_KEYS,
	ROOT_ATTRIBUTE_PRESET_REFS,
	savesAttributesToMarkup,
	STATE_KEYS,
} from '../utils/constants';
import { LEGACY_DEVICE_MAP } from './constants';
import { DEVICE_TO_STYLE_STATE } from '../utils/style-store';

const isPlainObject = ( value ) =>
	typeof value === 'object' && value !== null && ! Array.isArray( value );

/**
 * Write a dot-path into an object, copying each level on the way down.
 *
 * Defined here rather than imported from `utils/helpers` on purpose: that module
 * imports from the extension's entry point, and pulling it in from a module the
 * entry point itself loads would close an import cycle. `isPlainObject` above is
 * local for the same reason.
 *
 * Copying rather than mutating matters — the levels can be shared with the
 * attributes object this migration was handed, which must not be touched.
 *
 * @since 1.0.7
 * @param {Object} target Object to write into. Mutated at the top level only.
 * @param {string} path   Dot-separated path, e.g. `typography.fontSize`.
 * @param {*}      value  Value to set.
 * @return {void}
 */
const setNestedPath = ( target, path, value ) => {
	const segments = path.split( '.' );
	const last = segments.pop();
	let cursor = target;

	segments.forEach( ( segment ) => {
		cursor[ segment ] = isPlainObject( cursor[ segment ] ) ? { ...cursor[ segment ] } : {};
		cursor = cursor[ segment ];
	} );

	cursor[ last ] = value;
};

/**
 * Read a dot-path, without importing a helper. See `setNestedPath`.
 *
 * @since 1.0.7
 * @param {Object} source Object to read from.
 * @param {string} path   Dot-separated path.
 * @return {*} The value, or `undefined`.
 */
const getNestedPath = ( source, path ) =>
	path.split( '.' ).reduce( ( acc, segment ) => ( isPlainObject( acc ) ? acc[ segment ] : undefined ), source );

/**
 * Core's child-layout keys — how a block sits inside its parent's flex or grid
 * container. They never lived in the legacy store; when they appear at the
 * root of `style` they are core-authored and must survive the migration.
 * Mirrors `CORE_CHILD_LAYOUT_KEYS` in `utils/style-store.js` and the PHP
 * constant in `class-responsive-controls.php`.
 *
 * @since 1.0.7
 * @type {Array}
 */
const CORE_CHILD_LAYOUT_KEYS = [
	'selfStretch',
	'flexSize',
	'columnStart',
	'columnSpan',
	'rowStart',
	'rowSpan',
];

/**
 * Collapse legacy `lg` / `md` / `sm` keys onto the canonical device keys.
 *
 * A block can legitimately carry both — saved by an older version, then opened
 * in a build that wrote the new names. The canonical value wins, since it is the
 * one the current editor produced.
 *
 * @since 1.0.7
 * @param {Object} store The raw `responsiveControls` attribute.
 * @return {Object} Store keyed `base` / `@tablet` / `@mobile`.
 */
const canonicaliseStore = ( store ) => {
	const next = { ...store };
	let hadLegacy = false;

	Object.entries( LEGACY_DEVICE_MAP ).forEach( ( [ legacy, canonical ] ) => {
		if ( ! ( legacy in next ) ) {
			return;
		}

		const legacyBucket = isPlainObject( next[ legacy ] ) ? next[ legacy ] : {};
		const canonicalBucket = isPlainObject( next[ canonical ] ) ? next[ canonical ] : {};

		// Deep, leaf-level merge — the same `array_replace_recursive()` PHP's
		// normalisation uses, so a canonical `spacing.margin` no longer wipes a
		// legacy `spacing.padding` sitting in the same group.
		next[ canonical ] = deepMergeObjects( legacyBucket, canonicalBucket );

		delete next[ legacy ];
		hadLegacy = true;
	} );

	/*
	 * Bake the legacy cascade. The old generator resolved mobile as
	 * `sm -> md -> lg`, so a tablet value applied on phones whenever mobile was
	 * unset. The current generator follows core's model — each viewport over
	 * base only — which would change how that content renders. Copying the
	 * tablet bucket under the mobile one (mobile wins where both are set)
	 * preserves the authored rendering while the data moves to core semantics.
	 * Mirrors the same bake in PHP `LegacyStore::normalize_device_keys()`.
	 */
	if ( hadLegacy && isPlainObject( next[ '@tablet' ] ) && Object.keys( next[ '@tablet' ] ).length ) {
		const mobile = isPlainObject( next[ '@mobile' ] ) ? next[ '@mobile' ] : {};
		next[ '@mobile' ] = deepMergeObjects( next[ '@tablet' ], mobile );
	}

	return next;
};

/**
 * Deep-merge two plain objects; `override` wins at every leaf.
 *
 * Matches PHP's `array_replace_recursive()` for the shapes the store holds,
 * so the editor bakes the legacy cascade exactly as the front end does.
 *
 * @since 1.0.7
 * @param {Object} base     Values to inherit from.
 * @param {Object} override Values that win where both are set.
 * @return {Object} The merged object.
 */
const deepMergeObjects = ( base, override ) => {
	const merged = { ...base };

	Object.entries( override ).forEach( ( [ key, value ] ) => {
		merged[ key ] =
			isPlainObject( value ) && isPlainObject( merged[ key ] )
				? deepMergeObjects( merged[ key ], value )
				: value;
	} );

	return merged;
};

/**
 * How a markup-backed block's flat responsive keys map into core's per-state shape.
 *
 * Core keeps a `core/image`'s per-viewport dimensions NESTED under `dimensions`
 * inside the state — `style['@tablet'].dimensions.width` — and reads nothing
 * from a flat `style['@tablet'].width`. Verified on 7.1: the nested shape emits
 * a banded rule — `(480px < width <= 782px)` scoped to `.wp-states-… img` with
 * `width:400px !important` — while the flat shape emits nothing at all.
 *
 * `scale` is core's object-fit control and is stored as `objectFit`.
 *
 * @since 1.0.9
 * @type {Object}
 */
const MARKUP_BACKED_STATE_PATHS = Object.freeze( {
	width: 'dimensions.width',
	height: 'dimensions.height',
	aspectRatio: 'dimensions.aspectRatio',
	scale: 'dimensions.objectFit',
} );

/**
 * Migrate ONLY the narrower viewport states of a markup-backed block.
 *
 * The base layer is deliberately untouched and the legacy store is deliberately
 * kept — see the guard in `migrateLegacyResponsiveStore()` for why both matter.
 * What is safe, and necessary, is lifting the narrower buckets into core's own
 * state shape: core bands those into media queries at render, and the class it
 * keys them on (`wp-states-…`) is added at RENDER time, not by `save()`. The
 * states themselves live only in the block delimiter, which block validation
 * does not compare — so writing them changes nothing about the saved HTML.
 *
 * Without this, a block authored below 7.1 renders none of its tablet or mobile
 * sizes once the site moves to 7.1: Spectra stops painting image dimensions
 * there (`paints_core_image_dimensions()` is false) and core has no state to
 * band. The value is in the store, and nothing reads it.
 *
 * An authored state always wins; the store only fills gaps.
 *
 * @since 1.0.9
 * @param {Object} attributes Block attributes as parsed.
 * @param {Object} store      The canonicalised legacy store.
 * @param {string} blockName  The block name, for its flat keys.
 * @return {Object} Attributes carrying the migrated states.
 */
const migrateMarkupBackedStates = ( attributes, store, blockName ) => {
	const flatKeys = applyFilters(
		'spectra.responsive-controls.block-responsive-keys',
		BLOCK_RESPONSIVE_KEYS[ blockName ] || [],
		blockName
	);

	if ( ! flatKeys.length ) {
		return attributes;
	}

	const nextStyle = isPlainObject( attributes.style ) ? { ...attributes.style } : {};
	let wrote = false;

	[ '@tablet', '@mobile' ].forEach( ( state ) => {
		const bucket = store[ state ];

		if ( ! isPlainObject( bucket ) ) {
			return;
		}

		const target = isPlainObject( nextStyle[ state ] ) ? { ...nextStyle[ state ] } : {};
		let touched = false;

		flatKeys.forEach( ( key ) => {
			const path = MARKUP_BACKED_STATE_PATHS[ key ];

			if ( ! path || undefined === bucket[ key ] ) {
				return;
			}

			// An authored state wins, at either the nested or the flat position.
			if ( undefined !== getNestedPath( target, path ) || undefined !== target[ key ] ) {
				return;
			}

			setNestedPath( target, path, bucket[ key ] );
			touched = true;
		} );

		if ( touched ) {
			nextStyle[ state ] = target;
			wrote = true;
		}
	} );

	if ( ! wrote ) {
		return attributes;
	}

	return {
		...attributes,
		style: nextStyle,
	};
};

/**
 * Migrate one block's attributes from the legacy store into `style`.
 *
 * Idempotent: a block already in the new shape has no store to migrate and is
 * returned untouched, so re-parsing is a no-op.
 *
 * @since 1.0.7
 * @param {Object} attributes Block attributes as parsed from post content.
 * @param {string} blockName  The block name, used for its block-specific keys.
 * @return {Object} Attributes, migrated when there was anything to migrate.
 */
export const migrateLegacyResponsiveStore = ( attributes, blockName ) => {
	const rawStore = attributes?.responsiveControls;

	if ( ! isPlainObject( rawStore ) || ! Object.keys( rawStore ).length ) {
		return attributes;
	}

	/*
	 * A block whose `save()` serialises its attributes is not migrated at all.
	 *
	 * This migration exists to move values into core's shape so core's own
	 * panels can read them. For `core/image` that move cannot be made without
	 * changing what the block saves, and every part of it causes harm:
	 *
	 *   - rewriting the base fails block validation, because WordPress checks
	 *     stored HTML against `save( attributes-after-filters )`, and on 7.1 it
	 *     also changes what renders — `paints_core_image_dimensions()` is
	 *     `! ViewportSupport::renders_states()`, so the inline width stays in
	 *     the markup and outranks anything this extension emits (#908);
	 *   - emptying the store destroys the `lg` bucket, which below 7.1 is what
	 *     renders: there `remove_core_image_inline_dimensions()` DOES run, the
	 *     inline dimensions are stripped, and the store paints them. Dropping
	 *     it silently changes those sites on the next save and breaks the
	 *     downgrade safety `LegacyStore` is built around.
	 *
	 * So the store is left exactly as authored and keeps being served through
	 * `readLegacyBucket()`, which is what the legacy reader is for. The visible
	 * cost is that core's OWN per-device panels stay empty on Tablet and Mobile
	 * for this block until the value is re-authored; Spectra's controls read
	 * the store directly and show it. That is a cosmetic gap on un-re-saved
	 * legacy content, traded for never losing an authored value.
	 */
	if ( savesAttributesToMarkup( blockName ) ) {
		return migrateMarkupBackedStates( attributes, canonicaliseStore( rawStore ), blockName );
	}

	const store = canonicaliseStore( rawStore );
	/*
	 * Through the same filter the runtime uses, so Pro-registered per-device
	 * keys are migrated too. Reading the raw map skipped them: the values were
	 * never lifted into `style` while the store below was reset regardless —
	 * open a legacy post with a Pro mega-menu, save, and its per-device widths
	 * were gone from the database.
	 */
	const flatKeys = applyFilters(
		'spectra.responsive-controls.block-responsive-keys',
		BLOCK_RESPONSIVE_KEYS[ blockName ] || [],
		blockName
	);
	const nextStyle = isPlainObject( attributes.style ) ? { ...attributes.style } : {};

	Object.entries( DEVICE_TO_STYLE_STATE ).forEach( ( [ device, state ] ) => {
		const bucket = store[ device ];

		if ( ! isPlainObject( bucket ) ) {
			return;
		}

		// An empty state key means the base layer, which is the root of `style`.
		const isRoot = '' === state;

		const target = isRoot
			? nextStyle
			: { ...( isPlainObject( nextStyle[ state ] ) ? nextStyle[ state ] : {} ) };
		const groups = isPlainObject( bucket.style ) ? bucket.style : {};

		STATE_KEYS.forEach( ( group ) => {
			/*
			 * `layout`, `fontSize`, `fontFamily` and `borderColor` sit at the top
			 * level of a legacy bucket, beside the block-specific keys, rather
			 * than under `.style` where the shared groups live. Reading only
			 * `groups` dropped all four: the block fell back to its default
			 * layout at every breakpoint, and a preset border colour or font
			 * size vanished. The nested position is still consulted as a
			 * fallback, since an intermediate build may have written it there.
			 */
			const isTopLevelKey = BUCKET_TOP_LEVEL_STYLE_KEYS.includes( group );
			const value = isTopLevelKey ? bucket[ group ] ?? groups[ group ] : groups[ group ];

			if ( undefined === value ) {
				return;
			}

			/*
			 * A preset read from the TOP LEVEL of the bucket is the same thing
			 * core keeps in the block attribute — a bare slug. Inside a state
			 * that is invisible to core: its panels read only the nested
			 * `var:preset|…` form there, so a migrated `@tablet.fontSize:
			 * 'medium'` rendered at the right size but left the Font Size
			 * control blank with "Responsive styles" on. Translate it to the
			 * form core wrote itself, which is also the form the root-attribute
			 * sync recognises.
			 *
			 * Only the top-level position is translated. The nested fallback is
			 * where CUSTOM values live (`typography.fontSize: '45px'`), and those
			 * are already in core's shape and must pass through untouched.
			 */
			const presetRef = isTopLevelKey && undefined !== bucket[ group ] ? ROOT_ATTRIBUTE_PRESET_REFS[ group ] : undefined;

			/*
			 * Precedence differs by layer, mirroring PHP's hydration exactly.
			 * At `@tablet` / `@mobile` an existing state value was written by the
			 * current editor and wins — the store only fills gaps. At the ROOT
			 * the store wins: on legacy content the root is a scratch projection
			 * of whichever device was selected at the last save, so the bucket's
			 * value is the authored one. `layout` keeps the root's core child
			 * keys (selfStretch, flexSize, grid spans), which never lived in the
			 * legacy store and are genuinely core-authored.
			 */
			if ( ! isRoot ) {
				if ( presetRef && 'string' === typeof value && ! value.startsWith( 'var:' ) ) {
					// The state may already carry an authored value at either
					// position; the store only fills gaps.
					if ( undefined === target[ group ] && undefined === getNestedPath( target, presetRef.path ) ) {
						setNestedPath( target, presetRef.path, `var:preset|${ presetRef.preset }|${ value }` );
					}
					return;
				}

				if ( undefined === target[ group ] ) {
					target[ group ] = value;
				}
				return;
			}

			if ( 'layout' === group && isPlainObject( value ) ) {
				const existing = isPlainObject( target.layout ) ? target.layout : {};
				const childKeys = {};
				CORE_CHILD_LAYOUT_KEYS.forEach( ( key ) => {
					if ( undefined !== existing[ key ] ) {
						childKeys[ key ] = existing[ key ];
					}
				} );
				target.layout = { ...childKeys, ...value };
				return;
			}

			target[ group ] = value;
		} );

		flatKeys.forEach( ( key ) => {
			if ( undefined === bucket[ key ] ) {
				return;
			}

			// Same precedence as the groups: the store wins at the root,
			// fills gaps at the states.
			if ( isRoot || undefined === target[ key ] ) {
				target[ key ] = bucket[ key ];
			}
		} );

		// The root was mutated in place; only a state has to be attached.
		if ( ! isRoot && Object.keys( target ).length ) {
			nextStyle[ state ] = target;
		}
	} );

	/*
	 * The loop settles keys the base bucket holds. A key the store holds ONLY
	 * at a narrower breakpoint marks the root's copy as scratch too — it is
	 * the projection of that device — and PHP's hydration refuses to promote
	 * it to the base layer. Remove it from the root so the editor shows, and
	 * a re-save persists, the same base the front end renders. `layout` keeps
	 * core's child keys, which are core-authored rather than scratch.
	 */
	const baseBucket = isPlainObject( store.base ) ? store.base : {};
	const narrowBuckets = [ store[ '@tablet' ], store[ '@mobile' ] ].filter( isPlainObject );

	const bucketHolds = ( bucket, key, topLevel ) => {
		if ( topLevel && undefined !== bucket[ key ] ) {
			return true;
		}
		return isPlainObject( bucket.style ) && undefined !== bucket.style[ key ];
	};

	STATE_KEYS.forEach( ( group ) => {
		const topLevel = BUCKET_TOP_LEVEL_STYLE_KEYS.includes( group );

		if (
			undefined === nextStyle[ group ] ||
			bucketHolds( baseBucket, group, topLevel ) ||
			! narrowBuckets.some( ( bucket ) => bucketHolds( bucket, group, topLevel ) )
		) {
			return;
		}

		if ( 'layout' === group && isPlainObject( nextStyle.layout ) ) {
			const childKeys = {};
			CORE_CHILD_LAYOUT_KEYS.forEach( ( key ) => {
				if ( undefined !== nextStyle.layout[ key ] ) {
					childKeys[ key ] = nextStyle.layout[ key ];
				}
			} );

			if ( Object.keys( childKeys ).length ) {
				nextStyle.layout = childKeys;
			} else {
				delete nextStyle.layout;
			}
			return;
		}

		delete nextStyle[ group ];
	} );

	flatKeys.forEach( ( key ) => {
		if (
			undefined !== nextStyle[ key ] &&
			undefined === baseBucket[ key ] &&
			narrowBuckets.some( ( bucket ) => undefined !== bucket[ key ] )
		) {
			delete nextStyle[ key ];
		}
	} );

	/*
	 * Root ATTRIBUTES get the same scratch treatment as the root of `style`.
	 * The old editor wrote flat values both into the store and into the root
	 * attribute for whichever device was selected at save — so on legacy
	 * content the root attribute is a projection, frequently the mobile
	 * number, while the authored base value sits in the `lg` bucket. Block
	 * panels and canvases read the root attribute; leaving the scratch there
	 * showed (and, on the next edit, persisted) the wrong base value. For any
	 * key the store holds at any breakpoint, the root attribute is
	 * overwritten with the base value — or cleared when only a narrower
	 * device authored it.
	 */
	const nextAttributes = { ...attributes };
	const allBuckets     = [ baseBucket, ...narrowBuckets ];

	[ ...STATE_KEYS.filter( ( k ) => BUCKET_TOP_LEVEL_STYLE_KEYS.includes( k ) && 'layout' !== k ), ...flatKeys ].forEach( ( key ) => {
		if ( undefined === nextAttributes[ key ] ) {
			return;
		}

		if ( ! allBuckets.some( ( bucket ) => undefined !== bucket[ key ] ) ) {
			return;
		}

		nextAttributes[ key ] = baseBucket[ key ];
	} );

	return {
		...nextAttributes,
		style: nextStyle,
		// Reset to the attribute default so it stops being serialised.
		responsiveControls: {},
	};
};
