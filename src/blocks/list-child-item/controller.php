<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ListChildItem
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Set the attributes with fallback if required.
$anchor = $attributes['anchor'] ?? '';
$index  = $attributes['index'] ?? 1;
$level  = $attributes['level'] ?? 0;

// Get context from parent blocks with proper fallbacks.
$list_type = $block->context['spectra/list/listType'] ?? 'unordered';

// Get text colors with fallbacks from parent context.
$text_color       = $attributes['textColor'] ?? $block->context['spectra/list/textColor'] ?? '';
$text_color_hover = $attributes['textColorHover'] ?? $block->context['spectra/list/textColorHover'] ?? '';

// Extract WordPress core colors from style attribute.
$wp_text_color       = $attributes['style']['color']['text'] ?? '';
$wp_background_color = $attributes['style']['color']['background'] ?? '';

// Use WordPress core colors as fallback for Spectra colors.
$final_text_color       = $text_color ? $text_color : $wp_text_color;
$final_background_color = $attributes['backgroundColor'] ?? $wp_background_color;

// Additional classes.
$additional_classes = array(
	'spectra-list-item',
	'spectra-list-item-' . $list_type,
	'spectra-list-item-level-' . $level,
);

// Style and class configurations.
$config = array(
	array(
		'key'   => 'textColor',
		'value' => $final_text_color,
	),
	array(
		'key'   => 'textColorHover',
		'value' => $text_color_hover,
	),
	array(
		'key'   => 'backgroundColor',
		'value' => $final_background_color,
	),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Get the block wrapper attributes using WordPress core's proper handling.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$config,
	array(),
	$additional_classes
);

// Set context for child blocks.
$block->context['spectra/list-child-item/index'] = $index;
$block->context['spectra/list-child-item/level'] = $level;

// Return the view.
return 'file:./view.php';
