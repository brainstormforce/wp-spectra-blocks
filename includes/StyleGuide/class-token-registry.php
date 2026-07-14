<?php
/**
 * Token Registry — single source of truth for all CSS variable names and values.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.0
 */

namespace SpectraBlocks\StyleGuide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TokenRegistry
 *
 * @since 1.0.0
 */
class TokenRegistry {

	/**
	 * CSS variable prefix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PREFIX = 'spectra';

	/**
	 * All registered tokens: name => value.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private $tokens = array();

	/**
	 * All computed schemes.
	 *
	 * @since 1.0.0
	 * @var list<array<string, mixed>>
	 */
	private $schemes = array();

	/**
	 * Color metadata for WP palette names.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private $color_names = array();

	/*
	================================================================
		UI Styling Preset Definitions — PHP mirror of token-maps.js
		================================================================
	*/

	/**
	 * Button style presets.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $button_presets = array(
		'solid'    => array(
			'btn-bg'           => 'var(--wp--preset--color--primary)',
			'btn-text'         => 'var(--wp--preset--color--background)',
			'btn-border-color' => 'transparent',
			'btn-border-width' => '0px',
			'btn-border-style' => 'none',
			'btn-shadow'       => 'none',
		),
		'soft'     => array(
			'btn-bg'           => 'color-mix(in srgb, var(--wp--preset--color--primary) 15%, var(--wp--preset--color--background))',
			'btn-text'         => 'var(--wp--preset--color--primary)',
			'btn-border-color' => 'color-mix(in srgb, var(--wp--preset--color--primary) 25%, transparent)',
			'btn-border-width' => '1px',
			'btn-border-style' => 'solid',
			'btn-shadow'       => 'none',
		),
		'outline'  => array(
			'btn-bg'           => 'transparent',
			'btn-text'         => 'var(--wp--preset--color--primary)',
			'btn-border-color' => 'var(--wp--preset--color--primary)',
			'btn-border-width' => '1.5px',
			'btn-border-style' => 'solid',
			'btn-shadow'       => 'none',
		),
		'elevated' => array(
			'btn-bg'           => 'var(--wp--preset--color--primary)',
			'btn-text'         => 'var(--wp--preset--color--background)',
			'btn-border-color' => 'transparent',
			'btn-border-width' => '0px',
			'btn-border-style' => 'none',
			'btn-shadow'       => 'var(--spectra-shadow-md)',
		),
	);

	/**
	 * Secondary button derivation: maps primary style → secondary style tokens.
	 *
	 * When primary is solid/elevated, secondary becomes outline.
	 * When primary is soft, secondary becomes outline.
	 * When primary is outline, secondary becomes solid.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $button_secondary_map = array(
		'solid'    => array(
			'btn-secondary-bg'           => 'transparent',
			'btn-secondary-text'         => 'var(--wp--preset--color--primary)',
			'btn-secondary-border-color' => 'var(--wp--preset--color--primary)',
			'btn-secondary-border-width' => '1.5px',
			'btn-secondary-border-style' => 'solid',
		),
		'soft'     => array(
			'btn-secondary-bg'           => 'transparent',
			'btn-secondary-text'         => 'var(--wp--preset--color--primary)',
			'btn-secondary-border-color' => 'var(--wp--preset--color--primary)',
			'btn-secondary-border-width' => '1.5px',
			'btn-secondary-border-style' => 'solid',
		),
		'outline'  => array(
			'btn-secondary-bg'           => 'var(--wp--preset--color--primary)',
			'btn-secondary-text'         => 'var(--wp--preset--color--background)',
			'btn-secondary-border-color' => 'transparent',
			'btn-secondary-border-width' => '0px',
			'btn-secondary-border-style' => 'none',
		),
		'elevated' => array(
			'btn-secondary-bg'           => 'transparent',
			'btn-secondary-text'         => 'var(--wp--preset--color--primary)',
			'btn-secondary-border-color' => 'var(--wp--preset--color--primary)',
			'btn-secondary-border-width' => '1.5px',
			'btn-secondary-border-style' => 'solid',
		),
	);

	/**
	 * Card style presets.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $card_presets = array(
		'flat'     => array(
			'card-bg'           => 'transparent',
			'card-border-color' => 'transparent',
			'card-border-width' => '0px',
			'card-border-style' => 'none',
			'card-shadow'       => 'none',
		),
		'outlined' => array(
			'card-bg'           => 'var(--wp--preset--color--background)',
			'card-border-color' => 'var(--wp--preset--color--outline)',
			'card-border-width' => '1px',
			'card-border-style' => 'solid',
			'card-shadow'       => 'none',
		),
		'tinted'   => array(
			'card-bg'           => 'var(--wp--preset--color--surface)',
			'card-border-color' => 'transparent',
			'card-border-width' => '0px',
			'card-border-style' => 'none',
			'card-shadow'       => 'none',
		),
		'elevated' => array(
			'card-bg'           => 'var(--wp--preset--color--background)',
			'card-border-color' => 'transparent',
			'card-border-width' => '0px',
			'card-border-style' => 'none',
			'card-shadow'       => 'var(--spectra-shadow-md)',
		),
	);

	/**
	 * Shadow depth presets.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $shadow_presets = array(
		'none'   => array(
			'shadow-sm' => 'none',
			'shadow-md' => 'none',
			'shadow-lg' => 'none',
		),
		'subtle' => array(
			'shadow-sm' => '0 1px 2px rgba(0,0,0,0.04)',
			'shadow-md' => '0 2px 8px rgba(0,0,0,0.05)',
			'shadow-lg' => '0 4px 16px rgba(0,0,0,0.06)',
		),
		'medium' => array(
			'shadow-sm' => '0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)',
			'shadow-md' => '0 4px 12px rgba(0,0,0,0.08)',
			'shadow-lg' => '0 8px 24px rgba(0,0,0,0.12)',
		),
		'deep'   => array(
			'shadow-sm' => '0 2px 8px rgba(0,0,0,0.1)',
			'shadow-md' => '0 8px 24px rgba(0,0,0,0.14)',
			'shadow-lg' => '0 16px 48px rgba(0,0,0,0.2)',
		),
	);

	/**
	 * Era-contract shadow presets, keyed by shadow depth.
	 *
	 * The eight named shadows the ERA design skill references as
	 * var(--wp--preset--shadow--{name}). Each depth level scales the whole set so
	 * the Style Guide "shadow depth" control actually drives era-authored pages.
	 * 'outlined' is a ring bound to the outline colour token.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $era_shadow_presets = array(
		'none'   => array(
			'small'    => 'none',
			'medium'   => 'none',
			'large'    => 'none',
			'deep'     => 'none',
			'crisp'    => 'none',
			'sharp'    => 'none',
			'natural'  => 'none',
			'outlined' => '0 0 0 1px var(--wp--preset--color--outline)',
		),
		'subtle' => array(
			'small'    => '0 1px 2px rgba(0,0,0,0.04)',
			'medium'   => '0 2px 6px rgba(0,0,0,0.06)',
			'large'    => '0 6px 16px rgba(0,0,0,0.08)',
			'deep'     => '0 12px 28px rgba(0,0,0,0.12)',
			'crisp'    => '0 0 0 1px rgba(0,0,0,0.04), 0 1px 3px rgba(0,0,0,0.04)',
			'sharp'    => '0 1px 1px rgba(0,0,0,0.06), 0 2px 2px rgba(0,0,0,0.06)',
			'natural'  => '0 2px 8px rgba(0,0,0,0.05)',
			'outlined' => '0 0 0 2px var(--wp--preset--color--outline)',
		),
		'medium' => array(
			'small'    => '0 1px 2px rgba(0,0,0,0.05)',
			'medium'   => '0 4px 8px rgba(0,0,0,0.08)',
			'large'    => '0 10px 24px rgba(0,0,0,0.12)',
			'deep'     => '0 20px 40px rgba(0,0,0,0.16)',
			'crisp'    => '0 0 0 1px rgba(0,0,0,0.05), 0 2px 4px rgba(0,0,0,0.05)',
			'sharp'    => '0 1px 1px rgba(0,0,0,0.08), 0 2px 2px rgba(0,0,0,0.08)',
			'natural'  => '0 2px 10px rgba(0,0,0,0.06)',
			'outlined' => '0 0 0 2px var(--wp--preset--color--outline)',
		),
		'deep'   => array(
			'small'    => '0 2px 4px rgba(0,0,0,0.08)',
			'medium'   => '0 6px 14px rgba(0,0,0,0.12)',
			'large'    => '0 16px 32px rgba(0,0,0,0.18)',
			'deep'     => '0 28px 56px rgba(0,0,0,0.24)',
			'crisp'    => '0 0 0 1px rgba(0,0,0,0.08), 0 3px 6px rgba(0,0,0,0.08)',
			'sharp'    => '0 2px 2px rgba(0,0,0,0.12), 0 4px 4px rgba(0,0,0,0.12)',
			'natural'  => '0 4px 16px rgba(0,0,0,0.1)',
			'outlined' => '0 0 0 3px var(--wp--preset--color--outline)',
		),
	);

	/**
	 * Get the eight era-contract shadow presets for a given depth.
	 *
	 * @since 1.0.0
	 *
	 * @param string $depth Shadow depth (none|subtle|medium|deep).
	 * @return array<string, string> Map of shadow slug => CSS value.
	 */
	public static function get_era_shadow_presets( string $depth ): array {
		return isset( self::$era_shadow_presets[ $depth ] )
			? self::$era_shadow_presets[ $depth ]
			: self::$era_shadow_presets['subtle'];
	}

