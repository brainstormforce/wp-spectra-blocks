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

// Load WP test functions.
require_once "$_tests_dir/includes/functions.php";

// Load plugin.
/**
 * Register the Spectra Blocks plugin for testing.
 *
 * @since x.x.x
 * @return void
 */
function _register_spectra_blocks() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- PHPUnit bootstrap convention
	require SPECTRA_BLOCKS_DIR . 'spectra-blocks.php';
}
tests_add_filter( 'muplugins_loaded', '_register_spectra_blocks' );

// Start WP.
require "$_tests_dir/includes/bootstrap.php";
