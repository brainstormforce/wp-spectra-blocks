<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\AccordionChildDetails
 */

use SpectraBlocks\Helpers\HtmlSanitizer;

// If there's no innerblock, abandon ship.
if ( empty( $content ) ) {
	return;
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = get_block_wrapper_attributes();

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	data-wp-bind--id="spectra/accordion::context.detailsId"
	data-wp-bind--aria-labelledby="spectra/accordion::context.headerId"
	data-wp-style--display="spectra/accordion::context.detailsDisplay"
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
