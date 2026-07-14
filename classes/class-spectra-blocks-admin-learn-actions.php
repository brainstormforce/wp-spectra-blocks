<?php
/**
 * Spectra Blocks Admin Learn Actions
 * Handles tooltip guidance on admin dashboard pages for learn functionality steps
 *
 * @package SpectraBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spectra_Blocks_Admin_Learn_Actions' ) ) {

	/**
	 * Class Spectra_Blocks_Admin_Learn_Actions
	 *
	 * @since 1.0.0
	 */
	class Spectra_Blocks_Admin_Learn_Actions {

		/**
		 * Initialize the class.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function init() {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_learn_actions_script' ), 20 );
		}

		/**
		 * Enqueue JavaScript for learn actions on Spectra Blocks admin pages.
		 *
		 * Shows tooltips on Global Styles admin pages when navigated from the Learn tab.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function enqueue_admin_learn_actions_script() {
			// Only run on admin.php?page=spectra-blocks when 'learn' param is present.
			if ( ! isset( $_GET['page'], $_GET['learn'] ) || 'spectra-blocks' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			// Check if Spectra Blocks Pro is active.
			if ( ! is_plugin_active( 'spectra-blocks-pro/spectra-blocks-pro.php' ) ) {
				return;
			}

			// Check user capability.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Validate against allowlist.
			$learn_action  = sanitize_text_field( wp_unslash( $_GET['learn'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$valid_actions = array( 'open-global-styles', 'set-global-colors-fonts-spacing', 'use-block-defaults' );
			if ( ! in_array( $learn_action, $valid_actions, true ) ) {
				return;
			}

			$inline_script = "
			( function() {
				'use strict';

				// Global guard to prevent double-execution.
				if ( window.spectraBlocksAdminLearnActionExecuted ) {
					return;
				}
				window.spectraBlocksAdminLearnActionExecuted = true;

				/**
				 * Read the 'learn' URL parameter.
				 */
				function getLearnParam() {
					var params = new URLSearchParams( window.location.search );
					return params.get( 'learn' ) || '';
				}

				/**
				 * Remove the 'learn' param from the browser URL without reload.
				 */
				function cleanLearnParam() {
					try {
						var url = new URL( window.location.href );
						url.searchParams.delete( 'learn' );
						window.history.replaceState( {}, document.title, url.toString() );
					} catch ( e ) {
						// Silently fail.
					}
				}

				/**
				 * Wait for a DOM element to appear.
				 */
				function waitForElement( selector, callback, timeout ) {
					timeout = timeout || 5000;
					var startTime = Date.now();

					function check() {
						var el = document.querySelector( selector );
						if ( el ) {
							callback( el );
						} else if ( Date.now() - startTime < timeout ) {
							setTimeout( check, 200 );
						} else {
							// Timeout — clean up param silently.
							cleanLearnParam();
						}
					}

					check();
				}

				/**
				 * Show a tooltip near the target element.
				 */
				function showTooltip( element, text, preferredPosition ) {
					preferredPosition = preferredPosition || 'top';

					var rect = element.getBoundingClientRect();

					// Create tooltip elements.
					var tooltip = document.createElement( 'div' );
					tooltip.className = 'spectra-blocks-learn-tooltip';

					var tooltipContent = document.createElement( 'div' );
					tooltipContent.className = 'spectra-blocks-learn-tooltip-content';
					tooltipContent.textContent = text;

					var closeButton = document.createElement( 'button' );
					closeButton.className = 'spectra-blocks-learn-tooltip-close';
					closeButton.innerHTML = '&times;';
					closeButton.setAttribute( 'aria-label', '" . esc_attr( __( 'Close tooltip', 'spectra-blocks' ) ) . "' );

					var arrow = document.createElement( 'div' );
					arrow.className = 'spectra-blocks-learn-tooltip-arrow';

					tooltip.appendChild( tooltipContent );
					tooltip.appendChild( closeButton );
					tooltip.appendChild( arrow );

					// Determine position.
					var tooltipTop, tooltipLeft, position = preferredPosition;

					if ( preferredPosition === 'right' && rect.right + 220 <= window.innerWidth ) {
						tooltipTop = rect.top + ( rect.height / 2 );
						tooltipLeft = rect.right + 15;
						position = 'right';
					} else if ( preferredPosition === 'top' && rect.top >= 70 ) {
						tooltipTop = rect.top - 60;
						tooltipLeft = rect.left + ( rect.width / 2 ) - 100;
						position = 'top';
					} else if ( rect.top >= 70 ) {
						tooltipTop = rect.top - 60;
						tooltipLeft = rect.left + ( rect.width / 2 ) - 100;
						position = 'top';
					} else if ( rect.right + 220 <= window.innerWidth ) {
						tooltipTop = rect.top + ( rect.height / 2 );
						tooltipLeft = rect.right + 15;
						position = 'right';
					} else {
						tooltipTop = rect.top - 60;
						tooltipLeft = rect.left + ( rect.width / 2 ) - 100;
						position = 'top';
					}

					// Boundary adjustments.
					if ( position === 'top' ) {
						if ( tooltipLeft < 10 ) tooltipLeft = 10;
						if ( tooltipLeft + 200 > window.innerWidth ) tooltipLeft = window.innerWidth - 210;
					}

					tooltip.style.cssText = 'position: fixed; top: ' + tooltipTop + 'px; left: ' + tooltipLeft + 'px; width: 200px; padding: 10px 15px 10px 10px; background: #333; color: #fff; border-radius: 6px; font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; z-index: 1000000; box-shadow: 0 4px 12px #333; opacity: 0; transform: translateY(10px); transition: all 0.3s ease; pointer-events: auto;';

					// Add tooltip CSS if not already present.
					if ( ! document.getElementById( 'spectra-blocks-learn-actions-admin-css' ) ) {
						var style = document.createElement( 'style' );
						style.id = 'spectra-blocks-learn-actions-admin-css';
						style.textContent = '.spectra-blocks-learn-tooltip { position: relative; } .spectra-blocks-learn-tooltip-content { margin-right: 20px; } .spectra-blocks-learn-tooltip-close { position: absolute; top: 5px; right: 8px; background: none; border: none; color: #fff; font-size: 18px; line-height: 1; cursor: pointer; padding: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background-color 0.2s; } .spectra-blocks-learn-tooltip-close:hover { background-color: rgba(255, 255, 255, 0.1); } .spectra-blocks-learn-tooltip-close:focus { outline: 1px solid #fff; outline-offset: 1px; } .spectra-blocks-learn-tooltip-arrow { position: absolute; width: 0; height: 0; } .spectra-blocks-learn-tooltip.position-top .spectra-blocks-learn-tooltip-arrow { bottom: -8px; left: 50%; transform: translateX(-50%); border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid #333; } .spectra-blocks-learn-tooltip.position-right { transform: translateY(-50%); } .spectra-blocks-learn-tooltip.position-right .spectra-blocks-learn-tooltip-arrow { left: -8px; top: 50%; transform: translateY(-50%); border-top: 8px solid transparent; border-bottom: 8px solid transparent; border-right: 8px solid #333; } .spectra-blocks-learn-tooltip.position-left { transform: translateY(-50%); } .spectra-blocks-learn-tooltip.position-left .spectra-blocks-learn-tooltip-arrow { right: -8px; top: 50%; transform: translateY(-50%); border-top: 8px solid transparent; border-bottom: 8px solid transparent; border-left: 8px solid #333; }';
						document.head.appendChild( style );
					}

					document.body.appendChild( tooltip );
					tooltip.classList.add( 'position-' + position );

					// Remove tooltip helper.
					function removeTooltip() {
						if ( tooltip && tooltip.parentNode ) {
							tooltip.style.opacity = '0';
							var exitTransform = position === 'right' ? 'translateY(-50%) translateX(-10px)' : position === 'left' ? 'translateY(-50%) translateX(10px)' : 'translateY(-10px)';
							tooltip.style.transform = exitTransform;
							setTimeout( function() {
								if ( tooltip.parentNode ) {
									tooltip.parentNode.removeChild( tooltip );
								}
							}, 300 );
						}
					}

					// Close on button click.
					closeButton.addEventListener( 'click', removeTooltip );

					// Close on body click (after short delay to prevent immediate closure).
					setTimeout( function() {
						document.addEventListener( 'click', function handleBodyClick() {
							removeTooltip();
							document.removeEventListener( 'click', handleBodyClick );
						} );
					}, 100 );

					// Set initial transform based on position (include vertical centering for right/left).
					var initialTransform = position === 'right' ? 'translateY(-50%) translateX(-10px)' : position === 'left' ? 'translateY(-50%) translateX(10px)' : 'translateY(10px)';
					tooltip.style.transform = initialTransform;

					// Animate in.
					setTimeout( function() {
						tooltip.style.opacity = '1';
						tooltip.style.transform = position === 'right' || position === 'left' ? 'translateY(-50%) translateX(0)' : 'translateY(0)';
					}, 100 );
				}

				/**
				 * Action configuration map.
				 */
				var actionConfig = {
					'open-global-styles': {
						selector: '#toplevel_page_spectra-blocks .wp-submenu a[href*=\"global-styles\"]',
						text: '" . esc_js( __( 'Click here to open Global Styles and customize your site-wide styles.', 'spectra-blocks' ) ) . "',
						position: 'right'
					},
					'set-global-colors-fonts-spacing': {
						selector: '.system-variables',
						text: '" . esc_js( __( 'Define your brand colors here. They will be available as Global Styles classes across your site.', 'spectra-blocks' ) ) . "',
						position: 'right'
					},
					'use-block-defaults': {
						selector: '#block-defaults',
						text: '" . esc_js( __( 'Set default styles for each block type. New instances will automatically inherit these.', 'spectra-blocks' ) ) . "',
						position: 'right'
					}
				};

				/**
				 * Main initialization.
				 */
				function init() {
					var action = getLearnParam();

					if ( ! action || ! actionConfig[ action ] ) {
						return;
					}

					var config = actionConfig[ action ];

					// Wait for the React SPA to mount (2s delay), then poll for the target element.
					setTimeout( function() {
						waitForElement( config.selector, function( element ) {
							setTimeout( function() {
								showTooltip( element, config.text, config.position );
								cleanLearnParam();
							}, 300 );
						} );
					}, 2000 );
				}

				// Run on script load.
				init();

			} )();
			";

			wp_add_inline_script( 'spectra-blocks-admin-settings', $inline_script );
		}
	}
}

Spectra_Blocks_Admin_Learn_Actions::init();
