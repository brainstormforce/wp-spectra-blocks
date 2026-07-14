<?php
/**
 * Standalone verification script for Spectra_Blocks_Daily_KPI_Counters.
 *
 * Stubs the minimal WordPress functions needed by the class, seeds dummy data,
 * then asserts the expected behaviour. Run with:
 *
 *   php tests/verify-kpi-counters.php
 *
 * Exit code 0 = all assertions passed.
 * Exit code 1 = one or more failures.
 *
 * @package SpectraBlocks
 */

define( 'ABSPATH', __DIR__ . '/' );

// ---------------------------------------------------------------------------
// Minimal WordPress function stubs
// ---------------------------------------------------------------------------

$_options    = array(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$_transients = array(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

function get_option( $key, $default = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $_options;
	return array_key_exists( $key, $_options ) ? $_options[ $key ] : $default;
}

function update_option( $key, $value, $autoload = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $_options;
	$_options[ $key ] = $value;
	return true;
}

function delete_option( $key ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $_options;
	unset( $_options[ $key ] );
	return true;
}

function get_transient( $key ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $_transients;
	return array_key_exists( $key, $_transients ) ? $_transients[ $key ] : false;
}

function set_transient( $key, $value, $expiry = 0 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $_transients;
	$_transients[ $key ] = $value;
	return true;
}

function delete_transient( $key ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $_transients;
	unset( $_transients[ $key ] );
	return true;
}

function absint( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	return abs( (int) $value );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

// Hook registry — so add_action/do_action work without full WP.
$_hooks = array(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

function add_action( $tag, $callback, $priority = 10, $args = 1 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $_hooks;
	$_hooks[ $tag ][ $priority ][] = array( 'callback' => $callback, 'args' => $args );
}

function do_action( $tag, ...$args ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $_hooks;
	if ( empty( $_hooks[ $tag ] ) ) {
		return;
	}
	ksort( $_hooks[ $tag ] );
	foreach ( $_hooks[ $tag ] as $priority => $callbacks ) {
		foreach ( $callbacks as $cb ) {
			call_user_func_array( $cb['callback'], array_slice( $args, 0, $cb['args'] ) );
		}
	}
}

function wp_is_post_autosave( $post_id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	return false;
}

function wp_is_post_revision( $post_id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	return false;
}

// ---------------------------------------------------------------------------
// Load class under test
// ---------------------------------------------------------------------------

require_once dirname( __DIR__ ) . '/classes/class-spectra-blocks-daily-kpi-counters.php';

// ---------------------------------------------------------------------------
// Tiny test harness
// ---------------------------------------------------------------------------

$pass    = 0;
$fail    = 0;
$results = array();

/**
 * Assert two values are strictly equal.
 *
 * @param string $label Test label.
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value.
 */
function assert_eq( $label, $expected, $actual ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $pass, $fail, $results;
	if ( $expected === $actual ) {
		$results[] = "  PASS  {$label}";
		++$pass;
	} else {
		$exp_str   = json_encode( $expected );
		$act_str   = json_encode( $actual );
		$results[] = "  FAIL  {$label}\n        expected: {$exp_str}\n        actual  : {$act_str}";
		++$fail;
	}
}

/**
 * Assert value is true.
 *
 * @param string $label  Test label.
 * @param mixed  $actual Actual value.
 */
function assert_true( $label, $actual ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	assert_eq( $label, true, (bool) $actual );
}

/**
 * Assert array contains value.
 *
 * @param string $label  Test label.
 * @param mixed  $needle Value to find.
 * @param array  $haystack Array to search.
 */
function assert_in( $label, $needle, $haystack ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	assert_true( $label, in_array( $needle, (array) $haystack, true ) );
}

/**
 * Assert array does NOT contain key.
 *
 * @param string $label Test label.
 * @param string $key   Key to check.
 * @param array  $arr   Array to inspect.
 */
function assert_not_key( $label, $key, $arr ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	assert_true( $label, ! array_key_exists( $key, (array) $arr ) );
}

/**
 * Reset singleton + all options + hooks between test groups.
 */
function reset_kpi() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $_options, $_transients, $_hooks;
	$_options    = array();
	$_transients = array();
	$_hooks      = array();

	// PHP 8.1+ private properties are accessible without setAccessible().
	$ref  = new ReflectionClass( 'Spectra_Blocks_Daily_KPI_Counters' );
	$prop = $ref->getProperty( 'instance' );
	$prop->setValue( null, null );
}

// ---------------------------------------------------------------------------
// Helpers that simulate WP hooks manually (class registers via add_action,
// but we don't have WP here — so call public methods directly).
// ---------------------------------------------------------------------------

/**
 * Helper: simulate transition_post_status for a new publish.
 *
 * @param Spectra_Blocks_Daily_KPI_Counters $kpi     KPI instance.
 * @param string                            $content Post content.
 */
function simulate_publish( $kpi, $content ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	$post              = new stdClass();
	$post->post_content = $content;
	$post->post_status  = 'publish';
	$kpi->on_transition_post_status( 'publish', 'draft', $post );
}

/**
 * Helper: simulate save_post for a published post.
 *
 * @param Spectra_Blocks_Daily_KPI_Counters $kpi     KPI instance.
 * @param int                               $post_id Post ID.
 * @param string                            $content Post content.
 */
function simulate_save( $kpi, $post_id, $content ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	$post              = new stdClass();
	$post->ID           = $post_id;
	$post->post_content = $content;
	$post->post_status  = 'publish';
	$kpi->on_save_post_record_block_types( $post_id, $post );
}

// ===========================================================================
// Test group 1: Publish counting
// ===========================================================================

echo "\n--- Publish counting ---\n";
reset_kpi();
$kpi   = Spectra_Blocks_Daily_KPI_Counters::get_instance();
$today = gmdate( 'Y-m-d' );

simulate_publish( $kpi, '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->' );
$data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, 7 );
assert_eq( 'publish increments to 1', 1, $data[ $today ] ?? 0 );

simulate_publish( $kpi, '<!-- wp:spectra/content --><p></p><!-- /wp:spectra/content -->' );
$data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, 7 );
assert_eq( 'second publish increments to 2', 2, $data[ $today ] ?? 0 );

// Non-spectra post — count should not change.
simulate_publish( $kpi, '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->' );
$data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, 7 );
assert_eq( 'non-spectra publish does not increment', 2, $data[ $today ] ?? 0 );

