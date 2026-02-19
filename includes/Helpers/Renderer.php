<?php
/**
 * The Spectra Helpers that relate to rendering.
 *
 * @package Spectra
 */

namespace Spectra\Helpers;

use Spectra\Helpers\Core;
use Spectra\Helpers\HtmlSanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Class Renderer.
 * 
 * @since 3.0.0
 */
class Renderer {
	/**
	 * We have icon list in chunks in this variable we will merge all insides array into one single array.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	public static $icon_array_merged = array();

	/**
	 * Generate SVG.
	 *
	 * @since 3.0.0
	 * @param string  $icon             Icon name or raw SVG content.
	 * @param boolean $flip_for_rtl     Indicated if the current SVG needs to be flipped in RTL mode.
	 * @param array   $additional_props Any additional props.
	 * @return void
	 */
	public static function svg_html( $icon, $flip_for_rtl = false, $additional_props = array() ) {
		// Handle uploaded SVG (Elementor format).
		if ( ! empty( $icon ) && is_array( $icon ) && isset( $icon['library'] ) && 'svg' === $icon['library'] && isset( $icon['value']['id'] ) ) {
			$attachment_id = intval( $icon['value']['id'] );
			$svg_content   = self::get_uploaded_svg( $attachment_id );
			if ( ! empty( $svg_content ) ) {
				$svg_content       = self::add_svg_attributes( $svg_content, $additional_props, $flip_for_rtl );
				$sanitized_content = HtmlSanitizer::sanitize_svg( $svg_content );
				
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is sanitized by HtmlSanitizer::sanitize_svg().
				echo $sanitized_content;
			}
			return;
		}

		// Handle raw SVG content.
		if ( ! empty( $icon ) && is_string( $icon ) && strpos( $icon, '<svg' ) !== false ) {
			$svg_content = self::add_svg_attributes( $icon, $additional_props, $flip_for_rtl );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is sanitized by HtmlSanitizer::sanitize_svg()
			echo HtmlSanitizer::sanitize_svg( $svg_content );
			return;
		}
		
		// Handle FontAwesome icon names (existing logic).
		$icon = sanitize_text_field( esc_attr( $icon ) );
		$json = Core::backend_load_font_awesome_icons();

		if ( ! empty( $json ) ) {
			if ( empty( $icon_array_merged ) ) {
				foreach ( $json as $value ) {
					self::$icon_array_merged = array_merge( self::$icon_array_merged, $value );
				}
			}
			$json = self::$icon_array_merged;
		}

		$icon_brand_or_solid = isset( $json[ $icon ]['svg']['brands'] ) ? $json[ $icon ]['svg']['brands'] : ( isset( $json[ $icon ]['svg']['solid'] ) ? $json[ $icon ]['svg']['solid'] : array() );
		$path                = $icon_brand_or_solid['path'] ?? '';
		$view                = isset( $icon_brand_or_solid['width'] ) && isset( $icon_brand_or_solid['height'] ) ? '0 0 ' . $icon_brand_or_solid['width'] . ' ' . $icon_brand_or_solid['height'] : null;

		if ( $path && $view ) {
			// Build the class attribute, checking if spectra-icon is already in additional_props.
			$existing_classes = $additional_props['class'] ?? '';
			$class_array      = array_filter( explode( ' ', $existing_classes ) );
			
			// Add spectra-icon if it's not already present.
			if ( ! in_array( 'spectra-icon', $class_array, true ) ) {
				$class_array[] = 'spectra-icon';
			}
			
			$svg_classes = implode( ' ', $class_array );
			?>
			<svg class="<?php echo esc_attr( $svg_classes ); ?>" xmlns="https://www.w3.org/2000/svg" viewBox= "<?php echo esc_attr( $view ); ?>"
				<?php
				// If RTL inversion is required, mirror the SVG.
				$rtl_css_for_svg = array();
				if ( is_rtl() && $flip_for_rtl ) {
					$rtl_css_for_svg = array( 'transform' => 'scaleX(-1)' );
				}

				// If there are additional props, add them.
				if ( ! empty( $additional_props ) ) {

					// First check if we need to add the RTL styles.
					if ( ! empty( $rtl_css_for_svg ) ) {

						// If there is a style prop, then concatenate it with the RTL styles.
						if ( array_key_exists( 'style', $additional_props ) && is_array( $additional_props['style'] ) ) {

							// Check if there is a transform property already. If so, merge the scale of RTL into the transform property.
							if ( array_key_exists( 'transform', $additional_props['style'] ) && is_string( $additional_props['style']['transform'] ) ) {
								$additional_props['style']['transform'] = $rtl_css_for_svg['transform'] . ' ' . $additional_props['style']['transform'];
							} else {
								// Else just add the RTL transform to the style array.
								$additional_props['style'] = array_merge( $additional_props['style'], $rtl_css_for_svg );
							}
						} else {
							// Else just add the RTL css string if needed.
							$additional_props['style'] = $rtl_css_for_svg;
						}
					}

					// Add the additional props in a loop.  
					foreach ( $additional_props as $item => $details ) {
						// Skip the class attribute as it's already handled above.
						if ( 'class' === $item ) {
							continue;
						}
						
						// If this is the style attribute, then get the style string.
						if ( 'style' === $item ) {
							$rendered_styles = Core::concatenate_array( $details, 'style' );
							echo ' style="' . esc_attr( $rendered_styles ) . '"';
						} elseif ( ! empty( $details ) ) {
							// Else if there are details, then structure this attribute.
							echo ' ' . esc_attr( $item ) . '="' . esc_attr( $details ) . '"';
						}
					}
				} elseif ( ! empty( $rtl_css_for_svg ) ) {
					// If there are no additional props, but this is RTL, then just add transformation style.
					echo ' style="transform: scaleX(-1);"';
				}
				?>
			><path d="<?php echo esc_attr( $path ); ?>"></path></svg>
			<?php
		}
	}

