<?php
/**
 * The Spectra Block Attributes Helper.
 *
 * @package Spectra\Helpers
 */

namespace Spectra\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class BlockAttributes.
 * 
 * @since 3.0.0
 */
class BlockAttributes {

	/**
	 * Convert a string from camelCase or PascalCase to kebab-case.
	 * 
	 * @since 3.0.0
	 *
	 * @param string $string Input string (e.g., 'textSecondaryColor').
	 * @return string Kebab-case string (e.g., 'text-secondary-color').
	 */
	private static function to_kebab_case( $string ) {
		// If the input is not a string or empty, return an empty string.
		if ( ! is_string( $string ) || empty( $string ) ) {
			return '';
		}
		
		// Step 1: Insert a hyphen before each uppercase letter (except if it's the first character).
		// Example: "textSecondaryColor" becomes "text-Secondary-Color" (intermediate result).
		$string = preg_replace( '/(?<!^)([A-Z0-9])/', '-$1', $string );

		// Step 2: Convert the intermediate result to lowercase and replace any underscores with hyphens.
		// Final output: "text-Secondary-Color" becomes "text-secondary-color".
		return strtolower( str_replace( '_', '-', $string ) );
	}

	/**
	 * Convert WordPress preset format to proper CSS var() syntax.
	 * Handles: 'var:preset|type|slug' → 'var(--wp--preset--type--slug)'
	 *
	 * @since 3.0.0
	 *
	 * @param string $value The value to convert.
	 * @return string The converted CSS value, or original if no conversion needed.
	 */
	private static function convert_wordpress_preset( $value ) {
		if ( ! is_string( $value ) || empty( $value ) ) {
			return $value;
		}

		// Handle WordPress preset format: var:preset|type|slug.
		if ( preg_match( '/^var:preset\|([^|]+)\|(.+)$/', $value, $matches ) ) {
			return 'var(--wp--preset--' . $matches[1] . '--' . $matches[2] . ')';
		}

		return $value;
	}

	/**
	 * Generate styles and classes for a block based on configuration.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attributes Full block attributes.
	 * @param array $configs Array of configurations. Each can be:
	 *  - string (e.g., 'textColor'),
	 *  - array with:
	 *      - 'key' (string, required): Attribute key (e.g., 'textColor'),
	 *      - 'css_var' (?string): CSS variable (e.g., '--spectra-text-color') or null to skip,
	 *      - 'class_name' (?string): Class name (e.g., 'spectra-text-color') or null to skip,
	 *      - 'value' (mixed, optional): Explicit value (e.g., '#fff').
	 * @param array $custom_classes Additional custom classes (e.g., ['spectra-block']).
	 * @param array $custom_style   Additional styles mappings (e.g., ['--custom-color' => '#fff']).
	 * @return array                Indexed array [styles, classes] containing generated styles and classes.
	 */
	public static function generate_styles_and_classes(
		array $attributes,
		array $configs = array(),
		array $custom_classes = array(),
		array $custom_style = array()
	): array {
		$styles  = $custom_style;
		$classes = $custom_classes;

		if ( empty( $configs ) ) {
			return array( $styles, $classes );
		}

		$key        = null;
		$css_var    = null;
		$class_name = null;
		$value      = null;

		foreach ( $configs as $config ) {
			if ( is_string( $config ) ) {
				$key        = $config;
				$css_var    = '--spectra-' . self::to_kebab_case( $key );
				$class_name = 'spectra-' . self::to_kebab_case( $key );
			} else {
				$key = $config['key'] ?? null;

				if ( ! $key ) {
					continue;
				}

				$css_var    = isset( $config['css_var'] ) ? $config['css_var'] : ( $key ? '--spectra-' . self::to_kebab_case( $key ) : null ); // Ternary operator is because of $config['css_var'] can be null value.
				$class_name = isset( $config['class_name'] ) ? $config['class_name'] : ( $key ? 'spectra-' . self::to_kebab_case( $key ) : null ); // Ternary operator is because of $config['class_name'] can be null value.
				$value      = $config['value'] ?? null;
			}

			// Skip if key is missing or both css_var and class_name are null.
			if ( ! $key || ( is_null( $css_var ) && is_null( $class_name ) ) ) {
				continue;
			}

			// Resolve the value: explicit value takes precedence over attribute.
			$final_value = $value ?? ( $attributes[ $key ] ?? '' );

			// Skip if final_value is empty.
			if ( empty( $final_value ) && ! is_numeric( $final_value ) ) {
				continue;
			}

			// Convert WordPress preset format (var:preset|type|slug) to CSS var().
			$final_value = self::convert_wordpress_preset( $final_value );

			// Add styles if css_var isn't null.
			if ( ! is_null( $css_var ) ) {
				$styles[ $css_var ] = esc_attr( $final_value );
			}
		
			// Add if class_name isn't null.
			if ( ! is_null( $class_name ) ) {
				$classes[] = $class_name;
			}  
		}

		return array( $styles, $classes );
	}

