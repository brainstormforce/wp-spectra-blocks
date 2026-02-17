<?php
/**
 * Controller for rendering the SVG Animator block.
 *
 * @since x.x.x
 *
 * @package Spectra\Blocks\SvgAnimator
 */

use Spectra\Helpers\BlockAttributes;
use Spectra\Helpers\HtmlSanitizer;

// Extract attributes with fallbacks.
$anchor            = $attributes['anchor'] ?? '';
$svg_source        = $attributes['svgSource'] ?? 'upload';
$svg_content       = $attributes['svgContent'] ?? '';
$svg_url           = $attributes['svgUrl'] ?? '';
$fill_behavior     = $attributes['fillBehavior'] ?? 'none';
$animation_type    = $attributes['animationType'] ?? 'draw';
$animation_trigger = $attributes['animationTrigger'] ?? 'viewport';

// SVG security: sanitize inline SVG content.
if ( 'code' === $svg_source && ! empty( $svg_content ) ) {
	$svg_content = HtmlSanitizer::sanitize_svg( $svg_content );
}

// For uploaded SVGs, fetch and sanitize the content for inline rendering.
// SVG must be inline so JavaScript can animate individual path elements.
$inline_svg = '';
if ( 'upload' === $svg_source && ! empty( $attributes['svgId'] ) ) {
	// Use attachment ID instead of URL to prevent path traversal attacks.
	$svg_id          = absint( $attributes['svgId'] );
	$attachment_path = get_attached_file( $svg_id );

	if ( $attachment_path ) {
		$upload_dir      = wp_get_upload_dir();
		$uploads_realpath = realpath( $upload_dir['basedir'] );
		$file_realpath    = realpath( $attachment_path );

		// Validate: file exists, is within uploads directory, and is an SVG.
		if ( $file_realpath &&
			$uploads_realpath &&
			0 === strpos( $file_realpath, $uploads_realpath ) &&
			'image/svg+xml' === get_post_mime_type( $svg_id )
		) {
			$raw_svg = file_get_contents( $file_realpath ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( $raw_svg ) {
				$inline_svg = HtmlSanitizer::sanitize_svg( $raw_svg );
			}
		}
	}
} elseif ( 'code' === $svg_source && ! empty( $svg_content ) ) {
	$inline_svg = $svg_content;
}

// Accessibility handling.
$accessibility_mode  = $attributes['accessibilityMode'] ?? '';
$accessibility_label = $attributes['accessibilityLabel'] ?? '';

// Style and class configurations for CSS variables.
$config = array(
	array( 'key' => 'strokeColor' ),
	array( 'key' => 'strokeColorHover' ),
	array( 'key' => 'fillColor' ),
	array(
		'key'     => 'svgWidth',
		'css_var' => '--spectra-svg-animator-width',
	),
	array(
		'key'     => 'svgHeight',
		'css_var' => '--spectra-svg-animator-height',
	),
	array(
		'key'     => 'strokeWidth',
		'css_var' => '--spectra-svg-animator-stroke-width',
	),
);

// Data attributes for frontend animation JavaScript.
$element_attributes = array(
	'data-animation-type'     => esc_attr( $animation_type ),
	'data-animation-trigger'  => esc_attr( $animation_trigger ),
	'data-animation-duration' => (string) absint( $attributes['animationDuration'] ?? 2000 ),
	'data-animation-delay'    => (string) absint( $attributes['animationDelay'] ?? 0 ),
	'data-animation-easing'   => esc_attr( $attributes['animationEasing'] ?? 'ease' ),
	'data-animation-loop'     => ! empty( $attributes['animationLoop'] ) ? 'true' : 'false',
	'data-animation-reverse'  => ! empty( $attributes['animationReverse'] ) ? 'true' : 'false',
	'data-animation-yoyo'     => ! empty( $attributes['animationYoyo'] ) ? 'true' : 'false',
	'data-animation-stagger'  => (string) absint( $attributes['animationStagger'] ?? 0 ),
	'data-fill-behavior'      => esc_attr( $fill_behavior ),
);

// Custom classes.
$custom_classes = array( 'wp-block-spectra-svg-animator' );

// Get the block wrapper attributes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$config,
	$element_attributes,
	$custom_classes
);

// Return the view template.
return 'file:./view.php';
