/**
 * Frontend JavaScript for Button block.
 * Handles dynamic aria-label updates on hover.
 *
 * @since x.x.x
 */

document.addEventListener( 'DOMContentLoaded', function() {
	// Function to setup dynamic aria-label for buttons with hover icons.
	function setupDynamicAriaLabel() {
		const linkElements = document.querySelectorAll( 'a.has-hover-icon' );
		
		linkElements.forEach( ( linkElement ) => {
			// Skip if already processed.
			if ( linkElement.dataset.ariaSetup ) {
				return;
			}
			
			// Mark as processed.
			linkElement.dataset.ariaSetup = 'true';
			
			// Get the parent button wrapper for event handling.
			const buttonWrapper = linkElement.closest( '.wp-block-spectra-button' );
			if ( ! buttonWrapper ) {
				return;
			}
			
			// Store original aria-label.
			const originalAriaLabel = linkElement.getAttribute( 'aria-label' ) || '';
			
			// Get hover aria-label from the link element's data attribute.
			const hoverAriaLabel = linkElement.dataset.hoverAriaLabel;
			
			
			// Only proceed if we have hover aria-label.
			if ( ! hoverAriaLabel ) {
				return;
			}
			
			// Set up hover event listeners.
			function onMouseEnter() {
				linkElement.setAttribute( 'aria-label', hoverAriaLabel );
			}
			
			function onMouseLeave() {
				if ( originalAriaLabel ) {
					linkElement.setAttribute( 'aria-label', originalAriaLabel );
				} else {
					linkElement.removeAttribute( 'aria-label' );
				}
			}
			
			// Add event listeners to the button wrapper.
			buttonWrapper.addEventListener( 'mouseenter', onMouseEnter );
			buttonWrapper.addEventListener( 'mouseleave', onMouseLeave );
			
			// Add focus event listeners for accessibility to the link element.
			linkElement.addEventListener( 'focusin', onMouseEnter );
			linkElement.addEventListener( 'focusout', onMouseLeave );
		} );
	}
	
	// Run the setup.
	setupDynamicAriaLabel();
	
	// Re-run if new content is loaded dynamically.
	const observer = new MutationObserver( function( mutations ) {
		mutations.forEach( function( mutation ) {
			if ( mutation.addedNodes.length ) {
				setupDynamicAriaLabel();
			}
		} );
	} );
	
	observer.observe( document.body, {
		childList: true,
		subtree: true
	} );
} ); 