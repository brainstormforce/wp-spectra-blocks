/**
 * External dependencies.
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch, useSelect, select as dataSelect } from '@wordpress/data';
import { useCallback, useEffect, useMemo } from '@wordpress/element';
import { getBlockSupport, getBlockType } from '@wordpress/blocks';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import {
	DESKTOP,
	EXCLUDED_BLOCKS,
	SUPPORTED_BLOCKS,
	RESPONSIVE_KEYS,
	BREAKPOINT_TYPE_MAP,
	ALLOWED_PREFIXES,
	MUTUALLY_EXCLUSIVE_ATTR_PAIRS,
	BLOCK_RESPONSIVE_KEYS,
	STYLE_RESPONSIVE_KEYS,
	BUCKET_TOP_LEVEL_STYLE_KEYS,
	SCRATCH_ROOT_ATTRIBUTE_KEYS,
	ROOT_ATTRIBUTE_PRESET_REFS,
	coreViewportStatesAreIndependent,
} from './constants';
import {
	DEVICE_TO_STYLE_STATE,
	readBucketFromStyle,
	readLegacyBucket,
	writeBucketToStyle,
	mergeBuckets,
} from './style-store';
import { getResetInProgress } from '..';
import { useResponsiveEditing } from './use-responsive-editing';

/**
 * ===================================================================
 * UTILITY FUNCTIONS
 * ===================================================================
 *
 * This section contains general utility functions used throughout.
 * the responsive controls system. These functions handle object
 * manipulation, path traversal, and value checking.
 */
/**
 * Creates a deep clone of an object or array.
 *
 * @since x.x.x
 *
 * @param {*} obj - The object to clone.
 * @return {*} A deep clone of the input object.
 */
export const deepClone = ( obj ) => {
	// Return primitives as is.
	if ( typeof obj !== 'object' || obj === null ) {return obj;}

	// Use native structuredClone if available (modern browsers)
	if ( typeof structuredClone === 'function' ) {
		return structuredClone( obj );
	}

	// Use JSON methods for deep cloning.
	return JSON.parse( JSON.stringify( obj ) );
};


/**
 * Gets a nested value from an object using a dot-notation path.
 * Safely handles missing intermediate properties.
 *
 * @since x.x.x
 *
 * @param {Object} obj          - The object to retrieve the value from.
 * @param {string} path         - The dot-notation path to the value.
 * @param {*}      defaultValue - The default value to return if path is not found.
 * @return {*} The value at the specified path or defaultValue if not found.
 */
export const getNested = ( obj, path, defaultValue ) => {
	if ( ! obj || typeof path !== 'string' ) {return defaultValue;}

	const result = path.split( '.' ).reduce( ( current, key ) => {
		return current && typeof current === 'object' ? current[ key ] : undefined;
	}, obj );

	return result === undefined ? defaultValue : result;
};

/**
 * Sets a nested value in an object using a dot-notation path.
 * Creates intermediate objects if they don't exist.
 *
 * @since x.x.x
 *
 * @param {Object} obj   - The object to modify.
 * @param {string} path  - The dot-notation path where to set the value.
 * @param {*}      value - The value to set.
 * @return {void}
 */
export const setNested = ( obj, path, value ) => {
	if ( ! obj || typeof path !== 'string' ) {return;}

	const keys = path.split( '.' );
	const lastKey = keys.pop();

	const target = keys.reduce( ( current, key ) => {
		if ( ! ( key in current ) || typeof current[ key ] !== 'object' || current[ key ] === null ) {
			current[ key ] = {};
		}
		return current[ key ];
	}, obj );

	target[ lastKey ] = value;
};

/**
 * Deletes a nested key from an object using a dot-notation path.
 * Safely handles missing intermediate properties.
 *
 * @since x.x.x
 *
 * @param {Object} obj  - The object to modify.
 * @param {string} path - The dot-notation path to the key to delete.
 * @return {boolean} True if the property was deleted, false otherwise.
 */
export const deleteNested = ( obj, path ) => {
	if ( ! obj || typeof path !== 'string' ) {return false;}

	const keys = path.split( '.' );
	const lastKey = keys.pop();

	const target = keys.reduce( ( current, key ) => {
		return current && typeof current === 'object' ? current[ key ] : undefined;
	}, obj );

	if ( target && typeof target === 'object' && lastKey in target ) {
		delete target[ lastKey ];
		return true;
	}

	return false;
};

/**
 * Checks if an object has a property at the specified path.
 *
 * @since x.x.x
 *
 * @param {Object} obj  - The object to check.
 * @param {string} path - The dot-notation path to check.
 * @return {boolean} True if the property exists, false otherwise.
 */
const has = ( obj, path ) => {
	if ( ! obj || typeof path !== 'string' ) {return false;}

	const keys = path.split( '.' );
	let current = obj;

	for ( const key of keys ) {
		if ( current === null || current === undefined || typeof current !== 'object' ) {
			return false;
		}
		if ( ! ( key in current ) ) {
			return false;
		}
		current = current[ key ];
	}

	return true;
};

/**
 * Checks if value is an object (not array, null, or function).
 *
 * @since x.x.x
 *
 * @param {*} obj - The value to check.
 * @return {boolean} True if value is an object, false otherwise.
 */
export const isObject = ( obj ) => {
	return obj !== null && typeof obj === 'object' && ! Array.isArray( obj );
};

/**
 * Checks if value is an array.
 *
 * @since x.x.x
 *
 * @param {*} value - The value to check.
 * @return {boolean} True if value is an array, false otherwise.
 */
const isArray = Array.isArray;

/**
 * Checks if value is a Date object.
 *
 * @since x.x.x
 *
 * @param {*} obj - The value to check.
 * @return {boolean} True if value is a Date, false otherwise.
 */
const isDate = ( obj ) => obj instanceof Date;

/**
 * Checks if value is null or undefined.
 *
 * @since x.x.x
 *
 * @param {*} value - The value to check.
 * @return {boolean} True if value is null or undefined, false otherwise.
 */
const isNil = ( value ) => value === null || value === undefined;

/**
 * Checks if value is a function.
 *
 * @since x.x.x
 *
 * @param {*} value - The value to check.
 * @return {boolean} True if value is a function, false otherwise.
 */
const isFunction = ( value ) => typeof value === 'function';

/**
 * Checks if value is undefined.
 *
 * @since x.x.x
 *
 * @param {*} value - The value to check.
 * @return {boolean} True if value is undefined, false otherwise.
 */
const isUndefined = ( value ) => value === undefined;

/**
 * Deeply merges source object into target object.
 * Handles special cases like null values, type mismatches, and nested objects.
 *
 * @since x.x.x
 *
 * @param {Object} target - The target object to merge into.
 * @param {Object} source - The source object to merge from.
 * @return {Object} The merged object.
 */
const deepMerge = ( target, source ) => {
	// If source is not an object, return target unchanged.
	if ( ! source || ! isObject( source ) ) {
		return target;
	}

	// If target is not an object or is an array when source is an object (not array),
	// replace target with an empty object to ensure proper merging.
	if ( ! target || ! isObject( target ) || ( isArray( target ) && ! isArray( source ) ) ) {
		target = {};
	}

	// Iterate through all keys in source.
	for ( const key in source ) {
		// Even if source[key] is undefined, we should still apply it.
		// This ensures that undefined values can explicitly override existing values.
		// Handle null values - directly assign null.
		if ( source[ key ] === null ) {
			target[ key ] = null;
			continue;
		}

		// Special case: If types don't match (array vs object), replace completely.
		// This prevents errors when trying to merge incompatible data structures.
		if (
			( isArray( target[ key ] ) && ! isArray( source[ key ] ) ) ||
			( ! isArray( target[ key ] ) && isArray( source[ key ] ) )
		) {
			target[ key ] = source[ key ];
			continue;
		}

		// Handle objects (but not arrays or dates) - recursively merge.
		if ( source[ key ] && isObject( source[ key ] ) && ! isArray( source[ key ] ) && ! isDate( source[ key ] ) ) {
			// Initialize target key if needed or it's not an object.
			if ( ! target[ key ] || ! isObject( target[ key ] ) ) {
				target[ key ] = {};
			}
			// Recursively merge objects.
			deepMerge( target[ key ], source[ key ] );
		} else {
			// For primitives, arrays, and dates, just assign directly.
			target[ key ] = source[ key ];
		}
	}

	return target;
};

/**
 * Optimized comparison function that filters out functions and undefined values.
 * before performing deep equality check.
 *
 * @since x.x.x
 *
 * @param {Object} current - Current data object
 * @param {Object} updated - Updated data object
 * @return {boolean} True if objects are different, false if same
 */

/**
 * Checks if a responsive breakpoint data object is empty or has no meaningful values.
 *
 * @since x.x.x
 *
 * @param {Object} breakpointData - The breakpoint data to check.
 * @return {boolean} True if the breakpoint data is empty, false otherwise.
 */

/**
 * Checks if the device update from `from` to `to` should be skipped.
 * This is the case when the responsive controls are empty for the target device,
 * or when the initial load is for the desktop device and the default and lg values are the same.
 *
 * @since x.x.x
 *
 * @param {string} from     - The device type to transition from.
 * @param {string} to       - The device type to transition to.
 * @param {Object} controls - The responsive controls object.
 * @return {boolean} True if the device update should be skipped, false otherwise.
 */

