<?php
/**
 * Controller for rendering the block.
 *
 * @since 3.0.0
 *
 * @package Spectra\Blocks\ListChildIcon
 */

use SpectraBlocks\Helpers\BlockAttributes;

// Get context from parent blocks.
$list_type    = $block->context['spectra/list/listType'] ?? 'unordered';
$icon_name    = $attributes['icon'] ?? $block->context['spectra/list/iconName'] ?? 'circle';
$rotation     = $attributes['rotation'] ?? $block->context['spectra/list/rotation'] ?? null;
$flip_for_rtl = ( isset( $attributes['flipForRTL'] ) && true === $attributes['flipForRTL'] )
	? $attributes['flipForRTL']
	: ( $block->context['spectra/list/flipForRTL'] ?? false );
$item_index   = $attributes['index'] ?? $block->context['spectra/list-child-item/index'] ?? 1;

// Define text and background colors with three-level inheritance: Icon > List-child-item > List.
$text_color = $attributes['textColor'] ??
	$block->context['spectra/list-child-item/textColor'] ??
	$block->context['spectra/list/textColor'] ?? '';

$text_color_hover = $attributes['textColorHover'] ??
	$block->context['spectra/list-child-item/textColorHover'] ??
	$block->context['spectra/list/textColorHover'] ?? '';

$background_color                = $attributes['backgroundColor'] ?? '';
$background_color_hover          = $attributes['backgroundColorHover'] ?? '';
$background_gradient_color       = $attributes['backgroundGradientColor'] ?? '';
$background_gradient_color_hover = $attributes['backgroundGradientColorHover'] ?? '';

// Get list style, start value and reversed settings.
$list_style  = $block->context['spectra/list/listStyle'] ?? 'decimal';
$start_value = $block->context['spectra/list/start'] ?? 1;
$is_reversed = $block->context['spectra/list/reversed'] ?? false;
$total_items = $block->context['spectra/list/totalItems'] ?? 0;

// Format number based on list style.
$formatted_number = '';
if ( 'ordered' === $list_type ) {
	// Get total items and ensure at least 1.
	$final_total_items = max( intval( $total_items ), 1 );

	// Determine start value with better validation.
	$start = 1;
	if ( ! is_null( $start_value ) && '' !== $start_value && is_numeric( $start_value ) && intval( $start_value ) > 0 ) {
		// If start value is explicitly set and valid, use it.
		$start = intval( $start_value );
	} elseif ( $is_reversed ) {
		// For reversed lists without start value (null/undefined), use total items.
		$start = $final_total_items;
	}

	// Calculate the actual number based on position and reversed state.
	// For normal lists: start + (position - 1).
	// For reversed lists: start - (position - 1).
	$actual_num = intval( $item_index );
	if ( $is_reversed ) {
		// Reversed: start from start value and count backwards.
		// If start=3 and 3 items: positions should be 3, 2, 1.
		$actual_num = $start - ( $actual_num - 1 );
	} else {
		// Normal: start from start value and count forwards.
		// If start=1 and 3 items: positions should be 1, 2, 3.
		$actual_num = $start + ( $actual_num - 1 );
	}

	// Format based on list style.
	switch ( $list_style ) {
		case 'upper-alpha':
			if ( $actual_num <= 0 || $actual_num > 26 ) {
				$formatted_number = "{$actual_num}"; // Show negative numbers for letters.
			} else {
				$formatted_number = chr( 64 + $actual_num );
			}
			break;
		case 'lower-alpha':
			if ( $actual_num <= 0 || $actual_num > 26 ) {
				$formatted_number = "{$actual_num}"; // Show negative numbers for letters.
			} else {
				$formatted_number = chr( 96 + $actual_num );
			}
			break;
		case 'upper-roman':
		case 'lower-roman':
			if ( $actual_num <= 0 ) {
				$formatted_number = "{$actual_num}"; // Show negative numbers for Roman numerals.
			} else {
				// Direct implementation of Roman numeral conversion.
				$roman_numerals = array(
					'M'  => 1000,
					'CM' => 900,
					'D'  => 500,
					'CD' => 400,
					'C'  => 100,
					'XC' => 90,
					'L'  => 50,
					'XL' => 40,
					'X'  => 10,
					'IX' => 9,
					'V'  => 5,
					'IV' => 4,
					'I'  => 1,
				);
				$result         = '';
				$num            = $actual_num;

				foreach ( $roman_numerals as $key => $value ) {
					while ( $num >= $value ) {
						$result .= $key;
						$num    -= $value;
					}
				}
				// Format the number based on the case, if not `upper-roman` then `lower-roman`.
				$formatted_number = 'upper-roman' === $list_style ? strtoupper( $result ) : strtolower( $result );
			}
			break;
		case 'decimal-leading-zero':
			if ( $actual_num <= 0 ) {
				$formatted_number = "{$actual_num}"; // Show negative numbers.
			} else {
				$formatted_number = $actual_num < 10 ? "0{$actual_num}" : "{$actual_num}";
			}
			break;
		case 'decimal':
		default:
			$formatted_number = "{$actual_num}"; // Always show the number (including negative).
			break;
	}

	$formatted_number .= '.';
}


// Set the attributes with fallback if required.
$icon = 'ordered' === $list_type ? null : $icon_name;

// Set the default props required for the icon.
$icon_props = array(
	'focusable' => 'false',
	'style'     => array(
		'transform' => ! empty( $rotation ) ? 'rotate(' . $rotation . 'deg)' : '',
	),
);

// Additional classes.
$custom_classes = array(
	'spectra-list-icon',
	'spectra-list-icon-' . $list_type,
);

// Style and class configurations.
$config = array(
	array( 'key' => 'textColor' ),
	array( 'key' => 'textColorHover' ),
	array( 'key' => 'backgroundColor' ),
	array( 'key' => 'backgroundColorHover' ),
	array( 'key' => 'backgroundGradient' ),
	array( 'key' => 'backgroundGradientHover' ),
);

// Get the block wrapper attributes, and extend the styles and classes.
$wrapper_attributes = BlockAttributes::get_wrapper_attributes(
	$attributes,
	$config,
	array(),
	$custom_classes
);

// Render the icon block.
return 'file:./view.php';
