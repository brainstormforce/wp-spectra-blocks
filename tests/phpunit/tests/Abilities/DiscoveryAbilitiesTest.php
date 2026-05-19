<?php
/**
 * Tests for Discovery abilities: ListAvailableBlocks and GetBlockConfig.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use Spectra\Abilities\ListAvailableBlocks;
use Spectra\Abilities\GetBlockConfig;

/**
 * Discovery abilities test case.
 */
class DiscoveryAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user can edit.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
	}

	// -------------------------------------------------------------------------
	// ListAvailableBlocks
	// -------------------------------------------------------------------------

	/**
	 * Test ListAvailableBlocks metadata.
	 */
	public function test_list_available_blocks_metadata() {
		$ability = ListAvailableBlocks::instance();

		$this->assertSame( 'spectra-blocks/list-available-blocks', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-discovery', $ability->get_category() );
		$this->assertNotEmpty( $ability->get_label() );
		$this->assertNotEmpty( $ability->get_description() );
	}

	/**
	 * Test ListAvailableBlocks input schema structure.
	 */
	public function test_list_available_blocks_input_schema() {
		$schema = ListAvailableBlocks::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'type', $schema['properties'] );
		$this->assertSame( array( 'all', 'parent', 'child' ), $schema['properties']['type']['enum'] );
	}

	/**
	 * Test ListAvailableBlocks output schema structure.
	 */
	public function test_list_available_blocks_output_schema() {
		$schema = ListAvailableBlocks::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'blocks', $schema['properties'] );
		$this->assertArrayHasKey( 'count', $schema['properties'] );
	}

	/**
	 * Test ListAvailableBlocks execute returns expected structure.
	 */
	public function test_list_available_blocks_execute_returns_blocks_array() {
		$result = ListAvailableBlocks::instance()->execute( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'blocks', $result );
		$this->assertArrayHasKey( 'count', $result );
		$this->assertIsArray( $result['blocks'] );
		$this->assertSame( count( $result['blocks'] ), $result['count'] );
	}

	/**
	 * Test ListAvailableBlocks execute with explicit 'all' type.
	 */
	public function test_list_available_blocks_execute_with_all_type() {
		$result = ListAvailableBlocks::instance()->execute( array( 'type' => 'all' ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'blocks', $result );
	}

	/**
	 * Test ListAvailableBlocks execute with 'parent' type.
	 */
	public function test_list_available_blocks_execute_with_parent_filter() {
		$result = ListAvailableBlocks::instance()->execute( array( 'type' => 'parent' ) );

		$this->assertIsArray( $result );
		// Parent blocks should have no parent property or empty parent.
		foreach ( $result['blocks'] as $block ) {
			$this->assertEmpty( $block['parent'], "Block '{$block['name']}' should be a parent block (no parent set)." );
		}
	}

	/**
	 * Test ListAvailableBlocks execute with 'child' type.
	 */
	public function test_list_available_blocks_execute_with_child_filter() {
		$result = ListAvailableBlocks::instance()->execute( array( 'type' => 'child' ) );

		$this->assertIsArray( $result );
		// Child blocks should have a non-empty parent property.
		foreach ( $result['blocks'] as $block ) {
			$this->assertNotEmpty( $block['parent'], "Block '{$block['name']}' should be a child block (parent set)." );
		}
	}

	/**
	 * Test ListAvailableBlocks defaults to 'all' for invalid type.
	 */
	public function test_list_available_blocks_execute_invalid_type_defaults_to_all() {
		$all_result     = ListAvailableBlocks::instance()->execute( array( 'type' => 'all' ) );
		$invalid_result = ListAvailableBlocks::instance()->execute( array( 'type' => 'invalid' ) );

		$this->assertSame( $all_result['count'], $invalid_result['count'] );
	}

	/**
	 * Test ListAvailableBlocks blocks are sorted alphabetically.
	 */
	public function test_list_available_blocks_are_sorted() {
		$result = ListAvailableBlocks::instance()->execute( array() );

		if ( count( $result['blocks'] ) < 2 ) {
			$this->markTestSkipped( 'Need at least 2 blocks to test sorting.' );
		}

		$names  = array_column( $result['blocks'], 'name' );
		$sorted = $names;
		sort( $sorted );
		$this->assertSame( $sorted, $names );
	}

	/**
	 * Test ListAvailableBlocks block entries have expected keys.
	 */
	public function test_list_available_blocks_entry_structure() {
		$result = ListAvailableBlocks::instance()->execute( array() );

		if ( empty( $result['blocks'] ) ) {
			$this->markTestSkipped( 'No blocks registered.' );
		}

		$block = $result['blocks'][0];
		$this->assertArrayHasKey( 'name', $block );
		$this->assertArrayHasKey( 'title', $block );
		$this->assertArrayHasKey( 'description', $block );
		$this->assertArrayHasKey( 'category', $block );
		$this->assertArrayHasKey( 'parent', $block );
		$this->assertArrayHasKey( 'keywords', $block );
	}

	// -------------------------------------------------------------------------
	// GetBlockConfig
	// -------------------------------------------------------------------------

	/**
	 * Test GetBlockConfig metadata.
	 */
	public function test_get_block_config_metadata() {
		$ability = GetBlockConfig::instance();

		$this->assertSame( 'spectra-blocks/get-block-config', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-discovery', $ability->get_category() );
		$this->assertNotEmpty( $ability->get_label() );
		$this->assertNotEmpty( $ability->get_description() );
	}

	/**
	 * Test GetBlockConfig input schema requires block_name.
	 */
	public function test_get_block_config_input_schema() {
		$schema = GetBlockConfig::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'block_name', $schema['required'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
	}

	/**
	 * Test GetBlockConfig execute fails without block_name.
	 */
	public function test_get_block_config_execute_missing_block_name() {
		$result = GetBlockConfig::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test GetBlockConfig execute fails for non-existent block.
	 */
	public function test_get_block_config_execute_nonexistent_block() {
		$result = GetBlockConfig::instance()->execute( array( 'block_name' => 'spectra/nonexistent-block' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_not_found', $result->get_error_code() );
	}

	/**
	 * Test GetBlockConfig auto-prepends spectra/ prefix.
	 */
	public function test_get_block_config_adds_spectra_prefix() {
		// Both should resolve the same way (even if block doesn't exist).
		$result_with    = GetBlockConfig::instance()->execute( array( 'block_name' => 'spectra/nonexistent' ) );
		$result_without = GetBlockConfig::instance()->execute( array( 'block_name' => 'nonexistent' ) );

		// Both should fail with the same error since the block doesn't exist.
		$this->assertWPError( $result_with );
		$this->assertWPError( $result_without );
		$this->assertSame( $result_with->get_error_code(), $result_without->get_error_code() );
	}

	/**
	 * Test GetBlockConfig output schema.
	 */
	public function test_get_block_config_output_schema() {
		$schema = GetBlockConfig::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'config', $schema['properties'] );
	}

	/**
	 * Test ListAvailableBlocks denies subscribers.
	 */
	public function test_list_available_blocks_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = ListAvailableBlocks::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test GetBlockConfig denies subscribers.
	 */
	public function test_get_block_config_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = GetBlockConfig::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test ListAvailableBlocks grants access to editors.
	 */
	public function test_list_available_blocks_permission_editor() {
		$this->assertTrue( ListAvailableBlocks::instance()->check_permission() );
	}

	/**
	 * Test GetBlockConfig grants access to editors.
	 */
	public function test_get_block_config_permission_editor() {
		$this->assertTrue( GetBlockConfig::instance()->check_permission() );
	}

	/**
	 * Test GetBlockConfig execute succeeds for a valid block.
	 */
	public function test_get_block_config_execute_valid_block() {
		// Check if the build directory exists with a container block.
		$block_json = SPECTRA_BLOCKS_DIR . 'build/blocks/container/block.json';
		if ( ! file_exists( $block_json ) ) {
			$this->markTestSkipped( 'Build directory not found. Run npm run build first.' );
		}

		$result = GetBlockConfig::instance()->execute( array( 'block_name' => 'spectra/container' ) );

		$this->assertIsArray( $result );
		$this->assertSame( 'spectra/container', $result['block_name'] );
		$this->assertArrayHasKey( 'config', $result );
		$this->assertIsArray( $result['config'] );
	}
}
