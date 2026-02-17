import { store, getContext, getElement } from '@wordpress/interactivity';

// Track active modals and manage z-index interference
const activeModals = new Set();
const MODAL_ACTIVE_CLASS = 'spectra-modal-active';

/**
 * Manage z-index interference when modals are active
 * Adds/removes class to body to disable problematic z-index CSS
 */
function manageZIndexInterference() {
	const body = document.body;
	
	if ( activeModals.size > 0 ) {
		// Modal is active - add class to disable z-index interference
		body.classList.add( MODAL_ACTIVE_CLASS );
	} else {
		// No modals active - remove class to restore normal z-index behavior
		body.classList.remove( MODAL_ACTIVE_CLASS );
	}
}

export const { actions } = store( 'spectra/modal', {

	actions: {
		open() {
			const { blockId } = getContext();
			const modal = document.getElementById( blockId );
	
			if ( modal ) {
				modal.classList.add( 'active' );
				
				// Track modal opening for z-index management
				activeModals.add( blockId );
				manageZIndexInterference();
				
				// Set aria-hidden on the popup element, not the wrapper
				const popup = modal.querySelector( '.spectra-modal-popup' );
				if ( popup ) {
					popup.setAttribute( 'aria-hidden', 'false' );
					popup.setAttribute( 'aria-modal', 'true' );
					popup.setAttribute( 'role', 'dialog' );
				}

				// Dispatch custom event for modal opened
				const openedEvent = new CustomEvent( 'spectra:modal:opened', {
					detail: { blockId },
					bubbles: true,
				} );
				document.dispatchEvent( openedEvent );
			}
		},
	
		close() {
			const { blockId } = getContext();
			const modal = document.getElementById( blockId );
	
			if ( modal ) {
				modal.classList.remove( 'active' );
				
				// Track modal closing for z-index management
				activeModals.delete( blockId );
				manageZIndexInterference();
				
				// Set aria-hidden on the popup element, not the wrapper
				const popup = modal.querySelector( '.spectra-modal-popup' );
				if ( popup ) {
					popup.setAttribute( 'aria-hidden', 'true' );
					popup.removeAttribute( 'aria-modal' );
				}

				// Dispatch custom event for modal closed
				const closedEvent = new CustomEvent( 'spectra:modal:closed', {
					detail: { blockId },
					bubbles: true,
				} );
				document.dispatchEvent( closedEvent );
			}
		},
	
		toggle() {
			const { blockId } = getContext();
			const modal = document.getElementById( blockId );
	
			if ( modal ) {
				if ( modal.classList.contains( 'active' ) ) {
					actions.close();
				} else {
					actions.open();
				}
			}
		}
	},
	
	callbacks: {
		initialize() {
			const context = getContext();
			const { ref } = getElement();
			const { blockId, modalTrigger, cssClass, cssId, exitIntent, showAfterSeconds, noOfSecondsToShow, enableCookies, setCookiesOn, hideForDays, overlayClick, escPress } = context;

			// Don't set aria-hidden on the wrapper element
			// Instead, set it on the popup element after a short delay to ensure it's rendered
			setTimeout( () => {
				const popup = ref.querySelector( '.spectra-modal-popup' );
				if ( popup ) {
					popup.setAttribute( 'aria-hidden', 'true' );
					popup.setAttribute( 'role', 'dialog' );
				}
			}, 0 );

			const initializedEvent = new CustomEvent( 'spectra:modal:initialized', {
				detail: { blockId, modalTrigger, cssClass, cssId, exitIntent, showAfterSeconds, noOfSecondsToShow, enableCookies, setCookiesOn, hideForDays, overlayClick, escPress },
				bubbles: true,
			} );
			ref.dispatchEvent( initializedEvent );
		},
	},
} );
