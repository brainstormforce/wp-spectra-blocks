<?php
/**
 * Extension Usage Analytics Tracker for Spectra 3.
 *
 * @package Spectra
 */

namespace SpectraBlocks\Analytics;

use SpectraBlocks\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Extension Usage Tracker for BSF Analytics integration.
 *
 * This class tracks usage of Spectra 3 extensions and integrates with the existing
 * BSF Analytics system from the parent Spectra 2.x.x implementation.
 *
 * @since 3.0.0-beta.1
 */
class ExtensionUsageTracker {

	use Singleton;

	/**
	 * Extension analytics data storage key.
	 */
	const ANALYTICS_KEY = 'spectra_extension_analytics';

	/**
	 * Cache key for available extensions.
	 */
	const EXTENSIONS_CACHE_KEY = 'spectra_available_extensions';

	/**
	 * Initialize the extension analytics tracker.
	 *
	 * @since 3.0.0-beta.1
	 */
	public function init() {
		// Hook into WordPress save_post to track extension usage.
		add_action( 'save_post', array( $this, 'track_post_extension_usage' ), 10, 2 );

		// Hook into BSF Analytics stats collection.
		add_filter( 'bsf_core_stats', array( $this, 'add_extension_stats' ), 25 );

		// Hook into settings changes to handle cleanup.
		add_action( 'update_option_spectra_analytics_optin', array( $this, 'handle_analytics_toggle' ), 10, 2 );

		// Initialize usage data if not exists.
		$this->init_extension_data();
	}

	/**
	 * Initialize extension usage data storage.
	 *
	 * @since 3.0.0-beta.1
	 */
	private function init_extension_data() {
		if ( false === get_option( self::ANALYTICS_KEY, false ) ) {
			$initial_data = array(
				'usage_data' => array(), // Post-specific extension usage.
				'statistics' => array(
					'extensions_used'       => array(),
					'posts_with_extensions' => 0,
					'most_used_extensions'  => array(),
					'last_updated'          => time(),
				),
			);
			// Store as non-autoloaded to improve performance.
			add_option( self::ANALYTICS_KEY, $initial_data, '', 'no' );
		}
	}

	/**
	 * Track extension usage when a post is saved.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param int     $post_id Post ID being saved.
	 * @param WP_Post $post    Post object being saved.
	 */
	public function track_post_extension_usage( $post_id, $post ) {
		// Skip if user has not opted in for analytics.
		if ( ! $this->is_analytics_enabled() ) {
			return;
		}

		// Skip revisions and auto-saves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Only track posts with Gutenberg content.
		if ( ! has_blocks( $post->post_content ) ) {
			return;
		}

		$blocks          = parse_blocks( $post->post_content );
		$extension_usage = $this->extract_extension_usage( $blocks );

		if ( ! empty( $extension_usage ) ) {
			$this->update_extension_data( $post_id, $extension_usage );
			$this->update_extension_statistics( $extension_usage );
			$this->clear_extension_cache();
		}
	}

	/**
	 * Extract extension usage from parsed blocks array.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $blocks Parsed blocks array.
	 * @return array Array of extensions used.
	 */
	private function extract_extension_usage( $blocks ) {
		$extensions_used = array();

		foreach ( $blocks as $block ) {
			// Only check Spectra blocks.
			if ( strpos( $block['blockName'], 'spectra/' ) !== 0 ) {
				if ( ! empty( $block['innerBlocks'] ) ) {
					$inner_extensions = $this->extract_extension_usage( $block['innerBlocks'] );
					$extensions_used  = array_merge( $extensions_used, $inner_extensions );
				}
				continue;
			}

			// Check block attributes for extension usage.
			if ( ! empty( $block['attrs'] ) ) {
				$block_extensions = $this->detect_extension_usage_in_attributes( $block['attrs'] );
				$extensions_used  = array_merge( $extensions_used, $block_extensions );
			}

			// Process inner blocks recursively.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner_extensions = $this->extract_extension_usage( $block['innerBlocks'] );
				$extensions_used  = array_merge( $extensions_used, $inner_extensions );
			}
		}

