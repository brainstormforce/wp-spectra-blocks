/**
 * External dependencies.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

// Create the Tabs store.
store( 'spectra/tabs', {
	actions: {
		// Action to toggle the currently active item.
		updateActiveTab: () => {
			const context = getContext( 'spectra/tabs' );
			const { currentTab } = context;
			context.activeTab = currentTab;
		},
		// Action to switch tabs with the left and right keys.
		switchTabs: ( event ) => {
			// If the keycode was not left(37) or right(39), abandon ship.
			if ( ! [ 37, 39 ].includes( event.keyCode ) ) {
				return;
			}
			// Get the required context attributes.
			const context = getContext( 'spectra/tabs' );
			const { blockId, currentTab, firstTab, lastTab } = context;
			const { ref } = getElement();
			let nextTab;
			// Based on the keycode, move to the required tab.
			switch ( event.keyCode ) {
				case 37:
					// If moving left, loop around to the last tab if we're out of bounds.
					nextTab = currentTab - 1;
					if ( nextTab < firstTab ) {
						nextTab = lastTab;
					}
					break;
				case 39:
					// If moving right, loop around to the first tab if we're out of bounds.
					nextTab = currentTab + 1;
					if ( nextTab > lastTab ) {
						nextTab = firstTab;
					}
					break;
			}
			const nextTabElement = ref.parentNode.querySelector(
				`#${ blockId }-tab-${ nextTab }`
			);
			nextTabElement.focus();
			context.activeTab = nextTab;
		},
	},
	callbacks: {
		// Callback that is run on initialization.
		initializeTabs: () => {
			const context = getContext( 'spectra/tabs' );
			const { blockId, currentTab } = context;
			context.tabId = `${ blockId }-tab-${ currentTab }`;
			context.tabPanelId = `${ blockId }-tabpanel-${ currentTab }`;
		},
		// Callback that is run when the active item changes.
		isActiveTab: () => {
			const context = getContext( 'spectra/tabs' );
			const { activeTab, currentTab } = context;
			const wasActive = context.isActive;
			context.isActive = activeTab === currentTab;
			window.AOS.refresh(); // Refresh AOS to detect newly visible elements.

			// Handle animations when tab becomes active.
			if ( context.isActive && ! wasActive ) {
				const { ref } = getElement();

				if ( ref ) {
					// Hide animated elements immediately when tab becomes active.
					const animatedElements =
						ref.querySelectorAll( '[data-aos]' );
					animatedElements.forEach( ( element ) => {
						element.style.visibility = 'hidden';
						element.classList.remove( 'aos-init', 'aos-animate' );
					} );

					// Wait for the tab panel to be fully visible, then show and animate.
					setTimeout( () => {
						// Show all animated elements in the tab panel.
						animatedElements.forEach( ( element ) => {
							element.style.visibility = 'visible';
						} );

						// Reinitialize AOS to trigger animations.
						if ( typeof window.AOS !== 'undefined' ) {
							window.AOS.init();
						}
					}, 100 ); // Quick delay to ensure tab is fully active and visible.
				}
			}
		},
		// Action to toggle the currently active item.
		updateTabAttributes: () => {
			const context = getContext( 'spectra/tabs' );
			const { isActive } = context;
			context.ariaSelected = isActive ? 'true' : 'false';
			context.tabIndex = isActive ? '0' : '-1';
		},
	},
} );
