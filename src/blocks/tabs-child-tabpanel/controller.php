<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\TabsChildTabpanel
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Set the attributes with fallback if required.
$current_tab               = $attributes['currentTab'] ?? 0;
$style_context             = $block->context['spectra/tabs/styleColorText'] ?? array();
$style_color_text          = $style_context['color']['text'] ?? '';
$text_color                = $attributes['textColor'] ?? $block->context['spectra/tabs/textColorSecondary'] ?? $style_color_text;
$text_color_hover          = $attributes['textColorHover'] ?? $block->context['spectra/tabs/textColorHoverSecondary'] ?? '';
$background_color          = $attributes['backgroundColor'] ?? $block->context['spectra/tabs/backgroundColorSecondary'] ?? '';
$background_color_hover    = $attributes['backgroundColorHover'] ?? $block->context['spectra/tabs/backgroundColorHoverSecondary'] ?? '';
$background_gradient       = $attributes['backgroundGradient'] ?? $block->context['spectra/tabs/backgroundGradientSecondary'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? $block->context['spectra/tabs/backgroundGradientHoverSecondary'] ?? '';

// Add the contexts required for the tabpanel's interactivity.
$tabpanel_contexts = array(
	'currentTab' => $current_tab,
	'isActive'   => ( 0 === $current_tab ),
);

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
