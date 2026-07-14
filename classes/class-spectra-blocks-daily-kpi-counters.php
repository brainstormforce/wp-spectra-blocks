<?php
/**
 * Daily KPI accumulators for Spectra Blocks analytics.
 *
 * Maintains rolling 7-day time-series buckets for three KPI axes:
 *   1. Posts published with Spectra blocks (daily count)
 *   2. Distinct Spectra block types used across all published posts (daily union)
 *   3. Advanced features used on publish days (daily flag set)
 *
 * Data is included in the BSF Analytics payload as `kpi_records` and consumed
 * by the ClickHouse analytics pipeline at analytics.brainstormforce.com.
 *
 * @package SpectraBlocks
 *
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Singleton that hooks into post lifecycle events to accumulate daily KPI data.
 */
class Spectra_Blocks_Daily_KPI_Counters {

	/**
	 * Option key: rolling daily publish counts keyed by Y-m-d.
	 */
	const OPT_PUBLISH = 'spectra_blocks_daily_publish_counts';

	/**
	 * Option key: rolling daily distinct block-type sets keyed by Y-m-d.
	 */
	const OPT_BLOCK_TYPES = 'spectra_blocks_daily_block_types';

	/**
	 * Option key: rolling daily advanced-feature flag sets keyed by Y-m-d.
	 */
	const OPT_ADVANCED = 'spectra_blocks_daily_advanced_feature_uses';

	/**
	 * Transient key for caching the pages-with-spectra count at stats send time.
	 */
	const TRANSIENT_PAGES_COUNT = 'spectra_blocks_pages_with_spectra';

	/**
	 * Number of days to retain in rolling buckets.
	 */
	const RETENTION_DAYS = 7;

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Return the singleton instance.
	 *
	 * @since 1.0.0
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — register hooks.
	 */
	private function __construct() {
		// Priority 10: capture transition before post meta / analytics run.
		add_action( 'transition_post_status', array( $this, 'on_transition_post_status' ), 10, 3 );

		// Priority 20: run after BlockUsageTracker (priority 10) so we can re-use
		// the parsed content directly from the post object.
		add_action( 'save_post', array( $this, 'on_save_post_record_block_types' ), 20, 2 );

		// Global Block Styles advanced-feature tracking.
		add_action( 'update_option_spectra_global_block_styles', array( $this, 'on_gbs_changed' ) );
		add_action( 'add_option_spectra_global_block_styles', array( $this, 'on_gbs_added' ) );

		// Priority 30: append KPI data to the BSF Analytics payload.
		add_filter( 'bsf_core_stats', array( $this, 'add_kpi_stats' ), 30 );
	}

	// -------------------------------------------------------------------------
	// Hook callbacks
	// -------------------------------------------------------------------------

