/**
 * WordPress dependencies
 */
import { useBlockProps, getTypographyClassesAndStyles } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { useCounterAnimation } from '@spectra-hooks/useCounterAnimation';
import {
	colourVarStyles,
	getResponsivePreviewCss,
} from '@spectra-helpers/responsive-preview';

/**
 * Bar sizing custom properties.
 *
 * Painted here rather than through the `useSpectraStyles` config so the canvas's
 * per-device preview can re-derive the same two declarations per band from this
 * one function — see `helpers/responsive-preview.js`. Base and bands then cannot
 * disagree.
 *
 * No fallbacks: `block.json` defaults these to `32px` and `4`, and `style.scss`
 * repeats both as `var()` fallbacks, so an unauthored value is already covered
 * twice over.
 *
 * @since 1.0.13
 * @param {Object} attrs The block attributes.
 * @return {Object} The style object.
 */
const getBarSizeStyles = ( attrs ) => ( {
	'--spectra-counter-bar-height': attrs.barHeight,
	'--spectra-counter-bar-border-radius':
		attrs.barBorderRadius !== undefined ? `${ attrs.barBorderRadius }px` : undefined,
} );

/**
 * The render function for the Counter Progress Bar block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Render = ( props ) => {
	const {
		clientId,
		attributes,
		context: {
			'spectra/counter/counterStyle': counterStyle = 'simple',
			'spectra/counter/startNumber': rawStartNumber = 0,
			'spectra/counter/endNumber': rawEndNumber = 100,
			'spectra/counter/totalNumber': rawTotalNumber = 100,
			'spectra/counter/animationDuration': animationDuration = 2000,
			'spectra/counter/prefix': prefix = '',
			'spectra/counter/suffix': suffix = '',
			'spectra/counter/thousandSeparator': thousandSeparator,
			'spectra/counter/decimalPlaces': decimalPlaces = 0,
			'spectra/counter/progressColor': contextProgressColor,
			'spectra/counter/progressBackgroundColor': contextProgressBackgroundColor,
			'spectra/counter/progressSize': progressSize = '300px',
			'spectra/counter/progressStrokeWidth': progressStrokeWidth = 8,
			'spectra/counter/prefixColor': contextPrefixColor,
			'spectra/counter/suffixColor': contextSuffixColor,
		} = {},
	} = props;

	// Only render progress bar for bar style (circular is hardcoded in main counter).
	if ( counterStyle === 'simple' || counterStyle === 'circular' ) {
		return null;
	}

	const {
		progressColor: childProgressColor,
		progressBackgroundColor: childProgressBackgroundColor,
		prefixColor: childPrefixColor,
		suffixColor: childSuffixColor,
	} = attributes;

	// Use context colors from parent if available, otherwise use child's own colors.
	const progressColor = contextProgressColor || childProgressColor || '#4A90E2';
	const progressBackgroundColor = contextProgressBackgroundColor || childProgressBackgroundColor || '#E6E6E6';
	// Prefix/suffix: Child takes priority over parent context (reversed priority).
	const prefixColor = childPrefixColor || contextPrefixColor || undefined;
	const suffixColor = childSuffixColor || contextSuffixColor || undefined;

	// Ensure values are numbers to prevent NaN issues.
	const startNumber = typeof rawStartNumber === 'number' ? rawStartNumber : parseFloat( rawStartNumber ) || 0;
	const endNumber = typeof rawEndNumber === 'number' ? rawEndNumber : parseFloat( rawEndNumber ) || 100;
	let totalNumber = typeof rawTotalNumber === 'number' ? rawTotalNumber : parseFloat( rawTotalNumber ) || 100;

	// If total is smaller than both start and end numbers, use the larger number as effective total.
	if ( totalNumber < Math.max( Math.abs( startNumber ), Math.abs( endNumber ) ) ) {
		totalNumber = Math.max( Math.abs( startNumber ), Math.abs( endNumber ) );
	}

	// Get typography classes and styles for progress bar text.
	const typographyProps = getTypographyClassesAndStyles( attributes );

	// Use the same counter animation as the counter number for synchronization.
	// eslint-disable-next-line react-hooks/rules-of-hooks -- Early return above is conditional on counterStyle which doesn't change during render
	const animatedCounterNumber = useCounterAnimation( {
		startNumber,
		endNumber,
		duration: animationDuration,
		easing: 'linear',
		prefix: '',
		suffix: '',
		separator: '',
		decimals: decimalPlaces || 0,
		triggerAnimation: true,
	} );

	// Calculate progress based on the animated counter number (synchronized with counter).
	// animatedCounterNumber is a formatted string, so we need to parse it carefully.
	// Remove any non-numeric characters except decimal point and minus sign.
	const cleanedNumber = String( animatedCounterNumber ).replace( /[^\d.-]/g, '' );
	const parsedNumber = parseFloat( cleanedNumber );
	const currentNumber = isNaN( parsedNumber ) ? startNumber : parsedNumber;
	
	// Determine if we're counting up or down.
	const isReverse = startNumber > endNumber;
	
	// Calculate progress percentage.
	let progress;
	if ( isReverse ) {
		// Reverse progress: start at 100%, decrease to endNumber percentage.
		// When currentNumber = startNumber, progress = 100%.
		// When currentNumber = endNumber, progress = (endNumber/totalNumber) * 100.
		const startProgress = ( startNumber / totalNumber ) * 100;
		const endProgress = ( endNumber / totalNumber ) * 100;
		const range = startProgress - endProgress;
		const currentProgress = ( ( startNumber - currentNumber ) / ( startNumber - endNumber ) ) * range;
		progress = Math.max( Math.min( startProgress - currentProgress, startProgress ), endProgress );
	} else {
		// Normal progress: start at startNumber percentage, increase to endNumber percentage.
		const startProgress = ( startNumber / totalNumber ) * 100;
		const endProgress = ( endNumber / totalNumber ) * 100;
		const range = endProgress - startProgress;
		const currentProgress = ( ( currentNumber - startNumber ) / ( endNumber - startNumber ) ) * range;
		progress = Math.max( Math.min( startProgress + currentProgress, endProgress ), startProgress );
	}
	
	// Ensure progress is within valid range.
	progress = Math.max( 0, Math.min( 100, progress || 0 ) );

	// Format number for display in progress bar label.
	const formatNumber = ( number ) => {
		const decimals = decimalPlaces || 0;
		const separator = thousandSeparator;
		const fixedNumber = parseFloat( number ).toFixed( decimals );

		if ( separator && separator.trim() !== '' ) {
			const parts = fixedNumber.split( '.' );
			parts[0] = parts[0].replace( /\B(?=(\d{3})+(?!\d))/g, separator );
			return parts.join( '.' );
		}
		return fixedNumber;
	};

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'progressColor', cssVar: '--spectra-counter-progress-color', value: progressColor },
		{ key: 'progressBackgroundColor', cssVar: '--spectra-counter-progress-bg-color', value: progressBackgroundColor },
		{ key: 'progressSize', cssVar: '--spectra-counter-progress-size', value: progressSize },
		{ key: 'progressStrokeWidth', cssVar: '--spectra-counter-stroke-width', value: progressStrokeWidth },
		{ key: 'prefixColor', cssVar: '--spectra-prefix-color', value: prefixColor },
		{ key: 'suffixColor', cssVar: '--spectra-suffix-color', value: suffixColor },
	];

	// Generate styles and class names.
	// eslint-disable-next-line react-hooks/rules-of-hooks -- Early return above is conditional on counterStyle which doesn't change during render
	const { style, classNames } = useSpectraStyles( attributes, config );

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/counter-child-progress-bar',
		producers: [ getBarSizeStyles,
			( attrs ) =>
				colourVarStyles( attrs, {
					prefixColor: '--spectra-prefix-color',
					suffixColor: '--spectra-suffix-color',
				} ),
		],
	} );

	// eslint-disable-next-line react-hooks/rules-of-hooks -- Early return above is conditional on counterStyle which doesn't change during render
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames, [
			'wp-block-spectra-counter-child-progress-bar',
			`spectra-counter-progress-bar--${counterStyle}`,
			typographyProps.className,
		] ),
		style: { 
			...style, 
			...typographyProps.style,
			...getBarSizeStyles( attributes ),
		},
	} );


	// Render bar progress.
	if ( counterStyle === 'bar' ) {
		return (
			<div { ...blockProps }>
				{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
				<div className="spectra-counter-progress-track">
					<div
						className="spectra-counter-progress-bar"
						style={{ width: `${progress}%` }}
					>
						<div className="spectra-counter-progress-label">
							{ prefix && <span className="spectra-counter-prefix">{ prefix }</span> }
							<span className="spectra-counter-value">{ formatNumber( currentNumber ) }</span>
							{ suffix && <span className="spectra-counter-suffix">{ suffix }</span> }
						</div>
					</div>
				</div>
			</div>
		);
	}

	return null;
};

export default Render;
