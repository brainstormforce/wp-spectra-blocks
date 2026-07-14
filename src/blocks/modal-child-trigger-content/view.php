<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildTriggerContent
 */

?>
<<?php echo esc_attr( $tag_name ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	data-wp-context
	data-wp-on--click="spectra/modal::actions.toggle"
	role="button"
	tabindex="0"
>
	<?php echo wp_kses_post( $text ); ?>
</<?php echo esc_attr( $tag_name ); ?>>
