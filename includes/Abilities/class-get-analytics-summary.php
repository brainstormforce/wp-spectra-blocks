<?php
/**
 * Get Analytics Summary ability.
 *
 * Returns complete analytics data — block usage, extension usage, site activity.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use SpectraBlocks\AnalyticsManager;

defined( 'ABSPATH' ) || exit;

/**
 * GetAnalyticsSummary ability class.
 *
 * @since 1.0.0
 */
class GetAnalyticsSummary extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/get-analytics-summary';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Get Spectra Analytics Summary', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Returns complete analytics data including block usage statistics, top used blocks, extension usage, adoption rates, and site activity level.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-discovery';
	}

	/**
	 * Get ability annotations for REST discovery.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_annotations(): array {
		return array(
			'readonly'    => true,
			'destructive' => false,
			'idempotent'  => true,
		);
	}

	/**
	 * Get the input schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => new \stdClass(),
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'block_usage_stats'       => array(
					'type'        => 'object',
					'description' => __( 'Block usage statistics including total posts, most used blocks, and distribution.', 'spectra-blocks' ),
				),
				'top_used_blocks'         => array(
					'type'        => 'object',
					'description' => __( 'Top used blocks with usage counts.', 'spectra-blocks' ),
				),
				'block_adoption_rate'     => array(
					'type'        => 'object',
					'description' => __( 'Block adoption metrics — used vs available blocks.', 'spectra-blocks' ),
				),
				'site_activity'           => array(
					'type'        => 'object',
					'description' => __( 'Site activity level — inactive, active_site, or super_site.', 'spectra-blocks' ),
				),
				'extension_usage_stats'   => array(
					'type'        => 'object',
					'description' => __( 'Extension usage statistics.', 'spectra-blocks' ),
				),
				'extension_adoption_rate' => array(
					'type'        => 'object',
					'description' => __( 'Extension adoption metrics.', 'spectra-blocks' ),
				),
			),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array Analytics summary data.
	 */
	public function execute( array $params ): array {
		$manager = AnalyticsManager::instance();

		return $manager->get_block_analytics_summary();
	}
}