/**
 * Enhanced comparison for deeply nested responsive control structures.
 * This version avoids creating intermediate objects for comparison, reducing memory overhead.
 *
 * @since x.x.x
 *
 * @param {*} a - First object
 * @param {*} b - Second object
 * @return {boolean} True if objects are different, false if same
 */
const shouldUpdateResponsiveData = ( a, b ) => {
	// Fast reference check.
	if ( a === b ) {return false;}

	// If one is object and the other is not, they are different.
	if ( typeof a !== 'object' || typeof b !== 'object' || a === null || b === null ) {
		return a !== b;
	}

	const keysA = Object.keys( a );
	const keysB = Object.keys( b );

	// Check for different number of keys, but filter out undefined/function values.
	const cleanKeysA = keysA.filter( ( key ) => ! isUndefined( a[ key ] ) && ! isFunction( a[ key ] ) );
	const cleanKeysB = keysB.filter( ( key ) => ! isUndefined( b[ key ] ) && ! isFunction( b[ key ] ) );

	if ( cleanKeysA.length !== cleanKeysB.length ) {return true;}

	// Check key-value pairs.
	for ( const key of cleanKeysA ) {
		// If key doesn't exist in B or values are different, they are not equal.
		if ( ! has( b, key ) || shouldUpdateResponsiveData( a[ key ], b[ key ] ) ) {
			return true;
		}
	}

	return false;
};

/**
 * Drop undefined leaves and the empty objects they leave behind.
 *
 * See the call site in wrappedSetAttributes(): a cleared control sends an
 * explicit `undefined` leaf, and writing it verbatim serialises hollow groups
 * (`typography: {}`) into a viewport state forever.
 *
 * @since x.x.x
 *
 * @param {Object} obj - Object to prune, mutated in place.
 * @return {void}
 */
const pruneHollowValues = ( obj ) => {
	if ( ! isObject( obj ) ) {
		return;
	}

	Object.keys( obj ).forEach( ( key ) => {
		if ( undefined === obj[ key ] ) {
			delete obj[ key ];
			return;
		}

		if ( isObject( obj[ key ] ) ) {
			pruneHollowValues( obj[ key ] );

			if ( ! Object.keys( obj[ key ] ).length ) {
				delete obj[ key ];
			}
		}
	} );
};

/**
 * Turn every emptied leaf inside a `style` patch into an explicit `undefined`.
 *
 * Number and unit controls send `''` when their field is emptied, and the
 * clear-as-deletion path downstream fires only on `undefined`. The top-level
 * conversion beside `clearableKeys` catches a block's own flat keys, but a core
 * control's value is NESTED: emptying padding-top at Tablet arrives as
 * `{ style: { spacing: { padding: { top: '' } } } }`. That `''` was merged as
 * though it were a value, and `pruneHollowValues()` drops only `undefined`, so
 * the state kept a hollow override — `style['@tablet'].spacing.padding.top = ''`
 * — instead of losing the override entirely.
 *
 * It also split the editor from the site: PHP tests values with
 * `has_actual_value()`, which reads `''` as absent and inherits the base, while
 * the editor's own overlay resolves the state over the base and showed the empty
 * value. The site was right and the panel was wrong.
 *
 * Nothing is mutated in place: a node with an emptied leaf below it is rebuilt,
 * and a node with none is returned as it came, so an untouched group keeps its
 * identity all the way up. Called only on the routed `style` groups — see `clearEmptiedStyleLeaves()` for why the walk stops
 * there — so a block's text attributes, where an empty string is a legitimate
 * value, are untouched.
 *
 * @since x.x.x
 * @param {*} value A node of the incoming `style` patch.
 * @return {*} The node with its emptied leaves replaced by `undefined`.
 */
const emptiedLeavesAsUndefined = ( value ) => {
	if ( '' === value ) {
		return undefined;
	}

	if ( ! isObject( value ) || isArray( value ) || isDate( value ) ) {
		return value;
	}

	const next = {};
	let changed = false;

	Object.keys( value ).forEach( ( key ) => {
		next[ key ] = emptiedLeavesAsUndefined( value[ key ] );
		changed = changed || next[ key ] !== value[ key ];
	} );

	return changed ? next : value;
};

/**
 * Apply that rule to the `style` groups this extension actually routes.
 *
 * The walk is scoped to `STYLE_RESPONSIVE_KEYS` because those are the only
 * groups a viewport state can hold — `remove_conflicting_core_attributes()`
 * draws the same line in PHP. Everywhere else in `style` an empty string is
 * nobody's deletion signal: it is a value core wrote and core reads back, and
 * turning it into `undefined` here would hand the block a deletion its own
 * control never asked for. `style.background.backgroundImage.title` is the
 * plain case — an untitled image legitimately carries `''`.
 *
 * Returns the same object when nothing changed, so an unrelated `style` write
 * does not become a new object and does not perturb the identity checks
 * downstream.
 *
 * @since x.x.x
 * @param {Object} style The incoming `style` patch.
 * @return {Object} The patch with its routed groups' emptied leaves cleared.
 */
const clearEmptiedStyleLeaves = ( style ) => {
	let next = style;

	STYLE_RESPONSIVE_KEYS.forEach( ( group ) => {
		if ( ! ( group in style ) ) {
			return;
		}

		const cleared = emptiedLeavesAsUndefined( style[ group ] );

		if ( cleared !== style[ group ] ) {
			next = next === style ? { ...style } : next;
			next[ group ] = cleared;
		}
	} );

	return next;
};

/**
 * Resolves mutually exclusive attribute pairs in responsive controls.
 * When a preset value is set, the corresponding custom value is removed and vice versa.
 * This handles WordPress's pattern of having preset values (like color.text) and
 * custom values (like style.color.text) that should not coexist.
 *
 * @since x.x.x
 *
 * @param {Object} target - The target object to modify.
 * @param {Object} source - The source object containing new values.
 * @param {Array}  pairs  - Array of mutually exclusive attribute pairs [presetKey, customPath].
 */
const resolveMutualExclusivity = ( target, source, pairs ) => {
	pairs.forEach( ( [ presetKey, customPath ] ) => {
		/*
		 * A key can be PRESENT with an undefined value — core's patches carry
		 * registered attributes that way, and a routed clear adds the key
		 * deliberately. Presence alone is not the user "setting" that side of
		 * the pair: reading it as such deleted a block's custom font size on
		 * every colour change, because the patch merely mentioned `fontSize`.
		 * Only an actual value counts.
		 */
		const presetPathExists = hasValue( getNested( source, presetKey ) );
		const customPathExists = hasValue( getNested( source, customPath ) );

		// Handle layout conflicts: When source and target both have layout settings but they differ,
		// remove the layout from target to prevent conflicting layout configurations.
		if ( has( source, 'layout' ) && has( target, 'layout' ) && source.layout !== target.layout ) {
			deleteNested( target, 'layout' );
		}

		// Handle spacing inheritance: If target has padding/margin but source doesn't,
		// remove it from target to allow inheritance from parent devices.
		// Only do this if we're in the context of style spacing updates.
		if ( has( source, 'style' ) && has( source, 'style.spacing' ) ) {
			// If target has padding but source.style.spacing exists without padding, remove padding from target.
			if ( has( target, 'style.spacing.padding' ) && ! has( source, 'style.spacing.padding' ) ) {
				deleteNested( target, 'style.spacing.padding' );
			}
			// If target has margin but source.style.spacing exists without margin, remove margin from target.
			if ( has( target, 'style.spacing.margin' ) && ! has( source, 'style.spacing.margin' ) ) {
				deleteNested( target, 'style.spacing.margin' );
			}
			// If target has blockGap but source.style.spacing exists without blockGap, remove blockGap from target.
			if ( has( target, 'style.spacing.blockGap' ) && ! has( source, 'style.spacing.blockGap' ) ) {
				deleteNested( target, 'style.spacing.blockGap' );
			}
		}

		// Handle border style conflicts: When source has a visible border width (greater than 0)
		// but target still has border style set to 'none', remove the 'none' style from target
		// to allow the border to be visible with the specified width.
		if (
			has( source, 'style.border.width' ) &&
			Number.parseInt( source.style.border.width, 10 ) > 0 &&
			has( target, 'style.border.style' ) &&
			target.style.border.style === 'none'
		) {
			deleteNested( target, 'style.border.style' );
		}

		// Handle border type conflicts: When setting one type of border, remove the other type.
		if ( has( source, 'style.border' ) ) {
			// Check if source is setting single border properties.
			const sourceHasSingle =
				has( source, 'style.border.width' ) ||
				has( source, 'style.border.style' ) ||
				has( source, 'style.border.color' ) ||
				hasValue( source?.borderColor );

			// Check if source is setting mixed border properties.
			const sourceHasMixed =
				has( source, 'style.border.top' ) ||
				has( source, 'style.border.right' ) ||
				has( source, 'style.border.bottom' ) ||
				has( source, 'style.border.left' );

			if ( sourceHasSingle && has( target, 'style.border' ) ) {
				// Source is setting single borders, remove any mixed borders from target.
				deleteNested( target, 'style.border.top' );
				deleteNested( target, 'style.border.right' );
				deleteNested( target, 'style.border.bottom' );
				deleteNested( target, 'style.border.left' );
			} else if ( sourceHasMixed && has( target, 'style.border' ) ) {
				// Source is setting mixed borders, remove any single borders from target.
				deleteNested( target, 'style.border.width' );
				deleteNested( target, 'style.border.style' );
				deleteNested( target, 'style.border.color' );
				// Also remove borderColor preset if it exists.
				if ( has( target, 'borderColor' ) ) {
					delete target.borderColor;
				}
			}
		}

		if ( presetPathExists ) {
			// User is setting a preset → remove custom.
			deleteNested( target, customPath );
			if ( 'borderColor' === presetKey && undefined === source.borderColor ) {
				// If style.border not exists on source but if exists on target, remove it.
				if ( ! has( source, 'style.border' ) && has( target, 'style.border' ) ) {
					deleteNested( target, 'style.border' );
				}
			}
		} else if ( customPathExists ) {
			// User is setting a custom → remove preset.
			delete target[ presetKey ];
		} // else neither exists, no action needed.
	} );
};

