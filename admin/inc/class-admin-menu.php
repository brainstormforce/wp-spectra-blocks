<?php
/**
 * Spectra Blocks Admin Menu.
 *
 * @package Spectra_Blocks
 */

namespace SpectraBlocksAdmin\Inc;

use SpectraBlocksAdmin\Inc\Admin_Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * Class Admin_Menu.
 */
class Admin_Menu {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class object.
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Instance
	 *
	 * @access private
	 * @var string Class object.
	 * @since 1.0.0
	 */
	private $menu_slug = 'spectra-blocks';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->initialize_hooks();
	}

	/**
	 * Init Hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function initialize_hooks() {

		/* Setup the Admin Menu */
		add_action( 'admin_menu', array( $this, 'setup_menu' ) );
		add_action( 'admin_menu', array( $this, 'rename_classic_spectra_menu' ), 99 );
		add_action( 'admin_init', array( $this, 'settings_admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_submenu_styles' ) );
		add_filter(
			'admin_footer_text',
			function ( $text ) {
				$screen = get_current_screen();
				if ( $screen && false !== strpos( $screen->id, 'spectra-blocks' ) ) {
					return '';
				}
				return $text;
			}
		);

		/* Add the Action Links */
		add_filter( 'plugin_action_links_' . SPECTRA_BLOCKS_BASE, array( $this, 'add_action_links' ) );

		/* Render admin content view */
		add_action( 'spectra_blocks_render_admin_content', array( $this, 'render_content' ), 10, 2 );

		add_action( 'wp_ajax_spectra_blocks_recommended_plugin_activate', array( $this, 'spectra_blocks_activate_addon' ) );
		add_action( 'wp_ajax_spectra_blocks_recommended_plugin_install', 'wp_ajax_install_plugin' );
		add_action( 'wp_ajax_spectra_blocks_recommended_theme_install', 'wp_ajax_install_theme' );
	}

	/**
	 * List of plugins that we propose to install.
	 *
	 * @since 2.19.0
	 *
	 * @return array
	 */
	public static function get_bsf_plugins() {

		$plugins = array(

			'astra'                           => array(
				'type'         => 'theme',
				'name'         => esc_html__( 'Astra', 'spectra-blocks' ),
				'desc'         => esc_html__( 'Fast and customizable theme for your website.', 'spectra-blocks' ),
				'wporg'        => 'https://wordpress.org/themes/astra/',
				'url'          => 'https://downloads.wordpress.org/theme/astra.zip',
				'siteurl'      => 'https://wpastra.com/',
				'slug'         => 'astra',
				'isFree'       => true,
				'status'       => self::get_theme_status( 'astra' ),
				'settings_url' => admin_url( 'admin.php?page=astra' ),
			),

			'astra-sites/astra-sites.php'     => array(
				'type'         => 'plugin',
				'name'         => esc_html__( 'Starter Templates', 'spectra-blocks' ),
				'desc'         => esc_html__( 'Launch websites with AI or ready-made templates.', 'spectra-blocks' ),
				'wporg'        => 'https://wordpress.org/plugins/astra-sites/',
				'url'          => 'https://downloads.wordpress.org/plugin/astra-sites.zip',
				'siteurl'      => 'https://startertemplates.com/',
				'slug'         => 'astra-sites',
				'isFree'       => true,
				'status'       => self::get_plugin_status( 'astra-sites/astra-sites.php' ),
				'settings_url' => admin_url( 'admin.php?page=starter-templates' ),
			),

			'surecart/surecart.php'           => array(
				'type'         => 'plugin',
				'name'         => esc_html__( 'SureCart', 'spectra-blocks' ),
				'desc'         => esc_html__( 'Sell your products easily on WordPress.', 'spectra-blocks' ),
				'wporg'        => 'https://wordpress.org/plugins/surecart/',
				'url'          => 'https://downloads.wordpress.org/plugin/surecart.zip',
				'siteurl'      => 'https://surecart.com/',
				'isFree'       => true,
				'slug'         => 'surecart',
				'status'       => self::get_plugin_status( 'surecart/surecart.php' ),
				'settings_url' => admin_url( 'admin.php?page=sc-getting-started' ),
			),

			'presto-player/presto-player.php' => array(
				'type'         => 'plugin',
				'name'         => esc_html__( 'Presto Player', 'spectra-blocks' ),
				'desc'         => html_entity_decode( esc_html__( 'Display seamless & interactive videos.', 'spectra-blocks' ) ),
				'wporg'        => 'https://wordpress.org/plugins/presto-player/',
				'url'          => 'https://downloads.wordpress.org/plugin/presto-player.zip',
				'siteurl'      => 'https://prestoplayer.com/',
				'slug'         => 'presto-player',
				'isFree'       => true,
				'status'       => self::get_plugin_status( 'presto-player/presto-player.php' ),
				'settings_url' => admin_url( 'edit.php?post_type=pp_video_block' ),
			),

		);

		return $plugins;
	}

	/**
	 * Activate addon.
	 *
	 * @since 2.19.0
	 * @return void
	 */
	public function spectra_blocks_activate_addon() {

		// Run a security check.
		check_ajax_referer( 'updates', 'nonce' );

		// The 'updates' nonce is a WordPress-core-owned handle available to any
		// authenticated user viewing the Plugins or Updates page, so enforce a
		// unified capability gate here before the handler branches by $_POST['type'].
		// The per-branch checks below remain as defence-in-depth.
		if ( ! current_user_can( 'activate_plugins' ) && ! current_user_can( 'switch_themes' ) ) {
			wp_send_json_error( esc_html__( 'You do not have permission to activate plugins or themes on this site.', 'spectra-blocks' ) );
		}

		if ( isset( $_POST['plugin'] ) ) {

			$type = '';
			if ( ! empty( $_POST['type'] ) ) {
				$type = sanitize_key( wp_unslash( $_POST['type'] ) );
			}

			$plugin = sanitize_text_field( wp_unslash( $_POST['plugin'] ) );

			if ( 'plugin' === $type ) {

				// Check for permissions.
				if ( ! current_user_can( 'activate_plugins' ) ) {
					wp_send_json_error( esc_html__( 'Plugin activation is disabled for you on this site.', 'spectra-blocks' ) );
				}
				// Return redirect URL instead of activating directly — activation should be done from the Plugins page.
				wp_send_json_success(
					array(
						'redirect' => admin_url( 'plugins.php' ),
						'message'  => esc_html__( 'Please activate the plugin from the Plugins page.', 'spectra-blocks' ),
					)
				);
			}

			if ( 'theme' === $type ) {

				if ( isset( $_POST['slug'] ) ) {
					$slug = sanitize_key( wp_unslash( $_POST['slug'] ) );

					// Check for permissions.
					if ( ! ( current_user_can( 'switch_themes' ) ) ) {
						wp_send_json_error( esc_html__( 'Theme activation is disabled for you on this site.', 'spectra-blocks' ) );
					}

					// Return redirect URL instead of switching directly — activation should be done from the Themes page.
					wp_send_json_success(
						array(
							'redirect' => admin_url( 'themes.php' ),
							'message'  => esc_html__( 'Please activate the theme from the Themes page.', 'spectra-blocks' ),
						)
					);
				}
			}
		}

		if ( isset( $type ) ) {
			if ( 'plugin' === $type ) {
				wp_send_json_error( esc_html__( 'Could not process plugin. Please activate from the Plugins page.', 'spectra-blocks' ) );
			} elseif ( 'theme' === $type ) {
				wp_send_json_error( esc_html__( 'Could not process theme. Please activate from the Themes page.', 'spectra-blocks' ) );
			}
		}
	}

	/**
	 * Get the status of a plugin.
	 *
	 * @since 2.19.0
	 *
	 * @param  string $plugin_init_file Plugin init file.
	 * @return string
	 */
	public static function get_plugin_status( $plugin_init_file ) {

		$installed_plugins = get_plugins();

		if ( ! isset( $installed_plugins[ $plugin_init_file ] ) ) {
			return 'Install';
		} elseif ( is_plugin_active( $plugin_init_file ) ) {
			return 'Activated';
		} else {
			return 'Installed';
		}
	}

	/**
	 * Get the status of a theme.
	 *
	 * @param string $theme_slug The slug of the theme.
	 * @return string The theme status: 'Activated', 'Installed', or 'Install'.
	 *
	 * @since 2.19.0
	 */
	public static function get_theme_status( $theme_slug ) {
		$installed_themes = wp_get_themes();

		// Check if the theme is installed.
		if ( isset( $installed_themes[ $theme_slug ] ) ) {
			$current_theme = wp_get_theme();
			// Check if the current theme slug matches the provided theme slug.
			if ( $current_theme->get_stylesheet() === $theme_slug ) {
				return 'Activated'; // Theme is active.
			} else {
				return 'Installed'; // Theme is installed but not active.
			}
		} else {
			return 'Install'; // Theme is not installed at all.
		}
	}

	/**
	 * Show action on plugin page.
	 *
	 * @param  array $links links.
	 * @return array
	 */
	public function add_action_links( $links ) {

		$default_url = admin_url( 'admin.php?page=' . $this->menu_slug );
		$spectra_pro = \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/pricing/', 'free-plugin', 'plugin-list', 'plugin-list' );

		$free_links = array(
			'<a href="' . esc_url( $default_url ) . '">' . __( 'Settings', 'spectra-blocks' ) . '</a>',
		);

		// Check if Spectra Pro plugin is not active.
		if ( ! is_plugin_active( 'spectra-blocks-pro/spectra-blocks-pro.php' ) && ! file_exists( SPECTRA_BLOCKS_DIR . '../spectra-blocks-pro/spectra-blocks-pro.php' ) ) {
			$free_links[] = '<a href="' . esc_url( $spectra_pro ) . '" target="_blank" rel="noreferrer" class="spectra-plugins-go-pro">' . __( 'Get Spectra Pro', 'spectra-blocks' ) . '</a>';
		}

		// Merge with $links array if it exists (assuming $links is defined elsewhere).
		if ( isset( $links ) && is_array( $links ) ) {
			return array_merge( $free_links, $links );
		}

		return $free_links;
	}

	/**
	 *  Initialize after Spectra gets loaded.
	 */
	public function settings_admin_scripts() {

		// Enqueue admin scripts.
		if ( ( ! empty( $_GET['page'] ) && ( $this->menu_slug === sanitize_text_field( wp_unslash( $_GET['page'] ) ) || false !== strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), $this->menu_slug . '_' ) ) ) || ( array_key_exists( 'post_type', $_GET ) && 'spectra-blocks-popup' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.PHP.YodaConditions.NotYoda -- $_GET['page'] does not provide nonce; property comparison has no literal side.
			add_action( 'admin_enqueue_scripts', array( $this, 'styles_scripts' ) );
		}
	}

	/**
	 * Add submenu to admin menu.
	 *
	 * @since 1.0.0
	 */
	public function setup_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$menu_slug  = $this->menu_slug;
		$capability = 'manage_options';

		$icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDcwIDcwIiBmaWxsPSJub25lIiBjbGFzcz0ic3BlY3RyYS1wYWdlLXNldHRpbmdzLWJ1dHRvbiIgYXJpYS1oaWRkZW49InRydWUiIGZvY3VzYWJsZT0iZmFsc2UiPiA8cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTM1IDcwQzU0LjMzIDcwIDcwIDU0LjMzIDcwIDM1QzcwIDE1LjY3IDU0LjMzIDAgMzUgMEMxNS42NyAwIDAgMTUuNjcgMCAzNUMwIDU0LjMzIDE1LjY3IDcwIDM1IDcwWk0yNC40NDcxIDIzLjUxMTJDMTguOTcyMiAyNi43NDAzIDIwLjI4NTIgMzUuMzc1OSAyNi41MDMyIDM3LjAzNTFMMzYuODg3NSAzOS44MDZDMzcuNzUzMyA0MC4wMzcgMzcuOTEgNDEuMjI0IDM3LjEzNSA0MS42ODExTDI3LjA5NzIgNDcuNTc5OUwyNi4wMzYgNThMNDUuNTUyOSA0Ni40ODg4QzUxLjAyNzggNDMuMjU5NyA0OS43MTQ4IDM0LjYyNDEgNDMuNDk2OCAzMi45NjQ5TDMzLjExMjUgMzAuMTk0MUMzMi4yNDY3IDI5Ljk2MyAzMi4wOSAyOC43NzYgMzIuODY1IDI4LjMxODlMNDIuOTAyOCAyMi40MjAyTDQzLjk2NCAxMkwyNC40NDcxIDIzLjUxMTJaIj48L3BhdGg+IDwvc3ZnPg==';

		// Add the Spectra Menu.
		add_menu_page(
			__( 'Spectra Blocks', 'spectra-blocks' ),
			__( 'Spectra Blocks', 'spectra-blocks' ),
			$capability,
			$menu_slug,
			array( $this, 'render' ),
			$icon,
			30
		);

		// Add the Dashboard Submenu.
		add_submenu_page(
			$menu_slug,
			__( 'Spectra', 'spectra-blocks' ),
			__( 'Dashboard', 'spectra-blocks' ),
			$capability,
			$menu_slug,
			array( $this, 'render' )
		);

		add_submenu_page(
			$menu_slug,
			__( 'Spectra', 'spectra-blocks' ),
			__( 'AI Features', 'spectra-blocks' ),
			$capability,
			$menu_slug . '&path=ai-features',
			array( $this, 'render' )
		);

		// Use this action hook to add sub menu to above menu.
		do_action( 'spectra_blocks_after_menu_register', $menu_slug );

		// Add the Popup Builder Submenu.
		add_submenu_page(
			$menu_slug,
			__( 'Popup Builder', 'spectra-blocks' ),
			__( 'Popup Builder', 'spectra-blocks' ),
			$capability,
			'edit.php?post_type=spectra-blocks-popup',
			null
		);

		// Add the Learn tab in Submenu.
		add_submenu_page(
			$menu_slug,
			__( 'Spectra', 'spectra-blocks' ),
			__( 'Learn', 'spectra-blocks' ),
			$capability,
			$menu_slug . '&path=learn',
			array( $this, 'render' )
		);

		// Finally, add the Settings Submenu.
		add_submenu_page(
			$menu_slug,
			__( 'Spectra', 'spectra-blocks' ),
			__( 'Settings', 'spectra-blocks' ),
			$capability,
			$menu_slug . '&path=settings',
			array( $this, 'render' )
		);

		// Add the Free vs Pro Submenu.
		if ( ! file_exists( SPECTRA_BLOCKS_DIR . '../spectra-blocks-pro/spectra-blocks-pro.php' ) ) {
			add_submenu_page(
				$menu_slug,
				__( 'Free vs Pro', 'spectra-blocks' ),
				__( 'Get Spectra Pro', 'spectra-blocks' ),
				$capability,
				$menu_slug . '&path=free-vs-pro',
				array( $this, 'render' )
			);
		}
	}

	/**
	 * Renders the admin settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render() {

		$menu_page_slug = ( ! empty( $_GET['page'] ) ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : $this->menu_slug; //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['page'] does not provide nonce.
		$page_action    = '';

		if ( isset( $_GET['action'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['page'] does not provide nonce.
			$page_action = sanitize_text_field( wp_unslash( $_GET['action'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['page'] does not provide nonce.
			$page_action = str_replace( '_', '-', $page_action );
		}

		include_once SPECTRA_BLOCKS_ADMIN_DIR . 'views/admin-base.php';
	}

	/**
	 * Renders the admin settings content.
	 *
	 * @since 1.0.0
	 * @param string $menu_page_slug  current page name.
	 * @param string $_page_action    current page action (reserved, intentionally unused).
	 *
	 * @return void
	 */
	public function render_content( $menu_page_slug, $_page_action ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		if ( $this->menu_slug === $menu_page_slug ) {
			include_once SPECTRA_BLOCKS_ADMIN_DIR . 'views/dashboard-app.php';
		}
	}

	/**
	 * Enqueues the sidebar submenu styles on all admin pages.
	 *
	 * @since 0.0.9
	 * @return void
	 */
	public function enqueue_submenu_styles() {
		wp_enqueue_style( 'spectra-blocks-submenu-style', SPECTRA_BLOCKS_URL . 'admin/assets/spectra-submenu.css', array(), SPECTRA_BLOCKS_VER );
	}

	/**
	 * Enqueues the needed CSS/JS for the builder's admin settings page.
	 *
	 * @since 1.0.0
	 */
	public function styles_scripts() {

		$admin_slug  = 'spectra-blocks-admin';
		$blocks_info = $this->get_blocks_info_for_activation_deactivation();
		// Styles.
		wp_enqueue_style( 'wp-components' );

		$theme = wp_get_theme();

		$theme_data          = \WP_Theme_JSON_Resolver::get_theme_data();
		$theme_settings      = $theme_data->get_settings();
		$theme_font_families = isset( $theme_settings['typography']['fontFamilies']['theme'] ) && is_array( $theme_settings['typography']['fontFamilies']['theme'] ) ? $theme_settings['typography']['fontFamilies']['theme'] : array();
		$localize            = apply_filters(
			'spectra_blocks_admin_localize',
			array(
				'current_user'                        => ! empty( wp_get_current_user()->user_firstname ) ? wp_get_current_user()->user_firstname : wp_get_current_user()->display_name,
				'admin_base_url'                      => admin_url(),
				'spectra_blocks_base_url'             => admin_url( 'admin.php?page=' . $this->menu_slug ),
				'plugin_dir'                          => SPECTRA_BLOCKS_URL,
				'plugin_url'                          => SPECTRA_BLOCKS_URL,
				'preview_url'                         => SPECTRA_BLOCKS_URL . 'admin/assets/',
				'plugin_ver'                          => SPECTRA_BLOCKS_VER,
				'admin_url'                           => admin_url( 'admin.php' ),
				'ajax_url'                            => admin_url( 'admin-ajax.php' ),
				'wp_pages_url'                        => admin_url( 'post-new.php?post_type=page' ),
				'home_slug'                           => $this->menu_slug,
				'blocks_info'                         => $blocks_info,
				'reusable_url'                        => esc_url( admin_url( 'edit.php?post_type=wp_block' ) ),
				'global_data'                         => Admin_Helper::get_options(),
				'spectra_blocks_content_width_set_by' => \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_content_width_set_by', __( 'Spectra', 'spectra-blocks' ) ),
				'spectra_pro_installed'               => file_exists( SPECTRA_BLOCKS_DIR . '../spectra-blocks-pro/spectra-blocks-pro.php' ),
				'spectra_pro_licensing'               => file_exists( SPECTRA_BLOCKS_DIR . '../spectra-blocks-pro/admin/license-handler.php' ),
				'spectra_pro_status'                  => is_plugin_active( 'spectra-blocks-pro/spectra-blocks-pro.php' ),
				'spectra_pro_ver'                     => defined( 'SPECTRA_BLOCKS_PRO_VER' ) ? SPECTRA_BLOCKS_PRO_VER : null,
				'spectra_custom_fonts'                => apply_filters( 'spectra_system_fonts', array() ),
				'spectra_admin_video'                 => apply_filters( 'spectra_display_admin_video', true ),
				'is_allow_registration'               => (bool) get_option( 'users_can_register' ),
				'theme_fonts'                         => $theme_font_families,
				'is_block_theme'                      => \Spectra_Blocks_Admin_Helper::is_block_theme(),
				'spectra_blocks_site_url'             => SPECTRA_BLOCKS_URI,
				'spectra_website'                     => array(
					'baseUrl'                => SPECTRA_BLOCKS_URI,
					'docsUrl'                => \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/docs/', 'free-plugin', 'spectra-dashboard', 'documentation' ),
					'docsCategoryDynamicUrl' => \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/docs-category/{{category}}', 'free-plugin', 'spectra-dashboard', 'documentation' ),
					'vipPrioritySupportUrl'  => \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/vip-priority-support/', 'free-plugin', 'spectra-dashboard', 'vip-priority-support' ),
					'templatesUrl'           => \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/pricing/', 'free-plugin', 'spectra-dashboard', 'starter-templates' ),
					'banner'                 => \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/pricing/', 'free-plugin', 'spectra-dashboard', 'banner' ),
					'topBar'                 => \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/pricing/', 'free-plugin', 'spectra-dashboard', 'top-bar' ),
					'freeVsPro'              => \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/pricing/', 'free-plugin', 'spectra-dashboard', 'free-vs-pro' ),
					'setting'                => \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/pricing/', 'free-plugin', 'spectra-dashboard', 'setting' ),
					'uagDashboard'           => \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/pricing/', 'free-plugin', 'spectra-dashboard', 'uag-dashboard' ),
					'whatsNewFeedUrl'        => esc_url( SPECTRA_BLOCKS_URI . '/whats-new/feed/' ),
					'upsellModalAdmin'       => \Spectra_Blocks_Admin_Helper::get_spectra_pro_url( '/pricing/', 'free-plugin', 'spectra-dashboard', 'upsell-popup-view-plan' ),
				),
				'plugin_installing_text'              => esc_html__( 'Installing', 'spectra-blocks' ),
				'plugin_installed_text'               => esc_html__( 'Installed', 'spectra-blocks' ),
				'plugin_activating_text'              => esc_html__( 'Activating', 'spectra-blocks' ),
				'plugin_activated_text'               => esc_html__( 'Activated', 'spectra-blocks' ),
				'plugin_activate_text'                => esc_html__( 'Activate', 'spectra-blocks' ),
				'plugin_manager_nonce'                => wp_create_nonce( 'spectra_plugin_manager_nonce' ),
				'installer_nonce'                     => wp_create_nonce( 'updates' ),
				'pro_installed_status'                => 'inactive' === self::get_plugin_status( 'spectra-blocks-pro/spectra-blocks-pro.php' ) ? true : false,
				'pro_plugin_status'                   => self::get_plugin_status( 'spectra-blocks-pro/spectra-blocks-pro.php' ),
				'contry_code'                         => \Spectra_Blocks_Admin_Helper::get_user_country_code(),
				'clear_v3_cache_nonce'                => wp_create_nonce( 'spectra_blocks_clear_v3_cache' ),
				'disable_css_cache_nonce'             => wp_create_nonce( 'spectra_blocks_disable_css_cache' ),
				'recaptcha_site_key_v2_nonce'         => wp_create_nonce( 'spectra_blocks_recaptcha_site_key_v2' ),
				'recaptcha_secret_key_v2_nonce'       => wp_create_nonce( 'spectra_blocks_recaptcha_secret_key_v2' ),
				'recaptcha_site_key_v3_nonce'         => wp_create_nonce( 'spectra_blocks_recaptcha_site_key_v3' ),
				'recaptcha_secret_key_v3_nonce'       => wp_create_nonce( 'spectra_blocks_recaptcha_secret_key_v3' ),
				'enable_abilities_nonce'              => wp_create_nonce( 'spectra_blocks_enable_abilities' ),
				'enable_edit_abilities_nonce'         => wp_create_nonce( 'spectra_blocks_enable_edit_abilities' ),
				'enable_mcp_server_nonce'             => wp_create_nonce( 'spectra_blocks_enable_mcp_server' ),
				'is_mcp_adapter_active'               => class_exists( 'WP\\MCP\\Plugin' ),
				'rest_url'                            => get_rest_url(),
				'current_username'                    => wp_get_current_user()->user_login,
				'application_passwords_url'           => admin_url( 'profile.php#application-passwords-section' ),
			)
		);

		// Always expose plugin status, install nonce, and auth data.
		if ( is_array( $localize ) ) {
			$localize['zip_ai_plugin_status']             = self::get_plugin_status( SPECTRA_BLOCKS_ZIP_AI_PLUGIN_FILE );
			$localize['zip_ai_plugin_installed']          = 'Install' !== self::get_plugin_status( SPECTRA_BLOCKS_ZIP_AI_PLUGIN_FILE );
			$localize['install_zip_ai_nonce']             = wp_create_nonce( 'spectra_blocks_install_zip_ai' );
			$localize['activate_zip_ai_nonce']            = wp_create_nonce( 'spectra_blocks_activate_zip_ai' );
			$localize['zip_ai_verify_authenticity_nonce'] = wp_create_nonce( 'spectra_blocks_zip_ai_verify_authenticity' );
			$localize['zip_ai_module_status_nonce']       = wp_create_nonce( 'spectra_blocks_zip_ai_module_status' );
			$localize['zip_ai_is_authorized']             = self::is_zip_ai_authorized();
			$localize['zip_ai_open_url']                  = admin_url( 'index.php?zipwp_open_assistant=1' );
		}

		// First register any pre-required scripts.
		do_action( 'spectra_admin_prerequisite_scripts' );
		// Then register the admin scripts.
		$this->settings_app_scripts( $localize );
	}


	/**
	 * Create an Array of Blocks info which we need to show in Admin dashboard.
	 */
	public function get_blocks_info_for_activation_deactivation() {

		$blocks = \Spectra_Blocks_Admin_Helper::get_block_options();

		array_multisort(
			array_map(
				function ( $element ) {
					if ( isset( $element['priority'] ) ) {
						return $element['priority'];
					}
				},
				$blocks
			),
			SORT_ASC,
			$blocks
		);

		$cf7_status = $this->get_plugin_status( 'contact-form-7/wp-contact-form-7.php' );
		$gf_status  = $this->get_plugin_status( 'gravityforms/gravityforms.php' );

		if ( is_array( $blocks ) && ! empty( $blocks ) ) {

			$blocks_names = array();

			foreach ( $blocks as $addon => $info ) {

				$addon = str_replace( 'spectra/', '', $addon );

				$exclude_blocks = array(
					'column',
					'icon-list-child',
					'social-share-child',
					'buttons-child',
					'faq-child',
					'forms-name',
					'forms-email',
					'forms-hidden',
					'forms-phone',
					'forms-textarea',
					'forms-url',
					'forms-select',
					'forms-radio',
					'forms-checkbox',
					'forms-upload',
					'forms-toggle',
					'forms-date',
					'forms-accept',
					'post-title',
					'post-image',
					'post-button',
					'post-excerpt',
					'post-taxonomy',
					'post-meta',
					'restaurant-menu-child',
					'content-timeline-child',
					'tabs-child',
					'how-to-step',
					'slider-child',
					'slider-pro',
					'image-gallery-pro',
					'loop-wrapper',
					'loop-search',
					'loop-sort',
					'loop-reset',
					'loop-pagination',
					'loop-category',
					'modal-pro',
					'countdown-pro',
					'wp-search',
					'columns',
					'section',
					'cf7-styler',
					'gf-styler',
					'post-masonry',
				);

				if ( ( 'cf7-styler' === $addon && 'active' !== $cf7_status ) || ( 'gf-styler' === $addon && 'active' !== $gf_status ) ) {
					$exclude_blocks[] = $addon;
				}

				if ( array_key_exists( 'extension', $info ) && $info['extension'] ) {
					continue;
				}

				if ( in_array( $addon, $exclude_blocks, true ) ) {
					continue;
				}
				$info['slug']   = $addon;
				$blocks_names[] = $info;

			}

			return $blocks_names;
		}

		return array();
	}

	/**
	 * Settings app scripts
	 *
	 * @param array $localize Variable names.
	 */
	public function settings_app_scripts( $localize ) {
		// Check if we're on the popup builder page.
		$current_screen = get_current_screen();
		if ( isset( $current_screen ) && 'spectra-blocks-popup' === $current_screen->post_type ) {
			return; // Don't load dashboard scripts on popup builder page.
		}

		$handle            = 'spectra-blocks-admin-settings';
		$build_path        = SPECTRA_BLOCKS_ADMIN_DIR . 'assets/build/';
		$build_url         = SPECTRA_BLOCKS_ADMIN_URL . 'assets/build/';
		$script_asset_path = $build_path . 'dashboard-app.asset.php';
		$script_info       = file_exists( $script_asset_path )
			? include $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => SPECTRA_BLOCKS_VER,
			);

		$script_dep = array_merge( $script_info['dependencies'], array( 'updates' ) );

		wp_register_script(
			$handle,
			$build_url . 'dashboard-app.js',
			$script_dep,
			$script_info['version'],
			true
		);

		wp_register_style(
			$handle,
			$build_url . 'dashboard-app.css',
			array(),
			SPECTRA_BLOCKS_VER
		);

		wp_enqueue_script( $handle );
		wp_set_script_translations( $handle, 'spectra-blocks', SPECTRA_BLOCKS_DIR . 'languages' );
		if ( isset( $_GET['page'] ) && 'spectra-blocks' === $_GET['page'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['page'] does not provide nonce.
			wp_enqueue_style( $handle );
		}
		wp_style_add_data( $handle, 'rtl', 'replace' );
		wp_localize_script( $handle, 'spectra_blocks_admin_react', $localize );
		wp_localize_script( $handle, 'spectra_blocks_react', $localize );

		$current_user = wp_get_current_user();

		$user_data = array(
			'isLoggedIn'  => is_user_logged_in(),
			'username'    => $current_user->user_login,
			'firstName'   => $current_user->first_name,
			'lastName'    => $current_user->last_name,
			'email'       => $current_user->user_email,
			'displayName' => $current_user->display_name,
		);

		wp_localize_script( $handle, 'spectra_blocks_user_data', $user_data );

		$plugins_data      = self::get_bsf_plugins();
		$json_plugins_data = wp_json_encode( $plugins_data );

		wp_localize_script( $handle, 'spectra_blocks_plugins_data', $plugins_data );
	}

	/**
	 * Rename the Ultimate Addons for Gutenberg admin menu to "Spectra Legacy"
	 * when both plugins are active simultaneously.
	 *
	 * @since 0.0.9
	 * @return void
	 */
	/**
	 * Check whether ZipWP AI is authorized via the ZIP-AI plugin.
	 *
	 * @since 0.0.9
	 * @return bool
	 */
	public static function is_zip_ai_authorized() {
		if ( class_exists( '\\ZipAI\\MCP\\Classes\\Core\\Helper' ) && method_exists( '\\ZipAI\\MCP\\Classes\\Core\\Helper', 'is_authorized' ) ) {
			return \ZipAI\MCP\Classes\Core\Helper::is_authorized();
		}
		return false;
	}

	/**
	 * Rename the classic Spectra admin menu item when UAGB is active.
	 *
	 * @since 0.0.9
	 * @return void
	 */
	public function rename_classic_spectra_menu() {
		if ( ! defined( 'UAGB_VER' ) ) {
			return;
		}

		global $menu, $submenu;

		foreach ( $menu as $key => $item ) {
			if ( isset( $item[2] ) && 'spectra' === $item[2] ) {
				$menu[ $key ][0] = __( 'Spectra Legacy', 'spectra-blocks' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				break;
			}
		}
	}
}

Admin_Menu::get_instance();
