<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildTriggerIcon
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// Set the attributes with fallback if required.
$anchor                    = $attributes['anchor'] ?? '';
$icon                      = $attributes['icon'] ?? 'up-right-from-square';
$size                      = $attributes['size'] ?? '30px';
$text_color                = $attributes['textColor'] ?? '';
$text_color_hover          = $attributes['textColorHover'] ?? '';
$background_color          = $attributes['backgroundColor'] ?? '';
$background_color_hover    = $attributes['backgroundColorHover'] ?? '';
$background_gradient       = $attributes['backgroundGradient'] ?? '';
$background_gradient_hover = $attributes['backgroundGradientHover'] ?? '';
$modal_trigger             = ! empty( $attributes['modalTrigger'] ) ? $attributes['modalTrigger'] : ( $block->context['spectra/modal/modalTrigger'] ?? '' );

// Set the default props required for the icon.
$icon_props = array(
	'focusable' => 'false',
	'style'     => array(
		'transform' => ! empty( $attributes['rotation'] ) ? 'rotate(' . ( is_rtl() ? '-' : '' ) . $attributes['rotation'] . 'deg)' : '',
	),
);

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Custom classes.
$is_hidden = 'icon' !== $modal_trigger;

$custom_classes = array(
	$is_hidden ? 'is-hidden' : '',
	'modal-trigger-element',
);

// Add the accessibility details based on the attributes.
switch ( $attributes['accessibilityMode'] ?? '' ) {
	case 'svg':
		// SVG based accessibility attributes.
		$icon_props['role']        = 'graphics-symbol';
		$icon_props['aria-hidden'] = 'false';
		$icon_props['aria-label']  = ! empty( $attributes['accessibilityLabel'] )
			? $attributes['accessibilityLabel']
			: sprintf(
				/* translators: %s: The name of the SVG icon. */
				__( 'Open modal', 'spectra-blocks' )
			);
		break;
	case 'image':
		// Image based accessibility attributes.
		$icon_props['role']        = 'img';
		$icon_props['aria-hidden'] = 'false';
		$icon_props['aria-label']  = ! empty( $attributes['accessibilityLabel'] )
			? $attributes['accessibilityLabel']
			: sprintf(
				/* translators: %s: The name of the SVG image. */
				__( 'Open modal', 'spectra-blocks' )
			);
		break;
	default:
		// In any other case, the SVG should be hidden from the accessibility tree.
		$icon_props['aria-hidden'] = 'true';
}

// Prepare the wrapper aria-label.
$wrapper_aria_label = '';
if ( in_array( $attributes['accessibilityMode'] ?? '', array( 'svg', 'image' ), true ) && ! empty( $icon_props['aria-label'] ) ) {
	$wrapper_aria_label = $icon_props['aria-label'];
	// Set the SVG to be decorative since the wrapper has the label.
	$icon_props['aria-hidden'] = 'true';
	unset( $icon_props['aria-label'] );
	unset( $icon_props['role'] );
}

// Custom wrapper attributes.
$wrapper_config = array();
if ( ! empty( $anchor ) ) {
	$wrapper_config['id'] = esc_attr( $anchor );
}

// Inline style fallback to guarantee hiding regardless of CSS specificity.
$custom_styles = $is_hidden ? array( 'display' => 'none' ) : array();

// Get the block wrapper attributes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$config,
	$wrapper_config,
	$custom_classes,
	$custom_styles
);

// Render the icon block.
return 'file:./view.php';
