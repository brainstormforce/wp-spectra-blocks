<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildLabel
 */

?>
<div
	<?php echo spectra_blocks_esc_attributes( $wrapper_attributes ); ?>
>
	<?php echo wp_kses_post( $text ); ?>
</div>
