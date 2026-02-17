/**
 * Responsive Conditions Extension.
 *
 * This module enables responsive visibility controls for Spectra blocks by adding
 * device-specific hide/show options. It allows users to hide blocks on specific
 * devices (desktop, tablet, mobile) using CSS media queries.
 *
 * Features:
 * - Hide/show blocks on desktop (1024px+)
 * - Hide/show blocks on tablet (768px - 1023px)
 * - Hide/show blocks on mobile (767px and below)
 * - CSS-based implementation for optimal performance
 * - Integrates with Gutenberg's ToolsPanel UI
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
import addResponsiveConditionsControls, { addResponsiveConditionsClasses } from './controls';
import { extendBlockAttributes } from './utils/helpers';

/**
 * Import extension styles.
 * Frontend styles provide the responsive visibility classes.
 * Editor styles are automatically enqueued from src/styles/extensions/responsive-conditions.scss
 */
import './style.scss';

/**
 * Extends block attributes with responsive conditions properties.
 *
 * This filter adds a 'responsiveConditions' attribute to all supported Spectra blocks,
 * which stores device-specific visibility settings.
 *
 * @since x.x.x
 */
addFilter( 'blocks.registerBlockType', 'spectra/responsive-conditions/add-attributes', extendBlockAttributes );

/**
 * Enhances BlockEdit component with responsive conditions controls in the editor.
 *
 * This filter wraps the block edit component to add responsive conditions controls
 * in the inspector panel, allowing users to configure visibility settings.
 *
 * @since x.x.x
 */
addFilter( 'editor.BlockEdit', 'spectra/responsive-conditions/add-controls', addResponsiveConditionsControls );

/**
 * Adds responsive condition classes to block wrapper in the editor.
 *
 * This filter modifies the BlockListBlock component to add CSS classes that provide
 * visual indicators in the editor based on responsive conditions.
 *
 * @since x.x.x
 */
addFilter( 'editor.BlockListBlock', 'spectra/responsive-conditions/add-editor-classes', addResponsiveConditionsClasses );
