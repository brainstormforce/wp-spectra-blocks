<?php
/**
 * Controller for the Post Template block.
 *
 * @since 1.0.0
 * @package Spectra\Blocks\PostTemplate
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Queries\PostQuery;

// Get query and layout from parent Post block context.
$query_obj   = $block->context['spectra/post/query'] ?? array();
$layout_type = $block->context['spectra/post/layoutType'] ?? 'grid';
$navigation  = $block->context['spectra/post/navigation'] ?? true;
$pagination  = $block->context['spectra/post/pagination'] ?? true;


// Get pagination settings from context.
$query_id = $block->context['spectra/post/queryId'] ?? '';

$page_key = ( '' !== $query_id ) ? "query-{$query_id}-page" : ( $block->context['spectra/post/pageKey'] ?? 'page' );

// Build query arguments using shared helper.
$query_context = array(
	'pageKey'     => $page_key,
	'postsToShow' => $block->context['spectra/post/postsToShow'] ?? 6,
	'layoutType'  => $layout_type,
);

$query_args = PostQuery::get_query_args( $query_obj, $query_context );

// Execute query.
$query = new WP_Query( $query_args );

// Exit if no posts found.
if ( ! $query->have_posts() ) {
	return '';
}

// Get post type for inner block context filter.
$query_post_type = $query_args['post_type'];

// Store query globally for pagination block to access (following core/query pattern).
// Pagination will temporarily swap this with global $wp_query to use paginate_links().
global $spectra_current_query;
$spectra_current_query = $query;

// Preload featured images if needed.
if ( function_exists( 'block_core_post_template_uses_featured_image' ) &&
	block_core_post_template_uses_featured_image( $block->inner_blocks ) ) {
	update_post_thumbnail_cache( $query );
}

// Build the post items content.
$posts_content = '';
while ( $query->have_posts() ) {
	$query->the_post();
	$current_post_id = get_the_ID();

	// Get WordPress post classes.
	$post_classes = get_post_class( 'spectra-post-item' );

	// For carousel, add swiper-slide class.
	if ( 'carousel' === $layout_type ) {
		$post_classes[] = 'swiper-slide';
	}

	/**
	 * Filter the CSS classes for the post item.
	 *
	 * @since 1.0.0
	 * @param array    $post_classes    The array of CSS classes.
	 * @param int      $current_post_id The current post ID.
	 * @param WP_Block $block           The block instance.
	 * @param WP_Query $query           The WP_Query instance.
	 */
	$post_classes = apply_filters( 'spectra_post_item_classes', $post_classes, $current_post_id, $block, $query );

	// Prepare block instance for rendering inner blocks.
	$block_instance              = $block->parsed_block;
	$block_instance['blockName'] = 'core/null';

	// Set context for inner blocks.
	$filter_context = static function ( $context ) use ( $current_post_id, $query_post_type ) {
		$context['postType'] = $query_post_type;
		$context['postId']   = $current_post_id;
		return $context;
	};
	add_filter( 'render_block_context', $filter_context, 1 );

	// Render inner blocks.
	$block_content = ( new WP_Block( $block_instance ) )->render( array( 'dynamic' => false ) );

	remove_filter( 'render_block_context', $filter_context, 1 );

	// Build post item with proper HTML tag based on layout.
	// Carousel uses <div> (Swiper.js requirement), grid/masonry uses <li> (semantic HTML).
	$item_tag          = ( 'carousel' === $layout_type ) ? 'div' : 'li';
	$post_class_string = esc_attr( implode( ' ', $post_classes ) );
	$posts_content    .= sprintf(
		'<%1$s class="%2$s" data-wp-key="post-%3$s">%4$s</%1$s>',
		$item_tag,
		$post_class_string,
		$current_post_id,
		$block_content
	);
}

wp_reset_postdata();

// Custom classes.
$custom_classes = array();
if ( 'carousel' === $layout_type ) {
	$custom_classes[] = 'swiper';
} else {
	$custom_classes[] = 'spectra-post-loop-wrapper';
}

// Get wrapper attributes with proper block supports.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, array(), array(), $custom_classes );

// Prepare data for view.
$data = array(
	'wrapper_attributes' => $wrapper_attributes,
	'posts_content'      => $posts_content,
	'layout_type'        => $layout_type,
	'navigation'         => $navigation,
	'pagination'         => $pagination,
);

return 'file:./view.php';
