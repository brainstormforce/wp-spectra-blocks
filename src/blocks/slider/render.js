/**
 * External dependencies.
 */
import { useInnerBlocksProps, store as blockEditorStore, useBlockProps } from '@wordpress/block-editor';
import { useRefEffect } from '@wordpress/compose';
import { select, subscribe, useSelect } from '@wordpress/data';
import { memo, useEffect, useRef, } from '@wordpress/element';
// Swiper CSS (extracted at build time, no JS bundle cost).
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
/**
 * Internal dependencies.
 */
import RenderSVG from '@spectra-helpers/render-svg';
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { getBackgroundImageStyles, VideoBackground } from '@spectra-helpers/background';
import { getAdvancedGradientValue } from '@spectra-helpers/get-advanced-gradient-value';
import { SliderRootToolbar } from './helper';

const ALLOWED_BLOCKS = [ 'spectra/slider-child' ];

/**
 * The Editor Block render.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block.
 */
const Render = memo( ( props ) => {
	const { clientId, attributes, isSelected } = props;
	const {
		navigation = true,
		pagination = true,
		slidesPerView = '1',
		spaceBetween = 30,
		navigationPrevIcon,
		navigationNextIcon,
		sliderHeight,
		background,
		breakpoints = 'none',
		overflow,
		dimRatio,
		backgroundColor,
		backgroundGradient,
		enableAdvGradients,
		advBgGradient,
		style
	} = attributes;

	// Pro status detection for frontend fallback.
	const isProActivated = 'active' === spectra_blocks_info.spectra_pro_status? true : false;
	
	// Calculate effective slides per view for frontend.
	const getEffectiveSlidesPerView = () => {
		const currentValue = slidesPerView || '1';
		
		// If Pro is not activated, only allow '1'.
		if ( !isProActivated ) {
			// Check if it's a Pro value (anything other than '1').
			if ( currentValue !== '1' && currentValue !== 1 ) {
				// All Pro values fallback to '1' in free version.
				return '1';
			}
		}
		
		return currentValue;
	};
	
	const effectiveSlidesPerView = getEffectiveSlidesPerView();

	// PERFORMANCE FIX: Add refs for state tracking and slider instance.
	const sliderInstanceRef = useRef( null );
	const slideOrderRef = useRef( [] );
	const currentSelectedBlockRef = useRef( null );
	const navigationTimeoutRef = useRef( null );
	const isNavigatingRef = useRef( false );

	// Get final background gradient value (advanced or basic).
	const finalBackgroundGradient = getAdvancedGradientValue(
		true, // For single gradient blocks, always pass true for enableAdvBg.
		advBgGradient,
		backgroundGradient,
		enableAdvGradients
	);

	// Get background styles.
	const additionalStyles = {
		...getBackgroundImageStyles( { background, backgroundGradient: finalBackgroundGradient } ),
	};

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor', cssVar: '--spectra-text-color', className: null },
		{ key: 'paginationColor', className: null },
		{ key: 'sliderHeight', cssVar: '--spectra-slider-height', className: null },
		{ key: 'navigationSize', cssVar: '--spectra-slider-nav-size', className: null },
		{ key: 'navigationIconSize', cssVar: '--spectra-slider-nav-icon-size', className: null },
		{ key: 'arrowColor', className: null },
		{ key: 'arrowBackgroundColor', className: null },
		{ key: 'arrowColorHover', className: null },
		{ key: 'arrowBackgroundColorHover', className: null },
		{ key: 'arrowDistance', className: null },
		{ key: 'paginationColorHover', className: null },
		{ key: 'paginationColorActive', className: null },
		{ key: 'paginationColorActiveHover', className: null },
		{ key: 'paginationTopMargin', cssVar: '--spectra-pagination-margin-top', className: null },
		{ key: 'backgroundColor' },
		{ key: 'backgroundGradient', value: finalBackgroundGradient }
	];

	// If the Overflow attribute is set, push the overflow based configs to generate styles and classes.
	if ( overflow ) {
		config.push(
			{ key: 'overflow', cssVar: 'overflow', className: null },
		);
	}

	// Check if has video background and border radius.
	const hasVideoBackground = background?.type === 'video';
	const hasImageBackground = background?.type === 'image';
	const hasBorderRadius = style?.border?.radius;

	const additionalClassNames = [
		'spectra-slider',
		['image', 'video'].includes( background?.type ) && `spectra-background-${background.type}`,
		( backgroundColor || finalBackgroundGradient ) && 'spectra-background-overlay',
		hasVideoBackground && 'has-video-background',
		hasImageBackground && 'has-image-background',
		sliderHeight && 'spectra-has-slider-height',
		'spectra-overlay-color'
	];

	// Generate styles and class names.
	const { style: generatedStyle, classNames } = useSpectraStyles( attributes, config, additionalClassNames );

	// Background styles handling.
	const getBackgroundStyles = () => {
		const styles = {
			...generatedStyle,
			...additionalStyles,
		};
		
		// Set the slider height CSS variable when height is specified
		if ( sliderHeight && ! styles['--spectra-slider-height'] ) {
			styles['--spectra-slider-height'] = sliderHeight;
		}
		
		return styles;
	};

	// Simple ternary function to return the space between if it's numeric.
	const getSpaceBetween = () => ( 'number' === typeof( spaceBetween ) ? spaceBetween : 30 );
	// Determine the overflow value - use 'clip' when video background with border radius
	const computedOverflow = ( hasVideoBackground || hasImageBackground ) && hasBorderRadius ? 'clip' : overflow;
	/**
	 * Use block props.
	 *
	 * @return {Object} The block props.
	 */
	const blockProps = useBlockProps( {
		className: spectraClassNames( classNames ),
		style: {
			...getBackgroundStyles(),
			'--spectra-overlay-opacity': ( dimRatio / 100 ),
		},
		overflow: computedOverflow,
	} );

	// Render toolbar controls using helper component.
	const RenderToolbar = () => <SliderRootToolbar clientId={ clientId } />;

	// Initialize the slider.
	const sliderRef = useRefEffect(
		( element ) => {
			if ( ! element ) {
				return;
			}

			// Detect iframe context for mobile/tablet preview (shared utility)
			const getEditorIframe = () => document.querySelector( 'iframe[name="editor-canvas"]' );
			const getEditorDocument = () => {
				const editorIframe = getEditorIframe();
				return editorIframe?.contentDocument || editorIframe?.contentWindow?.document || document;
			};

			// Initialize the slider based on the breakpoints.
			const initSwiper = async () => {
				// Dynamically import Swiper only when the editor renders a slider block.
				const [ { default: Swiper }, { Navigation, Pagination, Autoplay } ] = await Promise.all( [
					import( /* webpackChunkName: "swiper-core" */ 'swiper' ),
					import( /* webpackChunkName: "swiper-modules" */ 'swiper/modules' ),
				] );

		// Use correct document context for navigation and pagination selectors.
		const targetDoc = getEditorDocument();

		// DOM-BASED: Determine initial slide based on selected block using is-selected class.
		const getInitialSlide = () => {
			// Get all swiper slides inside the container.
			const slides = element.querySelectorAll( '.swiper-slide' );
			
			// Find the index of the slide with the "is-selected" class.
			let initialSlideIndex = 0;
			slides.forEach( ( slide, index ) => {
				if ( slide.classList.contains( 'is-selected' ) ) {
					initialSlideIndex = index;
				}
			} );
			
			return initialSlideIndex;
		};
		
		// Initialize the slider options.
		const options = {
			modules: [ Navigation, Pagination, Autoplay ],
			slidesPerView: parseFloat( effectiveSlidesPerView || 1 ),
			spaceBetween: getSpaceBetween(),
					slidesOffsetAfter:0,
					speed: 400, // Fast speed for editor.
					loop: false, // Disabled in editor.
					autoplay: false, // Disabled in editor.
				initialSlide: getInitialSlide(), // Start at the selected slide based on DOM state.
			navigation: navigation
				? {
						nextEl: targetDoc.querySelector( `.block-${ clientId } .swiper-button-next` ),
						prevEl: targetDoc.querySelector( `.block-${ clientId } .swiper-button-prev` ),
				  }
				: false,
			pagination: pagination
				? {
						el: targetDoc.querySelector( `.block-${ clientId } .swiper-pagination` ),
						clickable: true,
				  }
				: false,
				
					grabCursor: false, // Disable grab cursor.
					allowTouchMove: true, // Enable touch move in the editor.
					simulateTouch: false, // Disable simulate touch.
					observer: true, // Enable observer.
					observeParents: true, // Enable observe parents.
					observeSlideChildren: true, // Enable observe slide children.
					resizeObserver: true, // Enable resize observer.
				};

				// Initialize the slider with the options.
		const slider = new Swiper( element, options );
		
				// Ensure slides are properly initialized, especially for new sliders.
		window.requestAnimationFrame( () => {
					if ( slider && !slider?.destroyed ) {
						// Force update to ensure proper initialization.
						slider.updateSize();
						slider.updateSlides();
						slider.updateProgress();
						slider.update();
						
						// Initialization complete.
						
						// Set a flag to indicate this is a newly initialized slider.
						slider._isNewlyInitialized = true;
			}
		} );
		
		return slider;
			};

			// PERFORMANCE FIX: Initialize refs with current state (synchronous, no slider needed).
		slideOrderRef.current = select( blockEditorStore ).getBlockOrder( clientId );
		currentSelectedBlockRef.current = select( blockEditorStore ).getSelectedBlock();

		// Mutable reference for the async slider instance used in cleanup.
		let slider = null;

		// initSwiper is async — store the instance once resolved.
		initSwiper().then( ( instance ) => {
			slider = instance;
			sliderInstanceRef.current = instance;
		} );

		return () => {
			// PERFORMANCE FIX: Clean shutdown without subscribe listeners
			// Clear navigation timeout if it exists
			if ( navigationTimeoutRef.current ) {
				clearTimeout( navigationTimeoutRef.current );
				navigationTimeoutRef.current = null;
			}

			// Guard: Swiper may not have resolved yet if block was removed very quickly.
			if ( slider && typeof slider.destroy === 'function' ) {
				slider.updateProgress();
				slider.update();
				slider.updateSlides();

				// Clean up pagination element and navigation buttons before destroying
				const paginationEl = element.querySelector( '.swiper-pagination' );
				if ( paginationEl ) {
					paginationEl.innerHTML = '';
					paginationEl.removeAttribute( 'style' );
				}

				// Clean up navigation buttons
				const prevButton = element.querySelector( '.swiper-button-prev' );
				const nextButton = element.querySelector( '.swiper-button-next' );
				if ( prevButton ) prevButton.removeAttribute( 'style' );
				if ( nextButton ) nextButton.removeAttribute( 'style' );

				// Preserve the spectra-slider-child elements.
				if ( slider.slides && slider.slides.length > 0 ) {
					Array.from( slider.slides ).forEach( slide => {
						const spectraSliderChild = slide.querySelector( '.spectra-slider-child' );
						if ( spectraSliderChild ) {
							spectraSliderChild.className = 'spectra-slider-child swiper-slide';
							spectraSliderChild.style.width = '';
							spectraSliderChild.style.transform = '';
							spectraSliderChild.style.transition = '';
						}
						slide.style.width = '';
						slide.style.transform = '';
					} );
				}

				// Destroy the Swiper instance but keep the DOM elements.
				slider.destroy( true, false );
			}
		};
		},
		[ navigation, pagination, slidesPerView, spaceBetween, breakpoints ]
	);

	// HYBRID: useSelect for slide order (performance), subscribe for block selection (accuracy)
	const { currentSlideOrder } = useSelect( 
		( selectStore ) => {
			const blockEditorSelect = selectStore( blockEditorStore );
			const slideOrder = blockEditorSelect.getBlockOrder( clientId );
			
			return {
				currentSlideOrder: slideOrder || [],
			};
		}, 
		[ clientId ] 
	);

	// SUBSCRIBE: Use subscribe for block selection to ensure accurate timing (this was working!).
	useEffect( () => {
		const slider = sliderInstanceRef.current;
		if ( !slider ) return;

		// Subscribe to block selection changes for this specific slider.
		const unsubscribe = subscribe( () => {
			if ( !slider || slider.destroyed || isNavigatingRef.current ) return;

			const selectedBlock = select( blockEditorStore ).getSelectedBlock();
			if ( !selectedBlock ) return;

			// Only handle relevant block types.
			const isRelevantBlock = selectedBlock.name === 'spectra/slider-child' || 
			                       selectedBlock.name?.startsWith?.( 'spectra/' );
			
			if ( !isRelevantBlock ) return;

			let targetSlideIndex = -1;
			
			// Determine target slide.
		if ( selectedBlock.name === 'spectra/slider-child' ) {
				targetSlideIndex = currentSlideOrder.indexOf( selectedBlock.clientId );
		} else {
				// Check if it's a child of a slider-child.
			const blockParents = select( blockEditorStore ).getBlockParents( selectedBlock.clientId );
				const parentSlideId = blockParents.find( parentId => currentSlideOrder.includes( parentId ) );
				if ( parentSlideId ) {
					targetSlideIndex = currentSlideOrder.indexOf( parentSlideId );
				}
			}
			
			// Navigate if needed.
			if ( targetSlideIndex >= 0 && slider.activeIndex !== targetSlideIndex ) {
				isNavigatingRef.current = true;
				
				// Simple, direct navigation
				slider.slideTo( targetSlideIndex, 0 );
				
				// Minimal pagination update - only once, after navigation
				if ( slider && !slider.destroyed && slider.pagination && slider.pagination.update ) {
					slider.pagination.update();
				}
				isNavigatingRef.current = false;
			}
		} );

		// Cleanup subscription
		return () => {
			unsubscribe();
		};
	}, [ currentSlideOrder, clientId ] );

	// PERFORMANCE FIX: Handle slide order changes with detailed operation detection
	useEffect( () => {
		const slider = sliderInstanceRef.current;
		if ( !slider ) return;
		
		const previousOrder = slideOrderRef.current;
		const hasOrderChanged = currentSlideOrder.toString() !== previousOrder.toString();
		
		if ( hasOrderChanged ) {
			// Get the selected block to understand the operation context
			const selectedBlock = select( blockEditorStore ).getSelectedBlock();
			
			// Determine operation type
			const slideAdded = currentSlideOrder.length > previousOrder.length;
			const slideRemoved = currentSlideOrder.length < previousOrder.length;
			const slideMoved = currentSlideOrder.length === previousOrder.length;
			
			// Get the active index before changes
			const activeIndex = slider.activeIndex;

			// Store old order before updating reference
			const oldOrder = [ ...previousOrder ];
			slideOrderRef.current = [ ...currentSlideOrder ];

			// Handle different operations
			if ( slideAdded ) {
				// Find which slide was added
				const newSlideId = currentSlideOrder.find( ( id ) => ! oldOrder.includes( id ) );
				if ( newSlideId ) {
					window.requestAnimationFrame( () => {
						// Force a complete update for added slides
						slider.updateSize();
						slider.updateSlides();
						slider.updateProgress();
						slider.update();
						
						// CRITICAL: Force pagination update for added slides
						if ( slider.pagination && slider.pagination.update ) {
							slider.pagination.update();
						}
						
						// Navigate to the new slide if it was manually added
						if ( selectedBlock?.isManuallyAdded ) {
							// For toolbar-added slides, go to the last slide
							slider.slideTo( currentSlideOrder.length - 1, 0 );
						} else {
							// For duplicated slides, find the new slide position and navigate there
							const newSlideIndex = currentSlideOrder.indexOf( newSlideId );
							if ( newSlideIndex >= 0 ) {
								slider.slideTo( newSlideIndex, 0 );
							}
						}
						
						// Force slides to be visible and properly styled
						const slideElements = slider.slides;
						if ( slideElements && slideElements.length > 0 ) {
							Array.from( slideElements ).forEach( slide => {
								// Trigger reflow to ensure visibility
								void slide.offsetHeight;
								// Reset any problematic styles
								slide.style.visibility = 'visible';
								slide.style.opacity = '1';
							} );
						}
						// Final update after style fixes
						slider.update();
						
						// CRITICAL: Update pagination after style fixes
						if ( slider.pagination && slider.pagination.update ) {
							slider.pagination.update();
						}
					} );
				}
			} else if ( slideRemoved ) {
				// Find which slide was removed
				const removedSlideId = oldOrder.find( ( id ) => ! currentSlideOrder.includes( id ) );
				if ( removedSlideId ) {
				window.requestAnimationFrame( () => {
						slider.update();
						
						// CRITICAL: Force pagination update for removed slides
						if ( slider.pagination && slider.pagination.update ) {
							slider.pagination.update();
						}
						
						// Adjust active index if needed
						const newIndex = Math.min( activeIndex, currentSlideOrder.length - 1 );
						slider.slideTo( Math.max( 0, newIndex ), 0 );
					} );
				}
			} else if ( slideMoved && selectedBlock ) {
				// Handle reordering with comprehensive Swiper update and DOM sync
				window.requestAnimationFrame( () => {
					// Force a complete re-sync of Swiper with the DOM
					slider.updateSize();
					slider.updateSlides();
					slider.updateProgress();
					slider.update();
					
					// CRITICAL: Force pagination update for moved slides
					if ( slider.pagination && slider.pagination.update ) {
						slider.pagination.update();
					}
					
					// Force Swiper to re-read the DOM structure
					// Re-initialize slide data
					slider.updateSize();
					slider.updateSlides();
					slider.updateProgress();
					
					// Find the new position of the moved slide
					const newIndex = currentSlideOrder.findIndex(
						( id ) => id === selectedBlock.clientId
					);
					
					if ( newIndex >= 0 ) {
						// Navigate to the moved slide immediately
						slider.slideTo( newIndex, 0 );
					} else {
						// Fallback: go to first slide if we can't find the selected block
						slider.slideTo( 0, 0 );
					}
					
					// Force all slides to be properly styled after DOM changes
					const slideElements = slider.slides;
					if ( slideElements && slideElements.length > 0 ) {
						Array.from( slideElements ).forEach( slide => {
							// Trigger reflow to ensure visibility
							void slide.offsetHeight;
							// Reset any problematic styles
							slide.style.visibility = 'visible';
							slide.style.opacity = '1';
						} );
					}
					
					// Final comprehensive update after all changes
					slider.update();
				} );
			}
		}
	}, [ currentSlideOrder ] );

	// PERFORMANCE FIX: Cleanup effect to prevent memory leaks
	useEffect( () => {
		return () => {
			// Clear navigation timeout on component unmount
			if ( navigationTimeoutRef.current ) {
				clearTimeout( navigationTimeoutRef.current );
			}
		};
	}, [] );
	
	/**
	 * Updates the slider with minimal DOM manipulation.
	 *
	 * @param {Object} swiperInstance The Swiper instance.
	 * @param {string} activeSlideId   The active slide ID.
	 * @param {boolean} isNewSlider    Whether this is a newly initialized slider.
	 * @param {Element} swiperEl       The swiper element.
	 */
	const updateSliderWithAnimation = ( swiperInstance, activeSlideId, isNewSlider, swiperEl ) => {
		if ( ! swiperInstance || swiperInstance.destroyed ) return;
		
		// First update the slider.
		swiperInstance.updateSize();
		swiperInstance.updateSlides();
		swiperInstance.updateProgress();
		swiperInstance.update();
		
		// Update pagination if it exists.
		if ( swiperInstance.pagination && swiperInstance.pagination.update ) {
			swiperInstance.pagination.update();
		}
		
		// Now fix the active index immediately.
		if ( ! swiperInstance || swiperInstance.destroyed ) return;
		
		// Special handling for new sliders.
		if ( isNewSlider ) {
			// For new sliders, ensure we're at the first slide.
			swiperInstance.slideTo( 0, 0, false );
			
			// Force a more thorough update for new sliders.
			swiperInstance.updateSize();
			swiperInstance.updateSlides();
			swiperInstance.updateProgress();
			
			// Force browser to recalculate layout.
			const slideElements = swiperEl.querySelectorAll( '.swiper-slide' );
			if ( slideElements && slideElements.length > 0 ) {
				Array.from( slideElements ).forEach( slide => {
					// Trigger reflow.
					void slide.offsetHeight;
					
					// Reset any problematic styles.
					slide.style.width = '';
					slide.style.visibility = 'visible';
					slide.style.opacity = '1';
				} );
			}
			
			// Final update after style changes.
			swiperInstance.update();
			return;
		}
		
		// Regular handling for existing sliders.
		if ( activeSlideId ) {
			// Find the slide by its data-block attribute.
			const slides = swiperInstance.slides;
			if ( slides && slides.length ) {
				for ( let i = 0; i < slides.length; i++ ) {
					if ( slides[i].getAttribute( 'data-block' ) === activeSlideId ) {
						// Found the previously active slide, navigate to it
						swiperInstance.slideTo( i, 0, false );
						break;
					}
				}
			}
		} else {
			// Fallback: ensure we're not on an invalid index.
			const slideCount = swiperInstance.slides ? swiperInstance.slides.length : 0;
			if ( slideCount > 0 && swiperInstance.activeIndex >= slideCount ) {
				// If current index is invalid, go to the last valid slide
				swiperInstance.slideTo( slideCount - 1, 0, false );
			} else if ( slideCount > 0 ) {
				// Ensure we're at a valid slide.
				swiperInstance.slideTo( Math.min( swiperInstance.activeIndex, slideCount - 1 ), 0, false );
			}
		}
		
		// Final update to ensure everything is in sync.
		swiperInstance.update();
	};

	// Handle slidesPerView changes efficiently.
	useEffect( () => {
		// Find the Swiper instance.
		const swiperEl = document.querySelector( `.block-${clientId} .swiper` );
		const swiperInstance = swiperEl?.__swiper;
		
		if ( ! swiperInstance || swiperInstance.destroyed ) return;
		
		// Get current active slide and total slides before updating.
		const activeSlide = swiperInstance.slides ? swiperInstance.slides[ swiperInstance.activeIndex ] : null;
		const activeSlideId = activeSlide ? activeSlide.getAttribute( 'data-block' ) : null;
		
		// Check if this is a new slider that needs special handling.
		const isNewSlider = swiperInstance._isNewlyInitialized === true;
		if ( isNewSlider ) {
			// Clear the flag
			swiperInstance._isNewlyInitialized = false;
		}
		
		// Update slidesPerView without destroying the slider.
		swiperInstance.params.slidesPerView = parseFloat( effectiveSlidesPerView || 1 );
		
		// Update the slider with minimal DOM manipulation and fix active index.
		window.requestAnimationFrame( () => {
			updateSliderWithAnimation( swiperInstance, activeSlideId, isNewSlider, swiperEl );
		} );
	}, [slidesPerView, clientId] );
	
	// Handle spaceBetween changes.
	useEffect( () => {
		// Find the Swiper instance.
		const swiperEl = document.querySelector( `.block-${clientId} .swiper` );
		const swiperInstance = swiperEl?.__swiper;
		
		if ( !swiperInstance || swiperInstance.destroyed ) return;
		
		// Update spaceBetween without destroying the slider.
		swiperInstance.params.spaceBetween = getSpaceBetween();
		
		// Update the slider with minimal DOM manipulation.
		window.requestAnimationFrame( () => {
			if ( !swiperInstance || swiperInstance.destroyed ) return;
			
			// Update sizes and positions without recreating slides.
			swiperInstance.updateSize();
			swiperInstance.updateSlides();
			swiperInstance.updateProgress();
			swiperInstance.update();
			
			// Update pagination if it exists.
			if ( swiperInstance.pagination && swiperInstance.pagination.update ) {
				swiperInstance.pagination.update();
			}
		} );
	}, [spaceBetween, clientId] );

	// Handle navigation and pagination updates when they change
	useEffect( () => {
		// Find the Swiper instance.
		const swiperEl = document.querySelector( `.block-${clientId} .swiper` );
		const swiperInstance = swiperEl?.__swiper;
		
		if ( ! swiperInstance || swiperInstance.destroyed ) return;
		
		// Detect iframe context for proper element targeting
		const getEditorIframe = () => document.querySelector( 'iframe[name="editor-canvas"]' );
		const getEditorDocument = () => {
			const editorIframe = getEditorIframe();
			return editorIframe?.contentDocument || editorIframe?.contentWindow?.document || document;
		};

		const targetDoc = getEditorDocument();

		// Update navigation elements
		if ( navigation && swiperInstance.navigation ) {
			const nextEl = targetDoc.querySelector( `.block-${ clientId } .swiper-button-next` );
			const prevEl = targetDoc.querySelector( `.block-${ clientId } .swiper-button-prev` );
			
			if ( nextEl && prevEl ) {
				swiperInstance.navigation.nextEl = nextEl;
				swiperInstance.navigation.prevEl = prevEl;
				swiperInstance.navigation.init();
				swiperInstance.navigation.update();
			}
		}

		// Update pagination elements  
		if ( pagination && swiperInstance.pagination ) {
			const paginationEl = targetDoc.querySelector( `.block-${ clientId } .swiper-pagination` );
			
			if ( paginationEl ) {
				swiperInstance.pagination.el = paginationEl;
				swiperInstance.pagination.init();
				swiperInstance.pagination.update();
			}
		}

		// Force update to ensure everything is connected
		window.requestAnimationFrame( () => {
			if ( swiperInstance && ! swiperInstance.destroyed ) {
				swiperInstance.update();
			}
		} );
	}, [navigation, pagination, navigationPrevIcon, navigationNextIcon, clientId] );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'swiper-wrapper',
			style: {
				height: 'auto',
			},
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: [ [ 'spectra/slider-child' ], [ 'spectra/slider-child' ], [ 'spectra/slider-child' ] ],
			renderAppender: false,
			orientation: 'horizontal',
			templateInsertUpdatesSelection: false,
		}
	);

	return (
		<>
			{ isSelected && <RenderToolbar /> }
			<div { ...blockProps }>
				<VideoBackground { ...{ background } } />
				<div className={ `spectra-slider-container block-${ clientId }` }>
					<div
						className="swiper"
						ref={ sliderRef }
						style={ { 
							height: sliderHeight || 'auto',
							overflow,
						} }
					>
						<div { ...innerBlocksProps } />
					</div>
					{ navigation && (
						<>
							<div className="swiper-button-prev">
								<RenderSVG svg={ navigationPrevIcon || 'arrow-left' } />
							</div>
							<div className="swiper-button-next">
								<RenderSVG svg={ navigationNextIcon || 'arrow-right' } />
							</div>
						</>
					) }
					{ pagination && <div className="swiper-pagination" />}
				</div>
			</div>
		</>
	);
} );


export default  Render;