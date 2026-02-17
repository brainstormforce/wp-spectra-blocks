/**
 * Sticky Container Extension Constants.
 *
 * @since x.x.x
 */

/**
 * Sticky position options.
 *
 * @since x.x.x
 */
export const STICKY_POSITION = {
	TOP: 'top',
	BOTTOM: 'bottom',
};

/**
 * Default sticky container settings.
 *
 * @since x.x.x
 */
export const DEFAULT_STICKY_SETTINGS = {
	stickyEnabled: false,
	stickAt: STICKY_POSITION.TOP,
	offset: '0px',
	keepInsideParent: false,
};

/**
 * Minimum and maximum offset values.
 *
 * @since x.x.x
 */
export const MIN_OFFSET = -500;
export const MAX_OFFSET = 500;
export const DEFAULT_OFFSET = '0px';
