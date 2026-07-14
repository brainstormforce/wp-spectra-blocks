<?php
/**
 * Contrast Engine — Layer 2 of the Style Guide pipeline.
 *
 * Implements APCA (Accessible Perceptual Contrast Algorithm) to determine
 * whether a background shade needs dark or light text for readability.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.0
 */

namespace SpectraBlocks\StyleGuide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ContrastEngine
 *
 * @since 1.0.0
 */
class ContrastEngine {

	/**
	 * APCA Lc threshold for readable text.
	 * Between body text (60) and large text (45). Pragmatic choice.
	 *
	 * @since 1.0.0
	 * @var float
	 */
	const THRESHOLD = 54.0;

	/**
	 * APCA exponent constants for sRGB linearization.
	 *
	 * @since 1.0.0
	 */
	const SRGB_MAINTRM = 0.022;
	const SRGB_NORMEXP = 1.414;

	/**
	 * APCA power curve constants.
	 *
	 * @since 1.0.0
	 */
	const NORM_BG   = 0.56;
	const NORM_TXT  = 0.57;
	const REV_TXT   = 0.62;
	const REV_BG    = 0.65;
	const BLK_THRS  = 0.022;
	const BLK_CLAMP = 1.414;
	const SCALE_BOW = 1.14;
	const SCALE_WOB = 1.14;
	const LOFST     = 0.027;

	/**
	 * Convert an sRGB channel value (0-255) to linearized luminance component.
	 *
	 * @since 1.0.0
	 *
	 * @param int $channel sRGB value (0-255).
	 * @return float Linearized value.
	 */
	private static function linearize( $channel ) {
		$val = $channel / 255.0;
		return pow( $val, 2.4 );
	}

	/**
	 * Calculate the Y (relative luminance) from an RGB array using sRGB coefficients.
	 *
	 * @since 1.0.0
	 *
	 * @param int[] $rgb Array of [r, g, b] (0-255).
	 * @return float Luminance Y value.
	 */
	public static function srgb_to_y( $rgb ) {
		return 0.2126729 * self::linearize( $rgb[0] )
			+ 0.7151522 * self::linearize( $rgb[1] )
			+ 0.0721750 * self::linearize( $rgb[2] );
	}

	/**
	 * Calculate APCA contrast (Lc value) between text and background.
	 *
	 * Implements the APCA-W3 algorithm for perceptual contrast.
	 * Returns absolute Lc value (always positive).
	 *
	 * @since 1.0.0
	 *
	 * @param string $text_hex Text color hex.
	 * @param string $bg_hex   Background color hex.
	 * @return float Absolute APCA Lc value.
	 */
	public static function apca_contrast( $text_hex, $bg_hex ) {
		$text_rgb = ShadeGenerator::hex_to_rgb( $text_hex );
		$bg_rgb   = ShadeGenerator::hex_to_rgb( $bg_hex );

		$text_y = self::srgb_to_y( $text_rgb );
		$bg_y   = self::srgb_to_y( $bg_rgb );

		// Soft clamp for very dark colors.
		if ( $text_y < self::BLK_THRS ) {
			$text_y += pow( self::BLK_THRS - $text_y, self::BLK_CLAMP );
		}
		if ( $bg_y < self::BLK_THRS ) {
			$bg_y += pow( self::BLK_THRS - $bg_y, self::BLK_CLAMP );
		}

		// Determine polarity and calculate contrast.
		if ( abs( $bg_y - $text_y ) < 0.0005 ) {
			return 0.0;
		}

		if ( $bg_y > $text_y ) {
			// Light background, dark text (positive polarity).
			$sapc   = ( pow( $bg_y, self::NORM_BG ) - pow( $text_y, self::NORM_TXT ) ) * self::SCALE_BOW;
			$output = ( $sapc < self::LOFST ) ? 0.0 : $sapc - self::LOFST;
		} else {
			// Dark background, light text (negative polarity).
			$sapc   = ( pow( $bg_y, self::REV_BG ) - pow( $text_y, self::REV_TXT ) ) * self::SCALE_WOB;
			$output = ( $sapc > -self::LOFST ) ? 0.0 : $sapc + self::LOFST;
		}

		return abs( $output * 100 );
	}

	/**
	 * Determine if a background color is light (needs dark text).
	 *
	 * @since 1.0.0
	 *
	 * @param string $bg_hex         Background color hex.
	 * @param string $dark_text_hex  Dark text color hex. Default '#000000'.
	 * @param string $light_text_hex Light text color hex. Default '#ffffff'.
	 * @return bool True if background is light (use dark text), false if dark (use light text).
	 */
	public static function is_light_background( $bg_hex, $dark_text_hex = '#000000', $light_text_hex = '#ffffff' ) {
		$dark_contrast  = self::apca_contrast( $dark_text_hex, $bg_hex );
		$light_contrast = self::apca_contrast( $light_text_hex, $bg_hex );

		// If dark text provides better or equal contrast, the background is light.
		return $dark_contrast >= $light_contrast;
	}

	/**
	 * Find the flip point in a shade array where the background switches
	 * from needing dark text to needing light text.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $shades      Array of hex shades ordered lightest to darkest.
	 * @param string   $dark_text   Dark text hex for contrast check. Default '#000000'.
	 * @param string   $light_text  Light text hex for contrast check. Default '#ffffff'.
	 * @return int The shade index where the flip occurs (first dark-bg shade).
	 *             Returns count of shades if no flip (all light backgrounds).
	 */
	public static function get_flip_point( $shades, $dark_text = '#000000', $light_text = '#ffffff' ) {
		foreach ( $shades as $index => $hex ) {
			if ( ! self::is_light_background( $hex, $dark_text, $light_text ) ) {
				return $index;
			}
		}

		return count( $shades );
	}
}
