/**
 * BlockDefaultsPanel — full port of the dashboard BlockDefaults tab.
 *
 * Reads/writes from window.spectra_editor_gs (localized by Pro plugin).
 * Uses iframe postMessage for live preview (same protocol as dashboard).
 *
 * @since x.x.x
 */

import { useState, useEffect, useCallback, useRef, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';
import Select from 'react-select';
import getClassOptions from '../../data/class-options';
import { useGBSConfig } from '../../hooks/useGBSConfig';
import { regenerateEditorCSS } from '../../utils/liveVars.js';

// ─── Constants ────────────────────────────────────────────────────────────────

const PSEUDO_ELEMENTS = [ 'before', 'after', 'first-line', 'first-letter', 'selection', 'marker', 'placeholder' ];

const isPseudoElement = ( p ) => PSEUDO_ELEMENTS.includes( p );
const isCompoundSelector = ( p ) => p && p.includes( '::' );

const getCSSSelector = ( p ) => {
	if ( ! p || p === 'default' ) {return '';}
	if ( isCompoundSelector( p ) ) {
		const [ pc, pe ] = p.split( '::' );
		return `:${ pc }::${ pe }`;
	}
	if ( isPseudoElement( p ) ) {return `::${ p }`;}
	return `:${ p }`;
};

const getPseudoDisplayLabel = ( p ) => {
	if ( ! p || p === 'default' ) {return __( 'Default', 'spectra-blocks' );}
	return getCSSSelector( p );
};

const PSEUDO_STATES = [ 'default', 'hover', 'focus-visible' ];

/**
 * Full block options list matching dashboard exactly.
 */
const blockOptions = [
	{ value: 'dropdown-section', heading: __( 'Core Blocks', 'spectra-blocks' ) },
	{ value: 'core-heading', label: __( 'Heading', 'spectra-blocks' ) },
	{ value: 'core-image', label: __( 'Image', 'spectra-blocks' ) },
	{ value: 'dropdown-section', heading: __( 'Common Blocks', 'spectra-blocks' ) },
	{ value: 'container', label: __( 'Container', 'spectra-blocks' ) },
	{ value: 'content', label: __( 'Text', 'spectra-blocks' ) },
	{ value: 'google-map', label: __( 'Google Map', 'spectra-blocks' ) },
	{ value: 'separator', label: __( 'Separator', 'spectra-blocks' ) },
	{ value: 'dropdown-section', heading: __( 'Button Blocks', 'spectra-blocks' ) },
	{ value: 'buttons', label: __( 'Buttons', 'spectra-blocks' ), type: [ 'wrapper', 'parent' ] },
	{ value: 'button', label: __( 'Button', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'dropdown-section', heading: __( 'Icon Blocks', 'spectra-blocks' ) },
	{ value: 'icons', label: __( 'Icons', 'spectra-blocks' ), type: [ 'wrapper', 'parent' ] },
	{ value: 'icon', label: __( 'Icon', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'dropdown-section', heading: __( 'List Blocks', 'spectra-blocks' ) },
	{ value: 'list', label: __( 'List', 'spectra-blocks' ), type: [ 'wrapper', 'parent' ] },
	{ value: 'list-child-item', label: __( 'List Item', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'list-child-icon', label: __( 'List Icon', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'dropdown-section', heading: __( 'Tabs Blocks', 'spectra-blocks' ) },
	{ value: 'tabs', label: __( 'Tabs', 'spectra-blocks' ), type: [ 'wrapper', 'parent' ] },
	{ value: 'tabs-child-tab-wrapper', label: __( 'Tab Area', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'tabs-child-tab-button', label: __( 'Tab', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'tabs-child-tabpanel', label: __( 'Panel', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'dropdown-section', heading: __( 'Accordion Blocks', 'spectra-blocks' ) },
	{ value: 'accordion', label: __( 'Accordion', 'spectra-blocks' ), type: [ 'wrapper', 'parent' ] },
	{ value: 'accordion-child-item', label: __( 'Accordion Item', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'accordion-child-header', label: __( 'Header', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'accordion-child-details', label: __( 'Details', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'accordion-child-header-content', label: __( 'Header Title', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'accordion-child-header-icon', label: __( 'Header Icon', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'dropdown-section', heading: __( 'Slider Blocks', 'spectra-blocks' ) },
	{ value: 'slider', label: __( 'Slider', 'spectra-blocks' ), type: [ 'wrapper', 'parent' ] },
	{ value: 'slider-child', label: __( 'Slide', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'dropdown-section', heading: __( 'Countdown Blocks', 'spectra-blocks' ) },
	{ value: 'countdown', label: __( 'Countdown', 'spectra-blocks' ), type: [ 'wrapper', 'parent' ] },
	{ value: 'countdown-child-day', label: __( 'Day Container', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'countdown-child-hour', label: __( 'Hour Container', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'countdown-child-minute', label: __( 'Minute Container', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'countdown-child-second', label: __( 'Second Container', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'countdown-child-number', label: __( 'Number', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'countdown-child-label', label: __( 'Label', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'countdown-child-separator', label: __( 'Separator', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'countdown-child-expiry-wrapper', label: __( 'Expiry Wrapper', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'dropdown-section', heading: __( 'Modal Blocks', 'spectra-blocks' ) },
	{ value: 'modal', label: __( 'Modal', 'spectra-blocks' ), type: [ 'wrapper', 'parent' ] },
	{ value: 'modal-child-trigger', label: __( 'Trigger', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'modal-child-button', label: __( 'Trigger: Button', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'modal-child-content', label: __( 'Trigger: Content', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'modal-child-icon', label: __( 'Trigger: Icon', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'modal-popup', label: __( 'Overlay', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'modal-popup-content', label: __( 'Popup', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'modal-child-popup-close-icon', label: __( 'Close Icon', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'dropdown-section', heading: __( 'Loop Builder Blocks', 'spectra-blocks' ) },
	{ value: 'loop-builder', label: __( 'Loop Builder', 'spectra-blocks' ), type: [ 'wrapper', 'parent' ] },
	{ value: 'loop-builder-child-search', label: __( 'Search', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-filter', label: __( 'Filter', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-filter-select', label: __( 'Filter: Dropdown', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-filter-checkbox', label: __( 'Filter: Checkbox', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-filter-button', label: __( 'Filter: Button', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-reset-all-button', label: __( 'Reset Button', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-sort', label: __( 'Sort', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-template', label: __( 'Template', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-pagination', label: __( 'Pagination', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-pagination-previous-button', label: __( 'Previous Button', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-pagination-page-numbers-button', label: __( 'Page Numbers', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-pagination-next-button', label: __( 'Next Button', 'spectra-blocks' ), type: [ 'child' ] },
	{ value: 'loop-builder-child-no-results', label: __( 'No Results', 'spectra-blocks' ), type: [ 'child' ] },
];

/**
 * Find nearest parent-type block in the same group.
 *
 * @param {string} blockValue Block value key.
 * @return {string} Parent block value.
 */
const getParentBlock = ( blockValue ) => {
	const idx = blockOptions.findIndex( ( o ) => o.value === blockValue );
	if ( idx === -1 ) {return blockValue;}
	if ( blockOptions[ idx ].type?.includes( 'parent' ) ) {return blockValue;}
	for ( let i = idx - 1; i >= 0; i-- ) {
		if ( blockOptions[ i ].value === 'dropdown-section' ) {break;}
		if ( blockOptions[ i ].type?.includes( 'parent' ) ) {return blockOptions[ i ].value;}
	}
	return blockValue;
};

/**
 * Map block value → Gutenberg block name + inner block config.
 *
 * @param {string} blockValue Block value key.
 * @param {Object} context    Additional context (e.g. originalBlock).
 * @return {Object|null} Block data for preview, or null if not found.
 */
const getBlockDataForPreview = ( blockValue, context = {} ) => {
	const gs = window.spectra_editor_gs;
	const blockNameMapping = {
		'core-heading': 'core/heading',
		'core-image': 'core/image',
		'buttons': 'spectra/buttons',
		'button': 'spectra/button',
		'icons': 'spectra/icons',
		'icon': 'spectra/icon',
		'list': 'spectra/list',
		'list-child-item': 'spectra/list-child-item',
		'list-child-icon': 'spectra/list-child-icon',
		'container': 'spectra/container',
		'content': 'spectra/content',
		'google-map': 'spectra/google-map',
		'separator': 'spectra/separator',
		'tabs': 'spectra/tabs',
		'tabs-child-tab-wrapper': 'spectra/tabs-child-tab-wrapper',
		'tabs-child-tab-button': 'spectra/tabs-child-tab-button',
		'tabs-child-tabpanel': 'spectra/tabs-child-tabpanel',
		'accordion': 'spectra/accordion',
		'accordion-child-item': 'spectra/accordion-child-item',
		'accordion-child-header': 'spectra/accordion-child-header',
		'accordion-child-details': 'spectra/accordion-child-details',
		'accordion-child-header-content': 'spectra/accordion-child-header-content',
		'accordion-child-header-icon': 'spectra/accordion-child-header-icon',
		'slider': 'spectra/slider',
		'slider-child': 'spectra/slider-child',
		'countdown': 'spectra/countdown',
		'countdown-child-day': 'spectra/countdown-child-day',
		'countdown-child-hour': 'spectra/countdown-child-hour',
		'countdown-child-minute': 'spectra/countdown-child-minute',
		'countdown-child-second': 'spectra/countdown-child-second',
		'countdown-child-number': 'spectra/countdown-child-number',
		'countdown-child-label': 'spectra/countdown-child-label',
		'countdown-child-separator': 'spectra/countdown-child-separator',
		'countdown-child-expiry-wrapper': 'spectra/countdown-child-expiry-wrapper',
		'modal': 'spectra/modal',
		'modal-child-trigger': 'spectra/modal-child-trigger',
		'modal-child-button': 'spectra/modal-child-button',
		'modal-child-content': 'spectra/modal-child-content',
		'modal-child-icon': 'spectra/modal-child-icon',
		'modal-popup': 'spectra/modal-popup',
		'modal-child-popup-close-icon': 'spectra/modal-child-popup-close-icon',
		'modal-popup-content': 'spectra/modal-popup-content',
		'loop-builder': 'spectra-pro/loop-builder',
		'loop-builder-child-search': 'spectra-pro/loop-builder-child-search',
		'loop-builder-child-filter': 'spectra-pro/loop-builder-child-filter',
		'loop-builder-child-filter-select': 'spectra-pro/loop-builder-child-filter-select',
		'loop-builder-child-filter-checkbox': 'spectra-pro/loop-builder-child-filter-checkbox',
		'loop-builder-child-filter-button': 'spectra-pro/loop-builder-child-filter-button',
		'loop-builder-child-reset-all-button': 'spectra-pro/loop-builder-child-reset-all-button',
		'loop-builder-child-sort': 'spectra-pro/loop-builder-child-sort',
		'loop-builder-child-template': 'spectra-pro/loop-builder-child-template',
		'loop-builder-child-pagination': 'spectra-pro/loop-builder-child-pagination',
		'loop-builder-child-pagination-previous-button': 'spectra-pro/loop-builder-child-pagination-previous-button',
		'loop-builder-child-pagination-page-numbers-button': 'spectra-pro/loop-builder-child-pagination-page-numbers-button',
		'loop-builder-child-pagination-next-button': 'spectra-pro/loop-builder-child-pagination-next-button',
		'loop-builder-child-no-results': 'spectra-pro/loop-builder-child-no-results',
	};

	const blockName = blockNameMapping[ blockValue ];
	if ( ! blockName ) {return null;}

	const blockData = {
		name: blockName,
		attributes: { isPreview: false, variationSelected: true },
	};

	switch ( blockValue ) {
		case 'core-heading':
			blockData.attributes.content = __( 'This is a sample heading for preview', 'spectra-blocks' );
			blockData.attributes.level = 2;
			break;
		case 'core-image':
			blockData.attributes.url = `${ gs?.pro_plugin_url || '' }assets/block-previews/global-styles/1m-sale-social-share.webp`;
			blockData.attributes.alt = __( 'Sample image for preview', 'spectra-blocks' );
			break;
		case 'container':
			blockData.inner_blocks = [ { name: 'spectra/content', attributes: { text: __( 'This is container content for preview', 'spectra-blocks' ) } } ];
			break;
		case 'content':
			blockData.attributes.text = __( 'This is sample content text for preview', 'spectra-blocks' );
			break;
		case 'google-map':
			blockData.attributes.address = __( 'New York, NY, USA', 'spectra-blocks' );
			blockData.attributes.zoom = 12;
			break;
		case 'buttons':
			blockData.inner_blocks = [
				{ name: 'spectra/button', attributes: { isPreview: false, text: __( 'Button 1', 'spectra-blocks' ) } },
				{ name: 'spectra/button', attributes: { isPreview: false, text: __( 'Button 2', 'spectra-blocks' ) } },
			];
			break;
		case 'icons':
			blockData.inner_blocks = [
				{ name: 'spectra/icon', attributes: { isPreview: false, icon: 'star' } },
				{ name: 'spectra/icon', attributes: { isPreview: false, icon: 'heart' } },
			];
			break;
		case 'list':
			blockData.inner_blocks = [ 1, 2, 3 ].map( ( i ) => ( {
				name: 'spectra/list-child-item',
				attributes: { index: i },
				inner_blocks: [
					{ name: 'spectra/list-child-icon', attributes: { itemIndex: i } },
					{ name: 'spectra/content', attributes: { text: sprintf( __( 'List item %d', 'spectra-blocks' ), i ) } },
				],
			} ) );
			break;
		case 'tabs':
			blockData.attributes.currentTab = 0;
			blockData.inner_blocks = [
				{
					name: 'spectra/tabs-child-tab-wrapper',
					attributes: {},
					inner_blocks: [ 1, 2, 3 ].map( ( i ) => ( {
						name: 'spectra/tabs-child-tab-button',
						attributes: { currentTab: i - 1, text: sprintf( __( 'Tab %d', 'spectra-blocks' ), i ) },
					} ) ),
				},
				...[ 0, 1, 2 ].map( ( i ) => ( {
					name: 'spectra/tabs-child-tabpanel',
					attributes: { currentTab: i },
					inner_blocks: [ { name: 'spectra/content', attributes: { text: sprintf( __( 'Content of Tab %d', 'spectra-blocks' ), i + 1 ) } } ],
				} ) ),
			];
			break;
		case 'accordion':
			blockData.inner_blocks = [ 1, 2, 3 ].map( ( i ) => ( {
				name: 'spectra/accordion-child-item',
				attributes: {},
				inner_blocks: [
					{
						name: 'spectra/accordion-child-header',
						attributes: {},
						inner_blocks: [
							{ name: 'spectra/accordion-child-header-icon', attributes: {} },
							{ name: 'spectra/accordion-child-header-content', attributes: { text: sprintf( __( 'Item %d', 'spectra-blocks' ), i ) } },
						],
					},
					{
						name: 'spectra/accordion-child-details',
						attributes: {},
						inner_blocks: [ { name: 'spectra/content', attributes: { tagName: 'p', text: sprintf( __( 'Details for item %d', 'spectra-blocks' ), i ) } } ],
					},
				],
			} ) );
			break;
		case 'slider':
			blockData.inner_blocks = [ 1, 2, 3 ].map( ( i ) => ( {
				name: 'spectra/slider-child',
				attributes: {},
				inner_blocks: [ { name: 'spectra/content', attributes: { text: sprintf( __( 'Slide %d content for preview', 'spectra-blocks' ), i ) } } ],
			} ) );
			break;
		case 'countdown':
			blockData.attributes.timerEndAction = 'replace';
			if ( context.originalBlock === 'countdown-child-expiry-wrapper' ) {
				blockData.attributes.editorInnerBlocksPreview = true;
			}
			blockData.inner_blocks = [
				{ name: 'spectra/countdown-child-day', attributes: {} },
				{ name: 'spectra/countdown-child-hour', attributes: {} },
				{ name: 'spectra/countdown-child-minute', attributes: {} },
				{ name: 'spectra/countdown-child-second', attributes: {} },
				{ name: 'spectra/countdown-child-separator', attributes: {} },
				{
					name: 'spectra/countdown-child-expiry-wrapper',
					attributes: { lock: { move: true, remove: true }, style: { typography: { fontSize: '1rem' } } },
					inner_blocks: [ { name: 'spectra/content', attributes: { text: __( 'Preview of expiry wrapper', 'spectra-blocks' ) } } ],
				},
			];
			break;
		case 'modal':
			blockData.attributes.isVisible = true;
			if ( context.originalBlock === 'modal-child-icon' ) {
				blockData.attributes.modalTrigger = 'icon';
			} else if ( context.originalBlock === 'modal-child-content' ) {
				blockData.attributes.modalTrigger = 'text';
			}
			blockData.inner_blocks = [
				{
					name: 'spectra/modal-child-trigger',
					attributes: {},
					inner_blocks: [
						{ name: 'spectra/modal-child-button', attributes: {} },
						{ name: 'spectra/modal-child-icon', attributes: {} },
						{ name: 'spectra/modal-child-content', attributes: {} },
					],
				},
				{
					name: 'spectra/modal-popup',
					attributes: {},
					inner_blocks: [
						{ name: 'spectra/modal-child-popup-close-icon', attributes: {} },
						{
							name: 'spectra/modal-popup-content',
							attributes: {},
							inner_blocks: [ { name: 'spectra/content', attributes: { tagName: 'p', text: __( 'Modal content preview', 'spectra-blocks' ) } } ],
						},
					],
				},
			];
			break;
	}

	return blockData;
};

// ─── React Select styles (matches GBS palette) ────────────────────────────────

const SELECT_STYLES = {
	control: ( base ) => ( {
		...base,
		'minHeight': '32px',
		'borderColor': 'var(--gbs-border, #ddd)',
		'borderRadius': '4px',
		'boxShadow': 'none',
		'&:hover': { borderColor: '#6b7cff' },
	} ),
	multiValue: ( base ) => ( { ...base, backgroundColor: '#f0f3ff' } ),
	multiValueLabel: ( base ) => ( { ...base, color: '#374151', fontSize: '12px' } ),
	multiValueRemove: ( base ) => ( {
		...base,
		'color': '#6b7280',
		'&:hover': { backgroundColor: '#dbe0ff', color: '#374151' },
	} ),
	placeholder: ( base ) => ( { ...base, fontSize: '13px', color: '#999' } ),
	menu: ( base ) => ( { ...base, zIndex: 99999 } ),
};

// ─── Main component ───────────────────────────────────────────────────────────

/**
 * BlockDefaultsPanel — full port of the dashboard BlockDefaults UI.
 *
 * @since x.x.x
 * @return {Element}
 */
const BlockDefaultsPanel = () => {
	const gs = window.spectra_editor_gs ?? null;

	const iframeRef = useRef( null );

	// ── State ──────────────────────────────────────────────────────────────────

	const [ selectedBlock, setSelectedBlock ]         = useState( '' );
	const [ selectedPseudo, setSelectedPseudo ]       = useState( 'default' );
	const [ selectedClasses, setSelectedClasses ]     = useState( [] );
	const [ blockDefaults, setBlockDefaults ]         = useState( gs?.block_defaults ?? {} );
	const [ isSaving, setIsSaving ]                   = useState( false );
	const [ tempClassMemory, setTempClassMemory ]     = useState( {} );
	const [ isIframeLoading, setIsIframeLoading ]     = useState( true );
	const [ showRemoveAll, setShowRemoveAll ]         = useState( false );
	const [ isRemovingAll, setIsRemovingAll ]         = useState( false );
	const [ isRemoveReady, setIsRemoveReady ]         = useState( false );
	const [ isTogglingEnabled, setIsTogglingEnabled ] = useState( false );
	const [ blockDefaultsEnabled, setBlockDefaultsEnabled ] = useState( () => {
		const v = gs?.block_defaults_enabled;
		return ( v === false || v === 0 || v === '0' || v === '' ) ? false : true;
	} );
	const [ successMsg, setSuccessMsg ]               = useState( '' );
	const [ errorMsg, setErrorMsg ]                   = useState( '' );

	// The user's saved custom colours (config.custom_colors) — appended to the
	// colour class groups so they're selectable as block defaults. Recomputed
	// when the config loads/changes (a useState initializer would miss the async
	// fetch and never show colours saved after mount).
	const { config: sgConfig } = useGBSConfig();
	const customColorSlugs = useMemo( () => Object.keys( sgConfig?.custom_colors ?? {} ), [ sgConfig ] );

	const availableClasses = useMemo( () => {
		// Strip default-{block} classes — they are the OUTPUT of block defaults, not valid inputs.
		// Assigning them would create circular CSS aggregation.
		return getClassOptions( null, customColorSlugs ).map( ( group ) => ( {
			...group,
			options: ( group.options ?? [] ).filter( ( opt ) => ! /^default-/.test( opt.value ) ),
		} ) ).filter( ( group ) => ( group.options ?? [] ).length > 0 );
	}, [ customColorSlugs ] );

	// ── Helpers ────────────────────────────────────────────────────────────────

	const showSuccess = ( msg ) => {
		setSuccessMsg( msg );
		setErrorMsg( '' );
		setTimeout( () => setSuccessMsg( '' ), 3000 );
	};

	const showError = ( msg ) => {
		setErrorMsg( msg );
		setSuccessMsg( '' );
	};

	const sendToIframe = ( type, payload = {} ) => {
		const iframe = iframeRef.current;
		if ( ! iframe?.contentWindow ) {return;}
		iframe.contentWindow.postMessage( { type, ...payload }, '*' );
	};

	const sendBlockToIframe = useCallback( ( blockValue, context = {} ) => {
		const blockData = getBlockDataForPreview( blockValue, context );
		if ( ! blockData ) {return;}
		sendToIframe( 'spectra-gs-render-block', { blockData } );
	}, [] );

	const clearIframePreview = () => sendToIframe( 'spectra-gs-clear-preview' );

	// ── AJAX ──────────────────────────────────────────────────────────────────

	const updateClassInStylesheet = useCallback( async ( action, blockName, pseudoSelector, className ) => {
		try {
			const fd = new window.FormData();
			fd.append( 'action', 'spectra_pro_gs_update_class_in_stylesheet' );
			fd.append( 'security', gs.ajax_nonce );
			fd.append( 'action_type', action );
			fd.append( 'block_name', blockName );
			fd.append( 'pseudo_selector', pseudoSelector );
			fd.append( 'class_name', className );

			const resp = await apiFetch( { url: gs.ajax_url, method: 'POST', body: fd } );
			if ( resp.success ) {
				const css = action === 'add' ? ( resp.data?.css || '' ) : '';
				sendToIframe( 'spectra-gs-update-class', { action, blockName, pseudoSelector, className, css } );
			}
		} catch ( e ) {
			// silent
		}
	}, [ gs ] );

	// ── CSS generation ────────────────────────────────────────────────────────

	const generateAndSendCSS = useCallback( async ( blockName, pseudoSelector, newClassNames ) => {
		if ( ! blockName ) {return;}
		const savedClasses = blockDefaults[ blockName ]?.[ pseudoSelector ] ?? [];
		const tempClasses  = tempClassMemory[ blockName ]?.[ pseudoSelector ] ?? null;
		const current      = tempClasses !== null ? tempClasses : savedClasses;

		const toAdd    = newClassNames.filter( ( c ) => ! current.includes( c ) );
		const toRemove = current.filter( ( c ) => ! newClassNames.includes( c ) );

		for ( const c of toRemove ) {
			await updateClassInStylesheet( 'remove', blockName, pseudoSelector, c );
		}
		for ( const c of toAdd ) {
			await updateClassInStylesheet( 'add', blockName, pseudoSelector, c );
		}
	}, [ blockDefaults, tempClassMemory, updateClassInStylesheet ] );

	const generateInitialBlockDefaultsCSS = useCallback( async () => {
		if ( ! blockDefaults || Object.keys( blockDefaults ).length === 0 ) {return;}
		for ( const blockName of Object.keys( blockDefaults ) ) {
			const pseudoSelectors = Array.isArray( blockDefaults[ blockName ] )
				? { default: blockDefaults[ blockName ] }
				: blockDefaults[ blockName ];
			for ( const ps of Object.keys( pseudoSelectors ) ) {
				const classes = pseudoSelectors[ ps ];
				if ( Array.isArray( classes ) && classes.length > 0 ) {
					for ( const cls of classes ) {
						await updateClassInStylesheet( 'add', blockName, ps, cls );
					}
				}
			}
		}
	}, [ blockDefaults, updateClassInStylesheet ] );

	// ── Effects ───────────────────────────────────────────────────────────────

	useEffect( () => {
		const handleMessage = ( event ) => {
			if ( ! event.data?.type ) {return;}
			if ( event.data.type === 'spectra-gs-preview-ready' ) {
				generateInitialBlockDefaultsCSS();
				if ( selectedBlock ) {
					const parent = getParentBlock( selectedBlock );
					sendBlockToIframe( parent, { originalBlock: selectedBlock } );
				}
			}
		};
		window.addEventListener( 'message', handleMessage );
		return () => window.removeEventListener( 'message', handleMessage );
	}, [ selectedBlock, generateInitialBlockDefaultsCSS, sendBlockToIframe ] );

	useEffect( () => {
		if ( ! selectedBlock ) {
			setSelectedPseudo( 'default' );
			return;
		}
		const parent  = getParentBlock( selectedBlock );
		sendBlockToIframe( parent, { originalBlock: selectedBlock } );
	}, [ selectedBlock, sendBlockToIframe ] );

	useEffect( () => {
		if ( ! window.spectraGSTemporaryStorage ) {
			window.spectraGSTemporaryStorage = {};
		}
		if ( ! selectedBlock || ! selectedPseudo ) {
			setSelectedClasses( [] );
			return;
		}

		let classes = [];
		const tempData = window.spectraGSTemporaryStorage[ selectedBlock ];
		if ( tempData?.[ selectedPseudo ] !== undefined ) {
			classes = tempData[ selectedPseudo ];
		} else if ( blockDefaults[ selectedBlock ]?.[ selectedPseudo ] ) {
			classes = blockDefaults[ selectedBlock ][ selectedPseudo ];
		}

		setSelectedClasses( classes );
		setTempClassMemory( ( prev ) => ( {
			...prev,
			[ selectedBlock ]: { ...( prev[ selectedBlock ] || {} ), [ selectedPseudo ]: classes },
		} ) );
		generateAndSendCSS( selectedBlock, selectedPseudo, classes );
	}, [ selectedBlock, selectedPseudo, blockDefaults ] ); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect( () => {
		if ( ! window.spectraGSTemporaryStorage ) {
			window.spectraGSTemporaryStorage = {};
		}
		Object.keys( tempClassMemory ).forEach( ( blockName ) => {
			const tempPs  = tempClassMemory[ blockName ] || {};
			const savedPs = blockDefaults[ blockName ] || {};
			let hasChanges  = false;
			const cleanData = {};
			Object.keys( tempPs ).forEach( ( ps ) => {
				if ( JSON.stringify( tempPs[ ps ] || [] ) !== JSON.stringify( savedPs[ ps ] || [] ) ) {
					hasChanges = true;
					cleanData[ ps ] = tempPs[ ps ];
				}
			} );
			if ( hasChanges ) {
				window.spectraGSTemporaryStorage[ blockName ] = cleanData;
			} else {
				delete window.spectraGSTemporaryStorage[ blockName ];
			}
		} );
	}, [ tempClassMemory, blockDefaults ] );

	useEffect( () => {
		if ( ! blockDefaultsEnabled ) {
			setSelectedBlock( '' );
			setSelectedPseudo( 'default' );
			setSelectedClasses( [] );
			clearIframePreview();
		}
	}, [ blockDefaultsEnabled ] ); // eslint-disable-line react-hooks/exhaustive-deps

	// ── Computed ──────────────────────────────────────────────────────────────

	const hasUnsavedChanges = selectedBlock && tempClassMemory[ selectedBlock ] !== undefined &&
		Object.keys( tempClassMemory[ selectedBlock ] || {} ).some( ( ps ) => {
			const saved = blockDefaults[ selectedBlock ]?.[ ps ] || [];
			const temp  = tempClassMemory[ selectedBlock ]?.[ ps ] || [];
			return JSON.stringify( saved ) !== JSON.stringify( temp );
		} );

	const hasClassesToClear = selectedBlock && ( () => {
		const tempPs = tempClassMemory[ selectedBlock ];
		if ( tempPs !== undefined ) {
			return Object.values( tempPs ).some( ( a ) => Array.isArray( a ) && a.length > 0 );
		}
		return Object.values( blockDefaults[ selectedBlock ] || {} ).some( ( a ) => Array.isArray( a ) && a.length > 0 );
	} )();

	const hasAnyBlockDefaults = Object.keys( blockDefaults ).length > 0 &&
		Object.values( blockDefaults ).some( ( config ) => {
			if ( Array.isArray( config ) ) {return config.length > 0;}
			return Object.values( config ).some( ( a ) => Array.isArray( a ) && a.length > 0 );
		} );

	const selectedClassesForSelect = selectedClasses.map( ( cls ) => {
		let label = cls;
		availableClasses.forEach( ( group ) => {
			const found = group.options?.find( ( o ) => o.value === cls );
			if ( found ) {label = found.label;}
		} );
		return { value: cls, label };
	} );

	// ── Handlers ──────────────────────────────────────────────────────────────

	const handleClassChange = ( newValue ) => {
		const newClasses = newValue ? newValue.map( ( i ) => i.value ) : [];
		setSelectedClasses( newClasses );

		if ( selectedBlock && selectedPseudo ) {
			const saved = blockDefaults[ selectedBlock ]?.[ selectedPseudo ] || [];
			if ( JSON.stringify( newClasses ) === JSON.stringify( saved ) ) {
				setTempClassMemory( ( prev ) => {
					const updated = { ...prev };
					if ( updated[ selectedBlock ] ) {
						delete updated[ selectedBlock ][ selectedPseudo ];
						if ( Object.keys( updated[ selectedBlock ] ).length === 0 ) {
							delete updated[ selectedBlock ];
						}
					}
					return updated;
				} );
				if ( window.spectraGSTemporaryStorage?.[ selectedBlock ] ) {
					delete window.spectraGSTemporaryStorage[ selectedBlock ][ selectedPseudo ];
					if ( Object.keys( window.spectraGSTemporaryStorage[ selectedBlock ] ).length === 0 ) {
						delete window.spectraGSTemporaryStorage[ selectedBlock ];
					}
				}
			} else {
				setTempClassMemory( ( prev ) => ( {
					...prev,
					[ selectedBlock ]: { ...( prev[ selectedBlock ] || {} ), [ selectedPseudo ]: newClasses },
				} ) );
			}
			generateAndSendCSS( selectedBlock, selectedPseudo, newClasses );
		}
	};

	const handleClearAll = () => {
		if ( ! selectedBlock ) {return;}
		const savedPs = blockDefaults[ selectedBlock ] || {};
		const tempPs  = tempClassMemory[ selectedBlock ] || {};
		const allPs   = new Set( [ ...Object.keys( savedPs ), ...Object.keys( tempPs ) ] );
		const emptyConfig = {};
		allPs.forEach( ( ps ) => { emptyConfig[ ps ] = []; } );
		if ( allPs.size === 0 ) {emptyConfig.default = [];}

		setTempClassMemory( ( prev ) => ( { ...prev, [ selectedBlock ]: emptyConfig } ) );
		setSelectedClasses( [] );

		const iframe = iframeRef.current;
		if ( iframe?.contentWindow ) {
			allPs.forEach( ( ps ) => {
				iframe.contentWindow.postMessage( { type: 'spectra-gs-clear-block-section', blockName: selectedBlock, pseudoSelector: ps }, '*' );
			} );
			if ( ! allPs.has( 'default' ) ) {
				iframe.contentWindow.postMessage( { type: 'spectra-gs-clear-block-section', blockName: selectedBlock, pseudoSelector: 'default' }, '*' );
			}
		}

		if ( window.spectraGSTemporaryStorage ) {
			window.spectraGSTemporaryStorage[ selectedBlock ] = emptyConfig;
		}
	};

	const handleSave = async () => {
		if ( ! hasUnsavedChanges || ! selectedBlock || isSaving ) {return;}
		setIsSaving( true );
		try {
			const completeDefaults = {
				...blockDefaults,
				[ selectedBlock ]: tempClassMemory[ selectedBlock ] || {},
			};
			const fd = new window.FormData();
			fd.append( 'action', 'spectra_pro_gs_block_defaults' );
			fd.append( 'security', gs.ajax_nonce );
			fd.append( 'block_defaults', JSON.stringify( completeDefaults ) );
			const resp = await apiFetch( { url: gs.ajax_url, method: 'POST', body: fd } );
			if ( resp.success ) {
				showSuccess( resp.data?.title || __( 'Saved!', 'spectra-blocks' ) );
				setBlockDefaults( ( prev ) => ( { ...prev, [ selectedBlock ]: tempClassMemory[ selectedBlock ] || {} } ) );
				if ( window.spectra_editor_gs ) {
					window.spectra_editor_gs.block_defaults = {
						...( window.spectra_editor_gs.block_defaults || {} ),
						[ selectedBlock ]: tempClassMemory[ selectedBlock ] || {},
					};
				}
				setTempClassMemory( ( prev ) => { const u = { ...prev }; delete u[ selectedBlock ]; return u; } );
				if ( window.spectraGSTemporaryStorage ) {
					delete window.spectraGSTemporaryStorage[ selectedBlock ];
				}
				window.dispatchEvent( new CustomEvent( 'spectraGSClassesUpdated' ) );
				// Re-inject the block-defaults stylesheet into the editor (incl. the
				// canvas iframe) so a newly-applied default's CSS shows immediately —
				// the server stylesheet only regenerates on a full page reload.
				regenerateEditorCSS();
			} else {
				showError( resp.data?.title || __( 'Save failed. Please try again.', 'spectra-blocks' ) );
			}
		} catch ( err ) {
			showError( err.message || __( 'Network error while saving.', 'spectra-blocks' ) );
		} finally {
			setIsSaving( false );
		}
	};

	const handleRemoveAllConfirm = async () => {
		setIsRemovingAll( true );
		try {
			const fd = new window.FormData();
			fd.append( 'action', 'spectra_pro_gs_remove_all_block_defaults' );
			fd.append( 'security', gs.ajax_nonce );
			const resp = await apiFetch( { url: gs.ajax_url, method: 'POST', body: fd } );
			if ( resp.success ) {
				sendToIframe( 'spectra-gs-replace-stylesheet', { css: '' } );
				setBlockDefaults( {} );
				setTempClassMemory( {} );
				setSelectedClasses( [] );
				setSelectedBlock( '' );
				setSelectedPseudo( 'default' );
				if ( window.spectraGSTemporaryStorage ) {window.spectraGSTemporaryStorage = {};}
				if ( window.spectra_editor_gs ) {window.spectra_editor_gs.block_defaults = {};}
				setShowRemoveAll( false );
				showSuccess( __( 'All block defaults removed.', 'spectra-blocks' ) );
			} else {
				showError( resp.data?.title || __( 'Failed to remove defaults.', 'spectra-blocks' ) );
				setShowRemoveAll( false );
			}
		} catch ( err ) {
			showError( err.message || __( 'Failed to remove all block defaults.', 'spectra-blocks' ) );
			setShowRemoveAll( false );
		} finally {
			setIsRemovingAll( false );
		}
	};

	const handleToggleEnabled = async () => {
		if ( isTogglingEnabled ) {return;}
		const next = ! blockDefaultsEnabled;
		setBlockDefaultsEnabled( next );
		setIsTogglingEnabled( true );
		try {
			const fd = new window.FormData();
			fd.append( 'action', 'spectra_pro_gs_block_defaults_enabled' );
			fd.append( 'security', gs.ajax_nonce );
			fd.append( 'enabled', next ? '1' : '0' );
			const resp = await apiFetch( { url: gs.ajax_url, method: 'POST', body: fd } );
			if ( resp.success ) {
				if ( window.spectra_editor_gs ) {window.spectra_editor_gs.block_defaults_enabled = next;}
				dispatch( 'spectra-pro/global-styles' )?.setBlockDefaultsEnabled?.( next );
				regenerateEditorCSS();
			} else {
				setBlockDefaultsEnabled( ! next );
				showError( resp.data?.title || __( 'Could not save setting.', 'spectra-blocks' ) );
			}
		} catch ( err ) {
			setBlockDefaultsEnabled( ! next );
			showError( err.message || __( 'Network error.', 'spectra-blocks' ) );
		} finally {
			setIsTogglingEnabled( false );
		}
	};

	// ── Render ────────────────────────────────────────────────────────────────

	if ( ! gs ) {
		return (
			<div className="spectra-gbs-panel__body">
				<div className="spectra-gbs-upgrade-lock">
					<h3 className="spectra-gbs-upgrade-lock__heading">
						{ __( 'Block defaults requires Spectra Pro', 'spectra-blocks' ) }
					</h3>
					<p className="spectra-gbs-upgrade-lock__body">
						{ __( 'Upgrade to assign default classes per block type.', 'spectra-blocks' ) }
					</p>
				</div>
			</div>
		);
	}

	return (
		<div className="spectra-gbs-panel__body">
			<div className="spectra-gbs-panel__content">
				<div className="spectra-gbs-bd">

					{/* ── Header row ──────────────────────────────────────────────── */}
					<div className="spectra-gbs-bd__header">
						<div className="spectra-gbs-bd__heading">
							<span className="spectra-gbs-bd__title">
								{ __( 'Block defaults', 'spectra-blocks' ) }
							</span>
							<span className="spectra-gbs-bd__subtitle">
								{ __( 'Pre-apply utility classes to every new block of a type.', 'spectra-blocks' ) }
							</span>
						</div>
						<div className="spectra-gbs-bd__toggle-row">
							<span className="spectra-gbs-bd__toggle-label">
								{ blockDefaultsEnabled
									? __( 'Enabled', 'spectra-blocks' )
									: __( 'Disabled', 'spectra-blocks' )
								}
							</span>
							<button
								type="button"
								role="switch"
								aria-checked={ blockDefaultsEnabled }
								className={ `spectra-gbs-toggle${ blockDefaultsEnabled ? ' is-on' : '' }${ isTogglingEnabled ? ' is-busy' : '' }` }
								onClick={ handleToggleEnabled }
								disabled={ isTogglingEnabled }
							/>
						</div>
					</div>

					{/* ── Disabled overlay ─────────────────────────────────────── */}
					{ ! blockDefaultsEnabled && (
						<div className="spectra-gbs-bd__disabled">
							<p className="spectra-gbs-bd__disabled-text">
								{ __( 'Enable block defaults using the toggle to configure default classes for your Spectra blocks.', 'spectra-blocks' ) }
							</p>
						</div>
					) }

					{ blockDefaultsEnabled && (
						<div className="spectra-gbs-bd__body">
							{/* ── Left column ────────────────────────────────────── */}
							<div className="spectra-gbs-bd__controls">

								{/* Block selector */}
								<div className="spectra-gbs-section__field">
									<label className="spectra-gbs-section__label">
										{ __( 'Select a block', 'spectra-blocks' ) }
									</label>
									<select
										className="spectra-gbs-keyframe-settings__select"
										value={ selectedBlock }
										disabled={ isIframeLoading }
										onChange={ ( e ) => {
											setSelectedBlock( e.target.value );
											setSelectedPseudo( 'default' );
										} }
									>
										<option value="">{ __( '— Choose a block —', 'spectra-blocks' ) }</option>
										{ ( () => {
											const groups = [];
											let currentHeading = null;
											let currentItems = [];
											blockOptions.forEach( ( opt ) => {
												if ( opt.value === 'dropdown-section' ) {
													if ( currentItems.length && currentHeading ) {
														groups.push( { heading: currentHeading, items: currentItems } );
													}
													currentHeading = opt.heading;
													currentItems = [];
												} else {
													currentItems.push( opt );
												}
											} );
											if ( currentItems.length && currentHeading ) {
												groups.push( { heading: currentHeading, items: currentItems } );
											}
											return groups.map( ( g, gi ) => (
												<optgroup key={ gi } label={ g.heading }>
													{ g.items.map( ( item ) => (
														<option key={ item.value } value={ item.value }>{ item.label }</option>
													) ) }
												</optgroup>
											) );
										} )() }
									</select>
								</div>

								{/* State selector */}
								{ selectedBlock && (
									<div className="spectra-gbs-section__field">
										<label className="spectra-gbs-section__label">
											{ __( 'State', 'spectra-blocks' ) }
										</label>
										<select
											className="spectra-gbs-keyframe-settings__select"
											value={ selectedPseudo }
											onChange={ ( e ) => setSelectedPseudo( e.target.value ) }
										>
											{ PSEUDO_STATES.map( ( ps ) => (
												<option key={ ps } value={ ps }>
													{ getPseudoDisplayLabel( ps ) }
												</option>
											) ) }
										</select>
									</div>
								) }

								{/* Class multi-select */}
								{ selectedBlock && (
									<div className="spectra-gbs-section__field">
										<label className="spectra-gbs-section__label">
											{ __( 'Add classes', 'spectra-blocks' ) }
										</label>
										<Select
											isMulti
											closeMenuOnSelect={ false }
											value={ selectedClassesForSelect }
											options={ availableClasses }
											placeholder={ __( 'Select classes…', 'spectra-blocks' ) }
											styles={ SELECT_STYLES }
											filterOption={ ( option, inputValue ) => {
												const text  = option?.label?.toLowerCase() || '';
												const query = inputValue?.toLowerCase() || '';
												let last = -1;
												for ( const ch of query ) {
													const i = text.indexOf( ch, last + 1 );
													if ( i === -1 || i <= last ) {return false;}
													last = i;
												}
												return true;
											} }
											onChange={ handleClassChange }
										/>
									</div>
								) }

								{ selectedBlock && ! hasClassesToClear && (
									<p className="spectra-gbs-section__hint">
										{ __( 'No classes assigned to this block yet — pick from “Add classes” above.', 'spectra-blocks' ) }
									</p>
								) }
							</div>

							{/* ── Preview column ─────────────────────────────────── */}
							<div className="spectra-gbs-bd__preview">
								<span className="spectra-gbs-section__label">
									{ __( 'Preview', 'spectra-blocks' ) }
								</span>
								<div className="spectra-gbs-bd__iframe-wrap">
									{ isIframeLoading && (
										<div className="spectra-gbs-bd__iframe-loading">
											<span>{ __( 'Loading preview…', 'spectra-blocks' ) }</span>
										</div>
									) }
									<iframe
										ref={ iframeRef }
										title={ __( 'Block defaults preview', 'spectra-blocks' ) }
										src={ gs.preview_url }
										width="800"
										height="600"
										style={ { width: '100%', display: isIframeLoading ? 'none' : 'block' } }
										onLoad={ () => {
											setIsIframeLoading( false );
											setTimeout( () => setIsRemoveReady( true ), 3000 );
											setTimeout( () => {
												generateInitialBlockDefaultsCSS();
												if ( selectedBlock ) {
													const parent = getParentBlock( selectedBlock );
													setTimeout( () => sendBlockToIframe( parent, { originalBlock: selectedBlock } ), 200 );
												}
											}, 100 );
										} }
									/>
								</div>
							</div>
						</div>
					) }
				</div>
			</div>

			{/* ── Remove All confirmation dialog ───────────────────────────── */}
			{ blockDefaultsEnabled && ( selectedBlock || hasAnyBlockDefaults ) && (
				<div className="spectra-gbs-panel__footer">
					<div className="spectra-gbs-bd__footer-left">
						{ hasAnyBlockDefaults && (
							<button
								type="button"
								className="spectra-gbs-btn--danger spectra-gbs-btn--sm"
								disabled={ ! isRemoveReady || isRemovingAll }
								onClick={ () => setShowRemoveAll( true ) }
							>
								{ isRemovingAll ? __( 'Removing…', 'spectra-blocks' ) : __( 'Remove all block defaults', 'spectra-blocks' ) }
							</button>
						) }
						{ successMsg && <span className="spectra-gbs-bd__status is-success" role="status">{ successMsg }</span> }
						{ errorMsg && <span className="spectra-gbs-bd__status is-error" role="alert">{ errorMsg }</span> }
					</div>
					{ selectedBlock && (
						<>
							<button
								type="button"
								className="spectra-gbs-btn--secondary"
								onClick={ handleClearAll }
								disabled={ isSaving || ! hasClassesToClear }
							>
								{ __( 'Clear all', 'spectra-blocks' ) }
							</button>
							<button
								type="button"
								className="spectra-gbs-btn--primary"
								onClick={ handleSave }
								disabled={ isSaving || ! hasUnsavedChanges }
							>
								{ isSaving ? __( 'Saving…', 'spectra-blocks' ) : __( 'Save changes', 'spectra-blocks' ) }
							</button>
						</>
					) }
				</div>
			) }

			{ showRemoveAll && (
				<div className="spectra-gbs-confirm-overlay" role="dialog" aria-modal="true">
					<div className="spectra-gbs-confirm">
						<div className="spectra-gbs-confirm__header">
							<strong>{ __( 'Remove all block defaults', 'spectra-blocks' ) }</strong>
							<button
								type="button"
								className="spectra-gbs-confirm__close"
								onClick={ () => setShowRemoveAll( false ) }
							>✕</button>
						</div>
						<div className="spectra-gbs-confirm__body">
							<p>
								{ __( 'Are you sure you want to remove all block defaults? This cannot be undone.', 'spectra-blocks' ) }
							</p>
						</div>
						<div className="spectra-gbs-confirm__footer">
							<button
								type="button"
								className="spectra-gbs-btn--secondary"
								onClick={ () => setShowRemoveAll( false ) }
							>
								{ __( 'Cancel', 'spectra-blocks' ) }
							</button>
							<button
								type="button"
								className="spectra-gbs-btn--danger"
								onClick={ handleRemoveAllConfirm }
								disabled={ isRemovingAll }
							>
								{ isRemovingAll ? __( 'Removing…', 'spectra-blocks' ) : __( 'Remove All', 'spectra-blocks' ) }
							</button>
						</div>
					</div>
				</div>
			) }
		</div>
	);
};

export default BlockDefaultsPanel;
