<?php
/**
 * Shade Generator — Layer 1 of the Style Guide pipeline.
 *
 * Generates color shades using OKLCH perceptual color space.
 * Unlike RGB white-mix, OKLCH produces visually consistent lightness
 * across all hues — a blue shade and a yellow shade at the same
 * ratio look equally bright to the human eye.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.0
 */

namespace SpectraBlocks\StyleGuide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ShadeGenerator
 *
 * @since 1.0.0
 */
class ShadeGenerator {

	/**
	 * White-mix ratios for neutral shades (8 shades).
	 * 1.0 = pure white, 0.0 = original color unchanged.
	 *
	 * @since 1.0.0
	 * @var float[]
	 */
	const NEUTRAL_RATIOS = array( 1.0, 0.95, 0.85, 0.70, 0.50, 0.30, 0.10, 0.0 );

	/**
	 * White-mix ratios for chromatic shades (7 shades).
	 * No pure-white shade — lightest is 95% white mix.
	 *
	 * @since 1.0.0
	 * @var float[]
	 */
	const CHROMATIC_RATIOS = array( 0.95, 0.85, 0.70, 0.50, 0.30, 0.10, 0.0 );

	/**
	 * Black-mix ratios for chromatic dark shades (4 shades, indices 8-11).
	 * Mirrors the white-mix curve in reverse — 1.0 = pure black, 0.0 = original.
	 *
	 * @since 1.0.0
	 * @var float[]
	 */
	const BLACK_RATIOS = array( 0.10, 0.30, 0.50, 0.70 );

	/**
	 * Opacity percentage levels for transparency tokens.
	 *
	 * @since 1.0.0
	 * @var int[]
	 */
	const OPACITY_LEVELS = array( 5, 10, 15, 20, 30, 40, 50, 60 );

	/**
	 * Saturation presets — power curve for chroma reduction during shade generation.
	 *
	 * Lower values preserve more chroma in lighter shades (vivid pastels).
	 * Higher values reduce chroma faster (muted, desaturated pastels).
	 *
	 * @since 1.0.0
	 * @var array<string, float>
	 */
	const SATURATION_PRESETS = array(
		'vivid'    => 0.7,
		'balanced' => 1.0,
		'muted'    => 1.5,
	);

	/**
	 * Shade profile presets — lightness distribution for chromatic shades.
	 *
	 * 'punchy' = wider contrast gaps between shades (bold brands).
	 * 'balanced' = default even distribution.
	 * 'soft' = tighter range, more mid-tones (gentle/luxury brands).
	 *
	 * @since 1.0.0
	 * @var array<string, float[]>
	 */
	const SHADE_PROFILES = array(
		'punchy'   => array( 0.97, 0.88, 0.72, 0.48, 0.25, 0.08, 0.0 ),
		'balanced' => array( 0.95, 0.85, 0.70, 0.50, 0.30, 0.10, 0.0 ),
		'soft'     => array( 0.92, 0.80, 0.65, 0.50, 0.35, 0.18, 0.0 ),
	);

	/**
	 * Neutral shade profiles — lightness distribution for neutral shades.
	 *
	 * Same mood logic as chromatic but includes pure-white (1.0) at index 0.
	 *
	 * @since 1.0.0
	 * @var array<string, float[]>
	 */
	const NEUTRAL_SHADE_PROFILES = array(
		'punchy'   => array( 1.0, 0.97, 0.88, 0.72, 0.48, 0.25, 0.08, 0.0 ),
		'balanced' => array( 1.0, 0.95, 0.85, 0.70, 0.50, 0.30, 0.10, 0.0 ),
		'soft'     => array( 1.0, 0.92, 0.80, 0.65, 0.50, 0.35, 0.18, 0.0 ),
	);

	// ──────────────────────────────────────────────────────
	// Hex / RGB conversion
	// ──────────────────────────────────────────────────────

