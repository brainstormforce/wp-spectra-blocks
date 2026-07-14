<?php
/**
 * Tests for Search abilities: SearchPostsByBlock, SearchPostContent.
 *
 * @package SpectraBlocks\Tests\Abilities
 */

use SpectraBlocks\Abilities\SearchPostsByBlock;
use SpectraBlocks\Abilities\SearchPostContent;

/**
 * Search abilities test case.
 */
class SearchAbilitiesTest extends WP_UnitTestCase {

	/**
	 * Set up test — ensure current user is editor.
	 */
	public function set_up() {
		parent::set_up();
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
	}

	// -------------------------------------------------------------------------
	// SearchPostsByBlock
	// -------------------------------------------------------------------------

	/**
	 * Test SearchPostsByBlock metadata.
	 */
	public function test_search_posts_by_block_metadata() {
		$ability = SearchPostsByBlock::instance();

		$this->assertSame( 'spectra-blocks/search-posts-by-block', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-discovery', $ability->get_category() );
	}

	/**
	 * Test SearchPostsByBlock permission grants editors.
	 */
	public function test_search_posts_by_block_permission() {
		$this->assertTrue( SearchPostsByBlock::instance()->check_permission() );
	}

	/**
	 * Test SearchPostsByBlock permission denies subscribers.
	 */
	public function test_search_posts_by_block_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = SearchPostsByBlock::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test SearchPostsByBlock execute returns matching posts.
	 */
	public function test_search_posts_by_block_execute_success() {
		self::factory()->post->create(
			array(
				'post_content' => '<!-- wp:spectra/container {"block_id":"test"} --><div></div><!-- /wp:spectra/container -->',
				'post_status'  => 'publish',
			)
		);

		$result = SearchPostsByBlock::instance()->execute(
			array( 'block_name' => 'container' )
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'posts', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertGreaterThanOrEqual( 1, $result['total'] );

		$post = $result['posts'][0];
		$this->assertArrayHasKey( 'id', $post );
		$this->assertArrayHasKey( 'title', $post );
		$this->assertArrayHasKey( 'type', $post );
		$this->assertArrayHasKey( 'url', $post );
	}

	/**
	 * Test SearchPostsByBlock auto-adds spectra prefix.
	 */
	public function test_search_posts_by_block_auto_prefix() {
		self::factory()->post->create(
			array(
				'post_content' => '<!-- wp:spectra/buttons {"block_id":"test"} --><div></div><!-- /wp:spectra/buttons -->',
				'post_status'  => 'publish',
			)
		);

		$result = SearchPostsByBlock::instance()->execute(
			array( 'block_name' => 'buttons' )
		);

		$this->assertIsArray( $result );
		$this->assertGreaterThanOrEqual( 1, $result['total'] );
	}

	/**
	 * Test SearchPostsByBlock fails without block_name.
	 */
	public function test_search_posts_by_block_missing_param() {
		$result = SearchPostsByBlock::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test SearchPostsByBlock returns empty for non-existent block.
	 */
	public function test_search_posts_by_block_no_results() {
		$result = SearchPostsByBlock::instance()->execute(
			array( 'block_name' => 'nonexistent-block-xyz' )
		);

		$this->assertIsArray( $result );
		$this->assertSame( 0, $result['total'] );
	}

	/**
	 * Test SearchPostsByBlock input schema.
	 */
	public function test_search_posts_by_block_input_schema() {
		$schema = SearchPostsByBlock::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'block_name', $schema['required'] );
	}

	// -------------------------------------------------------------------------
	// SearchPostContent
	// -------------------------------------------------------------------------

	/**
	 * Test SearchPostContent metadata.
	 */
	public function test_search_post_content_metadata() {
		$ability = SearchPostContent::instance();

		$this->assertSame( 'spectra-blocks/search-post-content', $ability->get_name() );
		$this->assertSame( 'spectra-blocks-discovery', $ability->get_category() );
	}

	/**
	 * Test SearchPostContent execute returns matching posts.
	 */
	public function test_search_post_content_execute_success() {
		self::factory()->post->create(
			array(
				'post_content' => '<p>This is a unique test keyword xylophone123</p>',
				'post_status'  => 'publish',
			)
		);

		$result = SearchPostContent::instance()->execute(
			array( 'keyword' => 'xylophone123' )
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'posts', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'total_found', $result );
		$this->assertGreaterThanOrEqual( 1, $result['total'] );

		$post = $result['posts'][0];
		$this->assertArrayHasKey( 'id', $post );
		$this->assertArrayHasKey( 'title', $post );
		$this->assertArrayHasKey( 'excerpt', $post );
	}

	/**
	 * Test SearchPostContent fails without keyword.
	 */
	public function test_search_post_content_missing_keyword() {
		$result = SearchPostContent::instance()->execute( array() );

		$this->assertWPError( $result );
		$this->assertSame( 'spectra_blocks_missing_param', $result->get_error_code() );
	}

	/**
	 * Test SearchPostContent input schema.
	 */
	public function test_search_post_content_input_schema() {
		$schema = SearchPostContent::instance()->get_input_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertContains( 'keyword', $schema['required'] );
		$this->assertArrayHasKey( 'keyword', $schema['properties'] );
	}

	/**
	 * Test SearchPostContent output schema.
	 */
	public function test_search_post_content_output_schema() {
		$schema = SearchPostContent::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'posts', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
		$this->assertArrayHasKey( 'total_found', $schema['properties'] );
	}

	// =========================================================================
	// Additional SearchPostsByBlock tests
	// =========================================================================

	/**
	 * Test SearchPostsByBlock with full spectra/ prefix already provided.
	 */
	public function test_search_posts_by_block_with_full_prefix() {
		self::factory()->post->create(
			array(
				'post_content' => '<!-- wp:spectra/container {"block_id":"fp"} --><div></div><!-- /wp:spectra/container -->',
				'post_status'  => 'publish',
			)
		);

		$result = SearchPostsByBlock::instance()->execute(
			array( 'block_name' => 'spectra/container' )
		);

		$this->assertIsArray( $result );
		$this->assertGreaterThanOrEqual( 1, $result['total'] );
	}

	/**
	 * Test SearchPostsByBlock output schema.
	 */
	public function test_search_posts_by_block_output_schema() {
		$schema = SearchPostsByBlock::instance()->get_output_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'posts', $schema['properties'] );
		$this->assertArrayHasKey( 'total', $schema['properties'] );
	}

	/**
	 * Test SearchPostsByBlock with custom post_type filter.
	 */
	public function test_search_posts_by_block_custom_post_type() {
		$block = '<!-- wp:spectra/separator {"block_id":"ptype"} --><hr/><!-- /wp:spectra/separator -->';

		// Create one post and one page with the block.
		self::factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_content' => $block,
				'post_status'  => 'publish',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => $block,
				'post_status'  => 'publish',
			)
		);

