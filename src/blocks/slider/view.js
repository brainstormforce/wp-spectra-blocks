/**
 * External dependencies.
 */
import Swiper from 'swiper';
import { applyFilters } from '@wordpress/hooks';

/**
 * Selector for focusable elements used throughout the slider.
 *
 * @type {string}
 */
const FOCUSABLE_SELECTOR =
       'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), details, [tabindex]:not([tabindex="-1"])';

/**
 * Utility to focus the first focusable element inside a slide.
 *
 * @param {Element} slide Slide element to search within.
 */
const focusFirstFocusable = ( slide ) => {
       if ( ! slide ) {
               return;
       }
       const firstFocusable = slide.querySelector( FOCUSABLE_SELECTOR );
       if ( firstFocusable ) {
               firstFocusable.focus();
       }
};

/**
 * Trigger animations for elements in a slide.
 *
 * @param {Element} slide The slide element to animate.
 */
const triggerSlideAnimations = ( slide ) => {
	if ( ! slide ) {
		return;
	}

	// First, ensure all non-animated elements are visible (fallback for elements without animations)
	const allElements = slide.querySelectorAll( '*' );
	allElements.forEach( ( element ) => {
		if ( ! element.hasAttribute( 'data-aos' ) && element.style.visibility === 'hidden' ) {
			element.style.visibility = 'visible';
		}
	} );
	
	// Handle animated elements
	const animatedElements = slide.querySelectorAll( '[data-aos]' );
	
	animatedElements.forEach( ( element ) => {
		// Get animation properties from data attributes
		const animationType = element.getAttribute( 'data-aos' );
		const animationDelay = parseInt( element.getAttribute( 'data-aos-delay' ) ) || 0;
		const animationDuration = parseInt( element.getAttribute( 'data-aos-duration' ) ) || 1000;
		const animationEasing = element.getAttribute( 'data-aos-easing' ) || 'ease';
		
		if ( ! animationType ) {
			// If no animation type, just show the element
			element.style.visibility = 'visible';
			return;
		}
		
		// Initialize animation timeouts object
		if ( ! element.animationTimeouts ) {
			element.animationTimeouts = {};
		}
		
		// Clear previous timeouts to prevent conflicts
		clearTimeout( element.animationTimeouts.start );
		clearTimeout( element.animationTimeouts.end );
		
		// Reset animation state but keep element hidden until animation starts
		element.classList.remove( 'aos-animate' );
		element.style.transitionDuration = '0s';
		element.style.transitionTimingFunction = animationEasing;
		
		// Start animation sequence with proper timing
		element.animationTimeouts.start = setTimeout( () => {
			// Show element and start animation
			element.style.visibility = 'visible';
			element.style.transitionDuration = `${ animationDuration / 1000 }s`;
			element.classList.add( 'aos-animate' );
		}, animationDelay );
		
		// Clean up after animation completes
		element.animationTimeouts.end = setTimeout( () => {
			// Keep the animation state but clean up temporary styles
			element.style.transitionDuration = '';
			element.style.transitionTimingFunction = '';
		}, animationDelay + animationDuration );
	} );
};

/**
 * Initialize the slider.
 *
 * @param {string} sliderId The ID of the slider.
 */
