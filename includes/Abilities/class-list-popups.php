<?php
/**
 * List Popups ability.
 *
 * Returns a list of all Spectra popups with filtering and pagination.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * ListPopups ability class.
 *
 * @since 1.0.0
 */
class ListPopups extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/list-popups';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'List Spectra Popups', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Returns a list of all Spectra popup builder popups, optionally filtered by enabled status. Includes popup title, type, enabled state, and repetition setting.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-content';
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
			'properties' => array(
				'status'   => array(
					'type'        => 'string',
					'description' => __( 'Filter by popup status: all, enabled, or disabled.', 'spectra-blocks' ),
					'enum'        => array( 'all', 'enabled', 'disabled' ),
					'default'     => 'all',
				),
				'page'     => array(
					'type'        => 'integer',
					'description' => __( 'Page number for pagination.', 'spectra-blocks' ),
					'default'     => 1,
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => __( 'Number of popups per page (max 50).', 'spectra-blocks' ),
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
				'popups'      => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'id'         => array( 'type' => 'integer' ),
							'title'      => array( 'type' => 'string' ),
							'type'       => array( 'type' => 'string' ),
							'enabled'    => array( 'type' => 'boolean' ),
							'repetition' => array( 'type' => 'number' ),
							'date'       => array( 'type' => 'string' ),
							'modified'   => array( 'type' => 'string' ),
						),
					),
				),
				'total'       => array( 'type' => 'integer' ),
				'total_pages' => array( 'type' => 'integer' ),
				'page'        => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Check if the current user has permission.
	 *
	 * Requires manage_options since popup CPT requires it.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'spectra_blocks_rest_forbidden',
			__( 'You do not have permission to manage popups.', 'spectra-blocks' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array List of popups.
	 */
	public function execute( array $params ): array {
		$status   = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'all';
		$page     = isset( $params['page'] ) ? max( 1, absint( $params['page'] ) ) : 1;
		$per_page = isset( $params['per_page'] ) ? min( 50, max( 1, absint( $params['per_page'] ) ) ) : 20;

		$allowed_statuses = array( 'all', 'enabled', 'disabled' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'all';
		}

		$query_args = array(
			'post_type'      => 'spectra-blocks-popup',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( 'all' !== $status ) {
			$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'spectra-blocks-popup-enabled',
					'value'   => 'enabled' === $status ? '1' : '0',
					'compare' => '=',
				),
			);
		}

		$query  = new WP_Query( $query_args );
		$popups = array();

		foreach ( $query->posts as $post ) {
			$popups[] = array(
				'id'         => $post->ID,
				'title'      => $post->post_title,
				'type'       => get_post_meta( $post->ID, 'spectra-blocks-popup-type', true ) ? get_post_meta( $post->ID, 'spectra-blocks-popup-type', true ) : 'unset',
				'enabled'    => (bool) get_post_meta( $post->ID, 'spectra-blocks-popup-enabled', true ),
				'repetition' => (float) get_post_meta( $post->ID, 'spectra-blocks-popup-repetition', true ),
				'date'       => $post->post_date,
				'modified'   => $post->post_modified,
			);
		}

		return array(
			'popups'      => $popups,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $page,
		);
	}
}
