/**
 * Responsive Controls Constants
 *
 * This module defines all constants used by the responsive controls system.
 * It includes device types, breakpoint mappings, attribute keys to track,
 * and default data structures for responsive attributes.
 *
 * @since x.x.x
 */

/**
 * Performance constants for optimization configurations.
 */

/**
 * Batch size for device switching processing.
 *
 * Controls how many blocks are processed per batch during device switching.
 * Smaller values = faster initial feedback, lower memory usage.
 * Reduced to 1 for maximum memory efficiency and browser responsiveness.
 *
 * Performance tuning:
 * - 1 block: Maximum memory efficiency, prevents browser freezing
 * - 2 blocks: Minimal memory footprint, excellent for small batches
 * - 3-5 blocks: Good for medium projects
 * - 10+ blocks: Risk of memory issues with many blocks
 *
 * @since x.x.x
 * @type {number}
 */
export const DEVICE_SWITCH_BATCH_SIZE = 1;

/**
 * External dependencies.
 */
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Array of blocks that should be excluded from responsive controls.
 *
 * This can be extended by third-party developers using the WordPress filter system.
 *
 * @since x.x.x
 * @type {Array}
 */
export const EXCLUDED_BLOCKS = applyFilters( 'spectra.excludedResponsiveControlsBlocks', [] );

/**
 * Array of blocks that explicitly support responsive controls.
 *
 * This can be extended by third-party developers using the WordPress filter system.
 * Note: Blocks with Spectra prefixes are automatically supported regardless of this list.
 *
 * @since x.x.x
 * @type {Array}
 */
export const SUPPORTED_BLOCKS = applyFilters( 'spectra.supportedResponsiveControlsBlocks', [ 'core/image' ] );

/**
 * Block name prefixes that are allowed to use responsive controls by default.
 *
 * Any block with these prefixes will automatically receive responsive control capabilities.
 *
 * @since x.x.x
 * @type {Array}
 */
export const ALLOWED_PREFIXES = [ 'spectra/', 'spectra-pro/' ];

/**
 * Device view type constants.
 *
 * These constants represent the three device views available in the WordPress editor.
 *
 * @since x.x.x
 * @type {string}
 */
export const MOBILE = 'Mobile';
export const TABLET = 'Tablet';
export const DESKTOP = 'Desktop';

/**
 * Device fallback order for responsive attribute resolution.
 *
 * This defines the cascade order when looking for responsive values:
 * - Mobile view: First check mobile settings, then tablet, then desktop
 * - Tablet view: First check tablet settings, then desktop
 * - Desktop view: Only check desktop settings
 *
 * @since x.x.x
 * @type {Object}
 */
export const DEVICE_FALLBACK_ORDER = {
	[ MOBILE ]: [ 'sm', 'md', 'lg' ], // First try mobile, then tablet, then desktop.
	[ TABLET ]: [ 'md', 'lg' ], // First try tablet, then desktop.
	[ DESKTOP ]: [ 'lg' ], // Only desktop.
};

/**
 * Attribute keys that should be tracked for responsive behavior.
 *
 * These are the top-level attributes that can have different values
 * across different device types.
 *
 * @since x.x.x
 * @type {Array}
 */
export const RESPONSIVE_KEYS = Object.freeze( [ 'style', 'layout', 'fontSize', 'fontFamily', 'borderColor' ] );

/**
 * Specific style categories that should be tracked for responsive behavior.
 *
 * @since x.x.x
 * @type {Array}
 */
export const STYLE_RESPONSIVE_KEYS = Object.freeze( [ 'spacing', 'border', 'typography', 'shadow', 'layout' ] );

/**
 * Specific property paths that should be merged for responsive controls.
 *
 * These dot-notation paths define exactly which nested properties within attributes
 * should be tracked and merged when switching between device views.
 *
 * @since x.x.x
 * @type {Array}
 */
export const PROPERTIES_TO_MERGE = Object.freeze( [
	'style.spacing.padding',
	'style.spacing.margin',
	'style.spacing.blockGap',
	'style.border.top',
	'style.border.right',
	'style.border.bottom',
	'style.border.left',
	'style.border.width',
	'style.border.color',
	'style.border.style',
	'style.border.radius',
	'style.border.radius.topLeft',
	'style.border.radius.topRight',
	'style.border.radius.bottomLeft',
	'style.border.radius.bottomRight',
	'style.typography',
	'style.shadow',
	'style.layout',
	'layout',
	'fontSize',
	'fontFamily',
	'borderColor',
] );

