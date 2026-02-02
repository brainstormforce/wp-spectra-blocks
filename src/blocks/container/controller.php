<?php
/**
 * View for rendering the block.
 * 
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Container
 */

use Spectra\Helpers\BlockAttributes;
use Spectra\Helpers\Core;
use Spectra\Helpers\Shadow;

// Retrieve block attributes.
$anchor              = $attributes['anchor'] ?? '';
$html_tag            = $attributes['htmlTag'] ?? 'div';
$overflow            = $attributes['overflow'] ?? 'visible';
$height              = $attributes['height'] ?? 'auto';
$dimRatio            = ( isset( $attributes['dimRatio'] ) && is_numeric( $attributes['dimRatio'] ) ? ( $attributes['dimRatio'] / 100 ) : null );
$orientation_reverse = $attributes['orientationReverse'] ?? false;
$layout              = $attributes['layout'] ?? array();

// Get overlay attributes.
$overlay_type       = $attributes['overlayType'] ?? 'none';
$overlay_image      = $attributes['overlayImage'] ?? array();
$overlay_position   = $attributes['overlayPosition'] ?? null;
$overlay_attachment = $attributes['overlayAttachment'] ?? 'scroll';
$overlay_repeat     = $attributes['overlayRepeat'] ?? 'no-repeat';
$overlay_size       = $attributes['overlaySize'] ?? 'cover';
$overlay_blend_mode = $attributes['overlayBlendMode'] ?? 'normal';
$overlay_opacity    = $attributes['overlayOpacity'] ?? 50;

// Get background attributes.
$background                = $attributes['background'] ?? array();
$enable_adv_gradients      = $attributes['enableAdvGradients'] ?? false;
$enable_adv_bg_gradient    = $attributes['enableAdvBgGradient'] ?? false;
$enable_adv_bg_grad_hover  = $attributes['enableAdvBgGradientHover'] ?? false;
$background_gradient       = Core::get_advanced_gradient_value(
	$enable_adv_bg_gradient,
	$attributes['advBgGradient'] ?? '',
	$attributes['backgroundGradient'] ?? '',
	$enable_adv_gradients
);
$background_gradient_hover = Core::get_advanced_gradient_value(
	$enable_adv_bg_grad_hover,
	$attributes['advBgGradientHover'] ?? '',
	$attributes['backgroundGradientHover'] ?? '',
	$enable_adv_gradients
);

