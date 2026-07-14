<?php
/**
 * Search Posts By Block ability.
 *
 * Finds posts containing a specific Spectra block type.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * SearchPostsByBlock ability class.
 *
 * @since 1.0.0
 */
class SearchPostsByBlock extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/search-posts-by-block';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Search Posts by Block Type', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Finds posts that contain a specific Spectra block type. Returns post IDs, titles, and block counts.', 'spectra-blocks' );
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
			'required'   => array( 'block_name' ),
			'properties' => array(
				'block_name' => array(
					'type'        => 'string',
					'description' => __( 'The block name to search for (e.g. "container", "spectra-pro/mega-menu"). The spectra/ prefix is added automatically if no namespace is given.', 'spectra-blocks' ),
				),
				'post_type'  => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Post types to search. Defaults to post and page.', 'spectra-blocks' ),
					'default'     => array( 'post', 'page' ),
				),
				'limit'      => array(
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
				'posts' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array( 'type' => 'integer' ),
							'title' => array( 'type' => 'string' ),
							'type'  => array( 'type' => 'string' ),
							'url'   => array( 'type' => 'string' ),
						),
					),
				),
				'total' => array( 'type' => 'integer' ),
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
		if ( empty( $params['block_name'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The block_name parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$block_name = sanitize_text_field( $params['block_name'] );

		// Add spectra/ prefix if not present.
		if ( ! str_contains( $block_name, '/' ) ) {
			$block_name = 'spectra/' . $block_name;
		}

		$post_types = ! empty( $params['post_type'] ) ? array_map( 'sanitize_text_field', (array) $params['post_type'] ) : array( 'post', 'page' );
		$limit      = ! empty( $params['limit'] ) ? min( absint( $params['limit'] ), 100 ) : 20;

		// Search for the block comment in post_content.
		global $wpdb;

		$search_term            = $wpdb->esc_like( 'wp:' . $block_name ) . '%';
		$post_type_placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_type FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_type IN ({$post_type_placeholders}) AND post_status = 'publish' ORDER BY post_date DESC LIMIT %d",
				array_merge(
					array( '%<!-- ' . $search_term ),
					$post_types,
					array( $limit )
				)
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$posts = array();
		foreach ( $results as $row ) {
			$posts[] = array(
				'id'    => (int) $row->ID,
				'title' => $row->post_title,
				'type'  => $row->post_type,
				'url'   => get_permalink( (int) $row->ID ),
			);
		}

		return array(
			'posts' => $posts,
			'total' => count( $posts ),
		);
	}
}
