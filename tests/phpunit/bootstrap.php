<?php
/**
 * PHPUnit bootstrap for Spectra Blocks.
 *
 * @package SpectraBlocks
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( "$_tests_dir/includes/functions.php" ) ) {
	echo "Could not find $_tests_dir/includes/functions.php\n";
	exit( 1 );
}

// Load WP test functions.
require_once "$_tests_dir/includes/functions.php";

// Load plugin.
function _register_spectra_blocks() {
	require SPECTRA_BLOCKS_DIR . 'spectra-blocks.php';
}
tests_add_filter( 'muplugins_loaded', '_register_spectra_blocks' );

// Start WP.
require "$_tests_dir/includes/bootstrap.php";
