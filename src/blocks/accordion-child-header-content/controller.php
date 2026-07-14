<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\AccordionChildHeaderContent
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config );

// Add the text if it exists, else make the placeholder as the text.
$text = ! empty( $attributes['text'] ) ? $attributes['text'] : __( 'Accordion Title', 'spectra-blocks' );

// Get the tagName attribute, defaulting to 'span'.
$tag_name = $attributes['tagName'] ?? 'span';

// Check if parent header element is button - if so, force span for valid HTML.
$parent_header_element = $block->context['spectra/accordion-child-header/headerElement'] ?? 'button';
if ( 'button' === $parent_header_element ) {
	$tag_name = 'span';
}

// return the view.
return 'file:./view.php';