	/**
	 * Roundness presets.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $roundness_presets = array(
		'sharp'   => array(
			'radius-interactive' => '2px',
			'radius-badge'       => '2px',
			'radius-input'       => '2px',
			'radius-card'        => '4px',
			'radius-image'       => '0px',
		),
		'default' => array(
			'radius-interactive' => '8px',
			'radius-badge'       => '6px',
			'radius-input'       => '8px',
			'radius-card'        => '12px',
			'radius-image'       => '8px',
		),
		'soft'    => array(
			'radius-interactive' => '12px',
			'radius-badge'       => '9999px',
			'radius-input'       => '12px',
			'radius-card'        => '20px',
			'radius-image'       => '16px',
		),
		'full'    => array(
			'radius-interactive' => '9999px',
			'radius-badge'       => '9999px',
			'radius-input'       => '9999px',
			'radius-card'        => '24px',
			'radius-image'       => '24px',
		),
	);

	/**
	 * Spacing base values in rem (multiplied by density factor).
	 *
	 * @since 1.0.0
	 * @var array<string, float>
	 */
	private static $spacing_base = array(
		'space-xs'  => 0.5,
		'space-sm'  => 1.0,
		'space-md'  => 1.5,
		'space-lg'  => 2.5,
		'space-xl'  => 4.0,
		'space-2xl' => 6.0,
	);

	/**
	 * Type scale token defaults (body text sizes).
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $text_scale = array(
		'text-xs'  => '0.75rem',    // 12px — Tailwind parity
		'text-sm'  => '0.875rem',   // 14px — Tailwind parity
		'text-md'  => '1rem',       // 16px — Tailwind "base" (.text-base class aliases to this token)
		'text-lg'  => '1.125rem',   // 18px — Tailwind parity (was 1.25rem)
		'text-xl'  => '1.25rem',    // 20px — Tailwind parity (was 1.5rem)
		'text-2xl' => '1.5rem',     // 24px — Tailwind parity (was 2rem)
		'text-3xl' => '1.875rem',   // 30px
		'text-4xl' => '2.25rem',    // 36px
		'text-5xl' => '3rem',       // 48px
		'text-6xl' => '3.75rem',    // 60px
		'text-7xl' => '4.5rem',     // 72px
		'text-8xl' => '6rem',       // 96px
		'text-9xl' => '8rem',       // 128px
	);

	/**
	 * Type scale token defaults (heading sizes).
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $heading_scale = array(
		'heading-1' => '2.25rem',
		'heading-2' => '1.875rem',
		'heading-3' => '1.5rem',
		'heading-4' => '1.25rem',
		'heading-5' => '1.125rem',
		'heading-6' => '1rem',
	);

	/**
	 * Spacing density multipliers.
	 *
	 * @since 1.0.0
	 * @var array<string, float>
	 */
	private static $spacing_multipliers = array(
		'compact'  => 0.75,
		'default'  => 1.0,
		'spacious' => 1.5,
	);

