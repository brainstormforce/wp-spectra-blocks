<?php
/**
 * Controller for rendering the counter number block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CounterChildNumber
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get context from parent counter block.
$raw_start_number = $block->context['spectra/counter/startNumber'] ?? 0;
$raw_end_number   = $block->context['spectra/counter/endNumber'] ?? 100;

// Validation: If start > end, use default values (0 to 100).
$start_number         = $raw_start_number > $raw_end_number ? 0 : $raw_start_number;
$end_number           = $raw_start_number > $raw_end_number ? 100 : $raw_end_number;
$prefix               = $block->context['spectra/counter/prefix'] ?? '';
$suffix               = $block->context['spectra/counter/suffix'] ?? '';
$thousand_separator   = $block->context['spectra/counter/thousandSeparator'] ?? '';
$decimal_places       = $block->context['spectra/counter/decimalPlaces'] ?? 0;
$context_prefix_color = $block->context['spectra/counter/prefixColor'] ?? '';
$context_suffix_color = $block->context['spectra/counter/suffixColor'] ?? '';

// Format the initial number display.
$formatted_number = number_format( $start_number, $decimal_places, '.', $thousand_separator );

// Additional classes.
$custom_classes = array(
	'wp-block-spectra-counter-child-number',
	'spectra-counter-number',
);

// Get prefix and suffix colors - use child's own colors if set, otherwise fallback to parent's colors from context.
$prefix_color = $attributes['prefixColor'] ?? $context_prefix_color;
$suffix_color = $attributes['suffixColor'] ?? $context_suffix_color;

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
);

// Add prefix/suffix colors if set.
if ( ! empty( $prefix_color ) ) {
	$config[] = array(
		'key'        => 'prefixColor',
		'css_var'    => '--spectra-prefix-color',
		'class_name' => null,
		'value'      => $prefix_color,
	);
}

if ( ! empty( $suffix_color ) ) {
	$config[] = array(
		'key'        => 'suffixColor',
		'css_var'    => '--spectra-suffix-color',
		'class_name' => null,
		'value'      => $suffix_color,
	);
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$config,
	array(),
	$custom_classes
);

// Render the number block.
return 'file:./view.php';
