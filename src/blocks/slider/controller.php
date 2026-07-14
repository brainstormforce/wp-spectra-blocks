<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Slider
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// Generate unique ID.
$slider_id = 'spectra-slider-' . wp_unique_id();

// Check if Spectra Pro is active (same pattern as modal).
$is_pro_activated = ( is_plugin_active( 'spectra-pro/spectra-pro.php' ) || is_plugin_active( 'spectra-blocks-pro/spectra-blocks-pro.php' ) );

/**
 * Get effective slides per view with Pro fallback logic.
 *
 * @since 3.0.0
 * @param mixed $slides_per_view The original slides per view value.
 * @return mixed The effective slides per view value.
 */
$get_effective_slides_per_view = function ( $slides_per_view ) use ( $is_pro_activated ) {
	// If Pro is activated, return as-is (supports decimal values).
	if ( $is_pro_activated ) {
		return $slides_per_view;
	}

	// For free version, only allow '1' or numeric value of 1.
	if ( ! is_numeric( $slides_per_view ) ) {
		// Non-numeric values (including 'auto') fallback to 1 in free version.
		return 1;
	}

	$numeric_value = (float) $slides_per_view;

	// If it's any Pro value (>1, including decimals) and Pro is not active, fallback to 1.
	if ( $numeric_value > 1 ) {
		// All Pro values (>1) fallback to 1 in free version.
		return 1;
	}

	// Backward compatibility: existing value of 1 remains 1.
	return $slides_per_view;
};

/**
 * Get responsive attribute value with proper fallback.
 *
 * This helper function retrieves responsive attribute values following the fallback hierarchy:
 * 1. Check responsive controls for the requested device with fallback (sm → md → lg).
 * 2. Check the main attribute for backward compatibility.
 * 3. Return default values if nothing is found.
 *
 * @since 3.0.0.1
 *
 * @param string $device    Device type (sm, md, lg).
 * @param string $attribute Attribute name to retrieve.
 * @return mixed The attribute value with proper type casting.
 */
$spectra_slider_get_responsive_attr = function ( $device, $attribute ) use ( &$responsive_controls, &$attributes, $get_effective_slides_per_view ) {
	$fallback_order = array(
		'sm' => array( 'sm', 'md', 'lg' ),
		'md' => array( 'md', 'lg' ),
		'lg' => array( 'lg' ),
	);

	foreach ( $fallback_order[ $device ] as $fallback_device ) {
		if ( isset( $responsive_controls[ $fallback_device ][ $attribute ] ) ) {
			$value = $responsive_controls[ $fallback_device ][ $attribute ];

			// Handle special cases.
			if ( 'slidesPerView' === $attribute ) {
				// Convert 'auto' to 1 for backward compatibility (auto is no longer supported).
				$effective_value = 'auto' === $value ? 1 : (float) $value;
				return $get_effective_slides_per_view( $effective_value );
			}

			// Default integer casting for numeric values.
			return is_numeric( $value ) ? (int) $value : $value;
		}
	}

	// Final fallback to main attribute if exists.
	if ( isset( $attributes[ $attribute ] ) ) {
		$value = $attributes[ $attribute ];
		if ( 'slidesPerView' === $attribute ) {
			// Convert 'auto' to 1 for backward compatibility (auto is no longer supported).
			$effective_value = 'auto' === $value ? 1 : (float) $value;
			return $get_effective_slides_per_view( $effective_value );
		}
		return is_numeric( $value ) ? (int) $value : $value;
	}

	// Default values if nothing is set.
	$defaults = array(
		'slidesPerView' => 1,
		'spaceBetween'  => 30,
	);

	return $defaults[ $attribute ] ?? 1;
};

// Get block attributes with defaults.
$loop          = $attributes['loop'] ?? false;
$slider_height = $attributes['sliderHeight'] ?? '';

