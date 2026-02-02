/**
 * Shadow Helper Functions
 * 
 * Utility functions for handling box shadow styles in Spectra blocks.
 * Provides parsing, generation, and style application for shadow attributes.
 * 
 * @since 3.0.0
 */

/**
 * Parse shadow CSS string into shadow object
 * 
 * @param {string} shadowString CSS shadow string
 * @return {Object} Parsed shadow object
 */
export const parseShadowString = ( shadowString ) => {
	if ( ! shadowString || shadowString === '' ) {
		return {
			color: '',
			x: 0,
			y: 0,
			blur: 0,
			spread: 0,
			inset: false,
		};
	}

	// Simple parsing - can be enhanced for more complex cases
	const isInset = shadowString.includes( 'inset' );
	const cleanString = shadowString.replace( 'inset', '' ).trim();
	const parts = cleanString.split( ' ' );
	
	// Extract numeric values and color
	const numericParts = [];
	let color = '';
	
	parts.forEach( part => {
		if ( part.includes( 'px' ) || part.includes( 'rem' ) || part.includes( 'em' ) ) {
			numericParts.push( parseInt( part ) || 0 );
		} else if ( part.includes( '#' ) || part.includes( 'rgb' ) || part.includes( 'hsl' ) ) {
			color = part;
		}
	} );

	return {
		color,
		x: numericParts[0] || 0,
		y: numericParts[1] || 0,
		blur: numericParts[2] || 0,
		spread: numericParts[3] || 0,
		inset: isInset,
	};
};

/**
 * Generate CSS shadow string from shadow object
 * 
 * @param {Object} shadowObj Shadow object
 * @return {string} CSS shadow string
 */
export const generateShadowString = ( shadowObj ) => {
	if ( ! shadowObj ) {
		return '';
	}
	
	const { x, y, blur, spread, color, inset } = shadowObj;
	
	// Use fallback color if empty, but only for CSS generation
	const finalColor = color || 'transparent';
	
	return `${inset ? 'inset ' : ''}${x}px ${y}px ${blur}px ${spread}px ${finalColor}`;
};

/**
 * Get shadow styles for CSS application
 * 
 * @param {Object} attributes Block attributes
 * @param {string} shadowAttribute Name of the shadow attribute (default: 'boxShadow')
 * @return {Object} CSS styles object
 */
export const getShadowStyles = ( attributes, shadowAttribute = 'boxShadow' ) => {
	const shadowValue = attributes[ shadowAttribute ];
	
	if ( ! shadowValue || shadowValue === '' ) {
		return {};
	}

	return {
		'box-shadow': shadowValue,
	};
};

/**
 * Get multiple shadow styles for different states (normal, hover, etc.)
 * This function returns configuration for useSpectraStyles hook
 * 
 * @param {Object} attributes Block attributes
 * @param {Object} config Shadow configuration object
 * @param {string} config.normal Normal state shadow attribute name
 * @param {string} config.hover Hover state shadow attribute name
 * @return {Array} Configuration array for useSpectraStyles hook
 */
export const getMultiStateShadowStyles = ( attributes, config = {} ) => {
	const {
		normal = 'boxShadow',
		hover = 'boxShadowHover',
	} = config;

	const shadowConfig = [];

	// Normal state shadow
	const normalShadow = attributes[ normal ];
	const hoverShadow = attributes[ hover ];
	
	if ( normalShadow && normalShadow !== '' ) {
		shadowConfig.push( {
			key: normal,
			cssVar: '--spectra-box-shadow',
			className: 'spectra-box-shadow',
			value: normalShadow,
		} );
	}

	// Hover state shadow - only add hover class if hover shadow is explicitly set
	if ( hoverShadow && hoverShadow !== '' ) {
		shadowConfig.push( {
			key: hover,
			cssVar: '--spectra-box-shadow-hover',
			className: 'spectra-box-shadow-hover',
			value: hoverShadow,
		} );
	}

	return shadowConfig;
};

/**
 * Default shadow presets
 */
export const DEFAULT_SHADOW_PRESETS = [
	{
		name: 'None',
		value: '',
	},
	{
		name: 'Small',
		value: '0px 1px 3px rgba(0, 0, 0, 0.12)',
	},
	{
		name: 'Medium',
		value: '0px 4px 8px rgba(0, 0, 0, 0.15)',
	},
	{
		name: 'Large',
		value: '0px 8px 16px rgba(0, 0, 0, 0.15)',
	},
	{
		name: 'Extra Large',
		value: '0px 16px 32px rgba(0, 0, 0, 0.15)',
	},
	{
		name: 'Inner Small',
		value: 'inset 0px 1px 3px rgba(0, 0, 0, 0.12)',
	},
	{
		name: 'Inner Medium',
		value: 'inset 0px 2px 6px rgba(0, 0, 0, 0.15)',
	},
];

/**
 * Check if shadow value has content
 * 
 * @param {string} shadowValue Shadow CSS string
 * @return {boolean} Whether shadow has content
 */
export const hasShadowValue = ( shadowValue ) => {
	return !! shadowValue && shadowValue !== '' && shadowValue !== 'none';
};