// Check if any breakpoint has video background, image background or overlay.
$has_video_background   = false;
$has_responsive_image   = false;
$responsive_controls    = $attributes['responsiveControls'] ?? array();
$video_background       = null;
$has_responsive_overlay = false;
// Check for video and image backgrounds in responsive controls.
$responsive_overlay_data = null;
foreach ( array( 'lg', 'md', 'sm' ) as $device ) {
	if ( isset( $responsive_controls[ $device ]['background']['type'] ) ) {
		if ( 'video' === $responsive_controls[ $device ]['background']['type'] ) {
			$has_video_background = true;
			// Store the first video found.
			if ( null === $video_background ) {
				$video_background = $responsive_controls[ $device ]['background'];
			}
		} elseif ( 'image' === $responsive_controls[ $device ]['background']['type'] && ! empty( $background['media']['url'] ) ) {
			$has_responsive_image = true;
		}
	}
	
	// Check for responsive overlay - when overlayType is image and has overlay image.
	if ( isset( $responsive_controls[ $device ]['overlayType'] ) && 'image' === $responsive_controls[ $device ]['overlayType'] &&
		isset( $responsive_controls[ $device ]['overlayImage']['url'] ) && ! empty( $responsive_controls[ $device ]['overlayImage']['url'] ) ) {
		$has_responsive_overlay = true;
		// Store the first overlay found for fallback.
		if ( null === $responsive_overlay_data ) {
			$responsive_overlay_data = $responsive_controls[ $device ];
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

// Check if container has video background and border radius.
$has_image_background = 'image' === $background_type;
$has_border_radius    = ! empty( $attributes['style']['border']['radius'] );

$background_styles = Core::get_background_image_styles( $background, $background_gradient, $background_gradient_hover );

// Get shadow configuration.
$shadow_config = Shadow::get_multi_state_shadow_styles(
	$attributes,
	array(
		'normal' => 'boxShadow',
		'hover'  => 'boxShadowHover',
	) 
);

$has_link = ! empty( $attributes['linkURL'] );

// If there's a link, define the anchor tag attributes.
$link_attributes = '';
if ( $has_link ) {
	$target          = ! empty( $attributes['linkTarget'] ) ? esc_attr( $attributes['linkTarget'] ) : '_self';
	$rel             = ! empty( $attributes['linkRel'] ) ? 'rel="' . esc_attr( Core::concatenate_array( $attributes['linkRel'] ) ) . '"' : '';
	$attributes_list = array(
		'href="' . esc_url( $attributes['linkURL'] ) . '"',
		'target="' . $target . '"',
		$rel,
	);
	// Filter out empty attributes and join them into a string.
	$link_attributes = Core::concatenate_array( $attributes_list );
}

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
	array( 'key' => 'backgroundColorHover' ),
	array(
		'key'   => 'backgroundGradientHover',
		'value' => $background_gradient_hover,
	),
	array( 'key' => 'backgroundColor' ),
	array(
		'key'   => 'backgroundGradient',
		'value' => $background_gradient,
	),
);

// Only add dimRatio to config if it has a valid numeric value.
if ( null !== $dimRatio ) {
	$config[] = array(
		'key'        => 'dimRatio',
		'css_var'    => '--spectra-overlay-opacity',
		'class_name' => 'spectra-dim-ratio',
		'value'      => $dimRatio,
	);
}


// Merge shadow configuration with main config.
$config = array_merge( $config, $shadow_config );

// Overlay CSS variables are now handled by ResponsiveAttributeCSS.php.

// Custom classes.
// Only add classes that are actually used in frontend styles.
$custom_classes = array( 
	// Video background class is required for proper positioning (from common.scss).
	( 'video' === $background_type || $has_video_background ) ? 'spectra-background-video' : '',
	// These classes are used for overflow handling with border-radius.
	$has_video_background ? 'has-video-background' : '',
	( $has_image_background || $has_responsive_image ) ? 'has-image-background' : '',
	// Add overlay class when overlay is used.
	$has_responsive_overlay ? 'spectra-background-overlay' : '',
	// Add background image class only when image URL is present.
	( ( 'image' === $background_type && ! empty( $background['media']['url'] ) ) || $has_responsive_image ) ? 'spectra-background-image' : '',
	// Add container overlay class - only when background is NOT video AND overlayType is image and has overlay image.
	( ( 'video' !== $background_type && 'image' === $overlay_type && ! empty( $overlay_image['url'] ) ) || $has_responsive_overlay ) ? 'has-container-overlay' : '',
	// Add root container class.
	( $attributes['isBlockRootParent'] ?? false ) ? 'spectra-is-root-container' : '',
	// Add alignment classes.
	( $attributes['align'] ?? '' ) === 'full' ? 'alignfull' : '',
	( $attributes['align'] ?? '' ) === 'wide' ? 'alignwide' : '',
	'spectra-overlay-color',
);



// Add responsive orientation classes for CSS targeting.
$layout_type = $layout['type'] ?? 'flex';
if ( 'flex' === $layout_type ) {
	// Check if orientation reverse is enabled on any device.
	$has_orientation_reverse = $orientation_reverse; // Base attribute.
	
	// Check for responsive orientation reverse.
	if ( ! $has_orientation_reverse && ! empty( $responsive_controls ) ) {
		foreach ( array( 'lg', 'md', 'sm' ) as $device ) {
			if ( isset( $responsive_controls[ $device ]['orientationReverse'] ) && $responsive_controls[ $device ]['orientationReverse'] ) {
				$has_orientation_reverse = true;
				break;
			}
		}
	}
	
	// Only add orientation classes if orientation reverse is enabled somewhere.
	$orientation_classes = array();
	if ( $has_orientation_reverse ) {
		// Collect orientation data for each device from responsive controls.
		$orientation_devices = array();
		$default_orientation = $layout['orientation'] ?? 'horizontal';
		
		foreach ( array( 'lg', 'md', 'sm' ) as $device ) {
			if ( isset( $responsive_controls[ $device ]['layout']['orientation'] ) ) {
				$orientation_devices[ $device ] = $responsive_controls[ $device ]['layout']['orientation'];
			}
		}
	
		// Desktop orientation (lg) - use responsive control or fallback to default.
		$desktop_orientation = $orientation_devices['lg'] ?? $default_orientation;
		if ( 'vertical' === $desktop_orientation ) {
			$orientation_classes[] = 'is-vertical-desktop';
		} else {
			$orientation_classes[] = 'is-horizontal-desktop';
		}
	
		// Tablet orientation (md) - use responsive control or fallback to desktop.
		if ( isset( $orientation_devices['md'] ) ) {
			if ( 'vertical' === $orientation_devices['md'] ) {
				$orientation_classes[] = 'is-vertical-tablet';
			} else {
				$orientation_classes[] = 'is-horizontal-tablet';
			}
		} else {
			// Tablet uses desktop value if no tablet-specific value.
			if ( 'vertical' === $desktop_orientation ) {
				$orientation_classes[] = 'is-vertical-tablet-from-desktop';
			} else {
				$orientation_classes[] = 'is-horizontal-tablet-from-desktop';
			}
		}
	
		// Mobile orientation (sm) - use responsive control or fallback to tablet/desktop.
		if ( isset( $orientation_devices['sm'] ) ) {
			if ( 'vertical' === $orientation_devices['sm'] ) {
				$orientation_classes[] = 'is-vertical-mobile';
			} else {
				$orientation_classes[] = 'is-horizontal-mobile';
			}
		} elseif ( isset( $orientation_devices['md'] ) ) {
			// Mobile uses tablet value if no mobile-specific value.
			if ( 'vertical' === $orientation_devices['md'] ) {
				$orientation_classes[] = 'is-vertical-mobile-from-tablet';
			} else {
				$orientation_classes[] = 'is-horizontal-mobile-from-tablet';
			}
		} else {
			// Mobile uses desktop value if no mobile or tablet values.
			if ( 'vertical' === $desktop_orientation ) {
				$orientation_classes[] = 'is-vertical-mobile-from-desktop';
			} else {
				$orientation_classes[] = 'is-horizontal-mobile-from-desktop';
			}
		}
	
		// Add all orientation classes.
		$custom_classes = array_merge( $custom_classes, $orientation_classes );
		
		// Only add base classes for backward compatibility if orientation reverse is not enabled.
		// When orientation reverse is enabled, device-specific classes provide better targeting.
		if ( ! $has_orientation_reverse ) {
			if ( 'vertical' === $desktop_orientation ) {
				$custom_classes[] = 'is-vertical';
			} else {
				$custom_classes[] = 'is-horizontal';
			}
		}
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

$additional_attributes = array();
if ( ! empty( $responsive_video_data ) ) {
	$additional_attributes['data-responsive-videos'] = wp_json_encode( $responsive_video_data );
}
// Add layout orientation data attribute for CSS targeting.
// Use desktop orientation from responsive controls if available, otherwise use base layout orientation.
$desktop_orientation_for_data = $layout['orientation'] ?? 'horizontal';
if ( ! empty( $responsive_controls['lg']['layout']['orientation'] ) ) {
	$desktop_orientation_for_data = $responsive_controls['lg']['layout']['orientation'];
}
$additional_attributes['data-orientation'] = $desktop_orientation_for_data;

// Note: Orientation reverse CSS is now handled by the ResponsiveControls extension's render_block filter.


// No inline CSS needed - styles are in SCSS file.
$overlay_css = '';


// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $additional_attributes, $custom_classes, $background_styles );

$link_attributes = 'a' === $html_tag ? $link_attributes : '';

// Return the view.
return 'file:./view.php';