// Same status re-publish — should not increment.
$post              = new stdClass();
$post->post_content = '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->';
$post->post_status  = 'publish';
$kpi->on_transition_post_status( 'publish', 'publish', $post );
$data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, 7 );
assert_eq( 're-save of published post does not increment', 2, $data[ $today ] ?? 0 );

// ===========================================================================
// Test group 2: Block-type recording
// ===========================================================================

echo "\n--- Block-type recording ---\n";
reset_kpi();
$kpi   = Spectra_Blocks_Daily_KPI_Counters::get_instance();
$today = gmdate( 'Y-m-d' );

simulate_save( $kpi, 1, '<!-- wp:spectra/container --><div><!-- wp:spectra/content --><p></p><!-- /wp:spectra/content --></div><!-- /wp:spectra/container -->' );
$data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES, 7 );
assert_in( 'spectra/container recorded', 'spectra/container', $data[ $today ] ?? array() );
assert_in( 'spectra/content recorded', 'spectra/content', $data[ $today ] ?? array() );

// Second save with same blocks — should remain deduplicated.
simulate_save( $kpi, 2, '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->' );
$data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES, 7 );
$count_container = count( array_filter( $data[ $today ] ?? array(), fn( $v ) => 'spectra/container' === $v ) );
assert_eq( 'spectra/container not duplicated', 1, $count_container );

// Draft save — should not record.
$post              = new stdClass();
$post->ID           = 99;
$post->post_content = '<!-- wp:spectra/image --><img><!-- /wp:spectra/image -->';
$post->post_status  = 'draft';
$kpi->on_save_post_record_block_types( 99, $post );
$data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES, 7 );
$before = $data[ $today ] ?? array();
assert_true( 'draft save does not add spectra/image', ! in_array( 'spectra/image', $before, true ) );

// ===========================================================================
// Test group 3: Advanced feature detection
// ===========================================================================

echo "\n--- Advanced feature detection ---\n";
reset_kpi();
$kpi   = Spectra_Blocks_Daily_KPI_Counters::get_instance();
$today = gmdate( 'Y-m-d' );

// Popup.
simulate_publish( $kpi, '<!-- wp:spectra/popup {"popupId":"p1"} --><div></div><!-- /wp:spectra/popup -->' );
$data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED, 7 );
assert_in( 'popup feature detected', 'popup', $data[ $today ] ?? array() );

// Dynamic content.
simulate_publish( $kpi, '<!-- wp:spectra/content {"dynamicContent":true,"textSource":"post-meta"} --><p></p><!-- /wp:spectra/content -->' );
$data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED, 7 );
assert_in( 'dynamic_content feature detected', 'dynamic_content', $data[ $today ] ?? array() );

// GBS via update_option hook.
$kpi->on_gbs_changed();
$data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED, 7 );
assert_in( 'gbs feature detected via on_gbs_changed', 'gbs', $data[ $today ] ?? array() );

// Dedup — second GBS change should not add duplicate.
$kpi->on_gbs_changed();
$data      = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED, 7 );
$gbs_count = count( array_filter( $data[ $today ] ?? array(), fn( $v ) => 'gbs' === $v ) );
assert_eq( 'gbs not duplicated in same day', 1, $gbs_count );

// ===========================================================================
// Test group 4: Bucket pruning and windowing
// ===========================================================================

echo "\n--- Bucket pruning ---\n";
reset_kpi();
$kpi = Spectra_Blocks_Daily_KPI_Counters::get_instance();

$stale = gmdate( 'Y-m-d', strtotime( '-10 days' ) );
$fresh = gmdate( 'Y-m-d', strtotime( '-2 days' ) );
update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, array( $stale => 5, $fresh => 2 ) );

// Trigger a new increment which runs prune().
simulate_publish( $kpi, '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->' );

