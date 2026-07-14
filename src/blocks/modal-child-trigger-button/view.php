<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildTriggerButton
 */

use SpectraBlocks\Helpers\Renderer;

?>

<div
<?php
	echo wp_kses_data( $wrapper_attributes );
?>
	data-wp-on--click="spectra/modal::actions.toggle"
	role="button"
	tabindex="0"
<?php if ( ! empty( $aria_label ) ) : ?>
	aria-label="<?php echo esc_attr( $aria_label ); ?>"
<?php endif; ?>
>
<?php
if ( isset( $icon ) && 'before' === $icon_position ) {
	Renderer::svg_html( $icon, $flip_for_rtl, $icon_props );
}

if ( $show_text ) {
	echo '<div class="spectra-button__link">' . wp_kses_post( $text ) . '</div>';
}

if ( isset( $icon ) && 'after' === $icon_position ) {
	Renderer::svg_html( $icon, $flip_for_rtl, $icon_props );
}
?>
</div>
