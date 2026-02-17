/**
 * Sticky Container Extension.
 *
 * This module enables sticky positioning for Spectra container blocks.
 * It allows users to make containers stick to the top or bottom of the viewport
 * while scrolling, with customizable offset values.
 *
 * Features:
 * - Enable/disable sticky positioning
 * - Stick to top or bottom of viewport
 * - Customizable offset (positive or negative)
 * - CSS-based implementation for optimal performance
 * - Frontend-only effect (no changes to editor view)
 *
 * @since x.x.x
 */

/**
 * External dependencies.
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import addStickyContainerControls from './controls';
import { extendBlockAttributes } from './utils/helpers';

/**
 * Extends block attributes with sticky container properties.
 *
 * This filter adds a 'stickyContainer' attribute to supported blocks (container),
 * which stores sticky positioning settings.
 *
 * @since x.x.x
 */
addFilter( 'blocks.registerBlockType', 'spectra/sticky-container/add-attributes', extendBlockAttributes );

/**
 * Enhances BlockEdit component with sticky container controls in the editor.
 *
 * This filter wraps the block edit component to add sticky container controls
 * in the inspector panel, allowing users to configure sticky settings.
 *
 * @since x.x.x
 */
addFilter( 'editor.BlockEdit', 'spectra/sticky-container/add-controls', addStickyContainerControls );

// Sticky positioning styles are applied via PHP render_block filter for proper frontend rendering
