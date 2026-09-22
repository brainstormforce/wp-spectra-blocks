/**
 * Responsive Controls Extension.
 *
 * This module enables responsive design capabilities for Spectra blocks. Values
 * live in core's `style` attribute — the base layer at its root, `@tablet` and
 * `@mobile` as viewport states — and switching the preview device never
 * modifies attributes; each control reads its device's value directly.
 *
 * @since 1.0.9
 */

/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { addFilter, applyFilters } from '@wordpress/hooks';

import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import {
	extendBlockAttributes,
	withContainerVariationSync,
	withResponsiveControls,
	extractResponsiveAttributes,
	isAllowedBlock,
	mirrorBaseValuesToAttributes,
	hasValue,
	isObject,
	deleteNested,
	deepClone,
	resetWroteTheDefault,
} from './utils/helpers';
import { withBackwardCompatibility } from './utils/backward-compatibility';
import {
	DESKTOP,
	BREAKPOINT_TYPE_MAP,
	coreViewportStatesAreIndependent,
	savesAttributesToMarkup,
	RESPONSIVE_CONTROLS_PANELS,
	RESPONSIVE_CONTROLS_PANEL_TEXT_DOMAINS,
	DROPDOWN_MENU_SELECTOR,
	MENU_ITEM_BUTTON_SELECTOR,
	MENU_ITEM_SELECTOR,
	isResetText,
	isResetAllText,
} from './utils/constants';
import { getResponsiveEditingActive } from './utils/use-responsive-editing';

/**
 * Remove empty nested objects left behind by deleted paths, so a state whose
 * last value was reset disappears instead of serialising as `{}`.
 *
 * @since 1.0.7
 * @param {Object} obj Object to prune, mutated in place.
 * @return {void}
 */
const pruneEmptyObjects = ( obj ) => {
	if ( ! obj || typeof obj !== 'object' || Array.isArray( obj ) ) {
		return;
	}

	Object.keys( obj ).forEach( ( key ) => {
		const value = obj[ key ];

		if ( value && typeof value === 'object' && ! Array.isArray( value ) ) {
			pruneEmptyObjects( value );

			if ( ! Object.keys( value ).length ) {
				delete obj[ key ];
			}
		}
	} );
};

/**
 * Reset state management
 */
let isResetInProgress = false;

/**
 * Export function to check reset state
 */
export const getResetInProgress = () => isResetInProgress;

/**
 * Set the reset flag.
 *
 * The flag lives here because both write routers read it through
 * `getResetInProgress()`, while the handler that raises it is version-specific:
 * the pre-7.1 one lives in `./legacy/classic-editor/reset-handler.js`. Exposing
 * a setter is what lets that module drive this flag without owning a second
 * copy of it — two flags would mean a reset suppressed in one router and not
 * the other.
 *
 * @since 1.0.7
 * @param {boolean} value Whether a reset is being processed.
 * @return {void}
 */
export const setResetInProgress = ( value ) => {
	isResetInProgress = value;
};

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
 * Completes core's per-viewport layout CSS in the canvas.
 *
 * Core emits no `flex-direction` for a horizontal orientation, so a band
 * cannot undo a vertical base. Editor-only and 7.1+ only; the module gates
 * itself on the capability.
 *
 * @see layout-orientation-preview.js for implementation details.
 */
import './layout-orientation-preview';

/**
 * Filters the blocks register to add responsive controls attributes.
 *
 * This filter adds a 'responsiveControls' attribute to all supported Spectra blocks,
 * which stores device-specific settings for various properties.
 *
 * @since 1.0.9
 */
addFilter( 'blocks.registerBlockType', 'spectra/responsive-controls/add-attributes', extendBlockAttributes );

/**
 * Mirror base-layer flat values from `style` into the root attributes as a
 * block is parsed, so block-local controls and core's preset panels — which
 * read root attributes — present the values the front end renders. Priority
 * 20 runs it after the legacy migration has lifted store values into `style`.
 *
 * Viewport-state path only: it reads the base layer OUT of `style`, which is
 * where nothing is stored without core's viewport states. Below 7.1 the values
 * are in `responsiveControls` and the projection layer puts the previewed
 * device into the root attributes instead.
 *
 * @since 1.0.9
 */
