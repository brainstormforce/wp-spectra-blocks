/**
 * Display Conditions Extension Constants.
 *
 * @since x.x.x
 */

/**
 * External dependencies.
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Default display conditions state.
 *
 * @since x.x.x
 */
export const DEFAULT_DISPLAY_CONDITIONS = {
	hideWhenLoggedIn: false,
	hideWhenLoggedOut: false,
	hideForRole: '',
	hideForBrowser: '',
	hideForOS: '',
	hideOnDays: [],
};

/**
 * Browser options for display conditions.
 *
 * @since x.x.x
 */
export const BROWSER_OPTIONS = [
	{ label: 'Select Browser', value: '' },
	{ label: 'Firefox', value: 'firefox' },
	{ label: 'Chrome', value: 'chrome' },
	{ label: 'Opera Mini', value: 'opera' },
	{ label: 'Opera', value: 'opera' },
	{ label: 'Safari', value: 'safari' },
	{ label: 'Edge', value: 'edge' },
];

/**
 * Operating system options for display conditions.
 *
 * @since x.x.x
 */
export const OS_OPTIONS = [
	{ label: 'Select OS', value: '' },
	{ label: 'iOS', value: 'iphone' },
	{ label: 'Android', value: 'android' },
	{ label: 'Windows', value: 'windows' },
	{ label: 'OpenBSD', value: 'open_bsd' },
	{ label: 'SunOS', value: 'sun_os' },
	{ label: 'Linux', value: 'linux' },
	{ label: 'Mac OS', value: 'mac_os' },
];

/**
 * Day of week options for display conditions.
 *
 * @since x.x.x
 */
export const DAY_OPTIONS = [
	'monday',
	'tuesday',
	'wednesday',
	'thursday',
	'friday',
	'saturday',
	'sunday',
];

/**
 * Array of blocks that should be excluded from display conditions.
 *
 * This can be extended by third-party developers using the WordPress filter system.
 *
 * @since x.x.x
 * @type {Array}
 */
export const EXCLUDED_BLOCKS = applyFilters(
	'spectra.excludedDisplayConditionsBlocks',
	[]
);

/**
 * Array of blocks that explicitly support display conditions.
 *
 * This can be extended by third-party developers using the WordPress filter system.
 * Note: Blocks with Spectra prefixes are automatically supported regardless of this list.
 *
 * @since x.x.x
 * @type {Array}
 */
export const SUPPORTED_BLOCKS = applyFilters(
	'spectra.supportedDisplayConditionsBlocks',
	[ 'core/image' ]
);

/**
 * Block name prefixes that are allowed to use display conditions by default.
 *
 * Any block with these prefixes will automatically receive display condition capabilities.
 *
 * @since x.x.x
 * @type {Array}
 */
export const ALLOWED_PREFIXES = [ 'spectra/', 'spectra-pro/' ];
