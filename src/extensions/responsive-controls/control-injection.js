/**
 * Control Injection System for Responsive Device Buttons.
 *
 * This module enhances Gutenberg's block editor by injecting responsive device
 * buttons (Desktop, Tablet, Mobile) into existing core controls. It enables users
 * to quickly switch between device views when editing responsive attributes like
 * padding, margin, typography, and more.
 *
 * Key Features:
 * - Automatically detects responsive controls based on label text.
 * - Maintains sync with WordPress device preview state.
 * - Handles DOM re-rendering and mutations gracefully.
 * - Only activates for Spectra blocks to avoid conflicts.
 * - Persists button state across editor interactions.
 *
 * @since x.x.x
 */

import { __, sprintf } from '@wordpress/i18n';
import { select, dispatch, subscribe } from '@wordpress/data';
import { MOBILE, TABLET, DESKTOP } from './utils/constants';
import { isAllowedBlock } from './utils/helpers';

/**
 * Device button configuration with SVG icons and labels.
 *
 * Each device type includes:.
 * - label: Translated button label for accessibility.
 * - icon: SVG icon representing the device type.
 *
 * Icons are from Lucide and designed to be minimal and match WordPress admin UI style.
 *
 * @since x.x.x
 *
 * @type {Object.<string, {label: string, icon: string}>}
 */
const DEVICES = Object.freeze( {
	[ DESKTOP ]: {
		label: __( 'Desktop', 'spectra' ),
		icon:
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-monitor-icon lucide-monitor"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>',
	},
	[ TABLET ]: {
		label: __( 'Tablet', 'spectra' ),
		icon:
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tablet-icon lucide-tablet"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><line x1="12" x2="12.01" y1="18" y2="18"/></svg>',
	},
	[ MOBILE ]: {
		label: __( 'Mobile', 'spectra' ),
		icon:
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-smartphone-icon lucide-smartphone"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>',
	},
} );

/**
 * List of panel names that should have responsive controls.
 *
 * All controls within these panels will automatically receive responsive device buttons
 * (Desktop/Tablet/Mobile). This approach ensures that new controls added to these
 * panels automatically get responsive capabilities without needing to update any
 * configuration.
 *
 * Panel names are case-insensitive and matched against the panel heading text.
 *
 * @type {string[]}
 */
const RESPONSIVE_CONTROLS_PANELS = Object.freeze( [
	'Spacing',
	'Typography',
	'Border & Shadow',
	'Border',
	'Shadow',
	'Layout',
	'Background',
	'Dimensions',
	'Content',
	'Overlay Settings',
	'Flex Direction',
	'Separator',
] );

/**
 * Block-specific panel inclusions.
 *
 * Structure: { blockName: [ panelNames ] }.
 * This allows specific blocks to have responsive controls in panels that are not in RESPONSIVE_CONTROLS_PANELS.
 *
 * @type {Object}
 */
const BLOCK_PANEL_INCLUSIONS = Object.freeze( {
	'spectra/slider': Object.freeze( [ 'general' ] ),
	'core/image': Object.freeze( [ 'Settings' ] ),
} );

/**
 * Control Injection Manager - Main class for managing responsive device buttons.
 *
 * This class handles:
 * - DOM observation for new controls.
 * - Device state synchronization.
 * - Button injection and lifecycle.
 * - Event handling for device switching.
 *
 * @class
 * @since x.x.x
 */
class ControlInjectionManager {
	/**
	 * Constructs an instance of ControlInjectionManager.
	 *
	 * Initializes the current device type by selecting it from the WordPress editor
	 * state, defaulting to 'DESKTOP' if unavailable. Sets the observer to null and
	 * calls the init method to set up the control injection system.
	 *
	 * @since x.x.x
	 */
	constructor() {
		this.currentDevice = select( 'core/editor' )?.getDeviceType?.() || DESKTOP;
		this.observer = null;
		this.tabState = this.getTabStateFromStorage(); // Get persisted tab state.
		this.lastClickedPosition = null; // Track exact scroll position for restoration.
		this.injectionTimeout = null; // Track pending injection timeout to prevent duplicate injections.
		this.intersectionObserver = null; // Intersection observer for efficient panel detection.
		this.isBlockStabilizing = false; // Flag to track block stabilization during initial insertion
		this.tabSwitchTimeout = null; // Track tab switch injection timeout to prevent duplicate calls
		this.buttonStateTimeout = null; // Track button state update timeout for cleanup
		this.isDestroyed = false; // Flag to track if instance has been destroyed

		// Cache frequently used selectors for better performance.
		this.selectors = Object.freeze( {
			inspector: '.block-editor-block-inspector',
			sidebar: '.interface-complementary-area.editor-sidebar', // Actual scrollable container.
			toolsPanel: '.editor-sidebar .components-tools-panel',
			panelBody: '.editor-sidebar .components-panel__body',
			toolsPanelItem: '.editor-sidebar .components-tools-panel-item',
			responsiveIcons: '.editor-sidebar .spectra-responsive-icons',
			enhancedControl: '.editor-sidebar .spectra-enhanced-control',
			excludedControl: '.editor-sidebar .spectra-excluded-control',
			tabs: '.editor-sidebar .block-editor-block-inspector__tabs',
			tabButton: ' [role="tab"]',
			activeTab: '.editor-sidebar .block-editor-block-inspector__tabs [aria-selected="true"]',
		} );

		// Create a debounced version of injectDeviceButtons.
		this.debouncedInject = this.debounce( () => {
			this.injectDeviceButtons();
		}, 50 ); // Increased debounce time for better performance

		// Create an immediate (non-debounced) version for block changes.
		this.immediateInject = () => {
			this.injectDeviceButtons();
		};

		// Cache for DOM queries to avoid repeated lookups - with aggressive cleanup
		this.domCache = new Map();
		this.maxCacheSize = 50; // Reduced for memory safety
		this.cachedScrollContainer = null; // Cache the scrollable container for performance.

		// Track core/image attributes that affect control visibility
		this.lastImageAttributes = {};
		this.maxImageAttributesCacheSize = 10; // Limit memory usage.

		this.init();
	}

	/**
	 * Debounce utility function to limit function calls.
	 *
	 * @since x.x.x
	 *
	 * @param {Function} func - Function to debounce
	 * @param {number} wait - Wait time in milliseconds
	 * @return {Function} Debounced function
	 */
	debounce( func, wait ) {
		let timeout;
		const debounced = function executedFunction( ...args ) {
			const later = () => {
				clearTimeout( timeout );
				func( ...args );
			};
			clearTimeout( timeout );
			timeout = setTimeout( later, wait );
		};

		// Add cancel method to prevent memory leaks
		debounced.cancel = () => {
			clearTimeout( timeout );
		};

		return debounced;
	}

	/**
	 * Initialize the control injection system.
	 *
	 * Sets up all necessary observers, listeners, and performs initial injection
	 * of device buttons once the editor is ready.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */
	init() {
		this.waitForEditor()
			.then( () => {
				try {
					// Setup listeners first for maximum responsiveness.
					this.setupDeviceListener();
					this.setupTabObserver();
					this.setupPanelClickHandler();

					this.immediateInject();

					this.startObserver();
				} catch ( error ) {
					// Silently handle error - don't expose to end users.
					// Attempt cleanup to prevent memory leaks.
					this.destroy();
				}
			} )
			.catch( () => {
				// Silently handle error - feature will be disabled but editor continues.
			} );
	}

