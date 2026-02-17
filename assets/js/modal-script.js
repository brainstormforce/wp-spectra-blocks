/**
 * Spectra Modal Script
 *
 * Wrapped in an IIFE with a load guard to prevent errors when the script
 * is inadvertently loaded multiple times (e.g., slider and modal blocks on same page).
 */
( function() {
	'use strict';

	// Prevent double execution if script is loaded multiple times.
	if ( window.spectraModalScriptLoaded ) {
		return;
	}
	window.spectraModalScriptLoaded = true;

	// Constants
	const CLOSE_BUTTON_RENDER_DELAY = 50; // Delay for close button to render

	// Store modal-specific data
	const modalHandlers = new Map();

/**
 * Get the correct document element (handles iframe-based themes like Twenty Twenty-Five)
 * This is critical for theme compatibility
 */
function getDocumentElement() {
	// For frontend, always use main document (Twenty Twenty-Five doesn't use iframe on frontend)
	if ( !document.body.classList.contains( 'wp-admin' ) && !document.body.classList.contains( 'block-editor-page' ) ) {
		return document;
	}
	
	// For editor, check for iframe
	let document_element = document;
	const getEditorIframe = document.querySelectorAll( 'iframe[name="editor-canvas"]' );
	
	if ( getEditorIframe?.length ) {
		const iframeDocument =
			getEditorIframe?.[ 0 ]?.contentWindow?.document || getEditorIframe?.[ 0 ]?.contentDocument;
		if ( iframeDocument ) {
			document_element = iframeDocument;
		}
	}
	return document_element;
}

/**
 * Attach global handlers for modal accessibility and interaction
 * Need to attach to BOTH main document AND iframe for full coverage
 */
function attachGlobalModalHandlers() {
	// Always attach to main document (for trigger elements)
	attachKeyboardHandlersToDocument( document );
	
	// Also attach to iframe document if it exists (for Twenty Twenty-Five theme)
	const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
	if ( iframe ) {
		const iframeDoc = iframe.contentWindow?.document || iframe.contentDocument;
		if ( iframeDoc ) {
			attachKeyboardHandlersToDocument( iframeDoc );
		} else {
			// Iframe not loaded yet, wait for it
			iframe.addEventListener( 'load', () => {
				const iframeDocLoaded = iframe.contentWindow?.document || iframe.contentDocument;
				if ( iframeDocLoaded ) {
					attachKeyboardHandlersToDocument( iframeDocLoaded );
				}
			} );
		}
	} else {
		// Try with delay to catch dynamically created iframes
		setTimeout( () => {
			const delayedIframe = document.querySelector( 'iframe[name="editor-canvas"]' );
			if ( delayedIframe ) {
				const iframeDoc = delayedIframe.contentWindow?.document || delayedIframe.contentDocument;
				if ( iframeDoc && !iframeDoc._spectraHandlersAttached ) {
					attachKeyboardHandlersToDocument( iframeDoc );
				}
			}
		}, 1000 );
	}
}

/**
 * Attach keyboard handlers to a specific document
 *
 * @param {Document} doc - The document to attach handlers to
 */
function attachKeyboardHandlersToDocument( doc ) {
	// Prevent duplicate attachment
	if ( doc._spectraHandlersAttached ) {
		return;
	}
	doc._spectraHandlersAttached = true;
	
	
	// Global keyboard handler for modal triggers and close buttons
	doc.addEventListener( 'keydown', ( e ) => {
		// 1. Handle modal trigger elements (Enter/Space to open)
		if ( ( e.target.classList.contains( 'modal-trigger-element' ) || 
			   e.target.getAttribute( 'data-wp-on--click' ) === 'spectra/modal::actions.toggle' ||
			   e.target.getAttribute( 'data-wp-on--click' ) === 'spectra/modal::actions.open' ) ) {
			
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				
				// Just trigger a click event - let the existing click handler do the work
				e.target.click();
			}
		}
		
		// 2. Handle close button elements (Enter/Space to close)
		else if ( e.target.classList.contains( 'spectra-modal-popup-close' ) || 
				  e.target.getAttribute( 'data-wp-on--click' ) === 'spectra/modal::actions.close' ) {
			
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				
				// Just trigger a click event
				e.target.click();
			}
		}
	} );
	
	// ESC key handler - attach to both documents to catch ESC from anywhere
	doc.addEventListener( 'keydown', ( e ) => {
		if ( e.key === 'Escape' ) {
			// Look for active modal - the active class is on the popup element, not wrapper
			let activeModal = document.querySelector( '.spectra-modal-popup.active' );
			
			// If not found in main document, check iframe
			if ( !activeModal ) {
				const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
				if ( iframe ) {
					const iframeDoc = iframe.contentWindow?.document || iframe.contentDocument;
					if ( iframeDoc ) {
						activeModal = iframeDoc.querySelector( '.spectra-modal-popup.active' );
					}
				}
			}
			
			if ( activeModal && activeModal.id ) {
				// Check if ESC key is enabled for this modal
				const escPress = activeModal.getAttribute( 'data-esc-press' );
				
				// Only close if ESC is explicitly enabled.
				if ( escPress !== 'true' ) {
					return; // Don't close if ESC is not explicitly enabled
				}
				
				e.preventDefault();
				e.stopPropagation();
				
				// Use closeModal function if available, otherwise manual close
				if ( typeof closeModal === 'function' ) {
					closeModal( activeModal.id );
				} else {
					activeModal.classList.remove( 'active' );
				}
			}
		}
	} );
	
	// Note: Overlay click is handled by attachOverlayClickClose function
	// This global handler is kept minimal to avoid conflicts
}

