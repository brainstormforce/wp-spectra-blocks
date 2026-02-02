/**
 * External dependencies.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import renderCustomSVG from './separator-svg';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { attributes } = props;

	const {
		separatorStyle = 'solid',
		separatorAlign = 'center',
		separatorWidth,
		separatorHeight,
		separatorColor,
		separatorSize,
	} = attributes;

	// Check if it's a custom SVG style
	const isCustomSVG = ['rectangles', 'parallelogram', 'slash', 'leaves'].includes( separatorStyle );

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'separatorColor' },
	];

	// Helper function to get justify content value
	const getJustifyContent = () => {
		if ( separatorAlign === 'left' ) return 'flex-start';
		if ( separatorAlign === 'right' ) return 'flex-end';
		return 'center';
	};

	// Custom styles for wrapper
	const customStyles = {
		display: 'flex',
		justifyContent: getJustifyContent(),
	};

	// Custom class names
	const customClassNames = ['wp-block-spectra-separator'];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames, customStyles );

	// Get processed color from useSpectraStyles
	const processedColor = style?.['--spectra-separator-color'] || separatorColor || 'currentColor';

	// Helper function to get default size for SVG patterns.
	const getDefaultSVGSize = () => {
		return separatorStyle === 'rectangles' ? '8px' : '16px';
	};

	// Helper function to get separator appearance styles
	const getSeparatorAppearance = () => {
		if ( isCustomSVG ) {
			const svgUrl = renderCustomSVG( separatorStyle );
			const effectiveSize = separatorSize || getDefaultSVGSize();
			
			return {
				backgroundColor: processedColor,
				maskImage: `url("${svgUrl}")`,
				maskRepeat: 'repeat-x',
				maskPosition: 'center',
				maskSize: `${effectiveSize} 100%`,
				WebkitMaskImage: `url("${svgUrl}")`,
				WebkitMaskRepeat: 'repeat-x',
				WebkitMaskPosition: 'center',
				WebkitMaskSize: `${effectiveSize} 100%`,
			};
		}
		return separatorStyle === 'solid' ? {
			backgroundColor: processedColor,
		} : {
			borderTop: `${separatorHeight || '3px'} ${separatorStyle} ${processedColor}`,
			backgroundColor: 'transparent',
		};
	};

	// Helper function to get margin values
	const getMarginLeft = () => separatorAlign === 'left' ? '0' : 'auto';
	const getMarginRight = () => separatorAlign === 'right' ? '0' : 'auto';

	// Separator line styles
	const separatorStyles = {
		width: separatorWidth || '100%',
		height: separatorHeight || '3px',
		...getSeparatorAppearance(),
		marginLeft: getMarginLeft(),
		marginRight: getMarginRight(),
	};

	// Use the block props
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	return (
		<div { ...blockProps }>
			<div 
				className="spectra-separator-line"
				style={ separatorStyles }
			/>
		</div>
	);
};

export default memo( Render );