	/**
	 * Get the spacing-density multiplier for a density key.
	 *
	 * The SSOT for spacing density (compact/default/spacious) so preset spacing
	 * and the --spectra-space-* tokens scale from one table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $density Density key.
	 * @return float Multiplier (1.0 when unknown).
	 */
	public static function get_spacing_multiplier( string $density ): float {
		return isset( self::$spacing_multipliers[ $density ] )
			? (float) self::$spacing_multipliers[ $density ]
			: 1.0;
	}

	/**
	 * Section layout base values in rem (multiplied by density factor).
	 *
	 * @since 1.0.0
	 * @var array<string, float>
	 */
	private static $section_layout_base = array(
		'section-padding-y' => 3.5,
		'section-gap'       => 2.0,
	);

	/**
	 * Card layout base values in rem (multiplied by density factor).
	 *
	 * @since 1.0.0
	 * @var array<string, float>
	 */
	private static $card_layout_base = array(
		'card-padding' => 2.0,
		'card-gap'     => 1.25,
	);

	/**
	 * Input style presets — PHP mirror of INPUT_STYLES in token-maps.js.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $input_presets = array(
		'boxed'      => array(
			'input-bg'           => 'var(--wp--preset--color--background)',
			'input-text'         => 'var(--spectra-neutral-7)',
			'input-border-color' => 'var(--wp--preset--color--outline)',
			'input-border-width' => '1px',
			'input-border-style' => 'solid',
			'input-placeholder'  => 'var(--spectra-opacity-dark-40)',
			'input-focus-border' => 'var(--wp--preset--color--primary)',
			'input-focus-shadow' => '0 0 0 3px color-mix(in srgb, var(--wp--preset--color--primary) 15%, transparent)',
		),
		'soft'       => array(
			'input-bg'           => 'var(--spectra-neutral-1)',
			'input-text'         => 'var(--spectra-neutral-7)',
			'input-border-color' => 'transparent',
			'input-border-width' => '0px',
			'input-border-style' => 'none',
			'input-placeholder'  => 'var(--spectra-opacity-dark-40)',
			'input-focus-border' => 'var(--wp--preset--color--primary)',
			'input-focus-shadow' => '0 0 0 3px color-mix(in srgb, var(--wp--preset--color--primary) 15%, transparent)',
		),
		'underlined' => array(
			'input-bg'           => 'var(--spectra-neutral-1)',
			'input-text'         => 'var(--spectra-neutral-7)',
			'input-border-color' => 'var(--spectra-neutral-3)',
			'input-border-width' => '0 0 2px 0',
			'input-border-style' => 'solid',
			'input-placeholder'  => 'var(--spectra-opacity-dark-40)',
			'input-focus-border' => 'var(--wp--preset--color--primary)',
			'input-focus-shadow' => 'none',
		),
	);

	/**
	 * Badge style presets.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $badge_presets = array(
		'subtle'  => array(
			'badge-bg'             => 'color-mix(in srgb, var(--wp--preset--color--primary) 10%, transparent)',
			'badge-text'           => 'var(--wp--preset--color--primary)',
			'badge-border-color'   => 'transparent',
			'badge-border-width'   => '0px',
			'badge-font-size'      => '0.75rem',
			'badge-font-weight'    => '600',
			'badge-letter-spacing' => '0.05em',
			'badge-text-transform' => 'uppercase',
			'badge-padding-x'      => '0.75em',
			'badge-padding-y'      => '0.25em',
		),
		'solid'   => array(
			'badge-bg'             => 'var(--wp--preset--color--primary)',
			'badge-text'           => 'var(--wp--preset--color--background)',
			'badge-border-color'   => 'transparent',
			'badge-border-width'   => '0px',
			'badge-font-size'      => '0.75rem',
			'badge-font-weight'    => '600',
			'badge-letter-spacing' => '0.05em',
			'badge-text-transform' => 'uppercase',
			'badge-padding-x'      => '0.75em',
			'badge-padding-y'      => '0.25em',
		),
		'outline' => array(
			'badge-bg'             => 'transparent',
			'badge-text'           => 'var(--wp--preset--color--primary)',
			'badge-border-color'   => 'var(--wp--preset--color--primary)',
			'badge-border-width'   => '1px',
			'badge-font-size'      => '0.75rem',
			'badge-font-weight'    => '600',
			'badge-letter-spacing' => '0.05em',
			'badge-text-transform' => 'uppercase',
			'badge-padding-x'      => '0.75em',
			'badge-padding-y'      => '0.25em',
		),
		'pill'    => array(
			'badge-bg'             => 'color-mix(in srgb, var(--wp--preset--color--primary) 10%, transparent)',
			'badge-text'           => 'var(--wp--preset--color--primary)',
			'badge-border-color'   => 'transparent',
			'badge-border-width'   => '0px',
			'badge-font-size'      => '0.75rem',
			'badge-font-weight'    => '600',
			'badge-letter-spacing' => '0.05em',
			'badge-text-transform' => 'uppercase',
			'badge-padding-x'      => '1em',
			'badge-padding-y'      => '0.35em',
		),
	);

	/**
	 * Link style tokens — derived from button style direction.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $link_tokens = array(
		'link-color'            => 'var(--wp--preset--color--primary)',
		'link-decoration'       => 'none',
		'link-hover-color'      => 'var(--wp--preset--color--secondary)',
		'link-hover-decoration' => 'underline',
	);

	/**
	 * Image treatment presets.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $image_presets = array(
		'natural'   => array(
			'image-overlay' => 'none',
			'image-filter'  => 'none',
			'image-border'  => 'none',
		),
		'polished'  => array(
			'image-overlay' => 'none',
			'image-filter'  => 'saturate(1.05) brightness(1.02)',
			'image-border'  => '1px solid var(--spectra-opacity-dark-5)',
		),
		'dramatic'  => array(
			'image-overlay' => 'linear-gradient(to bottom, transparent 60%, rgba(0,0,0,0.4) 100%)',
			'image-filter'  => 'contrast(1.05) saturate(1.1)',
			'image-border'  => 'none',
		),
		'editorial' => array(
			'image-overlay' => 'none',
			'image-filter'  => 'grayscale(0.15) contrast(1.05)',
			'image-border'  => '1px solid var(--spectra-opacity-dark-10)',
		),
	);

	/**
	 * Hover intensity presets.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $hover_presets = array(
		'none'       => array(
			'btn-hover-bg'           => 'var(--spectra-btn-bg)',
			'btn-hover-text'         => 'var(--spectra-btn-text)',
			'btn-hover-shadow'       => 'var(--spectra-btn-shadow)',
			'btn-hover-translate-y'  => '0px',
			'card-hover-shadow'      => 'var(--spectra-card-shadow)',
			'card-hover-translate-y' => '0px',
			'link-hover-color'       => 'var(--spectra-link-color)',
			'link-hover-decoration'  => 'var(--spectra-link-decoration)',
		),
		'subtle'     => array(
			'btn-hover-bg'           => 'color-mix(in srgb, var(--spectra-btn-bg) 90%, black)',
			'btn-hover-text'         => 'var(--spectra-btn-text)',
			'btn-hover-shadow'       => 'var(--spectra-shadow-sm)',
			'btn-hover-translate-y'  => '-1px',
			'card-hover-shadow'      => 'var(--spectra-shadow-md)',
			'card-hover-translate-y' => '-2px',
			'link-hover-color'       => 'var(--wp--preset--color--secondary)',
			'link-hover-decoration'  => 'underline',
		),
		'standard'   => array(
			'btn-hover-bg'           => 'color-mix(in srgb, var(--spectra-btn-bg) 85%, black)',
			'btn-hover-text'         => 'var(--spectra-btn-text)',
			'btn-hover-shadow'       => 'var(--spectra-shadow-md)',
			'btn-hover-translate-y'  => '-2px',
			'card-hover-shadow'      => 'var(--spectra-shadow-lg)',
			'card-hover-translate-y' => '-4px',
			'link-hover-color'       => 'var(--wp--preset--color--secondary)',
			'link-hover-decoration'  => 'underline',
		),
		'expressive' => array(
			'btn-hover-bg'           => 'color-mix(in srgb, var(--spectra-btn-bg) 80%, black)',
			'btn-hover-text'         => 'var(--spectra-btn-text)',
			'btn-hover-shadow'       => 'var(--spectra-shadow-lg)',
			'btn-hover-translate-y'  => '-3px',
			'card-hover-shadow'      => 'var(--spectra-shadow-lg)',
			'card-hover-translate-y' => '-6px',
			'link-hover-color'       => 'var(--wp--preset--color--secondary)',
			'link-hover-decoration'  => 'underline',
		),
	);

	/**
	 * Dark section tokens — inverted card/button styles for dark backgrounds.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $dark_section_tokens = array(
		'dark-card-bg'                    => 'color-mix(in srgb, var(--spectra-white) 8%, transparent)',
		'dark-card-border-color'          => 'var(--spectra-opacity-light-10)',
		'dark-card-shadow'                => 'none',
		'dark-btn-bg'                     => 'var(--spectra-white)',
		'dark-btn-text'                   => 'var(--spectra-neutral-7)',
		'dark-btn-secondary-bg'           => 'transparent',
		'dark-btn-secondary-text'         => 'var(--spectra-white)',
		'dark-btn-secondary-border-color' => 'var(--spectra-opacity-light-30)',
	);

	/*
	================================================================
		Phase 3 — Professional Polish Token Definitions
		================================================================
	*/

