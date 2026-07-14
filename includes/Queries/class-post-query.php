<?php
/**
 * Post Query Builder
 *
 * @package Spectra\Queries
 */

namespace SpectraBlocks\Queries;

use SpectraBlocks\Traits\Singleton;

/**
 * PostQuery class.
 *
 * Helper class for Post block query generation.
 *
 * @since 1.0.0
 */
class PostQuery {

	use Singleton;

	/**
	 * Get WP_Query arguments from block attributes.
	 *
	 * @param array $query_obj  The query attributes object.
	 * @param array $context    The block context (optional).
	 * @return array            The WP_Query arguments.
	 */
	public static function get_query_args( $query_obj, $context = array() ) {
		// Get query parameters.
		$post_type = $query_obj['postType'] ?? 'post';
		$per_page  = $query_obj['perPage'] ?? $context['postsToShow'] ?? 6;
		$order     = $query_obj['order'] ?? 'desc';
		$order_by  = $query_obj['orderBy'] ?? 'date';
		$author    = $query_obj['author'] ?? array();
		$search    = $query_obj['search'] ?? '';
		$exclude   = $query_obj['exclude'] ?? array();
		$sticky    = $query_obj['sticky'] ?? '';
		$offset    = $query_obj['offset'] ?? 0;
		$tax_vars  = $query_obj['taxQuery'] ?? null;

		// Handle pagination.
		$paged = 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for public pagination; value is sanitized with absint().
		if ( isset( $context['pageKey'] ) && isset( $_GET[ $context['pageKey'] ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for public pagination; value is sanitized with absint().
			$paged = max( 1, absint( $_GET[ $context['pageKey'] ] ) );
		} elseif ( get_query_var( 'paged' ) ) {
			$paged = get_query_var( 'paged' );
		}

		$query_args = array(
			'post_type'      => $post_type,
			'posts_per_page' => $per_page,
			'order'          => $order,
			'orderby'        => $order_by,
			'paged'          => $paged,
			'post_status'    => 'publish',
		);

		// Override posts_per_page for carousel layout.
		if ( isset( $context['layoutType'] ) && 'carousel' === $context['layoutType'] ) {
			$query_args['posts_per_page'] = $context['postsToShow'] ?? $per_page;
			// Carousel doesn't use paged pagination.
			unset( $query_args['paged'] );
			$query_args['offset'] = $offset; // For carousel we use raw offset.
		} else {
			$query_args['offset'] = $offset + ( ( $paged - 1 ) * $per_page );
		}

		// Handle Exclude Current Post.
		if ( isset( $query_obj['excludeCurrentPost'] ) && true === $query_obj['excludeCurrentPost'] ) {
			$current_id = get_the_ID();
			if ( $current_id ) {
				$exclude[] = $current_id;
			}
		}

		// Add filters.
		if ( ! empty( $author ) ) {
			$query_args['author__in'] = is_array( $author ) ? $author : array( $author );
		}
		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}
		if ( ! empty( $exclude ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQuery.post__not_in -- Exclude functionality is essential for the Post block; users explicitly configure excluded posts.
			$query_args['post__not_in'] = is_array( $exclude ) ? $exclude : array( $exclude );
		}
		// Handle sticky posts behavior.
		// - '': Include sticky posts at the top (default WordPress behavior).
		// - 'exclude': Exclude sticky posts from results (add to post__not_in).
		// - 'ignore': Ignore sticky status, treat as regular posts (ignore_sticky_posts).
		// - 'only': Show only sticky posts (use post__in).
		if ( 'exclude' === $sticky ) {
			$sticky_ids = get_option( 'sticky_posts' );
			if ( ! empty( $sticky_ids ) ) {
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQuery.post__not_in -- Excluding sticky posts is a core feature; users explicitly configure this.
				$existing_excluded                 = $query_args['post__not_in'] ?? array();
				$query_args['post__not_in']        = array_merge( $existing_excluded, $sticky_ids );
				$query_args['ignore_sticky_posts'] = 1; // Also ignore sticky post ordering.
			}
		} elseif ( 'ignore' === $sticky ) {
			$query_args['ignore_sticky_posts'] = 1;
		} elseif ( 'only' === $sticky ) {
			$sticky_ids             = get_option( 'sticky_posts' );
			$query_args['post__in'] = ! empty( $sticky_ids ) ? $sticky_ids : array( 0 );
		}

		// Build Taxonomy Query.
		if ( ! empty( $tax_vars ) && is_array( $tax_vars ) ) {
			$tax_query = array( 'relation' => 'AND' );
			foreach ( $tax_vars as $tax_slug => $terms ) {
				if ( ! empty( $terms ) ) {
					$tax_query[] = array(
						'taxonomy' => $tax_slug,
						'field'    => 'term_id',
						'terms'    => $terms,
					);
				}
			}
			if ( 1 < count( $tax_query ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filtering is a core feature of the Post block; query is necessary for user-configured filters.
				$query_args['tax_query'] = $tax_query;
			}
		}

		/**
		 * Filter the query arguments for the Post block.
		 *
		 * @since 1.0.0
		 * @param array $query_args The WP_Query arguments.
		 * @param array $query_obj  The query attributes object.
		 * @param array $context    The block context.
		 */
		$query_args = apply_filters( 'spectra_post_query_args', $query_args, $query_obj, $context );

		return $query_args;
	}
}
