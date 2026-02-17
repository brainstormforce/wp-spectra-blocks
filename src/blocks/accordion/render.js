/**
 * External dependencies.
 */
import {
	useBlockProps,
	InnerBlocks,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { memo, useMemo, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

// Setting the file based constants.
const accordionCount = 3;
const allowedBlocks = [
	'spectra/accordion-child-item',
];

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
		clientId,
		setAttributes,
	} = props;

	// Get currently selected block client ID - optimized version
	const { activeAccordionId } = useSelect( ( select ) => {
		const {
			getSelectedBlockClientId,
			getBlock,
			getBlockParents,
		} = select( blockEditorStore );
		
		// Get the selected block's ID.
		const selectedBlockId = getSelectedBlockClientId();
		
		if ( ! selectedBlockId ) {
			return { activeAccordionId: 0 };
		}

		// If the current selected block is the accordion itself, make the first accordion item active.
		if ( clientId === selectedBlockId ) {
			const accordionBlock = getBlock( clientId );
			const firstAccordionItem = accordionBlock?.innerBlocks?.find( 
				block => block.name === 'spectra/accordion-child-item' 
			);
			return {
				activeAccordionId: firstAccordionItem?.clientId || 0,
			};
		}
		
		// Use getBlockParents for more efficient parent traversal
		const parentIds = getBlockParents( selectedBlockId );
		
		// Check if the selected block or any of its parents belong to this accordion
		if ( parentIds.includes( clientId ) ) {
			// Find the accordion item that contains the selected block
			const accordionBlock = getBlock( clientId );
			const accordionItems = accordionBlock?.innerBlocks?.filter( 
				block => block.name === 'spectra/accordion-child-item' 
			) || [];
			
			// Find which accordion item contains the selected block
			for ( const item of accordionItems ) {
				const itemParents = getBlockParents( selectedBlockId );
				if ( item.clientId === selectedBlockId || itemParents.includes( item.clientId ) ) {
					return { activeAccordionId: item.clientId };
				}
			}
		}

		return { activeAccordionId: 0 };
	}, [ clientId ] );

	// Effect to set the active accordion whenever needed - optimized with early return
	useEffect( () => {
		// Only update if the value actually changed to prevent unnecessary re-renders
		if ( activeAccordionId !== props.attributes.activeAccordion ) {
			setAttributes( { activeAccordion: activeAccordionId } );
		}
	}, [ activeAccordionId, props.attributes.activeAccordion ] );

	// Function to get the accordion template - memoized to prevent recalculation
	const getAccordionChildTemplate = useMemo( () => {
		const childrenAccordions = [];

		for ( let i = 0; i < accordionCount; i++ ) {
			childrenAccordions.push( [ 'spectra/accordion-child-item' ] );
		}

		return childrenAccordions;
	}, [] ); // Empty dependency array since accordionCount is constant

	// Use the block props, with the added CSS classes.
	const blockProps = useBlockProps();

	return (
		<div
			{ ...blockProps }
			role="tablist"
		>
			<InnerBlocks
				template={ getAccordionChildTemplate }
				allowedBlocks={ allowedBlocks }
			/>
		</div>
	);
};

export default memo( Render );