// Attach global modal handlers immediately when script loads
// This ensures all modal interactions work even if modal initialization events don't fire properly
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', attachGlobalModalHandlers );
} else {
	attachGlobalModalHandlers();
}

// Listen for modal initialization events and attach behaviours.
// Listen on both main document and iframe document for full compatibility
function setupModalInitializationListener() {
	const handleInitialization = ( event ) => {
		const {
			blockId,
			overlayClick,
			escPress,
		} = event.detail;

		// Normalize values into args with default fallbacks.
		const args = {
			blockId,
			overlayClick: overlayClick || false,
			escPress: escPress || false,
		};

		// Clean up any existing handlers for this modal
		cleanupModalHandlers( blockId );

		// Attach the necessary modal behavior handlers.
		// Close modal on overlay click.
		attachOverlayClickClose( args );
		// Close modal on Escape key.
		attachEscKeyClose( args );
		// Trap focus inside modal and manage keyboard accessibility
		attachKeyboardHandlers( args );
		// Add keyboard support for close button
		attachCloseButtonKeyboard( args );
	};

	// Listen on main document
	document.addEventListener( 'spectra:modal:initialized', handleInitialization );
	
	// Also listen on iframe document if exists
	const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
	if ( iframe ) {
		const iframeDoc = iframe.contentWindow?.document || iframe.contentDocument;
		if ( iframeDoc ) {
			iframeDoc.addEventListener( 'spectra:modal:initialized', handleInitialization );
		}
	}
}

// Setup listeners when DOM is ready
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', setupModalInitializationListener );
} else {
	setupModalInitializationListener();
}


/**
 * Clean up any existing event handlers for a modal
 *
 * @param {string} blockId - The modal block ID
 */
function cleanupModalHandlers( blockId ) {
	const handlers = modalHandlers.get( blockId );
	if ( handlers ) {
		// Remove all stored event listeners
		handlers.forEach( ( { element, event, handler } ) => {
			element.removeEventListener( event, handler );
		} );
		modalHandlers.delete( blockId );
	}
}

/**
 * Store an event handler for later cleanup
 *
 * @param {string} blockId - The modal block ID
 * @param {Element} element - The DOM element
 * @param {string} event - The event name
 * @param {Function} handler - The event handler function
 */
function storeHandler( blockId, element, event, handler ) {
	if ( !modalHandlers.has( blockId ) ) {
		modalHandlers.set( blockId, [] );
	}
	modalHandlers.get( blockId ).push( { element, event, handler } );
	element.addEventListener( event, handler );
}

/**
 * Attach handler to close modal when clicking outside modal content (on the overlay).
 *
 * @param {Object} args - Contains modal settings like blockId and overlayClick.
 */