	/**
	 * Render the background video component if required.
	 *
	 * @since 3.0.0
	 * @param array $background The background attribute.
	 * @return void
	 */
	public static function background_video( $background ) {

		// Get the required data from the background attribute.
		$background_type = $background['type'] ?? '';
		$background_url  = $background['media']['url'] ?? '';

		// If the background type is not a video, or the URL does not exist, abandon ship.
		if ( 'video' !== $background_type || empty( $background_url ) ) {
			return;
		}

		// Additional XSS prevention: Check for javascript: protocol and other dangerous schemes.
		$dangerous_schemes    = array( 'javascript:', 'data:', 'vbscript:', 'onload=', 'onerror=' );
		$background_url_lower = strtolower( $background_url );
		
		foreach ( $dangerous_schemes as $scheme ) {
			if ( strpos( $background_url_lower, $scheme ) !== false ) {
				return; // Abort if dangerous scheme is found.
			}
		}

		// Sanitize the URL with WordPress function and validate it's a proper URL.
		$sanitized_url = esc_url( $background_url );
		if ( empty( $sanitized_url ) || ! filter_var( $sanitized_url, FILTER_VALIDATE_URL ) ) {
			return;
		}

		// Create the required classes for the video wrapper.
		// Remove overlay class - let responsive CSS handle all overlay logic per breakpoint.
		$background_video_classes = 'spectra-background-video__wrapper spectra-overlay-color';

		// Create a separate element that appears before the actual children of this wrapper.
		?>
			<div class="<?php echo esc_attr( $background_video_classes ); ?>">
				<video role="presentation" aria-hidden="true" autoPlay loop muted playsinline>
					<source src="<?php echo esc_url( $sanitized_url ); ?>" type="video/mp4" />
				</video>
			</div>
		<?php
	}

	/**
	 * Get icon name for accessibility and other string contexts.
	 * 
	 * @since 3.0.0
	 * @param mixed $icon_value The icon value (string for icon library, array for custom SVG).
	 * @return string The icon name or description.
	 */
	public static function get_icon_name( $icon_value ) {
		if ( empty( $icon_value ) ) {
			return 'star';
		}
		
		// Handle custom SVG uploads (array format).
		if ( is_array( $icon_value ) && isset( $icon_value['library'] ) && 'svg' === $icon_value['library'] ) {
			// Try to extract filename from URL.
			if ( isset( $icon_value['value']['url'] ) ) {
				$url      = $icon_value['value']['url'];
				$filename = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_FILENAME );
				return ! empty( $filename ) ? $filename : 'custom SVG';
			}
			return 'custom SVG';
		}
		
		// Handle icon library icons (string format).
		if ( is_string( $icon_value ) ) {
			return $icon_value;
		}
		
