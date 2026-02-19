<?php
/**
 * REST API extensions for Spectra Blocks.
 * Registers additional REST fields for common post types.
 *
 * @package SpectraBlocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extends the WP REST API with additional fields needed by blocks.
 */
class Spectra_Blocks_Rest_Api {

	/**
	 * Initialize REST API extensions.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_fields' ) );
	}

	/**
	 * Register additional REST fields.
	 *
	 * @return void
	 */
	public static function register_fields() {
		$post_types = get_post_types( array( 'show_in_rest' => true ), 'names' );

		foreach ( $post_types as $post_type ) {
			// Featured image URL field.
			register_rest_field(
				$post_type,
				'spectra_blocks_featured_image_url',
				array(
					'get_callback'    => array( __CLASS__, 'get_featured_image_url' ),
					'update_callback' => null,
					'schema'          => array(
						'description' => __( 'Featured image URL.', 'spectra-blocks' ),
						'type'        => 'object',
					),
				)
			);

			// Author info field.
			register_rest_field(
				$post_type,
				'spectra_blocks_author_info',
				array(
					'get_callback'    => array( __CLASS__, 'get_author_info' ),
					'update_callback' => null,
					'schema'          => array(
						'description' => __( 'Post author info.', 'spectra-blocks' ),
						'type'        => 'object',
					),
				)
			);
		}
	}

	/**
	 * Get featured image URLs in multiple sizes.
	 *
	 * @param array $post Post data.
	 * @return array|null Image URL data or null.
	 */
	public static function get_featured_image_url( $post ) {
		if ( ! isset( $post['id'] ) ) {
			return null;
		}
		$featured_image_id = get_post_thumbnail_id( $post['id'] );
		if ( ! $featured_image_id ) {
			return null;
		}

		$sizes = array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' );
		$urls  = array();

		foreach ( $sizes as $size ) {
			$image_src = wp_get_attachment_image_src( $featured_image_id, $size );
			if ( $image_src ) {
				$urls[ $size ] = array(
					'url'    => $image_src[0],
					'width'  => $image_src[1],
					'height' => $image_src[2],
				);
			}
		}

		return $urls;
	}

	/**
	 * Get post author information.
	 *
	 * @param array $post Post data.
	 * @return array Author info.
	 */
	public static function get_author_info( $post ) {
		if ( ! isset( $post['id'] ) ) {
			return array();
		}
		$author_id = get_post_field( 'post_author', $post['id'] );
		return array(
			'display_name' => get_the_author_meta( 'display_name', $author_id ),
			'avatar_url'   => get_avatar_url( $author_id, array( 'size' => 96 ) ),
			'author_link'  => get_author_posts_url( $author_id ),
			'description'  => get_the_author_meta( 'description', $author_id ),
		);
	}
}
