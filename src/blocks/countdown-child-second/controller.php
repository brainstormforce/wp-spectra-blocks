<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildSecond
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Retrieve the 'show' attribute for this block. Defaults to true if not provided.
$show = $attributes['show'] ?? true;

// Retrieve the 'showSeconds' value from the parent countdown block's context.
// This determines whether the seconds unit should be shown. Defaults to true.
$show_seconds = $block->context['spectra/countdown/showSeconds'] ?? true;

// If the block is not set to show, or seconds are disabled in the parent context,
// return early to skip rendering.
if ( ! $show || ! $show_seconds ) {
	return '';
}

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

// Render the tabs block.
return 'file:./view.php';