		return 'star';
	}

	/**
	 * Get uploaded SVG content
	 * 
	 * @since 3.0.0
	 * @param int $attachment_id The WordPress media attachment ID.
	 * @return string SVG content or empty string if file not found.
	 */
	private static function get_uploaded_svg( $attachment_id ) {
		$attachment_id = intval( $attachment_id );
		
		if ( $attachment_id <= 0 ) {
			return '';
		}

		// Get SVG from file.
		$attachment_file = get_attached_file( $attachment_id );
		if ( ! file_exists( $attachment_file ) ) {
			return '';
		}

		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reading local uploaded file, not remote URL
		$svg_content = file_get_contents( $attachment_file );
		
		if ( empty( $svg_content ) ) {
			return '';
		}
		
		return $svg_content; // Return raw content, HtmlSanitizer will handle sanitization.
	}



	/**
	 * Add attributes to SVG without complex processing
	 * 
	 * @since 3.0.0
	 * @param string  $svg_content SVG content.
	 * @param array   $additional_props Additional props.
	 * @param boolean $flip_for_rtl Whether to apply RTL transformation.
	 * @return string
	 */
	private static function add_svg_attributes( $svg_content, $additional_props = array(), $flip_for_rtl = false ) {
		// Build complete class list including additional props.
		$classes = array( 'spectra-icon', 'spectra-custom-svg' );
		
		// Add classes from additional props if provided.
		if ( isset( $additional_props['class'] ) && ! empty( $additional_props['class'] ) ) {
			$additional_classes = explode( ' ', $additional_props['class'] );
			$classes            = array_merge( $classes, $additional_classes );
		}
		
		// Remove duplicates and empty values.
		$classes      = array_unique( array_filter( $classes ) );
		$class_string = implode( ' ', $classes );
		
		// Apply classes to SVG.
		if ( strpos( $svg_content, 'class=' ) !== false ) {
			// Replace existing class attribute.
			$svg_content = preg_replace( '/class="([^"]*)"/', 'class="$1 ' . esc_attr( $class_string ) . '"', $svg_content );
		} else {
			// Add new class attribute.
			$svg_content = str_replace( '<svg', '<svg class="' . esc_attr( $class_string ) . '"', $svg_content );
		}
		
		// Handle RTL transformation for custom SVGs.
		if ( is_rtl() && $flip_for_rtl ) {
			// Add RTL transform to additional_props style array.
			if ( ! isset( $additional_props['style'] ) ) {
				$additional_props['style'] = array();
			}
			if ( ! is_array( $additional_props['style'] ) ) {
				$additional_props['style'] = array();
			}
			
			// Check if there's already a transform, if so merge it.
			$rtl_transform = 'scaleX(-1)';
			if ( isset( $additional_props['style']['transform'] ) && ! empty( $additional_props['style']['transform'] ) ) {
				$additional_props['style']['transform'] = $rtl_transform . ' ' . $additional_props['style']['transform'];
			} else {
				$additional_props['style']['transform'] = $rtl_transform;
			}
		}
		
		// Add additional attributes if provided.
		if ( ! empty( $additional_props ) ) {
			foreach ( $additional_props as $attr => $value ) {
				if ( ! empty( $value ) && 'class' !== $attr ) { // class already handled above.
					// For uploaded SVGs, avoid overriding fill attribute to preserve original colors.
					if ( 'fill' === $attr && 'currentColor' === $value ) {
						// Check if SVG already has fill/stroke attributes - if so, don't override.
						if ( preg_match( '/fill\s*=|stroke\s*=/', $svg_content ) ) {
							continue; // Skip adding fill="currentColor" for uploaded SVGs with existing colors.
						}
					}
					// Check if attribute already exists to avoid duplicates (which cause XML parsing errors).
					$attr_pattern = '/' . preg_quote( $attr, '/' ) . '\s*=/i';
					if ( preg_match( $attr_pattern, $svg_content ) ) {
						// Skip adding duplicate attributes.
						continue;
					}
					// Handle style attribute specially.
					if ( 'style' === $attr && is_array( $value ) ) {
						// Check if SVG has fill="none" attribute - if so, don't add fill:currentColor.
						$has_fill_none = preg_match( '/<svg[^>]+fill\s*=\s*["\']none["\']/i', $svg_content );

						if ( $has_fill_none && isset( $value['fill'] ) ) {
							// Remove fill:currentColor from the value array to respect the SVG's fill="none".
							unset( $value['fill'] );
						}

						// Build the new styles (only transform, etc - not fill if SVG has fill="none").
						$new_styles = Core::concatenate_array( $value, 'style' );

						// Check if SVG already has a style attribute.
						if ( preg_match( '/<svg([^>]*?)style\s*=\s*["\']([^"\']*)["\']([^>]*?)>/i', $svg_content ) ) {
							// Replace the existing style attribute value with new value.
							$svg_content = preg_replace(
								'/(<svg[^>]*?)style\s*=\s*["\']([^"\']*)["\']([^>]*?>)/i',
								'$1style="' . esc_attr( $new_styles ) . '"$3',
								$svg_content
							);
						} else {
							// No existing style attribute - add new one if there are styles to add.
							if ( ! empty( $new_styles ) ) {
								$svg_content = str_replace( '<svg', '<svg style="' . esc_attr( $new_styles ) . '"', $svg_content );
							}
						}
					} else {
						// Check if attribute already exists to avoid duplicates (which cause XML parsing errors).
						// Note: We skip this check for 'style' since it's handled specially above.
						$attr_pattern = '/' . preg_quote( $attr, '/' ) . '\s*=/i';
						if ( ! preg_match( $attr_pattern, $svg_content ) ) {
							// Attribute doesn't exist - add it.
							$svg_content = str_replace( '<svg', '<svg ' . esc_attr( $attr ) . '="' . esc_attr( $value ) . '"', $svg_content );
						}
					}
				}
			}
		}
		
		return $svg_content;
	}
}
