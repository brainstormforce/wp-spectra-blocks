/**
 * Sticky Container Extension Helper Functions.
 *
 * @since x.x.x
 */

/**
 * Internal dependencies.
 */
import { DEFAULT_STICKY_SETTINGS } from './constants';

/**
 * Checks if a block is allowed to have sticky container.
 * Only container blocks are allowed to use sticky positioning.
 *
 * @since x.x.x
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
 * Extends block attributes with sticky container settings.
 *
 * @since x.x.x
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
