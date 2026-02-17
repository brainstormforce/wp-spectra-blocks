<?php
/**
 * Controller for rendering the Before After Slider block.
 *
 * @since x.x.x
 * @package Spectra\Blocks\BeforeAfterSlider
 */

use Spectra\Helpers\BlockAttributes;

// Extract attributes with fallbacks.
$before_image_url = $attributes['beforeImageUrl'] ?? '';
$before_image_alt = $attributes['beforeImageAlt'] ?? '';
$after_image_url  = $attributes['afterImageUrl'] ?? '';
$after_image_alt  = $attributes['afterImageAlt'] ?? '';
$before_label     = $attributes['beforeLabel'] ?? 'Before';
$after_label      = $attributes['afterLabel'] ?? 'After';
$orientation      = $attributes['orientation'] ?? 'horizontal';
$initial_offset   = $attributes['initialOffset'] ?? 50;
$move_on_hover       = $attributes['moveOnHover'] ?? false;
$show_labels         = $attributes['showLabels'] ?? 'hover';
$overlay_color       = $attributes['overlayColor'] ?? '';
$before_overlay_color = $attributes['beforeOverlayColor'] ?? '';
$after_overlay_color  = $attributes['afterOverlayColor'] ?? '';
$handle_color        = $attributes['handleColor'] ?? '';
$handle_thickness = isset( $attributes['handleThickness'] ) ? $attributes['handleThickness'] . 'px' : '';
$handle_circle    = isset( $attributes['handleCircleSize'] ) ? $attributes['handleCircleSize'] . 'px' : '';
$label_color      = $attributes['labelColor'] ?? '';
$label_bg_color   = $attributes['labelBackgroundColor'] ?? '';

// Config array MUST mirror JS render.js config exactly.
$config = array();

if ( ! empty( $overlay_color ) ) {
	$config[] = array(
		'key'     => 'overlayColor',
		'css_var' => '--spectra-ba-overlay-color',
		'value'   => $overlay_color,
	);
}

if ( ! empty( $before_overlay_color ) ) {
	$config[] = array(
		'key'     => 'beforeOverlayColor',
		'css_var' => '--spectra-ba-before-overlay-color',
		'value'   => $before_overlay_color,
	);
}

if ( ! empty( $after_overlay_color ) ) {
	$config[] = array(
		'key'     => 'afterOverlayColor',
		'css_var' => '--spectra-ba-after-overlay-color',
		'value'   => $after_overlay_color,
	);
}

if ( ! empty( $handle_color ) ) {
	$config[] = array(
		'key'     => 'handleColor',
		'css_var' => '--spectra-ba-handle-color',
		'value'   => $handle_color,
	);
}

if ( ! empty( $handle_thickness ) ) {
	$config[] = array(
		'key'     => 'handleThickness',
		'css_var' => '--spectra-ba-handle-thickness',
		'value'   => $handle_thickness,
	);
}

if ( ! empty( $handle_circle ) ) {
	$config[] = array(
		'key'     => 'handleCircleSize',
		'css_var' => '--spectra-ba-handle-circle-size',
		'value'   => $handle_circle,
	);
}

if ( ! empty( $label_color ) ) {
	$config[] = array(
		'key'     => 'labelColor',
		'css_var' => '--spectra-ba-label-color',
		'value'   => $label_color,
	);
}

if ( ! empty( $label_bg_color ) ) {
	$config[] = array(
		'key'     => 'labelBackgroundColor',
		'css_var' => '--spectra-ba-label-bg-color',
		'value'   => $label_bg_color,
	);
}

// Element attributes (data attributes for frontend JS).
$element_attributes = array(
	'data-orientation'   => $orientation,
	'data-offset'        => (string) $initial_offset,
	'data-move-on-hover' => $move_on_hover ? 'true' : 'false',
);

// Custom classes.
$is_horizontal = 'horizontal' === $orientation;
$custom_classes = array(
	'wp-block-spectra-before-after-slider',
	$is_horizontal ? 'spectra-ba-slider--horizontal' : 'spectra-ba-slider--vertical',
	'spectra-ba-slider--labels-' . $show_labels,
);

// Get wrapper attributes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$config,
	$element_attributes,
	$custom_classes
);

// Return the view template.
return 'file:./view.php';
