/**
 * Post Block - Frontend Interactivity
 *
 * Handles client-side interactivity for the Post block using WordPress Interactivity API.
 * Manages carousel initialization, pagination navigation, and load more functionality.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { store, getContext, getElement, withSyncEvent } from '@wordpress/interactivity';

/**
 * External dependencies
 */
import { Navigation, Pagination, Autoplay, A11y } from 'swiper/modules';
import Swiper from 'swiper';

/**
 * Validates if a link is suitable for client-side navigation.
 *
 * @param {HTMLElement} ref - The link element to validate.
 * @return {boolean} True if the link is valid for navigation.
 */
const isValidLink = ( ref ) =>
	ref &&
	ref instanceof window.HTMLAnchorElement &&
	ref.href &&
	( ! ref.target || ref.target === '_self' ) &&
	ref.origin === window.location.origin;

/**
 * Validates if an event is suitable for client-side navigation.
 *
 * @param {Event} event - The event to validate.
 * @return {boolean} True if the event should trigger client-side navigation.
 */
const isValidEvent = ( event ) =>
	event.button === 0 && // Left clicks only.
	! event.metaKey && // Open in new tab (Mac).
	! event.ctrlKey && // Open in new tab (Windows).
	! event.altKey && // Download.
	! event.shiftKey &&
	! event.defaultPrevented;

/**
 * Spectra Post Block Interactivity Store.
 *
 * Handles:
 * - Carousel initialization with Swiper
 * - AJAX pagination (replace posts)
 * - Load more button pagination (append posts)
 *
 * @since x.x.x
 */
