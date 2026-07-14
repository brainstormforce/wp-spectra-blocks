<?php
/**
 * View for rendering the Post Template block.
 *
 * @since 1.0.0
 * @package Spectra\Blocks\PostTemplate
 */

use SpectraBlocks\Helpers\HtmlSanitizer;
use SpectraBlocks\Helpers\Renderer;

// Use div for carousel (Swiper.js requirement), ul for grid/masonry (semantic HTML).
$wrapper_tag = ( 'carousel' === $data['layout_type'] ) ? 'div' : 'ul';

$icon_props = array(
	'focusable' => 'false',
);
?>

<<?php echo esc_html( $wrapper_tag ); ?>
	<?php echo wp_kses_data( $data['wrapper_attributes'] ); ?>
>
	<?php if ( 'carousel' === $data['layout_type'] ) : ?>
		<div class="swiper-wrapper">
			<?php HtmlSanitizer::render( $data['posts_content'] ); ?>
		</div>
	<?php else : ?>
		<?php HtmlSanitizer::render( $data['posts_content'] ); ?>
	<?php endif; ?>
</<?php echo esc_html( $wrapper_tag ); ?>>

<?php if ( 'carousel' === $data['layout_type'] ) : ?>
	<?php if ( ! empty( $data['navigation'] ) ) : ?>
		<div 
			class="swiper-button-prev" 
			role="button"
			aria-label="<?php esc_attr_e( 'Previous slide', 'spectra-blocks' ); ?>"
			data-role="none"
			tabindex="0"
		>
			<span class="screen-reader-text"><?php esc_html_e( 'Previous slide', 'spectra-blocks' ); ?></span>
			<?php
			Renderer::svg_html(
				'arrow-left',
				false,
				array_merge( $icon_props, array( 'aria-hidden' => 'true' ) )
			);
			?>
		</div>
		<div 
			class="swiper-button-next" 
			role="button"
			aria-label="<?php esc_attr_e( 'Next slide', 'spectra-blocks' ); ?>"
			data-role="none"
			tabindex="0"
		>
			<span class="screen-reader-text"><?php esc_html_e( 'Next slide', 'spectra-blocks' ); ?></span>
			<?php
			Renderer::svg_html(
				'arrow-right',
				false,
				array_merge( $icon_props, array( 'aria-hidden' => 'true' ) )
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $data['pagination'] ) ) : ?>
		<div class="swiper-pagination"></div>
	<?php endif; ?>
<?php endif; ?>
