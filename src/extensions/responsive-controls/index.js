/**
 * Responsive Controls Extension.
 *
 * This module enables responsive design capabilities for Spectra blocks by adding
 * device-specific attribute controls (desktop, tablet, mobile). It handles the registration
 * of responsive attributes and manages the switching between different device views.
 *
 * @since x.x.x
 */

/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import {
	extendBlockAttributes,
	withDeviceViewUpdate,
	withResponsiveControls,
	extractResponsiveAttributes,
	isAllowedBlock,
	deepMergeAttributes,
	hasValue,
	isObject,
	deleteNested,
	deepClone,
} from './utils/helpers';
import { withBackwardCompatibility } from './utils/backward-compatibility';
import {
	DESKTOP,
	BREAKPOINT_TYPE_MAP,
	RESPONSIVE_CONTROLS_PANELS,
	DROPDOWN_MENU_SELECTOR,
	MENU_ITEM_BUTTON_SELECTOR,
	MENU_ITEM_SELECTOR,
	isResetText,
	isResetAllText,
} from './utils/constants';

/**
 * Reset state management
 */
let isResetInProgress = false;

/**
 * Export function to check reset state
 */
export const getResetInProgress = () => isResetInProgress;

/**
 * Import control injection system and styles.
 *
 * The control injection system enhances Gutenberg's block editor by automatically
 * injecting responsive device buttons (Desktop/Tablet/Mobile) into existing core
 * controls. This allows users to quickly switch between device views when editing
 * responsive attributes like padding, margin, typography, and more.
 *
 * Key features:
 * - Automatically detects responsive controls in panels like Spacing, Typography, etc.
 * - Maintains sync with WordPress device preview state.
 * - Only activates for Spectra blocks to avoid conflicts.
 * - Handles DOM mutations and re-renders gracefully.
 *
 * @see control-injection.js for implementation details.
 */
import './control-injection';

/**
 * Filters the blocks register to add responsive controls attributes.
 *
 * This filter adds a 'responsiveControls' attribute to all supported Spectra blocks,
 * which stores device-specific settings for various properties.
 *
 * @since x.x.x
 */
addFilter( 'blocks.registerBlockType', 'spectra/responsive-controls/add-attributes', extendBlockAttributes );

/**
 * Filters the block edit component to inject responsive controls.
 *
 * This filter wraps the block edit component to intercept attribute changes and
 * store responsive attributes in the appropriate device-specific location.
 *
 * @since x.x.x
 */
addFilter( 'editor.BlockEdit', 'spectra/responsive-controls/with-responsive-controls', withResponsiveControls );

/**
 * Filters the block edit component to update attributes on device view change.
 *
 * This filter ensures that when the user switches between device views (desktop/tablet/mobile),
 * the block attributes are updated to reflect the appropriate values for that device.
 * The priority of 20 ensures this runs after the responsive controls filter.
 *
 * @since x.x.x
 */
addFilter( 'editor.BlockEdit', 'spectra/responsive-controls/with-device-view-update', withDeviceViewUpdate, 11 );

/**
 * Filters the block edit component to handle backward compatibility of legacy attributes.
 *
 * This filter ensures that when existing blocks with legacy root-level attributes
 * are loaded in the editor, they are mapped to the new responsive structure for backward compatibility.
 *
 * @since x.x.x
 */
addFilter(
	'editor.BlockEdit',
	'spectra/responsive-controls/with-backward-compatibility',
	withBackwardCompatibility,
	9
);

/**
 * Memory-safe document click handler for responsive control reset actions.
 * Automatically cleaned up when page unloads to prevent memory leaks.
 *
 * @since x.x.x
 */
