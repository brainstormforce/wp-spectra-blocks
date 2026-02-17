/**
 * External dependencies.
 */
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { memo, useCallback, useMemo, useState } from '@wordpress/element';
import { select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import RenderSVG from '@spectra-helpers/render-svg';

const Render = ( props ) => {
	const { setAttributes, attributes } = props;

	const {
		text,
		showText,
		icon,
		iconPosition,
		flipForRTL,
		rotation,
		iconColor,
		iconColorHover,
		textColorHover,
		htmlTagLink,
		size,
		shadowHover,
		borderHover,
		showIconOnHover,
		hoverIcon,
		hoverIconPosition,
		hoverIconRotation,
		hoverIconFlipForRTL,
		hoverIconAriaLabel,
	} = attributes;

	/**
	 * Dynamically retrieve all registered RichText format types from the
	 * WordPress rich-text store and exclude the 'core/link' format.
	 *
	 * This ensures that all other formatting options (like bold, italic, etc.)
	 * remain available in the RichText toolbar, while preventing users from
	 * inserting or editing links.
	 */
	const allowedFormats = useMemo( () => {
		const allFormats = select( 'core/rich-text' ).getFormatTypes() || [];
	
		return allFormats
			.map( ( format ) => format.name )
			.filter( ( name ) => name !== 'core/link' && name !== 'core/footnote' );
	}, [] );

	// Convert shadow hover object to CSS string.
	const getShadowHoverValue = () => {
		if ( !shadowHover ) return undefined;
		const { x = 0, y = 4, blur = 8, spread = 0, color = '' } = shadowHover;
		
		// Only return shadow if color is actually set.
		if ( !color || color.trim() === '' ) {
			return undefined;
		}
		
		return `${x}px ${y}px ${blur}px ${spread}px ${color}`;
	};

	/**
	 * Get border hover color value.
	 * 
	 * Note: We only extract the color from borderHover, not width or style.
	 * The border width and style are handled by WordPress core's border settings
	 * and remain unchanged on hover. Only the border-color changes on hover,
	 * which is applied via CSS: `border-color: var(--spectra-border-hover-color)`.
	 * 
	 * This approach ensures responsive border width/style from core is preserved.
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
		{ key: 'iconColor' },
		{ key: 'iconColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
		{ 
			key: 'shadowHover', 
			cssVar: '--spectra-shadow-hover', 
			className: 'spectra-shadow-hover',
			value: getShadowHoverValue(),
		},
		/**
		 * Border hover color configuration.
		 * 
		 * Sets --spectra-border-hover-color CSS variable with just the color value.
		 * The CSS uses `border-color: var(--spectra-border-hover-color)` on hover,
		 * preserving the border width/style from WordPress core's responsive settings.
		 */
		...( borderHoverColor ? [
			{ 
				key: 'borderHoverColor', 
				cssVar: '--spectra-border-hover-color', 
				className: 'spectra-border-hover',
				value: borderHoverColor,
			}
		] : [] ),
		{ key: 'gap', cssVar: '--spectra-icon-gap', className: null },
	];

	const customClassNames = [ 
		'spectra-button', 
		'wp-block-button', 
		'wp-block-button__link',
		showIconOnHover && hoverIcon && 'has-hover-icon',
		borderHover && 'has-border-hover',
		borderHover && 'spectra-border-hover-override',
		shadowHover && 'spectra-shadow-hover-override',
	].filter( Boolean );

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	// Default aria-label for accessibility (normal state).
	const getDefaultAriaLabel = useCallback( () => {
		// Use the text content (strip HTML tags for better accessibility).
		return text ? text.replace( /<[^>]*>/g, '' ) : undefined;
	}, [ text ] );

	// Store current aria-label state.
	const [ currentAriaLabel, setCurrentAriaLabel ] = useState( getDefaultAriaLabel() );

	// Hover handlers for dynamic aria-label.
	const handleMouseEnter = useCallback( () => {
		if ( showIconOnHover && hoverIconAriaLabel ) {
			setCurrentAriaLabel( hoverIconAriaLabel );
		}
	}, [ showIconOnHover, hoverIconAriaLabel ] );

	const handleMouseLeave = useCallback( () => {
		setCurrentAriaLabel( getDefaultAriaLabel() );
	}, [ getDefaultAriaLabel ] );

	// Use the block props, with the added CSS variables and their related classes.
	const blockProps = useBlockProps( {
		'className': spectraClassNames( classNames ),
		style,
		'aria-label': currentAriaLabel,
		'onMouseEnter': handleMouseEnter,
		'onMouseLeave': handleMouseLeave,
		'onFocus': handleMouseEnter,
		'onBlur': handleMouseLeave,
	} );

	const iconHtml = useCallback(
		( position ) => {
			const finalIconPosition = iconPosition || 'after'; // Ensure a default value.
			// If there's no icon, or if the position given does not match the required one, abandon ship.
			if ( ! icon || position !== finalIconPosition ) {
				return null;
			}
			// Render the icon.
			return (
				<RenderSVG
					className={ spectraClassNames( [
						'spectra-button__icon',
						`spectra-button__icon-position-${ finalIconPosition }`,
						iconColor && 'spectra-icon-color',
						( iconColorHover || textColorHover ) && 'spectra-icon-color-hover',
					] ) }
					svg={ icon }
					needsRTL={ flipForRTL }
					extraProps={ {
						width: size ? size : '16px',
						height: size ? size : '16px',
						style: {
							width: size || '16px',
							height: size || '16px',
							transform: rotation ? `rotate(${ rotation }deg)` : '',
						},
					} }
				/>
			);
		},
		[ icon, iconPosition, size, iconColor, iconColorHover, textColorHover, rotation, flipForRTL ]
	);

	// Hover icon rendering function.
	const hoverIconHtml = useCallback(
		( position ) => {
			const finalHoverIconPosition = hoverIconPosition || 'right'; // Ensure a default value.
			// Only render if showIconOnHover is enabled, hoverIcon exists, and position matches.
			if ( ! showIconOnHover || ! hoverIcon || position !== finalHoverIconPosition ) {
				return null;
			}

			return (
				<RenderSVG
					className={ spectraClassNames( [
						'spectra-button__hover-icon',
						`spectra-button__hover-icon-position-${ finalHoverIconPosition }`,
						iconColor && 'spectra-icon-color',
						( iconColorHover || textColorHover ) && 'spectra-icon-color-hover',
					] ) }
					svg={ hoverIcon }
					needsRTL={ hoverIconFlipForRTL }
					extraProps={ {
						width: size ? size : '16px',
						height: size ? size : '16px',
						style: {
							width: size || '16px',
							height: size || '16px',
							transform: hoverIconRotation ? `rotate(${ hoverIconRotation }deg)` : '',
						},
					} }
				/>
			);
		},
		[ showIconOnHover, hoverIcon, hoverIconPosition, hoverIconRotation, size, iconColor, iconColorHover, textColorHover, hoverIconFlipForRTL ]
	);

	const btnText = useCallback( () => {
		// If we don't have to show text, abandon ship.
		if ( ! showText ) {
			return '';
		}
		return (
			<RichText
				placeholder={ __( 'Add text…', 'ultimate-addons-for-gutenberg' ) }
				value={ text }
				tagName="div"
				onChange={ ( value ) => setAttributes( { text: value } ) }
				className="spectra-button__link"
				rel={ htmlTagLink?.noFollow ? 'nofollow noopener' : 'follow noopener' }
				keepPlaceholderOnFocus
				allowedFormats={ allowedFormats }
			/>
		);
	}, [ showText, text, setAttributes, htmlTagLink ] );

	return (
		<div { ...blockProps }>
			{ iconHtml( 'before' ) }
			{ hoverIconHtml( 'left' ) }
			{ btnText() }
			{ hoverIconHtml( 'right' ) }
			{ iconHtml( 'after' ) }
		</div>
	);
};

export default memo( Render );
