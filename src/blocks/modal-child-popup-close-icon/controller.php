<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildPopupCloseIcon
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;

// Set the attributes with fallback if required.
$anchor              = $attributes['anchor'] ?? '';
$icon                = $attributes['icon'] ?? 'xmark';
$size                = $attributes['size'] ?? '25px';
$close_icon_position = $attributes['closeIconPosition'] ?? $block->context['spectra/modal/closeIconPosition'] ?? '';

// Set the default props required for the icon.
$icon_props = array(
	'focusable' => 'false',
	'style'     => array(
		'transform' => ! empty( $attributes['rotation'] ) ? 'rotate(' . ( is_rtl() ? '-' : '' ) . $attributes['rotation'] . 'deg)' : '',
	),
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
				__( 'Close modal', 'spectra-blocks' )
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
				__( 'Close modal', 'spectra-blocks' )
			);
		break;
	default:
		// In any other case, the SVG should be hidden from the accessibility tree.
		$icon_props['aria-hidden'] = 'true';
}


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
$custom_classes = array(
	'spectra-modal-popup-close',
	$close_icon_position,
);

// Prepare the wrapper aria-label.
$wrapper_aria_label = '';
if ( in_array( $attributes['accessibilityMode'] ?? '', array( 'svg', 'image' ), true ) && ! empty( $icon_props['aria-label'] ) ) {
	$wrapper_aria_label = $icon_props['aria-label'];
	// Set the SVG to be decorative since the wrapper has the label.
	$icon_props['aria-hidden'] = 'true';
	unset( $icon_props['aria-label'] );
	unset( $icon_props['role'] );
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array( 'id' => $anchor ), $custom_classes );

// Render the icon block.
return 'file:./view.php';
