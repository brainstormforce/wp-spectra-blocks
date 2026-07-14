<?php
/**
 * View for rendering the Post Block.
 *
 * @since 1.0.0
 *
 * @package Spectra\Blocks\Post
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

// Generate pagination HTML if needed.
$pagination_html = '';

if ( 'none' !== $pagination_type && 'carousel' !== $layout_type ) {
	// Get the Spectra query from post-template block.
	global $spectra_current_query;

	// Fallback to global query if custom query not available.
	$query_to_use = ! empty( $spectra_current_query ) ? $spectra_current_query : $GLOBALS['wp_query'];

	// Get max pages from the query.
	$max_pages = isset( $query_to_use->max_num_pages ) ? (int) $query_to_use->max_num_pages : 0;

	// Only render if there's more than one page.
	if ( 1 < $max_pages ) {
		// Temporarily swap global $wp_query to use WordPress paginate_links() (following core pattern).
		$prev_wp_query = null;
		if ( ! empty( $spectra_current_query ) ) {
			$prev_wp_query = $GLOBALS['wp_query'];
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Temporarily overriding $wp_query to use paginate_links() with custom query; restored immediately after use (line 135).
			$GLOBALS['wp_query'] = $spectra_current_query;
		}

		// Get current page from query-specific URL parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for public pagination; value is sanitized with absint().
		$current_page = isset( $_GET[ $page_key ] ) ? max( 1, absint( $_GET[ $page_key ] ) ) : 1;

		// Generate pagination content based on type.
		if ( 'standard' === $pagination_type ) {
			// Get current URL and clean up existing query parameters.
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- $_SERVER values are sanitized immediately with sanitize_text_field() and esc_url_raw().
			$host        = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $host . $request_uri;

			// Remove existing page parameter for this query to avoid conflicts.
			$current_url = remove_query_arg( $page_key, $current_url );

			// Clean up any other pagination parameters from different queries on the same page
			// to prevent URL pollution and ensure clean pagination URLs.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading query parameters for URL cleanup; no data modification.
			if ( isset( $_GET ) && is_array( $_GET ) ) {
				$params_to_remove = array();
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading query parameters for URL cleanup; no data modification.
				foreach ( $_GET as $param => $value ) {
					// Remove any query-*-page parameters except the current one.
					if ( preg_match( '/^query-[a-f0-9]+-page$/', $param ) && $param !== $page_key ) {
						$params_to_remove[] = $param;
					}
				}
				if ( ! empty( $params_to_remove ) ) {
					$current_url = remove_query_arg( $params_to_remove, $current_url );
				}
			}

			// Standard pagination with page numbers using WordPress core function.
			$page_links = paginate_links(
				array(
					'base'      => add_query_arg( $page_key, '%#%', $current_url ),
					'format'    => '',
					'current'   => $current_page,
					'total'     => $max_pages,
					'prev_text' => $pagination_prev,
					'next_text' => $pagination_next,
					'type'      => 'array',
					'mid_size'  => 2,
					'end_size'  => 1,
				)
			);

			if ( is_array( $page_links ) ) {
				// Add Interactivity API attributes to pagination links.
				$enhanced_links = array();

				foreach ( $page_links as $page_link ) {
					$processor = new WP_HTML_Tag_Processor( $page_link );

					// Process both <a> tags and <span> tags.
					if ( $processor->next_tag() ) {
						$tag_name = $processor->get_tag();

						// Handle span.current - convert to anchor tag.
						if ( 'SPAN' === $tag_name && $processor->has_class( 'current' ) ) {
							// Extract page number from span content.
							preg_match( '/<span[^>]*>(.*?)<\/span>/', $page_link, $matches );
							$page_num = $matches[1] ?? '';

							if ( $page_num ) {
								// Create anchor tag for current page.
								$href      = add_query_arg( $page_key, $page_num, $current_url );
								$key       = 'pagination-link-' . md5( $href . $page_num );
								$page_link = '<a href="' . esc_url( $href ) . '" class="page-numbers current" aria-current="page" data-wp-key="' . esc_attr( $key ) . '" data-wp-on--click="spectra/post::actions.navigate" data-wp-on-async--mouseenter="spectra/post::actions.prefetch" data-wp-watch="spectra/post::callbacks.prefetch">' . esc_html( $page_num ) . '</a>';
							}
						} elseif ( 'A' === $tag_name ) {
							// Handle anchor tags.
							$href = $processor->get_attribute( 'href' );
							$key  = 'pagination-link-' . md5( $href . $processor->get_updated_html() );

							$processor->set_attribute( 'data-wp-key', $key );
							$processor->set_attribute( 'data-wp-on--click', 'spectra/post::actions.navigate' );
							$processor->set_attribute( 'data-wp-on-async--mouseenter', 'spectra/post::actions.prefetch' );
							$processor->set_attribute( 'data-wp-watch', 'spectra/post::callbacks.prefetch' );

							$page_link = $processor->get_updated_html();
						} elseif ( 'SPAN' === $tag_name && $processor->has_class( 'dots' ) ) {
							// Keep dots as-is.
							$page_link = $page_link;
						}
					}

					$enhanced_links[] = $page_link;
				}

				$pagination_html  = '<nav class="spectra-post-pagination spectra-pagination-align-' . esc_attr( $pagination_alignment ) . ' spectra-pagination-layout-' . esc_attr( $pagination_layout ) . '" aria-label="' . esc_attr__( 'Posts navigation', 'spectra-blocks' ) . '">';
				$pagination_html .= '<div class="spectra-post-pagination-numbers">' . implode( "\n", $enhanced_links ) . '</div>';
				$pagination_html .= '</nav>';
			}
		} elseif ( 'button' === $pagination_type ) {
			// Load more button pagination.
			$pagination_html  = '<nav class="spectra-post-pagination spectra-pagination-align-' . esc_attr( $pagination_alignment ) . '" aria-label="' . esc_attr__( 'Posts navigation', 'spectra-blocks' ) . '">';
			$pagination_html .= sprintf(
				'<div class="spectra-load-more-wrapper">
				<button class="spectra-load-more-button" data-wp-on--click="spectra/post::actions.loadMore" data-wp-bind--disabled="state.isLoading" data-wp-bind--hidden="!state.hasMore">
					<span data-wp-bind--hidden="state.isLoading">%s</span>
					<span data-wp-bind--hidden="!state.isLoading">%s</span>
				</button>
			</div>',
				esc_html( $pagination_button ),
				esc_html( $pagination_loading )
			);
			$pagination_html .= '</nav>';
		} elseif ( 'infinite' === $pagination_type ) {
			// Infinite scroll pagination - always auto-loads on scroll.
			$pagination_html  = '<div class="spectra-post-inf-loader" data-wp-bind--hidden="!state.hasMore" aria-live="polite" aria-busy="true">';
			$pagination_html .= '<div class="spectra-post-loader-1"></div>';
			$pagination_html .= '<div class="spectra-post-loader-2"></div>';
			$pagination_html .= '<div class="spectra-post-loader-3"></div>';
			$pagination_html .= '<span class="screen-reader-text">' . esc_html__( 'Loading more posts...', 'spectra-blocks' ) . '</span>';
			$pagination_html .= '</div>';
		}

		// Restore original $wp_query if we swapped it.
		if ( ! empty( $spectra_current_query ) && isset( $prev_wp_query ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring original $wp_query after temporary override (see line 31).
			$GLOBALS['wp_query'] = $prev_wp_query;
		}
	}
}

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $post_context, 'spectra/post' ) ); ?>
	data-wp-interactive="spectra/post"
	data-wp-router-region="query-<?php echo esc_attr( $query_id ); ?>"
	data-wp-key="<?php echo esc_attr( $query_id ); ?>"
	<?php if ( 'carousel' === $layout_type ) : ?>
		data-wp-init="callbacks.initCarousel"
		data-swiper="<?php echo esc_attr( wp_json_encode( $swiper_config ) ); ?>"
	<?php endif; ?>
	<?php if ( 'infinite' === $pagination_type ) : ?>
		data-wp-init--infinite-scroll="callbacks.initInfiniteScroll"
	<?php endif; ?>
>
	<?php HtmlSanitizer::render( $content ); ?>
	<?php
	if ( ! empty( $pagination_html ) ) {
		echo $pagination_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</div>