/**
 * Inner properties of the background attribute that should have individual fallback support.
 *
 * These properties within the background object can inherit independently from parent devices.
 *
 * @since x.x.x
 * @type {Array}
 */
export const BACKGROUND_INNER_PROPERTIES = Object.freeze( [
	'type',
	'media',
	'useOverlay',
	'backgroundSize',
	'backgroundWidth',
	'backgroundRepeat',
	'backgroundPosition',
	'backgroundAttachment',
	'positionMode',
	'positionCentered',
	'positionX',
	'positionY',
] );

/**
 * Mapping between device type names and their corresponding breakpoint codes.
 *
 * - lg: Large screens (Desktop)
 * - md: Medium screens (Tablet)
 * - sm: Small screens (Mobile)
 *
 * @since x.x.x
 * @type {Object}
 */
export const BREAKPOINT_TYPE_MAP = Object.freeze( {
	[ MOBILE ]: 'sm',
	[ TABLET ]: 'md',
	[ DESKTOP ]: 'lg',
} );

/**
 * Pairs of mutually exclusive attributes that cannot coexist.
 *
 * When one attribute in a pair is set, the other should be removed.
 * This handles WordPress's pattern of having preset values and custom values
 * that should not be applied simultaneously.
 *
 * Format: [presetAttributeKey, customAttributePath]
 *
 * @since x.x.x
 * @type {Array}
 */
export const MUTUALLY_EXCLUSIVE_ATTR_PAIRS = [
	[ 'fontSize', 'style.typography.fontSize' ],
	[ 'borderColor', 'style.border.color' ],
];

// ===================================================================
// Block-Specific Constants
// ===================================================================

/**
 * Block-specific responsive keys mapping.
 *
 * Maps each block name to an array of attributes that should have responsive behavior.
 * Only includes attributes that actually exist in the block's block.json definition.
 *
 * @since x.x.x
 * @type {Object}
 */
export const BLOCK_RESPONSIVE_KEYS = Object.freeze( {
	'spectra/container': [
		'minWidth',
		'minHeight',
		'maxWidth',
		'maxHeight',
		'width',
		'height',
		'background',
		'overlayType',
		'overlayImage',
		'overlayPosition',
		'overlayPositionMode',
		'overlayPositionCentered',
		'overlayPositionX',
		'overlayPositionY',
		'overlayAttachment',
		'overlayRepeat',
		'overlaySize',
		'overlayCustomWidth',
		'overlayBlendMode',
		'overlayOpacity',
		'orientationReverse',
	],
	'spectra/google-map': [ 'height' ],
	'spectra/content': [
		'enableTextShadow',
		'textShadowColor',
		'textShadowBlur',
		'textShadowOffsetX',
		'textShadowOffsetY',
	],
	'spectra/button': [ 'size', 'gap' ],
	'spectra/buttons': [],
	'spectra/icon': [ 'size' ],
	'spectra/icons': [],
	'spectra/accordion': [ 'size' ],
	'spectra/accordion-child-header-icon': [ 'size' ],
	'spectra/tabs': [ 'size' ],
	'spectra/tabs-child-tab-button': [ 'size', 'gap' ],
	'spectra/countdown': [ 'minWidth', 'minHeight', 'maxWidth', 'maxHeight', 'width', 'height' ],
	'spectra/list': [ 'iconSize' ],
	'spectra/list-child-icon': [ 'iconSize' ],
	'spectra/slider': [
		'sliderHeight',
		'arrowDistance',
		'navigationSize',
		'navigationIconSize',
		'paginationTopMargin',
		'background',
		'slidesPerView',
		'spaceBetween',
	],
	'spectra/slider-child': [ 'background' ],
	'spectra/separator': [ 'separatorWidth', 'separatorHeight', 'separatorSize', 'separatorStyle', 'separatorAlign' ],
	'spectra/modal-child-button': [ 'size', 'gap' ],
	'spectra/modal-child-icon': [ 'size' ],
	'spectra/modal-child-popup-close-icon': [ 'size' ],
	'spectra/modal-popup-content': [
		'containerWidth',
		'containerHeight',
		'maxContainerHeight',
		'contentHeight',
		'background',
	],
	'spectra/popup-builder': [ 'width', 'height', 'background' ],
	'spectra/counter': [ 'prefixRightMargin', 'suffixLeftMargin' ],
	// Core Image block.
	'core/image': [ 'width', 'height', 'aspectRatio', 'scale' ],
} );

