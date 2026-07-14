<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildPopupContent
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// Set the attributes with fallback if required.
$text_color                = $attributes['textColor'] ?? '';
$text_color_hover          = $attributes['textColorHover'] ?? '';
$background_color          = $attributes['backgroundColor'] ?? '';
$background_color_hover    = $attributes['backgroundColorHover'] ?? '';
$background_gradient       = $attributes['backgroundGradient'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? '';
$background                = $attributes['background'] ?? array();
$appear_effect             = $block->context['spectra/modal-child-popup/appearEffect'] ?? '';

// Modal position related attributes from context.
$modal_position      = $block->context['spectra/modal/modalPosition'] ?? '';
$close_icon_position = $block->context['spectra/modal/closeIconPosition'] ?? '';
$h_position          = $block->context['spectra/modal/hPos'] ?? '';
$v_position          = $block->context['spectra/modal/vPos'] ?? '';

// Check if any breakpoint has video background or image background.
$has_video_background = false;
$has_responsive_image = false;
$responsive_controls  = $attributes['responsiveControls'] ?? array();
// Check if any device has contentHeight set to 'auto'.
$has_auto_height = false;
foreach ( array( 'lg', 'md', 'sm' ) as $device ) {
	if ( isset( $responsive_controls[ $device ]['contentHeight'] ) && 'auto' === $responsive_controls[ $device ]['contentHeight'] ) {
		$has_auto_height = true;
		break;
	}
}
$video_background       = null;
$has_responsive_overlay = false;

$dim_ratio = ( isset( $attributes['dimRatio'] ) ? ( $attributes['dimRatio'] / 100 ) : 100 );

foreach ( array( 'lg', 'md', 'sm' ) as $device ) {
	if ( isset( $responsive_controls[ $device ]['background']['type'] ) ) {
		if ( 'video' === $responsive_controls[ $device ]['background']['type'] ) {
			$has_video_background = true;
			// Store the first video found.
			if ( null === $video_background ) {
				$video_background = $responsive_controls[ $device ]['background'];
			}
		} elseif ( 'image' === $responsive_controls[ $device ]['background']['type'] ) {
			$has_responsive_image = true;
		}
	}
}

// If we found a video background in any responsive breakpoint, ensure we render the video element.
// Even if desktop has no background, we need the video element for responsive switching.
if ( $has_video_background && null !== $video_background ) {
	// If background is not set or is 'none', use the video background to ensure video element renders.
	if ( ! $background || ( isset( $background['type'] ) && 'none' === $background['type'] ) ) {
		$background = $video_background;
	}
}

// Background specific values required for conditional classes.
$background_type = $background['type'] ?? '';

// Get background styles for inline use.
$background_styles = Core::get_background_image_styles( $background, $background_gradient, $background_gradient_hover );

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
	array(
		'key'        => 'dimRatio',
		'css_var'    => '--spectra-overlay-opacity',
		'class_name' => null,
		'value'      => $dim_ratio,
	),
);

// Add position CSS variables if wrapper is not present (window positioning).
$is_window_positioning = in_array( $close_icon_position, array( 'window-top-left', 'window-top-right' ), true );
if ( $is_window_positioning && 'custom' === $modal_position ) {
	if ( $h_position ) {
		$config[] = array(
			'key'        => 'hPos',
			'value'      => $h_position,
			'css_var'    => '--spectra-modal-h-position',
			'class_name' => null,
		);
	}

	if ( $v_position ) {
		$config[] = array(
			'key'        => 'vPos',
			'value'      => $v_position,
			'css_var'    => '--spectra-modal-v-position',
			'class_name' => null,
		);
	}
}

// Add responsive video data as data attribute for JavaScript.
$responsive_video_data = array();
if ( ! empty( $responsive_controls ) ) {
	foreach ( array( 'lg', 'md', 'sm' ) as $device ) {
		if ( isset( $responsive_controls[ $device ]['background'], $responsive_controls[ $device ]['background']['type'] ) &&
		'video' === $responsive_controls[ $device ]['background']['type'] &&
		! empty( $responsive_controls[ $device ]['background']['media']['url'] ) ) {
			$responsive_video_data[ $device ] = $responsive_controls[ $device ]['background']['media']['url'];
		}
	}
}

// Custom inline styles for dimensions.
$custom_styles = $background_styles;

// Custom classes.
$custom_classes = array(
	// Video background class is required for proper positioning.
	( 'video' === $background_type || $has_video_background ) ? 'spectra-background-video' : '',
	// These classes are used for overflow handling with border-radius.
	( $has_video_background || 'video' === $background_type ) ? 'has-video-background' : '',
	( 'image' === $background_type || $has_responsive_image ) ? 'has-image-background' : '',
	// Add overlay class when background has overlay enabled.
	( $has_responsive_overlay ? 'spectra-background-overlay' : '' ),
	// Add background image class.
	( 'image' === $background_type || $has_responsive_image ) ? 'spectra-background-image' : '',
	// Add classes for custom modal position when wrapper is not present (window positioning).
	( $h_position && $is_window_positioning && 'custom' === $modal_position ) ? 'horizontal-position' : '',
	( $v_position && $is_window_positioning && 'custom' === $modal_position ) ? 'vertical-position' : '',
	'spectra-overlay-color',
	// Add class when content height is set to auto on any device.
	$has_auto_height ? 'spectra-height-auto' : '',
);

$additional_attributes = array();
if ( ! empty( $responsive_video_data ) ) {
	$additional_attributes['data-responsive-videos'] = wp_json_encode( $responsive_video_data );
}

// Get the block wrapper attributes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $additional_attributes, $custom_classes, $custom_styles );

// Return the view.
return 'file:./view.php';
