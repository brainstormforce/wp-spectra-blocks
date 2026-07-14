<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\List
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Set the attributes with fallback if required.
$list_type      = $attributes['listType'] ?? 'unordered';
$layout         = $attributes['layout'] ?? 'vertical';
$text_alignment = $attributes['textAlignment'] ?? 'left';
$icon_position  = $attributes['iconPosition'] ?? 'left';
$reversed       = $attributes['reversed'] ?? false;
$start          = $attributes['start'] ?? null;
$list_style     = $attributes['listStyle'] ?? null;
$anchor         = $attributes['anchor'] ?? '';
$icon           = $attributes['icon'] ?? 'circle';
$icon_size      = $attributes['iconSize'] ?? '16px';
$icon_spacing   = $attributes['iconSpacing'] ?? 10;
$flip_for_rtl   = $attributes['flipForRTL'] ?? false;
$rotation       = $attributes['rotation'] ?? null;

// Get text colors for passing to children.
$text_color       = $attributes['textColor'] ?? '';
$text_color_hover = $attributes['textColorHover'] ?? '';


// Count the direct list-child-item blocks to provide total items context.
$total_items = 0;
if ( isset( $block->inner_blocks ) && is_array( $block->inner_blocks ) ) {
	foreach ( $block->inner_blocks as $inner_block ) {
		if ( is_object( $inner_block ) && 'spectra/list-child-item' === $inner_block->name ) {
			++$total_items;
		}
	}
}
$block->context['spectra/list/totalItems'] = $total_items;

// Additional classes for the list.
$additional_classes = array(
	'spectra-list',
	'spectra-list-' . $list_type,
);

// Style and class configurations.
$config = array(
	array(
		'key'   => 'textColor',
		'class' => 'spectra-text-color',
		'value' => $text_color,
	),
	array(
		'key'   => 'textColorHover',
		'class' => 'spectra-text-color-hover',
		'value' => $text_color_hover,
	),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Prepare additional styles for ordered lists.
$additional_styles = array();
if ( 'ordered' === $list_type && ! is_null( $list_style ) ) {
	$additional_styles['list-style-type'] = esc_attr( $list_style );
}

// Prepare additional attributes for ordered lists.
$additional_attributes = array();
if ( 'ordered' === $list_type ) {
	if ( $reversed ) {
		$additional_attributes['reversed'] = '';
	}
	if ( ! is_null( $start ) ) {
		$additional_attributes['start'] = esc_attr( $start );
	}
	$additional_attributes['data-list-style'] = esc_attr( $list_style ?? 'decimal' );
	$additional_attributes['data-start']      = esc_attr( $start ?? '1' );
	$additional_attributes['data-reversed']   = esc_attr( $reversed ? 'true' : 'false' );
}

// Get the block wrapper attributes using WordPress core's proper handling.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$config,
	$additional_attributes,
	$additional_classes,
	$additional_styles
);

// For ordered lists, add counter-reset class.
if ( 'ordered' === $list_type ) {
	$wrapper_attributes = preg_replace( '/class=\"/', 'class="spectra-list-counter-reset ', $wrapper_attributes, 1 );
}

// Return the view.
return 'file:./view.php';
