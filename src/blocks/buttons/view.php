<?php
/**
 * View for rendering the buttons block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Buttons
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

/**
 * Render the buttons container with its child button blocks.
 */
?>
<div <?php echo spectra_blocks_esc_attributes( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
