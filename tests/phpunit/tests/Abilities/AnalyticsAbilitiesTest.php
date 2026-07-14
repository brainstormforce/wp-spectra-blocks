<?php
/**
 * Tests for Analytics abilities: GetAnalyticsSummary.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use SpectraBlocks\Abilities\GetAnalyticsSummary;

/**
 * Analytics abilities test case.
 */
class AnalyticsAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user can edit.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
	}

	/**
	 * Test GetAnalyticsSummary metadata.
	 */
	public function test_get_analytics_summary_metadata() {
		$ability = GetAnalyticsSummary::instance();

		$this->assertSame( 'spectra-blocks/get-analytics-summary', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-discovery', $ability->get_category() );
		$this->assertNotEmpty( $ability->get_label() );
		$this->assertNotEmpty( $ability->get_description() );
	}

	/**
	 * Test GetAnalyticsSummary has empty input schema.
	 */
	public function test_get_analytics_summary_empty_input_schema() {
		$schema = GetAnalyticsSummary::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertInstanceOf( stdClass::class, $schema['properties'] );
	}

	/**
	 * Test GetAnalyticsSummary output schema has expected keys.
	 */
	public function test_get_analytics_summary_output_schema() {
		$schema = GetAnalyticsSummary::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_usage_stats', $schema['properties'] );
		$this->assertArrayHasKey( 'top_used_blocks', $schema['properties'] );
		$this->assertArrayHasKey( 'site_activity', $schema['properties'] );
	}

	/**
	 * Test GetAnalyticsSummary execute returns array.
	 */
	public function test_get_analytics_summary_execute() {
		if ( ! class_exists( 'Spectra\AnalyticsManager' ) ) {
			$this->markTestSkipped( 'AnalyticsManager not available.' );
		}

		$result = GetAnalyticsSummary::instance()->execute( array() );

		$this->assertIsArray( $result );
	}

	/**
	 * Test GetAnalyticsSummary execute returns expected keys.
	 */
	public function test_get_analytics_summary_execute_structure() {
		if ( ! class_exists( 'Spectra\AnalyticsManager' ) ) {
			$this->markTestSkipped( 'AnalyticsManager not available.' );
		}

		$result = GetAnalyticsSummary::instance()->execute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'block_usage_stats', $result );
		$this->assertArrayHasKey( 'top_used_blocks', $result );
		$this->assertArrayHasKey( 'extension_usage_stats', $result );
	}

	/**
	 * Test GetAnalyticsSummary output schema has all expected keys.
	 */
	public function test_get_analytics_summary_output_schema_complete() {
		$schema = GetAnalyticsSummary::instance()->get_output_schema();

		$this->assertArrayHasKey( 'block_adoption_rate', $schema['properties'] );
		$this->assertArrayHasKey( 'extension_usage_stats', $schema['properties'] );
		$this->assertArrayHasKey( 'extension_adoption_rate', $schema['properties'] );
	}

	/**
	 * Test GetAnalyticsSummary uses default edit_posts permission.
	 */
	public function test_get_analytics_summary_permission_editor() {
		$this->assertTrue( GetAnalyticsSummary::instance()->check_permission() );
	}

	/**
	 * Test GetAnalyticsSummary denies subscribers.
	 */
	public function test_get_analytics_summary_permission_subscriber() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$result = GetAnalyticsSummary::instance()->check_permission();
		$this->assertWPError( $result );
	}
}
