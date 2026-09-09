/**
 * The pre-7.1 reset handler, restored from 1.0.6 unchanged.
 *
 * Core's panel "Reset" and "Reset all" clear the block's root attributes. On the
 * versions this serves, per-device values live in `responsiveControls`, so a
 * reset has to be mirrored into the previewed device's bucket — which is what
 * this does.
 *
 * The 7.1 handler in `../../index.js` does the equivalent work against core's
 * `style` states instead, and the two are mutually exclusive: only one is
 * initialised, chosen by the same capability check that picks the editor.
 *
 * SNAPSHOT — verbatim from 1.0.6 apart from the reset flag, which is now set
 * through `setResetInProgress()` because the flag lives in the shared module
 * that both the classic and viewport write routers read.
 *
 * @since 1.0.7
 */

/**
 * External dependencies.
 */
import { select, dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { setResetInProgress } from '../../index';
import { isObject, hasValue, deleteNested, deepClone, isAllowedBlock, resetWroteTheDefault } from '../../utils/helpers';
import { extractResponsiveAttributes, deepMergeAttributes } from './helpers';
import {
	DESKTOP,
	BREAKPOINT_TYPE_MAP,
	RESPONSIVE_CONTROLS_PANELS,
	DROPDOWN_MENU_SELECTOR,
	MENU_ITEM_BUTTON_SELECTOR,
	MENU_ITEM_SELECTOR,
	isResetText,
	isResetAllText,
} from './constants';

/**
 * Memory-safe document click handler for responsive control reset actions.
 * Automatically cleaned up when page unloads to prevent memory leaks.
 *
 * @since 1.0.7
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
		if ( ! dropdownMenu ) {return;}

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

		if ( ! panelName ) {return;}

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
			const spectraTranslation = normalize( __( panel, 'spectra-blocks' ) );
			return normalizedPanelName === spectraTranslation;
		} );

		if ( ! isResponsivePanel ) {return;}

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

		if ( ! isResetButton ) {return;}

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

		if ( ! blockBeforeReset || ! isAllowedBlock( blockBeforeReset ) ) {return;}

		// Set flag to disable withResponsiveControls during reset.
		setResetInProgress( true );

		const deviceType = select( 'core/editor' )?.getDeviceType?.() || DESKTOP;
		const breakpoint = BREAKPOINT_TYPE_MAP[ deviceType ] || 'lg';

		/**
		 * Process the reset action after core reset completes.
		 * We need to wait for the core reset to actually happen before reading the new attributes.
		 */
		requestAnimationFrame( () => {
			const block = getSelectedBlock();

			// Validate we have an allowed block with responsive attributes
			if ( ! block || ! isAllowedBlock( block ) ) {return;}

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
								if ( ! isObject( obj ) ) {return;}
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
					} else if ( '' === basePath && resetWroteTheDefault( name, key, beforeValue, afterValue ) ) {
						// A control whose reset writes its DEFAULT rather than clearing
						// the value — see `resetWroteTheDefault()`.
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
			setResetInProgress( false );

			// Phase 2: Apply merged attributes for current device only
			requestAnimationFrame( () => {
				const updatedBlock = getSelectedBlock();
				if ( ! updatedBlock || ! isAllowedBlock( updatedBlock ) ) {return;}

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

/**
 * Initialise the pre-7.1 reset handler.
 *
 * @since 1.0.7
 * @return {void}
 */
export const registerClassicResetHandler = () => {
	ResponsiveControlsClickHandler.init();
};