if ( coreViewportStatesAreIndependent() ) {
	addFilter(
		'blocks.getBlockAttributes',
		'spectra/responsive-controls/mirror-base-values',
		( attributes, blockType ) => {
			/*
			 * Not for a block whose saved markup carries its attributes.
			 * Mirroring exists so block-local readers see the base value that
			 * lives in `style`; on a markup-backed block the ROOT attribute is
			 * already that value, and filling one the stored HTML never had
			 * adds a declaration to `save()` output that is not in the post —
			 * which is #908's third route to a validation failure.
			 */
			if ( savesAttributesToMarkup( blockType?.name || '' ) ) {
				return attributes;
			}

			return mirrorBaseValuesToAttributes( attributes, blockType );
		},
		20
	);
}

/*
 * Legacy responsive-store support.
 *
 * Self-contained in `./legacy`, which registers its own parse-time filter. This
 * import is the only switch: delete the folder and this line to remove pre-1.0.6
 * compatibility entirely. Nothing on the current path imports from it.
 */
import './legacy';
import { registerClassicEditor } from './legacy/classic-editor';
import { registerClassicResetHandler } from './legacy/classic-editor/reset-handler';

/**
 * Register exactly one responsive implementation, chosen by what core can do.
 *
 * With viewport states (WordPress 7.1+), per-device values live in core's own
 * `style` attribute and core displays and renders the states itself, so all this
 * needs is a write router that addresses the previewed state.
 *
 * Without them, the site keeps the implementation from 1.0.6 — its own
 * `responsiveControls` store plus the projection layer that makes core's panels
 * show a device — restored intact in `./legacy/classic-editor`.
 *
 * The two are mutually exclusive on purpose. Both register on `editor.BlockEdit`
 * under the same filter name, and both wrap `setAttributes`; running them
 * together would send a single edit to two different places. The capability is
 * measured in PHP and exported to the editor, so this decision matches the one
 * the renderer makes — see `Extensions\ResponsiveControls\ViewportSupport`.
 *
 * @since 1.0.7
 */
if ( coreViewportStatesAreIndependent() ) {
	addFilter( 'editor.BlockEdit', 'spectra/responsive-controls/with-responsive-controls', withResponsiveControls );
} else {
	registerClassicEditor();
}

/**
 * Filters the block edit component to sync a container's variation state.
 *
 * Mount-only: when a container's PARENT already carries the `variationSelected`
 * marker, the marker is derived for the child so the variation picker does not
 * reappear inside an already-configured layout.
 * Device switching is deliberately not involved — it never touches attributes.
 *
 * @since 1.0.9
 */
if ( coreViewportStatesAreIndependent() ) {
	addFilter( 'editor.BlockEdit', 'spectra/responsive-controls/with-container-variation-sync', withContainerVariationSync, 11 );
}

/**
 * Filters the block edit component to handle backward compatibility of legacy attributes.
 *
 * This filter ensures that when existing blocks with legacy root-level attributes
 * are loaded in the editor, they are mapped to the new responsive structure for backward compatibility.
 *
 * @since 1.0.9
 */
if ( coreViewportStatesAreIndependent() ) {
	addFilter(
		'editor.BlockEdit',
		'spectra/responsive-controls/with-backward-compatibility',
		withBackwardCompatibility,
		9
	);
}

