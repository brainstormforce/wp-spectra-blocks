/**
 * Display Conditions Extension Helper Functions.
 *
 * @since x.x.x
 */

/**
 * External dependencies.
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import {
	DEFAULT_DISPLAY_CONDITIONS,
	EXCLUDED_BLOCKS,
	SUPPORTED_BLOCKS,
	ALLOWED_PREFIXES,
} from './constants';

/**
 * Checks if a block is allowed to have display conditions.
 *
 * @since x.x.x
 *
 * @param {Object} block - Block object with name property.
 * @return {boolean} Whether the block supports display conditions.
 */
export const isAllowedBlock = ( block ) => {
	if ( ! block || ! block.name ) {
		return false;
	}

	const blockName = block.name;

	/**
	 * Filter to allow or exclude specific blocks from display conditions.
	 *
	 * @since x.x.x
	 *
	 * @param {Array}  excludedBlocks - Array of block names to exclude.
	 * @param {string} blockName      - The current block name being checked.
	 * @return {Array} Modified array of excluded block names.
	 */
	const excludedBlocks = applyFilters(
		'spectra.excludedDisplayConditionsBlocks',
		EXCLUDED_BLOCKS,
		blockName
	);

	if ( excludedBlocks.includes( blockName ) ) {
		return false;
	}

	/**
	 * Filter to specify which blocks explicitly support display conditions.
	 *
	 * @since x.x.x
	 *
	 * @param {Array}  supportedBlocks - Array of block names that support display conditions.
	 * @param {string} blockName       - The current block name being checked.
	 * @return {Array} Modified array of supported block names.
	 */
	const supportedBlocks = applyFilters(
		'spectra.supportedDisplayConditionsBlocks',
		SUPPORTED_BLOCKS,
		blockName
	);

	if ( supportedBlocks.includes( blockName ) ) {
		return true;
	}

	/**
	 * Filter to modify the allowed block prefixes for display conditions.
	 *
	 * @since x.x.x
	 *
	 * @param {Array}  allowedPrefixes - Array of block name prefixes to allow.
	 * @param {string} blockName       - The current block name being checked.
	 * @return {Array} Modified array of allowed prefixes.
	 */
	const allowedPrefixes = applyFilters(
		'spectra.allowedDisplayConditionsPrefixes',
		ALLOWED_PREFIXES,
		blockName
	);

	return allowedPrefixes.some( ( prefix ) => blockName.startsWith( prefix ) );
};

/**
 * Extends block attributes with display conditions.
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
			displayConditions: {
				type: 'object',
				default: DEFAULT_DISPLAY_CONDITIONS,
			},
		},
	};
};
