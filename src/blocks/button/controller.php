<?php
/**
 * View for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Button
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// The main attributes that need to exist.
$text = $attributes['text'] ?? '';
$icon = $attributes['icon'] ?? '';

// Bail out if both text and icon are empty.
if ( empty( $text ) && empty( $icon ) ) {
	return;
}

// Ensure attributes exist.
$anchor        = $attributes['anchor'] ?? '';
$show_text     = $attributes['showText'] ?? true;
$icon_position = $attributes['iconPosition'] ?? 'after';
$flip_for_rtl  = $attributes['flipForRTL'] ?? false;
$size          = $attributes['size'] ?? null; // Icon size attribute.

// Icon colors.
$icon_color       = $attributes['iconColor'] ?? '';
$icon_color_hover = $attributes['iconColorHover'] ?? '';
$text_color_hover = $attributes['textColorHover'] ?? '';

// Hover icon attributes.
$show_icon_on_hover      = $attributes['showIconOnHover'] ?? false;
$hover_icon              = $attributes['hoverIcon'] ?? '';
$hover_icon_position     = $attributes['hoverIconPosition'] ?? 'right';
$hover_icon_rotation     = $attributes['hoverIconRotation'] ?? 0;
$hover_icon_flip_for_rtl = $attributes['hoverIconFlipForRTL'] ?? false;
$hover_icon_aria_label   = $attributes['hoverIconAriaLabel'] ?? '';


// Convert shadow hover object to CSS string.
$shadow_hover = '';
if ( ! empty( $attributes['shadowHover'] ) ) {
	$shadow = $attributes['shadowHover'];
	// If it's already a string, use it directly (but only if it contains a color).
	if ( is_string( $shadow ) ) {
		$shadow_hover = $shadow;
	} elseif ( is_array( $shadow ) ) {
		$color = $shadow['color'] ?? '';

		// Only set shadow if color is actually provided.
		if ( ! empty( $color ) && trim( $color ) !== '' ) {
			$shadow_hover = sprintf(
				'%dpx %dpx %dpx %dpx %s',
				isset( $shadow['x'] ) ? intval( $shadow['x'] ) : 0,
				isset( $shadow['y'] ) ? intval( $shadow['y'] ) : 4,
				isset( $shadow['blur'] ) ? intval( $shadow['blur'] ) : 8,
				isset( $shadow['spread'] ) ? intval( $shadow['spread'] ) : 0,
				$color
			);
		}
	}
}
$attributes['shadowHover'] = $shadow_hover;

// Convert border hover object to CSS strings - only set the hover color.
$border_hover_config = array();
if ( ! empty( $attributes['borderHover']['color'] ) ) {
	$border_hover = $attributes['borderHover'];
	$hover_color  = $border_hover['color'];

	// Only set the hover color as a CSS variable.
	// Let WordPress core border settings handle the responsive width/style.
	$border_hover_config[] = array(
		'key'        => 'borderHoverColor',
		'css_var'    => '--spectra-border-hover-color',
		'class_name' => 'spectra-border-hover',
		'value'      => $hover_color,
	);
}

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
$icon_style = array(
	'transform' => ! empty( $attributes['rotation'] ) ? 'rotate(' . $attributes['rotation'] . 'deg)' : '',
);

// For custom SVGs, add fill:currentColor inline so they respect color settings.
// FontAwesome icons get fill from CSS (style.scss:79 and 84).
if ( $is_custom_svg ) {
	$icon_style['fill'] = 'currentColor';
}

$icon_props = array(
	'class'     => Core::concatenate_array( $icon_classes ),
	'focusable' => 'false',
	'style'     => $icon_style,
);

// Hover icon classes and props.
$hover_icon_classes = array(
	'spectra-button__hover-icon',
	"spectra-button__hover-icon-position-$hover_icon_position",
	$icon_color ? 'spectra-icon-color' : '',
	( $icon_color_hover || $text_color_hover ) ? 'spectra-icon-color-hover' : '',
);

// Check if the hover icon is a custom SVG.
$is_hover_custom_svg = ( is_array( $hover_icon ) && isset( $hover_icon['library'] ) && 'svg' === $hover_icon['library'] )
	|| ( is_string( $hover_icon ) && strpos( $hover_icon, '<svg' ) !== false );

$hover_icon_style = array(
	'transform' => ! empty( $hover_icon_rotation ) ? 'rotate(' . $hover_icon_rotation . 'deg)' : '',
);

// For custom SVGs, add fill:currentColor inline.
if ( $is_hover_custom_svg ) {
	$hover_icon_style['fill'] = 'currentColor';
}

$hover_icon_props = array(
	'class'     => Core::concatenate_array( $hover_icon_classes ),
	'focusable' => 'false',
	'style'     => $hover_icon_style,
);

// Detect whether WP core `supports.color` values are set. Mirrors the
// content block's pattern (controller.php:52-54): the slug path
// (`attributes.textColor` / `.backgroundColor`) covers palette selections,
// and the nested `attributes.style.color.*` path covers custom hex picks.
// Either form is enough to add the WP core helper class so the global
// stylesheet rule (`.has-background`, `.has-text-color`) wires up.
$has_text_color       = ! empty( $attributes['textColor'] ?? '' )
	|| ! empty( $attributes['style']['color']['text'] ?? '' );
$has_background_color = ! empty( $attributes['backgroundColor'] ?? '' )
	|| ! empty( $attributes['style']['color']['background'] ?? '' )
	|| ! empty( $attributes['gradient'] ?? '' )
	|| ! empty( $attributes['style']['color']['gradient'] ?? '' );

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
	array(
		'key'        => 'shadowHover',
		'css_var'    => '--spectra-shadow-hover',
		'class_name' => 'spectra-shadow-hover',
	),
	array(
		'key'        => 'gap',
		'css_var'    => '--spectra-icon-gap',
		'class_name' => null,
	),
);

// Add border hover configurations to main config.
$config = array_merge( $config, $border_hover_config );

// Base classes.
$custom_classes = array( 'wp-block-button__link wp-element-button' );

// WP core color helper classes — paired with `has-<slug>-color` (palette)
// or with the inline `style="color:#..."` (custom hex) that WP core's
// `get_block_wrapper_attributes()` emits inside BlockAttributes helper.
if ( $has_text_color ) {
	$custom_classes[] = 'has-text-color';
}
if ( $has_background_color ) {
	$custom_classes[] = 'has-background';
}

// Add hover icon class if enabled.
if ( $show_icon_on_hover && ! empty( $hover_icon ) ) {
	$custom_classes[] = 'has-hover-icon';
}

// Add border hover classes if enabled.
if ( ! empty( $attributes['borderHover']['color'] ) ) {
	$custom_classes[] = 'has-border-hover';
	$custom_classes[] = 'spectra-border-hover-override';
}

// Add shadow hover classes if enabled.
if ( ! empty( $attributes['shadowHover'] ) ) {
	$custom_classes[] = 'spectra-shadow-hover-override';
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $custom_classes );

// Default link requirements for the button Block.
$has_link = ! empty( $attributes['linkURL'] );
$target   = '';
$rel      = '';
$aria     = '';

if ( $has_link ) {
	// Set the target, and keep a default rel string.
	$target = $attributes['linkTarget'] ?? '_self';

	// Set default aria-label (normal state) - use text content.
	$aria = $text;

	// Strip HTML tags from aria-label for better accessibility.
	if ( ! empty( $aria ) ) {
		$aria = wp_strip_all_tags( $aria );
	}

	// If the Rel attribute array exists, concatenate the attributes into a single string.
	if ( ! empty( $attributes['linkRel'] ) && is_array( $attributes['linkRel'] ) ) {
		// Note that the attribute is being formatted here.
		$rel = esc_attr( Core::concatenate_array( $attributes['linkRel'] ) );
	}
}

// return the view.
return 'file:./view.php';
