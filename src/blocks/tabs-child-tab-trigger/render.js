/**
 * External dependencies.
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { memo, useEffect } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { RenderFullWidthAppenderWhenEmpty } from '@spectra-components/block-appender';

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { 
		attributes, 
		clientId,
		isSelected,
		setAttributes,
		context: {
			'spectra/tabs/currentTab': currentlyActiveTab,
			'spectra/tabs/textColor': inheritedTextColor,
			'spectra/tabs/textColorHover': inheritedTextColorHover,
			'spectra/tabs/textColorActive': inheritedTextColorActive,
			'spectra/tabs/textColorActiveHover': inheritedTextColorActiveHover,
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
	} = props;

	const {
		overflow,
		width,
		height,
		minWidth,
		minHeight,
		maxWidth,
		maxHeight,
		currentTab,
		textColorHover = inheritedTextColorHover,
		textColorActive = inheritedTextColorActive,
		textColorActiveHover = inheritedTextColorActiveHover,
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
	} = attributes;

	const { getBlockIndex, getBlockParentsByBlockName } = useSelect( 'core/block-editor' );
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );
	const tabIndex = getBlockIndex( clientId );

	const { hasChildren, isAnyInnerBlockSelected, blockChildren } = useSelect(
		( select ) => {
			const { getBlock, getSelectedBlockClientId, getBlocks } = select( 'core/block-editor' );
			const block = getBlock( clientId );
			const selectedBlockId = getSelectedBlockClientId();
			
			// Recursive function to check if any inner block is selected
			const checkInnerBlocksSelection = ( blockId ) => {
				const innerBlocks = getBlocks( blockId );
				
				// Check each inner block
				for ( const innerBlock of innerBlocks ) {
					// Check if this inner block is selected
					if ( selectedBlockId === innerBlock.clientId ) {
						return true;
					}
					
					// Recursively check its children
					if ( checkInnerBlocksSelection( innerBlock.clientId ) ) {
						return true;
					}
				}
				
				return false;
			};
			
			return {
				hasChildren: block?.innerBlocks.length > 0,
				isAnyInnerBlockSelected: checkInnerBlocksSelection( clientId ),
				blockChildren: getBlocks( clientId )
			};
		},
		[ clientId ]
	);

	// Update the currentTab's value if the index position changes
	useEffect( () => {
		setAttributes( { currentTab: tabIndex } );
	}, [ tabIndex, setAttributes ] );

	// Set variationSelected to true for any Container blocks that are inserted
	useEffect( () => {
		blockChildren.forEach( ( child ) => {
			// Mark this child as the default selected variation if it is a container
			if ( 'spectra/container' === child.name && ! child.attributes.variationSelected ) {
				updateBlockAttributes( child.clientId, { variationSelected: true } );
			}
		} );
	}, [ blockChildren, updateBlockAttributes ] );

	// Make the content of this tab visible when selected or when any inner block is selected
	useEffect( () => {
		if ( isSelected || isAnyInnerBlockSelected ) {
			const tabParentId = getBlockParentsByBlockName(
				clientId,
				'spectra/tabs',
				true
			)[ 0 ];

			if ( tabParentId ) {
				updateBlockAttributes( tabParentId, { currentTab } );
			}
		}
	}, [ clientId, isSelected, isAnyInnerBlockSelected, currentTab ] );

	// Configuration for the useSpectraStyles hook
	const config = [
		{ key: 'textColor', value: inheritedTextColor },
		{ key: 'textColorHover', value: textColorHover },
		{ key: 'textColorActive', value: textColorActive },
		{ key: 'textColorActiveHover', value: textColorActiveHover },
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
	];

	// Custom class names
	const customClassNames = [
		currentTab === currentlyActiveTab && 'spectra-block-is-active',
		! hasChildren && 'inner-container'
	];

	// Generate styles and class names
	const { style, classNames } = useSpectraStyles( attributes, config, customClassNames );


	// Parent block properties
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style: {
			width,
			height,
			minWidth,
			'min-height': minHeight,
			'max-width': maxWidth,
			'max-height': maxHeight,
			'position': 'relative',
			overflow,
			...style,
		},
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks renderAppender={ RenderFullWidthAppenderWhenEmpty( clientId ) } >
				{ hasChildren ? <InnerBlocks.Content /> : null }
			</InnerBlocks>
		</div>
	);
};

export default memo( Render );