$text_color       = $attributes['textColor'] ?? 'inherit';
$navigation       = $attributes['navigation'] ?? true;
$display_arrows   = $attributes['displayArrows'] ?? true;
$pagination       = $attributes['pagination'] ?? true;
$autoplay         = $attributes['autoplay'] ?? false;
$autoplay_speed   = $attributes['autoplaySpeed'] ?? 3000;
$breakpoints      = $attributes['breakpoints'] ?? 'none';
$pause_on_hover   = $attributes['autoplayPauseOnHover'] ?? false;
$on_interaction   = $attributes['autoplayPauseOnInteraction'] ?? false;
$allow_touch_move = $attributes['allowTouchMove'] ?? true;
$overflow         = $attributes['overflow'] ?? '';

// Get background attributes.
$background           = $attributes['background'] ?? null;
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

// Check if any breakpoint has video background, image background or overlay.
$has_video_background   = false;
$has_responsive_image   = false;
$responsive_controls    = $attributes['responsiveControls'] ?? array();
$video_background       = null;
$has_responsive_overlay = false;

// The responsive-controls extension strips root-level 'background' from attrs and moves it
// to responsiveControls.lg. Fall back to lg so downstream logic (background type, classes) still works.
if ( null === $background ) {
	$background = $responsive_controls['lg']['background'] ?? null;
}

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
$has_border_radius    = ! empty( $attributes['style']['border']['radius'] );

$dim_ratio = ( isset( $attributes['dimRatio'] ) ? ( $attributes['dimRatio'] / 100 ) : 1 );

// Get custom navigation and pagination colors.
$navigation_size      = $attributes['navigationSize'] ?? '40px';
$navigation_icon_size = $attributes['navigationIconSize'] ?? '20px';

// Get background styles from Core helper.
$background_styles = Core::get_background_image_styles( $background, $final_background_gradient );

// Add the additional classes.
// Only add classes that are actually used in frontend styles.
$additional_classes = array(
	! empty( $attributes['className'] ) ? $attributes['className'] : '',
	// Video background class is required for proper positioning (from common.scss).
	( 'video' === $background_type || $has_video_background ) ? 'spectra-background-video' : '',
	// These classes are used for overflow handling with border-radius.
	$has_video_background ? 'has-video-background' : '',
	( $has_image_background || $has_responsive_image ) ? 'has-image-background' : '',
	// Add overlay class when overlay is used.
	( $has_responsive_overlay ? 'spectra-background-overlay' : '' ),
	// Add background image class.
	( 'image' === $background_type || $has_responsive_image ) ? 'spectra-background-image' : '',
	'spectra-overlay-color',
);

