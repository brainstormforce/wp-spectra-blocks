/**
 * Responsive Conditions Extension Constants.
 *
 * @since x.x.x
 */

/**
 * External dependencies.
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Available device breakpoints for responsive conditions.
 *
 * @since x.x.x
 */
export const DEVICE_BREAKPOINTS = {
	DESKTOP: 'desktop',
	TABLET: 'tablet',
	MOBILE: 'mobile',
};

/**
 * Default responsive conditions state.
 *
 * @since x.x.x
 */
export const DEFAULT_RESPONSIVE_CONDITIONS = {
	hideOnDesktop: false,
	hideOnTablet: false,
	hideOnMobile: false,
};

/**
 * Media query breakpoints (matching Spectra's responsive controls system).
 *
 * @since x.x.x
 */
export const MEDIA_QUERIES = {
	DESKTOP: '(min-width: 1024px)', // lg - desktop
	TABLET: '(min-width: 768px) and (max-width: 1023.98px)', // md - tablet
	MOBILE: '(max-width: 767.98px)', // sm - mobile
};

/**
 * Array of blocks that should be excluded from responsive conditions.
 *
 * This can be extended by third-party developers using the WordPress filter system.
 *
 * @since x.x.x
 * @type {Array}
 */
export const EXCLUDED_BLOCKS = applyFilters( 'spectra.excludedResponsiveConditionsBlocks', [] );

/**
 * Array of blocks that explicitly support responsive conditions.
 *
 * This can be extended by third-party developers using the WordPress filter system.
 * Note: Blocks with Spectra prefixes are automatically supported regardless of this list.
 *
 * @since x.x.x
 * @type {Array}
 */
export const SUPPORTED_BLOCKS = applyFilters( 'spectra.supportedResponsiveConditionsBlocks', [ 'core/image' ] );

/**
 * Block name prefixes that are allowed to use responsive conditions by default.
 *
 * Any block with these prefixes will automatically receive responsive condition capabilities.
 *
 * @since x.x.x
 * @type {Array}
 */
export const ALLOWED_PREFIXES = [ 'spectra/', 'spectra-pro/' ];
