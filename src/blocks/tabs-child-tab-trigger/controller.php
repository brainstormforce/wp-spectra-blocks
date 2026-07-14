<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\TabsChildTabTrigger
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// Ensure attributes exist.
$current_tab = $attributes['currentTab'] ?? 0;
$anchor      = $attributes['anchor'] ?? '';
$overflow    = $attributes['overflow'] ?? 'visible';
$height      = $attributes['height'] ?? 'auto';
$width       = $attributes['width'] ?? '';
$min_width   = $attributes['minWidth'] ?? '';
$min_height  = $attributes['minHeight'] ?? '';
$max_width   = $attributes['maxWidth'] ?? '';
$max_height  = $attributes['maxHeight'] ?? '';

$background_gradient       = $attributes['backgroundGradient'] ?? $block->context['spectra/tabs/backgroundGradient'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? $block->context['spectra/tabs/backgroundGradientHover'] ?? '';

// Define text and background colors.
$text_color                       = $block->context['spectra/tabs/textColor'] ?? '';
$text_color_hover                 = $attributes['textColorHover'] ?? $block->context['spectra/tabs/textColorHover'] ?? '';
$text_color_active                = $attributes['textColorActive'] ?? $block->context['spectra/tabs/textColorActive'] ?? '';
$text_color_active_hover          = $attributes['textColorActiveHover'] ?? $block->context['spectra/tabs/textColorActiveHover'] ?? '';
$background_color                 = $attributes['backgroundColor'] ?? $block->context['spectra/tabs/backgroundColor'] ?? '';
$background_color_hover           = $attributes['backgroundColorHover'] ?? $block->context['spectra/tabs/backgroundColorHover'] ?? '';
$background_color_active          = $attributes['backgroundColorActive'] ?? $block->context['spectra/tabs/backgroundColorActive'] ?? '';
$background_color_active_hover    = $attributes['backgroundColorActiveHover'] ?? $block->context['spectra/tabs/backgroundColorActiveHover'] ?? '';
$background_gradient_active       = $attributes['backgroundGradientActive'] ?? $block->context['spectra/tabs/backgroundGradientActive'] ?? '';
$background_gradient_active_hover = $attributes['backgroundGradientActiveHover'] ?? $block->context['spectra/tabs/backgroundGradientActiveHover'] ?? '';
$border_color_hover               = $attributes['borderColorHover'] ?? $block->context['spectra/tabs/borderColorHover'] ?? '';
$border_color_active              = $attributes['borderColorActive'] ?? $block->context['spectra/tabs/borderColorActive'] ?? '';
$border_color_active_hover        = $attributes['borderColorActiveHover'] ?? $block->context['spectra/tabs/borderColorActiveHover'] ?? '';

// Get the current tab from parent context.
$currently_active_tab = $block->context['spectra/tabs/currentTab'] ?? 0;
$is_active            = $current_tab === $currently_active_tab;

// Add the contexts required for the tab trigger's interactivity.
$tab_contexts = array(
	'currentTab' => $current_tab,
	'isActive'   => ( 0 === $current_tab ),
);

// Style and class configurations.
$config = array(
	array(
		'key'        => 'width',
		'css_var'    => 'width',
		'class_name' => null,
		'value'      => $width,
	),
	array(
		'key'        => 'height',
		'css_var'    => 'height',
		'class_name' => null,
		'value'      => $height,
	),
	array(
		'key'        => 'minWidth',
		'css_var'    => 'min-width',
		'class_name' => null,
		'value'      => $min_width,
	),
	array(
		'key'        => 'minHeight',
		'css_var'    => 'min-height',
		'class_name' => null,
		'value'      => $min_height,
	),
	array(
		'key'        => 'maxWidth',
		'css_var'    => 'max-width',
		'class_name' => null,
		'value'      => $max_width,
	),
	array(
		'key'        => 'maxHeight',
		'css_var'    => 'max-height',
		'class_name' => null,
		'value'      => $max_height,
	),
	array(
		'key'        => 'overflow',
		'css_var'    => 'overflow',
		'class_name' => null,
		'value'      => $overflow,
	),
	array(
		'key'   => 'textColor',
		'value' => $text_color,
	),
	array(
		'key'   => 'textColorHover',
		'value' => $text_color_hover,
	),
	array(
		'key'   => 'textColorActive',
		'value' => $text_color_active,
	),
	array(
		'key'   => 'textColorActiveHover',
		'value' => $text_color_active_hover,
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
		'key'   => 'backgroundColorActive',
		'value' => $background_color_active,
	),
	array(
		'key'   => 'backgroundColorActiveHover',
		'value' => $background_color_active_hover,
	),
	array(
		'key'   => 'backgroundGradient',
		'value' => $background_gradient,
	),
	array(
		'key'   => 'backgroundGradientHover',
		'value' => $background_gradient_hover,
	),
	array(
		'key'   => 'backgroundGradientActive',
		'value' => $background_gradient_active,
	),
	array(
		'key'   => 'backgroundGradientActiveHover',
		'value' => $background_gradient_active_hover,
	),
	array(
		'key'   => 'borderColorHover',
		'value' => $border_color_hover,
	),
	array(
		'key'   => 'borderColorActive',
		'value' => $border_color_active,
	),
	array(
		'key'   => 'borderColorActiveHover',
		'value' => $border_color_active_hover,
	),
);

// Additional inline styles.
$additional_styles = array(
	'position' => 'relative',
);

// Additional inline styles are now the only inline styles.
$inline_styles = $additional_styles;

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array( 'id' => $anchor ), array(), $inline_styles );

// Add ARIA attributes for accessibility.
$aria_attributes = array(
	'role'          => 'tab',
	'aria-selected' => $is_active ? 'true' : 'false',
	'tabindex'      => $is_active ? '0' : '-1',
);

// Return the view.
return 'file:./view.php';
