<?php
/**
 * Spectra Admin.
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! class_exists( 'Spectra_Admin' ) ) {

	/**
	 * Class Spectra_Admin.
	 */
	final class Spectra_Admin {

		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 *  Initiator
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			if ( ! is_admin() ) {
				return;
			}

			global $wp_customize;
			/**
			 * Conditionally load the scripts in the customizer.
			 * If the customizer is not set, it means we are not in the customizer.
			 * In that case load the script that will reload the page after migration is complete.
			 */
			if ( empty( $wp_customize ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'reload_on_migration_complete' ) );
			}
			add_action( 'wp_ajax_uag_migrate', array( $this, 'handle_migration_action_ajax' ) );

			add_action( 'admin_notices', array( $this, 'register_notices' ) );
			add_filter( 'wp_kses_allowed_html', array( $this, 'add_data_attributes' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'notice_styles_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'notice_styles_scripts_upgrade_pro' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_page_learn_actions_script' ), 20 );
			add_filter( 'rank_math/researches/toc_plugins', array( $this, 'toc_plugin' ) );
			add_action( 'admin_init', array( $this, 'activation_redirect' ) );
			add_action( 'admin_init', array( $this, 'update_old_user_option_by_url_params' ) );
			add_action( 'admin_init', array( $this, 'update_register_v2_blocks_option_by_url_params' ) );

			// Add fallback cleanup mechanism for failed rollbacks.
			add_action( 'admin_init', array( $this, 'check_stale_rollback_state' ) );

			// Add cleanup hook after plugin upgrade completion.
			add_action( 'upgrader_process_complete', array( $this, 'post_upgrade_cleanup' ), 10, 2 );
			add_action( 'admin_post_uag_rollback', array( $this, 'post_uagb_rollback' ) );
			// Update get access url in Template Kits.
			add_filter( 'ast_block_templates_pro_url', array( $this, 'update_gutenberg_templates_pro_url' ) );
			add_action( 'admin_post_uag_download_log', array( $this, 'handle_log_download' ) );

		}

		/**
		 * Handle migration action AJAX.
		 * 
		 * @since 0.0.1
		 * @return void
		 */
		public function handle_migration_action_ajax() {
			check_ajax_referer( 'spectra-migration', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => 'Permission Denied' ) );
			}

			// Trigger the migration.
			Spectra_Migrate_Blocks::get_instance()->blocks_migration();

			// Update the migration status to 'no' before starting.
			update_option( 'uag_migration_status', 'yes' );

			// Set a new option to know that the migration process has started.
			update_option( 'uag_migration_progress_status', 'in-progress' );

			// Prepare the response.
			$response = array(
				'success' => true,
				'data'    => array(
					'message' => esc_html__( 'Migration started successfully.', 'spectra' ),
				),
			);

			// Send JSON response.
			wp_send_json_success( $response );
		}

		/**
		 * Callback function to display migration log page content.
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public function handle_log_download() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'spectra' ) );
			}

			$log_file = ABSPATH . 'wp-content/uploads/migration-log.txt';

			if ( file_exists( $log_file ) ) {
				header( 'Content-Description: File Transfer' );
				header( 'Content-Type: application/octet-stream' );
				header( 'Content-Disposition: attachment; filename="' . basename( $log_file ) . '"' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate' );
				header( 'Pragma: public' );
				header( 'Content-Length: ' . filesize( $log_file ) );
				flush(); // Flush system output buffer.
				readfile( $log_file );
				exit;
			} else {
				wp_die( esc_html__( 'Log file not found.', 'spectra' ) );
			}
		}
		
		/**
		 * Updates the Gutenberg templates pro URL.
		 * This function returns the URL for the pro version of Gutenberg templates.
		 * 
		 * @since 0.0.1
		 * @return string The URL for Spectra Webpage.
		 */
		public function update_gutenberg_templates_pro_url() { 
			return \Spectra_Admin_Helper::get_spectra_pro_url( '/pricing/', 'gutenberg-templates', 'dashboard', 'Starter-Template-Backend' );
		}

		/**
		 * Update register v2 blocks option using URL Param.
		 *
		 * If any user wants to use old v2 blocks then just add the URL param as true.
		 *
		 * @since 0.0.1
		 * @access public
		 * @return void
		 */
		public function update_register_v2_blocks_option_by_url_params() {

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$spectra_old_user = isset( $_GET['register-v2-blocks'] ) ? sanitize_text_field( $_GET['register-v2-blocks'] ) : false; //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['spectra_old_user'] does not provide nonce.

			if ( 'yes' === $spectra_old_user ) {
				update_option( 'register-v2-blocks', 'yes' );
			} elseif ( 'no' === $spectra_old_user ) {
				delete_option( 'register-v2-blocks' );
			}
		}
 

		/**
		 * Update Old user option using URL Param.
		 *
		 * If any user wants to set the site as old user then just add the URL param as true.
		 *
		 * @since 0.0.1
		 * @access public
		 */
		public function update_old_user_option_by_url_params() {

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$spectra_old_user = isset( $_GET['spectra_old_user'] ) ? sanitize_text_field( $_GET['spectra_old_user'] ) : false; //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['spectra_old_user'] does not provide nonce.

			if ( 'yes' === $spectra_old_user ) {
				update_option( 'uagb-old-user-less-than-2', 'yes' );
			} elseif ( 'no' === $spectra_old_user ) {
				delete_option( 'uagb-old-user-less-than-2' );
			}
		}

		/**
		 * UAG version rollback.
		 *
		 * Rollback to previous UAG version.
		 *
		 * Fired by `admin_post_uag_rollback` action.
		 *
		 * @since 0.0.1
		 * @access public
		 * @return void returns nothing.
		 */
		public function post_uagb_rollback() {

			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_die(
					esc_html__( 'You do not have permission to access this page.', 'spectra' ),
					esc_html__( 'Rollback to Previous Version', 'spectra' ),
					array(
						'response' => 200,
					)
				);
			}

			check_admin_referer( 'uag_rollback' );

			$rollback_versions = Spectra_Admin_Helper::get_instance()->get_rollback_versions();
			$update_version    = isset( $_GET['version'] ) ? sanitize_text_field( $_GET['version'] ) : '';

			if ( empty( $update_version ) || ! in_array( $update_version, $rollback_versions, true ) ) {
				wp_die( esc_html__( 'Error occurred, The version selected is invalid. Try selecting different version.', 'spectra' ) );
			}

			// Set rollback state to prevent beta updates interference.
			$this->prepare_rollback_environment();

			$plugin_slug = basename( SPECTRA_FILE, '.php' );

			$rollback = new Spectra_Rollback(
				array(
					'version'     => $update_version,
					'plugin_name' => SPECTRA_BASE,
					'plugin_slug' => $plugin_slug,
					'package_url' => sprintf( 'https://downloads.wordpress.org/plugin/%s.%s.zip', $plugin_slug, $update_version ),
				)
			);

			$rollback->run();

			wp_die(
				'',
				esc_html__( 'Rollback to Previous Version', 'spectra' ),
				array(
					'response' => 200,
				)
			);
		}

		/**
		 * Prepare rollback environment.
		 *
		 * Clears beta update transients and sets rollback state to prevent
		 * beta updates from interfering with the rollback process.
		 *
		 * @since 0.0.1
		 * @access private
		 * @return void returns nothing.
		 */
		private function prepare_rollback_environment() {
			// Set rollback in progress flag to block beta updates.
			update_option( 'spectra_rollback_in_progress', 'yes' );

			// Store rollback initiation timestamp for safety cleanup.
			update_option( 'spectra_rollback_timestamp', time() );

			// Clear beta update transient to prevent contamination.
			$beta_transient_key = md5( 'spectra_beta_testers_response_key' );
			delete_site_transient( $beta_transient_key );

			// Force clear the update plugins transient to ensure clean state.
			delete_site_transient( 'update_plugins' );
		}

		/**
		 * Check for stale rollback state.
		 *
		 * Checks for stale rollback states on admin_init and cleans them up
		 * to prevent permanent blocking of beta updates.
		 *
		 * @since 0.0.1
		 * @access public
		 * @return void returns nothing.
		 */
		public function check_stale_rollback_state() {
			// Only run this check in admin and if rollback is marked as in progress.
			if ( ! is_admin() || 'yes' !== get_option( 'spectra_rollback_in_progress', 'no' ) ) {
				return;
			}

			$rollback_timestamp = get_option( 'spectra_rollback_timestamp', 0 );

			// If no timestamp exists, or if rollback has been running for more than 1 hour, clean it up.
			if ( 0 === $rollback_timestamp || time() - $rollback_timestamp > HOUR_IN_SECONDS ) {
				// Spectra Admin: Cleaning up stale rollback state detected on admin_init.
				
				// Clear all rollback-related options and transients.
				delete_option( 'spectra_rollback_in_progress' );
				delete_option( 'spectra_rollback_timestamp' );

				// Clean up forced rollback flag from transient.
				$update_plugins = get_site_transient( 'update_plugins' );
				if ( is_object( $update_plugins ) && isset( $update_plugins->spectra_forced_rollback ) ) {
					unset( $update_plugins->spectra_forced_rollback );
					set_site_transient( 'update_plugins', $update_plugins );
				}

				// Spectra Admin: Stale rollback state cleanup completed, beta updates re-enabled.
			}
		}

		/**
		 * Post upgrade cleanup.
		 *
		 * Cleans up rollback state after successful plugin upgrade completion.
		 * This ensures that rollback flags are cleared after successful rollbacks.
		 *
		 * @since 0.0.1
		 * @access public
		 *
		 * @param WP_Upgrader $upgrader_object The upgrader instance.
		 * @param array       $options         Array of bulk item update data.
		 * @return void returns nothing.
		 */
		public function post_upgrade_cleanup( $upgrader_object, $options ) {
			// Only handle plugin upgrades.
			if ( ! isset( $options['type'] ) || 'plugin' !== $options['type'] ) {
				return;
			}

			// Only handle if this plugin was upgraded.
			if ( ! isset( $options['plugins'] ) || ! is_array( $options['plugins'] ) ) {
				return;
			}

			// Check if our plugin was in the upgrade.
			if ( ! in_array( SPECTRA_BASE, $options['plugins'], true ) ) {
				return;
			}

			// If rollback was in progress, clean it up after successful upgrade.
			if ( 'yes' === get_option( 'spectra_rollback_in_progress', 'no' ) ) {
				// Spectra Admin: Plugin upgrade detected, cleaning up rollback state.
				
				// Clear all rollback-related options and transients.
				delete_option( 'spectra_rollback_in_progress' );
				delete_option( 'spectra_rollback_timestamp' );

				// Clean up forced rollback flag from transient.
				$update_plugins = get_site_transient( 'update_plugins' );
				if ( is_object( $update_plugins ) && isset( $update_plugins->spectra_forced_rollback ) ) {
					unset( $update_plugins->spectra_forced_rollback );
					set_site_transient( 'update_plugins', $update_plugins );
				}

				// Spectra Admin: Post-upgrade rollback state cleanup completed.
			}
		}

		/**
		 * Activation Reset
		 */
		public function activation_redirect() {

			$do_redirect = apply_filters( 'spectra_enable_redirect_activation', get_option( '__uagb_do_redirect' ) );

			if ( $do_redirect ) {

				update_option( '__uagb_do_redirect', false );

				if ( ! is_multisite() ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'page' => SPECTRA_SLUG,
								'spectra-activation-redirect' => true,
							),
							admin_url( 'admin.php' )
						)
					);
					exit();
				}
			}
		}

		/**
		 * Filters and Returns a list of allowed tags and attributes for a given context.
		 *
		 * @param Array  $allowedposttags Array of allowed tags.
		 * @param String $context Context type (explicit).
		 * @since 0.0.1
		 * @return Array
		 */
		public function add_data_attributes( $allowedposttags, $context ) {
			$allowedposttags['a']['data-repeat-notice-after'] = true;

			return $allowedposttags;
		}

		/**
		 * Ask Plugin Rating
		 *
		 * @since 0.0.1
		 */
		public function register_notices() {
			// Check if assets should be excluded for the current post type.
			if ( Spectra_Admin_Helper::should_exclude_assets_for_cpt() ) {
				return; // Early return to prevent loading assets.
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$image_path = SPECTRA_URL . 'admin/assets/images/uag-logo.svg';

			if ( ! get_option( 'uag_migration_status', false ) && 'yes' === get_option( 'uagb-old-user-less-than-2' ) && 'in-progress' !== get_option( 'uag_migration_progress_status', '' ) ) {

				Astra_Notices::add_notice(
					array(

						'id'                         => 'uagb-block-migration_status',
						'type'                       => '',
						'message'                    => sprintf(
							// Translators: %1$s: Spectra logo, %2$s: migration note , %3$s: The closing tag, %4$s: migration description, %5$s: migration button placeholder, %6$s: Learn more button, %7$s: learn more placeholder.
							'<div class="notice-image">
                            <img src="%1$s" class="custom-logo" alt="Spectra" itemprop="logo"></div>
                            <div class="notice-content">
                            <h4 style="margin: 0.5em 0" class="notice-heading">
                            %2$s
                            </h4>
						    %3$s<br /><br />
						     <strong>%4$s</strong>
                                <div style="margin-bottom: 0.5em" class="astra-review-notice-container">
                                    <a style="margin-right: 0.5em" id="trigger_migration" class="uagb-review-notice button-primary">
                                    %5$s
                                    </a>
									<a href="%6$s" class="uagb-review-notice button-primary">
                                    %7$s
                                    </a>
                                </div>
                                </div><br />',
							$image_path,
							__( 'Spectra database update required', 'spectra' ),
							__( "We've detected that some of your pages were created with an older version of Spectra. To ensure your designs remain unaffected, we recommend updating the Spectra database now. Updating the Spectra database will not impact any other parts of your website.", 'spectra' ),
							__( 'To be on the safer side, please be sure to back up your site before updating.', 'spectra' ),
							__( 'Update Spectra Database', 'spectra' ),
							esc_url( 'https://wpspectra.com/docs/spectra-database-update-instructions/' ),
							__( 'Learn More About This', 'spectra' )
						),
						'priority'                   => 20,
						'display-with-other-notices' => true,
					)
				);
			} elseif ( 'yes' !== get_option( 'uag_migration_complete', 0 ) && 'yes' === get_option( 'uagb-old-user-less-than-2' ) ) {
				Astra_Notices::add_notice(
					array(
						'id'                         => 'uag_migration_in_progress',
						'type'                       => 'info',
						'message'                    => sprintf(
							// Translators: %1$s: Spectra logo, %2$s: in-progress note.
							'<div class="notice-image">
                                <img src="%1$s" class="custom-logo" alt="Spectra" itemprop="logo"></div>
                                <div class="notice-content">
                                    <h4 style="margin: 0.5em 0" class="notice-heading">
                                        %2$s
                                    </h4>
                                    <div style="margin-bottom: 0.5em" class="astra-review-notice-container">
                                        <span class="spinner is-active"></span>
                                        %3$s
                                    </div>
                                </div><br />',
							$image_path,
							__( 'Spectra database update in progress', 'spectra' ),
							__( 'Great! This should only take a few minutes. Thanks for hanging in there.', 'spectra' )
						),
						'dismissible'                => false,
						'priority'                   => 20,
						'display-with-other-notices' => true,
					)
				);
			} elseif ( 'yes' === get_option( 'uag_migration_complete', 0 ) ) {
				Astra_Notices::add_notice(
					array(
						'id'                         => 'uag_migration_success',
						'type'                       => 'success',
						'message'                    => sprintf(
							// Translators: %1$s: Spectra logo, %2$s: success message, %3$s: additional note.
							'<div class="notice-image">
							<img src="%1$s" class="custom-logo" alt="Spectra" itemprop="logo"></div>
							<div class="notice-content">
								<h4 style="margin: 0.5em 0" class="notice-heading">
									%2$s
								</h4>
								<div style="margin-bottom: 0.5em" class="astra-review-notice-container">
									%3$s
								</div>
							</div><br />',
							$image_path,
							__( 'Update Successful!', 'spectra' ),
							__( 'Your Spectra database is now up-to-date. Your website will continue to function as before.', 'spectra' ) . ' <a href="' . esc_url( admin_url( 'admin-post.php?action=uag_download_log' ) ) . '">' . __( 'View Log', 'spectra' ) . '</a>'
						),
						'dismissible'                => true,
						'priority'                   => 20,
						'display-with-other-notices' => true,
					)
				);
			}
			

			if ( class_exists( 'Classic_Editor' ) ) {
				$editor_option = get_option( 'classic-editor-replace' );
				if ( 'block' !== $editor_option ) {
					Astra_Notices::add_notice(
						array(
							'id'                         => 'uagb-classic-editor',
							'type'                       => 'warning',
							'message'                    => sprintf(
								/* translators: %s: html tags */
								__( 'Spectra requires&nbsp;%3$sBlock Editor%4$s. You can change your editor settings to Block Editor from&nbsp;%1$shere%2$s. Plugin is currently NOT RUNNING.', 'spectra' ),
								'<a href="' . admin_url( 'options-writing.php' ) . '">',
								'</a>',
								'<strong>',
								'</strong>'
							),
							'priority'                   => 20,
							'display-with-other-notices' => true,
						)
					);
				}
			}
			$image_path = SPECTRA_URL . 'admin/assets/images/uag-logo.svg';

			$installed_plugins = get_plugins();

			$status = isset( $installed_plugins['spectra-pro/spectra-pro.php'] ) 
					? ( is_plugin_active( 'spectra-pro/spectra-pro.php' ) 
						? 'active' 
						: 'inactive' ) 
					: 'not-installed';

			if ( 'not-installed' === $status && isset( $_GET['post_type'] ) && 'spectra-popup' === $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['post_type'] does not provide nonce.
				Astra_Notices::add_notice(
					array(
						'id'                         => 'uagb-spectra-pro-popup-note',
						'type'                       => '',
						'message'                    => sprintf(
							'<div class="notice-image">
								<img src="%1$s" class="custom-logo" style="max-width: 40px;" alt="Spectra" itemprop="logo"></div>
								<div class="notice-content">
									<div class="notice-heading">
										<strong>
											%2$s
										</strong>
									</div>
									%3$s<br />
									<div class="astra-review-notice-container">
										<a href="%4$s" class="not-astra-notice-close uagb-review-notice button-primary" target="_blank">
										%5$s
										</a>
									
									</div>
								</div>',
							$image_path,
							__( 'Want to do more with Popup Builder?', 'spectra' ),
							__( 'Maximize your popup potential with Spectra Pro. Unlock enhanced features, intuitive design options, and increased conversions!', 'spectra' ),
							esc_url( \Spectra_Admin_Helper::get_spectra_pro_url( '/pricing/', 'free-plugin', 'popup-builder', 'popup-builder-banner' ) ),
							__( 'Upgrade Now', 'spectra' )
						),
						'dismissible'                => true,
						'priority'                   => 20,
						'display-with-other-notices' => true,
						'class'                      => 'spectra-upsell',
					)
				);
			}
		}

		/**
		 * Enqueue the needed CSS/JS for the builder's admin settings page.
		 *
		 * @since 0.0.1
		 */
		public function notice_styles_scripts() {
			$screen = get_current_screen();
	
			if ( $screen && 'admin_page_migration-log' === $screen->base ) {
				wp_enqueue_style( 'uag-admin-css', SPECTRA_URL . 'admin/assets/legacy/admin-notice.css', array(), SPECTRA_VER );
		
				// Add inline CSS to hide elements with the 'notice' class.
				$custom_css = '.notice { display: none !important; }';
				wp_add_inline_style( 'uag-admin-css', $custom_css );
			}
		}

		/**
		 * Enqueue the needed CSS/JS for the plugin / popup page.
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public function notice_styles_scripts_upgrade_pro() {
			$screen = get_current_screen();

			if ( $screen && ( 'plugins' === $screen->base || 'spectra-popup' === $screen->post_type ) ) {
				wp_enqueue_style( 'uag-admin-spectra-pro-upgrade-pro-css', SPECTRA_URL . 'admin/assets/legacy/admin-notice-spectra-pro-upgrade-pro.css', array(), SPECTRA_VER );
			}
			// Redirect to Pro pricing page when click on Get Spectra Pro button.
			if ( $screen && 'toplevel_page_spectra' === $screen->base ) {
				?>
					<script type="text/javascript">
						document.addEventListener('DOMContentLoaded', function() {
							let upgradeLink = document.querySelector('a[href$="&path=upgrade-now"]');
							if (upgradeLink) {
								upgradeLink.setAttribute('target', '_blank');
								upgradeLink.setAttribute('rel', 'noreferrer');
								upgradeLink.addEventListener('click', function(e) {
									e.preventDefault();
									window.open( '<?php echo esc_url( \Spectra_Admin_Helper::get_spectra_pro_url( '/pricing/', 'free-plugin', 'dashboard', 'setting' ) ); ?>', '_blank', 'noopener,noreferrer' );
								});
							}
						});
					</script>
				<?php
			}
		}

		/**
		 * Enqueue JavaScript for learn actions on Spectra admin pages.
		 *
		 * Shows tooltips on Global Styles admin pages when navigated from the Learn tab.
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public function enqueue_admin_page_learn_actions_script() {
			// Only run on admin.php?page=spectra-blocks when 'learn' param is present.
			if ( ! isset( $_GET['page'], $_GET['learn'] ) || 'spectra-blocks' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			// Check if Spectra Pro is active.
			if ( ! is_plugin_active( 'spectra-pro/spectra-pro.php' ) ) {
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
			 console.log('action');
				'use strict';

				// Global guard to prevent double-execution.
				if ( window.uagbAdminLearnActionExecuted ) {
					return;
				}
				window.uagbAdminLearnActionExecuted = true;

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
					tooltip.className = 'uagb-learn-tooltip';

					var tooltipContent = document.createElement( 'div' );
					tooltipContent.className = 'uagb-learn-tooltip-content';
					tooltipContent.textContent = text;

					var closeButton = document.createElement( 'button' );
					closeButton.className = 'uagb-learn-tooltip-close';
					closeButton.innerHTML = '&times;';
					closeButton.setAttribute( 'aria-label', '" . esc_attr( __( 'Close tooltip', 'spectra' ) ) . "' );

					var arrow = document.createElement( 'div' );
					arrow.className = 'uagb-learn-tooltip-arrow';

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
					if ( ! document.getElementById( 'learn-actions-admin-css' ) ) {
						var style = document.createElement( 'style' );
						style.id = 'learn-actions-admin-css';
						style.textContent = '.uagb-learn-tooltip { position: relative; } .uagb-learn-tooltip-content { margin-right: 20px; } .uagb-learn-tooltip-close { position: absolute; top: 5px; right: 8px; background: none; border: none; color: #fff; font-size: 18px; line-height: 1; cursor: pointer; padding: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background-color 0.2s; } .uagb-learn-tooltip-close:hover { background-color: rgba(255, 255, 255, 0.1); } .uagb-learn-tooltip-close:focus { outline: 1px solid #fff; outline-offset: 1px; } .uagb-learn-tooltip-arrow { position: absolute; width: 0; height: 0; } .uagb-learn-tooltip.position-top .uagb-learn-tooltip-arrow { bottom: -8px; left: 50%; transform: translateX(-50%); border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid #333; } .uagb-learn-tooltip.position-right { transform: translateY(-50%); } .uagb-learn-tooltip.position-right .uagb-learn-tooltip-arrow { left: -8px; top: 50%; transform: translateY(-50%); border-top: 8px solid transparent; border-bottom: 8px solid transparent; border-right: 8px solid #333; } .uagb-learn-tooltip.position-left { transform: translateY(-50%); } .uagb-learn-tooltip.position-left .uagb-learn-tooltip-arrow { right: -8px; top: 50%; transform: translateY(-50%); border-top: 8px solid transparent; border-bottom: 8px solid transparent; border-left: 8px solid #333; }';
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
						selector: '#toplevel_page_spectra .wp-submenu a[href*=\"global-styles\"]',
						text: '" . esc_js( __( 'Click here to open Global Styles and customize your site-wide styles.', 'spectra' ) ) . "',
						position: 'right'
					},
					'set-global-colors-fonts-spacing': {
						selector: '.system-variables',
						text: '" . esc_js( __( 'Define your brand colors here. They will be available as Global Styles classes across your site.', 'spectra' ) ) . "',
						position: 'right'
					},
					'use-block-defaults': {
						selector: '#block-defaults',
						text: '" . esc_js( __( 'Set default styles for each block type. New instances will automatically inherit these.', 'spectra' ) ) . "',
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
			wp_add_inline_script( 'uag-admin-settings', $inline_script );
		}

		/**
		 * Enqueue script to reload the page on migration complete.
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public function reload_on_migration_complete() {
			?>
			<script type="text/javascript">
				document.addEventListener('DOMContentLoaded', function() {
					var triggerButton = document.getElementById('trigger_migration');

					if (triggerButton) {
						triggerButton.addEventListener('click', function(e) {
							e.preventDefault();

							fetch('<?php echo esc_html( admin_url( 'admin-ajax.php' ) ); ?>', {
								method: 'POST',
								headers: {
									'Content-Type': 'application/x-www-form-urlencoded',
								},
								body: 'action=uag_migrate&security=' + encodeURIComponent('<?php echo esc_html( wp_create_nonce( 'spectra-migration' ) ); ?>'),
							})
							.then(function(response) {
								return response.json();
							})
							.then(function(data) {
								if (data.success) {
									location.reload();
									// Optionally, reload the page or perform additional actions.
								} else {
									return;
								}
							})
							.catch(function(error) {
								console.error('Error occurred during migration:', error);
							});
						});
					}
				});
			</script>
			<?php
		}


		/**
		 * Rank Math SEO filter to add kb-elementor to the TOC list.
		 *
		 * @param array $plugins TOC plugins.
		 */
		public function toc_plugin( $plugins ) {
			$plugins['spectra-blocks/spectra-blocks.php'] = 'Spectra Blocks';
			return $plugins;
		}
	}

	Spectra_Admin::get_instance();
}
