<?php
/**
 * Colour math — a minimal PHP port of the editor's `colorMath.js`, used to derive
 * the "More color variables" (foreground / surface-2 / overlay) SERVER-side with
 * the exact same formulas the Style Guide UI uses, so a reset value renders
 * identically in the editor preview and on the front end.
 *
 * Only the operations the derivations need are ported: hex parsing, linear mix,
 * WCAG relative luminance and contrast ratio. Keep these byte-for-byte equivalent
 * to `src/extensions/gbs-editor-v2/utils/colorMath.js` in the Pro plugin.
 *
 * @package Spectra\StyleGuide
 * @since   x.x.x
 */

namespace SpectraBlocks\StyleGuide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ColorMath
 *
 * @since x.x.x
 */
class ColorMath {

	/**
	 * Normalise to `#rrggbb` (lower-case), or a fallback when not a 6-digit hex.
	 *
	 * @since x.x.x
	 *
	 * @param string $hex      Raw colour.
	 * @param string $fallback Value returned when $hex is not a 6-digit hex.
	 * @return string `#rrggbb`.
	 */
	public static function normalize( string $hex, string $fallback = '#000000' ): string {
		if ( 1 === preg_match( '/^#?([0-9a-f]{6})$/i', trim( $hex ), $m ) ) {
			return '#' . strtolower( $m[1] );
		}
		return $fallback;
	}

	/**
	 * Parse `#rrggbb` into [r, g, b] (0-255). Mirrors colorMath.js `parse()`.
	 *
	 * @since x.x.x
	 *
	 * @param string $hex Colour.
	 * @return array{0:int,1:int,2:int} RGB channels.
	 */
	public static function parse( string $hex ): array {
		$h = self::normalize( $hex );
		return array(
			(int) hexdec( substr( $h, 1, 2 ) ),
			(int) hexdec( substr( $h, 3, 2 ) ),
			(int) hexdec( substr( $h, 5, 2 ) ),
		);
	}

	/**
	 * Linear interpolate from $a toward $b by $t (0..1). Mirrors colorMath.js `mix()`.
	 *
	 * @since x.x.x
	 *
	 * @param string $a Start colour.
	 * @param string $b End colour.
	 * @param float  $t Mix amount toward $b (0..1).
	 * @return string `#rrggbb`.
	 */
	public static function mix( string $a, string $b, float $t ): string {
		$from = self::parse( $a );
		$to   = self::parse( $b );
		$out  = '#';
		foreach ( $from as $i => $v ) {
			$c    = (int) round( $v + ( $to[ $i ] - $v ) * $t );
			$c    = max( 0, min( 255, $c ) );
			$out .= str_pad( dechex( $c ), 2, '0', STR_PAD_LEFT );
		}
		return $out;
	}

	/**
	 * WCAG relative luminance (0..1). Mirrors colorMath.js `luminance()`.
	 *
	 * @since x.x.x
	 *
	 * @param string $hex Colour.
	 * @return float Luminance.
	 */
	public static function luminance( string $hex ): float {
		$lin = array();
		foreach ( self::parse( $hex ) as $v ) {
			$x     = $v / 255;
			$lin[] = $x <= 0.03928 ? $x / 12.92 : pow( ( $x + 0.055 ) / 1.055, 2.4 );
		}
		return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
	}

	/**
	 * WCAG contrast ratio (1..21). Mirrors colorMath.js `contrastRatio()`.
	 *
	 * @since x.x.x
	 *
	 * @param string $a Colour A.
	 * @param string $b Colour B.
	 * @return float Ratio.
	 */
	public static function contrast_ratio( string $a, string $b ): float {
		$l1 = self::luminance( $a );
		$l2 = self::luminance( $b );
		return ( max( $l1, $l2 ) + 0.05 ) / ( min( $l1, $l2 ) + 0.05 );
	}
}
