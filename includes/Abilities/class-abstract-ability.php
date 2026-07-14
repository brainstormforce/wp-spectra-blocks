<?php
/**
 * Abstract base class for all Spectra Blocks abilities.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use SpectraBlocks\Traits\Singleton;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Ability class.
 *
 * Every concrete ability extends this class and implements the abstract methods.
 *
 * @since 1.0.0
 */
abstract class AbstractAbility {

	use Singleton;

	/**
	 * Get the unique ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Ability name (e.g. 'spectra-blocks/create-buttons').
	 */
	abstract public function get_name(): string;

	/**
	 * Get the human-readable label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Translated label.
	 */
	abstract public function get_label(): string;

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string Translated description.
	 */
	abstract public function get_description(): string;

	/**
	 * Get the ability category slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string Category slug (e.g. 'spectra-blocks-content').
	 */
	abstract public function get_category(): string;

	/**
	 * Get the JSON Schema for input parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return array JSON Schema array.
	 */
	abstract public function get_input_schema(): array;

	/**
	 * Get the JSON Schema for the output.
	 *
	 * @since 1.0.0
	 *
	 * @return array JSON Schema array.
	 */
	abstract public function get_output_schema(): array;

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Validated input parameters.
	 * @return array|WP_Error Result array or error.
	 */
	abstract public function execute( array $params );

	/**
	 * Get ability annotations for REST discovery.
	 *
	 * Override in subclasses to declare readonly, destructive, or idempotent behavior.
	 * These annotations drive HTTP method routing in the WordPress MCP Adapter:
	 * - readonly: true → GET
	 * - destructive: true → DELETE
	 * - All others → POST
	 *
	 * @since 1.0.0
	 *
	 * @return array { readonly: bool, destructive: bool, idempotent: bool }
	 */
	public function get_annotations(): array {
		$schema             = $this->get_input_schema();
		$is_replace_default = isset( $schema['properties']['mode']['default'] )
			&& 'replace' === $schema['properties']['mode']['default'];
		return array(
			'readonly'    => false,
			'destructive' => $is_replace_default,
			'idempotent'  => false,
		);
	}

	/**
	 * Register this ability with the WordPress Abilities API.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			$this->get_name(),
			array(
				'label'               => $this->get_label(),
				'description'         => $this->get_description(),
				'category'            => $this->get_category(),
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => $this->get_annotations(),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Check if the current user has permission to execute this ability.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function check_permission() {
		$annotations = $this->get_annotations();
		$capability  = ! empty( $annotations['readonly'] ) ? 'edit_posts' : 'edit_others_posts';

		if ( current_user_can( $capability ) ) {
			return true;
		}

		return new WP_Error(
			'spectra_blocks_rest_forbidden',
			__( 'You do not have permission to perform this action.', 'spectra-blocks' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Get the blocks build directory path.
	 *
	 * @since 1.0.0
	 *
	 * @return string Absolute path to the blocks build directory.
	 */
	protected function get_blocks_dir(): string {
		return SPECTRA_BLOCKS_DIR . 'build/blocks/';
	}

	/**
	 * Neutralize --> sequences inside block-comment JSON attribute payloads.
	 *
	 * A --> sequence inside a serialized block comment would close the HTML
	 * comment delimiter early, silently corrupting the block tree.
	 *
	 * @since 1.0.0
	 *
	 * @param string $markup Serialized block markup.
	 * @return string Sanitized markup.
	 */
	protected function sanitize_block_markup( string $markup ): string {
		$result = preg_replace_callback(
			'/<!--\s+wp:[\w\/\-]+\s+(\{[^<]*\})\s+-->/U',
			static function ( $m ) {
				$json = $m[1];
				$safe = str_replace( '-->', '-- >', $json );
				return str_replace( $json, $safe, $m[0] );
			},
			$markup
		);
		return null !== $result ? $result : $markup;
	}