	/**
	 * Typography expansion tokens.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $typography_tokens = array(
		'body-line-height'       => '1.65',
		'heading-line-height'    => '1.2',
		'body-letter-spacing'    => '0em',
		'heading-letter-spacing' => '-0.02em',
	);

	/**
	 * Gradient tokens — computed references to chromatic colors.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $gradient_tokens = array(
		'gradient-primary' => 'linear-gradient(135deg, var(--wp--preset--color--primary), var(--wp--preset--color--secondary))',
		'gradient-accent'  => 'linear-gradient(135deg, var(--wp--preset--color--primary), color-mix(in srgb, var(--wp--preset--color--primary) 60%, var(--spectra-chromatic2-4, #8b5cf6)))',
		'gradient-surface' => 'linear-gradient(180deg, var(--wp--preset--color--background), var(--wp--preset--color--surface))',
	);

	/**
	 * Overlay tokens.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $overlay_tokens = array(
		'overlay-dark'     => 'rgba(0, 0, 0, 0.5)',
		'overlay-light'    => 'rgba(255, 255, 255, 0.7)',
		'overlay-brand'    => 'color-mix(in srgb, var(--wp--preset--color--primary) 80%, transparent)',
		'overlay-gradient' => 'linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.6) 100%)',
	);

	/**
	 * Divider/separator tokens.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $divider_tokens = array(
		'divider-color' => 'var(--wp--preset--color--outline)',
		'divider-width' => '1px',
		'divider-style' => 'solid',
	);

	/**
	 * Navigation tokens.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $navigation_tokens = array(
		'nav-bg'            => 'var(--wp--preset--color--background)',
		'nav-text'          => 'var(--spectra-neutral-7)',
		'nav-link-hover'    => 'var(--wp--preset--color--primary)',
		'nav-border-bottom' => '1px solid var(--spectra-opacity-dark-5)',
	);

	/**
	 * Icon style tokens.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $icon_tokens = array(
		'icon-size'             => '24px',
		'icon-color'            => 'currentColor',
		'icon-container-bg'     => 'color-mix(in srgb, var(--wp--preset--color--primary) 10%, transparent)',
		'icon-container-radius' => 'var(--spectra-radius-badge)',
		'icon-container-size'   => '48px',
	);

	/*
	================================================================
		Phase 4 — Beat Competition Token Definitions
		================================================================
	*/

