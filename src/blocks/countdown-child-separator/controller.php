<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CountdownChildSeparator
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get the 'showSeparator' context from the parent countdown block.
// This determines whether separators (like colons) should be displayed. Defaults to true.
$show_separator = $block->context['spectra/countdown/showSeparator'] ?? true;

// Get the 'show' attribute for this individual separator block. Defaults to true.
$show = $attributes['show'] ?? true;

// Determine the separator text. Priority:
// 1. Block attribute 'text'.
// 2. Context value 'separatorType' from parent.
// 3. Default fallback to ':'.
$text = $attributes['text'] ?? $block->context['spectra/countdown/separatorType'] ?? ':';
// `"0"` is falsy in PHP and `false` is not a string; normalise once so every
// test below compares against a known string.
$text = is_scalar( $text ) ? (string) $text : '';

// If separator display is disabled globally or locally, or if text is empty, skip rendering.
// Strict compare: `! '0'` is true in PHP, so a separator of "0" was dropped.
if ( ! $show_separator || ! $show || '' === $text ) {
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