	/**
	 * Convert a hex color string to an RGB array.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hex Hex color (e.g., '#6431F6' or '6431F6').
	 * @return int[] Array of [red, green, blue] values (0-255).
	 */
	public static function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return array(
			(int) hexdec( substr( $hex, 0, 2 ) ),
			(int) hexdec( substr( $hex, 2, 2 ) ),
			(int) hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * Convert RGB values to a hex color string.
	 *
	 * @since 1.0.0
	 *
	 * @param int $r Red (0-255).
	 * @param int $g Green (0-255).
	 * @param int $b Blue (0-255).
	 * @return string Hex color (e.g., '#6431f6').
	 */
	public static function rgb_to_hex( $r, $g, $b ) {
		$r = max( 0, min( 255, (int) round( $r ) ) );
		$g = max( 0, min( 255, (int) round( $g ) ) );
		$b = max( 0, min( 255, (int) round( $b ) ) );

		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}

	// ──────────────────────────────────────────────────────
	// OKLCH color space conversion
	// ──────────────────────────────────────────────────────

	/**
	 * Convert an sRGB channel (0-1) to linear RGB.
	 *
	 * @since 1.0.0
	 *
	 * @param float $c sRGB channel value (0-1).
	 * @return float Linear RGB channel value.
	 */
	private static function srgb_to_linear( $c ) {
		if ( $c <= 0.04045 ) {
			return $c / 12.92;
		}

		return pow( ( $c + 0.055 ) / 1.055, 2.4 );
	}

	/**
	 * Convert a linear RGB channel to sRGB (0-1).
	 *
	 * @since 1.0.0
	 *
	 * @param float $c Linear RGB channel value.
	 * @return float sRGB channel value (0-1).
	 */
	private static function linear_to_srgb( $c ) {
		if ( $c <= 0.0031308 ) {
			return $c * 12.92;
		}

		return 1.055 * pow( $c, 1.0 / 2.4 ) - 0.055;
	}

	/**
	 * Convert a hex color to OKLCH array [L, C, h].
	 *
	 * Pipeline: sRGB → linear RGB → LMS → Oklab → OKLCH.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hex Hex color.
	 * @return float[] Array of [Lightness (0-1), Chroma (0+), Hue (radians)].
	 */
	public static function hex_to_oklch( $hex ) {
		$rgb = self::hex_to_rgb( $hex );

		// sRGB (0-255) → linear RGB (0-1).
		$r = self::srgb_to_linear( $rgb[0] / 255.0 );
		$g = self::srgb_to_linear( $rgb[1] / 255.0 );
		$b = self::srgb_to_linear( $rgb[2] / 255.0 );

		// Linear sRGB → LMS (Oklab M1 — direct single-step, NOT via XYZ).
		$l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
		$m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
		$s = 0.0883024619 * $r + 0.2024390869 * $g + 0.7172585572 * $b;

		// Cube root of LMS.
		$l_ = self::cbrt( $l );
		$m_ = self::cbrt( $m );
		$s_ = self::cbrt( $s );

		// LMS → Oklab.
		$L    = 0.2104542553 * $l_ + 0.7936177850 * $m_ - 0.0040720468 * $s_;
		$a    = 1.9779984951 * $l_ - 2.4285922050 * $m_ + 0.4505937099 * $s_;
		$b_ok = 0.0259040371 * $l_ + 0.7827717662 * $m_ - 0.8086757660 * $s_;

		// Oklab → OKLCH.
		$C = sqrt( $a * $a + $b_ok * $b_ok );
		$h = atan2( $b_ok, $a );

		return array( $L, $C, $h );
	}

	/**
	 * Convert OKLCH values to a hex color string with sRGB gamut mapping.
	 *
	 * If the target OKLCH color falls outside sRGB, chroma is reduced
	 * via binary search until the color fits. Hue is always preserved.
	 *
	 * @since 1.0.0
	 *
	 * @param float $L Lightness (0-1).
	 * @param float $C Chroma (0+).
	 * @param float $h Hue (radians).
	 * @return string Hex color.
	 */
	public static function oklch_to_hex( $L, $C, $h ) {
		// Pure white / black short-circuit.
		if ( $L >= 1.0 ) {
			return '#ffffff';
		}
		if ( $L <= 0.0 ) {
			return '#000000';
		}

		// Already in gamut — fast path.
		if ( self::is_in_srgb_gamut( $L, $C, $h ) ) {
			return self::oklch_to_hex_raw( $L, $C, $h );
		}

		// Binary search: reduce chroma until in gamut (20 iterations ≈ 1e-6 precision).
		$lo = 0.0;
		$hi = $C;

		for ( $i = 0; $i < 20; $i++ ) {
			$mid = ( $lo + $hi ) / 2.0;
			if ( self::is_in_srgb_gamut( $L, $mid, $h ) ) {
				$lo = $mid;
			} else {
				$hi = $mid;
			}
		}

		return self::oklch_to_hex_raw( $L, $lo, $h );
	}

	/**
	 * Check if an OKLCH color is within sRGB gamut.
	 *
	 * @since 1.0.0
	 *
	 * @param float $L Lightness.
	 * @param float $C Chroma.
	 * @param float $h Hue.
	 * @return bool True if within sRGB gamut.
	 */
	private static function is_in_srgb_gamut( $L, $C, $h ) {
		$rgb = self::oklch_to_linear_rgb( $L, $C, $h );
		$eps = -0.002;

		return $rgb[0] >= $eps && $rgb[0] <= 1.002
			&& $rgb[1] >= $eps && $rgb[1] <= 1.002
			&& $rgb[2] >= $eps && $rgb[2] <= 1.002;
	}

	/**
	 * Convert OKLCH to linear RGB (may be out of gamut).
	 *
	 * Pipeline: OKLCH → Oklab → LMS^(1/3) → LMS → linear sRGB (Oklab M1 inverse).
	 *
	 * @since 1.0.0
	 *
	 * @param float $L Lightness.
	 * @param float $C Chroma.
	 * @param float $h Hue.
	 * @return float[] Array of [r, g, b] in linear RGB (may be <0 or >1).
	 */
	private static function oklch_to_linear_rgb( $L, $C, $h ) {
		// OKLCH → Oklab.
		$a    = $C * cos( $h );
		$b_ok = $C * sin( $h );

		// Oklab → LMS^(1/3).
		$l_ = $L + 0.3963377774 * $a + 0.2158037573 * $b_ok;
		$m_ = $L - 0.1055613458 * $a - 0.0638541728 * $b_ok;
		$s_ = $L - 0.0894841775 * $a - 1.2914855480 * $b_ok;

		// Cube to get LMS.
		$l = $l_ * $l_ * $l_;
		$m = $m_ * $m_ * $m_;
		$s = $s_ * $s_ * $s_;

		// LMS → linear sRGB (exact numerical inverse of forward M1 matrix).
		$r = 4.0570646951 * $l - 3.2550220938 * $m + 0.1963862872 * $s;
		$g = -1.2393600619 * $l + 2.5318946794 * $m - 0.2902128834 * $s;
		$b = -0.1496725000 * $l - 0.3138728431 * $m + 1.4519297512 * $s;

		return array( $r, $g, $b );
	}

	/**
	 * Convert OKLCH to hex without gamut mapping (clamps to 0-255).
	 *
	 * @since 1.0.0
	 *
	 * @param float $L Lightness.
	 * @param float $C Chroma.
	 * @param float $h Hue.
	 * @return string Hex color.
	 */
	private static function oklch_to_hex_raw( $L, $C, $h ) {
		$rgb = self::oklch_to_linear_rgb( $L, $C, $h );

		$r = self::linear_to_srgb( max( 0.0, min( 1.0, $rgb[0] ) ) );
		$g = self::linear_to_srgb( max( 0.0, min( 1.0, $rgb[1] ) ) );
		$b = self::linear_to_srgb( max( 0.0, min( 1.0, $rgb[2] ) ) );

		return self::rgb_to_hex(
			(int) round( $r * 255 ),
			(int) round( $g * 255 ),
			(int) round( $b * 255 )
		);
	}

	/**
	 * Cube root that handles negative values (PHP's pow fails on negatives).
	 *
	 * @since 1.0.0
	 *
	 * @param float $x Value.
	 * @return float Cube root.
	 */
	private static function cbrt( $x ) {
		if ( $x >= 0 ) {
			return pow( $x, 1.0 / 3.0 );
		}

		return -pow( -$x, 1.0 / 3.0 );
	}

	// ──────────────────────────────────────────────────────
	// Shade generation (OKLCH-based)
	// ──────────────────────────────────────────────────────

	/**
	 * Generate a shade by interpolating toward white in OKLCH space.
	 *
	 * Lightness moves toward 1.0 (white), chroma reduces based on power curve,
	 * hue stays constant. This produces perceptually uniform shades
	 * regardless of the base hue.
	 *
	 * @since 1.0.0
	 *
	 * @param float[] $oklch        Base color as [L, C, h].
	 * @param float   $ratio        White ratio (1.0 = white, 0.0 = original color).
	 * @param float   $chroma_power Chroma reduction curve (< 1 = vivid, 1 = linear, > 1 = muted).
	 * @return string Hex color.
	 */
	public static function shade_oklch( $oklch, $ratio, $chroma_power = 1.0 ) {
		if ( $ratio >= 1.0 ) {
			return '#ffffff';
		}

		if ( $ratio <= 0.0 ) {
			return self::oklch_to_hex( $oklch[0], $oklch[1], $oklch[2] );
		}

		// Interpolate lightness toward white (L=1.0).
		$L = $oklch[0] + $ratio * ( 1.0 - $oklch[0] );

		// Reduce chroma with power curve — vivid keeps color longer, muted fades faster.
		$C = $oklch[1] * pow( 1.0 - $ratio, $chroma_power );

		// Hue stays constant.
		$h = $oklch[2];

		return self::oklch_to_hex( $L, $C, $h );
	}

	// ──────────────────────────────────────────────────────
	// Public shade generators
	// ──────────────────────────────────────────────────────

	/**
	 * Mix a color with white at a given ratio using RGB linear interpolation.
	 *
	 * @deprecated 1.0.0 Use shade_oklch() for perceptually uniform results.
	 * @since      1.0.0
	 *
	 * @param string $hex   Base color hex.
	 * @param float  $ratio White ratio (1.0 = pure white, 0.0 = original color).
	 * @return string Resulting hex color.
	 */
	public static function mix_with_white( $hex, $ratio ) {
		if ( $ratio >= 1.0 ) {
			return '#ffffff';
		}

		if ( $ratio <= 0.0 ) {
			$rgb = self::hex_to_rgb( $hex );
			return self::rgb_to_hex( $rgb[0], $rgb[1], $rgb[2] );
		}

		$rgb = self::hex_to_rgb( $hex );

		$r = 255 * $ratio + $rgb[0] * ( 1 - $ratio );
		$g = 255 * $ratio + $rgb[1] * ( 1 - $ratio );
		$b = 255 * $ratio + $rgb[2] * ( 1 - $ratio );

		return self::rgb_to_hex( (int) round( $r ), (int) round( $g ), (int) round( $b ) );
	}

	/**
	 * Generate 8 neutral shades from a (possibly tinted) base color.
	 *
	 * Uses OKLCH for perceptually uniform lightness steps.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $base_hex The neutral base hex (tinted or pure black).
	 * @param array<string, mixed> $options  Optional: 'shadeProfile' (punchy|balanced|soft).
	 * @return string[] Array of 8 hex strings indexed 0-7 (lightest to darkest).
	 */
	public static function generate_neutral_shades( $base_hex, $options = array() ) {
		$profile = isset( $options['shadeProfile'] ) ? $options['shadeProfile'] : 'balanced';
		$ratios  = isset( self::NEUTRAL_SHADE_PROFILES[ $profile ] )
			? self::NEUTRAL_SHADE_PROFILES[ $profile ]
			: self::NEUTRAL_SHADE_PROFILES['balanced'];

		$shades = array();
		$oklch  = self::hex_to_oklch( $base_hex );

		foreach ( $ratios as $index => $ratio ) {
			$shades[ $index ] = self::shade_oklch( $oklch, $ratio );
		}

		return $shades;
	}

	/**
	 * Generate 7 chromatic shades from a base color.
	 *
	 * Uses OKLCH for perceptually uniform lightness steps.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $hex     The chromatic base hex.
	 * @param array<string, mixed> $options Optional: 'shadeProfile' (punchy|balanced|soft),
	 *                                      'saturation' (vivid|balanced|muted).
	 * @return string[] Array of 7 hex strings indexed 1-7 (lightest to darkest).
	 */
	public static function generate_chromatic_shades( $hex, $options = array() ) {
		$profile    = isset( $options['shadeProfile'] ) ? $options['shadeProfile'] : 'balanced';
		$saturation = isset( $options['saturation'] ) ? $options['saturation'] : 'balanced';

		$ratios       = isset( self::SHADE_PROFILES[ $profile ] )
			? self::SHADE_PROFILES[ $profile ]
			: self::SHADE_PROFILES['balanced'];
		$chroma_power = isset( self::SATURATION_PRESETS[ $saturation ] )
			? self::SATURATION_PRESETS[ $saturation ]
			: 1.0;

		$shades = array();
		$oklch  = self::hex_to_oklch( $hex );

		foreach ( $ratios as $index => $ratio ) {
			// Chromatic shades are indexed 1-7, not 0-6.
			$shades[ $index + 1 ] = self::shade_oklch( $oklch, $ratio, $chroma_power );
		}

		return $shades;
	}

	/**
	 * Mix a color with black at a given ratio using RGB linear interpolation.
	 *
	 * Since black is #000000, this simplifies to multiplying each channel
	 * by (1 - ratio).
	 *
	 * @since 1.0.0
	 *
	 * @param string $hex   Base color hex.
	 * @param float  $ratio Black ratio (1.0 = pure black, 0.0 = original color).
	 * @return string Resulting hex color.
	 */
	public static function mix_with_black( $hex, $ratio ) {
		if ( $ratio >= 1.0 ) {
			return '#000000';
		}

		$rgb = self::hex_to_rgb( $hex );

		if ( $ratio <= 0.0 ) {
			return self::rgb_to_hex( $rgb[0], $rgb[1], $rgb[2] );
		}

		$r = $rgb[0] * ( 1 - $ratio );
		$g = $rgb[1] * ( 1 - $ratio );
		$b = $rgb[2] * ( 1 - $ratio );

		return self::rgb_to_hex( (int) round( $r ), (int) round( $g ), (int) round( $b ) );
	}

	/**
	 * Generate 4 dark chromatic shades from a base color using black-mixing.
	 *
	 * Extends the chromatic scale beyond index 7 (pure color) into darker territory.
	 * Indices 8-11 mirror the white-mix ratios in reverse.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hex The chromatic base hex.
	 * @return string[] Array of 4 hex strings indexed 8-11 (progressively darker).
	 */
	public static function generate_chromatic_dark_shades( $hex ) {
		$shades = array();

		foreach ( self::BLACK_RATIOS as $offset => $ratio ) {
			// Dark shades start at index 8.
			$shades[ $offset + 8 ] = self::mix_with_black( $hex, $ratio );
		}

		return $shades;
	}

	// ──────────────────────────────────────────────────────
	// Neutral tinting & opacity tokens
	// ──────────────────────────────────────────────────────

	/**
	 * Tint a neutral base by blending a chromatic color into pure black.
	 *
	 * @deprecated 1.0.0 Use tint_neutral_oklch() for perceptually accurate tinting.
	 * @since      1.0.0
	 *
	 * @param string $chromatic_hex The chromatic color to tint with.
	 * @param float  $strength      Tint strength (0.0-1.0). Default 0.05 (5%).
	 * @return string Tinted near-black hex.
	 */
	public static function tint_neutral( $chromatic_hex, $strength = 0.05 ) {
		$rgb = self::hex_to_rgb( $chromatic_hex );

		$r = (int) round( $rgb[0] * $strength );
		$g = (int) round( $rgb[1] * $strength );
		$b = (int) round( $rgb[2] * $strength );

		return self::rgb_to_hex( $r, $g, $b );
	}

	/**
	 * Tint a neutral base using OKLCH for perceptually accurate hue transfer.
	 *
	 * Unlike the RGB method which fades to near-invisible tints at low strengths,
	 * OKLCH preserves the hue character so the neutral palette feels intentionally
	 * warm or cool. The tint carries through into mid-range shades, not just the
	 * darkest shade.
	 *
	 * @since 1.0.0
	 *
	 * @param string $chromatic_hex The chromatic color whose hue tints the neutrals.
	 * @param float  $strength      Tint strength (0.0-1.0). Default 0.08 (8%).
	 * @return string Tinted near-black hex.
	 */
	public static function tint_neutral_oklch( $chromatic_hex, $strength = 0.08 ) {
		$oklch = self::hex_to_oklch( $chromatic_hex );

		// Near-black base with controlled chroma in the chromatic's hue.
		// L stays low (dark base), C scales with strength for visible tint.
		$L = 0.15;
		$C = min( $oklch[1] * 0.4, $strength * $oklch[1] * 3.0 );
		$h = $oklch[2];

		return self::oklch_to_hex( $L, $C, $h );
	}

	/**
	 * Generate opacity tokens for dark and light tracks.
	 *
	 * @since 1.0.0
	 *
	 * @param string $dark_hex  The darkest neutral hex (for light-bg borders/text).
	 * @param string $light_hex The lightest color (for dark-bg borders/text). Default white.
	 * @return array{dark: array<string>, light: array<string>}
	 */
	public static function generate_opacity_tokens( $dark_hex, $light_hex = '#ffffff' ) {
		$dark_rgb  = self::hex_to_rgb( $dark_hex );
		$light_rgb = self::hex_to_rgb( $light_hex );

		$tokens = array(
			'dark'  => array(),
			'light' => array(),
		);

		foreach ( self::OPACITY_LEVELS as $pct ) {
			$alpha = round( $pct / 100, 2 );

			$tokens['dark'][ (string) $pct ] = sprintf(
				'rgba(%d, %d, %d, %s)',
				$dark_rgb[0],
				$dark_rgb[1],
				$dark_rgb[2],
				$alpha
			);

			$tokens['light'][ (string) $pct ] = sprintf(
				'rgba(%d, %d, %d, %s)',
				$light_rgb[0],
				$light_rgb[1],
				$light_rgb[2],
				$alpha
			);
		}

		return $tokens;
	}
}
