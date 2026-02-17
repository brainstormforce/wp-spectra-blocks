/**
 * External dependencies.
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import addAnimationControls from './controls';
import { extendBlockAttributes } from './utils/helpers';

/**
 * Extends block attributes with animation-related properties.
 *
 * @since x.x.x
 *
 * @param {Object} settings - The block settings object.
 * @param {string} name     - The block name.
 * @return {Object} Modified settings with animation attributes.
 */
addFilter( 'blocks.registerBlockType', 'spectra/add-animations-attributes', extendBlockAttributes );

/**
 * Enhances BlockEdit component with animation controls in the editor.
 *
 * @since x.x.x
 *
 * @param {Function} BlockEdit - The original BlockEdit component.
 * @return {Function} Enhanced BlockEdit component with animation controls.
 */
addFilter( 'editor.BlockEdit', 'spectra/add-animations-controls', addAnimationControls );
