/**
 * Responsive Videos Handler.
 *
 * Handles switching video sources based on viewport size for responsive video backgrounds.
 * Works with both container and slider blocks that have responsive video backgrounds.
 *
 * @since x.x.x
 */

( function () {
	'use strict';

	// Fallback only: Spectra's historical breakpoints, used when the bands the
	// stylesheet was generated with are not on the page.
	const FALLBACK_BREAKPOINTS = {
		'base': 1024,      // Desktop: 1024px and above.
		'@tablet': 768,  // Tablet: 768px to 1023.98px.
		'@mobile': 0,    // Mobile: 0px to 767.98px.
	};

	/**
	 * Get the device whose band the viewport is in right now.
	 *
	 * The bands are the media queries the per-device CSS was generated with,
	 * published by the plugin as `window.spectraBlocksViewportBands`
	 * ({ desktop, tablet, mobile }). Matching against them keeps this switch in
	 * step with the stylesheet whatever the breakpoints are — core's defaults,
	 * the theme's, or Spectra's. The width comparison is only reached when that
	 * data is absent.
	 *
	 * @return {string} Device key: 'base', '@tablet', or '@mobile'.
	 */
	function getCurrentDevice() {
		const bands = window.spectraBlocksViewportBands;

		if ( bands && typeof window.matchMedia === 'function' ) {
			const mobile = bands.mobile ? window.matchMedia( bands.mobile ) : null;
			const tablet = bands.tablet ? window.matchMedia( bands.tablet ) : null;
			// A query this browser cannot parse reports `not all`; fall back to widths.
			const parsable = ( mq ) => mq && mq.media !== 'not all';
			if ( parsable( mobile ) && parsable( tablet ) ) {
				if ( mobile.matches ) {
					return '@mobile';
				}
				if ( tablet.matches ) {
					return '@tablet';
				}
				return 'base';
			}
		}

		const width = window.innerWidth;

		if ( width >= FALLBACK_BREAKPOINTS.base ) {
			return 'base';
		} else if ( width >= FALLBACK_BREAKPOINTS[ '@tablet' ] ) {
			return '@tablet';
		}
		return '@mobile';
	}

	/**
	 * Update video source based on current device and available responsive videos.
	 *
	 * @param {HTMLElement} container The container element with data-responsive-videos attribute.
	 */
	function updateVideoSource( container ) {
		const video = container.querySelector( '.spectra-background-video__wrapper video' );

		if ( ! video ) {
			return;
		}

		const responsiveVideosData = container.getAttribute( 'data-responsive-videos' );

		if ( ! responsiveVideosData ) {
			return;
		}

		let responsiveVideos;
		try {
			responsiveVideos = JSON.parse( responsiveVideosData );
		} catch ( error ) {
			return;
		}

		const currentDevice = getCurrentDevice();
		
		// Check if device actually changed.
		const lastDevice = container.getAttribute( 'data-last-device' );
		if ( lastDevice === currentDevice ) {
			return;
		}
		
		// Each viewport resolves over base only — core's inheritance model,
		// the same one the CSS generator follows. Legacy tablet-only videos
		// reach '@mobile' through the baked cascade before PHP emits the JSON.
		const fallbackOrder = {
			'@mobile': [ '@mobile', 'base' ],
			'@tablet': [ '@tablet', 'base' ],
			'base': [ 'base' ],
		};

		// Find the appropriate video URL using fallback hierarchy.
		let videoUrl = null;
		const deviceOrder = fallbackOrder[ currentDevice ] || [ 'base' ];

		for ( const device of deviceOrder ) {
			// An explicit empty entry means "this band has no video" (its
			// background is an image or none) and must not fall back to base.
			if ( Object.prototype.hasOwnProperty.call( responsiveVideos, device ) ) {
				videoUrl = responsiveVideos[ device ] || null;
				break;
			}
		}

		const source = video.querySelector( 'source' );
		const currentSrc = source ? source.getAttribute( 'src' ) : video.getAttribute( 'src' );

		if ( ! videoUrl ) {
			// This band has no video. The element is still in the DOM (hidden by
			// the band's CSS), so stop it and drop its source: with a source
			// attached the browser keeps the file buffered for a width that
			// never shows it.
			video.pause();
			if ( currentSrc ) {
				if ( source ) {
					source.removeAttribute( 'src' );
				} else {
					video.removeAttribute( 'src' );
				}
				video.load();
			}
		} else {
			if ( currentSrc !== videoUrl ) {
				if ( source ) {
					source.src = videoUrl;
				} else {
					video.src = videoUrl;
				}
				video.load();
			}
			// PHP renders without `autoplay` (and with `preload="none"`) when some
			// band has no video, so the browser does not fetch the file for a
			// width that never shows it; this band does, so start it here.
			if ( video.paused ) {
				const playing = video.play();
				if ( playing && 'function' === typeof playing.catch ) {
					playing.catch( function () {} );
				}
			}
		}

		// Update the last device.
		container.setAttribute( 'data-last-device', currentDevice );
	}

	/**
	 * Initialize responsive videos for all elements on the page.
	 */
	function initResponsiveVideos() {
		const containers = document.querySelectorAll( '[data-responsive-videos]' );

		containers.forEach( function ( container ) {
			updateVideoSource( container );
		} );
	}

	/**
	 * Handle window resize events with debouncing.
	 */
	let resizeTimeout;
	function handleResize() {
		clearTimeout( resizeTimeout );
		resizeTimeout = setTimeout( function () {
			initResponsiveVideos();
		}, 250 ); // 250ms debounce.
	}

	/**
	 * Initialize when DOM is ready.
	 */
	function init() {
		// Apply the viewport's own video straight away. PHP cannot know the
		// viewport and always emits the base source; skipping this step (as an
		// earlier version did, to avoid a flicker) left tablet and phone visitors
		// on the desktop video until a resize crossed a band. The swap is a no-op
		// at desktop widths, so nothing flickers there.
		initResponsiveVideos();

		// Listen for window resize events.
		window.addEventListener( 'resize', handleResize );

		// Also listen for orientation change on mobile devices.
		window.addEventListener( 'orientationchange', function () {
			setTimeout( initResponsiveVideos, 500 );
		} );
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
