<?php
/**
 * Tests for Spectra_Blocks_Daily_KPI_Counters.
 *
 * @package SpectraBlocks\Tests\Analytics
 */

/**
 * Unit tests for the daily KPI accumulator class.
 *
 * Covers:
 * - publish-count increment on transition_post_status
 * - block-type recording on save_post
 * - advanced feature recording (GBS, popup, dynamic content)
 * - rolling bucket pruning
 * - pages_with_spectra DB count
 * - kpi_records payload shape in get_specific_stats
 */
class DailyKpiCountersTest extends WP_UnitTestCase {

	/**
	 * @var Spectra_Blocks_Daily_KPI_Counters
	 */
	private $kpi;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();

		// The plugin bootstrap (muplugins_loaded) already instantiated the singleton
		// and registered its WP hooks. Re-instantiating here would register duplicate
		// hooks and cause double-counting. We must reuse the existing instance and
		// only clear the option data between tests.
		delete_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH );
		delete_option( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES );
		delete_option( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED );
		delete_option( 'spectra_blocks_pro_gs_user_css' );
		delete_transient( Spectra_Blocks_Daily_KPI_Counters::TRANSIENT_PAGES_COUNT );

		$this->kpi = Spectra_Blocks_Daily_KPI_Counters::get_instance();

		// add_kpi_stats() now requires usage opt-in before contributing to the payload.
		update_site_option( 'spectra_blocks_usage_optin', 'yes' );
	}

	// -------------------------------------------------------------------------
	// Publish counting
	// -------------------------------------------------------------------------

	/**
	 * Publishing a post with a Spectra block should increment today's count.
	 */
	public function test_publish_count_increments_on_transition() {
		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->',
			'post_status'  => 'draft',
		) );

		wp_publish_post( $post_id );

		$today = gmdate( 'Y-m-d' );
		$data  = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, 7 );

		$this->assertArrayHasKey( $today, $data );
		$this->assertSame( 1, $data[ $today ] );
	}

	/**
	 * Publishing a post WITHOUT Spectra blocks should not touch the counter.
	 */
	public function test_publish_count_ignores_non_spectra_posts() {
		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->',
			'post_status'  => 'draft',
		) );

		wp_publish_post( $post_id );

		$data = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, 7 );
		$this->assertEmpty( $data );
	}

	/**
	 * Re-publishing an already-published post should not double-count.
	 */
	public function test_publish_count_skips_already_published_resave() {
		// Create as draft so the factory does not fire a publish transition.
		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:spectra/content --><h2>Hi</h2><!-- /wp:spectra/content -->',
			'post_status'  => 'draft',
		) );

		// Clear options to ensure a clean baseline before the assertion.
		delete_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH );

		// Simulate re-saving an already-published post (publish → publish, same status).
		$post              = get_post( $post_id );
		$post->post_status = 'publish';
		do_action( 'transition_post_status', 'publish', 'publish', $post );

		$data = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, 7 );
		$this->assertEmpty( $data );
	}

	/**
	 * Multiple publishes on the same day should accumulate.
	 */
	public function test_publish_count_accumulates_multiple_publishes() {
		for ( $i = 0; $i < 3; $i++ ) {
			$post_id = self::factory()->post->create( array(
				'post_content' => '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->',
				'post_status'  => 'draft',
			) );
			wp_publish_post( $post_id );
		}

		$today = gmdate( 'Y-m-d' );
		$data  = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, 7 );

		$this->assertSame( 3, $data[ $today ] );
	}

	// -------------------------------------------------------------------------
	// Block-type recording
	// -------------------------------------------------------------------------

	/**
	 * Saving a published post should record its Spectra block types.
	 */
	public function test_block_types_recorded_on_save() {
		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:spectra/container --><div><!-- wp:spectra/content --><p></p><!-- /wp:spectra/content --></div><!-- /wp:spectra/container -->',
			'post_status'  => 'publish',
		) );

		// Trigger save_post at priority 20 manually.
		do_action( 'save_post', $post_id, get_post( $post_id ) );

		$today = gmdate( 'Y-m-d' );
		$data  = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES, 7 );

		$this->assertArrayHasKey( $today, $data );
		$this->assertContains( 'spectra/container', $data[ $today ] );
		$this->assertContains( 'spectra/content', $data[ $today ] );
	}

	/**
	 * Block types should be deduplicated within the same day.
	 */
	public function test_block_types_deduped_per_day() {
		$content = '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->';

		for ( $i = 0; $i < 3; $i++ ) {
			$post_id = self::factory()->post->create( array(
				'post_content' => $content,
				'post_status'  => 'publish',
			) );
			do_action( 'save_post', $post_id, get_post( $post_id ) );
		}

		$today = gmdate( 'Y-m-d' );
		$data  = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES, 7 );

		$this->assertCount( 1, $data[ $today ] );
		$this->assertSame( array( 'spectra/container' ), $data[ $today ] );
	}

	/**
	 * Draft saves should not record block types.
	 */
	public function test_block_types_ignored_for_drafts() {
		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->',
			'post_status'  => 'draft',
		) );

		do_action( 'save_post', $post_id, get_post( $post_id ) );

		$data = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES, 7 );
		$this->assertEmpty( $data );
	}

	// -------------------------------------------------------------------------
	// Advanced feature detection
	// -------------------------------------------------------------------------

	/**
	 * Publishing a post with a spectra/popup block records 'popup'.
	 */
	public function test_advanced_feature_popup_detected() {
		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:spectra/popup {"popupId":"x"} --><div></div><!-- /wp:spectra/popup -->',
			'post_status'  => 'draft',
		) );

		wp_publish_post( $post_id );

		$today = gmdate( 'Y-m-d' );
		$data  = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED, 7 );

		$this->assertArrayHasKey( $today, $data );
		$this->assertContains( 'popup', $data[ $today ] );
	}

	/**
	 * Publishing a post with dynamic content attributes records 'dynamic_content'.
	 */
	public function test_advanced_feature_dynamic_content_detected() {
		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:spectra/content {"dynamicContent":true,"textSource":"post-meta"} --><p></p><!-- /wp:spectra/content -->',
			'post_status'  => 'draft',
		) );

		wp_publish_post( $post_id );

		$today = gmdate( 'Y-m-d' );
		$data  = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED, 7 );

		$this->assertArrayHasKey( $today, $data );
		$this->assertContains( 'dynamic_content', $data[ $today ] );
	}

	/**
	 * Updating the GBS option records 'gbs' advanced feature.
	 */
	public function test_advanced_feature_gbs_on_update_option() {
		update_option( 'spectra_blocks_pro_gs_user_css', array( 'color' => '#fff' ) );

		$today = gmdate( 'Y-m-d' );
		$data  = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED, 7 );

		$this->assertArrayHasKey( $today, $data );
		$this->assertContains( 'gbs', $data[ $today ] );
	}

	/**
	 * Advanced features should be deduplicated within a day.
	 */
	public function test_advanced_features_deduped_per_day() {
		update_option( 'spectra_blocks_pro_gs_user_css', array( 'color' => '#fff' ) );
		update_option( 'spectra_blocks_pro_gs_user_css', array( 'color' => '#000' ) );

		$today = gmdate( 'Y-m-d' );
		$data  = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED, 7 );

		$gbs_entries = array_filter( $data[ $today ], fn( $v ) => 'gbs' === $v );
		$this->assertCount( 1, $gbs_entries );
	}

	// -------------------------------------------------------------------------
	// Bucket pruning
	// -------------------------------------------------------------------------

	/**
	 * Entries older than RETENTION_DAYS should be pruned after a new accumulate.
	 */
	public function test_old_entries_pruned_after_accumulate() {
		$stale_date = gmdate( 'Y-m-d', strtotime( '-10 days' ) );

		// Manually seed a stale entry.
		update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, array( $stale_date => 5 ) );

		// Trigger an accumulate which runs prune().
		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->',
			'post_status'  => 'draft',
		) );
		wp_publish_post( $post_id );

		$data = get_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH );
		$this->assertArrayNotHasKey( $stale_date, $data, 'Stale entry should have been pruned.' );
	}

	/**
	 * get_last_n_days returns only entries within the requested window.
	 */
	public function test_get_last_n_days_respects_window() {
		$today      = gmdate( 'Y-m-d' );
		$yesterday  = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
		$old_date   = gmdate( 'Y-m-d', strtotime( '-8 days' ) );

		update_option(
			Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH,
			array(
				$today     => 3,
				$yesterday => 2,
				$old_date  => 99,
			)
		);

		$data = $this->kpi->get_last_n_days( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH, 7 );

		$this->assertArrayHasKey( $today, $data );
		$this->assertArrayHasKey( $yesterday, $data );
		$this->assertArrayNotHasKey( $old_date, $data );
	}

	// -------------------------------------------------------------------------
	// pages_with_spectra
	// -------------------------------------------------------------------------

	/**
	 * get_pages_with_spectra counts only published posts with spectra/ block comments.
	 */
	public function test_pages_with_spectra_counts_published_posts() {
		// Two posts with Spectra blocks.
		self::factory()->post->create( array(
			'post_content' => '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->',
			'post_status'  => 'publish',
		) );
		self::factory()->post->create( array(
			'post_content' => '<!-- wp:spectra/content --><p></p><!-- /wp:spectra/content -->',
			'post_status'  => 'publish',
		) );
		// One post without Spectra blocks.
		self::factory()->post->create( array(
			'post_content' => '<p>No blocks</p>',
			'post_status'  => 'publish',
		) );

		// Clear transient to get fresh count.
		delete_transient( Spectra_Blocks_Daily_KPI_Counters::TRANSIENT_PAGES_COUNT );

		$this->assertSame( 2, $this->kpi->get_pages_with_spectra() );
	}

	/**
	 * Draft posts should not be counted.
	 */
	public function test_pages_with_spectra_excludes_drafts() {
		self::factory()->post->create( array(
			'post_content' => '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->',
			'post_status'  => 'draft',
		) );

		delete_transient( Spectra_Blocks_Daily_KPI_Counters::TRANSIENT_PAGES_COUNT );

		$this->assertSame( 0, $this->kpi->get_pages_with_spectra() );
	}

	/**
	 * Result is cached in a transient and not re-queried.
	 */
	public function test_pages_with_spectra_caches_result() {
		// Seed a fake cached value.
		set_transient( Spectra_Blocks_Daily_KPI_Counters::TRANSIENT_PAGES_COUNT, 42 );

		// Even though no posts exist with that count, the cache should be returned.
		$this->assertSame( 42, $this->kpi->get_pages_with_spectra() );
	}

	// -------------------------------------------------------------------------
	// kpi_records payload shape
	// -------------------------------------------------------------------------

	/**
	 * get_kpi_tracking_data (via loader) produces entries with the correct structure.
	 */
	public function test_kpi_records_shape_via_loader() {
		if ( ! class_exists( 'Spectra_Blocks_Loader' ) ) {
			$this->markTestSkipped( 'Spectra_Blocks_Loader not available.' );
		}

		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		// Seed past-day data directly into the options.
		update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH,     array( $yesterday => 4 ) );
		update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_BLOCK_TYPES, array( $yesterday => array( 'spectra/container', 'spectra/content' ) ) );
		update_option( Spectra_Blocks_Daily_KPI_Counters::OPT_ADVANCED,    array( $yesterday => array( 'gbs' ) ) );

		// Invoke the filter.
		$stats = apply_filters( 'bsf_core_stats', array() );

		$kpi_records = $stats['plugin_data']['spectra_blocks']['kpi_records'] ?? array();

		$this->assertNotEmpty( $kpi_records, 'kpi_records should not be empty.' );

		$this->assertArrayHasKey( $yesterday, $kpi_records, "Record for {$yesterday} missing." );
		$yesterday_record = $kpi_records[ $yesterday ];
		$this->assertArrayHasKey( 'numeric_values', $yesterday_record );

		$nv = $yesterday_record['numeric_values'];
		$this->assertArrayHasKey( 'spectra_posts_published_daily', $nv );
		$this->assertArrayHasKey( 'spectra_distinct_block_types_daily', $nv );
		$this->assertArrayHasKey( 'spectra_advanced_features_used_daily', $nv );

		$this->assertSame( 4, $nv['spectra_posts_published_daily'] );
		$this->assertSame( 2, $nv['spectra_distinct_block_types_daily'] );
		$this->assertSame( 1, $nv['spectra_advanced_features_used_daily'] );
	}

	/**
	 * Today's data should NOT appear in kpi_records (day still accumulating).
	 */
	public function test_kpi_records_excludes_today() {
		if ( ! class_exists( 'Spectra_Blocks_Loader' ) ) {
			$this->markTestSkipped( 'Spectra_Blocks_Loader not available.' );
		}

		$today     = gmdate( 'Y-m-d' );
		$yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		// Seed both today and yesterday so kpi_records is non-empty.
		update_option(
			Spectra_Blocks_Daily_KPI_Counters::OPT_PUBLISH,
			array(
				$today     => 10,
				$yesterday => 2,
			)
		);

		$stats       = apply_filters( 'bsf_core_stats', array() );
		$kpi_records = $stats['plugin_data']['spectra_blocks']['kpi_records'] ?? array();

		// Yesterday's entry must be present; today's must not be.
		$this->assertNotEmpty( $kpi_records, 'kpi_records should contain at least yesterday.' );

		$dates = array_keys( $kpi_records );
		$this->assertContains( $yesterday, $dates, 'Yesterday record missing.' );
		$this->assertNotContains( $today, $dates, "Today's entry should be excluded." );
	}

	/**
	 * user_segment should be one of the four valid values.
	 */
	public function test_user_segment_valid_value() {
		if ( ! class_exists( 'Spectra_Blocks_Loader' ) ) {
			$this->markTestSkipped( 'Spectra_Blocks_Loader not available.' );
		}

		$stats   = apply_filters( 'bsf_core_stats', array() );
		$segment = $stats['plugin_data']['spectra_blocks']['user_segment'] ?? null;

		$valid = array( 'free_active', 'free_inactive', 'pro_active', 'pro_dormant' );
		$this->assertContains( $segment, $valid );
	}

	/**
	 * pages_with_spectra in payload should be a non-negative integer.
	 */
	public function test_pages_with_spectra_in_payload() {
		if ( ! class_exists( 'Spectra_Blocks_Loader' ) ) {
			$this->markTestSkipped( 'Spectra_Blocks_Loader not available.' );
		}

		delete_transient( Spectra_Blocks_Daily_KPI_Counters::TRANSIENT_PAGES_COUNT );

		$stats = apply_filters( 'bsf_core_stats', array() );
		$pages = $stats['plugin_data']['spectra_blocks']['pages_with_spectra'] ?? -1;

		$this->assertIsInt( $pages );
		$this->assertGreaterThanOrEqual( 0, $pages );
	}
}
