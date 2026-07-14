/**
 * Display Conditions Extension.
 *
 * This module enables user-state visibility controls for Spectra blocks by adding
 * login-based hide/show options. It allows users to hide blocks from logged-in
 * or logged-out users. Blocks are completely removed from the page output server-side.
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
import addDisplayConditionsControls, {
	addDisplayConditionsClasses,
} from './controls';
import { extendBlockAttributes } from './utils/helpers';

/**
 * Extends block attributes with display conditions properties.
 *
 * This filter adds a 'displayConditions' attribute to all supported Spectra blocks,
 * which stores user-state visibility settings.
 *
 * @since x.x.x
 */
addFilter(
	'blocks.registerBlockType',
	'spectra/display-conditions/add-attributes',
	extendBlockAttributes
);

/**
 * Enhances BlockEdit component with display conditions controls in the editor.
 *
 * This filter wraps the block edit component to add display conditions controls
 * in the inspector panel, allowing users to configure visibility settings.
 *
 * @since x.x.x
 */
addFilter(
	'editor.BlockEdit',
	'spectra/display-conditions/add-controls',
	addDisplayConditionsControls
);

/**
 * Adds display condition classes to block wrapper in the editor.
 *
 * This filter modifies the BlockListBlock component to add CSS classes that provide
 * visual indicators in the editor based on display conditions.
 *
 * @since x.x.x
 */
addFilter(
	'editor.BlockListBlock',
	'spectra/display-conditions/add-editor-classes',
	addDisplayConditionsClasses
);
