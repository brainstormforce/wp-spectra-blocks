<?php
/**
 * Block Usage Analytics Tracker for Spectra 3.
 *
 * @package Spectra
 */

namespace Spectra\Analytics;

use SpectraBlocks\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Block Usage Tracker for BSF Analytics integration.
 *
 * This class tracks usage of Spectra 3 blocks and integrates with the existing
 * BSF Analytics system from the parent Spectra 2.x.x implementation.
 *
 * @since 3.0.0-beta.1
 */
class BlockUsageTracker {

	use Singleton;

	/**
	 * Block analytics data storage key.
	 */
	const ANALYTICS_KEY = 'spectra_blocks_block_analytics';


	/**
	 * Initialize the analytics tracker.
	 *
	 * @since 3.0.0-beta.1
	 */
	public function init(): void {
		// Hook into WordPress save_post to track block usage.
		add_action( 'save_post', array( $this, 'track_post_block_usage' ), 10, 2 );

		// Hook into WordPress delete_post to clean up analytics data.
		add_action( 'delete_post', array( $this, 'remove_post_analytics_data' ) );

		// Hook into WordPress trash/untrash to exclude/include trashed posts from active count.
		add_action( 'wp_trash_post', array( $this, 'handle_post_trashed' ) );
		add_action( 'untrash_post', array( $this, 'handle_post_untrashed' ) );

		// Hook into BSF Analytics stats collection.
		add_filter( 'bsf_core_stats', array( $this, 'add_spectra_3_stats' ), 20 );

		// Hook into settings changes to handle cleanup.
		add_action( 'update_option_spectra_blocks_usage_optin', array( $this, 'handle_analytics_toggle' ), 10, 2 );

		// Initialize usage data if not exists.
		$this->init_usage_data();
	}

	/**
	 * Initialize usage data storage.
	 *
	 * @since 3.0.0-beta.1
	 */
	private function init_usage_data(): void {
		if ( false === get_option( self::ANALYTICS_KEY, false ) ) {
			$initial_data = array(
				'usage_data' => array(), // Post-specific block usage.
				'statistics' => array(
					'total_posts_with_blocks' => 0,
					'most_used_blocks'        => array(),
					'blocks_per_post'         => array(),
					'last_updated'            => time(),
				),
			);
			// Store as non-autoloaded to improve performance.
			add_option( self::ANALYTICS_KEY, $initial_data, '', 'no' );
		}
	}

	/**
	 * Track block usage when a post is saved.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param int      $post_id Post ID being saved.
	 * @param \WP_Post $post    Post object being saved.
	 */
	public function track_post_block_usage( $post_id, $post ): void {
		// Skip if user has not opted in for analytics.
		if ( ! $this->is_analytics_enabled() ) {
			return;
		}

		// Skip revisions and auto-saves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$spectra_3_blocks = array();

		// Extract Spectra blocks if post has Gutenberg content.
		if ( has_blocks( $post->post_content ) ) {
			$blocks           = parse_blocks( $post->post_content );
			$spectra_3_blocks = $this->extract_spectra_3_blocks( $blocks );
		}

		// If no Spectra blocks, remove any existing data for this post.
		if ( empty( $spectra_3_blocks ) ) {
			$this->remove_post_from_usage_data( $post_id );
			return;
		}

		$this->update_usage_data( $post_id, $spectra_3_blocks );
		$this->update_usage_statistics( $spectra_3_blocks );

		// Invalidate cache when new data is added.
		$this->clear_analytics_cache();
	}

	/**
	 * Remove post from usage data when Spectra blocks are removed.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 */
	private function remove_post_from_usage_data( $post_id ): void {
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );

		if ( ! isset( $analytics_data['usage_data'][ $post_id ] ) ) {
			return;
		}

		unset( $analytics_data['usage_data'][ $post_id ] );

		if ( isset( $analytics_data['statistics'] ) ) {
			$analytics_data['statistics']['total_posts_with_blocks'] = $this->count_valid_posts( $analytics_data['usage_data'] );
			$analytics_data['statistics']['last_updated']            = time();
		}

