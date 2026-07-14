<?php
/**
 * Controller for processing Google Map block attributes.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\GoogleMap
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get the attributes with default values.
$address          = $attributes['address'] ?? 'Brainstorm Force';
$enable_satellite = $attributes['enableSatelliteView'] ?? false;
$height           = $attributes['height'] ?? '400px';
$language         = $attributes['language'] ?? 'en';
$zoom             = $attributes['zoom'] ?? 15;

// Early return if no address is provided.
if ( empty( $address ) || trim( $address ) === '' ) {
	return '';
}

// Build the Google Maps embed URL.
$encoded_address = rawurlencode( $address );
$lang_par        = $language ? $language : 'en';
$map_type        = $enable_satellite ? 'k' : 'm';

$map_url = "https://maps.google.com/maps?q={$encoded_address}&z={$zoom}&hl={$lang_par}&t={$map_type}&output=embed&iwloc=near";

// Generate block attributes for rendering.
$config                = array(
	array( 'key' => 'height' ),
);
$additional_classes    = array( 'spectra-google-map' );
$additional_attributes = array();

$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$config,
	$additional_attributes,
	$additional_classes
);

// Return the view file for rendering.
return 'file:./view.php';
