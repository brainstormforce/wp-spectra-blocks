<?php
/**
 * View for rendering the block.
 * 
 * @since 0.0.1
 *
 * @package Spectra\Blocks\CountdownChildLabel
 */

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php echo wp_kses_post( $text ); ?>
</div>
