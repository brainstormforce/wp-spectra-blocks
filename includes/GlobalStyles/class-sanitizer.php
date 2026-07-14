<?php
/**
 * Sanitizers for Global Styles CSS payloads.
 *
 * Centralizes CSS property/value, JSON, and keyframe sanitization used by
 * the REST controller (and any other caller that accepts user-supplied CSS).
 *
 * @package Spectra\GlobalStyles
 * @since   1.0.0
 */

namespace SpectraBlocks\GlobalStyles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sanitizer.
 *
 * @since 1.0.0
 */
class Sanitizer {

	/**
	 * Sanitize a CSS property name.
	 *
	 * Allows standard properties and CSS custom properties (--var-name).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $property The CSS property name.
	 * @return string
	 */
	public static function sanitize_css_property( $property ): string {
		if ( ! is_string( $property ) ) {
			return '';
		}

		$property = trim( $property );

		if ( preg_match( '/^-{0,2}[a-zA-Z][a-zA-Z0-9-]*$/', $property ) ) {
			return strtolower( $property );
		}

		return '';
	}

	/**
	 * Sanitize a CSS value while preserving valid CSS syntax.
	 *
	 * More permissive than sanitize_text_field() to allow CSS functions,
	 * units, punctuation, and quoted strings — while blocking script/URL
	 * injection patterns.
	 *
	 * When `$strict` is true (used for user-supplied payloads and JIT bracket
	 * tokens), any `var()` usage is rejected. The engine's own colour utility
	 * emission path — which legitimately emits `var(--spectra-chromatic1-6)`
	 * et al. from the Style Guide palette — passes `$strict = false`.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value  The CSS value to sanitize.
	 * @param bool  $strict When true, rejects any `var(...)` usage.
	 * @return string
	 */
	public static function sanitize_css_value( $value, bool $strict = false ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );
		$value = str_replace( chr( 0 ), '', $value );

		$dangerous_patterns = array(
			'/javascript\s*:/i',
			'/expression\s*\(/i',
			'/behavior\s*:/i',
			'/-moz-binding\s*:/i',
			'/vbscript\s*:/i',
			'/<\s*script/i',
			'/<\s*\/\s*script/i',
			'/on\w+\s*=/i',
			'/data\s*:\s*text\/html/i',
		);

		foreach ( $dangerous_patterns as $pattern ) {
			if ( preg_match( $pattern, $value ) ) {
				return '';
			}
		}

		if ( $strict && preg_match( '/\bvar\s*\(/i', $value ) ) {
			return '';
		}

		// REMOVED: wp_strip_all_tags( $value ) — was destroying SVG data URLs.
		// CSS values can legitimately contain `<svg>...</svg>` markup inside
		// `url('data:image/svg+xml;utf8,...')`. wp_strip_all_tags() treats
		// these as HTML tags and rips them out, leaving a truncated unclosed
		// `url('data:image/svg+xml;utf8,` that breaks the browser CSS parser
		// and silently drops every subsequent rule in the inline stylesheet.
		// XSS surface is already covered by the dangerous_patterns regex
		// above (blocks <script>, javascript:, expression(), etc.) and the
		// character whitelist below.

		$value = preg_replace( '/[^\w\s\-\.\#\%\(\)\,\'\"\:\;\/\!\@\+\*\=\[\]\{\}\<\>\_\|\&\^\~\`\$]/u', '', $value );

