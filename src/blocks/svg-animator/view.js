/**
 * SVG Animator Frontend JavaScript
 *
 * Handles all SVG animations on the frontend using the Web Animations API.
 * No external dependencies (no GSAP). Uses IntersectionObserver for scroll triggers.
 *
 * @since x.x.x
 * @package Spectra\Blocks\SvgAnimator
 */

/**
 * SVG Animator Class.
 *
 * Manages animation lifecycle for a single SVG animator block instance.
 */
class SpectraSvgAnimator {
	constructor( element ) {
		this.element = element;
		this.svg = element.querySelector( 'svg' );
		this.animations = [];
		this.hasPlayed = false;

		// Parse settings from data attributes.
		this.settings = {
			type: element.dataset.animationType || 'draw',
			trigger: element.dataset.animationTrigger || 'viewport',
			duration: parseInt( element.dataset.animationDuration, 10 ) || 2000,
			delay: parseInt( element.dataset.animationDelay, 10 ) || 0,
			easing: element.dataset.animationEasing || 'ease',
			loop: element.dataset.animationLoop === 'true',
			reverse: element.dataset.animationReverse === 'true',
			yoyo: element.dataset.animationYoyo === 'true',
			stagger: parseInt( element.dataset.animationStagger, 10 ) || 0,
			fillBehavior: element.dataset.fillBehavior || 'none',
		};

		this.init();
	}

	/**
	 * Initialize the animator.
	 */
	init() {
		if ( ! this.svg ) {
			return;
		}

		// Prepare paths for draw animation.
		if ( this.settings.type === 'draw' ) {
			this.preparePaths();
		}

		// Set initial hidden state for non-draw animations.
		if ( this.settings.type !== 'draw' ) {
			this.setInitialState();
		}

		// Setup the trigger.
		this.setupTrigger();
	}

	/**
	 * Prepare SVG paths for draw animation by calculating total lengths.
	 */
	preparePaths() {
		this.paths = this.svg.querySelectorAll( 'path, circle, rect, polygon, line, polyline, ellipse' );

		this.paths.forEach( ( path ) => {
			const length = this.getPathLength( path );
			if ( length > 0 ) {
				path.style.strokeDasharray = length;
				path.style.strokeDashoffset = this.settings.reverse ? -length : length;
			}

			// Handle fill based on fill behavior.
			if ( this.settings.fillBehavior === 'none' || this.settings.fillBehavior === 'after-stroke' ) {
				path.style.fillOpacity = '0';
			}
		} );
	}

	/**
	 * Get the total length of an SVG path element.
	 *
	 * @param {SVGElement} path The SVG path element.
	 * @return {number} The total length.
	 */
	getPathLength( path ) {
		if ( typeof path.getTotalLength === 'function' ) {
			try {
				return path.getTotalLength();
			} catch ( e ) {
				return 0;
			}
		}
		return 0;
	}

	/**
	 * Set initial hidden state for non-draw animations.
	 */
	setInitialState() {
		switch ( this.settings.type ) {
			case 'fade':
				this.svg.style.opacity = '0';
				break;
			case 'scale':
				this.svg.style.transform = 'scale(0)';
				this.svg.style.transformOrigin = 'center center';
				break;
			case 'rotate':
				this.svg.style.transformOrigin = 'center center';
				break;
		}
	}

	/**
	 * Setup animation trigger based on settings.
	 */
	setupTrigger() {
		switch ( this.settings.trigger ) {
			case 'load':
				// Slight delay to ensure DOM is ready.
				setTimeout( () => this.play(), 50 );
				break;
			case 'viewport':
				this.setupViewportTrigger();
				break;
			case 'hover':
				this.setupHoverTrigger();
				break;
			case 'click':
				this.setupClickTrigger();
				break;
		}
	}

