/**
 * Sticky Container Extension Helper Functions.
 *
 * @since 1.0.16
 */

/**
 * Internal dependencies.
 */
import { DEFAULT_STICKY_SETTINGS } from './constants';

/**
 * Checks if a block is allowed to have sticky container.
 * Only container blocks are allowed to use sticky positioning.
 *
 * @since 1.0.16
 *
 * @param {Object} block - Block object with name property.
 * @return {boolean} Whether the block supports sticky container.
 */
export const isAllowedBlock = ( block ) => {
	if ( ! block || ! block.name ) {
		return false;
	}

	// Only allow sticky positioning on container blocks.
	return block.name === 'spectra/container';
};

/**
 * Whether the container sticks on a device.
 *
 * A key missing from the saved object means sticky, so containers saved
 * before the device settings existed keep sticking everywhere.
 *
 * @since 1.0.16
 *
 * @param {Object} stickyContainer - The stickyContainer attribute.
 * @param {string} key             - One of STICKY_DEVICE_KEYS.
 * @return {boolean} Whether sticky applies on that device.
 */
export const isStickyOnDevice = ( stickyContainer, key ) => false !== stickyContainer?.[ key ];

/**
 * Extends block attributes with sticky container settings.
 *
 * @since 1.0.16
 *
 * @param {Object} settings - Block settings.
 * @param {string} name     - Block name.
 * @return {Object} Extended block settings.
 */
export const extendBlockAttributes = ( settings, name ) => {
	if ( ! isAllowedBlock( { name } ) ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			stickyContainer: {
				type: 'object',
				default: DEFAULT_STICKY_SETTINGS,
			},
		},
	};
};
