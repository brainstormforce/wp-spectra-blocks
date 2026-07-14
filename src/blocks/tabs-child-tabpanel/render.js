/**
 * External dependencies.
 */
import {
	useBlockProps,
	InnerBlocks,
} from '@wordpress/block-editor';
import { memo, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { useSpectraStyles } from '@spectra-hooks';
import { spectraClassNames } from '@spectra-helpers';
import { getTabpanelIndex } from '@spectra-blocks/tabs/helpers';
import { RenderFullWidthAppenderWhenEmpty } from '@spectra-components/block-appender';

/**
 * The Editor Block render.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const {
		isSelected,
		clientId,
		setAttributes,
		context: {
			'spectra/tabs/currentTab': currentlyActiveTab,
			'spectra/tabs/textColorSecondary': inheritedTextColor,
			'spectra/tabs/textColorHoverSecondary': inheritedTextColorHover,
			'spectra/tabs/styleColorText': inheritedStyleColorText,
			'spectra/tabs/backgroundColorSecondary': inheritedBackgroundColor,
			'spectra/tabs/backgroundColorHoverSecondary': inheritedBackgroundColorHover,
			'spectra/tabs/backgroundGradientSecondary': inheritedBackgroundGradient,
			'spectra/tabs/backgroundGradientHoverSecondary': inheritedBackgroundGradientHover,
		},
		attributes,
	} = props;

	const {
		currentTab,
		textColor = inheritedTextColor || inheritedStyleColorText?.color?.text,
		textColorHover = inheritedTextColorHover,
		backgroundColor = inheritedBackgroundColor,
		backgroundColorHover = inheritedBackgroundColorHover,
		backgroundGradient = inheritedBackgroundGradient,
		backgroundGradientHover = inheritedBackgroundGradientHover,
	} = attributes;

	const tabpanelIndex = getTabpanelIndex( clientId );

	const tabParentId = useSelect( ( select ) => {
		if ( ! isSelected ) {
			return null;
		}
		const { getBlockParentsByBlockName } = select( 'core/block-editor' );
		return getBlockParentsByBlockName( clientId, 'spectra/tabs', true )[0];
	}, [ clientId, isSelected ] );

	// 🚨 INFINITE LOOP FIX: Only update currentTab if it's actually different
	useEffect( () => {
		if ( -1 !== tabpanelIndex && tabpanelIndex !== currentTab ) {
			const timeoutId = setTimeout( () => {
				setAttributes( { currentTab: tabpanelIndex } );
			}, 50 );
			return () => clearTimeout( timeoutId );
		}
	}, [ tabpanelIndex, currentTab, setAttributes ] );

	// Get dispatch function using proper useDispatch hook
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	// 🚨 INFINITE LOOP FIX: Only update parent if value is different + debounce
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
		{ key: 'backgroundColor', value: backgroundColor },
		{ key: 'backgroundColorHover', value: backgroundColorHover },
		{ key: 'backgroundGradient', value: backgroundGradient },
		{ key: 'backgroundGradientHover', value: backgroundGradientHover },
	];

	// Custom class names
	const customClassNames = [
		( currentTab !== currentlyActiveTab ) && 'spectra-block-is-hidden',
		( textColor || textColorHover ) && 'has-text-color',
	];

	// Generate styles and class names.
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );

	// Use the block props, with the added CSS varialbes and their related classes.
	const blockProps = useBlockProps( {
		style,
		className: spectraClassNames( classNames ),
	} );

	return (
		<div { ...blockProps } >
			<InnerBlocks
				templateLock={ false }
				renderAppender={ RenderFullWidthAppenderWhenEmpty( clientId ) }
			/>
		</div>
	);
};

export default memo( Render );
