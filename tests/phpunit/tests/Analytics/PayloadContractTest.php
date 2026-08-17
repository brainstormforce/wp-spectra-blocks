<?php
/**
 * Contract tests for the Spectra Blocks analytics payload.
 *
 * The bsf_core_stats payload this plugin emits is ingested by the same backend
 * pipeline as the legacy Spectra plugin, so it must match the legacy shape:
 *
 * - `kpi_records` is a DATE-KEYED MAP (not a sequential list), each entry a
 *   `numeric_values` array with the three daily KPI names.
 * - `events_record` is a LIST of `{event_name, event_value, properties, date}`.
 * - Nothing is contributed unless the site has opted in.
 *
 * @package SpectraBlocks\Tests\Analytics
 */

/**
 * Validates the emitted payload contract end-to-end via the bsf_core_stats filter.
 */
class PayloadContractTest extends WP_UnitTestCase {

	/**
	 * Set up: clean the accumulators + event queue and opt in by default.
	 */
	public function set_up() {
		parent::set_up();

		delete_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH );
		delete_option( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES );
		delete_option( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED );
		delete_option( \Spectra\Analytics\Events::PENDING_OPTION );
		delete_option( \Spectra\Analytics\Events::PUSHED_OPTION );

		update_site_option( 'spectra_blocks_usage_optin', 'yes' );
	}

	/**
	 * kpi_records must be a date-keyed associative map matching the legacy shape.
	 */
	public function test_kpi_records_is_date_keyed_map() {
		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
		update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, array( $yesterday => 3 ) );

		$stats       = apply_filters( 'bsf_core_stats', array() );
		$kpi_records = $stats['plugin_data']['spectra_blocks']['kpi_records'] ?? array();

		$this->assertArrayHasKey( $yesterday, $kpi_records, 'kpi_records must be keyed by date.' );
		$this->assertArrayNotHasKey( 0, $kpi_records, 'kpi_records must NOT be a sequential list.' );
		$this->assertArrayNotHasKey( 'date', $kpi_records[ $yesterday ], 'No inline date key in the legacy map shape.' );
		$this->assertArrayHasKey( 'numeric_values', $kpi_records[ $yesterday ] );

		$nv = $kpi_records[ $yesterday ]['numeric_values'];
		$this->assertArrayHasKey( 'spectra_posts_published_daily', $nv );
		$this->assertArrayHasKey( 'spectra_distinct_block_types_daily', $nv );
		$this->assertArrayHasKey( 'spectra_advanced_features_used_daily', $nv );
		$this->assertSame( 3, $nv['spectra_posts_published_daily'] );
	}

	/**
	 * events_record must be a list of event objects with the legacy keys.
	 */
	public function test_events_record_shape() {
		\Spectra\Analytics\Events::track( 'plugin_activated', '1.0.0', array( 'source' => 'self' ) );

		$stats  = apply_filters( 'bsf_core_stats', array() );
		$events = $stats['plugin_data']['spectra_blocks']['events_record'] ?? array();

		$this->assertNotEmpty( $events, 'events_record should contain the queued event.' );
		$this->assertArrayHasKey( 0, $events, 'events_record must be a sequential list.' );

		$first = $events[0];
		$this->assertArrayHasKey( 'event_name', $first );
		$this->assertArrayHasKey( 'event_value', $first );
		$this->assertArrayHasKey( 'properties', $first );
		$this->assertSame( 'plugin_activated', $first['event_name'] );
	}

	/**
	 * When opted out, neither KPIs nor events may be contributed to the payload.
	 */
	public function test_payload_gated_on_optin() {
		update_site_option( 'spectra_blocks_usage_optin', 'no' );

		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
		update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, array( $yesterday => 3 ) );
		\Spectra\Analytics\Events::track( 'plugin_activated', '1.0.0' );

		$stats  = apply_filters( 'bsf_core_stats', array() );
		$bucket = $stats['plugin_data']['spectra_blocks'] ?? array();

		$this->assertArrayNotHasKey( 'kpi_records', $bucket, 'KPI records must not be sent when opted out.' );
		$this->assertArrayNotHasKey( 'events_record', $bucket, 'Events must not be sent when opted out.' );
	}
}
