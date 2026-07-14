<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildDay
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get the 'show' attribute to determine whether this child block should be visible.
// Defaults to true if not explicitly set.
$show = $attributes['show'] ?? true;

// Retrieve the 'showDays' context from the parent countdown block.
// Defaults to true if the context is not provided.
$show_days = $block->context['spectra/countdown/showDays'] ?? true;

// Return early and do not render the block if it's set to not show or days are hidden in parent.
if ( ! $show || ! $show_days ) {
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
