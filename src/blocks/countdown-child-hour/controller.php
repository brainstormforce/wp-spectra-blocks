<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildHour
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get the 'show' attribute from the block's attributes, default to true if not set.
$show = $attributes['show'] ?? true;

// Retrieve the 'showHours' value from the parent countdown block's context, defaulting to true.
$show_hours = $block->context['spectra/countdown/showHours'] ?? true;

// If the current block is set to not show, or if the parent has disabled showing hours,
// return early without rendering anything.
if ( ! $show || ! $show_hours ) {
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
