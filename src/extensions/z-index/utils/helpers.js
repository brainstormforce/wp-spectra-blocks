/**
 * Z-Index Extension Helper Functions
 *
 * This module provides utility functions for the z-index extension system.
 * It includes block validation, attribute extension, and CSS generation helpers.
 *
 * @since 0.0.1
 */

/**
 * Internal dependencies.
 */
import { EXCLUDED_BLOCKS, SUPPORTED_BLOCKS, ALLOWED_PREFIXES } from './constants';

/**
 * Check if a block is allowed for z-index controls.
 *
 * @since 0.0.1
 *
 * @param {Object} block The block object.
 * @return {boolean} Whether the block is allowed.
 */
export const isAllowedBlock = ( block ) => {
	// Check there's no block name, or if the block is excluded.
	if ( ! block?.name || EXCLUDED_BLOCKS.includes( block.name ) ) {
		return false;
	}

	// Check if the block is allowed.
	return (
		ALLOWED_PREFIXES.some( ( prefix ) => block.name.startsWith( prefix ) ) ||
		SUPPORTED_BLOCKS.includes( block.name )
	);
};

/**
 * Extends block settings with z-index related attributes.
 *
 * @since 0.0.1
 *
 * @param {Object} settings - The original block settings object.
 * @param {string} name     - The name of the block.
 * @return {Object} Modified block settings with z-index attributes added.
 */
export const extendBlockAttributes = ( settings, name ) => {
	if ( ! isAllowedBlock( { name } ) ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			spectraZIndex: {
				type: 'number',
				default: null,
			},
		},
	};
};

/**
 * Generate CSS custom properties for z-index values.
 *
 * @since 0.0.1
 *
 * @param {number} zIndex - The z-index value.
 * @return {Object} CSS custom properties object.
 */
export const generateZIndexStyles = ( zIndex ) => {
	if ( zIndex === undefined || zIndex === null ) {
		return {};
	}

	return {
		'--spectra-z-index': zIndex,
	};
};