<?php
/**
 * Controller for rendering the counter child wrapper block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\CounterChildWrapper
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get the block wrapper attributes with layout support.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	array(),
	array(),
	array( 'wp-block-spectra-counter-child-wrapper', 'spectra-counter-child-wrapper' )
);

// Render the wrapper block.
return 'file:./view.php';
