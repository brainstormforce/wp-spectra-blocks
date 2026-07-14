/**
 * Convert array of classes into a single string.
 *
 * @param {Array} classes Selectors to combine.
 * @return {string}         The single-line selector string.
 *
 * @since 2.6.0
 */
export const spectraBlocksClassNames = ( classes ) => ( classes.filter( Boolean ).join( ' ' ) );

/**
 * Format a number to add commas as thousand separators.
 *
 * @param {number} number The number to format.
 * @return {string}        The number with commas at every thousand.
 *
 * @since 2.10.2
 */
export const displayInThousands = ( number ) => ( number.toString().replace( /\B(?=(\d{3})+(?!\d))/g, ',' ) );

/**
 * Check if an object is not empty.
 *
 * @param {Object} obj The object to check.
 * @return {boolean} True if the object is not empty.
 */
export const isObjectNotEmpty = ( obj ) => {
	return typeof obj === 'object' && obj !== null && Object.keys( obj ).length > 0;
};

/**
 * Debounce a function call.
 *
 * @param {Function} func  Function to debounce.
 * @param {number}   delay Delay in milliseconds.
 * @return {Function} Debounced function.
 */
export const debounce = ( func, delay ) => {
	let timer;
	return function ( ...args ) {
		clearTimeout( timer );
		timer = setTimeout( () => func.apply( this, args ), delay );
	};
};
