/**
 * HTML Attributes Extension Constants.
 *
 * @since x.x.x
 */

import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Blocks excluded from the HTML attributes extension.
 *
 * These are inner/child blocks that render as inner block content and do not
 * expose a meaningful standalone root wrapper that users would attribute.
 *
 * @since x.x.x
 */
export const EXCLUDED_BLOCKS = applyFilters( 'spectra.excludedHtmlAttributesBlocks', [
	'spectra/accordion-child-details',
	'spectra/post-template',
	'spectra/post-no-results',
] );

/**
 * Additional blocks explicitly opted in to html attributes support.
 * Extend via the spectra.supportedHtmlAttributesBlocks filter.
 *
 * @since x.x.x
 */
export const SUPPORTED_BLOCKS = applyFilters( 'spectra.supportedHtmlAttributesBlocks', [] );

/**
 * Block name prefixes that receive html attributes support by default.
 *
 * @since x.x.x
 */
export const ALLOWED_PREFIXES = [ 'spectra/', 'spectra-pro/' ];

/**
 * Attributes that are always blocked from the custom repeater.
 * class/id/style have dedicated paths (block supports, anchor, inline styles);
 * srcdoc can carry a full HTML document (script included) into an iframe.
 *
 * @since x.x.x
 */
export const BLOCKED_ATTRS = new Set( [ 'class', 'id', 'style', 'srcdoc' ] );

/**
 * Attributes whose values must be treated as URLs.
 *
 * @since x.x.x
 */
export const URL_ATTRS = new Set( [ 'href', 'cite', 'src', 'action', 'formaction', 'poster' ] );

/**
 * HTML boolean attributes. Valid with no value (e.g. a bare `download` on an
 * `<a>`, `reversed` on an `<ol>`, `open` on a `<dialog>`), so a custom-repeater
 * row that names one of these is complete even without a value. Mirrors the
 * PHP BOOLEAN_ATTRS list in includes/Extensions/class-html-attributes.php.
 *
 * @since x.x.x
 */
export const BOOLEAN_ATTRS = new Set( [
	'download',
	'reversed',
	'open',
	'required',
	'hidden',
	'inert',
	'autofocus',
	'readonly',
	'disabled',
	'multiple',
	'selected',
	'checked',
	'novalidate',
	'formnovalidate',
	'ismap',
	'default',
	'nomodule',
	'allowfullscreen',
] );

/**
 * Central tag-to-contextual-attribute map (Layer 1).
 *
 * Each key is an HTML tag name. The value is an array of field definitions
 * shown in the inspector directly below the tag switcher when that tag is active.
 *
 * Field shape:
 *   attribute {string}  — HTML attribute name
 *   label     {string}  — human-readable label shown in the panel
 *   type      {string}  — 'text' | 'url' | 'number' | 'select' | 'toggle'
 *   help      {string}  — one-line hint explaining why the attribute matters
 *   options   {Array}   — only for type 'select': array of { label, value } objects
 *
 * @since x.x.x
 */
