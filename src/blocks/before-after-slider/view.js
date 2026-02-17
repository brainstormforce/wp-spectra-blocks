/**
 * Before After Slider Frontend JavaScript.
 *
 * @since x.x.x
 * @package Spectra\Blocks\BeforeAfterSlider
 */

class SpectraBeforeAfterSlider {
	constructor( element ) {
		this.element = element;
		this.container = element.querySelector( '.spectra-ba-slider__container' );
		this.beforeEl = element.querySelector( '.spectra-ba-slider__before' );
		this.handle = element.querySelector( '.spectra-ba-slider__handle' );

		if ( ! this.container || ! this.beforeEl || ! this.handle ) {
			return;
		}

		// Parse settings from data attributes with validation.
		const validOrientations = [ 'horizontal', 'vertical' ];
		this.orientation = validOrientations.includes( element.dataset.orientation )
			? element.dataset.orientation
			: 'horizontal';

		const rawOffset = parseFloat( element.dataset.offset );
		this.offset = ( ! isNaN( rawOffset ) && rawOffset >= 0 && rawOffset <= 100 )
			? rawOffset
			: 50;

		this.moveOnHover = element.dataset.moveOnHover === 'true';
		this.isHorizontal = this.orientation === 'horizontal';
		this.isDragging = false;

		this.init();
	}

	init() {
		// Set initial position.
		this.setPosition( this.offset );

		if ( this.moveOnHover ) {
			this.setupHoverMode();
		} else {
			this.setupDragMode();
		}
	}

	/**
	 * Calculate position percentage from a pointer event.
	 *
	 * @param {MouseEvent|Touch} event The event or touch object.
	 * @return {number} Position as percentage (0-100).
	 */
	getPositionFromEvent( event ) {
		const rect = this.container.getBoundingClientRect();

		let percentage;
		if ( this.isHorizontal ) {
			const x = event.clientX - rect.left;
			percentage = ( x / rect.width ) * 100;
		} else {
			const y = event.clientY - rect.top;
			percentage = ( y / rect.height ) * 100;
		}

		// Clamp between 0 and 100.
		return Math.max( 0, Math.min( 100, percentage ) );
	}

	/**
	 * Set the slider position.
	 *
	 * @param {number} percentage Position as percentage (0-100).
	 */
	setPosition( percentage ) {
		if ( this.isHorizontal ) {
			this.beforeEl.style.clipPath = `inset(0 ${ 100 - percentage }% 0 0)`;
			this.handle.style.left = `${ percentage }%`;
			this.handle.style.top = '';
		} else {
			this.beforeEl.style.clipPath = `inset(0 0 ${ 100 - percentage }% 0)`;
			this.handle.style.top = `${ percentage }%`;
			this.handle.style.left = '';
		}
	}

	setupHoverMode() {
		this._onMouseMove = ( event ) => {
			const percentage = this.getPositionFromEvent( event );
			this.setPosition( percentage );
		};

		this._onMouseLeave = () => {
			this.setPosition( this.offset );
		};

		this.container.addEventListener( 'mousemove', this._onMouseMove );
		this.container.addEventListener( 'mouseleave', this._onMouseLeave );
	}

	setupDragMode() {
		// Mouse events.
		this._onMouseDown = ( event ) => {
			event.preventDefault();
			this.isDragging = true;
			this.element.classList.add( 'spectra-ba-slider--active' );
			const percentage = this.getPositionFromEvent( event );
			this.setPosition( percentage );
		};

		this._onMouseMove = ( event ) => {
			if ( ! this.isDragging ) {
				return;
			}
			event.preventDefault();
			const percentage = this.getPositionFromEvent( event );
			this.setPosition( percentage );
		};

		this._onMouseUp = () => {
			if ( this.isDragging ) {
				this.isDragging = false;
				this.element.classList.remove( 'spectra-ba-slider--active' );
			}
		};

		// Touch events.
		this._onTouchStart = ( event ) => {
			this.isDragging = true;
			this.element.classList.add( 'spectra-ba-slider--active' );
			const percentage = this.getPositionFromEvent( event.touches[ 0 ] );
			this.setPosition( percentage );
		};

		this._onTouchMove = ( event ) => {
			if ( ! this.isDragging ) {
				return;
			}
			event.preventDefault();
			const percentage = this.getPositionFromEvent( event.touches[ 0 ] );
			this.setPosition( percentage );
		};

		this._onTouchEnd = () => {
			if ( this.isDragging ) {
				this.isDragging = false;
				this.element.classList.remove( 'spectra-ba-slider--active' );
			}
		};

		// Bind mouse events: mousedown on container, move/up on document.
		this.container.addEventListener( 'mousedown', this._onMouseDown );
		document.addEventListener( 'mousemove', this._onMouseMove );
		document.addEventListener( 'mouseup', this._onMouseUp );

		// Bind touch events.
		this.container.addEventListener( 'touchstart', this._onTouchStart, { passive: true } );
		this.container.addEventListener( 'touchmove', this._onTouchMove, { passive: false } );
		this.container.addEventListener( 'touchend', this._onTouchEnd );
	}

	destroy() {
		// Remove hover mode listeners.
		if ( this.moveOnHover ) {
			if ( this._onMouseMove ) {
				this.container.removeEventListener( 'mousemove', this._onMouseMove );
			}
			if ( this._onMouseLeave ) {
				this.container.removeEventListener( 'mouseleave', this._onMouseLeave );
			}
		} else {
			// Remove drag mode listeners.
			if ( this._onMouseDown ) {
				this.container.removeEventListener( 'mousedown', this._onMouseDown );
			}
			if ( this._onMouseMove ) {
				document.removeEventListener( 'mousemove', this._onMouseMove );
			}
			if ( this._onMouseUp ) {
				document.removeEventListener( 'mouseup', this._onMouseUp );
			}
			if ( this._onTouchStart ) {
				this.container.removeEventListener( 'touchstart', this._onTouchStart );
			}
			if ( this._onTouchMove ) {
				this.container.removeEventListener( 'touchmove', this._onTouchMove );
			}
			if ( this._onTouchEnd ) {
				this.container.removeEventListener( 'touchend', this._onTouchEnd );
			}
		}
	}
}

/**
 * Initialize all Before After Slider blocks on the page.
 */
function initBeforeAfterSliderBlocks() {
	const blocks = document.querySelectorAll( '.wp-block-spectra-before-after-slider' );
	if ( ! blocks.length ) {
		return;
	}

	blocks.forEach( ( element ) => {
		if ( element.spectraBeforeAfterSlider ) {
			return; // Skip if already initialized.
		}
		element.spectraBeforeAfterSlider = new SpectraBeforeAfterSlider( element );
	} );
}

// Initialize.
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initBeforeAfterSliderBlocks );
} else {
	initBeforeAfterSliderBlocks();
}

// Re-init on dynamic content.
document.addEventListener( 'spectra-blocks-content-update', initBeforeAfterSliderBlocks );

// Cleanup on removal.
document.addEventListener( 'spectra-blocks-content-remove', ( event ) => {
	const block = event.target.closest( '.wp-block-spectra-before-after-slider' );
	if ( block && block.spectraBeforeAfterSlider ) {
		block.spectraBeforeAfterSlider.destroy();
		delete block.spectraBeforeAfterSlider;
	}
} );
