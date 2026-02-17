/**
 * External dependencies.
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { memo, useCallback, useMemo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { getBackgroundImageStyles, VideoBackground } from '@spectra-helpers/background';
import { getMultiStateShadowStyles } from '@spectra-helpers/shadow';
import { RenderFullWidthAppenderWhenEmpty } from '@spectra-components/block-appender';
import { useHtmlTagToolbar, DEFAULT_TAG_NAME } from './toolbar';
import { getAdvancedGradientValue } from '@spectra-helpers/get-advanced-gradient-value';
import shapes from './shapes';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { attributes, clientId, setAttributes } = props;

	const {
		htmlTag = 'div',
		overflow,
		background,
		width,
		height,
		minWidth,
		minHeight,
		maxWidth,
		maxHeight,
		backgroundColor,
		backgroundGradient,
		backgroundGradientHover,
		enableAdvBgGradient,
		enableAdvBgGradientHover,
		advBgGradient,
		advBgGradientHover,
		enableAdvGradients,
		dimRatio,
		isBlockRootParent,
		align,
		overlayType,
		overlayImage,
		overlayPosition,
		overlayPositionMode,
		overlayPositionCentered,
		overlayPositionX,
		overlayPositionY,
		overlayAttachment,
		overlayRepeat,
		overlaySize,
		overlayCustomWidth,
		overlayBlendMode,
		overlayOpacity,
		orientationReverse,
		layout,
		topType,
		topColor,
		topHeight,
		topWidth,
		topFlip,
		topInvert,
		topContentAboveShape,
		bottomType,
		bottomColor,
		bottomHeight,
		bottomWidth,
		bottomFlip,
		bottomInvert,
		bottomContentAboveShape,
		borderHover,
	} = attributes;

	const hasChildren = useSelect(
		( select ) => select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks.length > 0,
		[ clientId ]
	);

	// Get shadow configuration
	const shadowConfig = getMultiStateShadowStyles( attributes, {
		normal: 'boxShadow',
		hover: 'boxShadowHover',
	} );

	// Check if has video background and border radius.
	const hasVideoBackground = background?.type === 'video';
	const hasImageBackground = background?.type === 'image';

	const finalBackgroundGradient = getAdvancedGradientValue( enableAdvBgGradient, advBgGradient, backgroundGradient, enableAdvGradients );
	const finalBackgroundGradientHover = getAdvancedGradientValue( enableAdvBgGradientHover, advBgGradientHover, backgroundGradientHover, enableAdvGradients );

	/**
	 * Get border hover color value.
	 * 
	 * Note: We only extract the color from borderHover, not width or style.
	 * This is because WordPress core handles the responsive border width/style,
	 * and remain unchanged on hover. Only the border-color changes on hover,
	 * which is applied via CSS: `border-color: var(--spectra-border-hover-color)`.
	 * 
	 * @return {string|undefined} The border hover color value or undefined if not set.
	 */
	const getBorderHoverColor = () => {
		if ( !borderHover?.color ) return undefined;
		return borderHover.color;
	};

	const borderHoverColor = getBorderHoverColor();

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient', value: finalBackgroundGradient },
		{ key: 'backgroundGradientHover', value: finalBackgroundGradientHover },
		...shadowConfig, // Spread shadow configuration
		/**
		 * Border hover color configuration.
		 * 
		 * Sets --spectra-border-hover-color CSS variable with just the color value.
		 * The CSS uses `border-color: var(--spectra-border-hover-color)` on hover,
		 * while width and style remain from the normal state WordPress core settings.
		 */
		...( borderHoverColor ? [ {
			key: 'borderHoverColor',
			cssVar: '--spectra-border-hover-color',
			className: 'spectra-border-hover',
			value: borderHoverColor,
		} ] : [] ),
	];

	// Custom class names.
	const customClassNames = [
		background?.type === 'video' && 'spectra-background-video',
		background?.type === 'image' && background?.media?.url && 'spectra-background-image',
		( backgroundColor || finalBackgroundGradient ) && 'spectra-background-overlay',
		hasVideoBackground && 'has-video-background',
		hasImageBackground && 'has-image-background',
		isBlockRootParent && 'spectra-is-root-container',
		align === 'full' && 'alignfull',
		align === 'wide' && 'alignwide',
		( background?.type !== 'video' && overlayType === 'image' && overlayImage?.url ) && 'has-container-overlay',
		orientationReverse && 'spectra-orientation-reverse',
		'spectra-overlay-color',
		// Border hover classes.
		borderHover && 'has-border-hover',
		borderHover && 'spectra-border-hover-override',
	];

	// Generate styles and class names.
	const { style: generatedStyle, classNames } = useSpectraStyles( attributes, config, customClassNames );


	// Background styles handling with overlay CSS variables for editor.
	const getBackgroundStyles = useMemo( () => {
		const styles = {
			...generatedStyle,
			...getBackgroundImageStyles( { background, backgroundGradient: finalBackgroundGradient, backgroundGradientHover: finalBackgroundGradientHover } ),
		};

		// Add shape divider CSS variables
		if ( topType && topType !== 'none' ) {
			styles['--spectra-shape-divider-top-color'] = topColor || '#333';
			styles['--spectra-shape-divider-top-width'] = topWidth || '100%';
			styles['--spectra-shape-divider-top-height'] = topHeight || '100px';
		}

		if ( bottomType && bottomType !== 'none' ) {
			styles['--spectra-shape-divider-bottom-color'] = bottomColor || '#333';
			styles['--spectra-shape-divider-bottom-width'] = bottomWidth || '100%';
			styles['--spectra-shape-divider-bottom-height'] = bottomHeight || '100px';
		}
		
		// Check if we should show overlay - background is NOT video AND overlay type is image AND overlay image exists
		const shouldShowOverlay = background?.type !== 'video' && overlayType === 'image' && overlayImage?.url;
		
		if ( shouldShowOverlay ) {
			// Sanitize URL to prevent XSS
			const sanitizedUrl = overlayImage.url.replace( /["'\\]/g, '' );
			if ( sanitizedUrl && ( sanitizedUrl.startsWith( 'http' ) || sanitizedUrl.startsWith( '/' ) || sanitizedUrl.startsWith( 'data:image/' ) ) ) {
				styles['--spectra-overlay-image'] = `url("${sanitizedUrl}")`;
				// Add overlay properties with defaults
				// Handle position based on mode
				if ( overlayPositionMode === 'custom' ) {
					// Custom positioning mode - use X/Y values with any unit
					// If centralized position is enabled, force both to 50%
					const xPos = overlayPositionCentered ? '50%' : ( overlayPositionX || '0%' );
					const yPos = overlayPositionCentered ? '50%' : ( overlayPositionY || '0%' );
					styles['--spectra-overlay-position'] = `${ xPos } ${ yPos }`;
				} else {
					// Default focal point mode - convert coordinates to CSS position values
					const focalX = overlayPosition?.x !== undefined ? overlayPosition.x : 0.5;
					const focalY = overlayPosition?.y !== undefined ? overlayPosition.y : 0.5;
					styles['--spectra-overlay-position'] = `${focalX * 100}% ${focalY * 100}%`;
				}
				styles['--spectra-overlay-attachment'] = overlayAttachment || 'scroll';
				styles['--spectra-overlay-repeat'] = overlayRepeat || 'no-repeat';
				// Handle custom overlay size with width.
				if ( overlaySize === 'custom' ) {
					const customWidth = overlayCustomWidth || '100%';
					styles['--spectra-overlay-size'] = `${ customWidth } auto`;
				} else {
					styles['--spectra-overlay-size'] = overlaySize || 'cover';
				}
				styles['--spectra-overlay-blend-mode'] = overlayBlendMode || 'normal';
				styles['--spectra-overlay-opacity-value'] = ( overlayOpacity !== undefined && overlayOpacity !== null ? overlayOpacity : 50 ) / 100;
			} else {
				// Clear overlay if URL is invalid
				styles['--spectra-overlay-image'] = 'none';
				styles['--spectra-overlay-opacity-value'] = '0';
			}
		} else {
			// Clear overlay variables when no overlay is set or conditions not met
			styles['--spectra-overlay-image'] = 'none';
			styles['--spectra-overlay-opacity-value'] = '0';
			// Clear other overlay properties
			styles['--spectra-overlay-position'] = '50% 50%';
			styles['--spectra-overlay-attachment'] = 'scroll';
			styles['--spectra-overlay-repeat'] = 'no-repeat';
			styles['--spectra-overlay-size'] = 'cover';
			styles['--spectra-overlay-blend-mode'] = 'normal';
		}

		return styles;
	}, [ generatedStyle, background, backgroundGradient, backgroundGradientHover, enableAdvBgGradient, enableAdvBgGradientHover, advBgGradient, advBgGradientHover, enableAdvGradients, overlayType, overlayImage, overlayPosition, overlayPositionMode, overlayPositionCentered, overlayPositionX, overlayPositionY, overlayAttachment, overlayRepeat, overlaySize, overlayCustomWidth, overlayBlendMode, overlayOpacity, hasImageBackground, topType, topColor, topWidth, topHeight, bottomType, bottomColor, bottomWidth, bottomHeight ] );

	// Parent block properties.
	const blockProps = useBlockProps( {
		'className': spectraClassNames( classNames ),
		'data-orientation': layout?.orientation || 'vertical',
		'style': {
			...( ( !align || align === 'none' ) && { width } ), // Only apply width if no alignment is set
			height,
			minWidth,
			minHeight,
			maxWidth,
			maxHeight,
			position: 'relative', // Ensure overlay positions correctly.
			overflow,
			...( typeof dimRatio === 'number' && ! isNaN( dimRatio ) ? { '--spectra-overlay-opacity': ( dimRatio / 100 ) } : {} ),
			...getBackgroundStyles,
		},
	} );

	// Memoized current tag value
	const currentTag = useMemo( () => htmlTag || DEFAULT_TAG_NAME, [ htmlTag ] );

	// Memoized tag change handler - prevents recreation on every render.
	const handleTagChange = useCallback( ( newTag ) => {
		setAttributes( { htmlTag: newTag } );
	}, [ setAttributes ] );

	// Create toolbar controls using custom hook.
	const toolbarControls = useHtmlTagToolbar( currentTag, handleTagChange );

	const CustomTag = htmlTag === 'a' ? 'div' : htmlTag;

	// Shape divider HTML
	const topDividerHtml = topType && topType !== 'none' && (
		<div
			className={ spectraClassNames( [
				'spectra-container__shape',
				'spectra-container__shape-top',
				topFlip && 'spectra-container__shape-flip',
				topContentAboveShape && 'spectra-container__shape-above-content',
				topInvert && 'spectra-container__invert',
			] ) }
		>
			{ shapes[ topType ] }
		</div>
	);

	const bottomDividerHtml = bottomType && bottomType !== 'none' && (
		<div
			className={ spectraClassNames( [
				'spectra-container__shape',
				'spectra-container__shape-bottom',
				bottomFlip && 'spectra-container__shape-flip',
				bottomContentAboveShape && 'spectra-container__shape-above-content',
				bottomInvert && 'spectra-container__invert',
			] ) }
		>
			{ shapes[ bottomType ] }
		</div>
	);

	return (
		<>
			{ toolbarControls }
			<CustomTag { ...blockProps }>
				<VideoBackground { ...{ background } } />
				{ topDividerHtml }
				{ bottomDividerHtml }
				<InnerBlocks renderAppender={ RenderFullWidthAppenderWhenEmpty( clientId ) } >
					{ hasChildren ? <InnerBlocks.Content /> : null }
				</InnerBlocks>
			</CustomTag>
		</>
	);
};

export default memo( Render );