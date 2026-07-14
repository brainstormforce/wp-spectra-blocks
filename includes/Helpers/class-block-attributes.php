<?php
/**
 * The Spectra Block Attributes Helper.
 *
 * @package Spectra\Helpers
 */

namespace SpectraBlocks\Helpers;

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
	 * @param string $text Input string (e.g., 'textSecondaryColor').
	 * @return string Kebab-case string (e.g., 'text-secondary-color').
	 */
	private static function to_kebab_case( $text ) {
		// If the input is not a string or empty, return an empty string.
		if ( ! is_string( $text ) || empty( $text ) ) {
			return '';
		}

		// Step 1: Insert a hyphen before each uppercase letter (except if it's the first character).
		// Example: "textSecondaryColor" becomes "text-Secondary-Color" (intermediate result).
		$text = preg_replace( '/(?<!^)([A-Z0-9])/', '-$1', $text );

		// Step 2: Convert the intermediate result to lowercase and replace any underscores with hyphens.
		// Final output: "text-Secondary-Color" becomes "text-secondary-color".
		return strtolower( str_replace( '_', '-', $text ) );
	}

	/**
	 * Convert WordPress preset format to proper CSS var() syntax.
	 * Handles: 'var:preset|type|slug' → 'var(--wp--preset--type--slug)'
	 * and a bare colour-preset slug → 'var(--wp--preset--color--slug)'.
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

		// Handle a bare colour-preset slug (e.g. 'vivid-green-cyan'). Colour
		// controls store the palette slug rather than a hex value so the colour
		// stays themeable, but a bare slug is not valid CSS — resolve it to the
		// matching WordPress preset custom property. Only values that are
		// actually registered palette slugs are converted, so genuine CSS
		// colour keywords (e.g. 'red', 'transparent', 'currentColor') and raw
		// values (hex, rgb(), hsl(), var(...)) pass through untouched.
		if ( self::is_color_palette_slug( $value ) ) {
			return 'var(--wp--preset--color--' . $value . ')';
		}

		return $value;
	}

	/**
	 * Determine whether a value is a registered colour-palette slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The value to test.
	 * @return bool True when the value matches a registered colour-palette slug.
	 */
	private static function is_color_palette_slug( $value ) {
		// A slug is lowercase alphanumeric words joined by single hyphens.
		// Anything else (hex, rgb(), var(...), spaces, uppercase) is not a slug.
		if ( ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value ) ) {
			return false;
		}

		$slugs = self::get_color_palette_slugs();

		return isset( $slugs[ $value ] );
	}

	/**
	 * Get the set of registered colour-palette slugs across all origins
	 * (default, theme, custom). Cached for the duration of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,bool> Map of slug => true for O(1) lookups.
	 */
	private static function get_color_palette_slugs() {
		static $slugs = null;

		if ( null !== $slugs ) {
			return $slugs;
		}

		$slugs = array();

		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return $slugs;
		}

		$palette = wp_get_global_settings( array( 'color', 'palette' ) );

		if ( ! is_array( $palette ) ) {
			return $slugs;
		}

		// wp_get_global_settings() returns the palette grouped by origin
		// (default/theme/custom) when multiple origins exist, or a flat list
		// otherwise. Support both shapes.
		$groups = array();
		if ( isset( $palette['default'] ) || isset( $palette['theme'] ) || isset( $palette['custom'] ) ) {
			foreach ( array( 'default', 'theme', 'custom' ) as $origin ) {
				if ( ! empty( $palette[ $origin ] ) && is_array( $palette[ $origin ] ) ) {
					$groups[] = $palette[ $origin ];
				}
			}
		} else {
			$groups[] = $palette;
		}

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}
			foreach ( $group as $color ) {
				if ( ! empty( $color['slug'] ) && is_string( $color['slug'] ) ) {
					$slugs[ $color['slug'] ] = true;
				}
			}
		}

		return $slugs;
	}

	/**
	 * Helper-class families that are superseded when the block's `className`
	 * already carries a GBS JIT token on the matching utility axis.
	 *
	 * The gate in {@see self::should_emit_helper_class()} inspects the
	 * `className` attribute and returns false for any key listed here when a
	 * corresponding `text-*` / `bg-*` / `border-*` token is present (including
	 * bracket escapes such as `text-[#f59e0b]`). This prevents the legacy
	 * `spectra-text-color` / `spectra-background-color` wrapper helpers from
	 * being re-emitted on server-render when the GBS engine already owns that
	 * surface via a utility class — the cause of the ~74x per-page
	 * `spectra-text-color` duplication tracked in GIT-106.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private const SUPERSEDING_UTILITY_AXES = array(
		'textColor'            => 'text',
		'textColorHover'       => 'text',
		'textSecondaryColor'   => 'text',
		'backgroundColor'      => 'bg',
		'backgroundColorHover' => 'bg',
		'borderColor'          => 'border',
		'borderColorHover'     => 'border',
	);

	/**
	 * Known non-color keyword values on the `text-` axis. When the className
	 * carries one of these as `text-<keyword>` (e.g. `text-xs`, `text-2xl`),
	 * the token is a FONT-SIZE utility — not a color. Excluded from the
	 * color-helper suppression gate so `spectra-text-color` still emits.
	 *
	 * @var array<int, string>
	 */
	private const NON_COLOR_TEXT_KEYWORDS = array(
		'xs',
		'sm',
		'base',
		'lg',
		'xl',
		'2xl',
		'3xl',
		'4xl',
		'5xl',
		'6xl',
		'7xl',
		'8xl',
		'9xl',
		// Tailwind text alignment / decoration / transform utilities
		// share the `text-` prefix but aren't colors:.
		'left',
		'center',
		'right',
		'justify',
		'start',
		'end',
		'wrap',
		'nowrap',
		'balance',
		'pretty',
	);

	/**
	 * Determine whether a helper class should be emitted for a given attribute.
	 *
	 * When the GBS JIT has already resolved the same visual axis via a utility
	 * token on the block's `className` (e.g. `text-chromatic1-6`,
	 * `text-[#f59e0b]`, `text-primary`), re-emitting the legacy
	 * `spectra-text-color` / `spectra-background-color` helper is redundant
	 * output pollution. This gate returns false in that case.
	 *
	 * Bracket escapes (`text-[...]`, `bg-[...]`, `border-[...]`) are matched
	 * non-greedily so they survive square-bracket content without breaking the
	 * token boundary. Standard utilities match `axis-<ident>` where `<ident>`
	 * is any kebab/alnum run.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key           Block attribute key driving the emission
	 *                              (e.g. `textColor`).
	 * @param string $class_name    The helper class-name candidate (e.g.
	 *                              `spectra-text-color`). Emission is only
	 *                              suppressed when this starts with `spectra-`
	 *                              — other custom class names pass through.
	 * @param string $existing_class Raw `className` attribute value from the
	 *                               block payload.
	 * @return bool True when the helper should be emitted, false when a GBS
	 *              utility already covers the axis.
	 */
	private static function should_emit_helper_class( string $key, string $class_name, string $existing_class ): bool {
		if ( '' === $existing_class ) {
			return true;
		}

		if ( 0 !== strpos( $class_name, 'spectra-' ) ) {
			return true;
		}

		if ( ! isset( self::SUPERSEDING_UTILITY_AXES[ $key ] ) ) {
			return true;
		}

		$axis = self::SUPERSEDING_UTILITY_AXES[ $key ];

		// Tokenise on whitespace; preserves bracket escapes as single tokens.
		$tokens = preg_split( '/\s+/', trim( $existing_class ) );
		if ( ! is_array( $tokens ) ) {
			return true;
		}

		$bracket_prefix = $axis . '-[';

		foreach ( $tokens as $token ) {
			if ( '' === $token ) {
				continue;
			}

			// Bracket-escape form: `text-[#hex]`, `bg-[rgb(...)]`, etc.
			if ( 0 === strpos( $token, $bracket_prefix ) && substr( $token, -1 ) === ']' ) {
				// On the `text-` axis, bracket tokens are ambiguous: Tailwind
				// uses the same `text-[...]` prefix for both font-size
				// (`text-[16px]`, `text-[clamp(...)]`) and color
				// (`text-[#fff]`, `text-[rgb(...)]`). Length/length-returning
				// values are NOT colors — skip them so the color helper
				// (`spectra-text-color`) still emits and the inline
				// `--spectra-text-color` CSS var is actually consumed.
				if ( 'text' === $axis ) {
					$value = substr( $token, strlen( $bracket_prefix ), -1 );
					if ( self::is_length_value( $value ) ) {
						continue;
					}
				}
				return false;
			}

			// Standard utility form: `text-chromatic1-6`, `bg-primary`, etc.
			// Requires at least one non-empty ident segment after `axis-`.
			if ( preg_match( '/^' . preg_quote( $axis, '/' ) . '-[a-z0-9][a-z0-9-]*$/i', $token ) ) {
				// On the `text-` axis, skip size/alignment/wrap keywords
				// (`text-xs`, `text-2xl`, `text-center`, `text-nowrap`, etc.)
				// — they're not color utilities, so the color helper must
				// still emit.
				if ( 'text' === $axis ) {
					$bare = substr( $token, strlen( 'text-' ) );
					if ( in_array( $bare, self::NON_COLOR_TEXT_KEYWORDS, true ) ) {
						continue;
					}
				}
				return false;
			}
		}

		return true;
	}

	/**
	 * Decide whether a bracket-utility value is a CSS length / length-returning
	 * expression rather than a color. Used to distinguish `text-[16px]` /
	 * `text-[clamp(...)]` (sizes — keep the color helper) from `text-[#hex]` /
	 * `text-[rgb(...)]` (colors — suppress the redundant helper).
	 *
	 * Matches:
	 *   - number + length unit:       `16px`, `1.5rem`, `2em`, `100%`, `50vh`, `80vw`, `1ch`, `2ex`
	 *   - CSS length-returning fn:    `clamp(...)`, `calc(...)`, `min(...)`, `max(...)`
	 *
	 * Does NOT match bare colors (`#hex`, `rgb(...)`, `hsl(...)`) nor
	 * `var(...)` references (which may resolve to either). Being conservative
	 * here errs toward "keep the helper emitted" when the value is ambiguous.
	 *
	 * @param string $value Raw value inside the brackets.
	 * @return bool True when the value is a length or length-returning expression.
	 */
	private static function is_length_value( string $value ): bool {
		return 1 === preg_match(
			'/^(?:\d+(?:\.\d+)?(?:px|rem|em|%|vh|vw|ch|ex)|(?:clamp|calc|min|max)\()/i',
			$value
		);
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

		$existing_class_name = isset( $attributes['className'] ) && is_string( $attributes['className'] )
			? $attributes['className']
			: '';

		$key        = null;
		$css_var    = null;
		$class_name = null;
		$value      = null;
		$default    = null;

		foreach ( $configs as $config ) {
			if ( is_string( $config ) ) {
				$key        = $config;
				$css_var    = '--spectra-' . self::to_kebab_case( $key );
				$class_name = 'spectra-' . self::to_kebab_case( $key );
				$default    = null;
			} else {
				$key = $config['key'] ?? null;

				if ( ! $key ) {
					continue;
				}

				$css_var    = isset( $config['css_var'] ) ? $config['css_var'] : ( $key ? '--spectra-' . self::to_kebab_case( $key ) : null ); // Ternary operator is because of $config['css_var'] can be null value.
				$class_name = isset( $config['class_name'] ) ? $config['class_name'] : ( $key ? 'spectra-' . self::to_kebab_case( $key ) : null ); // Ternary operator is because of $config['class_name'] can be null value.
				$value      = $config['value'] ?? null;
				$default    = array_key_exists( 'default', $config ) ? $config['default'] : null;
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

			// Skip when the final value matches this config's declared default —
			// emitting an inline style for a default would override any class-
			// based utility the author set (inline style beats class selectors
			// in specificity). Authors who actually want the default can rely
			// on the block's base CSS; the author-set overrides win instead.
			if ( ! is_null( $default ) && $final_value === $default ) {
				continue;
			}

			// Convert WordPress preset format (var:preset|type|slug) to CSS var().
			$final_value = self::convert_wordpress_preset( $final_value );

			// Add styles if css_var isn't null.
			if ( ! is_null( $css_var ) ) {
				$styles[ $css_var ] = esc_attr( $final_value );
			}

			// Add if class_name isn't null and the helper isn't already superseded
			// by an equivalent GBS JIT utility token on the block's className.
			if ( ! is_null( $class_name ) && self::should_emit_helper_class( (string) $key, $class_name, $existing_class_name ) ) {
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

		// Separate CSS custom properties (--*) from regular styles before passing to
		// get_block_wrapper_attributes(). WP's implementation calls safecss_filter_attr() on
		// the merged 'style' value, which in WP 7.0+ strips CSS custom property gradient values
		// that use color functions beyond rgb()/rgba() (e.g. hsl(), oklch(), var(--wp--preset--)).
		// We bypass this by re-injecting our custom properties directly after the call.
		$custom_props   = array();
		$regular_styles = array();
		foreach ( $styles as $prop => $value ) {
			if ( str_starts_with( (string) $prop, '--' ) ) {
				$custom_props[ $prop ] = $value;
			} else {
				$regular_styles[ $prop ] = $value;
			}
		}

		$wrapper_attrs = array(
			'style' => Core::concatenate_array( $regular_styles, 'style' ),
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

		// 2026-05-18: pipe source-authored HTML attrs (role/aria/tabindex/
		// data-*/title/lang/dir/etc.) through to the wrapper. Skip event
		// handlers (on*) and the cascade-owning attrs (class/id/style)
		// that have dedicated paths above. wp_kses_data on the rendered
		// string strips javascript: URLs as the second line of defence.
		if ( isset( $attributes['htmlAttributes'] ) && is_array( $attributes['htmlAttributes'] ) ) {
			foreach ( $attributes['htmlAttributes'] as $name => $value ) {
				$name = strtolower( (string) $name );
				if ( '' === $name || str_starts_with( $name, 'on' ) || in_array( $name, array( 'class', 'id', 'style' ), true ) ) {
					continue;
				}
				if ( is_scalar( $value ) ) {
					$wrapper_attrs[ $name ] = (string) $value;
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

		// Re-inject CSS custom properties that were excluded above to avoid safecss_filter_attr() stripping.
		// Values are already esc_attr()-safe (produced by generate_styles_and_classes() or passed as $custom_style).
		if ( ! empty( $custom_props ) ) {
			$parts = array();
			foreach ( $custom_props as $prop => $value ) {
				$parts[] = $prop . ': ' . esc_attr( $value );
			}
			$inject    = implode( '; ', $parts ) . '; ';
			$style_pos = strpos( $wrapper_attributes_string, 'style="' );
			if ( false !== $style_pos ) {
				$insert_at                 = $style_pos + strlen( 'style="' );
				$wrapper_attributes_string = substr( $wrapper_attributes_string, 0, $insert_at )
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS custom properties already esc_attr()-escaped above.
					. $inject
					. substr( $wrapper_attributes_string, $insert_at );
			} else {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS custom properties already esc_attr()-escaped above.
				$wrapper_attributes_string .= ' style="' . rtrim( $inject, ' ' ) . '"';
			}
		}

		return $wrapper_attributes_string;
	}
}
