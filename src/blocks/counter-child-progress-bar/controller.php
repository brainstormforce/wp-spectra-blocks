<?php
/**
 * Controller for rendering the counter progress bar block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CounterChildProgressBar
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get context from parent counter block.
$counter_style                     = $block->context['spectra/counter/counterStyle'] ?? 'simple';
$raw_start_number                  = $block->context['spectra/counter/startNumber'] ?? 0;
$raw_end_number                    = $block->context['spectra/counter/endNumber'] ?? 100;
$total_number                      = $block->context['spectra/counter/totalNumber'] ?? 100;
$prefix                            = $block->context['spectra/counter/prefix'] ?? '';
$suffix                            = $block->context['spectra/counter/suffix'] ?? '';
$thousand_separator                = $block->context['spectra/counter/thousandSeparator'] ?? '';
$decimal_places                    = $block->context['spectra/counter/decimalPlaces'] ?? 0;
$progress_size                     = $block->context['spectra/counter/progressSize'] ?? '300px';
$progress_stroke_width             = $block->context['spectra/counter/progressStrokeWidth'] ?? 8;
$context_progress_color            = $block->context['spectra/counter/progressColor'] ?? '';
$context_progress_background_color = $block->context['spectra/counter/progressBackgroundColor'] ?? '';
$context_prefix_color              = $block->context['spectra/counter/prefixColor'] ?? '';
$context_suffix_color              = $block->context['spectra/counter/suffixColor'] ?? '';

// Get child block's own attributes.
$child_progress_color            = $attributes['progressColor'] ?? '';
$child_progress_background_color = $attributes['progressBackgroundColor'] ?? '';
$child_prefix_color              = $attributes['prefixColor'] ?? '';
$child_suffix_color              = $attributes['suffixColor'] ?? '';
$text_color                      = $attributes['textColor'] ?? '';
$bar_height                      = $attributes['barHeight'] ?? '32px';
$bar_border_radius               = $attributes['barBorderRadius'] ?? 4;

// Use context colors from parent if available, otherwise use child's own colors.
$progress_color            = $context_progress_color ? $context_progress_color : ( $child_progress_color ? $child_progress_color : '#4A90E2' );
$progress_background_color = $context_progress_background_color ? $context_progress_background_color : ( $child_progress_background_color ? $child_progress_background_color : '#E6E6E6' );
// Prefix/suffix: Child takes priority over parent context (reversed priority).
$prefix_color = $child_prefix_color ? $child_prefix_color : $context_prefix_color;
$suffix_color = $child_suffix_color ? $child_suffix_color : $context_suffix_color;

// Support reverse counting: allow start > end for decrement animation.
// Ensure values are numbers to prevent NaN issues.
$start_number = is_numeric( $raw_start_number ) ? floatval( $raw_start_number ) : 0;
$end_number   = is_numeric( $raw_end_number ) ? floatval( $raw_end_number ) : 100;

// Format the initial number display.
$formatted_number = number_format( $start_number, $decimal_places, '.', $thousand_separator );

// Additional classes.
$custom_classes = array(
	'wp-block-spectra-counter-child-progress-bar',
	'spectra-counter-progress-bar--' . $counter_style,
);

// Style and class configurations.
$config = array(
	array(
		'key'        => 'progressColor',
		'css_var'    => '--spectra-counter-progress-color',
		'class_name' => null,
		'value'      => $progress_color,
	),
	array(
		'key'        => 'progressBackgroundColor',
		'css_var'    => '--spectra-counter-progress-bg-color',
		'class_name' => null,
		'value'      => $progress_background_color,
	),
	array(
		'key'        => 'barHeight',
		'css_var'    => '--spectra-counter-bar-height',
		'class_name' => null,
		'value'      => $bar_height,
	),
	array(
		'key'        => 'barBorderRadius',
		'css_var'    => '--spectra-counter-bar-border-radius',
		'class_name' => null,
		'value'      => is_numeric( $bar_border_radius ) ? $bar_border_radius . 'px' : $bar_border_radius,
	),
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

// Render the progress bar block.
return 'file:./view.php';
