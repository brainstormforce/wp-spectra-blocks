<?php
/**
 * Controller for rendering the block.
 * 
 * @since 3.0.0
 *
 * @package Spectra\Blocks\AccordionChildHeaderContent
 */


defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
use Spectra\Helpers\BlockAttributes;

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

// Add the text if it exists, else make the placeholder as the text.
$text = ! empty( $attributes['text'] ) ? $attributes['text'] : __( 'Accordion Title', 'spectra-blocks' );

// Get the tagName attribute, defaulting to 'span', and validate against allowlist.
$allowed_tag_names = array( 'span', 'div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
$raw_tag_name      = $attributes['tagName'] ?? 'span';
$tag_name          = in_array( $raw_tag_name, $allowed_tag_names, true ) ? $raw_tag_name : 'span';

// Check if parent header element is button - if so, force span for valid HTML.
$parent_header_element = $block->context['spectra/accordion-child-header/headerElement'] ?? 'button';
if ( 'button' === $parent_header_element ) {
	$tag_name = 'span';
}

// return the view.
return 'file:./view.php';