	/**
	 * Motion/animation presets.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, string>>
	 */
	private static $motion_presets = array(
		'none'       => array(
			'transition-fast'   => '0ms',
			'transition-normal' => '0ms',
			'transition-slow'   => '0ms',
			'ease-default'      => 'linear',
			'ease-bounce'       => 'linear',
			'ease-spring'       => 'linear',
			'entrance-duration' => '0ms',
			'entrance-distance' => '0px',
			'entrance-stagger'  => '0ms',
		),
		'subtle'     => array(
			'transition-fast'   => '100ms',
			'transition-normal' => '200ms',
			'transition-slow'   => '350ms',
			'ease-default'      => 'cubic-bezier(0.4, 0, 0.2, 1)',
			'ease-bounce'       => 'cubic-bezier(0.34, 1.56, 0.64, 1)',
			'ease-spring'       => 'cubic-bezier(0.22, 1, 0.36, 1)',
			'entrance-duration' => '400ms',
			'entrance-distance' => '12px',
			'entrance-stagger'  => '60ms',
		),
		'standard'   => array(
			'transition-fast'   => '150ms',
			'transition-normal' => '250ms',
			'transition-slow'   => '400ms',
			'ease-default'      => 'cubic-bezier(0.4, 0, 0.2, 1)',
			'ease-bounce'       => 'cubic-bezier(0.34, 1.56, 0.64, 1)',
			'ease-spring'       => 'cubic-bezier(0.22, 1, 0.36, 1)',
			'entrance-duration' => '600ms',
			'entrance-distance' => '20px',
			'entrance-stagger'  => '100ms',
		),
		'expressive' => array(
			'transition-fast'   => '200ms',
			'transition-normal' => '350ms',
			'transition-slow'   => '500ms',
			'ease-default'      => 'cubic-bezier(0.4, 0, 0.2, 1)',
			'ease-bounce'       => 'cubic-bezier(0.68, -0.55, 0.265, 1.55)',
			'ease-spring'       => 'cubic-bezier(0.22, 1, 0.36, 1)',
			'entrance-duration' => '800ms',
			'entrance-distance' => '30px',
			'entrance-stagger'  => '150ms',
		),
	);

	/**
	 * Micro-interaction tokens.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $micro_interaction_tokens = array(
		'btn-press-scale' => '0.97',
		'focus-scale'     => '1.02',
	);

	/**
	 * Accessibility tokens.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static $accessibility_tokens = array(
		'focus-ring-color'  => 'var(--wp--preset--color--primary)',
		'focus-ring-width'  => '2px',
		'focus-ring-offset' => '2px',
		'focus-ring-style'  => 'solid',
	);

	/**
	 * Register a token.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Token name without prefix (e.g., 'neutral-0').
	 * @param string $value CSS value (hex, rgba, etc.).
	 * @return void
	 */
	public function set( $name, $value ): void {
		$this->tokens[ $name ] = $value;
	}

	/**
	 * Get a single token value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Token name without prefix.
	 * @return string|null Token value or null if not found.
	 */
	public function get( $name ) {
		return isset( $this->tokens[ $name ] ) ? $this->tokens[ $name ] : null;
	}

	/**
	 * Get all registered tokens.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> All tokens as name => value.
	 */
	public function get_all() {
		return $this->tokens;
	}

	/**
	 * Set a color name for palette display.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token_name Token name (e.g., 'chromatic1-4').
	 * @param string $label      Display label (e.g., 'Primary').
	 * @return void
	 */
	public function set_color_name( $token_name, $label ): void {
		$this->color_names[ $token_name ] = $label;
	}

	/**
	 * Store computed schemes.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array<string, mixed>> $schemes Array of scheme objects.
	 * @return void
	 */
	public function set_schemes( $schemes ): void {
		$this->schemes = $schemes;
	}

	/**
	 * Get all computed schemes.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array<string, mixed>> All schemes.
	 */
	public function get_schemes() {
		return $this->schemes;
	}

	/**
	 * Get a scheme by its background key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Background token key (e.g., 'chromatic1-4').
	 * @return array<string, mixed>|null Scheme object or null.
	 */
	public function get_scheme( $key ) {
		$prefixed_key = self::PREFIX . '-' . $key;

		foreach ( $this->schemes as $scheme ) {
			if ( $scheme['background'] === $prefixed_key ) {
				return $scheme;
			}
		}

		return null;
	}

	/**
	 * Generate CSS that maps each scheme key to its 5 CSS custom properties.
	 *
	 * Produces one CSS rule per computed scheme:
	 *
	 *   [data-spectra-scheme="chromatic1-7"] {
	 *       --spectra-scheme-background: var(--spectra-chromatic1-7);
	 *       --spectra-scheme-foreground: var(--spectra-chromatic1-7);
	 *       --spectra-scheme-text: var(--spectra-white);
	 *       --spectra-scheme-accent: var(--spectra-white);
	 *       --spectra-scheme-border: var(--spectra-opacity-light-20);
	 *   }
	 *
	 * These variables are consumed by scheme-override.css which already
	 * contains comprehensive rules for [data-spectra-scheme] elements.
	 *
	 * @since 1.0.0
	 *
	 * @return string Generated CSS string, or empty string if no schemes.
	 */
	public function get_scheme_css(): string {
		if ( empty( $this->schemes ) ) {
			return '';
		}

		$prefix = self::PREFIX;
		$rules  = array();

		foreach ( $this->schemes as $scheme ) {
			// Derive attribute key: 'spectra-chromatic1-7' → 'chromatic1-7'.
			$attr_key = str_replace( $prefix . '-', '', $scheme['background'] );

			$rules[] = sprintf(
				"[data-spectra-scheme=\"%s\"] {\n\t--spectra-scheme-background: var(--%s);\n\t--spectra-scheme-foreground: var(--%s);\n\t--spectra-scheme-text: var(--%s);\n\t--spectra-scheme-accent: var(--%s);\n\t--spectra-scheme-border: var(--%s);\n}",
				esc_attr( $attr_key ),
				esc_attr( $scheme['background'] ),
				esc_attr( $scheme['foreground'] ),
				esc_attr( $scheme['text'] ),
				esc_attr( $scheme['accent'] ),
				esc_attr( $scheme['border'] )
			);
		}

		return implode( "\n\n", $rules );
	}

