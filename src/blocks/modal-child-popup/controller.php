<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ModalChildPopup
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Set the attributes with fallback values.
$icon_position       = $attributes['iconPosition'] ?? 'after';
$open_as_modal       = $block->context['spectra/modal/openModalAs'] ?? 'popup';
$modal_position      = $block->context['spectra/modal/modalPosition'] ?? '';
$close_icon_position = $block->context['spectra/modal/closeIconPosition'] ?? '';
$h_position          = ! empty( $attributes['hPos'] ) ? $attributes['hPos'] : ( $block->context['spectra/modal/hPos'] ?? '' );
$v_position          = ! empty( $attributes['vPos'] ) ? $attributes['vPos'] : ( $block->context['spectra/modal/vPos'] ?? '' );
$appear_effect       = $block->context['spectra/modal/appearEffect'] ?? '';
$is_pro              = ( is_plugin_active( 'spectra-pro/spectra-pro.php' ) || is_plugin_active( 'spectra-blocks-pro/spectra-blocks-pro.php' ) );

// Check if we need a wrapper - add wrapper if NOT window positioning.
$needs_wrapper = ! ( 'window-top-left' === $close_icon_position || 'window-top-right' === $close_icon_position );

// Generate unique ID.
$modal_id = 'spectra-modal-' . wp_unique_id();

// Configuration for styles and classes.
$style_configs = array(
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundGradient' ),
);

// Add horizontal and vertical position CSS variables if they are set.
if ( $h_position ) {
	$style_configs[] = array(
		'key'        => 'hPos',
		'value'      => $h_position,
		'css_var'    => '--spectra-modal-h-position',
		'class_name' => null,
	);
}

if ( $v_position ) {
	$style_configs[] = array(
		'key'        => 'vPos',
		'value'      => $v_position,
		'css_var'    => '--spectra-modal-v-position',
		'class_name' => null,
	);
}

// Custom classes.
$custom_classes = array_filter(
	array(
		$appear_effect,
		'spectra-modal-popup',
		'before' === $icon_position ? 'icon-before' : 'icon-after',
		$is_pro ? "spectra-modal-type-{$open_as_modal}" : '',
		( $is_pro && 'popup' === $open_as_modal ) ? "spectra-modal-position-{$modal_position}" : '',
	)
);

// Custom wrapper attributes.
$wrapper_config = array(
	'data-spectra-modal'          => true,
	'data-wp-interactive'         => 'spectra/modal',
	'data-wp-bind--data-modal-id' => 'spectra/modal::context.blockId',
	'data-wp-bind--id'            => 'spectra/modal::context.blockId',
);

// Get block wrapper attributes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$style_configs,
	$wrapper_config,
	$custom_classes
);

// Prepare wrapper classes if needed.
if ( $needs_wrapper ) {
	$wrap_class              = array_filter(
		array(
			$h_position && 'custom' === $modal_position ? 'horizontal-position' : '',
			$v_position && 'custom' === $modal_position ? 'vertical-position' : '',
			'spectra-modal-popup-wrap',
		)
	);
	$content_wrap_attributes = 'class="' . esc_attr( implode( ' ', $wrap_class ) ) . '"';
}

// Return the view.
return 'file:./view.php';
