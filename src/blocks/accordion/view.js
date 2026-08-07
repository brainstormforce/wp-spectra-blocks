/**
 * External dependencies.
 */
import {
	store,
	getContext,
	getElement,
} from '@wordpress/interactivity';

// Track items that are open by default on initial page load.
// Used to skip the expand animation during hydration, preventing a visual flash.
// Non-reactive (plain JS Set) so modifications don't trigger watchers.
const initiallyOpenItems = new Set();

/**
 * Compute the collapsed and expanded max-height bounds for an accordion item.
 *
 * The item uses `box-sizing: border-box`, so `max-height` includes the item's
 * own vertical padding and border. The collapsed bound must therefore be the
 * header height PLUS the item's padding + border (not the header height alone),
 * and the expanded bound must add the item's border to `scrollHeight` (which
 * already covers content + padding but not border). Otherwise the animation
 * over- or under-shoots the item's true resting height and jolts at the edges.
 *
 * @param {HTMLElement} item   The accordion child item element.
 * @param {HTMLElement} header The accordion child header element.
 * @return {{collapsedHeight: number, expandedHeight: number}} The animation bounds in px.
 */
const getAnimationBounds = ( item, header ) => {
	// eslint-disable-next-line no-undef
	const styles = window.getComputedStyle( item );
	const verticalBorder =
		parseFloat( styles.borderTopWidth ) + parseFloat( styles.borderBottomWidth );
	const verticalPadding =
		parseFloat( styles.paddingTop ) + parseFloat( styles.paddingBottom );

	return {
		collapsedHeight: header.offsetHeight + verticalPadding + verticalBorder,
		expandedHeight: item.scrollHeight + verticalBorder,
	};
};