store(
	'spectra/post',
	{
		state: {
			get isLoading() {
				const context = getContext( 'spectra/post' );
				return context.isLoading;
			},
			get hasMore() {
				const context = getContext( 'spectra/post' );
				return context.hasMore;
			},
		},

		actions: {
			/**
			 * Handles navigation when a pagination link is clicked.
			 * Uses Interactivity Router for client-side navigation.
			 *
			 * @param {Event} e - The click event.
			 */
			navigate: withSyncEvent( function* ( e ) {
				const { ref } = getElement();
				const context = getContext( 'spectra/post' );

				if ( isValidLink( ref ) && isValidEvent( e ) ) {
					e.preventDefault();

					const { actions } = yield import( '@wordpress/interactivity-router' );
					yield actions.navigate( ref.href );
					context.url = ref.href;

					// Focus the first link in the post template after navigation.
					const queryRef = ref.closest( '[data-wp-router-region]' );
					const firstLink = queryRef?.querySelector( '.spectra-post-loop-wrapper a[href]' );
					firstLink?.focus();
				}
			} ),

			/**
			 * Prefetches a link's content on hover or focus.
			 */
			*prefetch() {
				const { ref } = getElement();
				if ( isValidLink( ref ) ) {
					const { actions } = yield import( '@wordpress/interactivity-router' );
					yield actions.prefetch( ref.href );
				}
			},

			/**
			 * Load more posts for button pagination.
			 * Uses HTML fetching to avoid REST API dependency.
			 */
			*loadMore() {
				const context = getContext( 'spectra/post' );

				if ( ! context.hasMore || context.isLoading ) {
					return;
				}

				const { ref } = getElement();

				context.isLoading = true;
				yield;

				try {
					const nextPage = context.currentPage + 1;
					// Construct URL for next page using query-specific parameter (e.g., query-abc123-page).
					const url = new URL( window.location.href );
					url.searchParams.set( context.pageKey, nextPage );

					const response = yield fetch( url.href );
					if ( ! response.ok ) {throw new Error( 'Network response was not ok' );}

					const html = yield response.text();
					const parser = new DOMParser();
					const doc = parser.parseFromString( html, 'text/html' );

					// Find the current block's router region to match in the new content.
					const routerRegion = ref.closest( '[data-wp-router-region]' );
					const regionId = routerRegion?.dataset?.wpRouterRegion;

					if ( regionId ) {
						const newContainer = doc.querySelector( `[data-wp-router-region="${ regionId }"]` );
						const newLoopWrapper = newContainer?.querySelector( '.spectra-post-loop-wrapper' );

						if ( newLoopWrapper ) {
							// Get the current loop wrapper.
							const loopWrapper = routerRegion.querySelector( '.spectra-post-loop-wrapper' );
							const targetWrapper = loopWrapper.querySelector( '.swiper-wrapper' ) || loopWrapper;

							// Append new posts.
							const newItems = newLoopWrapper.innerHTML;
							targetWrapper.insertAdjacentHTML( 'beforeend', newItems );

							// Update swiper if carousel.
							if ( context.layoutType === 'carousel' ) {
								const container = ref.closest( '[data-wp-interactive="spectra/post"]' );
								if ( container.swiperInstance ) {
									container.swiperInstance.update();
								}
							}

							// Update context.
							context.currentPage = nextPage;
							context.hasMore = nextPage < context.totalPages;
						}
					}
				} catch ( error ) {
					context.hasMore = false; // Stop trying on error.
				} finally {
					context.isLoading = false;
				}
			},
		},

		callbacks: {
			/**
			 * Prefetches content based on context URL on initialization or update.
			 * This is called by data-wp-watch for pagination links.
			 */
			*prefetch() {
				const { url } = getContext( 'spectra/post' );
				const { ref } = getElement();

				if ( url && isValidLink( ref ) ) {
					const { actions } = yield import( '@wordpress/interactivity-router' );
					yield actions.prefetch( ref.href );
				}
			},

			/**
			 * Initialize infinite scroll observer.
			 * Uses IntersectionObserver for better performance than scroll events.
			 */
			initInfiniteScroll() {
				const context = getContext( 'spectra/post' );
				const { ref } = getElement();

				// Cleanup existing observer if pagination type changed from infinite.
				if ( ref.intersectionObserver && context.paginationType !== 'infinite' ) {
					ref.intersectionObserver.disconnect();
					ref.intersectionObserver = null;
					return;
				}

				// Only initialize for infinite scroll (which always auto-loads).
				if ( context.paginationType !== 'infinite' ) {
					return;
				}

				// Prevent duplicate observers.
				if ( ref.intersectionObserver ) {
					return;
				}

				// Observe the loader element.
				const loaderElement = ref.querySelector( '.spectra-post-inf-loader' );

				/**
				 * Internal async function to load more posts.
				 * Extracted from loadMore action for reuse.
				 */
				const loadMorePosts = async () => {
					if ( ! context.hasMore || context.isLoading ) {
						return;
					}

					context.isLoading = true;

					try {
						const nextPage = context.currentPage + 1;
						const url = new URL( window.location.href );
						url.searchParams.set( context.pageKey, nextPage );

						const response = await fetch( url.href );
						if ( ! response.ok ) {throw new Error( 'Network response was not ok' );}

						const html = await response.text();
						const parser = new DOMParser();
						const doc = parser.parseFromString( html, 'text/html' );

						const routerRegion = ref.closest( '[data-wp-router-region]' );
						const regionId = routerRegion?.dataset?.wpRouterRegion;

						if ( regionId ) {
							const newContainer = doc.querySelector( `[data-wp-router-region="${ regionId }"]` );
							const newLoopWrapper = newContainer?.querySelector( '.spectra-post-loop-wrapper' );

							if ( newLoopWrapper ) {
								const loopWrapper = routerRegion.querySelector( '.spectra-post-loop-wrapper' );
								const targetWrapper = loopWrapper.querySelector( '.swiper-wrapper' ) || loopWrapper;

								const newItems = newLoopWrapper.innerHTML;
								targetWrapper.insertAdjacentHTML( 'beforeend', newItems );

								// Update swiper if carousel.
								if ( context.layoutType === 'carousel' ) {
									const container = ref.closest( '[data-wp-interactive="spectra/post"]' );
									if ( container.swiperInstance ) {
										container.swiperInstance.update();
									}
								}

								context.currentPage = nextPage;
								context.hasMore = nextPage < context.totalPages;
							}
						}
					} catch ( error ) {
						context.hasMore = false;
					} finally {
						context.isLoading = false;
						// Recursively check if we need to load more (in case screen isn't full yet)
						if ( context.hasMore && loaderElement ) {
							const rect = loaderElement.getBoundingClientRect();
							if ( rect.top < window.innerHeight + 200 ) {
								loadMorePosts();
							}
						}
					}
				};

				// Create observer that triggers when loader comes into view.
				const observer = new IntersectionObserver(
					( entries ) => {
						entries.forEach( ( entry ) => {
							if ( entry.isIntersecting && context.hasMore && ! context.isLoading ) {
								loadMorePosts();
							}
						} );
					},
					{
						root: null,
						rootMargin: '200px',
						threshold: 0.01,
					}
				);

				if ( loaderElement ) {
					observer.observe( loaderElement );
					ref.intersectionObserver = observer;
				}
			},

			/**
			 * Initialize carousel with Swiper.
			 */
			initCarousel() {
				const context = getContext( 'spectra/post' );
				const { ref } = getElement();

				// Cleanup existing Swiper instance if layout changed from carousel.
				if ( ref.swiperInstance && context.layoutType !== 'carousel' ) {
					ref.swiperInstance.destroy( true, true );
					ref.swiperInstance = null;
					return;
				}

				// Only initialize for carousel layout.
				if ( context.layoutType !== 'carousel' ) {
					return;
				}

				// Prevent duplicate initialization.
				if ( ref.swiperInstance ) {
					return;
				}

				// Parse Swiper config from data attribute.
				let config = {};
				try {
					config = JSON.parse( ref.dataset.swiper || '{}' );
				} catch ( error ) {
					return;
				}

				// Get the actual swiper element (child of ref).
				const swiperElement = ref.querySelector( '.swiper' );
				if ( ! swiperElement ) {
					return;
				}

				// Get the parent carousel container (where arrows and pagination are siblings).
				const carouselContainer = ref.closest( '.spectra-post-layout-carousel' );

				// Build Swiper configuration - query navigation from parent container.
				const swiperConfig = {
					modules: [ Navigation, Pagination, Autoplay, A11y ],
					...config,
					navigation: config.navigation
						? {
								nextEl: carouselContainer.querySelector( '.swiper-button-next' ),
								prevEl: carouselContainer.querySelector( '.swiper-button-prev' ),
						  }
						: false,
					pagination: config.pagination
						? {
								el: carouselContainer.querySelector( '.swiper-pagination' ),
								clickable: true,
						  }
						: false,
				};

				// Initialize Swiper on the swiper element.
				try {
					const swiper = new Swiper( swiperElement, swiperConfig );
					ref.swiperInstance = swiper;
				} catch ( error ) {}
			},
		},
	},
	{ lock: true }
);
