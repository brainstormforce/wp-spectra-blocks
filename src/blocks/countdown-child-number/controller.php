<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildNumber
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Define the ARIA role for accessibility, indicating this is a timer region.
$label_role = 'timer';

// Get the ariaLiveType from the parent countdown block context.
$aria_live_type = $block->context['spectra/countdown/ariaLiveType'] ?? 'off';

// Style and class configurations.
$config = array(
	array( 'key' => 'numberColor' ),
	array( 'key' => 'numberColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Build ARIA attributes array.
$extra_attributes = array(
	'role' => $label_role,
);

// Add aria-live only if not set to 'off'.
if ( 'off' !== $aria_live_type ) {
	$extra_attributes['aria-live'] = $aria_live_type;
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $extra_attributes );

// return the view.
return 'file:./view.php';
