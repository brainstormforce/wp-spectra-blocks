<?php
/**
 * Search Post Content ability.
 *
 * Searches post content by text/keyword.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * SearchPostContent ability class.
 *
 * @since 1.0.0
 */
class SearchPostContent extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/search-post-content';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Search Post Content', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Searches across published posts for content matching a keyword or phrase. Returns matching posts with excerpts.', 'spectra-blocks' );
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
			'required'   => array( 'keyword' ),
			'properties' => array(
				'keyword'   => array(
					'type'        => 'string',
					'description' => __( 'The keyword or phrase to search for in post content.', 'spectra-blocks' ),
				),
				'post_type' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Post types to search. Defaults to post and page.', 'spectra-blocks' ),
					'default'     => array( 'post', 'page' ),
				),
				'limit'     => array(
					'type'        => 'integer',
					'description' => __( 'Maximum number of results. Default 20.', 'spectra-blocks' ),
					'default'     => 20,
				),
			),
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
				'posts'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'      => array( 'type' => 'integer' ),
							'title'   => array( 'type' => 'string' ),
							'type'    => array( 'type' => 'string' ),
							'url'     => array( 'type' => 'string' ),
							'excerpt' => array( 'type' => 'string' ),
						),
					),
				),
				'total'       => array( 'type' => 'integer' ),
				'total_found' => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array|WP_Error Result or error.
	 */
	public function execute( array $params ) {
		if ( empty( $params['keyword'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The keyword parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$keyword    = sanitize_text_field( $params['keyword'] );
		$post_types = ! empty( $params['post_type'] ) ? array_map( 'sanitize_text_field', (array) $params['post_type'] ) : array( 'post', 'page' );
		$limit      = ! empty( $params['limit'] ) ? min( absint( $params['limit'] ), 100 ) : 20;

		$query = new WP_Query(
			array(
				's'              => $keyword,
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'relevance',
			)
		);

		$posts = array();
		foreach ( $query->posts as $post ) {
			$content = wp_strip_all_tags( $post->post_content );
			$pos     = stripos( $content, $keyword );
			$excerpt = '';

			if ( false !== $pos ) {
				$start   = max( 0, $pos - 50 );
				$excerpt = substr( $content, $start, 200 );

				if ( $start > 0 ) {
					$excerpt = '...' . $excerpt;
				}

				if ( strlen( $content ) > $start + 200 ) {
					$excerpt .= '...';
				}
			}

			$posts[] = array(
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'type'    => $post->post_type,
				'url'     => get_permalink( $post->ID ),
				'excerpt' => $excerpt,
			);
		}

		return array(
			'posts'       => $posts,
			'total'       => count( $posts ),
			'total_found' => (int) $query->found_posts,
		);
	}
}
