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
	<?php echo spectra_blocks_esc_attributes( $wrapper_attributes ); ?>
>
	<?php
		// Render the background video element if needed.
		Renderer::background_video( $background, Renderer::video_on_every_band( $attributes ) );
		HtmlSanitizer::render( $content );
	?>
</div>
