<?php
/**
 * Tests for Spectra\Analytics\IncrementalBlockTracker.
 *
 * @package SpectraBlocks\Tests\Analytics
 */

use Spectra\Analytics\IncrementalBlockTracker;

/**
 * Lifecycle tests for the delta-based per-block usage tracker.
 *
 * Covers the full save / trash / restore / delete lifecycle, with the
 * REST-restore regression (a status change that fires save_post but NOT
 * untrash_post) as the primary guard.
 */
class IncrementalBlockTrackerTest extends WP_UnitTestCase {

	/**
	 * Tracker instance (reused singleton — hooks already registered at bootstrap).
	 *
	 * @var IncrementalBlockTracker
	 */
	private $tracker;

	/**
	 * Block names this test registered (to unregister on teardown).
	 *
	 * @var string[]
	 */
	private $registered = array();

	/**
	 * Post content with three root-level Spectra containers.
	 *
	 * @var string
	 */
	private $three_containers = "<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->\n<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->\n<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->";

	/**
	 * Set up: register the blocks the tracker counts, reset its cache, opt in.
	 */
	public function set_up() {
		parent::set_up();

		// The tracker filters by the `spectra/` namespace via the block registry,
		// so the blocks referenced in fixture content must be registered.
		foreach ( array( 'spectra/container', 'spectra/content' ) as $block ) {
			if ( ! WP_Block_Type_Registry::get_instance()->is_registered( $block ) ) {
				register_block_type( $block, array() );
				$this->registered[] = $block;
			}
		}

		$this->tracker = IncrementalBlockTracker::instance();
		$this->reset_trackable_cache();

		update_site_option( 'spectra_blocks_usage_optin', 'yes' );
		delete_option( IncrementalBlockTracker::USAGE_OPTION );
	}

	/**
	 * Tear down: unregister test blocks, reset cache, clear state.
	 */
	public function tear_down() {
		foreach ( $this->registered as $block ) {
			unregister_block_type( $block );
		}
		$this->registered = array();
		$this->reset_trackable_cache();
		delete_option( IncrementalBlockTracker::USAGE_OPTION );

		parent::tear_down();
	}

	/**
	 * Force the tracker to rebuild its cached trackable-block set from the registry.
	 */
	private function reset_trackable_cache() {
		$prop = new ReflectionProperty( IncrementalBlockTracker::class, 'trackable' );
		$prop->setAccessible( true );
		$prop->setValue( $this->tracker, null );
	}

	/**
	 * Current sitewide instance count for spectra/container.
	 *
	 * @return int
	 */
	private function container_count() {
		$data = get_option( IncrementalBlockTracker::USAGE_OPTION, array() );
		return (int) ( $data['block_usage_stats']['spectra/container'] ?? 0 );
	}

	/**
	 * Create a published post with three Spectra containers.
	 *
	 * @return int Post ID.
	 */
	private function make_post() {
		return self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => $this->three_containers,
			)
		);
	}

	/**
	 * Publishing a post counts its block instances.
	 */
	public function test_counts_block_instances_on_create() {
		$this->make_post();
		$this->assertSame( 3, $this->container_count() );
	}

	/**
	 * Re-saving an unchanged post does not inflate the count.
	 */
	public function test_no_inflation_on_resave() {
		$post_id = $this->make_post();
		wp_update_post( array( 'ID' => $post_id, 'post_title' => 'edited' ) );
		$this->assertSame( 3, $this->container_count() );
	}

	/**
	 * REGRESSION: trash then restore via a REST-style status change (which fires
	 * save_post but NOT untrash_post) must restore the count. Before the fix the
	 * count stayed at 0.
	 */
	public function test_rest_status_change_restore_recounts() {
		$post_id = $this->make_post();
		$this->assertSame( 3, $this->container_count(), 'baseline' );

		// REST DELETE → wp_trash_post (fires wp_trash_post + save_post status=trash).
		wp_trash_post( $post_id );
		$this->assertSame( 0, $this->container_count(), 'trash removes contribution' );

		// REST restore: the posts controller calls wp_update_post() with the new
		// status — this fires save_post but NOT untrash_post.
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
		$this->assertSame( 3, $this->container_count(), 'REST-style restore re-counts' );
	}

	/**
	 * Classic trash + restore via wp_untrash_post restores the count.
	 */
	public function test_classic_trash_restore_recounts() {
		$post_id = $this->make_post();
		wp_trash_post( $post_id );
		$this->assertSame( 0, $this->container_count() );

		wp_untrash_post( $post_id );
		$this->assertSame( 3, $this->container_count() );
	}

	/**
	 * Trashing via the REST path and restoring via the classic path must not
	 * double-count (regardless of whether untrash_post or save_post fires first).
	 */
	public function test_rest_trash_classic_restore_no_double_count() {
		$post_id = $this->make_post();
		wp_trash_post( $post_id );
		$this->assertSame( 0, $this->container_count() );

		wp_untrash_post( $post_id );
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
		$this->assertSame( 3, $this->container_count(), 'no double-count (not 6)' );
	}

	/**
	 * Permanently deleting a live post removes its contribution.
	 */
	public function test_permanent_delete_removes_contribution() {
		$post_id = $this->make_post();
		$this->assertSame( 3, $this->container_count() );

		wp_delete_post( $post_id, true );
		$this->assertSame( 0, $this->container_count() );
	}

	/**
	 * Editing out a block decrements the count.
	 */
	public function test_editing_out_blocks_decrements() {
		$post_id = $this->make_post();
		$this->assertSame( 3, $this->container_count() );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => '<!-- wp:spectra/container --><div></div><!-- /wp:spectra/container -->',
			)
		);
		$this->assertSame( 1, $this->container_count() );
	}
}
