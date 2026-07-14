<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\Modal
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Set the attributes with fallback if required.
$background_color         = $attributes['backgroundColor'] ?? '';
$background_gradient      = $attributes['backgroundGradient'] ?? '';
$modal_trigger            = ! empty( $attributes['modalTrigger'] ) ? $attributes['modalTrigger'] : '';
$css_class                = ! empty( $attributes['cssClass'] ) ? $attributes['cssClass'] : '';
$css_id                   = ! empty( $attributes['cssId'] ) ? $attributes['cssId'] : '';
$exit_intent              = $attributes['exitIntent'] ?? false;
$enable_cookies           = $attributes['enableCookies'] ?? false;
$enable_display_after_sec = $attributes['showAfterSeconds'] ?? false;
$sec_to_show              = ! empty( $attributes['noOfSecondsToShow'] ) ? $attributes['noOfSecondsToShow'] : '';
$set_cookies_on           = ! empty( $attributes['setCookiesOn'] ) ? $attributes['setCookiesOn'] : '';
$hide_for_days            = ! empty( $attributes['hideForDays'] ) ? $attributes['hideForDays'] : '';
$overlay_click            = $attributes['overlayClick'] ?? false;
$esc_press                = $attributes['escPress'] ?? false;
$open_modal_as            = $attributes['openModalAs'] ?? 'popup';
$modal_position           = $attributes['modalPosition'] ?? '';
$h_pos                    = $attributes['hPos'] ?? '';
$v_pos                    = $attributes['vPos'] ?? '';
$appear_effect            = $attributes['appearEffect'] ?? '';
$close_icon_position      = $attributes['closeIconPosition'] ?? 'popup-top-right';

// Make a unique ID for this modal block.
$modal_block_id = wp_unique_id( 'spectra-modal-' );

$modal_context = array(
	'blockId'           => $modal_block_id,
	'isVisible'         => false,
	'modalTrigger'      => $modal_trigger,
	'cssClass'          => $css_class,
	'cssId'             => $css_id,
	'exitIntent'        => $exit_intent,
	'showAfterSeconds'  => $enable_display_after_sec,
	'noOfSecondsToShow' => $sec_to_show,
	'enableCookies'     => $enable_cookies,
	'setCookiesOn'      => $set_cookies_on,
	'hideForDays'       => $hide_for_days,
	'overlayClick'      => $overlay_click,
	'escPress'          => $esc_press,
	'openModalAs'       => $open_modal_as,
	'modalPosition'     => $modal_position,
	'hPos'              => $h_pos,
	'vPos'              => $v_pos,
	'appearEffect'      => $appear_effect,
	'closeIconPosition' => $close_icon_position,
);

// Configuration for styles and classes.
$style_configs = array(
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Custom classes.
$custom_classes = array(
	'spectra-modal-wrapper',
);

// Custom wrapper attributes.
$wrapper_config = array();
// Only set the wrapper ID if it's NOT being used as a custom trigger.
if ( ! empty( $css_id ) && 'custom-id' !== $modal_trigger ) {
	$wrapper_config['id'] = esc_attr( $css_id );
}

// Get the block wrapper attributes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$style_configs,
	$wrapper_config,
	$custom_classes
);

// Return the view.
return 'file:./view.php';
