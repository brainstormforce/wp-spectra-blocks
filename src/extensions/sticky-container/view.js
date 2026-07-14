/**
 * Sticky Container - JavaScript-based Implementation
 *
 * Uses JavaScript to handle sticky positioning for better control and compatibility.
 *
 * @since x.x.x
 */

( function () {
	'use strict';

	class StickyContainer {
		constructor( element ) {
			this.element = element;
			this.spacer = null;
			this.isSticky = false;
			this.aosReady = false; // Flag to prevent sticky activation until AOS finishes.
			this.ticking = false; // Flag for requestAnimationFrame throttling.
			this.originalStyles = {
				position: '',
				top: '',
				bottom: '',
				left: '',
				right: '',
				width: '',
				zIndex: '',
				marginTop: '',
				marginBottom: '',
				marginLeft: '',
				marginRight: '',
				transform: '',
				opacity: '',
				visibility: '',
				transition: '',
			};
			this.aosAttributes = null; // Store AOS attributes.

			// Bind handlers once to prevent memory leaks.
			this.boundOnScroll = this.onScroll.bind( this );
			this.boundHandleResize = this.handleResize.bind( this );

			// Get settings from CSS custom properties and data attributes.
			this.settings = this.getSettings();

			// Initialize.
			this.init();
		}

		/**
		 * Convert CSS value with unit to pixels.
		 *
		 * @param {string} value - CSS value (e.g., '10px', '50%', '2em', '5vh').
		 * @param {string} axis  - 'horizontal' or 'vertical' for percentage calculations.
		 * @return {number} Value in pixels.
		 */
		convertToPixels( value, axis = 'vertical' ) {
			if ( ! value || value === '0' ) {
				return 0;
			}

			const numericValue = parseFloat( value );
			const unit = value.replace( numericValue.toString(), '' ).trim();

			switch ( unit ) {
				case 'px':
					return numericValue;

				case '%':
					// Percentage is relative to viewport.
					if ( axis === 'vertical' ) {
						return ( numericValue / 100 ) * window.innerHeight;
					}
					return ( numericValue / 100 ) * window.innerWidth;

				case 'vh':
					return ( numericValue / 100 ) * window.innerHeight;

				case 'vw':
					return ( numericValue / 100 ) * window.innerWidth;

				case 'em':
				case 'rem': {
					// Create temporary element to calculate em/rem in pixels.
					const tempEl = document.createElement( 'div' );
					tempEl.style.position = 'absolute';
					tempEl.style.visibility = 'hidden';
					tempEl.style.height = value;
					document.body.appendChild( tempEl );
					const pixels = tempEl.offsetHeight;
					document.body.removeChild( tempEl );
					return pixels;
				}

				default:
					// If no unit or unknown unit, treat as pixels.
					return numericValue;
			}
		}

		getSettings() {
			const computedStyle = window.getComputedStyle( this.element );
			const offsetValue = computedStyle.getPropertyValue( '--spectra-sticky-offset' ).trim();
			const keepInsideParentValue = computedStyle
				.getPropertyValue( '--spectra-sticky-keep-inside-parent' )
				.trim();

			// Convert offset to pixels (supports px, %, em, rem, vh, vw).
			const offset = this.convertToPixels( offsetValue, 'vertical' );

			// Determine stick position from class.
			const stickAt = this.element.classList.contains( 'spectra-sticky-bottom' ) ? 'bottom' : 'top';

			// Check for admin bar.
			const adminBar = document.getElementById( 'wpadminbar' );
			let adminBarHeight = 0;
			if ( adminBar && stickAt === 'top' ) {
				adminBarHeight = adminBar.offsetHeight;
			}

			return {
				offset: offset + adminBarHeight,
				stickAt,
				zIndex: 9999,
				keepInsideParent: keepInsideParentValue === '1',
			};
		}

		init() {
			// Create spacer element.
			this.createSpacer();

			// Bind events.
			this.bindEvents();

			// Check if element has AOS animation.
			if ( this.element.hasAttribute( 'data-aos' ) ) {
				// Check if element is ALREADY in sticky position on page load.
				// If yes, activate sticky immediately (don't wait for animation).
				// This prevents blank space when element starts at top with long animation delay.

				// Temporarily clear transforms to get true position (animations like Slide Left offset the element).
				const originalTransform = this.element.style.transform;
				this.element.style.transform = 'none';

				const elementRect = this.element.getBoundingClientRect();
				const offset = this.settings.offset;
				const alreadyInStickyPosition =
					this.settings.stickAt === 'top'
						? elementRect.top <= offset
						: elementRect.bottom >= window.innerHeight - offset;

				// Restore transform.
				this.element.style.transform = originalTransform;

				if ( alreadyInStickyPosition ) {
					// Element is already at sticky position - activate immediately.
					// Add aos-animate class to ensure element is visible and passes aosReadyToStick check.
					this.element.classList.add( 'aos-animate' );
					this.aosReady = true;
					this.activate();
				} else {
					// Element is below sticky position - wait for AOS to animate first.
					this.waitForAOS( () => {
						this.aosReady = true;
						this.checkPosition();
					} );
				}
			} else {
				// No AOS, mark as ready immediately.
				this.aosReady = true;
				this.checkPosition();
			}
		}

		/**
		 * Wait for AOS to initialize and animate before proceeding.
		 *
		 * This ensures AOS has time to animate the element before we potentially
		 * disable it for sticky positioning. Uses MutationObserver to detect
		 * when AOS adds the 'aos-animate' class.
		 *
		 * @param {Function} callback - Function to call after AOS has animated.
		 * @since x.x.x
		 */
		waitForAOS( callback ) {
			// Check if element already has aos-animate class (already animated).
			if ( this.element.classList.contains( 'aos-animate' ) ) {
				callback();
				return;
			}

			// Wait for AOS to initialize first.
			let aosInitAttempts = 0;
			const maxAOSInitAttempts = 50; // Max 5 seconds (50 * 100ms).

			const waitForAOSInit = () => {
				aosInitAttempts++;

				if ( typeof window.AOS === 'undefined' || typeof window.AOS.refresh !== 'function' ) {
					// AOS not loaded yet.
					if ( aosInitAttempts < maxAOSInitAttempts ) {
						// Keep waiting.
						setTimeout( waitForAOSInit, 100 );
					} else {
						// AOS didn't load after 5 seconds, proceed anyway.
						callback();
					}
					return;
				}

				// AOS is loaded. Now wait for the aos-animate class to be added.
				// Use MutationObserver to watch for class changes.
				const observer = new MutationObserver( ( mutations ) => {
					mutations.forEach( ( mutation ) => {
						if ( mutation.type === 'attributes' && mutation.attributeName === 'class' ) {
							if ( this.element.classList.contains( 'aos-animate' ) ) {
								// Animation class added! Wait for animation to complete.
								observer.disconnect();
								const aosDuration = parseInt(
									this.element.getAttribute( 'data-aos-duration' ) || '400',
									10
								);
								setTimeout( callback, aosDuration + 100 );
							}
						}
					} );
				} );

				// Start observing.
				observer.observe( this.element, {
					attributes: true,
					attributeFilter: [ 'class' ],
				} );

				// Calculate timeout based on delay + duration for elements in viewport.
				// For elements not in viewport, this ensures we don't wait forever.
				const aosDelay = parseInt( this.element.getAttribute( 'data-aos-delay' ) || '0', 10 );
				const aosDuration = parseInt( this.element.getAttribute( 'data-aos-duration' ) || '400', 10 );
				const timeoutDuration = Math.min( aosDelay + aosDuration + 200, 5000 ); // Cap at 5 seconds.

				setTimeout( () => {
					observer.disconnect();
					callback();
				}, timeoutDuration );
			};

			waitForAOSInit();
		}

		createSpacer() {
			this.spacer = document.createElement( 'div' );
			this.spacer.className = 'spectra-sticky__spacer';
			this.spacer.style.display = 'none';
			this.spacer.style.pointerEvents = 'none';
		}

		bindEvents() {
			// Use passive listeners for better scroll performance.
			window.addEventListener( 'scroll', this.boundOnScroll, { passive: true } );
			window.addEventListener( 'resize', this.boundHandleResize );
		}

		/**
		 * Throttled scroll handler using requestAnimationFrame.
		 *
		 * Prevents layout thrashing by ensuring checkPosition runs at most
		 * once per animation frame.
		 */
		onScroll() {
			if ( ! this.ticking ) {
				requestAnimationFrame( () => {
					this.checkPosition();
					this.ticking = false;
				} );

				this.ticking = true;
			}
		}

		checkPosition() {
			const elementRect = this.getElementRect();
			const offset = this.settings.offset;

			if ( this.settings.stickAt === 'top' ) {
				this.checkTopPosition( elementRect, offset );
			} else {
				this.checkBottomPosition( elementRect, offset );
			}
		}

		getElementRect() {
			// If sticky is active, get the spacer's position.
			if ( this.isSticky && this.spacer.parentNode ) {
				return this.spacer.getBoundingClientRect();
			}
			return this.element.getBoundingClientRect();
		}

		checkTopPosition( elementRect, offset ) {
			const shouldStick = elementRect.top <= offset;

			// Additional safety: Don't activate if AOS is about to animate.
			// If element has data-aos but not aos-animate, AOS hasn't animated yet.
			const aosReadyToStick =
				! this.element.hasAttribute( 'data-aos' ) || this.element.classList.contains( 'aos-animate' );

			// Check if element is currently visible (not in mid-animation).
			const isVisible = this.isElementVisible();

			if ( shouldStick && ! this.isSticky && this.aosReady && aosReadyToStick && isVisible ) {
				this.activate();
			} else if ( ! shouldStick && this.isSticky ) {
				this.deactivate();
			}

			if ( this.isSticky && this.settings.keepInsideParent && this.settings.stickAt === 'top' ) {
				const parentRect = this.element.parentNode.getBoundingClientRect();
				const elementHeight = this.element.offsetHeight;
				const marginBottom = parseFloat( this.element.style.marginBottom ) || 0;
				const maxTop = parentRect.bottom - elementHeight - marginBottom;
				this.element.style.top = Math.min( offset, maxTop ) + 'px';
			}
		}

		checkBottomPosition( elementRect, offset ) {
			const viewportHeight = window.innerHeight;
			const shouldStick = elementRect.bottom >= viewportHeight - offset;

			// Additional safety: Don't activate if AOS is about to animate.
			// If element has data-aos but not aos-animate, AOS hasn't animated yet.
			const aosReadyToStick =
				! this.element.hasAttribute( 'data-aos' ) || this.element.classList.contains( 'aos-animate' );

			// Check if element is currently visible (not in mid-animation).
			const isVisible = this.isElementVisible();

			if ( shouldStick && ! this.isSticky && this.aosReady && aosReadyToStick && isVisible ) {
				this.activate();
			} else if ( ! shouldStick && this.isSticky ) {
				this.deactivate();
			}
		}

		/**
		 * Check if element is currently visible.
		 *
		 * Prevents activating sticky on elements that are mid-animation or hidden.
		 * Checks computed opacity and visibility to ensure element is truly visible.
		 *
		 * @since x.x.x
		 * @return {boolean} True if element is visible.
		 */
		isElementVisible() {
			const computedStyle = window.getComputedStyle( this.element );
			const opacity = parseFloat( computedStyle.opacity );
			const visibility = computedStyle.visibility;

			// Element must have opacity > 0.1 and not be visibility: hidden.
			// We use 0.1 threshold to allow for animations in progress.
			return opacity > 0.1 && visibility !== 'hidden';
		}

		activate() {
			// Save original styles.
			this.originalStyles.position = this.element.style.position;
			this.originalStyles.top = this.element.style.top;
			this.originalStyles.bottom = this.element.style.bottom;
			this.originalStyles.left = this.element.style.left;
			this.originalStyles.right = this.element.style.right;
			this.originalStyles.width = this.element.style.width;
			this.originalStyles.zIndex = this.element.style.zIndex;
			this.originalStyles.marginTop = this.element.style.marginTop;
			this.originalStyles.marginBottom = this.element.style.marginBottom;
			this.originalStyles.marginLeft = this.element.style.marginLeft;
			this.originalStyles.marginRight = this.element.style.marginRight;
			this.originalStyles.transform = this.element.style.transform;
			this.originalStyles.opacity = this.element.style.opacity;
			this.originalStyles.visibility = this.element.style.visibility;
			this.originalStyles.transition = this.element.style.transition;

			// Disable transition immediately to prevent measurement errors (e.g. from AOS transforms).
			this.element.style.transition = 'none';

			// Handle AOS (Animate On Scroll) conflict.
			this.disableAOS();

			// Extra safety: Ensure element is visible after disabling AOS.
			// This prevents stuck invisible states if animation was interrupted.
			this.element.style.opacity = '1';
			this.element.style.visibility = 'visible';

			// Get current dimensions and computed styles.
			const computedStyle = window.getComputedStyle( this.element );
			const marginLeft = parseFloat( computedStyle.marginLeft ) || 0;
			const marginRight = parseFloat( computedStyle.marginRight ) || 0;
			const marginTop = parseFloat( computedStyle.marginTop ) || 0;
			const marginBottom = parseFloat( computedStyle.marginBottom ) || 0;

			// Clear any transforms that might offset the element (e.g., from AOS).
			this.element.style.transform = 'none';

			// Get rect AFTER clearing transforms to get true position.
			const rect = this.element.getBoundingClientRect();

			// Calculate actual width without margins.
			const width = rect.width;

			// Insert spacer to maintain layout space.
			this.spacer.style.display = 'block';
			this.spacer.style.height = rect.height + 'px';
			this.spacer.style.width = width + 'px';
			this.spacer.style.marginLeft = marginLeft + 'px';
			this.spacer.style.marginRight = marginRight + 'px';
			this.spacer.style.marginTop = marginTop + 'px';
			this.spacer.style.marginBottom = marginBottom + 'px';
			this.spacer.style.flexGrow = '0';
			this.spacer.style.flexShrink = '0';
			this.element.parentNode.insertBefore( this.spacer, this.element );

			// Apply sticky styles.
			// Keep side margins for visual design, only zero out margin in stick direction.
			this.element.style.position = 'fixed';
			this.element.style.width = width + 'px';
			this.element.style.zIndex = this.settings.zIndex;
			this.element.style.left = rect.left - marginLeft + 'px';
			this.element.style.right = 'auto';
			this.element.style.marginLeft = marginLeft + 'px';
			this.element.style.marginRight = marginRight + 'px';

			if ( this.settings.stickAt === 'top' ) {
				// Sticking at top: remove top margin, keep bottom margin.
				this.element.style.marginTop = '0';
				this.element.style.marginBottom = marginBottom + 'px';
				this.element.style.top = this.settings.offset + 'px';
				this.element.style.bottom = 'auto';
			} else {
				// Sticking at bottom: remove bottom margin, keep top margin.
				this.element.style.marginBottom = '0';
				this.element.style.marginTop = marginTop + 'px';
				this.element.style.bottom = this.settings.offset + 'px';
				this.element.style.top = 'auto';
			}

			// Add active class.
			this.element.classList.add( 'spectra-sticky--active' );
			this.isSticky = true;

			// Restore transition.
			// We use requestAnimationFrame to ensure the 'none' transition has been applied
			// and the layout snap has occurred without animation.
			requestAnimationFrame( () => {
				this.element.style.transition = this.originalStyles.transition;
			} );
		}

		deactivate() {
			// Remove spacer.
			if ( this.spacer.parentNode ) {
				this.spacer.parentNode.removeChild( this.spacer );
			}
			this.spacer.style.display = 'none';

			// Restore original styles.
			this.element.style.position = this.originalStyles.position;
			this.element.style.top = this.originalStyles.top;
			this.element.style.bottom = this.originalStyles.bottom;
			this.element.style.left = this.originalStyles.left;
			this.element.style.right = this.originalStyles.right;
			this.element.style.width = this.originalStyles.width;
			this.element.style.zIndex = this.originalStyles.zIndex;
			this.element.style.marginTop = this.originalStyles.marginTop;
			this.element.style.marginBottom = this.originalStyles.marginBottom;
			this.element.style.marginLeft = this.originalStyles.marginLeft;
			this.element.style.marginRight = this.originalStyles.marginRight;
			this.element.style.transform = this.originalStyles.transform;
			this.element.style.opacity = this.originalStyles.opacity;
			this.element.style.opacity = this.originalStyles.opacity;
			this.element.style.visibility = this.originalStyles.visibility;
			this.element.style.transition = this.originalStyles.transition;

			// Remove active class.
			this.element.classList.remove( 'spectra-sticky--active' );
			this.isSticky = false;

			// Re-enable AOS if it was active.
			this.enableAOS();
		}

		/**
		 * Disable AOS on the element to prevent animation conflicts.
		 *
		 * Saves all AOS-related attributes and removes them from the element,
		 * then manually triggers AOS refresh if available.
		 *
		 * @since x.x.x
		 */
		disableAOS() {
			// Check if element has AOS attributes.
			if ( ! this.element.hasAttribute( 'data-aos' ) ) {
				return;
			}

			// Save all AOS-related attributes.
			this.aosAttributes = {
				'data-aos': this.element.getAttribute( 'data-aos' ),
				'data-aos-duration': this.element.getAttribute( 'data-aos-duration' ),
				'data-aos-delay': this.element.getAttribute( 'data-aos-delay' ),
				'data-aos-easing': this.element.getAttribute( 'data-aos-easing' ),
				'data-aos-offset': this.element.getAttribute( 'data-aos-offset' ),
				'data-aos-anchor': this.element.getAttribute( 'data-aos-anchor' ),
				'data-aos-anchor-placement': this.element.getAttribute( 'data-aos-anchor-placement' ),
				'data-aos-once': this.element.getAttribute( 'data-aos-once' ),
			};

			// Add aos-animate class FIRST to ensure element stays visible.
			this.element.classList.add( 'aos-animate' );

			// Force element to be visible during sticky state.
			this.element.style.opacity = '1';
			this.element.style.visibility = 'visible';

			// Remove all AOS attributes from the element.
			Object.keys( this.aosAttributes ).forEach( ( attr ) => {
				if ( this.aosAttributes[ attr ] !== null ) {
					this.element.removeAttribute( attr );
				}
			} );

			// Manually refresh AOS if available.
			if ( typeof window.AOS !== 'undefined' && typeof window.AOS.refresh === 'function' ) {
				requestAnimationFrame( () => {
					window.AOS.refresh();
				} );
			}
		}

		/**
		 * Re-enable AOS on the element after sticky deactivation.
		 *
		 * Restores all previously saved AOS attributes and triggers AOS refresh.
		 * Respects user's "Play Repeatedly on Scroll" setting.
		 *
		 * @since x.x.x
		 */
		enableAOS() {
			// Check if we have saved AOS attributes.
			if ( ! this.aosAttributes ) {
				return;
			}

			// Restore all AOS attributes (including data-aos-once).
			Object.keys( this.aosAttributes ).forEach( ( attr ) => {
				if ( this.aosAttributes[ attr ] !== null ) {
					this.element.setAttribute( attr, this.aosAttributes[ attr ] );
				} else {
					// If attribute was not set originally, remove it.
					this.element.removeAttribute( attr );
				}
			} );

			// We do NOT remove the aos-animate class here, even if "Play Repeatedly" is enabled.
			// Removing it causes the element to vanish immediately upon unsticking.
			// We leave it present so the element stays visible.
			// AOS.refresh() will detect the element. If it's configured to play repeatedly (once=false),
			// AOS will automatically remove the class when the user scrolls the element out of view.

			// Clear saved attributes.
			this.aosAttributes = null;

			// Force AOS to re-evaluate the element using requestAnimationFrame.
			// This ensures the DOM has updated before AOS checks the element.
			if ( typeof window.AOS !== 'undefined' && typeof window.AOS.refresh === 'function' ) {
				requestAnimationFrame( () => {
					// Double RAF to ensure styles are applied.
					requestAnimationFrame( () => {
						window.AOS.refresh();
					} );
				} );
			}
		}

		handleResize() {
			if ( this.isSticky ) {
				// Temporarily deactivate and reactivate to recalculate dimensions.
				this.deactivate();
				// Use requestAnimationFrame for better performance.
				requestAnimationFrame( () => {
					this.checkPosition();
				} );
			}
		}

		destroy() {
			if ( this.isSticky ) {
				this.deactivate();
			}
			window.removeEventListener( 'scroll', this.boundOnScroll );
			window.removeEventListener( 'resize', this.boundHandleResize );
		}
	}

	// Initialize all sticky containers.
	function initStickyContainers() {
		const stickyElements = document.querySelectorAll( '.spectra-sticky-container' );

		stickyElements.forEach( ( element ) => {
			// Check if already initialized.
			if ( element.dataset.spectraStickyInitialized ) {
				return;
			}

			// Initialize.
			const stickyInstance = new StickyContainer( element );

			// Store instance on element for later access.
			element.spectraStickyInstance = stickyInstance;
			element.dataset.spectraStickyInitialized = 'true';
		} );
	}

	// Initialize on DOM ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initStickyContainers );
	} else {
		initStickyContainers();
	}

	// Re-initialize on dynamic content changes (e.g., AJAX).
	const observer = new MutationObserver( ( mutations ) => {
		mutations.forEach( ( mutation ) => {
			mutation.addedNodes.forEach( ( node ) => {
				if ( node.nodeType === 1 ) {
					// Element node.
					// Check if the added node itself is sticky.
					if (
						node.classList &&
						node.classList.contains( 'spectra-sticky-container' ) &&
						! node.dataset.spectraStickyInitialized
					) {
						const stickyInstance = new StickyContainer( node );
						node.spectraStickyInstance = stickyInstance;
						node.dataset.spectraStickyInitialized = 'true';
					}

					// Check for sticky containers within added node.
					const stickyChildren =
						node.querySelectorAll &&
						node.querySelectorAll( '.spectra-sticky-container:not([data-spectra-sticky-initialized])' );
					if ( stickyChildren && stickyChildren.length > 0 ) {
						stickyChildren.forEach( ( element ) => {
							const stickyInstance = new StickyContainer( element );
							element.spectraStickyInstance = stickyInstance;
							element.dataset.spectraStickyInitialized = 'true';
						} );
					}
				}
			} );
		} );
	} );

	// Observe the body for changes.
	observer.observe( document.body, {
		childList: true,
		subtree: true,
	} );
} )();
