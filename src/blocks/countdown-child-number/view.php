<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildNumber
 */

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php
	// Hardcoded '00' as a placeholder for dynamic countdown values (replaced via frontend JS). No translation needed since the final values are numeric and handled separately.
	echo '00';
	?>
</div>
