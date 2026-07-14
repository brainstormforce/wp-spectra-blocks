<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 * @package Spectra\Blocks\SliderChild
 */

use SpectraBlocks\Helpers\HtmlSanitizer;
use SpectraBlocks\Helpers\Renderer;

?>

<div <?php echo wp_kses_data( $wrapper_attributes ); ?>>
	<?php
		// Render the background video element if needed.
		// When any responsive breakpoint uses video, render from that breakpoint's data
		// so the element exists in the DOM for CSS to show/hide per viewport.
		Renderer::background_video( ( $has_video_background && null !== $video_background ) ? $video_background : $background );
	?>
	<div class="slide-content">
		<?php HtmlSanitizer::render( $content ); ?>
	</div>
</div>