	/**
	 * Get the actual scrollable container with caching.
	 *
	 * @since x.x.x
	 *
	 * @return {HTMLElement|null} The scrollable container element.
	 */
	getScrollableContainer() {
		if ( this.cachedScrollContainer && this.cachedScrollContainer.isConnected ) {
			return this.cachedScrollContainer;
		}

		// Try to find the actual scrollable container first.
		const sidebar = document.querySelector( this.selectors.sidebar );
		if ( sidebar ) {
			this.cachedScrollContainer = sidebar;
			return sidebar;
		}

		// Fallback to inspector.
		const inspector = document.querySelector( this.selectors.inspector );
		if ( inspector ) {
			this.cachedScrollContainer = inspector;
			return inspector;
		}

		return null;
	}

	/**
	 * Wait for the WordPress editor to be fully loaded.
	 *
	 * Polls for the presence of the editor store and block inspector DOM element
	 * before proceeding with initialization.
	 *
	 * @since x.x.x
	 *
	 * @return {Promise<void>} Resolves when editor is ready
	 */
	async waitForEditor() {
		return new Promise( ( resolve ) => {
			const check = () => {
				if ( select( 'core/editor' ) && this.getScrollableContainer() ) {
					resolve();
				} else {
					requestAnimationFrame( check );
				}
			};
			check();
		} );
	}

	/**
	 * Check if the block is in a stabilizing state during initial insertion.
	 * This prevents injection issues when blocks are being inserted but haven't
	 * fully stabilized (e.g., container blocks without variations selected).
	 *
	 * @since x.x.x
	 *
	 * @param {Object} selectedBlock - The currently selected block
	 * @return {boolean} True if the block is stabilizing, false otherwise.
	 */
	isBlockInStabilizingState( selectedBlock ) {
		// Fast path: return false for non-container blocks or missing blocks
		if ( ! selectedBlock || selectedBlock.name !== 'spectra/container' ) {
			return false;
		}

		const attributes = selectedBlock.attributes;

		// Quick check: if block has many attributes, it's likely stable
		if ( Object.keys( attributes ).length > 3 ) {
			return false;
		}

		// Check if it's missing essential attributes that would normally be set after variation selection
		// Use direct property access for better performance
		return ! attributes.htmlTag && ! attributes.background && ! attributes.responsiveControls;
	}

	/**
	 * Check if a panel is currently visible, accounting for tab visibility.
	 * This is more robust than just checking offsetHeight during tab switches.
	 * Optimized for performance with early returns and minimal DOM queries.
	 *
	 * @since x.x.x
	 *
	 * @param {HTMLElement} panel - The panel element to check
	 * @return {boolean} True if the panel is visible, false otherwise.
	 */
	isPanelVisible( panel ) {
		if ( ! panel ) return false;

		// Fast path: if panel is clearly visible, return immediately
		if ( panel.offsetHeight > 0 && panel.offsetParent !== null ) {
			return true;
		}

		// Slower path: only check tab visibility if basic visibility failed
		// This reduces DOM queries for normally visible panels
		const tabPanel = panel.closest( '[role="tabpanel"]' );
		if ( ! tabPanel ) {
			return false; // Not in a tab and not visible
		}

		// Check if we're in the active tab (most common case during tab switches)
		const isActiveTab = tabPanel.getAttribute( 'aria-hidden' ) !== 'true';
		if ( isActiveTab ) {
			return true; // In active tab, should be injectable
		}

		// Final check: For inactive tabs, only inject if panel structure exists
		// Use a quick check instead of full querySelector for performance
		return (
			panel.classList.contains( 'components-tools-panel' ) || panel.classList.contains( 'components-panel__body' )
		);
	}

	/**
	 * Sets up a MutationObserver to watch for changes to the block inspector's control tree.
	 *
	 * If either of the above conditions are true, the observer will reinject the device
	 * buttons after a 100ms delay. This is done to ensure that the buttons are injected
	 * after the control has finished rendering.
	 *
	 * @since x.x.x
	 */
	startObserver() {
		const target = document.querySelector( this.selectors.inspector ) || document.body;

		// Batch mutations and process them together.
		const mutationBatch = [];
		let mutationTimer = null;

		const processMutations = function () {
			let shouldReinject = false;

			// Process all batched mutations at once.
			for ( const mutation of mutationBatch ) {
				// Skip attribute changes unless it's a relevant element.
				if ( mutation.type === 'attributes' && ! shouldReinject ) {
					const mutationTarget = mutation.target;
					if (
						mutationTarget.nodeType === Node.ELEMENT_NODE &&
						( mutationTarget.classList?.contains( 'components-tools-panel-item' ) ||
							mutationTarget.closest?.( '.components-tools-panel-item' ) ) &&
						! mutationTarget.querySelector( '.spectra-responsive-icons' )
					) {
						shouldReinject = true;
					}
				}

				// Check for added elements (needed for Scale control in core/image only).
				if ( mutation.type === 'childList' && ! shouldReinject ) {
					const currentBlock = select( 'core/block-editor' )?.getSelectedBlock?.();
					if ( currentBlock?.name === 'core/image' ) {
						for ( const addedNode of mutation.addedNodes ) {
							if (
								addedNode.nodeType === Node.ELEMENT_NODE &&
								( addedNode.classList?.contains( 'components-tools-panel-item' ) ||
									addedNode.querySelector?.( '.components-tools-panel-item' ) )
							) {
								shouldReinject = true;
								break;
							}
						}
					}
				}

				if ( shouldReinject ) break;
			}

			// Clear the batch to prevent memory leak
			mutationBatch.length = 0;
			mutationTimer = null;

			// Guard: Skip if instance has been destroyed.
			if ( this.isDestroyed || typeof this.debouncedInject !== 'function' ) {
				return;
			}

			if ( shouldReinject ) {
				// Cache block check to avoid repeated selects
				const selectedBlock = select( 'core/block-editor' )?.getSelectedBlock?.();
				if ( isAllowedBlock( selectedBlock ) ) {
					// Use immediate injection for new blocks, debounced for other changes.
					const hasNewPanels = false; // Simplified - mutationBatch is already cleared

					if ( hasNewPanels ) {
						// New panels detected - use instant injection for immediate appearance.
						this.instantInjectPanels();
						this.immediateInject();
					} else {
						this.debouncedInject();
					}
				} else {
					this.debouncedInject();
				}
			}
		}.bind( this );

		this.observer = new MutationObserver( ( mutations ) => {
			// Add mutations to batch.
			mutationBatch.push( ...mutations );

			// Aggressive batch size limit to prevent memory issues
			if ( mutationBatch.length > 10 ) {
				mutationBatch.splice( 0, mutationBatch.length - 10 );
			}

			// Clear existing timer.
			if ( mutationTimer ) {
				clearTimeout( mutationTimer );
			}

			// Process mutations after a balanced delay.
			mutationTimer = setTimeout( processMutations, 100 ); // Further increased to reduce CPU usage
		} );

		this.observer.observe( target, {
			subtree: true,
			childList: true,
			attributes: true,
			attributeFilter: [ 'class', 'style' ],
		} );
	}

	/**
	 * Check if a node is relevant for responsive controls.
	 *
	 * @since x.x.x
	 *
	 * @param {Node} node - The node to check.
	 * @return {boolean} True if node is relevant.
	 */
	isRelevantNode( node ) {
		return (
			node.classList?.contains( 'components-tools-panel-item' ) ||
			node.classList?.contains( 'components-tools-panel__item' ) ||
			node.querySelector?.( '.components-tools-panel-item' ) ||
			node.querySelector?.( '.components-tools-panel__item' )
		);
	}

