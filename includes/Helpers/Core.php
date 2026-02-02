<?php
/**
 * The Spectra Helper.
 *
 * @package Spectra\Helpers
 */

namespace Spectra\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Core.
 *
 * @since 1.0.0
 */
class Core {

	/**
	 * Store Json variable for icons.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public static $icon_json = null;

	/**
	 * Get Json Data for FontAwesome icons.
	 *
	 * For the lightweight version, this returns an empty array.
	 * Icons can still be used via raw SVG input.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function backend_load_font_awesome_icons() {
		// Return cached icons if available.
		if ( null !== self::$icon_json ) {
			return self::$icon_json;
		}

		// For lightweight plugin, return empty array.
		// Users can use raw SVG icons instead.
		self::$icon_json = array();

		/**
		 * Filter to add custom icon sets.
		 *
		 * @since 1.0.0
		 * @param array $icon_json The icon data array.
		 */
		return apply_filters( 'spectra_icon_chunks', self::$icon_json );
	}

	/**
	 * Concatenate non-empty string values from an array into a single string.
	 *
	 * Based on the type, the returned string will differ.
	 * -> By default, the returned string will just concatenate all the values in the array into a single string.
	 * -> If the type is 'style', the returned string will concatenate the keys with the values as style properties.
	 *
	 * @since 1.0.0
	 * @param array  $value_array Array containing the values to concatenate.
	 * @param string $type        Determines the type of concatenation.
	 * @return string Formatted string with concatenated key-value pairs.
	 */
	public static function concatenate_array( $value_array, $type = '' ) {

		// Filter out non-string values and empty strings.
		$required_values = array_filter(
			$value_array,
			function ( $value ) {
				return ( is_string( $value ) && trim( $value ) !== '' );
			}
		);

		// Set the default separator to a space.
		$separator = ' ';

		// If the type is 'style', then the items will be parsed in the form of 'key: value;'.
		if ( 'style' === $type ) {
			// Format each key-value pair into a single string for each array value.
			$required_values = array_map(
				function ( $key, $value ) {
					return empty( $value ) && ! is_numeric( $value ) ? null : "$key: $value";
				},
				array_keys( $required_values ),
				$required_values
			);
			// Set the separator to a semicolon before the space.
			$separator = '; ';
		}

		// Check if the array is empty.
		$required_values = array_filter( $required_values );

		// If not empty, join all pairs with the required separator, and append the trimmed separator to the end.
		return ( ! empty( $required_values ) ) ? trim( implode( $separator, $required_values ) ) . trim( $separator ) : '';
	}

	/**
	 * Get the block name without the namespace and without the 'child' word inside it.
	 *
	 * @since 1.0.0
	 * @param string $blockname The block name as fetched from WordPress.
	 * @return string Either an empty string, the blockname without changes, or the blockname as expected.
	 */
	public static function get_blockname_without_child_keyword( $blockname ) {
		// If the given blockname is empty, or is not a string, abandon ship.
		if ( empty( $blockname ) || ! is_string( $blockname ) ) {
			return '';
		}

		// Split by slashes.
		$blockname_parts = explode( '/', $blockname );

		// If the split was not possible, abandon ship.
		if ( empty( $blockname_parts ) ) {
			return $blockname;
		}

		// Get last part safely.
		$blockname_without_namespace = end( $blockname_parts );

		// If the last part for some reason does not exist, abandon ship.
		if ( false === $blockname_without_namespace ) {
			return $blockname;
		}

		// Replace '-child-' with '-'. This will format the blockname without the child substring.
		return str_replace( '-child-', '-', \sanitize_key( $blockname_without_namespace ) );
	}

	/**
	 * Get the background image styles based on the current background.
	 *
	 * @since 1.0.0
	 * @param array  $background                The background attribute.
	 * @param string $background_gradient       The background gradient attribute.
	 * @param string $background_gradient_hover The background gradient hover attribute.
	 * @return array Either an empty array, or the array of style attributes.
	 */
	public static function get_background_image_styles( $background, $background_gradient = '', $background_gradient_hover = '' ) {

		// If the background type is not image, or there's no URL to the media, abandon ship.
		if (
			! isset( $background['type'] )
			|| 'image' !== $background['type']
			|| ! isset( $background['media']['url'] )
		) {
			return array();
		}

		// Create an object to add the additional styles to.
		$styles = array();

		// Create the conditional variables.
		$background_url     = $background['media']['url'];
		$background_overlay = $background['useOverlay'] ?? false;

		// If there's a background gradient and an image without overlay, combine the two. Else just use the image.
		if ( $background_gradient && ! $background_overlay ) {
			$styles['--spectra-background-image'] = 'url(' . $background_url . '),var(--spectra-background-gradient)';
		} else {
			$styles['--spectra-background-image'] = 'url(' . $background_url . ')';
		}

		// If there's a background gradient on hover and an image without overlay, combine the two. Else just use the image.
		if ( $background_gradient_hover && ! $background_overlay ) {
			$styles['--spectra-background-image-hover'] = 'url(' . $background_url . '),var(--spectra-background-gradient-hover)';
		} else {
			$styles['--spectra-background-image-hover'] = 'url(' . $background_url . ')';
		}

		// Add the other background image based props.
		$styles['--spectra-background-size']   = $background['backgroundSize'] ?? 'cover';
		$styles['--spectra-background-repeat'] = $background['backgroundRepeat'] ?? 'no-repeat';

		// If focal point values are defined (including 0), add the focal point CSS.
		if ( isset( $background['backgroundPosition'] ) &&
			( isset( $background['backgroundPosition']['x'] ) || isset( $background['backgroundPosition']['y'] ) ) ) {

			$position_x = ( isset( $background['backgroundPosition']['x'] ) && is_numeric( $background['backgroundPosition']['x'] ) )
				? $background['backgroundPosition']['x']
				: 0.5;

			$position_y = ( isset( $background['backgroundPosition']['y'] ) && is_numeric( $background['backgroundPosition']['y'] ) )
				? $background['backgroundPosition']['y']
				: 0.5;

			$styles['--spectra-background-position'] = $position_x * 100 . '% ' . $position_y * 100 . '%';
		}

		// Add background attachment.
		if ( isset( $background['backgroundAttachment'] ) ) {
			$styles['--spectra-background-attachment'] = $background['backgroundAttachment'];
		}

		// Return the styles array.
		return $styles;
	}

	/**
	 * Get the final gradient value based on advanced mode.
	 *
	 * @since 1.0.0
	 * @param bool   $enable_adv_bg Whether advanced gradient is enabled.
	 * @param string $adv_value The advanced gradient value.
	 * @param string $basic_value The basic gradient value.
	 * @param bool   $enable_adv_gradients Whether advanced gradients are globally enabled.
	 * @return string The final gradient value to use.
	 */
	public static function get_advanced_gradient_value( $enable_adv_bg, $adv_value, $basic_value, $enable_adv_gradients = false ) {
		// If both toggles are enabled, use advanced value only (never fallback to basic).
		if ( $enable_adv_gradients && $enable_adv_bg ) {
			return ! empty( $adv_value ) ? $adv_value : '';
		}
		// Otherwise use basic value.
		return $basic_value;
	}

	/**
	 * Get - RGBA Color
	 *
	 * Get HEX color and return RGBA. Default return RGB color.
	 *
	 * @since 1.0.0
	 *
	 * @param string $color      Gets the color value.
	 * @param string $opacity    Gets the opacity value.
	 * @param array  $is_array Gets an array of the value.
	 * @return string $output Returns the color value.
	 */
	public static function hex2rgba( $color, $opacity = false, $is_array = false ) {
		$default = $color;

		// Return default if no color provided or not a string.
		if ( empty( $color ) || ! is_string( $color ) ) {
			return $default;
		}

		// Sanitize $color if "#" is provided.
		if ( '#' === $color[0] ) {
			$color = substr( $color, 1 );
		}

		// Check if color has 6 or 3 characters and get values.
		if ( strlen( $color ) === 6 ) {
			$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) === 3 ) {
			$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return $default;
		}

		// Validate hex values before conversion to prevent deprecated warnings.
		$valid_hex = array();
		foreach ( $hex as $hex_value ) {
			if ( ctype_xdigit( $hex_value ) ) {
				$valid_hex[] = $hex_value;
			} else {
				// Invalid hex characters found, return default.
				return $default;
			}
		}

		// Convert hexadec to rgb.
		$rgb = array_map( 'hexdec', $valid_hex );

		// Check if opacity is set(rgba or rgb).
		if ( false !== $opacity && '' !== $opacity ) {
			// Handle null opacity (keep original behavior).
			if ( null === $opacity ) {
				$output = 'rgba(' . implode( ', ', $rgb ) . ', )';
			} else {
				// Ensure opacity is numeric before using abs().
				if ( is_numeric( $opacity ) ) {
					if ( abs( (float) $opacity ) > 1 ) {
						$opacity = (float) $opacity / 100;
					}
				} else {
					$opacity = 1; // Default to full opacity for invalid values.
				}
				$output = 'rgba(' . implode( ', ', $rgb ) . ', ' . $opacity . ')';
			}
		} else {
			$output = 'rgb(' . implode( ', ', $rgb ) . ')';
		}

		if ( $is_array ) {
			return $rgb;
		} else {
			// Return rgb(a) color string.
			return $output;
		}
	}

	/**
	 * Check if the given metadata represents a Spectra block.
	 *
	 * @since 1.0.0
	 *
	 * @param array $metadata The block metadata.
	 * @return bool True if the block is a Spectra block, false otherwise.
	 */
	public static function is_spectra_block( $metadata ) {
		return isset( $metadata['name'] ) && strpos( $metadata['name'], 'spectra/' ) === 0;
	}
}
