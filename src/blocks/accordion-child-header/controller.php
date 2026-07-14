<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\AccordionChildHeader
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Set the attributes with fallback if required - either from the parent item, or the parent accordion block.
$allowed_header_elements   = array( 'button', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p' );
$raw_header_element        = $attributes['headerElement'] ?? 'button';
$header_element            = in_array( $raw_header_element, $allowed_header_elements, true ) ? $raw_header_element : 'button';
$text_color                = $attributes['textColor'] ?? $block->context['spectra/accordion/item/textColorSecondary'] ?? $block->context['spectra/accordion/textColorSecondary'] ?? '';
$text_color_hover          = $attributes['textColorHover'] ?? $block->context['spectra/accordion/item/textColorHoverSecondary'] ?? $block->context['spectra/accordion/textColorHoverSecondary'] ?? '';
$background_color          = $attributes['backgroundColor'] ?? $block->context['spectra/accordion/item/backgroundColorSecondary'] ?? $block->context['spectra/accordion/backgroundColorSecondary'] ?? '';
$background_color_hover    = $attributes['backgroundColorHover'] ?? $block->context['spectra/accordion/item/backgroundColorHoverSecondary'] ?? $block->context['spectra/accordion/backgroundColorHoverSecondary'] ?? '';
$background_gradient       = $attributes['backgroundGradient'] ?? $block->context['spectra/accordion/item/backgroundGradientSecondary'] ?? $block->context['spectra/accordion/backgroundGradientSecondary'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? $block->context['spectra/accordion/item/backgroundGradientHoverSecondary'] ?? $block->context['spectra/accordion/backgroundGradientHoverSecondary'] ?? '';

// Style and class configurations.
$config = array(
	array(
		'key'   => 'textColor',
		'value' => $text_color,
	),
	array(
		'key'   => 'textColorHover',
		'value' => $text_color_hover,
	),
	array(
		'key'   => 'backgroundColor',
		'value' => $background_color,
	),
	array(
		'key'   => 'backgroundColorHover',
		'value' => $background_color_hover,
	),
	array(
		'key'   => 'backgroundGradient',
		'value' => $background_gradient,
	),
	array(
		'key'   => 'backgroundGradientHover',
		'value' => $background_gradient_hover,
	),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config );

// Return the view.
return 'file:./view.php';
