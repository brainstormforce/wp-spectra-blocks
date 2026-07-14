<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildTrigger
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get modalTrigger from context.
$modal_trigger = $block->context['spectra/modal/modalTrigger'] ?? '';

// Configuration for styles and classes.
$style_configs = array(
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Hide trigger if modalTrigger is not button, icon, or text.
$should_hide = $modal_trigger && ! in_array( $modal_trigger, array( 'button', 'icon', 'text' ), true );

// Custom classes.
$custom_classes = array(
	'spectra-modal-trigger',
	'wp-block-button',
);

// Add is-hidden class if needed.
if ( $should_hide ) {
	$custom_classes[] = 'is-hidden';
}

// Inline style fallback to guarantee hiding regardless of CSS specificity.
$inline_styles = $should_hide ? array( 'display' => 'none' ) : array();

// Get the block wrapper attributes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$style_configs,
	array(),
	$custom_classes,
	$inline_styles
);

// Return the view.
return 'file:./view.php';
