/**
 * External dependencies.
 */
import { CountUp } from 'countup.js';

/**
 * Counter Animation Manager
 * 
 * Handles animated counting and progress visualization for counter blocks.
 * Uses CountUp.js for smooth, reliable animations.
 * 
 * @since x.x.x
 */


/**
 * Spectra Counter Class
 * Integrates CountUp.js with progress bar synchronization
 */
class SpectraCounter {
	constructor( element, options = {} ) {
		this.element = element;
		this.options = {
			startNumber: 0,
			endNumber: 100,
			totalNumber: 100,
			duration: 2000,
			prefix: '',
			suffix: '',
			separator: ',',
			decimals: 0,
			...options
		};
		
		// Find counter elements
	const valueElement = element.querySelector( '.spectra-counter-value' );
		this.counterElement = valueElement || element;
		this.hasNewStructure = !! valueElement;
		this.prefixElement = this.hasNewStructure ? element.querySelector( '.spectra-counter-prefix' ) : null;
		this.suffixElement = this.hasNewStructure ? element.querySelector( '.spectra-counter-suffix' ) : null;
		
		// Find progress elements - check if element is already the counter block or find parent
		let counterBlock = element.closest( '.wp-block-spectra-counter' );
		if ( ! counterBlock && element.classList.contains( 'wp-block-spectra-counter' ) ) {
			counterBlock = element;
		}
		
		if ( counterBlock ) {
			this.circularProgress = counterBlock.querySelector( '.spectra-counter-progress-circle' );
			this.barProgress = counterBlock.querySelector( '.spectra-counter-progress-bar' );
			this.barLabel = counterBlock.querySelector( '.spectra-counter-progress-label' );
			
			// Get individual elements for prefix, number, and suffix in progress bar
			if ( this.barLabel ) {
				this.barPrefix = this.barLabel.querySelector( '.spectra-counter-prefix' );
				this.barValue = this.barLabel.querySelector( '.spectra-counter-value' );
				this.barSuffix = this.barLabel.querySelector( '.spectra-counter-suffix' );
			}
		}
		
		// Calculate circular progress values if needed
		if ( this.circularProgress ) {
			const radius = this.circularProgress.r.baseVal.value;
			this.circumference = 2 * Math.PI * radius;
			this.circularProgress.style.strokeDasharray = this.circumference;
			
			// Calculate initial stroke-dashoffset based on startNumber
			let totalNumber = 100;
			if ( this.options.totalNumber && this.options.totalNumber > 0 ) {
				totalNumber = this.options.totalNumber;
			} else if ( this.options.endNumber && this.options.endNumber > 0 ) {
				totalNumber = this.options.endNumber;
			}
			const safeStartNumber = isNaN( this.options.startNumber ) ? 0 : this.options.startNumber;
			const safeEndNumber = isNaN( this.options.endNumber ) ? 100 : this.options.endNumber;
			let safeTotalNumber = isNaN( totalNumber ) ? 100 : totalNumber;
			
			// If total is smaller than both start and end numbers, use the larger number as effective total
			if ( safeTotalNumber < Math.max( Math.abs( safeStartNumber ), Math.abs( safeEndNumber ) ) ) {
				safeTotalNumber = Math.max( Math.abs( safeStartNumber ), Math.abs( safeEndNumber ) );
			}
			let initialPoint = 100 * ( safeStartNumber / safeTotalNumber );
			initialPoint = Math.max( 0, Math.min( 100, initialPoint ) );
			initialPoint = 100 - initialPoint;
			const initialOffset = ( initialPoint / 100 ) * this.circumference;
			
			this.circularProgress.style.strokeDashoffset = initialOffset;
		}
		
		// Initialize bar progress
		if ( this.barProgress ) {
			// Calculate initial width based on startNumber
			let totalNumber = 100;
			if ( this.options.totalNumber && this.options.totalNumber > 0 ) {
				totalNumber = this.options.totalNumber;
			} else if ( this.options.endNumber && this.options.endNumber > 0 ) {
				totalNumber = this.options.endNumber;
			}
			const safeStartNumber = isNaN( this.options.startNumber ) ? 0 : this.options.startNumber;
			const safeEndNumber = isNaN( this.options.endNumber ) ? 100 : this.options.endNumber;
			let safeTotalNumber = isNaN( totalNumber ) ? 100 : totalNumber;
			
			// If total is smaller than both start and end numbers, use the larger number as effective total
			if ( safeTotalNumber < Math.max( Math.abs( safeStartNumber ), Math.abs( safeEndNumber ) ) ) {
				safeTotalNumber = Math.max( Math.abs( safeStartNumber ), Math.abs( safeEndNumber ) );
			}
			let initialWidth = Math.round( ( safeStartNumber / safeTotalNumber ) * 100 );
			initialWidth = Math.max( 0, Math.min( 100, initialWidth ) );
			
			this.barProgress.style.width = `${initialWidth}%`;
			if ( this.barLabel ) {
				this.updateBarLabel( this.options.startNumber );
			}
		}
		
		// Set up prefix/suffix if using new structure
		if ( this.hasNewStructure ) {
			if ( this.prefixElement && this.options.prefix ) {
				this.prefixElement.textContent = this.options.prefix;
			}
			if ( this.suffixElement && this.options.suffix ) {
				this.suffixElement.textContent = this.options.suffix;
			}
		}
		
		// Create CountUp instance only if we have a counter element
		this.countUp = null;
		if ( this.counterElement && ( valueElement || ! element.classList.contains( 'wp-block-spectra-counter' ) ) ) {
			const countUpOptions = {
				startVal: this.options.startNumber,
				duration: this.options.duration / 1000, // CountUp uses seconds
				separator: this.options.separator,
				decimal: '.',
				decimalPlaces: this.options.decimals,
				prefix: this.hasNewStructure ? '' : this.options.prefix,
				suffix: this.hasNewStructure ? '' : this.options.suffix,
				useEasing: false, // Use linear animation for consistent speed
				useGrouping: true,
			};
			
			this.countUp = new CountUp( this.counterElement, this.options.endNumber, countUpOptions );
		}
	}
	
