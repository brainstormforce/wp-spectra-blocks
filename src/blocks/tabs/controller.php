<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Tabs
 */

use SpectraBlocks\Helpers\BlockAttributes;

// If a variation is not selected or there is no content, abandon this render on the front-end.
if ( empty( $attributes['variationSelected'] ) || empty( trim( $content ) ) ) {
	return;
}

// Make a unique ID for this tab block, so that accessibility inside can be maintained with this key.
$tabs_block_id = wp_unique_id( 'spectra-tabs-' );

// Set the attributes with fallback if required.

$background_color    = $attributes['backgroundColorTertiary'] ?? '';
$background_gradient = $attributes['backgroundGradientTertiary'] ?? '';
$anchor              = $attributes['anchor'] ?? '';


// Set the contexts required for the accordion wrapper.
$tabs_context = array(
	'activeTab' => 0, // Stores the current tab.
	'blockId'   => $tabs_block_id,
);

// Style and class configurations.
$config = array(
	array(
		'key'        => 'backgroundColorTertiary',
		'css_var'    => '--spectra-background-color',
		'class_name' => 'spectra-background-color',
	),
	array(
		'key'        => 'backgroundGradientTertiary',
		'css_var'    => '--spectra-background-gradient',
		'class_name' => 'spectra-background-gradient',
	),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config );

// Render the tabs block.
return 'file:./view.php';