function attachOverlayClickClose( args ) {
	const modal = getDocumentElement().getElementById( args.blockId );
	if ( !modal ) {
		return;
	}
	if ( args.overlayClick ) {
		const handler = ( e ) => {
			// The modal structure is: 
			// <div id="blockId" class="active"> <!-- modal wrapper -->
			//   <div class="spectra-modal-popup active"> <!-- popup/overlay -->
			//     <div class="spectra-modal-popup-wrap"> <!-- content wrapper -->
			
			// If click target is the wrapper (modal element itself), close it
			if ( e.currentTarget === modal && e.target === modal ) {
				closeModal( args.blockId );
				return;
			}
			
			// Find the popup element
			const popup = modal.querySelector( '.spectra-modal-popup' );
			if ( !popup || !popup.classList.contains( 'active' ) ) return;
			
			const popupWrap = popup.querySelector( '.spectra-modal-popup-wrap' );
			if ( !popupWrap ) return;
			
			// Click is on overlay if click is on popup but not inside popup-wrap
			if ( e.target === popup ) {
				closeModal( args.blockId );
			}
		};
		
		// Attach to both modal wrapper and popup for complete coverage
		storeHandler( args.blockId, modal, 'click', handler );
		
		const popup = modal.querySelector( '.spectra-modal-popup' );
		if ( popup ) {
			const popupHandler = ( e ) => {
				const popupWrap = popup.querySelector( '.spectra-modal-popup-wrap' );
				if ( popupWrap && e.target === popup ) {
					e.stopPropagation();
					closeModal( args.blockId );
				}
			};
			storeHandler( args.blockId, popup, 'click', popupHandler );
		}
	}
}

/**
 * Attach handler to close modal when Escape key is pressed.
 *
 * @param {Object} args - Contains modal settings including escPress flag.
 */
function attachEscKeyClose( args ) {
	if ( args.escPress ) {
		const handler = ( e ) => {
			if ( e.key === 'Escape' ) {
				const modal = getDocumentElement().getElementById( args.blockId );
				if ( modal && modal.classList.contains( 'active' ) ) {
					closeModal( args.blockId );
				}
			}
		};
		storeHandler( args.blockId, getDocumentElement(), 'keydown', handler );
	}
}

/**
 * Close the modal by hiding it and removing active state.
 *
 * @param {string} blockId - The modal block ID to close.
 */
function closeModal( blockId ) {
	const modal = getDocumentElement().getElementById( blockId );
	if ( ! modal ) return;

	modal.classList.remove( 'active' );
	
	// Also remove active from popup element if it exists
	const popup = modal.querySelector( '.spectra-modal-popup' );
	if ( popup ) {
		popup.classList.remove( 'active' );
		popup.setAttribute( 'aria-hidden', 'true' );
		popup.removeAttribute( 'aria-modal' );
	}
	
	// Restore focus to previously focused element
	const state = modalHandlers.get( blockId )?.state;
	if ( state?.previouslyFocusedElement ) {
		// Use setTimeout to ensure focus is restored after modal is fully closed
		setTimeout( () => {
			if ( state.previouslyFocusedElement && typeof state.previouslyFocusedElement.focus === 'function' ) {
				state.previouslyFocusedElement.focus();
			}
		}, 0 );
	}
	
	// Dispatch close event for any cleanup
	const event = new CustomEvent( 'spectra:modal:closed', {
		detail: { blockId },
		bubbles: true
	} );
	document.dispatchEvent( event );
}

/**
 * Enable keyboard accessibility for modal including focus trap and restoring focus.
 *
 * @param {Object} args - Modal configuration.
 */
