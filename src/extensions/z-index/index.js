/**
 * External dependencies.
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import addZIndexControls from './controls';
import { extendBlockAttributes } from './utils/helpers';

/**
 * Extends block attributes with z-index related properties.
 *
 * @since 0.0.1
 *
 * @param {Object} settings - The block settings object.
 * @param {string} name     - The block name.
 * @return {Object} Modified settings with z-index attributes.
 */
addFilter( 'blocks.registerBlockType', 'spectra/add-z-index-attributes', extendBlockAttributes );

/**
 * Enhances BlockEdit component with z-index controls in the editor.
 *
 * @since 0.0.1
 *
 * @param {Function} BlockEdit - The original BlockEdit component.
 * @return {Function} Enhanced BlockEdit component with z-index controls.
 */
addFilter( 'editor.BlockEdit', 'spectra/add-z-index-controls', addZIndexControls );

// Z-index styles are applied via PHP render_block filter for proper persistence