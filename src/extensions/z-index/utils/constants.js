/**
 * Z-Index Extension Constants
 *
 * This module defines all constants used by the z-index extension system.
 * It includes excluded blocks, supported blocks, and default values.
 *
 * @since 3.0.0
 */

/**
 * External dependencies.
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Array of blocks that should be excluded from z-index controls.
 *
 * This can be extended by third-party developers using the WordPress filter system.
 *
 * @since 3.0.0
 * @type {Array}
 */
export const EXCLUDED_BLOCKS = applyFilters( 'spectra.excludedZIndexBlocks', [
	// Child blocks that inherit from parent
	'spectra/accordion-child-details',
	'spectra/accordion-child-header',
	'spectra/accordion-child-header-content',
	'spectra/accordion-child-header-icon',
	'spectra/accordion-child-item',
	'spectra/countdown-child-day',
	'spectra/countdown-child-hour',
	'spectra/countdown-child-label',
	'spectra/countdown-child-minute',
	'spectra/countdown-child-number',
	'spectra/countdown-child-second',
	'spectra/countdown-child-separator',
	'spectra/list-child-icon',
	'spectra/list-child-item',
	'spectra/modal-child-popup',
	'spectra/modal-child-popup-close-icon',
	'spectra/modal-child-popup-content',
	'spectra/modal-child-trigger',
	'spectra/modal-child-trigger-button',
	'spectra/modal-child-trigger-content',
	'spectra/modal-child-trigger-icon',
	'spectra/slider-child',
	'spectra/tabs-child-tab-button',
	'spectra/tabs-child-tab-wrapper',
	'spectra/tabs-child-tabpanel',
	'spectra/popup-builder',
] );

/**
 * Array of blocks that explicitly support z-index controls.
 *
 * This can be extended by third-party developers using the WordPress filter system.
 * Note: Blocks with Spectra prefixes are automatically supported regardless of this list.
 *
 * @since 3.0.0
 * @type {Array}
 */
export const SUPPORTED_BLOCKS = applyFilters( 'spectra.supportedZIndexBlocks', [] );

/**
 * Block name prefixes that are allowed to use z-index controls by default.
 *
 * Any block with these prefixes will automatically receive z-index capabilities.
 *
 * @since 3.0.0
 * @type {Array}
 */
export const ALLOWED_PREFIXES = [ 'spectra/', 'spectra-pro/', 'core/' ];

/**
 * Default z-index value.
 *
 * @since 3.0.0
 * @type {number}
 */
export const DEFAULT_Z_INDEX = null;

/**
 * Minimum z-index value.
 *
 * @since 3.0.0
 * @type {number}
 */
export const MIN_Z_INDEX = -100;

/**
 * Maximum z-index value.
 *
 * @since 3.0.0
 * @type {number}
 */
export const MAX_Z_INDEX = 1000;