	/**
	 * Get the set of scheme keys that represent dark backgrounds.
	 *
	 * NOTE: The isDark field naming is inverted relative to background darkness:
	 *   isDark = true  → light background (needs dark text)
	 *   isDark = false → dark background (needs light text)
	 *
	 * This method returns keys where the BACKGROUND is dark (isDark === false),
	 * used by the render_block filter to auto-add .spectra-dark-scheme class.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, true> Dark background scheme keys for O(1) lookup.
	 */
	public function get_dark_scheme_keys(): array {
		$dark_keys = array();

		foreach ( $this->schemes as $scheme ) {
			// isDark = false means dark BACKGROUND (light text needed).
			if ( ! $scheme['isDark'] ) {
				$key               = str_replace( self::PREFIX . '-', '', $scheme['background'] );
				$dark_keys[ $key ] = true;
			}
		}

		return $dark_keys;
	}

	/**
	 * Register UI styling tokens based on Style Guide preset selections.
	 *
	 * Reads the saved preset IDs and registers the corresponding CSS custom
	 * property tokens. These auto-propagate to :root via get_css_string_with_legacy().
	 *
	 * Registration order matters: shadow → roundness → buttons → cards → spacing
	 * → section → inputs → badges → links → images → hover → dark section.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $presets Preset selections from config['presets'].
	 * @return void
	 */
	public function register_ui_tokens( array $presets ): void {
		$button_style    = isset( $presets['buttonStyle'] ) ? $presets['buttonStyle'] : 'solid';
		$card_style      = isset( $presets['cardStyle'] ) ? $presets['cardStyle'] : 'flat';
		$shadow_depth    = isset( $presets['shadowDepth'] ) ? $presets['shadowDepth'] : 'subtle';
		$roundness       = isset( $presets['roundness'] ) ? $presets['roundness'] : 'default';
		$spacing_density = isset( $presets['spacingDensity'] ) ? $presets['spacingDensity'] : 'default';
		$input_style     = isset( $presets['inputStyle'] ) ? $presets['inputStyle'] : 'boxed';
		$badge_style     = isset( $presets['badgeStyle'] ) ? $presets['badgeStyle'] : 'subtle';
		$image_treatment = isset( $presets['imageTreatment'] ) ? $presets['imageTreatment'] : 'natural';
		$hover_intensity = isset( $presets['hoverIntensity'] ) ? $presets['hoverIntensity'] : 'subtle';

		// --- Existing tokens ---

		// Shadow tokens (first — buttons/cards may reference them).
		$shadows = isset( self::$shadow_presets[ $shadow_depth ] )
			? self::$shadow_presets[ $shadow_depth ]
			: self::$shadow_presets['subtle'];

		foreach ( $shadows as $name => $value ) {
			$this->set( $name, $value );
		}

		// Roundness tokens.
		$radii = isset( self::$roundness_presets[ $roundness ] )
			? self::$roundness_presets[ $roundness ]
			: self::$roundness_presets['default'];

		foreach ( $radii as $name => $value ) {
			$this->set( $name, $value );
		}

		// Button primary tokens.
		$buttons = isset( self::$button_presets[ $button_style ] )
			? self::$button_presets[ $button_style ]
			: self::$button_presets['solid'];

		foreach ( $buttons as $name => $value ) {
			$this->set( $name, $value );
		}

		// Button secondary tokens (auto-derived from primary style).
		$secondary = isset( self::$button_secondary_map[ $button_style ] )
			? self::$button_secondary_map[ $button_style ]
			: self::$button_secondary_map['solid'];

		foreach ( $secondary as $name => $value ) {
			$this->set( $name, $value );
		}

		// Card tokens.
		$cards = isset( self::$card_presets[ $card_style ] )
			? self::$card_presets[ $card_style ]
			: self::$card_presets['flat'];

		foreach ( $cards as $name => $value ) {
			$this->set( $name, $value );
		}

		// Spacing tokens (base × density multiplier).
		$multiplier = isset( self::$spacing_multipliers[ $spacing_density ] )
			? self::$spacing_multipliers[ $spacing_density ]
			: 1.0;

		foreach ( self::$spacing_base as $name => $base_rem ) {
			$value = round( $base_rem * $multiplier, 3 );
			$this->set( $name, $value . 'rem' );
		}

		// Type scale tokens.
		foreach ( self::$text_scale as $name => $value ) {
			$this->set( $name, $value );
		}

		foreach ( self::$heading_scale as $name => $value ) {
			$this->set( $name, $value );
		}

		// --- Phase 1 new tokens ---

		// Section layout tokens (scaled by density).
		foreach ( self::$section_layout_base as $name => $base_rem ) {
			$value = round( $base_rem * $multiplier, 3 );
			$this->set( $name, $value . 'rem' );
		}
		$this->set( 'content-max-width', '1200px' );

		// Card layout tokens (scaled by density).
		foreach ( self::$card_layout_base as $name => $base_rem ) {
			$value = round( $base_rem * $multiplier, 3 );
			$this->set( $name, $value . 'rem' );
		}

		// Input/form tokens.
		$inputs = isset( self::$input_presets[ $input_style ] )
			? self::$input_presets[ $input_style ]
			: self::$input_presets['boxed'];

		foreach ( $inputs as $name => $value ) {
			$this->set( $name, $value );
		}

		// Badge tokens.
		$badges = isset( self::$badge_presets[ $badge_style ] )
			? self::$badge_presets[ $badge_style ]
			: self::$badge_presets['subtle'];

		foreach ( $badges as $name => $value ) {
			$this->set( $name, $value );
		}

		// Link tokens.
		foreach ( self::$link_tokens as $name => $value ) {
			$this->set( $name, $value );
		}

		// Image treatment tokens.
		$images = isset( self::$image_presets[ $image_treatment ] )
			? self::$image_presets[ $image_treatment ]
			: self::$image_presets['natural'];

		foreach ( $images as $name => $value ) {
			$this->set( $name, $value );
		}

		// Hover state tokens (registered after buttons/cards since they reference them).
		$hovers = isset( self::$hover_presets[ $hover_intensity ] )
			? self::$hover_presets[ $hover_intensity ]
			: self::$hover_presets['subtle'];

		foreach ( $hovers as $name => $value ) {
			$this->set( $name, $value );
		}

		// Soft buttons need tint-based hover instead of darkening — prevents ugly results on tinted backgrounds.
		if ( 'soft' === $button_style && 'none' !== $hover_intensity ) {
			$this->set( 'btn-hover-bg', 'color-mix(in srgb, var(--wp--preset--color--primary) 25%, var(--wp--preset--color--background))' );
			$this->set( 'btn-hover-text', 'var(--wp--preset--color--primary)' );
		}

		// Dark section tokens.
		foreach ( self::$dark_section_tokens as $name => $value ) {
			$this->set( $name, $value );
		}

		// --- Phase 3 polish tokens ---

		// Typography expansion.
		foreach ( self::$typography_tokens as $name => $value ) {
			$this->set( $name, $value );
		}

		// Gradient tokens.
		foreach ( self::$gradient_tokens as $name => $value ) {
			$this->set( $name, $value );
		}

		// Overlay tokens.
		foreach ( self::$overlay_tokens as $name => $value ) {
			$this->set( $name, $value );
		}

		// Divider tokens.
		foreach ( self::$divider_tokens as $name => $value ) {
			$this->set( $name, $value );
		}

		// Navigation tokens.
		foreach ( self::$navigation_tokens as $name => $value ) {
			$this->set( $name, $value );
		}

		// Icon tokens.
		foreach ( self::$icon_tokens as $name => $value ) {
			$this->set( $name, $value );
		}

		// --- Phase 4 competition tokens ---

		// Motion/animation tokens.
		$motion_style = isset( $presets['motionStyle'] ) ? $presets['motionStyle'] : 'subtle';
		$motion       = isset( self::$motion_presets[ $motion_style ] )
			? self::$motion_presets[ $motion_style ]
			: self::$motion_presets['subtle'];

		foreach ( $motion as $name => $value ) {
			$this->set( $name, $value );
		}

		// Micro-interaction tokens.
		foreach ( self::$micro_interaction_tokens as $name => $value ) {
			$this->set( $name, $value );
		}

		// Accessibility tokens.
		foreach ( self::$accessibility_tokens as $name => $value ) {
			$this->set( $name, $value );
		}
	}