// Style and class configurations.
$config = array(
	array(
		'key'        => 'textColor',
		'css_var'    => '--spectra-text-color',
		'class_name' => null,
		'value'      => $text_color,
	),
	array(
		'key'        => 'hasSliderHeight',
		'css_var'    => null,
		'class_name' => 'spectra-has-slider-height',
		'value'      => ! empty( $attributes['sliderHeight'] ) ||
						! empty( $responsive_controls['lg']['sliderHeight'] ) ||
						! empty( $responsive_controls['md']['sliderHeight'] ) ||
						! empty( $responsive_controls['sm']['sliderHeight'] ),
	),
	array(
		'key'        => 'navigationColor',
		'class_name' => null,
	),
	array(
		'key'        => 'navigationBackgroundColor',
		'class_name' => null,
	),
	array(
		'key'        => 'arrowColor',
		'class_name' => null,
	),
	array(
		'key'        => 'arrowColorHover',
		'class_name' => null,
	),
	array(
		'key'        => 'arrowBackgroundColor',
		'class_name' => null,
	),
	array(
		'key'        => 'arrowBackgroundColorHover',
		'class_name' => null,
	),
	array(
		'key'        => 'paginationColor',
		'class_name' => null,
	),
	array(
		'key'        => 'paginationColorHover',
		'class_name' => null,
	),
	array(
		'key'        => 'paginationColorActive',
		'class_name' => null,
	),
	array(
		'key'        => 'paginationColorActiveHover',
		'class_name' => null,
	),
	array(
		'key'        => 'paginationTopMargin',
		'css_var'    => '--spectra-pagination-margin-top',
		'class_name' => null,
	),
	array(
		'key' => 'backgroundColor',
	),
	array(
		'key'   => 'backgroundGradient',
		'value' => $final_background_gradient,
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


// Prepare responsive values for frontend initialization.
$responsive_values = array(
	'sm' => array(
		'slidesPerView' => $spectra_slider_get_responsive_attr( 'sm', 'slidesPerView' ),
		'spaceBetween'  => $spectra_slider_get_responsive_attr( 'sm', 'spaceBetween' ),
	),
	'md' => array(
		'slidesPerView' => $spectra_slider_get_responsive_attr( 'md', 'slidesPerView' ),
		'spaceBetween'  => $spectra_slider_get_responsive_attr( 'md', 'spaceBetween' ),
	),
	'lg' => array(
		'slidesPerView' => $spectra_slider_get_responsive_attr( 'lg', 'slidesPerView' ),
		'spaceBetween'  => $spectra_slider_get_responsive_attr( 'lg', 'spaceBetween' ),
	),
);

// Prepare swiper parameters.
// Use desktop values as base to minimize flickering for majority of users.
// Even though Swiper uses min-width breakpoints, we start with desktop values
// to reduce FOUC (Flash of Unstyled Content) for desktop users.
$swiper_params = array(
	'slidesPerView'  => $responsive_values['lg']['slidesPerView'],
	'spaceBetween'   => $responsive_values['lg']['spaceBetween'],
	'loop'           => $loop,
	'navigation'     => array(
		'enabled' => $navigation,
		'nextEl'  => '.swiper-button-next',
		'prevEl'  => '.swiper-button-prev',
	),
	'pagination'     => array(
		'enabled'   => $pagination,
		'el'        => '.swiper-pagination',
		'clickable' => true,
	),
	'autoplay'       => $autoplay ? array(
		'delay'                => $autoplay_speed,
		'disableOnInteraction' => $on_interaction,
		'pauseOnMouseEnter'    => $pause_on_hover,
	) : false,
	'allowTouchMove' => $allow_touch_move,
	// Always include breakpoints for responsive behavior.
	// Since we're starting with desktop values to reduce flickering,
	// we need to include all breakpoints including mobile.
	'breakpoints'    => array(
		// Mobile breakpoint (0-767px) - explicitly set mobile values.
		0    => array(
			'slidesPerView' => $responsive_values['sm']['slidesPerView'],
			'spaceBetween'  => $responsive_values['sm']['spaceBetween'],
		),
		// Tablet breakpoint (768-1023px).
		768  => array(
			'slidesPerView' => $responsive_values['md']['slidesPerView'],
			'spaceBetween'  => $responsive_values['md']['spaceBetween'],
		),
		// Desktop breakpoint (1024px+).
		1024 => array(
			'slidesPerView' => $responsive_values['lg']['slidesPerView'],
			'spaceBetween'  => $responsive_values['lg']['spaceBetween'],
		),
	),
);

// Allow extensions to modify the Swiper parameters and modules.
$swiper_params  = apply_filters( 'spectra_slider_params', $swiper_params, $attributes );
$swiper_modules = apply_filters( 'spectra_slider_modules', array(), $attributes );

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
	'id'           => $slider_id,
	'data-swiper'  => wp_json_encode( $swiper_params ),
	'data-modules' => wp_json_encode( $swiper_modules ),
);

if ( ! empty( $responsive_video_data ) ) {
	$wrapper_config['data-responsive-videos'] = wp_json_encode( $responsive_video_data );
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $wrapper_config, $additional_classes, $background_styles );

return 'file:./view.php';
