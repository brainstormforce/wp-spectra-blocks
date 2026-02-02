/**
 * External dependencies.
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch, useSelect, select as dataSelect, subscribe as dataSubscribe } from '@wordpress/data';
import { useCallback, useEffect, useMemo, useRef } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import {
	DESKTOP,
	DEVICE_FALLBACK_ORDER,
	EXCLUDED_BLOCKS,
	SUPPORTED_BLOCKS,
	RESPONSIVE_KEYS,
	PROPERTIES_TO_MERGE,
	BREAKPOINT_TYPE_MAP,
	ALLOWED_PREFIXES,
	MUTUALLY_EXCLUSIVE_ATTR_PAIRS,
	BLOCK_RESPONSIVE_KEYS,
	STYLE_RESPONSIVE_KEYS,
	BACKGROUND_INNER_PROPERTIES,
	TABLET,
	MOBILE,
	DEVICE_SWITCH_BATCH_SIZE,
} from './constants';
import { getResetInProgress } from '..';

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
	if ( typeof obj !== 'object' || obj === null ) return obj;

	// Use native structuredClone if available (modern browsers)
	if ( typeof structuredClone === 'function' ) {
		return structuredClone( obj );
	}

	// Use JSON methods for deep cloning.
	return JSON.parse( JSON.stringify( obj ) );
};

/**
 * Performs deep equality comparison between two values.
 * Handles all data types including nested objects and arrays.
 *
 * @since x.x.x
 *
 * @param {*} a - First value to compare.
 * @param {*} b - Second value to compare.
 * @return {boolean} True if values are deeply equal, false otherwise.
 */
const isEqual = ( a, b ) => {
	if ( a === b ) return true;

	if ( a === null || a === undefined || b === null || b === undefined ) {
		return a === b;
	}

	if ( typeof a !== typeof b ) return false;

	// Add Date comparison.
	if ( a instanceof Date && b instanceof Date ) {
		return a.getTime() === b.getTime();
	}

	if ( Array.isArray( a ) && Array.isArray( b ) ) {
		if ( a.length !== b.length ) return false;
		return a.every( ( val, i ) => isEqual( val, b[ i ] ) );
	}

	if ( typeof a === 'object' && typeof b === 'object' ) {
		const keysA = Object.keys( a );
		const keysB = Object.keys( b );

		if ( keysA.length !== keysB.length ) return false;

		return keysA.every( ( key ) => keysB.includes( key ) && isEqual( a[ key ], b[ key ] ) );
	}

	return a === b;
};

/**
 * Gets a nested value from an object using a dot-notation path.
 * Safely handles missing intermediate properties.
 *
 * @since x.x.x
 *
 * @param {Object} obj - The object to retrieve the value from.
 * @param {string} path - The dot-notation path to the value.
 * @param {*} defaultValue - The default value to return if path is not found.
 * @return {*} The value at the specified path or defaultValue if not found.
 */