/**
 * Whether a value is a plain object (not null, not an array).
 *
 * @since x.x.x
 * @param {*} value The value.
 * @return {boolean} True for plain objects.
 */
const isPlainObjectValue = ( value ) => Boolean( value ) && 'object' === typeof value && ! Array.isArray( value );

/**
 * Checks if a value is set (not null or empty string), and if it is an object,
 * recursively checks if any of its properties have a value.
 *
 * This is used to determine if a property should be considered as having.
 * a meaningful value that should be applied to the block.
 *
 * @since x.x.x
 *
 * @param {*} value - The value to check.
 * @return {boolean} True if value is set, false otherwise.
 */
export const hasValue = ( value ) => {
	// Check for null/undefined/empty string.
	if ( isNil( value ) || value === '' ) {return false;}

	// Handle objects and arrays.
	if ( isObject( value ) ) {
		// For arrays, check if they have any elements.
		if ( isArray( value ) ) {return value.length > 0;}
		// For objects, check if any property has a value.
		return Object.keys( value ).length > 0 && Object.values( value ).some( ( v ) => hasValue( v ) );
	}

	// All other values are considered valid.
	return true;
};

/**
 * Whether a reset wrote the attribute's DEFAULT instead of clearing it.
 *
 * `processReset()` learns what the author reset by diffing the block before and
 * after core's reset ran, and calls a property reset when it HAD a value and no
 * longer does. That misses every control whose `onDeselect` writes a sentinel
 * rather than `undefined`. The Container's overlay is one:
 *
 *     onDeselect={ () => setAttributes( { overlayType: 'none' } ) }
 *
 * `hasValue( 'none' )` is true, so `overlayType: 'image' → 'none'` looked like
 * an ordinary edit, the path was never added to the reset list, and
 * `style.overlayType` kept the image. Root said "none" while the authoritative
 * layer said "image": the overlay went on painting, and Reset looked broken —
 * on the canvas and, once saved, on the site.
 *
 * The reset target is the attribute's registered default, so that is what this
 * compares against. Top-level attributes only: nested style paths have no
 * defaults to write.
 *
 * @since 1.0.7
 * @param {string} blockName   The block name.
 * @param {string} key         Attribute name.
 * @param {*}      beforeValue The value before the reset.
 * @param {*}      afterValue  The value after it.
 * @return {boolean} True when the reset replaced a value with its default.
 */
export const resetWroteTheDefault = ( blockName, key, beforeValue, afterValue ) => {
	if ( ! blockName || ! key || ! hasValue( beforeValue ) ) {
		return false;
	}

	const defaultValue = getBlockType( blockName )?.attributes?.[ key ]?.default;

	if ( undefined === defaultValue || isObject( defaultValue ) ) {
		return false;
	}

	return afterValue === defaultValue && beforeValue !== defaultValue;
};

/**
 * Guarantee a layout object carries a `type`.
 *
 * `attributes.layout` is what WordPress core's editor layout support renders the
 * canvas from, and it resolves the layout type like this:
 *
 *     const usedLayout = … : layout || defaultBlockLayout || {};
 *     getLayoutType( usedLayout?.type || 'default' )
 *
 * The `defaultBlockLayout` fallback only fires when `layout` is absent entirely.
 * A PARTIAL layout is therefore worse than none: `{ justifyContent: 'right' }` is
 * truthy, so core uses it as-is, finds no `type`, and falls back to the `default`
 * (flow) layout — which has no justification at all. The block silently stops
 * being a flex container in the editor.
 *
 * Core's own controls do write partial objects: on a block whose `supports.layout`
 * sets `allowSwitching: false` there is no type control, so `type` never comes
 * from the panel. Core gets away with it because it merges over
 * `supports.layout.default`; anything that writes `attributes.layout` here has to
 * do the same.
 *
 * Mirrors the fallback merge in `ResponsiveControls::generate_layout_css()`, so the
 * editor canvas and the front end resolve a layout the same way.
 *
 * @since 1.0.7
 *
 * @param {Object} layout    A layout object, possibly partial.
 * @param {string} blockName The block name, for its `supports.layout.default`.
 * @return {Object} The layout, with `type` and any other default properties filled in.
 */
export const withLayoutType = ( layout, blockName ) => {
	if ( ! isObject( layout ) || layout.type ) {
		return layout;
	}

	const layoutSupport = getBlockSupport( blockName, 'layout' );
	const defaultLayout = isObject( layoutSupport?.default ) ? layoutSupport.default : null;

	if ( ! defaultLayout?.type ) {
		return layout;
	}

	return { ...defaultLayout, ...layout };
};


/**
 * Extracts responsive attributes from a given set of attributes.
 * This function is used to separate responsive-specific attributes.
 * from regular block attributes based on the block type.
 *
 * @since x.x.x
 *
 * @param {Object} attributes - The attributes to extract from.
 * @param {string} blockName  - The name of the block.
 * @return {Object} Object containing only responsive attributes.
 */
export const extractResponsiveAttributes = ( attributes, blockName ) => {
	// Performance hack: Cache expensive lookups and use sets for faster includes()
	const blockResponsiveKeys = getBlockResponsiveKeys( blockName );
	const blockResponsiveKeysSet = new Set( blockResponsiveKeys ); // O(1) lookup vs O(n)
	const responsiveAttrs = {};

	// Performance hack: Handle style separately first (most common case)
	const style = attributes.style;
	if ( style && typeof style === 'object' ) {
		const filteredStyle = {};
		// Direct property access instead of forEach for better performance.
		for ( const styleKey of STYLE_RESPONSIVE_KEYS ) {
			if ( styleKey in style ) {
				if ( styleKey === 'border' && typeof style.border === 'object' ) {
					filteredStyle.border = style.border;
				} else {
					filteredStyle[ styleKey ] = style[ styleKey ];
				}
			}
		}
		if ( Object.keys( filteredStyle ).length > 0 ) {
			responsiveAttrs.style = filteredStyle;
		}
	}

	/*
	 * Process other attributes with fast Set lookup.
	 *
	 * An explicitly-undefined value is carried through, because a DELETION is
	 * as responsive as a value. It used to be dropped here and left to the
	 * verbatim pass-through, on the reasoning that clearing a control should
	 * clear the attribute — which is true at Desktop, where the root attribute
	 * IS the base layer, and wrong at every other device, where the value lives
	 * in a viewport state.
	 *
	 * Dropped, the deletion never reached the router: the patch looked
	 * non-responsive, the write loop never ran, and the state kept the old
	 * value while the root cleared and the control went empty. Measured on 7.1,
	 * emptying a Container's Height at Tablet — `root=300px base=undefined
	 * tablet=300px` became `root=undefined base=undefined tablet=300px`, so the
	 * site kept a height the panel no longer showed and emptying the field
	 * again could not remove it.
	 *
	 * Carried through, `deepMerge()` keeps the undefined leaf, `pruneHollowValues()`
	 * drops it, and `writeBucketToStyle()` then sees the key as absent and
	 * deletes it from the state — the path a cleared control was always meant
	 * to take. `Object.entries()` only yields keys the patch actually carries,
	 * so an attribute nobody touched is still never mentioned.
	 */
	for ( const [ key, value ] of Object.entries( attributes ) ) {
		if ( key !== 'style' && blockResponsiveKeysSet.has( key ) ) {
			responsiveAttrs[ key ] = value;
		}
	}

	return responsiveAttrs;
};

/**
 * ===================================================================
 * BLOCK VALIDATION
 * ===================================================================
 *
 * Functions in this section determine which blocks should have.
 * responsive controls applied and what responsive attributes
 * are available for each block type.
 */

/**
 * Determines if a block should have responsive controls applied.
 *
 * Checks if the block is in the supported list or has an allowed prefix,
 * and ensures it's not in the excluded blocks list.
 *
 * @since x.x.x
 *
 * @param {Object} block - The block object containing at least a name property.
 * @return {boolean} True if the block should have responsive controls, false otherwise.
 */
export const isAllowedBlock = ( block ) => {
	// Fast path: return false for invalid blocks.
	if ( ! block?.name ) {return false;}

	// Check if block is excluded first.
	if ( EXCLUDED_BLOCKS.includes( block.name ) ) {
		return false;
	}

	// Check if block is allowed via prefix or direct inclusion.
	return (
		ALLOWED_PREFIXES.some( ( prefix ) => block.name.startsWith( prefix ) ) ||
		SUPPORTED_BLOCKS.includes( block.name )
	);
};