	/**
	 * Setup IntersectionObserver for viewport-triggered animations.
	 */
	setupViewportTrigger() {
		this.observer = new IntersectionObserver(
			( entries ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting && ! this.hasPlayed ) {
						this.play();
						if ( ! this.settings.loop ) {
							this.observer.disconnect();
						}
					}
				} );
			},
			{
				threshold: 0.2,
				rootMargin: '0px 0px -50px 0px',
			}
		);

		this.observer.observe( this.element );
	}

	/**
	 * Setup hover trigger for animations.
	 */
	setupHoverTrigger() {
		this._onMouseEnter = () => {
			this.play();
		};

		this._onMouseLeave = () => {
			if ( this.settings.reverse ) {
				this.reverseAnimations();
			}
		};

		this.element.addEventListener( 'mouseenter', this._onMouseEnter );
		this.element.addEventListener( 'mouseleave', this._onMouseLeave );
	}

	/**
	 * Setup click trigger for animations.
	 */
	setupClickTrigger() {
		this._onClick = () => {
			if ( this.hasPlayed ) {
				this.reset();
			}
			this.play();
		};

		this.element.addEventListener( 'click', this._onClick );
	}

	/**
	 * Play the animation.
	 */
	play() {
		this.hasPlayed = true;

		switch ( this.settings.type ) {
			case 'draw':
				this.animateDraw();
				break;
			case 'fade':
				this.animateFade();
				break;
			case 'scale':
				this.animateScale();
				break;
			case 'rotate':
				this.animateRotate();
				break;
		}
	}

	/**
	 * Get Web Animations API options from settings.
	 *
	 * @param {number} extraDelay Additional delay for staggered elements.
	 * @return {Object} Animation options.
	 */
	getAnimationOptions( extraDelay = 0 ) {
		return {
			duration: this.settings.duration,
			delay: this.settings.delay + extraDelay,
			easing: this.settings.easing,
			fill: 'forwards',
			iterations: this.settings.loop ? Infinity : 1,
			direction: this.settings.yoyo ? 'alternate' : 'normal',
		};
	}

	/**
	 * Draw animation: Animate strokeDashoffset from total length to 0.
	 */
	animateDraw() {
		if ( ! this.paths || this.paths.length === 0 ) {
			return;
		}

		this.paths.forEach( ( path, index ) => {
			const length = this.getPathLength( path );
			if ( length <= 0 ) {
				return;
			}

			const startOffset = this.settings.reverse ? -length : length;
			const staggerDelay = index * this.settings.stagger;
			const options = this.getAnimationOptions( staggerDelay );

			// Animate the stroke.
			const strokeAnimation = path.animate(
				[
					{ strokeDashoffset: startOffset },
					{ strokeDashoffset: 0 },
				],
				options
			);

			this.animations.push( strokeAnimation );

			// Fill after stroke completes (if configured).
			if ( this.settings.fillBehavior === 'after-stroke' ) {
				strokeAnimation.addEventListener( 'finish', () => {
					const fillAnimation = path.animate(
						[
							{ fillOpacity: 0 },
							{ fillOpacity: 1 },
						],
						{
							duration: Math.min( 500, this.settings.duration / 4 ),
							easing: 'ease-out',
							fill: 'forwards',
						}
					);
					this.animations.push( fillAnimation );
				}, { once: true } );
			}
		} );
	}

	/**
	 * Fade animation: Animate opacity from 0 to 1.
	 */
	animateFade() {
		const options = this.getAnimationOptions();
		const animation = this.svg.animate(
			[
				{ opacity: 0 },
				{ opacity: 1 },
			],
			options
		);
		this.animations.push( animation );
	}

	/**
	 * Scale animation: Animate transform scale from 0 to 1.
	 */
	animateScale() {
		const options = this.getAnimationOptions();
		const animation = this.svg.animate(
			[
				{ transform: 'scale(0)' },
				{ transform: 'scale(1)' },
			],
			options
		);
		this.animations.push( animation );
	}

	/**
	 * Rotate animation: Animate transform rotate from 0 to 360 degrees.
	 */
	animateRotate() {
		const options = this.getAnimationOptions();
		const animation = this.svg.animate(
			[
				{ transform: 'rotate(0deg)' },
				{ transform: 'rotate(360deg)' },
			],
			options
		);
		this.animations.push( animation );
	}

	/**
	 * Reverse all running animations.
	 */
	reverseAnimations() {
		this.animations.forEach( ( animation ) => {
			if ( animation.playState === 'running' || animation.playState === 'finished' ) {
				animation.reverse();
			}
		} );
	}

	/**
	 * Reset all animations to initial state.
	 */
	reset() {
		this.animations.forEach( ( animation ) => {
			animation.cancel();
		} );
		this.animations = [];
		this.hasPlayed = false;

		// Re-prepare paths for draw animation.
		if ( this.settings.type === 'draw' ) {
			this.preparePaths();
		} else {
			this.setInitialState();
		}
	}

	/**
	 * Clean up the animator instance.
	 */
	destroy() {
		this.animations.forEach( ( animation ) => {
			animation.cancel();
		} );
		this.animations = [];

		if ( this.observer ) {
			this.observer.disconnect();
		}

		// Remove event listeners to prevent memory leaks.
		if ( this._onMouseEnter ) {
			this.element.removeEventListener( 'mouseenter', this._onMouseEnter );
		}
		if ( this._onMouseLeave ) {
			this.element.removeEventListener( 'mouseleave', this._onMouseLeave );
		}
		if ( this._onClick ) {
			this.element.removeEventListener( 'click', this._onClick );
		}
	}
}

/**
 * Initialize all SVG animator blocks on the page.
 */
function initSvgAnimators() {
	const animators = document.querySelectorAll( '.wp-block-spectra-svg-animator' );

	if ( ! animators.length ) {
		return;
	}

	animators.forEach( ( element ) => {
		// Skip if already initialized.
		if ( element.spectraSvgAnimator ) {
			return;
		}

		element.spectraSvgAnimator = new SpectraSvgAnimator( element );
	} );
}

// Initialize on DOM content loaded (following counter block pattern).
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initSvgAnimators );
} else {
	initSvgAnimators();
}

// Re-init on dynamic content changes (following counter block pattern).
document.addEventListener( 'spectra-blocks-content-update', initSvgAnimators );

// Clean up on block removal (following counter block pattern).
document.addEventListener( 'spectra-blocks-content-remove', ( event ) => {
	const animator = event.target.closest( '.wp-block-spectra-svg-animator' );
	if ( animator && animator.spectraSvgAnimator ) {
		animator.spectraSvgAnimator.destroy();
		delete animator.spectraSvgAnimator;
	}
} );
