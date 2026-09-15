<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Content
 */

use SpectraBlocks\AssetLoader;

// `wp_kses_post()` with the imported-markup allowance, but ONLY around this
// call. WP's `post` context carries neither `<canvas>` nor inline SVG, so an
// imported page loses its drawing surface (the block's `spectraCustomJS`
// no-ops without the element) and its icons (the `<span>` survives, the `<svg>`
// inside it does not). Scoping the filter to this call keeps every other
// `post`-context `wp_kses_post()` on the page on the standard list; non-imported
// pages are unaffected — the filter's own guards return it too.
$spectra_content_html = AssetLoader::with_imported_markup_allowed( (string) $text );
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