	animateCircularProgress() {
		if ( ! this.circularProgress ) {
			return;
		}
		
		// Calculate progress percentages - support both increment and decrement
		// Ensure totalNumber defaults to 100 if not set, empty, or invalid
		let totalNumber = 100;
		if ( this.options.totalNumber && this.options.totalNumber > 0 ) {
			totalNumber = this.options.totalNumber;
		} else if ( this.options.endNumber && this.options.endNumber > 0 ) {
			totalNumber = this.options.endNumber;
		}
		
		// Add safeguards to prevent NaN in calculations
		const safeStartNumber = isNaN( this.options.startNumber ) ? 0 : this.options.startNumber;
		const safeEndNumber = isNaN( this.options.endNumber ) ? 100 : this.options.endNumber;
		let safeTotalNumber = isNaN( totalNumber ) ? 100 : totalNumber;
		
		// If total is smaller than both start and end numbers, use the larger number as effective total
		// This prevents cases like start=7000, end=6000, total=100 showing 100% progress
		if ( safeTotalNumber < Math.max( Math.abs( safeStartNumber ), Math.abs( safeEndNumber ) ) ) {
			safeTotalNumber = Math.max( Math.abs( safeStartNumber ), Math.abs( safeEndNumber ) );
		}
		
		// Calculate progress percentages based on totalNumber
		// For normal cases: start=3500, end=5000, total=5000 → start=70%, end=100%
		// For reverse cases: start=5000, end=3000, total=5000 → start=100%, end=60%
		let startPoint = 100 * ( safeStartNumber / safeTotalNumber );
		startPoint = Math.max( 0, Math.min( 100, startPoint ) ); // Clamp to 0-100%
		startPoint = 100 - startPoint;
		startPoint = ( startPoint / 100 ) * this.circumference;
		
		let endPoint = 100 * ( safeEndNumber / safeTotalNumber );
		endPoint = Math.max( 0, Math.min( 100, endPoint ) ); // Clamp to 0-100%
		endPoint = 100 - endPoint;
		endPoint = ( endPoint / 100 ) * this.circumference;
		
		// Use Web Animations API for smooth animation
		const animationKeyframes = [
			{ strokeDashoffset: `${startPoint}px` },
			{ strokeDashoffset: `${endPoint}px` }
		];
		
		const animationProperties = {
			duration: this.options.duration,
			fill: 'forwards',
			easing: 'linear'
		};
		
		this.circularProgress.animate( animationKeyframes, animationProperties );
	}
	
