<?php
/**
 * View for rendering the counter number block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CounterChildNumber
 */

?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<span class="spectra-counter-number">
	<?php
	if ( isset( $prefix ) && '' !== $prefix ) :
		?>
		<span class="spectra-counter-prefix"><?php echo esc_html( $prefix ); ?></span><?php endif; ?><span class="spectra-counter-value"><?php echo esc_html( $formatted_number ); ?></span>
		<?php
		if ( isset( $suffix ) && '' !== $suffix ) :
			?>
		<span class="spectra-counter-suffix"><?php echo esc_html( $suffix ); ?></span><?php endif; ?></span>
</div>