const initSlider = ( sliderId ) => {
	// Get the slider element.
	const sliderElement = document.getElementById( sliderId );
	if ( ! sliderElement ) {
		return;
	}

	// Get the Swiper parameters.
	let params = {};
	try {
		params = JSON.parse( sliderElement.dataset.swiper || '{}' );
	} catch ( e ) {
		return;
	}

	// Get the Swiper container.
	const swiperContainer = sliderElement.querySelector( '.swiper' );

	// Get the next and previous buttons.
	const nextEl = sliderElement.querySelector(
		'.spectra-slider-container .swiper-button-next'
	);
	const prevEl = sliderElement.querySelector(
		'.spectra-slider-container .swiper-button-prev'
	);
	const paginationEl = sliderElement.querySelector( '.swiper-pagination' );

	// The standalone swiper-bundle.min.js pre-registers all standard modules
	// (Navigation, Pagination, Autoplay, HashNavigation, A11y, etc.).
	// Only custom/third-party modules from extensions need to be passed here.
	const modules = applyFilters( 'spectra.sliderModules', [], params );
	params = applyFilters( 'spectra.sliderParams', params, params );

	// Sanitize slidesPerView values - convert 'auto' to numeric 1 for legacy data.
	const sanitizeSlidesPerView = ( value ) => {
		if ( 'auto' === value || ! value ) {
			return 1;
		}
		const numValue = parseFloat( value );
		return isNaN( numValue ) ? 1 : numValue;
	};

	// Sanitize main slidesPerView.
	if ( params.slidesPerView ) {
		params.slidesPerView = sanitizeSlidesPerView( params.slidesPerView );
	}

	// Sanitize breakpoints slidesPerView values.
	if ( params.breakpoints ) {
		Object.keys( params.breakpoints ).forEach( ( breakpoint ) => {
			if ( params.breakpoints[ breakpoint ].slidesPerView ) {
				params.breakpoints[ breakpoint ].slidesPerView = sanitizeSlidesPerView(
					params.breakpoints[ breakpoint ].slidesPerView
				);
			}
		} );
	}

	// Create the Swiper configuration.
	const config = {
		...( modules.length > 0 ? { modules } : {} ),
		...params,
		// Base slider settings.
		slidesPerView: params.slidesPerView || 1,
		spaceBetween: 'number' === typeof( params?.spaceBetween ) ? params.spaceBetween : 30,
		loop: params.loop === true,
		watchOverflow: true,
		allowTouchMove: params.allowTouchMove !== false,
		keyboard: {
			enabled: true,
			onlyInViewport: true,
		},
		
		// Enhanced accessibility configuration from swiper here {{index}} is the current slide index.
		a11y: {
			enabled: true,
			prevSlideMessage: 'Previous slide',
			nextSlideMessage: 'Next slide',
			firstSlideMessage: 'This is the first slide',
			lastSlideMessage: 'This is the last slide',
			paginationBulletMessage: 'Go to slide {{index}}',
			slideLabelMessage: 'Slide {{index}} of {{slidesLength}}',
			containerMessage: 'Slider with {{slidesLength}} slides',
			containerRoleDescriptionMessage: 'carousel',
			itemRoleDescriptionMessage: 'slide',
		},
		
		// Navigation settings.
		navigation: params.navigation ? {
			nextEl,
			prevEl,
			enabled: true,
			clickable: true,
			disabledClass: 'swiper-button-disabled',
		} : false,

		// Pagination settings.
		pagination: params.pagination ? {
			el: paginationEl,
			clickable: true,
			type: 'bullets',
		} : false,

		// Autoplay settings - Fixed configuration
		autoplay: params.autoplay ? {
			delay: params.autoplay.delay || 3000,
			disableOnInteraction: params.autoplay.disableOnInteraction,
			pauseOnMouseEnter: params.autoplay.pauseOnMouseEnter,
		} : false,

		// Hash navigation.
		hashNavigation: params.hashNavigation || false,

		// Responsive breakpoints.
		breakpoints: params.breakpoints || {},

		// Event handlers for accessibility.
		on: {
			init( swiper ) {
				setTimeout( () => {
					swiper.update();
					// Initialize AOS for the initial slide.
					if ( typeof window.AOS !== 'undefined' ) {
						window.AOS.init();
					}
					
					// Hide all animated elements by default across all slides
					swiper.slides.forEach( ( slide ) => {
						const animatedElements = slide.querySelectorAll( '[data-aos]' );
						animatedElements.forEach( ( element ) => {
							// Ensure animated elements are hidden by default
							element.style.visibility = 'hidden';
							element.classList.remove( 'aos-animate' );
							// Reset any previous animation styles
							element.style.transitionDuration = '';
							element.style.transitionTimingFunction = '';
							element.style.transform = '';
							element.style.opacity = '';
						} );
					} );
					
					// Then trigger animations for the currently active slide
					const activeSlide = swiper.slides[ swiper.activeIndex ];
					if ( activeSlide ) {
						// Small delay to ensure clean initialization
						setTimeout( () => {
							triggerSlideAnimations( activeSlide );
						}, 50 );
					}
				}, 100 );
			},
			// Reset animations when slide transition starts.
			slideChangeTransitionStart( swiper ) {
				// Reset animations for all slides to ensure clean state
				swiper.slides.forEach( ( slide ) => {
					const animatedElements = slide.querySelectorAll( '[data-aos]' );
					animatedElements.forEach( ( element ) => {
						// Clear any running animation timeouts to prevent conflicts
						if ( element.animationTimeouts ) {
							clearTimeout( element.animationTimeouts.start );
							clearTimeout( element.animationTimeouts.end );
							element.animationTimeouts = {};
						}
						
						// Immediately hide animated elements to prevent visual glitches
						element.style.visibility = 'hidden';
						
						// Reset animation state and clear any applied styles
						element.classList.remove( 'aos-animate' );
						element.style.transitionDuration = '';
						element.style.transitionTimingFunction = '';
						element.style.transform = '';
						element.style.opacity = '';
					} );
				} );
			},
			// Trigger animations when slide transition ends.
			slideChangeTransitionEnd( swiper ) {
				const activeSlide = swiper.slides[ swiper.activeIndex ];
				if ( activeSlide ) {
					// Trigger animations for the active slide
					triggerSlideAnimations( activeSlide );
				}
				setTimeout( () => swiper.update(), 100 );
				setupAccessibilityFeatures( swiper );
				manageSlideFocus( swiper );
			},
			slideChange( swiper ) {
				manageSlideFocus( swiper );
			},
		},
	};

	// Create the Swiper instance.
	const swiper = new Swiper( swiperContainer, config );

	// Setup enhanced focus management.
	setupEnhancedFocusManagement( swiper, sliderElement, params );

	// Dispatch event for extensions.
	const event = new CustomEvent( 'spectra-slider-init', {
		detail: {
			slider: swiper,
			config,
			element: sliderElement,
		}
	} );
	document.dispatchEvent( event );

	// Store the Swiper instance on the slider element.
	sliderElement.swiperInstance = swiper

	// Only add autoplay-related event listeners if autoplay is enabled.
	if ( params.autoplay ) {
		// Touch events - only add custom pause/resume behavior when disableOnInteraction is false
		// When disableOnInteraction is true, Swiper handles it natively (stops permanently on interaction)
		if ( params.autoplay.disableOnInteraction === false ) {
			swiper.on( 'touchStart', () => {
				swiper.autoplay.stop();
			} );

			swiper.on( 'touchEnd', () => {
				swiper.autoplay.start();
			} );
		}

		// Mouse events - add pause/resume behavior when pauseOnMouseEnter is true.
		if ( params.autoplay.pauseOnMouseEnter === true ) {
			swiperContainer.addEventListener( 'mouseenter', () => {
				swiper.autoplay.stop();
			} );

			swiperContainer.addEventListener( 'mouseleave', () => {
				swiper.autoplay.start();
			} );
		}
	}

	return swiper;
};

