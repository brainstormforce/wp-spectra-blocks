<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Buttons
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

$background_color          = $attributes['backgroundColor'] ?? '';
$background_color_hover    = $attributes['backgroundColorHover'] ?? '';
$background_gradient       = $attributes['backgroundGradient'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? '';

// Base classes.
$custom_classes = array( 'wp-block-button', 'spectra-buttons' );

// Style and class configurations.
$config = array(
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
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $custom_classes );

// Return the view.
return 'file:./view.php';
