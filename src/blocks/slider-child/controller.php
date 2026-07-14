<?php
/**
 * Controller for rendering the slider child block.
 *
 * @since 3.0.0
 * @package Spectra\Blocks\SliderChild
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// Generate unique ID.
$slider_id = 'spectra-slider-' . wp_unique_id();
// Get colors from context and attributes.
$text_color           = $block->context['spectra/slider/textColor'] ?? '';
$background_color     = $attributes['backgroundColor'] ?? '';
$background_gradient  = $attributes['backgroundGradient'] ?? '';
$enable_adv_gradients = $attributes['enableAdvGradients'] ?? false;
$adv_bg_gradient      = $attributes['advBgGradient'] ?? '';

// Get final background gradient value (advanced or basic).
$final_background_gradient = Core::get_advanced_gradient_value(
	true, // For single gradient blocks, always pass true for enable_adv_bg.
	$adv_bg_gradient,
	$background_gradient,
	$enable_adv_gradients
);

// Get background attributes.
$background = $attributes['background'] ?? null;
$dim_ratio  = ( isset( $attributes['dimRatio'] ) ? ( $attributes['dimRatio'] / 100 ) : 1 );
$overflow   = $attributes['overflow'] ?? '';
$style      = $attributes['style'] ?? array();

// Check if any breakpoint has video background, image background or overlay.
$has_video_background   = false;
$has_responsive_image   = false;
$responsive_controls    = $attributes['responsiveControls'] ?? array();
$video_background       = null;
$has_responsive_overlay = false;

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

$background_type = $background['type'] ?? '';

// Check if container has video background and border radius.
$has_image_background = 'image' === $background_type;
$has_border_radius    = ! empty( $style['border']['radius'] );

// Get background styles from Core helper.
$background_styles = Core::get_background_image_styles( $background, $final_background_gradient );

// Add the additional classes.
// Only add classes that are actually used in frontend styles.
$additional_classes = array(
	'swiper-slide',
	! empty( $attributes['className'] ) ? $attributes['className'] : '',
	// Video background class is required for proper positioning.
	( 'video' === $background_type || $has_video_background ) ? 'spectra-background-video' : '',
	// These classes are used for overflow handling with border-radius.
	$has_video_background ? 'has-video-background' : '',
	( $has_image_background || $has_responsive_image ) ? 'has-image-background' : '',
	// Only add overlay class when an actual background (image/video) warrants it.
	// Empty or 'none' type means solid color — no overlay layer needed.
	( ( $background_color || $final_background_gradient ) && '' !== $background_type && 'none' !== $background_type ) ? 'spectra-background-overlay' : '',
	// Add background image class.
	( 'image' === $background_type || $has_responsive_image ) ? 'spectra-background-image' : '',
	'spectra-overlay-color',
);

// Style and class configurations.
$config = array(
	array(
		'key'   => 'textColor',
		'value' => $text_color,
	),
	array(
		'key'     => 'backgroundColor',
		'css_var' => '--spectra-background-color',
	),
	array(
		'key'        => 'backgroundGradient',
		'css_var'    => '--spectra-background-gradient',
		'value'      => $final_background_gradient,
		'class_name' => null,
	),
	array(
		'key'        => 'dimRatio',
		'css_var'    => '--spectra-overlay-opacity',
		'class_name' => null,
		'value'      => $dim_ratio,
	),
);

// If the Overflow attribute is set, push the overflow based configs to generate styles and classes.
if ( ! empty( $overflow ) ) {
	$config[] = array(
		'key'        => 'overflow',
		'css_var'    => 'overflow',
		'class_name' => null,
		'value'      => $overflow,
	);
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

// Get block wrapper attributes.
$wrapper_config = array(
	'id' => $slider_id,
);
if ( ! empty( $responsive_video_data ) ) {
	$wrapper_config['data-responsive-videos'] = wp_json_encode( $responsive_video_data );
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $wrapper_config, $additional_classes, $background_styles );

return 'file:./view.php';
