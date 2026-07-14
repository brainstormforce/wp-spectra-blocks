<?php
/**
 * Scheme Computer — Layer 3 of the Style Guide pipeline.
 *
 * Computes pre-built section color schemes from shade tokens and APCA contrast data.
 * Each scheme defines background, text, accent, border, and dark/light classification.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.0
 */

namespace SpectraBlocks\StyleGuide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SchemeComputer
 *
 * @since 1.0.0
 */
class SchemeComputer {

	/**
	 * Compute a single scheme for a shade used as a section background.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bg_key              Token key for the background (e.g., 'chromatic1-4').
	 * @param string $bg_hex              Hex value of the background shade.
	 * @param string $main_accent_key     Token key for the main chromatic accent (e.g., 'chromatic1-4').
	 * @param string $neutral_darkest     Token key for darkest neutral (e.g., 'neutral-7').
	 * @param string $neutral_darkest_hex Hex of darkest neutral.
	 * @return array<string, mixed> Scheme object.
	 */
	public static function compute_scheme( $bg_key, $bg_hex, $main_accent_key, $neutral_darkest, $neutral_darkest_hex ) {
		$prefix       = TokenRegistry::PREFIX;
		$is_light_bg  = ContrastEngine::is_light_background( $bg_hex, $neutral_darkest_hex );
		$shade_index  = self::extract_shade_index( $bg_key );
		$palette_type = self::extract_palette_type( $bg_key );
		$chromatic    = self::extract_chromatic_index( $bg_key );

		if ( $is_light_bg ) {
			// Light background — dark text, dark borders.
			$accent = self::pick_accent_for_light_bg( $bg_key, $shade_index, $main_accent_key );

			return array(
				'background' => "{$prefix}-{$bg_key}",
				'foreground' => "{$prefix}-{$bg_key}",
				'text'       => "{$prefix}-neutral-7",
				'accent'     => $accent,
				'border'     => "{$prefix}-opacity-dark-15",
				'isDark'     => true,
				'palette'    => $palette_type,
				'shade'      => $shade_index,
				'chromatic'  => $chromatic,
			);
		}

		// Dark background — light text, light borders.
		return array(
			'background' => "{$prefix}-{$bg_key}",
			'foreground' => "{$prefix}-{$bg_key}",
			'text'       => "{$prefix}-white",
			'accent'     => "{$prefix}-white",
			'border'     => "{$prefix}-opacity-light-20",
			'isDark'     => false,
			'palette'    => $palette_type,
			'shade'      => $shade_index,
			'chromatic'  => $chromatic,
		);
	}

	/**
	 * Compute all schemes from a populated TokenRegistry.
	 *
	 * @since 1.0.0
	 *
	 * @param TokenRegistry $tokens          The populated token registry.
	 * @param string        $main_accent_key Token key for main chromatic accent.
	 * @param int           $chromatic_count Number of chromatic colors (1-6).
	 * @return list<array<string, mixed>> Array of scheme objects.
	 */
	public static function compute_all_schemes( TokenRegistry $tokens, $main_accent_key, $chromatic_count ) {
		$schemes             = array();
		$neutral_darkest_hex = $tokens->get( 'neutral-7' );

		if ( empty( $neutral_darkest_hex ) ) {
			return $schemes;
		}

		// 8 neutral schemes (shade 0-7).
		for ( $i = 0; $i <= 7; $i++ ) {
			$key = "neutral-{$i}";
			$hex = $tokens->get( $key );

			if ( null === $hex ) {
				continue;
			}

			$schemes[] = self::compute_scheme(
				$key,
				$hex,
				$main_accent_key,
				'neutral-7',
				$neutral_darkest_hex
			);
		}

		// N × 7 chromatic schemes (shade 1-7 per chromatic).
		for ( $c = 1; $c <= $chromatic_count; $c++ ) {
			for ( $s = 1; $s <= 7; $s++ ) {
				$key = "chromatic{$c}-{$s}";
				$hex = $tokens->get( $key );

				if ( null === $hex ) {
					continue;
				}

				$schemes[] = self::compute_scheme(
					$key,
					$hex,
					$main_accent_key,
					'neutral-7',
					$neutral_darkest_hex
				);
			}
		}

		return $schemes;
	}

	/**
	 * Pick an accent color for a light background scheme.
	 *
	 * Lightest shades (0-1) use the main chromatic for visual pop.
	 * All other light shades use darkest neutral for subtle contrast.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bg_key          Background token key.
	 * @param int    $shade_index     Shade index.
	 * @param string $main_accent_key Main chromatic accent token key.
	 * @return string Prefixed token key for the accent color.
	 */
	private static function pick_accent_for_light_bg( $bg_key, $shade_index, $main_accent_key ) {
		$prefix = TokenRegistry::PREFIX;

		// On white or near-white backgrounds, use chromatic accent for button/link pop.
		if ( $shade_index <= 1 ) {
			return "{$prefix}-{$main_accent_key}";
		}

		// On colored light backgrounds, use dark neutral to avoid color-on-color clash.
		return "{$prefix}-neutral-7";
	}

	/**
	 * Extract the shade index from a token key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Token key (e.g., 'neutral-3' or 'chromatic2-5').
	 * @return int Shade index.
	 */
	private static function extract_shade_index( $key ) {
		if ( preg_match( '/-(\d)$/', $key, $matches ) ) {
			return (int) $matches[1];
		}

		return 0;
	}

	/**
	 * Extract the palette type from a token key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Token key.
	 * @return string 'neutral' or 'chromatic'.
	 */
	private static function extract_palette_type( $key ) {
		return ( 0 === strpos( $key, 'neutral' ) ) ? 'neutral' : 'chromatic';
	}

	/**
	 * Extract the chromatic index from a token key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Token key (e.g., 'chromatic2-5').
	 * @return int|null Chromatic index (1-6) or null for neutral.
	 */
	private static function extract_chromatic_index( $key ) {
		if ( preg_match( '/^chromatic(\d+)/', $key, $matches ) ) {
			return (int) $matches[1];
		}

		return null;
	}
}
