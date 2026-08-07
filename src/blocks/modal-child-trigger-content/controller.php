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
// Strict compare: `empty('0')` is true, so "0" was replaced by the placeholder.
$text = $attributes['text'] ?? '';
// `"0"` is falsy in PHP and `false` is not a string; normalise once so every
// test below compares against a known string.
$text = is_scalar( $text ) ? (string) $text : '';
$text = '' !== $text ? $text : __( 'Get started by writing something!', 'spectra-blocks' );

// The label must never contain an anchor: view.php always wraps it in an
// element with role="button" that toggles the modal, and a link inside it
// would hijack the click. Strip <a> tags (keeping inner text and all other
// formatting) via a kses allowlist, which also neutralizes malformed anchors
// that a regex would miss. Editor-side counterpart: removeAnchorTag() in @spectra-helpers.
if ( '' !== $text ) {
	$allowed_label_tags = wp_kses_allowed_html( 'post' );
	unset( $allowed_label_tags['a'] );
	$text = wp_kses( $text, $allowed_label_tags );
}

// return the view.
return 'file:./view.php';
