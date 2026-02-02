/**
 * External dependencies.
 */
import { useEffect, useState, useRef } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import { VariationPicker } from '@spectra-components/variation-picker';
import RenderBlockPreview from '@spectra-components/render-block-preview';
import blockIcons from '@spectra-helpers/block-icons';
import { variations } from './variations';
import ContainerToolbar from './toolbar';
import './editor.scss';
import Settings from './settings';
import Render from './render';

const Edit = ( props ) => {
	const {
		isSelected,
		name,
		clientId,
		attributes: {
			isPreview,
			variationSelected,
		},
		attributes,
		setAttributes,
	} = props;

	// State to control the variation picker visibility
	// When true, shows the variation picker UI instead of the normal container content
	// This state is toggled by the toolbar button and reset after variation selection
	const [ showVariationPicker, setShowVariationPicker ] = useState( false );

	// Ref to track if we're currently applying a variation (to prevent auto-selection conflicts)
	const isApplyingVariation = useRef( false );


	// Get block variations and parents.
	const {
		defaultVariation,
		blockChildren,
		parentBlocks,
	} = useSelect( ( select ) => {
		// Use the selectors.
		const {
			getDefaultBlockVariation,
		} = select( 'core/blocks' );
		const {
			getBlockParents,
			getBlocks,
			getBlocksByClientId,
		} = select( 'core/block-editor' );

		// Get the parent (if any), the children (if any), and the parent ID (if any).
		const currentBlockParents = getBlockParents( clientId );
		const currentBlockChildren = getBlocks( clientId );
		const currentParentBlocks = getBlocksByClientId( currentBlockParents );

		// Return the required data.
		return {
			defaultVariation: getDefaultBlockVariation( name ),
			blockParents: currentBlockParents,
			blockChildren: currentBlockChildren,
			parentBlocks: currentParentBlocks,
		};
	} );

	// Ref to track previous children count for auto-selection
	// Initialize with current length to avoid auto-selection on mount/remount (e.g. switching device views)
	const previousChildrenCount = useRef( blockChildren ? blockChildren.length : 0 );

	const { updateBlockAttributes, selectBlock } = useDispatch( 'core/block-editor' );
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	// Set isBlockRootParent based on parent blocks and manage align support.
	useEffect( () => {
		const isRootContainer = ! parentBlocks || parentBlocks.length === 0 || ! parentBlocks.some( parent => parent.name && parent.name.includes( 'spectra' ) );

		if ( isRootContainer !== attributes.isBlockRootParent ) {
			const updates = { isBlockRootParent: isRootContainer };

			// For root containers, set default align if not set
			if ( isRootContainer && !attributes.align ) {
				updates.align = 'full';
			}
			// For nested containers, remove align to prevent alignment controls
			else if ( !isRootContainer && attributes.align ) {
				updates.align = undefined;
			}

			setAttributes( updates );
		}
	}, [ parentBlocks, attributes.isBlockRootParent, attributes.align ] );

	// Handle backward compatibility: Remove old core style.shadow when custom boxShadow is used.
	// This prevents conflicts between the old WordPress core shadow and the new custom shadow system.
	// TODO: Remove this entire useEffect after one version release (safe to remove in version after 3.0.0.3)
	useEffect( () => {
		const { boxShadow, boxShadowHover, style } = attributes;

		// If custom boxShadow is used and old style.shadow exists, remove it
		if ( ( boxShadow || boxShadowHover ) && style?.shadow ) {
			setAttributes( {
				style: {
					...style,
					shadow: undefined
				}
			} );
		}
	}, [ attributes.boxShadow, attributes.boxShadowHover, attributes.style?.shadow ] );

	// Propagate variation selection to inner blocks when required.
	useEffect( () => {
		if ( variationSelected ) {
			blockChildren.forEach( ( child ) => {
				// Mark this child as the default selected variation if it is a container.
				if ( 'spectra/container' === child.name ) {
					updateBlockAttributes( child.clientId, { variationSelected: true } );
				}
			} );
		}
	}, [ variationSelected, blockChildren ] );

	// Auto-select newly inserted container blocks within this container
	useEffect( () => {
		const currentChildrenCount = blockChildren.length;
		
		// Only proceed if count actually increased
		if ( currentChildrenCount <= previousChildrenCount.current ) {
			previousChildrenCount.current = currentChildrenCount;
			return;
		}

		// Skip auto-selection if we're currently applying a variation
		// This prevents conflicts between variation selection and auto-selection
		if ( isApplyingVariation.current ) {
			previousChildrenCount.current = currentChildrenCount;
			return;
		}

		// Get the most recently added child block
		const newBlock = blockChildren[ currentChildrenCount - 1 ];
		
		// Only auto-select if it's a container block
		if ( newBlock?.name === 'spectra/container' ) {
			// Check if block is ready for selection
			const { getBlock } = wp.data.select( 'core/block-editor' );
			if ( getBlock( newBlock.clientId ) ) {
				// Block exists and is ready - select immediately
				selectBlock( newBlock.clientId );
			} else {
				// Block not ready yet - defer to next frame
				requestAnimationFrame( () => {
					selectBlock( newBlock.clientId );
				} );
			}
		}
		
		previousChildrenCount.current = currentChildrenCount;
	}, [ blockChildren, selectBlock ] );

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview blockName="container"/>;
	}

	// Handle variation selection
	const handleVariationSelection = ( nextVariation ) => {
		// Validate input
		if ( !nextVariation ) {
			return;
		}

		// Set flag to prevent auto-selection conflicts during variation application
		isApplyingVariation.current = true;

		// Set the attributes from the selected variation
		// Important: We use setAttributes which does a shallow merge at the top level
		// For nested objects like 'layout' and 'style', WordPress replaces them entirely
		// This ensures switching between grid/flex layouts works correctly
		if ( nextVariation.attributes ) {
			setAttributes( {
				...nextVariation.attributes,
				variationSelected: true,
			} );
		}

		// Check if all existing children are containers (layout structure vs mixed content)
		const allChildrenAreContainers = blockChildren.length > 0 && blockChildren.every(
			child => child.name === 'spectra/container'
		);

		// Check if we have non-container content (e.g., paragraphs, images directly in parent)
		const hasDirectContent = blockChildren.length > 0 && !allChildrenAreContainers;

		// Handle innerBlocks based on whether the variation defines a layout structure
		if ( nextVariation.innerBlocks ) {
			// Variation HAS innerBlocks (e.g., 2-column, 3-column) - needs container structure

			// Determine if we should adjust the layout structure
			// We adjust if: empty, has only containers, OR has direct content (from one-column)
			const shouldAdjustLayout = blockChildren.length === 0 || allChildrenAreContainers || hasDirectContent;

			if ( shouldAdjustLayout ) {
				// Get the number of containers required by the new variation
				const requiredContainerCount = nextVariation.innerBlocks.length;
				const currentContainerCount = blockChildren.length;

				// Create new blocks from template
				const newBlocks = createBlocksFromInnerBlocksTemplate( nextVariation.innerBlocks );

				// If we have existing container children with content, preserve their content
				if ( allChildrenAreContainers && currentContainerCount > 0 ) {
					// Map existing container content to new structure
					blockChildren.forEach( ( existingChild, index ) => {
						if ( index < requiredContainerCount && existingChild.innerBlocks.length > 0 ) {
							// Transfer the content from existing container to new container
							newBlocks[ index ].innerBlocks = existingChild.innerBlocks;
						}
					} );
				}
				// If we have direct content (from one-column variation), put it in first container
				else if ( hasDirectContent ) {
					// Put all direct content into the first container
					newBlocks[ 0 ].innerBlocks = blockChildren;
				}

				// Mark all new container blocks as variationSelected
				let firstContainerBlock = null;
				newBlocks.forEach( block => {
					if ( block.name === 'spectra/container' ) {
						block.attributes.variationSelected = true;
						if ( ! firstContainerBlock ) {
							firstContainerBlock = block;
						}
					}
				} );

				// Replace the inner blocks with adjusted structure
				replaceInnerBlocks( clientId, newBlocks );

				// Auto-select the first container block if it exists
				if ( firstContainerBlock ) {
					requestAnimationFrame( () => {
						selectBlock( firstContainerBlock.clientId );
						isApplyingVariation.current = false;
					} );
				} else {
					isApplyingVariation.current = false;
				}
			} else {
				// Mixed content (not all containers) - just update layout attributes, preserve all content
				selectBlock( clientId, -1 );
				isApplyingVariation.current = false;
			}
		} else {
			// Variation has NO innerBlocks (e.g., one-column variation)
			// Remove container structure and flatten content to parent

			if ( allChildrenAreContainers && blockChildren.length > 0 ) {
				// Collect all content from child containers and flatten to parent level
				const contentBlocks = [];

				blockChildren.forEach( containerChild => {
					// Get all blocks inside this container
					if ( containerChild.innerBlocks && containerChild.innerBlocks.length > 0 ) {
						contentBlocks.push( ...containerChild.innerBlocks );
					}
				} );

				// Replace child containers with just the content blocks
				replaceInnerBlocks( clientId, contentBlocks );

				// Select parent container
				selectBlock( clientId, -1 );
			}

			// Reset flag
			isApplyingVariation.current = false;
		}

		// Hide the variation picker
		setShowVariationPicker( false );
	};

	// Initial creation or showing variation picker after toolbar button click
	if ( !variationSelected || showVariationPicker ) {
		return (
			<VariationPicker { ...{
				...props,
				icon: blockIcons.container(),
				label: __( 'Container', 'spectra' ),
				instructions: __( 'Select a container layout', 'spectra' ),
				variations,
				defaultVariation,
				onSelect: handleVariationSelection
			} } />
		);
	}
	
	// Normal render with consolidated toolbar
	return (
		<>
			<ContainerToolbar
				{ ...props }
				onShowVariationPicker={ () => setShowVariationPicker( true ) }
			/>
			
			{ isSelected && <Settings { ...props } /> }
			<Render { ...props } />
		</>
	);
};

export default Edit;