/**
 * Mirror base-layer flat values from `style` into the root attributes at parse.
 *
 * The system's canonical storage for flat responsive keys is `style` (its root
 * for the base layer, the viewport states for narrower devices) — but every
 * block-local reader, and core's own preset controls (`fontSize`), read the
 * ROOT ATTRIBUTE. Content migrated from the legacy store, or saved by the
 * current editor after a device-routed edit, holds those values only inside
 * `style`, so a 7.0 user opening their post saw text shadow (and any other
 * flat setting) presented as OFF while the front end rendered it fine.
 *
 * Runs on `blocks.getBlockAttributes`: parsing is not a change, so the post is
 * not marked dirty. Only fills attributes the block does not already carry —
 * an existing root value is the editing session's scratch and stays.
 *
 * @since x.x.x
 * @param {Object} attributes Block attributes as parsed from post content.
 * @param {Object} blockType  The block type being parsed.
 * @return {Object} Attributes with base flat values mirrored to the root.
 */
export const mirrorBaseValuesToAttributes = ( attributes, blockType ) => {
	const name = blockType?.name || '';

	if ( ! name || ! isAllowedBlock( { name } ) ) {
		return attributes;
	}

	const style = attributes?.style;

	if ( ! isObject( style ) ) {
		return attributes;
	}

	let next = attributes;

	getBlockResponsiveKeys( name ).forEach( ( key ) => {
		if ( 'layout' === key || 'style' === key ) {
			return;
		}

		if ( undefined === next[ key ] && undefined !== style[ key ] ) {
			if ( next === attributes ) {
				next = { ...attributes };
			}
			next[ key ] = style[ key ];
		}
	} );

	return next;
};

/**
 * Gets the block-specific responsive keys based on block name.
 * Different block types may have different responsive attributes.
 *
 * @since x.x.x
 *
 * @param {string}  blockName      - The name of the block.
 * @param {boolean} onlyBlockAttrs - Whether to return only block-specific responsive keys.
 * @return {Array} Array of responsive keys for the specific block type.
 */
export const getBlockResponsiveKeys = ( blockName, onlyBlockAttrs = false ) => {
	// If no blockName provided, return default keys or empty array.
	if ( ! blockName ) {return onlyBlockAttrs ? [] : RESPONSIVE_KEYS;}

	// Get block-specific responsive keys from the mapping.
	let blockSpecificKeys = BLOCK_RESPONSIVE_KEYS[ blockName ] || [];

	/**
	 * Filter to allow external plugins (especially Spectra Pro) to add block-specific responsive keys.
	 *
	 * This filter enables Spectra Pro plugin to register additional responsive attributes
	 * for premium blocks that extend the base responsive control system.
	 *
	 * @since x.x.x
	 *
	 * @param {Array<string>} blockSpecificKeys Array of responsive attribute keys for this block type.
	 * @param {string}        blockName         The block name (e.g., 'spectra/advanced-heading').
	 *
	 * @return {Array<string>} Modified array of responsive keys including Pro block attributes.
	 */
	blockSpecificKeys = applyFilters(
		'spectra.responsive-controls.block-responsive-keys',
		blockSpecificKeys,
		blockName
	);

	// Return only block-specific attributes if requested.
	if ( onlyBlockAttrs ) {
		return blockSpecificKeys;
	}

	// Otherwise return combined keys (common responsive keys + block-specific keys).
	return [ ...RESPONSIVE_KEYS, ...blockSpecificKeys ];
};

/**
 * Whether a patch does nothing but clear this block's own flat keys.
 *
 * The shape a Spectra control's "Reset" sends: one or more of the block's flat
 * attributes set to `undefined`, and nothing else. It is deliberately strict —
 * a `style` group, a sibling attribute or a real value alongside means the
 * patch is doing something a reset router should not be guessing at, and it
 * takes the ordinary path instead.
 *
 * @since 1.0.7
 * @param {Object} newAttributes The patch handed to `setAttributes()`.
 * @param {string} blockName     The block name.
 * @return {boolean} True when every key in the patch is a flat key being cleared.
 */
export const clearsOnlyFlatKeys = ( newAttributes, blockName ) => {
	if ( ! isObject( newAttributes ) ) {
		return false;
	}

	const keys = Object.keys( newAttributes );

	if ( ! keys.length ) {
		return false;
	}

	const flatKeys = new Set( getBlockResponsiveKeys( blockName, true ) );

	return keys.every( ( key ) => flatKeys.has( key ) && undefined === newAttributes[ key ] );
};

/**
 * ===================================================================
 * ATTRIBUTE MANAGEMENT
 * ===================================================================
 *
 * Functions for extending block attributes with responsive control.
 * capabilities. This section handles the registration of responsive
 * attributes in block settings.
 */

/**
 * Extend block attributes with the identifier the CSS generator targets.
 *
 * `spectraId` is permanent — every generated rule is scoped by it, on every
 * WordPress version and in both storage models.
 *
 * The `responsiveControls` store is registered separately, by `../legacy/`. It
 * is not permanent: it is the ACTIVE store on a WordPress without core's
 * viewport states and a legacy input where they exist, so it outlives this
 * extension's current shape but not the plugin. Keeping its registration beside
 * the code that owns it means removing that folder removes the attribute — and,
 * just as important, that it CANNOT be removed sooner, because an attribute the
 * parser does not know about is dropped from post content on load. Anything that
 * deletes this registration while a single post still carries the store deletes
 * that post's per-device values.
 *
 * @since x.x.x
 *
 * @param {Object} settings - The block settings object.
 * @param {string} name     - The block name.
 * @return {Object} Modified settings with the Spectra identifier attribute.
 */
export const extendBlockAttributes = ( settings, name ) => {
	// Skip blocks that shouldn't have responsive controls.
	if ( ! isAllowedBlock( { name } ) ) {return settings;}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			spectraId: {
				type: 'string',
			},
		},
	};
};

/**
 * ===================================================================
 * RESPONSIVE VALUE HANDLING
 * ===================================================================
 *
 * Functions for mapping device types to CSS breakpoint identifiers.
 * This section handles the translation between WordPress device types.
 * and the corresponding CSS breakpoint types used in the stylesheet.
 */

/**
 * Maps a device type to its corresponding breakpoint type.
 * Converts WordPress device types to CSS breakpoint identifiers.
 *
 * @since x.x.x
 *
 * @param {string} deviceType - The device type (e.g., 'Desktop', 'Tablet').
 * @return {string} The breakpoint type (e.g., 'base', '@tablet', '@mobile').
 */
const getBreakpointType = ( deviceType ) => BREAKPOINT_TYPE_MAP[ deviceType ] || 'base';

/**
 * ===================================================================
 * ATTRIBUTE MERGING
 * ===================================================================
 *
 * Functions for merging responsive attributes into base attributes.
 * This section handles the inheritance of values across breakpoints.
 * and resolves conflicts between mutually exclusive attribute pairs.
 *
 * CSS variables are generated based on these merged attributes following.
 * the naming convention --spectra-attribute-name.
 */

/**
 * ===================================================================
 * HIGHER-ORDER COMPONENTS
 * ===================================================================
 *
 * Components that wrap block edit components to add responsive control.
 * functionality. These HOCs handle attribute storage, device preview
 * updates, and CSS variable generation following the naming convention
 * --spectra-attribute-name for variables and spectra-attribute-name for
 * CSS selectors.
 */

/**
 * Higher-order component that adds responsive control handling to block edit components.
 *
 * Intercepts setAttributes calls to store responsive attributes in the.
 * responsiveControls object for the current breakpoint.
 *
 * @since x.x.x
 */
