<?php
/**
 * Controller for rendering the Separator block.
 * 
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Separator
 */

use Spectra\Helpers\BlockAttributes;

// Note: Separator style, alignment, and color logic has been moved to ResponsiveAttributeCSS.php
// to support responsive behavior. Inline styles are removed to prevent specificity conflicts.

// Style and class configurations.
$config = array(
	array( 'key' => 'separatorColor' ),
);

// Custom classes.
$custom_classes = array( 'wp-block-spectra-separator' );

// Get the block wrapper attributes - responsive CSS is handled automatically.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $custom_classes );

// Return the view.
return 'file:./view.php';