	/**
	 * Generate the full CSS string for :root declaration.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS custom properties block.
	 */
	public function get_css_string() {
		if ( empty( $this->tokens ) ) {
			return '';
		}

		$lines = array();

		foreach ( $this->tokens as $name => $value ) {
			$lines[] = sprintf(
				"\t--%s-%s: %s;",
				self::PREFIX,
				esc_attr( $name ),
				esc_attr( $value )
			);
		}

		return ":root {\n" . implode( "\n", $lines ) . "\n}\n";
	}

	/**
	 * Get legacy variable mappings from old naming to new token names.
	 *
	 * Maps old --color--primary, --color--secondary etc. to the
	 * new --spectra-chromatic{N}-{shade} token names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Legacy variable name => new variable reference.
	 */
	public function get_legacy_mapping() {
		$mapping = array();

		// Map chromatic1 to old 'primary' naming.
		$chromatic_to_legacy = array(
			1 => 'primary',
			2 => 'secondary',
		);

		foreach ( $chromatic_to_legacy as $index => $legacy_name ) {
			$base_key = "chromatic{$index}-4";

			if ( null === $this->get( $base_key ) ) {
				continue;
			}

			$mapping[ "--color--{$legacy_name}" ] = 'var(--' . self::PREFIX . "-{$base_key})";

			// Map shade variants.
			$shade_map = array(
				'light'      => "chromatic{$index}-2",
				'lighter'    => "chromatic{$index}-1",
				'lightest'   => "chromatic{$index}-1",
				'dark'       => "chromatic{$index}-6",
				'darker'     => "chromatic{$index}-7",
				'darkest'    => "chromatic{$index}-7",
				'near-white' => "chromatic{$index}-1",
				'near-black' => "chromatic{$index}-7",
			);

			foreach ( $shade_map as $old_suffix => $new_key ) {
				if ( null !== $this->get( $new_key ) ) {
					$mapping[ "--color--{$legacy_name}-{$old_suffix}" ] = 'var(--' . self::PREFIX . "-{$new_key})";
				}
			}
		}

		// Map 'base' to neutral-7.
		if ( null !== $this->get( 'neutral-7' ) ) {
			$mapping['--color--base'] = 'var(--' . self::PREFIX . '-neutral-7)';
		}

		return $mapping;
	}

	/**
	 * Get the full CSS string including legacy variable mappings.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS with both new and legacy variables.
	 */
	public function get_css_string_with_legacy() {
		if ( empty( $this->tokens ) ) {
			return '';
		}

		$lines = array();

		// New tokens.
		foreach ( $this->tokens as $name => $value ) {
			$lines[] = sprintf(
				"\t--%s-%s: %s;",
				self::PREFIX,
				esc_attr( $name ),
				esc_attr( $value )
			);
		}

		// Legacy mappings.
		$legacy = $this->get_legacy_mapping();
		if ( ! empty( $legacy ) ) {
			$lines[] = '';
			$lines[] = "\t/* Legacy Spectra Pro variable mappings (deprecated) */";
			foreach ( $legacy as $old_name => $new_ref ) {
				$lines[] = sprintf( "\t%s: %s;", esc_attr( $old_name ), esc_attr( $new_ref ) );
			}
		}

		return ":root {\n" . implode( "\n", $lines ) . "\n}\n";
	}

