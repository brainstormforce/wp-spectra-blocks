<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\AccordionChildHeaderIcon
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Set the icon requirements, with fallback from the root accordion, or the default.
$collapsed_icon = $attributes['icon'] ?? $block->context['spectra/accordion/icon'] ?? 'plus';
$expanded_icon  = $attributes['iconSecondary'] ?? $block->context['spectra/accordion/iconSecondary'] ?? 'minus';
$rotation       = ! empty( $attributes['rotation'] ) ? $attributes['rotation'] : ( $block->context['spectra/accordion/rotation'] ?? '' );

// Set the props required for the icon.
$icon_props = array(
	'focusable'   => 'false',
	'aria-hidden' => 'true',
	'style'       => array(
		'transform' => ! empty( $rotation ) ? 'rotate(' . $rotation . 'deg)' : '',
	),
);

// Set the accordion contexts for interactivity.
$accordion_icon_contexts = array(
	'displayType'  => 'flex',
	'styleDisplay' => 'none',
	'styleHide'    => 'flex',
);

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

// Finally, render the icon for the Accordion Header.
return 'file:./view.php';
