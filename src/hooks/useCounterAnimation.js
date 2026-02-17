/**
 * Custom hook for counter animation in the editor
 * 
 * @since x.x.x
 */

import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Easing functions for smooth animations.
 */
const easingFunctions = {
	linear: ( t ) => t,
	easeOutQuart: ( t ) => 1 - Math.pow( 1 - t, 4 ),
	easeOutCubic: ( t ) => 1 - Math.pow( 1 - t, 3 ),
	easeOutBack: ( t ) => {
		const c1 = 1.70158;
		const c3 = c1 + 1;
		return 1 + c3 * Math.pow( t - 1, 3 ) + c1 * Math.pow( t - 1, 2 );
	},
	easeOutBounce: ( t ) => {
		const n1 = 7.5625;
		const d1 = 2.75;
		if ( t < 1 / d1 ) {
			return n1 * t * t;
		} else if ( t < 2 / d1 ) {
			return n1 * ( t -= 1.5 / d1 ) * t + 0.75;
		} else if ( t < 2.5 / d1 ) {
			return n1 * ( t -= 2.25 / d1 ) * t + 0.9375;
		} 
			return n1 * ( t -= 2.625 / d1 ) * t + 0.984375;
		
	},
};

/**
 * Format number with prefix, suffix, separator, and decimal places.
 * 
 * @param {number} number The number to format
 * @param {string} prefix The prefix to add
 * @param {string} suffix The suffix to add
 * @param {string} separator The thousand separator
 * @param {number} decimals The number of decimal places
 * @return {string} The formatted number
 */
function formatNumber( number, prefix = '', suffix = '', separator = ',', decimals = 0 ) {
	// Ensure all parameters are strings to prevent concatenation issues
	const safePrefix = String( prefix || '' );
	const safeSuffix = String( suffix || '' );
	const safeSeparator = separator !== undefined ? String( separator ) : ',';
	const safeDecimals = Number( decimals ) || 0;
	
	// Format to specified decimal places (keeps trailing zeros).
	const formatted = Number( number ).toFixed( safeDecimals );
	
	// Split into integer and decimal parts.
	const parts = formatted.split( '.' );
	
	// Add thousand separators (only if separator is not empty or 'none').
	if ( safeSeparator && safeSeparator !== 'none' ) {
		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, safeSeparator );
	}
	
	return safePrefix + parts.join( '.' ) + safeSuffix;
}

/**
 * Custom hook for counter animation
 * 
 * @param {Object} options Animation options
 * @return {string} The current animated number display
 */
export const useCounterAnimation = ( options = {} ) => {
	const {
		startNumber = 0,
		endNumber = 100,
		duration = 2000,
		easing = 'linear',
		prefix = '',
		suffix = '',
		separator = ',',
		decimals = 0,
		triggerAnimation = true,
	} = options;

	const [ currentNumber, setCurrentNumber ] = useState( startNumber );
	const animationRef = useRef( null );
	const startTimeRef = useRef( null );

	useEffect( () => {
		// Clear any existing animation.
		if ( animationRef.current ) {
			cancelAnimationFrame( animationRef.current );
		}

		// Reset to start number.
		setCurrentNumber( startNumber );

		// Only animate if triggerAnimation is true.
		if ( ! triggerAnimation ) {
			return;
		}

		const easingFunction = easingFunctions[ easing ] || easingFunctions.linear;
		const difference = endNumber - startNumber;
		startTimeRef.current = performance.now();

		const animate = ( currentTime ) => {
			const elapsed = currentTime - startTimeRef.current;
			const progress = Math.min( elapsed / duration, 1 );
			const easedProgress = easingFunction( progress );
			const newNumber = startNumber + ( difference * easedProgress );
			
			setCurrentNumber( newNumber );

			// Continue animation if not complete
			if ( progress < 1 ) {
				animationRef.current = requestAnimationFrame( animate );
			} else {
				// Ensure final value is set correctly when animation completes
				setCurrentNumber( endNumber );
			}
		};

		// Start animation after a short delay to ensure smooth transition
		const timeoutId = setTimeout( () => {
			animationRef.current = requestAnimationFrame( animate );
		}, 100 );

		return () => {
			clearTimeout( timeoutId );
			if ( animationRef.current ) {
				cancelAnimationFrame( animationRef.current );
			}
		};
	}, [ startNumber, endNumber, duration, easing, triggerAnimation, prefix, suffix, separator, decimals ] );

	// Format the current number for display.
	return formatNumber( currentNumber, prefix, suffix, separator, decimals );
};

export default useCounterAnimation;
