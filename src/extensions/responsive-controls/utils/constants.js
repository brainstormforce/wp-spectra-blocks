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
 * Responsive attributes that live at the top level of a bucket, not under `.style`.
 *
 * Two shapes meet in this extension. Inside a `style` state object every value is
 * keyed by name at one level. In the bucket shape the rest of the extension passes
 * around, the shared style groups nest under `.style` while these sit beside the
 * block-specific keys — which is where the CSS generator, the block controllers
 * and `process_responsive_attributes()` all read them from.
 *
 * This is `RESPONSIVE_KEYS` without `style`: the top-level block attributes that
 * are tracked per breakpoint. WordPress core draws the same line — `layout`,
 * `fontSize`, `fontFamily` and `borderColor` are each their own block attribute
 * and are never nested inside `style`.
 *
 * Reading only the nested position dropped all four on migration, which took a
 * preset border colour or font size with it.
 *
 * Keep in sync with `ResponsiveControls::BUCKET_TOP_LEVEL_STYLE_KEYS`.
 *
 * @since x.x.x
 * @type {Array}
 */
export const BUCKET_TOP_LEVEL_STYLE_KEYS = Object.freeze( [
	'layout',
	'fontSize',
	'fontFamily',
	'borderColor',
] );

/**
 * The root attributes the editor may point at the previewed device.
 *
 * `BUCKET_TOP_LEVEL_STYLE_KEYS` minus `layout`. The other three are display
 * scratch in the strict sense — PHP strips every one of them before render, and
 * nothing but a control reads them — so pointing them at the previewed device
 * costs nothing even if a save serialises them.
 *
 * `layout` is not like them on either count. PHP keeps it for
 * `spectra/container` (`remove_conflicting_core_attributes()`), because core's
 * `wp_render_layout_support_flag` reads the real config from there, so scratch
 * written into it renders on the site. And a save serialises the whole block
 * tree, so one edit anywhere persisted the previewed device's layout as the
 * base of every container on the page — blocks the author never touched.
 *
 * Nor does it need pointing anywhere: measured on 7.1, core reads
 * `style[state].layout` itself for both the canvas and its own Layout panel.
 *
 * @since 1.0.7
 * @type {Array}
 */
export const SCRATCH_ROOT_ATTRIBUTE_KEYS = Object.freeze(
	BUCKET_TOP_LEVEL_STYLE_KEYS.filter( ( key ) => 'layout' !== key )
);

/**
 * Every key that can appear directly inside a `style` state object.
 *
 * The union of the shared style groups and the top-level responsive attributes.
 * Iterating only `STYLE_RESPONSIVE_KEYS` never visited `fontSize`, `fontFamily`
 * or `borderColor`, so they were neither read from nor written to a state.
 *
 * @since x.x.x
 * @type {Array}
 */
export const STATE_KEYS = Object.freeze( [
	...new Set( [ ...STYLE_RESPONSIVE_KEYS, ...BUCKET_TOP_LEVEL_STYLE_KEYS ] ),
] );

/**
 * Mapping between device type names and their corresponding breakpoint codes.
 *
 * - base:    applies at every width unless a narrower state overrides it
 * - @tablet: tablet band only
 * - @mobile: mobile band only
 *
 * These are WordPress core's viewport state names, adopted so that Spectra's
 * store and core's `style` attribute describe breakpoints identically.
 *
 * @since x.x.x
 * @type {Object}
 */

