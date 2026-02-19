/**
 * External dependencies.
 */
import { createBlock } from '@wordpress/blocks';
import {
	BlockControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { select, dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { helperIcons } from '@spectra-helpers/block-icons';

/**
 * Function to get the current Tabpanel's index relative to other Tabpanel blocks.
 *
 * @param {string} clientId The current block's ID.
 * @since x.x.x
 * @return {number} The tabpanel index, or -1 if something went wrong.
 */
export const getTabpanelIndex = ( clientId ) => {
	const { getBlockParentsByBlockName, getBlock } = select( blockEditorStore );

	// Get the tab parent block, then get all the tabpanels.
	const tabParentId = getBlockParentsByBlockName(
		clientId,
		'spectra/tabs',
		true
	)[0];
	const tabpanelBlocks =
		getBlock( tabParentId )?.innerBlocks.filter(
			( block ) => block.name === 'spectra/tabs-child-tabpanel'
		) || [];
	let tabpanelIndex;

	// Find the current tabpanel block's index.
	const foundTabpanel = tabpanelBlocks.some( ( block, index ) => {
		const isTheCurrentBlock = clientId === block.clientId;

		// If this is the tab current block, update it's tab index relative to the current tabpanel blocks.
		if ( isTheCurrentBlock ) {
			tabpanelIndex = index;
		}

		// If the current block was not found, you'll be back here.
		return isTheCurrentBlock;
	} );

	// If for some reason the tab was not added, abandon ship. Else return the tab index.
	return !foundTabpanel ? -1 : tabpanelIndex;
};

/**
 * Function to insert a new tab.
 *
 * @param {string} clientId The current block's ID.
 * @since x.x.x
 */
export const insertNewTab = ( clientId ) => {
	const { getBlock, getBlockParentsByBlockName } = select( blockEditorStore );
	const { insertBlock } = dispatch( blockEditorStore );

	// Get the currentBlock, and keep empty varaibles for the innerBlocks and tabNumber.
	const currentBlock = getBlock( clientId );
	let rootClientId, innerBlocks, tabNumber;

	// If the current block is the tabs block, get the innerblocks.
	if ( 'spectra/tabs' === currentBlock.name ) {
		rootClientId = currentBlock.clientId;
		innerBlocks = currentBlock.innerBlocks;
	} else {
		rootClientId = getBlockParentsByBlockName(
			clientId,
			'spectra/tabs',
			true
		)[0];
		innerBlocks = getBlock( rootClientId ).innerBlocks;
	}

	// Get the tab wrapper.
	const tabAdded = innerBlocks.some( ( block ) => {
		const isTabWrapper = 'spectra/tabs-child-tab-wrapper' === block.name;

		// If this is the tab wrapper, add the tab to it, and get the tab number.
		if ( isTabWrapper ) {
			// First set the number of the new tab.
			tabNumber = block.innerBlocks.length + 1;

			// Then create a new tab block with this number.
			const newTab = createBlock( 'spectra/tabs-child-tab-button', {
				placeholder: `Tab ${tabNumber}`,
			} );

			// And finally, insert it at the end of the tab wrapper, disabling focus.
			insertBlock( newTab, tabNumber, block.clientId, false );
		}

		// If the tabWrapper was not found, you'll be back here.
		return isTabWrapper;
	} );

	// If for some reason the tab was not added, abandon ship.
	if ( !tabAdded ) {
		return;
	}

	// Check if the tabs block has a vertical layout.
	const tabsBlock = getBlock( rootClientId );
	const isVerticalLayout =
		tabsBlock?.attributes?.layout?.orientation === 'horizontal';

	// Create the new tabpanel block with appropriate attributes for vertical layout.
	const newTabpanel = createBlock(
		'spectra/tabs-child-tabpanel',
		isVerticalLayout
			? {
				style: {
					layout: {
						selfStretch: 'fixed',
						flexSize: '100%',
					},
				},
			}
			: {}
	);

	// Insert the new tabpanel block, disabling focus.
	insertBlock( newTabpanel, tabNumber, rootClientId, false );
};

/**
 * Function to remove the current tab.
 *
 * @since x.x.x
 *
 * @param {string} clientId  The current block's ID.
 * @param {string} blockType The current block type.
 */
export const removeCurrentTab = ( clientId, blockType = 'tab' ) => {
	// Get the block editor store functions.
	const { getBlock, getBlockIndex, getBlockParentsByBlockName } =
		select( blockEditorStore );

	// Get the current block.
	const currentBlock = getBlock( clientId );
	if ( !currentBlock ) {
		return;
	}

	// Get the tabs root block.
	const tabsRootId =
		'tabs' === blockType
			? clientId
			: getBlockParentsByBlockName( clientId, 'spectra/tabs', true )[0];
	const tabsRootBlock = getBlock( tabsRootId );
	if ( !tabsRootBlock ) {
		return;
	}

	// Get the remove block function.
	const { removeBlock } = dispatch( blockEditorStore );

	// Handle different block types for removal.
	switch ( blockType ) {
		case 'tab':
			// Remove tab button/trigger and associated tabpanel.
			removeTabAndAssociatedTabpanel(
				clientId,
				currentBlock,
				tabsRootBlock,
				getBlockIndex,
				removeBlock
			);
			break;
		case 'tabpanel':
			// Remove tabpanel and associated tab button.
			removeTabpanelAndAssociatedTabButton(
				clientId,
				currentBlock,
				tabsRootBlock,
				getBlockIndex,
				removeBlock
			);
			break;
		case 'tab-wrapper':
		case 'tabs':
			// Remove last tab button/trigger and associated tabpanel.
			removeLastTabButtonAndAssociatedTabpanel(
				tabsRootBlock,
				removeBlock
			);
			break;
	}
};

/**
 * Remove tab button/trigger and its associated tabpanel.
 *
 * @param {string} clientId The current block's ID.
 * @param {Object} currentBlock The current block object.
 * @param {Object} tabsRootBlock The tabs root block object.
 * @param {Function} getBlockIndex Function to get block index.
 * @param {Function} removeBlock Function to remove block.
 */
const removeTabAndAssociatedTabpanel = (
	clientId,
	currentBlock,
	tabsRootBlock,
	getBlockIndex,
	removeBlock
) => {
	// Verify this is a tab button or tab trigger block.
	if (
		'spectra/tabs-child-tab-button' !== currentBlock.name &&
		'spectra/tabs-child-tab-trigger' !== currentBlock.name
	) {
		return;
	}

	// Find the first tab wrapper block in the tabs root block.
	const tabWrapperBlock = tabsRootBlock.innerBlocks.find(
		( block ) => 'spectra/tabs-child-tab-wrapper' === block.name
	);
	if ( !tabWrapperBlock ) {
		return;
	}

	// Get all tab button/trigger blocks from the tab wrapper and find the current one's index.
	const tabBlocks = tabWrapperBlock.innerBlocks.filter(
		( block ) =>
			'spectra/tabs-child-tab-button' === block.name ||
			'spectra/tabs-child-tab-trigger' === block.name
	);
	const tabIndex = tabBlocks.findIndex(
		( block ) => block.clientId === clientId
	);

	if ( tabIndex === -1 ) {
		return;
	}

	// Get all tabpanel blocks from the tabs root block.
	const tabpanelBlocks = tabsRootBlock.innerBlocks.filter(
		( block ) => 'spectra/tabs-child-tabpanel' === block.name
	);

	// Remove the current tab button/trigger.
	removeBlock( clientId );

	// Remove the associated tabpanel if it exists.
	if ( tabpanelBlocks[tabIndex] ) {
		removeBlock( tabpanelBlocks[tabIndex].clientId, false );
	}
};

/**
 * Remove tabpanel and its associated tab button/trigger.
 *
 * @param {string} clientId The current block's ID.
 * @param {Object} currentBlock The current block object.
 * @param {Object} tabsRootBlock The tabs root block object.
 * @param {Function} getBlockIndex Function to get block index.
 * @param {Function} removeBlock Function to remove block.
 */
const removeTabpanelAndAssociatedTabButton = (
	clientId,
	currentBlock,
	tabsRootBlock,
	getBlockIndex,
	removeBlock
) => {
	// Verify this is a tabpanel block.
	if ( 'spectra/tabs-child-tabpanel' !== currentBlock.name ) {
		return;
	}

	// Get all tabpanel blocks from the tabs root block and find the current one's index.
	const tabpanelBlocks = tabsRootBlock.innerBlocks.filter(
		( block ) => 'spectra/tabs-child-tabpanel' === block.name
	);
	const tabpanelIndex = tabpanelBlocks.findIndex(
		( block ) => block.clientId === clientId
	);

	if ( tabpanelIndex === -1 ) {
		return;
	}

	// Find the first tab wrapper block in the tabs root block.
	const tabWrapperBlock = tabsRootBlock.innerBlocks.find(
		( block ) => 'spectra/tabs-child-tab-wrapper' === block.name
	);
	if ( !tabWrapperBlock ) {
		return;
	}

	// Get all tab button/trigger blocks from the tab wrapper.
	const tabBlocks = tabWrapperBlock.innerBlocks.filter(
		( block ) =>
			'spectra/tabs-child-tab-button' === block.name ||
			'spectra/tabs-child-tab-trigger' === block.name
	);

	// Remove the current tabpanel.
	removeBlock( clientId );

	// Remove the associated tab button/trigger if it exists.
	if ( tabBlocks[tabpanelIndex] ) {
		removeBlock( tabBlocks[tabpanelIndex].clientId, false );
	}
};

/**
 * Remove the last tab button/trigger and its associated tabpanel.
 *
 * @param {Object} tabsRootBlock The tabs root block object.
 * @param {Function} removeBlock Function to remove block.
 */
const removeLastTabButtonAndAssociatedTabpanel = (
	tabsRootBlock,
	removeBlock
) => {
	// Find the first tab wrapper block in the tabs root block.
	const tabWrapperBlock = tabsRootBlock.innerBlocks.find(
		( block ) => 'spectra/tabs-child-tab-wrapper' === block.name
	);
	if ( !tabWrapperBlock ) {
		return;
	}

	// Get all tab button/trigger blocks from the tab wrapper.
	const tabBlocks = tabWrapperBlock.innerBlocks.filter(
		( block ) =>
			'spectra/tabs-child-tab-button' === block.name ||
			'spectra/tabs-child-tab-trigger' === block.name
	);
	if ( tabBlocks.length === 0 ) {
		return;
	}

	// Get all tabpanel blocks from the tabs root block.
	const tabpanelBlocks = tabsRootBlock.innerBlocks.filter(
		( block ) => 'spectra/tabs-child-tabpanel' === block.name
	);

	// Get the last tab index.
	const lastTabIndex = tabBlocks.length - 1;

	// Remove the last tab button/trigger.
	removeBlock( tabBlocks[lastTabIndex].clientId );

	// Remove the associated tabpanel if it exists.
	if ( tabpanelBlocks[lastTabIndex] ) {
		removeBlock( tabpanelBlocks[lastTabIndex].clientId, false );
	}
};

/**
 * Element Sub-settings: The Block Controls for the tab blocks to insert and remove tabs.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block controls.
 */
export const TabBlockControls = ( props ) => {
	const { clientId, removalType } = props;

	const { tabsCount, currentBlockType } = useSelect(
		( selectData ) => {
			const { getBlock, getBlockParentsByBlockName } =
				selectData( blockEditorStore );

			const rootId =
				getBlockParentsByBlockName(
					clientId,
					'spectra/tabs',
					true
				)[0] || clientId;
			const rootBlock = getBlock( rootId );
			const currentBlock = getBlock( clientId );

			const tabWrapper = rootBlock?.innerBlocks.find(
				( block ) => block.name === 'spectra/tabs-child-tab-wrapper'
			);
			const count = tabWrapper?.innerBlocks.length || 0;

			// Determine the current block type for removal logic using a mapping object.
			const blockTypeMap = {
				'spectra/tabs-child-tab-button': 'tab',
				'spectra/tabs-child-tab-trigger': 'tab',
				'spectra/tabs-child-tabpanel': 'tabpanel',
				'spectra/tabs-child-tab-wrapper': 'tab-wrapper',
				'spectra/tabs': 'tabs',
			};

			let blockType = removalType;
			if ( !blockType && currentBlock ) {
				blockType = blockTypeMap[currentBlock.name] || null;
			}

			return {
				tabsCount: count,
				currentBlockType: blockType,
			};
		},
		[clientId, removalType]
	);

	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon={helperIcons.tabs.addTab()}
					label={__(
						'Add a new tab and tabpanel',
						'spectra-blocks'
					)}
					onClick={() => {
						insertNewTab( clientId );
					}}
				/>
				{currentBlockType && (
					<ToolbarButton
						icon={helperIcons.tabs.removeTab( tabsCount > 1 )}
						label={
							['tab', 'tabpanel'].includes( currentBlockType )
								? __(
									'Remove this tab and tabpanel',
									'spectra-blocks'
								)
								: __(
									'Remove the last tab and tabpanel',
									'spectra-blocks'
								)
						}
						onClick={() => {
							removeCurrentTab( clientId, currentBlockType );
						}}
						disabled={tabsCount <= 1}
					/>
				)}
			</ToolbarGroup>
		</BlockControls>
	);
};
