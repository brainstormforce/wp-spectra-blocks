/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockControls, store as blockEditorStore } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { useDispatch, useSelect, dispatch, select } from '@wordpress/data';
import { createBlock, cloneBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { helperIcons } from '../../helpers/block-icons';

/**
 * Helper function to duplicate an accordion item and clean its content.
 *
 * @param {Object} sourceBlock The accordion item block to duplicate.
 * @since x.x.x
 * @return {Object} The cleaned duplicated block.
 */
const duplicateAndCleanAccordionItem = ( sourceBlock ) => {
	// Clone the entire accordion item block
	const duplicatedItem = cloneBlock( sourceBlock );
	
	// Function to recursively clean header content blocks
	const cleanHeaderContent = ( block ) => {
		if ( block.name === 'spectra/accordion-child-header-content' ) {
			// Clear the text attribute
			return {
				...block,
				attributes: {
					...block.attributes,
					text: '', // Clear the text content
				},
			};
		}
		
		// Recursively clean innerBlocks
		if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
			return {
				...block,
				innerBlocks: block.innerBlocks.map( cleanHeaderContent ),
			};
		}
		
		return block;
	};
	
	// Function to clear details innerBlocks
	const clearDetailsContent = ( block ) => {
		if ( block.name === 'spectra/accordion-child-details' ) {
			// Clear all innerBlocks from details
			return {
				...block,
				innerBlocks: [], // Clear all content from details
			};
		}
		
		// Recursively process innerBlocks
		if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
			return {
				...block,
				innerBlocks: block.innerBlocks.map( clearDetailsContent ),
			};
		}
		
		return block;
	};
	
	// Clean the duplicated item
	let cleanedItem = cleanHeaderContent( duplicatedItem );
	cleanedItem = clearDetailsContent( cleanedItem );
	
	return cleanedItem;
};

/**
 * Function to insert a new accordion item by duplicating an existing one.
 *
 * @param {string}      clientId        The current block's ID.
 * @param {string|null} duplicateItemId Optional specific item ID to duplicate. If not provided, duplicates the last item or creates new if none exist.
 * @since x.x.x
 */
export const insertNewAccordionItem = ( clientId, duplicateItemId = null ) => {
	const {
		getBlock,
		getBlockParentsByBlockName,
	} = select( blockEditorStore );
	const { insertBlock } = dispatch( blockEditorStore );
	
	// Get the currentBlock, and keep empty variables for the innerBlocks.
	const currentBlock = getBlock( clientId );
	let rootClientId, innerBlocks;

	// If the current block is the accordion block, get the innerblocks.
	if ( 'spectra/accordion' === currentBlock.name ) {
		rootClientId = currentBlock.clientId;
		innerBlocks = currentBlock.innerBlocks;
	} else {
		rootClientId = getBlockParentsByBlockName( clientId, 'spectra/accordion', true )[0];
		innerBlocks = getBlock( rootClientId ).innerBlocks;
	}

	// Filter to get only accordion item blocks
	const accordionItems = innerBlocks.filter( block => block.name === 'spectra/accordion-child-item' );
	
	let newAccordionItem;
	
	if ( accordionItems.length > 0 ) {
		// Determine which item to duplicate
		let sourceItem;
		
		if ( duplicateItemId ) {
			// Find the specific item to duplicate
			sourceItem = accordionItems.find( item => item.clientId === duplicateItemId );
			if ( ! sourceItem ) {
				// Fallback to last item if specified item not found
				sourceItem = accordionItems[ accordionItems.length - 1 ];
			}
		} else {
			// Default: duplicate the last item
			sourceItem = accordionItems[ accordionItems.length - 1 ];
		}
		
		// Duplicate and clean the source item
		newAccordionItem = duplicateAndCleanAccordionItem( sourceItem );
	} else {
		// Fallback: create a new accordion item if none exist
		newAccordionItem = createBlock( 'spectra/accordion-child-item' );
	}

	// Set the position for the new accordion item
	const itemPosition = innerBlocks.length;

	// Insert the new accordion item, disabling focus.
	insertBlock( newAccordionItem, itemPosition, rootClientId, false );
};

/**
 * Function to remove the current accordion item.
 *
 * @since x.x.x
 *
 * @param {string} clientId The current block's ID.
 */