	animateBarProgress() {
		if ( ! this.barProgress ) {
			return;
		}
		
		// Calculate start and end width percentages - support both increment and decrement
		// Ensure totalNumber defaults to 100 if not set, empty, or invalid
		let totalNumber = 100;
		if ( this.options.totalNumber && this.options.totalNumber > 0 ) {
			totalNumber = this.options.totalNumber;
		} else if ( this.options.endNumber && this.options.endNumber > 0 ) {
			totalNumber = this.options.endNumber;
		}
		
		// Add safeguards to prevent NaN in calculations
		const safeStartNumber = isNaN( this.options.startNumber ) ? 0 : this.options.startNumber;
		const safeEndNumber = isNaN( this.options.endNumber ) ? 100 : this.options.endNumber;
		let safeTotalNumber = isNaN( totalNumber ) ? 100 : totalNumber;
		
		// If total is smaller than both start and end numbers, use the larger number as effective total
		// This prevents cases like start=7000, end=6000, total=100 showing 100% progress
		if ( safeTotalNumber < Math.max( Math.abs( safeStartNumber ), Math.abs( safeEndNumber ) ) ) {
			safeTotalNumber = Math.max( Math.abs( safeStartNumber ), Math.abs( safeEndNumber ) );
		}
		
		// Calculate progress percentages based on totalNumber
		// For normal cases: start=3500, end=5000, total=5000 → start=70%, end=100%
		// For reverse cases: start=5000, end=3000, total=5000 → start=100%, end=60%
		let startWidth = Math.round( ( safeStartNumber / safeTotalNumber ) * 100 );
		let endWidth = Math.round( ( safeEndNumber / safeTotalNumber ) * 100 );
		
		// Ensure percentages are within valid range (0-100%)
		startWidth = Math.max( 0, Math.min( 100, startWidth ) );
		endWidth = Math.max( 0, Math.min( 100, endWidth ) );
		
		// Use Web Animations API for smooth animation
		const animationKeyframes = [
			{ width: `${startWidth}%` },
			{ width: `${endWidth}%` }
		];
		
		const animationProperties = {
			duration: this.options.duration,
			fill: 'forwards',
			easing: 'linear'
		};
		
		this.barProgress.animate( animationKeyframes, animationProperties );
		
		// Animate the label separately if it exists
		if ( this.barLabel ) {
			this.animateBarLabel();
		}
	}
	
	formatNumber( number ) {
		// Format number with decimal places and thousand separators
		const decimals = this.options.decimals || 0;
		const separator = this.options.separator;
		
		// Round to specified decimal places
		const fixedNumber = parseFloat( number ).toFixed( decimals );
		
		// Add thousand separators only if separator is defined and not empty
		if ( separator && separator.trim() !== '' ) {
			const parts = fixedNumber.split( '.' );
			parts[0] = parts[0].replace( /\B(?=(\d{3})+(?!\d))/g, separator );
			return parts.join( '.' );
		}
		
		return fixedNumber;
	}

	updateBarLabel( number ) {
		if ( ! this.barLabel ) {
			return;
		}

		const formattedNumber = this.formatNumber( number );

		// Update individual spans if they exist, otherwise fall back to textContent
		if ( this.barValue ) {
			this.barValue.textContent = formattedNumber;
		} else {
			// Fallback for old structure
			this.barLabel.textContent = `${this.options.prefix}${formattedNumber}${this.options.suffix}`;
		}
	}

	animateBarLabel() {
		if ( ! this.barLabel ) {
			return;
		}

		// Animate label from start number to end number
		const duration = this.options.duration;
		
		let startTime = null;
		
		const updateLabel = ( timestamp ) => {
			if ( ! startTime ) {
				startTime = timestamp;
			}
			
			const elapsed = timestamp - startTime;
		const progress = Math.min( elapsed / duration, 1 );
			
			// Apply linear easing
			const easedProgress = progress;
			const currentNumber = this.options.startNumber + ( this.options.endNumber - this.options.startNumber ) * easedProgress;
			
			// Bar label shows the counter number with prefix and suffix
			// Format the number with decimal places and thousand separators like the main counter
			this.updateBarLabel( currentNumber );
			
			if ( progress < 1 ) {
				requestAnimationFrame( updateLabel );
			}
		};
		
		requestAnimationFrame( updateLabel );
	}
	
	start() {
		// Start CountUp animation if available
		if ( this.countUp && ! this.countUp.error ) {
			this.countUp.start();
		}
		
		// Always start progress animations (even without counter number)
		this.animateCircularProgress();
		this.animateBarProgress();
	}
	
