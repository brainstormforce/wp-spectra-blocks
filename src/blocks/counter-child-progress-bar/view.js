/**
 * Frontend script for Counter Progress Bar block animations
 * Integrates with the main counter animation system
 * 
 * @since x.x.x
 */

document.addEventListener( 'DOMContentLoaded', function() {
	
	/**
	 * Enhanced Counter Progress Bar Animation Class
	 * Syncs with main counter animations
	 */
	class SpectraCounterProgressBar {
		constructor( element ) {
			this.element = element;
			this.progressBar = element.querySelector( '.spectra-counter-progress-bar' );
			this.progressLabel = element.querySelector( '.spectra-counter-progress-label' );
			this.progressValue = element.querySelector( '.spectra-counter-value' );
			
			if ( ! this.progressBar || ! this.progressValue ) {
				return;
			}
			
			// Get animation data from parent counter block
			this.counterBlock = element.closest( '.wp-block-spectra-counter' );
			if ( ! this.counterBlock ) {
				return;
			}
			
		// Extract animation parameters from parent counter
		this.startNumber = parseFloat( this.counterBlock.dataset.counterStart ) || 0;
		this.endNumber = parseFloat( this.counterBlock.dataset.counterEnd ) || 100;
		this.totalNumber = parseFloat( this.counterBlock.dataset.counterTotal ) || 100;
		this.duration = parseInt( this.counterBlock.dataset.counterDuration ) || 2000;
		this.prefix = this.counterBlock.dataset.counterPrefix || '';
		this.suffix = this.counterBlock.dataset.counterSuffix || '';
		this.separator = this.counterBlock.dataset.counterSeparator !== undefined ? this.counterBlock.dataset.counterSeparator : ',';
		this.decimals = parseInt( this.counterBlock.dataset.counterDecimals ) || 0;
			
			// Ensure totalNumber is valid for progress calculation
			if ( this.totalNumber < Math.max( Math.abs( this.startNumber ), Math.abs( this.endNumber ) ) ) {
				this.totalNumber = Math.max( Math.abs( this.startNumber ), Math.abs( this.endNumber ) );
			}
			
			// Set initial state
			this.setInitialState();
			
			// Hook into the main counter animation system
			this.hookIntoCounterAnimation();
		}
		
		/**
		 * Set initial progress bar state
		 */
		setInitialState() {
			const initialProgress = this.totalNumber > 0 ? Math.min( Math.max( ( this.startNumber / this.totalNumber ) * 100, 0 ), 100 ) : 0;
			this.progressBar.style.width = `${initialProgress}%`;
			this.updateProgressLabel( this.startNumber );
		}
		
		/**
		 * Format number with separators and decimals
		 *
		 * @param {number} number The number to format.
		 *
		 * @return {string} Formatted number string.
		 */
		formatNumber( number ) {
			const fixedNumber = parseFloat( number ).toFixed( this.decimals );
			
			if ( this.separator && this.separator.trim() !== '' ) {
				const parts = fixedNumber.split( '.' );
				parts[0] = parts[0].replace( /\B(?=(\d{3})+(?!\d))/g, this.separator );
				return parts.join( '.' );
			}
			
			return fixedNumber;
		}
		
		/**
		 * Update progress label with prefix/suffix
		 *
		 * @param {number} number The current number value.
		 */
		updateProgressLabel( number ) {
			const formattedNumber = this.formatNumber( number );
			
			// Update prefix
			const prefixElement = this.progressLabel.querySelector( '.spectra-counter-prefix' );
			if ( prefixElement ) {
				prefixElement.textContent = this.prefix;
			}
			
			// Update value
			this.progressValue.textContent = formattedNumber;
			
			// Update suffix
			const suffixElement = this.progressLabel.querySelector( '.spectra-counter-suffix' );
			if ( suffixElement ) {
				suffixElement.textContent = this.suffix;
			}
		}
		
		/**
		 * Hook into the main counter animation system
		 */
		hookIntoCounterAnimation() {
			// Listen for counter animation events
			this.counterBlock.addEventListener( 'counterAnimationStart', () => {
				this.startProgressAnimation();
			} );
			
			this.counterBlock.addEventListener( 'counterAnimationUpdate', ( event ) => {
				if ( event.detail && typeof event.detail.currentNumber !== 'undefined' ) {
					this.updateProgress( event.detail.currentNumber );
				}
			} );
			
			// Fallback: Start animation when counter comes into view
			this.observeElement();
		}
		
		/**
		 * Start progress bar animation
		 */
		startProgressAnimation() {
			this.element.classList.add( 'spectra-counter-animating' );
			
			const startTime = performance.now();
			const difference = this.endNumber - this.startNumber;
			
			const animateStep = ( currentTime ) => {
				const elapsed = currentTime - startTime;
				const progress = Math.min( elapsed / this.duration, 1 );
				
				// Linear easing for consistent timing
				const currentNumber = this.startNumber + ( difference * progress );
				this.updateProgress( currentNumber );
				
				// Continue animation if not complete
				if ( progress < 1 ) {
					requestAnimationFrame( animateStep );
				} else {
					// Ensure final value is set correctly
					this.updateProgress( this.endNumber );
					this.element.classList.remove( 'spectra-counter-animating' );
				}
			};
			
			requestAnimationFrame( animateStep );
		}
		
		/**
		 * Update progress bar and label
		 *
		 * @param {number} currentNumber The current progress number.
		 */
		updateProgress( currentNumber ) {
			// Ensure currentNumber is valid
			const validCurrentNumber = isNaN( currentNumber ) ? this.startNumber : currentNumber;
			
			const currentProgress = this.totalNumber > 0 ? Math.min( Math.max( ( validCurrentNumber / this.totalNumber ) * 100, 0 ), 100 ) : 0;
			
			// Update progress bar width
			this.progressBar.style.width = `${currentProgress}%`;
			
			// Update counter text
			this.updateProgressLabel( validCurrentNumber );
		}
		
		/**
		 * Observe element for intersection (fallback animation trigger)
		 */
		observeElement() {
			const observer = new IntersectionObserver( ( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting ) {
						// Trigger animation if not already started
						setTimeout( () => {
							if ( ! this.element.classList.contains( 'spectra-counter-animating' ) ) {
								this.startProgressAnimation();
							}
						}, 100 );
						observer.unobserve( entry.target ); // Only animate once
					}
				} );
			}, {
				threshold: 0.1, // Trigger when 10% visible
				rootMargin: '0px 0px -50px 0px' // Start animation slightly before fully visible
			} );
			
			observer.observe( this.element );
		}
	}
	
	/**
	 * Initialize all counter progress bars on the page
	 */
	function initCounterProgressBars() {
		const progressBars = document.querySelectorAll( '.wp-block-spectra-counter-child-progress-bar.spectra-counter-progress-bar--bar' );

		progressBars.forEach( ( element ) => {
			if ( element.spectraCounterProgressBar ) return;
			element.spectraCounterProgressBar = new SpectraCounterProgressBar( element );
		} );
	}
	
	// Initialize progress bars
	initCounterProgressBars();
	
	// Re-initialize on dynamic content load (for AJAX/SPA scenarios)
	document.addEventListener( 'spectra-blocks-loaded', initCounterProgressBars );
	
} );
