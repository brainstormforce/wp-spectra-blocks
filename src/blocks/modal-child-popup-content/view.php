<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildPopupContent
 */

use SpectraBlocks\Helpers\Renderer;
use SpectraBlocks\Helpers\HtmlSanitizer;

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
