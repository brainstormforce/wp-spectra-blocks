<?php
/**
 * View file for rendering the Google Map block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\GoogleMap
 */

?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<embed
		class="spectra-google-map__iframe"
		title="<?php echo esc_attr__( 'Google Map for ', 'spectra-blocks' ) . esc_attr( $address ); ?>"
		src="<?php echo esc_url( $map_url ); ?>"
		width="100%"
		height="100%"
		style="border: 0;"
		allowfullscreen=""
		loading="lazy"
		referrerpolicy="no-referrer-when-downgrade"
	></embed>
</div>
