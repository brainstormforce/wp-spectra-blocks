/**
 * Responsive Conditions Extension Helper Functions.
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
import { DEFAULT_RESPONSIVE_CONDITIONS, EXCLUDED_BLOCKS, SUPPORTED_BLOCKS, ALLOWED_PREFIXES } from './constants';

/**
 * Checks if a block is allowed to have responsive conditions.
 * Uses the same filtering logic as responsive controls to ensure consistency.
 *
 * @since x.x.x
 *
 * @param {Object} block - Block object with name property.
 * @return {boolean} Whether the block supports responsive conditions.
 */
export const isAllowedBlock = ( block ) => {
	if ( ! block || ! block.name ) {
		return false;
	}

	const blockName = block.name;

	/**
	 * Filter to allow or exclude specific blocks from responsive conditions.
	 * This filter allows developers to customize which blocks support responsive conditions.
	 *
	 * @since x.x.x
	 *
	 * @param {Array} excludedBlocks - Array of block names to exclude.
	 * @param {string} blockName - The current block name being checked.
	 * @return {Array} Modified array of excluded block names.
	 */
	const excludedBlocks = applyFilters( 'spectra.excludedResponsiveConditionsBlocks', EXCLUDED_BLOCKS, blockName );

	// Skip if block is excluded.
	if ( excludedBlocks.includes( blockName ) ) {
		return false;
	}

	/**
	 * Filter to specify which blocks explicitly support responsive conditions.
	 * This filter allows developers to add support for additional blocks.
	 *
	 * @since x.x.x
	 *
	 * @param {Array} supportedBlocks - Array of block names that support responsive conditions.
	 * @param {string} blockName - The current block name being checked.
	 * @return {Array} Modified array of supported block names.
	 */
	const supportedBlocks = applyFilters( 'spectra.supportedResponsiveConditionsBlocks', SUPPORTED_BLOCKS, blockName );

	// Check if block is explicitly supported.
	if ( supportedBlocks.includes( blockName ) ) {
		return true;
	}

	/**
	 * Filter to modify the allowed block prefixes for responsive conditions.
	 * This filter allows developers to add or remove prefixes.
	 *
	 * @since x.x.x
	 *
	 * @param {Array} allowedPrefixes - Array of block name prefixes to allow.
	 * @param {string} blockName - The current block name being checked.
	 * @return {Array} Modified array of allowed prefixes.
	 */
	const allowedPrefixes = applyFilters( 'spectra.allowedResponsiveConditionsPrefixes', ALLOWED_PREFIXES, blockName );

	// Check if block has an allowed prefix.
	return allowedPrefixes.some( ( prefix ) => blockName.startsWith( prefix ) );
};

/**
 * Extends block attributes with responsive conditions.
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
			responsiveConditions: {
				type: 'object',
				default: DEFAULT_RESPONSIVE_CONDITIONS,
			},
		},
	};
};
