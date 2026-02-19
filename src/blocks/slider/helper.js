/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { BlockControls, store as blockEditorStore } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { useDispatch, useSelect, dispatch, select } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { memo, useCallback, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { helperIcons } from '../../helpers/block-icons';

/**
 * Function to insert a new slide.
 *
 * @param {string} clientId The current block's ID.
 * @since x.x.x
 */
export const insertNewSlide = ( clientId ) => {
	const {
		getBlock,
		getBlockParentsByBlockName,
	} = select( blockEditorStore );
	const { insertBlock, selectBlock } = dispatch( blockEditorStore );
	
	// Get the currentBlock, and keep empty variables for the innerBlocks.
	const currentBlock = getBlock( clientId );
	let rootClientId, innerBlocks;

	// If the current block is the slider block, get the innerblocks.
	if ( 'spectra/slider' === currentBlock.name ) {
		rootClientId = currentBlock.clientId;
		innerBlocks = currentBlock.innerBlocks;
	} else {
		rootClientId = getBlockParentsByBlockName( clientId, 'spectra/slider', true )[0];
		innerBlocks = getBlock( rootClientId ).innerBlocks;
	}

	// Set the number of the new slide.
	const slideNumber = innerBlocks.length + 1;

	// Create a new slide block.
	const newSlide = createBlock( 'spectra/slider-child' );

	// Set a flag on the block to indicate it's manually added in the editor so that it can be handled by the subscribe function.
	newSlide.isManuallyAdded = true;

	// Insert it at the end of the slider, enabling focus on the new slide.
	insertBlock( newSlide, slideNumber, rootClientId, true );

	// Performance optimization: Reduced timeout for faster response
	setTimeout( () => {
		// Double-check that the block was actually inserted before selecting it.
		const insertedBlock = select( blockEditorStore ).getBlock( newSlide.clientId );
		if ( insertedBlock ) {
			selectBlock( newSlide.clientId );
		}
	}, 32 ); // Reduced from 50ms to 32ms for better responsiveness
};

/**
 * Function to remove the current slide.
 *
 * @since x.x.x
 *
 * @param {string} clientId The current block's ID.
 */
export const removeCurrentSlide = ( clientId ) => {
	// Get the block editor store functions.
	const {
		getBlock,
		getBlockParentsByBlockName,
	} = select( blockEditorStore );

	// If the current block is already a slider child, remove it directly.
	const currentBlock = getBlock( clientId );
	if ( 'spectra/slider-child' === currentBlock?.name ) {
		// Get the slider parent to check if this is the last slide.
		const sliderParentId = getBlockParentsByBlockName( clientId, 'spectra/slider', true )[0];
		const sliderParentBlock = getBlock( sliderParentId );
		const slides = sliderParentBlock?.innerBlocks.filter( block => block.name === 'spectra/slider-child' ) || [];

		// Don't remove if this is the last slide.
		if ( slides.length <= 1 ) {
			return;
		}

		// Get the remove block function.
		const { removeBlock } = dispatch( blockEditorStore );
		// Remove the current slide block.
		removeBlock( clientId );
		return;
	}

	// Get the slider child parent that contains this block.
	const sliderChildParentId = getBlockParentsByBlockName( clientId, 'spectra/slider-child', true )[0];
	
	// If we can't find a slider child parent, abandon ship.
	if ( ! sliderChildParentId ) {
		return;
	}

	// Get the slider parent to check if this is the last slide.
	const sliderParentId = getBlockParentsByBlockName( sliderChildParentId, 'spectra/slider', true )[0];
	const sliderParentBlock = getBlock( sliderParentId );
	const slides = sliderParentBlock?.innerBlocks.filter( block => block.name === 'spectra/slider-child' ) || [];

	// Don't remove if this is the last slide.
	if ( slides.length <= 1 ) {
		return;
	}
	// Get the remove block function.
	const { removeBlock } = dispatch( blockEditorStore );
	// Remove the slider child parent block.
	removeBlock( sliderChildParentId );
};

/**
 * Function to remove a slide from the slider parent.
 *
 * @since x.x.x
 *
 * @param {string} clientId The slider block's ID.
 */
export const removeSlideFromParent = ( clientId ) => {
	const {
		getBlock,
		getSelectedBlock,
	} = select( blockEditorStore );

	const sliderBlock = getBlock( clientId );
	const slides = sliderBlock?.innerBlocks.filter( block => block.name === 'spectra/slider-child' ) || [];

	// Don't remove if this is the last slide.
	if ( slides.length <= 1 ) {
		return;
	}

	const { removeBlock } = dispatch( blockEditorStore );
	const selectedBlock = getSelectedBlock();

	// If a slide is selected, remove it
	if ( selectedBlock && selectedBlock.name === 'spectra/slider-child' ) {
		// Check if the selected slide belongs to this slider
		const slideParent = select( blockEditorStore ).getBlockParentsByBlockName( selectedBlock.clientId, 'spectra/slider', true )[0];
		if ( slideParent === clientId ) {
			removeBlock( selectedBlock.clientId );
			return;
		}
	}

	// If no slide is selected or selected slide doesn't belong to this slider, remove the last slide
	if ( slides.length > 0 ) {
		const lastSlide = slides[ slides.length - 1 ];
		removeBlock( lastSlide.clientId );
	}
};

/**
 * Element Sub-settings: The Block Controls for the slider child blocks to insert and remove slides.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block controls.
 */
export const SliderChildBlockControls = memo( ( props ) => {
	const {
		clientId,
	} = props;

	// Performance optimization: Memoize the useSelect dependencies
	const { slidesCount } = useSelect(
		( selectData ) => {
			const {
				getBlock,
				getBlockParentsByBlockName,
			} = selectData( blockEditorStore );

			const rootId = getBlockParentsByBlockName( clientId, 'spectra/slider', true )[0] || clientId;
			const rootBlock = getBlock( rootId );

			const slides = rootBlock?.innerBlocks.filter( ( block ) => block.name === 'spectra/slider-child' ) || [];
			const count = slides.length;

			return { slidesCount: count };
		},
		[ clientId ]
	);

	// Performance optimization: Memoize callbacks
	const handleInsertSlide = useCallback( () => {
		insertNewSlide( clientId );
	}, [ clientId ] );

	const handleRemoveSlide = useCallback( () => {
		removeCurrentSlide( clientId );
	}, [ clientId ] );

	// Performance optimization: Memoize disabled state
	const isDisabled = useMemo( () => slidesCount <= 1, [ slidesCount ] );

	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon={ helperIcons.slider.add }
					label={ __( 'Add a new slide', 'spectra-blocks' ) }
					onClick={ handleInsertSlide }
				/>
				<ToolbarButton
					icon={ helperIcons.slider.remove( !isDisabled ) }
					label={ __( 'Remove this slide', 'spectra-blocks' ) }
					onClick={ handleRemoveSlide }
					disabled={ isDisabled }
				/>
			</ToolbarGroup>
		</BlockControls>
	);
} );

