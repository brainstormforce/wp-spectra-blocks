<?php
/**
 * PHPUnit bootstrap for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention
}

// Load PHPUnit Polyfills if path is provided (required by WP test suite with PHPUnit 10+).
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- PHPUnit bootstrap convention
}

if ( ! file_exists( "$_tests_dir/includes/functions.php" ) ) {
	echo "Could not find $_tests_dir/includes/functions.php\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- test output
	exit( 1 );
}

// Plugin root directory.
$_plugin_dir = dirname( __DIR__, 2 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention

// Load WP test functions.
require_once "$_tests_dir/includes/functions.php";

// Stub WordPress Abilities API functions when running on WP < 6.8.
// Check if the WP test installation provides the Abilities API file to avoid redeclaration.
$_wp_phpunit_dir       = getenv( 'WP_PHPUNIT__DIR' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention
$_wp_develop_dir       = getenv( 'WP_DEVELOP_DIR' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention
$_wp_test_dir          = $_wp_phpunit_dir ? $_wp_phpunit_dir : ( $_wp_develop_dir ? $_wp_develop_dir . '/src' : '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention
$_wp_tests_install     = getenv( 'WP_TESTS_INSTALLATION' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention
$_wp_install_dir       = $_wp_tests_install ? $_wp_tests_install : ( rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention
$_wp_has_abilities_api = file_exists( "$_wp_install_dir/wp-includes/abilities-api.php" ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention

if ( ! $_wp_has_abilities_api && ! function_exists( 'wp_register_ability' ) ) {
	$_spectra_test_registered_abilities = array(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- test stub

	/**
	 * Stub for wp_register_ability.
	 *
	 * @param string $name Ability name.
	 * @param array  $args Ability arguments.
	 * @return void
	 */
	function wp_register_ability( $name, $args = array() ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- WP API stub
		global $_spectra_test_registered_abilities; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- test stub
		$_spectra_test_registered_abilities[ $name ] = $args; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- test stub
	}
}

if ( ! $_wp_has_abilities_api && ! function_exists( 'wp_register_ability_category' ) ) {
	$_spectra_test_registered_ability_categories = array(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- test stub

	/**
	 * Stub for wp_register_ability_category.
	 *
	 * @param string $slug Category slug.
	 * @param array  $args Category arguments.
	 * @return void
	 */
	function wp_register_ability_category( $slug, $args = array() ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- WP API stub
		global $_spectra_test_registered_ability_categories; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- test stub
		$_spectra_test_registered_ability_categories[ $slug ] = $args; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- test stub
	}
}

unset( $_wp_phpunit_dir, $_wp_develop_dir, $_wp_test_dir, $_wp_tests_install, $_wp_install_dir, $_wp_has_abilities_api );

// Enable the Abilities API for testing so AbilitiesManager hooks into wp_abilities_api_init.
// Use pre_option_ so the filter fires before the DB lookup — the option won't exist in the
// test DB, so option_ would never trigger and the toggle guard would block registration.
tests_add_filter(
	'muplugins_loaded',
	function () {
		add_filter(
			'pre_option_spectra_blocks_enable_abilities',
			function () {
				return 'enabled';
			}
		);
	},
	9
);

// Load plugin.
/**
 * Register the Spectra Blocks plugin for testing.
 *
 * @since x.x.x
 * @return void
 */
function _register_spectra_blocks() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- PHPUnit bootstrap convention
	require dirname( __DIR__, 2 ) . '/spectra-blocks.php';
}
tests_add_filter( 'muplugins_loaded', '_register_spectra_blocks' );

// Start WP.
require "$_tests_dir/includes/bootstrap.php";
