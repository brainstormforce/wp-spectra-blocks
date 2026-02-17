/**
 * External dependencies.
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { useEffect, useRef } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';

/**
 * The render function for the Counter block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Render = ( props ) => {
	const {
		attributes,
		clientId,
	} = props;
	
	const {
	counterStyle = 'simple',
	startNumber: rawStartNumber = 0,
	endNumber: rawEndNumber = 100,
	animationDuration = 2000,
	prefix = '',
	suffix = '',
	thousandSeparator,
	decimalPlaces = 0,
		progressColor,
		progressBackgroundColor,
		progressSize,
		progressStrokeWidth,
		barHeight,
		barBorderRadius,
		textColor,
		backgroundColor,
		backgroundGradient,
		totalNumber = 100,
		prefixRightMargin,
		suffixLeftMargin,
		prefixColor,
		suffixColor,
		style: blockStyle,
	} = attributes;

	// Extract WordPress core colors to use in Spectra system (like content block).
	const wpTextColor = blockStyle?.color?.text;
	const wpBackgroundColor = blockStyle?.color?.background;

	// Dynamic template based on counter style - all wrapped in counter-child-wrapper
	const TEMPLATE = counterStyle === 'bar' 
		? [ 
			[ 'spectra/counter-child-wrapper', {}, [
				[ 'spectra/icons' ],
				[ 'spectra/counter-child-number' ],
				[ 'spectra/content', { placeholder: 'Add your content...' } ],
				[ 'spectra/counter-child-progress-bar' ],
			] ]
		]
		: [ 
			[ 'spectra/counter-child-wrapper', {}, [
				[ 'spectra/icons' ],
				[ 'spectra/counter-child-number' ],
				[ 'spectra/content', { placeholder: 'Add your content...' } ]
			] ]
		];

	// Ensure values are numbers to prevent NaN issues (needed for useEffect dependencies).
	const startNumber = typeof rawStartNumber === 'number' ? rawStartNumber : parseFloat( rawStartNumber ) || 0;
	const endNumber = typeof rawEndNumber === 'number' ? rawEndNumber : parseFloat( rawEndNumber ) || 100;

	// Calculate circular progress values (needed for useEffect dependencies).
	const size = parseInt( ( progressSize || '300px' ).replace( 'px', '' ) ) || 300;
	const radius = ( size / 2 ) - ( ( progressStrokeWidth || 8 ) / 2 );
	const circumference = 2 * Math.PI * radius;

	// Get dispatch functions for block management.
	const { insertBlock, removeBlock } = useDispatch( 'core/block-editor' );

	// Get all inner blocks (including nested ones).
	const innerBlocks = useSelect( 
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ]
	);

	// Effect to manage progress bar block based on counter style.
	useEffect( () => {
		// Check if we have any inner blocks.
		if ( ! innerBlocks || innerBlocks.length === 0 ) {
			return;
		}

		// Find the counter-child-wrapper block.
		const wrapperBlock = innerBlocks.find(
			block => block.name === 'spectra/counter-child-wrapper'
		);

		if ( ! wrapperBlock ) {
			return;
		}

		// Get the inner blocks of the wrapper.
		const wrapperInnerBlocks = wrapperBlock.innerBlocks || [];

		// Find progress bar block if it exists inside the wrapper.
		const progressBarBlock = wrapperInnerBlocks.find( 
			block => block.name === 'spectra/counter-child-progress-bar' 
		);

		// For bar style: ensure progress bar exists inside wrapper.
		if ( counterStyle === 'bar' ) {
			// If progress bar doesn't exist, add it inside the wrapper.
			if ( ! progressBarBlock ) {
				const newProgressBarBlock = createBlock( 'spectra/counter-child-progress-bar' );
				
				// Find the position to insert (after counter-child-number).
				const counterNumberIndex = wrapperInnerBlocks.findIndex( 
					block => block.name === 'spectra/counter-child-number' 
				);
				
				const insertIndex = counterNumberIndex >= 0 ? counterNumberIndex + 1 : wrapperInnerBlocks.length;
				
				// Insert the progress bar block inside the wrapper (as grandchild of counter).
				insertBlock( newProgressBarBlock, insertIndex, wrapperBlock.clientId );
			}
		} else if ( progressBarBlock ) {
			// For simple or circular style: remove progress bar if it exists.
			removeBlock( progressBarBlock.clientId, false );
		}
	}, [ counterStyle, innerBlocks, clientId, insertBlock, removeBlock ] );

	// Use requestAnimationFrame for circular progress animation (synchronized with counter number).
	useEffect( () => {
		const duration = animationDuration || 2000;
		let animationRef = null;
		let startTimeRef = null;
		
		// Animate circular progress
		if ( circularProgressRef.current && counterStyle === 'circular' ) {
			// Ensure totalNumber defaults to 100 if not set, empty, or invalid.
			let totalNum;
			if ( totalNumber && totalNumber > 0 ) {
				totalNum = totalNumber;
			} else if ( endNumber && endNumber > 0 ) {
				totalNum = endNumber;
			} else {
				totalNum = 100;
			}
			
			// Calculate progress percentages - support both increment and decrement.
			// Add safeguards to prevent NaN in calculations.
			const safeStartNumber = isNaN( startNumber ) ? 0 : startNumber;
			const safeEndNumber = isNaN( endNumber ) ? 100 : endNumber;
			let safeTotalNum = isNaN( totalNum ) ? 100 : totalNum;
			
			// If total is smaller than both start and end numbers, use the larger number as effective total.
			if ( safeTotalNum < Math.max( Math.abs( safeStartNumber ), Math.abs( safeEndNumber ) ) ) {
				safeTotalNum = Math.max( Math.abs( safeStartNumber ), Math.abs( safeEndNumber ) );
			}
			
			// Calculate progress percentages based on totalNumber.
			let startPoint = 100 * ( safeStartNumber / safeTotalNum );
			startPoint = startPoint < 100 ? startPoint : 100;
			startPoint = 100 - startPoint;
			startPoint = ( startPoint / 100 ) * circumference;
			
			let endPoint = 100 * ( safeEndNumber / safeTotalNum );
			endPoint = endPoint < 100 ? endPoint : 100;
			endPoint = 100 - endPoint;
			endPoint = ( endPoint / 100 ) * circumference;
			
			// Set initial position.
			circularProgressRef.current.style.strokeDashoffset = `${startPoint}px`;
			
			// Animation function using requestAnimationFrame (same as counter number).
			const animate = ( currentTime ) => {
				if ( ! startTimeRef ) {
					startTimeRef = currentTime;
				}
				
				const elapsed = currentTime - startTimeRef;
				const progress = Math.min( elapsed / duration, 1 );
				
				// Linear easing to match counter number animation.
				const currentOffset = startPoint + ( ( endPoint - startPoint ) * progress );
				
				if ( circularProgressRef.current ) {
					circularProgressRef.current.style.strokeDashoffset = `${currentOffset}px`;
				}
				
				// Continue animation if not complete.
				if ( progress < 1 ) {
					animationRef = requestAnimationFrame( animate );
				}
			};
			
			// Start animation after a short delay (same as counter number - 100ms).
			const timeoutId = setTimeout( () => {
				animationRef = requestAnimationFrame( animate );
			}, 100 );
			
			// Cleanup function.
			return () => {
				clearTimeout( timeoutId );
				if ( animationRef ) {
					cancelAnimationFrame( animationRef );
				}
			};
		}
	}, [ counterStyle, animationDuration, progressSize, progressStrokeWidth, startNumber, endNumber, totalNumber, circumference ] );

// 	// Get typography classes and styles for progress bar text.
// 	const typographyProps = getTypographyClassesAndStyles( attributes );

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor', className: 'spectra-text-color', value: textColor || wpTextColor },
		{ key: 'backgroundColor', className: 'spectra-background-color', value: backgroundColor || wpBackgroundColor },
		{ key: 'backgroundGradient', className: 'spectra-background-gradient', value: backgroundGradient },
		{ 
			key: 'prefixRightMargin', 
			cssVar: '--spectra-prefix-right-margin', 
			className: null,
			value: prefixRightMargin !== undefined ? `${prefixRightMargin}px` : undefined
		},
		{ 
			key: 'suffixLeftMargin', 
			cssVar: '--spectra-suffix-left-margin', 
			className: null,
			value: suffixLeftMargin !== undefined ? `${suffixLeftMargin}px` : undefined
		},
		{ key: 'prefixColor', cssVar: '--spectra-prefix-color', className: null, value: prefixColor },
		{ key: 'suffixColor', cssVar: '--spectra-suffix-color', className: null, value: suffixColor },
	];


	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	// Add counter-specific styles.
	const counterStyles = {
		...style,
		'--spectra-counter-progress-color': progressColor || '#007cba',
		'--spectra-counter-progress-bg-color': progressBackgroundColor || '#e0e0e0',
		'--spectra-counter-progress-size': progressSize || '300px',
		'--spectra-counter-stroke-width': progressStrokeWidth ? `${progressStrokeWidth}px` : '8px',
		'--spectra-counter-bar-height': barHeight || '32px',
		'--spectra-counter-bar-border-radius': barBorderRadius !== undefined ? `${barBorderRadius}px` : undefined,
	};

	// Create refs for circular progress animation.
	const circularProgressRef = useRef( null );


	// Generate combined class names.
	const combinedClassNames = [
		...classNames,
		'wp-block-spectra-counter',
		`spectra-counter--${counterStyle}`,
	];
    
	// Removed contentWrapperProps - not needed since children are rendered directly.
	const blockProps = useBlockProps( {
		'className': spectraClassNames( combinedClassNames ),
		'style': counterStyles,
		'role': 'region',
		'aria-label': `Counter block in ${counterStyle} style`,
		'data-counter-start': startNumber !== undefined ? startNumber : 0, // Default applied only when used
		'data-counter-end': endNumber !== undefined ? endNumber : 100, // Default applied only when used
		'data-counter-total': totalNumber !== undefined ? totalNumber : 100, // Default applied only when used
		'data-counter-duration': animationDuration !== undefined ? animationDuration : 2000,
		'data-counter-prefix': prefix !== undefined ? prefix : '',
		'data-counter-suffix': suffix !== undefined ? suffix : '',
		'data-counter-separator': thousandSeparator,
		'data-counter-decimals': decimalPlaces !== undefined ? decimalPlaces : 0,
		'data-stroke-width': progressStrokeWidth || 8,
	} );

	// Render circular progress style - hardcoded SVG with child blocks
	if ( counterStyle === 'circular' ) {
		// eslint-disable-next-line react-hooks/rules-of-hooks -- Conditional render based on counterStyle which is static during component lifecycle
		const innerBlocksProps = useInnerBlocksProps( 
			blockProps,
			{
				directInsert: false,
				template: TEMPLATE,
				templateLock: false,
				templateInsertUpdatesSelection: false,
				__experimentalCaptureToolbars: true,
			} 
		);
		
		// Extract children from innerBlocksProps.
		const { children, ...wrapperProps } = innerBlocksProps;

		const progressPercentage = Math.round( ( ( endNumber - startNumber ) / ( totalNumber || endNumber ) ) * 100 );

		// Calculate aria-valuemax to avoid nested ternaries
		let ariaValueMax;
		if ( totalNumber && totalNumber > 0 ) {
			ariaValueMax = totalNumber;
		} else if ( endNumber && endNumber > 0 ) {
			ariaValueMax = endNumber;
		} else {
			ariaValueMax = 100;
		}

		return (
			<div { ...wrapperProps }>
				<div className="spectra-counter-circular-wrapper">
					<div
						className="spectra-counter-progress"
						role="progressbar"
						aria-valuenow={ endNumber || 100 }
						aria-valuemin={ startNumber || 0 }
						aria-valuemax={ ariaValueMax }
						aria-label={ `Progress: ${progressPercentage}%` }
					>
						<svg 
							width={ size } 
							height={ size }
							aria-hidden="true"
							focusable="false"
						>
							<circle
								className="spectra-counter-progress-bg"
								cx="50%"
								cy="50%"
								r={ radius }
								stroke={ progressBackgroundColor || '#e0e0e0' }
								strokeWidth={ progressStrokeWidth || 8 }
								fill="none"
							/>
							<circle
								ref={ circularProgressRef }
								className="spectra-counter-progress-circle"
								cx="50%"
								cy="50%"
								r={ radius }
								stroke={ progressColor || '#007cba' }
								strokeWidth={ progressStrokeWidth || 8 }
								fill="none"
								strokeLinecap="butt"
								strokeDasharray={ circumference }
								strokeDashoffset={ ( () => {
									// Calculate initial stroke-dashoffset for starting position
									let totalNum;
									if ( totalNumber && totalNumber > 0 ) {
										totalNum = totalNumber;
									} else if ( endNumber && endNumber > 0 ) {
										totalNum = endNumber;
									} else {
										totalNum = 100;
									}
									const safeStartNumber = isNaN( startNumber ) ? 0 : startNumber;
									let safeTotalNum = isNaN( totalNum ) ? 100 : totalNum;
									
									// If total is smaller than both start and end numbers, use the larger number as effective total
									if ( safeTotalNum < Math.max( Math.abs( safeStartNumber ), Math.abs( endNumber ) ) ) {
										safeTotalNum = Math.max( Math.abs( safeStartNumber ), Math.abs( endNumber ) );
									}
									
									let startPoint = 100 * ( safeStartNumber / safeTotalNum );
									startPoint = startPoint < 100 ? startPoint : 100;
									startPoint = 100 - startPoint;
									return ( startPoint / 100 ) * circumference;
								} )() }
							/>
						</svg>
						<span className="screen-reader-text">
						{ `Counter progress: ${endNumber || 100} out of ${ariaValueMax}` }
						</span>
					</div>
					<div className="spectra-counter-content">
						<div className="spectra-counter-content-wrapper">
							{ children }
						</div>
					</div>
				</div>
			</div>
		);
	}

	// Render bar progress style - now uses direct child blocks.
	if ( counterStyle === 'bar' ) {
		// eslint-disable-next-line react-hooks/rules-of-hooks -- Conditional render based on counterStyle which is static during component lifecycle
		const barContentWrapperProps = useInnerBlocksProps( 
			blockProps,
			{
				directInsert: false,
				template: TEMPLATE,
				templateLock: false,
				templateInsertUpdatesSelection: false,
				__experimentalCaptureToolbars: true,
			} 
		);

		return (
			<div { ...barContentWrapperProps } />
		);
	}

	// Render simple style - now uses direct child blocks
	// eslint-disable-next-line react-hooks/rules-of-hooks -- Conditional render based on counterStyle which is static during component lifecycle
	const simpleContentWrapperProps = useInnerBlocksProps( 
		blockProps,
		{
			directInsert: false,
			template: TEMPLATE,
			templateLock: false,
			templateInsertUpdatesSelection: false,
			__experimentalCaptureToolbars: true,
		} 
	);

	return (
		<div { ...simpleContentWrapperProps } />
	);
};

export default Render;