// Create the Accordion store.
store( 'spectra/accordion', {
	actions: {
		// Action to toggle the currently active item.
		toggleAnswer: () => {
			const context = getContext( 'spectra/accordion' );

			// Check if the accordion is disabled.
			if ( context.isDisabled ) {
				return;
			}

			if ( ! context.exclusiveMode && context.openByDefault ) {
				// Before exclusive mode, openByDefault items toggle independently
				// without affecting other items.
				context.userToggled = true;
				context.isExpanded = ! context.isExpanded;
			} else {
				// Clicking a non-openByDefault item (or any item once in exclusive mode)
				// activates exclusive single-item mode for the rest of the session.
				context.exclusiveMode = true;
				context.activeItem = context.isExpanded ? null : context.item;
			}
		},
		// Action to handle keyboard events for div elements with role="button".
		handleKeyDown: ( event ) => {
			// Check if Enter or Space key was pressed.
			if ( event.key === 'Enter' || event.key === ' ' ) {
				// Prevent default behavior (e.g., scrolling for Space key).
				event.preventDefault();

				const context = getContext( 'spectra/accordion' );

				// Check if the accordion is disabled.
				if ( context.isDisabled ) {
					return;
				}

				// Same toggle logic as toggleAnswer.
				if ( ! context.exclusiveMode && context.openByDefault ) {
					context.userToggled = true;
					context.isExpanded = ! context.isExpanded;
				} else {
					context.exclusiveMode = true;
					context.activeItem = context.isExpanded ? null : context.item;
				}
			}
		},
	},
	callbacks: {
		// Callback that registers an open-by-default item for animation skip on hydration.
		// Runs via data-wp-init (before data-wp-watch), so the item is registered
		// before isAnimated runs and would otherwise trigger an expand animation flash.
		initOpenByDefault: () => {
			const context = getContext( 'spectra/accordion' );
			initiallyOpenItems.add( context.item );
		},
		// Callback that is run when the active item changes.
		isToggled: () => {
			const context = getContext( 'spectra/accordion' );
			const { activeItem, item, openByDefault, userToggled, exclusiveMode } = context;

			const wasExpanded = context.isExpanded;

			if ( exclusiveMode ) {
				// In exclusive mode, ALL items follow the single activeItem,
				// regardless of openByDefault or userToggled state.
				context.isExpanded = ( activeItem === item );
			} else if ( userToggled ) {
				// Item was independently toggled before exclusive mode.
				// Its isExpanded is managed directly by the toggle action.
				return;
			} else if ( openByDefault ) {
				// openByDefault items remain expanded until the user interacts with them.
				context.isExpanded = true;
			} else {
				// Regular items follow the shared activeItem mechanism.
				context.isExpanded = ( activeItem === item );
			}

			// Handle AOS animations only when transitioning from collapsed to expanded.
			if ( ! wasExpanded && context.isExpanded ) {
				const { ref } = getElement();

				// Find the accordion details panel within this accordion item.
				const accordionDetails = ref.querySelector( '.wp-block-spectra-accordion-child-details' );

				if ( accordionDetails ) {
					// Use more efficient selector and batch DOM operations.
					const animatedElements = accordionDetails.querySelectorAll( '[data-aos]' );

					if ( animatedElements.length > 0 ) {
						// Batch style updates to minimize reflows.
						animatedElements.forEach( ( element ) => {
							element.style.visibility = 'hidden';
							element.classList.remove( 'aos-init', 'aos-animate' );
						} );

						// Use requestAnimationFrame for better timing instead of setTimeout.
						// eslint-disable-next-line no-undef
						requestAnimationFrame( () => {
							setTimeout( () => {
								// Batch visibility updates.
								animatedElements.forEach( ( element ) => {
									element.style.visibility = 'visible';
								} );

								// Only reinitialize AOS if it exists and there are animated elements.
								if ( typeof window.AOS !== 'undefined' && window.AOS.refresh ) {
									// Use refresh instead of init for better performance.
									window.AOS.refresh();
								}
							}, 250 ); // Reduced timing to match animation duration.
						} );
					}
				}
			}
		},
		// Callback that is run when the previously active or inactive item now needs to be animated.
		isAnimated: () => {
			const context = getContext( 'spectra/accordion' );
			const { isExpanded } = context;

			// Skip animation for items that are open by default during initial page load.
			// These items are already expanded in the SSR output, so animating them would
			// cause a visible collapse-then-expand flash. Set.delete() returns true if
			// the item was present, atomically removing it so subsequent runs animate normally.
			if ( initiallyOpenItems.delete( context.item ) ) {
				return;
			}

			const { ref } = getElement();

			// Cache DOM elements to avoid repeated queries
			const accordionHeader = ref.querySelector( '.wp-block-spectra-accordion-child-header' );
			const accordionDetails = ref.querySelector( '.wp-block-spectra-accordion-child-details' );

			if ( ! accordionHeader || ! accordionDetails ) {
				return;
			}

			if ( isExpanded ) {
				// Batch DOM reads to minimize layout recalculations
				accordionDetails.style.display = '';
				ref.style.maxHeight = '';

				// Use a single rAF to batch all DOM measurements
				// eslint-disable-next-line no-undef
				requestAnimationFrame( () => {
					// The item uses box-sizing: border-box, so max-height must include
					// the item's own vertical padding + border. Basing the collapsed
					// height on the header alone would undershoot the item's true
					// resting height and cause a visible jolt at the animation edge.
					const { collapsedHeight, expandedHeight } = getAnimationBounds( ref, accordionHeader );

					// Set initial state
					ref.style.maxHeight = `${ collapsedHeight }px`;

					// Animate to full height in next frame
					// eslint-disable-next-line no-undef
					requestAnimationFrame( () => {
						ref.style.maxHeight = `${ expandedHeight }px`;

						// Clean up after animation
						const cleanup = () => {
							ref.style.maxHeight = '';
							context.detailsDisplay = '';
						};

						// Use transitionend event instead of setTimeout for more reliable timing
						ref.addEventListener( 'transitionend', cleanup, { once: true } );
						// Fallback timeout in case transitionend doesn't fire
						setTimeout( cleanup, 300 );
					} );
				} );
			} else {
				// Batch DOM reads for collapse animation
				// eslint-disable-next-line no-undef
				requestAnimationFrame( () => {
					// Include the item's own padding + border so the collapse settles
					// at the item's true resting height (box-sizing: border-box).
					const { collapsedHeight, expandedHeight } = getAnimationBounds( ref, accordionHeader );

					// Set initial full height
					ref.style.maxHeight = `${ expandedHeight }px`;

					// Animate to header height in next frame
					// eslint-disable-next-line no-undef
					requestAnimationFrame( () => {
						ref.style.maxHeight = `${ collapsedHeight }px`;

						// Clean up after animation
						const cleanup = () => {
							ref.style.maxHeight = '';
							context.detailsDisplay = 'none';
						};

						// Use transitionend event instead of setTimeout for more reliable timing
						ref.addEventListener( 'transitionend', cleanup, { once: true } );
						// Fallback timeout in case transitionend doesn't fire
						setTimeout( cleanup, 300 );
					} );
				} );
			}
		},
		// Callback that is run when the display styles of the expanded and collapsed icons needs to be changed.
		updateIconDisplay: () => {
			const context = getContext( 'spectra/accordion' );
			const { isExpanded, displayType } = context;
			context.styleDisplay = isExpanded ? displayType : 'none';
			context.styleHide = isExpanded ? 'none' : displayType;
		},
		// Callback that is run when the disabled state changes to update tabindex.
		updateHeaderTabIndex: () => {
			const context = getContext( 'spectra/accordion' );
			const { isDisabled } = context;
			context.headerTabIndex = isDisabled ? -1 : 0;
		},
	},
} );