	/**
	 * Instant panel injection - completely synchronous, zero delays.
	 * This is the fastest possible injection for immediate button appearance.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */
	instantInjectPanels() {
		// Get scrollable container immediately.
		const container = this.getScrollableContainer();
		if ( ! container ) return;

		// Get all panels in one query.
		const panels = container.querySelectorAll( `${ this.selectors.toolsPanel }, ${ this.selectors.panelBody }` );

		// Process first 2 visible panels immediately (no loops, no delays).
		let processed = 0;
		for ( let i = 0; i < panels.length && processed < 2; i++ ) {
			const panel = panels[ i ];

			// Quick checks first.
			if ( ! this.panelHasResponsiveControls( panel ) || this.hasDeviceButtons( panel ) ) {
				continue;
			}

			// Enhanced visibility check - also check if panel is in active tab
			const isVisible = this.isPanelVisible( panel );
			if ( isVisible ) {
				// Direct button injection - no method calls.
				if ( panel.classList.contains( 'components-tools-panel' ) ) {
					const heading = panel.querySelector( '.components-tools-panel-header h2' );
					if ( heading && ! heading.querySelector( '.spectra-responsive-icons' ) ) {
						const iconsContainer = document.createElement( 'span' );
						iconsContainer.className = 'spectra-responsive-icons spectra-panel-responsive-icons';
						iconsContainer.innerHTML = this.createDeviceButtonsHTML();
						heading.appendChild( iconsContainer );
						panel.classList.add( 'spectra-enhanced-panel' );
						this.attachDeviceButtonHandlers( iconsContainer );

						// Add help text
						const helpTextHTML = this.createHelpTextHTML();
						if ( helpTextHTML ) {
							const header = panel.querySelector( '.components-tools-panel-header' );
							if ( header ) {
								header.insertAdjacentHTML( 'afterend', helpTextHTML );
							}
						}
					}
				} else if ( panel.classList.contains( 'components-panel__body' ) ) {
					const titleButton = panel.querySelector( '.components-panel__body-title button' );
					if ( titleButton && ! titleButton.querySelector( '.spectra-responsive-icons' ) ) {
						const iconsContainer = document.createElement( 'span' );
						iconsContainer.className = 'spectra-responsive-icons spectra-panel-responsive-icons';
						iconsContainer.innerHTML = this.createDeviceButtonsHTML();
						titleButton.appendChild( iconsContainer );
						panel.classList.add( 'spectra-enhanced-panel' );
						this.attachDeviceButtonHandlers( iconsContainer );
					}
				}
				processed++;
			}
		}
	}

	/**
	 * Maintain panel states efficiently.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */
	maintainPanelStates() {
		let hasChanges = false;

		// Get selected block once
		const selectedBlock = select( 'core/block-editor' ).getSelectedBlock();

		// Process all controls at once.
		const allControls = document.querySelectorAll( this.selectors.toolsPanelItem );
		const controlsToUpdate = [];

		for ( const control of allControls ) {
			const panel = control.closest( `${ this.selectors.toolsPanel }, ${ this.selectors.panelBody }` );
			if ( ! panel || ! this.panelHasResponsiveControls( panel, selectedBlock ) ) {
				continue;
			}

			const shouldApplyClasses = this.shouldApplyControlClasses( control, selectedBlock );
			const hasEnhanced = control.classList.contains( 'spectra-enhanced-control' );
			const hasExcluded = control.classList.contains( 'spectra-excluded-control' );

			if ( shouldApplyClasses ) {
				const isResponsive = this.isResponsiveControl( control, selectedBlock );
				if ( isResponsive && ! hasEnhanced ) {
					controlsToUpdate.push( { control, action: 'enhance' } );
					hasChanges = true;
				} else if ( ! isResponsive && ! hasExcluded ) {
					controlsToUpdate.push( { control, action: 'exclude' } );
				}
			}
		}

		// Apply all updates at once.
		if ( controlsToUpdate.length > 0 ) {
			requestAnimationFrame( () => {
				for ( const { control, action } of controlsToUpdate ) {
					if ( action === 'enhance' ) {
						control.classList.add( 'spectra-enhanced-control' );
						control.classList.remove( 'spectra-excluded-control' );
					}
				}

				if ( hasChanges ) {
					this.updateButtonStates();
				}
			} );
		}

		// Check and update panel bodies.
		this.updatePanelBodies();
	}

	/**
	 * Update panel bodies efficiently.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */
	updatePanelBodies() {
		const panelBodies = document.querySelectorAll( this.selectors.panelBody );
		const updates = [];

		for ( const panel of panelBodies ) {
			if ( this.panelHasResponsiveControls( panel ) ) {
				if ( ! panel.classList.contains( 'spectra-enhanced-panel' ) ) {
					updates.push( { panel, action: 'addClass' } );
				}

				if ( ! this.hasDeviceButtons( panel ) ) {
					updates.push( { panel, action: 'addButtons' } );
				}
			}
		}

		// Apply updates.
		if ( updates.length > 0 ) {
			requestAnimationFrame( () => {
				for ( const { panel, action } of updates ) {
					if ( action === 'addClass' ) {
						panel.classList.add( 'spectra-enhanced-panel' );
					} else if ( action === 'addButtons' ) {
						this.addDeviceButtonsToPanelBody( panel );
					}
				}
			} );
		}
	}

	/**
	 * Set up device state listener.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */
	setupDeviceListener() {
		let lastSelectedBlockId = null;

		// Store unsubscribe function to prevent memory leak
		this.unsubscribe = subscribe( () => {
			const editorStore = select( 'core/editor' );
			const blockEditorStore = select( 'core/block-editor' );

			// Cache store calls to avoid repeated selects
			const newDevice = editorStore?.getDeviceType?.() || DESKTOP;
			const selectedBlock = blockEditorStore?.getSelectedBlock?.();
			const currentBlockId = selectedBlock?.clientId || null;

			// Check for core/image attribute changes that affect control visibility.
			if ( selectedBlock?.name === 'core/image' ) {
				const { aspectRatio, width, height } = selectedBlock.attributes || {};
				const currentAttrs = { aspectRatio, width, height };
				const lastAttrs = this.lastImageAttributes[ selectedBlock.clientId ] || {};

				// Check if any tracked attributes changed.
				if (
					aspectRatio !== lastAttrs.aspectRatio ||
					width !== lastAttrs.width ||
					height !== lastAttrs.height
				) {
					// Memory management: Clean cache if it gets too large.
					const cacheKeys = Object.keys( this.lastImageAttributes );
					if ( cacheKeys.length >= this.maxImageAttributesCacheSize ) {
						// Keep only the most recent entries.
						const keysToKeep = cacheKeys.slice( -5 );
						const newCache = {};
						keysToKeep.forEach( ( key ) => {
							newCache[ key ] = this.lastImageAttributes[ key ];
						} );
						this.lastImageAttributes = newCache;
					}

					this.lastImageAttributes[ selectedBlock.clientId ] = currentAttrs;

					// Reinject when these attributes change (affects Scale control visibility).
					setTimeout( () => {
						this.immediateInject();
					}, 100 );
				}
			}

			// Handle device changes.
			if ( newDevice !== this.currentDevice ) {
				this.currentDevice = newDevice;
				this.updateButtonStates();

				// Use requestAnimationFrame for smoother updates.
				requestAnimationFrame( () => {
					// Guard: Skip if instance has been destroyed.
					if ( this.isDestroyed || typeof this.debouncedInject !== 'function' ) {
						return;
					}
					this.debouncedInject();
					this.restoreTabState();
					this.restoreScrollPosition();
				} );
			}

			// Handle block selection changes - use intersection observer instead of multiple timeouts.
			if ( currentBlockId !== lastSelectedBlockId ) {
				lastSelectedBlockId = currentBlockId;

				if ( isAllowedBlock( selectedBlock ) ) {
					// Check if block is in a stabilizing state during initial insertion
					const isStabilizing = this.isBlockInStabilizingState( selectedBlock );

					if ( isStabilizing ) {
						// Set stabilizing flag and delay injection
						this.isBlockStabilizing = true;

						// Use a longer delay for stabilizing blocks to allow DOM to settle
						setTimeout( () => {
							// Re-check if block is still selected and now stable
							const currentBlock = select( 'core/block-editor' )?.getSelectedBlock?.();
							if (
								currentBlock &&
								currentBlock.clientId === currentBlockId &&
								! this.isBlockInStabilizingState( currentBlock )
							) {
								this.isBlockStabilizing = false;
								this.instantInjectPanels();
								this.immediateInject();
								this.updateButtonStates();
							}
						}, 200 ); // Longer delay for block stabilization
					} else {
						// INSTANT injection - completely synchronous, no delays.
						this.isBlockStabilizing = false;
						this.instantInjectPanels();

						// Follow up with full injection and observation.
						this.immediateInject();
						this.updateButtonStates();
					}
				}
			}
		} );
	}