		// Search only pages.
		$result = SearchPostsByBlock::instance()->execute(
			array(
				'block_name' => 'separator',
				'post_type'  => array( 'page' ),
			)
		);

		$this->assertIsArray( $result );
		foreach ( $result['posts'] as $post ) {
			$this->assertSame( 'page', $post['type'] );
		}
	}

	/**
	 * Test SearchPostsByBlock respects limit parameter.
	 */
	public function test_search_posts_by_block_respects_limit() {
		$block = '<!-- wp:spectra/buttons {"block_id":"lim"} --><div></div><!-- /wp:spectra/buttons -->';
		for ( $i = 0; $i < 5; $i++ ) {
			self::factory()->post->create(
				array(
					'post_content' => $block,
					'post_status'  => 'publish',
				)
			);
		}

		$result = SearchPostsByBlock::instance()->execute(
			array(
				'block_name' => 'buttons',
				'limit'      => 2,
			)
		);

		$this->assertIsArray( $result );
		$this->assertLessThanOrEqual( 2, $result['total'] );
	}

	/**
	 * Test SearchPostsByBlock excludes draft posts.
	 */
	public function test_search_posts_by_block_excludes_drafts() {
		self::factory()->post->create(
			array(
				'post_content' => '<!-- wp:spectra/counter {"block_id":"draft"} --><div></div><!-- /wp:spectra/counter -->',
				'post_status'  => 'draft',
			)
		);

		$result = SearchPostsByBlock::instance()->execute(
			array( 'block_name' => 'counter' )
		);

		// Draft posts should not appear in results; verify all returned posts are published.
		$this->assertIsArray( $result['posts'] );
		foreach ( $result['posts'] as $post ) {
			$actual_status = get_post_status( $post['id'] );
			$this->assertSame( 'publish', $actual_status );
		}
		// No published counter posts were created, so total should be 0.
		$this->assertSame( 0, $result['total'] );
	}

	// =========================================================================
	// Additional SearchPostContent tests
	// =========================================================================

	/**
	 * Test SearchPostContent denies subscribers.
	 */
	public function test_search_post_content_permission_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = SearchPostContent::instance()->check_permission();
		$this->assertWPError( $result );
	}

	/**
	 * Test SearchPostContent respects limit.
	 */
	public function test_search_post_content_respects_limit() {
		for ( $i = 0; $i < 5; $i++ ) {
			self::factory()->post->create(
				array(
					'post_content' => '<p>Unique limit test keyword unicorn987</p>',
					'post_status'  => 'publish',
				)
			);
		}

		$result = SearchPostContent::instance()->execute(
			array(
				'keyword' => 'unicorn987',
				'limit'   => 2,
			)
		);

		$this->assertIsArray( $result );
		$this->assertLessThanOrEqual( 2, count( $result['posts'] ) );
		$this->assertGreaterThanOrEqual( 2, $result['total_found'] );
	}

	/**
	 * Test SearchPostContent excerpt starts with "..." when match is deep in content.
	 */
	public function test_search_post_content_excerpt_context() {
		$padding = str_repeat( 'word ', 50 ); // ~250 chars of padding.
		self::factory()->post->create(
			array(
				'post_content' => "<p>{$padding}deepkeyword999</p>",
				'post_status'  => 'publish',
			)
		);

		$result = SearchPostContent::instance()->execute(
			array( 'keyword' => 'deepkeyword999' )
		);

		if ( ! empty( $result['posts'] ) && ! empty( $result['posts'][0]['excerpt'] ) ) {
			$this->assertStringStartsWith( '...', $result['posts'][0]['excerpt'] );
		}
	}

	/**
	 * Test SearchPostContent returns empty for unmatched keyword.
	 */
	public function test_search_post_content_no_results() {
		$result = SearchPostContent::instance()->execute(
			array( 'keyword' => 'absolutelynonexistentterm999xyz' )
		);

		$this->assertIsArray( $result );
		$this->assertSame( 0, $result['total'] );
		$this->assertSame( 0, $result['total_found'] );
		$this->assertEmpty( $result['posts'] );
	}

	/**
	 * Test SearchPostContent grants editors.
	 */
	public function test_search_post_content_permission_editor() {
		$this->assertTrue( SearchPostContent::instance()->check_permission() );
	}

	/**
	 * Test SearchPostContent with custom post_type filter.
	 */
	public function test_search_post_content_post_type_filter() {
		self::factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_content' => '<p>TypeFilter keyword ptypefilter789</p>',
				'post_status'  => 'publish',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => '<p>TypeFilter keyword ptypefilter789</p>',
				'post_status'  => 'publish',
			)
		);

		$result = SearchPostContent::instance()->execute(
			array(
				'keyword'   => 'ptypefilter789',
				'post_type' => array( 'page' ),
			)
		);

		$this->assertIsArray( $result );
		foreach ( $result['posts'] as $post ) {
			$this->assertSame( 'page', $post['type'] );
		}
	}

	/**
	 * Test SearchPostsByBlock grants editors.
	 */
	public function test_search_posts_by_block_permission_editor() {
		$this->assertTrue( SearchPostsByBlock::instance()->check_permission() );
	}

	/**
	 * Test SearchPostContent output schema post items structure.
	 */
	public function test_search_post_content_output_schema_items() {
		$schema = SearchPostContent::instance()->get_output_schema();
		$items  = $schema['properties']['posts']['items']['properties'];

		$this->assertArrayHasKey( 'id', $items );
		$this->assertArrayHasKey( 'title', $items );
		$this->assertArrayHasKey( 'type', $items );
		$this->assertArrayHasKey( 'url', $items );
		$this->assertArrayHasKey( 'excerpt', $items );
	}
}