export const withResponsiveControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { attributes, setAttributes, name } = props;
		const { responsiveControls = {}, spectraId } = attributes;

		// Determine if responsive controls should be applied to this block.
		const isResponsive = isAllowedBlock( { name } );

		// If not responsive, return the original BlockEdit component.
		if ( ! isResponsive ) {
			return <BlockEdit { ...props } />;
		}

		// Simple ID generation - just ensure new blocks get an ID.
		// Duplicate handling will be done server-side in PHP.
		useEffect( () => {
			// Only generate ID if it doesn't exist (new block).
			if ( ! spectraId ) {
				const timestamp = Date.now().toString( 36 );
				const random = Math.random().toString( 36 ).substring( 2, 8 );
				setAttributes( { spectraId: `spectra-${ timestamp }-${ random }` } );
			}
		}, [] ); // Run once on mount.

		// Get the current device type from the editor.
		const deviceType = useSelect( ( select ) => select( 'core/editor' )?.getDeviceType?.() || DESKTOP, [] );

		/*
		 * Whether core is actually editing a viewport state, not merely
		 * previewing one. See ./use-responsive-editing.js for why this cannot be
		 * a selector.
		 */
		const { isResponsiveEditingActive, ResponsiveEditingProbes } =
			useResponsiveEditing( deviceType, props.clientId );

		/*
		 * The layer this block's edits belong to.
		 *
		 * BOTH signals decide it, never the device alone. With Responsive styles
		 * OFF the device switcher is a PREVIEW: core writes every edit to the
		 * base layer, so routing by device sent root edits into
		 * `style['@tablet']` while core put its own in the base — the two
		 * disagreeing about the same edit. Core's Typography panel was the
		 * clearest casualty on `spectra/button`, which has no typography
		 * attributes of its own: changing a font size while previewing Tablet
		 * looked inert on the canvas, because core renders state CSS there only
		 * when the mode is on, and left behind a tablet override the user never
		 * asked for and could not see.
		 *
		 * Desktop needs no special case — `getBreakpointType()` already maps it
		 * to 'base'.
		 *
		 * This governs Spectra's OWN controls too, which is the part with a
		 * user-visible consequence. The device buttons in a Spectra panel header
		 * no longer decide storage by themselves: with Responsive styles off,
		 * picking Tablet there previews tablet and edits the base, exactly as it
		 * does for a core control. Per-device values require the mode to be on.
		 * That is the alignment, not a side effect of it — a control that stores
		 * per-device while core stores globally is the bug — and it is what the
		 * "Turn On" hint in control-injection.js exists to shorten.
		 */
		const breakpoint = useMemo( () => {
			/*
			 * Below 7.1 the device is the only signal there is.
			 *
			 * Those versions have no viewport style states and no Responsive
			 * styles mode, so Spectra's own device buttons ARE the mechanism and
			 * routing by device is correct. `isResponsiveEditingActive` is false
			 * there — necessarily, since the mode does not exist — and treating
			 * that false as "editing the base" would send every per-device edit
			 * to the base layer and break responsive controls outright on every
			 * pre-7.1 site.
			 *
			 * Capability-checked rather than version-checked: PHP measures core
			 * once and exports the answer, so a 7.0 site running the Gutenberg
			 * plugin is treated as having the states it actually has.
			 */
			if ( ! coreViewportStatesAreIndependent() ) {
				return getBreakpointType( deviceType );
			}

			return isResponsiveEditingActive ? getBreakpointType( deviceType ) : 'base';
		}, [ deviceType, isResponsiveEditingActive ] );

		/*
		 * Keep the ROOT attributes pointed at the previewed device.
		 *
		 * `fontSize`, `fontFamily` and `borderColor` are responsive properties core
		 * reads from a block ATTRIBUTE rather than from `style`. Nothing updated
		 * those on a device switch, so a per-device value was stored and rendered
		 * correctly but invisible in the editor: at Tablet the Font Size control
		 * read the root `fontSize`, still on the base preset, while
		 * `style['@tablet'].fontSize` held the tablet one.
		 *
		 * These three only mis-DISPLAY, and PHP strips all of them before render
		 * (they are listed in `$core_attributes`), so they are scratch by design —
		 * as the router below says. The write is marked not-persistent, so it adds
		 * no undo step and no dirty post, and the stored `style[state]` is never
		 * touched.
		 *
		 * `layout` is NOT here, and that is the point. Core needs no help with it:
		 * measured on 7.1, core reads `style[state].layout` itself for BOTH the
		 * canvas and its own Layout panel, so a block whose `@mobile` layout
		 * justifies center is drawn centred at Mobile and its Justification control
		 * reads center — with the root attribute left at the authored base.
		 *
		 * Writing it was actively harmful. `layout` is the one member of
		 * `BUCKET_TOP_LEVEL_STYLE_KEYS` that PHP keeps for `spectra/container`
		 * (`remove_conflicting_core_attributes()`), so scratch written there reached
		 * the front end — and because a save serialises the whole block tree, editing
		 * any one block persisted the previewed device's layout as the BASE of every
		 * container on the page, in blocks the author never touched. Marking the
		 * change not-persistent does not help: that keeps it out of the undo history,
		 * not out of the post.
		 *
		 * Note `setAttributes` here is the RAW prop, not `wrappedSetAttributes`
		 * (defined below). Routing this patch would write scratch back into
		 * storage, which is the bug it exists to avoid.
		 *
		 * Resolution follows core's model: the state over the base, base alone at
		 * Desktop.
		 */
		const { __unstableMarkNextChangeAsNotPersistent } = useDispatch( 'core/block-editor' );

		useEffect( () => {
			const stateKey = DEVICE_TO_STYLE_STATE[ breakpoint ];
			const base = attributes?.style;
			const state = stateKey ? attributes?.style?.[ stateKey ] : undefined;

			// Nothing in the store to follow: the root attributes are core's.
			if ( ! isObject( base ) && ! isObject( state ) ) {
				return;
			}

			const patch = {};

			/*
			 * `layout` is deliberately absent — core reads its own viewport states
			 * for it, and writing scratch there corrupted saved content. See the
			 * note above and `SCRATCH_ROOT_ATTRIBUTE_KEYS`.
			 */
			SCRATCH_ROOT_ATTRIBUTE_KEYS.forEach( ( key ) => {
				/*
				 * The rest are scalar presets, so a state's value replaces the
				 * base's rather than merging with it. Each also has a NESTED
				 * counterpart in the same layer — a `var:preset|…` reference, or
				 * a custom value — and the two are mutually exclusive: when the
				 * layer that wins carries the nested one, the root attribute has
				 * to be CLEARED, or core resolves the leftover preset and
				 * out-specifies what the state actually says. That clear is also
				 * what makes the migration's output readable to core's panels,
				 * since inside a state core reads only the nested form.
				 */
				const presetRef = ROOT_ATTRIBUTE_PRESET_REFS[ key ];
				const customPath = presetRef?.path;

				/**
				 * The slug inside a `var:preset|<namespace>|<slug>` reference.
				 *
				 * @param {*} value The stored nested value.
				 * @return {string|undefined} The slug, or `undefined` when the value
				 *                            is anything else — i.e. custom.
				 */
				const slugFromReference = ( value ) => {
					if ( ! presetRef || 'string' !== typeof value ) {
						return undefined;
					}

					const prefix = `var:preset|${ presetRef.preset }|`;

					return value.startsWith( prefix ) ? value.slice( prefix.length ) : undefined;
				};

				const readLayer = ( layer ) => ( {
					preset: isObject( layer ) ? layer[ key ] : undefined,
					custom: customPath && isObject( layer ) ? getNested( layer, customPath ) : undefined,
				} );

				const fromState = readLayer( state );
				const fromBase = readLayer( base );

				let resolved;

				/*
				 * A nested `var:preset|…` reference RESOLVES to its slug rather than
				 * clearing the root attribute, and only a genuinely custom value
				 * clears it.
				 *
				 * Clearing unconditionally is what shipped, and it broke the mode it
				 * was not measured in. Core's panels bind to the previewed STATE
				 * while "Responsive styles" is on and to the BASE layer while it is
				 * off, so with it off the root attribute is the only thing they read.
				 * Since the migration writes states in the nested form, clearing left
				 * the Font Size control EMPTY at Tablet and Mobile on migrated
				 * content. Resolving to the slug is what keeps that control filled.
				 *
				 * The toggle is still not a trigger, and cannot be: it is a DOM class
				 * that flips with no attribute change, so this effect does not re-run
				 * on it — measured, the root attribute stays on its previous value
				 * until a device switch or an edit runs the effect again. What the
				 * gate below uses is `breakpoint`, which already encodes the toggle
				 * and is correct whenever the effect DOES run. That leaves a window
				 * where these attributes are stale after a bare toggle; it predates
				 * the gate and is tracked separately.
				 */
				/*
				 * The root attribute mirrors the BASE layer, never a state's.
				 *
				 * These three are single, viewport-less block attributes, and core
				 * renders them in the canvas from the attribute alone — `fontFamily`
				 * as `has-<slug>-font-family`, `fontSize` as `has-<slug>-font-size`.
				 * Putting a STATE's value there therefore paints every viewport with
				 * it: setting a font on Mobile changed Desktop too, while core's own
				 * blocks left Desktop alone. Measured on `spectra/content` against
				 * `core/paragraph` with identical input — Spectra wrote
				 * `fontFamily: 'inter'`, core wrote nothing.
				 *
				 * With "Responsive styles" ON there is nothing to gain from writing
				 * it: core's panels bind to the previewed STATE and read the nested
				 * `var:preset|…` form, which this never touches, so the control still
				 * shows the state's value with the attribute empty.
				 *
				 * With the option OFF, `breakpoint` is `base` — as it is at Desktop in
				 * either mode — so `state` IS the base layer and the resolution below
				 * is unchanged. That preserves what this code was written for: core's
				 * panels read only the root attribute in that mode, and on migrated
				 * content, whose states hold the nested form, clearing it left the
				 * Font Size control empty at Tablet and Mobile.
				 *
				 * Clearing rather than leaving a stale value is deliberate — it is
				 * what lets content that already carries a leaked attribute correct
				 * itself the next time the block renders.
				 *
				 * `layout` is excluded, and handled above: it is merged base-over-
				 * state on purpose, because the canvas draws flex and grid from
				 * `attributes.layout` and a state-less merge would stop the previewed
				 * device laying out correctly.
				 *
				 * Capability-gated as well as breakpoint-gated. Below 7.1 `breakpoint`
				 * is the previewed DEVICE with no toggle in the picture, so a bare
				 * `breakpoint !== 'base'` would fire at Tablet and Mobile there — and
				 * since those sites keep per-device values in `responsiveControls`
				 * rather than in `style` states, the base layer read here is usually
				 * empty and the clear below would wipe a root attribute the classic
				 * path is still using. Requiring core's viewport states leaves
				 * pre-7.1 behaviour exactly as it was.
				 */
				const mirrorsBaseOnly = coreViewportStatesAreIndependent() && 'base' !== breakpoint;

				if ( ! mirrorsBaseOnly && hasValue( fromState.preset ) ) {
					resolved = fromState.preset;
				} else if ( ! mirrorsBaseOnly && hasValue( fromState.custom ) ) {
					resolved = slugFromReference( fromState.custom );
				} else if ( hasValue( fromBase.preset ) ) {
					resolved = fromBase.preset;
				} else if ( hasValue( fromBase.custom ) ) {
					resolved = slugFromReference( fromBase.custom );
				} else if ( mirrorsBaseOnly ) {
					// Base says nothing, so neither may the attribute — otherwise a
					// value the author set for one viewport keeps painting the rest.
					resolved = undefined;
				} else {
					// Neither layer stores this key, so whatever core put in the
					// root attribute is the only value there is. Leave it.
					return;
				}

				if ( attributes?.[ key ] !== resolved ) {
					patch[ key ] = resolved;
				}
			} );

			if ( ! Object.keys( patch ).length ) {
				return;
			}

			__unstableMarkNextChangeAsNotPersistent();
			setAttributes( patch );
			// The deps are the exact reads above. Listing `attributes` whole, as
			// the rule wants, would re-run this on every unrelated edit.
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [ breakpoint, attributes?.style, attributes?.layout, attributes?.fontSize, attributes?.fontFamily, attributes?.borderColor, name, setAttributes, __unstableMarkNextChangeAsNotPersistent ] );

		// Block-specific attributes sit flat inside a state; the shared style
		// groups nest by name. The store helpers translate between the two.
		const flatKeys = useMemo( () => getBlockResponsiveKeys( name, true ), [ name ] );

		/*
		 * `style` is the source of truth. `responsiveControls` is only read so
		 * that posts saved before the move still open with their values intact;
		 * anything `style` declares wins, because that is what the current
		 * editor wrote.
		 */
		const currentData = useMemo(
			() => mergeBuckets( readLegacyBucket( responsiveControls, breakpoint ), readBucketFromStyle( attributes?.style, breakpoint, flatKeys ) ),
			[ responsiveControls, attributes?.style, breakpoint, flatKeys ]
		);

		/**
		 * Custom setAttributes function that handles responsive attributes.
		 *
		 * Separates responsive and non-responsive attributes and stores.
		 * responsive ones in the responsiveControls object.
		 */
		const wrappedSetAttributes = useCallback(
			( newAttributes ) => {
				/*
				 * Complete a partial layout before anything else, because three of the
				 * exits below hand `newAttributes` straight to `setAttributes()`.
				 *
				 * Core's Layout panel writes only the properties it owns, and on a block
				 * whose `supports.layout` sets `allowSwitching: false` there is no type
				 * control — so `type` never comes from the panel. Core compensates by
				 * merging over `supports.layout.default`; anything writing
				 * `attributes.layout` has to do the same, or core resolves
				 * `layout || default` against a truthy partial object, finds no `type`,
				 * and falls back to the `default` (flow) layout. The block stops being a
				 * flex container in the canvas and the justification control has nothing
				 * to drive.
				 *
				 * Completing it only on the main path left the early exits — reset in
				 * progress, nothing responsive in the change, or a resolved bucket that
				 * did not differ — persisting the raw partial. The last of those fires
				 * routinely, which is why a typeless root kept reappearing.
				 */
				if ( undefined !== newAttributes?.layout ) {
					newAttributes = {
						...newAttributes,
						layout: withLayoutType( newAttributes.layout, name ),
					};
				}

				/*
				 * An emptied control means CLEAR, so say so in the shape the rest of
				 * this function understands.
				 *
				 * Number controls send `''` when the field is emptied, not
				 * `undefined`. The clear-as-deletion path below fires only on an
				 * explicit `undefined`, so `''` was stored as though it were a
				 * value: clearing the Icon size at Tablet wrote
				 * `style['@tablet'].size = ''`, which overrode the base with
				 * nothing instead of removing the override. The panel then showed
				 * an empty field and the canvas fell to the stylesheet default,
				 * while the FRONT END — which tests values with `hasValue()` and
				 * so reads `''` as absent — correctly inherited the base. Editor
				 * and site disagreed, and `"size":""` was persisted into post
				 * content.
				 *
				 * Scoped to the keys whose values are CSS-ish and for which empty
				 * can only mean cleared. Text attributes are untouched, where an
				 * empty string is a legitimate value.
				 */
				const clearableKeys = [ ...getBlockResponsiveKeys( name ), ...BUCKET_TOP_LEVEL_STYLE_KEYS ];
				const emptied = clearableKeys.filter(
					( key ) => 'layout' !== key && key in newAttributes && '' === newAttributes[ key ]
				);

				if ( emptied.length ) {
					newAttributes = { ...newAttributes };
					emptied.forEach( ( key ) => {
						newAttributes[ key ] = undefined;
					} );
				}

				/*
				 * The same rule, applied to the nested values core's own controls
				 * write. `clearableKeys` above only sees the patch's top level, so
				 * an emptied padding or font size — `{ style: { spacing: { padding:
				 * { top: '' } } } }` — slipped through and was stored as a hollow
				 * override. See `emptiedLeavesAsUndefined()`.
				 */
				if ( isObject( newAttributes?.style ) ) {
					const clearedStyle = clearEmptiedStyleLeaves( newAttributes.style );

					if ( clearedStyle !== newAttributes.style ) {
						newAttributes = { ...newAttributes, style: clearedStyle };
					}
				}

				/*
				 * Skip responsive controls processing during reset operations —
				 * except for a reset that ONLY clears this block's own flat keys.
				 *
				 * The raw pass-through exists for core's style groups, whose
				 * layers `processReset()` cleans up afterwards by diffing the
				 * block before and after core's reset. That diff reads the block
				 * out of the STORE, and for a flat key the store is not what the
				 * panel was showing: `deviceAttributes` below resolves the key
				 * from `style` for display, so a control can legitimately show
				 * the base value while the root attribute holds nothing. The
				 * reset then writes `undefined` over an already-undefined root,
				 * the diff sees no change, no layer is cleared, and the value the
				 * author just reset is still there — measured on a container
				 * whose Mobile background had been reset first, leaving
				 * `background` absent at the root and `style.background` intact:
				 * "Background Type reset to default" was announced and nothing
				 * changed, at Desktop and at Tablet both.
				 *
				 * Routing the clear instead makes it delete the key from the
				 * layer the matching EDIT would have written, which is what the
				 * reset means, and needs no signal from the store to do it.
				 * Anything else in the patch — a `style` group, another attribute
				 * — keeps the pass-through, so core's own resets are untouched.
				 */
				if ( getResetInProgress() && ! clearsOnlyFlatKeys( newAttributes, name ) ) {
					setAttributes( newAttributes );
					return;
				}

				/*
				 * Decide which layer this edit belongs to before reading anything.
				 *
				 * A state that differs from the stored one can only have been written
				 * by core's per-viewport control in this same call — then it is the
				 * authored value. A state that is unchanged is not part of this edit.
				 */
				const stateKey = DEVICE_TO_STYLE_STATE[ breakpoint ];

				/*
				 * Compare against the style this change actually carries. Core often
				 * sends `{ layout: … }` with no `style` at all, and reading the state
				 * out of an absent object reported a change that had not happened.
				 */
				const incomingStyle = newAttributes?.style ?? attributes?.style;
				const stateEdited =
					undefined !== stateKey &&
					'' !== stateKey &&
					shouldUpdateResponsiveData( attributes?.style?.[ stateKey ], incomingStyle?.[ stateKey ] );

				/*
				 * The edit is one of two shapes, and each names its own source.
				 *
				 * When the viewport state changed, the state bucket IS the edit. The
				 * root groups riding along in the same style object are the block's
				 * base styling, not part of this change — reading them here swept
				 * every base group into the device state on any tablet or mobile
				 * edit, silently pinning inheritance against later base changes; and
				 * when the edit CLEARED the state's last value, the root-only reading
				 * resurrected it from stale data. Taking the state bucket alone fixes
				 * both: a cleared or unchanged state produces no responsive delta,
				 * so the patch passes through untouched below and the deletion
				 * persists.
				 *
				 * Otherwise the edit went to the root — Spectra's own controls, or
				 * core at Desktop — and the root groups are the candidates. But only
				 * the groups this patch actually changed count: `style` always
				 * arrives whole, so untouched groups are passengers, not edits, and
				 * merging them into the device bucket pinned base styling exactly
				 * the same way.
				 */
				let responsiveAttrs;

				if ( stateEdited ) {
					responsiveAttrs = readBucketFromStyle( incomingStyle, breakpoint, flatKeys );

					/*
					 * Complete `layout` from the base layer.
					 *
					 * With core's Responsive Styles mode on, core writes the viewport
					 * state itself and writes ONLY the property the control changed.
					 * Changing Orientation at Tablet therefore stores
					 * `{ orientation: 'horizontal' }` with no `justifyContent` — and
					 * for layout a missing property is not "inherit", it is the CSS
					 * initial value. The tablet band then emits no `justify-content`
					 * and flex falls back to `flex-start`, so a block set to centre
					 * on Desktop silently renders left-aligned on tablet while the
					 * control still shows centre. Measured on 7.1 with Responsive
					 * Styles on: one click on Orientation produced
					 * `@tablet.layout = { type, orientation }`.
					 *
					 * Every other group survives a partial write, because a missing
					 * `padding.top` or `typography.fontSize` genuinely means "take the
					 * base value" and the generator resolves it that way. `layout` is
					 * the exception, so it is the only group completed here — filling
					 * the others would pin base styling into the state and defeat
					 * inheritance, which is the bug the comment above describes.
					 *
					 * Base wins nothing: the state's own values are kept and only the
					 * keys it does not carry come from base.
					 *
					 * The state's OWN previous layout has to be a layer of its own,
					 * because core does not send it. Core replaces the state's layout
					 * with just the property the control changed, so by the time the
					 * incoming style arrives the state's other values are already
					 * gone from it. Completing from base alone therefore overwrote
					 * them: with Desktop centre + vertical and Tablet set to left,
					 * one click on Orientation at Tablet stored
					 * `{ type, orientation }`, base filled `justifyContent` back to
					 * centre, and the tablet's left was lost while the control
					 * snapped back to centre. Layered base → previous state →
					 * incoming, each only filling what the next does not carry.
					 */
					const baseLayout     = incomingStyle?.layout;
					const previousLayout = readBucketFromStyle( attributes?.style, breakpoint, flatKeys )?.layout;

					if ( ( isObject( baseLayout ) || isObject( previousLayout ) ) && isObject( responsiveAttrs?.layout ) ) {
						responsiveAttrs = {
							...responsiveAttrs,
							layout: {
								...( isObject( baseLayout ) ? baseLayout : {} ),
								...( isObject( previousLayout ) ? previousLayout : {} ),
								...responsiveAttrs.layout,
							},
						};
					}
				} else {
					responsiveAttrs = extractResponsiveAttributes( newAttributes, name );

					const changed = {};
					const oldStyle = attributes?.style;
					const sentStyle = newAttributes?.style;

					if ( isObject( responsiveAttrs.style ) ) {
						Object.keys( responsiveAttrs.style ).forEach( ( group ) => {
							if (
								shouldUpdateResponsiveData(
									oldStyle?.[ group ],
									responsiveAttrs.style[ group ]
								)
							) {
								changed[ group ] = responsiveAttrs.style[ group ];
							}
						} );
					}

					/*
					 * Core sends the WHOLE style object, so a tracked group present
					 * before but absent from a style the patch actually carries is a
					 * routed DELETION, not an omission. Marking it undefined lets the
					 * merge-and-prune below clear it from the current device's
					 * bucket — clearing a font size while previewing Tablet clears
					 * the tablet value, and the base layer is restored by the write
					 * loop instead of silently losing its value to the pass-through.
					 */
					if ( undefined !== sentStyle ) {
						STYLE_RESPONSIVE_KEYS.forEach( ( group ) => {
							if (
								undefined !== oldStyle?.[ group ] &&
								( ! isObject( sentStyle ) || undefined === sentStyle[ group ] )
							) {
								changed[ group ] = undefined;
							}
						} );
					}

					responsiveAttrs = { ...responsiveAttrs };

					if ( Object.keys( changed ).length ) {
						responsiveAttrs.style = changed;
					} else {
						delete responsiveAttrs.style;
					}

					/*
					 * Top-level responsive keys (fontSize, fontFamily, borderColor)
					 * and the block's flat keys clear as explicitly-undefined
					 * attributes, but their copies inside `style` — planted by the
					 * write loop below on an earlier edit — would survive and PHP
					 * would keep rendering the cleared value forever. Route the
					 * clear as a deletion, UNLESS this same patch deliberately
					 * (re)writes the style copy — the backward-compatibility
					 * mapping sends exactly that shape.
					 */
					getBlockResponsiveKeys( name ).forEach( ( key ) => {
						if ( 'layout' === key || 'style' === key ) {
							return;
						}

						if ( ! ( key in newAttributes ) || undefined !== newAttributes[ key ] ) {
							return;
						}

						// Nothing stored for this key means nothing to clear — adding
						// the key regardless made every patch that merely mentions
						// it look like an edit to it.
						if ( undefined === ( isObject( oldStyle ) ? oldStyle[ key ] : undefined ) ) {
							return;
						}

						/*
						 * "Deliberate" means this patch SUPPLIES a value for the key
						 * inside `style` — the backward-compatibility mapping sends
						 * exactly that shape and must not have it undone here.
						 *
						 * It previously asked only whether the patch's style DIFFERED
						 * from the stored one at this key, which is true whenever the
						 * patch simply does not mention the key: `undefined` vs the
						 * stored value reads as a difference. Any clear arriving
						 * alongside a `style` object was therefore treated as a
						 * deliberate rewrite and dropped, so `style.size` survived and
						 * only the root attribute cleared — emptying Icon size at
						 * Desktop left the base at its old value and reset did nothing.
						 * Require an actual value before believing the patch means to
						 * keep the key.
						 */
						const deliberate =
							isObject( sentStyle ) &&
							hasValue( sentStyle[ key ] ) &&
							shouldUpdateResponsiveData(
								isObject( oldStyle ) ? oldStyle[ key ] : undefined,
								sentStyle[ key ]
							);

						if ( ! deliberate ) {
							responsiveAttrs[ key ] = undefined;
						}
					} );
				}

				// If there are no responsive-specific updates, apply attributes directly.
				if ( Object.keys( responsiveAttrs ).length === 0 ) {
					setAttributes( newAttributes );
					return;
				}

				// Create a working copy of the current breakpoint's data.
				// Deep clone needed since resolveMutualExclusivity and deepMerge modify nested objects.
				/*
				 * Seed from the style THIS patch carries, not the stored one. A
				 * patch can legitimately arrive with a style the block never had —
				 * the variation picker applies `variation.attributes` wholesale —
				 * and seeding from the stored attributes rebuilt every layer from
				 * stale data: the incoming states were wiped by the write loop
				 * below and incoming flat keys (`height`) deleted from the base.
				 * The stored `currentData` remains the comparison baseline only.
				 */
				const incomingBucket = mergeBuckets(
					readLegacyBucket( responsiveControls, breakpoint ),
					readBucketFromStyle( incomingStyle, breakpoint, flatKeys )
				);
				const updateData = Array.isArray( incomingBucket ) ? {} : deepClone( incomingBucket );

				// Handle mutual exclusivity between preset and custom values.
				resolveMutualExclusivity( updateData, responsiveAttrs, MUTUALLY_EXCLUSIVE_ATTR_PAIRS );

				// Merge the responsive attributes into the current breakpoint data.
				deepMerge( updateData, responsiveAttrs );

				/*
				 * A cleared control arrives as an explicit `undefined` leaf —
				 * `{ typography: { fontSize: undefined } }` — which deepMerge keeps
				 * so that deletions propagate. Written as-is it serialises a hollow
				 * `typography: {}` into the state forever. Dropping the undefined
				 * leaves and the empty objects they leave behind lets
				 * writeBucketToStyle() see the group as absent and delete it, so a
				 * state whose last value was cleared disappears entirely.
				 */
				pruneHollowValues( updateData );

				// Use optimized comparison that filters out functions and undefined values.
				const shouldUpdate = shouldUpdateResponsiveData( currentData, updateData );

				if ( ! shouldUpdate ) {
					setAttributes( newAttributes );
					return;
				}

				/*
				 * Write every breakpoint, not just the edited one. A block still
				 * carrying a legacy `responsiveControls` is migrated wholesale on
				 * its first responsive edit, so it never ends up half in each
				 * store — which is the state that made the two disagree.
				 */
				let nextStyle = newAttributes?.style ?? attributes?.style;

				Object.keys( DEVICE_TO_STYLE_STATE ).forEach( ( device ) => {
					/*
					 * The non-edited STATES read from the incoming style, so a patch
					 * carrying its own states — a variation pick — keeps them; a
					 * control's patch carries them unchanged, which is the same data.
					 *
					 * The BASE is ambiguous when the edit belongs to a narrower
					 * device: a root-writing control (Spectra's own) puts the routed
					 * value at the root of the patch, so reading the incoming root
					 * would copy a tablet keystroke into the base layer. Only when
					 * the state itself was edited is the incoming root a genuine
					 * base payload rather than routing scratch.
					 */
					const styleSource =
						'base' === device && 'base' !== breakpoint && ! stateEdited
							? attributes?.style
							: incomingStyle;
					const bucket =
						device === breakpoint
							? updateData
							: mergeBuckets( readLegacyBucket( responsiveControls, device ), readBucketFromStyle( styleSource, device, flatKeys ) );

					/*
					 * Pre-store content (v3-era container/slider/separator) keeps its
					 * base flat values in ROOT ATTRIBUTES only — nothing in `style`,
					 * nothing in a legacy store. The first narrower-device edit used
					 * to leave base empty while the routed value overwrote the root
					 * attribute's scratch copy, so a single mobile edit destroyed the
					 * desktop value on save. Seed the base from the root attribute —
					 * but only for a key NO viewport state carries: once any state
					 * has it, the root attribute is this session's routing scratch,
					 * not base truth, and must never be promoted.
					 */
					if ( 'base' === device && 'base' !== breakpoint && ! stateEdited ) {
						const blockDefaults = getBlockType( name )?.attributes ?? {};

						flatKeys.forEach( ( key ) => {
							if ( undefined !== bucket[ key ] || undefined === attributes?.[ key ] ) {
								return;
							}

							const oldStyle = attributes?.style;
							const stateHasKey = [ '@tablet', '@mobile' ].some(
								( state ) => undefined !== oldStyle?.[ state ]?.[ key ]
							);

							if ( stateHasKey || attributes[ key ] === blockDefaults[ key ]?.default ) {
								return;
							}

							bucket[ key ] = attributes[ key ];
						} );
					}

					nextStyle = writeBucketToStyle( nextStyle, device, bucket, flatKeys );
				} );

				const nextAttributes = {
					...newAttributes,
					style: nextStyle,
					// Reset to the attribute default so it stops being serialised.
					responsiveControls: undefined,
				};

				/*
				 * `attributes.layout` is a scratch surface, not storage. PHP strips it
				 * (it is listed in `$core_attributes`) so it never reaches the front
				 * end, while the base layer lives in the store. In the editor it is
				 * what core's Layout panel shows as the control's current value and
				 * what `useLayoutSupport` renders the canvas from — and the canvas is
				 * sized to the selected device, so the selected device's value is
				 * exactly what belongs there.
				 *
				 * It only has to carry a `type`: core resolves `layout || default`, so
				 * a partial object is used as-is, resolves to the `default` layout type
				 * and drops the block out of flex entirely.
				 */
				setAttributes( nextAttributes );
			},
			[ breakpoint, responsiveControls, currentData, name, setAttributes, attributes?.style, flatKeys ]
		);

		/*
		 * Show each control the value that applies at the SELECTED device.
		 *
		 * A block's own controls read flat attributes off `props.attributes` —
		 * `textShadowColor`, `size`, `gap`, the overlay family — but those hold
		 * the base value only: per-device values live in `style`'s viewport
		 * states, which is why the front end was right while the panel kept
		 * showing the desktop number at Tablet and Mobile.
		 *
		 * The overlay is display-only and never persisted; writes still travel
		 * through wrappedSetAttributes, which routes them to the selected
		 * device. Falling back to the base value matches what actually renders
		 * there — core's model resolves each viewport over base.
		 *
		 * It runs at Desktop too, not only at the narrower devices. The root
		 * attribute is NOT a trustworthy base: the router leaves the
		 * last-edited device's value there, and a save persists it, so a post
		 * whose last edit was at Mobile reopened at Desktop showed the mobile
		 * number in the panel AND in the canvas while `style` held the real
		 * base. Measured on a saved button — base 30px/20px, root 16px/6px,
		 * Desktop displaying 16/6. `style` is authoritative, which is what PHP
		 * already assumes when it renders the bands, so the editor reads it the
		 * same way.
		 *
		 * Only the block's own flat keys are overlaid. `style` itself is left
		 * untouched, because core's panels read their viewport states directly
		 * and manage that display themselves.
		 */
		const deviceAttributes = useMemo( () => {
			if ( ! flatKeys.length ) {
				return attributes;
			}

			/*
			 * Why this overlay stops at flat keys.
			 *
			 * Core's own style panels — Dimensions, Border, Typography — do not
			 * read `props.attributes`. They select
			 * `getBlockAttributes( clientId )?.style` straight from the store
			 * (see `DimensionsPanel` in block-editor's hooks), and there is no
			 * filter on that read. So no transformation of props can change what
			 * those panels DISPLAY. Their WRITES do arrive through props, which is
			 * why per-device routing works for them regardless.
			 *
			 * Making their displayed values follow the previewed device therefore
			 * requires mutating the store on every device switch. That is exactly
			 * what the pre-7.1 module does, and it is the right answer THERE
			 * because that WordPress offers nothing else. It is the wrong answer
			 * here: #732 removed it from this path (b2da4f56) after it corrupted
			 * content — `layout.type` wiped on a Tablet-to-Mobile switch, partial
			 * keystrokes persisted, Desktop edits discarded, every post opening
			 * dirty. On 7.1 core reads the states itself, so nothing needs
			 * projecting and this overlay only has to serve Spectra's own flat
			 * keys.
			 */
			const stateBucket = readBucketFromStyle( attributes?.style, breakpoint, flatKeys );
			const baseBucket  = readBucketFromStyle( attributes?.style, 'base', flatKeys );
			let next = attributes;

			flatKeys.forEach( ( key ) => {
				/*
				 * The state's own value wins; otherwise the base copy in `style`
				 * beats the root attribute, which holds the last-edited device's
				 * scratch — without this, a block edited at Mobile showed the
				 * mobile number in the Tablet panel, and after a save in the
				 * Desktop one too.
				 *
				 * At Desktop `stateBucket` IS the base bucket, so this resolves
				 * to the base value and falls through to the root attribute only
				 * when `style` holds nothing for the key — content that never
				 * had a per-device value written, where the root attribute is
				 * the only copy there is.
				 */
				/*
				 * `hasValue()` rather than `??`, so that a stored `''` inherits
				 * instead of blanking the control. New edits no longer write `''`
				 * (see the normalisation at the top of wrappedSetAttributes), but
				 * content saved before that fix still carries it, and this is the
				 * same test the front end applies — so both agree on what an empty
				 * string means.
				 */
				const stateValue = stateBucket[ key ];
				const baseValue = baseBucket[ key ];
				let shown = [ stateValue, baseValue ].find( ( value ) => hasValue( value ) );

				/*
				 * An object-valued key (`background`) resolves per PROPERTY, the
				 * way PHP hydrates it: a band that stores only `backgroundSize`
				 * keeps the base's type and media. Replacing the whole object
				 * made the panel show "no image" for a band that only changed
				 * the size, while the front end kept painting the image.
				 */
				if ( isPlainObjectValue( stateValue ) && isPlainObjectValue( baseValue ) ) {
					shown = { ...baseValue, ...stateValue };
				}

				if ( undefined === shown ) {
					/*
					 * Nothing stored for this device or for base. The root
					 * attribute is this session's routing scratch — it holds
					 * whatever device was edited last — so it must not be shown
					 * as this device's value when SOME other device authored the
					 * key: a Tablet-only size read as 40 at Mobile and Desktop
					 * while nothing was stored for either. Clear it here so the
					 * control shows its default. When no device ever authored the
					 * key, the root is the only copy there is and stays.
					 */
					const authoredElsewhere = Object.keys( DEVICE_TO_STYLE_STATE ).some(
						( device ) => hasValue( readBucketFromStyle( attributes?.style, device, [ key ] )[ key ] )
					);

					if ( authoredElsewhere && undefined !== attributes[ key ] ) {
						if ( next === attributes ) {
							next = { ...attributes };
						}
						next[ key ] = undefined;
					}
					return;
				}

				if ( shown === attributes[ key ] ) {
					return;
				}

				if ( next === attributes ) {
					next = { ...attributes };
				}

				next[ key ] = shown;
			} );

			return next;
		}, [ attributes, breakpoint, flatKeys ] );

		return (
			<>
				{ /*
				  * Required for the detection above: the hook reports what these
				  * mount into, so without them `isResponsiveEditingActive` is
				  * permanently false and the routing never changes.
				  */ }
				<ResponsiveEditingProbes />
				<BlockEdit { ...props } attributes={ deviceAttributes } setAttributes={ wrappedSetAttributes } />
			</>
		);
	};
}, 'withResponsiveControls' );

