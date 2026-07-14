<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Icon
 */

use SpectraBlocks\Helpers\BlockAttributes;
use SpectraBlocks\Helpers\Core;
use SpectraBlocks\Helpers\Renderer;


// Set the attributes with fallback if required.
$anchor = $attributes['anchor'] ?? '';
$icon   = $attributes['icon'] ?? 'star';

// Set the default props required for the icon.
$icon_props = array(
	'focusable' => 'false',
	'style'     => array(
		'transform' => ! empty( $attributes['rotation'] ) ? 'rotate(' . $attributes['rotation'] . 'deg)' : '',
	),
);

// Default link requirements for the Icon Block.
$has_link = ! empty( $attributes['linkURL'] );
$target   = '';
$rel      = '';

// If there's an anchor available, then render the anchor.
if ( $has_link ) {
	// Set the target, and keep a default rel string.
	$target = $attributes['linkTarget'] ?? '_self';

	// If the Rel attribute array exists, concatenate the attributes into a single string.
	if ( ! empty( $attributes['linkRel'] ) && is_array( $attributes['linkRel'] ) ) {
		// Note that the attribute is being formatted here.
		$concatenated_rel = trim( Core::concatenate_array( $attributes['linkRel'] ) );
		$rel              = ! empty( $concatenated_rel ) ? esc_attr( $concatenated_rel ) : '';
	}
}

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
				__( 'An icon named %s', 'spectra-blocks' ),
				Renderer::get_icon_name( $icon )
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
				__( 'An image named %s', 'spectra-blocks' ),
				Renderer::get_icon_name( $icon )
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

// Array of element attributes to extend to the wrapper.
$element_attributes = array();

// Variable to determine if the link tags should be rendered.
$render_link = ! empty( $target ) && ! empty( $attributes['linkURL'] );

// Determine the tag to be rendered based on whether a link is set or not.
$tag_name = $render_link ? 'a' : 'div';

// If this is an anchor, then render the required anchor attributes in this tag as well.
if ( $render_link ) {
	$element_attributes['href']   = $attributes['linkURL'];
	$element_attributes['target'] = $target;
	$element_attributes['rel']    = $rel;

	// Only add aria-label to links if the icon is not decorative.
	$accessibility_mode = $attributes['accessibilityMode'] ?? '';
	if ( 'decorative' !== $accessibility_mode ) {
		// For non-decorative icons with links, use the provided label if any, else use the icon's name.
		$element_attributes['aria-label'] = ! empty( $icon_props['aria-label'] ) ? $icon_props['aria-label'] : Renderer::get_icon_name( $icon );
	}
	// For decorative icons with links, no aria-label is added - screen readers will announce the link URL.
}

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, $element_attributes );

// Render the icon block.
return 'file:./view.php';
