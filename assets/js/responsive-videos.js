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

	// Spectra breakpoints (matching the responsive controls system).
	const BREAKPOINTS = {
		lg: 1024, // Desktop: 1024px and above.
		md: 768,  // Tablet: 768px to 1023.98px.
		sm: 0,    // Mobile: 0px to 767.98px.
	};

	/**
	 * Get current device type based on viewport width.
	 *
	 * @return {string} Device type: 'lg', 'md', or 'sm'.
	 */
	function getCurrentDevice() {
		const width = window.innerWidth;

		if ( width >= BREAKPOINTS.lg ) {
			return 'lg';
		} else if ( width >= BREAKPOINTS.md ) {
			return 'md';
		}

		return 'sm';
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
		
		const fallbackOrder = {
			sm: [ 'sm', 'md', 'lg' ], // Mobile: mobile -> tablet -> desktop.
			md: [ 'md', 'lg' ], // Tablet: tablet -> desktop.
			lg: [ 'lg' ], // Desktop: desktop only.
		};

		// Find the appropriate video URL using fallback hierarchy.
		let videoUrl = null;
		const deviceOrder = fallbackOrder[ currentDevice ] || [ 'lg' ];

		for ( const device of deviceOrder ) {
			if ( responsiveVideos[ device ] ) {
				videoUrl = responsiveVideos[ device ];
				break;
			}
		}

		// Only update if URL is different from current source.
		const source = video.querySelector( 'source' );
		const currentSrc = source ? source.src : video.src;
		
		if ( videoUrl && currentSrc !== videoUrl ) {
			// Update source.
			if ( source ) {
				source.src = videoUrl;
			} else {
				video.src = videoUrl;
			}
			
			// Reload video.
			video.load();
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
		// Skip initial setup to prevent flicker on page load.
		// Videos will use their default source from PHP.
		
		// Mark all containers as initialized with current device.
		const containers = document.querySelectorAll( '[data-responsive-videos]' );
		const currentDevice = getCurrentDevice();
		containers.forEach( function ( container ) {
			container.setAttribute( 'data-last-device', currentDevice );
		} );

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
