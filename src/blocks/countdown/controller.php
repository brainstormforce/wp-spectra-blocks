<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Countdown
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get the block attributes.
$end_date_time = $attributes['endDateTime'] ?? '';
$timer_type    = $attributes['timerType'] ?? 'date';
$show_days     = $attributes['showDays'] ?? true;
$show_hours    = $attributes['showHours'] ?? true;
$show_minutes  = $attributes['showMinutes'] ?? true;
$show_seconds  = $attributes['showSeconds'] ?? true;

// Nothing to render when all time units are hidden.
if ( ! $show_days && ! $show_hours && ! $show_minutes && ! $show_seconds ) {
	return '';
}

// A DATE timer without an end date-time has nothing to count to — skip it.
// Non-date timer types (e.g. Pro's evergreen) compute their own deadline
// per visitor and legitimately have NO endDateTime; they must still render
// (previously this guard silently emitted NOTHING for evergreen blocks —
// the deadline runtime never got a DOM node to drive). Without Pro active,
// a non-date block renders its static placeholder tiles, which is a visible
// signal rather than a vanished section.
if ( 'date' === $timer_type && empty( $end_date_time ) ) {
	return '';
}

// Extract additional block attributes with default fallback values.
$anchor         = $attributes['anchor'] ?? '';
$show_labels    = $attributes['showLabels'] ?? true;
$aria_live_type = $attributes['ariaLiveType'] ?? 'off';

// Set default singular and plural labels for each time unit, with translation support.
$day_label     = $attributes['dayLabel'] ?? __( 'Day', 'spectra-blocks' );
$days_label    = $attributes['daysLabel'] ?? __( 'Days', 'spectra-blocks' );
$hour_label    = $attributes['hourLabel'] ?? __( 'Hour', 'spectra-blocks' );
$hours_label   = $attributes['hoursLabel'] ?? __( 'Hours', 'spectra-blocks' );
$minute_label  = $attributes['minuteLabel'] ?? __( 'Minute', 'spectra-blocks' );
$minutes_label = $attributes['minutesLabel'] ?? __( 'Minutes', 'spectra-blocks' );
$second_label  = $attributes['secondLabel'] ?? __( 'Second', 'spectra-blocks' );
$seconds_label = $attributes['secondsLabel'] ?? __( 'Seconds', 'spectra-blocks' );

// Extract overflow and height attributes.
$overflow = $attributes['overflow'] ?? 'visible';
$height   = $attributes['height'] ?? 'auto';

// Style and class configurations.
$config = array(
	array(
		'key'        => 'overflow',
		'css_var'    => 'overflow',
		'class_name' => null,
		'value'      => $overflow,
	),
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Get layout settings.
$layout_type = $attributes['layout']['type'] ?? 'flex';
$block_gap   = $attributes['style']['spacing']['blockGap'] ?? null;

// Prepare custom classes for flow(default)/constrained layouts when blockGap is not set.
$additional_classes = array();
if ( ( 'default' === $layout_type || 'constrained' === $layout_type ) && is_null( $block_gap ) ) {
	$additional_classes[] = 'countdown-is-layout-flow-constrained';
}

// Prepare ARIA attributes based on ariaLiveType.
$aria_attributes = array();

// Add ARIA label for accessibility context.
// Note: aria-live is added to individual countdown number elements (via context)
// following v2's pattern where each time unit announces independently.
if ( 'off' !== $aria_live_type ) {
	$aria_attributes['aria-label'] = __( 'Countdown timer', 'spectra-blocks' );
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$config,
	$aria_attributes,
	$additional_classes,
);

// Default countdown context to initialize the countdown object in the store.
$default_countdown = array(
	'days'      => '00',
	'hours'     => '00',
	'minutes'   => '00',
	'seconds'   => '00',
	'isExpired' => false,
);

// Set the contexts required for the countdown wrapper.
$countdown_context = array(
	'endDateTime' => $end_date_time,
	'showDays'    => $show_days,
	'showHours'   => $show_hours,
	'showMinutes' => $show_minutes,
	'showSeconds' => $show_seconds,
	'labels'      => array(
		'dayLabel'     => $day_label,
		'daysLabel'    => $days_label,
		'hourLabel'    => $hour_label,
		'hoursLabel'   => $hours_label,
		'minuteLabel'  => $minute_label,
		'minutesLabel' => $minutes_label,
		'secondLabel'  => $second_label,
		'secondsLabel' => $seconds_label,
	),
	'countdown'   => $default_countdown,
);

/**
 * Filter the countdown context.
 *
 * @since 3.0.0
 *
 * @param array $countdown_context The countdown context.
 * @param array $attributes       The block attributes.
 * @return array The modified countdown context.
 */
$countdown_context = apply_filters( 'spectra_countdown_context', $countdown_context, $attributes );

// Render the tabs block.
return 'file:./view.php';
