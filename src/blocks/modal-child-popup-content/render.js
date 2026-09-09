import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { getBlockTypes } from '@wordpress/blocks';
import { excludeBlocks } from './modal-config.js';
import {
	getBackgroundImageStyles,
	VideoBackground,
} from '@spectra-helpers/background';
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { RenderFullWidthAppenderWhenEmpty } from '@spectra-components/block-appender';
import { getResponsivePreviewCss } from '@spectra-helpers/responsive-preview';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
/**
 * The popup content's own dimensions, painted on the block element.
 *
 * Named so the per-device preview emitter can re-derive them per band — see
 * `helpers/responsive-preview.js`. `maxContainerHeight` is resolved outside the
 * band, so it is passed in rather than read from the band's attributes.
 *
 * @since 1.0.7
 * @param {Object} attrs              The block's attributes, or a band's merge.
 * @param {string} maxContainerHeight The resolved max height.
 * @return {Object} A React style object.
 */
export const getPopupContentSizeStyles = ( attrs = {}, maxContainerHeight ) => {
	// `contentHeight` defaults to 'custom' — the block.json default and what the
	// PHP painter assumes (`?? 'custom'`). The canvas used to fall back to 'auto'
	// here, so a band that stored no Content Height previewed as auto while the
	// front end rendered the fixed height; the two surfaces now read a missing
	// value the same way.
	const containerWidth = attrs.containerWidth || '600px';
	const containerHeight = attrs.containerHeight || '100%';
	const contentHeight = attrs.contentHeight || 'custom';

	return {
		width: containerWidth,
		height: 'custom' === contentHeight ? containerHeight : 'auto',
		maxHeight: 'auto' === contentHeight ? maxContainerHeight : undefined,
	};
};

const Render = ( props ) => {
	const { attributes, clientId } = props;

	const {
		background,
		contentHeight = 'custom',
		maxContainerHeight = '100%',
		backgroundGradient,
		backgroundGradientHover,
		backgroundColor,
		style,
		dimRatio,
	} = attributes;

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	// Check if has video background and border radius.
	const hasVideoBackground = background?.type === 'video';
	const hasImageBackground = background?.type === 'image';
	const hasBorderRadius = style?.border?.radius;

	// Custom class names.
	const customClassNames = [
		[ 'image', 'video' ].includes( background?.type ) && `spectra-background-${ background.type }`,
		( backgroundColor || backgroundGradient ) && 'spectra-background-overlay',
		hasVideoBackground && 'has-video-background',
		hasImageBackground && 'has-image-background',
		'spectra-overlay-color',
		contentHeight === 'auto' && 'spectra-height-auto'
	];

	// Generate styles and class names.
	const { style: generatedStyle, classNames } = useSpectraStyles( attributes, config, customClassNames );
	// Determine the overflow value - use 'clip' when video background with border radius
	const computedOverflow = ( hasVideoBackground || hasImageBackground ) && hasBorderRadius ? 'clip' : 'visible';

	// Background styles handling.
	const getBackgroundStyles = () => {
		const styles = {
			...style,
			...generatedStyle,
			...getBackgroundImageStyles( { background, backgroundGradient, backgroundGradientHover } ),
		};
		return styles;
	};

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/modal-popup-content',
		producers: [
			( attrs ) => getPopupContentSizeStyles( attrs, maxContainerHeight ),
			( attrs ) => getBackgroundImageStyles( { ...attrs, backgroundGradient, backgroundGradientHover } ),
		],
	} );

	// Parent block properties.
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style: {
			...getPopupContentSizeStyles( attributes, maxContainerHeight ),
			'maxWidth': '100%',
			...getBackgroundStyles(),
			// An unset dimRatio used to produce `NaN` here — an invalid value on
			// every popup in the canvas. PHP falls back to fully opaque; match it.
			'--spectra-overlay-opacity': 'number' === typeof dimRatio ? dimRatio / 100 : 1,
			'overflow': computedOverflow,
		}
	} );

	const RenderAppender = RenderFullWidthAppenderWhenEmpty( clientId );

	const ALLOWED_BLOCKS = getBlockTypes()
	.map( ( block ) => block.name )
	.filter( ( blockName ) => ! excludeBlocks.includes( blockName ) );

	return (
        <>
			<div { ...blockProps }>
				{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
				{/* Modal Content Block */}
				<VideoBackground { ...{ background } }/>
				<div className="spectra-modal-popup-content">
					<InnerBlocks
						allowedBlocks={ ALLOWED_BLOCKS }
						renderAppender={ RenderAppender }
					/>
				</div>
			</div>
        </>
	);
};

export default Render;
