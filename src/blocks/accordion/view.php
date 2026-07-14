<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Accordion
 */

use SpectraBlocks\Helpers\HtmlSanitizer;
use SpectraBlocks\Helpers\BlockAttributes;

// Set the contexts required for the accordion wrapper.
$accordion_contexts = array(
	'activeItem'    => '', // Stores the last opened child item.
	'exclusiveMode' => false, // When true, only one item can be open at a time.
);

// Get the block wrapper attributes using custom function that properly handles anchor attribute.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	array(), // No style configs needed.
	array(), // No additional wrapper config.
	array(), // No custom classes.
	array()  // No custom styles.
);

?>
<div
	<?php echo wp_kses_data( $wrapper_attributes ); ?>
	data-wp-interactive="spectra/accordion"
	<?php echo wp_kses_data( wp_interactivity_data_wp_context( $accordion_contexts, 'spectra/accordion' ) ); ?>
>
	<?php HtmlSanitizer::render( $content ); ?>
</div>