/**
 * Slider Root Toolbar Component for the parent slider block
 *
 * @param {Object} props Component props
 * @return {Element} Toolbar component
 */
export const SliderRootToolbar = memo( ( props ) => {
	const { clientId } = props;

	// Performance optimization: Optimize useSelect with minimal re-renders
	const { innerBlocks } = useSelect(
		( selectData ) => {
			const { getBlock } = selectData( 'core/block-editor' );
			const block = getBlock( clientId );
			return {
				innerBlocks: block?.innerBlocks || [],
			};
		},
		[ clientId ]
	);

	// Setup dispatch functions
	const { insertBlock } = useDispatch( 'core/block-editor' );

	// Performance optimization: Memoize callbacks
	const addSlide = useCallback( () => {
		const newSlide = createBlock( 'spectra/slider-child' );
		// Set a flag on the block to indicate it's manually added in the editor
		newSlide.isManuallyAdded = true;
		insertBlock( newSlide, innerBlocks.length, clientId );
	}, [ insertBlock, innerBlocks.length, clientId ] );

	const removeSlide = useCallback( () => {
		removeSlideFromParent( clientId );
	}, [ clientId ] );

	// Performance optimization: Memoize disabled state
	const isDisabled = useMemo( () => innerBlocks.length <= 1, [ innerBlocks.length ] );

	return (
		<BlockControls>
			<ToolbarGroup>
				<ToolbarButton
					icon={ helperIcons.slider.add }
					label={ __(
						'Add Slide',
						'spectra-blocks'
					) }
					onClick={ addSlide }
				/>
				<ToolbarButton
					icon={ helperIcons.slider.remove( !isDisabled ) }
					label={ __(
						'Remove Slide',
						'spectra-blocks'
					) }
					onClick={ removeSlide }
					disabled={ isDisabled }
				/>
			</ToolbarGroup>
		</BlockControls>
	);
} ); 