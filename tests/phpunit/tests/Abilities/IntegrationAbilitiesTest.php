<?php
/**
 * Cross-ability integration tests.
 *
 * Verifies that different abilities work together correctly
 * when chained in real-world workflows.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use SpectraBlocks\Abilities\CreateContainer;
use SpectraBlocks\Abilities\CreateButtons;
use SpectraBlocks\Abilities\CreateSeparator;
use SpectraBlocks\Abilities\GetPostContent;
use SpectraBlocks\Abilities\UpdateBlockAttributes;
use SpectraBlocks\Abilities\RemoveBlock;
use SpectraBlocks\Abilities\MoveBlock;
use SpectraBlocks\Abilities\DuplicateBlock;
use SpectraBlocks\Abilities\SearchPostsByBlock;
use SpectraBlocks\Abilities\SearchPostContent;
use SpectraBlocks\Abilities\ApplyAnimation;
use SpectraBlocks\Abilities\RemoveAnimation;
use SpectraBlocks\Abilities\ApplySticky;
use SpectraBlocks\Abilities\ApplyZIndex;
use SpectraBlocks\Abilities\ApplyResponsiveConditions;
use SpectraBlocks\Abilities\RemoveResponsiveConditions;

/**
 * Integration abilities test case.
 */
class IntegrationAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user is editor.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
	}

	// -------------------------------------------------------------------------
	// Create → Read Roundtrips
	// -------------------------------------------------------------------------

	/**
	 * Test CreateContainer then GetPostContent reads back the created block.
	 */
	public function test_create_container_then_read_roundtrip() {
		$post_id = self::factory()->post->create( array( 'post_content' => '' ) );

		// Create a container and insert into the post.
		$create_result = CreateContainer::instance()->execute(
			array(
				'post_id' => $post_id,
				'mode'    => 'replace',
				'htmlTag' => 'section',
			)
		);

		$this->assertIsArray( $create_result );
		$this->assertSame( $post_id, $create_result['post_id'] );

		// Read back the post content.
		$read_result = GetPostContent::instance()->execute(
			array( 'post_id' => $post_id )
		);

		$this->assertIsArray( $read_result );
		$this->assertGreaterThanOrEqual( 1, $read_result['count'] );
		$this->assertSame( 'spectra/container', $read_result['blocks'][0]['name'] );
	}

	/**
	 * Test CreateButtons then GetPostContent reads back inserted buttons.
	 */
	public function test_create_buttons_then_read_roundtrip() {
		$post_id = self::factory()->post->create( array( 'post_content' => '' ) );

		CreateButtons::instance()->execute(
			array(
				'buttons' => array(
					array( 'text' => 'Primary CTA' ),
					array( 'text' => 'Secondary CTA' ),
				),
				'post_id' => $post_id,
				'mode'    => 'replace',
			)
		);

		$read_result = GetPostContent::instance()->execute(
			array( 'post_id' => $post_id )
		);

		$this->assertIsArray( $read_result );
		$this->assertGreaterThanOrEqual( 1, $read_result['count'] );

		// Filter by buttons specifically.
		$buttons_only = GetPostContent::instance()->execute(
			array(
				'post_id'    => $post_id,
				'block_name' => 'spectra/buttons',
			)
		);

		$this->assertGreaterThanOrEqual( 1, $buttons_only['count'] );
	}

	/**
	 * Test multiple block creation then read back verifies all blocks present.
	 */
	public function test_create_multiple_blocks_then_read() {
		$post_id = self::factory()->post->create( array( 'post_content' => '' ) );

		// Create separator first.
		CreateSeparator::instance()->execute(
			array(
				'separatorStyle' => 'dashed',
				'post_id'        => $post_id,
				'mode'           => 'replace',
			)
		);

		// Append buttons.
		CreateButtons::instance()->execute(
			array(
				'buttons' => array( array( 'text' => 'Click' ) ),
				'post_id' => $post_id,
				'mode'    => 'append',
			)
		);

		// Read and verify both.
		$read_result = GetPostContent::instance()->execute(
			array( 'post_id' => $post_id )
		);

		$this->assertGreaterThanOrEqual( 2, $read_result['count'] );

		$block_names = array_column( $read_result['blocks'], 'name' );
		$this->assertContains( 'spectra/separator', $block_names );
		$this->assertContains( 'spectra/buttons', $block_names );
	}

	// -------------------------------------------------------------------------
	// Create → Search Roundtrips
	// -------------------------------------------------------------------------

	/**
	 * Test SearchPostsByBlock finds posts after content creation.
	 */
	public function test_search_after_content_creation() {
		$post_id = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);

		// Insert buttons block into the published post.
		CreateButtons::instance()->execute(
			array(
				'buttons' => array( array( 'text' => 'Search Test Button' ) ),
				'post_id' => $post_id,
				'mode'    => 'replace',
			)
		);

		// Now search for posts containing the buttons block.
		$search_result = SearchPostsByBlock::instance()->execute(
			array( 'block_name' => 'buttons' )
		);

		$this->assertIsArray( $search_result );
		$this->assertGreaterThanOrEqual( 1, $search_result['total'] );

		$post_ids = array_column( $search_result['posts'], 'id' );
		$this->assertContains( $post_id, $post_ids );
	}

	/**
	 * Test SearchPostContent finds keyword after block creation.
	 */
	public function test_search_post_content_after_creation() {
		$post_id = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);

		// Create container with unique content.
		CreateContainer::instance()->execute(
			array(
				'content' => '<!-- wp:paragraph --><p>UniqueIntegrationKeyword12345</p><!-- /wp:paragraph -->',
				'post_id' => $post_id,
				'mode'    => 'replace',
			)
		);

		// Search for the unique keyword.
		$search_result = SearchPostContent::instance()->execute(
			array( 'keyword' => 'UniqueIntegrationKeyword12345' )
		);

		$this->assertIsArray( $search_result );
		$this->assertGreaterThanOrEqual( 1, $search_result['total'] );
	}

	// -------------------------------------------------------------------------
	// Create → Update → Read Roundtrips
	// -------------------------------------------------------------------------

	/**
	 * Test create block, update its attributes, then read back changes.
	 */
	public function test_create_update_read_roundtrip() {
		$post_id = self::factory()->post->create( array( 'post_content' => '' ) );

		// Create a separator.
		CreateSeparator::instance()->execute(
			array(
				'separatorStyle' => 'solid',
				'post_id'        => $post_id,
				'mode'           => 'replace',
			)
		);

		// Update the separator's attributes.
		$update_result = UpdateBlockAttributes::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'attributes'  => array(
					'separatorStyle' => 'dashed',
					'separatorColor' => '#ff0000',
				),
			)
		);

		$this->assertTrue( $update_result['success'] );

		// Read back and verify.
		$read_result = GetPostContent::instance()->execute(
			array( 'post_id' => $post_id )
		);

		$attrs = $read_result['blocks'][0]['attributes'];
		$this->assertSame( 'dashed', $attrs['separatorStyle'] );
		$this->assertSame( '#ff0000', $attrs['separatorColor'] );
	}

	// -------------------------------------------------------------------------
	// Extension → Read Integration
	// -------------------------------------------------------------------------

	/**
	 * Test applying multiple extensions to a block and reading them back.
	 */
	public function test_apply_multiple_extensions_then_read() {
		$content = '<!-- wp:spectra/container {"block_id":"multi-ext"} --><div></div><!-- /wp:spectra/container -->';
		$post_id = self::factory()->post->create(
			array(
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);

		// Apply animation.
		ApplyAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'type'        => 'fadeIn',
				'duration'    => 1000,
			)
		);

		// Apply sticky.
		ApplySticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		// Apply z-index.
		ApplyZIndex::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'zindex'      => 50,
			)
		);

		// Apply responsive conditions.
		ApplyResponsiveConditions::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'hide_mobile' => true,
			)
		);

		// Read back and verify all extensions are present.
		$read_result = GetPostContent::instance()->execute(
			array( 'post_id' => $post_id )
		);

		$attrs = $read_result['blocks'][0]['attributes'];
		$this->assertSame( 'fadeIn', $attrs['UAGAnimationType'] );
		$this->assertSame( 1000, $attrs['UAGAnimationTime'] );
		$this->assertSame( 'sticky', $attrs['UAGPosition'] );
		$this->assertSame( 50, $attrs['spectraZIndex'] );
		$this->assertTrue( $attrs['UAGHideMob'] );
		$this->assertTrue( $attrs['UAGResponsiveConditions'] );
	}

	/**
	 * Test removing one extension doesn't affect other extensions.
	 */
	public function test_remove_one_extension_preserves_others() {
		$content = '<!-- wp:spectra/container {"block_id":"preserve-ext"} --><div></div><!-- /wp:spectra/container -->';
		$post_id = self::factory()->post->create(
			array(
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);

		// Apply multiple extensions.
		ApplyAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'type'        => 'slideInUp',
			)
		);

		ApplySticky::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		ApplyZIndex::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'zindex'      => 10,
			)
		);

		// Remove only the animation.
		RemoveAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		// Verify other extensions are preserved.
		$read_result = GetPostContent::instance()->execute(
			array( 'post_id' => $post_id )
		);

		$attrs = $read_result['blocks'][0]['attributes'];
		$this->assertArrayNotHasKey( 'UAGAnimationType', $attrs );
		$this->assertSame( 'sticky', $attrs['UAGPosition'] );
		$this->assertSame( 10, $attrs['spectraZIndex'] );
	}

	// -------------------------------------------------------------------------
	// CRUD + Extension Combined Workflow
	// -------------------------------------------------------------------------

	/**
	 * Test full workflow: create block, apply extensions, duplicate, update duplicate, remove original.
	 */
	public function test_full_workflow_create_extend_duplicate_update_remove() {
		$post_id = self::factory()->post->create( array( 'post_content' => '' ) );

		// Step 1: Create a separator and insert.
		CreateSeparator::instance()->execute(
			array(
				'separatorStyle' => 'solid',
				'post_id'        => $post_id,
				'mode'           => 'replace',
			)
		);

		// Step 2: Apply animation to the separator.
		ApplyAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'type'        => 'fadeIn',
			)
		);

		// Step 3: Duplicate the separator.
		$dup_result = DuplicateBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		$this->assertTrue( $dup_result['success'] );

		// Step 4: Update the duplicate's style.
		UpdateBlockAttributes::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 1,
				'attributes'  => array( 'separatorStyle' => 'dotted' ),
			)
		);

		// Step 5: Remove the original.
		RemoveBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
			)
		);

		// Verify: only the modified duplicate remains.
		$read_result = GetPostContent::instance()->execute(
			array( 'post_id' => $post_id )
		);

		$this->assertSame( 1, $read_result['count'] );
		$attrs = $read_result['blocks'][0]['attributes'];
		$this->assertSame( 'dotted', $attrs['separatorStyle'] );
		// Animation should be carried over from the duplicate.
		$this->assertSame( 'fadeIn', $attrs['UAGAnimationType'] );
	}

	/**
	 * Test move block then apply extension to the moved block.
	 */
	public function test_move_then_apply_extension() {
		$content = '<!-- wp:spectra/container {"block_id":"move-a"} --><div>A</div><!-- /wp:spectra/container -->'
			. "\n\n" . '<!-- wp:spectra/buttons {"block_id":"move-b"} --><div>B</div><!-- /wp:spectra/buttons -->';

		$post_id = self::factory()->post->create(
			array(
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);

		// Move container (index 0) to position 1 — buttons first, then container.
		MoveBlock::instance()->execute(
			array(
				'post_id'    => $post_id,
				'from_index' => 0,
				'to_index'   => 1,
			)
		);

		// Apply animation to the moved container (now at index 1).
		ApplyAnimation::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 1,
				'type'        => 'zoomIn',
			)
		);

		// Verify.
		$read_result = GetPostContent::instance()->execute(
			array( 'post_id' => $post_id )
		);

		$this->assertSame( 'spectra/buttons', $read_result['blocks'][0]['name'] );
		$this->assertSame( 'spectra/container', $read_result['blocks'][1]['name'] );
		$this->assertSame( 'zoomIn', $read_result['blocks'][1]['attributes']['UAGAnimationType'] );
	}

	// -------------------------------------------------------------------------
	// Search After Modification
	// -------------------------------------------------------------------------

	/**
	 * Test SearchPostContent finds content after UpdateBlockAttributes changes markup.
	 */
	public function test_search_content_after_attribute_update() {
		$content = '<!-- wp:spectra/container {"block_id":"searchmod"} --><div class="wp-block-spectra-container"><p>OriginalSearchTerm789</p></div><!-- /wp:spectra/container -->';
		$post_id = self::factory()->post->create(
			array(
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);

		// Verify the original content is searchable.
		$search_before = SearchPostContent::instance()->execute(
			array( 'keyword' => 'OriginalSearchTerm789' )
		);

		$this->assertGreaterThanOrEqual( 1, $search_before['total'] );

		// Update block attributes (this modifies block metadata, not innerHTML).
		UpdateBlockAttributes::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 0,
				'attributes'  => array( 'backgroundColor' => '#000' ),
			)
		);

		// Original content should still be searchable.
		$search_after = SearchPostContent::instance()->execute(
			array( 'keyword' => 'OriginalSearchTerm789' )
		);

		$this->assertGreaterThanOrEqual( 1, $search_after['total'] );
	}

	/**
	 * Test SearchPostsByBlock still finds block after removing a different block.
	 */
	public function test_search_finds_block_after_sibling_removal() {
		$content = '<!-- wp:spectra/container {"block_id":"keep"} --><div>Keep</div><!-- /wp:spectra/container -->'
			. "\n\n" . '<!-- wp:spectra/buttons {"block_id":"remove"} --><div>Remove</div><!-- /wp:spectra/buttons -->';

		$post_id = self::factory()->post->create(
			array(
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);

		// Remove the buttons block.
		RemoveBlock::instance()->execute(
			array(
				'post_id'     => $post_id,
				'block_index' => 1,
			)
		);

		// Container should still be findable.
		$search_result = SearchPostsByBlock::instance()->execute(
			array( 'block_name' => 'container' )
		);

		$post_ids = array_column( $search_result['posts'], 'id' );
		$this->assertContains( $post_id, $post_ids );
	}
}