/**
 * Global Device Update Manager
 *
 * Optimizes device switching performance by processing blocks in controlled batches
 * instead of triggering 400+ simultaneous updates. Features immediate cancellation
 * for rapid device switching and cross-iframe context support.
 *
 * Performance Benefits:
 * - Prevents browser freeze with 200+ blocks
 * - Processes 5 blocks per batch (configurable)
 * - Cancels outdated processing immediately
 * - Works seamlessly across iframe/window contexts
 * - Uses WeakMap for automatic garbage collection of refs
 *
 * @since x.x.x
 */


/**
 * Higher-order component that updates block attributes based on the current device view.
 *
 * This component is responsible for merging responsive attributes into the block's.
 * attributes based on the current device type (Desktop, Tablet, Mobile). It:
 *
 * 1. Initializes responsive controls on first load by extracting responsive attributes.
 * 2. Computes merged attributes when device type or responsive controls change.
 * 3. Efficiently updates block attributes with non-persistent changes for preview.
 * 4. Handles cleanup to prevent memory leaks.
 *
 * @since x.x.x
 */
export const withContainerVariationSync = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { attributes, name, clientId } = props;

		// Get dispatch functions for updating block attributes.
		const { __unstableMarkNextChangeAsNotPersistent, updateBlockAttributes } = useDispatch( 'core/block-editor' );

		// Determine if responsive controls should be applied to this block.
		const isResponsive = isAllowedBlock( { name } );

		if ( ! isResponsive ) {
			return <BlockEdit { ...props } />;
		}

		// Initialize responsiveControls on first load (runs once).
		useEffect( () => {
			// If the block is a child container and its not variationSelected but its parent's variationSelected, set it's variationSelected to true.
			if ( name === 'spectra/container' && ! attributes?.variationSelected ) {
				const parentBlocks = dataSelect( 'core/block-editor' ).getBlockParents( clientId );

				if ( Array.isArray( parentBlocks ) && parentBlocks.length > 0 ) {
					const getBlock = dataSelect( 'core/block-editor' ).getBlock;

					for ( const parentClientId of parentBlocks ) {
						const parentBlock = getBlock( parentClientId );

						if ( parentBlock?.name === 'spectra/container' && parentBlock?.attributes?.variationSelected ) {
							__unstableMarkNextChangeAsNotPersistent();
							updateBlockAttributes( clientId, { variationSelected: true } );
							break;
						}
					}
				}
			}
		}, [] ); // Empty dependency array ensures this runs only on mount.


		// Render the original block edit component with the same props.
		return <BlockEdit { ...props } />;
	};
}, 'withContainerVariationSync' );
