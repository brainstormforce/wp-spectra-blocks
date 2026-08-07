<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildLabel
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get the 'showLabels' context from the parent countdown block.
// This determines whether labels (like "Days", "Hours") should be shown.
// Defaults to true if not provided.
$show_label = $block->context['spectra/countdown/showLabels'] ?? true;

// Get the label text from block attributes. Defaults to an empty string if not set.
$text = $attributes['text'] ?? '';
// `"0"` is falsy and `false` is not a string; normalise once so the strict
// compare below is always against a known string.
$text = is_scalar( $text ) ? (string) $text : '';

// Return early without rendering anything if labels are disabled
// or if no label text is provided.
// Strict compare on the text: empty('0')/!'0' are true in PHP, so a label of
// "0" was dropped entirely.
if ( ! $show_label || '' === $text ) {
	return '';
}

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config );

// return the view.
return 'file:./view.php';
