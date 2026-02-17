/**
 * Optimized styles hook for countdown blocks.
 * 
 * This hook provides memoized style calculations with better caching
 * to reduce the performance impact when multiple countdown blocks are present.
 * 
 * @since x.x.x
 */

import { useMemo } from '@wordpress/element';
import { useSpectraStyles } from '@spectra-hooks';

// Create a WeakMap cache for style configurations
const styleConfigCache = new WeakMap();

/**
 * Get or create a cached style configuration.
 * 
 * @since x.x.x
 * @param {Array} config - The style configuration array.
 * @return {Array} The cached configuration.
 */
const getCachedConfig = ( config ) => {
	if ( styleConfigCache.has( config ) ) {
		return styleConfigCache.get( config );
	}
	
	// Create a frozen copy to ensure immutability
	const cachedConfig = Object.freeze( config.map( item => Object.freeze( { ...item } ) ) );
	styleConfigCache.set( config, cachedConfig );
	return cachedConfig;
};

/**
 * Optimized countdown styles hook.
 * 
 * @since x.x.x
 * @param {Object} attributes - Block attributes.
 * @param {Array} config - Style configuration.
 * @param {Array} additionalClasses - Additional CSS classes.
 * @return {Object} Style and class names.
 */
export const useCountdownStyles = ( attributes, config, additionalClasses = [] ) => {
	// Create a stable reference for the config
	const stableConfig = useMemo( 
		() => getCachedConfig( config ),
		[ config ]
	);
	
	// Create a stable reference for additional classes
	const stableAdditionalClasses = useMemo(
		() => additionalClasses,
		[ additionalClasses.join( ',' ) ]
	);
	
	// Extract only the attributes that affect styles
	const styleAttributes = useMemo( () => {
		const {
			textColor,
			textColorHover,
			backgroundColor,
			backgroundColorHover,
			backgroundGradient,
			backgroundGradientHover,
			typography,
			style,
			className,
			...rest
		} = attributes;
		
		// Return only style-related attributes
		return {
			textColor,
			textColorHover,
			backgroundColor,
			backgroundColorHover,
			backgroundGradient,
			backgroundGradientHover,
			typography,
			style,
			className,
			// Include any other style-related attributes from rest that match config keys
			...Object.keys( rest ).reduce( ( acc, key ) => {
				if ( stableConfig.some( item => item.key === key ) ) {
					acc[ key ] = rest[ key ];
				}
				return acc;
			}, {} )
		};
	}, [
		attributes.textColor,
		attributes.textColorHover,
		attributes.backgroundColor,
		attributes.backgroundColorHover,
		attributes.backgroundGradient,
		attributes.backgroundGradientHover,
		attributes.typography,
		attributes.style,
		attributes.className,
		// Only re-compute if style-related attributes change
		JSON.stringify( stableConfig.map( item => attributes[ item.key ] ) )
	] );
	
	// Use the optimized attributes with the original hook
	return useSpectraStyles( styleAttributes, stableConfig, stableAdditionalClasses );
};

