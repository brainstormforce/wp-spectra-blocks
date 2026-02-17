/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';

/**
 * 
 * Generate Background Styles.
 *
 * @since x.x.x
 * @param {Object} props The required attributes to determine the returned styles.
 * @return {Object} The image style variables. 
 */
export const getBackgroundImageStyles = ( props ) => {

	// Get the required values from the passed object.
	const {
		backgroundGradient,
		backgroundGradientHover,
		background = {},
	} = props;

	// If the background type is not image, or there's no URL to the media, abandon ship.
	if ( 'image' !== background?.type || ! background?.media?.url ) {
		return {};
	}

	// Get the background media URL and the background usage of overlay.
	const backgroundURL = background.media.url
	const backgroundOverlay = background?.useOverlay || false;

	// Create an object to add the additional styles to.
	const styles = {};

	// If there's a background gradient and an image without overlay, combine the two. Else just use the image.
	// IMPORTANT: When backgroundGradient exists, treat it as overlay mode to prevent double gradient application
	// The CSS will apply the gradient via ::before pseudo-element when gradient is present
	const isOverlayMode = backgroundOverlay || !!backgroundGradient;

	if ( backgroundGradient && ! isOverlayMode ) {
		styles[ '--spectra-background-image' ] = `url(${ backgroundURL }),var(--spectra-background-gradient)`;
	} else {
		styles[ '--spectra-background-image' ] = `url(${ backgroundURL })`;
	}

	// If there's a background gradient on hover and an image without overlay, combine the two. Else just use the image.
	const isOverlayModeHover = backgroundOverlay || !!backgroundGradientHover;

	if ( backgroundGradientHover && ! isOverlayModeHover ) {
		styles[ '--spectra-background-image-hover' ] = `url(${ backgroundURL }),var(--spectra-background-gradient-hover)`;
	} else {
		styles[ '--spectra-background-image-hover' ] = `url(${ backgroundURL })`;
	}

	// Add the other background image based props.
	if ( background.backgroundSize === 'custom' ) {
		const width = background.backgroundWidth || '100%';
		styles[ '--spectra-background-size' ] = `${ width } auto`;
	} else {
		styles[ '--spectra-background-size' ] = background.backgroundSize || 'cover';
	}
	styles[ '--spectra-background-repeat' ] = background.backgroundRepeat || 'no-repeat';

	// Handle background position based on mode.
	if ( background.positionMode === 'custom' ) {
		// Custom positioning mode - use X/Y values with any unit.
		// If centralized position is enabled, force both to 50%.
		const xPos = background.positionCentered ? '50%' : ( background.positionX || '0%' );
		const yPos = background.positionCentered ? '50%' : ( background.positionY || '0%' );
		styles[ '--spectra-background-position' ] = `${ xPos } ${ yPos }`;
	} else if (
		'number' === typeof background.backgroundPosition?.x ||
		'number' === typeof background.backgroundPosition?.y
	) {
		// Default focal point mode.
		const focalPoints = {
			x: ( 'number' === typeof background.backgroundPosition?.x ) ? background.backgroundPosition.x : 0.5,
			y: ( 'number' === typeof background.backgroundPosition?.y ) ? background.backgroundPosition.y : 0.5,
		}
		styles[ '--spectra-background-position' ] = `${ focalPoints.x * 100 }% ${ focalPoints.y * 100 }%`;
	}

	// Add background attachment.
	if ( background.backgroundAttachment ) {
		styles[ '--spectra-background-attachment' ] = background.backgroundAttachment;
	}

	// Return the styles object.
	return styles;
};

export const VideoBackground = ( { background = {} } ) => {
	// Check if we have a video background with a valid URL.
	if ( background?.type !== 'video' || ! background?.media?.url ) {
		return null;
	}

	return (
		<div className={ spectraClassNames( [
			'spectra-background-video__wrapper',
			'spectra-background-video__wrapper--overlay',
			'spectra-overlay-color',
		] ) }>
			<video
				// For this specific video, we need to disable interactive elements.
				// Since this video works as a background, and should never be interacted with.
				role="presentation" // eslint-disable-line jsx-a11y/no-interactive-element-to-noninteractive-role
				aria-hidden="true"
				autoPlay
				loop
				muted
				playsInline
				src={ background.media.url }
			/>
		</div>
	);
};