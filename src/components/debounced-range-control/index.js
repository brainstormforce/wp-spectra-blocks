/**
 * External dependencies.
 */
import { useState, useRef, useCallback, useEffect } from '@wordpress/element';
import { RangeControl } from '@wordpress/components';

/**
 * Debounced RangeControl Component
 * 
 * Wraps the WordPress RangeControl to only trigger onChange when user stops sliding.
 * This prevents excessive re-renders and improves editor performance.
 *
 * @param {Object}   props                The component props.
 * @param {number}   props.value          The current value.
 * @param {Function} props.onChange       Callback when value changes (debounced).
 * @param {boolean}  props.enableDebounce Enable/disable debouncing (default: true).
 * @param {boolean}  props.showLivePreview Show live preview during sliding (default: true).
 * @since x.x.x
 * @return {Element} The debounced RangeControl component.
 */
const DebouncedRangeControl = ( props ) => {
	const {
		value,
		onChange,
		enableDebounce = true,
		showLivePreview = true,
		...restProps
	} = props;
	// Internal state for immediate visual feedback.
	const [ internalValue, setInternalValue ] = useState( value );
	const [ isInteracting, setIsInteracting ] = useState( false );
	
	// Refs for managing timeouts and interaction state.
	const debounceTimeoutRef = useRef( null );
	const isMouseDownRef = useRef( false );
	const isTouchActiveRef = useRef( false );
	const rangeControlRef = useRef( null );
	const previousValueRef = useRef( value );

	// Sync internal value when external value changes.
	useEffect( () => {
		if ( ! isInteracting ) {
			setInternalValue( value );
		}
		previousValueRef.current = value;
	}, [ value, isInteracting ] );

	// Optimized ref-based solution to detect and handle empty input field
	useEffect( () => {
		const rangeControl = rangeControlRef.current;
		if ( ! rangeControl ) return;

		// Find the input field within the RangeControl
		const inputField = rangeControl.querySelector( 'input[type="number"]' );
		if ( ! inputField ) return;

		// Memoized handlers to prevent recreation on every render
		const handleInputChange = ( event ) => {
			// Only handle empty input for performance
			if ( event.target.value === '' && onChange ) {
				onChange( undefined );
			}
		};

		const handleKeyDown = ( event ) => {
			// Only process backspace events
			if ( event.key !== 'Backspace' || ! onChange ) return;
			
			const target = event.target;
			const { value: inputValue, selectionStart, selectionEnd } = target;
			
			// Check if backspace will clear the entire field
			const willBeEmpty = (
				( selectionStart === 0 && selectionEnd === inputValue.length ) || // All selected
				( inputValue.length === 1 && selectionStart === 1 ) // Last character
			);
			
			if ( willBeEmpty ) {
				// Use requestAnimationFrame instead of setTimeout for better performance
				requestAnimationFrame( () => onChange( undefined ) );
			}
		};

		// Add event listeners with passive option for better performance
		inputField.addEventListener( 'input', handleInputChange, { passive: true } );
		inputField.addEventListener( 'keydown', handleKeyDown );

		// Cleanup
		return () => {
			inputField.removeEventListener( 'input', handleInputChange );
			inputField.removeEventListener( 'keydown', handleKeyDown );
		};
	}, [ onChange ] );

	// Clear timeout on unmount.
	useEffect( () => {
		return () => {
			if ( debounceTimeoutRef.current ) {
				clearTimeout( debounceTimeoutRef.current );
			}
		};
	}, [] );


	/**
	 * Handle value changes during sliding.
	 */
	const handleChange = useCallback( ( newValue ) => {
		// Always update internal value for immediate visual feedback.
		setInternalValue( newValue );

		// If debouncing is disabled, call onChange immediately.
		if ( ! enableDebounce ) {
			if ( onChange ) {
				onChange( newValue );
			}
			return;
		}

		// If we're NOT interacting (e.g., programmatic change or input field), call onChange immediately.
		if ( ! isInteracting ) {
			if ( onChange ) {
				onChange( newValue );
			}
		}
		// If we're interacting (dragging), DON'T call onChange - wait for interaction end.
	}, [ enableDebounce, onChange, isInteracting ] );

	/**
	 * Handle interaction start (mouse/touch down)
	 */
	const handleInteractionStart = useCallback( ( event ) => {
		setIsInteracting( true );
		
		// Track interaction type.
		if ( event.type === 'mousedown' ) {
			isMouseDownRef.current = true;
		} else if ( event.type === 'touchstart' ) {
			isTouchActiveRef.current = true;
		}

		// Clear any existing timeout.
		if ( debounceTimeoutRef.current ) {
			clearTimeout( debounceTimeoutRef.current );
		}

		// Call original event handler if provided.
		if ( restProps.onMouseDown && event.type === 'mousedown' ) {
			restProps.onMouseDown( event );
		}
		if ( restProps.onTouchStart && event.type === 'touchstart' ) {
			restProps.onTouchStart( event );
		}
	}, [ restProps.onMouseDown, restProps.onTouchStart ] );

	/**
	 * Handle interaction end (mouse/touch up, blur, leave)
	 */
	const handleInteractionEnd = useCallback( ( event ) => {
		const eventType = event.type;
		
		// Only end interaction for relevant events.
		const shouldEndInteraction = (
			( eventType === 'mouseup' && isMouseDownRef.current ) ||
			( eventType === 'touchend' && isTouchActiveRef.current ) ||
			eventType === 'mouseleave' ||
			eventType === 'blur'
		);

		if ( shouldEndInteraction && isInteracting && enableDebounce ) {
			// Clear any existing timeout.
			if ( debounceTimeoutRef.current ) {
				clearTimeout( debounceTimeoutRef.current );
			}
			
			// Trigger immediate change if value is different.
			if ( onChange && internalValue !== value ) {
				onChange( internalValue );
			}
			
			setIsInteracting( false );
		} else if ( shouldEndInteraction && ! enableDebounce ) {
			// If debouncing is disabled, just reset interaction state.
			setIsInteracting( false );
		}

		// Reset interaction tracking.
		if ( eventType === 'mouseup' ) {
			isMouseDownRef.current = false;
		} else if ( eventType === 'touchend' ) {
			isTouchActiveRef.current = false;
		}

		// Call original event handlers if provided.
		if ( restProps.onMouseUp && eventType === 'mouseup' ) {
			restProps.onMouseUp( event );
		}
		if ( restProps.onTouchEnd && eventType === 'touchend' ) {
			restProps.onTouchEnd( event );
		}
		if ( restProps.onMouseLeave && eventType === 'mouseleave' ) {
			restProps.onMouseLeave( event );
		}
		if ( restProps.onBlur && eventType === 'blur' ) {
			restProps.onBlur( event );
		}
	}, [ isInteracting, enableDebounce, onChange, internalValue, value, restProps ] );

	// Determine which value to display.
	const displayValue = showLivePreview ? internalValue : value;

	return (
		<div ref={ rangeControlRef }>
			<RangeControl
				{ ...restProps }
				value={ displayValue }
				onChange={ handleChange }
				onMouseDown={ handleInteractionStart }
				onMouseUp={ handleInteractionEnd }
				onMouseLeave={ handleInteractionEnd }
				onTouchStart={ handleInteractionStart }
				onTouchEnd={ handleInteractionEnd }
				onBlur={ handleInteractionEnd }
			/>
		</div>
	);
};

export default DebouncedRangeControl;