export const removeCurrentAccordionItem = ( clientId ) => {
	// Get the block editor store functions.
	const {
		getBlock,
		getBlockParentsByBlockName,
	} = select( blockEditorStore );

	// If the current block is already an accordion item, remove it directly
	const currentBlock = getBlock( clientId );
	if ( 'spectra/accordion-child-item' === currentBlock?.name ) {
		// Get the accordion parent to check if this is the last item.
		const accordionParentId = getBlockParentsByBlockName( clientId, 'spectra/accordion', true )[0];
		const accordionParentBlock = getBlock( accordionParentId );
		const accordionItems = accordionParentBlock?.innerBlocks.filter( block => block.name === 'spectra/accordion-child-item' ) || [];

		// Don't remove if this is the last accordion item.
		if ( accordionItems.length <= 1 ) {
			return;
		}

		// Get the remove block function.
		const { removeBlock } = dispatch( blockEditorStore );
		// Remove the current accordion item block.
		removeBlock( clientId );
		return;
	}

	// Get the accordion item parent that contains this block.
	const accordionItemParentId = getBlockParentsByBlockName( clientId, 'spectra/accordion-child-item', true )[0];
	
	// If we can't find an accordion item parent, abandon ship.
	if ( ! accordionItemParentId ) {
		return;
	}

	// Get the accordion parent to check if this is the last item.
	const accordionParentId = getBlockParentsByBlockName( accordionItemParentId, 'spectra/accordion', true )[0];
	const accordionParentBlock = getBlock( accordionParentId );
	const accordionItems = accordionParentBlock?.innerBlocks.filter( block => block.name === 'spectra/accordion-child-item' ) || [];

	// Don't remove if this is the last accordion item.
	if ( accordionItems.length <= 1 ) {
		return;
	}
	// Get the remove block function.
	const { removeBlock } = dispatch( blockEditorStore );
	// Remove the accordion item parent block.
	removeBlock( accordionItemParentId );
};

/**
 * Element Sub-settings: The Block Controls for the accordion item blocks to insert and remove accordion items.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block controls.
 */
export const AccordionItemBlockControls = ( props ) => {
	const {
		clientId,
	} = props;

	const { accordionItemsCount, currentAccordionItemId } = useSelect(
		( selectData ) => {
			const {
				getBlock,
				getBlockParentsByBlockName,
			} = selectData( blockEditorStore );

			// More efficient parent lookup
			const rootId = getBlockParentsByBlockName( clientId, 'spectra/accordion', true )[0] || clientId;
			const rootBlock = getBlock( rootId );

			// Use a more efficient filter with early return
			if ( ! rootBlock?.innerBlocks ) {
				return { accordionItemsCount: 0 };
			}

			const accordionItems = rootBlock.innerBlocks.filter( ( block ) => block.name === 'spectra/accordion-child-item' );

			const count = accordionItems.length;

			// Find the current accordion item ID
			let currentItemId = null;
			const currentBlock = getBlock( clientId );
			if ( 'spectra/accordion-child-item' === currentBlock?.name ) {
				currentItemId = clientId;
			} else {
				// Find the parent accordion item
				const accordionItemParent = getBlockParentsByBlockName( clientId, 'spectra/accordion-child-item', true )[0];
				currentItemId = accordionItemParent || null;
			}

			return {
				accordionItemsCount: count,
				currentAccordionItemId: currentItemId,
			};
		},
		[ clientId ]
	);

	const isDisabled = accordionItemsCount <= 1;
	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon={ helperIcons.accordion.addAccordion }
					label={ __( 'Replicate this accordion item', 'spectra-blocks' ) }
					onClick={ () => { insertNewAccordionItem( clientId, currentAccordionItemId ) } }
				/>
				<ToolbarButton
					icon={ helperIcons.accordion.removeAccordion( !isDisabled ) }
					label={ __( 'Remove this accordion item', 'spectra-blocks' ) }
					onClick={ () => { removeCurrentAccordionItem( clientId ) } }
					disabled={ isDisabled }
				/>
			</ToolbarGroup>
		</BlockControls>
	);
};

// Root-level toolbar for the accordion block
export const AccordionRootToolbar = ( { clientId } ) => {
	const { innerBlocks = [], accordionItems = [] } = useSelect(
		( selectData ) => {
			const block = selectData( 'core/block-editor' ).getBlock( clientId );
			const blocks = block?.innerBlocks || [];
			const items = blocks.filter( item => item.name === 'spectra/accordion-child-item' );
			return { 
				innerBlocks: blocks,
				accordionItems: items,
			};
		},
		[clientId]
	);

	const { insertBlock, removeBlock } = useDispatch( 'core/block-editor' );
	const isDisabled = accordionItems.length <= 1;

	const addAccordionItem = () => {
		if ( accordionItems.length > 0 ) {
			// Duplicate the last accordion item
			const lastItem = accordionItems[ accordionItems.length - 1 ];
			const duplicatedItem = duplicateAndCleanAccordionItem( lastItem );
			insertBlock( duplicatedItem, innerBlocks.length, clientId );
		} else {
			// Create a new accordion item if none exist
			const newAccordionItem = createBlock( 'spectra/accordion-child-item' );
			insertBlock( newAccordionItem, innerBlocks.length, clientId );
		}
	};

	const removeAccordionItem = () => {
		if ( !isDisabled && accordionItems.length > 0 ) {
			const lastItemId = accordionItems[ accordionItems.length - 1 ].clientId;
			removeBlock( lastItemId );
		}
	};

	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon={helperIcons.accordion.addAccordion}
					label={__( 'Add Accordion Item', 'spectra-blocks' )}
					onClick={addAccordionItem}
				/>
				<ToolbarButton
					icon={helperIcons.accordion.removeAccordion( !isDisabled )}
					label={__( 'Remove Last Accordion Item', 'spectra-blocks' )}
					onClick={removeAccordionItem}
					disabled={isDisabled}
				/>
			</ToolbarGroup>
		</BlockControls>
	);
};
