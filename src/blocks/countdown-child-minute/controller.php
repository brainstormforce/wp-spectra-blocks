<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildMinute
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get the 'show' attribute from the block's attributes; defaults to true.
$show = $attributes['show'] ?? true;

// Get the 'showMinutes' context from the parent countdown block.
// This determines whether the minutes unit should be displayed; defaults to true.
$show_minutes = $block->context['spectra/countdown/showMinutes'] ?? true;

// Return early if the block is not set to show or if the parent has disabled minute display.
if ( ! $show || ! $show_minutes ) {
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