	/**
	 * Format tokens as a WordPress theme.json color palette array.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{slug: string, color: string, name: string}> Array of palette entries.
	 */
	public function get_wp_palette() {
		$palette = array();

		foreach ( $this->tokens as $name => $value ) {
			// Only include shade tokens (not opacity, not constants like 'white', 'transparent').
			if ( ! $this->is_shade_token( $name ) ) {
				continue;
			}

			$slug  = self::PREFIX . '-' . self::kebab_case_name( $name );
			$label = $this->get_palette_label( $name );

			$palette[] = array(
				'slug'  => $slug,
				'color' => $value,
				'name'  => $label,
			);
		}

		return $palette;
	}

	/**
	 * Check if a token name is a shade token (neutral or chromatic shade).
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Token name.
	 * @return bool True if shade token.
	 */
	private function is_shade_token( $name ) {
		// Neutral shades: neutral-0 through neutral-7.
		if ( preg_match( '/^neutral-\d$/', $name ) ) {
			return true;
		}

		// Chromatic shades: chromatic{N}-{N} (indices 1-11, including dark shades 8-11).
		if ( preg_match( '/^chromatic\d+-\d{1,2}$/', $name ) ) {
			return true;
		}

		// Include white as a palette entry (users need pure white).
		if ( 'white' === $name ) {
			return true;
		}

		return false;
	}

	/**
	 * Generate a human-readable palette label for a token name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Token name (e.g., 'chromatic1-4', 'neutral-2').
	 * @return string Display label.
	 */
	private function get_palette_label( $name ) {
		// Check for custom name.
		if ( isset( $this->color_names[ $name ] ) ) {
			return $this->color_names[ $name ];
		}

		// Constants.
		if ( 'white' === $name ) {
			return 'White';
		}

		// Neutral shades.
		if ( preg_match( '/^neutral-(\d)$/', $name, $matches ) ) {
			$shade_labels = array(
				0 => 'Neutral · Lightest',
				1 => 'Neutral · Lighter',
				2 => 'Neutral · Light',
				3 => 'Neutral · Mid-Light',
				4 => 'Neutral · Mid',
				5 => 'Neutral · Mid-Dark',
				6 => 'Neutral · Dark',
				7 => 'Neutral · Darkest',
			);
			$index        = (int) $matches[1];
			return isset( $shade_labels[ $index ] ) ? $shade_labels[ $index ] : "Neutral {$index}";
		}

		// Chromatic shades (indices 1-11, including black-mix dark shades 8-11).
		if ( preg_match( '/^chromatic(\d+)-(\d{1,2})$/', $name, $matches ) ) {
			$chromatic_index = (int) $matches[1];
			$shade_index     = (int) $matches[2];

			// Use custom color name if available.
			$base_name_key = "chromatic{$chromatic_index}";
			$color_label   = isset( $this->color_names[ $base_name_key ] )
				? $this->color_names[ $base_name_key ]
				: "Color {$chromatic_index}";

			$shade_suffixes = array(
				1  => 'Lightest',
				2  => 'Light',
				3  => 'Mid-Light',
				4  => '', // Base — no suffix.
				5  => 'Mid-Dark',
				6  => 'Dark',
				7  => 'Darkest',
				8  => 'Darker+1',
				9  => 'Darker+2',
				10 => 'Darker+3',
				11 => 'Darker+4',
			);

			$suffix = isset( $shade_suffixes[ $shade_index ] ) ? $shade_suffixes[ $shade_index ] : "Shade {$shade_index}";

			return empty( $suffix ) ? $color_label : "{$color_label} · {$suffix}";
		}

		return $name;
	}

	/**
	 * Format a palette slug into a human-readable label.
	 *
	 * Strips the `sg-` prefix if present, then converts the remaining
	 * hyphen-separated slug into title-cased words (e.g. `sg-secondary` → `Secondary`,
	 * `background` → `Background`, `sg-mid-dark` → `Mid Dark`).
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Palette slug (e.g. 'sg-secondary', 'primary').
	 * @return string Human-readable label.
	 */
	public static function format_slug_label( $slug ) {
		if ( 0 === strpos( $slug, 'sg-' ) ) {
			$label = ucwords( str_replace( '-', ' ', substr( $slug, 3 ) ) );
			return "{$label} ({$slug})";
		}
		return ucwords( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * Convert a token name to kebab-case for use as a WP palette slug.
	 *
	 * WordPress applies _wp_to_kebab_case() when generating CSS custom property
	 * names from palette slugs, inserting hyphens between letters and digits.
	 * But var() references use the raw slug. To prevent mismatches (e.g.
	 * --wp--preset--color--spectra-chromatic-1-7 vs var(--wp--preset--color--spectra-chromatic1-7)),
	 * we pre-kebab-case the slug so WP's transform is a no-op.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Token name (e.g., 'chromatic1-7').
	 * @return string Kebab-cased name (e.g., 'chromatic-1-7').
	 */
	public static function kebab_case_name( $name ): string {
		// Insert hyphen between a letter and a digit: chromatic1 → chromatic-1.
		$name = preg_replace( '/([a-zA-Z])(\d)/', '$1-$2', $name ) ?? $name;
		// Insert hyphen between a digit and a letter: 1abc → 1-abc.
		$name = preg_replace( '/(\d)([a-zA-Z])/', '$1-$2', $name ) ?? $name;
		return $name;
	}

	/**
	 * Clear all tokens and schemes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clear(): void {
		$this->tokens      = array();
		$this->schemes     = array();
		$this->color_names = array();
	}
}
