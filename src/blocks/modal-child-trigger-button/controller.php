<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildTriggerButton
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// The main attributes that need to exist.
$text = $attributes['text'] ?? '';
$icon = $attributes['icon'] ?? null;

// If the main attributes do not exist, abandon ship.
if ( ! $text && ! isset( $icon ) ) {
	return;
}

// Ensure attributes exist.
$anchor        = $attributes['anchor'] ?? '';
$show_text     = $attributes['showText'] ?? true;
$icon_position = $attributes['iconPosition'] ?? 'after';
$size          = $attributes['size'] ?? '16px';
$flip_for_rtl  = $attributes['flipForRTL'] ?? false;
$modal_trigger = $attributes['modalTrigger'] ?? ( $block->context['spectra/modal/modalTrigger'] ?? '' );

// Aria label for accessibility - prioritize user-defined ariaLabel, fallback to text content.
$user_aria_label = $attributes['ariaLabel'] ?? '';
$aria_label      = ! empty( $user_aria_label ) ? $user_aria_label : ( ! empty( $text ) ? wp_strip_all_tags( $text ) : '' );

// Icon colors.
$icon_color       = $attributes['iconColor'] ?? '';
$icon_color_hover = $attributes['iconColorHover'] ?? '';
$text_color_hover = $attributes['textColorHover'] ?? '';

// Define base classes.
$icon_classes = array(
	'spectra-button__icon',
	"spectra-button__icon-position-$icon_position",
	$icon_color ? 'spectra-icon-color' : '',
	( $icon_color_hover || $text_color_hover ) ? 'spectra-icon-color-hover' : '',
);

// Check if the icon is a custom SVG (array format or raw SVG string).
$is_custom_svg = ( is_array( $icon ) && isset( $icon['library'] ) && 'svg' === $icon['library'] )
	|| ( is_string( $icon ) && strpos( $icon, '<svg' ) !== false );

// Add the default specific icon props.
$icon_style = array();

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
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
	array(
		'key'        => 'iconColor',
		'css_var'    => '--spectra-icon-color',
		'class_name' => null,
	),
	array(
		'key'        => 'iconColorHover',
		'css_var'    => '--spectra-icon-color-hover',
		'class_name' => null,
	),
);

// Base classes.
$is_hidden = 'button' !== $modal_trigger;

$custom_classes = array(
	$is_hidden ? 'is-hidden' : '',
	'wp-block-button',
	'wp-block-button__link wp-element-button',
	'modal-trigger-element',
);

// Inline style fallback to guarantee hiding regardless of CSS specificity.
$custom_styles = $is_hidden ? array( 'display' => 'none' ) : array();

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array( 'id' => $anchor ), $custom_classes, $custom_styles );

// return the view.
return 'file:./view.php';