		return array_unique( $extensions_used );
	}

	/**
	 * Detect extension usage based on block attributes.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $attributes Block attributes.
	 * @return array Array of extensions detected.
	 */
	private function detect_extension_usage_in_attributes( $attributes ) {
		$extensions = array();

		// Check for Animation extension usage.
		if ( ! empty( $attributes['spectraAnimationType'] ) ) {
			$extensions[] = 'animations';
		}

		// Check for Image Mask extension usage.
		if ( ! empty( $attributes['spectraImageMask'] ) && ! empty( $attributes['spectraImageMask']['enable'] ) ) {
			$extensions[] = 'image-mask';
		}

		// Check for Z-Index extension usage.
		if ( isset( $attributes['spectraZIndex'] ) && '' !== $attributes['spectraZIndex'] ) {
			$extensions[] = 'z-index';
		}

		// Check for Responsive Controls extension usage.
		$responsive_attributes = array( 'mobile', 'tablet', 'desktop' );
		foreach ( $attributes as $attr_name => $attr_value ) {
			foreach ( $responsive_attributes as $device ) {
				if ( strpos( $attr_name, ucfirst( $device ) ) !== false && ! empty( $attr_value ) ) {
					$extensions[] = 'responsive-controls';
					break 2; // Break both loops once found.
				}
			}
		}

		// Cross-plugin extension points — spectra_ prefix is intentional; spectra-blocks-pro hooks into these filters.
		return apply_filters( 'spectra_blocks_analytics_detected_extensions', array_unique( $extensions ), $attributes );
	}

	/**
	 * Update extension usage data for a specific post.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param int   $post_id Post ID.
	 * @param array $extensions Array of extension names used in the post.
	 */
	private function update_extension_data( $post_id, $extensions ) {
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );

		// Ensure structure exists.
		if ( ! isset( $analytics_data['usage_data'] ) ) {
			$analytics_data['usage_data'] = array();
		}

		$analytics_data['usage_data'][ $post_id ] = array(
			'extensions' => $extensions,
			'count'      => count( $extensions ),
			'updated'    => time(),
		);

		update_option( self::ANALYTICS_KEY, $analytics_data, 'no' );
	}

	/**
	 * Update overall extension usage statistics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $extensions Array of extension names used.
	 */
	private function update_extension_statistics( $extensions ) {
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );

		// Ensure structure exists.
		if ( ! isset( $analytics_data['statistics'] ) ) {
			$analytics_data['statistics'] = array(
				'extensions_used'       => array(),
				'posts_with_extensions' => 0,
				'most_used_extensions'  => array(),
				'last_updated'          => time(),
			);
		}

		$stats = &$analytics_data['statistics'];

		// Initialize stats if malformed.
		if ( ! is_array( $stats ) || ! isset( $stats['most_used_extensions'] ) ) {
			$stats = array(
				'extensions_used'       => array(),
				'posts_with_extensions' => 0,
				'most_used_extensions'  => array(),
				'last_updated'          => time(),
			);
		}

		// Update total posts count.
		$stats['posts_with_extensions'] = $this->get_total_posts_with_extensions();

		// Update most used extensions counter.
		foreach ( $extensions as $extension_name ) {
			if ( ! isset( $stats['most_used_extensions'][ $extension_name ] ) ) {
				$stats['most_used_extensions'][ $extension_name ] = 0;
			}
			++$stats['most_used_extensions'][ $extension_name ];
		}

		// Update timestamp.
		$stats['last_updated'] = time();

		update_option( self::ANALYTICS_KEY, $analytics_data, 'no' );
	}

	/**
	 * Get total number of posts containing Spectra 3 extensions.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return int Total post count.
	 */
	private function get_total_posts_with_extensions() {
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );
		return count( $analytics_data['usage_data'] ?? array() );
	}

	/**
	 * Get extension usage statistics for analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Extension usage statistics.
	 */
	public function get_extension_statistics() {
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );
		$stats          = $analytics_data['statistics'] ?? array();
		$extension_data = $analytics_data['usage_data'] ?? array();

		// Calculate additional metrics.
		$total_extension_instances = 0;
		foreach ( $extension_data as $post_data ) {
			$total_extension_instances += $post_data['count'];
		}

		// Get available extensions.
		$available_extensions = $this->get_available_extensions();

		return array_merge(
			$stats,
			array(
				'total_extension_instances'   => $total_extension_instances,
				'unique_extensions_used'      => count( $stats['most_used_extensions'] ?? array() ),
				'total_extensions_available'  => count( $available_extensions ),
				'extension_adoption_rate'     => count( $available_extensions ) > 0
					? round( ( count( $stats['most_used_extensions'] ?? array() ) / count( $available_extensions ) ) * 100, 2 )
					: 0,
				'average_extensions_per_post' => empty( $stats['posts_with_extensions'] )
					? 0
					: round( $total_extension_instances / $stats['posts_with_extensions'], 2 ),
			)
		);
	}

	/**
	 * Get top N most used extensions.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param int $limit Number of top extensions to return.
	 * @return array Top used extensions with usage counts.
	 */
	public function get_top_used_extensions( $limit = 10 ) {
		$stats     = $this->get_extension_statistics();
		$most_used = $stats['most_used_extensions'] ?? array();

		arsort( $most_used );
		return array_slice( $most_used, 0, $limit, true );
	}

	/**
	 * Get all available Spectra 3 extensions.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Array of available extension names.
	 */
	public function get_available_extensions() {
		// Check cache first.
		$cached_extensions = wp_cache_get( self::EXTENSIONS_CACHE_KEY, 'spectra' );
		if ( false !== $cached_extensions ) {
			return $cached_extensions;
		}

		// Dynamically discover extensions from src/extensions directory.
		$extensions_dir       = SPECTRA_BLOCKS_DIR . 'src/extensions/';
		$available_extensions = array();

		if ( is_dir( $extensions_dir ) && is_readable( $extensions_dir ) ) {
			$extension_dirs = array_filter( glob( $extensions_dir . '*' ), 'is_dir' );

			foreach ( $extension_dirs as $extension_path ) {
				$extension_name = basename( $extension_path );

				// Skip hidden directories and common non-extension files.
				if ( strpos( $extension_name, '.' ) === 0 ) {
					continue;
				}

				// Verify it's a valid extension by checking for index.js.
				if ( file_exists( $extension_path . '/index.js' ) ) {
					$available_extensions[] = $extension_name;
				}
			}
		}

		// Cross-plugin extension point — spectra_ prefix is intentional; spectra-blocks-pro hooks into this filter.
		$available_extensions = apply_filters( 'spectra_blocks_analytics_available_extensions', $available_extensions );

		// Cache for 6 hours since extensions don't change often.
		wp_cache_set( self::EXTENSIONS_CACHE_KEY, $available_extensions, 'spectra', 6 * HOUR_IN_SECONDS );

		return $available_extensions;
	}

	/**
	 * Get cached extension analytics data to avoid expensive recalculations.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Comprehensive extension analytics data.
	 */
	public function get_cached_extension_analytics() {
		$cache_key   = 'spectra_3_extension_analytics';
		$cached_data = wp_cache_get( $cache_key, 'spectra' );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		// Calculate comprehensive extension analytics.
		$extension_stats = $this->get_extension_statistics();
		$top_extensions  = $this->get_top_used_extensions( 3 );

		$analytics_data = array(
			'posts_with_extensions'           => $extension_stats['posts_with_extensions'] ?? 0,
			'total_extension_instances'       => $extension_stats['total_extension_instances'] ?? 0,
			'unique_extensions_used'          => $extension_stats['unique_extensions_used'] ?? 0,
			'total_extensions_available'      => $extension_stats['total_extensions_available'] ?? 4,
			'extension_adoption_rate_percent' => $extension_stats['extension_adoption_rate'] ?? 0,
			'average_extensions_per_post'     => $extension_stats['average_extensions_per_post'] ?? 0,
			'top_extensions'                  => $top_extensions,
			'most_popular_extension'          => ! empty( $top_extensions ) ? array_key_first( $top_extensions ) : '',
			'extension_engagement_level'      => $this->get_extension_engagement_level( $extension_stats ),
		);

		// Cache for 1 hour.
		wp_cache_set( $cache_key, $analytics_data, 'spectra', HOUR_IN_SECONDS );

		return $analytics_data;
	}

	/**
	 * Determine extension engagement level based on usage.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $extension_stats Extension statistics data.
	 * @return string Extension engagement level.
	 */
	private function get_extension_engagement_level( $extension_stats ) {
		$posts_with_extensions = $extension_stats['posts_with_extensions'] ?? 0;
		$adoption_rate         = $extension_stats['extension_adoption_rate'] ?? 0;

		if ( 0 === $posts_with_extensions ) {
			return 'none';
		}

		if ( $posts_with_extensions >= 5 && $adoption_rate > 75 ) {
			return 'high';
		}

		if ( $posts_with_extensions >= 2 && $adoption_rate > 50 ) {
			return 'medium';
		}

		return 'low';
	}

	/**
	 * Add extension statistics to BSF Analytics data.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param array $stats Existing BSF Analytics stats.
	 * @return array Enhanced stats with extension data.
	 */
	public function add_extension_stats( $stats ) {
		// Skip if user has not opted in for analytics.
		if ( ! $this->is_analytics_enabled() ) {
			return $stats;
		}

		// Ensure the spectra plugin data container exists.
		if ( empty( $stats['plugin_data']['spectra'] ) || ! is_array( $stats['plugin_data']['spectra'] ) ) {
			$stats['plugin_data']['spectra'] = array();
		}

		// Get comprehensive extension analytics with caching.
		$extension_analytics = $this->get_cached_extension_analytics();

		// Add extension usage data using SureForms structure.
		$stats['plugin_data']['spectra']['spectra_3_extensions'] = array(
			'numeric_values'             => array(
				'posts_with_extensions'           => $extension_analytics['posts_with_extensions'],
				'total_extension_instances'       => $extension_analytics['total_extension_instances'],
				'unique_extensions_used'          => $extension_analytics['unique_extensions_used'],
				'total_extensions_available'      => $extension_analytics['total_extensions_available'],
				'extension_adoption_rate_percent' => $extension_analytics['extension_adoption_rate_percent'],
				'average_extensions_per_post'     => $extension_analytics['average_extensions_per_post'],
			),
			'boolean_values'             => array(
				'extensions_actively_used'     => $extension_analytics['unique_extensions_used'] > 0,
				'high_extension_adoption_rate' => $extension_analytics['extension_adoption_rate_percent'] > 50,
			),
			'top_used_extensions'        => $extension_analytics['top_extensions'],
			'most_popular_extension'     => $extension_analytics['most_popular_extension'],
			'extension_engagement_level' => $extension_analytics['extension_engagement_level'],
		);

		// Add extension-specific analytics data.
		$stats['plugin_data']['spectra']['spectra_3_extensions']['extension_specific_data'] = $this->get_extension_specific_analytics();

		return $stats;
	}

	/**
	 * Get extension-specific analytics data for all extensions.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Extension-specific analytics data.
	 */
	private function get_extension_specific_analytics() {
		$specific_data        = array();
		$available_extensions = $this->get_available_extensions();

		foreach ( $available_extensions as $extension_name ) {
			// Call extension-specific analytics method if it exists.
			$method_name = 'get_' . str_replace( '-', '_', $extension_name ) . '_specific_analytics';

			if ( method_exists( $this, $method_name ) ) {
				$specific_data[ $extension_name ] = $this->$method_name();
			}

			// Cross-plugin extension point — spectra_ prefix is intentional; spectra-blocks-pro hooks into this filter.
			$specific_data[ $extension_name ] = apply_filters(
				"spectra_blocks_analytics_extension_specific_{$extension_name}",
				$specific_data[ $extension_name ] ?? array(),
				$this->get_extension_usage_data( $extension_name )
			);

			// Remove empty entries.
			if ( empty( $specific_data[ $extension_name ] ) ) {
				unset( $specific_data[ $extension_name ] );
			}
		}

		return $specific_data;
	}

	/**
	 * Get usage data for a specific extension.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @param string $extension_name Extension name.
	 * @return array Usage data for the specific extension.
	 */
	private function get_extension_usage_data( $extension_name ) {
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );
		$usage_data     = $analytics_data['usage_data'] ?? array();

		$extension_usage = array();

		foreach ( $usage_data as $post_id => $post_data ) {
			if ( in_array( $extension_name, $post_data['extensions'] ?? array(), true ) ) {
				$extension_usage[ $post_id ] = $post_data;
			}
		}

		return $extension_usage;
	}

	/**
	 * Get animations extension-specific analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Animations-specific analytics data.
	 */
	private function get_animations_specific_analytics() {
		// This method tracks which animation types are most used.
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );
		$usage_data     = $analytics_data['usage_data'] ?? array();

		$animation_types           = array();
		$total_animation_instances = 0;

		foreach ( $usage_data as $post_data ) {
			if ( ! in_array( 'animations', $post_data['extensions'] ?? array(), true ) ) {
				continue;
			}

			// In a real implementation, you'd parse the post content to extract
			// specific animation types from block attributes like spectraAnimationType.
			// For now, we'll use a placeholder structure.
			++$total_animation_instances;

			// Placeholder: In real implementation, extract from post content.
			$animation_types['fadeIn'] = ( $animation_types['fadeIn'] ?? 0 ) + 1;
		}

		if ( 0 === $total_animation_instances ) {
			return array();
		}

		arsort( $animation_types );

		return array(
			'total_animation_instances' => $total_animation_instances,
			'animation_types_used'      => $animation_types,
			'most_popular_animation'    => ! empty( $animation_types ) ? array_key_first( $animation_types ) : '',
			'unique_animation_types'    => count( $animation_types ),
		);
	}

	/**
	 * Get image-mask extension-specific analytics.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Image-mask-specific analytics data.
	 */
	private function get_image_mask_specific_analytics() {
		// This method tracks which mask types are most used.
		$analytics_data = get_option( self::ANALYTICS_KEY, array() );
		$usage_data     = $analytics_data['usage_data'] ?? array();

		$mask_types           = array();
		$total_mask_instances = 0;

		foreach ( $usage_data as $post_data ) {
			if ( ! in_array( 'image-mask', $post_data['extensions'] ?? array(), true ) ) {
				continue;
			}

			// In a real implementation, you'd parse the post content to extract
			// specific mask types from block attributes like spectraImageMask.
			// For now, we'll use a placeholder structure.
			++$total_mask_instances;

			// Placeholder: In real implementation, extract from post content.
			$mask_types['circle'] = ( $mask_types['circle'] ?? 0 ) + 1;
		}

		if ( 0 === $total_mask_instances ) {
			return array();
		}

		arsort( $mask_types );

		return array(
			'total_mask_instances' => $total_mask_instances,
			'mask_types_used'      => $mask_types,
			'most_popular_mask'    => ! empty( $mask_types ) ? array_key_first( $mask_types ) : '',
			'unique_mask_types'    => count( $mask_types ),
		);
	}

	/**
	 * Reset all extension analytics data (for testing/debugging).
	 *
	 * @since 3.0.0-beta.1
	 */
	public function reset_extension_analytics_data() {
		delete_option( self::ANALYTICS_KEY );
		$this->init_extension_data();
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
			$this->clear_extension_cache();
		}
	}

	/**
	 * Clear extension analytics cache when data is updated.
	 *
	 * @since 3.0.0-beta.1
	 */
	private function clear_extension_cache() {
		wp_cache_delete( 'spectra_3_extension_analytics', 'spectra' );
		wp_cache_delete( self::EXTENSIONS_CACHE_KEY, 'spectra' );
	}

	/**
	 * Check if analytics tracking is enabled by user opt-in.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return bool True if analytics is enabled, false otherwise.
	 */
	private function is_analytics_enabled() {
		$optin_status = \Spectra_Blocks_Settings::get( 'analytics_optin', 'no' );

		return 'yes' === $optin_status;
	}
}
