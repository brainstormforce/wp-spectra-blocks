<?php
/**
 * Controller for rendering the block.
 * 
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Content
 */

use Spectra\Helpers\BlockAttributes;

// Get the text content from attributes with empty string fallback.
$text = $attributes['text'] ?? '';

// Early return if no text content is provided.
if ( empty( $text ) ) {
	return '';
}

$valid_tag_names = array( 'p', 'span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
$tag_name        = ( ! empty( $attributes['tagName'] ) && in_array( $attributes['tagName'], $valid_tag_names, true ) ) ? $attributes['tagName'] : 'p';

$anchor        = $attributes['anchor'] ?? '';
$drop_cap      = $attributes['dropCap'] ?? false;
$align         = $attributes['style']['typography']['textAlign'] ?? '';
$is_root_block = $attributes['isRootBlock'] ?? true;


// Determine if we need a span wrapper.
$needs_span_wrapper = 'span' === $tag_name && $is_root_block;

// Check if the drop cap is disabled.
$has_drop_cap_disabled = in_array( $align, array( 'center', 'right' ), true ) || 'span' === $tag_name;
$drop_cap_class        = ( ! $has_drop_cap_disabled && $drop_cap ) ? 'has-drop-cap' : '';

// Check for various color settings.
$has_link_color       = ! empty( $attributes['style']['elements']['link']['color']['text'] ?? '' );
$has_background_color = ! empty( $attributes['backgroundColor'] ?? '' );
$has_text_color       = ! empty( $attributes['textColor'] ?? '' );

// Get link hover color. Note: core bug prevents :focus styles from applying when using Tab key.
$link_hover_color = $attributes['style']['elements']['link'][':hover']['color']['text'] ?? '';

// Generate inline CSS style for the link hover color.
if ( $link_hover_color ) {
	// Generate inline CSS style for the hover color.
	$styles           = wp_style_engine_get_styles(
		array(
			'color' => array( 'text' => $link_hover_color ),
		),
		array( 'context' => 'block-supports' )
	);
	$link_hover_color = $styles['declarations']['color'] ?? $link_hover_color;
}

// Style and class configurations.
$config = array( 
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),

	// Link hover color as a custom variable for fixing core bug with focus.
	array(
		'key'        => 'linkHoverColor',
		'css_var'    => '--spectra-link-hover-color',
		'class_name' => 'spectra-link-hover-color',
		'value'      => $link_hover_color,
	),

);

// Additional classes.
$additional_classes = array(
	$drop_cap_class,
	$has_background_color ? 'has-background' : '',
	$has_text_color ? 'has-text-color' : '',
	$has_link_color ? 'has-link-color' : '',
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $additional_classes );

// return the view.
return 'file:./view.php';

