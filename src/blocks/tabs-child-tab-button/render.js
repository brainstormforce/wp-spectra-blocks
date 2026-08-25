/**
 * External dependencies.
 */
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { memo, useEffect, useCallback } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { useSpectraStyles } from '@spectra-hooks';
import { removeAnchorTag, spectraClassNames } from '@spectra-helpers';
import RenderSVG from '@spectra-helpers/render-svg';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since 1.0.6
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const {
		clientId,
		isSelected,
		setAttributes,
		context: {
			'spectra/tabs/currentTab': currentlyActiveTab,
			'spectra/tabs/icon': tabsIcon,
			'spectra/tabs/iconPosition': tabsIconPosition,
			'spectra/tabs/size': tabsIconSize,
			'spectra/tabs/textColor': inheritedTextColor,
			'spectra/tabs/styleColorText': inheritedStyleColorText,
			'spectra/tabs/textColorHover': inheritedTextColorHover,
			'spectra/tabs/textColorActive': inheritedTextColorActive,
			'spectra/tabs/textColorActiveHover': inheritedTextColorActiveHover,
			'spectra/tabs/iconColor': inheritedIconColor,
			'spectra/tabs/iconColorHover': inheritedIconColorHover,
			'spectra/tabs/iconColorActive': inheritedIconColorActive,
			'spectra/tabs/iconColorActiveHover': inheritedIconColorActiveHover,
			'spectra/tabs/backgroundColor': inheritedBackgroundColor,
			'spectra/tabs/backgroundColorHover': inheritedBackgroundColorHover,
			'spectra/tabs/backgroundColorActive': inheritedBackgroundColorActive,
			'spectra/tabs/backgroundColorActiveHover': inheritedBackgroundColorActiveHover,
			'spectra/tabs/backgroundGradient': inheritedBackgroundGradient,
			'spectra/tabs/backgroundGradientHover': inheritedBackgroundGradientHover,
			'spectra/tabs/backgroundGradientActive': inheritedBackgroundGradientActive,
			'spectra/tabs/backgroundGradientActiveHover': inheritedBackgroundGradientActiveHover,
			'spectra/tabs/borderColorHover': inheritedBorderColorHover,
			'spectra/tabs/borderColorActive': inheritedBorderColorActive,
			'spectra/tabs/borderColorActiveHover': inheritedBorderColorActiveHover,
		},
		attributes,
	} = props;

	const {
		currentTab,
		placeholder,
		text,
		showText,
		icon,
		iconPosition,
		flipForRTL,
		rotation,
		textColor = inheritedTextColor || inheritedStyleColorText?.color?.text,
		textColorHover = inheritedTextColorHover,
		textColorActive = inheritedTextColorActive,
		textColorActiveHover = inheritedTextColorActiveHover,
		iconColor = inheritedIconColor,
		iconColorHover = inheritedIconColorHover,
		iconColorActive = inheritedIconColorActive,
		iconColorActiveHover = inheritedIconColorActiveHover,
		backgroundColor = inheritedBackgroundColor,
		backgroundColorHover = inheritedBackgroundColorHover,
		backgroundColorActive = inheritedBackgroundColorActive,
		backgroundColorActiveHover = inheritedBackgroundColorActiveHover,
		backgroundGradient = inheritedBackgroundGradient,
		backgroundGradientHover = inheritedBackgroundGradientHover,
		backgroundGradientActive = inheritedBackgroundGradientActive,
		backgroundGradientActiveHover = inheritedBackgroundGradientActiveHover,
		borderColorHover = inheritedBorderColorHover,
		borderColorActive = inheritedBorderColorActive,
		borderColorActiveHover = inheritedBorderColorActiveHover,
		size,
	} = attributes;

	// Set the icon and size to use based on the attribute or context.
	const iconToUse = icon || tabsIcon;
	const sizeToUse = size || tabsIconSize || '16px';
	// Normalize each SOURCE before the fallback, not the collapsed result: with
	// `iconPosition || tabsIconPosition`, an INVALID child value still won the
	// `||` and shadowed a valid parent. Child 'left' + parent 'before' painted
	// right in the editor and left on the frontend, because core unsets the
	// out-of-enum child and controller.php then reads the context. Same order
	// here keeps the two surfaces agreeing.
	const validPosition = ( v ) => ( [ 'before', 'after' ].includes( v ) ? v : '' );
	const positionToUse = validPosition( iconPosition ) || validPosition( tabsIconPosition ) || 'after';
	
	// Get tab index and parent ID
	const tabIndex = useSelect( ( select ) => {
		const { getBlockIndex } = select( 'core/block-editor' );
		return getBlockIndex( clientId );
	}, [ clientId ] );

	const tabParentId = useSelect( ( select ) => {
		if ( ! isSelected ) {
			return null;
		}
		const { getBlockParentsByBlockName } = select( 'core/block-editor' );
		return getBlockParentsByBlockName( clientId, 'spectra/tabs', true )[0];
	}, [ clientId, isSelected ] );

	// INFINITE LOOP FIX: Only update currentTab if it's actually different for duplicate causing memeory leak.
	useEffect( () => {
		if ( typeof tabIndex === 'number' && tabIndex >= 0 && tabIndex !== currentTab ) {
			const timeoutId = setTimeout( () => {
				setAttributes( { currentTab: tabIndex } );
			}, 50 );
			return () => clearTimeout( timeoutId );
		}
	}, [ tabIndex, currentTab, setAttributes ] );

	// Get dispatch function using proper useDispatch hook.
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	// INFINITE LOOP FIX: Only update parent if value is different + debounce.
	useEffect( () => {
		if ( isSelected && tabParentId && typeof currentTab !== 'undefined' ) {
			const timeoutId = setTimeout( () => {
				const { select } = wp.data;
				const parentBlock = select( 'core/block-editor' ).getBlock( tabParentId );
				if ( parentBlock && parentBlock.attributes?.currentTab !== currentTab ) {
					updateBlockAttributes( tabParentId, { currentTab } );
				}
			}, 100 );
			return () => clearTimeout( timeoutId );
		}
	}, [ isSelected, tabParentId, currentTab, updateBlockAttributes ] );

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor', value: textColor },
		{ key: 'textColorHover', value: textColorHover },
		{ key: 'textColorActive', value: textColorActive },
		{ key: 'textColorActiveHover', value: textColorActiveHover },
		{ key: 'iconColor', value: iconColor },
		{ key: 'iconColorHover', value: iconColorHover },
		{ key: 'iconColorActive', value: iconColorActive },
		{ key: 'iconColorActiveHover', value: iconColorActiveHover },
		{ key: 'backgroundColor', value: backgroundColor },
		{ key: 'backgroundColorHover', value: backgroundColorHover },
		{ key: 'backgroundColorActive', value: backgroundColorActive },
		{ key: 'backgroundColorActiveHover', value: backgroundColorActiveHover },
		{ key: 'backgroundGradient', value: backgroundGradient },
		{ key: 'backgroundGradientHover', value: backgroundGradientHover },
		{ key: 'backgroundGradientActive', value: backgroundGradientActive },
		{ key: 'backgroundGradientActiveHover', value: backgroundGradientActiveHover },
		{ key: 'borderColorHover', value: borderColorHover },
		{ key: 'borderColorActive', value: borderColorActive },
		{ key: 'borderColorActiveHover', value: borderColorActiveHover },
		{ key: 'gap' },
	];

	const customClassNames = [
		'spectra-button',
		'wp-block-button',
		'wp-block-button__link',
		( currentTab === currentlyActiveTab ) && 'spectra-block-is-active',
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	// Render the icon HTML when any required attribute changes.
	const iconHtml = useCallback( ( position ) => {
		const finalIconPosition = positionToUse; // Already normalized to 'before' | 'after' above.
		// If there's no icon, or if the position given does not match the required one, abandon ship.
		if ( ! iconToUse || position !== finalIconPosition ) {
			return null;
		}
		// Render the icon.
		return (
			<RenderSVG
				className={ spectraClassNames( [
					'spectra-button__icon',
					`spectra-button__icon-position-${ finalIconPosition }`,
					( iconColor || inheritedIconColor ) && 'spectra-icon-color',
					( iconColorHover || inheritedIconColorHover ) && 'spectra-icon-color-hover',
					( iconColorActive || inheritedIconColorActive ) && 'spectra-icon-color-active',
					( iconColorActiveHover || inheritedIconColorActiveHover ) && 'spectra-icon-color-active-hover',
				] ) }
				svg={ iconToUse }
				needsRTL={ flipForRTL }
				extraProps={ {
					width: sizeToUse,
					height: sizeToUse,
					style: {
						width: sizeToUse,
						height: sizeToUse,
						transform: rotation ? `rotate(${ rotation }deg)` : '',
					},
				} }
			/>
		);
	}, [
		icon,
		tabsIcon,
		iconPosition,
		tabsIconPosition,
		size,
		tabsIconSize,
		iconColor,
		inheritedIconColor,
		iconColorHover,
		inheritedIconColorHover,
		iconColorActive,
		iconColorActiveHover,
		rotation,
		flipForRTL
	] );

	// Render the button text whenever any of the required attributes changes.
	const btnText = useCallback( () => {
		// If we don't have to show text, abandon ship.
		if ( ! showText ) {
			return '';
		}
		return (
			<RichText
				placeholder={ placeholder }
				value={ text }
				tagName="span"
				onChange={ ( value ) => setAttributes( { text: removeAnchorTag( value ) } ) }
				className="spectra-button__link"
				withoutInteractiveFormatting
			/>
		);
	}, [
		showText,
		text,
		setAttributes
	] );

	// Note, the tab will render as a div in the editor, but it is rendered as a button in the front-end.
	return (
		<div { ...blockProps }>
			{ iconHtml( 'before' ) }
			{ btnText() }
			{ iconHtml( 'after' ) }
		</div>
	)
};

export default memo( Render );
