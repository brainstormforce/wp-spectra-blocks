/**
 * External dependencies.
 */
import { pasteHandler } from '@wordpress/blocks';
import { useEffect, useRef } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';

/**
 * Custom paste handler for content block to handle paste operations.
 * like WordPress core paragraph block.
 *
 * @param {Object} props Block props.
 * @return {Object} Ref object for the paste handler.
 */
export function usePasteHandler( props ) {
    const { clientId, attributes, setAttributes, onReplace } = props;
    const { text } = attributes;
    const isEmpty = ! text || text.length === 0;
    
    // Create a ref to track if we've handled a paste event.
    const handledPaste = useRef( false );
    
    // Get the notices dispatch function.
    const { createErrorNotice } = useDispatch( noticesStore );
    
    useEffect( () => {
        // Only add paste handler if the block is empty.
        if ( ! isEmpty ) {
            return;
        }
        
        // Get the DOM node for this block.
        const element = document.querySelector( `[data-block="${ clientId }"]` );
        if ( ! element ) {
            return;
        }
        
        // Custom paste handler function.
        const handlePaste = ( event ) => {
            // Prevent handling the same paste event multiple times.
            if ( handledPaste.current ) {
                return;
            }
            
            // Get clipboard data.
            const clipboardData = event.clipboardData || window.clipboardData;
            if ( ! clipboardData ) {
                return;
            }
            
            // Get HTML content from clipboard.
            const html = clipboardData.getData( 'text/html' );
            const plainText = clipboardData.getData( 'text/plain' );
            
            if ( ! html && ! plainText ) {
                return;
            }
            
            // Process the pasted content using WordPress paste handler.
            const pastedBlocks = pasteHandler( {
                HTML: html,
                plainText,
                mode: 'BLOCKS',
            } );
            
            // Only proceed if we have blocks to process.
            if ( ! pastedBlocks || pastedBlocks.length === 0 ) {
                // Fallback: if no blocks were created, let the default paste behavior handle it.
                return;
            }
            
            // Prevent default paste behavior only after we confirm we have blocks to work with.
            event.preventDefault();
            event.stopPropagation();
            
            // Handle different paste scenarios.
            if ( pastedBlocks.length === 1 && pastedBlocks[ 0 ].name === 'spectra/content' ) {
                // Scenario 1: Pasting a single content block - update current block with all attributes.
                const pastedBlock = pastedBlocks[ 0 ];
                const pastedAttributes = pastedBlock.attributes || {};
                
                // Attributes that should not be copied over (internal/system attributes).
                const excludedAttributes = new Set( [
                    'isPreview',
                    'isRootBlock',
                    'clientId',
                ] );
                
                // Create a function to safely copy attributes, filtering out undefined values and excluded attributes.
                const copyValidAttributes = ( source, target, excluded ) => {
                    return Object.fromEntries(
                        Object.entries( source )
                            .filter( ( [ key, value ] ) => {
                                // Exclude internal attributes.
                                if ( excluded.has( key ) ) {
                                    return false;
                                }
                                // Only include defined values (but allow false, 0, empty string).
                                return value !== undefined && value !== null;
                            } )
                    );
                };
                
                // Copy all valid attributes from pasted block.
                const validPastedAttributes = copyValidAttributes( pastedAttributes, attributes, excludedAttributes );
                
                // Always ensure we have core content attributes with fallbacks.
                const attributesToUpdate = {
                    // Set fallbacks for essential attributes.
                    text: pastedAttributes.text || '',
                    tagName: pastedAttributes.tagName || attributes.tagName || 'p',
                    // Spread all other valid attributes.
                    ...validPastedAttributes,
                };
                
                setAttributes( attributesToUpdate );
            } else {
                // Scenario 2: Pasting different block types OR multiple blocks.
                // Replace the current empty content block with the pasted blocks.
                // Examples: Button, Image, Heading, List, Gallery, Embed, Custom blocks, etc.
                try {
                    onReplace( pastedBlocks );
                } catch ( error ) {
                    // Show user-friendly error notice.
                    createErrorNotice(
                        __( 'Failed to paste content. Please try again.', 'spectra' ),
                        {
                            type: 'snackbar',
                            isDismissible: true,
                        }
                    );
                }
            }
            
            // Mark that we've handled this paste event
            handledPaste.current = true;
            
            // Reset the flag after a short delay
            setTimeout( () => {
                handledPaste.current = false;
            }, 100 );
            
            return true;
        };
        
        // Add paste event listener
        element.addEventListener( 'paste', handlePaste );
        
        // Clean up
        return () => {
            element.removeEventListener( 'paste', handlePaste );
        };
    }, [ clientId, isEmpty, setAttributes, onReplace, attributes.tagName ] );
    
    return handledPaste;
}