	/**
	 * Inject device buttons into responsive controls.
	 *
	 * Scans the block inspector for panels that should have responsive controls
	 * and adds device buttons to the panel header. Only runs for Spectra blocks.
	 *
	 * @since x.x.x
	 *
	 * @param {Document|HTMLElement} container - Container to search for controls.
	 * @return {void}
	 */
	injectDeviceButtons( container = document ) {
		try {
			// Only inject for Spectra blocks.
			const selectedBlock = select( 'core/block-editor' )?.getSelectedBlock?.();
			if ( ! isAllowedBlock( selectedBlock ) ) {
				return;
			}

			// Skip injection if block is currently stabilizing during initial insertion
			if ( this.isBlockStabilizing || this.isBlockInStabilizingState( selectedBlock ) ) {
				return;
			}

			const targetContainer =
				container === document ? document.querySelector( this.selectors.inspector ) || document : container;

			// Batch DOM reads.
			const toolsPanels = Array.from( targetContainer.querySelectorAll( this.selectors.toolsPanel ) );
			const panelBodies = Array.from( targetContainer.querySelectorAll( this.selectors.panelBody ) );
			const allControls = Array.from( targetContainer.querySelectorAll( this.selectors.toolsPanelItem ) );

			// Process in requestAnimationFrame for better performance.
			requestAnimationFrame( () => {
				try {
					// Process ToolsPanels.
					for ( const panel of toolsPanels ) {
						if (
							this.panelHasResponsiveControls( panel, selectedBlock ) &&
							! this.hasDeviceButtons( panel ) &&
							this.isPanelVisible( panel )
						) {
							this.addDeviceButtonsToPanel( panel );
						}
					}

					// Process Panel Bodies.
					for ( const panel of panelBodies ) {
						if ( this.panelHasResponsiveControls( panel, selectedBlock ) && this.isPanelVisible( panel ) ) {
							if ( ! panel.classList.contains( 'spectra-enhanced-panel' ) ) {
								panel.classList.add( 'spectra-enhanced-panel' );
							}

							if ( ! this.hasDeviceButtons( panel ) ) {
								this.addDeviceButtonsToPanelBody( panel );
							}
						}
					}

					// Process individual controls.
					const controlUpdates = [];
					for ( const control of allControls ) {
						const panel = control.closest(
							`${ this.selectors.toolsPanel }, ${ this.selectors.panelBody }`
						);
						if ( panel && this.panelHasResponsiveControls( panel, selectedBlock ) ) {
							const shouldApplyClasses = this.shouldApplyControlClasses( control, selectedBlock );

							if ( shouldApplyClasses ) {
								if ( this.isResponsiveControl( control, selectedBlock ) ) {
									if ( ! control.classList.contains( 'spectra-enhanced-control' ) ) {
										controlUpdates.push( { control, type: 'enhance' } );
									}
								} else if ( ! control.classList.contains( 'spectra-excluded-control' ) ) {
									controlUpdates.push( { control, type: 'exclude' } );
								}
							}
						}
					}

					// Apply control updates.
					for ( const { control, type } of controlUpdates ) {
						if ( type === 'enhance' ) {
							control.classList.add( 'spectra-enhanced-control' );
							control.classList.remove( 'spectra-excluded-control' );
						} else {
							control.classList.add( 'spectra-excluded-control' );
							control.classList.remove( 'spectra-enhanced-control' );
						}
					}

					// Update button states.
					this.updateButtonStates();
				} catch ( error ) {
					// Silently handle error during control processing.
				}
			} );
		} catch ( error ) {
			// Silently handle error - non-critical feature shouldn't break the editor.
		}
	}

	/**
	 * Check if a control should have enhanced/excluded classes applied.
	 * Typography panel controls are excluded from getting these classes.
	 *
	 * @since x.x.x
	 *
	 * @param {HTMLElement} control - The control element to check.
	 * @param {Object} selectedBlock - Optional cached selected block to avoid repeated selectors.
	 * @return {boolean} True if control should have enhanced/excluded classes.
	 */
	shouldApplyControlClasses( control, selectedBlock = null ) {
		const panel = control.closest( `${ this.selectors.toolsPanel }, ${ this.selectors.panelBody }` );
		if ( ! panel ) {
			return false;
		}

		const panelTitle = this.getPanelTitle( panel );

		// Don't apply classes to Typography panel controls (translation-aware)
		if ( this.isPanelTitleMatch( panelTitle, 'Typography' ) ) {
			return false;
		}

		return this.isResponsiveControl( control, selectedBlock );
	}