	/**
	 * Get wrapper attributes by merging styles, classes, and custom attributes.
	 * 
	 * @since 3.0.0
	 *
	 * @param array $attributes Full block attributes.
	 * @param array $configs Array of style/class configurations.
	 * @param array $wrapper_config Array of attribute arrays (e.g., [ 'id' => $anchor ]).
	 * @param array $custom_classes Additional custom classes (e.g., ['spectra-block']).
	 * @param array $custom_style Additional styles mappings (e.g., ['--custom-color' => '#fff']).
	 * @return string Wrapper attributes string.
	 */
	public static function get_wrapper_attributes(
		array $attributes,
		array $configs = array(),
		array $wrapper_config = array(),
		array $custom_classes = array(),
		array $custom_style = array()
	): string {
		// Generate styles and classes.
		list( $styles, $classes ) = self::generate_styles_and_classes( $attributes, $configs, $custom_classes, $custom_style );

		$wrapper_attrs = array(
			'style' => Core::concatenate_array( $styles, 'style' ),
			'class' => Core::concatenate_array( $classes ),
		);

		// Handle anchor attribute manually before processing wrapper_config.
		// We do this because get_block_wrapper_attributes() might not always have access to
		// the block context needed to apply anchor support automatically.
		$has_anchor = ! empty( $attributes['anchor'] );
		if ( $has_anchor ) {
			$wrapper_attrs['id'] = $attributes['anchor'];
		}

		if ( ! empty( $wrapper_config ) && is_array( $wrapper_config ) ) {
			foreach ( $wrapper_config as $key => $value ) {
				// Add custom attribute only if the key is not empty and the value is non-empty.
				if ( ! empty( $key ) && ! empty( $value ) ) {
					// Special handling for class attribute - merge with existing classes.
					if ( 'class' === $key ) {
						$existing_classes       = ! empty( $wrapper_attrs['class'] ) ? $wrapper_attrs['class'] : '';
						$wrapper_attrs['class'] = trim( $existing_classes . ' ' . $value );
					} elseif ( 'id' === $key ) {
						// Only use custom ID if no anchor is set (anchor takes priority).
						if ( ! $has_anchor ) {
							$wrapper_attrs['id'] = $value;
						}
					} else {
						$wrapper_attrs[ $key ] = $value;
					}
				}
			}
		}

		// Call get_block_wrapper_attributes() which will merge our attributes with WordPress block supports.
		// Note: If WordPress's apply_block_supports() also adds an ID from anchor, it will be concatenated.
		// To prevent this, we need to check the result and clean duplicates.
		$wrapper_attributes_string = get_block_wrapper_attributes( $wrapper_attrs );

		// Fix duplicate IDs if WordPress also added the anchor as ID.
		// Pattern: id="value value" - same value repeated with space.
		if ( $has_anchor ) {
			$anchor_value              = esc_attr( $attributes['anchor'] );
			$duplicate_pattern         = 'id="' . $anchor_value . ' ' . $anchor_value . '"';
			$single_pattern            = 'id="' . $anchor_value . '"';
			$wrapper_attributes_string = str_replace( $duplicate_pattern, $single_pattern, $wrapper_attributes_string );
		}

		return $wrapper_attributes_string;
	}
}
