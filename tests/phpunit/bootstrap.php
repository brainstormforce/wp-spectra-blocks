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
$_wp_test_dir = getenv( 'WP_PHPUNIT__DIR' ) ?: ( getenv( 'WP_DEVELOP_DIR' ) ? getenv( 'WP_DEVELOP_DIR' ) . '/src' : '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention
$_wp_install_dir = getenv( 'WP_TESTS_INSTALLATION' ) ?: ( rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PHPUnit bootstrap convention
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
		global $_spectra_test_registered_abilities;
		$_spectra_test_registered_abilities[ $name ] = $args;
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
		global $_spectra_test_registered_ability_categories;
		$_spectra_test_registered_ability_categories[ $slug ] = $args;
	}
}

unset( $_wp_test_dir, $_wp_install_dir, $_wp_has_abilities_api );

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