export const BREAKPOINT_TYPE_MAP = Object.freeze( {
	[ MOBILE ]: '@mobile',
	[ TABLET ]: '@tablet',
	[ DESKTOP ]: 'base',
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
/**
 * Whether core resolves each viewport state against the base layer alone.
 *
 * WordPress 7.1 introduced per-viewport block styles and made the two states
 * independent overrides of the base: `get_viewport_media_queries()` emits
 * mutually exclusive bands — `@tablet` is `(mobile < width <= tablet)`, so it
 * does not match at mobile widths — and every state is resolved with
 * `array_replace( base, state )`, never against a wider state. Mobile
 * therefore falls back to Desktop, not to Tablet.
 *
 * Below 7.1 neither the storage nor that renderer exists, and Spectra's own
 * generator is the only thing resolving breakpoints, so the older
 * tablet-then-desktop wording still describes what a reset does there.
 *
 * CAPABILITY-based, not version-based. PHP measures this once — with
 * `function_exists()` and `is_callable()` against core itself — and exports the
 * answer as `spectra_blocks_info.viewport_support`, so the editor and the
 * renderer can never disagree about which implementation a site is running.
 * See `Extensions\ResponsiveControls\ViewportSupport`.
 *
 * The version string is only a fallback for the case where that data is absent
 * (a stale cached script, a context that never enqueued the plugin's inline
 * data). It is deliberately the weaker signal: a 7.0 site running the Gutenberg
 * plugin has viewport states while its version says otherwise, and a partially
 * updated 7.0 install can carry core's states file on disk without ever loading
 * it — both were observed while building this.
 *
 * @since 1.0.7
 * @return {boolean} True when core's independent-viewport model applies.
 */
export const coreViewportStatesAreIndependent = () => {
	const support = window?.spectra_blocks_info?.viewport_support;

	if ( support && typeof support.hasViewportStates === 'boolean' ) {
		return support.hasViewportStates;
	}

	const version = window?.spectra_blocks_info?.wp_version;

	if ( typeof version !== 'string' ) {
		// Unknown core: assume the current model rather than describing a
		// cascade the generator no longer performs for new content.
		return true;
	}

	const parts = version.split( '.' );
	const major = parseInt( parts[ 0 ], 10 );

	if ( ! Number.isFinite( major ) ) {
		return true;
	}

	if ( major !== 7 ) {
		return major > 7;
	}

	const minor = parseInt( parts[ 1 ], 10 );

	return Number.isFinite( minor ) && minor >= 1;
};

/**
 * Whether this core build offers the responsive-styles view option.
 *
 * 7.1 exposes `responsiveEditingEnabled` on the editor settings; that is the
 * same flag core's own View menu uses to decide whether to offer the
 * "Responsive styles" toggle. The toggle's live on/off state lives in the
 * block-editor store behind core's private API and is not readable from a
 * plugin, so the hint is shown wherever the option EXISTS rather than only
 * while it is off — better a redundant hint than none on the version that
 * needs it. Falls back to the version check when the setting is absent.
 *
 * @since 1.0.7
 * @return {boolean} True when the responsive-styles view option exists.
 */
export const coreResponsiveEditingAvailable = () => {
	try {
		const settings = window.wp?.data?.select( 'core/editor' )?.getEditorSettings?.();

		if ( settings && undefined !== settings.responsiveEditingEnabled ) {
			return Boolean( settings.responsiveEditingEnabled );
		}
	} catch ( e ) {
		// Editor store unavailable — fall through to the version check.
	}

	return coreViewportStatesAreIndependent();
};

/**
 * Whether core's "Responsive styles" view option is currently ON.
 *
 * The state itself lives in the block-editor store behind core's private API,
 * but core's View dropdown renders it: the wrapper carries
 * `is-responsive-editing` while the option is enabled
 * (`editor-preview-dropdown` in core's PreviewDropdown). Reading that class is
 * public, needs no menu interaction, and stays correct when the user toggles
 * the option themselves.
 *
 * @since 1.0.7
 * @return {boolean} True while per-viewport editing is active.
 */
export const coreResponsiveEditingActive = () =>
	Boolean( document.querySelector( '.editor-preview-dropdown.is-responsive-editing' ) );

export const MUTUALLY_EXCLUSIVE_ATTR_PAIRS = [
	[ 'fontSize', 'style.typography.fontSize' ],
	[ 'borderColor', 'style.border.color' ],
];

/**
 * How a root attribute's value is expressed NESTED inside a `style` layer.
 *
 * The four `BUCKET_TOP_LEVEL_STYLE_KEYS` are block ATTRIBUTES, and they hold
 * preset slugs (`x-large`, `vivid-red`). Core has a second, equivalent way to
 * say the same thing inside `style` — a `var:preset|…` reference at a nested
 * path — and inside a viewport state that nested form is the ONLY one core
 * reads. Measured on 7.1: with "Responsive styles" on, a state holding
 * `fontSize: 'medium'` flat leaves the Font Size control showing nothing at
 * all, while the same state holding
 * `typography.fontSize: 'var:preset|font-size|medium'` selects Medium.
 *
 * So this map is what makes a per-device preset visible to core's own panels.
 * Two consumers share it, which is why it lives here rather than in either:
 *
 *   - the legacy migration, which writes states in this form
 *   - the root-attribute sync, which reads these paths to know when a state has
 *     already said something and the root attribute must be cleared rather than
 *     left on a stale preset
 *
 * `path` is relative to the layer, so it suits the root of `style` and a state
 * equally — a state holds the same groups at its own root.
 *
 * `layout` is absent: it is an object with no preset form, and core reads it
 * from the attribute in every mode. The `fontSize` and `borderColor` paths also
 * appear in `MUTUALLY_EXCLUSIVE_ATTR_PAIRS` as the custom counterpart of the
 * same attribute; the two must stay in step.
 *
 * @since 1.0.7
 * @type {Object}
 */
export const ROOT_ATTRIBUTE_PRESET_REFS = Object.freeze( {
	fontSize: Object.freeze( { path: 'typography.fontSize', preset: 'font-size' } ),
	fontFamily: Object.freeze( { path: 'typography.fontFamily', preset: 'font-family' } ),
	borderColor: Object.freeze( { path: 'border.color', preset: 'color' } ),
} );


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
		'topWidth',
		'topHeight',
		'bottomWidth',
		'bottomHeight',
		'advBgGradientAngle',
		'advBgGradientLocation1',
		'advBgGradientLocation2',
		'advBgGradientHoverAngle',
		'advBgGradientHoverLocation1',
		'advBgGradientHoverLocation2',
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
	'spectra/tabs-child-tab-trigger': [ 'width', 'height', 'minWidth', 'minHeight', 'maxWidth', 'maxHeight' ],
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
		'contentHeight',
		'background',
	],
	'spectra/popup-builder': [ 'width', 'height', 'background' ],
	'spectra/counter': [ 'prefixRightMargin', 'suffixLeftMargin' ],
	'spectra/post': [
		'columns',
		'columnGap',
		'rowGap',
		'slidesPerView',
		'spaceBetween',
		'arrowSize',
		'arrowDistance',
		'paginationTopMargin',
	],
	// Core Image block.
	'core/image': [ 'width', 'height', 'aspectRatio', 'scale' ],
	'spectra-pro/svg-animator': [ 'size', 'strokeWidth' ],
	'spectra-pro/svg-animators': [],
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
	'Carousel',
	'Grid & Masonry',
	'Shape Dividers',
	'Separator',
	// Core Image block - needed for reset handling.
	'Settings',
];

/**
 * Text domains a panel name may have been translated under.
 *
 * The reset handler recognises a panel by its label, and on a translated site
 * the label is whatever `__()` produced for the plugin that rendered it. The
 * list above is spelled in English; matching it against the DOM therefore has
 * to translate each entry under every domain a matching panel can come from.
 *
 * Core's panels (Dimensions, Typography, …) translate under `default`, this
 * plugin's under `spectra-blocks`. Spectra Pro renders panels with the same
 * English names under its own domain — a site whose Pro translation of
 * "General" differs from the free plugin's would otherwise have its resets
 * silently skipped there. Pro appends its domain through the
 * `spectra.responsive-controls.reset-panel-text-domains` filter; the panel
 * list itself is filterable as `spectra.responsive-controls.reset-panels`.
 *
 * @since 1.0.7
 * @type {Array<string>}
 */
export const RESPONSIVE_CONTROLS_PANEL_TEXT_DOMAINS = [ 'default', 'spectra-blocks' ];

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
