/**
 * Custom hook for progress animation in the editor
 * 
 * @since x.x.x
 */

import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Easing functions for smooth animations
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
 * Custom hook for progress animation
 * 
 * @param {Object} options Animation options
 * @return {Object} Progress animation values
 */
export const useProgressAnimation = ( options = {} ) => {
	const {
		startNumber = 0,
		endNumber = 100,
		totalNumber = 100,
		duration = 2000,
		easing = 'linear',
		progressSize = '300px',
		strokeWidth = 8,
		triggerAnimation = true,
	} = options;

	const [ progress, setProgress ] = useState( 0 );
	const [ animationProgress, setAnimationProgress ] = useState( startNumber );
	const animationRef = useRef( null );
	const startTimeRef = useRef( null );

	useEffect( () => {
		// Clear any existing animation
		if ( animationRef.current ) {
			cancelAnimationFrame( animationRef.current );
		}

		// Reset progress to starting values
		setProgress( 0 );
		setAnimationProgress( startNumber );

		// Only animate if triggerAnimation is true
		if ( ! triggerAnimation ) {
			return;
		}

		const easingFunction = easingFunctions[ easing ] || easingFunctions.linear;
		const difference = endNumber - startNumber;
		startTimeRef.current = performance.now();

		const animate = ( currentTime ) => {
			const elapsed = currentTime - startTimeRef.current;
			const progressPercent = Math.min( elapsed / duration, 1 );
			const easedProgress = easingFunction( progressPercent );
			const currentNumber = startNumber + ( difference * easedProgress );
			
			// For circular progress: calculate based on the range from startNumber to endNumber
			// This ensures the circular progress matches the counter number exactly
			const progressValue = ( ( currentNumber - startNumber ) / ( endNumber - startNumber ) ) * 100;
			setProgress( Math.min( Math.max( progressValue, 0 ), 100 ) );
			
			// Store the actual current number for accurate calculations
			setAnimationProgress( currentNumber );

			// Continue animation if not complete
			if ( progressPercent < 1 ) {
				animationRef.current = requestAnimationFrame( animate );
			}
		};

		// Start animation after a short delay
		const timeoutId = setTimeout( () => {
			animationRef.current = requestAnimationFrame( animate );
		}, 100 );

		return () => {
			clearTimeout( timeoutId );
			if ( animationRef.current ) {
				cancelAnimationFrame( animationRef.current );
			}
		};
	}, [ startNumber, endNumber, totalNumber, duration, easing, triggerAnimation ] );

	// Calculate circular progress values
	const size = parseInt( ( progressSize || '300px' ).replace( 'px', '' ) ) || 300;
	// Calculate radius: half the size minus half the stroke width (stroke is centered on the path)
	const radius = ( size / 2 ) - ( ( strokeWidth || 8 ) / 2 );
	const circumference = 2 * Math.PI * radius;
	
	// Calculate stroke-dashoffset properly:
	// - Start from full circumference (no progress shown)
	// - Subtract the progress portion to reveal the stroke
	// - This ensures the progress starts from 0% and fills to the current progress
	const strokeDashoffset = circumference - ( progress / 100 ) * circumference;

	return {
		progress,
		animationProgress,
		circumference,
		strokeDashoffset,
		radius,
	};
};

export default useProgressAnimation;
