<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\TabsChildTabWrapper
 */

use SpectraBlocks\Helpers\BlockAttributes;

// If there are no tab inner blocks, don't render this tab wrapper.
if ( empty( $block->parsed_block['innerBlocks'] ) ) {
	return '';
}

// Get the number of tabs in this list, and count them.
$all_tabs = $block->parsed_block['innerBlocks'] ?? array();
$last_tab = count( $all_tabs ) - 1;

// Add the contexts required for the tab wrapper's interactivity, used inside the tab buttons.
$tab_button_wrapper_contexts = array(
	'firstTab' => 0,
	'lastTab'  => $last_tab,
);

// Style and class configurations.
$config = array(
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundGradient' ),
);

// Base classes.
$custom_classes = array( 'wp-block-button' );

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes( $attributes, $config, array(), $custom_classes );

// Return the view.
return 'file:./view.php';
