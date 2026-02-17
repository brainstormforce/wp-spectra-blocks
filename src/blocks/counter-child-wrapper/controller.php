<?php
/**
 * Controller for rendering the counter child wrapper block.
 * 
 * @since 0.0.1
 *
 * @package Spectra\Blocks\CounterChildWrapper
 */

use Spectra\Helpers\BlockAttributes;

// Get the block wrapper attributes with layout support.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( 
	$attributes,
	array(),
	array(),
	array( 'wp-block-spectra-counter-child-wrapper', 'spectra-counter-child-wrapper' )
);

// Render the wrapper block.
return 'file:./view.php';