export const TAG_ATTRIBUTE_CONFIG = {
	// --- Text content tags ---
	time: [
		{
			attribute: 'datetime',
			label: __( 'Date/Time', 'spectra-blocks' ),
			type: 'text',
			help: __( 'Machine–readable date/time value — e.g. 2024–01–15, 2024–01, 14:30', 'spectra-blocks' ),
		},
	],
	label: [
		{
			attribute: 'for',
			label: __( 'For (Input ID)', 'spectra-blocks' ),
			type: 'text',
			help: __( 'ID of the form control this label is associated with', 'spectra-blocks' ),
		},
	],
	blockquote: [
		{
			attribute: 'cite',
			label: __( 'Cite URL', 'spectra-blocks' ),
			type: 'url',
			help: __( 'URL of the source being quoted', 'spectra-blocks' ),
		},
	],

	// --- Headings ---
	h1: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Accessible name when the visible heading text is not descriptive enough', 'spectra-blocks' ) },
	],
	h2: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Accessible name when the visible heading text is not descriptive enough', 'spectra-blocks' ) },
	],
	h3: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Accessible name when the visible heading text is not descriptive enough', 'spectra-blocks' ) },
	],
	h4: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Accessible name when the visible heading text is not descriptive enough', 'spectra-blocks' ) },
	],
	h5: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Accessible name when the visible heading text is not descriptive enough', 'spectra-blocks' ) },
	],
	h6: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Accessible name when the visible heading text is not descriptive enough', 'spectra-blocks' ) },
	],

	// --- Generic containers ---
	div: [
		{ attribute: 'role', label: __( 'ARIA Role', 'spectra-blocks' ), type: 'text', help: __( 'Landmark or widget role (e.g. region, presentation, list)', 'spectra-blocks' ) },
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Accessible name for this element when no visible label is present', 'spectra-blocks' ) },
	],
	span: [
		{ attribute: 'role', label: __( 'ARIA Role', 'spectra-blocks' ), type: 'text', help: __( 'Widget role for this inline element', 'spectra-blocks' ) },
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Accessible name for screen readers', 'spectra-blocks' ) },
	],

	// --- Landmark elements ---
	header: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Distinguishes multiple headers on the page; omit for the main site header', 'spectra-blocks' ) },
	],
	footer: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Distinguishes multiple footers on the page; omit for the main site footer', 'spectra-blocks' ) },
	],
	article: [
		{ attribute: 'aria-labelledby', label: __( 'ARIA Labelledby', 'spectra-blocks' ), type: 'text', help: __( 'ID of the heading that names this article', 'spectra-blocks' ) },
	],
	section: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Section only becomes a landmark when it has an accessible name', 'spectra-blocks' ) },
		{ attribute: 'aria-labelledby', label: __( 'ARIA Labelledby', 'spectra-blocks' ), type: 'text', help: __( 'ID of the heading that labels this section (alternative to ARIA Label)', 'spectra-blocks' ) },
	],
	aside: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Label this complementary landmark', 'spectra-blocks' ) },
	],
	nav: [
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Required when a page has more than one nav element', 'spectra-blocks' ) },
	],
	figure: [
		{ attribute: 'aria-labelledby', label: __( 'ARIA Labelledby', 'spectra-blocks' ), type: 'text', help: __( 'ID of the figcaption element inside this figure', 'spectra-blocks' ) },
	],
	figcaption: [
		{ attribute: 'id', label: __( 'ID', 'spectra-blocks' ), type: 'text', help: __( 'Reference target for aria-labelledby on the parent figure', 'spectra-blocks' ) },
	],

	// --- Interactive ---
	dialog: [
		{ attribute: 'aria-labelledby', label: __( 'ARIA Labelledby', 'spectra-blocks' ), type: 'text', help: __( 'ID of the element that names this dialog', 'spectra-blocks' ) },
		{ attribute: 'aria-modal', label: __( 'ARIA Modal', 'spectra-blocks' ), type: 'toggle', help: __( 'Marks this as a modal dialog for assistive technology', 'spectra-blocks' ) },
		{ attribute: 'open', label: __( 'Open by default', 'spectra-blocks' ), type: 'toggle', help: __( 'Renders the dialog in the open (visible) state on page load', 'spectra-blocks' ) },
	],

	// --- Link ---
	a: [
		{ attribute: 'href', label: __( 'Href', 'spectra-blocks' ), type: 'url', help: __( 'URL this link points to', 'spectra-blocks' ) },
		{
			attribute: 'target',
			label: __( 'Target', 'spectra-blocks' ),
			type: 'select',
			options: [
				{ label: __( '— Default —', 'spectra-blocks' ), value: '' },
				{ label: '_blank', value: '_blank' },
				{ label: '_self', value: '_self' },
				{ label: '_parent', value: '_parent' },
				{ label: '_top', value: '_top' },
			],
			help: __( 'Where to open the link', 'spectra-blocks' ),
		},
		{ attribute: 'rel', label: __( 'Rel', 'spectra-blocks' ), type: 'text', help: __( 'Link relationship; noopener noreferrer is added automatically when target is _blank', 'spectra-blocks' ) },
		{ attribute: 'aria-label', label: __( 'ARIA Label', 'spectra-blocks' ), type: 'text', help: __( 'Accessible label when the visible link text is not descriptive', 'spectra-blocks' ) },
		{ attribute: 'download', label: __( 'Download', 'spectra-blocks' ), type: 'toggle', help: __( 'Prompts the browser to download the resource instead of navigating to it', 'spectra-blocks' ) },
	],

	// --- Lists ---
	ol: [
		{ attribute: 'start', label: __( 'Start', 'spectra-blocks' ), type: 'number', help: __( 'Starting value for the ordered list counter', 'spectra-blocks' ) },
		{
			attribute: 'type',
			label: __( 'Marker Type', 'spectra-blocks' ),
			type: 'select',
			options: [
				{ label: __( '— Default —', 'spectra-blocks' ), value: '' },
				{ label: '1 (numbers)', value: '1' },
				{ label: 'a (lowercase letters)', value: 'a' },
				{ label: 'A (uppercase letters)', value: 'A' },
				{ label: 'i (lowercase roman)', value: 'i' },
				{ label: 'I (uppercase roman)', value: 'I' },
			],
			help: __( 'Numbering type for list markers', 'spectra-blocks' ),
		},
		{ attribute: 'reversed', label: __( 'Reversed', 'spectra-blocks' ), type: 'toggle', help: __( 'Count down instead of up', 'spectra-blocks' ) },
	],
	li: [
		{ attribute: 'value', label: __( 'Value', 'spectra-blocks' ), type: 'number', help: __( 'Sets the counter for this item and all subsequent items', 'spectra-blocks' ) },
	],
};
