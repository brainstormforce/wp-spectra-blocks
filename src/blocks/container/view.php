<?php
/**
 * View for rendering the block.
 * 
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Button
 */

use Spectra\Helpers\Renderer;
use Spectra\Helpers\HtmlSanitizer;

?>
<<?php echo esc_attr( $html_tag ); ?>
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	<?php echo wp_kses_data( $link_attributes ); ?>
>
	<?php 
		// Render the background video element if needed.
		Renderer::background_video( $background );
		HtmlSanitizer::render( $content );
	?>
</<?php echo esc_attr( $html_tag ); ?>>