$raw = get_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH );
assert_not_key( 'stale entry pruned from option', $stale, $raw );
assert_true( 'fresh entry retained after prune', isset( $raw[ $fresh ] ) );

// get_last_n_days respects window.
reset_kpi();
$kpi    = Spectra_Blocks_Daily_KPI_Counters::get_instance();
$today  = gmdate( 'Y-m-d' );
$old    = gmdate( 'Y-m-d', strtotime( '-8 days' ) );
$recent = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, array( $today => 1, $recent => 2, $old => 99 ) );

$window = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, 7 );
assert_true( 'today in window', isset( $window[ $today ] ) );
assert_true( 'recent in window', isset( $window[ $recent ] ) );
assert_not_key( '8-day-old entry excluded from window', $old, $window );

// ===========================================================================
// Test group 5: pages_with_spectra (transient cache)
// ===========================================================================

echo "\n--- pages_with_spectra cache ---\n";
reset_kpi();
$kpi = Spectra_Blocks_Daily_KPI_Counters::get_instance();

// Seed a known cache value — the DB query is skipped.
set_transient( Spectra_Blocks_Daily_KPI_Counters::TRANSIENT_PAGES_COUNT, 17 );
assert_eq( 'cached value returned without DB query', 17, $kpi->get_pages_with_spectra() );

// After clearing cache, method falls through to wpdb query (which is stubbed — will return 0 via null).
// We cannot run a real DB query here, but we verify it tries and returns an integer.
delete_transient( Spectra_Blocks_Daily_KPI_Counters::TRANSIENT_PAGES_COUNT );
// Stub global $wpdb.
$wpdb          = new stdClass();
$wpdb->posts   = 'wp_posts';
$wpdb->last_error = '';
$wpdb->get_var = static function () { return '3'; }; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// Monkey-patch: since wpdb->get_var is called as a method, use a mock.
$GLOBALS['wpdb'] = new class() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	public $posts = 'wp_posts';
	public function get_var( $sql ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return '3';
	}
};
$count = $kpi->get_pages_with_spectra();
assert_eq( 'DB count returned as int and cached', 3, $count );
assert_eq( 'result cached in transient', 3, get_transient( Spectra_Blocks_Daily_KPI_Counters::TRANSIENT_PAGES_COUNT ) );

// ===========================================================================
// Test group 6: kpi_records payload shape
// ===========================================================================

echo "\n--- kpi_records payload ---\n";
reset_kpi();
$kpi       = Spectra_Blocks_Daily_KPI_Counters::get_instance();
$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
$today_str = gmdate( 'Y-m-d' );

update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH,     array( $yesterday => 5, $today_str => 2 ) );
update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES, array( $yesterday => array( 'spectra/container', 'spectra/content', 'spectra/image' ) ) );
update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED,    array( $yesterday => array( 'gbs', 'popup' ) ) );

// Call get_kpi_tracking_data via reflection (private static method in loader — replicate the logic here).
$publish_data    = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH,     Spectra_Blocks_Daily_KPI_Counters::RETENTION_DAYS );
$block_type_data = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES, Spectra_Blocks_Daily_KPI_Counters::RETENTION_DAYS );
$advanced_data   = $kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED,    Spectra_Blocks_Daily_KPI_Counters::RETENTION_DAYS );

$all_dates = array_unique( array_merge( array_keys( $publish_data ), array_keys( $block_type_data ), array_keys( $advanced_data ) ) );

$records = array();
foreach ( $all_dates as $date ) {
	if ( $date === $today_str ) {
		continue;
	}
	$records[ $date ] = array(
		'date'           => $date,
		'numeric_values' => array(
			'spectra_posts_published_daily'        => isset( $publish_data[ $date ] ) ? (int) $publish_data[ $date ] : 0,
			'spectra_distinct_block_types_daily'   => isset( $block_type_data[ $date ] ) && is_array( $block_type_data[ $date ] ) ? count( $block_type_data[ $date ] ) : 0,
			'spectra_advanced_features_used_daily' => isset( $advanced_data[ $date ] ) && is_array( $advanced_data[ $date ] ) ? count( $advanced_data[ $date ] ) : 0,
		),
	);
}

assert_true( 'yesterday record present', isset( $records[ $yesterday ] ) );
assert_true( 'today record excluded', ! isset( $records[ $today_str ] ) );
assert_eq( 'published count correct', 5, $records[ $yesterday ]['numeric_values']['spectra_posts_published_daily'] );
assert_eq( 'distinct block types correct', 3, $records[ $yesterday ]['numeric_values']['spectra_distinct_block_types_daily'] );
assert_eq( 'advanced features count correct', 2, $records[ $yesterday ]['numeric_values']['spectra_advanced_features_used_daily'] );

// ===========================================================================
// Summary
// ===========================================================================

echo "\n";
foreach ( $results as $line ) {
	echo $line . "\n";
}

$total = $pass + $fail;
echo "\n{$pass}/{$total} assertions passed";

if ( $fail > 0 ) {
	echo " — {$fail} FAILED\n";
	exit( 1 );
}

echo " — all green\n";
exit( 0 );
