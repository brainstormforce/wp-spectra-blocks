<?php
/**
 * View for rendering the counter child wrapper block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CounterChildWrapper
 */

use SpectraBlocks\Helpers\HtmlSanitizer;
?>

<div <?php echo spectra_blocks_esc_attributes( $wrapper_attributes ); ?>>
	<?php
	HtmlSanitizer::render( $content );
	?>
</div>

