<?php
/**
 * Spectra Loader.
 *
 * @package SpectraBlocks
 */

use SpectraBlocks\Admin_Helper;
use \SpectraZipAI\Classes\Module as Zip_Ai_Module;
use SpectraAdmin\Inc\Admin_Learn;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Spectra_Blocks_Loader' ) ) {

	/**
	 * Class Spectra_Blocks_Loader.
	 */
	final class Spectra_Blocks_Loader {

		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;

		/**
		 * Post assets object cache
		 *
		 * @var array
		 */
		public $post_assets_objs = array();

		/**
		 * Block analytics instance
		 *
		 * @var Spectra_Blocks_Block_Analytics
		 */
		public $block_analytics;

		/**
		 *  Initiator
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();

				/**
				 * Spectra loaded.
				 *
				 * Fires when Spectra was fully loaded and instantiated.
				 *
				 * @since 2.1.0
				 */
				do_action( 'spectra_blocks_core_loaded' );
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			// Activation hook.
			register_activation_hook( SPECTRA_BLOCKS_FILE, array( $this, 'activation_reset' ) );

			// deActivation hook.
			register_deactivation_hook( SPECTRA_BLOCKS_FILE, array( $this, 'deactivation_reset' ) );

			$this->define_constants();

			$this->loader();

			add_action( 'after_setup_theme', array( $this, 'load_compatibility' ) );

			add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );

			add_action( 'init', array( $this, 'init_actions' ) );

			/*
			* BSF Analytics.
			*/
			if ( ! class_exists( 'BSF_Analytics_Loader' ) ) {
				require_once SPECTRA_BLOCKS_DIR . 'lib/bsf-analytics/class-bsf-analytics-loader.php';
			}

			if ( class_exists( 'BSF_Analytics_Loader' ) && is_callable( 'BSF_Analytics_Loader::get_instance' ) ) {
				$spectra_blocks_bsf_analytics = BSF_Analytics_Loader::get_instance();

				$spectra_blocks_bsf_analytics->set_entity(
					array(
						'spectra' => array(
							'product_name'        => 'Spectra',
							'path'                => SPECTRA_BLOCKS_DIR . 'lib/bsf-analytics',
							'author'              => 'Spectra by Brainstorm Force',
							'time_to_display'     => '+24 hours',
							'deactivation_survey' => apply_filters(
								'spectra_blocks_deactivation_survey_data',
								array(
									array(
										'id'              => 'deactivation-survey-ultimate-addons-for-gutenberg',
										'popup_logo'      => esc_url( plugin_dir_url( __DIR__ ) . 'assets/images/logos/spectra.svg' ),
										'plugin_slug'     => 'spectra',
										'popup_title'     => 'Quick Feedback',
										'support_url'     => 'https://wpspectra.com/contact/',
										'popup_description' => 'If you have a moment, please share why you are deactivating Spectra:',
										'show_on_screens' => array( 'plugins' ),
										'plugin_version'  => SPECTRA_BLOCKS_VER,
									),
								)
							),
							'hide_optin_checkbox' => true,
						),
					)
				);
			}

			add_filter( 'bsf_core_stats', array( $this, 'spectra_blocks_get_specific_stats' ) );

			// Initialize block analytics after BSF analytics is set up.
			$this->block_analytics = Spectra_Blocks_Block_Analytics::get_instance();
		}

		/**
		 * Defines all constants
		 *
		 * @since 1.0.0
		 */
		public function define_constants() {
			define( 'SPECTRA_BLOCKS_BASE', plugin_basename( SPECTRA_BLOCKS_FILE ) );
			define( 'SPECTRA_BLOCKS_DIR', plugin_dir_path( SPECTRA_BLOCKS_FILE ) );
			define( 'SPECTRA_BLOCKS_URL', plugins_url( '/', SPECTRA_BLOCKS_FILE ) );
			define( 'SPECTRA_BLOCKS_VER', '0.0.1' );
			define( 'SPECTRA_BLOCKS_MODULES_DIR', SPECTRA_BLOCKS_DIR . 'modules/' );
			define( 'SPECTRA_BLOCKS_MODULES_URL', SPECTRA_BLOCKS_URL . 'modules/' );
			define( 'SPECTRA_BLOCKS_SLUG', 'spectra-blocks' );
			define( 'SPECTRA_BLOCKS_URI', trailingslashit( 'https://wpspectra.com/' ) );

			if ( ! defined( 'SPECTRA_BLOCKS_TABLET_BREAKPOINT' ) ) {
				define( 'SPECTRA_BLOCKS_TABLET_BREAKPOINT', '976' );
			}
			if ( ! defined( 'SPECTRA_BLOCKS_MOBILE_BREAKPOINT' ) ) {
				define( 'SPECTRA_BLOCKS_MOBILE_BREAKPOINT', '767' );
			}

			if ( ! defined( 'SPECTRA_BLOCKS_UPLOAD_DIR_NAME' ) ) {
				define( 'SPECTRA_BLOCKS_UPLOAD_DIR_NAME', 'spectra-plugin' );
			}

			$upload_dir = wp_upload_dir( null, false );

			if ( ! defined( 'SPECTRA_BLOCKS_UPLOAD_DIR' ) ) {
				define( 'SPECTRA_BLOCKS_UPLOAD_DIR', $upload_dir['basedir'] . '/' . SPECTRA_BLOCKS_UPLOAD_DIR_NAME . '/' );
			}

			if ( ! defined( 'SPECTRA_BLOCKS_UPLOAD_URL' ) ) {
				define( 'SPECTRA_BLOCKS_UPLOAD_URL', $upload_dir['baseurl'] . '/' . SPECTRA_BLOCKS_UPLOAD_DIR_NAME . '/' );
			}

			define( 'SPECTRA_BLOCKS_ASSET_VER', get_option( '__uagb_asset_version', SPECTRA_BLOCKS_VER ) );
			define( 'SPECTRA_BLOCKS_CSS_EXT', defined( 'WP_DEBUG' ) && WP_DEBUG ? '.css' : '.min.css' );
			define( 'SPECTRA_BLOCKS_JS_EXT', defined( 'WP_DEBUG' ) && WP_DEBUG ? '.js' : '.min.js' );
		}

		/**
		 * Loads Other files.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function loader() {

			require_once SPECTRA_BLOCKS_DIR . 'classes/utils.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-block-prioritization.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-install.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-filesystem.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-update.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-block.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/analytics/class-spectra-blocks-block-analytics.php';
		}

		/**
		 * Loads plugin files.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function load_plugin() {

			// SECURITY: Load security helper class first.
		require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-security-helper.php';
		require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-scripts-utils.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-block-module.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-admin-helper.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-helper.php';
			require_once SPECTRA_BLOCKS_DIR . 'blocks-config/blocks-config.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/blocks-loader.php';
			require_once SPECTRA_BLOCKS_DIR . 'lib/astra-notices/class-astra-notices.php';
			require_once SPECTRA_BLOCKS_DIR . 'lib/class-spectra-blocks-zipwp-images.php';
			require_once SPECTRA_BLOCKS_DIR . 'lib/class-spectra-blocks-nps-survey.php';
			/**
			 * UTM Analytics lib file.
			 */
			require_once SPECTRA_BLOCKS_DIR . 'lib/class-spectra-blocks-utm-analytics.php';

			if ( is_admin() ) {
				require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-admin.php';
			}

			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-post-assets.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-front-assets.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-init-blocks.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-rest-api.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-visibility.php';
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-caching.php';

			if ( 'twentyseventeen' === get_template() ) {
				require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-twenty-seventeen-compatibility.php';
			}

			if ( 'twentysixteen' === get_template() ) {
				require_once SPECTRA_BLOCKS_DIR . 'compatibility/class-spectra-blocks-twenty-sixteen-compatibility.php';
			}

			require_once SPECTRA_BLOCKS_DIR . 'admin/admin-loader.php';

			add_filter( 'rest_pre_dispatch', array( $this, 'rest_pre_dispatch' ), 10, 3 );

			$enable_templates_button = Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'uag_enable_templates_button', 'yes' );

			// Sync the Zip AI Library textdomain with the Spectra textdomain.
			add_filter( 'zip_ai_library_textdomain', array( $this, 'sync_library_textdomain' ) );

			if ( 'yes' === $enable_templates_button ) {
				require_once SPECTRA_BLOCKS_DIR . 'lib/class-spectra-blocks-ast-block-templates.php';
			} else {
				add_filter( 'ast_block_templates_disable', '__return_true' );
			}

			// Add the filters for the Zip AI Library and include it.
			add_filter( 'zip_ai_collab_product_details', array( $this, 'add_zip_ai_collab_product_details' ), 20, 1 );
			add_filter( 'zip_ai_modules', array( $this, 'add_zip_ai_modules' ), 20, 1 );
			add_filter( 'zip_ai_auth_redirection_flag', '__return_true', 20, 1 );
			add_filter( 'zip_ai_auth_redirection_url', array( $this, 'add_zip_ai_redirection_url' ), 20, 1 );
			add_filter( 'zip_ai_revoke_redirection_url', array( $this, 'add_zip_ai_redirection_url' ), 20, 1 );

			require_once SPECTRA_BLOCKS_DIR . 'lib/class-spectra-blocks-zip-ai.php';
		}

		/**
		 * Sync the Zip AI Library textdomain with the Spectra textdomain.
		 *
		 * @param string $textdomain The textdomain for the Zip AI Library.
		 * @since 2.13.9
		 * @return string The Spectra textdomain.
		 */
		public function sync_library_textdomain( $textdomain ) {
			return 'spectra';
		}

		/**
		 * Loads theme compatibility files.
		 *
		 * @since 2.5.1
		 *
		 * @return void
		 */
		public function load_compatibility() {
			require_once SPECTRA_BLOCKS_DIR . 'classes/class-spectra-blocks-fse-fonts-compatibility.php';
		}
		/**
		 * Fix REST API issue with blocks registered via PHP register_block_type.
		 *
		 * @since 1.25.2
		 *
		 * @param mixed  $result  Response to replace the requested version with.
		 * @param object $server  Server instance.
		 * @param object $request Request used to generate the response.
		 *
		 * @return array Returns updated results.
		 */
		public function rest_pre_dispatch( $result, $server, $request ) {

			if ( strpos( $request->get_route(), '/wp/v2/block-renderer' ) !== false && isset( $request['attributes'] ) ) {

					$attributes = $request['attributes'];

				if ( isset( $attributes['UAGUserRole'] ) ) {
					unset( $attributes['UAGUserRole'] );
				}

				if ( isset( $attributes['UAGBrowser'] ) ) {
					unset( $attributes['UAGBrowser'] );
				}

				if ( isset( $attributes['UAGSystem'] ) ) {
					unset( $attributes['UAGSystem'] );
				}

				if ( isset( $attributes['UAGDisplayConditions'] ) ) {
					unset( $attributes['UAGDisplayConditions'] );
				}

				if ( isset( $attributes['UAGHideDesktop'] ) ) {
					unset( $attributes['UAGHideDesktop'] );
				}

				if ( isset( $attributes['UAGHideMob'] ) ) {
					unset( $attributes['UAGHideMob'] );
				}

				if ( isset( $attributes['UAGHideTab'] ) ) {
					unset( $attributes['UAGHideTab'] );
				}

				if ( isset( $attributes['UAGLoggedIn'] ) ) {
					unset( $attributes['UAGLoggedIn'] );
				}

				if ( isset( $attributes['UAGLoggedOut'] ) ) {
					unset( $attributes['UAGLoggedOut'] );
				}

				if ( isset( $attributes['UAGDay'] ) ) {
					unset( $attributes['UAGDay'] );
				}

				if ( isset( $attributes['zIndex'] ) ) {
					unset( $attributes['zIndex'] );
				}

				if ( isset( $attributes['UAGResponsiveConditions'] ) ) {
					unset( $attributes['UAGResponsiveConditions'] );
				}

				if ( isset( $attributes['UAGAnimationType'] ) ) {
					unset( $attributes['UAGAnimationType'] );
				}

				if ( isset( $attributes['UAGAnimationTime'] ) ) {
					unset( $attributes['UAGAnimationTime'] );
				}

				if ( isset( $attributes['UAGAnimationDelay'] ) ) {
					unset( $attributes['UAGAnimationDelay'] );
				}

				if ( isset( $attributes['UAGAnimationEasing'] ) ) {
					unset( $attributes['UAGAnimationEasing'] );
				}

				if ( isset( $attributes['UAGAnimationRepeat'] ) ) {
					unset( $attributes['UAGAnimationRepeat'] );
				}

				if ( isset( $attributes['UAGAnimationDelayInterval'] ) ) {
					unset( $attributes['UAGAnimationDelayInterval'] );
				}

				if ( isset( $attributes['UAGAnimationDoNotApplyToContainer'] ) ) {
					unset( $attributes['UAGAnimationDoNotApplyToContainer'] );
				}

				if ( isset( $attributes['UAGStickyLocation'] ) ) {
					unset( $attributes['UAGStickyLocation'] );
				}

				if ( isset( $attributes['UAGStickyRestricted'] ) ) {
					unset( $attributes['UAGStickyRestricted'] );
				}

				if ( isset( $attributes['UAGStickyOffset'] ) ) {
					unset( $attributes['UAGStickyOffset'] );
				}

				if ( isset( $attributes['UAGPosition'] ) ) {
					unset( $attributes['UAGPosition'] );
				}

					$request['attributes'] = $attributes;

			}

			return $result;
		}

		/**
		 * Check if Gutenberg is active
		 *
		 * @since 1.1.0
		 *
		 * @return boolean
		 */
		public function is_gutenberg_active() {
			return function_exists( 'register_block_type' );
		}

		/**
		 * Load Ultimate Gutenberg Text Domain.
		 * This will load the translation textdomain depending on the file priorities.
		 *      1. Global Languages /wp-content/languages/ultimate-addons-for-gutenberg/ folder
		 *      2. Local directory /wp-content/plugins/ultimate-addons-for-gutenberg/languages/ folder
		 *
		 * @since  1.0.0
		 * @return void
		 */
		public function load_textdomain() {
			// Default languages directory.
			$lang_dir = SPECTRA_BLOCKS_DIR . 'languages/';

			// Traditional WordPress plugin locale filter.
			global $wp_version;

			$get_locale = get_locale();

			if ( $wp_version >= 4.7 ) {
				$get_locale = get_user_locale();
			}

			/**
			 * Language Locale for plugin
			 *
			 * @var string $get_locale The locale to use.
			 * Uses get_user_locale()` in WordPress 4.7 or greater,
			 * otherwise uses `get_locale()`.
			 */
			$locale = apply_filters( 'plugin_locale', $get_locale, 'spectra-blocks' );
			$mofile = '';
			if ( is_string( $locale ) ) {
				$mofile = sprintf( '%1$s-%2$s.mo', 'spectra', $locale );
			}

			// Setup paths to current locale file.
			$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
			$mofile_local  = $lang_dir . $mofile;
			// Load the text domain.
			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/ultimate-addons-for-gutenberg/ folder.
				load_textdomain( 'spectra', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/ultimate-addons-for-gutenberg/languages/ folder.
				load_textdomain( 'spectra', $mofile_local );
			} else {
				// Load the default language files.
				load_plugin_textdomain( 'spectra', false, $lang_dir );
			}
		}

		/**
		 * Fires admin notice when Gutenberg is not installed and activated.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function spectra_blocks_fails_to_load() {

			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}

			$class = 'notice notice-error';
			/* translators: %s: html tags */
			$message = sprintf( __( 'The %1$sSpectra%2$s plugin requires %1$sGutenberg%2$s plugin installed & activated.', 'spectra-blocks' ), '<strong>', '</strong>' );

			$action_url   = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=gutenberg' ), 'install-plugin_gutenberg' );
			$button_label = __( 'Install Gutenberg', 'spectra-blocks' );

			$button = '<p><a href="' . $action_url . '" class="button-primary">' . $button_label . '</a></p><p></p>';

			printf( '<div class="%1$s"><p>%2$s</p>%3$s</div>', esc_attr( $class ), wp_kses_post( $message ), wp_kses_post( $button ) );
		}

		/**
		 * Activation Reset
		 */
		public function activation_reset() {
			$has_activated_before = get_option( '__uagb_activated_before', false );

			if ( ! $has_activated_before ) {
				spectra_blocks_install()->create_files();
		
				update_option( '__uagb_do_redirect', true );
				update_option( '__uagb_activated_before', true );
				update_option( '__uagb_asset_version', time() );
			} else {
				update_option( '__uagb_do_redirect', false );
			}
		}

		/**
		 * Deactivation Reset
		 */
		public function deactivation_reset() {
			update_option( '__uagb_do_redirect', false );
		}

		/**
		 * Init actions
		 *
		 * @since 2.0.0
		 *
		 * @return void
		 */
		public function init_actions() {

			// Check if Gutenberg is active, if not, don't load anything.
			// TO DO: Add an admin notice to inform the user that Gutenberg is not active.
			if ( ! $this->is_gutenberg_active() ) {
				add_action( 'admin_notices', array( $this, 'spectra_blocks_fails_to_load' ) );
				return;
			}

			// Load the text domain for translation.
			$this->load_textdomain();

			// Register all UAG Lite Blocks. This is done by calling the register_blocks method
			// on the spectra_blocks_block() instance. This method is responsible for registering all the
			// blocks in the plugin.
			spectra_blocks_block()->register_blocks();

			$theme_folder = get_template();

			if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
				if ( 'twentytwentytwo' === $theme_folder ) {
					require_once SPECTRA_BLOCKS_DIR . 'compatibility/class-spectra-blocks-twenty-twenty-two-compatibility.php';
				}
			}

			if ( 'astra' === $theme_folder ) {
				require_once SPECTRA_BLOCKS_DIR . 'compatibility/class-spectra-blocks-astra-compatibility.php';
			}
			
			register_meta(
				'post',
				'_uag_custom_page_level_css',
				array(
					'show_in_rest'      => true,
					'type'              => 'string',
					'single'            => true,
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
					'sanitize_callback' => function( $meta_value ) {
						return Spectra_Blocks_Admin_Helper::sanitize_inline_css( $meta_value );
					},
				)
			);

			// This class is loaded from blocks config.
			Spectra_Blocks_Popup_Builder::generate_scripts();

			Spectra_Blocks_Update::migrate_visibility_mode();

			// Adds filters to modify the blocks allowed in excerpts.
			add_filter( 'excerpt_allowed_blocks', array( $this, 'add_blocks_to_excerpt' ), 20 );
			add_filter( 'excerpt_allowed_wrapper_blocks', array( $this, 'add_wrapper_blocks_to_excerpt' ), 20 );
			add_filter( 'spectra_blocks_allowed_in_excerpt', array( $this, 'add_uagb_blocks_to_excerpt' ), 20, 2 );
		}

		/**
		 * Adds specified blocks to the list of allowed blocks in excerpts.
		 *
		 * @param array $allowed    List of allowed blocks in excerpts.
		 * @since 2.6.0
		 * @return array            Modified list of allowed blocks in excerpts.
		 */
		public function add_blocks_to_excerpt( $allowed ) {
			return apply_filters( 'spectra_blocks_allowed_in_excerpt', $allowed, array( 'uagb/advanced-heading' ) );
		}

		/**
		 * Adds specified wrapper blocks to the list of allowed blocks in excerpts.
		 *
		 * @param array $allowed    List of allowed blocks in excerpts.
		 * @since 2.6.0
		 * @return array            Modified list of allowed blocks in excerpts.
		 */
		public function add_wrapper_blocks_to_excerpt( $allowed ) {
			return apply_filters(
				'spectra_blocks_allowed_in_excerpt',
				$allowed,
				array(
					'uagb/container',
					'uagb/columns',
					'uagb/column',
				)
			);
		}

		/**
		 * Adds specified Spectra blocks to the list of allowed blocks in excerpts.
		 *
		 * @param array $excerpt_blocks     List of allowed blocks in excerpts.
		 * @param array $blocks_to_add      Blocks to add to the list of allowed blocks in excerpts.
		 * @since 2.6.0
		 * @return array                    The merged excerpt blocks array if both parameters are arrays, or the original excerpt blocks if either parameter is not an array.
		 */
		public function add_uagb_blocks_to_excerpt( $excerpt_blocks, $blocks_to_add ) {
			if ( is_array( $excerpt_blocks ) && is_array( $blocks_to_add ) ) {
				return array_merge( $excerpt_blocks, $blocks_to_add );
			}

			// If either parameter is not an array, return the original excerpt blocks.
			return $excerpt_blocks;
		}

		/**
		 * Generate assets on migration.
		 *
		 * @since 2.7.10
		 * @return void
		 */
		public function get_regenerate_assets_on_migration() {
			// Parse the host (domain/hostname) from the site URL.
			$site_host = wp_parse_url( site_url(), PHP_URL_HOST );

			// Check if $site_host is empty or not a string. If true, return and exit the function.
			if ( empty( $site_host ) || ! is_string( $site_host ) ) {
				return;
			}

			// Remove 'www.' from the domain.
			$domain = str_replace( 'www.', '', $site_host );

			// Replace dots (.) with dashes (-) in the domain to create $site_domain.
			$site_domain = str_replace( '.', '-', $domain );

			// Retrieve the stored domain from admin settings.
			$stored_domain = \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_site_url', '' );

			// If the stored domain is empty, update the 'spectra_blocks_site_url' option in admin settings with the modified site domain and return.
			if ( empty( $stored_domain ) ) {
				\Spectra_Blocks_Admin_Helper::update_admin_settings_option( 'spectra_blocks_site_url', $site_domain );
				return;
			}

			// If the stored domain is different from the current site domain, update the '__uagb_asset_version' option with the current timestamp.
			if ( $stored_domain !== $site_domain ) {
				\Spectra_Blocks_Admin_Helper::update_admin_settings_option( '__uagb_asset_version', time() );
			}
		}

		/**
		 * Add the Zip AI Collab Product Details.
		 *
		 * @param mixed $product_details The previous product details, if any.
		 * @since 2.10.2
		 * @return array The Spectra product details.
		 */
		public function add_zip_ai_collab_product_details( $product_details ) {
			// Overwrite the product details that were of a lower priority, if any.
			$product_details = array(
				'product_name'                          => 'Spectra',
				'product_slug'                          => 'spectra',
				'product_logo'                          => file_get_contents( SPECTRA_BLOCKS_DIR . 'assets/images/logos/spectra.svg' ),
				'product_primary_color'                 => '#5733ff',
				'ai_assistant_learn_more_url'           => admin_url( 'admin.php?page=spectra&path=ai-features' ),
				'ai_assistant_authorized_disable_url'   => admin_url( 'admin.php?page=spectra&path=ai-features&manage-features=yes' ),
				'ai_assistant_unauthorized_disable_url' => admin_url( 'admin.php?page=spectra&path=ai-features&manage-features=yes' ),
			);
			// Return the Spectra product details.
			return $product_details;
		}

		/**
		 * Add the Zip AI Modules that come with Spectra.
		 *
		 * @param mixed $modules The modules for Zip AI, if any.
		 * @since 2.10.2
		 * @return array The Spectra default modules.
		 */
		public function add_zip_ai_modules( $modules ) {
			// If the filtered modules is not an array, make it one.
			$modules = is_array( $modules ) ? $modules : array();

			// List of module names to enable.
			$modules_to_enable = array( 'ai_assistant', 'ai_design_copilot' );

			// Ensure each module in the list is enabled.
			foreach ( $modules_to_enable as $module_name ) {
				// @phpcs:ignore WordPress.NamingConventions.ValidVariableName
				if ( class_exists( '\SpectraZipAI\Classes\Module' ) && method_exists( '\SpectraZipAI\Classes\Module', 'force_enabled' ) ) {
					\SpectraZipAI\Classes\Module::force_enabled( $modules, $module_name );
				}
			}

			// Return the Spectra default modules.
			return $modules;
		}

		/**
		 * Add the Zip AI Authorization/Revoke URL.
		 *
		 * @param mixed $auth_url The previous authorization URL, if any.
		 * @since 2.10.2
		 * @return string The Spectra redirection URL.
		 */
		public function add_zip_ai_redirection_url( $auth_url ) {
			return admin_url( 'admin.php?page=spectra&path=ai-features' );
		}

		/**
		 * Create an array of block status.
		 *
		 * @return array $block_status_data An associative array of block slug => status.
		 *                                  The status can be either 'enabled' or 'disabled'.
		 */
		public function create_block_status_array() {
			$saved_blocks      = (array) \Spectra_Blocks_Admin_Helper::get_admin_settings_option( '_uagb_blocks' );
			$block_manager     = spectra_blocks_block();
			$blocks            = ( method_exists( $block_manager, 'get_blocks' ) )
			? (array) $block_manager->get_blocks()
			: array();
			$block_status_data = array();
			if ( is_array( $blocks ) ) {
				foreach ( $blocks as $slug => $data ) {
					$_slug = str_replace( 'uagb/', '', $slug );
			
					// Skip child blocks.
					if ( isset( $blocks[ $slug ]['is_child'] ) ) {
						continue;
					}
			
					// Initialize status array.
					$block_status_data[ $_slug ] = array();
			
					// Check saved status.
					if ( isset( $saved_blocks[ $_slug ] ) ) {
						$block_status_data[ $_slug ] = 
							'disabled' === $saved_blocks[ $_slug ] ? 'disabled' : 'enabled';
					} else {
						$block_status_data[ $_slug ] = 'enabled';
					}
				}
			}

			return $block_status_data;
		}

		/**
		 * Generates global setting data for analytics
		 *
		 * @since 1.4.0
		 * @return array
		 */
		public function global_settings_data() {
			$global_data = array();
			// Prepare to get the Zip AI Co-pilot modules.
			$zip_ai_modules                               = array();
			$bsf_internal_referrer                        = get_option( 'bsf_product_referers', array() );
			$bsf_internal_referrer                        = (array) $bsf_internal_referrer;
			$global_data['internal_referer']              = isset( $bsf_internal_referrer['spectra'] ) 
				? $bsf_internal_referrer['spectra'] 
				: '';
			$global_data['beta']                          = get_option( 'spectra_blocks_beta' );
			$global_data['file_generation']               = get_option( '_uagb_allow_file_generation' );
			$global_data['templates_button']              = get_option( 'uag_enable_templates_button' );
			$global_data['on_page_css_button']            = get_option( 'uag_enable_on_page_css_button' );
			$global_data['block_condition']               = get_option( 'uag_enable_block_condition' );
			$global_data['quick_action_sidebar']          = get_option( 'uag_enable_quick_action_sidebar' );
			$global_data['gbs_extension']                 = get_option( 'uag_enable_gbs_extension' );
			$global_data['block_responsive']              = get_option( 'uag_enable_block_responsive' );
			$global_data['load_select_font_globally']     = get_option( 'uag_load_select_font_globally' );
			$global_data['load_gfonts_locally']           = get_option( 'uag_load_gfonts_locally' );
			$global_data['collapse_panels']               = get_option( 'uag_collapse_panels' );
			$global_data['copy_paste']                    = get_option( 'uag_copy_paste' );
			$global_data['preload_local_fonts']           = get_option( 'uag_preload_local_fonts' );
			$global_data['visibility_mode']               = get_option( 'uag_visibility_mode' );
			$global_data['container_global_padding']      = get_option( 'uag_container_global_padding' );
			$global_data['container_global_elements_gap'] = get_option( 'uag_container_global_elements_gap' );
			$global_data['btn_inherit_from_theme']        = get_option( 'uag_btn_inherit_from_theme' );
			$global_data['blocks_editor_spacing']         = get_option( 'uag_blocks_editor_spacing' );
			$global_data['load_font_awesome_5']           = get_option( 'uag_load_font_awesome_5' );
			$global_data['auto_block_recovery']           = get_option( 'uag_auto_block_recovery' );
			$global_data['load_fse_font_globally']        = get_option( 'uag_load_fse_font_globally' );
			// If the Zip AI Helper is available, get the required modules and their states.
			if ( class_exists( '\SpectraZipAI\Classes\Module' ) ) {
				$zip_ai_modules = Zip_Ai_Module::get_all_modules();
				// Restructure AI-related data.
				if ( isset( $zip_ai_modules['ai_assistant'] ) ) {
					$global_data['ai_assistant'] = $zip_ai_modules['ai_assistant']['status'];
				}
				
				if ( isset( $zip_ai_modules['ai_design_copilot'] ) ) {
					$global_data['ai_design_copilot'] = $zip_ai_modules['ai_design_copilot']['status'];
				}
				
				// Merge the rest of the modules.
				$global_data = array_merge_recursive(
					$global_data,
					array_filter(
						$zip_ai_modules,
						function( $key ) {
							return ! in_array( $key, array( 'ai_assistant', 'ai_design_copilot' ) );
						},
						ARRAY_FILTER_USE_KEY
					)
				);
			}
			// Return the global data.
			return $global_data;
		}

		/**
		 * Add Spectra learn progress analytics data.
		 *
		 * @param array $analytics_data Reference to the Spectra stats data.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public static function add_learn_progress_analytics_data( &$analytics_data ) {
			global $wpdb; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

			/** 
			 * Get all users who have learn progress data.
			 * 
			 * @psalm-suppress UndefinedConstant 
			 */
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prepare(
					"SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
					'spectra_blocks_learn_progress'
				),
				ARRAY_A
			);

			// Return if no data found.
			if ( empty( $results ) ) {
				return;
			}

			// Get the actual chapters structure to validate against.
			$chapters = (array) Admin_Learn::get_chapters_structure();

			// Initialize array to store unique completed chapter IDs across all users.
			$completed_chapters = array();

			// Process each user's progress data.
			foreach ( $results as $row ) {
				$progress_data = maybe_unserialize( $row['meta_value'] );

				// Skip if data is not an array.
				if ( ! is_array( $progress_data ) ) {
					continue;
				}

				// Check each chapter from the actual structure.
				foreach ( $chapters as $chapter ) {
					$chapter_id = $chapter['id'];

					// Skip if already recorded as completed.
					if ( in_array( $chapter_id, $completed_chapters, true ) ) {
						continue;
					}

					// Skip if this chapter has no steps defined.
					if ( ! isset( $chapter['steps'] ) || ! is_array( $chapter['steps'] ) || empty( $chapter['steps'] ) ) {
						continue;
					}

					// Skip if this chapter doesn't exist in this user's progress data.
					if ( ! isset( $progress_data[ $chapter_id ] ) || ! is_array( $progress_data[ $chapter_id ] ) ) {
						continue;
					}

					// Check if ALL steps from the chapter definition are completed for this user.
					$all_steps_completed = true;
					foreach ( $chapter['steps'] as $step ) {
						$step_id = $step['id'];

						// If step is not in progress data or not completed, chapter is incomplete.
						if ( ! isset( $progress_data[ $chapter_id ][ $step_id ] ) || ! $progress_data[ $chapter_id ][ $step_id ] ) {
							$all_steps_completed = false;
							break;
						}
					}

					// If all steps are completed for this user, add chapter ID to the array.
					if ( $all_steps_completed ) {
						$completed_chapters[] = $chapter_id;
					}
				}
			}
			// Add to spectra stats if we have completed chapters.
			if ( ! empty( $completed_chapters ) ) {
				$analytics_data['learn_chapters_completed'] = array_values( array_unique( $completed_chapters ) );
			}
		}

		/**
		 * Pass spectra specific stats to BSF analytics.
		 *
		 * @since 2.19.5
		 * @param array $default_stats Default stats array.
		 * @return array $default_stats Default stats with spectra specific stats array.
		 */
		public function spectra_blocks_get_specific_stats( $default_stats ) {
			// Ensure the spectra plugin data container exists.
			if ( empty( $default_stats['plugin_data']['spectra'] ) || ! is_array( $default_stats['plugin_data']['spectra'] ) ) {
				$default_stats['plugin_data']['spectra'] = array();
			}

			// Add the default values to the spectra plugin data container.
			$array_of_defaults = array(
				'version'              => SPECTRA_BLOCKS_VER,
				'old_user_less_than_2' => get_option( 'uagb-old-user-less-than-2' ), // Retrieves current user is old user less than 2 or not.
				'migration_status'     => get_option( 'uag_migration_status' ), // Retrieves migration status.
			);

			// Merge the default values with the global settings data and block status data.
			$default_stats['plugin_data']['spectra'] = array_merge_recursive( $default_stats['plugin_data']['spectra'], $array_of_defaults );
			$default_stats['plugin_data']['spectra'] = array_merge_recursive( $default_stats['plugin_data']['spectra'], $this->global_settings_data() );
			$default_stats['plugin_data']['spectra'] = array_merge_recursive( $default_stats['plugin_data']['spectra'], $this->create_block_status_array() );

			// Add advanced block usage statistics.
			if ( is_object( $this->block_analytics ) ) {
				$default_stats['plugin_data']['spectra'] = $this->block_analytics->get_block_stats_for_analytics( $default_stats['plugin_data']['spectra'] );
			}

			// Add learn progress analytics data.
			self::add_learn_progress_analytics_data( $default_stats['plugin_data']['spectra'] );
			// Return the modified default stats.
			return $default_stats;
		}
	}
}

/**
 *  Prepare if class 'Spectra_Blocks_Loader' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Spectra_Blocks_Loader::get_instance();

/**
 * Load main object
 *
 * @since 2.0.0
 *
 * @return object
 */
function spectra_blocks() {
	return Spectra_Blocks_Loader::get_instance();
}