/**
 * List of blocks and their attributes to maintain for backward compatibility.
 *
 * @since x.x.x
 * @type {Object}
 */
export const BACKWARD_COMPATIBILITY_ATTRIBUTES = {
	'spectra/separator': [ 'separatorStyle', 'separatorAlign' ],
};

/**
 * Responsive control panels that should trigger reset handling.
 *
 * These panels are used to determine which responsive controls
 * should be reset when the "Reset" or "Reset ALL" buttons are clicked.
 *
 * IMPORTANT: Panel names must match WordPress core capitalization exactly.
 * These strings are translated by WordPress core using the 'default' textdomain.
 *
 * @since x.x.x
 * @type {Array}
 */
export const RESPONSIVE_CONTROLS_PANELS = [
	'Spacing',
	'Typography',
	'Border & Shadow',
	'Border',
	'Shadow',
	'Layout',
	'Background',
	'Dimensions',
	'Content',
	'General', // Note: important for Slider setting reset.
	'Flex Direction',
	'Overlay Settings',
	'Separator',
	// Core Image block - needed for reset handling.
	'Settings',
];

/**
 * Global constants for DOM selectors and comparison values.
 *
 * @since x.x.x
 *
 * @constant {string} DROPDOWN_MENU_SELECTOR - Selector for dropdown menu elements
 * @constant {string} MENU_ITEM_BUTTON_SELECTOR - Selector for menu item buttons
 * @constant {string} MENU_ITEM_SELECTOR - Selector for menu items
 * @constant {Set<string>} RESET_TEXTS - Allowed reset text variations (English fast-path)
 */
export const DROPDOWN_MENU_SELECTOR = '.components-dropdown-menu__menu';
export const MENU_ITEM_BUTTON_SELECTOR = '.components-menu-item__button';
export const MENU_ITEM_SELECTOR = '.components-menu-item__item';

/**
 * English reset texts for fast-path checking.
 * For translated versions, use the isResetText() helper function.
 *
 * @since x.x.x
 * @type {Set<string>}
 */
export const RESET_TEXTS = new Set( [ 'reset', 'reset all' ] );

/**
 * Check if text represents a reset action (supports translations).
 *
 * IMPORTANT: Handles translated reset button text for non-English languages.
 * WordPress core translates "Reset" and "Reset all" to the current language.
 *
 * @since x.x.x
 *
 * @param {string} text - The text to check (should be already lowercased)
 * @return {boolean} True if text represents a reset action
 */
export const isResetText = ( text ) => {
	if ( ! text || typeof text !== 'string' ) {
		return false;
	}

	// Fast path: Check English first
	if ( RESET_TEXTS.has( text ) ) {
		return true;
	}

	// Check translated versions (WordPress core textdomain)
	// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
	const translatedReset = __( 'Reset', 'default' ).toLowerCase();
	// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
	const translatedResetAll = __( 'Reset all', 'default' ).toLowerCase();

	return text === translatedReset || text === translatedResetAll;
};

/**
 * Check if text represents "Reset all" action specifically (supports translations).
 *
 * IMPORTANT: Handles translated "Reset all" button text for non-English languages.
 *
 * @since x.x.x
 *
 * @param {string} text - The text to check (should be already lowercased)
 * @return {boolean} True if text represents "Reset all"
 */
export const isResetAllText = ( text ) => {
	if ( ! text || typeof text !== 'string' ) {
		return false;
	}

	// Fast path: Check English first
	if ( text === 'reset all' ) {
		return true;
	}

	// Check translated version (WordPress core textdomain)
	// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
	const translatedResetAll = __( 'Reset all', 'default' ).toLowerCase();

	return text === translatedResetAll;
};
