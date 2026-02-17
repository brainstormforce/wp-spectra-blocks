<?php
/**
 * View for rendering the block.
 * 
 * @since 0.0.1
 * 
 * @package Spectra\Blocks\ModalChildPopupContent
 */

use Spectra\Helpers\Renderer;
use Spectra\Helpers\HtmlSanitizer;

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
>
	<?php
		// Render the background video element if needed.
		Renderer::background_video( $background );
		HtmlSanitizer::render( $content );
	?>
</div>
