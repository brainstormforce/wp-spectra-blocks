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
import { getResponsivePreviewCss } from '@spectra-helpers/responsive-preview';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @return {Element} Element to render.
 */
/**
 * The popup's own dimensions, painted as custom properties on the block.
 *
 * Named so the per-device preview emitter can re-derive them per band — see
 * `helpers/responsive-preview.js`. Mirrors the expressions the inline paint uses,
 * `fixedHeight` included, since that decides which of the two height variables a
 * value lands in.
 *
 * @since 1.0.7
 * @param {Object}  attrs       The block's attributes, or a band's merge.
 * @param {boolean} fixedHeight Whether the popup has a fixed height.
 * @return {Object} A React style object.
 */
export const getPopupDimensionStyles = ( attrs = {}, fixedHeight ) => ( {
	...( attrs.height ? { height: attrs.height } : {} ),
	...( attrs.minWidth ? { minWidth: attrs.minWidth } : {} ),
	'--spectra-popup-width': attrs.width,
	'--spectra-popup-max-height': ! fixedHeight && attrs.height ? attrs.height : 'none',
} );

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
	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/popup-builder',
		producers: [
			( attrs ) => getPopupDimensionStyles( attrs, fixedHeight ),
			( attrs ) => getBackgroundImageStyles( { ...attrs, backgroundGradient, backgroundGradientHover } ),
		],
	} );

	const paddingValue = `${paddingObject.top} ${paddingObject.right} ${paddingObject.bottom} ${paddingObject.left}`;

	/*
	 * Per-device padding for the canvas.
	 *
	 * `--spectra-popup-padding` is painted inline below, from the ROOT of `style`
	 * — one value, so every device previewed the base padding. It cannot come
	 * from `getResponsivePreviewCss()` either: that bands a block's FLAT keys,
	 * and padding is one of the groups core owns.
	 *
	 * Core does emit its own per-state spacing, but onto the block element, which
	 * here is the full-viewport overlay — `editor.scss` zeroes its padding on
	 * purpose, because on 7.1 the overlay took that padding on top of its own
	 * `width: 100%` and grew a horizontal scrollbar the front end never had. So
	 * the states are re-expressed as the variable the visible box actually reads.
	 *
	 * `!important` is required, not defensive: the base value is applied INLINE
	 * on this same element, and an inline declaration beats a stylesheet rule of
	 * any specificity.
	 *
	 * A side absent from a state falls back to base, which is how the front end
	 * resolves it too.
	 */
	const paddingPreviewCss = Object.entries(
		window?.spectra_blocks_info?.viewport_media_queries || {}
		)
		.map( ( [ state, query ] ) => {
			const statePadding = style?.[ state ]?.spacing?.padding;

			// `base` has no query — it is the inline paint, already applied.
			if ( ! query || ! statePadding ) {
				return '';
			}

			const side = ( key ) => statePadding[ key ] || paddingObject[ key ];
			const value = `${ side( 'top' ) } ${ side( 'right' ) } ${ side( 'bottom' ) } ${ side( 'left' ) }`;

			return `@media ${ query }{[data-block="${ clientId }"]{--spectra-popup-padding:${ value } !important;}}`;
		} )
		.join( '' );
	// Additional inline styles for dimensions and layout
	const additionalStyles = {
		...style,
		...getPopupDimensionStyles( attributes, fixedHeight ),
		'min-height': minHeight,
		'max-width': maxWidth,
		'max-height': maxHeight,
		'--spectra-close-icon-size': `${ closeIconSize }px`,
		'--spectra-close-icon-color': closeIconColor,
		'--spectra-close-icon-color-hover': closeIconColorHover,
		'--spectra-close-icon-bg': closeIconBgColor,
		'--spectra-content-align': contentAlign,
		'--spectra-content-valign': ( () => {
			if ( contentVAlign === 'top' ) {return 'flex-start';}
			if ( contentVAlign === 'bottom' ) {return 'flex-end';}
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
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			{ paddingPreviewCss && <style>{ paddingPreviewCss }</style> }
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