	/**
	 * Check if a control should have responsive device buttons.
	 *
	 * Examines the control's aria-label, legend text, and label elements to
	 * determine if it matches any of the RESPONSIVE_CONTROLS patterns.
	 *
	 * @since x.x.x
	 *
	 * @param {HTMLElement} control - The control element to check.
	 * @param {Object} selectedBlock - Optional cached selected block to avoid repeated selectors.
	 * @return {boolean} True if control should have device buttons.
	 */
	isResponsiveControl( control, selectedBlock = null ) {
		// Check cache first with size limit
		if ( this.domCache.has( control ) ) {
			return this.domCache.get( control );
		}

		// Aggressive cache management to prevent memory leaks
		if ( this.domCache.size >= this.maxCacheSize ) {
			// Clear entire cache when full - prevents gradual accumulation
			this.domCache.clear();
		}

		// Find which panel this control belongs to.
		const panel = control.closest( `${ this.selectors.toolsPanel }, ${ this.selectors.panelBody }` );
		if ( ! panel ) {
			this.domCache.set( control, false );
			return false;
		}

		// Use passed selectedBlock or get it once.
		const currentBlock = selectedBlock || select( 'core/block-editor' )?.getSelectedBlock?.();
		if ( ! currentBlock ) {
			this.domCache.set( control, false );
			return false;
		}

		// Get control text for checking.
		const texts = [];

		// Get aria-label.
		const ariaLabel = control.getAttribute( 'aria-label' );
		if ( ariaLabel ) texts.push( ariaLabel.toLowerCase() );

		// Get legend text.
		const legend = control.querySelector( 'legend' );
		if ( legend?.textContent ) texts.push( legend.textContent.toLowerCase() );

		// Get label texts.
		const labels = control.querySelectorAll(
			'label, .components-base-control__label, .components-toggle-control__label'
		);
		for ( const label of labels ) {
			if ( label.textContent ) texts.push( label.textContent.toLowerCase() );
		}

		// Get help text.
		const help = control.querySelector( '.components-base-control__help' );
		if ( help?.textContent ) texts.push( help.textContent.toLowerCase() );

		const allTexts = texts.join( ' ' );
		const panelTitle = this.getPanelTitle( panel );

		// Special handling for slider General panel - only include specific controls (translation-aware).
		if ( currentBlock.name === 'spectra/slider' && this.isPanelTitleMatch( panelTitle, 'General' ) ) {
			const sliderInclusions = [ 'Slides Per View', 'Space Between Slides' ];
			const isIncluded = sliderInclusions.some( ( includedControl ) => {
				// Check English version
				if ( allTexts.includes( includedControl.toLowerCase() ) ) {
					return true;
				}
				// Check translated version (Spectra textdomain for slider controls)
				// eslint-disable-next-line @wordpress/i18n-no-variables
				const translated = __( includedControl, 'spectra' ).toLowerCase();
				return allTexts.includes( translated );
			} );
			this.domCache.set( control, isIncluded );
			return isIncluded;
		}

		// Special handling for core Image block Settings panel - only include specific controls (translation-aware).
		if ( currentBlock.name === 'core/image' && this.isPanelTitleMatch( panelTitle, 'Settings' ) ) {
			const imageInclusions = [ 'Aspect ratio', 'Width', 'Height', 'Scale' ];
			// Check both English and translated versions of control labels
			const isIncluded = imageInclusions.some( ( includedControl ) => {
				// Check English version
				if ( allTexts.includes( includedControl.toLowerCase() ) ) {
					return true;
				}
				// Check translated version (WordPress core textdomain for image block controls)
				// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
				const translated = __( includedControl, 'default' ).toLowerCase();
				return allTexts.includes( translated );
			} );
			// Don't cache core/image controls as they can appear/disappear dynamically.
			return isIncluded;
		}

		// Check if this panel is in RESPONSIVE_CONTROLS_PANELS.
		if ( ! this.panelHasResponsiveControls( panel, currentBlock ) ) {
			this.domCache.set( control, false );
			return false;
		}

		// If we're here, the control is in a responsive panel.
		// All controls in responsive panels should have responsive buttons.
		this.domCache.set( control, true );
		return true;
	}

	/**
	 * Get panel title efficiently.
	 *
	 * @since x.x.x
	 *
	 * @param {HTMLElement} panel - The panel element.
	 * @return {string} The panel title.
	 */
	getPanelTitle( panel ) {
		// Check cache with size management
		if ( this.domCache.has( panel ) ) {
			const cached = this.domCache.get( panel );
			if ( cached?.title !== undefined ) {
				return cached.title;
			}
		}

		// Aggressive cache management
		if ( this.domCache.size >= this.maxCacheSize ) {
			this.domCache.clear();
		}

		let title = '';

		if ( panel.classList.contains( 'components-tools-panel' ) ) {
			const h2 = panel.querySelector( 'h2' );
			title = h2?.textContent?.trim()?.toLowerCase() || '';
		} else if ( panel.classList.contains( 'components-panel__body' ) ) {
			const button = panel.querySelector( '.components-panel__body-title button' );
			title = button?.textContent?.trim()?.toLowerCase() || '';
		}

		// Cache the title.
		const cacheEntry = this.domCache.get( panel ) || {};
		cacheEntry.title = title;
		this.domCache.set( panel, cacheEntry );

		return title;
	}

	/**
	 * Check if a panel title matches a given English name (supports translations).
	 *
	 * @since x.x.x
	 *
	 * @param {string} panelTitle - The actual panel title from DOM (lowercased).
	 * @param {string} englishName - The English panel name to check against.
	 * @return {boolean} True if the panel title matches.
	 */
	isPanelTitleMatch( panelTitle, englishName ) {
		// Fast path: Check English first
		if ( panelTitle === englishName.toLowerCase() ) {
			return true;
		}

		// Check translated version (try both core and Spectra textdomains)
		// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
		const coreTranslation = __( englishName, 'default' ).toLowerCase();
		// eslint-disable-next-line @wordpress/i18n-no-variables
		const spectraTranslation = __( englishName, 'spectra' ).toLowerCase();

		return panelTitle === coreTranslation || panelTitle === spectraTranslation;
	}

	/**
	 * Check if a panel should have responsive controls based on its heading.
	 *
	 * IMPORTANT: This function now handles translated panel names.
	 * WordPress core translates panel names like "Layout" to the current language.
	 * We translate our English panel names and check both English and translated versions.
	 *
	 * @since x.x.x
	 *
	 * @param {HTMLElement} panel - The panel element to check.
	 * @param {Object} selectedBlock - Optional cached selected block to avoid repeated selectors.
	 * @return {boolean} True if panel should have responsive controls.
	 */
	panelHasResponsiveControls( panel, selectedBlock = null ) {
		const panelTitle = this.getPanelTitle( panel );

		// Check standard responsive panels - match both English and translated names.
		const isPanelMatch = RESPONSIVE_CONTROLS_PANELS.some( ( panelName ) => {
			const lowerPanelName = panelName.toLowerCase();
			// Check exact English match first (fastest).
			if ( panelTitle === lowerPanelName ) {
				return true;
			}
			// Check translated version (for non-English languages).
			// Try both WordPress core and Spectra textdomains to handle:
			// 1. WordPress core panels (Spacing, Typography, Layout, etc.)
			// 2. Spectra custom panels translated via Loco Translate (Background, Overlay Settings, etc.)
			// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
			const coreTranslation = __( panelName, 'default' ).toLowerCase();
			// eslint-disable-next-line @wordpress/i18n-no-variables
			const spectraTranslation = __( panelName, 'spectra' ).toLowerCase();
			return panelTitle === coreTranslation || panelTitle === spectraTranslation;
		} );

		if ( isPanelMatch ) {
			return true;
		}

		// Check block-specific panel inclusions.
		const currentBlock = selectedBlock || select( 'core/block-editor' ).getSelectedBlock();
		if ( currentBlock && BLOCK_PANEL_INCLUSIONS[ currentBlock.name ] ) {
			return BLOCK_PANEL_INCLUSIONS[ currentBlock.name ].some( ( includedPanel ) => {
				const lowerIncludedPanel = includedPanel.toLowerCase();
				// Check exact English match.
				if ( panelTitle === lowerIncludedPanel ) {
					return true;
				}
				// Check translated version - use appropriate textdomain based on block type.
				// Core blocks (like core/image) use WordPress core textdomain 'default'.
				// Spectra blocks use 'spectra' textdomain.
				const isCore = currentBlock.name?.startsWith( 'core/' );
				// eslint-disable-next-line @wordpress/i18n-text-domain, @wordpress/i18n-no-variables
				const translatedName = __(
					includedPanel,
					isCore ? 'default' : 'spectra'
				).toLowerCase();
				return panelTitle === translatedName;
			} );
		}

		return false;
	}

	/**
	 * Check if an element already has device buttons.
	 *
	 * @since x.x.x
	 *
	 * @param {HTMLElement} element - The element to check.
	 * @return {boolean} True if buttons already exist.
	 */
	hasDeviceButtons( element ) {
		return !! element.querySelector( this.selectors.responsiveIcons );
	}

