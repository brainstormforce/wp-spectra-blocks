<?php
/**
 * View for rendering the block.
 * 
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Content
 */

?>
<?php if ( $needs_span_wrapper ) : ?>
	<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<span><?php echo wp_kses_post( $text ); ?></span>
	</div>
<?php else : ?>
	<<?php echo esc_attr( $tag_name ); ?>
		<?php echo wp_kses_data( $wrapper_attributes ); ?>>
		<?php echo wp_kses_post( $text ); ?>
	</<?php echo esc_attr( $tag_name ); ?>>
<?php endif; ?>