	reset() {
		if ( this.countUp && ! this.countUp.error ) {
			this.countUp.reset();
		}
		
		// Cancel any running animations
		if ( this.circularProgress ) {
			this.circularProgress.getAnimations().forEach( animation => animation.cancel() );
			this.circularProgress.style.strokeDashoffset = this.circumference;
		}
		
		if ( this.barProgress ) {
			this.barProgress.getAnimations().forEach( animation => animation.cancel() );
		this.barProgress.style.width = '0%';
		if ( this.barLabel ) {
			this.updateBarLabel( 0 );
		}
		}
	}
	
	destroy() {
		// Cancel any running animations
		if ( this.circularProgress ) {
			this.circularProgress.getAnimations().forEach( animation => animation.cancel() );
		}
		
		if ( this.barProgress ) {
			this.barProgress.getAnimations().forEach( animation => animation.cancel() );
		}
		
		// CountUp doesn't have a destroy method, but we can clean up
		if ( this.countUp ) {
			this.countUp = null;
		}
	}
}

/**
 * Initialize a single counter
 * 
 * @param {HTMLElement} element The counter element
 * @param {Object} options Animation options
 */
function initCounter( element, options ) {
	// Store counter instance on element for cleanup
	if ( element.spectraCounter ) {
		element.spectraCounter.destroy();
	}
	
	element.spectraCounter = new SpectraCounter( element, options );
	element.spectraCounter.start();
	
	return element.spectraCounter;
}

/**
 * Initialize counter animations using Intersection Observer
 */
function initCounterAnimations() {
	const counters = document.querySelectorAll( '.wp-block-spectra-counter:not(.spectra-counter-content-wrapper)' );
	
	if ( ! counters.length ) return;

	// Create intersection observer
	const observer = new IntersectionObserver( ( entries ) => {
		entries.forEach( ( entry ) => {
			if ( entry.isIntersecting ) {
				const counter = entry.target;

				// Get animation options from data attributes
				// Handle undefined/empty values properly - don't override valid zeros
				const startNum = counter.dataset.counterStart;
				const endNum = counter.dataset.counterEnd;
				const totalNum = counter.dataset.counterTotal;
				const decimalsNum = counter.dataset.counterDecimals;
				
				const options = {
					startNumber: startNum !== undefined && startNum !== '' ? parseFloat( startNum ) : 0,
					endNumber: endNum !== undefined && endNum !== '' ? parseFloat( endNum ) : 100,
					totalNumber: totalNum !== undefined && totalNum !== '' ? parseFloat( totalNum ) : 100,
					duration: parseInt( counter.dataset.counterDuration ) || 2000,
					prefix: counter.dataset.counterPrefix || '',
					suffix: counter.dataset.counterSuffix || '',
					separator: counter.dataset.counterSeparator !== undefined ? counter.dataset.counterSeparator : ',',
					decimals: decimalsNum !== undefined && decimalsNum !== '' ? parseInt( decimalsNum ) : 0,
					strokeWidth: parseInt( counter.dataset.strokeWidth ) || 8,
				};

				// Initialize counter with CountUp.js integration
				const numberElement = counter.querySelector( '.spectra-counter-number' );
				if ( numberElement ) {
					initCounter( numberElement, options );
				} else {
					// If no counter number element, still animate progress bars
					const spectraCounter = new SpectraCounter( counter, options );
					spectraCounter.start();
				}

				// Stop observing this element
				observer.unobserve( counter );
			}
		} );
	}, {
		threshold: 0.3,
		rootMargin: '0px 0px -20px 0px',
	} );

	// Start observing all counters
	counters.forEach( ( counter ) => {
		observer.observe( counter );
	} );
}

/**
 * Initialize all counters on the page
 */
function initAllCounters() {
	initCounterAnimations();
}

// Initialize on DOM content loaded (following slider pattern)
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initAllCounters );
} else {
	initAllCounters();
}

// Re-init on dynamic content changes (following slider pattern)
document.addEventListener( 'spectra-blocks-content-update', initAllCounters );

// Clean up on block removal (following slider pattern)
document.addEventListener( 'spectra-blocks-content-remove', ( event ) => {
	const counter = event.target.closest( '.wp-block-spectra-counter' );
	if ( counter ) {
		const numberElement = counter.querySelector( '.spectra-counter-number' );
		if ( numberElement && numberElement.spectraCounter ) {
			numberElement.spectraCounter.destroy();
			delete numberElement.spectraCounter;
		}
	}
} );

// Legacy global function for backward compatibility
if ( typeof window !== 'undefined' ) {
	window.spectraCounterInit = initAllCounters;
}