	/**
	 * Create device buttons HTML.
	 *
	 * @since x.x.x
	 *
	 * @return {string} The HTML for device buttons.
	 */
	createDeviceButtonsHTML() {
		return Object.entries( DEVICES )
			.map( ( [ device, config ] ) => {
				const isActive = this.currentDevice === device;
				return `
					<button 
						type="button" 
						class="spectra-responsive-icon ${ isActive ? 'is-active' : '' }"
						data-device="${ device }"
						title="${ config.label }"
						aria-label="${ config.label }"
						aria-pressed="${ isActive ? 'true' : 'false' }"
					>
						${ config.icon }
					</button>
				`;
			} )
			.join( '' );
	}

	/**
	 * Create help text HTML based on current device.
	 *
	 * @since x.x.x
	 *
	 * @return {string} The HTML for help text.
	 */
	createHelpTextHTML() {
		let helpText = '';

		if ( this.currentDevice === TABLET ) {
			helpText = sprintf(
				// Translators: %1$s: The opening bold tag; %2$s: The closing bold tag;
				__( '%1$sNote:%2$s Inherits from Desktop on reset.', 'spectra' ),
				'<b>',
				'</b>'
			);
		} else if ( this.currentDevice === MOBILE ) {
			helpText = sprintf(
				// Translators: %1$s: The opening bold tag; %2$s: The closing bold tag;
				__( '%1$sNote:%2$s Inherits from Tablet or Desktop on reset.', 'spectra' ),
				'<b>',
				'</b>'
			);
		}

		if ( ! helpText ) return '';

		// Use WordPress core notice structure
		return `
			<div class="components-notice is-info spectra-responsive-help-notice" role="status">
				<div class="components-notice__content">
					<p>${ helpText }</p>
				</div>
			</div>
		`;
	}

	/**
	 * Add device buttons to a ToolsPanel.
	 *
	 * Creates the device button container with Desktop, Tablet, and Mobile buttons
	 * and appends it to the panel's header.
	 *
	 * @since x.x.x
	 *
	 * @param {HTMLElement} panel - The ToolsPanel element to enhance.
	 * @return {void}
	 */
	addDeviceButtonsToPanel( panel ) {
		const heading = panel.querySelector( '.components-tools-panel-header h2' );
		if ( ! heading ) return;

		// Check if buttons already exist to prevent duplicates.
		if ( heading.querySelector( '.spectra-responsive-icons' ) ) {
			return;
		}

		const iconsContainer = document.createElement( 'span' );
		iconsContainer.className = 'spectra-responsive-icons spectra-panel-responsive-icons';
		iconsContainer.innerHTML = this.createDeviceButtonsHTML();

		heading.appendChild( iconsContainer );

		// Add help text after the panel header
		const helpTextHTML = this.createHelpTextHTML();
		if ( helpTextHTML ) {
			// Check if help text already exists
			const existingHelp = panel.querySelector( '.spectra-responsive-help-notice' );
			if ( existingHelp ) {
				existingHelp.remove();
			}

			// Insert help text after the header
			const header = panel.querySelector( '.components-tools-panel-header' );
			if ( header ) {
				header.insertAdjacentHTML( 'afterend', helpTextHTML );
			}
		}

		panel.classList.add( 'spectra-enhanced-panel' );

		// Use event delegation.
		this.attachDeviceButtonHandlers( iconsContainer, panel );
	}

	/**
	 * Add device buttons to a Panel Body (older structure like Layout).
	 *
	 * Creates the device button container with Desktop, Tablet, and Mobile buttons
	 * and appends it to the panel's title button.
	 *
	 * @since x.x.x
	 *
	 * @param {HTMLElement} panel - The Panel Body element to enhance.
	 * @return {void}
	 */
	addDeviceButtonsToPanelBody( panel ) {
		const selectedBlock = select( 'core/block-editor' )?.getSelectedBlock?.();
		if ( ! isAllowedBlock( selectedBlock ) ) {
			return;
		}

		const titleButton = panel.querySelector( '.components-panel__body-title button' );
		if ( ! titleButton ) return;

		// Check if buttons already exist to prevent duplicates.
		if ( titleButton.querySelector( '.spectra-responsive-icons' ) ) {
			return;
		}

		const iconsContainer = document.createElement( 'span' );
		iconsContainer.className = 'spectra-responsive-icons spectra-panel-responsive-icons';
		iconsContainer.innerHTML = this.createDeviceButtonsHTML();

		titleButton.appendChild( iconsContainer );

		// No help text for panel bodies - only show on ToolsPanel

		panel.classList.add( 'spectra-enhanced-panel' );

		// Use event delegation.
		this.attachDeviceButtonHandlers( iconsContainer );
	}

