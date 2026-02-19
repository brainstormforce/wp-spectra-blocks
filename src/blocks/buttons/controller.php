<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Buttons
 */

defined( 'ABSPATH' ) || exit;
use Spectra\Helpers\BlockAttributes;

// Base classes.
$custom_classes = array( 'wp-block-button', 'spectra-buttons' );

// Style and class configurations.
$config = array(
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $custom_classes );

// Return the view.
return 'file:./view.php';