/**
 * Memory-safe document click handler for responsive control reset actions.
 * Automatically cleaned up when page unloads to prevent memory leaks.
 *
 * @since 1.0.9
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
		 *
		 * The label in the DOM is whatever `__()` produced for the plugin that
		 * rendered the panel, so each English name is compared as-is and then
		 * as translated under every domain a matching panel can come from:
		 * core's (`default`), this plugin's, and — through the filter — Spectra
		 * Pro's, which renders "General" and "Settings" panels of its own that
		 * carry per-device values.
		 */
		const normalize = ( str ) => str.toLowerCase().trim();
		const normalizedPanelName = normalize( panelName );

		const panels = applyFilters(
			'spectra.responsive-controls.reset-panels',
			RESPONSIVE_CONTROLS_PANELS
		);
		const textDomains = applyFilters(
			'spectra.responsive-controls.reset-panel-text-domains',
			RESPONSIVE_CONTROLS_PANEL_TEXT_DOMAINS
		);

		const isResponsivePanel = panels.some( ( panel ) => {
			// Exact English match first (fastest path).
			if ( normalizedPanelName === normalize( panel ) ) {
				return true;
			}

			return textDomains.some(
				// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
				( domain ) => normalizedPanelName === normalize( __( panel, domain ) )
			);
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
		isResetInProgress = true;

		const deviceType = select( 'core/editor' )?.getDeviceType?.() || DESKTOP;

		/*
		 * Reset the layer the WRITE router would have used, not the one the
		 * device implies.
		 *
		 * These have to agree. If an edit made on Tablet with Responsive styles
		 * off went to the base layer, a reset that clears `style['@tablet']`
		 * removes nothing and the value the user is looking at survives —
		 * "Reset" appears broken. Reading the same signal keeps the pair honest.
		 *
		 * `getResponsiveEditingActive()` rather than the hook: this runs from a
		 * document click listener, outside any component.
		 */
		const responsiveEditing = getResponsiveEditingActive();

		const breakpoint =
			! coreViewportStatesAreIndependent() || responsiveEditing
				? BREAKPOINT_TYPE_MAP[ deviceType ] || 'base'
				: 'base';

		/*
		 * Whether the reset should also clear the viewport states.
		 *
		 * With core's "Responsive styles" off its panels are bound to the base
		 * layer whatever device is previewed, so `breakpoint` above is `base` and
		 * the reset clears base — correct, because that is where the matching
		 * EDIT would have gone.
		 *
		 * But a block can still CARRY states: content migrated from the pre-7.1
		 * store arrives with them, and so does anything authored earlier with the
		 * option on. Clearing base alone then leaves those states overriding the
		 * value that was just reset, and the author sees the reverse of what they
		 * asked for — pressing Reset while previewing Tablet changes Desktop and
		 * leaves Tablet exactly as it was.
		 *
		 * So with the option off, a reset means "clear this property, whatever
		 * viewport it was set for". With it ON the author is addressing one
		 * viewport deliberately, and Desktop must clear base only — which is why
		 * this is gated on the option and not on `breakpoint === 'base'`, a
		 * condition RS-on + Desktop also satisfies.
		 */
		const clearsAllStates = coreViewportStatesAreIndependent() && ! responsiveEditing;

		/**
		 * Process the reset action after core reset completes.
		 * We need to wait for the core reset to actually happen before reading the new attributes.
		 */
		requestAnimationFrame( () => {
			try {
				this.processReset( getSelectedBlock(), breakpoint, blockBeforeReset, resetType, clearsAllStates );
			} finally {
				// Never leak the flag: left true, every later edit would take
				// the raw pass-through and Tablet/Mobile edits would land in
				// the base layer for the rest of the session.
				isResetInProgress = false;
			}
		} );
	},

	/**
	 * Apply the post-reset cleanup for the selected block.
	 *
	 * @param {Object}  block            The selected block, if any.
	 * @param {string}  breakpoint       Store device key for the active preview.
	 * @param {Object}  blockBeforeReset The block as it was before core's reset ran.
	 * @param {string}  resetType        Either 'individual' or 'resetAll'.
	 * @param {boolean} clearsAllStates  Whether to clear the reset paths from the
	 *                                   viewport states as well as the target
	 *                                   layer. True only while core's "Responsive
	 *                                   styles" option is off.
	 * @return {void}
	 */
	processReset( block, breakpoint, blockBeforeReset, resetType, clearsAllStates = false ) {
		{
			// Validate we have an allowed block with responsive attributes
			if ( ! block || ! isAllowedBlock( block ) ) {return;}

			const { attributes, name, clientId } = block;
			const responsiveAttrs = extractResponsiveAttributes( attributes, name );

			const { updateBlockAttributes, __unstableMarkNextChangeAsNotPersistent } = dispatch( 'core/block-editor' );

			// Detect what was actually reset by comparing BEFORE and AFTER states.
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

			/*
			 * Delete the reset paths from the device's own `style` state — the
			 * single source every read derives from. Core's reset has already
			 * cleared what it owns; this removes Spectra-only keys and anything
			 * the setAttributes overlay may have re-added from stale data. It
			 * writes nothing else: no store attribute, no device projection —
			 * both re-created the base-corruption regressions this extension
			 * spent three iterations removing.
			 */
			const nextStyle = deepClone( attributes.style || {} );
			let container = nextStyle;

			if ( 'base' !== breakpoint ) {
				/*
				 * Put the BASE layer back exactly as it was before core's reset.
				 *
				 * Core binds its panel items to the base layer whenever its
				 * Responsive Styles mode is off — the default — so a "Reset all"
				 * pressed while previewing Mobile clears DESKTOP's values. Spectra
				 * then clears the device's own on top, and one device-scoped action
				 * destroys two layers. Restoring base is what makes the reset mean
				 * "reset this device".
				 *
				 * Only the base keys are restored, never the viewport states: when
				 * core IS in responsive mode it clears the previewed state itself,
				 * and that is exactly the work this reset is supposed to do. An
				 * earlier attempt rebuilt the whole style from the pre-reset
				 * snapshot and silently undid core's own clearing, turning "Reset
				 * all" into a no-op in that mode.
				 */
				const styleBeforeReset = blockBeforeReset.attributes?.style;

				Object.keys( nextStyle ).forEach( ( key ) => {
					if ( ! key.startsWith( '@' ) ) {
						delete nextStyle[ key ];
					}
				} );

				if ( isObject( styleBeforeReset ) ) {
					Object.keys( styleBeforeReset ).forEach( ( key ) => {
						if ( ! key.startsWith( '@' ) ) {
							nextStyle[ key ] = deepClone( styleBeforeReset[ key ] );
						}
					} );
				}

				container = isObject( nextStyle[ breakpoint ] ) ? nextStyle[ breakpoint ] : {};
			}

			filteredResetProperties.forEach( ( path ) => {
				// Paths are in the extracted bucket shape (`style.spacing.padding`,
				// or a flat key); state objects hold the groups directly.
				const statePath = path.startsWith( 'style.' ) ? path.slice( 6 ) : path;
				deleteNested( container, statePath );
			} );

			pruneEmptyObjects( container );

			/*
			 * Clear the same paths from every viewport state.
			 *
			 * Only with core's "Responsive styles" off, where the panel spoke for
			 * no particular viewport — see `clearsAllStates` where it is derived.
			 * A state left holding the property would override the base value this
			 * reset just cleared, so the author would watch a viewport they were
			 * not previewing change while the one they were looking at did not.
			 */
			if ( clearsAllStates ) {
				Object.keys( nextStyle ).forEach( ( key ) => {
					if ( ! key.startsWith( '@' ) || ! isObject( nextStyle[ key ] ) ) {
						return;
					}

					filteredResetProperties.forEach( ( path ) => {
						const statePath = path.startsWith( 'style.' ) ? path.slice( 6 ) : path;
						deleteNested( nextStyle[ key ], statePath );
					} );

					pruneEmptyObjects( nextStyle[ key ] );

					if ( ! Object.keys( nextStyle[ key ] ).length ) {
						delete nextStyle[ key ];
					}
				} );
			}

			if ( 'base' !== breakpoint ) {
				if ( Object.keys( container ).length ) {
					nextStyle[ breakpoint ] = container;
				} else {
					delete nextStyle[ breakpoint ];
				}
			}

			// Merge into core's own reset step rather than adding a second undo entry.
			__unstableMarkNextChangeAsNotPersistent();
			updateBlockAttributes( clientId, { style: nextStyle } );
		}
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

/*
 * Initialise exactly one reset handler.
 *
 * Both watch the same clicks on core's panel menus, and both write the cleared
 * paths back — but to different places: this one to core's `style` states, the
 * pre-7.1 one to `responsiveControls`. Running both would have each undo the
 * other's idea of what a reset means.
 */
if ( coreViewportStatesAreIndependent() ) {
	ResponsiveControlsClickHandler.init();
} else {
	registerClassicResetHandler();
}