	/**
	 * Attach device button handlers using event delegation.
	 *
	 * @since x.x.x
	 *
	 * @param {HTMLElement} container - The icons container.
	 * @return {void}
	 */
	attachDeviceButtonHandlers( container ) {
		container.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();

			const button = e.target.closest( '.spectra-responsive-icon' );
			if ( ! button ) return;

			// Track exact scroll position for restoration.
			this.trackExactScrollPosition();

			// Save tab state.
			this.saveCurrentTabState();

			// Switch device.
			this.switchDevice( button.dataset.device );
		} );
	}

	/**
	 * Detect tab type from aria-label, handling translated tab names.
	 *
	 * WordPress core translates tab names like "Styles" and "Settings" to the current language.
	 * This function checks both English and translated versions to correctly detect the tab type.
	 *
	 * @since x.x.x
	 *
	 * @param {string} ariaLabel - The aria-label attribute of the tab.
	 * @return {string} The tab type ('styles' or 'settings').
	 */
	detectTabType( ariaLabel ) {
		if ( ! ariaLabel ) {
			return 'settings'; // Safe default
		}

		const lowerLabel = ariaLabel.toLowerCase();

		// Check for English "Style" first (fastest path).
		if ( lowerLabel.includes( 'style' ) ) {
			return 'styles';
		}

		// Check for translated "Styles" (WordPress core textdomain).
		// eslint-disable-next-line @wordpress/i18n-text-domain
		const translatedStyles = __( 'Styles', 'default' ).toLowerCase();
		if ( translatedStyles !== 'styles' && lowerLabel.includes( translatedStyles ) ) {
			return 'styles';
		}

		// Check for English "Setting" to be explicit.
		if ( lowerLabel.includes( 'setting' ) ) {
			return 'settings';
		}

		// Check for translated "Settings" (WordPress core textdomain).
		// eslint-disable-next-line @wordpress/i18n-text-domain
		const translatedSettings = __( 'Settings', 'default' ).toLowerCase();
		if ( translatedSettings !== 'settings' && lowerLabel.includes( translatedSettings ) ) {
			return 'settings';
		}

		// Default to 'settings' as fallback.
		return 'settings';
	}

	/**
	 * Save current tab state.
	 *
	 * IMPORTANT: This function handles translated tab names.
	 * WordPress core translates tab names like "Styles" and "Settings" to the current language.
	 * We check both English and translated versions to detect the correct tab.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */
	saveCurrentTabState() {
		const activeTab = document.querySelector( this.selectors.activeTab );
		if ( activeTab ) {
			const ariaLabel = activeTab.getAttribute( 'aria-label' );
			if ( ariaLabel ) {
				const tabType = this.detectTabType( ariaLabel );
				this.saveTabStateToStorage( tabType );
			}
		}
	}

	/**
	 * Switch the editor's device preview mode.
	 *
	 * Uses WordPress's experimental API to change device preview and updates
	 * all button states to reflect the new active device.
	 * Supports both post editor (core/edit-post) and site editor (core/edit-site).
	 *
	 * @since x.x.x
	 *
	 * @param {string} device - Device type (Desktop|Tablet|Mobile).
	 * @return {void}
	 */
	switchDevice( device ) {
		// Preserve enhanced panel classes.
		const enhancedPanels = document.querySelectorAll( '.components-panel__body.spectra-enhanced-panel' );
		const panelInfo = Array.from( enhancedPanels )
			.map( ( panel ) => {
				const titleButton = panel.querySelector( '.components-panel__body-title button' );
				if ( titleButton ) {
					const textNode = Array.from( titleButton.childNodes ).find(
						( node ) => node.nodeType === Node.TEXT_NODE && node.textContent.trim()
					);
					if ( textNode ) {
						return {
							title: textNode.textContent.trim(),
							isOpened: panel.classList.contains( 'is-opened' ),
						};
					}
				}
				return null;
			} )
			.filter( Boolean );

		// Trigger WordPress device preview change.
		// Try post editor first, then site editor (FSE).
		const postEditorDispatch = dispatch( 'core/edit-post' );
		if ( postEditorDispatch?.__experimentalSetPreviewDeviceType ) {
			postEditorDispatch.__experimentalSetPreviewDeviceType( device );
		} else {
			// Try site editor (FSE context).
			const siteEditorDispatch = dispatch( 'core/edit-site' );
			if ( siteEditorDispatch?.__experimentalSetPreviewDeviceType ) {
				siteEditorDispatch.__experimentalSetPreviewDeviceType( device );
			}
		}
		this.currentDevice = device;
		this.updateButtonStates();

		// Restore panel classes.
		requestAnimationFrame( () => {
			panelInfo.forEach( ( info ) => {
				const panels = document.querySelectorAll( '.components-panel__body' );
				panels.forEach( ( panel ) => {
					const titleButton = panel.querySelector( '.components-panel__body-title button' );
					if ( titleButton ) {
						const textNode = Array.from( titleButton.childNodes ).find(
							( node ) => node.nodeType === Node.TEXT_NODE && node.textContent.trim()
						);
						if ( textNode && textNode.textContent.trim() === info.title ) {
							panel.classList.add( 'spectra-enhanced-panel' );
						}
					}
				} );
			} );
		} );
	}

	/**
	 * Update visual state of all device buttons.
	 *
	 * Sets the active state and ARIA attributes based on the current device.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */
	updateButtonStates() {
		// Guard: Skip if instance has been destroyed to prevent memory leaks and errors.
		if ( this.isDestroyed ) {
			return;
		}

		// Get fresh device state to handle edge cases.
		const currentDevice = select( 'core/editor' )?.getDeviceType?.() || DESKTOP;
		this.currentDevice = currentDevice;

		const buttons = document.querySelectorAll( '.spectra-responsive-icon' );

		// Handle case where no buttons exist yet.
		if ( buttons.length === 0 ) {
			// Try to inject buttons if we're on a Spectra block.
			const selectedBlock = select( 'core/block-editor' )?.getSelectedBlock?.();
			if ( isAllowedBlock( selectedBlock ) && typeof this.debouncedInject === 'function' ) {
				this.debouncedInject();

				// Try updating buttons again after injection.
				// Store timeout reference for cleanup.
				const timeoutId = setTimeout( () => {
					// Double-check instance is still valid before recursive call.
					if ( ! this.isDestroyed ) {
						this.updateButtonStates();
					}
				}, 100 );

				// Track timeout for potential cleanup.
				this.buttonStateTimeout = timeoutId;
			}
			return;
		}

		requestAnimationFrame( () => {
			buttons.forEach( ( button ) => {
				const isActive = button.dataset.device === this.currentDevice;
				button.classList.toggle( 'is-active', isActive );
				button.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
			} );

			// Update help text for all panels
			this.updateHelpText();
		} );
	}

	/**
	 * Update help text in all responsive panels based on current device.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */

	updateHelpText() {
		const scrollContainer = this.getScrollableContainer();
		if ( ! scrollContainer ) return;

		const helpTextHTML = this.createHelpTextHTML();
		const panels = document.querySelectorAll( '.spectra-enhanced-panel' );

		// Apply the updates
		panels.forEach( ( panel ) => {
			const existingHelp = panel.querySelector( '.spectra-responsive-help-notice' );
			if ( helpTextHTML ) {
				if ( existingHelp ) {
					existingHelp.outerHTML = helpTextHTML;
				} else if ( panel.classList.contains( 'components-tools-panel' ) ) {
					const header = panel.querySelector( '.components-tools-panel-header' );
					if ( header ) {
						header.insertAdjacentHTML( 'afterend', helpTextHTML );
					}
				}
			} else if ( existingHelp ) {
				existingHelp.remove();
			}
		} );
	}

	/**
	 * Get tab state from localStorage.
	 *
	 * @since x.x.x
	 *
	 * @return {Object|null} The stored tab state or null.
	 */
	getTabStateFromStorage() {
		if ( ! window.localStorage ) return null;

		try {
			const storedState = localStorage.getItem( 'spectraV3TabState' );
			return storedState ? JSON.parse( storedState ) : null;
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Save tab state to localStorage.
	 *
	 * @since x.x.x
	 *
	 * @param {string} tabType - The tab type ('settings' or 'styles').
	 * @return {void}
	 */
	saveTabStateToStorage( tabType ) {
		if ( ! window.localStorage ) return;

		const selectedBlock = select( 'core/block-editor' )?.getSelectedBlock?.();
		if ( ! selectedBlock ) return;

		const state = this.tabState || {};
		state[ selectedBlock.name ] = {
			activeTab: tabType,
			timestamp: Date.now(),
		};

		try {
			localStorage.setItem( 'spectraV3TabState', JSON.stringify( state ) );
			this.tabState = state;
		} catch ( e ) {
			// Handle storage errors silently.
		}
	}

	/**
	 * Set up observer for tab changes in the block inspector.
	 *
	 * Monitors clicks on tab buttons to capture the active tab state
	 * so it can be restored after responsive mode changes.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */
	setupTabObserver() {
		this.boundHandleTabClick = ( e ) => {
			const tabButton = e.target.closest( `${ this.selectors.tabs } ${ this.selectors.tabButton }` );
			if ( tabButton ) {
				const label = tabButton.getAttribute( 'aria-label' );
				const tab = label ? this.detectTabType( label ) : 'settings';
				this.saveTabStateToStorage( tab );

				// Clear any existing tab switch timeout to prevent duplicate injections
				if ( this.tabSwitchTimeout ) {
					clearTimeout( this.tabSwitchTimeout );
					this.tabSwitchTimeout = null;
				}

				// Schedule injection after tab content becomes visible (debounced)
				this.tabSwitchTimeout = setTimeout( () => {
					this.tabSwitchTimeout = null;
					const selectedBlock = select( 'core/block-editor' )?.getSelectedBlock?.();
					if ( isAllowedBlock( selectedBlock ) && ! this.isBlockStabilizing ) {
						this.immediateInject();
						this.updateButtonStates();
					}
				}, 50 ); // Short delay to ensure tab content is rendered
			}
		};
		document.addEventListener( 'click', this.boundHandleTabClick, true );
	}

	/**
	 * Restore the previously active tab after DOM re-render.
	 *
	 * Checks if the stored tab state exists and if so, programmatically
	 * clicks the appropriate tab to restore the user's tab selection.
	 *
	 * @since x.x.x
	 *
	 * @return {boolean} True if a tab was restored, false otherwise.
	 */
	restoreTabState() {
		const selectedBlock = select( 'core/block-editor' )?.getSelectedBlock?.();
		if ( ! selectedBlock || ! this.tabState || ! this.tabState[ selectedBlock.name ] ) {
			return false;
		}

		// Skip tab restoration if block is stabilizing to prevent interference
		if ( this.isBlockStabilizing || this.isBlockInStabilizingState( selectedBlock ) ) {
			return false;
		}

		const blockTabState = this.tabState[ selectedBlock.name ];
		const activeTab = blockTabState.activeTab;
		const tabRestored = false;

		// Use requestAnimationFrame for better performance.
		const attemptRestore = ( attempts = 0 ) => {
			if ( attempts > 10 ) return;

			// Double-check stabilization state before attempting restoration
			const currentBlock = select( 'core/block-editor' )?.getSelectedBlock?.();
			if ( this.isBlockStabilizing || this.isBlockInStabilizingState( currentBlock ) ) {
				return;
			}

			const tabs = document.querySelectorAll( `${ this.selectors.tabs } ${ this.selectors.tabButton }` );
			if ( tabs.length === 0 ) {
				requestAnimationFrame( () => attemptRestore( attempts + 1 ) );
				return;
			}

			// Find and click the appropriate tab.
			for ( const tab of tabs ) {
				const ariaLabel = tab.getAttribute( 'aria-label' );
				const isSelected = tab.getAttribute( 'aria-selected' ) === 'true';

				if ( ! isSelected && ariaLabel ) {
					// Use detectTabType to handle translated tab names
					const tabType = this.detectTabType( ariaLabel );
					if ( tabType === activeTab ) {
						// Tab will be restored, scroll restoration will be scheduled
						// The setupTabObserver will handle injection after tab switch
						tab.click();
						break;
					}
				}
			}
		};

		requestAnimationFrame( () => attemptRestore() );
		return tabRestored;
	}

	/**
	 * Set up click handler to maintain panel classes.
	 *
	 * Uses event delegation to ensure panel classes are maintained when panels are toggled.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */
	setupPanelClickHandler() {
		this.boundHandlePanelClick = ( e ) => {
			const toggle = e.target.closest( '.components-panel__body-toggle' );
			if ( ! toggle ) return;
			const panel = toggle.closest( '.components-panel__body' );
			if ( panel && this.panelHasResponsiveControls( panel ) ) {
				panel.classList.add( 'spectra-enhanced-panel' );
				requestAnimationFrame( () => {
					panel.classList.add( 'spectra-enhanced-panel' );
					if ( ! this.hasDeviceButtons( panel ) ) {
						this.addDeviceButtonsToPanelBody( panel );
					}
				} );
			}
		};
		document.addEventListener( 'click', this.boundHandlePanelClick, true );
	}

	/**
	 * Track exact scroll position before device switch.
	 *
	 * Stores the scroll position as a percentage of the container's scrollHeight and
	 * identifies the closest panel for anchoring.
	 *
	 * @since x.x.x
	 * @return {void}
	 */
	trackExactScrollPosition() {
		const container = this.getScrollableContainer();
		if ( ! container ) return;

		this.lastClickedPosition = {
			scrollTop: container.scrollTop,
			scrollPercentage: container.scrollTop / Math.max( 1, container.scrollHeight ),
			containerElement: container,
			device: this.currentDevice,
		};
	}

	/**
	 * Restore exact scroll position.
	 *
	 * Restores scroll position based on device switch:
	 * - For Mobile ↔ Tablet switches, restores scroll only if reset detected.
	 * - For Desktop ↔ Tablet/Mobile, uses percentage-based scrollTop or anchor panel position.
	 *
	 * @since x.x.x
	 * @return {void}
	 */
	restoreScrollPosition() {
		if ( ! this.lastClickedPosition ) return;

		const container = this.lastClickedPosition.containerElement?.isConnected
			? this.lastClickedPosition.containerElement
			: this.getScrollableContainer();

		if ( ! container ) return;

		setTimeout( () => {
			const scrollTop = this.lastClickedPosition.scrollPercentage * Math.max( 1, container.scrollHeight );

			container.scrollTo( {
				top: scrollTop,
				behavior: 'smooth',
			} );
		}, 300 );
	}

	/**
	 * Clean up resources when needed.
	 *
	 * @since x.x.x
	 *
	 * @return {void}
	 */
	destroy() {
		// Mark as destroyed immediately to prevent any pending async ops.
		this.isDestroyed = true;

		// CRITICAL: Unsubscribe from store changes FIRST to prevent new callbacks
		// from firing during the cleanup process. This fixes the race condition
		// where store changes during save/reload could trigger code that accesses
		// nulled methods.
		if ( this.unsubscribe ) {
			this.unsubscribe();
			this.unsubscribe = null;
		}

		// Clear timeouts and animation frames BEFORE disconnecting observers
		// to prevent any pending async callbacks from firing.
		if ( this.injectionTimeout ) {
			clearTimeout( this.injectionTimeout );
			this.injectionTimeout = null;
		}
		if ( this.tabSwitchTimeout ) {
			clearTimeout( this.tabSwitchTimeout );
			this.tabSwitchTimeout = null;
		}
		if ( this.scrollTimeout ) {
			clearTimeout( this.scrollTimeout );
			this.scrollTimeout = null;
		}
		if ( this.buttonStateTimeout ) {
			clearTimeout( this.buttonStateTimeout );
			this.buttonStateTimeout = null;
		}
		if ( this.rafId ) {
			cancelAnimationFrame( this.rafId );
			this.rafId = null;
		}

		// Disconnect MutationObserver.
		if ( this.observer ) {
			this.observer.disconnect();
			this.observer = null;
		}

		// Clear core/image attributes cache to prevent memory leaks.
		this.lastImageAttributes = null;

		// Disconnect IntersectionObserver.
		if ( this.intersectionObserver ) {
			this.intersectionObserver.disconnect();
			this.intersectionObserver = null;
		}

		// Cancel debounced injection - safe to null now since all callbacks are stopped.
		if ( this.debouncedInject && typeof this.debouncedInject.cancel === 'function' ) {
			this.debouncedInject.cancel();
			this.debouncedInject = null;
		}

		// Remove document/window event listeners.
		if ( this.boundHandleVisibilityChange ) {
			document.removeEventListener( 'visibilitychange', this.boundHandleVisibilityChange );
			this.boundHandleVisibilityChange = null;
		}
		if ( this.boundHandleWindowFocus ) {
			window.removeEventListener( 'focus', this.boundHandleWindowFocus );
			this.boundHandleWindowFocus = null;
		}
		if ( this.boundHandleTabClick ) {
			document.removeEventListener( 'click', this.boundHandleTabClick, true );
			this.boundHandleTabClick = null;
		}
		if ( this.boundHandlePanelClick ) {
			document.removeEventListener( 'click', this.boundHandlePanelClick, true );
			this.boundHandlePanelClick = null;
		}

		// Clear DOM-related cache and internal state.
		this.domCache.clear();
		this.lastClickedPosition = null;
		this.cachedScrollContainer = null;
		this.tabState = null;
		this.isBlockStabilizing = false;
		this.tabSwitchTimeout = null;
	}
}

// Initialize with error boundary and cleanup on page unload.
let controlInjectionManager = null;

try {
	controlInjectionManager = new ControlInjectionManager();

	// Essential cleanup on page unload to prevent memory leaks
	const cleanup = () => {
		if ( controlInjectionManager ) {
			controlInjectionManager.destroy();
			controlInjectionManager = null;
		}
	};

	window.addEventListener( 'beforeunload', cleanup, { once: true } );
	window.addEventListener( 'pagehide', cleanup, { once: true } );
} catch ( error ) {
	// Silently handle initialization failure.
	// The editor will continue to work without responsive control buttons.
}