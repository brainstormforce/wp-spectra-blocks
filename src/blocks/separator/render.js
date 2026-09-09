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
import { getResponsivePreviewCss } from '@spectra-helpers/responsive-preview';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
/**
 * The separator's line and wrapper styles.
 *
 * Two selectors, because the block splits them: alignment becomes
 * `justify-content` on the WRAPPER while the dimensions and appearance land on
 * the `.spectra-separator-line` CHILD. A single style object could not express
 * that, which is why producers may return selector-scoped entries — see
 * `helpers/responsive-preview.js`.
 *
 * `processedColor` is resolved outside the band, so it is passed in.
 *
 * @since 1.0.7
 * @param {Object} attrs          The block's attributes, or a band's merge.
 * @param {string} processedColor The resolved separator colour.
 * @return {Array} Selector-scoped style entries.
 */
export const getSeparatorStyles = ( attrs = {}, processedColor ) => {
	const { separatorWidth, separatorHeight, separatorStyle, separatorAlign } = attrs;

	const justifyContent = 'left' === separatorAlign ? 'flex-start' : ( 'right' === separatorAlign ? 'flex-end' : 'center' ); // eslint-disable-line no-nested-ternary
	const appearance = 'solid' === separatorStyle
		? { backgroundColor: processedColor }
		: { borderTop: `${ separatorHeight || '3px' } ${ separatorStyle } ${ processedColor }`, backgroundColor: 'transparent' };

	return [
		{ selector: '', styles: { justifyContent } },
		{
			selector: ' .spectra-separator-line',
			styles: {
				width: separatorWidth || '100%',
				height: separatorHeight || '3px',
				...appearance,
				marginLeft: 'left' === separatorAlign ? '0' : 'auto',
				marginRight: 'right' === separatorAlign ? '0' : 'auto',
			},
		},
	];
};

const Render = ( props ) => {
	const { attributes, clientId } = props;

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
		if ( separatorAlign === 'left' ) {return 'flex-start';}
		if ( separatorAlign === 'right' ) {return 'flex-end';}
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

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/separator',
		producers: [ ( attrs ) => getSeparatorStyles( attrs, processedColor ) ],
	} );

	// Use the block props
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	return (
		<div { ...blockProps }>
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			<div 
				className="spectra-separator-line"
				style={ separatorStyles }
			/>
		</div>
	);
};

export default memo( Render );