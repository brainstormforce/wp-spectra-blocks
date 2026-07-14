<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildSeparator
 */

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php echo wp_kses_post( $text ); ?>
</div>
