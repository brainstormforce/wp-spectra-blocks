<?php
/**
 * Plugin loader for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin loader - initializes all infrastructure.
 */
class Spectra_Blocks_Loader {

	/**
	 * Initialize the plugin.
	 */
	public static function init() {
		self::define_constants();
		self::setup_hooks();
	}

	/**
	 * Load all plugin classes and features.
	 * Hooked to plugins_loaded to ensure all WP functions (wp_get_current_user, etc.) are available.
	 */
	public static function load_plugin() {
		self::load_infrastructure();
		self::load_admin();
		self::load_v3_core();
	}

	/**
	 * Define additional plugin constants.
	 */
	private static function define_constants() {
		define( 'SPECTRA_BLOCKS_BASE', plugin_basename( SPECTRA_BLOCKS_FILE ) );
		define( 'SPECTRA_BLOCKS_URI', trailingslashit( 'https://wpspectra.com/' ) );
		define( 'SPECTRA_BLOCKS_BREAKPOINT_MEDIUM', 1024 );
		define( 'SPECTRA_BLOCKS_BREAKPOINT_SMALL', 767 );
		define( 'SPECTRA_BLOCKS_TABLET_BREAKPOINT', SPECTRA_BLOCKS_BREAKPOINT_MEDIUM );
		define( 'SPECTRA_BLOCKS_MOBILE_BREAKPOINT', SPECTRA_BLOCKS_BREAKPOINT_SMALL );
	}

	/**
	 * Load infrastructure class files.
	 */
	private static function load_infrastructure() {
		$classes_dir = SPECTRA_BLOCKS_DIR . 'classes/';

		require_once $classes_dir . 'class-spectra-blocks-settings.php';
		require_once $classes_dir . 'class-spectra-blocks-filesystem.php';
		require_once $classes_dir . 'class-spectra-blocks-security-helper.php';
		require_once $classes_dir . 'class-spectra-blocks-helper.php';
		require_once $classes_dir . 'class-spectra-blocks-admin-helper.php';
		require_once $classes_dir . 'class-spectra-blocks-rest-api.php';

		Spectra_Blocks_Rest_Api::init();
		add_action( 'init', array( 'Spectra_Blocks_Helper', 'init' ) );

		// Lib wrappers.
		$lib_dir = SPECTRA_BLOCKS_DIR . 'lib/';
		if ( file_exists( $lib_dir . 'class-spectra-blocks-bsf-analytics.php' ) ) {
			require_once $lib_dir . 'class-spectra-blocks-bsf-analytics.php';
		}
		if ( file_exists( $lib_dir . 'class-spectra-blocks-zip-ai.php' ) ) {
			require_once $lib_dir . 'class-spectra-blocks-zip-ai.php';
		}
		if ( file_exists( $lib_dir . 'class-spectra-blocks-astra-notices.php' ) ) {
			require_once $lib_dir . 'class-spectra-blocks-astra-notices.php';
		}
		if ( file_exists( $lib_dir . 'class-spectra-blocks-nps-survey.php' ) ) {
			require_once $lib_dir . 'class-spectra-blocks-nps-survey.php';
		}
		if ( file_exists( $lib_dir . 'class-spectra-blocks-zipwp-images.php' ) ) {
			require_once $lib_dir . 'class-spectra-blocks-zipwp-images.php';
		}
		if ( file_exists( $lib_dir . 'class-spectra-blocks-ast-block-templates.php' ) ) {
			require_once $lib_dir . 'class-spectra-blocks-ast-block-templates.php';
		}
	}

	/**
	 * Load admin dashboard.
	 */
	private static function load_admin() {
		$admin_loader = SPECTRA_BLOCKS_DIR . 'admin/class-admin-loader.php';
		if ( file_exists( $admin_loader ) ) {
			require_once $admin_loader;
		}
	}

	/**
	 * Load the V3 core bootstrap.
	 */
	private static function load_v3_core() {
		require_once SPECTRA_BLOCKS_DIR . 'spectra-blocks-init.php';
	}

	/**
	 * Set up activation/deactivation hooks.
	 */
	private static function setup_hooks() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_plugin' ) );
		register_activation_hook( SPECTRA_BLOCKS_FILE, array( __CLASS__, 'on_activation' ) );
		register_deactivation_hook( SPECTRA_BLOCKS_FILE, array( __CLASS__, 'on_deactivation' ) );

		// Sync lib text domains to plugin slug for WP.org compliance.
		add_filter( 'zip_ai_library_textdomain', array( __CLASS__, 'sync_library_textdomain' ) );
	}

	/**
	 * Sync library text domains to the plugin text domain.
	 *
	 * @since x.x.x
	 * @param string $textdomain The library text domain.
	 * @return string The plugin text domain.
	 */
	public static function sync_library_textdomain( $textdomain ) {
		return 'spectra-blocks';
	}

	/**
	 * Plugin activation callback.
	 */
	public static function on_activation() {
		// Set default settings.
		if ( ! get_option( 'spectra_blocks_active_blocks' ) ) {
			update_option( 'spectra_blocks_active_blocks', array() );
		}
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation callback.
	 */
	public static function on_deactivation() {
		flush_rewrite_rules();
	}
}

Spectra_Blocks_Loader::init();
