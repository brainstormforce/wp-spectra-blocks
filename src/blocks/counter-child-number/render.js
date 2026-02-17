/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { useCounterAnimation } from '@spectra-hooks/useCounterAnimation';

/**
 * The render function for the Counter Number block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Render = ( props ) => {
	const {
		attributes,
		context: {
			'spectra/counter/startNumber': rawStartNumber = 0,
		'spectra/counter/endNumber': rawEndNumber = 100,
		'spectra/counter/animationDuration': animationDuration = 2000,
		'spectra/counter/prefix': prefix = '',
		'spectra/counter/suffix': suffix = '',
		'spectra/counter/thousandSeparator': thousandSeparator,
		'spectra/counter/decimalPlaces': decimalPlaces = 0,
			'spectra/counter/prefixColor': contextPrefixColor = '',
			'spectra/counter/suffixColor': contextSuffixColor = '',
		} = {},
	} = props;
	
	// Support reverse counting: allow start > end for decrement animation
	// Ensure values are numbers to prevent NaN issues
	const startNumber = typeof rawStartNumber === 'number' ? rawStartNumber : parseFloat( rawStartNumber ) || 0;
	const endNumber = typeof rawEndNumber === 'number' ? rawEndNumber : parseFloat( rawEndNumber ) || 100;
	
	const {
		textColor,
		prefixColor,
		suffixColor,
	} = attributes;

	// Use child's own colors if set, otherwise fallback to parent's colors from context
	const finalPrefixColor = prefixColor || contextPrefixColor;
	const finalSuffixColor = suffixColor || contextSuffixColor;

	// Use animation hook for live preview in editor (without prefix/suffix)
	const animatedNumber = useCounterAnimation( {
		startNumber,
		endNumber,
		duration: animationDuration,
		easing: 'linear', // Force linear easing for consistent timing
		prefix: '',
		suffix: '',
		separator: thousandSeparator || '',
		decimals: decimalPlaces || 0,
		triggerAnimation: true,
	} );

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor', className: 'spectra-text-color', value: textColor },
		{ key: 'prefixColor', cssVar: '--spectra-prefix-color', className: null, value: finalPrefixColor },
		{ key: 'suffixColor', cssVar: '--spectra-suffix-color', className: null, value: finalSuffixColor },
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config );

	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames, [
			'wp-block-spectra-counter-child-number',
			'spectra-counter-number',
		] ),
		style,
	} );

	return (
		<div { ...blockProps }>
			<span className="spectra-counter-number">
				{ prefix && <span className="spectra-counter-prefix">{ prefix }</span> }
				<span className="spectra-counter-value">{ animatedNumber || '0' }</span>
				{ suffix && <span className="spectra-counter-suffix">{ suffix }</span> }
			</span>
		</div>
	);
};

export default Render;
