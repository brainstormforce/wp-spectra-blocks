/**
 * External dependencies.
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { memo, useCallback } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { RenderFullWidthAppenderWhenEmpty } from '@spectra-components/block-appender';
import { getBackgroundImageStyles, VideoBackground } from '@spectra-helpers/background';
import { getAdvancedGradientValue } from '@spectra-helpers/get-advanced-gradient-value';

/**
 * The render component for the slider-child block.
 *
 * @param {Object} props  Block props.
 * @return {JSX.Element} The rendered component.
 */
const Render = memo( ( props ) => {
	const {
		clientId,
		context: { 'spectra/slider/textColor': textColor },
		attributes,
	} = props;

	const { backgroundColor, backgroundGradient, background, dimRatio, style, overflow, enableAdvGradients, advBgGradient } = attributes;

	// Get final background gradient value (advanced or basic).
	const finalBackgroundGradient = getAdvancedGradientValue(
		true, // For single gradient blocks, always pass true for enableAdvBg.
		advBgGradient,
		backgroundGradient,
		enableAdvGradients
	);

	// Get background image styles
	const additionalStyles = {
		...getBackgroundImageStyles( { background, backgroundGradient: finalBackgroundGradient } ),
	};

	// Check if has video background and border radius.
	const hasVideoBackground = background?.type === 'video';
	const hasImageBackground = background?.type === 'image';
	const hasBorderRadius = style?.border?.radius;

	// Performance optimization: Memoize configuration to prevent unnecessary re-renders
	const config = useCallback( () => [
		{ key: 'textColor', cssVar: '--spectra-text-color',value: textColor },
		{ key: 'backgroundColor' },
		{ key: 'backgroundGradient', value: finalBackgroundGradient },
	], [ textColor, finalBackgroundGradient ] );

	// Performance optimization: Memoize additional class names
	const additionalClassNames = useCallback( () => [
		'spectra-slider-child swiper-slide',
		['image', 'video'].includes( background?.type ) && `spectra-background-${background.type}`,
		( backgroundColor || finalBackgroundGradient ) && 'spectra-background-overlay',
		hasVideoBackground && 'has-video-background',
		hasImageBackground && 'has-image-background',
		'spectra-overlay-color'
	], [ background?.type, backgroundColor, finalBackgroundGradient, hasVideoBackground, hasImageBackground ] );

	// Generate styles and class names with optimized dependencies
	const { style: generatedStyle, classNames } = useSpectraStyles( attributes, config(), additionalClassNames() );
	// Determine the overflow value - use 'clip' when video background with border radius
	const computedOverflow = ( hasVideoBackground || hasImageBackground ) && hasBorderRadius ? 'clip' : overflow;
	// Performance optimization: Memoize block props
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style: {
			...generatedStyle,
			...additionalStyles,
			'--spectra-overlay-opacity': ( dimRatio / 100 ),
		},
		overflow: computedOverflow,
	} );

	// Performance optimization: Memoize inner blocks props
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'slide-content' },
		{
			renderAppender: RenderFullWidthAppenderWhenEmpty( clientId ),
			orientation: 'vertical',
			layout: { type: 'flex', orientation: 'vertical', justifyContent: 'center' }
		}
	);

	return (
		<div { ...blockProps }>
			<VideoBackground { ...{ background } } />
			<div { ...innerBlocksProps } />
		</div>
	);
} );

export default Render;
