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

		// Shared BSF libraries — use class_exists / global version negotiation
		// to avoid conflicts when UAGB (or another BSF plugin) is also active.
		$lib_dir = SPECTRA_BLOCKS_DIR . 'lib/';

		// BSF Analytics.
		if ( ! class_exists( 'BSF_Analytics_Loader' ) && file_exists( $lib_dir . 'bsf-analytics/class-bsf-analytics-loader.php' ) ) {
			require_once $lib_dir . 'bsf-analytics/class-bsf-analytics-loader.php';
		}

		// Zip AI — global version negotiation via $zip_ai_version / $zip_ai_path.
		self::load_versioned_lib( $lib_dir . 'zip-ai/version.json', 'zip-ai', $lib_dir . 'zip-ai/zip-ai.php', 'plugins_loaded', 15 );

		// Astra Notices.
		if ( file_exists( $lib_dir . 'astra-notices/class-bsf-admin-notices.php' ) ) {
			require_once $lib_dir . 'astra-notices/class-bsf-admin-notices.php';
		}

		// One Onboarding library.
		if ( ! defined( 'ONE_ONBOARDING_FILE' ) && file_exists( $lib_dir . 'one-onboarding/one-onboarding.php' ) ) {
			require_once $lib_dir . 'one-onboarding/one-onboarding.php';
		}

		// NPS Survey — global version negotiation via $nps_survey_version / $nps_survey_init.
		self::load_versioned_lib( $lib_dir . 'nps-survey/version.json', 'nps-survey', $lib_dir . 'nps-survey/nps-survey.php', 'init', 999 );

		// ZipWP Images — global version negotiation via $zipwp_images_version / $zipwp_images_init.
		self::load_versioned_lib( $lib_dir . 'zipwp-images/version.json', 'zipwp-images', $lib_dir . 'zipwp-images/zipwp-images.php', 'init' );

		// Gutenberg Templates (Design Library).
		// Register the disable filter before the GT entry point loads at init:999.
		// When the "Enable Templates Button" setting is off, the Design Library
		// button should not appear in the editor at all.
		add_filter(
			'ast_block_templates_disable',
			static function ( $disabled ) {
				if ( $disabled ) {
					return true;
				}
				return 'no' === \Spectra_Blocks_Admin_Helper::get_admin_settings_option( 'spectra_blocks_enable_templates_button', 'yes' );
			}
		);

		if ( file_exists( $lib_dir . 'class-spectra-blocks-ast-block-templates.php' ) ) {
			require_once $lib_dir . 'class-spectra-blocks-ast-block-templates.php';
		}

		// Load learn actions for block editor guided steps.
		require_once $classes_dir . 'class-spectra-blocks-learn-actions.php';

		// Load learn actions for admin dashboard guided tooltips.
		require_once $classes_dir . 'class-spectra-blocks-admin-learn-actions.php';

		// Daily KPI counters for BSF Analytics payload.
		require_once $classes_dir . 'class-spectra-blocks-daily-kpi-counters.php';
		Spectra_Blocks_Daily_KPI_Counters::get_instance();

		// Onboarding wizard.
		require_once $classes_dir . 'class-spectra-blocks-onboarding.php';
		Spectra_Blocks_Onboarding::get_instance();

		// Load Abilities Manager (WordPress Abilities API / MCP integration).
		require_once $classes_dir . 'abilities/class-spectra-blocks-abilities-manager.php';
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
	 * @since 1.0.0
	 * @param string $textdomain The library text domain.
	 * @return string The plugin text domain.
	 */
	public static function sync_library_textdomain( $textdomain ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
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

		// Bump the Global Styles JIT version stamp so every per-post cached
		// stylesheet recompiles under the current compiler — necessary after
		// a compiler upgrade that changes selector-escaping, breakpoint
		// semantics, or the utility grammar (GIT-106 cutover).
		if ( class_exists( '\\SpectraBlocks\\GlobalStyles\\JitCache' ) ) {
			\SpectraBlocks\GlobalStyles\JitCache::bump_version();
		}

		update_option( '__spectra_blocks_do_redirect', true );
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation callback.
	 */
	public static function on_deactivation() {
		flush_rewrite_rules();
	}

	/**
	 * Load a BSF library that uses global version negotiation.
	 *
	 * Multiple plugins may bundle the same library. Each registers its
	 * version via a global variable; the highest version wins and its
	 * entry-point is included on the specified hook.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version_file Absolute path to the library's version.json.
	 * @param string $lib_key      Key inside version.json (e.g. 'zip-ai').
	 * @param string $entry_file   Absolute path to the library's main PHP file.
	 * @param string $hook         WordPress hook to load on (e.g. 'init').
	 * @param int    $priority     Hook priority.
	 * @return void
	 */
	private static function load_versioned_lib( $version_file, $lib_key, $entry_file, $hook = 'init', $priority = 10 ) {
		$version_file = realpath( $version_file );
		if ( ! $version_file || ! is_file( $version_file ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$file_data = json_decode( file_get_contents( $version_file ), true );
		$version   = isset( $file_data[ $lib_key ] ) ? $file_data[ $lib_key ] : '0';
		$path      = realpath( $entry_file );

		// Global variable names follow BSF convention: $<lib_key>_version, $<lib_key>_path (or _init).
		$var_version = str_replace( '-', '_', $lib_key ) . '_version';
		$var_path    = str_replace( '-', '_', $lib_key ) . '_init';

		global $$var_version, $$var_path;

		if ( null === $$var_version ) {
			$$var_version = '0'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- BSF shared-lib version negotiation uses library-owned globals.
		}

		if ( version_compare( $version, $$var_version, '>=' ) ) {
			$$var_version = $version; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- BSF shared-lib version negotiation uses library-owned globals.
			$$var_path    = $path; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- BSF shared-lib version negotiation uses library-owned globals.
		}

		add_action(
			$hook,
			static function () use ( $var_path ) {
				global $$var_path;
				if ( ! empty( $$var_path ) && is_file( realpath( $$var_path ) ) ) {
					include_once realpath( $$var_path );
				}
			},
			$priority
		);
	}
}

Spectra_Blocks_Loader::init();
