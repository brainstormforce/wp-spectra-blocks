/**
 * Internal dependencies.
 */
import { EXCLUDED_BLOCKS, SUPPORTED_BLOCKS } from './constants';

/**
 * Check if a block is allowed for dynamic content.
 *
 * @since x.x.x
 *
 * @param {Object} block The block object.
 * @return {boolean} Whether the block is allowed.
 */
export const isAllowedBlock = ( block ) => {
	// Check there's no block name, or if the block is excluded.
	if ( ! block?.name || EXCLUDED_BLOCKS.includes( block.name ) ) {
		return false;
	}

	// Define allowed block prefixes.
	const ALLOWED_PREFIXES = [ 'spectra/', 'spectra-pro/', 'core/' ];

	// Check if the block is allowed.
	return (
		ALLOWED_PREFIXES.some( ( prefix ) => block.name.startsWith( prefix ) ) ||
		SUPPORTED_BLOCKS.includes( block.name )
	);
};

/**
 * Extends block settings with animation-related attributes.
 *
 * @since x.x.x
 *
 * @param {Object} settings - The original block settings object.
 * @param {string} name     - The name of the block.
 * @return {Object} Modified block settings with animation attributes added.
 */
export const extendBlockAttributes = ( settings, name ) => {
	if ( ! isAllowedBlock( { name } ) ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			spectraAnimationType: {
				type: 'string',
				default: '',
			},
			spectraAnimationTime: {
				type: 'number',
				default: 400,
			},
			spectraAnimationDelay: {
				type: 'number',
				default: 0,
			},
			spectraAnimationEasing: {
				type: 'string',
				default: 'ease',
			},
			spectraAnimationOnce: {
				type: 'boolean',
				default: false,
			},
		},
	};
};
