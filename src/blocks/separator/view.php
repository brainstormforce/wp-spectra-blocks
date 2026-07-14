<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Separator
 */

?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<div class="spectra-separator-line"
	<?php
	if ( ! empty( $separator_line_style ) ) :
		?>
		style="<?php echo $separator_line_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- value is hardcoded from $custom_svg_urls constant, not user input. ?>"<?php endif; ?>></div>
</div>
