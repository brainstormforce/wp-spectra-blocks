<?php
/**
 * Incremental per-block usage tracker for Spectra Blocks.
 *
 * @package Spectra
 */

namespace Spectra\Analytics;

use SpectraBlocks\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Maintains an accurate, delta-based count of Spectra block instances across all
 * posts. Ported from UAGB's incremental tracker: on each save it diffs the post's
 * previous block counts against the current ones and applies only the net change
 * to a sitewide tally — decrementing when blocks are removed and on trash/delete.
 * This avoids the inflation of a save-incremented presence counter.
 *
 * @since 1.0.14
 */
final class IncrementalBlockTracker {

	use Singleton;

	/**
	 * Sitewide block-usage option key.
	 */
	const USAGE_OPTION = 'spectra_blocks_block_usage';

	/**
	 * Per-post block-counts meta key.
	 */
	const COUNTS_META = '_spectra_blocks_block_counts';

	/**
	 * Cached set of trackable full block names (spectra/ + spectra-pro/).
	 *
	 * @var array<string,bool>|null
	 */
	private $trackable = null;

	/**
	 * Register lifecycle hooks.
	 *
	 * @since 1.0.14
	 * @return void
	 */
	public function init() {
		add_action( 'save_post', array( $this, 'track_block_changes_on_save' ), 10, 2 );
		add_action( 'before_delete_post', array( $this, 'track_block_removal_on_delete' ) );
		add_action( 'wp_trash_post', array( $this, 'track_block_removal_on_trash' ) );
		add_action( 'untrash_post', array( $this, 'track_block_addition_on_untrash' ) );
	}

	/**
	 * Apply the net block-count change for a saved post to the sitewide tally.
	 *
	 * @since 1.0.14
	 * @param int           $post_id Post ID.
	 * @param \WP_Post|null $post    Post object.
	 * @return void
	 */
	public function track_block_changes_on_save( $post_id, $post = null ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! is_object( $post ) || ! $this->is_public_post_type( $post->post_type ) ) {
			return;
		}

		$previous = $this->get_post_counts( $post_id );

		// A trashed post contributes nothing — count its blocks only while it is
		// live. This makes save_post the single source of truth across the full
		// trash/restore lifecycle (both classic and REST status changes fire it),
		// so the count never double-counts or strands a trashed post's blocks.
		$current = ( 'trash' === $post->post_status )
			? array()
			: $this->count_block_instances( $post->post_content );