export const getNested = ( obj, path, defaultValue ) => {
	if ( ! obj || typeof path !== 'string' ) return defaultValue;

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
 * @param {Object} obj - The object to modify.
 * @param {string} path - The dot-notation path where to set the value.
 * @param {*} value - The value to set.
 * @return {void}
 */
export const setNested = ( obj, path, value ) => {
	if ( ! obj || typeof path !== 'string' ) return;

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
 * @param {Object} obj - The object to modify.
 * @param {string} path - The dot-notation path to the key to delete.
 * @return {boolean} True if the property was deleted, false otherwise.
 */
export const deleteNested = ( obj, path ) => {
	if ( ! obj || typeof path !== 'string' ) return false;

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
 * @param {Object} obj - The object to check.
 * @param {string} path - The dot-notation path to check.
 * @return {boolean} True if the property exists, false otherwise.
 */
const has = ( obj, path ) => {
	if ( ! obj || typeof path !== 'string' ) return false;

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
const shouldUpdateData = ( current, updated ) => {
	// Fast reference check first.
	if ( current === updated ) return false;

	// Performance hack: Direct comparison without creating intermediate objects.
	// Iterate through keys once and compare directly.
	const currentKeys = Object.keys( current );
	const updatedKeys = Object.keys( updated );

	// Quick length check first.
	if ( currentKeys.length !== updatedKeys.length ) return true;

	// Check all current keys exist in updated and values are equal.
	for ( const key of currentKeys ) {
		const currentVal = current[ key ];
		const updatedVal = updated[ key ];

		// Skip functions and undefined values.
		const currentHasValue = ! isFunction( currentVal ) && ! isUndefined( currentVal );
		const updatedHasValue = ! isFunction( updatedVal ) && ! isUndefined( updatedVal );

		// If value presence differs, objects are different.
		if ( currentHasValue !== updatedHasValue ) return true;

		// If both have values, compare them.
		if ( currentHasValue && ! isEqual( currentVal, updatedVal ) ) return true;
	}

	return false;
};

/**
 * Checks if a responsive breakpoint data object is empty or has no meaningful values.
 *
 * @since x.x.x
 *
 * @param {Object} breakpointData - The breakpoint data to check.
 * @return {boolean} True if the breakpoint data is empty, false otherwise.
 */
const isBreakpointDataEmpty = ( breakpointData ) => {
	if ( ! breakpointData || typeof breakpointData !== 'object' ) {
		return true;
	}

	// Check if object has any keys.
	const keys = Object.keys( breakpointData );
	if ( keys.length === 0 ) {
		return true;
	}

	// Check if any key has a meaningful value.
	return ! keys.some( ( key ) => hasValue( breakpointData[ key ] ) );
};

/**
 * Checks if the device update from `from` to `to` should be skipped.
 * This is the case when the responsive controls are empty for the target device,
 * or when the initial load is for the desktop device and the default and lg values are the same.
 *
 * @since x.x.x
 *
 * @param {string} from - The device type to transition from.
 * @param {string} to - The device type to transition to.
 * @param {Object} controls - The responsive controls object.
 * @return {boolean} True if the device update should be skipped, false otherwise.
 */
function shouldSkipDeviceUpdate( from, to, controls ) {
	const lg = controls?.lg;
	const md = controls?.md;
	const sm = controls?.sm;

	// Desktop → Tablet when md is empty.
	if ( from === DESKTOP && to === TABLET && isBreakpointDataEmpty( md ) ) return true;

	// Tablet → Mobile when sm is empty.
	if ( from === TABLET && to === MOBILE && isBreakpointDataEmpty( sm ) ) return true;

	// Tablet → Desktop when both lg and md are empty.
	if ( from === TABLET && to === DESKTOP && isBreakpointDataEmpty( lg ) && isBreakpointDataEmpty( md ) ) return true;

	// Mobile → Tablet when both sm and md are empty.
	if ( from === MOBILE && to === TABLET && isBreakpointDataEmpty( sm ) && isBreakpointDataEmpty( md ) ) return true;

	return false;
}

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
	if ( a === b ) return false;

	// If one is object and the other is not, they are different.
	if ( typeof a !== 'object' || typeof b !== 'object' || a === null || b === null ) {
		return a !== b;
	}

	const keysA = Object.keys( a );
	const keysB = Object.keys( b );

	// Check for different number of keys, but filter out undefined/function values.
	const cleanKeysA = keysA.filter( ( key ) => ! isUndefined( a[ key ] ) && ! isFunction( a[ key ] ) );
	const cleanKeysB = keysB.filter( ( key ) => ! isUndefined( b[ key ] ) && ! isFunction( b[ key ] ) );

	if ( cleanKeysA.length !== cleanKeysB.length ) return true;

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
 * Checks if any breakpoint in responsive controls has mixed radius format.
 *
 * @since x.x.x
 *
 * @param {Object} responsiveControls - The responsive controls object.
 * @return {boolean} True if any breakpoint has mixed radius format.
 */
const hasMixedRadiusInAnyBreakpoint = ( responsiveControls ) => {
	const breakpoints = [ 'lg', 'md', 'sm' ];

	return breakpoints.some( ( bp ) => {
		const breakpointData = responsiveControls[ bp ];
		if ( ! breakpointData || typeof breakpointData !== 'object' ) return false;

		return (
			has( breakpointData, 'style.border.radius.topLeft' ) ||
			has( breakpointData, 'style.border.radius.topRight' ) ||
			has( breakpointData, 'style.border.radius.bottomLeft' ) ||
			has( breakpointData, 'style.border.radius.bottomRight' )
		);
	} );
};

/**
 * Converts single radius to mixed radius format.
 *
 * @since x.x.x
 *
 * @param {string|number} singleRadius - The single radius value.
 * @return {Object} Mixed radius object with all corners set to the single value.
 */
const convertSingleRadiusToMixed = ( singleRadius ) => {
	return {
		topLeft: singleRadius,
		topRight: singleRadius,
		bottomLeft: singleRadius,
		bottomRight: singleRadius,
	};
};

/**
 * Normalizes radius format across all breakpoints to ensure consistency.
 * If any breakpoint has mixed radius, convert all single radius to mixed format.
 *
 * @since x.x.x
 *
 * @param {Object} responsiveControls - The responsive controls object to normalize.
 * @return {Object} Normalized responsive controls with consistent radius format.
 */
const normalizeRadiusFormat = ( responsiveControls ) => {
	// Check if any breakpoint has mixed radius
	if ( ! hasMixedRadiusInAnyBreakpoint( responsiveControls ) ) {
		return responsiveControls; // No mixed radius found, return as-is
	}

	// Clone to avoid mutation
	const normalized = deepClone( responsiveControls );
	const breakpoints = [ 'lg', 'md', 'sm' ];

	breakpoints.forEach( ( bp ) => {
		const breakpointData = normalized[ bp ];
		if ( ! breakpointData || typeof breakpointData !== 'object' ) return;

		// Check if this breakpoint has single radius
		if ( has( breakpointData, 'style.border.radius' ) && typeof breakpointData.style.border.radius !== 'object' ) {
			// Convert single to mixed
			const singleValue = breakpointData.style.border.radius;
			const mixedRadius = convertSingleRadiusToMixed( singleValue );

			// Clean up: remove the single radius property first
			deleteNested( normalized, `${ bp }.style.border.radius` );

			// Then set the mixed radius
			setNested( normalized, `${ bp }.style.border.radius`, mixedRadius );
		}
	} );

	return normalized;
};

/**
 * Checks if any breakpoint in responsive controls has individual border format.
 *
 * @since x.x.x
 *
 * @param {Object} responsiveControls - The responsive controls object.
 * @return {boolean} True if any breakpoint has individual border format.
 */
const hasIndividualBorderInAnyBreakpoint = ( responsiveControls ) => {
	const breakpoints = [ 'lg', 'md', 'sm' ];

	return breakpoints.some( ( bp ) => {
		const breakpointData = responsiveControls[ bp ];
		if ( ! breakpointData || typeof breakpointData !== 'object' ) return false;

		return (
			has( breakpointData, 'style.border.top' ) ||
			has( breakpointData, 'style.border.right' ) ||
			has( breakpointData, 'style.border.bottom' ) ||
			has( breakpointData, 'style.border.left' )
		);
	} );
};

/**
 * Converts shorthand border to individual border format.
 *
 * @since x.x.x
 *
 * @param {Object} shorthandBorder - The shorthand border object with width, style, color.
 * @param {string} borderColorPreset - Optional borderColor preset value.
 * @return {Object} Individual border object with top, right, bottom, left properties.
 */
const convertShorthandBorderToIndividual = ( shorthandBorder, borderColorPreset = null ) => {
	const individualBorder = {};

	[ 'top', 'right', 'bottom', 'left' ].forEach( ( side ) => {
		// Only create border object if there are actual border properties
		const sideProperties = {};

		if ( shorthandBorder.width ) {
			sideProperties.width = shorthandBorder.width;
		}
		if ( shorthandBorder.style ) {
			sideProperties.style = shorthandBorder.style;
		}
		// Priority: shorthand color > borderColor preset
		if ( shorthandBorder.color ) {
			sideProperties.color = shorthandBorder.color;
		} else if ( borderColorPreset && ( shorthandBorder.width || shorthandBorder.style ) ) {
			// Apply borderColor preset only if there's width or style (actual border)
			// Handle both formats: preset name only or already formatted variable
			if ( borderColorPreset.startsWith( 'var:preset|color|' ) ) {
				sideProperties.color = borderColorPreset;
			} else {
				sideProperties.color = `var:preset|color|${ borderColorPreset }`;
			}
		}

		// Only add if there are properties to add
		if ( Object.keys( sideProperties ).length > 0 ) {
			individualBorder[ side ] = sideProperties;
		}
	} );

	return individualBorder;
};

/**
 * Normalizes border format across all breakpoints to ensure consistency.
 * If any breakpoint has individual borders, convert all shorthand borders to individual format.
 *
 * @since x.x.x
 *
 * @param {Object} responsiveControls - The responsive controls object to normalize.
 * @return {Object} Normalized responsive controls with consistent border format.
 */
const normalizeBorderFormat = ( responsiveControls ) => {
	// Check if any breakpoint has individual borders
	if ( ! hasIndividualBorderInAnyBreakpoint( responsiveControls ) ) {
		return responsiveControls; // No individual borders found, return as-is
	}

	// Clone to avoid mutation
	const normalized = deepClone( responsiveControls );
	const breakpoints = [ 'lg', 'md', 'sm' ];

	breakpoints.forEach( ( bp ) => {
		const breakpointData = normalized[ bp ];
		if ( ! breakpointData || typeof breakpointData !== 'object' ) return;

		// Check if this breakpoint has shorthand border properties
		const hasShorthandBorder =
			has( breakpointData, 'style.border.width' ) ||
			has( breakpointData, 'style.border.style' ) ||
			has( breakpointData, 'style.border.color' ) ||
			hasValue( breakpointData?.borderColor );

		if ( hasShorthandBorder ) {
			// Extract shorthand border properties
			const shorthandBorder = {};
			if ( has( breakpointData, 'style.border.width' ) ) {
				shorthandBorder.width = breakpointData.style.border.width;
			}
			if ( has( breakpointData, 'style.border.style' ) ) {
				shorthandBorder.style = breakpointData.style.border.style;
			}
			if ( has( breakpointData, 'style.border.color' ) ) {
				shorthandBorder.color = breakpointData.style.border.color;
			}

			// Get borderColor preset if it exists
			const borderColorPreset = normalized[ bp ]?.borderColor || null;

			// Convert to individual format, applying borderColor if needed
			const individualBorder = convertShorthandBorderToIndividual( shorthandBorder, borderColorPreset );

			// Clean up: remove shorthand properties
			deleteNested( normalized, `${ bp }.style.border.width` );
			deleteNested( normalized, `${ bp }.style.border.style` );
			deleteNested( normalized, `${ bp }.style.border.color` );

			// Set individual border properties
			Object.keys( individualBorder ).forEach( ( side ) => {
				setNested( normalized, `${ bp }.style.border.${ side }`, individualBorder[ side ] );
			} );

			// Remove borderColor preset only if we successfully applied it to individual borders
			if ( borderColorPreset && has( normalized[ bp ], 'borderColor' ) ) {
				delete normalized[ bp ].borderColor;
			}
		}
	} );

	return normalized;
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
 * @param {Array} pairs - Array of mutually exclusive attribute pairs [presetKey, customPath].
 */
const resolveMutualExclusivity = ( target, source, pairs ) => {
	pairs.forEach( ( [ presetKey, customPath ] ) => {
		const presetPathExists = has( source, presetKey );
		const customPathExists = has( source, customPath );

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
 * Generates all nested paths for spacing, typography, border and other complex properties.
 *
 * @since x.x.x
 *
 * @param {string} basePath - The base path like 'style.spacing'
 * @param {Object} obj - The object to traverse
 * @param {Array} paths - Array to collect paths
 * @param {number} maxDepth - Maximum depth to traverse
 */
const generateNestedPaths = ( basePath, obj, paths = [], maxDepth = 3 ) => {
	if ( maxDepth <= 0 || ! obj || typeof obj !== 'object' ) {
		return paths;
	}

	Object.keys( obj ).forEach( ( key ) => {
		const currentPath = basePath ? `${ basePath }.${ key }` : key;
		paths.push( currentPath );

		// Recursively generate paths for nested objects.
		if ( obj[ key ] && typeof obj[ key ] === 'object' && ! Array.isArray( obj[ key ] ) ) {
			generateNestedPaths( currentPath, obj[ key ], paths, maxDepth - 1 );
		}
	} );

	return paths;
};

/**
 * Cleans up stale nested keys in responsive attributes.
 *
 * This function identifies and marks for removal any properties that exist in the base.
 * attributes but are being removed in the responsive attributes. This ensures CSS variables
 * are properly cleared when values are removed at specific breakpoints.
 *
 * @since x.x.x
 *
 * @param {Object} result       - The original attributes object.
 * @param {Object} valuesToApply - The object containing values to be applied.
 * @param {Array<string>} keysToCleanup - Flat dot-notation paths to clean.
 */
const cleanupStaleNestedKeys = ( result, valuesToApply, keysToCleanup ) => {
	// Generate comprehensive paths including all nested properties.
	const allPaths = [];
	keysToCleanup.forEach( ( path ) => {
		// Add the path itself.
		allPaths.push( path );

		// If path points to an object in result, generate all nested paths.
		const baseObj = getNested( result, path );
		if ( baseObj && typeof baseObj === 'object' && ! Array.isArray( baseObj ) ) {
			generateNestedPaths( path, baseObj, allPaths );
		}
	} );

	// Clean up all paths.
	allPaths.forEach( ( path ) => {
		const baseVal = getNested( result, path );
		const applyVal = getNested( valuesToApply, path );

		if ( hasValue( baseVal ) && ! hasValue( applyVal ) ) {
			setNested( valuesToApply, path, undefined );
		}
	} );
};

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
	if ( isNil( value ) || value === '' ) return false;

	// Handle objects and arrays.
	if ( isObject( value ) ) {
		// For arrays, check if they have any elements.
		if ( isArray( value ) ) return value.length > 0;
		// For objects, check if any property has a value.
		return Object.keys( value ).length > 0 && Object.values( value ).some( ( v ) => hasValue( v ) );
	}

	// All other values are considered valid.
	return true;
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

	// Performance hack: Process other attributes with fast Set lookup.
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
	if ( ! block?.name ) return false;

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
 * Global Device Change Listener
 *
 * Single centralized listener that detects device changes and triggers
 * the batch processing system. Replaces 400+ individual useEffect listeners
 * that would cause browser performance issues.
 *
 * Architecture Benefits:
 * - ONE listener instead of 400+ individual listeners
 * - Immediate batch processing trigger
 * - Optimal memory usage and performance
 * - Compatible with WordPress data store patterns
 *
 * @type {Function|null} Unsubscribe function from WordPress data store
 * @since x.x.x
 */
let globalDeviceListener = null;

/**
 * Cleanup function for the global device listener.
 * Ensures proper memory cleanup in all scenarios.
 *
 * @since x.x.x
 */
const cleanupGlobalDeviceListener = () => {
	if ( globalDeviceListener ) {
		try {
			globalDeviceListener();
		} catch ( e ) {
			// Silent error handling for cleanup
		} finally {
			globalDeviceListener = null;
		}
	}
	// Also cleanup the update manager
	GlobalDeviceUpdateManager.cleanup();
};

/**
 * Initialize the global device change detection system.
 *
 * Creates a single WordPress data store subscriber that monitors
 * device type changes and triggers optimized batch processing.
 *
 * @since x.x.x
 */
const initGlobalDeviceListener = () => {
	if ( globalDeviceListener ) return; // Already initialized.

	// Use imported WordPress data functions.
	if ( ! dataSubscribe || ! dataSelect ) return;

	let previousDevice = dataSelect( 'core/editor' )?.getDeviceType?.() || DESKTOP;

	// Trigger initial device state on page load to ensure desktop values are reflected.
	const initialTimeout = setTimeout( () => {
		GlobalDeviceUpdateManager.processDeviceChange( previousDevice );
	}, 0 );

	globalDeviceListener = dataSubscribe( () => {
		const currentDevice = dataSelect( 'core/editor' )?.getDeviceType?.() || DESKTOP;
		if ( currentDevice !== previousDevice ) {
			// Device changed - trigger batch update.
			GlobalDeviceUpdateManager.processDeviceChange( currentDevice );
			previousDevice = currentDevice;
		}
	} );

	// Enhanced cleanup on multiple events to prevent memory leaks
	if ( typeof window !== 'undefined' ) {
		window.addEventListener( 'beforeunload', cleanupGlobalDeviceListener, { once: true } );
		window.addEventListener( 'pagehide', cleanupGlobalDeviceListener, { once: true } );

		// Cleanup the initial timeout if page unloads before it executes
		const originalCleanup = () => {
			if ( globalDeviceListener ) {
				try {
					globalDeviceListener();
				} catch ( e ) {
					// Silent error handling for cleanup
				} finally {
					globalDeviceListener = null;
				}
			}
			// Also cleanup the update manager
			GlobalDeviceUpdateManager.cleanup();
		};

		const enhancedCleanup = () => {
			clearTimeout( initialTimeout );
			originalCleanup();
		};

		// Replace the cleanup function with enhanced version
		window.removeEventListener( 'beforeunload', cleanupGlobalDeviceListener );
		window.removeEventListener( 'pagehide', cleanupGlobalDeviceListener );
		window.addEventListener( 'beforeunload', enhancedCleanup, { once: true } );
		window.addEventListener( 'pagehide', enhancedCleanup, { once: true } );
	}
};

// Initialize the global device change detection system.
function whenEditorIsReady() {
	return new Promise( ( resolve ) => {
		const unsubscribe = dataSubscribe( () => {
			if ( dataSelect( 'core/block-editor' ).getBlockCount() > 0 ) {
				unsubscribe();
				resolve();
			}
		} );
	} );
}

wp.domReady( () => {
	whenEditorIsReady().then( () => {
		if ( typeof window !== 'undefined' ) {
			initGlobalDeviceListener();
		}
	} );
} );

/**
 * Gets the block-specific responsive keys based on block name.
 * Different block types may have different responsive attributes.
 *
 * @since x.x.x
 *
 * @param {string} blockName - The name of the block.
 * @param {boolean} onlyBlockAttrs - Whether to return only block-specific responsive keys.
 * @return {Array} Array of responsive keys for the specific block type.
 */
export const getBlockResponsiveKeys = ( blockName, onlyBlockAttrs = false ) => {
	// If no blockName provided, return default keys or empty array.
	if ( ! blockName ) return onlyBlockAttrs ? [] : RESPONSIVE_KEYS;

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
	 * @param {string} blockName The block name (e.g., 'spectra/advanced-heading').
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
 * ===================================================================
 * ATTRIBUTE MANAGEMENT
 * ===================================================================
 *
 * Functions for extending block attributes with responsive control.
 * capabilities. This section handles the registration of responsive
 * attributes in block settings.
 */

/**
 * Extends block attributes with responsive controls.
 *
 * Adds the responsiveControls attribute to block settings for blocks.
 * that should support responsive behavior.
 *
 * @since x.x.x
 *
 * @param {Object} settings - The block settings object.
 * @param {string} name     - The block name.
 * @return {Object} Modified settings with responsive controls attribute.
 */
export const extendBlockAttributes = ( settings, name ) => {
	// Skip blocks that shouldn't have responsive controls.
	if ( ! isAllowedBlock( { name } ) ) return settings;

	// Add the responsiveControls attribute to the block.
	return {
		...settings,
		attributes: {
			...settings.attributes,
			responsiveControls: {
				type: 'object',
				default: {},
			},
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
 * @return {string} The breakpoint type (e.g., 'lg', 'md', 'sm').
 */
const getBreakpointType = ( deviceType ) => BREAKPOINT_TYPE_MAP[ deviceType ] || 'lg';

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
 * Deep merges responsive attributes into the base attributes.
 *
 * Takes responsive values for the current device type and merges.
 * them into the base attributes. Handles mutually exclusive attribute pairs
 * like preset values vs custom style values according to WordPress conventions.
 *
 * @since x.x.x
 *
 * @param {Object} baseResponsiveAttrs       - The baseResponsiveAttrs attributes to merge into.
 * @param {Object} responsive - The responsive attributes to merge from.
 * @param {string} deviceType - The device type to get the responsive attributes for.
 * @param {string} blockName  - The name of the block.
 *
 * @return {Object} The merged attributes with properly handled responsive values.
 */
export const deepMergeAttributes = ( baseResponsiveAttrs, responsive, deviceType, blockName ) => {
	// SAFETY FIRST: Always deep clone to prevent data corruption.
	// setNested() modifies nested objects in-place, making shallow cloning unsafe
	const result = deepClone( baseResponsiveAttrs );

	// RADIUS FORMAT NORMALIZATION: Ensure consistent radius format across breakpoints
	// If any breakpoint has mixed radius, convert all single radius to mixed format
	let normalizedResponsive = normalizeRadiusFormat( responsive );

	// Also normalize the base attributes if mixed radius exists anywhere
	if (
		hasMixedRadiusInAnyBreakpoint( responsive ) &&
		has( result, 'style.border.radius' ) &&
		typeof result.style.border.radius !== 'object'
	) {
		// Convert single radius in base attributes to mixed format
		const singleValue = result.style.border.radius;
		const mixedRadius = convertSingleRadiusToMixed( singleValue );
		setNested( result, 'style.border.radius', mixedRadius );
	}

	// BORDER FORMAT NORMALIZATION: Ensure consistent border format across breakpoints
	// If any breakpoint has individual borders, convert all shorthand borders to individual format
	normalizedResponsive = normalizeBorderFormat( normalizedResponsive );

	// Also normalize the base attributes if individual borders exist anywhere
	if ( hasIndividualBorderInAnyBreakpoint( responsive ) ) {
		const hasShorthandBorderInBase =
			has( result, 'style.border.width' ) ||
			has( result, 'style.border.style' ) ||
			has( result, 'style.border.color' ) ||
			hasValue( result.borderColor );

		if ( hasShorthandBorderInBase ) {
			// Extract shorthand border properties from base
			const shorthandBorder = {};
			if ( has( result, 'style.border.width' ) ) {
				shorthandBorder.width = result.style.border.width;
			}
			if ( has( result, 'style.border.style' ) ) {
				shorthandBorder.style = result.style.border.style;
			}
			if ( has( result, 'style.border.color' ) ) {
				shorthandBorder.color = result.style.border.color;
			}

			// Get borderColor preset if it exists in base
			const borderColorPreset = result?.borderColor || null;

			// Convert to individual format, applying borderColor if needed
			const individualBorder = convertShorthandBorderToIndividual( shorthandBorder, borderColorPreset );

			// Clean up: remove shorthand properties from base
			deleteNested( result, 'style.border.width' );
			deleteNested( result, 'style.border.style' );
			deleteNested( result, 'style.border.color' );

			// Set individual border properties in base
			Object.keys( individualBorder ).forEach( ( side ) => {
				setNested( result, `style.border.${ side }`, individualBorder[ side ] );
			} );

			// Remove borderColor preset only if we successfully applied it to individual borders
			if ( borderColorPreset && has( result, 'borderColor' ) ) {
				delete result.borderColor;
			}
		}
	}

	// Get the responsive keys for this block type.
	const blockKeys = getBlockResponsiveKeys( blockName, true );
	const allPaths = [ ...blockKeys, ...PROPERTIES_TO_MERGE ];

	// Check if this block supports background attribute.
	const supportsBackground = blockKeys.includes( 'background' );

	// If background is supported, add inner background properties to the paths to merge.
	if ( supportsBackground ) {
		BACKGROUND_INNER_PROPERTIES.forEach( ( prop ) => {
			allPaths.push( `background.${ prop }` );
		} );
	}

	// Get the fallback order for the current device type.
	const fallbackOrder = DEVICE_FALLBACK_ORDER[ deviceType ] || [ 'lg' ];

	// Get the current breakpoint and its data.
	const currentBreakpoint = fallbackOrder[ 0 ];
	const currentData = normalizedResponsive[ currentBreakpoint ] || {};

	// Object to collect all values that should be applied.
	const valuesToApply = {};

	/**
	 * Step 2: Apply normal paths — with clean inheritance for all properties.
	 */
	allPaths.forEach( ( path ) => {
		// Check if the path exists and has a meaningful value in current breakpoint data.
		if ( has( currentData, path ) ) {
			const currentVal = getNested( currentData, path );
			if ( hasValue( currentVal ) ) {
				setNested( valuesToApply, path, currentVal );
				return;
			}
		}

		// Special handling for background.media - only inherit if background type matches.
		if ( path === 'background.media' && supportsBackground ) {
			const currentType = getNested( currentData, 'background.type' );

			// Look for values in fallback breakpoints, but only if type matches.
			for ( const bp of fallbackOrder.slice( 1 ) ) {
				const fallbackType = getNested( normalizedResponsive[ bp ] || {}, 'background.type' );
				const val = getNested( normalizedResponsive[ bp ] || {}, path );

				// Only inherit media if the background types match or current device has no type set.
				if ( hasValue( val ) && ( ! hasValue( currentType ) || currentType === fallbackType ) ) {
					setNested( valuesToApply, path, val );
					break;
				}
			}
			return;
		}

		// For all other paths, use standard inheritance.
		for ( const bp of fallbackOrder.slice( 1 ) ) {
			const val = getNested( normalizedResponsive[ bp ] || {}, path );
			if ( hasValue( val ) ) {
				setNested( valuesToApply, path, val );
				break;
			}
		}
	} );

	/**
	 * Step 3: Cleanup stale nested keys.
	 * Only clean up keys that are in the RESPONSIVE_KEYS and STYLE_RESPONSIVE_KEYS lists.
	 * Border properties are handled dynamically.
	 */
	const keysToCleanup = [];
	RESPONSIVE_KEYS.forEach( ( key ) => {
		if ( key === 'style' ) {
			STYLE_RESPONSIVE_KEYS.forEach( ( styleKey ) => {
				keysToCleanup.push( `style.${ styleKey }` );
			} );
		} else {
			keysToCleanup.push( key );
		}
	} );

	blockKeys.forEach( ( key ) => {
		if ( ! keysToCleanup.includes( key ) ) keysToCleanup.push( key );

		// If it's the background key, also add inner properties for cleanup.
		if ( key === 'background' && supportsBackground ) {
			BACKGROUND_INNER_PROPERTIES.forEach( ( prop ) => {
				keysToCleanup.push( `background.${ prop }` );
			} );
		}
	} );

	cleanupStaleNestedKeys( result, valuesToApply, keysToCleanup );

	// Apply all collected values to the result object.
	deepMerge( result, valuesToApply );

	// Ensure border visibility when width is set but style is "none"
	const borderData = getNested( result, 'style.border' ) || {};
	if ( hasValue( borderData.width ) && Number.parseInt( borderData.width, 10 ) > 0 ) {
		// If style is missing or "none", default to undefined for visibility.
		if ( borderData.style === 'none' ) {
			setNested( result, 'style.border.style', undefined );
		}
	}

	// Final cleanup: ensure mutually exclusive attributes don't coexist in the final result.
	MUTUALLY_EXCLUSIVE_ATTR_PAIRS.forEach( ( [ presetKey, customPath ] ) => {
		const presetValue = result[ presetKey ];
		const customValue = getNested( result, customPath );
		const currentHasPreset = has( currentData, presetKey );
		const currentHasCustom = has( currentData, customPath );

		// If both exist, determine which one to keep based on what was explicitly set for current device.
		if ( hasValue( presetValue ) && hasValue( customValue ) ) {
			if ( currentHasPreset ) {
				// Current device has preset, remove custom.
				setNested( result, customPath, undefined );
			} else if ( currentHasCustom ) {
				// Current device has custom, remove preset.
				result[ presetKey ] = undefined;
			} else {
				// Neither set on current device, prefer preset (fallback priority).
				setNested( result, customPath, undefined );
			}
		}
		// Only populate the path that the current device actually uses for input compatibility.
		else if ( hasValue( presetValue ) && ! hasValue( customValue ) && ! currentHasCustom ) {
			// Only clear custom if current device doesn't explicitly use custom.
			setNested( result, customPath, undefined );
		}
	} );

	// Check if result has 'layout' and all inner values are undefined
	if ( has( result, 'layout' ) ) {
		const layout = getNested( result, 'layout' ) || {};

		const isLayoutEmpty = ! Object.values( layout ).some( ( value ) => hasValue( value ) );

		if ( isLayoutEmpty ) {
			result.layout = undefined;
		}
	}

	// FINAL CLEANUP: Remove borderColor if individual border colors exist in final result
	// This ensures mutual exclusivity regardless of how attributes got there (inheritance, base, etc.)

	// Get individual border colors once (avoid duplication)
	const topColor = getNested( result, 'style.border.top.color' );
	const rightColor = getNested( result, 'style.border.right.color' );
	const bottomColor = getNested( result, 'style.border.bottom.color' );
	const leftColor = getNested( result, 'style.border.left.color' );

	const hasIndividualBorderColorsInResult =
		hasValue( topColor ) || hasValue( rightColor ) || hasValue( bottomColor ) || hasValue( leftColor );

	// SMART BORDER COLOR OPTIMIZATION: Convert individual colors to shorthand when all same
	if ( hasIndividualBorderColorsInResult ) {
		// Check if all sides have the same color
		const allColorsAreSame =
			hasValue( topColor ) &&
			hasValue( rightColor ) &&
			hasValue( bottomColor ) &&
			hasValue( leftColor ) &&
			topColor === rightColor &&
			rightColor === bottomColor &&
			bottomColor === leftColor;

		if ( allColorsAreSame ) {
			// All sides have same color - ADD shorthand (keep individual for settings)
			if ( ! result.style.border ) result.style.border = {};
			result.style.border.color = topColor;

			// Keep individual colors for settings controls
			// Don't delete them - they're needed for block settings display

			// Remove borderColor preset if it exists
			if ( has( result, 'borderColor' ) ) {
				result.borderColor = undefined;
			}
		}
	}

	return result;
};

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

		// Convert device type to breakpoint type (lg, md, sm) and memoize the result.
		const breakpoint = useMemo( () => getBreakpointType( deviceType ), [ deviceType ] );

		// Get the current data for this breakpoint, ensuring it's an object (not an array).
		const currentData =
			typeof responsiveControls[ breakpoint ] === 'object' &&
			responsiveControls[ breakpoint ] !== null &&
			! Array.isArray( responsiveControls[ breakpoint ] )
				? responsiveControls[ breakpoint ]
				: {};

		/**
		 * Custom setAttributes function that handles responsive attributes.
		 *
		 * Separates responsive and non-responsive attributes and stores.
		 * responsive ones in the responsiveControls object.
		 */
		const wrappedSetAttributes = useCallback(
			( newAttributes ) => {
				// Skip responsive controls processing during reset operations.
				if ( getResetInProgress() ) {
					setAttributes( newAttributes );
					return;
				}

				// Extract responsive attributes using the common function.
				const responsiveAttrs = extractResponsiveAttributes( newAttributes, name );

				// If there are no responsive-specific updates, apply attributes directly.
				if ( Object.keys( responsiveAttrs ).length === 0 ) {
					setAttributes( newAttributes );
					return;
				}

				// Create a working copy of the current breakpoint's data.
				// Deep clone needed since resolveMutualExclusivity and deepMerge modify nested objects.
				const updateData = Array.isArray( currentData ) ? {} : deepClone( currentData );

				// Handle mutual exclusivity between preset and custom values.
				resolveMutualExclusivity( updateData, responsiveAttrs, MUTUALLY_EXCLUSIVE_ATTR_PAIRS );

				// Merge the responsive attributes into the current breakpoint data.
				deepMerge( updateData, responsiveAttrs );

				// Use optimized comparison that filters out functions and undefined values.
				const shouldUpdate = shouldUpdateResponsiveData( currentData, updateData );

				setAttributes(
					shouldUpdate
						? {
								...newAttributes,
								responsiveControls: {
									...responsiveControls,
									[ breakpoint ]: updateData,
								},
						  }
						: newAttributes
				);
			},
			[ breakpoint, responsiveControls, currentData, name, setAttributes ]
		);

		return <BlockEdit { ...props } setAttributes={ wrappedSetAttributes } />;
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

const GlobalDeviceUpdateManager = {
	/** @type {Map<string, {clientId: string, updateFn: Function}>} Queue of registered block update functions */
	updateQueue: new Map(),

	/** @type {boolean} Flag to prevent overlapping processing cycles */
	isProcessing: false,

	/** @type {string|null} Current device being processed for change detection */
	currentDevice: null,

	/** @type {Set<number>} Track scheduled timeouts for immediate cancellation */
	processingTimeouts: new Set(),

	/**
	 * Register a block for device update processing.
	 *
	 * @param {string} clientId - Unique WordPress block identifier.
	 * @param {Function} updateFn - Block-specific device update function.
	 * @since x.x.x
	 */
	register( clientId, updateFn ) {
		// Safety valve: prevent runaway growth (rare but possible)
		if ( this.updateQueue.size > 1000 ) {
			const entries = Array.from( this.updateQueue.keys() );
			for ( let i = 0; i < 200; i++ ) {
				this.updateQueue.delete( entries[ i ] );
			}
		}

		this.updateQueue.set( clientId, { clientId, updateFn } );
	},

	/**
	 * Unregister a block from device update processing.
	 *
	 * Called during block unmounting to prevent memory leaks and
	 * avoid processing non-existent blocks.
	 * Fixed to properly remove items without iteration issues.
	 *
	 * @param {string} clientId - Unique WordPress block identifier to remove.
	 * @since x.x.x
	 */
	unregister( clientId ) {
		this.updateQueue.delete( clientId );
	},

	/**
	 * Cancel all pending batch processing immediately.
	 *
	 * Critical for responsive device switching - when user rapidly switches
	 * from Desktop→Tablet→Mobile, this ensures Tablet processing stops
	 * immediately and Mobile processing starts fresh.
	 *
	 * @since x.x.x
	 */
	cancelPendingProcessing() {
		this.processingTimeouts.forEach( ( timeoutId ) => clearTimeout( timeoutId ) );
		this.processingTimeouts.clear();
		this.isProcessing = false;
	},

	/**
	 * Complete cleanup of all resources to prevent memory leaks.
	 *
	 * @since x.x.x
	 */
	cleanup() {
		this.cancelPendingProcessing();
		this.updateQueue.clear();
		this.currentDevice = null;
	},

	/**
	 * Process device change for all registered blocks in optimized batches.
	 * CLEAN SLATE APPROACH: Clear everything before starting to prevent memory accumulation.
	 *
	 * @param {string} newDevice - Target device type ('Desktop', 'Tablet', 'Mobile').
	 * @since x.x.x
	 */
	processDeviceChange( newDevice ) {
		// Immediate cancellation for rapid switching
		if ( this.isProcessing && this.currentDevice !== newDevice ) {
			this.cancelPendingProcessing();
		}

		// Prevent duplicate processing of same device
		if ( this.currentDevice === newDevice ) return;

		// CLEAN SLATE: Clear everything before starting batch processing
		this.cancelPendingProcessing();

		this.isProcessing = true;
		this.currentDevice = newDevice;

		// Get current snapshot of blocks (they may register/unregister during processing)
		const batchSize = DEVICE_SWITCH_BATCH_SIZE;
		const items = Array.from( this.updateQueue.values() );
		let index = 0;

		const processBatch = () => {
			// Safety check: ensure device hasn't changed during async processing
			if ( this.currentDevice !== newDevice ) {
				return; // Clean exit, no cleanup needed since we clear at start
			}

			// Process current batch of blocks
			const endIndex = Math.min( index + batchSize, items.length );
			for ( let i = index; i < endIndex; i++ ) {
				const { updateFn } = items[ i ];
				if ( updateFn ) {
					try {
						updateFn( newDevice );
					} catch ( e ) {
						// Continue processing other blocks if one fails
					}
				}
			}

			// Move to next batch
			index = endIndex;
			if ( index < items.length ) {
				// Schedule next batch
				const timeoutId = setTimeout( processBatch, 1 );
				this.processingTimeouts.add( timeoutId );
			} else {
				// All blocks processed - mark complete and clear
				this.isProcessing = false;
				this.processingTimeouts.clear();
			}
		};

		// Start processing immediately
		processBatch();
	},
};

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
export const withDeviceViewUpdate = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { attributes, name, clientId } = props;
		const { responsiveControls = {} } = attributes;

		// Get dispatch functions for updating block attributes.
		const { __unstableMarkNextChangeAsNotPersistent, updateBlockAttributes } = useDispatch( 'core/block-editor' );

		// Determine if responsive controls should be applied to this block.
		const isResponsive = isAllowedBlock( { name } );

		if ( ! isResponsive ) {
			return <BlockEdit { ...props } />;
		}

		// Track previous device to conditionally skip.
		const prevDevice = useRef( DESKTOP ); // Initialize with default device.

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

		// Store current values in refs to avoid effect re-runs.
		const attributesRef = useRef( attributes );
		const responsiveControlsRef = useRef( responsiveControls );

		// Update refs on every render.
		attributesRef.current = attributes;
		responsiveControlsRef.current = responsiveControls;

		// Device update function for this specific block - memoized with latest refs.
		const handleDeviceUpdate = useCallback(
			( newDeviceType ) => {
				// Use the latest values from refs to avoid stale closures
				const currentAttributes = attributesRef.current;
				const currentResponsiveControls = responsiveControlsRef.current;

				if ( Object.keys( currentResponsiveControls ).length === 0 ) {
					prevDevice.current = newDeviceType;
					return;
				}

				// Extract responsive attributes first to check if there's anything to process
				const responsiveAttrs = extractResponsiveAttributes( currentAttributes, name );

				// Skip if switching between devices within different scenarios.
				const from = prevDevice.current;
				const to = newDeviceType;

				// Also check for block-specific responsive keys that exist in responsiveControls
				// but not in currentAttributes (attributes without defaults in block.json).
				const blockResponsiveKeys = getBlockResponsiveKeys( name );
				const breakpoints = [ 'lg', 'md', 'sm' ];
				for ( const key of blockResponsiveKeys ) {
					if ( ! ( key in responsiveAttrs ) ) {
						// Check if this key exists in any breakpoint of responsiveControls
						for ( const bp of breakpoints ) {
							if ( currentResponsiveControls[ bp ]?.[ key ] !== undefined ) {
								// Initialize with undefined so deepMergeAttributes can fill it from responsiveControls
								responsiveAttrs[ key ] = undefined;
								break;
							}
						}
					}
				}

				// Early exit if no responsive attributes to process
				if ( Object.keys( responsiveAttrs ).length === 0 ) {
					prevDevice.current = to;
					return;
				}

				// Skip device updates using optimized logic (only if we have responsive controls)
				if ( shouldSkipDeviceUpdate( from, to, currentResponsiveControls ) ) {
					prevDevice.current = to;
					return;
				}

				// Merge responsive attributes based on the current device type.
				const mergedResponsiveAttributes = deepMergeAttributes(
					responsiveAttrs,
					currentResponsiveControls,
					to,
					name
				);

				// Use optimized comparison that filters out functions and undefined values.
				const mergedDiffers = shouldUpdateData( responsiveAttrs, mergedResponsiveAttributes );

				if ( ! mergedDiffers ) {
					prevDevice.current = to;
					return;
				}

				// Update block attributes with responsive changes while preserving non-responsive properties.
				// The style object contains both responsive keys (spacing, border, typography, etc.)
				// and non-responsive keys (color, etc.) that must be preserved.
				const mergedAttributes = {
					...currentAttributes,
					...mergedResponsiveAttributes,
				};

				// Safely merge style objects if they exist
				if ( currentAttributes.style || mergedResponsiveAttributes.style ) {
					mergedAttributes.style = {
						...( currentAttributes.style || {} ),
						...( mergedResponsiveAttributes.style || {} ),
					};
				}

				// Critical check: Only update if merged attributes actually differ from current attributes.
				// This prevents unnecessary updates that trigger the save button on initial load.
				const attributesChanged = shouldUpdateData( currentAttributes, mergedAttributes );

				if ( attributesChanged ) {
					__unstableMarkNextChangeAsNotPersistent();
					updateBlockAttributes( clientId, mergedAttributes );
				}

				prevDevice.current = to;
			},
			[ name, clientId, __unstableMarkNextChangeAsNotPersistent, updateBlockAttributes ]
		);

		// Register this block with global device manager.
		useEffect( () => {
			// Initialize prevDevice with current device when registering.
			const currentDevice = dataSelect( 'core/editor' )?.getDeviceType?.() || DESKTOP;
			prevDevice.current = currentDevice;

			GlobalDeviceUpdateManager.register( clientId, handleDeviceUpdate );

			// Trigger initial update to ensure desktop values are reflected on load (critical for FSE).
			// Use requestAnimationFrame to defer until after initial render to minimize save button activation.
			const rafId = requestAnimationFrame( () => {
				handleDeviceUpdate( currentDevice );
			} );

			return () => {
				cancelAnimationFrame( rafId );
				GlobalDeviceUpdateManager.unregister( clientId );
			};
		}, [ clientId, handleDeviceUpdate ] );

		// Render the original block edit component with the same props.
		return <BlockEdit { ...props } />;
	};
}, 'withDeviceViewUpdate' );
