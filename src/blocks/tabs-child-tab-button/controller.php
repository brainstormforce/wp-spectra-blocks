<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\TabsChildTabButton
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// Ensure attributes exist.
$current_tab   = $attributes['currentTab'] ?? 0;
$text          = ! empty( $attributes['text'] ) ? $attributes['text'] : ( $attributes['placeholder'] ?? __( 'Tab', 'spectra-blocks' ) );
$show_text     = $attributes['showText'] ?? true;
$icon          = $attributes['icon'] ?? $block->context['spectra/tabs/icon'] ?? '';
$icon_position = $attributes['iconPosition'] ?? $block->context['spectra/tabs/iconPosition'] ?? 'after';
$flip_for_rtl  = $attributes['flipForRTL'] ?? false;
$aria_label    = ( ! $show_text && ! empty( $text ) ) ? $text : ''; // Aria label is only required when the text is not shown.

// Define text and background colors.
$style_context                    = $block->context['spectra/tabs/styleColorText'] ?? array();
$style_color_text                 = $style_context['color']['text'] ?? '';
$text_color                       = $attributes['textColor'] ?? $block->context['spectra/tabs/textColor'] ?? $style_color_text;
$text_color_hover                 = $attributes['textColorHover'] ?? $block->context['spectra/tabs/textColorHover'] ?? '';
$text_color_active                = $attributes['textColorActive'] ?? $block->context['spectra/tabs/textColorActive'] ?? '';
$text_color_active_hover          = $attributes['textColorActiveHover'] ?? $block->context['spectra/tabs/textColorActiveHover'] ?? '';
$icon_color                       = $attributes['iconColor'] ?? $block->context['spectra/tabs/iconColor'] ?? '';
$icon_color_hover                 = $attributes['iconColorHover'] ?? $block->context['spectra/tabs/iconColorHover'] ?? '';
$icon_color_active                = $attributes['iconColorActive'] ?? $block->context['spectra/tabs/iconColorActive'] ?? '';
$icon_color_active_hover          = $attributes['iconColorActiveHover'] ?? $block->context['spectra/tabs/iconColorActiveHover'] ?? '';
$background_color                 = $attributes['backgroundColor'] ?? $block->context['spectra/tabs/backgroundColor'] ?? '';
$background_color_hover           = $attributes['backgroundColorHover'] ?? $block->context['spectra/tabs/backgroundColorHover'] ?? '';
$background_color_active          = $attributes['backgroundColorActive'] ?? $block->context['spectra/tabs/backgroundColorActive'] ?? '';
$background_color_active_hover    = $attributes['backgroundColorActiveHover'] ?? $block->context['spectra/tabs/backgroundColorActiveHover'] ?? '';
$background_gradient              = $attributes['backgroundGradient'] ?? $block->context['spectra/tabs/backgroundGradient'] ?? '';
$background_gradient_hover        = $attributes['backgroundGradientHover'] ?? $block->context['spectra/tabs/backgroundGradientHover'] ?? '';
$background_gradient_active       = $attributes['backgroundGradientActive'] ?? $block->context['spectra/tabs/backgroundGradientActive'] ?? '';
$background_gradient_active_hover = $attributes['backgroundGradientActiveHover'] ?? $block->context['spectra/tabs/backgroundGradientActiveHover'] ?? '';
$border_color_hover               = $attributes['borderColorHover'] ?? $block->context['spectra/tabs/borderColorHover'] ?? '';
$border_color_active              = $attributes['borderColorActive'] ?? $block->context['spectra/tabs/borderColorActive'] ?? '';
$border_color_active_hover        = $attributes['borderColorActiveHover'] ?? $block->context['spectra/tabs/borderColorActiveHover'] ?? '';

// Add the contexts required for the tab button's interactivity.
$tab_button_contexts = array(
	'currentTab' => $current_tab,
	'isActive'   => ( 0 === $current_tab ),
);

// Define base classes.
$icon_classes = array(
	'spectra-button__icon',
	"spectra-button__icon-position-$icon_position",
	$icon_color ? 'spectra-icon-color' : '',
	( $icon_color_hover || $text_color_hover ) ? 'spectra-icon-color-hover' : '',
	$icon_color_active ? 'spectra-icon-color-active' : '',
	( $icon_color_active_hover || $text_color_active_hover ) ? 'spectra-icon-color-active-hover' : '',
);

// Check if the icon is a custom SVG (array format or raw SVG string).
$is_custom_svg = ( is_array( $icon ) && isset( $icon['library'] ) && 'svg' === $icon['library'] )
	|| ( is_string( $icon ) && strpos( $icon, '<svg' ) !== false );

// Add the default specific icon props.
$icon_style = array(
	'transform' => ! empty( $attributes['rotation'] ) ? 'rotate(' . $attributes['rotation'] . 'deg)' : '',
);

// For custom SVGs, add fill:currentColor inline so they respect color settings.
// FontAwesome icons get fill from CSS (style.scss).
if ( $is_custom_svg ) {
	$icon_style['fill'] = 'currentColor';
}

$icon_props = array(
	'class'     => Core::concatenate_array( $icon_classes ),
	'focusable' => 'false',
	'style'     => $icon_style,
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
		'key'   => 'textColorActive',
		'value' => $text_color_active,
	),
	array(
		'key'   => 'textColorActiveHover',
		'value' => $text_color_active_hover,
	),
	array(
		'key'     => 'iconColor',
		'css_var' => '--spectra-icon-color',
		'value'   => $icon_color,
	),
	array(
		'key'     => 'iconColorHover',
		'css_var' => '--spectra-icon-color-hover',
		'value'   => $icon_color_hover,
	),
	array(
		'key'     => 'iconColorActive',
		'css_var' => '--spectra-icon-color-active',
		'value'   => $icon_color_active,
	),
	array(
		'key'     => 'iconColorActiveHover',
		'css_var' => '--spectra-icon-color-active-hover',
		'value'   => $icon_color_active_hover,
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

// Base classes.
$custom_classes = array( 'wp-block-button', 'wp-block-button__link wp-element-button' );

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array( 'aria-label' => $aria_label ), $custom_classes );

// return the view.
return 'file:./view.php';