		update_option( self::ANALYTICS_KEY, $analytics_data, false );
		$this->clear_analytics_cache();
	}

	/**
	 * Count valid (non-trashed, existing) posts in usage data.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int|string, mixed> $usage_data Usage data array.
	 * @return int Count of valid posts.
	 */
	private function count_valid_posts( array $usage_data ): int {
		if ( empty( $usage_data ) || ! is_array( $usage_data ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $usage_data as $post_id => $post_data ) {
			if ( ! empty( $post_data['trashed'] ) ) {
				continue;
			}

			$post_status = get_post_status( $post_id );
			if ( false === $post_status || 'trash' === $post_status ) {
				continue;
			}

			++$count;
		}

		return $count;
	}

	/**
	 * Remove analytics data for a deleted post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID being deleted.
	 */
	public function remove_post_analytics_data( $post_id ): void {
		if ( ! $this->is_analytics_enabled() ) {
			return;
		}

		$analytics_data = get_option( self::ANALYTICS_KEY, array() );

		if ( ! isset( $analytics_data['usage_data'][ $post_id ] ) ) {
			return;
		}

		unset( $analytics_data['usage_data'][ $post_id ] );

		if ( isset( $analytics_data['statistics'] ) ) {
			$analytics_data['statistics']['total_posts_with_blocks'] = count( $analytics_data['usage_data'] );
			$analytics_data['statistics']['last_updated']            = time();
		}

		update_option( self::ANALYTICS_KEY, $analytics_data, false );
		$this->clear_analytics_cache();
	}

	/**
	 * Handle post being moved to trash.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID being trashed.
	 */
	public function handle_post_trashed( $post_id ): void {
		if ( ! $this->is_analytics_enabled() ) {
			return;
		}

		$analytics_data = get_option( self::ANALYTICS_KEY, array() );

		if ( ! isset( $analytics_data['usage_data'][ $post_id ] ) ) {
			return;
		}

		$analytics_data['usage_data'][ $post_id ]['trashed'] = true;

		if ( isset( $analytics_data['statistics'] ) ) {
			$analytics_data['statistics']['last_updated'] = time();
		}

		update_option( self::ANALYTICS_KEY, $analytics_data, false );
		$this->clear_analytics_cache();
	}

	/**
	 * Handle post being restored from trash.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID being restored.
	 */
	public function handle_post_untrashed( $post_id ): void {
		if ( ! $this->is_analytics_enabled() ) {
			return;
		}

		$analytics_data = get_option( self::ANALYTICS_KEY, array() );

		if ( ! isset( $analytics_data['usage_data'][ $post_id ] ) ) {
			return;
		}

		unset( $analytics_data['usage_data'][ $post_id ]['trashed'] );

		if ( isset( $analytics_data['statistics'] ) ) {
			$analytics_data['statistics']['last_updated'] = time();
		}

		update_option( self::ANALYTICS_KEY, $analytics_data, false );
		$this->clear_analytics_cache();
	}

	/**
	 * Extract Spectra blocks from parsed blocks array.
	 *
	 * Supports both Spectra 3 (spectra/) and Spectra Pro blocks (spectra-pro/).
	 * Only tracks blocks that users can see and insert from the block inserter,
	 * not child blocks or inner blocks that are auto-generated.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int|string, mixed> $blocks Parsed blocks array.
	 * @return array<int, string> Array of root-level Spectra block names found.
	 */
	private function extract_spectra_3_blocks( array $blocks ): array {
		$root_level_blocks = array();

		foreach ( $blocks as $block ) {
			// If the block doesn't have a name, jump over it.
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			$block_prefix = '';
			$block_name   = '';

			// Check if this is a Spectra Blocks block.
			if ( strpos( $block['blockName'], 'spectra/' ) === 0 ) {
				$block_prefix = 'spectra-blocks';
				$block_name   = str_replace( 'spectra/', '', $block['blockName'] );
			} elseif ( strpos( $block['blockName'], 'spectra-pro/' ) === 0 ) {
				// Check if this is a Spectra Pro block.
				$block_prefix = 'spectra-pro';
				$block_name   = str_replace( 'spectra-pro/', '', $block['blockName'] );
			}

			// If we found a Spectra block, process it.
			if ( ! empty( $block_prefix ) && ! empty( $block_name ) ) {
				// Apply security filter to ensure only allowed blocks are tracked.
				$allowed_block = apply_filters(
					'spectra_blocks_analytics_allow_block_tracking',
					true,
					$block_name,
					$block_prefix,
					$block['blockName']
				);

				if ( ! $allowed_block ) {
					continue;
				}

				// Only track if it's a root-level block (not a child block).
				if ( $this->is_root_level_block( $block_name, $block_prefix ) ) {
					// Store with prefix to distinguish Spectra 3 vs Pro blocks.
					$prefixed_block_name = 'spectra-pro' === $block_prefix ? 'pro-' . $block_name : $block_name;
					$root_level_blocks[] = $prefixed_block_name;
				}
			}

			// Process inner blocks recursively, but still only extract root-level blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner_root_blocks = $this->extract_spectra_3_blocks( $block['innerBlocks'] );
				$root_level_blocks = array_merge( $root_level_blocks, $inner_root_blocks );
			}
		}

		// Cross-plugin extension points — spectra_ prefix is intentional; spectra-blocks-pro hooks into these filters.
		$root_level_blocks = apply_filters( 'spectra_blocks_analytics_tracked_blocks', $root_level_blocks, $blocks );

		return array_unique( $root_level_blocks );
	}

	/**
	 * Update block usage data for a specific post.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param int                $post_id Post ID.
	 * @param array<int, string> $blocks  Array of block names used in the post.
	 */
	private function update_usage_data( int $post_id, array $blocks ): void {
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );

		// Ensure structure exists.
		if ( ! isset( $analytics_data['usage_data'] ) ) {
			$analytics_data['usage_data'] = array();
		}

		$analytics_data['usage_data'][ $post_id ] = array(
			'blocks'  => $blocks,
			'count'   => count( $blocks ),
			'updated' => time(),
		);

		update_option( self::ANALYTICS_KEY, $analytics_data, false );
	}

	/**
	 * Update overall usage statistics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array<int, string> $blocks Array of block names used.
	 */
	private function update_usage_statistics( array $blocks ): void {
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );

		// Ensure structure exists.
		if ( ! isset( $analytics_data['statistics'] ) ) {
			$analytics_data['statistics'] = array(
				'total_posts_with_blocks' => 0,
				'most_used_blocks'        => array(),
				'blocks_per_post'         => array(),
				'last_updated'            => time(),
			);
		}

		$stats = &$analytics_data['statistics'];

		// Update total posts count.
		$stats['total_posts_with_blocks'] = $this->get_total_posts_with_spectra_3_blocks();

		// Update most used blocks counter.
		foreach ( $blocks as $block_name ) {
			if ( ! isset( $stats['most_used_blocks'][ $block_name ] ) ) {
				$stats['most_used_blocks'][ $block_name ] = 0;
			}
			++$stats['most_used_blocks'][ $block_name ];
		}

		// Update blocks per post distribution.
		$block_count = count( $blocks );
		$count_key   = "posts_with_{$block_count}_blocks";
		if ( ! isset( $stats['blocks_per_post'][ $count_key ] ) ) {
			$stats['blocks_per_post'][ $count_key ] = 0;
		}
		++$stats['blocks_per_post'][ $count_key ];

		// Update timestamp.
		$stats['last_updated'] = time();

		update_option( self::ANALYTICS_KEY, $analytics_data, false );
	}

	/**
	 * Get total number of posts containing Spectra 3 blocks.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return int Total post count.
	 */
	private function get_total_posts_with_spectra_3_blocks() {
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );
		$usage_data     = $analytics_data['usage_data'] ?? array();

		$count = 0;
		foreach ( $usage_data as $post_id => $post_data ) {
			if ( ! empty( $post_data['trashed'] ) ) {
				continue;
			}

			$post_status = get_post_status( $post_id );
			if ( false === $post_status || 'trash' === $post_status ) {
				continue;
			}

			++$count;
		}

		return $count;
	}

	/**
	 * Get block usage statistics for analytics (root-level blocks only).
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Block usage statistics for root-level blocks only.
	 */
	public function get_usage_statistics() {
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );
		$stats          = $analytics_data['statistics'] ?? array();
		$usage_data     = $analytics_data['usage_data'] ?? array();

		// Filter to only include root-level blocks.
		$filtered_stats      = $this->filter_stats_to_root_blocks( $stats );
		$filtered_usage_data = $this->filter_usage_data_to_root_blocks( $usage_data );

		// Calculate additional metrics based on filtered data.
		$total_block_instances = 0;
		foreach ( $filtered_usage_data as $post_data ) {
			$total_block_instances += $post_data['count'];
		}

		return array_merge(
			$filtered_stats,
			array(
				'total_block_instances'   => $total_block_instances,
				'unique_blocks_used'      => count( $filtered_stats['most_used_blocks'] ?? array() ),
				'average_blocks_per_post' => empty( $filtered_stats['total_posts_with_blocks'] )
					? 0
					: round( $total_block_instances / $filtered_stats['total_posts_with_blocks'], 2 ),
			)
		);
	}

	/**
	 * Get top N most used root-level blocks.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param int $limit Number of top blocks to return.
	 * @return array Top used root-level blocks with usage counts.
	 */
	public function get_top_used_blocks( $limit = 10 ) {
		$stats     = $this->get_usage_statistics();
		$most_used = $stats['most_used_blocks'] ?? array();

		arsort( $most_used );
		return array_slice( $most_used, 0, $limit, true );
	}

	/**
	 * Get site activity level based on Spectra block edits in the last 180 days.
	 *
	 * @since 1.0.0
	 *
	 * @return array Site activity data with classification.
	 */
	public function get_site_activity_level() {
		$days_threshold = 180;
		$cutoff_time    = time() - ( $days_threshold * DAY_IN_SECONDS );

		$analytics_data = get_option( self::ANALYTICS_KEY, array() );
		$usage_data     = $analytics_data['usage_data'] ?? array();

		$active_pages_count = 0;

		foreach ( $usage_data as $post_id => $post_data ) {
			if ( ! empty( $post_data['trashed'] ) ) {
				continue;
			}

			$post_status = get_post_status( $post_id );
			if ( false === $post_status || 'trash' === $post_status ) {
				continue;
			}

			$updated_time = $post_data['updated'] ?? 0;

			if ( $updated_time >= $cutoff_time ) {
				++$active_pages_count;
			}
		}

		$site_type = 'inactive';
		if ( $active_pages_count >= 15 ) {
			$site_type = 'super_site';
		} elseif ( $active_pages_count >= 1 ) {
			$site_type = 'active_site';
		}

		return array(
			'active_pages_180d' => $active_pages_count,
			'site_type'         => $site_type,
			'is_active_site'    => $active_pages_count >= 1,
			'is_super_site'     => $active_pages_count >= 15,
		);
	}

	/**
	 * Add Spectra 3 statistics to BSF Analytics data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $stats Existing BSF Analytics stats.
	 * @return array Enhanced stats with Spectra 3 data.
	 */
	public function add_spectra_3_stats( $stats ) {
		// Skip if user has not opted in for analytics.
		if ( ! $this->is_analytics_enabled() ) {
			return $stats;
		}

		// Ensure the spectra_blocks plugin data container exists.
		if ( empty( $stats['plugin_data']['spectra_blocks'] ) || ! is_array( $stats['plugin_data']['spectra_blocks'] ) ) {
			$stats['plugin_data']['spectra_blocks'] = array();
		}

		// Get comprehensive analytics with caching.
		$analytics_data = $this->get_cached_analytics_data();

		// Get usage stats to access most_used_blocks.
		$usage_stats = $this->get_usage_statistics();

		// Get all available blocks to initialize with 0.
		$available_blocks = $this->get_available_blocks();

		// Format individual block usage stats with 'block_usage_' prefix.
		$formatted_block_usage_stats = array();

		// First, initialize ALL available blocks with 0 count.
		foreach ( $available_blocks as $block_name ) {
			$full_block_name = $this->get_full_block_name( $block_name );
			$formatted_block_usage_stats[ 'block_usage_' . $full_block_name ] = 0;
		}

		// Then, override with actual usage counts for blocks that are used.
		if ( ! empty( $usage_stats['most_used_blocks'] ) && is_array( $usage_stats['most_used_blocks'] ) ) {
			foreach ( $usage_stats['most_used_blocks'] as $block_name => $count ) {
				// Convert block names like 'container' or 'pro-loop-builder' to 'block_usage_spectra/container' or 'block_usage_spectra-pro/loop-builder'.
				$full_block_name = $this->get_full_block_name( $block_name );
				$formatted_block_usage_stats[ 'block_usage_' . $full_block_name ] = $count;
			}
		}

		// Get site activity level for Active Site / Super Site KPIs.
		$site_activity = $this->get_site_activity_level();

		// Prepare the stats structure with numeric_values for individual block counts.
		$spectra_3_stats = array(
			'numeric_values'        => array_merge(
				$formatted_block_usage_stats,
				array(
					'total_posts_with_blocks' => $analytics_data['total_posts_with_blocks'],
					'total_block_instances'   => $analytics_data['total_block_instances'],
					'unique_blocks_used'      => $analytics_data['unique_blocks_used'],
					'total_blocks_available'  => $analytics_data['total_blocks_available'],
					'adoption_rate_percent'   => $analytics_data['adoption_rate_percent'],
					'active_pages_180d'       => $site_activity['active_pages_180d'],
				)
			),
			'boolean_values'        => array(
				'blocks_actively_used' => $analytics_data['unique_blocks_used'] > 0,
				'high_adoption_rate'   => $analytics_data['adoption_rate_percent'] > 50,
				'is_active_site'       => $site_activity['is_active_site'],
				'is_super_site'        => $site_activity['is_super_site'],
			),
			'top_used_blocks'       => $analytics_data['top_blocks'],
			'most_popular_block'    => $analytics_data['most_popular_block'],
			'user_engagement_level' => $this->get_user_engagement_level( $analytics_data ),
			'site_activity'         => $site_activity,
		);

		// Merge numeric_values by adding numbers if they already exist.
		if ( isset( $spectra_3_stats['numeric_values'], $stats['plugin_data']['spectra_blocks']['numeric_values'] )
			&& is_array( $spectra_3_stats['numeric_values'] )
			&& is_array( $stats['plugin_data']['spectra_blocks']['numeric_values'] ) ) {

			foreach ( $spectra_3_stats['numeric_values'] as $key => $value ) {
				$stats['plugin_data']['spectra_blocks']['numeric_values'][ $key ] = ( isset( $stats['plugin_data']['spectra_blocks']['numeric_values'][ $key ] )
					&& is_numeric( $value )
					&& is_numeric( $stats['plugin_data']['spectra_blocks']['numeric_values'][ $key ] ) )
					? $stats['plugin_data']['spectra_blocks']['numeric_values'][ $key ] + $value
					: $value;
			}
			unset( $spectra_3_stats['numeric_values'] );
		}

		// Merge remaining stats (metadata, etc.) with existing stats.
		$stats['plugin_data']['spectra_blocks']['spectra_3_blocks'] = array_merge_recursive(
			$stats['plugin_data']['spectra_blocks']['spectra_3_blocks'] ?? array(),
			$spectra_3_stats
		);

		return $stats;
	}

	/**
	 * Reset all analytics data (for testing/debugging).
	 *
	 * @since 3.0.0-beta.1
	 */
	public function reset_analytics_data() {
		delete_option( self::ANALYTICS_KEY );
		$this->init_usage_data();
	}

	/**
	 * Handle analytics toggle - clean up data when disabled.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $old_value Previous option value.
	 * @param string $new_value New option value.
	 */
	public function handle_analytics_toggle( $old_value, $new_value ) {
		// If analytics was disabled, clean up our data.
		if ( 'yes' === $old_value && 'no' === $new_value ) {
			delete_option( self::ANALYTICS_KEY );
			$this->clear_analytics_cache();
		}
	}

	/**
	 * Get all available Spectra blocks using WordPress Block Registry.
	 *
	 * Dynamically discovers all registered Spectra blocks including future blocks.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param bool $include_pro Whether to include Spectra Pro blocks.
	 * @return array Array of Spectra block names in internal format.
	 */
	public function get_available_blocks( $include_pro = true ) {
		$cache_key  = 'spectra_all_registered_blocks';
		$all_blocks = wp_cache_get( $cache_key, 'spectra' );

		if ( false !== $all_blocks ) {
			return $all_blocks;
		}

		$all_blocks = array();

		// Use WordPress Block Registry to dynamically get all registered blocks.
		if ( class_exists( '\WP_Block_Type_Registry' ) ) {
			$registry   = \WP_Block_Type_Registry::get_instance();
			$registered = $registry->get_all_registered();

			foreach ( $registered as $block_name => $block_type ) {
				// Check for Spectra 3 blocks.
				if ( strpos( $block_name, 'spectra/' ) === 0 ) {
					$short_name   = str_replace( 'spectra/', '', $block_name );
					$all_blocks[] = $short_name;
				} elseif ( $include_pro && strpos( $block_name, 'spectra-pro/' ) === 0 ) {
					// Check for Spectra Pro blocks.
					$short_name   = str_replace( 'spectra-pro/', '', $block_name );
					$all_blocks[] = 'pro-' . $short_name;
				}
			}
		}

		// Cache for 12 hours.
		wp_cache_set( $cache_key, $all_blocks, 'spectra', 12 * HOUR_IN_SECONDS );

		return $all_blocks;
	}

	/**
	 * Get cached analytics data to avoid expensive recalculations.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Comprehensive analytics data.
	 */
	private function get_cached_analytics_data() {
		$cache_key   = 'spectra_3_comprehensive_analytics';
		$cached_data = wp_cache_get( $cache_key, 'spectra' );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		// Calculate comprehensive analytics.
		$usage_stats      = $this->get_usage_statistics();
		$top_blocks       = $this->get_top_used_blocks( 5 );
		$available_blocks = $this->get_available_blocks();

		$total_available = count( $available_blocks );
		$total_used      = count( $usage_stats['most_used_blocks'] ?? array() );
		$adoption_rate   = $total_available > 0 ? round( ( $total_used / $total_available ) * 100, 2 ) : 0;

		$analytics_data = array(
			'total_posts_with_blocks' => $usage_stats['total_posts_with_blocks'] ?? 0,
			'total_block_instances'   => $usage_stats['total_block_instances'] ?? 0,
			'unique_blocks_used'      => $total_used,
			'total_blocks_available'  => $total_available,
			'adoption_rate_percent'   => $adoption_rate,
			'top_blocks'              => $top_blocks,
			'most_popular_block'      => ! empty( $top_blocks ) ? array_key_first( $top_blocks ) : '',
		);

		// Cache for 1 hour like SureForms does.
		wp_cache_set( $cache_key, $analytics_data, 'spectra', HOUR_IN_SECONDS );

		return $analytics_data;
	}

	/**
	 * Determine user engagement level based on block usage.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $analytics_data Analytics data array.
	 * @return string User engagement level.
	 */
	private function get_user_engagement_level( $analytics_data ) {
		$posts_with_blocks = $analytics_data['total_posts_with_blocks'];
		$adoption_rate     = $analytics_data['adoption_rate_percent'];

		if ( 0 === $posts_with_blocks ) {
			return 'none';
		}

		if ( $posts_with_blocks >= 10 && $adoption_rate > 60 ) {
			return 'high';
		}

		if ( $posts_with_blocks >= 3 && $adoption_rate > 30 ) {
			return 'medium';
		}

		return 'low';
	}

	/**
	 * Check if analytics tracking is enabled by user opt-in.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return bool True if analytics is enabled, false otherwise.
	 */
	private function is_analytics_enabled() {
		$optin_status = get_option( 'spectra_blocks_usage_optin', 'no' );

		return 'yes' === $optin_status;
	}

	/**
	 * Determine if a block is a root-level block by checking its metadata.
	 *
	 * Root-level blocks are those that:
	 * 1. Don't have a parent specified in block.json
	 * 2. Are not child blocks (don't contain -child- in the name)
	 * 3. Can be inserted directly by users from the block inserter
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $block_name   Block name without the prefix.
	 * @param string $block_prefix Block prefix ('spectra' or 'spectra-pro').
	 * @return bool True if this is a root-level block, false otherwise.
	 */
	private function is_root_level_block( $block_name, $block_prefix = 'spectra-blocks' ) {
		// Quick check: if the block name contains 'child', it's likely a child block.
		if ( strpos( $block_name, 'child' ) !== false ) {
			return false;
		}

		// Get cached root blocks to avoid repeated file system operations.
		$cache_key          = 'spectra_root_blocks_' . $block_prefix;
		$cached_root_blocks = wp_cache_get( $cache_key, 'spectra' );

		if ( false !== $cached_root_blocks ) {
			return in_array( $block_name, $cached_root_blocks, true );
		}

		// Build the list of root-level blocks by analyzing block.json files.
		$root_blocks = $this->build_root_blocks_list( $block_prefix );

		// Cache for 12 hours since block structure doesn't change often.
		wp_cache_set( $cache_key, $root_blocks, 'spectra', 12 * HOUR_IN_SECONDS );

		return in_array( $block_name, $root_blocks, true );
	}

	/**
	 * Build a list of root-level blocks by analyzing block.json files.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $block_prefix Block prefix ('spectra' or 'spectra-pro').
	 * @return array Array of root-level block names.
	 */
	private function build_root_blocks_list( $block_prefix = 'spectra-blocks' ) {
		$root_blocks = array();

		// Determine the blocks directory based on prefix.
		if ( 'spectra-pro' === $block_prefix ) {
			// Check multiple possible locations for Spectra Pro blocks.
			// Resolve the plugins directory by stripping trailing slash before dirname().
			$plugins_base  = wp_normalize_path( dirname( rtrim( SPECTRA_BLOCKS_DIR, '/\\' ) ) ) . '/';
			$possible_dirs = array(
				$plugins_base . 'spectra-pro/spectra-pro-v2/build/blocks/',
				$plugins_base . 'spectra-pro/spectra-pro-v2/src/blocks/',
			);

			$blocks_dir = '';
			foreach ( $possible_dirs as $dir ) {
				if ( is_dir( $dir ) && is_readable( $dir ) ) {
					$blocks_dir = $dir;
					break;
				}
			}

			if ( empty( $blocks_dir ) ) {
				// Apply filter to allow custom directory specification.
				$blocks_dir = apply_filters( 'spectra_pro_blocks_directory', $blocks_dir );
				if ( empty( $blocks_dir ) || ! is_dir( $blocks_dir ) || ! is_readable( $blocks_dir ) ) {
					return $root_blocks;
				}
			}

			$expected_prefix = 'spectra-pro/';
		} else {
			$blocks_dir      = SPECTRA_BLOCKS_DIR . 'build/blocks/';
			$expected_prefix = 'spectra/';
		}

		if ( ! is_dir( $blocks_dir ) || ! is_readable( $blocks_dir ) ) {
			return $root_blocks;
		}

		$block_files = glob( $blocks_dir . '**/block.json' );

		if ( false === $block_files ) {
			return $root_blocks;
		}

		foreach ( $block_files as $block_file ) {
			// Additional security check: ensure we can read the file.
			if ( ! is_readable( $block_file ) ) {
				continue;
			}

			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$file_contents = $wp_filesystem && method_exists( $wp_filesystem, 'get_contents' ) ?
				$wp_filesystem->get_contents( $block_file ) :
				false;
			if ( false === $file_contents ) {
				continue;
			}

			$block_data = json_decode( $file_contents, true );

			// Ensure valid JSON and required fields.
			if ( ! is_array( $block_data ) || ! isset( $block_data['name'] ) ||
				strpos( $block_data['name'], $expected_prefix ) !== 0 ) {
				continue;
			}

			$block_name = str_replace( $expected_prefix, '', $block_data['name'] );

			// Skip if empty block name or clearly a child block.
			if ( empty( $block_name ) || strpos( $block_name, 'child' ) !== false ) {
				continue;
			}

			// Apply security filter to ensure only safe blocks are included.
			$allow_block = apply_filters(
				'spectra_blocks_analytics_allow_root_block',
				true,
				$block_name,
				$block_prefix,
				$block_data
			);

			if ( ! $allow_block ) {
				continue;
			}

			// Check if it has parent restrictions in block.json.
			$has_parent_restriction = false;

			// Check for parent in block.json.
			if ( isset( $block_data['parent'] ) && ! empty( $block_data['parent'] ) ) {
				$has_parent_restriction = true;
			}

			// Check for ancestor restrictions.
			if ( isset( $block_data['ancestor'] ) && ! empty( $block_data['ancestor'] ) ) {
				$has_parent_restriction = true;
			}

			// If no parent/ancestor restrictions, it's a root-level block.
			if ( ! $has_parent_restriction ) {
				$root_blocks[] = $block_name;
			}
		}

		return array_unique( $root_blocks );
	}

	/**
	 * Filter stats data to only include root-level blocks.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $stats Original stats data.
	 * @return array Filtered stats data with only root-level blocks.
	 */
	private function filter_stats_to_root_blocks( $stats ) {
		if ( empty( $stats['most_used_blocks'] ) || ! is_array( $stats['most_used_blocks'] ) ) {
			return $stats;
		}

		$filtered_stats    = $stats;
		$root_level_blocks = array();

		// Filter most_used_blocks to only include root-level blocks.
		foreach ( $stats['most_used_blocks'] as $block_name => $count ) {
			if ( $this->is_root_level_block( $block_name ) ) {
				$root_level_blocks[ $block_name ] = $count;
			}
		}

		$filtered_stats['most_used_blocks'] = $root_level_blocks;
		return $filtered_stats;
	}

	/**
	 * Filter usage data to only include root-level blocks.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $usage_data Original usage data.
	 * @return array Filtered usage data with only root-level blocks.
	 */
	private function filter_usage_data_to_root_blocks( $usage_data ) {
		if ( empty( $usage_data ) || ! is_array( $usage_data ) ) {
			return $usage_data;
		}

		$filtered_data = array();

		foreach ( $usage_data as $post_id => $post_data ) {
			// Skip if marked as trashed in our data.
			if ( ! empty( $post_data['trashed'] ) ) {
				continue;
			}

			// Verify post still exists and is not trashed.
			$post_status = get_post_status( $post_id );
			if ( false === $post_status || 'trash' === $post_status ) {
				continue;
			}

			if ( empty( $post_data['blocks'] ) || ! is_array( $post_data['blocks'] ) ) {
				continue;
			}

			// Filter blocks to only include root-level blocks.
			$root_level_blocks = array();
			foreach ( $post_data['blocks'] as $block_name ) {
				if ( $this->is_root_level_block( $block_name ) ) {
					$root_level_blocks[] = $block_name;
				}
			}

			// Only include post data if it has root-level blocks.
			if ( ! empty( $root_level_blocks ) ) {
				$filtered_data[ $post_id ] = array(
					'blocks'  => $root_level_blocks,
					'count'   => count( $root_level_blocks ),
					'updated' => $post_data['updated'] ?? time(),
				);
			}
		}

		return $filtered_data;
	}

	/**
	 * Check if Spectra Pro plugin is available and active.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return bool True if Spectra Pro is available, false otherwise.
	 */
	private function is_spectra_pro_available() {
		// Check if Spectra Pro plugin directory exists using function-based path resolution.
		$plugins_base   = wp_normalize_path( dirname( rtrim( SPECTRA_BLOCKS_DIR, '/\\' ) ) ) . '/';
		$pro_plugin_dir = $plugins_base . 'spectra-pro/spectra-pro-v2';
		if ( ! is_dir( $pro_plugin_dir ) ) {
			return false;
		}

		// Cross-plugin extension point — spectra_ prefix is intentional; spectra-blocks-pro hooks into this filter.
		return apply_filters( 'spectra_blocks_analytics_include_pro_blocks', true );
	}

	/**
	 * Clear analytics cache when data is updated.
	 *
	 * @since 3.0.0-beta.1
	 */
	private function clear_analytics_cache() {
		wp_cache_delete( 'spectra_3_comprehensive_analytics', 'spectra' );
		wp_cache_delete( 'spectra_all_registered_blocks', 'spectra' );
	}

	/**
	 * Get the full block name with prefix for analytics.
	 *
	 * Converts internal storage format back to full block names:
	 * - 'container' -> 'spectra/container'
	 * - 'pro-loop-builder' -> 'spectra-pro/loop-builder'
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $block_name Block name (may be prefixed with 'pro-').
	 * @return string Full block name with namespace.
	 */
	private function get_full_block_name( $block_name ) {
		// Check if this is a Pro block (prefixed with 'pro-').
		if ( strpos( $block_name, 'pro-' ) === 0 ) {
			// Remove 'pro-' prefix and add 'spectra-pro/' namespace.
			return 'spectra-pro/' . substr( $block_name, 4 );
		}

		// Default to Spectra Blocks namespace.
		return 'spectra/' . $block_name;
	}
}