		$this->update_global_stats_delta( $previous, $current );
		$this->store_post_counts( $post_id, $current );
	}

	/**
	 * Remove a post's block contribution on permanent delete.
	 *
	 * @since 1.0.14
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function track_block_removal_on_delete( $post_id ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$previous = $this->get_post_counts( $post_id );
		if ( empty( $previous ) ) {
			return;
		}

		$this->update_global_stats_delta( $previous, array() );
		$this->store_post_counts( $post_id, array() );
	}

	/**
	 * Remove a post's block contribution on trash.
	 *
	 * The contribution is removed from the sitewide tally but the per-post meta is
	 * kept so an untrash can restore it.
	 *
	 * @since 1.0.14
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function track_block_removal_on_trash( $post_id ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$previous = $this->get_post_counts( $post_id );
		if ( empty( $previous ) ) {
			return;
		}

		// Remove the contribution AND clear the per-post meta. Restoring the post —
		// via the classic untrash_post hook OR a REST status change (which does NOT
		// fire untrash_post) — then self-heals: save_post / the untrash handler see
		// previous = {} vs current content and re-apply the +delta on every path.
		$this->update_global_stats_delta( $previous, array() );
		$this->store_post_counts( $post_id, array() );
	}

	/**
	 * Re-add a post's block contribution on untrash.
	 *
	 * @since 1.0.14
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function track_block_addition_on_untrash( $post_id ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) || ! $this->is_public_post_type( $post->post_type ) ) {
			return;
		}

		// Reconcile the stored meta (cleared on trash) with the current content via
		// the same previous->current delta as save_post. Idempotent: whether this
		// fires before or after save_post, the count ends up correct without
		// double-counting.
		$previous = $this->get_post_counts( $post_id );
		$current  = $this->count_block_instances( $post->post_content );

		$this->update_global_stats_delta( $previous, $current );
		$this->store_post_counts( $post_id, $current );
	}

	/**
	 * Get the sitewide per-block usage counts (full block name => instance count).
	 *
	 * @since 1.0.14
	 * @return array<string,int> In-use blocks only (count > 0).
	 */
	public function get_block_usage_counts() {
		$data  = get_option( self::USAGE_OPTION, array() );
		$stats = ( is_array( $data ) && isset( $data['block_usage_stats'] ) && is_array( $data['block_usage_stats'] ) ) ? $data['block_usage_stats'] : array();

		$out = array();
		foreach ( $stats as $block => $count ) {
			$count = (int) $count;
			if ( $count > 0 ) {
				$out[ (string) $block ] = $count;
			}
		}

		return $out;
	}

	/**
	 * Apply a previous->current block-count delta to the sitewide tally.
	 *
	 * @since 1.0.14
	 * @param array<string,int> $previous Block counts before the change.
	 * @param array<string,int> $current  Block counts after the change.
	 * @return void
	 */
	private function update_global_stats_delta( array $previous, array $current ) {
		if ( $previous === $current ) {
			return;
		}

		$data  = get_option( self::USAGE_OPTION, array() );
		$data  = is_array( $data ) ? $data : array();
		$stats = ( isset( $data['block_usage_stats'] ) && is_array( $data['block_usage_stats'] ) ) ? $data['block_usage_stats'] : array();

		$blocks = array_unique( array_merge( array_keys( $previous ), array_keys( $current ) ) );
		foreach ( $blocks as $block ) {
			$prev = (int) ( $previous[ $block ] ?? 0 );
			$curr = (int) ( $current[ $block ] ?? 0 );
			if ( $prev === $curr ) {
				continue;
			}

			// Remove the old contribution, add the new one.
			$new = (int) ( $stats[ $block ] ?? 0 ) - $prev + $curr;
			if ( $new > 0 ) {
				$stats[ $block ] = $new;
			} else {
				unset( $stats[ $block ] ); // 0 = not in use; keep the map lean.
			}
		}

		$data['block_usage_stats'] = $stats;
		$data['last_updated']      = time();

		update_option( self::USAGE_OPTION, $data, false );
	}

	/**
	 * Count Spectra block instances in post content (recursive, includes inner blocks).
	 *
	 * @since 1.0.14
	 * @param string $content Post content.
	 * @return array<string,int> Full block name => instance count.
	 */
	private function count_block_instances( $content ) {
		$counts = array();

		if ( empty( $content ) || ! has_blocks( $content ) ) {
			return $counts;
		}

		$this->tally( parse_blocks( $content ), $counts );

		return $counts;
	}

	/**
	 * Recursively tally Spectra block instances.
	 *
	 * @since 1.0.14
	 * @param array<int|string,mixed> $blocks Parsed blocks.
	 * @param array<string,int>       $counts Reference to the running counts.
	 * @return void
	 */
	private function tally( $blocks, array &$counts ) {
		$trackable = $this->get_trackable_block_names();

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$name = ( isset( $block['blockName'] ) && is_string( $block['blockName'] ) ) ? $block['blockName'] : '';
			if ( '' !== $name && isset( $trackable[ $name ] ) ) {
				$counts[ $name ] = ( isset( $counts[ $name ] ) ? (int) $counts[ $name ] : 0 ) + 1;
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$this->tally( $block['innerBlocks'], $counts );
			}
		}
	}

	/**
	 * Build the set of trackable full block names from the registry (spectra/ + spectra-pro/).
	 *
	 * @since 1.0.14
	 * @return array<string,bool>
	 */
	private function get_trackable_block_names() {
		if ( null !== $this->trackable ) {
			return $this->trackable;
		}

		$this->trackable = array();

		if ( class_exists( '\WP_Block_Type_Registry' ) ) {
			foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $block_name => $block_type ) {
				if ( 0 === strpos( $block_name, 'spectra/' ) || 0 === strpos( $block_name, 'spectra-pro/' ) ) {
					$this->trackable[ $block_name ] = true;
				}
			}
		}

		return $this->trackable;
	}

	/**
	 * Get the per-post stored block counts.
	 *
	 * @since 1.0.14
	 * @param int $post_id Post ID.
	 * @return array<string,int>
	 */
	private function get_post_counts( $post_id ) {
		$counts = get_post_meta( $post_id, self::COUNTS_META, true );
		return is_array( $counts ) ? $counts : array();
	}

	/**
	 * Store (or clear) the per-post block counts.
	 *
	 * @since 1.0.14
	 * @param int               $post_id Post ID.
	 * @param array<string,int> $counts  Block counts (empty to clear).
	 * @return void
	 */
	private function store_post_counts( $post_id, array $counts ) {
		if ( empty( $counts ) ) {
			delete_post_meta( $post_id, self::COUNTS_META );
		} else {
			update_post_meta( $post_id, self::COUNTS_META, $counts );
		}
	}

	/**
	 * Whether usage tracking is enabled (opt-in).
	 *
	 * @since 1.0.14
	 * @return bool
	 */
	private function is_enabled() {
		return 'yes' === get_site_option( 'spectra_blocks_usage_optin', 'no' );
	}

	/**
	 * Whether a post type is public (and therefore tracked).
	 *
	 * @since 1.0.14
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	private function is_public_post_type( $post_type ) {
		return in_array( $post_type, get_post_types( array( 'public' => true ), 'names' ), true );
	}
}
