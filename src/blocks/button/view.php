<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Button
 */

use SpectraBlocks\Helpers\Renderer;

?>
<a 
<?php if ( $has_link ) : ?>
	href="<?php echo esc_url( $attributes['linkURL'] ); ?>"
	<?php if ( ! empty( $target ) ) : ?>
		target="<?php echo esc_attr( $target ); ?>"
	<?php endif; ?>
	<?php if ( ! empty( $aria ) ) : ?>
		aria-label="<?php echo esc_attr( $aria ); ?>"
	<?php endif; ?>
	<?php if ( ! empty( $rel ) ) : ?>
		rel="<?php echo esc_attr( $rel ); ?>"
	<?php endif; ?>
<?php endif; ?>
<?php if ( $show_icon_on_hover && ! empty( $hover_icon_aria_label ) ) : ?>
	data-hover-aria-label="<?php echo esc_attr( $hover_icon_aria_label ); ?>"
<?php endif; ?>
<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
<?php
// Hover icon on top.
if ( $show_icon_on_hover && ! empty( $hover_icon ) && 'top' === $hover_icon_position ) {
	Renderer::svg_html( $hover_icon, $hover_icon_flip_for_rtl, $hover_icon_props );
}

// Regular icon before text.
if ( isset( $icon ) && 'before' === $icon_position ) {
	Renderer::svg_html( $icon, $flip_for_rtl, $icon_props );
}

// Hover icon on left.
if ( $show_icon_on_hover && ! empty( $hover_icon ) && 'left' === $hover_icon_position ) {
	Renderer::svg_html( $hover_icon, $hover_icon_flip_for_rtl, $hover_icon_props );
}

// Button text.
if ( $show_text ) {
	echo '<div class="spectra-button__link">' . wp_kses_post( $text ) . '</div>';
}

// Hover icon on right.
if ( $show_icon_on_hover && ! empty( $hover_icon ) && 'right' === $hover_icon_position ) {
	Renderer::svg_html( $hover_icon, $hover_icon_flip_for_rtl, $hover_icon_props );
}

// Regular icon after text.
if ( isset( $icon ) && 'after' === $icon_position ) {
	Renderer::svg_html( $icon, $flip_for_rtl, $icon_props );
}

// Hover icon on bottom.
if ( $show_icon_on_hover && ! empty( $hover_icon ) && 'bottom' === $hover_icon_position ) {
	Renderer::svg_html( $hover_icon, $hover_icon_flip_for_rtl, $hover_icon_props );
}
?>
</a>
