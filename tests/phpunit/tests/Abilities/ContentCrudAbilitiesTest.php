<?php
/**
 * Tests for Content CRUD abilities: GetPostContent, UpdateBlockAttributes, RemoveBlock, MoveBlock, DuplicateBlock.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use SpectraBlocks\Abilities\GetPostContent;
use SpectraBlocks\Abilities\UpdateBlockAttributes;
use SpectraBlocks\Abilities\RemoveBlock;
use SpectraBlocks\Abilities\MoveBlock;
use SpectraBlocks\Abilities\DuplicateBlock;

/**
 * Content CRUD abilities test case.
 */
class ContentCrudAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user is editor.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
	}

	/**
	 * Helper: Create a post with block content.
	 *
	 * @param string $content Block content.
	 * @return int Post ID.
	 */
	private function create_post_with_blocks( string $content = '' ): int {
		if ( empty( $content ) ) {
			$content = '<!-- wp:spectra/container {"block_id":"abc123"} --><div class="wp-block-spectra-container"><!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph --></div><!-- /wp:spectra/container -->' . "\n\n" .
				'<!-- wp:spectra/buttons {"block_id":"def456"} --><div class="wp-block-spectra-buttons"></div><!-- /wp:spectra/buttons -->';
		}

		return self::factory()->post->create(
			array(
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);
	}

	// -------------------------------------------------------------------------
	// GetPostContent
	// -------------------------------------------------------------------------

	/**
	 * Test GetPostContent metadata.
	 */
	public function test_get_post_content_metadata() {
		$ability = GetPostContent::instance();

		$this->assertSame( 'spectra-blocks/get-post-content', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-discovery', $ability->get_category() );
	}

	/**
	 * Test GetPostContent permission grants editors.
	 */
	public function test_get_post_content_permission_editor() {
		$this->assertTrue( GetPostContent::instance()->check_permission() );
	}

	/**
	 * Test GetPostContent permission denies subscribers.
	 */
	public function test_get_post_content_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = GetPostContent::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test GetPostContent execute with valid post.
	 */
	public function test_get_post_content_execute_success() {
		$post_id = $this->create_post_with_blocks();

		$result = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'blocks', $result );
		$this->assertArrayHasKey( 'count', $result );
		$this->assertGreaterThanOrEqual( 1, $result['count'] );

		$block = $result['blocks'][0];
		$this->assertArrayHasKey( 'index', $block );
		$this->assertArrayHasKey( 'name', $block );
		$this->assertArrayHasKey( 'attributes', $block );
		$this->assertArrayHasKey( 'innerBlocks', $block );
		$this->assertArrayHasKey( 'innerHTML', $block );
	}

	/**
	 * Test GetPostContent execute with block_name filter.
	 */
	public function test_get_post_content_filter_by_block_name() {
		$post_id = $this->create_post_with_blocks();

		$result = GetPostContent::instance()->execute(
			array(
				'post_id'    => $post_id,
				'block_name' => 'spectra/buttons',
			)
		);

		$this->assertIsArray( $result );
		foreach ( $result['blocks'] as $block ) {
			$this->assertSame( 'spectra/buttons', $block['name'] );
		}
	}

	/**
	 * Test GetPostContent execute fails without post_id.
	 */
	public function test_get_post_content_missing_post_id() {
		$result = GetPostContent::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test GetPostContent execute fails for non-existent post.
	 */
	public function test_get_post_content_nonexistent_post() {
		$result = GetPostContent::instance()->execute( array( 'post_id' => 999999 ) );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test GetPostContent input schema.
	 */
	public function test_get_post_content_input_schema() {
		$schema = GetPostContent::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertArrayHasKey( 'post_id', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
	}

	/**
	 * Test GetPostContent output schema.
	 */
	public function test_get_post_content_output_schema() {
		$schema = GetPostContent::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'blocks', $schema['properties'] );
		$this->assertArrayHasKey( 'count', $schema['properties'] );
	}

	// -------------------------------------------------------------------------
	// UpdateBlockAttributes
	// -------------------------------------------------------------------------

	/**
	 * Test UpdateBlockAttributes metadata.
	 */
	public function test_update_block_attributes_metadata() {
		$ability = UpdateBlockAttributes::instance();

		$this->assertSame( 'spectra-blocks/update-block-attributes', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test UpdateBlockAttributes execute success.
	 */
	public function test_update_block_attributes_execute_success() {
		$post_id = $this->create_post_with_blocks();

		$result = UpdateBlockAttributes::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'attributes'  => array( 'backgroundColor' => '#ff0000' ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'block_name', $result );
		$this->assertArrayHasKey( 'updated_attributes', $result );
		$this->assertSame( '#ff0000', $result['updated_attributes']['backgroundColor'] );
	}

	/**
	 * Test UpdateBlockAttributes preserves existing attributes.
	 */
	public function test_update_block_attributes_merges() {
		$post_id = $this->create_post_with_blocks();

		$result = UpdateBlockAttributes::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'attributes'  => array( 'newAttr' => 'value' ),
			)
		);

		$this->assertIsArray( $result );
		// Original block_id attribute should still be present.
		$this->assertSame( 'abc123', $result['updated_attributes']['block_id'] );
		$this->assertSame( 'value', $result['updated_attributes']['newAttr'] );
	}

	/**
	 * Test UpdateBlockAttributes fails without required params.
	 */
	public function test_update_block_attributes_missing_params() {
		$result = UpdateBlockAttributes::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test UpdateBlockAttributes fails for invalid block index.
	 */
	public function test_update_block_attributes_invalid_index() {
		$post_id = $this->create_post_with_blocks();

		$result = UpdateBlockAttributes::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
				'attributes'  => array( 'test' => true ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	/**
	 * Test UpdateBlockAttributes input schema.
	 */
	public function test_update_block_attributes_input_schema() {
		$schema = UpdateBlockAttributes::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'block_index', $schema['required'] );
		$this->assertContains( 'attributes', $schema['required'] );
	}

	// -------------------------------------------------------------------------
	// RemoveBlock
	// -------------------------------------------------------------------------

	/**
	 * Test RemoveBlock metadata.
	 */
	public function test_remove_block_metadata() {
		$ability = RemoveBlock::instance();

		$this->assertSame( 'spectra-blocks/remove-block', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test RemoveBlock execute success.
	 */
	public function test_remove_block_execute_success() {
		$post_id = $this->create_post_with_blocks();

		// Get initial block count.
		$initial       = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );
		$initial_count = $initial['count'];

		$result = RemoveBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'removed_block', $result );
		$this->assertSame( $initial_count - 1, $result['blocks_remaining'] );
	}

	/**
	 * Test RemoveBlock fails without params.
	 */
	public function test_remove_block_missing_params() {
		$result = RemoveBlock::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test RemoveBlock fails for invalid index.
	 */
	public function test_remove_block_invalid_index() {
		$post_id = $this->create_post_with_blocks();

		$result = RemoveBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	// -------------------------------------------------------------------------
	// MoveBlock
	// -------------------------------------------------------------------------

	/**
	 * Test MoveBlock metadata.
	 */
	public function test_move_block_metadata() {
		$ability = MoveBlock::instance();

		$this->assertSame( 'spectra-blocks/move-block', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test MoveBlock execute success.
	 */
	public function test_move_block_execute_success() {
		$post_id = $this->create_post_with_blocks();

		$result = MoveBlock::instance()->execute(
			array(
				'post_id'    => $post_id,
				'from_index' => 0,
				'to_index'   => 1,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 0, $result['from_index'] );
		$this->assertSame( 1, $result['to_index'] );

		// Verify the blocks were reordered.
		$content = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );
		$this->assertSame( 'spectra/buttons', $content['blocks'][0]['name'] );
		$this->assertSame( 'spectra/container', $content['blocks'][1]['name'] );
	}

	/**
	 * Test MoveBlock fails when from_index equals to_index.
	 */
	public function test_move_block_same_index() {
		$post_id = $this->create_post_with_blocks();

		$result = MoveBlock::instance()->execute(
			array(
				'post_id'    => $post_id,
				'from_index' => 0,
				'to_index'   => 0,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_param', $result->get_error_code() );
	}

	/**
	 * Test MoveBlock fails without params.
	 */
	public function test_move_block_missing_params() {
		$result = MoveBlock::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test MoveBlock input schema.
	 */
	public function test_move_block_input_schema() {
		$schema = MoveBlock::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'from_index', $schema['required'] );
		$this->assertContains( 'to_index', $schema['required'] );
	}

	// -------------------------------------------------------------------------
	// DuplicateBlock
	// -------------------------------------------------------------------------

	/**
	 * Test DuplicateBlock metadata.
	 */
	public function test_duplicate_block_metadata() {
		$ability = DuplicateBlock::instance();

		$this->assertSame( 'spectra-blocks/duplicate-block', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-content', $ability->get_category() );
	}

	/**
	 * Test DuplicateBlock execute success.
	 */
	public function test_duplicate_block_execute_success() {
		$post_id = $this->create_post_with_blocks();

		$initial       = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );
		$initial_count = $initial['count'];

		$result = DuplicateBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 0, $result['original_index'] );
		$this->assertSame( 1, $result['new_index'] );

		// Verify block count increased.
		$after = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );
		$this->assertSame( $initial_count + 1, $after['count'] );
	}

	/**
	 * Test DuplicateBlock fails without params.
	 */
	public function test_duplicate_block_missing_params() {
		$result = DuplicateBlock::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test DuplicateBlock fails for invalid index.
	 */
	public function test_duplicate_block_invalid_index() {
		$post_id = $this->create_post_with_blocks();

		$result = DuplicateBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 999,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	// =========================================================================
	// Additional GetPostContent tests
	// =========================================================================

	/**
	 * Test GetPostContent on a post with no blocks returns count 0.
	 */
	public function test_get_post_content_execute_empty_post() {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => '<p>Just plain HTML, no blocks</p>',
				'post_status'  => 'publish',
			)
		);

		$result = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );

		$this->assertIsArray( $result );
		$this->assertSame( 0, $result['count'] );
		$this->assertEmpty( $result['blocks'] );
	}

	/**
	 * Test GetPostContent filter by a block_name that does not exist in the post.
	 */
	public function test_get_post_content_filter_by_nonexistent_block_name() {
		$post_id = $this->create_post_with_blocks();

		$result = GetPostContent::instance()->execute(
			array(
				'post_id'    => $post_id,
				'block_name' => 'spectra/nonexistent-block',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 0, $result['count'] );
		$this->assertEmpty( $result['blocks'] );
	}

	/**
	 * Test GetPostContent output schema block items structure.
	 */
	public function test_get_post_content_output_schema_items() {
		$schema = GetPostContent::instance()->get_output_schema();
		$items  = $schema['properties']['blocks']['items']['properties'];

		$this->assertArrayHasKey( 'index', $items );
		$this->assertArrayHasKey( 'name', $items );
		$this->assertArrayHasKey( 'attributes', $items );
		$this->assertArrayHasKey( 'innerBlocks', $items );
		$this->assertArrayHasKey( 'innerHTML', $items );
	}

	/**
	 * Test GetPostContent grants access to authors for their own posts.
	 */
	public function test_get_post_content_permission_author_own_post() {
		$author_id = self::factory()->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $author_id );

		$post_id = self::factory()->post->create(
			array(
				'post_author'  => $author_id,
				'post_content' => '<!-- wp:spectra/container {"block_id":"test"} --><div></div><!-- /wp:spectra/container -->',
				'post_status'  => 'publish',
			)
		);

		$result = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );

		$this->assertIsArray( $result );
		$this->assertGreaterThanOrEqual( 1, $result['count'] );
	}

	// =========================================================================
	// Additional UpdateBlockAttributes tests
	// =========================================================================

	/**
	 * Test UpdateBlockAttributes fails for non-existent post.
	 */
	public function test_update_block_attributes_nonexistent_post() {
		$result = UpdateBlockAttributes::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
				'attributes'  => array( 'test' => true ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test UpdateBlockAttributes output schema.
	 */
	public function test_update_block_attributes_output_schema() {
		$schema = UpdateBlockAttributes::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'updated_attributes', $schema['properties'] );
	}

	/**
	 * Test UpdateBlockAttributes denies subscribers.
	 */
	public function test_update_block_attributes_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = UpdateBlockAttributes::instance()->check_permission();
		$this->assertWPError( $result );
	}

	// =========================================================================
	// Additional RemoveBlock tests
	// =========================================================================

	/**
	 * Test RemoveBlock fails for non-existent post.
	 */
	public function test_remove_block_nonexistent_post() {
		$result = RemoveBlock::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test RemoveBlock output schema.
	 */
	public function test_remove_block_output_schema() {
		$schema = RemoveBlock::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'removed_block', $schema['properties'] );
		$this->assertArrayHasKey( 'blocks_remaining', $schema['properties'] );
	}

	/**
	 * Test RemoveBlock removes the only block, leaving 0 remaining.
	 */
	public function test_remove_block_last_block() {
		$post_id = $this->create_post_with_blocks(
			'<!-- wp:spectra/container {"block_id":"only"} --><div></div><!-- /wp:spectra/container -->'
		);

		$result = RemoveBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 0, $result['blocks_remaining'] );
	}

	// =========================================================================
	// Additional MoveBlock tests
	// =========================================================================

	/**
	 * Test MoveBlock fails for non-existent post.
	 */
	public function test_move_block_nonexistent_post() {
		$result = MoveBlock::instance()->execute(
			array(
				'post_id'    => 999999,
				'from_index' => 0,
				'to_index'   => 1,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test MoveBlock fails for from_index out of range.
	 */
	public function test_move_block_invalid_from_index() {
		$post_id = $this->create_post_with_blocks();

		$result = MoveBlock::instance()->execute(
			array(
				'post_id'    => $post_id,
				'from_index' => 999,
				'to_index'   => 0,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	/**
	 * Test MoveBlock fails for to_index out of range.
	 */
	public function test_move_block_invalid_to_index() {
		$post_id = $this->create_post_with_blocks();

		$result = MoveBlock::instance()->execute(
			array(
				'post_id'    => $post_id,
				'from_index' => 0,
				'to_index'   => 999,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_block_index', $result->get_error_code() );
	}

	/**
	 * Test MoveBlock output schema.
	 */
	public function test_move_block_output_schema() {
		$schema = MoveBlock::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'from_index', $schema['properties'] );
		$this->assertArrayHasKey( 'to_index', $schema['properties'] );
	}

	/**
	 * Test MoveBlock with three blocks to verify correct reordering.
	 */
	public function test_move_block_three_blocks_reorder() {
		$content = '<!-- wp:spectra/container {"block_id":"a"} --><div>A</div><!-- /wp:spectra/container -->'
			. "\n\n" . '<!-- wp:spectra/buttons {"block_id":"b"} --><div>B</div><!-- /wp:spectra/buttons -->'
			. "\n\n" . '<!-- wp:spectra/separator {"block_id":"c"} --><hr /><!-- /wp:spectra/separator -->';

		$post_id = $this->create_post_with_blocks( $content );

		// Move A (index 0) to position 2 → expected: B, C, A.
		MoveBlock::instance()->execute(
			array(
				'post_id'    => $post_id,
				'from_index' => 0,
				'to_index'   => 2,
			)
		);

		$result = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );
		$this->assertSame( 'spectra/buttons', $result['blocks'][0]['name'] );
		$this->assertSame( 'spectra/separator', $result['blocks'][1]['name'] );
		$this->assertSame( 'spectra/container', $result['blocks'][2]['name'] );
	}

	// =========================================================================
	// Additional DuplicateBlock tests
	// =========================================================================

	/**
	 * Test DuplicateBlock output schema.
	 */
	public function test_duplicate_block_output_schema() {
		$schema = DuplicateBlock::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'success', $schema['properties'] );
		$this->assertArrayHasKey( 'block_name', $schema['properties'] );
		$this->assertArrayHasKey( 'original_index', $schema['properties'] );
		$this->assertArrayHasKey( 'new_index', $schema['properties'] );
	}

	/**
	 * Test DuplicateBlock fails for non-existent post.
	 */
	public function test_duplicate_block_nonexistent_post() {
		$result = DuplicateBlock::instance()->execute(
			array(
				'post_id'     => 999999,
				'block_index' => 0,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_invalid_post', $result->get_error_code() );
	}

	/**
	 * Test DuplicateBlock preserves all attributes in the copy.
	 */
	public function test_duplicate_block_preserves_attributes() {
		$post_id = $this->create_post_with_blocks();

		DuplicateBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$content = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );

		// Original at index 0 and duplicate at index 1 should have same attributes.
		$this->assertSame(
			$content['blocks'][0]['attributes']['block_id'],
			$content['blocks'][1]['attributes']['block_id']
		);
		$this->assertSame( $content['blocks'][0]['name'], $content['blocks'][1]['name'] );
	}

	// =========================================================================
	// Content CRUD Integration Cycle
	// =========================================================================

	/**
	 * Test full content CRUD cycle: Read → Update → Verify → Remove → Verify.
	 */
	public function test_full_content_crud_cycle() {
		$post_id = $this->create_post_with_blocks();

		// Read.
		$read = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );
		$this->assertSame( 2, $read['count'] );
		$this->assertSame( 'spectra/container', $read['blocks'][0]['name'] );

		// Update attributes on first block.
		$update = UpdateBlockAttributes::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'attributes'  => array( 'textColor' => 'blue' ),
			)
		);
		$this->assertTrue( $update['success'] );

		// Verify update via fresh read.
		$after_update = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );
		$this->assertSame( 'blue', $after_update['blocks'][0]['attributes']['textColor'] );
		$this->assertSame( 'abc123', $after_update['blocks'][0]['attributes']['block_id'] );

		// Remove the first block.
		$remove = RemoveBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);
		$this->assertTrue( $remove['success'] );
		$this->assertSame( 1, $remove['blocks_remaining'] );

		// Verify removal.
		$after_remove = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );
		$this->assertSame( 1, $after_remove['count'] );
		$this->assertSame( 'spectra/buttons', $after_remove['blocks'][0]['name'] );
	}

	// =========================================================================
	// Permission edge cases
	// =========================================================================

	/**
	 * Test RemoveBlock denies subscribers.
	 */
	public function test_remove_block_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = RemoveBlock::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test MoveBlock denies subscribers.
	 */
	public function test_move_block_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = MoveBlock::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test DuplicateBlock denies subscribers.
	 */
	public function test_duplicate_block_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = DuplicateBlock::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test UpdateBlockAttributes grants editors.
	 */
	public function test_update_block_attributes_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertTrue( UpdateBlockAttributes::instance()->check_permission() );
	}

	/**
	 * Test RemoveBlock grants editors.
	 */
	public function test_remove_block_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertTrue( RemoveBlock::instance()->check_permission() );
	}

	/**
	 * Test MoveBlock grants editors.
	 */
	public function test_move_block_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertTrue( MoveBlock::instance()->check_permission() );
	}

	/**
	 * Test DuplicateBlock grants editors.
	 */
	public function test_duplicate_block_permission_editor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertTrue( DuplicateBlock::instance()->check_permission() );
	}

	// =========================================================================
	// Missing input/output schema tests
	// =========================================================================

	/**
	 * Test RemoveBlock input schema.
	 */
	public function test_remove_block_input_schema() {
		$schema = RemoveBlock::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'block_index', $schema['required'] );
	}

	/**
	 * Test DuplicateBlock input schema.
	 */
	public function test_duplicate_block_input_schema() {
		$schema = DuplicateBlock::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'post_id', $schema['required'] );
		$this->assertContains( 'block_index', $schema['required'] );
	}

	/**
	 * Test DuplicateBlock preserves innerBlocks in the copy.
	 */
	public function test_duplicate_block_preserves_inner_blocks() {
		$content = '<!-- wp:spectra/container {"block_id":"outer"} --><div class="wp-block-spectra-container"><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --></div><!-- /wp:spectra/container -->';
		$post_id = $this->create_post_with_blocks( $content );

		DuplicateBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$result = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );

		// Both original (0) and duplicate (1) should have innerBlocks.
		$this->assertNotEmpty( $result['blocks'][0]['innerBlocks'] );
		$this->assertNotEmpty( $result['blocks'][1]['innerBlocks'] );
		$this->assertCount(
			count( $result['blocks'][0]['innerBlocks'] ),
			$result['blocks'][1]['innerBlocks']
		);
	}

	/**
	 * Test duplicate then update: changes to duplicate don't affect original.
	 */
	public function test_duplicate_then_update_independence() {
		$post_id = $this->create_post_with_blocks();

		// Duplicate block 0.
		DuplicateBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		// Update the duplicate (now at index 1) with a new attribute.
		UpdateBlockAttributes::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 1,
				'attributes'  => array( 'customAttr' => 'modified' ),
			)
		);

		// Read back and verify original at index 0 is unaffected.
		$content = GetPostContent::instance()->execute( array( 'post_id' => $post_id ) );
		$this->assertArrayNotHasKey( 'customAttr', $content['blocks'][0]['attributes'] );
		$this->assertSame( 'modified', $content['blocks'][1]['attributes']['customAttr'] );
	}
}