	/**
	 * Returns the build/blocks/ directory for spectra-blocks-pro, or empty string if not active.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_pro_blocks_dir(): string {
		return defined( 'SPECTRA_BLOCKS_PRO_DIR' ) ? SPECTRA_BLOCKS_PRO_DIR . 'build/blocks/' : '';
	}

	/**
	 * Whether spectra-blocks-pro is active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	protected function is_pro_active(): bool {
		return defined( 'SPECTRA_BLOCKS_PRO_DIR' );
	}

	/**
	 * Validate and retrieve a post object from a post_id parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID to validate.
	 * @return \WP_Post|WP_Error The post object or an error.
	 */
	protected function get_validated_post( int $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return new WP_Error(
				'spectra_blocks_invalid_post',
				__( 'The specified post does not exist.', 'spectra-blocks' ),
				array( 'status' => 404 )
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'spectra_blocks_rest_forbidden',
				__( 'You do not have permission to edit this post.', 'spectra-blocks' ),
				array( 'status' => 403 )
			);
		}

		if ( ! post_type_supports( $post->post_type, 'editor' ) ) {
			return new WP_Error(
				'spectra_blocks_invalid_post_type',
				__( 'This post type does not support the block editor.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		if ( 'trash' === $post->post_status ) {
			return new WP_Error(
				'spectra_blocks_trashed_post',
				__( 'Cannot modify a trashed post.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		return $post;
	}

	/**
	 * Insert block markup into an existing post.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id      The post ID.
	 * @param string $block_markup The serialized block markup to insert.
	 * @param string $mode         Insertion mode: 'replace', 'append', or 'prepend'.
	 * @return array|WP_Error Result with post_id and updated content, or WP_Error.
	 */
	protected function insert_into_post( int $post_id, string $block_markup, string $mode = 'append' ) {
		global $wpdb;

		$post = $this->get_validated_post( $post_id );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$allowed_modes = array( 'replace', 'append', 'prepend' );
		if ( ! in_array( $mode, $allowed_modes, true ) ) {
			$mode = 'append';
		}

		$snapshot_modified = $post->post_modified;
		$existing_content  = $post->post_content;

		switch ( $mode ) {
			case 'replace':
				$new_content = $block_markup;
				break;

			case 'prepend':
				$new_content = $block_markup . "\n\n" . $existing_content;
				break;

			case 'append':
			default:
				$new_content = $existing_content . "\n\n" . $block_markup;
				break;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Optimistic concurrency: WHERE post_modified matches snapshot; wp_update_post() does not support conditional WHERE.
		$updated = $wpdb->update(
			$wpdb->posts,
			array(
				'post_content'      => $new_content,
				'post_modified'     => current_time( 'mysql' ),
				'post_modified_gmt' => current_time( 'mysql', true ),
			),
			array(
				'ID'            => $post_id,
				'post_modified' => $snapshot_modified,
			),
			array( '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);

		if ( false === $updated ) {
			return new WP_Error(
				'spectra_blocks_db_error',
				__( 'Failed to update post content.', 'spectra-blocks' ),
				array( 'status' => 500 )
			);
		}

		if ( 0 === $updated ) {
			return new WP_Error(
				'spectra_blocks_concurrent_modification',
				__( 'Post was modified concurrently. Please retry.', 'spectra-blocks' ),
				array( 'status' => 409 )
			);
		}

		clean_post_cache( $post_id );

		return array(
			'post_id'      => $post_id,
			'post_content' => $new_content,
		);
	}

	/**
	 * Build the common output schema for abilities that produce block markup.
	 *
	 * @since 1.0.0
	 *
	 * @return array JSON Schema for block markup output.
	 */
	protected function get_block_markup_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'block_markup' => array(
					'type'        => 'string',
					'description' => __( 'The serialized block markup.', 'spectra-blocks' ),
				),
				'post_id'      => array(
					'type'        => 'integer',
					'description' => __( 'The post ID if content was inserted into a post.', 'spectra-blocks' ),
				),
				'post_content' => array(
					'type'        => 'string',
					'description' => __( 'The full post content after insertion.', 'spectra-blocks' ),
				),
			),
		);
	}

	/**
	 * Build the result array, optionally inserting into a post.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_markup The serialized block markup.
	 * @param array  $params       The ability parameters (may contain post_id and mode).
	 * @return array|WP_Error Result array or WP_Error.
	 */
	protected function maybe_insert_and_return( string $block_markup, array $params ) {
		$block_markup = $this->sanitize_block_markup( $block_markup );
		$result       = array( 'block_markup' => $block_markup );

		if ( ! empty( $params['post_id'] ) ) {
			$post_id = absint( $params['post_id'] );

			if ( $post_id <= 0 ) {
				return new WP_Error(
					'spectra_blocks_invalid_post_id',
					__( 'The post_id must be a positive integer.', 'spectra-blocks' ),
					array( 'status' => 400 )
				);
			}

			$mode = ! empty( $params['mode'] ) ? sanitize_text_field( $params['mode'] ) : 'append';

			$insert_result = $this->insert_into_post( $post_id, $block_markup, $mode );

			if ( is_wp_error( $insert_result ) ) {
				return $insert_result;
			}

			$result['post_id']      = $insert_result['post_id'];
			$result['post_content'] = $insert_result['post_content'];
		}

		return $result;
	}

	/**
	 * Get the common post_id and mode schema properties for input schemas.
	 *
	 * @since 1.0.0
	 *
	 * @return array Schema properties for post_id and mode.
	 */
	protected function get_post_insertion_schema(): array {
		return array(
			'post_id' => array(
				'type'        => 'integer',
				'description' => __( 'Optional post ID to insert the block into.', 'spectra-blocks' ),
			),
			'mode'    => array(
				'type'        => 'string',
				'description' => __( 'Insertion mode: replace, append, or prepend.', 'spectra-blocks' ),
				'enum'        => array( 'replace', 'append', 'prepend' ),
				'default'     => 'append',
			),
		);
	}

	/**
	 * Parse blocks from a post, filtering out empty/whitespace-only blocks.
	 *
	 * Returns an indexed array of meaningful blocks (non-null blockName).
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID.
	 * @return array|\WP_Post|WP_Error Array with 'post' and 'blocks' keys, or WP_Error.
	 */
	protected function get_parsed_blocks( int $post_id ) {
		$post = $this->get_validated_post( $post_id );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$all_blocks = parse_blocks( $post->post_content );
		$blocks     = array();
		$index      = 0;

		foreach ( $all_blocks as $raw_index => $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			$blocks[] = array(
				'index'       => $index,
				'raw_index'   => $raw_index,
				'blockName'   => $block['blockName'],
				'attrs'       => $block['attrs'] ?? array(),
				'innerBlocks' => $block['innerBlocks'] ?? array(),
				'innerHTML'   => $block['innerHTML'] ?? '',
			);

			++$index;
		}

		return array(
			'post'   => $post,
			'blocks' => $blocks,
		);
	}

	/**
	 * Update post content by serializing a blocks array back to the post.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $post_id The post ID.
	 * @param array $blocks  The full parsed blocks array (including empty blocks).
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	protected function update_post_blocks( int $post_id, array $blocks ) {
		$new_content = serialize_blocks( $blocks );

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => wp_slash( $new_content ),
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Find the raw index of a block by its flat index (skipping empty blocks).
	 *
	 * @since 1.0.0
	 *
	 * @param array $all_blocks The full parsed blocks array from parse_blocks().
	 * @param int   $index      The flat index (0-based, skipping empty blocks).
	 * @return int|WP_Error The raw index or WP_Error if not found.
	 */
	protected function find_block_raw_index( array $all_blocks, int $index ) {
		$current = 0;

		foreach ( $all_blocks as $raw_index => $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			if ( $current === $index ) {
				return $raw_index;
			}

			++$current;
		}

		return new WP_Error(
			'spectra_blocks_invalid_block_index',
			/* translators: %d: block index */
			sprintf( __( 'No block found at index %d.', 'spectra-blocks' ), $index ),
			array( 'status' => 404 )
		);
	}
}
