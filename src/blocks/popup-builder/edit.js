/**
 * External dependencies.
 */
import { useEffect, useState } from '@wordpress/element';
import { useSelect, useDispatch, select } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarButton } from '@wordpress/components';
import { createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import { VariationPicker } from '@spectra-components/variation-picker';
import RenderBlockPreview from '@spectra-components/render-block-preview';
import blockIcons, { helperIcons } from '@spectra-helpers/block-icons';
import variations from './variations';
import Settings from './settings';
import Render from './render';
import './editor.scss';

const Edit = ( props ) => {
	/**
	 * The edit function describes the structure of your block in the context of the
	 * editor. This represents what the editor will render when the block is used.
	 *
	 * @param {Object} props The element props.
	 * @return {Element} Element to render.
	 */
	const {
		isSelected,
		clientId,
		attributes: {
			isPreview,
			blockId,
			isOpen,
			variationSelected,
			variantType,
		},
		setAttributes,
	} = props;

	// State to control the variation picker visibility
	const [ showVariationPicker, setShowVariationPicker ] = useState( false );

	// Get block variations and parents.
	const { blockChildren } = useSelect( ( selector ) => {
		// Use the selectors.
		const { getBlocks } = selector( 'core/block-editor' );

		// Get the children (if any).
		const currentBlockChildren = getBlocks( clientId );

		// Return the required data.
		return {
			blockChildren: currentBlockChildren,
		};
	} );

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	// Propagate variation selection to inner blocks when required.
	useEffect( () => {
		if ( variationSelected ) {
			blockChildren.forEach( ( child ) => {
				// Mark this child as the default selected variation if it is a container.
				if (
					false === child.attributes?.variationSelected &&
					'spectra/container' === child.name
				) {
					updateBlockAttributes( child.clientId, {
						variationSelected: true,
					} );
				}
			} );
		}
	}, [ variationSelected, blockChildren, updateBlockAttributes ] );

	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );
	const postType = useSelect( ( selector ) =>
		selector( 'core/editor' ).getCurrentPostType()
	);
	const postId = useSelect( ( selector ) =>
		selector( 'core/editor' ).getCurrentPostId()
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	// Generate unique block ID if not set
	useEffect( () => {
		setAttributes( { blockId: `popup-${ clientId.substring( 0, 8 ) }` } );
	}, [ blockId, clientId, setAttributes ] );

	useEffect( () => {
		setAttributes( { popupId: `popup-${ postId }` } );
	}, [ postId, setAttributes ] );

	// Always show popup in editor for editing
	useEffect( () => {
		if ( ! isOpen && isSelected ) {
			setAttributes( { isOpen: true } );
		}
	}, [ isSelected, isOpen, setAttributes ] );

	useEffect( () => {
		const isLegacyPopup = 'spectra-popup' === postType;
		if ( 'spectra-blocks-popup' !== postType && ! isLegacyPopup ) {
			return;
		}
		const typeKey    = isLegacyPopup ? 'spectra-popup-type' : 'spectra-blocks-popup-type';
		const enabledKey = isLegacyPopup ? 'spectra-popup-enabled' : 'spectra-blocks-popup-enabled';
		setMeta(
			! variationSelected
				? {
						...meta,
						[ typeKey ]: 'unset',
						[ enabledKey ]: false,
				  }
				: {
						...meta,
						[ typeKey ]: variantType,
						[ enabledKey ]: true,
				  }
		);
	}, [ variationSelected, variantType ] );

	// Early return if not in popup post type context
	if ( 'spectra-blocks-popup' !== window.typenow && 'spectra-popup' !== window.typenow ) {
		return null;
	}

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview blockName="popup-builder" />;
	}

	// Handle variation selection
	const handleVariationSelection = ( nextVariation ) => {
		// Validate input
		if ( ! nextVariation ) {
			return;
		}

		// Set the attributes from the selected variation
		if ( nextVariation.attributes ) {
			setAttributes( {
				...nextVariation.attributes,
				variationSelected: true,
				isOpen: true, // Always open in editor for editing
			} );
		}

		// Set the innerblocks if required
		if ( nextVariation.innerBlocks ) {
			// Create the blocks from the template
			const newBlocks = createBlocksFromInnerBlocksTemplate(
				nextVariation.innerBlocks
			);

			// Replace the inner blocks
			replaceInnerBlocks( clientId, newBlocks );
		}

		// Hide the variation picker
		setShowVariationPicker( false );
	};

	// Initial creation or showing variation picker after toolbar button click
	if (
		( ! variationSelected || showVariationPicker ) &&
		0 === select( 'core/block-editor' ).getBlockParents( clientId ).length
	) {
		return (
			<VariationPicker
				{ ...{
					...props,
					icon: blockIcons.modal(),
					label: __(
						'Spectra Popup Builder',
						'spectra-blocks'
					),
					instructions: __(
						'Select a Popup type to start with.',
						'spectra-blocks'
					),
					variations,
					onSelect: handleVariationSelection,
				} }
			/>
		);
	}

	// Normal render with toolbar button
	return (
		<>
			{ isSelected && (
				<BlockControls>
					<ToolbarButton
						icon={ helperIcons.variationSwitch() }
						label={ __(
							'Choose Type',
							'spectra-blocks'
						) }
						onClick={ () => setShowVariationPicker( true ) }
					/>
				</BlockControls>
			) }

			{ isSelected && <Settings { ...props } /> }
			<Render { ...props } />
		</>
	);
};

export default Edit;
