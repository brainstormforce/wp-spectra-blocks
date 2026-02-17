<?php
/**
 * Update Compatibility
 *
 * @package Spectra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spectra_Update' ) ) :

	/**
	 * Spectra Update initial setup
	 *
	 * @since 0.0.1
	 */
	class Spectra_Update {

		/**
		 * Class instance.
		 *
		 * @access private
		 * @var $instance Class instance.
		 */
		private static $instance;

		/**
		 * Initiator
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 *  Constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'init' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'in_plugin_update_message-' . SPECTRA_BASE, array( $this, 'plugin_update_notification' ), 10 );
		}

		/**
		 * Init
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public function init() {
			// Get auto saved version number.
			$saved_version = get_option( 'uagb-version', false );

			// Update auto saved version number.
			if ( ! $saved_version || ! is_string( $saved_version ) ) {

				// Fresh install updation.
				$this->fresh_install_update_asset_generation_option();

				// Update current version.
				update_option( 'uagb-version', SPECTRA_VER );
				return;
			}

			do_action( 'spectra_update_before' );

			// If equals then return.
			if ( version_compare( $saved_version, SPECTRA_VER, '=' ) ) {
				return;
			}

			// If user is older than 3.0.0 then set the option.
			if ( version_compare( $saved_version, '3.0.0', '<' ) ) {
				update_option( 'uagb-old-user-less-than-3', 'yes' );
			}

			// If user is older than equal to 2.12.1 then set the option.
			if ( version_compare( $saved_version, '2.12.1', '<=' ) ) {
				Spectra_Admin_Helper::update_admin_settings_option( 'uag_enable_quick_action_sidebar', 'disabled' );
			}

			// Delete any of the unused options that have been unsupported or no longer required.

			// Delete the header titlebar option if it exists- which has been removed from version 2.14.1.
			if ( Spectra_Admin_Helper::get_admin_settings_option( 'uag_enable_header_titlebar' ) ) {
				Spectra_Admin_Helper::delete_admin_settings_option( 'uag_enable_header_titlebar' );
			}

			// Create a Core Block Array for all versions in which a Core Spectra Block was added.
			$core_blocks   = array();
			$blocks_status = Spectra_Admin_Helper::get_admin_settings_option( '_uagb_blocks' );

			// If Block Statuses exists and is not empty, enable the required Core Spectra Blocks.
			if ( is_array( $blocks_status ) && ! empty( $blocks_status ) ) {

				// If user is older than 2.0.16 then enable all the Core Spectra Blocks, as we have removed option to disable core blocks from 2.0.16.
				if ( version_compare( $saved_version, '2.0.16', '<' ) ) {
					array_push(
						$core_blocks,
						'container',
						'advanced-heading',
						'image',
						'buttons',
						'info-box',
						'call-to-action'
					);
				}

				// If user is older than 2.4.0 then enable the Icon Block that was added to the Core Blocks in this release.
				if ( version_compare( $saved_version, '2.4.0', '<' ) ) {
					array_push(
						$core_blocks,
						'icon'
					);
				}

				// If user is older than 2.6.0 then enable the Countdown Block that was added to the Core Blocks in this release.
				if ( version_compare( $saved_version, '2.6.0', '<' ) ) {
					array_push(
						$core_blocks,
						'countdown'
					);
				}

				// If user is older than 2.12.3 then enable the popup-builder Block that was added to the Core Blocks in this release.
				if ( version_compare( $saved_version, '2.12.3', '<' ) ) {
					array_push(
						$core_blocks,
						'popup-builder'
					);
				}
			}

			$inherit_from_theme = Spectra_Admin_Helper::get_admin_settings_option( 'uag_btn_inherit_from_theme' );
			// If user is older than 2.13.4 and Inherit from theme is enabled update the fallback.
			if ( version_compare( $saved_version, '2.13.4', '<' ) && 'enabled' === $inherit_from_theme ) {
				Spectra_Admin_Helper::update_admin_settings_option( 'uag_btn_inherit_from_theme_fallback', 'disabled' );
			}

			// If the core block array is not empty, update the enabled blocks option.
			if ( ! empty( $core_blocks ) ) {

				foreach ( $core_blocks as $block ) {
					$blocks_status[ $block ] = $block;
				}

				Spectra_Admin_Helper::update_admin_settings_option( '_uagb_blocks', $blocks_status );
			}

			// Create file if not present.
			spectra_install()->create_files();

			/* Create activated blocks stylesheet */
			Spectra_Admin_Helper::create_specific_stylesheet();

			// Update asset version number.
			update_option( '__uagb_asset_version', time() );

			// Update auto saved version number.
			update_option( 'uagb-version', SPECTRA_VER );

			do_action( 'spectra_update_after' );
		}


		/**
		 * Migrate_visibility_mode
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public static function migrate_visibility_mode() {

			$old_option      = Spectra_Admin_Helper::get_admin_settings_option( 'uag_enable_coming_soon_mode' );
			$old_option_page = Spectra_Admin_Helper::get_admin_settings_option( 'uag_coming_soon_page' );

			if ( ! $old_option && ! $old_option_page ) {
				return;
			}

			// Update the option.
			Spectra_Admin_Helper::update_admin_settings_option( 'uag_visibility_mode', $old_option ? $old_option : 'disabled' );
			Spectra_Admin_Helper::update_admin_settings_option( 'uag_visibility_page', $old_option_page ? $old_option_page : '' );

			// Delete the old option.
			Spectra_Admin_Helper::delete_admin_settings_option( 'uag_enable_coming_soon_mode' );
			Spectra_Admin_Helper::delete_admin_settings_option( 'uag_coming_soon_page' );
		}

		/**
		 * Update asset generation option if it is not exist.
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public function fresh_install_update_asset_generation_option() {

			spectra_install()->create_files();

			if ( Spectra_Helper::is_uag_dir_has_write_permissions() ) {
				update_option( '_uagb_allow_file_generation', 'enabled' );
			}
		}

		/**
		 * Plugin update notification.
		 *
		 * @param array $data Plugin update data.
		 * @since 0.0.1
		 * @return void
		 */
		public function plugin_update_notification( $data ) {
			if ( ! empty( $data['upgrade_notice'] ) ) { ?>
				<hr class="uagb-plugin-update-notification__separator" />
				<div class="uagb-plugin-update-notification">
					<div class="uagb-plugin-update-notification__icon">
						<span class="dashicons dashicons-info"></span>
					</div>
					<div>
						<div class="uagb-plugin-update-notification__title">
							<?php echo esc_html__( 'Heads up!', 'spectra' ); ?>
						</div>
						<div class="uagb-plugin-update-notification__message">
							<?php
								printf(
									wp_kses(
										$data['upgrade_notice'],
										array( 'a' => array( 'href' => array() ) )
									)
								);
							?>
						</div>
					</div>
				</div>
				<?php
			} //end if
		}

		/**
		 * Enqueue styles.
		 *
		 * @since 0.0.1
		 * @return void
		 */
		public function enqueue_styles() {
			// Check if assets should be excluded for the current post type.
			if ( Spectra_Admin_Helper::should_exclude_assets_for_cpt() ) {
				return; // Early return to prevent loading assets.
			}

			$screen = get_current_screen();
			if ( empty( $screen->id ) || 'plugins' !== $screen->id ) {
				return;
			}
			wp_enqueue_style( 'uagb-update-notice', SPECTRA_URL . 'admin/assets/legacy/css/update-notice.css', array(), SPECTRA_VER );
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Spectra_Update::get_instance();

endif;
