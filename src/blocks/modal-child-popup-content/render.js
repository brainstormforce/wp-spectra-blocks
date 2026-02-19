import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import { getBlockTypes } from '@wordpress/blocks';
import { excludeBlocks } from './modal-config.js';
import {
	getBackgroundImageStyles,
	VideoBackground,
} from '@spectra-helpers/background';
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { RenderFullWidthAppenderWhenEmpty } from '@spectra-components/block-appender';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Render = ( props ) => {
	const { attributes, clientId } = props;

	const {
		background,
		containerWidth = '600px',
		containerHeight = '100%',
		contentHeight = 'auto',
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

	// Parent block properties.
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style: {
			'width': containerWidth,
			'maxWidth': '100%',
			'height': contentHeight === 'custom' ? containerHeight : 'auto',
			'maxHeight': contentHeight === 'auto' ? maxContainerHeight : undefined,
			...getBackgroundStyles(),
			'--spectra-overlay-opacity': ( dimRatio / 100 ),
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

export default memo( Render );
