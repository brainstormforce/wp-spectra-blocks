<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\AccordionChildItem
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Make a unique ID for this accordion item.
$accordion_item_id = wp_unique_id( 'spectra-accordion-item-' );

// Set the attributes with fallback if required.
$anchor                    = $attributes['anchor'] ?? '';
$text_color                = $attributes['textColor'] ?? $block->context['spectra/accordion/textColor'] ?? '';
$text_color_hover          = $attributes['textColorHover'] ?? $block->context['spectra/accordion/textColorHover'] ?? '';
$background_color          = $attributes['backgroundColor'] ?? $block->context['spectra/accordion/backgroundColor'] ?? '';
$background_color_hover    = $attributes['backgroundColorHover'] ?? $block->context['spectra/accordion/backgroundColorHover'] ?? '';
$background_gradient       = $attributes['backgroundGradient'] ?? $block->context['spectra/accordion/backgroundGradient'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? $block->context['spectra/accordion/backgroundGradientHover'] ?? '';

// Check if the accordion has an available details block or not.
$is_disabled = false === strpos( $content, 'wp-block-spectra-accordion-child-details' );

// Determine if this item should be open by default.
$open_by_default = ! $is_disabled && ! empty( $attributes['openByDefault'] );

// Add the contexts required for the accordion item interactivity.
$accordion_item_contexts = array(
	'item'           => $accordion_item_id,
	'isDisabled'     => $is_disabled,
	'openByDefault'  => $open_by_default,
	'userToggled'    => false,
	'headerId'       => $accordion_item_id . '-header',
	'detailsId'      => $accordion_item_id . '-details',
	'isExpanded'     => $open_by_default,
	'detailsDisplay' => $open_by_default ? '' : 'none',
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

// Add 'is-active' class for items that are open by default (SSR active styling).
$custom_classes = $open_by_default ? array( 'is-active' ) : array();

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $custom_classes );

// Return the view.
return 'file:./view.php';