/**
 * Setup accessibility features for the slider.
 *
 * @param {Object} swiper The Swiper instance.
 */
function setupAccessibilityFeatures( swiper ) {
	const slides = swiper.slides;
	
	// Add proper ARIA attributes to slides.
	slides.forEach( ( slide, index ) => {
		// Ensure proper role and aria attributes.
		slide.setAttribute( 'role', 'tabpanel' );
		slide.setAttribute( 'aria-roledescription', 'slide' );
		slide.setAttribute( 'aria-label', `Slide ${ index + 1 } of ${ slides.length }` );
		
		// Add unique IDs if not present.
		if ( ! slide.id ) {
			slide.id = `slide-${ index + 1 }`;
		}
	} );
}

/**
 * Manage focus for slides and their focusable content.
 *
 * @param {Object} swiper The Swiper instance.
 */
function manageSlideFocus( swiper ) {
	const slides = swiper.slides;
	const activeIndex = swiper.activeIndex;
	
	slides.forEach( ( slide, index ) => {
		const isActive = index === activeIndex;
               const focusableElements = slide.querySelectorAll( FOCUSABLE_SELECTOR );
		
		if ( isActive ) {
			// Active slide: make content focusable and mark as current.
			slide.setAttribute( 'aria-current', 'true' );
			slide.removeAttribute( 'aria-hidden' );
			
			// Restore original tabindex values for focusable elements.
			focusableElements.forEach( el => {
				const originalTabindex = el.getAttribute( 'data-original-tabindex' );
				if ( originalTabindex !== null ) {
					if ( originalTabindex === 'null' ) {
						el.removeAttribute( 'tabindex' );
					} else {
						el.setAttribute( 'tabindex', originalTabindex );
					}
					el.removeAttribute( 'data-original-tabindex' );
				}
			} );
		} else {
			// Inactive slides: hide from screen readers and make content non-focusable.
			slide.removeAttribute( 'aria-current' );
			slide.setAttribute( 'aria-hidden', 'true' );
			
			// Store original tabindex and make elements non-focusable.
			focusableElements.forEach( el => {
				const currentTabindex = el.getAttribute( 'tabindex' );
				el.setAttribute( 'data-original-tabindex', currentTabindex || 'null' );
				el.setAttribute( 'tabindex', '-1' );
			} );
		}
	} );
}

/**
 * Setup enhanced focus management for proper keyboard navigation.
 *
 * @param {Object}  swiper        The Swiper instance.
 * @param {Element} sliderElement The slider container element.
 * @param {Object}  params        The slider configuration parameters.
 */
