/**
 * HTML Attributes Extension Helper Functions.
 *
 * @since x.x.x
 */

import { applyFilters } from '@wordpress/hooks';

import { ALLOWED_PREFIXES, EXCLUDED_BLOCKS, SUPPORTED_BLOCKS, TAG_ATTRIBUTE_CONFIG } from './constants';

/**
 * Checks if a block should receive html attributes support.
 *
 * @since x.x.x
 *
 * @param {Object} block - Block object with a name property.
 * @return {boolean} Whether the block supports html attributes.
 */
export const isAllowedBlock = ( block ) => {
	if ( ! block?.name ) {
		return false;
	}
	if ( EXCLUDED_BLOCKS.includes( block.name ) ) {
		return false;
	}
	if ( SUPPORTED_BLOCKS.includes( block.name ) ) {
		return true;
	}
	return ALLOWED_PREFIXES.some( ( prefix ) => block.name.startsWith( prefix ) );
};

/**
 * Extends block settings with the htmlAttributes attribute.
 *
 * Runs via the blocks.registerBlockType filter. Adds the htmlAttributes array
 * to every allowed block so custom attributes can be stored and rendered.
 * Content and container already define htmlAttributes in their block.json —
 * this ensures the type is consistently array for all blocks.
 *
 * @since x.x.x
 *
 * @param {Object} settings - The block settings object.
 * @param {string} name     - The block name.
 * @return {Object} Modified settings with htmlAttributes added.
 */
export const extendBlockAttributes = ( settings, name ) => {
	if ( ! isAllowedBlock( { name } ) ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...( settings.attributes || {} ),
			htmlAttributes: {
				type: 'array',
				default: [],
			},
		},
	};
};

/**
 * Returns the contextual attribute field definitions for a given HTML tag.
 *
 * @since x.x.x
 *
 * @param {string} tag - The HTML tag name (lowercase).
 * @return {Array} Array of field definition objects, or empty array if none.
 */
export const getTagFields = ( tag ) => TAG_ATTRIBUTE_CONFIG[ tag ] ?? [];

/**
 * Returns block-specific contextual attribute field definitions for a block.
 *
 * Third-party code (individual blocks or Spectra Pro) can extend this via the
 * `spectra.blockContextualFields` WordPress filter:
 *
 *     addFilter(
 *         'spectra.blockContextualFields',
 *         'my-namespace/my-block/html-attrs',
 *         ( fields, blockName ) => {
 *             if ( blockName !== 'spectra/my-block' ) return fields;
 *             return [ ...fields, { attribute: 'loading', label: 'Loading', type: 'select', options: [...], help: '...' } ];
 *         }
 *     );
 *
 * @since x.x.x
 *
 * @param {string} blockName - The block name (e.g. 'spectra/image').
 * @return {Array} Array of field definition objects registered for this block.
 */
export const getBlockFields = ( blockName ) =>
	applyFilters( 'spectra.blockContextualFields', [], blockName );

/**
 * Returns attribute names that are contextually managed for a given tag.
 *
 * @since x.x.x
 *
 * @param {string} tag - The HTML tag name (lowercase).
 * @return {Set<string>} Set of attribute names managed by the contextual panel.
 */
export const getContextualAttrNames = ( tag ) =>
	new Set( getTagFields( tag ).map( ( f ) => f.attribute ) );

/**
 * Reads a single attribute value from the htmlAttributes array.
 *
 * @since x.x.x
 *
 * @param {Array}  htmlAttributes - The stored attributes array.
 * @param {string} attrName       - The attribute name to look up.
 * @return {string} The attribute value, or an empty string if not found.
 */
export const getAttrValue = ( htmlAttributes, attrName ) =>
	htmlAttributes.find( ( item ) => item.attribute === attrName )?.value ?? '';

/**
 * Returns a new htmlAttributes array with the given attribute set or removed.
 *
 * Passing an empty string or false as value removes the entry from the array.
 *
 * @since x.x.x
 *
 * @param {Array}          htmlAttributes - The current attributes array.
 * @param {string}         attrName       - The attribute name to update.
 * @param {string|boolean} value          - The new value; empty/false removes the entry.
 * @return {Array} Updated attributes array.
 */
export const setAttrValue = ( htmlAttributes, attrName, value ) => {
	const filtered = htmlAttributes.filter( ( item ) => item.attribute !== attrName );
	if ( value === '' || value === false || value === null || value === undefined ) {
		return filtered;
	}
	return [ ...filtered, { attribute: attrName, value: String( value ) } ];
};

/**
 * Splits the htmlAttributes array into contextual and custom groups.
 *
 * Contextual attrs are those managed by TAG_ATTRIBUTE_CONFIG for the current tag.
 * Custom attrs are everything else (the free-form repeater entries).
 *
 * @since x.x.x
 *
 * @param {Array}  htmlAttributes - The stored attributes array.
 * @param {string} tag            - The currently selected HTML tag (lowercase).
 * @return {{ contextual: Array, custom: Array }} Split attribute groups.
 */
export const splitAttributes = ( htmlAttributes, tag ) => {
	const managed = getContextualAttrNames( tag );
	return {
		contextual: htmlAttributes.filter( ( item ) => managed.has( item.attribute ) ),
		custom: htmlAttributes.filter( ( item ) => ! managed.has( item.attribute ) ),
	};
};