	/**
	 * On real "any → publish" transitions, increment the daily publish counter
	 * and record any advanced features present in the post content.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Previous post status.
	 * @param \WP_Post $post       The post object.
	 */
	public function on_transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || $old_status === $new_status ) {
			return;
		}

		if ( ! $this->content_has_spectra_block( $post->post_content ) ) {
			return;
		}

		$this->increment_publish();
		$this->detect_advanced_features( $post->post_content );
	}

	/**
	 * On every save of a published post, merge the distinct Spectra block types
	 * used in the content into today's daily bucket.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    The post object.
	 */
	public function on_save_post_record_block_types( $post_id, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$block_names = $this->extract_spectra_block_names( $post->post_content );
		if ( ! empty( $block_names ) ) {
			$this->record_block_types( $block_names );
		}
	}

	/**
	 * Record GBS advanced feature when the option is updated.
	 *
	 * @since 1.0.0
	 */
	public function on_gbs_changed() {
		$this->record_advanced_feature( 'gbs' );
	}

	/**
	 * Record GBS advanced feature when the option is first created.
	 *
	 * @since 1.0.0
	 */
	public function on_gbs_added() {
		$this->record_advanced_feature( 'gbs' );
	}

	// -------------------------------------------------------------------------
	// Public counters API
	// -------------------------------------------------------------------------

	/**
	 * Return the last N days of data for a given rolling-bucket option.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_key One of the OPT_* constants.
	 * @param int    $days       Number of days to return (default: RETENTION_DAYS).
	 * @return array<string, mixed> Date-keyed array, e.g. [ '2024-01-15' => 3, ... ].
	 */
	public function get_last_n_days( $option_key, $days = self::RETENTION_DAYS ) {
		$all   = get_option( $option_key, array() );
		$out   = array();
		$today = gmdate( 'Y-m-d' );

		for ( $i = 0; $i < $days; $i++ ) {
			$date = gmdate( 'Y-m-d', strtotime( "-{$i} days", strtotime( $today ) ) );
			if ( isset( $all[ $date ] ) ) {
				$out[ $date ] = $all[ $date ];
			}
		}

		return $out;
	}

	/**
	 * Count published posts that contain at least one Spectra block.
	 *
	 * Result is cached for one hour — this method is only called at BSF Analytics
	 * send time (typically once per day) so a direct DB query is acceptable.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function get_pages_with_spectra() {
		$cached = get_transient( self::TRANSIENT_PAGES_COUNT );
		if ( false !== $cached ) {
			return absint( $cached );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- cached via set_transient() above.
		$count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_status = 'publish'
			 AND post_content LIKE '%<!-- wp:spectra/%'"
		);

		set_transient( self::TRANSIENT_PAGES_COUNT, $count, HOUR_IN_SECONDS );
		return $count;
	}

	// -------------------------------------------------------------------------
	// Internal accumulators
	// -------------------------------------------------------------------------

	/**
	 * Increment today's publish-count bucket by 1.
	 */
	private function increment_publish() {
		$today = gmdate( 'Y-m-d' );
		$data  = get_option( self::OPT_PUBLISH, array() );

		$data[ $today ] = isset( $data[ $today ] ) ? $data[ $today ] + 1 : 1;
		$data           = $this->prune( $data );

		update_option( self::OPT_PUBLISH, $data, false );
	}

	/**
	 * Merge a list of block names into today's distinct block-types set.
	 *
	 * @param string[] $block_names E.g. [ 'spectra/container', 'spectra/content' ].
	 */
	private function record_block_types( array $block_names ) {
		$today = gmdate( 'Y-m-d' );
		$data  = get_option( self::OPT_BLOCK_TYPES, array() );

		$existing       = isset( $data[ $today ] ) && is_array( $data[ $today ] ) ? $data[ $today ] : array();
		$data[ $today ] = array_values( array_unique( array_merge( $existing, $block_names ) ) );
		$data           = $this->prune( $data );

		update_option( self::OPT_BLOCK_TYPES, $data, false );
	}

	/**
	 * Add a named advanced feature to today's flag set.
	 *
	 * @param string $feature_key E.g. 'gbs', 'popup', 'dynamic_content'.
	 */
	private function record_advanced_feature( $feature_key ) {
		$today = gmdate( 'Y-m-d' );
		$data  = get_option( self::OPT_ADVANCED, array() );

		$existing = isset( $data[ $today ] ) && is_array( $data[ $today ] ) ? $data[ $today ] : array();
		if ( ! in_array( $feature_key, $existing, true ) ) {
			$existing[] = $feature_key;
		}
		$data[ $today ] = $existing;
		$data           = $this->prune( $data );

		update_option( self::OPT_ADVANCED, $data, false );
	}

	// -------------------------------------------------------------------------
	// Content helpers
	// -------------------------------------------------------------------------

	/**
	 * Return true when post content contains at least one Spectra block comment.
	 *
	 * @param string $content Post content.
	 * @return bool
	 */
	private function content_has_spectra_block( $content ) {
		return (bool) preg_match( '/<!-- wp:spectra\//', $content );
	}

	/**
	 * Detect and record advanced features present in the post content.
	 *
	 * @param string $content Post content.
	 */
	private function detect_advanced_features( $content ) {
		if ( $this->content_has_popup_block( $content ) ) {
			$this->record_advanced_feature( 'popup' );
		}

		if ( $this->content_has_dynamic_content( $content ) ) {
			$this->record_advanced_feature( 'dynamic_content' );
		}
	}

	/**
	 * Return true when content contains a spectra/popup block.
	 *
	 * @param string $content Post content.
	 * @return bool
	 */
	private function content_has_popup_block( $content ) {
		return (bool) preg_match( '/<!-- wp:spectra\/popup\b/', $content );
	}

	/**
	 * Return true when content uses Dynamic Content attributes.
	 *
	 * @param string $content Post content.
	 * @return bool
	 */
	private function content_has_dynamic_content( $content ) {
		return (bool) preg_match( '/"dynamicContent"|"[A-Za-z]+Source"\s*:\s*"[^"]+-meta"/', $content );
	}

	/**
	 * Extract all unique spectra/* block names from post content.
	 *
	 * @param string $content Post content.
	 * @return string[]
	 */
	private function extract_spectra_block_names( $content ) {
		preg_match_all( '/<!-- wp:(spectra\/[a-z0-9-]+)[\s\/{>]/', $content, $matches );
		return array_values( array_unique( $matches[1] ?? array() ) );
	}

	// -------------------------------------------------------------------------
	// BSF Analytics payload
	// -------------------------------------------------------------------------

	/**
	 * Append KPI records, user segment, and pages_with_spectra to the BSF Analytics payload.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $stats Existing stats array.
	 * @return array<string, mixed>
	 */
	public function add_kpi_stats( $stats ) {
		if ( empty( $stats['plugin_data']['spectra_blocks'] ) || ! is_array( $stats['plugin_data']['spectra_blocks'] ) ) {
			$stats['plugin_data']['spectra_blocks'] = array();
		}

		$today   = gmdate( 'Y-m-d' );
		$publish = get_option( self::OPT_PUBLISH, array() );
		$types   = get_option( self::OPT_BLOCK_TYPES, array() );
		$adv     = get_option( self::OPT_ADVANCED, array() );

		// Build kpi_records — exclude today (day is still accumulating).
		$kpi_records = array();
		$all_dates   = array_unique( array_merge( array_keys( $publish ), array_keys( $types ), array_keys( $adv ) ) );

		foreach ( $all_dates as $date ) {
			if ( $date === $today ) {
				continue;
			}
			$kpi_records[] = array(
				'date'           => $date,
				'numeric_values' => array(
					'spectra_posts_published_daily'        => isset( $publish[ $date ] ) ? (int) $publish[ $date ] : 0,
					'spectra_distinct_block_types_daily'   => isset( $types[ $date ] ) && is_array( $types[ $date ] ) ? count( $types[ $date ] ) : 0,
					'spectra_advanced_features_used_daily' => isset( $adv[ $date ] ) && is_array( $adv[ $date ] ) ? count( $adv[ $date ] ) : 0,
				),
			);
		}

		$stats['plugin_data']['spectra_blocks']['kpi_records'] = $kpi_records;

		// Determine user_segment.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$is_pro    = is_plugin_active( 'spectra-pro/spectra-pro.php' );
		$is_active = ! empty( $publish ) || $this->get_pages_with_spectra() > 0;

		if ( $is_pro ) {
			$stats['plugin_data']['spectra_blocks']['user_segment'] = $is_active ? 'pro_active' : 'pro_dormant';
		} else {
			$stats['plugin_data']['spectra_blocks']['user_segment'] = $is_active ? 'free_active' : 'free_inactive';
		}

		// pages_with_spectra snapshot.
		$stats['plugin_data']['spectra_blocks']['pages_with_spectra'] = $this->get_pages_with_spectra();

		return $stats;
	}

	// -------------------------------------------------------------------------
	// Utility
	// -------------------------------------------------------------------------

	/**
	 * Remove entries older than RETENTION_DAYS from a date-keyed array.
	 *
	 * @param array $data Date-keyed data array.
	 * @return array
	 */
	private function prune( array $data ) {
		$cutoff = gmdate( 'Y-m-d', strtotime( '-' . self::RETENTION_DAYS . ' days' ) );
		foreach ( array_keys( $data ) as $date ) {
			if ( $date < $cutoff ) {
				unset( $data[ $date ] );
			}
		}
		return $data;
	}
}
