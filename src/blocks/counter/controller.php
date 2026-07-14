<?php
/**
 * Controller for rendering the counter block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Counter
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// Set the attributes with fallback if required.
$anchor = $attributes['anchor'] ?? '';

// Get counter-specific attributes.
$counter_style             = $attributes['counterStyle'] ?? 'simple';
$start_number              = $attributes['startNumber'] ?? '';
$end_number                = $attributes['endNumber'] ?? '';
$total_number              = $attributes['totalNumber'] ?? '';
$animation_duration        = $attributes['animationDuration'] ?? 2000;
$prefix                    = $attributes['prefix'] ?? '';
$suffix                    = $attributes['suffix'] ?? '';
$thousand_separator        = $attributes['thousandSeparator'] ?? '';
$decimal_places            = $attributes['decimalPlaces'] ?? 0;
$progress_color            = $attributes['progressColor'] ?? '';
$progress_background_color = $attributes['progressBackgroundColor'] ?? '';
$progress_size             = $attributes['progressSize'] ?? '';
$progress_stroke_width     = $attributes['progressStrokeWidth'] ?? '';
$bar_height                = $attributes['barHeight'] ?? '';
$bar_border_radius         = isset( $attributes['barBorderRadius'] ) ? $attributes['barBorderRadius'] . 'px' : null;
$prefix_color              = $attributes['prefixColor'] ?? '';
$suffix_color              = $attributes['suffixColor'] ?? '';
$prefix_right_margin       = $attributes['prefixRightMargin'] ?? '';
$suffix_left_margin        = $attributes['suffixLeftMargin'] ?? '';

// Extract WordPress core colors to use in Spectra system (like content block).
$wp_text_color       = $attributes['style']['color']['text'] ?? '';
$wp_background_color = $attributes['style']['color']['background'] ?? '';

// Apply defaults only when values are used (following icon block pattern).
// No default conversion here - defaults applied in usage.

// Style and class configurations.
$config = array(
	array(
		'key'   => 'textColor',
		'value' => $attributes['textColor'] ?? $wp_text_color,
	),
	array(
		'key'   => 'backgroundColor',
		'value' => $attributes['backgroundColor'] ?? $wp_background_color,
	),
	array( 'key' => 'backgroundGradient' ),
);

// Add progress-specific CSS variables for circular and bar styles.
if ( 'circular' === $counter_style || 'bar' === $counter_style ) {
	$config[] = array(
		'key'        => 'progressColor',
		'css_var'    => '--spectra-counter-progress-color',
		'class_name' => 'spectra-counter-progress-color',
		'value'      => $progress_color ? $progress_color : '#007cba',
	);
	$config[] = array(
		'key'        => 'progressBackgroundColor',
		'css_var'    => '--spectra-counter-progress-bg-color',
		'class_name' => 'spectra-counter-progress-bg-color',
		'value'      => $progress_background_color ? $progress_background_color : '#e0e0e0',
	);

	if ( 'circular' === $counter_style ) {
		$config[] = array(
			'key'        => 'progressSize',
			'css_var'    => '--spectra-counter-progress-size',
			'class_name' => 'spectra-counter-progress-size',
			'value'      => $progress_size ? $progress_size : '300px', // Default applied only when used.
		);
		$config[] = array(
			'key'        => 'progressStrokeWidth',
			'css_var'    => '--spectra-counter-stroke-width',
			'class_name' => 'spectra-counter-stroke-width',
			'value'      => ( $progress_stroke_width ? $progress_stroke_width : 8 ) . 'px', // Default applied only when used.
		);
	}

	if ( 'bar' === $counter_style ) {
		$config[] = array(
			'key'        => 'barHeight',
			'css_var'    => '--spectra-counter-bar-height',
			'class_name' => 'spectra-counter-bar-height',
			'value'      => $bar_height ? $bar_height : '32px', // Default applied only when used.
		);

		// Only add border radius if it's set.
		if ( null !== $bar_border_radius ) {
			$config[] = array(
				'key'        => 'barBorderRadius',
				'css_var'    => '--spectra-counter-bar-border-radius',
				'class_name' => 'spectra-counter-bar-border-radius',
				'value'      => $bar_border_radius,
			);
		}
	}
}

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

// Note: Prefix/suffix margins are handled by the responsive controls system.
// which generates CSS directly for both main counter and bar progress label.

// Custom classes for counter styles.
$custom_classes = array(
	'wp-block-spectra-counter',
	'spectra-counter--' . $counter_style,
);

// Array of element attributes to extend to the wrapper.
// Apply defaults only when used (following icon block pattern).
// Note: data-counter-start, data-counter-end, data-counter-prefix, data-counter-suffix, and data-counter-separator are added directly in view.php to bypass Spectra helper filtering because empty strings or 0 values might be filtered.
$element_attributes = array(
	'data-counter-total'    => (string) ( isset( $attributes['totalNumber'] ) ? $attributes['totalNumber'] : 100 ),
	'data-counter-duration' => $animation_duration,
	'data-counter-decimals' => $decimal_places,
);

// Store these separately to add directly in view.php (bypass Spectra helper as empty strings or value 0 might be filtered).
$counter_start_value     = isset( $attributes['startNumber'] ) ? $attributes['startNumber'] : 0;
$counter_end_value       = isset( $attributes['endNumber'] ) ? $attributes['endNumber'] : 100;
$counter_prefix_value    = array_key_exists( 'prefix', $attributes ) ? $attributes['prefix'] : '';
$counter_suffix_value    = array_key_exists( 'suffix', $attributes ) ? $attributes['suffix'] : '';
$counter_separator_value = $thousand_separator;

// Support reverse counting: allow start > end for decrement animation.
// No validation needed - let users count backwards (e.g., 3000 to 2000).

// Add progress-specific data attributes for circular and bar styles.
if ( 'circular' === $counter_style || 'bar' === $counter_style ) {
	$element_attributes['data-progress-color']    = $progress_color;
	$element_attributes['data-progress-bg-color'] = $progress_background_color;
	$element_attributes['data-progress-size']     = $progress_size;
	if ( 'circular' === $counter_style ) {
		$element_attributes['data-stroke-width'] = $progress_stroke_width;
	}
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $element_attributes, $custom_classes );
// Return the view.
return 'file:./view.php';
