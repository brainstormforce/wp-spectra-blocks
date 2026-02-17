/**
 * External dependencies.
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import { getBlockTypes } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import {
	getBackgroundImageStyles,
	VideoBackground,
} from '@spectra-helpers/background';
import RenderSVG from '@spectra-helpers/render-svg';
import { select } from '@wordpress/data';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @return {Element} Element to render.
 */
const Render = ( props ) => {
	const { attributes, clientId } = props;

	const {
		blockId,
		variantType,
		isOpen,
		hasOverlay,
		closeIcon,
		closeIconPosition,
		closeIconSize,
		closeIconColor,
		closeIconColorHover,
		closeIconBgColor,
		width,
		height,
		minWidth,
		minHeight,
		maxWidth,
		maxHeight,
		fixedHeight,
		backgroundGradient,
		backgroundGradientHover,
		contentAlign,
		contentVAlign,
		animation,
		isDismissable,
		popupPositionV,
		popupContentAlignmentV,
		dimRatio,
		background,
		style,
		overflow,
		hasFixedHeight,
		rotation,
		flipForRTL,
	} = attributes;

	const config = [
		{ key: 'textColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
		{ key: 'closeIconColor' },
		{ key: 'closeIconColorHover' },
		{ key: 'closeIconBgColor' },
		{ key: 'popupOverlayColor' },
		{ key: 'height', cssVar: '--spectra-popup-height', className: null },
	];

	const {
		getBlockOrder,
	} = select( 'core/block-editor' );
	const hasChildBlocks = getBlockOrder( clientId ).length > 0;
	
	// Define excluded blocks similar to v2 implementation
	const excludeBlocks = [
		'spectra/popup-builder', // Prevent nesting popup-builders
	];
	
	// Get allowed blocks (all registered blocks except excluded ones)
	const ALLOWED_BLOCKS = getBlockTypes().map( block => block.name ).filter( blockName => ! excludeBlocks.includes( blockName ) );
	// Configuration for the useSpectraStyles hook. 

	// Check if has video background and border radius.
	const hasVideoBackground = background?.type === 'video';
	const hasImageBackground = background?.type === 'image';
	const hasBorderRadius = style?.border?.radius;

	// Custom class names.
	const customClassNames = [
		'spectra-popup-wrapper',
		`spectra-popup-builder-${ blockId }`,
		`spectra-popup-${ variantType }`,
		`spectra-popup-position-${ popupPositionV }`,
		hasFixedHeight ? `spectra-popup-builder--content-align-${ popupContentAlignmentV }` : '',
		isOpen && 'spectra-popup-open',
		hasOverlay && 'spectra-popup-has-overlay',
		animation && `spectra-popup-animation-${ animation }`,
		`spectra-popup-close-icon-position-${ closeIconPosition }`,
		[ 'image', 'video' ].includes( background?.type ) &&
			`spectra-background-${ background.type }`,
			'spectra-background-overlay',
		hasVideoBackground && 'has-video-background',
		hasImageBackground && 'has-image-background',
		'spectra-overlay-color',
		hasFixedHeight ? 'spectra-has-fixed-height' : 'spectra-has-auto-height',
		rotation && 'spectra-rotate-close-icon',
	];

	// Generate styles and class names.
	const { style: generatedStyle, classNames } = useSpectraStyles(
		attributes,
		config,
		customClassNames
	);

	// Background styles handling.
	const getBackgroundStyles = () => {
		const styles = {
			...generatedStyle,
			...getBackgroundImageStyles( {
				background,
				backgroundGradient,
				backgroundGradientHover,
			} ),
		};
		return styles;
	};

	// Determine the overflow value - use 'clip' when video background with border radius
	const computedOverflow = ( hasVideoBackground || hasImageBackground ) && hasBorderRadius ? 'clip' : overflow;
	const paddingObject = {
		top: style?.spacing?.padding?.top ? style.spacing.padding.top : '8px',
		right: style?.spacing?.padding?.right ? style.spacing.padding.right : '8px',
		bottom: style?.spacing?.padding?.bottom ? style.spacing.padding.bottom : '8px',
		left: style?.spacing?.padding?.left ? style.spacing.padding.left : '8px'
	};
	const paddingValue = `${paddingObject.top} ${paddingObject.right} ${paddingObject.bottom} ${paddingObject.left}`;
	// Additional inline styles for dimensions and layout
	const additionalStyles = {
		...style,
		height,
		minWidth,
		'min-height': minHeight,
		'max-width': maxWidth,
		'max-height': maxHeight,
		'--spectra-popup-width': width,
		// '--spectra-popup-height': fixedHeight && height ? height : 'auto',
		'--spectra-popup-max-height': ! fixedHeight && height ? height : 'none',
		'--spectra-close-icon-size': `${ closeIconSize }px`,
		'--spectra-close-icon-color': closeIconColor,
		'--spectra-close-icon-color-hover': closeIconColorHover,
		'--spectra-close-icon-bg': closeIconBgColor,
		'--spectra-content-align': contentAlign,
		'--spectra-content-valign': ( () => {
			if ( contentVAlign === 'top' ) return 'flex-start';
			if ( contentVAlign === 'bottom' ) return 'flex-end';
			return 'center';
		} )(),
		'--spectra-overlay-opacity': dimRatio / 100,
		'overflow': computedOverflow,
		'--spectra-popup-builder--position': popupPositionV,
		...getBackgroundStyles(),
		'--spectra-rotation-close-icon': `${rotation}deg`,
		'--spectra-popup-padding': paddingValue
	};

	// Use the block props, with the added CSS variables and their related classes.
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style: additionalStyles,
	} );

	return (
		<div { ...blockProps }>
			{ variantType !== 'popup' && (
				<VideoBackground { ...{ background } } />
			) }
			<div
				className={ `spectra-popup-builder__wrapper spectra-popup-builder__wrapper--${ variantType }` }
			>
				{ variantType !== 'banner' && (
					<VideoBackground { ...{ background } } />
				) }
				<div
					className={ `spectra-popup-builder__container spectra-popup-builder__container--${ variantType }` }
				>
					<InnerBlocks 
						allowedBlocks={ ALLOWED_BLOCKS }
						templateLock={ false }
						renderAppender={ hasChildBlocks ? undefined : InnerBlocks.ButtonBlockAppender } 
					/>
				</div>
				{ isDismissable && closeIcon && (
					<button className="spectra-popup-builder__close">
						<RenderSVG 
							svg={closeIcon || 'cross'} 
							needsRTL={flipForRTL} 
							extraProps={{
								width: `${closeIconSize}px` || '20px',
								height: `${closeIconSize}px` || '20px',
								style: {
									width: `${closeIconSize}px` || '20px',
									height: `${closeIconSize}px` || '20px',
									transform: rotation ? `rotate(${rotation}deg)` : '',
								},
							}}
						/>
					</button>
				) }
			</div>
		</div>
	);
};

export default memo( Render );
