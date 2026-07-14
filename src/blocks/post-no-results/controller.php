<?php
/**
 * Controller for the Post No Results block.
 *
 * @since 1.0.0
 * @package Spectra\Blocks\PostNoResults
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Queries\PostQuery;

// Get query parameters.
$query_obj = $block->context['spectra/post/query'] ?? array();

// Get pagination settings from context to match keys.
$query_id = $block->context['spectra/post/queryId'] ?? '';
$page_key = $block->context['spectra/post/pageKey'] ?? ( $query_id ? "query-{$query_id}-page" : 'page' );

// Build query arguments using shared helper.
$query_context = array(
	'pageKey'     => $page_key,
	'postsToShow' => $block->context['spectra/post/postsToShow'] ?? 6,
);

$query_args = PostQuery::get_query_args( $query_obj, $query_context );

// Execute query.
$query = new WP_Query( $query_args );

// Exit if posts found (we only want to show this block if NO posts serve).
if ( $query->have_posts() ) {
	return '';
}

// Get wrapper attributes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, array(), array(), array( 'spectra-post-no-results' ) );

return 'file:./view.php';
