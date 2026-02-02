<?php
/**
 * The Spectra Shadow Helper.
 *
 * @package Spectra\Helpers
 */

namespace Spectra\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Shadow.
 * 
 * @since 3.0.0
 */
class Shadow {

	/**
	 * Get shadow styles for CSS application.
	 * 
	 * @since 3.0.0
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $shadow_attribute Name of the shadow attribute (default: 'boxShadow').
	 * @return array CSS styles array.
	 */
	public static function get_shadow_styles( $attributes, $shadow_attribute = 'boxShadow' ) {
		$shadow_value = $attributes[ $shadow_attribute ] ?? '';
		
		if ( empty( $shadow_value ) || 'none' === $shadow_value ) {
			return array();
		}

		return array(
			'box-shadow' => $shadow_value,
		);
	}

	/**
	 * Get multiple shadow styles configuration for different states (normal, hover, etc.).
	 * This function returns configuration for BlockAttributes::get_wrapper_attributes
	 * 
	 * @since 3.0.0
	 *
	 * @param array $attributes Block attributes.
	 * @param array $config Shadow configuration array.
	 * @return array Configuration array for BlockAttributes.
	 */
	public static function get_multi_state_shadow_styles( $attributes, $config = array() ) {
		$default_config = array(
			'normal' => 'boxShadow',
			'hover'  => 'boxShadowHover',
		);
		
		$config        = array_merge( $default_config, $config );
		$shadow_config = array();

		// Normal state shadow.
		$normal_shadow = $attributes[ $config['normal'] ] ?? '';
		$hover_shadow  = $attributes[ $config['hover'] ] ?? '';
		
		if ( ! empty( $normal_shadow ) && 'none' !== $normal_shadow ) {
			$shadow_config[] = array(
				'key'        => $config['normal'],
				'css_var'    => '--spectra-box-shadow',
				'class_name' => 'spectra-box-shadow',
				'value'      => $normal_shadow,
			);
		}

		// Hover state shadow - only add hover class if hover shadow is explicitly set.
		if ( ! empty( $hover_shadow ) && 'none' !== $hover_shadow ) {
			$shadow_config[] = array(
				'key'        => $config['hover'],
				'css_var'    => '--spectra-box-shadow-hover',
				'class_name' => 'spectra-box-shadow-hover',
				'value'      => $hover_shadow,
			);
		}

		return $shadow_config;
	}

	/**
	 * Check if shadow value has content.
	 * 
	 * @since 3.0.0
	 *
	 * @param string $shadow_value Shadow CSS string.
	 * @return boolean Whether shadow has content.
	 */
	public static function has_shadow_value( $shadow_value ) {
		return ! empty( $shadow_value ) && 'none' !== $shadow_value;
	}

	/**
	 * Parse shadow CSS string into shadow components.
	 * 
	 * @since 3.0.0
	 *
	 * @param string $shadow_string CSS shadow string.
	 * @return array Parsed shadow components.
	 */
	public static function parse_shadow_string( $shadow_string ) {
		if ( empty( $shadow_string ) || 'none' === $shadow_string ) {
			return array(
				'color'  => '#000000',
				'x'      => 0,
				'y'      => 4,
				'blur'   => 8,
				'spread' => 0,
				'inset'  => false,
			);
		}

		// Simple parsing - can be enhanced for more complex cases.
		$is_inset     = strpos( $shadow_string, 'inset' ) !== false;
		$clean_string = str_replace( 'inset', '', $shadow_string );
		$clean_string = trim( $clean_string );
		$parts        = explode( ' ', $clean_string );
		
		// Extract numeric values and color.
		$numeric_parts = array();
		$color         = '#000000';
		
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( empty( $part ) ) {
				continue;
			}
			
			if ( strpos( $part, 'px' ) !== false || strpos( $part, 'rem' ) !== false || strpos( $part, 'em' ) !== false ) {
				$numeric_parts[] = intval( $part );
			} elseif ( strpos( $part, '#' ) !== false || strpos( $part, 'rgb' ) !== false || strpos( $part, 'hsl' ) !== false ) {
				$color = $part;
			}
		}

		return array(
			'color'  => $color,
			'x'      => $numeric_parts[0] ?? 0,
			'y'      => $numeric_parts[1] ?? 4,
			'blur'   => $numeric_parts[2] ?? 8,
			'spread' => $numeric_parts[3] ?? 0,
			'inset'  => $is_inset,
		);
	}

	/**
	 * Generate CSS shadow string from shadow components.
	 * 
	 * @since 3.0.0
	 *
	 * @param array $shadow_obj Shadow components array.
	 * @return string CSS shadow string.
	 */
	public static function generate_shadow_string( $shadow_obj ) {
		if ( empty( $shadow_obj ) ) {
			return '';
		}
		
		$x      = $shadow_obj['x'] ?? 0;
		$y      = $shadow_obj['y'] ?? 4;
		$blur   = $shadow_obj['blur'] ?? 8;
		$spread = $shadow_obj['spread'] ?? 0;
		$color  = $shadow_obj['color'] ?? '#000000';
		$inset  = $shadow_obj['inset'] ?? false;
		
		$inset_prefix = $inset ? 'inset ' : '';
		
		return sprintf( '%s%dpx %dpx %dpx %dpx %s', $inset_prefix, $x, $y, $blur, $spread, $color );
	}

	/**
	 * Default shadow presets.
	 * 
	 * @since 3.0.0
	 *
	 * @return array Default shadow presets.
	 */
	public static function get_default_presets() {
		return array(
			array(
				'name'  => __( 'None', 'spectra' ),
				'value' => '',
			),
			array(
				'name'  => __( 'Small', 'spectra' ),
				'value' => '0px 1px 3px rgba(0, 0, 0, 0.12)',
			),
			array(
				'name'  => __( 'Medium', 'spectra' ),
				'value' => '0px 4px 8px rgba(0, 0, 0, 0.15)',
			),
			array(
				'name'  => __( 'Large', 'spectra' ),
				'value' => '0px 8px 16px rgba(0, 0, 0, 0.15)',
			),
			array(
				'name'  => __( 'Extra Large', 'spectra' ),
				'value' => '0px 16px 32px rgba(0, 0, 0, 0.15)',
			),
			array(
				'name'  => __( 'Inner Small', 'spectra' ),
				'value' => 'inset 0px 1px 3px rgba(0, 0, 0, 0.12)',
			),
			array(
				'name'  => __( 'Inner Medium', 'spectra' ),
				'value' => 'inset 0px 2px 6px rgba(0, 0, 0, 0.15)',
			),
		);
	}
}
