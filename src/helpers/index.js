/**
 * Function to concatenate an array of valid classes into a single string.
 *
 * @param {Array} classes
 * @since x.x.x
 * @return {string} The concatinated classes.
 */
export const spectraClassNames = ( classes ) => classes.filter( Boolean ).join( ' ' );

/**
 * Function to parse a ratio string into its floating point value.
 *
 * @param {string} ratio contains the sting in the format 'x/y'.
 * @param {boolean} calculate determines if the processed ratio value should be returned or just the numbers.
 * @since x.x.x
 * @return {number | Array | false} The floating point prosessed value, the ratio values in an array, or specifically false.
 */
export const parseRatio = ( ratio, calculate = true ) => {
	// If this isn't valid, abandon ship.
	if ( ! ratio?.toLowerCase || [ '', 'auto' ].includes( ratio.toLowerCase() ) ) {
		return false;
	}
	// Split the ratio, and if there's a second part, return the calculation. Else just return the value.
	const splitRatio = ratio.split( '/' );

	// If you don't need to calculate, directly return the array.
	let parsedRatio;
	if ( calculate ) {
		// If there's only one number, return it - else calculate the ratio.
		parsedRatio = !! splitRatio[ 1 ]
			? parseInt( splitRatio[ 0 ] ) / parseInt( splitRatio[ 1 ] )
			: parseInt( splitRatio );
	} else {
		// If there's only one number, return it as the second value as well.
		parsedRatio = [ splitRatio[ 0 ], splitRatio[ 1 ] || splitRatio[ 0 ] ];
	}
	return parsedRatio;
};

/**
 * Function to capitalize the first letter in a string.
 *
 * @param {string} string the given string.
 * @since x.x.x
 * @return {string} The string with the first letter capitalized.
 */
export const capitalizeFirstLetter = ( string ) =>
	String( string ).charAt( 0 ).toUpperCase() + String( string ).slice( 1 );

/**
 * Function to get the text of a string after the last slash.
 *
 * @param {string} string the given string.
 * @since x.x.x
 * @return {string} The substring after the last slash.
 */
export const getTextAfterLastSlash = ( string ) => {
	// Get the last slash, and if it exists - get the text after it. Else just return the string as is.
	const lastSlashIndex = string.lastIndexOf( '/' );
	return lastSlashIndex === -1 ? string : string.slice( lastSlashIndex + 1 );
};

/**
 * Function to remove the 'child' word from the given blockname.
 *
 * @param {string} blockname the given block name.
 * @since x.x.x
 * @return {string} The blockname without the child keyword inside it.
 */
export const removeChildFromBlockName = ( blockname ) => blockname.replace( '-child-', '-' );

/**
 * Function to convert a string from camelCase or PascalCase to kebab-case.
 *
 * @param {string} string the given string.
 * @since x.x.x
 * @return {string} The string in kebab-case.
 */
export const convertToKebabCase = ( string ) => {
	return string.replace( /([a-z0-9])([A-Z])/g, '$1-$2' ).toLowerCase();
};

/**
 * Function to get icon name for accessibility purposes.
 *
 * @param {string|Object} iconValue The icon value (string for icon library, object for custom SVG).
 * @since x.x.x
 * @return {string} The icon name or description.
 */
export const getIconName = ( iconValue ) => {
	if ( ! iconValue ) {
		return 'circle-check';
	}
	
	// Handle custom SVG uploads (object format)
	if ( typeof iconValue === 'object' && iconValue.library === 'svg' ) {
		// Try to extract filename from URL
		if ( iconValue.value?.url ) {
			const url = iconValue.value.url;
			const filename = url.split( '/' ).pop().split( '.' )[0];
			return filename || 'custom SVG';
		}
		return 'custom SVG';
	}
	
	// Handle icon library icons (string format)
	if ( typeof iconValue === 'string' ) {
		return iconValue;
	}
	
	return 'circle-check';
};

/**
 * Re-export SVG security utilities
 */
export { processSpectraSVG, validateSVGStructure, isSVGUploadEnabled } from './svg-security';

/**
 * Re-export enhanced gradient hook
 */
export { useEnhancedGradient } from './use-enhanced-gradient';
