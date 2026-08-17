<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Content
 */

use SpectraBlocks\AssetLoader;

// `wp_kses_post()` with `<canvas>` permitted, but ONLY around this call. WP's
// `post` context strips `<canvas>`, and an imported page's drawing script
// (carried on the block's `spectraCustomJS`) no-ops without the element. The
// allowance used to be a request-wide filter, which widened the allow-list for
// every other `wp_kses_post()` on the page too. Non-imported pages are
// unaffected — the filter's own guards return the standard list.
$spectra_content_html = AssetLoader::with_canvas_allowed( (string) $text );
?>
<?php if ( $needs_span_wrapper ) : ?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<span><?php echo $spectra_content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post()'d above. ?></span>
	</div>
<?php else : ?>
	<<?php echo esc_attr( $tag_name ); ?>
		<?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<?php echo $spectra_content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post()'d above. ?>
	</<?php echo esc_attr( $tag_name ); ?>>
<?php endif; ?>