function setupEnhancedFocusManagement( swiper, sliderElement, params ) {
	// Ensure navigation buttons are always focusable.
	const prevButton = sliderElement.querySelector( '.swiper-button-prev' );
	const nextButton = sliderElement.querySelector( '.swiper-button-next' );
	const paginationBullets = sliderElement.querySelectorAll( '.swiper-pagination-bullet' );
	
	// Make sure navigation buttons maintain proper tabindex.
	if ( prevButton ) {
		prevButton.setAttribute( 'tabindex', '0' );
	}
	
	if ( nextButton ) {
		nextButton.setAttribute( 'tabindex', '0' );
	}
	
	// Handle pagination bullets.
	paginationBullets.forEach( bullet => {
		bullet.setAttribute( 'tabindex', '0' );
	} );
	
	// Handle keyboard navigation on slider controls.
	sliderElement.addEventListener( 'keydown', ( event ) => {
		const { key, target } = event;
		
		// Arrow key navigation when focused on navigation buttons or pagination.
		if ( target.classList.contains( 'swiper-button-prev' ) || 
			 target.classList.contains( 'swiper-button-next' ) ||
			 target.classList.contains( 'swiper-pagination-bullet' ) ) {
			
			if ( key === 'ArrowLeft' || key === 'ArrowUp' ) {
				event.preventDefault();
				swiper.slidePrev();
				// After keyboard navigation, focus on first focusable element in new slide for accessibility.
                               setTimeout( () => {
                                       focusFirstFocusable( swiper.slides[ swiper.activeIndex ] );
                               }, 100 );
			} else if ( key === 'ArrowRight' || key === 'ArrowDown' ) {
				event.preventDefault();
				swiper.slideNext();
				// After keyboard navigation, focus on first focusable element in new slide for accessibility
                               setTimeout( () => {
                                       focusFirstFocusable( swiper.slides[ swiper.activeIndex ] );
                               }, 100 );
			}
		}
		
		// Home/End navigation.
		if ( key === 'Home' && event.ctrlKey ) {
			event.preventDefault();
			swiper.slideTo( 0 );
			// Focus on first focusable element in first slide.
                       setTimeout( () => {
                               const firstSlide = swiper.slides[ 0 ];
                               focusFirstFocusable( firstSlide );
                       }, 100 );
		} else if ( key === 'End' && event.ctrlKey ) {
			event.preventDefault();
			swiper.slideTo( swiper.slides.length - 1 );
			// Focus on first focusable element in last slide.
                       setTimeout( () => {
                               const lastSlide = swiper.slides[ swiper.slides.length - 1 ];
                               focusFirstFocusable( lastSlide );
                       }, 100 );
		}
	} );
	
	// Only add focus-based autoplay management if autoplay is enabled and disableOnInteraction is false
	// When disableOnInteraction is true, let Swiper handle it natively without any custom interference
	if ( params.autoplay && params.autoplay.disableOnInteraction === false ) {
		// Pause autoplay when any interactive element gets focus.
	sliderElement.addEventListener( 'focusin', () => {
		if ( swiper.autoplay && swiper.autoplay.running ) {
			swiper.autoplay.stop();
		}
	} );
	
		// Resume autoplay when focus leaves the slider (with delay to handle focus transitions).
	sliderElement.addEventListener( 'focusout', () => {
		setTimeout( () => {
				// Only resume autoplay if focus has completely left the slider.
			if ( ! sliderElement.contains( sliderElement.ownerDocument.activeElement ) && 
				 swiper.autoplay &&  
				 swiper.autoplay.paused ) {
				swiper.autoplay.start();
			}
		}, 100 );
	} );
	}
}

// Initialize all sliders.
const initAllSliders = () => {
	const sliders = document.querySelectorAll( '.wp-block-spectra-slider[id]' );
	sliders.forEach( ( slider ) => {
		if ( slider.id && ! slider.swiperInstance ) {
			initSlider( slider.id );
		}
	} );
};

// Initialize on DOM content loaded.
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initAllSliders );
} else {
	initAllSliders();
}

// Re-init on dynamic content changes.
document.addEventListener( 'spectra-blocks-content-update', initAllSliders );

// Clean up on block removal.
document.addEventListener( 'spectra-blocks-content-remove', ( event ) => {
	const slider = event.target.closest( '.wp-block-spectra-slider' );
	if ( slider && slider.swiperInstance ) {
		slider.swiperInstance.destroy( true, true );
		delete slider.swiperInstance;
	}
} );
