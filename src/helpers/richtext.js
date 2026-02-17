/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useRefEffect } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import { ENTER, BACKSPACE, DELETE } from '@wordpress/keycodes';

// Function to add another instance of the current block when enter is pressed - optimized for performance
export const useOnEnter = ( { clientId } ) => {
	const { insertBlock } = useDispatch( blockEditorStore );

	// Memoize store selectors to prevent unnecessary re-subscriptions
	const blockData = useSelect( ( select ) => {
		const {
			getBlockRootClientId,
			getBlockIndex,
			getBlockName,
			canInsertBlockType,
		} = select( blockEditorStore );

		// Cache block metadata that doesn't change often
		const wrapperClientId = getBlockRootClientId( clientId );
		const blockName = getBlockName( clientId );
		const canInsert = canInsertBlockType( blockName, wrapperClientId );

		return {
			wrapperClientId,
			blockName,
			canInsert,
			getBlockIndex: () => getBlockIndex( clientId ),
		};
	}, [ clientId ] );

	return useRefEffect( ( element ) => {
		const onKeyDown = ( event ) => {
			// If the pressed key is not ENTER or the Shift key is held, return early.
			if ( event.keyCode !== ENTER || event.shiftKey ) {
				return;
			}

			// Early return if we can't insert blocks
			if ( ! blockData.canInsert ) {
				return;
			}

			// Get current selection and text from the DOM element directly for better performance
			// eslint-disable-next-line @wordpress/no-global-get-selection -- Required for RichText component interaction
			const selection = window.getSelection();
			if ( ! selection.rangeCount ) {
				return;
			}

			// Check if the current string starts with a slash.
			const textContent = element.textContent || '';
			if ( textContent.startsWith( '/' ) ) {
				return;
			}

			// Get range after all early returns for optimal performance
			const range = selection.getRangeAt( 0 );

			// Check if cursor is at the end of the text using DOM selection
			const isAtEnd = range.collapsed && range.endOffset === textContent.length;

			if ( ! isAtEnd ) {
				return;
			}

			// Prevent the default behavior of the ENTER key.
			event.preventDefault();

			// Get the position only when needed (lazy evaluation)
			const position = blockData.getBlockIndex() + 1;

			// Insert the new block
			insertBlock( createBlock( blockData.blockName ), position, blockData.wrapperClientId );
		};

		// Add the keydown event listener with passive option for better performance
		element.addEventListener( 'keydown', onKeyDown, { passive: false } );

		return () => {
			// Remove the keydown event listener.
			element.removeEventListener( 'keydown', onKeyDown );
		};
	}, [ blockData, insertBlock ] ); // Include dependencies for proper memoization
};

// Function to delete the current block when the RichText is empty and a delete key is pressed - optimized
export const useOnDelete = ( { clientId } ) => {
	const { removeBlock } = useDispatch( blockEditorStore );

	return useRefEffect( ( element ) => {
		const onKeyDown = ( event ) => {
			// If the pressed key is not DELETE or BACKSPACE, return early.
			if ( event.keyCode !== DELETE && event.keyCode !== BACKSPACE ) {
				return;
			}

			// Check if the text is empty using DOM directly for better performance
			const textContent = element.textContent || '';

			// Check if the text is empty. If it isn't, return.
			if ( textContent.trim() ) {
				return;
			}

			// Prevent default behavior to handle deletion ourselves
			event.preventDefault();

			// Call your block removal handler
			removeBlock( clientId );

			// Stop event propagation to prevent other handlers.
			event.stopImmediatePropagation();
		};

		// Add the keydown event listener with passive option for better performance
		element.addEventListener( 'keydown', onKeyDown, { passive: false } );

		return () => {
			// Remove the keydown event listener.
			element.removeEventListener( 'keydown', onKeyDown );
		};
	}, [ removeBlock ] ); // Include removeBlock dependency for proper memoization
};
