<?php
/**
 * Create Post ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreatePost ability class.
 *
 * @since 1.0.0
 */
class CreatePost extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-post';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Post Query Block', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra post query block that displays posts in a grid, masonry, or carousel layout with configurable query options.', 'spectra-blocks' );
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
	 * Get the input schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array_merge(
				array(
					'layoutType'  => array(
						'type'        => 'string',
						'description' => __( 'Display layout: grid, masonry, or carousel.', 'spectra-blocks' ),
						'enum'        => array( 'grid', 'masonry', 'carousel' ),
						'default'     => 'grid',
					),
					'postsToShow' => array(
						'type'        => 'number',
						'description' => __( 'Number of posts to display.', 'spectra-blocks' ),
						'default'     => 6,
					),
					'orderBy'     => array(
						'type'        => 'string',
						'description' => __( 'Order posts by: date, title, rand, or menu_order.', 'spectra-blocks' ),
						'enum'        => array( 'date', 'title', 'rand', 'menu_order' ),
						'default'     => 'date',
					),
					'order'       => array(
						'type'        => 'string',
						'description' => __( 'Sort order: asc or desc.', 'spectra-blocks' ),
						'enum'        => array( 'asc', 'desc' ),
						'default'     => 'desc',
					),
					'columns'     => array(
						'type'        => 'number',
						'description' => __( 'Number of columns for grid or masonry layout.', 'spectra-blocks' ),
					),
					'postType'    => array(
						'type'        => 'string',
						'description' => __( 'Post type to query. Defaults to "post".', 'spectra-blocks' ),
						'default'     => 'post',
					),
					'navigation'  => array(
						'type'        => 'boolean',
						'description' => __( 'Show navigation arrows. Applies to carousel layout.', 'spectra-blocks' ),
						'default'     => true,
					),
					'pagination'  => array(
						'type'        => 'boolean',
						'description' => __( 'Show pagination dots. Applies to carousel layout.', 'spectra-blocks' ),
						'default'     => true,
					),
					'autoplay'    => array(
						'type'        => 'boolean',
						'description' => __( 'Enable carousel autoplay.', 'spectra-blocks' ),
						'default'     => true,
					),
					'loop'        => array(
						'type'        => 'boolean',
						'description' => __( 'Enable carousel loop.', 'spectra-blocks' ),
						'default'     => true,
					),
					'equalHeight' => array(
						'type'        => 'boolean',
						'description' => __( 'Force equal height cards.', 'spectra-blocks' ),
						'default'     => true,
					),
				),
				$this->get_post_insertion_schema()
			),
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_output_schema(): array {
		return $this->get_block_markup_output_schema();
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $params Input parameters.
	 * @return array<string, mixed>|WP_Error
	 */
	public function execute( array $params ) {
		$allowed_layout_types = array( 'grid', 'masonry', 'carousel' );
		$layout_type          = ! empty( $params['layoutType'] ) && in_array( $params['layoutType'], $allowed_layout_types, true )
			? $params['layoutType']
			: 'grid';

		$allowed_order_by = array( 'date', 'title', 'rand', 'menu_order' );
		$order_by         = ! empty( $params['orderBy'] ) && in_array( $params['orderBy'], $allowed_order_by, true )
			? $params['orderBy']
			: 'date';

		$order = ! empty( $params['order'] ) && in_array( $params['order'], array( 'asc', 'desc' ), true )
			? $params['order']
			: 'desc';

		$posts_to_show = isset( $params['postsToShow'] ) ? absint( is_scalar( $params['postsToShow'] ) ? (int) $params['postsToShow'] : 0 ) : 6;
		if ( $posts_to_show < 1 ) {
			$posts_to_show = 6;
		}

		$post_type = ! empty( $params['postType'] ) ? sanitize_key( is_scalar( $params['postType'] ) ? (string) $params['postType'] : '' ) : 'post';

		$block_attrs = array(
			'layoutType'        => $layout_type,
			'postsToShow'       => $posts_to_show,
			'variationSelected' => true,
			'query'             => array(
				'perPage'            => $posts_to_show,
				'postType'           => $post_type,
				'order'              => $order,
				'orderBy'            => $order_by,
				'author'             => array(),
				'search'             => '',
				'exclude'            => array(),
				'sticky'             => '',
				'inherit'            => false,
				'excludeCurrentPost' => false,
				'taxQuery'           => null,
			),
		);

		if ( isset( $params['columns'] ) ) {
			$block_attrs['columns'] = absint( is_scalar( $params['columns'] ) ? (int) $params['columns'] : 0 );
		}

		if ( isset( $params['navigation'] ) ) {
			$block_attrs['navigation'] = (bool) $params['navigation'];
		}

		if ( isset( $params['pagination'] ) ) {
			$block_attrs['pagination'] = (bool) $params['pagination'];
		}

		if ( isset( $params['autoplay'] ) ) {
			$block_attrs['autoplay'] = (bool) $params['autoplay'];
		}

		if ( isset( $params['loop'] ) ) {
			$block_attrs['loop'] = (bool) $params['loop'];
		}

		if ( isset( $params['equalHeight'] ) ) {
			$block_attrs['equalHeight'] = (bool) $params['equalHeight'];
		}

		$attrs_json   = ' ' . wp_json_encode( $block_attrs );
		$block_markup = '<!-- wp:spectra/post' . $attrs_json . ' -->'
			. "\n" . '<!-- /wp:spectra/post -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