const ResponsiveControlsClickHandler = {
	/**
	 * The actual click handler function
	 *
	 * @param {Event} e - The click event object
	 * @return {void}
	 */
	handleClick( e ) {
		/**
		 * Check if click occurred within a dropdown menu
		 *
		 * @type {Element|null}
		 */
		const dropdownMenu = e.target.closest( DROPDOWN_MENU_SELECTOR );
		if ( ! dropdownMenu ) return;

		/**
		 * Verify the dropdown is for a responsive control panel
		 *
		 * @type {string|null}
		 */
		let panelName = dropdownMenu.getAttribute( 'aria-label' );

		// Try to get the label text if available (more reliable than aria-label for some languages)
		const groupLabel = dropdownMenu.querySelector( '.components-menu-group__label' );
		if ( groupLabel && groupLabel.textContent ) {
			panelName = groupLabel.textContent;
		}

		if ( ! panelName ) return;

		/*
		 * Check if the panel is a responsive control panel.
		 * Normalizes panel names to lowercase and trims whitespace for accurate comparison.
		 * Supports both English and translated panel names by checking against:
		 * 1. WordPress core panels (Spacing, Typography, Layout, etc.) - 'default' textdomain
		 * 2. Spectra custom panels (Background, Overlay Settings, etc.) - plugin textdomain
		 */
		const normalize = ( str ) => str.toLowerCase().trim();
		const normalizedPanelName = normalize( panelName );

		const isResponsivePanel = RESPONSIVE_CONTROLS_PANELS.some( ( panel ) => {
			// Check exact English match first (fastest path)
			if ( normalizedPanelName === normalize( panel ) ) {
				return true;
			}

			// Check WordPress core translation (for core panels like Spacing, Typography, etc.)
			// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
			const coreTranslation = normalize( __( panel, 'default' ) );
			if ( normalizedPanelName === coreTranslation ) {
				return true;
			}

			// Check Spectra plugin translation (for Spectra-specific panels)
			// eslint-disable-next-line @wordpress/i18n-no-variables
			const spectraTranslation = normalize( __( panel, 'ultimate-addons-for-gutenberg' ) );
			return normalizedPanelName === spectraTranslation;
		} );

		if ( ! isResponsivePanel ) return;

		/**
		 * Determine if the click represents a reset action through multiple checks:
		 * 1. Direct text content match (supports translations)
		 * 2. Contained span text match (supports translations)
		 * 3. ARIA label check (supports translations)
		 * 4. Sibling element fallback check
		 *
		 * @type {boolean}
		 */
		let isResetButton = isResetText( e.target.textContent?.trim().toLowerCase() || '' );

		if ( ! isResetButton ) {
			const menuButton = e.target.closest( MENU_ITEM_BUTTON_SELECTOR );
			if ( menuButton ) {
				// Check all spans within the button (translation-aware)
				const spans = menuButton.querySelectorAll( 'span' );
				for ( const span of spans ) {
					if ( isResetText( span.textContent?.trim().toLowerCase() ) ) {
						isResetButton = true;
						break;
					}
				}

				// Check button's ARIA label as fallback (translation-aware)
				if ( ! isResetButton ) {
					const ariaLabel = menuButton.getAttribute( 'aria-label' )?.toLowerCase() || '';
					isResetButton = isResetText( ariaLabel );
				}
			}
		}

		// Legacy support for specific menu item structure
		if ( ! isResetButton && e.target.classList.contains( MENU_ITEM_SELECTOR.split( '.' )[ 1 ] ) ) {
			const nextText = e.target.nextElementSibling?.textContent?.trim().toLowerCase() || '';
			isResetButton = isResetText( nextText ); // Translation-aware legacy check
		}

		if ( ! isResetButton ) return;

		/**
		 * Detect the type of reset (individual reset vs reset all).
		 * Uses translation-aware checking for non-English languages.
		 */
		let resetType = 'individual';
		const clickedText = e.target.textContent?.trim().toLowerCase() || '';
		if ( isResetAllText( clickedText ) ) {
			resetType = 'resetAll';
		} else {
			// Check spans and other elements for "reset all" (translation-aware).
			const menuButton = e.target.closest( MENU_ITEM_BUTTON_SELECTOR );
			if ( menuButton ) {
				const spans = menuButton.querySelectorAll( 'span' );
				for ( const span of spans ) {
					if ( isResetAllText( span.textContent?.trim().toLowerCase() ) ) {
						resetType = 'resetAll';
						break;
					}
				}
			}
		}

		/**
		 * Store the current state BEFORE reset happens
		 */
		const { getSelectedBlock } = select( 'core/block-editor' );
		const blockBeforeReset = getSelectedBlock();

		if ( ! blockBeforeReset || ! isAllowedBlock( blockBeforeReset ) ) return;

		// Set flag to disable withResponsiveControls during reset.
		isResetInProgress = true;

		const deviceType = select( 'core/editor' )?.getDeviceType?.() || DESKTOP;
		const breakpoint = BREAKPOINT_TYPE_MAP[ deviceType ] || 'lg';

		/**
		 * Process the reset action after core reset completes.
		 * We need to wait for the core reset to actually happen before reading the new attributes.
		 */
		requestAnimationFrame( () => {
			const block = getSelectedBlock();

			// Validate we have an allowed block with responsive attributes
			if ( ! block || ! isAllowedBlock( block ) ) return;

			const { attributes, name, clientId } = block;
			const { responsiveControls = {} } = attributes || {};
			const responsiveAttrs = extractResponsiveAttributes( attributes, name );

			const { updateBlockAttributes, __unstableMarkNextChangeAsNotPersistent } = dispatch( 'core/block-editor' );

			// Phase 1: Detect what was actually reset by comparing BEFORE and AFTER states
			// CRITICAL: Deep copy to avoid mutating the original data structure
			const currentBreakpointData = deepClone( responsiveControls[ breakpoint ] || {} );

			const resetProperties = [];

			// Find all properties that existed in breakpoint BEFORE reset but don't exist in current responsive attrs AFTER reset
			// These are the properties that were just reset by the user
			const findResetProperties = ( beforeData, afterData, basePath = '' ) => {
				if ( ! isObject( beforeData ) ) {
					return;
				}

				Object.keys( beforeData ).forEach( ( key ) => {
					const beforeValue = beforeData[ key ];
					const afterValue = afterData && afterData[ key ];
					const currentPath = basePath ? `${ basePath }.${ key }` : key;

					if ( isObject( beforeValue ) ) {
						if ( isObject( afterValue ) ) {
							// Both are objects - recurse deeper to find what changed
							findResetProperties( beforeValue, afterValue, currentPath );
						} else if ( ! hasValue( afterValue ) ) {
							// Before had nested object, after doesn't - entire path was reset
							resetProperties.push( currentPath );
							// Also add all nested paths that existed before
							const addNestedPaths = ( obj, pathPrefix ) => {
								if ( ! isObject( obj ) ) return;
								Object.keys( obj ).forEach( ( nestedKey ) => {
									const nestedPath = `${ pathPrefix }.${ nestedKey }`;
									resetProperties.push( nestedPath );
									if ( isObject( obj[ nestedKey ] ) ) {
										addNestedPaths( obj[ nestedKey ], nestedPath );
									}
								} );
							};
							addNestedPaths( beforeValue, currentPath );
						}
					} else if ( hasValue( beforeValue ) && ! hasValue( afterValue ) ) {
						// Leaf property existed before but not after - it was reset
						resetProperties.push( currentPath );
					}
				} );
			};

			// Find what was reset by comparing before/after responsive attributes
			const responsiveAttrsBeforeReset = extractResponsiveAttributes( blockBeforeReset.attributes, name );
			findResetProperties( responsiveAttrsBeforeReset, responsiveAttrs );

			// Filter reset properties based on reset type and radius detection.
			let filteredResetProperties;

			if ( resetType === 'resetAll' ) {
				// For reset all, keep all properties.
				filteredResetProperties = resetProperties;
			} else {
				// For individual resets, check if we have radius properties.
				const radiusProperties = resetProperties.filter( ( prop ) => prop.startsWith( 'style.border.radius' ) );

				if ( radiusProperties.length > 0 ) {
					// If we have radius properties, only keep radius properties and discard others (including borderColor).
					filteredResetProperties = radiusProperties;
				} else {
					// If no radius properties, keep all properties as normal.
					filteredResetProperties = resetProperties;
				}
			}

			// Directly delete the filtered reset properties from the current breakpoint data.
			filteredResetProperties.forEach( ( path ) => {
				deleteNested( currentBreakpointData, path );
			} );

			updateBlockAttributes( clientId, {
				responsiveControls: {
					...responsiveControls,
					[ breakpoint ]: currentBreakpointData,
				},
			} );

			// Clear the reset flag immediately after our update.
			isResetInProgress = false;

			// Phase 2: Apply merged attributes for current device only
			requestAnimationFrame( () => {
				const updatedBlock = getSelectedBlock();
				if ( ! updatedBlock || ! isAllowedBlock( updatedBlock ) ) return;

				// Only update if we're on the device that was reset - don't force inheritance updates
				if ( select( 'core/editor' )?.getDeviceType?.() !== deviceType ) {
					return;
				}

				// Extract current responsive attributes for merge calculation
				const updatedResponsiveAttrs = extractResponsiveAttributes(
					updatedBlock.attributes,
					updatedBlock.name
				);

				const mergedResponsiveAttributes = deepMergeAttributes(
					updatedResponsiveAttrs,
					updatedBlock.attributes?.responsiveControls || {},
					deviceType,
					updatedBlock.name
				);

				// Prevent duplicate saves
				__unstableMarkNextChangeAsNotPersistent();

				// Update block attributes with responsive changes while preserving non-responsive properties.
				const mergedAttributes = {
					...updatedBlock.attributes,
					...mergedResponsiveAttributes,
				};

				// Safely merge style objects if they exist.
				if ( updatedBlock.attributes.style || mergedResponsiveAttributes.style ) {
					mergedAttributes.style = {
						...( updatedBlock.attributes.style || {} ),
						...( mergedResponsiveAttributes.style || {} ),
					};
				}

				updateBlockAttributes( updatedBlock.clientId, mergedAttributes );
			} );
		} );
	},

	/**
	 * Initialize the click handler with proper cleanup
	 */
	init() {
		// Bind the handler to preserve 'this' context
		this.boundHandler = this.handleClick.bind( this );

		// Add the event listener
		document.addEventListener( 'click', this.boundHandler, { capture: true } );

		// Set up cleanup on page unload to prevent memory leaks
		this.cleanup = () => {
			if ( this.boundHandler ) {
				document.removeEventListener( 'click', this.boundHandler, { capture: true } );
				this.boundHandler = null;
			}
		};

		// Auto-cleanup on page unload
		window.addEventListener( 'beforeunload', this.cleanup );
		window.addEventListener( 'pagehide', this.cleanup );

		// For SPA navigation cleanup
		if ( typeof window.wp !== 'undefined' && window.wp.hooks ) {
			window.wp.hooks.addAction( 'spectra.cleanup', 'spectra/responsive-controls', this.cleanup );
		}
	},

	boundHandler: null,
	cleanup: null,
};

// Initialize the memory-safe click handler
ResponsiveControlsClickHandler.init();
