<?php
/**
 * Insert Into Post trait.
 *
 * Shared logic for inserting block markup into a post (replace, append, prepend).
 *
 * @package Spectra_Blocks
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait Spectra_Blocks_Insert_Into_Post_Trait.
 *
 * @since 1.0.0
 */
trait Spectra_Blocks_Insert_Into_Post_Trait {

	/**
	 * Insert block markup into a post.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $markup  Block markup.
	 * @param string $mode    Insert mode: 'replace', 'append', or 'prepend'.
	 * @return true|WP_Error
	 */
	protected function insert_into_post( $post_id, $markup, $mode ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'post_not_found', __( 'Post not found.', 'spectra-blocks' ) );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'insufficient_permissions', __( 'Cannot edit this post.', 'spectra-blocks' ) );
		}
		if ( ! post_type_supports( $post->post_type, 'editor' ) ) {
			return new WP_Error( 'unsupported_post_type', __( 'This post type does not support block editing.', 'spectra-blocks' ) );
		}
		if ( 'trash' === $post->post_status ) {
			return new WP_Error( 'post_trashed', __( 'Cannot edit blocks in a trashed post.', 'spectra-blocks' ) );
		}

		$snapshot_modified = $post->post_modified;

		$existing = $post->post_content;
		switch ( $mode ) {
			case 'replace':
				$content = $markup;
				break;
			case 'prepend':
				$content = $markup . "\n" . $existing;
				break;
			default:
				$content = $existing . "\n" . $markup;
				break;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Optimistic concurrency: WHERE post_modified matches snapshot; wp_update_post() does not support conditional WHERE.
		$updated = $wpdb->update(
			$wpdb->posts,
			array(
				'post_content'      => $content,
				'post_modified'     => current_time( 'mysql' ),
				'post_modified_gmt' => current_time( 'mysql', 1 ),
			),
			array(
				'ID'            => $post_id,
				'post_modified' => $snapshot_modified,
			),
			array( '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'db_error', __( 'Failed to update post content.', 'spectra-blocks' ) );
		}
		if ( 0 === $updated ) {
			return new WP_Error( 'concurrent_modification', __( 'Post was modified concurrently. Please retry.', 'spectra-blocks' ) );
		}

		clean_post_cache( $post_id );

		return true;
	}
}