		$max_length = 2000;
		if ( strlen( (string) $value ) > $max_length ) {
			$value = substr( (string) $value, 0, $max_length );
		}

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Recursively sanitize decoded JSON using CSS-aware rules at deeper levels.
	 *
	 * At depth >= 2, keys are treated as CSS properties and values as CSS values.
	 * Strict mode (the default) rejects any `var(...)` references — user-supplied
	 * custom classes and keyframes must not reference CSS variables.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int|string, mixed> $json_data Decoded JSON.
	 * @param int                      $depth     Current recursion depth.
	 * @param bool                     $strict    Reject `var(...)` in values at depth >= 2.
	 * @return array<int|string, mixed>
	 */
	public static function recursively_sanitize_json( array $json_data, int $depth = 0, bool $strict = true ): array {
		$sanitized = array();

		foreach ( $json_data as $key => $value ) {
			if ( is_int( $key ) ) {
				$clean_key = $key;
			} elseif ( $depth >= 2 ) {
				$clean_key = self::sanitize_css_property( $key );
				if ( '' === $clean_key ) {
					continue;
				}
			} else {
				$clean_key = sanitize_text_field( $key );
			}

			if ( is_array( $value ) ) {
				$sanitized[ $clean_key ] = self::recursively_sanitize_json( $value, $depth + 1, $strict );
			} elseif ( is_object( $value ) ) {
				$sanitized[ $clean_key ] = self::recursively_sanitize_json( (array) $value, $depth + 1, $strict );
			} elseif ( $depth >= 2 ) {
				$sanitized[ $clean_key ] = self::sanitize_css_value( $value, $strict );
			} else {
				$sanitized[ $clean_key ] = sanitize_text_field( (string) $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize a JSON string or array payload.
	 *
	 * Accepts either a JSON string (to decode) or an already-decoded array.
	 * Strict mode (default) rejects `var(...)` references.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $input      JSON string or array.
	 * @param bool  $strict     Reject `var(...)` in CSS values.
	 * @param int   $base_depth Depth the top-level payload starts at. Callers
	 *                          whose payload is already unwrapped one level
	 *                          (e.g. per-class styles `{bucket:{prop:value}}`,
	 *                          where the value sits at depth 1) pass `1` so the
	 *                          CSS-aware value rules (var rejection, char
	 *                          whitelist, length cap) — which apply at depth >= 2
	 *                          — still reach the values. Defaults to `0`.
	 * @return array<int|string, mixed>
	 */
	public static function sanitize_json( $input, bool $strict = true, int $base_depth = 0 ): array {
		if ( is_array( $input ) ) {
			return self::recursively_sanitize_json( $input, $base_depth, $strict );
		}

		if ( ! is_string( $input ) ) {
			return array();
		}

		$decoded = json_decode( wp_unslash( $input ), true );
		if ( null === $decoded || ! is_array( $decoded ) ) {
			return array();
		}

		return self::recursively_sanitize_json( $decoded, $base_depth, $strict );
	}

	/**
	 * Sanitize keyframe data payload.
	 *
	 * Accepts the raw-CSS format ({css, meta}).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $input JSON string or array.
	 * @return array{css:string, meta: array{defaultDuration:string, defaultEasing:string, defaultIterations:string}}
	 */
	public static function sanitize_keyframe_data( $input ): array {
		$data = $input;

		if ( is_string( $data ) ) {
			$data = json_decode( stripslashes( $data ), true );
		}

		if ( ! is_array( $data ) ) {
			return array(
				'css'  => '',
				'meta' => self::default_keyframe_meta(),
			);
		}

		$sanitized = array();

		if ( isset( $data['css'] ) && is_string( $data['css'] ) ) {
			$sanitized['css'] = self::sanitize_keyframe_css( $data['css'] );
		} else {
			$sanitized['css'] = '';
		}

		if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
			$sanitized['meta'] = array(
				'defaultDuration'   => self::sanitize_animation_duration( $data['meta']['defaultDuration'] ?? '0.3s' ),
				'defaultEasing'     => self::sanitize_animation_easing( $data['meta']['defaultEasing'] ?? 'ease-out' ),
				'defaultIterations' => self::sanitize_animation_iterations( $data['meta']['defaultIterations'] ?? '1' ),
			);
		} else {
			$sanitized['meta'] = self::default_keyframe_meta();
		}

		return $sanitized;
	}

	/**
	 * Default keyframe meta values.
	 *
	 * @since 1.0.0
	 *
	 * @return array{defaultDuration:string, defaultEasing:string, defaultIterations:string}
	 */
	private static function default_keyframe_meta(): array {
		return array(
			'defaultDuration'   => '0.3s',
			'defaultEasing'     => 'ease-out',
			'defaultIterations' => '1',
		);
	}

	/**
	 * Sanitize animation duration (e.g. "0.3s", "300ms").
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $duration The duration value.
	 * @return string
	 */
	public static function sanitize_animation_duration( $duration ): string {
		if ( empty( $duration ) || ! is_string( $duration ) ) {
			return '0.3s';
		}

		$duration = trim( $duration );

		if ( preg_match( '/^(\d+(?:\.\d+)?)(s|ms)$/i', $duration, $matches ) ) {
			$value = floatval( $matches[1] );
			$unit  = strtolower( $matches[2] );

			if ( 's' === $unit && $value >= 0 && $value <= 30 ) {
				return $value . 's';
			}
			if ( 'ms' === $unit && $value >= 0 && $value <= 30000 ) {
				return intval( $value ) . 'ms';
			}
		}

		return '0.3s';
	}

	/**
	 * Sanitize animation easing keyword or function.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $easing The easing value.
	 * @return string
	 */
	public static function sanitize_animation_easing( $easing ): string {
		if ( empty( $easing ) || ! is_string( $easing ) ) {
			return 'ease-out';
		}

		$easing = trim( $easing );

		$allowed_keywords = array( 'linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out', 'step-start', 'step-end' );
		if ( in_array( strtolower( $easing ), $allowed_keywords, true ) ) {
			return strtolower( $easing );
		}

		if ( preg_match( '/^cubic-bezier\s*\(\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*\)$/i', $easing, $matches ) ) {
			$p1 = floatval( $matches[1] );
			$p2 = floatval( $matches[2] );
			$p3 = floatval( $matches[3] );
			$p4 = floatval( $matches[4] );

			if ( $p1 >= 0 && $p1 <= 1 && $p3 >= 0 && $p3 <= 1 && abs( $p2 ) <= 10 && abs( $p4 ) <= 10 ) {
				return sprintf( 'cubic-bezier(%s, %s, %s, %s)', $p1, $p2, $p3, $p4 );
			}
		}

		if ( preg_match( '/^steps\s*\(\s*(\d+)\s*(?:,\s*(start|end|jump-start|jump-end|jump-none|jump-both))?\s*\)$/i', $easing, $matches ) ) {
			$steps    = intval( $matches[1] );
			$position = isset( $matches[2] ) ? strtolower( $matches[2] ) : 'end';

			if ( $steps >= 1 && $steps <= 100 ) {
				return sprintf( 'steps(%d, %s)', $steps, $position );
			}
		}

		return 'ease-out';
	}

	/**
	 * Sanitize animation iteration count.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $iterations The iteration count value.
	 * @return string
	 */
	public static function sanitize_animation_iterations( $iterations ): string {
		if ( '' === $iterations || null === $iterations ) {
			return '1';
		}

		$iterations = trim( (string) $iterations );

		if ( 'infinite' === strtolower( $iterations ) ) {
			return 'infinite';
		}

		if ( preg_match( '/^(\d+(?:\.\d+)?)$/', $iterations, $matches ) ) {
			$value = floatval( $matches[1] );
			if ( $value >= 0 && $value <= 1000 ) {
				return ( floor( $value ) === $value ) ? strval( intval( $value ) ) : strval( $value );
			}
		}

		return '1';
	}

	/**
	 * Sanitize raw keyframe CSS content (without the @keyframes wrapper).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $css The raw CSS content.
	 * @return string
	 */
	public static function sanitize_keyframe_css( $css ): string {
		if ( empty( $css ) || ! is_string( $css ) ) {
			return '';
		}

		$css = (string) preg_replace( '/@keyframes\s+[\w-]+\s*\{/i', '', $css );
		$css = wp_strip_all_tags( $css );

		$pattern = '/(?:from|to|\d{1,3}%)\s*(?:,\s*(?:from|to|\d{1,3}%))*\s*\{([^}]*)\}/i';
		if ( ! preg_match_all( $pattern, $css, $matches, PREG_SET_ORDER ) ) {
			return '';
		}

		$blocks = array();
		foreach ( $matches as $match ) {
			$full_match   = $match[0];
			$declarations = isset( $match[1] ) ? $match[1] : '';

			if ( preg_match( '/^((?:from|to|\d{1,3}%)(?:\s*,\s*(?:from|to|\d{1,3}%))*)/i', trim( $full_match ), $pos_match ) ) {
				$position = self::sanitize_keyframe_positions( $pos_match[1] );
			} else {
				continue;
			}

			if ( '' === $position ) {
				continue;
			}

			$sanitized_declarations = self::sanitize_keyframe_declarations( $declarations );
			if ( '' !== $sanitized_declarations ) {
				$blocks[] = $position . " {\n" . $sanitized_declarations . "\n}";
			}
		}

		return implode( "\n", $blocks );
	}

	/**
	 * Sanitize a comma-separated list of keyframe positions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $positions The positions string.
	 * @return string
	 */
	private static function sanitize_keyframe_positions( string $positions ): string {
		$parts     = array_map( 'trim', explode( ',', $positions ) );
		$sanitized = array();

		foreach ( $parts as $part ) {
			$clean = self::sanitize_keyframe_position( $part );
			if ( '' !== $clean ) {
				$sanitized[] = $clean;
			}
		}

		return implode( ', ', array_unique( $sanitized ) );
	}

	/**
	 * Sanitize a single keyframe position (from|to|0–100%).
	 *
	 * @since 1.0.0
	 *
	 * @param string $position The position value.
	 * @return string
	 */
	private static function sanitize_keyframe_position( string $position ): string {
		if ( in_array( $position, array( 'from', 'to' ), true ) ) {
			return $position;
		}

		if ( preg_match( '/^([0-9]{1,3})%$/', $position, $matches ) ) {
			$percent = intval( $matches[1] );
			if ( $percent >= 0 && $percent <= 100 ) {
				return $percent . '%';
			}
		}

		return '0%';
	}

	/**
	 * Sanitize the declarations inside a single keyframe block.
	 *
	 * @since 1.0.0
	 *
	 * @param string $declarations The declaration string.
	 * @return string
	 */
	private static function sanitize_keyframe_declarations( string $declarations ): string {
		if ( '' === $declarations ) {
			return '';
		}

		$parts     = array_filter( array_map( 'trim', explode( ';', $declarations ) ) );
		$sanitized = array();

		foreach ( $parts as $declaration ) {
			if ( false === strpos( $declaration, ':' ) ) {
				continue;
			}

			list( $property, $value ) = array_map( 'trim', explode( ':', $declaration, 2 ) );

			$clean_property = self::sanitize_css_property( $property );
			if ( '' === $clean_property ) {
				continue;
			}

			$clean_value = self::sanitize_css_value( $value, true );
			if ( '' === $clean_value ) {
				continue;
			}

			$sanitized[] = "\t" . $clean_property . ': ' . $clean_value . ';';
		}

		return implode( "\n", $sanitized );
	}
}
