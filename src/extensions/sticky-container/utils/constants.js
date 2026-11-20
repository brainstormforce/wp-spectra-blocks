/**
 * Sticky Container Extension Constants.
 *
 * @since 1.0.16
 */

/**
 * Sticky position options.
 *
 * @since 1.0.16
 */
export const STICKY_POSITION = {
	TOP: 'top',
	BOTTOM: 'bottom',
};

/**
 * Default sticky container settings.
 *
 * @since 1.0.16
 */
export const DEFAULT_STICKY_SETTINGS = {
	stickyEnabled: false,
	stickAt: STICKY_POSITION.TOP,
	offset: '0px',
	keepInsideParent: false,
	stickyOnDesktop: true,
	stickyOnTablet: true,
	stickyOnMobile: true,
};

/**
 * Per-device sticky setting keys, in Desktop / Tablet / Mobile order.
 *
 * Containers saved before these keys existed do not have them, and a missing
 * key means sticky on that device, so read them with `isStickyOnDevice()`.
 *
 * @since 1.0.16
 */
export const STICKY_DEVICE_KEYS = [ 'stickyOnDesktop', 'stickyOnTablet', 'stickyOnMobile' ];

/**
 * Minimum and maximum offset values.
 *
 * @since 1.0.16
 */
export const MIN_OFFSET = -500;
export const MAX_OFFSET = 500;
export const DEFAULT_OFFSET = '0px';
