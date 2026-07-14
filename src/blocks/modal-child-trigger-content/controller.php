<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildTriggerContent
 */

use SpectraBlocks\Helpers\BlockAttributes;

$valid_tag_names = array( 'p', 'span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
$tag_name        = ( ! empty( $attributes['tagName'] ) && in_array( $attributes['tagName'], $valid_tag_names, true ) ) ? $attributes['tagName'] : 'p';

$anchor        = $attributes['anchor'] ?? '';
$drop_cap      = $attributes['dropCap'] ?? false;
$align         = $attributes['style']['typography']['textAlign'] ?? '';
$modal_trigger = ! empty( $attributes['modalTrigger'] ) ? $attributes['modalTrigger'] : ( $block->context['spectra/modal/modalTrigger'] ?? '' );

// Check if the drop cap is disabled.
$has_drop_cap_disabled = in_array( $align, array( 'center', 'right' ), true ) || 'span' === $tag_name;
$drop_cap_class        = ( ! $has_drop_cap_disabled && $drop_cap ) ? 'has-drop-cap' : '';

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Custom classes.
$is_hidden = 'text' !== $modal_trigger;

$custom_classes = array(
	$is_hidden ? 'is-hidden' : '',
	$drop_cap_class,
	'modal-trigger-element',
);

// Inline style fallback to guarantee hiding regardless of CSS specificity.
$custom_styles = $is_hidden ? array( 'display' => 'none' ) : array();

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array( 'id' => $anchor ), $custom_classes, $custom_styles );

// Add the text if it exists, else make the placeholder as the text.
$text = ! empty( $attributes['text'] ) ? $attributes['text'] : __( 'Get started by writing something!', 'spectra-blocks' );

// return the view.
return 'file:./view.php';
