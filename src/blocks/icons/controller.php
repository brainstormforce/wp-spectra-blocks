<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Icons
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get attributes.
$background_color          = $attributes['backgroundColor'] ?? '';
$background_color_hover    = $attributes['backgroundColorHover'] ?? '';
$background_gradient       = $attributes['backgroundGradient'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? '';

// Style and class configurations.
$config = array(
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config );

// Return the view.
return 'file:./view.php';