function attachKeyboardHandlers( args ) {
	// Store modal-specific state in the handlers map
	if ( !modalHandlers.has( args.blockId ) ) {
		modalHandlers.set( args.blockId, [] );
	}
	
	// Add state properties
	modalHandlers.get( args.blockId ).state = {
		previouslyFocusedElement: null,
		isFirstTab: true
	};

	// Trap tab focus inside the modal
	const keydownHandler = ( e ) => {
		const modal = getDocumentElement().getElementById( args.blockId );
		if ( ! modal || ! modal.classList.contains( 'active' ) ) return;
		
		// Check if the popup element is hidden (not the wrapper)
		const popup = modal.querySelector( '.spectra-modal-popup' );
		if ( popup && popup.getAttribute( 'aria-hidden' ) === 'true' ) return;
		
		const state = modalHandlers.get( args.blockId )?.state;
		if ( ! state ) return;

		const focusable = modal.querySelectorAll(
			'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
		);
		if ( !focusable.length ) return;

		const first = focusable[0];
		const last = focusable[focusable.length - 1];
		const activeElement = modal.ownerDocument.activeElement;

		if ( e.key === 'Tab' ) {
			// Check if modal was triggered automatically and this is first tab
			const modalTrigger = modal.getAttribute( 'data-modal-trigger' ) || 'manual';
			
			if ( state.isFirstTab && modalTrigger === 'automatic' && !modal.contains( activeElement ) ) {
				// First tab on automatic modal - focus close button
				e.preventDefault();
				first.focus();
				state.isFirstTab = false;
				return;
			}
			
			// Trap focus inside modal.
			if ( e.shiftKey && activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( !e.shiftKey && activeElement === last ) {
				e.preventDefault();
				first.focus();
			} else if ( !modal.contains( activeElement ) ) {
				// If focus is outside modal, bring it back inside
				e.preventDefault();
				if ( e.shiftKey ) {
					last.focus();
				} else {
					first.focus();
				}
			}
		} else if ( e.key === 'Escape' && args.escPress ) {
			// Close modal and restore previous focus only if escPress is enabled.
			closeModal( args.blockId );
			if ( state.previouslyFocusedElement ) {
				state.previouslyFocusedElement.focus();
			}
		}
	};
	storeHandler( args.blockId, getDocumentElement(), 'keydown', keydownHandler );
	
	// Prevent focus on elements outside modal when clicking
	const focusinHandler = ( e ) => {
		const modal = getDocumentElement().getElementById( args.blockId );
		if ( ! modal || ! modal.classList.contains( 'active' ) ) return;
		
		// If focus moves outside the modal, bring it back
		if ( !modal.contains( e.target ) ) {
			e.preventDefault();
			e.stopPropagation();
			
			const focusable = modal.querySelectorAll(
				'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
			);
			if ( focusable.length > 0 ) {
				focusable[0].focus();
			}
		}
	};
	storeHandler( args.blockId, getDocumentElement(), 'focusin', focusinHandler );

	// Set initial focus to first focusable element on modal open.
	const openHandler = ( event ) => {
		// Check if this event is for our modal
		if ( event.detail?.blockId && event.detail.blockId !== args.blockId ) return;
		
		const modal = getDocumentElement().getElementById( args.blockId );
		if ( !modal || !modal.classList.contains( 'active' ) ) return;
		
		const state = modalHandlers.get( args.blockId )?.state;
		if ( ! state ) return;

		const focusable = modal.querySelectorAll(
			'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
		);
		if ( !focusable.length ) return;
		const activeElement = modal.ownerDocument.activeElement;

		// Save currently focused element to return focus when modal closes.
		// Prefer trigger element from event detail, otherwise use active element
		if ( event.detail?.triggerElement ) {
			state.previouslyFocusedElement = event.detail.triggerElement;
		} else {
			state.previouslyFocusedElement = activeElement;
		}
		
		// Reset first tab flag
		state.isFirstTab = true;
		
		// Get modal trigger type from event detail if available
		const modalTrigger = event.detail?.modalTrigger || modal.getAttribute( 'data-modal-trigger' ) || 'manual';
		
		// Don't auto-focus any element when modal opens
		// The user should control focus with Tab key
		// Only exception: automatic modals might need different handling
		if ( modalTrigger === 'automatic' ) {
			// For automatic modals, we might want different behavior
			// Currently, we don't focus anything
		}
	};
	storeHandler( args.blockId, getDocumentElement(), 'spectra:modal:opened', openHandler );
}

/**
 * Add keyboard support for close button (Enter/Space to close)
 *
 * @param {Object} args - Modal configuration.
 */
function attachCloseButtonKeyboard( args ) {
	const modal = getDocumentElement().getElementById( args.blockId );
	if ( !modal ) return;
	
	// Wait a bit for modal content to render
	setTimeout( () => {
		// Find close button with the specific class
		const closeButtons = modal.querySelectorAll( '.spectra-modal-popup-close' );
		
		closeButtons.forEach( button => {
			// Make sure the element is focusable if it's not a button/link
			if ( button.tagName !== 'BUTTON' && button.tagName !== 'A' && !button.hasAttribute( 'tabindex' ) ) {
				button.setAttribute( 'tabindex', '0' );
			}
			
			// Add role button if not already a button
			if ( button.tagName !== 'BUTTON' && !button.hasAttribute( 'role' ) ) {
				button.setAttribute( 'role', 'button' );
			}
			
			const handler = ( e ) => {
				// Handle Enter key (Note: Tab should not trigger close, it's for navigation)
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					e.stopPropagation();
					
					// Trigger click event on the close button
					button.click();
					
					// Or directly close the modal (which will restore focus)
					closeModal( args.blockId );
				}
			};
			storeHandler( args.blockId, button, 'keydown', handler );
		} );
	}, CLOSE_BUTTON_RENDER_DELAY );
}

} )(); // End IIFE
