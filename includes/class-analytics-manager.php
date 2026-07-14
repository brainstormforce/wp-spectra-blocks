<?php
/**
 * Analytics Manager for Spectra 3 Block Usage Tracking.
 *
 * @package Spectra
 *
 * @since 3.0.0-beta.1
 */

namespace SpectraBlocks;

use SpectraBlocks\Traits\Singleton;
use Spectra\Analytics\BlockUsageTracker;
use SpectraBlocks\Analytics\ExtensionUsageTracker;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics Manager for Spectra 3 Block and Extension Usage.
 *
 * Focused specifically on comprehensive analytics to identify:
 * - Most used blocks and extensions
 * - Usage patterns and adoption rates
 * - How many of the provided features are actually used by users
 *
 * @since 3.0.0-beta.1
 */
class AnalyticsManager {

	use Singleton;

	/**
	 * Initialize the block and extension analytics.
	 *
	 * @since 3.0.0-beta.1
	 */
	public function init() {
		// Initialize block usage tracker.
		( BlockUsageTracker::instance() )->init();

		// Initialize extension usage tracker separately.
		( ExtensionUsageTracker::instance() )->init();
	}

	/**
	 * Get comprehensive block and extension analytics summary.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Block and extension analytics summary.
	 */
	public function get_block_analytics_summary() {
		$block_tracker     = BlockUsageTracker::instance();
		$extension_tracker = ExtensionUsageTracker::instance();

		return array(
			// Block analytics.
			'block_usage_stats'       => $block_tracker->get_usage_statistics(),
			'top_used_blocks'         => $block_tracker->get_top_used_blocks( 10 ),
			'all_available_blocks'    => $block_tracker->get_available_blocks(),
			'block_adoption_rate'     => $this->calculate_block_adoption_rate(),
			// Extension analytics.
			'extension_usage_stats'   => $extension_tracker->get_extension_statistics(),
			'top_used_extensions'     => $extension_tracker->get_top_used_extensions( 4 ),
			'extension_adoption_rate' => $this->calculate_extension_adoption_rate(),
		);
	}

	/**
	 * Calculate what percentage of available blocks are actually being used.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Block adoption statistics.
	 */
	private function calculate_block_adoption_rate() {
		$tracker          = BlockUsageTracker::instance();
		$available_blocks = $tracker->get_available_blocks();
		$usage_stats      = $tracker->get_usage_statistics();
		$used_blocks      = $usage_stats['most_used_blocks'] ?? array();

		$total_available = count( $available_blocks );
		$total_used      = count( $used_blocks );
		$adoption_rate   = $total_available > 0 ? round( ( $total_used / $total_available ) * 100, 2 ) : 0;

		return array(
			'total_blocks_available' => $total_available,
			'total_blocks_used'      => $total_used,
			'adoption_rate_percent'  => $adoption_rate,
			'unused_blocks'          => array_diff( $available_blocks, array_keys( $used_blocks ) ),
		);
	}

	/**
	 * Calculate what percentage of available extensions are actually being used.
	 *
	 * @since 3.0.0-beta.1
	 *
	 * @return array Extension adoption statistics.
	 */
	private function calculate_extension_adoption_rate() {
		$tracker         = ExtensionUsageTracker::instance();
		$extension_stats = $tracker->get_extension_statistics();
		$used_extensions = $extension_stats['most_used_extensions'] ?? array();

		$available_extensions = array( 'animations', 'image-mask', 'responsive-controls', 'z-index' );
		$total_available      = count( $available_extensions );
		$total_used           = count( $used_extensions );
		$adoption_rate        = $total_available > 0 ? round( ( $total_used / $total_available ) * 100, 2 ) : 0;

		return array(
			'total_extensions_available' => $total_available,
			'total_extensions_used'      => $total_used,
			'adoption_rate_percent'      => $adoption_rate,
			'unused_extensions'          => array_diff( $available_extensions, array_keys( $used_extensions ) ),
		);
	}
}
