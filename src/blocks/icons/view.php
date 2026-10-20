<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Icons
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

?>

<div <?php echo spectra_blocks_esc_attributes( $wrapper_attributes ); ?>>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
