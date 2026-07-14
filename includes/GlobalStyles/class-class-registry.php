<?php
/**
 * Class registry for generating all Global Styles utility classes.
 *
 * Static utility classes are generated from hardcoded arrays (opcached).
 * Dynamic classes (colors, spacing, fonts) read Style Guide config and are
 * WP object-cached.
 *
 * @package Spectra\GlobalStyles
 * @since   1.0.0
 */

namespace SpectraBlocks\GlobalStyles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClassRegistry.
 *
 * @since 1.0.0
 */
class ClassRegistry {

	/**
	 * Maps SG chromatic shade indices (1-11) to Tailwind shade labels (50-950).
	 *
	 * @since 1.0.0
	 * @var array<int, string>
	 */
	const CHROMATIC_SHADE_MAP = array(
		1  => '50',
		2  => '100',
		3  => '200',
		4  => '300',
		5  => '400',
		6  => '500',
		7  => '600',
		8  => '700',
		9  => '800',
		10 => '900',
		11 => '950',
	);

	/**
	 * Palette channel → CSS property map for `{channel}-{slug}-{shade}`
	 * color utilities. THE map {@see self::get_color_classes()} consumes —
	 * the exported palette grammar derives its channel list from these
	 * same constants ({@see self::palette_channels()}), so the export can
	 * never drift from what the generator actually emits. (Its hand-typed
	 * predecessor, `PALETTE_CHANNELS`, drifted from day one: it listed 7
	 * channels while the generator emitted 16 — missing outline, fill,
	 * stroke, and all six directional border channels.)
	 *
	 * `fill`/`stroke` are SVG paint — they emit the `fill`/`stroke`
	 * properties themselves (SVG has no `*-color` longhand).
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const PALETTE_PROPERTY_MAP = array(
		'bg'      => 'background',
		'text'    => 'color',
		'border'  => 'border-color',
		'outline' => 'outline-color',
		'overlay' => 'background',
		'fill'    => 'fill',
		'stroke'  => 'stroke',
	);

	/**
	 * Directional border palette channels (single side).
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const PALETTE_BORDER_POSITION_MAP = array(
		'border-t' => 'border-top-color',
		'border-r' => 'border-right-color',
		'border-b' => 'border-bottom-color',
		'border-l' => 'border-left-color',
	);

	/**
	 * Multi-side border palette channels.
	 *
	 * @since 1.0.0
	 * @var array<string, array<int, string>>
	 */
	const PALETTE_BORDER_MULTI_MAP = array(
		'border-x' => array( 'border-left-color', 'border-right-color' ),
		'border-y' => array( 'border-top-color', 'border-bottom-color' ),
	);

	/**
	 * Gradient stop channels, in CASCADE ORDER — `via-*` overrides
	 * `--tw-gradient-stops` with the 3-stop form so it must be emitted
	 * after `from-*`; `to-*` only sets `--tw-gradient-to`. The generator
	 * iterates this constant in order ({@see self::get_color_classes()}).
	 *
	 * @since 1.0.0
	 * @var array<int, string>
	 */
	const PALETTE_GRADIENT_CHANNELS = array( 'from', 'via', 'to' );

	/**
	 * Every palette channel prefix accepting `{channel}-{slug}-{shade}` —
	 * DERIVED from the maps the generator consumes; exported via the
	 * metadata `contract` so the SaaS-side mirror never hand-syncs it.
	 *
	 * @since 1.0.0
	 * @return array<int, string>
	 */
	public static function palette_channels(): array {
		return array_merge(
			array_keys( self::PALETTE_PROPERTY_MAP ),
			array_keys( self::PALETTE_BORDER_POSITION_MAP ),
			array_keys( self::PALETTE_BORDER_MULTI_MAP ),
			self::PALETTE_GRADIENT_CHANNELS
		);
	}

	/**
	 * Maps SG neutral shade indices (0-7) to Tailwind shade labels.
	 * Gaps at 400, 700, 900 are filled via NEUTRAL_GAP_FILL.
	 *
	 * @since 1.0.0
	 * @var array<int, string>
	 */
	const NEUTRAL_SHADE_MAP = array(
		0 => '50',
		1 => '100',
		2 => '200',
		3 => '300',
		4 => '500',
		5 => '600',
		6 => '800',
		7 => '950',
	);

	/**
	 * Fills neutral Tailwind shade gaps using the nearest available SG neutral token.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	const NEUTRAL_GAP_FILL = array(
		'400' => 'neutral-3',
		'700' => 'neutral-5',
		'900' => 'neutral-6',
	);

	/**
	 * Opacity steps for color-mix() variants (10% through 90%).
	 *
	 * @since 1.0.0
	 * @var array<int>
	 */
	const OPACITY_STEPS = array( 10, 20, 30, 40, 50, 60, 70, 80, 90 );

	/**
	 * WP object cache key for dynamic classes.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CACHE_KEY = 'spectra_blocks_gs_class_registry';

	/**
	 * WP object cache group.
	 *
	 * Standardized to match the StyleGuide engine's cache group so cache
	 * invalidation can be coordinated via a single group reference.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CACHE_GROUP = 'spectra_blocks';

	/**
	 * In-memory cache for merged classes.
	 *
	 * @since 1.0.0
	 * @var array|null
	 */
	private static $all_classes_cache = null;

	/**
	 * In-memory cache for dynamic classes only.
	 *
	 * @since 1.0.0
	 * @var array|null
	 */
	private static $dynamic_classes_cache = null;

	// ─────────────────────────────────────────────────────────────
	// STATIC UTILITY GENERATORS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Display utility classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_display_classes(): array {
		return array(
			'block'        => array(
				'css'         => 'display: block;',
				'title'       => 'Block',
				'description' => 'Sets the element to block display.',
				'category'    => 'display',
				'tags'        => array( 'display', 'block' ),
			),
			'contents'     => array(
				'css'         => 'display: contents;',
				'title'       => 'Contents',
				'description' => 'Makes the element act as if replaced by its children.',
				'category'    => 'display',
				'tags'        => array( 'display', 'contents' ),
			),
			'flex'         => array(
				'css'         => 'display: flex;',
				'title'       => 'Flex',
				'description' => 'Sets the element to flex display.',
				'category'    => 'display',
				'tags'        => array( 'display', 'flex' ),
			),
			'grid'         => array(
				'css'         => 'display: grid;',
				'title'       => 'Grid',
				'description' => 'Sets the element to grid display.',
				'category'    => 'display',
				'tags'        => array( 'display', 'grid' ),
			),
			'inline'       => array(
				'css'         => 'display: inline;',
				'title'       => 'Inline',
				'description' => 'Sets the element to inline display.',
				'category'    => 'display',
				'tags'        => array( 'display', 'inline' ),
			),
			'inline-block' => array(
				'css'         => 'display: inline-block;',
				'title'       => 'Inline block',
				'description' => 'Sets the element to inline-block display.',
				'category'    => 'display',
				'tags'        => array( 'display', 'inline-block' ),
			),
			'inline-flex'  => array(
				'css'         => 'display: inline-flex;',
				'title'       => 'Inline flex',
				'description' => 'Sets the element to inline-flex display.',
				'category'    => 'display',
				'tags'        => array( 'display', 'inline-flex' ),
			),
			'inline-grid'  => array(
				'css'         => 'display: inline-grid;',
				'title'       => 'Inline grid',
				'description' => 'Sets the element to inline-grid display.',
				'category'    => 'display',
				'tags'        => array( 'display', 'inline-grid' ),
			),
			'hidden'       => array(
				'css'         => 'display: none;',
				'title'       => 'Hidden',
				'description' => 'Hides the element from display.',
				'category'    => 'display',
				'tags'        => array( 'display', 'hidden', 'none' ),
			),
		);
	}

	/**
	 * Border utility classes (width, style, radius).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_border_classes(): array {
		$classes = array();

		// --- Border Width ---
		// Tailwind preflight parity: every border-width utility pairs with
		// `border-style: solid` so an author's `<div class="border-t">` renders
		// the line Tailwind's reset would have produced. Without the paired
		// style:solid declaration the width applies but the style defaults to
		// `none` and the border is invisible — breaks dividers, card accents,
		// input underlines, and every Tailwind-authored layout that expects
		// the preflight default.
		$widths = array(
			'border-0' => array(
				'css'   => 'border-width: 0; border-style: none;',
				'title' => 'No border',
			),
			'border'   => array(
				'css'   => 'border-width: 1px; border-style: solid;',
				'title' => 'Border 1px',
			),
			'border-2' => array(
				'css'   => 'border-width: 2px; border-style: solid;',
				'title' => 'Border 2px',
			),
			'border-4' => array(
				'css'   => 'border-width: 4px; border-style: solid;',
				'title' => 'Border 4px',
			),
			'border-8' => array(
				'css'   => 'border-width: 8px; border-style: solid;',
				'title' => 'Border 8px',
			),
		);

		foreach ( $widths as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'border',
				'tags'        => array( 'border', 'width' ),
			);
		}

		// --- Per-side border widths (scale form; bracket form still routes via PREFIX_MAP). ---
		// Physical properties intentional — matches PREFIX_MAP ('border-t' => 'border-top-width').
		// Each width > 0 ships the paired per-side `*-style: solid` so the
		// line renders (Tailwind-preflight parity — see note on the all-sides
		// width block above).
		$side_map   = array(
			't' => array(
				'width' => array( 'border-top-width' ),
				'style' => array( 'border-top-style' ),
			),
			'r' => array(
				'width' => array( 'border-right-width' ),
				'style' => array( 'border-right-style' ),
			),
			'b' => array(
				'width' => array( 'border-bottom-width' ),
				'style' => array( 'border-bottom-style' ),
			),
			'l' => array(
				'width' => array( 'border-left-width' ),
				'style' => array( 'border-left-style' ),
			),
			'x' => array(
				'width' => array( 'border-left-width', 'border-right-width' ),
				'style' => array( 'border-left-style', 'border-right-style' ),
			),
			'y' => array(
				'width' => array( 'border-top-width', 'border-bottom-width' ),
				'style' => array( 'border-top-style', 'border-bottom-style' ),
			),
		);
		$side_scale = array( 0, 1, 2, 4, 8 );
		foreach ( $side_map as $side_key => $props ) {
			foreach ( $side_scale as $px ) {
				$name  = 1 === $px ? "border-{$side_key}" : "border-{$side_key}-{$px}";
				$decls = array();
				foreach ( $props['width'] as $prop ) {
					$decls[] = $prop . ': ' . $px . 'px;';
				}
				if ( $px > 0 ) {
					foreach ( $props['style'] as $sprop ) {
						$decls[] = $sprop . ': solid;';
					}
				}
				$classes[ $name ] = array(
					'css'         => implode( ' ', $decls ),
					'title'       => "Border {$side_key} {$px}px",
					'description' => "Sets border-{$side_key} width to {$px}px.",
					'category'    => 'border',
					'tags'        => array( 'border', 'width', $side_key ),
				);
			}
		}

		// --- Border Style ---
		$styles = array( 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset' );

		// All-sides styles.
		foreach ( $styles as $style ) {
			$classes[ "border-{$style}" ] = array(
				'css'         => "border-style: {$style};",
				'title'       => ucfirst( $style ) . ' line border',
				'description' => "Gives the border a {$style} line.",
				'category'    => 'border',
				'tags'        => array( 'border', $style ),
			);
		}

		// Positional styles (top, bottom, left, right) — includes "none".
		$style_positions = array(
			't' => array(
				'prop'  => 'border-block-start-style',
				'label' => 'top',
			),
			'b' => array(
				'prop'  => 'border-block-end-style',
				'label' => 'bottom',
			),
			'l' => array(
				'prop'  => 'border-inline-start-style',
				'label' => 'left',
			),
			'r' => array(
				'prop'  => 'border-inline-end-style',
				'label' => 'right',
			),
		);

		$positional_styles = array_merge( array( 'none' ), $styles );

		foreach ( $style_positions as $pos => $pos_data ) {
			foreach ( $positional_styles as $style ) {
				$classes[ "border-{$pos}-{$style}" ] = array(
					'css'         => "{$pos_data['prop']}: {$style};",
					'title'       => ucfirst( $style ) . " {$pos_data['label']} border",
					'description' => 'none' === $style
						? "Removes the {$pos_data['label']} border."
						: "Gives the {$pos_data['label']} border a {$style} line.",
					'category'    => 'border',
					'tags'        => array( 'border', $pos_data['label'], $style ),
				);
			}
		}

		// --- Border Radius ---
		// Values aligned with Tailwind v4 defaults so SaaS-authored markup
		// (which uses v4 in its preview generator) renders consistently
		// against Block-UI and live front-end. v3 had `sm: 0.125rem` and no
		// `xs`/`4xl`; v4 promoted `sm` to `0.25rem`, added `xs: 0.125rem`
		// and `4xl: 2rem`.
		// @since 1.0.0
		$radius_sizes = array(
			'none' => '0',
			'xs'   => '0.125rem',
			'sm'   => '0.25rem',
			''     => '0.25rem', // base — class is "rounded" not "rounded-base".
			'md'   => '0.375rem',
			'lg'   => '0.5rem',
			'xl'   => '0.75rem',
			'2xl'  => '1rem',
			'3xl'  => '1.5rem',
			'4xl'  => '2rem',
			'full' => '9999px',
		);

		// All-corners.
		foreach ( $radius_sizes as $size_key => $value ) {
			$suffix = '' === $size_key ? '' : "-{$size_key}";
			$label  = '' === $size_key ? 'Base' : strtoupper( $size_key );

			$classes[ "rounded{$suffix}" ] = array(
				'css'         => "border-radius: {$value};",
				'title'       => "{$label} radius",
				'description' => 'none' === $size_key
					? 'Removes border radius.'
					: "Applies {$label} border radius.",
				'category'    => 'border',
				'tags'        => array( 'radius', ! empty( $size_key ) ? $size_key : 'base' ),
			);
		}

		// Side radius positions.
		$radius_sides = array(
			't'  => array(
				'props' => array( 'border-top-left-radius', 'border-top-right-radius' ),
				'label' => 'top',
			),
			'b'  => array(
				'props' => array( 'border-bottom-left-radius', 'border-bottom-right-radius' ),
				'label' => 'bottom',
			),
			'l'  => array(
				'props' => array( 'border-top-left-radius', 'border-bottom-left-radius' ),
				'label' => 'left',
			),
			'r'  => array(
				'props' => array( 'border-top-right-radius', 'border-bottom-right-radius' ),
				'label' => 'right',
			),
			'tl' => array(
				'props' => array( 'border-top-left-radius' ),
				'label' => 'top-left',
			),
			'tr' => array(
				'props' => array( 'border-top-right-radius' ),
				'label' => 'top-right',
			),
			'bl' => array(
				'props' => array( 'border-bottom-left-radius' ),
				'label' => 'bottom-left',
			),
			'br' => array(
				'props' => array( 'border-bottom-right-radius' ),
				'label' => 'bottom-right',
			),
		);

		foreach ( $radius_sides as $pos => $pos_data ) {
			foreach ( $radius_sizes as $size_key => $value ) {
				$suffix     = '' === $size_key ? '' : "-{$size_key}";
				$size_label = '' === $size_key ? 'Base' : strtoupper( $size_key );
				$css_parts  = array();

				foreach ( $pos_data['props'] as $prop ) {
					$css_parts[] = "{$prop}: {$value};";
				}

				$classes[ "rounded-{$pos}{$suffix}" ] = array(
					'css'         => implode( ' ', $css_parts ),
					'title'       => "{$size_label} {$pos_data['label']} radius",
					'description' => 'none' === $size_key
						? "Makes {$pos_data['label']} corners sharp."
						: "Adds {$size_label} curve to {$pos_data['label']} corners.",
					'category'    => 'border',
					'tags'        => array( 'radius', $pos_data['label'], ! empty( $size_key ) ? $size_key : 'base' ),
				);
			}
		}

		return $classes;
	}

	/**
	 * Outline utility classes (width, style, offset, enum).
	 *
	 * Token-dispatch contract:
	 *   1. Known-class first: outline-none, outline-solid|dashed|dotted|double,
	 *      outline-0|1|2|4|8, outline-offset-0|1|2|4|8, outline-inherit|current.
	 *   2. Bracket form outline-offset-[Npx] routes via PREFIX_MAP entry
	 *      'outline-offset' => 'outline-offset' (matched before 'outline').
	 *   3. Bracket form outline-[color|length] routes via PREFIX_MAP shape-aware
	 *      '__shape:outline' → outline-color (color) or outline-width (length).
	 *   4. outline-{palette}-{shade} via get_color_classes() property_map.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_outline_classes(): array {
		$classes = array();

		$classes['outline-none'] = array(
			'css'         => 'outline: 2px solid transparent; outline-offset: 2px;',
			'title'       => 'No outline',
			'description' => 'Removes the visible outline while preserving accessibility focus rings.',
			'category'    => 'outline',
			'tags'        => array( 'outline', 'none' ),
		);

		foreach ( array( 'solid', 'dashed', 'dotted', 'double' ) as $style ) {
			$classes[ "outline-{$style}" ] = array(
				'css'         => "outline-style: {$style};",
				'title'       => ucfirst( $style ) . ' outline',
				'description' => "Sets outline-style to {$style}.",
				'category'    => 'outline',
				'tags'        => array( 'outline', 'style', $style ),
			);
		}

		foreach ( array( 0, 1, 2, 4, 8 ) as $px ) {
			$classes[ "outline-{$px}" ]        = array(
				'css'         => "outline-width: {$px}px;",
				'title'       => "Outline {$px}px",
				'description' => "Sets outline-width to {$px}px.",
				'category'    => 'outline',
				'tags'        => array( 'outline', 'width' ),
			);
			$classes[ "outline-offset-{$px}" ] = array(
				'css'         => "outline-offset: {$px}px;",
				'title'       => "Outline offset {$px}px",
				'description' => "Sets outline-offset to {$px}px.",
				'category'    => 'outline',
				'tags'        => array( 'outline', 'offset' ),
			);
		}

		$classes['outline-inherit'] = array(
			'css'         => 'outline-color: inherit;',
			'title'       => 'Outline inherit',
			'description' => 'Inherits outline color from parent.',
			'category'    => 'outline',
			'tags'        => array( 'outline', 'color', 'inherit' ),
		);
		$classes['outline-current'] = array(
			'css'         => 'outline-color: currentColor;',
			'title'       => 'Outline currentColor',
			'description' => 'Uses currentColor for outline.',
			'category'    => 'outline',
			'tags'        => array( 'outline', 'color', 'current' ),
		);

		return $classes;
	}

	/**
	 * Sizing utility classes (width, height, aspect-ratio, object-fit/position).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_sizing_classes(): array {
		$classes = array();

		// Fixed Tailwind rem scale (shared by width, height, and spacing).
		$rem_scale = array(
			'0'   => '0',
			'px'  => '1px',
			'0.5' => '0.125rem',
			'1'   => '0.25rem',
			'1.5' => '0.375rem',
			'2'   => '0.5rem',
			'2.5' => '0.625rem',
			'3'   => '0.75rem',
			'3.5' => '0.875rem',
			'4'   => '1rem',
			'5'   => '1.25rem',
			'6'   => '1.5rem',
			'7'   => '1.75rem',
			'8'   => '2rem',
			'9'   => '2.25rem',
			'10'  => '2.5rem',
			'11'  => '2.75rem',
			'12'  => '3rem',
			'14'  => '3.5rem',
			'16'  => '4rem',
			'20'  => '5rem',
			'24'  => '6rem',
			'28'  => '7rem',
			'32'  => '8rem',
			'36'  => '9rem',
			'40'  => '10rem',
			'44'  => '11rem',
			'48'  => '12rem',
			'52'  => '13rem',
			'56'  => '14rem',
			'60'  => '15rem',
			'64'  => '16rem',
			'72'  => '18rem',
			'80'  => '20rem',
			'96'  => '24rem',
		);

		// --- Width ---
		$width_classes = array(
			'w-auto'       => array(
				'css'   => 'width: auto;',
				'title' => 'Auto width',
			),
			'w-fit'        => array(
				'css'   => 'inline-size: fit-content;',
				'title' => 'Fit content width',
			),
			'w-full'       => array(
				'css'   => 'inline-size: 100%;',
				'title' => 'Full width',
			),
			'w-screen'     => array(
				'css'   => 'inline-size: 100vw;',
				'title' => 'Screen width',
			),
			'w-1/2'        => array(
				'css'   => 'width: 50%;',
				'title' => 'Half width',
			),
			'w-1/3'        => array(
				'css'   => 'width: 33.333333%;',
				'title' => 'One third width',
			),
			'w-2/3'        => array(
				'css'   => 'width: 66.666667%;',
				'title' => 'Two thirds width',
			),
			'w-1/4'        => array(
				'css'   => 'width: 25%;',
				'title' => 'One quarter width',
			),
			'w-2/4'        => array(
				'css'   => 'width: 50%;',
				'title' => 'Two quarters width',
			),
			'w-3/4'        => array(
				'css'   => 'width: 75%;',
				'title' => 'Three quarters width',
			),
			'w-1/5'        => array(
				'css'   => 'width: 20%;',
				'title' => 'One fifth width',
			),
			'w-2/5'        => array(
				'css'   => 'width: 40%;',
				'title' => 'Two fifths width',
			),
			'w-3/5'        => array(
				'css'   => 'width: 60%;',
				'title' => 'Three fifths width',
			),
			'w-4/5'        => array(
				'css'   => 'width: 80%;',
				'title' => 'Four fifths width',
			),
			'w-1/6'        => array(
				'css'   => 'width: 16.666667%;',
				'title' => 'One sixth width',
			),
			'w-5/6'        => array(
				'css'   => 'width: 83.333333%;',
				'title' => 'Five sixths width',
			),
			'min-w-0'      => array(
				'css'   => 'min-inline-size: 0;',
				'title' => 'Min width 0',
			),
			'min-w-full'   => array(
				'css'   => 'min-inline-size: 100%;',
				'title' => 'Min full width',
			),
			'min-w-fit'    => array(
				'css'   => 'min-inline-size: fit-content;',
				'title' => 'Min fit width',
			),
			'max-w-none'   => array(
				'css'   => 'max-inline-size: none;',
				'title' => 'No max width',
			),
			'max-w-full'   => array(
				'css'   => 'max-inline-size: 100%;',
				'title' => 'Max full width',
			),
			'max-w-screen' => array(
				'css'   => 'max-inline-size: 100vw;',
				'title' => 'Max screen width',
			),
		);

		// Fixed rem-scale widths: w-0 through w-96.
		foreach ( $rem_scale as $size_key => $value ) {
			$width_classes[ "w-{$size_key}" ] = array(
				'css'   => "inline-size: {$value};",
				'title' => "Width {$size_key}",
			);
		}

		// Max-width tokens (Tailwind container breakpoints + prose).
		$max_width_tokens = array(
			'max-w-xs'    => array(
				'css'   => 'max-inline-size: 20rem;',
				'title' => 'Max width xs',
			),
			'max-w-sm'    => array(
				'css'   => 'max-inline-size: 24rem;',
				'title' => 'Max width sm',
			),
			'max-w-md'    => array(
				'css'   => 'max-inline-size: 28rem;',
				'title' => 'Max width md',
			),
			'max-w-lg'    => array(
				'css'   => 'max-inline-size: 32rem;',
				'title' => 'Max width lg',
			),
			'max-w-xl'    => array(
				'css'   => 'max-inline-size: 36rem;',
				'title' => 'Max width xl',
			),
			'max-w-2xl'   => array(
				'css'   => 'max-inline-size: 42rem;',
				'title' => 'Max width 2xl',
			),
			'max-w-3xl'   => array(
				'css'   => 'max-inline-size: 48rem;',
				'title' => 'Max width 3xl',
			),
			'max-w-4xl'   => array(
				'css'   => 'max-inline-size: 56rem;',
				'title' => 'Max width 4xl',
			),
			'max-w-5xl'   => array(
				'css'   => 'max-inline-size: 64rem;',
				'title' => 'Max width 5xl',
			),
			'max-w-6xl'   => array(
				'css'   => 'max-inline-size: 72rem;',
				'title' => 'Max width 6xl',
			),
			'max-w-7xl'   => array(
				'css'   => 'max-inline-size: 80rem;',
				'title' => 'Max width 7xl',
			),
			'max-w-prose' => array(
				'css'   => 'max-inline-size: 65ch;',
				'title' => 'Max width prose',
			),
		);

		$width_classes = array_merge( $width_classes, $max_width_tokens );

		foreach ( $width_classes as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'sizing',
				'tags'        => array( 'width' ),
			);
		}

		// --- Height ---
		$height_classes = array(
			'h-auto'       => array(
				'css'   => 'block-size: auto;',
				'title' => 'Auto height',
			),
			'h-full'       => array(
				'css'   => 'block-size: 100%;',
				'title' => 'Full height',
			),
			'h-screen'     => array(
				'css'   => 'block-size: 100vh;',
				'title' => 'Screen height',
			),
			'h-1/2'        => array(
				'css'   => 'block-size: 50%;',
				'title' => 'Half height',
			),
			'h-1/3'        => array(
				'css'   => 'block-size: 33.333333%;',
				'title' => 'One third height',
			),
			'h-2/3'        => array(
				'css'   => 'block-size: 66.666667%;',
				'title' => 'Two thirds height',
			),
			'h-1/4'        => array(
				'css'   => 'block-size: 25%;',
				'title' => 'One quarter height',
			),
			'h-2/4'        => array(
				'css'   => 'block-size: 50%;',
				'title' => 'Two quarters height',
			),
			'h-3/4'        => array(
				'css'   => 'block-size: 75%;',
				'title' => 'Three quarters height',
			),
			'h-1/5'        => array(
				'css'   => 'block-size: 20%;',
				'title' => 'One fifth height',
			),
			'h-2/5'        => array(
				'css'   => 'block-size: 40%;',
				'title' => 'Two fifths height',
			),
			'h-3/5'        => array(
				'css'   => 'block-size: 60%;',
				'title' => 'Three fifths height',
			),
			'h-4/5'        => array(
				'css'   => 'block-size: 80%;',
				'title' => 'Four fifths height',
			),
			'h-1/6'        => array(
				'css'   => 'block-size: 16.666667%;',
				'title' => 'One sixth height',
			),
			'h-5/6'        => array(
				'css'   => 'block-size: 83.333333%;',
				'title' => 'Five sixths height',
			),
			'min-h-0'      => array(
				'css'   => 'min-block-size: 0;',
				'title' => 'Min height 0',
			),
			'min-h-full'   => array(
				'css'   => 'min-block-size: 100%;',
				'title' => 'Min full height',
			),
			'min-h-screen' => array(
				'css'   => 'min-block-size: 100vh;',
				'title' => 'Min screen height',
			),
			'min-h-fit'    => array(
				'css'   => 'min-block-size: fit-content;',
				'title' => 'Min fit height',
			),
			'max-h-none'   => array(
				'css'   => 'max-block-size: none;',
				'title' => 'No max height',
			),
			'max-h-full'   => array(
				'css'   => 'max-block-size: 100%;',
				'title' => 'Max full height',
			),
			'max-h-screen' => array(
				'css'   => 'max-block-size: 100vh;',
				'title' => 'Max screen height',
			),
		);

		// Fixed rem-scale heights: h-0 through h-96.
		foreach ( $rem_scale as $size_key => $value ) {
			$height_classes[ "h-{$size_key}" ] = array(
				'css'   => "block-size: {$value};",
				'title' => "Height {$size_key}",
			);
		}

		foreach ( $height_classes as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'sizing',
				'tags'        => array( 'height' ),
			);
		}

		// --- Size (shorthand for matching width + height) ---
		// Tailwind's `size-*` sets both width AND height to the same value.
		// Register the full rem scale so `size-14` / `size-12` / `size-16`
		// work for square icon/avatar tiles without the author having to
		// repeat `w-N h-N`. Arbitrary bracket form (`size-[56px]`) already
		// works via PREFIX_MAP['size'] → [width, height] in the JIT.
		$size_classes = array(
			'size-auto' => array(
				'css'   => 'inline-size: auto; block-size: auto;',
				'title' => 'Auto size',
			),
			// `size-full` is intentionally NOT registered here (nor in the
			// size-keyword map below): it collides with WordPress core's
			// reserved `.size-full` image class and would force full-size
			// `core/image` figures to fill+squish instead of shrink-wrapping.
			// Use `w-full h-full` or arbitrary `size-[100%]` instead.
			'size-fit'  => array(
				'css'   => 'inline-size: fit-content; block-size: fit-content;',
				'title' => 'Fit size',
			),
			'size-min'  => array(
				'css'   => 'inline-size: min-content; block-size: min-content;',
				'title' => 'Min size',
			),
			'size-max'  => array(
				'css'   => 'inline-size: max-content; block-size: max-content;',
				'title' => 'Max size',
			),
		);
		foreach ( $rem_scale as $size_key => $value ) {
			$size_classes[ "size-{$size_key}" ] = array(
				'css'   => "inline-size: {$value}; block-size: {$value};",
				'title' => "Size {$size_key}",
			);
		}
		foreach ( $size_classes as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'sizing',
				'tags'        => array( 'size' ),
			);
		}

		// --- Aspect Ratio ---
		$aspect_classes = array(
			'aspect-auto'   => array(
				'css'   => 'aspect-ratio: auto;',
				'title' => 'Auto aspect ratio',
			),
			'aspect-square' => array(
				'css'   => 'aspect-ratio: 1/1;',
				'title' => 'Square aspect ratio',
			),
			'aspect-video'  => array(
				'css'   => 'aspect-ratio: 16/9;',
				'title' => 'Video aspect ratio',
			),
			'aspect-1/2'    => array(
				'css'   => 'aspect-ratio: 1/2;',
				'title' => '1:2 aspect ratio',
			),
			'aspect-2/1'    => array(
				'css'   => 'aspect-ratio: 2/1;',
				'title' => '2:1 aspect ratio',
			),
			'aspect-2/3'    => array(
				'css'   => 'aspect-ratio: 2/3;',
				'title' => '2:3 aspect ratio',
			),
			'aspect-3/2'    => array(
				'css'   => 'aspect-ratio: 3/2;',
				'title' => '3:2 aspect ratio',
			),
			'aspect-3/4'    => array(
				'css'   => 'aspect-ratio: 3/4;',
				'title' => '3:4 aspect ratio',
			),
			'aspect-4/3'    => array(
				'css'   => 'aspect-ratio: 4/3;',
				'title' => '4:3 aspect ratio',
			),
			'aspect-9/16'   => array(
				'css'   => 'aspect-ratio: 9/16;',
				'title' => '9:16 aspect ratio',
			),
		);

		foreach ( $aspect_classes as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'sizing',
				'tags'        => array( 'aspect', 'ratio' ),
			);
		}

		// --- Object Fit ---
		$object_fit = array(
			'object-contain'    => 'object-fit: contain;',
			'object-cover'      => 'object-fit: cover;',
			'object-fill'       => 'object-fit: fill;',
			'object-none'       => 'object-fit: none;',
			'object-scale-down' => 'object-fit: scale-down;',
		);

		foreach ( $object_fit as $name => $css ) {
			$label            = str_replace( array( 'object-' ), '', $name );
			$classes[ $name ] = array(
				'css'         => $css,
				'title'       => 'Object ' . $label,
				'description' => 'Object ' . $label . '.',
				'category'    => 'sizing',
				'tags'        => array( 'object', 'fit', $label ),
			);
		}

		// --- Object Position ---
		$object_positions = array(
			'object-left-top'     => array(
				'css'   => 'object-position: top left;',
				'title' => 'Object left top',
			),
			'object-top'          => array(
				'css'   => 'object-position: top;',
				'title' => 'Object top',
			),
			'object-right-top'    => array(
				'css'   => 'object-position: top right;',
				'title' => 'Object right top',
			),
			'object-left'         => array(
				'css'   => 'object-position: left;',
				'title' => 'Object left',
			),
			'object-center'       => array(
				'css'   => 'object-position: center;',
				'title' => 'Object center',
			),
			'object-right'        => array(
				'css'   => 'object-position: right;',
				'title' => 'Object right',
			),
			'object-left-bottom'  => array(
				'css'   => 'object-position: bottom left;',
				'title' => 'Object left bottom',
			),
			'object-bottom'       => array(
				'css'   => 'object-position: bottom;',
				'title' => 'Object bottom',
			),
			'object-right-bottom' => array(
				'css'   => 'object-position: bottom right;',
				'title' => 'Object right bottom',
			),
		);

		foreach ( $object_positions as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'sizing',
				'tags'        => array( 'object', 'position' ),
			);
		}

		return $classes;
	}

	/**
	 * Layout utility classes (flex, grid, alignment, z-index).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_layout_classes(): array {
		$classes = array();

		// --- Flex ---
		$flex_classes = array(
			'flex-row'          => array(
				'css'   => 'flex-direction: row;',
				'title' => 'Flex row',
			),
			'flex-row-reverse'  => array(
				'css'   => 'flex-direction: row-reverse;',
				'title' => 'Flex row reverse',
			),
			'flex-col'          => array(
				'css'   => 'flex-direction: column;',
				'title' => 'Flex column',
			),
			'flex-col-reverse'  => array(
				'css'   => 'flex-direction: column-reverse;',
				'title' => 'Flex column reverse',
			),
			'flex-nowrap'       => array(
				'css'   => 'flex-wrap: nowrap;',
				'title' => 'Flex nowrap',
			),
			'flex-wrap'         => array(
				'css'   => 'flex-wrap: wrap;',
				'title' => 'Flex wrap',
			),
			'flex-wrap-reverse' => array(
				'css'   => 'flex-wrap: wrap-reverse;',
				'title' => 'Flex wrap reverse',
			),
			// Flex shorthand.
			'flex-1'            => array(
				'css'   => 'flex: 1 1 0%;',
				'title' => 'Flex 1',
			),
			'flex-auto'         => array(
				'css'   => 'flex: 1 1 auto;',
				'title' => 'Flex auto',
			),
			'flex-initial'      => array(
				'css'   => 'flex: 0 1 auto;',
				'title' => 'Flex initial',
			),
			'flex-none'         => array(
				'css'   => 'flex: none;',
				'title' => 'Flex none',
			),
			// Grow / Shrink.
			'grow'              => array(
				'css'   => 'flex-grow: 1;',
				'title' => 'Grow',
			),
			'grow-0'            => array(
				'css'   => 'flex-grow: 0;',
				'title' => 'Grow 0',
			),
			'shrink'            => array(
				'css'   => 'flex-shrink: 1;',
				'title' => 'Shrink',
			),
			'shrink-0'          => array(
				'css'   => 'flex-shrink: 0;',
				'title' => 'Shrink 0',
			),
		);

		foreach ( $flex_classes as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'layout',
				'tags'        => array( 'flex' ),
			);
		}

		// --- Alignment ---
		$alignment_classes = array(
			// align-content.
			'content-start'         => 'align-content: flex-start;',
			'content-end'           => 'align-content: flex-end;',
			'content-center'        => 'align-content: center;',
			'content-between'       => 'align-content: space-between;',
			'content-around'        => 'align-content: space-around;',
			'content-evenly'        => 'align-content: space-evenly;',
			'content-stretch'       => 'align-content: stretch;',
			// align-items.
			'items-stretch'         => 'align-items: stretch;',
			'items-start'           => 'align-items: flex-start;',
			'items-end'             => 'align-items: flex-end;',
			'items-center'          => 'align-items: center;',
			'items-baseline'        => 'align-items: baseline;',
			// align-self.
			'self-start'            => 'align-self: flex-start;',
			'self-end'              => 'align-self: flex-end;',
			'self-center'           => 'align-self: center;',
			'self-stretch'          => 'align-self: stretch;',
			'self-baseline'         => 'align-self: baseline;',
			// justify-content.
			'justify-start'         => 'justify-content: flex-start;',
			'justify-end'           => 'justify-content: flex-end;',
			'justify-center'        => 'justify-content: center;',
			'justify-between'       => 'justify-content: space-between;',
			'justify-around'        => 'justify-content: space-around;',
			'justify-evenly'        => 'justify-content: space-evenly;',
			// justify-items.
			'justify-items-start'   => 'justify-items: start;',
			'justify-items-end'     => 'justify-items: end;',
			'justify-items-center'  => 'justify-items: center;',
			'justify-items-stretch' => 'justify-items: stretch;',
			// justify-self.
			'justify-self-start'    => 'justify-self: start;',
			'justify-self-end'      => 'justify-self: end;',
			'justify-self-center'   => 'justify-self: center;',
			'justify-self-stretch'  => 'justify-self: stretch;',
		);

		foreach ( $alignment_classes as $name => $css ) {
			$title            = ucwords( str_replace( '-', ' ', $name ) );
			$classes[ $name ] = array(
				'css'         => $css,
				'title'       => $title,
				'description' => $title . '.',
				'category'    => 'layout',
				'tags'        => array( 'alignment', 'flex', 'grid' ),
			);
		}

		// --- Grid Templates ---
		// Equal columns: 1-12.
		for ( $i = 1; $i <= 12; $i++ ) {
			$classes[ "grid-cols-{$i}" ] = array(
				'css'         => "grid-template-columns: repeat({$i}, minmax(0px, 1fr));",
				'title'       => "{$i} column grid",
				'description' => "Creates a {$i} column grid layout.",
				'category'    => 'layout',
				'tags'        => array( 'grid', 'cols' ),
			);
		}

		// Ratio grids.
		$ratio_grids = array(
			'grid-cols-1/2'     => 'grid-template-columns: minmax(0px, 1fr) minmax(0px, 2fr);',
			'grid-cols-1/3'     => 'grid-template-columns: minmax(0px, 1fr) minmax(0px, 3fr);',
			'grid-cols-2/1'     => 'grid-template-columns: minmax(0px, 2fr) minmax(0px, 1fr);',
			'grid-cols-2/3'     => 'grid-template-columns: minmax(0px, 2fr) minmax(0px, 3fr);',
			'grid-cols-3/1'     => 'grid-template-columns: minmax(0px, 3fr) minmax(0px, 1fr);',
			'grid-cols-3/2'     => 'grid-template-columns: minmax(0px, 3fr) minmax(0px, 2fr);',
			// Tailwind v4 subgrid keyword forms.
			// @since 1.0.0.
			'grid-cols-subgrid' => 'grid-template-columns: subgrid;',
			'grid-rows-subgrid' => 'grid-template-rows: subgrid;',
		);

		foreach ( $ratio_grids as $name => $css ) {
			$classes[ $name ] = array(
				'css'         => $css,
				'title'       => str_replace( 'grid-cols-', '', $name ) . ' ratio grid',
				'description' => 'Creates a ratio-based grid layout.',
				'category'    => 'layout',
				'tags'        => array( 'grid', 'cols', 'ratio' ),
			);
		}

		// --- Grid Column Start/End/Span ---
		for ( $i = 1; $i <= 13; $i++ ) {
			$classes[ "col-start-{$i}" ] = array(
				'css'         => "grid-column-start: {$i};",
				'title'       => "Column start {$i}",
				'description' => "Starts the grid item at column line {$i}.",
				'category'    => 'layout',
				'tags'        => array( 'col', 'start', 'grid' ),
			);
			$classes[ "col-end-{$i}" ]   = array(
				'css'         => "grid-column-end: {$i};",
				'title'       => "Column end {$i}",
				'description' => "Ends the grid item at column line {$i}.",
				'category'    => 'layout',
				'tags'        => array( 'col', 'end', 'grid' ),
			);
		}

		$classes['col-end-last'] = array(
			'css'         => 'grid-column-end: -1;',
			'title'       => 'Column end last',
			'description' => 'Ends the grid item at the last column line.',
			'category'    => 'layout',
			'tags'        => array( 'col', 'end', 'last', 'grid' ),
		);

		for ( $i = 1; $i <= 12; $i++ ) {
			$classes[ "col-span-{$i}" ] = array(
				'css'         => "grid-column: span {$i} / span {$i};",
				'title'       => "Column span {$i}",
				'description' => "Makes the grid item span {$i} columns.",
				'category'    => 'layout',
				'tags'        => array( 'col', 'span', 'grid' ),
			);
		}

		$classes['col-span-full'] = array(
			'css'         => 'grid-column: 1 / -1;',
			'title'       => 'Column span full',
			'description' => 'Makes the grid item span all columns.',
			'category'    => 'layout',
			'tags'        => array( 'col', 'span', 'full', 'grid' ),
		);

		// --- Grid Row Start/End/Span ---
		for ( $i = 1; $i <= 7; $i++ ) {
			$classes[ "row-start-{$i}" ] = array(
				'css'         => "grid-row-start: {$i};",
				'title'       => "Row start {$i}",
				'description' => "Starts the grid item at row line {$i}.",
				'category'    => 'layout',
				'tags'        => array( 'row', 'start', 'grid' ),
			);
			$classes[ "row-end-{$i}" ]   = array(
				'css'         => "grid-row-end: {$i};",
				'title'       => "Row end {$i}",
				'description' => "Ends the grid item at row line {$i}.",
				'category'    => 'layout',
				'tags'        => array( 'row', 'end', 'grid' ),
			);
		}

		$classes['row-end-last'] = array(
			'css'         => 'grid-row-end: -1;',
			'title'       => 'Row end last',
			'description' => 'Ends the grid item at the last row line.',
			'category'    => 'layout',
			'tags'        => array( 'row', 'end', 'last', 'grid' ),
		);

		for ( $i = 1; $i <= 6; $i++ ) {
			$classes[ "row-span-{$i}" ] = array(
				'css'         => "grid-row: span {$i} / span {$i};",
				'title'       => "Row span {$i}",
				'description' => "Makes the grid item span {$i} rows.",
				'category'    => 'layout',
				'tags'        => array( 'row', 'span', 'grid' ),
			);
		}

		$classes['row-span-full'] = array(
			'css'         => 'grid-row: 1 / -1;',
			'title'       => 'Row span full',
			'description' => 'Makes the grid item span all rows.',
			'category'    => 'layout',
			'tags'        => array( 'row', 'span', 'full', 'grid' ),
		);

		// --- Order ---
		$order_classes = array(
			'order-first' => array(
				'css'   => 'order: -9999;',
				'title' => 'Order first',
			),
			'order-last'  => array(
				'css'   => 'order: 9999;',
				'title' => 'Order last',
			),
			'order-none'  => array(
				'css'   => 'order: 0;',
				'title' => 'Order none',
			),
		);

		for ( $i = 1; $i <= 12; $i++ ) {
			$order_classes[ "order-{$i}" ] = array(
				'css'   => "order: {$i};",
				'title' => "Order {$i}",
			);
		}

		foreach ( $order_classes as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'layout',
				'tags'        => array( 'order', 'flex' ),
			);
		}

		// --- Isolation ---
		// Tailwind v4 isolation utilities. The bracket form `isolation-[isolate]`
		// is covered by the JIT PREFIX_MAP entry; these are the keyword presets.
		// @since 1.0.0
		$classes['isolate']        = array(
			'css'         => 'isolation: isolate;',
			'title'       => 'Isolate',
			'description' => 'Creates a new stacking context.',
			'category'    => 'layout',
			'tags'        => array( 'isolation', 'stacking-context' ),
		);
		$classes['isolation-auto'] = array(
			'css'         => 'isolation: auto;',
			'title'       => 'Isolation auto',
			'description' => 'Resets isolation to auto.',
			'category'    => 'layout',
			'tags'        => array( 'isolation' ),
		);

		// --- Z-Index ---
		$z_index = array(
			'z-0'     => array(
				'css'   => 'z-index: 0;',
				'title' => 'Z-index 0',
			),
			'z-10'    => array(
				'css'   => 'z-index: 10;',
				'title' => 'Z-index 10',
			),
			'z-20'    => array(
				'css'   => 'z-index: 20;',
				'title' => 'Z-index 20',
			),
			'z-30'    => array(
				'css'   => 'z-index: 30;',
				'title' => 'Z-index 30',
			),
			'z-40'    => array(
				'css'   => 'z-index: 40;',
				'title' => 'Z-index 40',
			),
			'z-50'    => array(
				'css'   => 'z-index: 50;',
				'title' => 'Z-index 50',
			),
			'z-auto'  => array(
				'css'   => 'z-index: auto;',
				'title' => 'Z-index auto',
			),
			'z-back'  => array(
				'css'   => 'z-index: -1;',
				'title' => 'Z-index back',
			),
			'z-front' => array(
				'css'   => 'z-index: 99999;',
				'title' => 'Z-index front',
			),
			// Negative z-index — Tailwind v4 ships these as standard.
			// @since 1.0.0.
			'-z-10'   => array(
				'css'   => 'z-index: -10;',
				'title' => 'Z-index -10',
			),
			'-z-20'   => array(
				'css'   => 'z-index: -20;',
				'title' => 'Z-index -20',
			),
			'-z-30'   => array(
				'css'   => 'z-index: -30;',
				'title' => 'Z-index -30',
			),
			'-z-40'   => array(
				'css'   => 'z-index: -40;',
				'title' => 'Z-index -40',
			),
			'-z-50'   => array(
				'css'   => 'z-index: -50;',
				'title' => 'Z-index -50',
			),
		);

		foreach ( $z_index as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'layout',
				'tags'        => array( 'z-index' ),
			);
		}

		return $classes;
	}

	/**
	 * Filter utility classes (backdrop-blur).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_filter_classes(): array {
		// Aligned with Tailwind v4 defaults — backdrop-blur shares the same
		// scale as filter blur (xs:4px through 3xl:64px). v3.0 used a totally
		// different scale (sm:2px, default:5px, md:10px, lg:15px, xl:20px,
		// 2xl:25px) which was already legacy by v3.4 and removed in v4.
		$blur_sizes = array(
			'backdrop-blur-xs'  => '4px',
			'backdrop-blur-sm'  => '8px',
			'backdrop-blur'     => '8px',
			'backdrop-blur-md'  => '12px',
			'backdrop-blur-lg'  => '16px',
			'backdrop-blur-xl'  => '24px',
			'backdrop-blur-2xl' => '40px',
			'backdrop-blur-3xl' => '64px',
		);

		$classes = array();
		foreach ( $blur_sizes as $name => $value ) {
			$classes[ $name ] = array(
				'css'         => "-webkit-backdrop-filter: blur({$value}); backdrop-filter: blur({$value});",
				'title'       => ucwords( str_replace( '-', ' ', $name ) ),
				'description' => "Applies backdrop blur of {$value}.",
				'category'    => 'filters',
				'tags'        => array( 'backdrop', 'blur' ),
			);
		}

		return $classes;
	}

	/**
	 * Font weight utility classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_font_weight_classes(): array {
		$weights = array(
			'font-thin'       => array(
				'value' => '100',
				'title' => 'Thin',
			),
			'font-extralight' => array(
				'value' => '200',
				'title' => 'Extra light',
			),
			'font-light'      => array(
				'value' => '300',
				'title' => 'Light',
			),
			'font-normal'     => array(
				'value' => '400',
				'title' => 'Normal',
			),
			'font-medium'     => array(
				'value' => '500',
				'title' => 'Medium',
			),
			'font-semibold'   => array(
				'value' => '600',
				'title' => 'Semibold',
			),
			'font-bold'       => array(
				'value' => '700',
				'title' => 'Bold',
			),
			'font-extrabold'  => array(
				'value' => '800',
				'title' => 'Extra bold',
			),
			'font-black'      => array(
				'value' => '900',
				'title' => 'Black',
			),
		);

		$classes = array();
		foreach ( $weights as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => "font-weight: {$data['value']};",
				'title'       => $data['title'],
				'description' => "Sets font weight to {$data['value']}.",
				'category'    => 'font-weight',
				'tags'        => array( 'font', 'weight', strtolower( $data['title'] ) ),
			);
		}

		return $classes;
	}

	/**
	 * Font style utility classes (italic / not-italic).
	 *
	 * Tailwind's `italic` / `not-italic` map to `font-style: italic|normal`.
	 * Without registry coverage these utilities silently no-op on live —
	 * authoring `<span class="italic">` against a font with an italic axis
	 * still renders upright text.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_font_style_classes(): array {
		return array(
			'italic'     => array(
				'css'         => 'font-style: italic;',
				'title'       => 'Italic',
				'description' => 'Sets font-style to italic.',
				'category'    => 'font-style',
				'tags'        => array( 'font', 'style', 'italic' ),
			),
			'not-italic' => array(
				'css'         => 'font-style: normal;',
				'title'       => 'Not italic',
				'description' => 'Sets font-style to normal.',
				'category'    => 'font-style',
				'tags'        => array( 'font', 'style', 'normal' ),
			),
		);
	}

	/**
	 * Text style utility classes (alignment, transform, decoration, wrap, whitespace, tracking).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_text_style_classes(): array {
		$classes = array();

		// --- Text Alignment ---
		$alignments = array(
			'text-left'    => 'left',
			'text-center'  => 'center',
			'text-right'   => 'right',
			'text-justify' => 'justify',
			'text-start'   => 'start',
			'text-end'     => 'end',
		);

		foreach ( $alignments as $name => $value ) {
			$classes[ $name ] = array(
				'css'         => "text-align: {$value};",
				'title'       => ucfirst( $value ) . ' align',
				'description' => "Aligns text to the {$value}.",
				'category'    => 'text-align',
				'tags'        => array( 'text', 'align', $value ),
			);
		}

		// --- Text Transform ---
		$transforms = array(
			'uppercase'   => array(
				'css'   => 'text-transform: uppercase;',
				'title' => 'Uppercase',
			),
			'lowercase'   => array(
				'css'   => 'text-transform: lowercase;',
				'title' => 'Lowercase',
			),
			'capitalize'  => array(
				'css'   => 'text-transform: capitalize;',
				'title' => 'Capitalize',
			),
			'normal-case' => array(
				'css'   => 'text-transform: none;',
				'title' => 'Normal case',
			),
		);

		foreach ( $transforms as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'text-style',
				'tags'        => array( 'text', 'transform', strtolower( $data['title'] ) ),
			);
		}

		// --- Text Decoration ---
		$decorations = array(
			'underline'    => array(
				'css'   => 'text-decoration-line: underline;',
				'title' => 'Underline',
			),
			'overline'     => array(
				'css'   => 'text-decoration-line: overline;',
				'title' => 'Overline',
			),
			'line-through' => array(
				'css'   => 'text-decoration-line: line-through;',
				'title' => 'Line through',
			),
			'no-underline' => array(
				'css'   => 'text-decoration-line: none;',
				'title' => 'No underline',
			),
		);

		foreach ( $decorations as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'text-style',
				'tags'        => array( 'text', 'decoration', strtolower( $data['title'] ) ),
			);
		}

		// --- Text Wrap ---
		$wraps = array(
			'text-wrap'    => array(
				'css'   => 'text-wrap: wrap;',
				'title' => 'Text wrap',
			),
			'text-nowrap'  => array(
				'css'   => 'text-wrap: nowrap;',
				'title' => 'Text nowrap',
			),
			'text-balance' => array(
				'css'   => 'text-wrap: balance;',
				'title' => 'Text balance',
			),
			'text-pretty'  => array(
				'css'   => 'text-wrap: pretty;',
				'title' => 'Text pretty',
			),
		);

		foreach ( $wraps as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => $data['css'],
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'text-style',
				'tags'        => array( 'text', 'wrap' ),
			);
		}

		// --- Whitespace ---
		$whitespace = array(
			'whitespace-normal'       => 'normal',
			'whitespace-nowrap'       => 'nowrap',
			'whitespace-pre'          => 'pre',
			'whitespace-pre-line'     => 'pre-line',
			'whitespace-pre-wrap'     => 'pre-wrap',
			'whitespace-break-spaces' => 'break-spaces',
		);

		foreach ( $whitespace as $name => $value ) {
			$classes[ $name ] = array(
				'css'         => "white-space: {$value};",
				'title'       => ucfirst( str_replace( '-', ' ', $name ) ),
				'description' => "Sets white-space to {$value}.",
				'category'    => 'text-style',
				'tags'        => array( 'whitespace', $value ),
			);
		}

		// --- Tracking (Letter Spacing) ---
		$tracking = array(
			'tracking-tighter' => array(
				'value' => '-0.05em',
				'title' => 'Tighter tracking',
			),
			'tracking-tight'   => array(
				'value' => '-0.025em',
				'title' => 'Tight tracking',
			),
			'tracking-normal'  => array(
				'value' => '0em',
				'title' => 'Normal tracking',
			),
			'tracking-wide'    => array(
				'value' => '0.025em',
				'title' => 'Wide tracking',
			),
			'tracking-wider'   => array(
				'value' => '0.05em',
				'title' => 'Wider tracking',
			),
			'tracking-widest'  => array(
				'value' => '0.1em',
				'title' => 'Widest tracking',
			),
		);

		foreach ( $tracking as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => "letter-spacing: {$data['value']};",
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'tracking',
				'tags'        => array( 'tracking', 'letter-spacing' ),
			);
		}

		return $classes;
	}

	/**
	 * Box shadow utility classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_shadow_classes(): array {
		// Composed box-shadow — sets --tw-shadow to this utility's value and
		// emits a three-layer box-shadow so rings/offset-rings/shadows stack.
		// Each --tw-* falls back to `0 0 #0000` when unset (no visual effect),
		// so a bare `shadow-lg` still renders identically to the legacy form.
		$composed = 'box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow, 0 0 #0000);';

		$shadows = array(
			// Tailwind v4 added `shadow-2xs` and `shadow-xs` to the bottom of
			// the scale. v3 started at `shadow-sm`.
			// @since 1.0.0.
			'shadow-2xs'   => array(
				'shadow' => '0 1px rgb(0 0 0 / 0.05)',
				'title'  => '2x extra small shadow',
			),
			'shadow-xs'    => array(
				'shadow' => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
				'title'  => 'Extra small shadow',
			),
			'shadow-sm'    => array(
				'shadow' => '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
				'title'  => 'Small shadow',
			),
			'shadow'       => array(
				'shadow' => '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
				'title'  => 'Shadow',
			),
			'shadow-md'    => array(
				'shadow' => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
				'title'  => 'Medium shadow',
			),
			'shadow-lg'    => array(
				'shadow' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
				'title'  => 'Large shadow',
			),
			'shadow-xl'    => array(
				'shadow' => '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
				'title'  => 'Extra large shadow',
			),
			'shadow-2xl'   => array(
				'shadow' => '0 25px 50px -12px rgb(0 0 0 / 0.25)',
				'title'  => '2x large shadow',
			),
			'shadow-inner' => array(
				'shadow' => 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)',
				'title'  => 'Inner shadow',
			),
			'shadow-none'  => array(
				'shadow' => '0 0 #0000',
				'title'  => 'No shadow',
			),
		);

		$classes = array();
		foreach ( $shadows as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => '--tw-shadow: ' . $data['shadow'] . '; ' . $composed,
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'shadow',
				'tags'        => array( 'shadow', 'box-shadow' ),
			);
		}

		// Tailwind v4 inset-shadow scale — composes via the same three-layer
		// `box-shadow` declaration as `shadow-*` but writes the inset form
		// into `--tw-shadow`. Color composition (e.g. `inset-shadow-red-500/30`)
		// is handled by the alpha-slash path in the color resolver.
		// @since 1.0.0.
		$inset_shadows = array(
			'inset-shadow-2xs'  => array(
				'shadow' => 'inset 0 1px rgb(0 0 0 / 0.05)',
				'title'  => '2x extra small inset shadow',
			),
			'inset-shadow-xs'   => array(
				'shadow' => 'inset 0 1px 1px rgb(0 0 0 / 0.05)',
				'title'  => 'Extra small inset shadow',
			),
			'inset-shadow-sm'   => array(
				'shadow' => 'inset 0 2px 4px rgb(0 0 0 / 0.05)',
				'title'  => 'Small inset shadow',
			),
			'inset-shadow'      => array(
				'shadow' => 'inset 0 2px 4px rgb(0 0 0 / 0.05)',
				'title'  => 'Inset shadow',
			),
			'inset-shadow-md'   => array(
				'shadow' => 'inset 0 4px 6px rgb(0 0 0 / 0.10)',
				'title'  => 'Medium inset shadow',
			),
			'inset-shadow-lg'   => array(
				'shadow' => 'inset 0 6px 10px rgb(0 0 0 / 0.10)',
				'title'  => 'Large inset shadow',
			),
			'inset-shadow-xl'   => array(
				'shadow' => 'inset 0 8px 16px rgb(0 0 0 / 0.10)',
				'title'  => 'Extra large inset shadow',
			),
			'inset-shadow-2xl'  => array(
				'shadow' => 'inset 0 12px 24px rgb(0 0 0 / 0.10)',
				'title'  => '2x large inset shadow',
			),
			'inset-shadow-none' => array(
				'shadow' => '0 0 #0000',
				'title'  => 'No inset shadow',
			),
		);

		foreach ( $inset_shadows as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => '--tw-shadow: ' . $data['shadow'] . '; ' . $composed,
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'shadow',
				'tags'        => array( 'shadow', 'inset-shadow', 'box-shadow' ),
			);
		}

		return $classes;
	}

	/**
	 * Opacity utility classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_opacity_classes(): array {
		$classes = array();

		for ( $i = 0; $i <= 100; $i += 5 ) {
			$value                     = $i / 100;
			$classes[ "opacity-{$i}" ] = array(
				'css'         => "opacity: {$value};",
				'title'       => "Opacity {$i}%",
				'description' => "Sets opacity to {$i}%.",
				'category'    => 'opacity',
				'tags'        => array( 'opacity' ),
			);
		}

		return $classes;
	}

	/**
	 * Overflow utility classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_overflow_classes(): array {
		$values = array( 'auto', 'hidden', 'clip', 'visible', 'scroll' );

		$classes = array();
		foreach ( $values as $value ) {
			// Base: overflow.
			$classes[ "overflow-{$value}" ] = array(
				'css'         => "overflow: {$value};",
				'title'       => 'Overflow ' . $value,
				'description' => "Sets overflow to {$value}.",
				'category'    => 'overflow',
				'tags'        => array( 'overflow', $value ),
			);

			// Overflow-x.
			$classes[ "overflow-x-{$value}" ] = array(
				'css'         => "overflow-x: {$value};",
				'title'       => 'Overflow X ' . $value,
				'description' => "Sets horizontal overflow to {$value}.",
				'category'    => 'overflow',
				'tags'        => array( 'overflow', 'x', $value ),
			);

			// Overflow-y.
			$classes[ "overflow-y-{$value}" ] = array(
				'css'         => "overflow-y: {$value};",
				'title'       => 'Overflow Y ' . $value,
				'description' => "Sets vertical overflow to {$value}.",
				'category'    => 'overflow',
				'tags'        => array( 'overflow', 'y', $value ),
			);
		}

		return $classes;
	}

	/**
	 * Position utility classes (keywords + inset/directional values).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_position_classes(): array {
		$classes = array();

		// --- Position Keywords ---
		$keywords = array( 'static', 'relative', 'absolute', 'fixed', 'sticky' );

		foreach ( $keywords as $keyword ) {
			// `absolute` / `fixed` also emit `width: auto` to override
			// Spectra's `.wp-block-spectra-container:not(.alignfull) { width: 100% }`
			// default. Without this, an absolutely-positioned container
			// stretches to the full parent width and `right-N` anchors pull
			// its computed `left` negative, misplacing the element. Matches
			// Tailwind's shrink-to-fit expectation for absolute utilities.
			$css_decls = "position: {$keyword};";
			if ( 'absolute' === $keyword || 'fixed' === $keyword ) {
				$css_decls .= ' width: auto;';
			}

			$classes[ $keyword ] = array(
				'css'         => $css_decls,
				'title'       => ucfirst( $keyword ),
				'description' => "Sets position to {$keyword}.",
				'category'    => 'position',
				'tags'        => array( 'position', $keyword ),
			);
		}

		// --- Inset Values ---
		$inset_values = array(
			'0'    => '0',
			'auto' => 'auto',
		);

		foreach ( $inset_values as $suffix => $value ) {
			$classes[ "inset-{$suffix}" ] = array(
				'css'         => "inset: {$value};",
				'title'       => "Inset {$suffix}",
				'description' => "Sets all inset to {$value}.",
				'category'    => 'position',
				'tags'        => array( 'inset', 'position' ),
			);

			$classes[ "inset-x-{$suffix}" ] = array(
				'css'         => "right: {$value}; left: {$value};",
				'title'       => "Inset X {$suffix}",
				'description' => "Sets left and right to {$value}.",
				'category'    => 'position',
				'tags'        => array( 'inset', 'x', 'position' ),
			);

			$classes[ "inset-y-{$suffix}" ] = array(
				'css'         => "top: {$value}; bottom: {$value};",
				'title'       => "Inset Y {$suffix}",
				'description' => "Sets top and bottom to {$value}.",
				'category'    => 'position',
				'tags'        => array( 'inset', 'y', 'position' ),
			);
		}

		// --- Directional Values ---
		$directions = array(
			'top'    => 'top',
			'right'  => 'right',
			'bottom' => 'bottom',
			'left'   => 'left',
		);

		$directional_values = array(
			'0'    => '0',
			'px'   => '1px',
			'0.5'  => '0.125rem',
			'1'    => '0.25rem',
			'1.5'  => '0.375rem',
			'2'    => '0.5rem',
			'3'    => '0.75rem',
			'4'    => '1rem',
			'auto' => 'auto',
			'full' => '100%',
			'1/2'  => '50%',
		);

		foreach ( $directions as $dir_name => $css_prop ) {
			foreach ( $directional_values as $suffix => $value ) {
				$classes[ "{$dir_name}-{$suffix}" ] = array(
					'css'         => "{$css_prop}: {$value};",
					'title'       => ucfirst( $dir_name ) . " {$suffix}",
					'description' => "Sets {$css_prop} to {$value}.",
					'category'    => 'position',
					'tags'        => array( $dir_name, 'position' ),
				);
			}
		}

		return $classes;
	}

	/**
	 * Visibility utility classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_visibility_classes(): array {
		return array(
			'visible'   => array(
				'css'         => 'visibility: visible;',
				'title'       => 'Visible',
				'description' => 'Makes the element visible.',
				'category'    => 'visibility',
				'tags'        => array( 'visibility', 'visible' ),
			),
			'invisible' => array(
				'css'         => 'visibility: hidden;',
				'title'       => 'Invisible',
				'description' => 'Hides the element but preserves its space.',
				'category'    => 'visibility',
				'tags'        => array( 'visibility', 'hidden' ),
			),
			'collapse'  => array(
				'css'         => 'visibility: collapse;',
				'title'       => 'Collapse',
				'description' => 'Collapses the element (for table rows/columns).',
				'category'    => 'visibility',
				'tags'        => array( 'visibility', 'collapse' ),
			),
		);
	}

	/**
	 * Cursor utility classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_cursor_classes(): array {
		$cursors = array(
			'cursor-auto'        => 'auto',
			'cursor-default'     => 'default',
			'cursor-pointer'     => 'pointer',
			'cursor-wait'        => 'wait',
			'cursor-text'        => 'text',
			'cursor-move'        => 'move',
			'cursor-not-allowed' => 'not-allowed',
			'cursor-grab'        => 'grab',
			'cursor-grabbing'    => 'grabbing',
		);

		$classes = array();
		foreach ( $cursors as $name => $value ) {
			$classes[ $name ] = array(
				'css'         => "cursor: {$value};",
				'title'       => ucwords( str_replace( '-', ' ', $name ) ),
				'description' => "Sets cursor to {$value}.",
				'category'    => 'cursor',
				'tags'        => array( 'cursor', $value ),
			);
		}

		return $classes;
	}

	/**
	 * List style utility classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_list_classes(): array {
		return array(
			'list-none'    => array(
				'css'         => 'list-style-type: none;',
				'title'       => 'List none',
				'description' => 'Removes list markers.',
				'category'    => 'list-style',
				'tags'        => array( 'list', 'none' ),
			),
			'list-disc'    => array(
				'css'         => 'list-style-type: disc;',
				'title'       => 'List disc',
				'description' => 'Sets list markers to disc.',
				'category'    => 'list-style',
				'tags'        => array( 'list', 'disc' ),
			),
			'list-decimal' => array(
				'css'         => 'list-style-type: decimal;',
				'title'       => 'List decimal',
				'description' => 'Sets list markers to decimal numbers.',
				'category'    => 'list-style',
				'tags'        => array( 'list', 'decimal' ),
			),
			'list-inside'  => array(
				'css'         => 'list-style-position: inside;',
				'title'       => 'List inside',
				'description' => 'Places list markers inside the content flow.',
				'category'    => 'list-style',
				'tags'        => array( 'list', 'inside', 'position' ),
			),
			'list-outside' => array(
				'css'         => 'list-style-position: outside;',
				'title'       => 'List outside',
				'description' => 'Places list markers outside the content flow.',
				'category'    => 'list-style',
				'tags'        => array( 'list', 'outside', 'position' ),
			),
		);
	}

	// ─────────────────────────────────────────────────────────────
	// DYNAMIC SG-POWERED GENERATORS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Generates color classes from Style Guide chromatic and neutral tokens.
	 *
	 * For each chromatic color family, generates:
	 * - bg-{slug}-{shade}, text-{slug}-{shade}, border-{slug}-{shade}, overlay-{slug}-{shade}
	 * - border-t/r/b/l/x/y-{slug}-{shade}
	 * - Opacity variants: {prefix}-{slug}-{shade}/{opacity}
	 *
	 * Also generates white, black, and transparent utility colors.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_color_classes(): array {
		$classes = array();

		// Channel → property maps: the named constants ARE the source of
		// truth — the exported palette grammar derives its channel list
		// from them ({@see self::palette_channels()}), so generator and
		// export cannot drift.
		$property_map     = self::PALETTE_PROPERTY_MAP;
		$border_positions = self::PALETTE_BORDER_POSITION_MAP;
		$border_multi     = self::PALETTE_BORDER_MULTI_MAP;

		// Get SG config for chromatic colors.
		$sg_config  = static::get_sg_config();
		$chromatics = isset( $sg_config['chromatics'] ) ? $sg_config['chromatics'] : array();

		// --- Chromatic Color Families ---
		foreach ( $chromatics as $index => $chromatic ) {
			$slug = self::get_chromatic_slug( $index, $chromatic );

			foreach ( self::CHROMATIC_SHADE_MAP as $sg_shade => $tw_shade ) {
				$token = "chromatic{$index}-{$sg_shade}";
				$var   = "var(--spectra-{$token})";

				self::register_color_for_all_prefixes(
					$classes,
					$slug,
					$tw_shade,
					$var,
					$chromatic['name'] ?? "Chromatic {$index}",
					$property_map,
					$border_positions,
					$border_multi
				);
			}
		}

		// --- Neutral (Base) Family ---
		// Direct shade mappings from SG neutral indices.
		foreach ( self::NEUTRAL_SHADE_MAP as $sg_index => $tw_shade ) {
			$var = "var(--spectra-neutral-{$sg_index})";

			self::register_color_for_all_prefixes(
				$classes,
				'base',
				$tw_shade,
				$var,
				'Base',
				$property_map,
				$border_positions,
				$border_multi
			);
		}

		// Gap-fill shades using nearest SG neutral token.
		foreach ( self::NEUTRAL_GAP_FILL as $tw_shade => $token ) {
			$var = "var(--spectra-{$token})";

			self::register_color_for_all_prefixes(
				$classes,
				'base',
				$tw_shade,
				$var,
				'Base',
				$property_map,
				$border_positions,
				$border_multi
			);
		}

		// --- Common Colors: White, Black, Transparent ---
		$common_colors = array(
			'white'       => array(
				'css_value' => '#ffffff',
				'label'     => 'White',
			),
			'black'       => array(
				'css_value' => '#000000',
				'label'     => 'Black',
			),
			'transparent' => array(
				'css_value' => 'transparent',
				'label'     => 'Transparent',
			),
		);

		foreach ( $common_colors as $color_name => $color_data ) {
			$value = $color_data['css_value'];
			$label = $color_data['label'];

			foreach ( $property_map as $prefix => $css_prop ) {
				$class_name = "{$prefix}-{$color_name}";

				// Pair `color` with `-webkit-text-fill-color` for the text
				// prefix — same reasoning as in register_color_for_all_prefixes.
				// Without this, `text-transparent` in a `bg-clip-text` gradient
				// recipe renders as invisible text on WebKit.
				$base_css = "{$css_prop}: {$value};";
				if ( 'color' === $css_prop ) {
					$base_css .= " -webkit-text-fill-color: {$value};";
				}

				$classes[ $class_name ] = array(
					'css'         => $base_css,
					'title'       => ucfirst( $prefix ) . ' - ' . $label,
					'description' => ucfirst( $prefix ) . " using {$label}.",
					'category'    => 'colors',
					'tags'        => array( $prefix, $color_name, 'color' ),
				);
			}

			foreach ( $border_positions as $prefix => $css_prop ) {
				$class_name             = "{$prefix}-{$color_name}";
				$classes[ $class_name ] = array(
					'css'         => "{$css_prop}: {$value};",
					'title'       => ucfirst( str_replace( '-', ' ', $prefix ) ) . ' - ' . $label,
					'description' => ucfirst( str_replace( '-', ' ', $prefix ) ) . " using {$label}.",
					'category'    => 'colors',
					'tags'        => array( 'border', $color_name, 'color' ),
				);
			}

			foreach ( $border_multi as $prefix => $css_props ) {
				$css_parts = array();
				foreach ( $css_props as $prop ) {
					$css_parts[] = "{$prop}: {$value};";
				}
				$class_name             = "{$prefix}-{$color_name}";
				$classes[ $class_name ] = array(
					'css'         => implode( ' ', $css_parts ),
					'title'       => ucfirst( str_replace( '-', ' ', $prefix ) ) . ' - ' . $label,
					'description' => ucfirst( str_replace( '-', ' ', $prefix ) ) . " using {$label}.",
					'category'    => 'colors',
					'tags'        => array( 'border', $color_name, 'color' ),
				);
			}
		}

		// --- Gradient stops: from-/via-/to- utilities -----------------------
		// Palette-aware stop utilities that drive the `bg-gradient-*` linear
		// gradients via shared custom properties (`--tw-gradient-*` namespace).
		// Iterates PALETTE_GRADIENT_CHANNELS in CONSTANT ORDER — the `via-*`
		// rule overrides `--tw-gradient-stops` with a 3-stop form, so it must
		// appear AFTER `from-*` in the cascade; `to-*` only sets
		// `--tw-gradient-to`, which both 2-stop and 3-stop forms read.
		$stop_sources = self::build_gradient_stop_sources( $chromatics );
		foreach ( self::PALETTE_GRADIENT_CHANNELS as $kind ) {
			$classes = array_merge( $classes, self::build_gradient_stop_classes( $stop_sources, $kind ) );
		}

		return $classes;
	}

	/**
	 * Collect every registered color source keyed by its gradient-stop class slug.
	 *
	 * Output shape: `[ 'primary-500' => [ 'var' => 'var(--spectra-chromatic0-500)', 'label' => 'Primary 500' ], ... ]`.
	 * Includes chromatic shades, base neutral shades (both direct and gap-fill),
	 * and common solid colors (`white`, `black`, `transparent`).
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, mixed>> $chromatics SG chromatic list.
	 * @return array<string, array{var:string, label:string}>
	 */
	private static function build_gradient_stop_sources( array $chromatics ): array {
		$sources = array();

		foreach ( $chromatics as $index => $chromatic ) {
			$slug  = self::get_chromatic_slug( $index, $chromatic );
			$label = $chromatic['name'] ?? "Chromatic {$index}";
			foreach ( self::CHROMATIC_SHADE_MAP as $sg_shade => $tw_shade ) {
				$sources[ "{$slug}-{$tw_shade}" ] = array(
					'var'   => "var(--spectra-chromatic{$index}-{$sg_shade})",
					'label' => "{$label} {$tw_shade}",
				);
			}
		}

		foreach ( self::NEUTRAL_SHADE_MAP as $sg_index => $tw_shade ) {
			$sources[ "base-{$tw_shade}" ] = array(
				'var'   => "var(--spectra-neutral-{$sg_index})",
				'label' => "Base {$tw_shade}",
			);
		}

		foreach ( self::NEUTRAL_GAP_FILL as $tw_shade => $token ) {
			$sources[ "base-{$tw_shade}" ] = array(
				'var'   => "var(--spectra-{$token})",
				'label' => "Base {$tw_shade}",
			);
		}

		$sources['white']       = array(
			'var'   => '#ffffff',
			'label' => 'White',
		);
		$sources['black']       = array(
			'var'   => '#000000',
			'label' => 'Black',
		);
		$sources['transparent'] = array(
			'var'   => 'transparent',
			'label' => 'Transparent',
		);

		return $sources;
	}

	/**
	 * Build `{from|via|to}-{token}` utility map for every stop source.
	 *
	 * Writes to `--tw-gradient-*` — the single gradient-variable namespace
	 * all direction utilities now consume (`bg-gradient-to-*` v3 and
	 * `bg-linear-*` / `bg-radial-*` / `bg-conic-*` v4). A prior split-namespace
	 * design (`--gs-gradient-*` for v3, `--tw-gradient-*` for v4) meant
	 * mixing palette-slug stops with a bracket-color stop OR mixing v3
	 * direction with v4 stops silently produced transparent gradients.
	 *
	 * - `from-*`: sets `--tw-gradient-from` and the 2-stop `--tw-gradient-stops`.
	 * - `via-*`:  sets `--tw-gradient-via` and overrides stops with the 3-stop form.
	 * - `to-*`:   sets `--tw-gradient-to` (consumed by both stop forms).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array{var:string, label:string}> $sources Stop sources.
	 * @param string                                         $kind    One of `from`, `via`, `to`.
	 * @return array<string, array>
	 */
	private static function build_gradient_stop_classes( array $sources, string $kind ): array {
		$classes = array();

		foreach ( $sources as $token => $meta ) {
			$value = $meta['var'];
			$label = $meta['label'];

			if ( 'from' === $kind ) {
				$css = "--tw-gradient-from: {$value}; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);";
			} elseif ( 'via' === $kind ) {
				$css = "--tw-gradient-via: {$value}; --tw-gradient-stops: var(--tw-gradient-from, transparent), var(--tw-gradient-via), var(--tw-gradient-to, transparent);";
			} else {
				$css = "--tw-gradient-to: {$value};";
			}

			$classes[ "{$kind}-{$token}" ] = array(
				'css'         => $css,
				'title'       => ucfirst( $kind ) . ' - ' . $label,
				'description' => "Gradient {$kind} stop using {$label}.",
				'category'    => 'gradient',
				'tags'        => array( 'gradient', $kind, 'color' ),
			);
		}

		return $classes;
	}

	/**
	 * Generates spacing classes from SG tokens and fixed Tailwind rem scale.
	 *
	 * Generates padding (p, pt, pr, pb, pl, px, py), margin (m, mt, mr, mb, ml, mx, my),
	 * and gap (gap, gap-x, gap-y) classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_spacing_classes(): array {
		$classes = array();

		// SG token sizes: use var(--spectra-space-*).
		$sg_sizes = array(
			'xs'  => 'var(--spectra-space-xs)',
			'sm'  => 'var(--spectra-space-sm)',
			'md'  => 'var(--spectra-space-md)',
			'lg'  => 'var(--spectra-space-lg)',
			'xl'  => 'var(--spectra-space-xl)',
			'2xl' => 'var(--spectra-space-2xl)',
		);

		// Fixed Tailwind rem scale.
		$fixed_sizes = array(
			'0'   => '0',
			'px'  => '1px',
			'0.5' => '0.125rem',
			'1'   => '0.25rem',
			'1.5' => '0.375rem',
			'2'   => '0.5rem',
			'2.5' => '0.625rem',
			'3'   => '0.75rem',
			'3.5' => '0.875rem',
			'4'   => '1rem',
			'5'   => '1.25rem',
			'6'   => '1.5rem',
			'7'   => '1.75rem',
			'8'   => '2rem',
			'9'   => '2.25rem',
			'10'  => '2.5rem',
			'11'  => '2.75rem',
			'12'  => '3rem',
			'14'  => '3.5rem',
			'16'  => '4rem',
			'20'  => '5rem',
			'24'  => '6rem',
			'28'  => '7rem',
			'32'  => '8rem',
			'36'  => '9rem',
			'40'  => '10rem',
			'44'  => '11rem',
			'48'  => '12rem',
			'52'  => '13rem',
			'56'  => '14rem',
			'60'  => '15rem',
			'64'  => '16rem',
			'72'  => '18rem',
			'80'  => '20rem',
			'96'  => '24rem',
		);

		// Union: fixed first, SG tokens after.
		// Using + instead of array_merge() to preserve numeric string keys
		// (array_merge re-indexes integer-like keys, destroying the Tailwind scale).
		$all_sizes = $fixed_sizes + $sg_sizes;

		// Padding positions.
		$padding_positions = array(
			'p'  => array(
				'props' => array( 'padding' ),
				'label' => 'Padding',
			),
			'pt' => array(
				'props' => array( 'padding-top' ),
				'label' => 'Padding top',
			),
			'pr' => array(
				'props' => array( 'padding-right' ),
				'label' => 'Padding right',
			),
			'pb' => array(
				'props' => array( 'padding-bottom' ),
				'label' => 'Padding bottom',
			),
			'pl' => array(
				'props' => array( 'padding-left' ),
				'label' => 'Padding left',
			),
			'px' => array(
				'props' => array( 'padding-right', 'padding-left' ),
				'label' => 'Horizontal padding',
			),
			'py' => array(
				'props' => array( 'padding-top', 'padding-bottom' ),
				'label' => 'Vertical padding',
			),
		);

		// Margin positions.
		$margin_positions = array(
			'm'  => array(
				'props' => array( 'margin' ),
				'label' => 'Margin',
			),
			'mt' => array(
				'props' => array( 'margin-top' ),
				'label' => 'Margin top',
			),
			'mr' => array(
				'props' => array( 'margin-right' ),
				'label' => 'Margin right',
			),
			'mb' => array(
				'props' => array( 'margin-bottom' ),
				'label' => 'Margin bottom',
			),
			'ml' => array(
				'props' => array( 'margin-left' ),
				'label' => 'Margin left',
			),
			'mx' => array(
				'props' => array( 'margin-right', 'margin-left' ),
				'label' => 'Horizontal margin',
			),
			'my' => array(
				'props' => array( 'margin-top', 'margin-bottom' ),
				'label' => 'Vertical margin',
			),
		);

		// Gap positions.
		$gap_positions = array(
			'gap'   => array(
				'props' => array( 'gap' ),
				'label' => 'Gap',
			),
			'gap-x' => array(
				'props' => array( 'column-gap' ),
				'label' => 'Column gap',
			),
			'gap-y' => array(
				'props' => array( 'row-gap' ),
				'label' => 'Row gap',
			),
		);

		// Padding + Margin.
		foreach ( array_merge( $padding_positions, $margin_positions ) as $prefix => $pos_data ) {
			foreach ( $all_sizes as $size_key => $value ) {
				$class_name = "{$prefix}-{$size_key}";
				$css_parts  = array();

				foreach ( $pos_data['props'] as $prop ) {
					$css_parts[] = "{$prop}: {$value};";
				}

				$classes[ $class_name ] = array(
					'css'         => implode( ' ', $css_parts ),
					'title'       => $pos_data['label'] . ' ' . $size_key,
					'description' => $pos_data['label'] . ' ' . $size_key . '.',
					'category'    => 'spacing',
					'tags'        => array( explode( '-', $prefix )[0], $size_key ),
				);
			}
		}

		// Auto margin utilities (Tailwind: mx-auto, my-auto).
		$auto_margins = array(
			'mx-auto' => array(
				'css'         => 'margin-right: auto; margin-left: auto;',
				'title'       => 'Horizontal margin auto',
				'description' => 'Centers the element horizontally.',
				'category'    => 'spacing',
				'tags'        => array( 'margin', 'auto', 'center' ),
			),
			'my-auto' => array(
				'css'         => 'margin-top: auto; margin-bottom: auto;',
				'title'       => 'Vertical margin auto',
				'description' => 'Centers the element vertically.',
				'category'    => 'spacing',
				'tags'        => array( 'margin', 'auto', 'center' ),
			),
			'ml-auto' => array(
				'css'         => 'margin-left: auto;',
				'title'       => 'Margin left auto',
				'description' => 'Pushes the element to the right.',
				'category'    => 'spacing',
				'tags'        => array( 'margin', 'auto' ),
			),
			'mr-auto' => array(
				'css'         => 'margin-right: auto;',
				'title'       => 'Margin right auto',
				'description' => 'Pushes the element to the left.',
				'category'    => 'spacing',
				'tags'        => array( 'margin', 'auto' ),
			),
		);

		$classes = array_merge( $classes, $auto_margins );

		// Gap (uses the same sizes).
		foreach ( $gap_positions as $prefix => $pos_data ) {
			foreach ( $all_sizes as $size_key => $value ) {
				$class_name = "{$prefix}-{$size_key}";
				$css_parts  = array();

				foreach ( $pos_data['props'] as $prop ) {
					$css_parts[] = "{$prop}: {$value};";
				}

				$classes[ $class_name ] = array(
					'css'         => implode( ' ', $css_parts ),
					'title'       => $pos_data['label'] . ' ' . $size_key,
					'description' => $pos_data['label'] . ' ' . $size_key . '.',
					'category'    => 'spacing',
					'tags'        => array( 'gap', $size_key ),
				);
			}
		}

		return $classes;
	}

	/**
	 * Generates font-size classes from SG tokens.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_font_classes(): array {
		$classes = array();

		// Heading sizes use --spectra-heading-{N}.
		for ( $i = 1; $i <= 6; $i++ ) {
			$classes[ "text-heading-{$i}" ] = array(
				'css'         => "font-size: var(--spectra-heading-{$i});",
				'title'       => "H{$i} heading size",
				'description' => "Applies the H{$i} heading font size.",
				'category'    => 'typography',
				'tags'        => array( 'text', 'heading', "h{$i}" ),
			);
		}

		// Body text sizes — Tailwind-parity: each text-* utility emits both
		// font-size AND its paired line-height, matching Tailwind's default
		// fontSize scale. Previously only font-size was emitted and line-height
		// inherited from body, which broke utility behavior.
		//
		// Each entry carries a `fallback` value matching the Tailwind v4 default
		// fontSize scale. Without the fallback, a theme that doesn't define
		// `--spectra-text-7xl` (etc.) would resolve `font-size: var(--spectra-text-7xl)`
		// to nothing and the utility silently no-ops at the larger end of the
		// scale (observed: `lg:text-9xl` failing to render at 128px on themes
		// that only ship through `--spectra-text-6xl`).
		//
		// @since 1.0.0.
		$text_sizes = array(
			'text-xs'   => array(
				'token'    => 'text-xs',
				'lh'       => '1rem',
				'fallback' => '0.75rem',
				'title'    => 'Extra small text',
			),
			'text-sm'   => array(
				'token'    => 'text-sm',
				'lh'       => '1.25rem',
				'fallback' => '0.875rem',
				'title'    => 'Small text',
			),
			'text-base' => array(
				'token'    => 'text-md',
				'lh'       => '1.5rem',
				'fallback' => '1rem',
				'title'    => 'Base text',
			),
			'text-lg'   => array(
				'token'    => 'text-lg',
				'lh'       => '1.75rem',
				'fallback' => '1.125rem',
				'title'    => 'Large text',
			),
			'text-xl'   => array(
				'token'    => 'text-xl',
				'lh'       => '1.75rem',
				'fallback' => '1.25rem',
				'title'    => 'Extra large text',
			),
			'text-2xl'  => array(
				'token'    => 'text-2xl',
				'lh'       => '2rem',
				'fallback' => '1.5rem',
				'title'    => '2x large text',
			),
			'text-3xl'  => array(
				'token'    => 'text-3xl',
				'lh'       => '2.25rem',
				'fallback' => '1.875rem',
				'title'    => '3x large text',
			),
			'text-4xl'  => array(
				'token'    => 'text-4xl',
				'lh'       => '2.5rem',
				'fallback' => '2.25rem',
				'title'    => '4x large text',
			),
			'text-5xl'  => array(
				'token'    => 'text-5xl',
				'lh'       => '1',
				'fallback' => '3rem',
				'title'    => '5x large text',
			),
			'text-6xl'  => array(
				'token'    => 'text-6xl',
				'lh'       => '1',
				'fallback' => '3.75rem',
				'title'    => '6x large text',
			),
			'text-7xl'  => array(
				'token'    => 'text-7xl',
				'lh'       => '1',
				'fallback' => '4.5rem',
				'title'    => '7x large text',
			),
			'text-8xl'  => array(
				'token'    => 'text-8xl',
				'lh'       => '1',
				'fallback' => '6rem',
				'title'    => '8x large text',
			),
			'text-9xl'  => array(
				'token'    => 'text-9xl',
				'lh'       => '1',
				'fallback' => '8rem',
				'title'    => '9x large text',
			),
		);

		foreach ( $text_sizes as $class_name => $data ) {
			$classes[ $class_name ] = array(
				'css'         => "font-size: var(--spectra-{$data['token']}, {$data['fallback']}); line-height: {$data['lh']};",
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'typography',
				'tags'        => array( 'text', 'font-size' ),
			);
		}

		return $classes;
	}

	/**
	 * Generates line-height classes with fixed values.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_line_height_classes(): array {
		// Named line-height values.
		$line_heights = array(
			'leading-none'    => array(
				'value' => '1',
				'title' => 'No extra line height',
			),
			'leading-tight'   => array(
				'value' => '1.25',
				'title' => 'Tight line height',
			),
			'leading-snug'    => array(
				'value' => '1.375',
				'title' => 'Snug line height',
			),
			'leading-normal'  => array(
				'value' => '1.5',
				'title' => 'Normal line height',
			),
			'leading-relaxed' => array(
				'value' => '1.625',
				'title' => 'Relaxed line height',
			),
			'leading-loose'   => array(
				'value' => '2',
				'title' => 'Loose line height',
			),
		);

		// Numeric rem-based line-height values.
		$numeric_leading = array(
			'leading-3'  => array(
				'value' => '0.75rem',
				'title' => 'Line height 3',
			),
			'leading-4'  => array(
				'value' => '1rem',
				'title' => 'Line height 4',
			),
			'leading-5'  => array(
				'value' => '1.25rem',
				'title' => 'Line height 5',
			),
			'leading-6'  => array(
				'value' => '1.5rem',
				'title' => 'Line height 6',
			),
			'leading-7'  => array(
				'value' => '1.75rem',
				'title' => 'Line height 7',
			),
			'leading-8'  => array(
				'value' => '2rem',
				'title' => 'Line height 8',
			),
			'leading-9'  => array(
				'value' => '2.25rem',
				'title' => 'Line height 9',
			),
			'leading-10' => array(
				'value' => '2.5rem',
				'title' => 'Line height 10',
			),
		);

		$classes = array();
		foreach ( array_merge( $line_heights, $numeric_leading ) as $name => $data ) {
			$classes[ $name ] = array(
				'css'         => "line-height: {$data['value']};",
				'title'       => $data['title'],
				'description' => $data['title'] . '.',
				'category'    => 'typography',
				'tags'        => array( 'leading', 'line-height' ),
			);
		}

		return $classes;
	}

	/**
	 * Tailwind-parity extended utilities: negative margins, transforms,
	 * transitions, animations, filters, extended text sizes, truncate,
	 * line-clamp, aspect-ratio, space-x/y, divide-x/y, and gradient
	 * direction utilities. Colour utilities remain Spectra-palette-only.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_extended_classes(): array {
		$classes = array();

		// --- Negative margins (mirrors the fixed spacing scale) ---
		$neg_sizes = array(
			'px'  => '-1px',
			'0.5' => '-0.125rem',
			'1'   => '-0.25rem',
			'1.5' => '-0.375rem',
			'2'   => '-0.5rem',
			'2.5' => '-0.625rem',
			'3'   => '-0.75rem',
			'3.5' => '-0.875rem',
			'4'   => '-1rem',
			'5'   => '-1.25rem',
			'6'   => '-1.5rem',
			'8'   => '-2rem',
			'10'  => '-2.5rem',
			'12'  => '-3rem',
			'16'  => '-4rem',
			'20'  => '-5rem',
			'24'  => '-6rem',
			'32'  => '-8rem',
			'40'  => '-10rem',
			'48'  => '-12rem',
			'56'  => '-14rem',
			'64'  => '-16rem',
		);

		$neg_positions = array(
			'-m'  => array( 'margin' ),
			'-mt' => array( 'margin-top' ),
			'-mr' => array( 'margin-right' ),
			'-mb' => array( 'margin-bottom' ),
			'-ml' => array( 'margin-left' ),
			'-mx' => array( 'margin-right', 'margin-left' ),
			'-my' => array( 'margin-top', 'margin-bottom' ),
		);

		foreach ( $neg_positions as $prefix => $props ) {
			foreach ( $neg_sizes as $size => $value ) {
				$parts = array();
				foreach ( $props as $prop ) {
					$parts[] = "{$prop}: {$value};";
				}
				$classes[ "{$prefix}-{$size}" ] = array(
					'css'         => implode( ' ', $parts ),
					'title'       => "Negative {$prefix} {$size}",
					'description' => "Applies a negative margin of {$value}.",
					'category'    => 'spacing',
					'tags'        => array( 'margin', 'negative' ),
				);
			}
		}

		// --- space-x-* / space-y-* (sibling-gap utilities) ---
		$space_sizes = array(
			'0'   => '0',
			'px'  => '1px',
			'0.5' => '0.125rem',
			'1'   => '0.25rem',
			'1.5' => '0.375rem',
			'2'   => '0.5rem',
			'2.5' => '0.625rem',
			'3'   => '0.75rem',
			'4'   => '1rem',
			'5'   => '1.25rem',
			'6'   => '1.5rem',
			'8'   => '2rem',
			'10'  => '2.5rem',
			'12'  => '3rem',
			'16'  => '4rem',
			'20'  => '5rem',
			'24'  => '6rem',
		);

		foreach ( $space_sizes as $size => $value ) {
			$classes[ "space-x-{$size}" ] = array(
				'css'         => "& > :not([hidden]) ~ :not([hidden]) { margin-left: {$value}; }",
				'title'       => "Horizontal sibling gap {$size}",
				'description' => "Adds {$value} of horizontal gap between direct children.",
				'category'    => 'spacing',
				'tags'        => array( 'space', 'gap', 'sibling' ),
			);
			$classes[ "space-y-{$size}" ] = array(
				'css'         => "& > :not([hidden]) ~ :not([hidden]) { margin-top: {$value}; }",
				'title'       => "Vertical sibling gap {$size}",
				'description' => "Adds {$value} of vertical gap between direct children.",
				'category'    => 'spacing',
				'tags'        => array( 'space', 'gap', 'sibling' ),
			);
		}

		// --- Negative variants: -space-x-* / -space-y-* ---
		// Mirrors the negative-margin pattern at lines 2708-2722 above.
		// Without these entries the JIT silently ignores `-space-x-3`/`-space-y-*`
		// authored on flex containers (e.g. avatar stacks using `flex -space-x-3`),
		// which break the intended sibling overlap.
		foreach ( $space_sizes as $size => $value ) {
			// Skip the '0' entry — `-space-x-0` is identical to `space-x-0`.
			if ( '0' === $size ) {
				continue;
			}
			$neg_value                     = '-' . $value;
			$classes[ "-space-x-{$size}" ] = array(
				'css'         => "& > :not([hidden]) ~ :not([hidden]) { margin-left: {$neg_value}; }",
				'title'       => "Horizontal sibling overlap {$size}",
				'description' => "Pulls direct children leftward by {$value} so siblings overlap.",
				'category'    => 'spacing',
				'tags'        => array( 'space', 'gap', 'sibling', 'negative' ),
			);
			$classes[ "-space-y-{$size}" ] = array(
				'css'         => "& > :not([hidden]) ~ :not([hidden]) { margin-top: {$neg_value}; }",
				'title'       => "Vertical sibling overlap {$size}",
				'description' => "Pulls direct children upward by {$value} so siblings overlap.",
				'category'    => 'spacing',
				'tags'        => array( 'space', 'gap', 'sibling', 'negative' ),
			);
		}

		// --- divide-x-* / divide-y-* (sibling borders) ---
		$divide_widths = array(
			'0' => '0px',
			'2' => '2px',
			'4' => '4px',
			'8' => '8px',
		);

		foreach ( $divide_widths as $size => $value ) {
			$classes[ "divide-x-{$size}" ] = array(
				'css'         => "& > :not([hidden]) ~ :not([hidden]) { border-left-width: {$value}; }",
				'title'       => "Horizontal divider {$size}",
				'description' => "Left border of {$value} between siblings.",
				'category'    => 'border',
				'tags'        => array( 'divide', 'border' ),
			);
			$classes[ "divide-y-{$size}" ] = array(
				'css'         => "& > :not([hidden]) ~ :not([hidden]) { border-top-width: {$value}; }",
				'title'       => "Vertical divider {$size}",
				'description' => "Top border of {$value} between siblings.",
				'category'    => 'border',
				'tags'        => array( 'divide', 'border' ),
			);
		}

		// Default divide with no numeric suffix = 1px.
		$classes['divide-x'] = array(
			'css'         => '& > :not([hidden]) ~ :not([hidden]) { border-left-width: 1px; }',
			'title'       => 'Horizontal divider',
			'description' => '1px left border between siblings.',
			'category'    => 'border',
			'tags'        => array( 'divide', 'border' ),
		);
		$classes['divide-y'] = array(
			'css'         => '& > :not([hidden]) ~ :not([hidden]) { border-top-width: 1px; }',
			'title'       => 'Vertical divider',
			'description' => '1px top border between siblings.',
			'category'    => 'border',
			'tags'        => array( 'divide', 'border' ),
		);

		// --- Flex basis ---
		$basis_fractions = array(
			'auto' => 'auto',
			'full' => '100%',
			'0'    => '0%',
			'1/2'  => '50%',
			'1/3'  => '33.333333%',
			'2/3'  => '66.666667%',
			'1/4'  => '25%',
			'2/4'  => '50%',
			'3/4'  => '75%',
			'1/5'  => '20%',
			'2/5'  => '40%',
			'3/5'  => '60%',
			'4/5'  => '80%',
			'1/6'  => '16.666667%',
			'5/6'  => '83.333333%',
			'1/12' => '8.333333%',
		);

		foreach ( $basis_fractions as $key => $value ) {
			$classes[ "basis-{$key}" ] = array(
				'css'         => "flex-basis: {$value};",
				'title'       => "Flex basis {$key}",
				'description' => "Sets flex-basis to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'flex', 'basis' ),
			);
		}

		// --- Extended font sizes (Tailwind 3xl through 9xl) ---
		$extended_text = array(
			'3xl' => array( '1.875rem', '2.25rem' ),
			'4xl' => array( '2.25rem', '2.5rem' ),
			'5xl' => array( '3rem', '1' ),
			'6xl' => array( '3.75rem', '1' ),
			'7xl' => array( '4.5rem', '1' ),
			'8xl' => array( '6rem', '1' ),
			'9xl' => array( '8rem', '1' ),
		);

		foreach ( $extended_text as $label => $pair ) {
			[ $size, $lh ]              = $pair;
			$classes[ "text-{$label}" ] = array(
				'css'         => "font-size: {$size}; line-height: {$lh};",
				'title'       => "{$label} text",
				'description' => "Font size {$size} with matching line-height.",
				'category'    => 'typography',
				'tags'        => array( 'text', 'font-size' ),
			);
		}

		// --- Truncate + line-clamp ---
		$classes['truncate'] = array(
			'css'         => 'overflow: hidden; text-overflow: ellipsis; white-space: nowrap;',
			'title'       => 'Truncate',
			'description' => 'Truncates overflowing text with an ellipsis.',
			'category'    => 'typography',
			'tags'        => array( 'truncate', 'ellipsis' ),
		);

		for ( $i = 1; $i <= 6; $i++ ) {
			$classes[ "line-clamp-{$i}" ] = array(
				'css'         => "overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: {$i}; line-clamp: {$i};",
				'title'       => "Line clamp {$i}",
				'description' => "Clamps text to {$i} lines with ellipsis overflow.",
				'category'    => 'typography',
				'tags'        => array( 'line-clamp', 'ellipsis' ),
			);
		}

		$classes['line-clamp-none'] = array(
			'css'         => 'overflow: visible; display: block; -webkit-box-orient: horizontal; -webkit-line-clamp: none; line-clamp: none;',
			'title'       => 'Line clamp none',
			'description' => 'Removes line-clamp restriction.',
			'category'    => 'typography',
			'tags'        => array( 'line-clamp' ),
		);

		// --- Aspect ratio ---
		$aspect = array(
			'aspect-auto'   => 'auto',
			'aspect-square' => '1 / 1',
			'aspect-video'  => '16 / 9',
		);

		foreach ( $aspect as $name => $value ) {
			$classes[ $name ] = array(
				'css'         => "aspect-ratio: {$value};",
				'title'       => ucwords( str_replace( '-', ' ', $name ) ),
				'description' => "Sets aspect-ratio to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'aspect-ratio' ),
			);
		}

		// --- Transforms ---
		$rotate = array( 0, 1, 2, 3, 6, 12, 45, 90, 180 );
		foreach ( $rotate as $deg ) {
			$classes[ "rotate-{$deg}" ]  = array(
				'css'         => "transform: rotate({$deg}deg);",
				'title'       => "Rotate {$deg}",
				'description' => "Rotates the element by {$deg} degrees.",
				'category'    => 'transform',
				'tags'        => array( 'rotate', 'transform' ),
			);
			$classes[ "-rotate-{$deg}" ] = array(
				'css'         => "transform: rotate(-{$deg}deg);",
				'title'       => "Rotate -{$deg}",
				'description' => "Rotates the element by -{$deg} degrees.",
				'category'    => 'transform',
				'tags'        => array( 'rotate', 'transform', 'negative' ),
			);
		}

		$scales = array( 0, 50, 75, 90, 95, 100, 105, 110, 125, 150 );
		foreach ( $scales as $s ) {
			$value                     = number_format( $s / 100, 2, '.', '' );
			$classes[ "scale-{$s}" ]   = array(
				'css'         => "transform: scale({$value});",
				'title'       => "Scale {$s}",
				'description' => "Scales the element to {$s}%.",
				'category'    => 'transform',
				'tags'        => array( 'scale', 'transform' ),
			);
			$classes[ "scale-x-{$s}" ] = array(
				'css'         => "transform: scaleX({$value});",
				'title'       => "Scale X {$s}",
				'description' => "Scales the element horizontally to {$s}%.",
				'category'    => 'transform',
				'tags'        => array( 'scale', 'transform' ),
			);
			$classes[ "scale-y-{$s}" ] = array(
				'css'         => "transform: scaleY({$value});",
				'title'       => "Scale Y {$s}",
				'description' => "Scales the element vertically to {$s}%.",
				'category'    => 'transform',
				'tags'        => array( 'scale', 'transform' ),
			);
		}

		// `translate-x-*` / `-translate-x-*` / `translate-y-*` / `-translate-y-*`
		// are resolved exclusively through {@see \SpectraBlocks\GlobalStyles\JitCompiler::resolve_tw_translate}
		// which emits the CSS individual `translate:` property (plus the
		// `--tw-translate-{x,y}` axis vars so X and Y compose on the same
		// element). Registering parallel `transform: translateX(...)` rules
		// here previously caused both properties to apply to the same element
		// — `transform` and `translate` are independent CSS properties that
		// compose additively, so the centring idiom `left-1/2 -translate-x-1/2`
		// shifted elements by -100% of their own width instead of -50%.
		//
		// @since 1.0.0
		$skews = array( 0, 1, 2, 3, 6, 12 );
		foreach ( $skews as $deg ) {
			$classes[ "skew-x-{$deg}" ]  = array(
				'css'         => "transform: skewX({$deg}deg);",
				'title'       => "Skew X {$deg}",
				'description' => "Skews the element horizontally by {$deg}deg.",
				'category'    => 'transform',
				'tags'        => array( 'skew', 'transform' ),
			);
			$classes[ "skew-y-{$deg}" ]  = array(
				'css'         => "transform: skewY({$deg}deg);",
				'title'       => "Skew Y {$deg}",
				'description' => "Skews the element vertically by {$deg}deg.",
				'category'    => 'transform',
				'tags'        => array( 'skew', 'transform' ),
			);
			$classes[ "-skew-x-{$deg}" ] = array(
				'css'         => "transform: skewX(-{$deg}deg);",
				'title'       => "Skew X -{$deg}",
				'description' => "Skews the element horizontally by -{$deg}deg.",
				'category'    => 'transform',
				'tags'        => array( 'skew', 'transform', 'negative' ),
			);
			$classes[ "-skew-y-{$deg}" ] = array(
				'css'         => "transform: skewY(-{$deg}deg);",
				'title'       => "Skew Y -{$deg}",
				'description' => "Skews the element vertically by -{$deg}deg.",
				'category'    => 'transform',
				'tags'        => array( 'skew', 'transform', 'negative' ),
			);
		}

		$origins = array(
			'center'       => 'center',
			'top'          => 'top',
			'top-right'    => 'top right',
			'right'        => 'right',
			'bottom-right' => 'bottom right',
			'bottom'       => 'bottom',
			'bottom-left'  => 'bottom left',
			'left'         => 'left',
			'top-left'     => 'top left',
		);

		foreach ( $origins as $key => $value ) {
			$classes[ "origin-{$key}" ] = array(
				'css'         => "transform-origin: {$value};",
				'title'       => "Origin {$key}",
				'description' => "Sets transform-origin to {$value}.",
				'category'    => 'transform',
				'tags'        => array( 'origin', 'transform' ),
			);
		}

		// --- Transitions ---
		$classes['transition']           = array(
			'css'         => 'transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms;',
			'title'       => 'Transition',
			'description' => 'Default transition for common properties.',
			'category'    => 'transition',
			'tags'        => array( 'transition' ),
		);
		$classes['transition-none']      = array(
			'css'         => 'transition-property: none;',
			'title'       => 'Transition none',
			'description' => 'Removes transition property.',
			'category'    => 'transition',
			'tags'        => array( 'transition' ),
		);
		$classes['transition-all']       = array(
			'css'         => 'transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms;',
			'title'       => 'Transition all',
			'description' => 'Transitions all animatable properties.',
			'category'    => 'transition',
			'tags'        => array( 'transition' ),
		);
		$classes['transition-colors']    = array(
			'css'         => 'transition-property: color, background-color, border-color, text-decoration-color, fill, stroke; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms;',
			'title'       => 'Transition colors',
			'description' => 'Transitions colour-related properties.',
			'category'    => 'transition',
			'tags'        => array( 'transition', 'colors' ),
		);
		$classes['transition-opacity']   = array(
			'css'         => 'transition-property: opacity; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms;',
			'title'       => 'Transition opacity',
			'description' => 'Transitions opacity.',
			'category'    => 'transition',
			'tags'        => array( 'transition', 'opacity' ),
		);
		$classes['transition-shadow']    = array(
			'css'         => 'transition-property: box-shadow; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms;',
			'title'       => 'Transition shadow',
			'description' => 'Transitions box-shadow.',
			'category'    => 'transition',
			'tags'        => array( 'transition', 'shadow' ),
		);
		$classes['transition-transform'] = array(
			'css'         => 'transition-property: transform, translate, scale, rotate; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms;',
			'title'       => 'Transition transform',
			'description' => 'Transitions transform, translate, scale, rotate (Tailwind v4 spec).',
			'category'    => 'transition',
			'tags'        => array( 'transition', 'transform' ),
		);

		$durations = array( 0, 75, 100, 150, 200, 300, 500, 700, 1000 );
		foreach ( $durations as $ms ) {
			$classes[ "duration-{$ms}" ] = array(
				'css'         => "transition-duration: {$ms}ms;",
				'title'       => "Duration {$ms}ms",
				'description' => "Sets transition-duration to {$ms}ms.",
				'category'    => 'transition',
				'tags'        => array( 'transition', 'duration' ),
			);
			$classes[ "delay-{$ms}" ]    = array(
				'css'         => "transition-delay: {$ms}ms;",
				'title'       => "Delay {$ms}ms",
				'description' => "Sets transition-delay to {$ms}ms.",
				'category'    => 'transition',
				'tags'        => array( 'transition', 'delay' ),
			);
		}

		$eases = array(
			'linear' => 'linear',
			'in'     => 'cubic-bezier(0.4, 0, 1, 1)',
			'out'    => 'cubic-bezier(0, 0, 0.2, 1)',
			'in-out' => 'cubic-bezier(0.4, 0, 0.2, 1)',
		);
		foreach ( $eases as $key => $value ) {
			$classes[ "ease-{$key}" ] = array(
				'css'         => "transition-timing-function: {$value};",
				'title'       => "Ease {$key}",
				'description' => "Sets transition-timing-function to {$key}.",
				'category'    => 'transition',
				'tags'        => array( 'transition', 'ease' ),
			);
		}

		// --- Animations (with built-in keyframes) ---
		$spin_kf   = '@keyframes spectra-spin { to { transform: rotate(360deg); } }';
		$ping_kf   = '@keyframes spectra-ping { 75%, 100% { transform: scale(2); opacity: 0; } }';
		$pulse_kf  = '@keyframes spectra-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }';
		$bounce_kf = '@keyframes spectra-bounce { 0%, 100% { transform: translateY(-25%); animation-timing-function: cubic-bezier(0.8, 0, 1, 1); } 50% { transform: none; animation-timing-function: cubic-bezier(0, 0, 0.2, 1); } }';

		$classes['animate-none']   = array(
			'css'         => 'animation: none;',
			'title'       => 'Animate none',
			'description' => 'Removes animation.',
			'category'    => 'animation',
			'tags'        => array( 'animation' ),
		);
		$classes['animate-spin']   = array(
			'css'         => 'animation: spectra-spin 1s linear infinite;',
			'title'       => 'Animate spin',
			'description' => 'Spins the element continuously.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'spin' ),
			'keyframes'   => $spin_kf,
		);
		$classes['animate-ping']   = array(
			'css'         => 'animation: spectra-ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;',
			'title'       => 'Animate ping',
			'description' => 'Ping pulse animation.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'ping' ),
			'keyframes'   => $ping_kf,
		);
		$classes['animate-pulse']  = array(
			'css'         => 'animation: spectra-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;',
			'title'       => 'Animate pulse',
			'description' => 'Opacity pulse animation.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'pulse' ),
			'keyframes'   => $pulse_kf,
		);
		$classes['animate-bounce'] = array(
			'css'         => 'animation: spectra-bounce 1s infinite;',
			'title'       => 'Animate bounce',
			'description' => 'Vertical bounce animation.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'bounce' ),
			'keyframes'   => $bounce_kf,
		);

		// --- Preset animation library for LLM-authored entrance + staging effects.
		// Every preset uses `animation-fill-mode: both` so elements hold their
		// final frame after animating (the LLM typically pairs entrance presets
		// with `opacity-0` as the starting state). Keyframe names follow the
		// `spectra-<name>` convention so REST keyframe CRUD can reserve them
		// alongside the existing spin/ping/pulse/bounce builtins.
		$fade_up_kf     = '@keyframes spectra-fade-up { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }';
		$fade_in_kf     = '@keyframes spectra-fade-in { 0% { opacity: 0; } 100% { opacity: 1; } }';
		$fade_down_kf   = '@keyframes spectra-fade-down { 0% { opacity: 0; transform: translateY(-20px); } 100% { opacity: 1; transform: translateY(0); } }';
		$slide_left_kf  = '@keyframes spectra-slide-left { 0% { opacity: 0; transform: translateX(30px); } 100% { opacity: 1; transform: translateX(0); } }';
		$slide_right_kf = '@keyframes spectra-slide-right { 0% { opacity: 0; transform: translateX(-30px); } 100% { opacity: 1; transform: translateX(0); } }';
		$scale_in_kf    = '@keyframes spectra-scale-in { 0% { opacity: 0; transform: scale(0.95); } 100% { opacity: 1; transform: scale(1); } }';
		$ring_in_kf     = '@keyframes spectra-ring-in { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }';
		$ring_dash_kf   = '@keyframes spectra-ring-dash { 0% { stroke-dashoffset: 100; } 100% { stroke-dashoffset: 0; } }';
		$drift_kf       = '@keyframes spectra-drift { 0% { transform: translate(0, 0); opacity: 0.4; } 50% { transform: translate(10px, -20px); opacity: 0.8; } 100% { transform: translate(0, 0); opacity: 0.4; } }';
		$pulse_dot_kf   = '@keyframes spectra-pulse-dot { 0%, 100% { box-shadow: 0 0 0 0 currentColor; } 50% { box-shadow: 0 0 0 8px transparent; } }';
		$wiggle_kf      = '@keyframes spectra-wiggle { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-3deg); } 75% { transform: rotate(3deg); } }';
		$shake_kf       = '@keyframes spectra-shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-4px); } 75% { transform: translateX(4px); } }';
		$reveal_kf      = '@keyframes spectra-reveal { 0% { clip-path: inset(0 100% 0 0); } 100% { clip-path: inset(0 0 0 0); } }';
		$fade_up_m_kf   = '@keyframes spectra-fade-up-m { 0% { opacity: 0; transform: translateY(12px); } 100% { opacity: 1; transform: translateY(0); } }';

		$classes['animate-fade-up']     = array(
			'css'         => 'animation: spectra-fade-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;',
			'title'       => 'Animate fade-up',
			'description' => 'Entrance: fade in while translating up.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'entrance', 'fade' ),
			'keyframes'   => $fade_up_kf,
		);
		$classes['animate-fade-in']     = array(
			'css'         => 'animation: spectra-fade-in 0.5s ease-out both;',
			'title'       => 'Animate fade-in',
			'description' => 'Entrance: opacity 0 to 1.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'entrance', 'fade' ),
			'keyframes'   => $fade_in_kf,
		);
		$classes['animate-fade-down']   = array(
			'css'         => 'animation: spectra-fade-down 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;',
			'title'       => 'Animate fade-down',
			'description' => 'Entrance: fade in while translating down.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'entrance', 'fade' ),
			'keyframes'   => $fade_down_kf,
		);
		$classes['animate-slide-left']  = array(
			'css'         => 'animation: spectra-slide-left 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;',
			'title'       => 'Animate slide-left',
			'description' => 'Entrance: slide in from right.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'entrance', 'slide' ),
			'keyframes'   => $slide_left_kf,
		);
		$classes['animate-slide-right'] = array(
			'css'         => 'animation: spectra-slide-right 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;',
			'title'       => 'Animate slide-right',
			'description' => 'Entrance: slide in from left.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'entrance', 'slide' ),
			'keyframes'   => $slide_right_kf,
		);
		$classes['animate-scale-in']    = array(
			'css'         => 'animation: spectra-scale-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;',
			'title'       => 'Animate scale-in',
			'description' => 'Entrance: scale from 0.95 to 1 with fade.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'entrance', 'scale' ),
			'keyframes'   => $scale_in_kf,
		);
		$classes['animate-ring-in']     = array(
			'css'         => 'animation: spectra-ring-in 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;',
			'title'       => 'Animate ring-in',
			'description' => 'Entrance: scale from 0.8 to 1 with fade (for rings/orbits).',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'entrance', 'ring' ),
			'keyframes'   => $ring_in_kf,
		);
		$classes['animate-ring-dash']   = array(
			'css'         => 'animation: spectra-ring-dash 1.2s ease-out both;',
			'title'       => 'Animate ring-dash',
			'description' => 'SVG stroke-dashoffset reveal for dashed rings/circles.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'svg' ),
			'keyframes'   => $ring_dash_kf,
		);
		$classes['animate-drift']       = array(
			'css'         => 'animation: spectra-drift 18s ease-in-out infinite;',
			'title'       => 'Animate drift',
			'description' => 'Slow floating motion loop for particles/decorative elements.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'ambient' ),
			'keyframes'   => $drift_kf,
		);
		$classes['animate-pulse-dot']   = array(
			'css'         => 'animation: spectra-pulse-dot 2s ease-in-out infinite;',
			'title'       => 'Animate pulse-dot',
			'description' => 'Box-shadow pulse for live/status indicator dots.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'status' ),
			'keyframes'   => $pulse_dot_kf,
		);
		$classes['animate-wiggle']      = array(
			'css'         => 'animation: spectra-wiggle 0.8s ease-in-out;',
			'title'       => 'Animate wiggle',
			'description' => 'Small rotational oscillation for attention.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'attention' ),
			'keyframes'   => $wiggle_kf,
		);
		$classes['animate-shake']       = array(
			'css'         => 'animation: spectra-shake 0.4s ease-in-out;',
			'title'       => 'Animate shake',
			'description' => 'Horizontal shake for errors / attention.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'attention' ),
			'keyframes'   => $shake_kf,
		);
		$classes['animate-reveal']      = array(
			'css'         => 'animation: spectra-reveal 0.8s cubic-bezier(0.77, 0, 0.18, 1) both;',
			'title'       => 'Animate reveal',
			'description' => 'Clip-path wipe reveal from left to right.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'entrance', 'reveal' ),
			'keyframes'   => $reveal_kf,
		);
		$classes['animate-fade-up-m']   = array(
			'css'         => 'animation: spectra-fade-up-m 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;',
			'title'       => 'Animate fade-up (mobile)',
			'description' => 'Shorter-distance fade-up for mobile layouts.',
			'category'    => 'animation',
			'tags'        => array( 'animation', 'entrance', 'mobile' ),
			'keyframes'   => $fade_up_m_kf,
		);

		// --- Filters ---
		// Aligned with Tailwind v4 defaults: `xs: 4px` is new, `sm` promoted from
		// 4px → 8px (matches v4 base `--blur` scale). v3 had no `xs` and used
		// 4px for `sm`. Backdrop-blur shares the same scale (see get_filter_classes).
		$blur_sizes = array(
			'xs'  => '4px',
			'sm'  => '8px',
			''    => '8px',
			'md'  => '12px',
			'lg'  => '16px',
			'xl'  => '24px',
			'2xl' => '40px',
			'3xl' => '64px',
		);
		foreach ( $blur_sizes as $key => $value ) {
			$name             = '' === $key ? 'blur' : "blur-{$key}";
			$classes[ $name ] = array(
				'css'         => "filter: blur({$value});",
				'title'       => ucwords( str_replace( '-', ' ', $name ) ),
				'description' => "Applies a blur filter of {$value}.",
				'category'    => 'filter',
				'tags'        => array( 'filter', 'blur' ),
			);
		}

		$percent_filters = array(
			'brightness' => array( 0, 50, 75, 90, 95, 100, 105, 110, 125, 150, 200 ),
			'contrast'   => array( 0, 50, 75, 100, 125, 150, 200 ),
			'saturate'   => array( 0, 50, 100, 150, 200 ),
		);
		foreach ( $percent_filters as $prop => $steps ) {
			foreach ( $steps as $step ) {
				$value                        = number_format( $step / 100, 2, '.', '' );
				$classes[ "{$prop}-{$step}" ] = array(
					'css'         => "filter: {$prop}({$value});",
					'title'       => ucfirst( $prop ) . " {$step}",
					'description' => "Applies {$prop}({$value}) filter.",
					'category'    => 'filter',
					'tags'        => array( 'filter', $prop ),
				);
			}
		}

		$boolean_filters = array(
			'grayscale' => 'grayscale(100%)',
			'invert'    => 'invert(100%)',
			'sepia'     => 'sepia(100%)',
		);
		foreach ( $boolean_filters as $name => $value ) {
			$classes[ $name ]       = array(
				'css'         => "filter: {$value};",
				'title'       => ucfirst( $name ),
				'description' => "Applies {$value} filter.",
				'category'    => 'filter',
				'tags'        => array( 'filter', $name ),
			);
			$classes[ "{$name}-0" ] = array(
				'css'         => "filter: {$name}(0);",
				'title'       => ucfirst( $name ) . ' 0',
				'description' => "Disables the {$name} filter.",
				'category'    => 'filter',
				'tags'        => array( 'filter', $name ),
			);
		}

		$hue_steps = array( 0, 15, 30, 60, 90, 180 );
		foreach ( $hue_steps as $deg ) {
			$classes[ "hue-rotate-{$deg}" ]  = array(
				'css'         => "filter: hue-rotate({$deg}deg);",
				'title'       => "Hue rotate {$deg}",
				'description' => "Applies a hue-rotate filter of {$deg}deg.",
				'category'    => 'filter',
				'tags'        => array( 'filter', 'hue-rotate' ),
			);
			$classes[ "-hue-rotate-{$deg}" ] = array(
				'css'         => "filter: hue-rotate(-{$deg}deg);",
				'title'       => "Hue rotate -{$deg}",
				'description' => "Applies a hue-rotate filter of -{$deg}deg.",
				'category'    => 'filter',
				'tags'        => array( 'filter', 'hue-rotate', 'negative' ),
			);
		}

		$drop_shadows = array(
			'none' => 'drop-shadow(0 0 #0000)',
			'sm'   => 'drop-shadow(0 1px 1px rgba(0,0,0,0.05))',
			''     => 'drop-shadow(0 1px 2px rgba(0,0,0,0.1)) drop-shadow(0 1px 1px rgba(0,0,0,0.06))',
			'md'   => 'drop-shadow(0 4px 3px rgba(0,0,0,0.07)) drop-shadow(0 2px 2px rgba(0,0,0,0.06))',
			'lg'   => 'drop-shadow(0 10px 8px rgba(0,0,0,0.04)) drop-shadow(0 4px 3px rgba(0,0,0,0.1))',
			'xl'   => 'drop-shadow(0 20px 13px rgba(0,0,0,0.03)) drop-shadow(0 8px 5px rgba(0,0,0,0.08))',
			'2xl'  => 'drop-shadow(0 25px 25px rgba(0,0,0,0.15))',
		);
		foreach ( $drop_shadows as $key => $value ) {
			$name             = '' === $key ? 'drop-shadow' : "drop-shadow-{$key}";
			$classes[ $name ] = array(
				'css'         => "filter: {$value};",
				'title'       => ucwords( str_replace( '-', ' ', $name ) ),
				'description' => "Applies a drop-shadow filter ({$name}).",
				'category'    => 'filter',
				'tags'        => array( 'filter', 'drop-shadow' ),
			);
		}

		// --- Gradient directions (palette stops live in get_color_classes as from-/via-/to-) ---
		$gradient_dirs = array(
			'to-t'  => 'to top',
			'to-tr' => 'to top right',
			'to-r'  => 'to right',
			'to-br' => 'to bottom right',
			'to-b'  => 'to bottom',
			'to-bl' => 'to bottom left',
			'to-l'  => 'to left',
			'to-tl' => 'to top left',
		);
		// Single `--tw-gradient-*` namespace for ALL gradient stops + directions
		// (v3 `bg-gradient-to-*` and v4 `bg-linear-*`/`bg-radial-*`/`bg-conic-*`
		// both consume it). Unified so mixing palette-slug stops with bracket
		// stops — or mixing v3 with v4 directions — always produces a
		// non-transparent gradient.
		$fallback_stops = 'var(--tw-gradient-from, transparent), var(--tw-gradient-to, transparent)';
		foreach ( $gradient_dirs as $key => $direction ) {
			$classes[ "bg-gradient-{$key}" ] = array(
				'css'         => "background-image: linear-gradient({$direction}, var(--tw-gradient-stops, {$fallback_stops}));",
				'title'       => "Gradient {$key}",
				'description' => "Linear gradient direction {$direction}.",
				'category'    => 'gradient',
				'tags'        => array( 'gradient', 'background' ),
			);
		}

		return $classes;
	}

	/**
	 * Returns Tailwind v3 parity utility presets.
	 *
	 * Fills gaps between Spectra's existing utility surface and Tailwind v3's
	 * standard utilities so authors can use familiar class names instead of the
	 * arbitrary-property form. Families added here are documented inline with
	 * `// --- Family name ---` separators.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_parity_v3_classes(): array {
		$classes = array();

		// --- Text decoration: underline-offset / decoration style & thickness ---
		$underline_offsets = array(
			'0'    => '0px',
			'1'    => '1px',
			'2'    => '2px',
			'4'    => '4px',
			'8'    => '8px',
			'auto' => 'auto',
		);
		foreach ( $underline_offsets as $key => $value ) {
			$classes[ "underline-offset-{$key}" ] = array(
				'css'         => "text-underline-offset: {$value};",
				'title'       => "Underline offset {$key}",
				'description' => "Sets the text underline offset to {$value}.",
				'category'    => 'typography',
				'tags'        => array( 'underline', 'text-decoration', 'offset' ),
			);
		}

		$decoration_styles = array( 'solid', 'double', 'dotted', 'dashed', 'wavy' );
		foreach ( $decoration_styles as $style ) {
			$classes[ "decoration-{$style}" ] = array(
				'css'         => "text-decoration-style: {$style};",
				'title'       => "Decoration style {$style}",
				'description' => "Sets text-decoration-style to {$style}.",
				'category'    => 'typography',
				'tags'        => array( 'decoration', 'text-decoration', 'style' ),
			);
		}

		$decoration_thickness = array(
			'0' => '0px',
			'1' => '1px',
			'2' => '2px',
			'4' => '4px',
			'8' => '8px',
		);
		foreach ( $decoration_thickness as $key => $value ) {
			$classes[ "decoration-{$key}" ] = array(
				'css'         => "text-decoration-thickness: {$value};",
				'title'       => "Decoration thickness {$key}",
				'description' => "Sets text-decoration-thickness to {$value}.",
				'category'    => 'typography',
				'tags'        => array( 'decoration', 'text-decoration', 'thickness' ),
			);
		}

		$classes['decoration-auto']      = array(
			'css'         => 'text-decoration-thickness: auto;',
			'title'       => 'Decoration thickness auto',
			'description' => 'Lets the browser decide text-decoration thickness.',
			'category'    => 'typography',
			'tags'        => array( 'decoration', 'text-decoration', 'thickness' ),
		);
		$classes['decoration-from-font'] = array(
			'css'         => 'text-decoration-thickness: from-font;',
			'title'       => 'Decoration from font',
			'description' => 'Uses the font-defined text-decoration thickness.',
			'category'    => 'typography',
			'tags'        => array( 'decoration', 'text-decoration', 'thickness' ),
		);

		// --- Vertical align ---
		$vertical_aligns = array(
			'baseline'    => 'baseline',
			'top'         => 'top',
			'middle'      => 'middle',
			'bottom'      => 'bottom',
			'text-top'    => 'text-top',
			'text-bottom' => 'text-bottom',
			'sub'         => 'sub',
			'super'       => 'super',
		);
		foreach ( $vertical_aligns as $key => $value ) {
			$classes[ "align-{$key}" ] = array(
				'css'         => "vertical-align: {$value};",
				'title'       => "Vertical align {$key}",
				'description' => "Sets vertical-align to {$value}.",
				'category'    => 'typography',
				'tags'        => array( 'vertical-align', 'align' ),
			);
		}

		// --- Tables ---
		$classes['table-auto']      = array(
			'css'         => 'table-layout: auto;',
			'title'       => 'Table layout auto',
			'description' => 'Uses automatic table column sizing.',
			'category'    => 'layout',
			'tags'        => array( 'table', 'table-layout' ),
		);
		$classes['table-fixed']     = array(
			'css'         => 'table-layout: fixed;',
			'title'       => 'Table layout fixed',
			'description' => 'Uses fixed table column sizing.',
			'category'    => 'layout',
			'tags'        => array( 'table', 'table-layout' ),
		);
		$classes['border-collapse'] = array(
			'css'         => 'border-collapse: collapse;',
			'title'       => 'Border collapse',
			'description' => 'Collapses adjacent table cell borders.',
			'category'    => 'border',
			'tags'        => array( 'border', 'table' ),
		);
		$classes['border-separate'] = array(
			'css'         => 'border-collapse: separate;',
			'title'       => 'Border separate',
			'description' => 'Keeps table cell borders separate.',
			'category'    => 'border',
			'tags'        => array( 'border', 'table' ),
		);
		$classes['caption-top']     = array(
			'css'         => 'caption-side: top;',
			'title'       => 'Caption top',
			'description' => 'Positions the table caption above the table.',
			'category'    => 'layout',
			'tags'        => array( 'caption', 'table' ),
		);
		$classes['caption-bottom']  = array(
			'css'         => 'caption-side: bottom;',
			'title'       => 'Caption bottom',
			'description' => 'Positions the table caption below the table.',
			'category'    => 'layout',
			'tags'        => array( 'caption', 'table' ),
		);

		$border_spacing_scale = array(
			'0'   => '0',
			'px'  => '1px',
			'0.5' => '0.125rem',
			'1'   => '0.25rem',
			'1.5' => '0.375rem',
			'2'   => '0.5rem',
			'2.5' => '0.625rem',
			'3'   => '0.75rem',
			'4'   => '1rem',
			'5'   => '1.25rem',
			'6'   => '1.5rem',
			'8'   => '2rem',
			'10'  => '2.5rem',
			'12'  => '3rem',
			'16'  => '4rem',
			'20'  => '5rem',
			'24'  => '6rem',
		);
		foreach ( $border_spacing_scale as $key => $value ) {
			$classes[ "border-spacing-{$key}" ] = array(
				'css'         => "border-spacing: {$value};",
				'title'       => "Border spacing {$key}",
				'description' => "Sets border-spacing to {$value}.",
				'category'    => 'border',
				'tags'        => array( 'border-spacing', 'table' ),
			);
		}

		// --- Screen readers ---
		$classes['sr-only']     = array(
			'css'         => 'position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0;',
			'title'       => 'Screen-reader only',
			'description' => 'Visually hides content while keeping it available to assistive technology.',
			'category'    => 'accessibility',
			'tags'        => array( 'sr-only', 'a11y', 'screen-reader' ),
		);
		$classes['not-sr-only'] = array(
			'css'         => 'position: static; width: auto; height: auto; padding: 0; margin: 0; overflow: visible; clip: auto; white-space: normal;',
			'title'       => 'Not screen-reader only',
			'description' => 'Restores a screen-reader only element to its natural visual layout.',
			'category'    => 'accessibility',
			'tags'        => array( 'sr-only', 'a11y', 'screen-reader' ),
		);

		// --- Appearance ---
		$classes['appearance-none'] = array(
			'css'         => 'appearance: none; -webkit-appearance: none;',
			'title'       => 'Appearance none',
			'description' => 'Removes native form control styling.',
			'category'    => 'interactivity',
			'tags'        => array( 'appearance', 'form' ),
		);
		$classes['appearance-auto'] = array(
			'css'         => 'appearance: auto;',
			'title'       => 'Appearance auto',
			'description' => 'Uses the native form control styling.',
			'category'    => 'interactivity',
			'tags'        => array( 'appearance', 'form' ),
		);

		// --- Resize ---
		$resize_values = array(
			'resize-none' => 'none',
			'resize-y'    => 'vertical',
			'resize-x'    => 'horizontal',
			'resize'      => 'both',
		);
		foreach ( $resize_values as $name => $value ) {
			$classes[ $name ] = array(
				'css'         => "resize: {$value};",
				'title'       => ucwords( str_replace( '-', ' ', $name ) ),
				'description' => "Sets resize to {$value}.",
				'category'    => 'interactivity',
				'tags'        => array( 'resize' ),
			);
		}

		// --- Scroll behavior ---
		$classes['scroll-auto']   = array(
			'css'         => 'scroll-behavior: auto;',
			'title'       => 'Scroll behavior auto',
			'description' => 'Uses default (instant) scroll behavior.',
			'category'    => 'interactivity',
			'tags'        => array( 'scroll', 'scroll-behavior' ),
		);
		$classes['scroll-smooth'] = array(
			'css'         => 'scroll-behavior: smooth;',
			'title'       => 'Scroll behavior smooth',
			'description' => 'Enables smooth scrolling between positions.',
			'category'    => 'interactivity',
			'tags'        => array( 'scroll', 'scroll-behavior' ),
		);

		// --- Scroll snap ---
		$snap_types = array(
			'none' => 'none',
			'x'    => 'x mandatory',
			'y'    => 'y mandatory',
			'both' => 'both mandatory',
		);
		foreach ( $snap_types as $key => $value ) {
			$classes[ "snap-{$key}" ] = array(
				'css'         => "scroll-snap-type: {$value};",
				'title'       => "Snap {$key}",
				'description' => "Sets scroll-snap-type to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'snap', 'scroll-snap' ),
			);
		}
		$classes['snap-mandatory'] = array(
			'css'         => '--tw-scroll-snap-strictness: mandatory;',
			'title'       => 'Snap mandatory',
			'description' => 'Sets scroll-snap strictness to mandatory.',
			'category'    => 'layout',
			'tags'        => array( 'snap', 'scroll-snap' ),
		);
		$classes['snap-proximity'] = array(
			'css'         => '--tw-scroll-snap-strictness: proximity;',
			'title'       => 'Snap proximity',
			'description' => 'Sets scroll-snap strictness to proximity.',
			'category'    => 'layout',
			'tags'        => array( 'snap', 'scroll-snap' ),
		);

		$snap_aligns = array(
			'start'      => 'start',
			'end'        => 'end',
			'center'     => 'center',
			'align-none' => 'none',
		);
		foreach ( $snap_aligns as $key => $value ) {
			$classes[ "snap-{$key}" ] = array(
				'css'         => "scroll-snap-align: {$value};",
				'title'       => "Snap align {$key}",
				'description' => "Sets scroll-snap-align to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'snap', 'scroll-snap-align' ),
			);
		}

		$snap_stops = array(
			'normal' => 'normal',
			'always' => 'always',
		);
		foreach ( $snap_stops as $key => $value ) {
			$classes[ "snap-{$key}" ] = array(
				'css'         => "scroll-snap-stop: {$value};",
				'title'       => "Snap stop {$key}",
				'description' => "Sets scroll-snap-stop to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'snap', 'scroll-snap-stop' ),
			);
		}

		// --- Scroll margin / scroll padding ---
		$scroll_scale      = array(
			'0'   => '0',
			'px'  => '1px',
			'0.5' => '0.125rem',
			'1'   => '0.25rem',
			'1.5' => '0.375rem',
			'2'   => '0.5rem',
			'2.5' => '0.625rem',
			'3'   => '0.75rem',
			'3.5' => '0.875rem',
			'4'   => '1rem',
			'5'   => '1.25rem',
			'6'   => '1.5rem',
			'8'   => '2rem',
			'10'  => '2.5rem',
			'12'  => '3rem',
			'16'  => '4rem',
			'20'  => '5rem',
			'24'  => '6rem',
		);
		$scroll_directions = array(
			'scroll-m'  => array( 'scroll-margin' ),
			'scroll-mt' => array( 'scroll-margin-top' ),
			'scroll-mr' => array( 'scroll-margin-right' ),
			'scroll-mb' => array( 'scroll-margin-bottom' ),
			'scroll-ml' => array( 'scroll-margin-left' ),
			'scroll-mx' => array( 'scroll-margin-right', 'scroll-margin-left' ),
			'scroll-my' => array( 'scroll-margin-top', 'scroll-margin-bottom' ),
			'scroll-p'  => array( 'scroll-padding' ),
			'scroll-pt' => array( 'scroll-padding-top' ),
			'scroll-pr' => array( 'scroll-padding-right' ),
			'scroll-pb' => array( 'scroll-padding-bottom' ),
			'scroll-pl' => array( 'scroll-padding-left' ),
			'scroll-px' => array( 'scroll-padding-right', 'scroll-padding-left' ),
			'scroll-py' => array( 'scroll-padding-top', 'scroll-padding-bottom' ),
		);
		foreach ( $scroll_directions as $prefix => $props ) {
			$kind = 0 === strpos( $prefix, 'scroll-m' ) ? 'margin' : 'padding';
			foreach ( $scroll_scale as $size => $value ) {
				$parts = array();
				foreach ( $props as $prop ) {
					$parts[] = "{$prop}: {$value};";
				}
				$classes[ "{$prefix}-{$size}" ] = array(
					'css'         => implode( ' ', $parts ),
					'title'       => "Scroll {$kind} {$prefix}-{$size}",
					'description' => "Applies scroll {$kind} of {$value}.",
					'category'    => 'spacing',
					'tags'        => array( 'scroll', $kind ),
				);
			}
		}

		// --- Overscroll behavior ---
		$overscroll_values = array( 'auto', 'contain', 'none' );
		foreach ( $overscroll_values as $value ) {
			$classes[ "overscroll-{$value}" ]   = array(
				'css'         => "overscroll-behavior: {$value};",
				'title'       => "Overscroll {$value}",
				'description' => "Sets overscroll-behavior to {$value}.",
				'category'    => 'interactivity',
				'tags'        => array( 'overscroll' ),
			);
			$classes[ "overscroll-x-{$value}" ] = array(
				'css'         => "overscroll-behavior-x: {$value};",
				'title'       => "Overscroll X {$value}",
				'description' => "Sets overscroll-behavior-x to {$value}.",
				'category'    => 'interactivity',
				'tags'        => array( 'overscroll' ),
			);
			$classes[ "overscroll-y-{$value}" ] = array(
				'css'         => "overscroll-behavior-y: {$value};",
				'title'       => "Overscroll Y {$value}",
				'description' => "Sets overscroll-behavior-y to {$value}.",
				'category'    => 'interactivity',
				'tags'        => array( 'overscroll' ),
			);
		}

		// --- Touch action ---
		$touch_values = array(
			'auto',
			'none',
			'pan-x',
			'pan-left',
			'pan-right',
			'pan-y',
			'pan-up',
			'pan-down',
			'pinch-zoom',
			'manipulation',
		);
		foreach ( $touch_values as $value ) {
			$classes[ "touch-{$value}" ] = array(
				'css'         => "touch-action: {$value};",
				'title'       => "Touch action {$value}",
				'description' => "Sets touch-action to {$value}.",
				'category'    => 'interactivity',
				'tags'        => array( 'touch', 'touch-action' ),
			);
		}

		// --- User select ---
		$select_values = array( 'none', 'text', 'all', 'auto' );
		foreach ( $select_values as $value ) {
			$classes[ "select-{$value}" ] = array(
				'css'         => "user-select: {$value};",
				'title'       => "User select {$value}",
				'description' => "Sets user-select to {$value}.",
				'category'    => 'interactivity',
				'tags'        => array( 'select', 'user-select' ),
			);
		}

		// --- Pointer events ---
		$pointer_events = array( 'none', 'auto' );
		foreach ( $pointer_events as $value ) {
			$classes[ "pointer-events-{$value}" ] = array(
				'css'         => "pointer-events: {$value};",
				'title'       => "Pointer events {$value}",
				'description' => "Sets pointer-events to {$value}.",
				'category'    => 'interactivity',
				'tags'        => array( 'pointer-events' ),
			);
		}

		// --- Will change ---
		$will_change = array(
			'auto'      => 'auto',
			'scroll'    => 'scroll-position',
			'contents'  => 'contents',
			'transform' => 'transform',
		);
		foreach ( $will_change as $key => $value ) {
			$classes[ "will-change-{$key}" ] = array(
				'css'         => "will-change: {$value};",
				'title'       => "Will change {$key}",
				'description' => "Hints the browser that {$value} will change.",
				'category'    => 'interactivity',
				'tags'        => array( 'will-change', 'performance' ),
			);
		}

		// --- Text overflow ---
		$classes['text-ellipsis'] = array(
			'css'         => 'text-overflow: ellipsis;',
			'title'       => 'Text overflow ellipsis',
			'description' => 'Truncates overflowing text with an ellipsis.',
			'category'    => 'typography',
			'tags'        => array( 'text-overflow', 'ellipsis' ),
		);
		$classes['text-clip']     = array(
			'css'         => 'text-overflow: clip;',
			'title'       => 'Text overflow clip',
			'description' => 'Clips overflowing text without an ellipsis.',
			'category'    => 'typography',
			'tags'        => array( 'text-overflow', 'clip' ),
		);

		// --- Word / line break ---
		$classes['break-normal'] = array(
			'css'         => 'overflow-wrap: normal; word-break: normal;',
			'title'       => 'Break normal',
			'description' => 'Uses default line-break and word-break behavior.',
			'category'    => 'typography',
			'tags'        => array( 'word-break', 'overflow-wrap' ),
		);
		$classes['break-words']  = array(
			'css'         => 'overflow-wrap: break-word;',
			'title'       => 'Break words',
			'description' => 'Allows long words to break onto the next line.',
			'category'    => 'typography',
			'tags'        => array( 'word-break', 'overflow-wrap' ),
		);
		$classes['break-all']    = array(
			'css'         => 'word-break: break-all;',
			'title'       => 'Break all',
			'description' => 'Allows line breaks between any two characters.',
			'category'    => 'typography',
			'tags'        => array( 'word-break' ),
		);
		$classes['break-keep']   = array(
			'css'         => 'word-break: keep-all;',
			'title'       => 'Break keep',
			'description' => 'Prevents line breaks within CJK text.',
			'category'    => 'typography',
			'tags'        => array( 'word-break' ),
		);

		// --- Font smoothing ---
		$classes['antialiased']          = array(
			'css'         => '-webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;',
			'title'       => 'Antialiased',
			'description' => 'Uses antialiased font smoothing for crisper text.',
			'category'    => 'typography',
			'tags'        => array( 'font-smoothing', 'antialias' ),
		);
		$classes['subpixel-antialiased'] = array(
			'css'         => '-webkit-font-smoothing: auto; -moz-osx-font-smoothing: auto;',
			'title'       => 'Subpixel antialiased',
			'description' => 'Uses subpixel font smoothing.',
			'category'    => 'typography',
			'tags'        => array( 'font-smoothing', 'antialias' ),
		);

		// --- Font variant numeric ---
		$font_variant_numeric = array(
			'normal-nums'        => 'normal',
			'ordinal'            => 'ordinal',
			'slashed-zero'       => 'slashed-zero',
			'lining-nums'        => 'lining-nums',
			'oldstyle-nums'      => 'oldstyle-nums',
			'proportional-nums'  => 'proportional-nums',
			'tabular-nums'       => 'tabular-nums',
			'diagonal-fractions' => 'diagonal-fractions',
			'stacked-fractions'  => 'stacked-fractions',
		);
		foreach ( $font_variant_numeric as $name => $value ) {
			$classes[ $name ] = array(
				'css'         => "font-variant-numeric: {$value};",
				'title'       => ucwords( str_replace( '-', ' ', $name ) ),
				'description' => "Sets font-variant-numeric to {$value}.",
				'category'    => 'typography',
				'tags'        => array( 'font-variant-numeric', 'numbers' ),
			);
		}

		// --- Backdrop filters ---
		$backdrop_brightness = array( 0, 50, 75, 90, 95, 100, 105, 110, 125, 150, 200 );
		foreach ( $backdrop_brightness as $v ) {
			$classes[ "backdrop-brightness-{$v}" ] = array(
				'css'         => "backdrop-filter: brightness({$v}%); -webkit-backdrop-filter: brightness({$v}%);",
				'title'       => "Backdrop brightness {$v}",
				'description' => "Applies brightness({$v}%) to the element's backdrop.",
				'category'    => 'filters',
				'tags'        => array( 'backdrop', 'brightness' ),
			);
		}

		$backdrop_contrast = array( 0, 50, 75, 100, 125, 150, 200 );
		foreach ( $backdrop_contrast as $v ) {
			$classes[ "backdrop-contrast-{$v}" ] = array(
				'css'         => "backdrop-filter: contrast({$v}%); -webkit-backdrop-filter: contrast({$v}%);",
				'title'       => "Backdrop contrast {$v}",
				'description' => "Applies contrast({$v}%) to the element's backdrop.",
				'category'    => 'filters',
				'tags'        => array( 'backdrop', 'contrast' ),
			);
		}

		$classes['backdrop-grayscale-0'] = array(
			'css'         => 'backdrop-filter: grayscale(0); -webkit-backdrop-filter: grayscale(0);',
			'title'       => 'Backdrop grayscale 0',
			'description' => 'Removes grayscale from the backdrop.',
			'category'    => 'filters',
			'tags'        => array( 'backdrop', 'grayscale' ),
		);
		$classes['backdrop-grayscale']   = array(
			'css'         => 'backdrop-filter: grayscale(100%); -webkit-backdrop-filter: grayscale(100%);',
			'title'       => 'Backdrop grayscale',
			'description' => 'Applies full grayscale to the backdrop.',
			'category'    => 'filters',
			'tags'        => array( 'backdrop', 'grayscale' ),
		);

		$backdrop_hue_rotate = array( 0, 15, 30, 60, 90, 180 );
		foreach ( $backdrop_hue_rotate as $v ) {
			$classes[ "backdrop-hue-rotate-{$v}" ] = array(
				'css'         => "backdrop-filter: hue-rotate({$v}deg); -webkit-backdrop-filter: hue-rotate({$v}deg);",
				'title'       => "Backdrop hue rotate {$v}",
				'description' => "Rotates the backdrop hue by {$v}deg.",
				'category'    => 'filters',
				'tags'        => array( 'backdrop', 'hue-rotate' ),
			);
			if ( 0 === $v ) {
				continue;
			}
			$classes[ "-backdrop-hue-rotate-{$v}" ] = array(
				'css'         => "backdrop-filter: hue-rotate(-{$v}deg); -webkit-backdrop-filter: hue-rotate(-{$v}deg);",
				'title'       => "Backdrop hue rotate -{$v}",
				'description' => "Rotates the backdrop hue by -{$v}deg.",
				'category'    => 'filters',
				'tags'        => array( 'backdrop', 'hue-rotate', 'negative' ),
			);
		}

		$classes['backdrop-invert-0'] = array(
			'css'         => 'backdrop-filter: invert(0); -webkit-backdrop-filter: invert(0);',
			'title'       => 'Backdrop invert 0',
			'description' => 'Removes inversion from the backdrop.',
			'category'    => 'filters',
			'tags'        => array( 'backdrop', 'invert' ),
		);
		$classes['backdrop-invert']   = array(
			'css'         => 'backdrop-filter: invert(100%); -webkit-backdrop-filter: invert(100%);',
			'title'       => 'Backdrop invert',
			'description' => 'Fully inverts the backdrop colors.',
			'category'    => 'filters',
			'tags'        => array( 'backdrop', 'invert' ),
		);

		$backdrop_opacity = array( 0, 5, 10, 20, 25, 30, 40, 50, 60, 70, 75, 80, 90, 95, 100 );
		foreach ( $backdrop_opacity as $v ) {
			$classes[ "backdrop-opacity-{$v}" ] = array(
				'css'         => "backdrop-filter: opacity({$v}%); -webkit-backdrop-filter: opacity({$v}%);",
				'title'       => "Backdrop opacity {$v}",
				'description' => "Applies opacity({$v}%) to the backdrop filter.",
				'category'    => 'filters',
				'tags'        => array( 'backdrop', 'opacity' ),
			);
		}

		$backdrop_saturate = array( 0, 50, 100, 150, 200 );
		foreach ( $backdrop_saturate as $v ) {
			$classes[ "backdrop-saturate-{$v}" ] = array(
				'css'         => "backdrop-filter: saturate({$v}%); -webkit-backdrop-filter: saturate({$v}%);",
				'title'       => "Backdrop saturate {$v}",
				'description' => "Applies saturate({$v}%) to the backdrop.",
				'category'    => 'filters',
				'tags'        => array( 'backdrop', 'saturate' ),
			);
		}

		$classes['backdrop-sepia-0'] = array(
			'css'         => 'backdrop-filter: sepia(0); -webkit-backdrop-filter: sepia(0);',
			'title'       => 'Backdrop sepia 0',
			'description' => 'Removes sepia from the backdrop.',
			'category'    => 'filters',
			'tags'        => array( 'backdrop', 'sepia' ),
		);
		$classes['backdrop-sepia']   = array(
			'css'         => 'backdrop-filter: sepia(100%); -webkit-backdrop-filter: sepia(100%);',
			'title'       => 'Backdrop sepia',
			'description' => 'Fully applies sepia to the backdrop.',
			'category'    => 'filters',
			'tags'        => array( 'backdrop', 'sepia' ),
		);

		// --- Columns ---
		for ( $i = 1; $i <= 12; $i++ ) {
			$classes[ "columns-{$i}" ] = array(
				'css'         => "columns: {$i};",
				'title'       => "Columns {$i}",
				'description' => "Splits content into {$i} columns.",
				'category'    => 'layout',
				'tags'        => array( 'columns' ),
			);
		}
		$classes['columns-auto'] = array(
			'css'         => 'columns: auto;',
			'title'       => 'Columns auto',
			'description' => 'Uses automatic column count.',
			'category'    => 'layout',
			'tags'        => array( 'columns' ),
		);
		$columns_sizes           = array(
			'3xs' => '16rem',
			'2xs' => '18rem',
			'xs'  => '20rem',
			'sm'  => '24rem',
			'md'  => '28rem',
			'lg'  => '32rem',
			'xl'  => '36rem',
			'2xl' => '42rem',
			'3xl' => '48rem',
			'4xl' => '56rem',
			'5xl' => '64rem',
			'6xl' => '72rem',
			'7xl' => '80rem',
		);
		foreach ( $columns_sizes as $key => $value ) {
			$classes[ "columns-{$key}" ] = array(
				'css'         => "columns: {$value};",
				'title'       => "Columns {$key}",
				'description' => "Uses {$value} as the ideal column width.",
				'category'    => 'layout',
				'tags'        => array( 'columns' ),
			);
		}

		$break_values = array( 'auto', 'avoid', 'all', 'avoid-page', 'page', 'left', 'right', 'column' );
		foreach ( $break_values as $value ) {
			$classes[ "break-before-{$value}" ] = array(
				'css'         => "break-before: {$value};",
				'title'       => "Break before {$value}",
				'description' => "Sets break-before to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'break', 'break-before' ),
			);
			$classes[ "break-after-{$value}" ]  = array(
				'css'         => "break-after: {$value};",
				'title'       => "Break after {$value}",
				'description' => "Sets break-after to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'break', 'break-after' ),
			);
		}

		$break_inside_values = array( 'auto', 'avoid', 'avoid-page', 'avoid-column' );
		foreach ( $break_inside_values as $value ) {
			$classes[ "break-inside-{$value}" ] = array(
				'css'         => "break-inside: {$value};",
				'title'       => "Break inside {$value}",
				'description' => "Sets break-inside to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'break', 'break-inside' ),
			);
		}

		// --- Grid completeness ---
		$grid_flow = array(
			'row'       => 'row',
			'col'       => 'column',
			'row-dense' => 'row dense',
			'col-dense' => 'column dense',
			'dense'     => 'dense',
		);
		foreach ( $grid_flow as $key => $value ) {
			$classes[ "grid-flow-{$key}" ] = array(
				'css'         => "grid-auto-flow: {$value};",
				'title'       => "Grid flow {$key}",
				'description' => "Sets grid-auto-flow to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'grid', 'grid-auto-flow' ),
			);
		}

		$auto_axis = array(
			'auto' => 'auto',
			'min'  => 'min-content',
			'max'  => 'max-content',
			'fr'   => 'minmax(0, 1fr)',
		);
		foreach ( $auto_axis as $key => $value ) {
			$classes[ "auto-cols-{$key}" ] = array(
				'css'         => "grid-auto-columns: {$value};",
				'title'       => "Auto cols {$key}",
				'description' => "Sets grid-auto-columns to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'grid', 'grid-auto-columns' ),
			);
			$classes[ "auto-rows-{$key}" ] = array(
				'css'         => "grid-auto-rows: {$value};",
				'title'       => "Auto rows {$key}",
				'description' => "Sets grid-auto-rows to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'grid', 'grid-auto-rows' ),
			);
		}

		// justify-self-auto (the other justify-self-* are in get_layout_classes()).
		$classes['justify-self-auto'] = array(
			'css'         => 'justify-self: auto;',
			'title'       => 'Justify self auto',
			'description' => 'Uses default justify-self behavior.',
			'category'    => 'layout',
			'tags'        => array( 'grid', 'justify-self' ),
		);

		$place_items = array( 'start', 'end', 'center', 'stretch', 'baseline' );
		foreach ( $place_items as $value ) {
			$classes[ "place-items-{$value}" ] = array(
				'css'         => "place-items: {$value};",
				'title'       => "Place items {$value}",
				'description' => "Sets place-items to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'grid', 'place-items' ),
			);
		}

		$place_content     = array( 'start', 'end', 'center', 'between', 'around', 'evenly', 'stretch', 'baseline' );
		$place_content_map = array(
			'start'    => 'start',
			'end'      => 'end',
			'center'   => 'center',
			'between'  => 'space-between',
			'around'   => 'space-around',
			'evenly'   => 'space-evenly',
			'stretch'  => 'stretch',
			'baseline' => 'baseline',
		);
		foreach ( $place_content as $key ) {
			$value                             = $place_content_map[ $key ];
			$classes[ "place-content-{$key}" ] = array(
				'css'         => "place-content: {$value};",
				'title'       => "Place content {$key}",
				'description' => "Sets place-content to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'grid', 'place-content' ),
			);
		}

		$place_self = array( 'auto', 'start', 'end', 'center', 'stretch' );
		foreach ( $place_self as $value ) {
			$classes[ "place-self-{$value}" ] = array(
				'css'         => "place-self: {$value};",
				'title'       => "Place self {$value}",
				'description' => "Sets place-self to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'grid', 'place-self' ),
			);
		}

		// --- Divide style ---
		$divide_styles = array( 'solid', 'dashed', 'dotted', 'double', 'none' );
		foreach ( $divide_styles as $style ) {
			$classes[ "divide-{$style}" ] = array(
				'css'         => "& > :not([hidden]) ~ :not([hidden]) { border-style: {$style}; }",
				'title'       => "Divide style {$style}",
				'description' => "Sets border-style between siblings to {$style}.",
				'category'    => 'border',
				'tags'        => array( 'divide', 'border-style' ),
			);
		}

		return $classes;
	}

	/**
	 * Tailwind v4 parity utility presets.
	 *
	 * Adds v4-only utility families on top of the v3 parity layer:
	 *   - Logical property utilities (`ps-*`, `pe-*`, `ms-*`, `me-*`, `start-*`,
	 *     `end-*`, `border-s*`, `border-e*`, `rounded-s/e/ss/se/es/ee-*`).
	 *   - Dynamic viewport units on sizing utilities (`h-dvh`, `size-svh`, …).
	 *   - `text-shadow-*` presets (xs → 2xl).
	 *   - `size-*` shorthand (spacing scale + fractions + keywords).
	 *   - `inset-ring-*` presets (inset box-shadow form).
	 *   - `field-sizing-content` / `field-sizing-fixed`.
	 *   - 3D transform presets that DON'T compose (perspective, perspective-origin,
	 *     transform-style, backface-visibility). Axis rotations/translations/scales
	 *     compose via `resolve_tw_transform()` in the JIT compiler instead.
	 *   - New gradient direction aliases `bg-linear-to-*` + angle presets + radial/conic.
	 *   - `scheme-*` (color-scheme) utilities.
	 *   - `transition-discrete` / `transition-normal` / `interpolate-size-*`.
	 *   - `@container`, `@container-normal`, `container-type-*` (gated by the
	 *     `spectra_blocks_gbs_container_queries` filter).
	 *
	 * Container-name-{slug} is intentionally deferred — it needs per-site slug
	 * validation. Arbitrary bracket form is planned via `container-name-[name]:`
	 * on the JIT side instead of the registry.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_parity_v4_classes(): array {
		$classes = array();

		// --- Logical property spacing scale (reused for ps/pe/ms/me/start/end) ---
		$logical_spacing = array(
			'0'   => '0',
			'px'  => '1px',
			'0.5' => '0.125rem',
			'1'   => '0.25rem',
			'1.5' => '0.375rem',
			'2'   => '0.5rem',
			'2.5' => '0.625rem',
			'3'   => '0.75rem',
			'3.5' => '0.875rem',
			'4'   => '1rem',
			'5'   => '1.25rem',
			'6'   => '1.5rem',
			'7'   => '1.75rem',
			'8'   => '2rem',
			'9'   => '2.25rem',
			'10'  => '2.5rem',
			'11'  => '2.75rem',
			'12'  => '3rem',
			'14'  => '3.5rem',
			'16'  => '4rem',
			'20'  => '5rem',
			'24'  => '6rem',
			'28'  => '7rem',
			'32'  => '8rem',
			'36'  => '9rem',
			'40'  => '10rem',
			'44'  => '11rem',
			'48'  => '12rem',
			'52'  => '13rem',
			'56'  => '14rem',
			'60'  => '15rem',
			'64'  => '16rem',
			'72'  => '18rem',
			'80'  => '20rem',
			'96'  => '24rem',
		);

		$logical_map = array(
			'ps' => 'padding-inline-start',
			'pe' => 'padding-inline-end',
			'ms' => 'margin-inline-start',
			'me' => 'margin-inline-end',
		);
		foreach ( $logical_map as $prefix => $property ) {
			foreach ( $logical_spacing as $key => $value ) {
				$classes[ "{$prefix}-{$key}" ] = array(
					'css'         => "{$property}: {$value};",
					'title'       => "{$prefix}-{$key}",
					'description' => "Sets {$property} to {$value}.",
					'category'    => 'spacing',
					'tags'        => array( 'logical', 'spacing', $prefix ),
				);
			}
		}

		// --- Logical inset start/end (inset-inline-*) ---
		$inset_logical_map = array(
			'start' => 'inset-inline-start',
			'end'   => 'inset-inline-end',
		);
		foreach ( $inset_logical_map as $prefix => $property ) {
			foreach ( $logical_spacing as $key => $value ) {
				$classes[ "{$prefix}-{$key}" ] = array(
					'css'         => "{$property}: {$value};",
					'title'       => "{$prefix}-{$key}",
					'description' => "Sets {$property} to {$value}.",
					'category'    => 'position',
					'tags'        => array( 'logical', 'position', $prefix ),
				);
			}
			$classes[ "{$prefix}-auto" ] = array(
				'css'         => "{$property}: auto;",
				'title'       => "{$prefix}-auto",
				'description' => "Sets {$property} to auto.",
				'category'    => 'position',
				'tags'        => array( 'logical', 'position', $prefix ),
			);
			$classes[ "{$prefix}-full" ] = array(
				'css'         => "{$property}: 100%;",
				'title'       => "{$prefix}-full",
				'description' => "Sets {$property} to 100%.",
				'category'    => 'position',
				'tags'        => array( 'logical', 'position', $prefix ),
			);
		}

		// --- Logical border widths ---
		$logical_border_widths = array(
			'0' => '0',
			'2' => '2px',
			'4' => '4px',
			'8' => '8px',
		);
		$logical_border_map    = array(
			's' => 'border-inline-start-width',
			'e' => 'border-inline-end-width',
		);
		foreach ( $logical_border_map as $side => $property ) {
			foreach ( $logical_border_widths as $key => $value ) {
				$classes[ "border-{$side}-{$key}" ] = array(
					'css'         => "{$property}: {$value};",
					'title'       => "border-{$side}-{$key}",
					'description' => "Sets {$property} to {$value}.",
					'category'    => 'border',
					'tags'        => array( 'logical', 'border', "border-{$side}" ),
				);
			}
			// Bare form → 1px.
			$classes[ "border-{$side}" ] = array(
				'css'         => "{$property}: 1px;",
				'title'       => "border-{$side}",
				'description' => "Sets {$property} to 1px.",
				'category'    => 'border',
				'tags'        => array( 'logical', 'border', "border-{$side}" ),
			);
		}

		// --- Logical border radius ---
		// Aligned with Tailwind v4 (see all-corners radius_sizes block above).
		// @since 1.0.0
		$radius_scale         = array(
			'none'    => '0',
			'xs'      => '0.125rem',
			'sm'      => '0.25rem',
			'DEFAULT' => '0.25rem',
			'md'      => '0.375rem',
			'lg'      => '0.5rem',
			'xl'      => '0.75rem',
			'2xl'     => '1rem',
			'3xl'     => '1.5rem',
			'4xl'     => '2rem',
			'full'    => '9999px',
		);
		$logical_radius_pairs = array(
			's'  => array( 'border-start-start-radius', 'border-end-start-radius' ),
			'e'  => array( 'border-start-end-radius', 'border-end-end-radius' ),
			'ss' => array( 'border-start-start-radius' ),
			'se' => array( 'border-start-end-radius' ),
			'es' => array( 'border-end-start-radius' ),
			'ee' => array( 'border-end-end-radius' ),
		);
		foreach ( $logical_radius_pairs as $suffix => $properties ) {
			foreach ( $radius_scale as $key => $value ) {
				$class_name = 'DEFAULT' === $key ? "rounded-{$suffix}" : "rounded-{$suffix}-{$key}";
				$decls      = '';
				foreach ( $properties as $property ) {
					$decls .= "{$property}: {$value}; ";
				}
				$classes[ $class_name ] = array(
					'css'         => rtrim( $decls ),
					'title'       => $class_name,
					'description' => "Logical corner radius: {$class_name}.",
					'category'    => 'border',
					'tags'        => array( 'logical', 'rounded', 'border-radius' ),
				);
			}
		}

		// --- Dynamic viewport units on sizing utilities ---
		$dv_size_map = array(
			'h'     => 'height',
			'min-h' => 'min-height',
			'max-h' => 'max-height',
			'w'     => 'width',
			'min-w' => 'min-width',
			'max-w' => 'max-width',
		);
		$dv_units    = array( 'dvh', 'svh', 'lvh', 'dvw', 'svw', 'lvw' );
		foreach ( $dv_size_map as $prefix => $property ) {
			foreach ( $dv_units as $unit ) {
				$classes[ "{$prefix}-{$unit}" ] = array(
					'css'         => "{$property}: 100{$unit};",
					'title'       => "{$prefix}-{$unit}",
					'description' => "Sets {$property} to 100{$unit} (dynamic viewport unit).",
					'category'    => 'sizing',
					'tags'        => array( 'sizing', 'viewport', $unit ),
				);
			}
		}
		// `size-dvh`/svh/lvh/dvw/svw/lvw — both width + height to 100<unit>.
		foreach ( $dv_units as $unit ) {
			$classes[ "size-{$unit}" ] = array(
				'css'         => "width: 100{$unit}; height: 100{$unit};",
				'title'       => "size-{$unit}",
				'description' => "Sets width and height to 100{$unit}.",
				'category'    => 'sizing',
				'tags'        => array( 'sizing', 'size', 'viewport', $unit ),
			);
		}

		// --- Text-shadow presets ---
		$text_shadows = array(
			'2xs' => '0 1px 0 rgb(0 0 0 / 0.15)',
			'xs'  => '0 1px 1px rgb(0 0 0 / 0.2)',
			'sm'  => '0 1px 2px rgb(0 0 0 / 0.25)',
			'md'  => '0 2px 4px rgb(0 0 0 / 0.25)',
			'lg'  => '0 4px 6px rgb(0 0 0 / 0.25)',
			'xl'  => '0 4px 12px rgb(0 0 0 / 0.3)',
			'2xl' => '0 6px 16px rgb(0 0 0 / 0.35)',
		);
		foreach ( $text_shadows as $size => $value ) {
			$classes[ "text-shadow-{$size}" ] = array(
				'css'         => "text-shadow: {$value};",
				'title'       => "text-shadow-{$size}",
				'description' => "Applies a text-shadow at the {$size} preset.",
				'category'    => 'text-style',
				'tags'        => array( 'text-shadow', 'typography' ),
			);
		}
		$classes['text-shadow-none'] = array(
			'css'         => 'text-shadow: none;',
			'title'       => 'text-shadow-none',
			'description' => 'Removes any applied text-shadow.',
			'category'    => 'text-style',
			'tags'        => array( 'text-shadow', 'typography' ),
		);

		// --- size-* shorthand (spacing scale + keywords + fractions) ---
		foreach ( $logical_spacing as $key => $value ) {
			$classes[ "size-{$key}" ] = array(
				'css'         => "width: {$value}; height: {$value};",
				'title'       => "size-{$key}",
				'description' => "Sets width and height to {$value}.",
				'category'    => 'sizing',
				'tags'        => array( 'sizing', 'size' ),
			);
		}
		$size_keywords = array(
			'auto' => array(
				'width'  => 'auto',
				'height' => 'auto',
			),
			// `full` (width/height:100%) is intentionally omitted: `size-full`
			// is a WordPress core reserved class on every full-size core/image
			// (`figure.wp-block-image.size-full`). Emitting it as a utility
			// forces the figure to fill+squish instead of shrink-wrapping the
			// image, breaking is-resized/aligned/negative-margin layouts. Use
			// `w-full h-full` or arbitrary `size-[100%]` when full sizing is
			// genuinely wanted.
			'min'  => array(
				'width'  => 'min-content',
				'height' => 'min-content',
			),
			'max'  => array(
				'width'  => 'max-content',
				'height' => 'max-content',
			),
			'fit'  => array(
				'width'  => 'fit-content',
				'height' => 'fit-content',
			),
		);
		foreach ( $size_keywords as $keyword => $map ) {
			$classes[ "size-{$keyword}" ] = array(
				'css'         => "width: {$map['width']}; height: {$map['height']};",
				'title'       => "size-{$keyword}",
				'description' => "Sets both axes to {$keyword}.",
				'category'    => 'sizing',
				'tags'        => array( 'sizing', 'size' ),
			);
		}
		$size_fractions = array(
			'1/2' => '50%',
			'1/3' => '33.333333%',
			'2/3' => '66.666667%',
			'1/4' => '25%',
			'3/4' => '75%',
			'1/5' => '20%',
			'2/5' => '40%',
			'3/5' => '60%',
			'4/5' => '80%',
		);
		foreach ( $size_fractions as $fraction => $pct ) {
			$classes[ "size-{$fraction}" ] = array(
				'css'         => "width: {$pct}; height: {$pct};",
				'title'       => "size-{$fraction}",
				'description' => "Sets width and height to {$pct}.",
				'category'    => 'sizing',
				'tags'        => array( 'sizing', 'size', 'fraction' ),
			);
		}

		// --- inset-ring-* (simple inset shadow form; color composition deferred) ---
		$inset_ring_widths = array(
			'0' => '0',
			'1' => '1px',
			'2' => '2px',
			'4' => '4px',
			'8' => '8px',
		);
		foreach ( $inset_ring_widths as $key => $width ) {
			$value                          = '0' === $width ? 'none' : "inset 0 0 0 {$width} currentColor";
			$classes[ "inset-ring-{$key}" ] = array(
				'css'         => "box-shadow: {$value};",
				'title'       => "inset-ring-{$key}",
				'description' => "Emits an inset box-shadow of {$width} in currentColor.",
				'category'    => 'shadow',
				'tags'        => array( 'inset-ring', 'ring', 'shadow' ),
			);
		}
		$classes['inset-ring'] = array(
			'css'         => 'box-shadow: inset 0 0 0 1px currentColor;',
			'title'       => 'inset-ring',
			'description' => 'Emits a 1px inset ring in currentColor.',
			'category'    => 'shadow',
			'tags'        => array( 'inset-ring', 'ring', 'shadow' ),
		);

		// --- field-sizing-* ---
		$classes['field-sizing-content'] = array(
			'css'         => 'field-sizing: content;',
			'title'       => 'field-sizing-content',
			'description' => 'Form controls size themselves to their content.',
			'category'    => 'layout',
			'tags'        => array( 'field-sizing', 'form' ),
		);
		$classes['field-sizing-fixed']   = array(
			'css'         => 'field-sizing: fixed;',
			'title'       => 'field-sizing-fixed',
			'description' => 'Form controls use their default fixed sizing.',
			'category'    => 'layout',
			'tags'        => array( 'field-sizing', 'form' ),
		);

		// --- 3D transforms — non-composing presets ---
		// Axis rotate/scale/translate are handled in `JitCompiler::resolve_tw_transform()`
		// so they compose with 2D transforms through the `--tw-*` var slots; these
		// non-composing presets (perspective, perspective-origin, transform-style,
		// backface-visibility) ship as registry entries.
		$perspective_presets = array(
			'none' => 'none',
			'250'  => '250px',
			'500'  => '500px',
			'750'  => '750px',
			'1000' => '1000px',
			'1500' => '1500px',
		);
		foreach ( $perspective_presets as $key => $value ) {
			$classes[ "perspective-{$key}" ] = array(
				'css'         => "perspective: {$value};",
				'title'       => "perspective-{$key}",
				'description' => "Sets perspective to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'perspective', '3d', 'transform' ),
			);
		}

		$perspective_origins = array(
			'center'       => 'center',
			'top'          => 'top',
			'top-right'    => 'top right',
			'right'        => 'right',
			'bottom-right' => 'bottom right',
			'bottom'       => 'bottom',
			'bottom-left'  => 'bottom left',
			'left'         => 'left',
			'top-left'     => 'top left',
		);
		foreach ( $perspective_origins as $key => $value ) {
			$classes[ "perspective-origin-{$key}" ] = array(
				'css'         => "perspective-origin: {$value};",
				'title'       => "perspective-origin-{$key}",
				'description' => "Sets perspective-origin to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'perspective', 'perspective-origin', '3d' ),
			);
		}

		$classes['transform-3d']     = array(
			'css'         => 'transform-style: preserve-3d;',
			'title'       => 'transform-3d',
			'description' => 'Preserves 3D transforms on descendants.',
			'category'    => 'layout',
			'tags'        => array( 'transform-style', '3d' ),
		);
		$classes['transform-flat']   = array(
			'css'         => 'transform-style: flat;',
			'title'       => 'transform-flat',
			'description' => 'Flattens descendants onto the 2D plane.',
			'category'    => 'layout',
			'tags'        => array( 'transform-style', '3d' ),
		);
		$classes['backface-visible'] = array(
			'css'         => 'backface-visibility: visible;',
			'title'       => 'backface-visible',
			'description' => 'Shows the back face of a 3D-transformed element.',
			'category'    => 'layout',
			'tags'        => array( 'backface-visibility', '3d' ),
		);
		$classes['backface-hidden']  = array(
			'css'         => 'backface-visibility: hidden;',
			'title'       => 'backface-hidden',
			'description' => 'Hides the back face of a 3D-transformed element.',
			'category'    => 'layout',
			'tags'        => array( 'backface-visibility', '3d' ),
		);

		// --- Gradient v4 aliases + angles + radial/conic shorthand ---
		// `bg-linear-to-*` mirrors the existing `bg-gradient-to-*` emission.
		$linear_directions = array(
			't'  => 'to top',
			'tr' => 'to top right',
			'r'  => 'to right',
			'br' => 'to bottom right',
			'b'  => 'to bottom',
			'bl' => 'to bottom left',
			'l'  => 'to left',
			'tl' => 'to top left',
		);
		foreach ( $linear_directions as $key => $direction ) {
			$classes[ "bg-linear-to-{$key}" ] = array(
				'css'         => "background-image: linear-gradient({$direction}, var(--tw-gradient-stops));",
				'title'       => "bg-linear-to-{$key}",
				'description' => "Linear gradient {$direction}.",
				'category'    => 'gradient',
				'tags'        => array( 'gradient', 'background', 'linear' ),
			);
		}
		$linear_angles = array( 0, 45, 90, 135, 180, 225, 270, 315 );
		foreach ( $linear_angles as $deg ) {
			$classes[ "bg-linear-{$deg}" ] = array(
				'css'         => "background-image: linear-gradient({$deg}deg, var(--tw-gradient-stops));",
				'title'       => "bg-linear-{$deg}",
				'description' => "Linear gradient at {$deg}deg.",
				'category'    => 'gradient',
				'tags'        => array( 'gradient', 'background', 'linear' ),
			);
		}
		$classes['bg-radial'] = array(
			'css'         => 'background-image: radial-gradient(var(--tw-gradient-stops));',
			'title'       => 'bg-radial',
			'description' => 'Radial gradient using the --tw-gradient-stops scaffold.',
			'category'    => 'gradient',
			'tags'        => array( 'gradient', 'background', 'radial' ),
		);
		$classes['bg-conic']  = array(
			'css'         => 'background-image: conic-gradient(var(--tw-gradient-stops));',
			'title'       => 'bg-conic',
			'description' => 'Conic gradient using the --tw-gradient-stops scaffold.',
			'category'    => 'gradient',
			'tags'        => array( 'gradient', 'background', 'conic' ),
		);

		// --- color-scheme utilities ---
		$color_schemes = array(
			'normal'     => 'normal',
			'dark'       => 'dark',
			'light'      => 'light',
			'light-dark' => 'light dark',
			'only-dark'  => 'only dark',
			'only-light' => 'only light',
		);
		foreach ( $color_schemes as $key => $value ) {
			$classes[ "scheme-{$key}" ] = array(
				'css'         => "color-scheme: {$value};",
				'title'       => "scheme-{$key}",
				'description' => "Sets color-scheme to {$value}.",
				'category'    => 'layout',
				'tags'        => array( 'color-scheme', 'scheme' ),
			);
		}

		// --- transition-behavior + interpolate-size ---
		$classes['transition-discrete']             = array(
			'css'         => 'transition-behavior: allow-discrete;',
			'title'       => 'transition-discrete',
			'description' => 'Allows transitioning discrete properties (display, etc.).',
			'category'    => 'layout',
			'tags'        => array( 'transition-behavior', 'transition' ),
		);
		$classes['transition-normal']               = array(
			'css'         => 'transition-behavior: normal;',
			'title'       => 'transition-normal',
			'description' => 'Default transition behavior.',
			'category'    => 'layout',
			'tags'        => array( 'transition-behavior', 'transition' ),
		);
		$classes['interpolate-size-allow-keywords'] = array(
			'css'         => 'interpolate-size: allow-keywords;',
			'title'       => 'interpolate-size-allow-keywords',
			'description' => 'Allows interpolating to intrinsic sizing keywords.',
			'category'    => 'layout',
			'tags'        => array( 'interpolate-size' ),
		);
		$classes['interpolate-size-numeric-only']   = array(
			'css'         => 'interpolate-size: numeric-only;',
			'title'       => 'interpolate-size-numeric-only',
			'description' => 'Restricts size interpolation to numeric values.',
			'category'    => 'layout',
			'tags'        => array( 'interpolate-size' ),
		);

		// --- Container-query utilities (gated by filter) ---
		$container_queries_enabled = (bool) apply_filters( 'spectra_blocks_gbs_container_queries', true );
		if ( $container_queries_enabled ) {
			$classes['@container']                 = array(
				'css'         => 'container-type: inline-size;',
				'title'       => '@container',
				'description' => 'Establishes an inline-size container query context.',
				'category'    => 'layout',
				'tags'        => array( 'container-query', 'container' ),
			);
			$classes['@container-normal']          = array(
				'css'         => 'container-type: normal;',
				'title'       => '@container-normal',
				'description' => 'Clears container-query context.',
				'category'    => 'layout',
				'tags'        => array( 'container-query', 'container' ),
			);
			$classes['container-type-size']        = array(
				'css'         => 'container-type: size;',
				'title'       => 'container-type-size',
				'description' => 'Establishes a size-based container query context.',
				'category'    => 'layout',
				'tags'        => array( 'container-query', 'container' ),
			);
			$classes['container-type-inline-size'] = array(
				'css'         => 'container-type: inline-size;',
				'title'       => 'container-type-inline-size',
				'description' => 'Establishes an inline-size container query context.',
				'category'    => 'layout',
				'tags'        => array( 'container-query', 'container' ),
			);
			$classes['container-type-normal']      = array(
				'css'         => 'container-type: normal;',
				'title'       => 'container-type-normal',
				'description' => 'Clears container-query context.',
				'category'    => 'layout',
				'tags'        => array( 'container-query', 'container' ),
			);
		}

		return $classes;
	}

	/**
	 * Component classes — curated `sg-*` pattern tokens.
	 *
	 * These are styled in `assets/css/component-tokens.css`, NOT generated by
	 * the JIT. Registering them here makes them first-class citizens in the
	 * class registry so validators, editor autocomplete, the cheatsheet UI,
	 * and any GS-aware tooling see them alongside utility classes. The `css`
	 * field is intentionally empty — `get_flat_classes()` filters empties so
	 * the JIT doesn't emit duplicate / stub rules for them.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_component_classes(): array {
		return array(
			'sg-section'        => array(
				'css'         => '',
				'title'       => 'Section',
				'description' => 'Full-bleed section wrapper with vertical rhythm tokens (top/bottom padding).',
				'category'    => 'component',
				'tags'        => array( 'component', 'section', 'layout' ),
			),
			'sg-card'           => array(
				'css'         => '',
				'title'       => 'Card',
				'description' => 'Surface card with uniform padding, radius, and subtle shadow tokens.',
				'category'    => 'component',
				'tags'        => array( 'component', 'card', 'surface' ),
			),
			'sg-badge'          => array(
				'css'         => '',
				'title'       => 'Badge',
				'description' => 'Inline badge / tag with padding + radius tokens.',
				'category'    => 'component',
				'tags'        => array( 'component', 'badge', 'tag' ),
			),
			'sg-icon-contained' => array(
				'css'         => '',
				'title'       => 'Icon (contained)',
				'description' => 'Icon with a tinted container background and consistent sizing.',
				'category'    => 'component',
				'tags'        => array( 'component', 'icon' ),
			),
			'sg-overlay-light'  => array(
				'css'         => '',
				'title'       => 'Overlay (light)',
				'description' => 'Light scrim overlay for contrast on bright imagery.',
				'category'    => 'component',
				'tags'        => array( 'component', 'overlay', 'scrim' ),
			),
			'sg-overlay-dark'   => array(
				'css'         => '',
				'title'       => 'Overlay (dark)',
				'description' => 'Dark scrim overlay for contrast on photographic backdrops.',
				'category'    => 'component',
				'tags'        => array( 'component', 'overlay', 'scrim' ),
			),
			'sg-text-gradient'  => array(
				'css'         => '',
				'title'       => 'Text gradient',
				'description' => 'Gradient fill applied to inline text.',
				'category'    => 'component',
				'tags'        => array( 'component', 'text', 'gradient' ),
			),
		);
	}

	// ─────────────────────────────────────────────────────────────
	// MERGE + CACHE
	// ─────────────────────────────────────────────────────────────

	/**
	 * Returns all registered classes merged from static and dynamic generators.
	 *
	 * Results are cached in memory and in the WP object cache.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	public static function get_all_classes(): array {
		if ( null !== self::$all_classes_cache ) {
			return self::$all_classes_cache;
		}

		// Static classes — always the same, opcached via PHP.
		$static = array_merge(
			self::get_display_classes(),
			self::get_border_classes(),
			self::get_outline_classes(),
			self::get_sizing_classes(),
			self::get_layout_classes(),
			self::get_filter_classes(),
			self::get_line_height_classes(),
			self::get_font_weight_classes(),
			self::get_font_style_classes(),
			self::get_text_style_classes(),
			self::get_shadow_classes(),
			self::get_opacity_classes(),
			self::get_overflow_classes(),
			self::get_position_classes(),
			self::get_visibility_classes(),
			self::get_cursor_classes(),
			self::get_list_classes(),
			self::get_extended_classes(),
			self::get_parity_v3_classes(),
			self::get_parity_v4_classes(),
			self::get_component_classes()
		);

		// Dynamic classes — depend on SG config, WP object-cached.
		$dynamic = self::get_dynamic_classes();

		self::$all_classes_cache = array_merge( $static, $dynamic );

		return self::$all_classes_cache;
	}

	/**
	 * Returns a flat class-name => CSS-string map for stylesheet generation.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string>
	 */
	public static function get_flat_classes(): array {
		$all  = self::get_all_classes();
		$flat = array();

		foreach ( $all as $name => $data ) {
			$css = $data['css'] ?? '';
			// Component classes carry empty CSS — their visual rules live in
			// `assets/css/component-tokens.css`. Skip them here so the JIT
			// stylesheet doesn't emit empty or duplicate selectors.
			if ( '' === $css ) {
				continue;
			}
			$flat[ $name ] = $css;
		}

		return $flat;
	}

	/**
	 * Returns a flat indexed array for the admin CheatSheet UI.
	 *
	 * Each entry includes name, css, title, description, category, and tags.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{name: string, css: string, title: string, description: string, category: string, tags: array}>
	 */
	public static function get_cheatsheet_data(): array {
		$all  = self::get_all_classes();
		$list = array();

		foreach ( $all as $name => $data ) {
			$list[] = array(
				'name'        => $name,
				'css'         => $data['css'],
				'title'       => $data['title'] ?? '',
				'description' => $data['description'] ?? '',
				'category'    => $data['category'] ?? '',
				'tags'        => $data['tags'] ?? array(),
			);
		}

		return $list;
	}

	/**
	 * Returns grouped options for the editor class dropdown (React Select format).
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{label: string, options: array}>
	 */
	public static function get_grouped_options_for_editor(): array {
		$all = self::get_all_classes();

		// Group definitions: group key => label.
		$group_order = array(
			'bg-color'      => 'Background: Color',
			'text-color'    => 'Color: Text',
			'border-color'  => 'Border: Color',
			'overlay-color' => 'Overlay: Color',
			'padding'       => 'Spacing: Padding',
			'margin'        => 'Spacing: Margin',
			'gap'           => 'Spacing: Gap',
			'font-size'     => 'Typography: Font Size',
			'font-weight'   => 'Typography: Font Weight',
			'line-height'   => 'Typography: Line Height',
			'text-align'    => 'Typography: Text Align',
			'tracking'      => 'Typography: Letter Spacing',
			'text-style'    => 'Typography: Text Style',
			'display'       => 'Display',
			'layout'        => 'Layout',
			'sizing'        => 'Sizing',
			'border'        => 'Border',
			'shadow'        => 'Shadow',
			'opacity'       => 'Opacity',
			'filters'       => 'Filters',
			'overflow'      => 'Overflow',
			'position'      => 'Position',
			'visibility'    => 'Visibility',
			'cursor'        => 'Cursor',
			'list-style'    => 'Lists',
		);

		$groups = array();
		foreach ( array_keys( $group_order ) as $key ) {
			$groups[ $key ] = array();
		}

		foreach ( $all as $class_name => $data ) {
			$group = self::classify_for_editor_group( $class_name, $data );

			if ( null === $group || ! isset( $groups[ $group ] ) ) {
				continue;
			}

			$groups[ $group ][] = array(
				'value' => $class_name,
				'label' => $class_name,
			);
		}

		// Build final grouped array, omitting empty groups.
		$result = array();
		foreach ( $group_order as $key => $label ) {
			if ( ! empty( $groups[ $key ] ) ) {
				$result[] = array(
					'label'   => $label,
					'options' => $groups[ $key ],
				);
			}
		}

		return $result;
	}

	/**
	 * Invalidates both in-memory and WP object caches.
	 *
	 * Call this when SG config changes (e.g., color renamed, slug changed).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function invalidate_cache(): void {
		self::$all_classes_cache     = null;
		self::$dynamic_classes_cache = null;
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Export the full utility catalogue + bracket-prefix metadata.
	 *
	 * Designed as the single source of truth for the SaaS-side utility mirror
	 * (`resources/data/spectra-gs-utilities.php` in zipwp-credits-saas). The
	 * shape is intentionally stable: `utilities` is the same structure
	 * {@see self::get_cheatsheet_data()} emits, while `bracket_prefixes`
	 * mirrors {@see JitCompiler::get_prefix_map()} so the validator/resolver
	 * can reason about both registered utilities and per-utility arbitrary
	 * values without re-encoding the grammar.
	 *
	 * @since 1.0.0
	 *
	 * @return array{
	 *     utilities: array<int, array{name:string, css:string, title:string, description:string, category:string, tags:array<int,string>}>,
	 *     bracket_prefixes: array<string, string|array<int, string>>
	 * }
	 */
	public static function export_metadata(): array {
		return array(
			'utilities'        => self::get_cheatsheet_data(),
			'bracket_prefixes' => JitCompiler::get_prefix_map(),
			// Machine-readable styling GRAMMAR (SaaS remediation plan,
			// Phase 3A/3B; grammar v2): variant grammar derived from the
			// live JIT maps, palette grammar derived from the SAME channel
			// maps the generator consumes, arbitrary-value rules. Emission
			// POLICY (banned attrs/scopes/exemptions) deliberately does NOT
			// ship from here — it is pipeline-owned (SaaS
			// `config/spectra-contract.json`). ADDITIVE — never reshape
			// `utilities`/`bracket_prefixes` (the SaaS mirror depends on
			// them verbatim).
			'contract'         => array_merge(
				JitCompiler::export_contract(),
				array(
					'palette' => array(
						'channels' => self::palette_channels(),
						'shades'   => array_values( self::CHROMATIC_SHADE_MAP ),
					),
				)
			),
		);
	}

	// ─────────────────────────────────────────────────────────────
	// PRIVATE HELPERS
	// ─────────────────────────────────────────────────────────────

	/**
	 * Fetches dynamic classes (colors, spacing, fonts) with WP object cache.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array>
	 */
	private static function get_dynamic_classes(): array {
		if ( null !== self::$dynamic_classes_cache ) {
			return self::$dynamic_classes_cache;
		}

		$cached = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );
		if ( false !== $cached && is_array( $cached ) ) {
			self::$dynamic_classes_cache = $cached;
			return $cached;
		}

		$dynamic = array_merge(
			self::get_color_classes(),
			self::get_spacing_classes(),
			self::get_font_classes()
		);

		wp_cache_set( self::CACHE_KEY, $dynamic, self::CACHE_GROUP );
		self::$dynamic_classes_cache = $dynamic;

		return $dynamic;
	}

	/**
	 * Returns the SG configuration array.
	 *
	 * Falls back to default config if not set.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected static function get_sg_config(): array {
		if ( ! class_exists( '\SpectraBlocks\StyleGuide\Engine' ) ) {
			return array();
		}

		$engine = \SpectraBlocks\StyleGuide\Engine::get_instance();
		$config = $engine->get_config();

		return is_array( $config ) ? $config : array();
	}

	/**
	 * Derives the class slug for a chromatic color.
	 *
	 * Reads classSlug from config if present, otherwise falls back to kebab-cased name.
	 * Default slugs: chromatic1=primary, chromatic2=secondary.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $index    The chromatic index (1-7).
	 * @param array      $chromatic The chromatic config entry.
	 * @return string
	 */
	private static function get_chromatic_slug( $index, array $chromatic ): string {
		// If classSlug is explicitly set, use it.
		if ( ! empty( $chromatic['classSlug'] ) ) {
			return sanitize_title( $chromatic['classSlug'] );
		}

		// Default slugs for first two chromatics.
		$defaults = array(
			1 => 'primary',
			2 => 'secondary',
		);

		if ( isset( $defaults[ (int) $index ] ) ) {
			return $defaults[ (int) $index ];
		}

		// Fall back to kebab-cased name.
		if ( ! empty( $chromatic['name'] ) ) {
			return sanitize_title( $chromatic['name'] );
		}

		return 'color-' . $index;
	}

	/**
	 * Registers a full set of color classes for all prefixes (bg, text, border, overlay, etc.)
	 * including opacity variants.
	 *
	 * @since 1.0.0
	 *
	 * @param array  &$classes          Reference to the classes array being built.
	 * @param string $slug              The color family slug (e.g., 'primary', 'base').
	 * @param string $tw_shade          The Tailwind shade label (e.g., '500').
	 * @param string $css_var               The CSS var() reference (e.g., 'var(--spectra-chromatic1-6)').
	 * @param string $family_label      Human-readable family name (e.g., 'Primary').
	 * @param array  $property_map      Standard prefix => CSS property map.
	 * @param array  $border_positions  Single-side border prefix => CSS property map.
	 * @param array  $border_multi      Multi-side border prefix => CSS properties map.
	 * @return void
	 */
	private static function register_color_for_all_prefixes(
		array &$classes,
		string $slug,
		string $tw_shade,
		string $css_var,
		string $family_label,
		array $property_map,
		array $border_positions,
		array $border_multi
	): void {
		$color_id = "{$slug}-{$tw_shade}";

		// Standard prefixes (bg, text, border, overlay).
		foreach ( $property_map as $prefix => $css_prop ) {
			$class_name = "{$prefix}-{$color_id}";

			// Every `text-*` utility emits `color` AND `-webkit-text-fill-color`.
			// Without this pair, a `bg-clip-text text-transparent` gradient-text
			// recipe renders as invisible text on WebKit (Chrome, Safari) because
			// `-webkit-text-fill-color` defaults to `currentColor` and wins over
			// `color: transparent`. Tailwind v4 emits the pair on every `text-*`
			// for the same reason — the bloat is one line per color and the
			// correctness gain is uniform (any author-written text color renders
			// through a clipped background without extra ceremony).
			$base_css = "{$css_prop}: {$css_var};";
			if ( 'color' === $css_prop ) {
				$base_css .= " -webkit-text-fill-color: {$css_var};";
			}

			$classes[ $class_name ] = array(
				'css'         => $base_css,
				'title'       => ucfirst( $prefix ) . ' - ' . $family_label . ' ' . $tw_shade,
				'description' => ucfirst( $prefix ) . " using {$family_label} shade {$tw_shade}.",
				'category'    => 'colors',
				'tags'        => array( $prefix, $slug, 'color' ),
			);

			// Opacity variants.
			foreach ( self::OPACITY_STEPS as $pct ) {
				$opacity_class = "{$class_name}/{$pct}";
				$mix_value     = "color-mix(in srgb, {$css_var} {$pct}%, transparent)";
				$opacity_css   = "{$css_prop}: {$mix_value};";
				if ( 'color' === $css_prop ) {
					$opacity_css .= " -webkit-text-fill-color: {$mix_value};";
				}

				$classes[ $opacity_class ] = array(
					'css'         => $opacity_css,
					'title'       => ucfirst( $prefix ) . ' - ' . $family_label . ' ' . $tw_shade . '/' . $pct,
					'description' => ucfirst( $prefix ) . " using {$family_label} shade {$tw_shade} at {$pct}% opacity.",
					'category'    => 'colors',
					'tags'        => array( $prefix, $slug, 'color', 'opacity' ),
				);
			}
		}

		// Single-side border positions (border-t, border-r, border-b, border-l).
		foreach ( $border_positions as $prefix => $css_prop ) {
			$class_name             = "{$prefix}-{$color_id}";
			$classes[ $class_name ] = array(
				'css'         => "{$css_prop}: {$css_var};",
				'title'       => ucfirst( str_replace( '-', ' ', $prefix ) ) . ' - ' . $family_label . ' ' . $tw_shade,
				'description' => ucfirst( str_replace( '-', ' ', $prefix ) ) . " color using {$family_label} shade {$tw_shade}.",
				'category'    => 'colors',
				'tags'        => array( 'border', $slug, 'color' ),
			);

			// Opacity variants for border positions.
			foreach ( self::OPACITY_STEPS as $pct ) {
				$opacity_class = "{$class_name}/{$pct}";
				$opacity_css   = "{$css_prop}: color-mix(in srgb, {$css_var} {$pct}%, transparent);";

				$classes[ $opacity_class ] = array(
					'css'         => $opacity_css,
					'title'       => ucfirst( str_replace( '-', ' ', $prefix ) ) . ' - ' . $family_label . ' ' . $tw_shade . '/' . $pct,
					'description' => ucfirst( str_replace( '-', ' ', $prefix ) ) . " color using {$family_label} shade {$tw_shade} at {$pct}% opacity.",
					'category'    => 'colors',
					'tags'        => array( 'border', $slug, 'color', 'opacity' ),
				);
			}
		}

		// Multi-side border positions (border-x, border-y).
		foreach ( $border_multi as $prefix => $css_props ) {
			$css_parts = array();
			foreach ( $css_props as $prop ) {
				$css_parts[] = "{$prop}: {$css_var};";
			}

			$class_name             = "{$prefix}-{$color_id}";
			$classes[ $class_name ] = array(
				'css'         => implode( ' ', $css_parts ),
				'title'       => ucfirst( str_replace( '-', ' ', $prefix ) ) . ' - ' . $family_label . ' ' . $tw_shade,
				'description' => ucfirst( str_replace( '-', ' ', $prefix ) ) . " color using {$family_label} shade {$tw_shade}.",
				'category'    => 'colors',
				'tags'        => array( 'border', $slug, 'color' ),
			);

			// Opacity variants for multi-side border.
			foreach ( self::OPACITY_STEPS as $pct ) {
				$opacity_css_parts = array();
				foreach ( $css_props as $prop ) {
					$opacity_css_parts[] = "{$prop}: color-mix(in srgb, {$css_var} {$pct}%, transparent);";
				}

				$opacity_class             = "{$class_name}/{$pct}";
				$classes[ $opacity_class ] = array(
					'css'         => implode( ' ', $opacity_css_parts ),
					'title'       => ucfirst( str_replace( '-', ' ', $prefix ) ) . ' - ' . $family_label . ' ' . $tw_shade . '/' . $pct,
					'description' => ucfirst( str_replace( '-', ' ', $prefix ) ) . " color using {$family_label} shade {$tw_shade} at {$pct}% opacity.",
					'category'    => 'colors',
					'tags'        => array( 'border', $slug, 'color', 'opacity' ),
				);
			}
		}

		// Ring color variants — set custom properties consumed by resolve_tw_ring().
		// `ring-{slug}-{shade}` sets --tw-ring-color; `ring-offset-{slug}-{shade}`
		// sets --tw-ring-offset-color. Both emit only the custom prop; the
		// composed box-shadow comes from a sibling width utility (ring-N /
		// ring-offset-N) in the same class list.
		$classes[ "ring-{$color_id}" ]        = array(
			'css'         => "--tw-ring-color: {$css_var};",
			'title'       => 'Ring color - ' . $family_label . ' ' . $tw_shade,
			'description' => "Ring color using {$family_label} shade {$tw_shade}.",
			'category'    => 'colors',
			'tags'        => array( 'ring', $slug, 'color' ),
		);
		$classes[ "ring-offset-{$color_id}" ] = array(
			'css'         => "--tw-ring-offset-color: {$css_var};",
			'title'       => 'Ring offset color - ' . $family_label . ' ' . $tw_shade,
			'description' => "Ring offset color using {$family_label} shade {$tw_shade}.",
			'category'    => 'colors',
			'tags'        => array( 'ring', 'offset', $slug, 'color' ),
		);
	}

	/**
	 * Classifies a class into an editor group key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The class name.
	 * @param array  $data       The class data array.
	 * @return string|null The group key, or null if it should be excluded.
	 */
	private static function classify_for_editor_group( string $class_name, array $data ): ?string {
		$category = $data['category'] ?? '';

		// Color classes — categorize by prefix.
		if ( 'colors' === $category ) {
			if ( 0 === strpos( $class_name, 'bg-' ) ) {
				return 'bg-color';
			}
			if ( 0 === strpos( $class_name, 'text-' ) ) {
				return 'text-color';
			}
			if ( 0 === strpos( $class_name, 'overlay-' ) ) {
				return 'overlay-color';
			}
			if ( 0 === strpos( $class_name, 'border' ) ) {
				return 'border-color';
			}
			return null;
		}

		// Spacing classes — categorize by prefix.
		if ( 'spacing' === $category ) {
			if ( 0 === strpos( $class_name, 'p' ) && ( 1 === strlen( $class_name ) || '-' === $class_name[1] || 't' === $class_name[1] || 'r' === $class_name[1] || 'b' === $class_name[1] || 'l' === $class_name[1] || 'x' === $class_name[1] || 'y' === $class_name[1] ) ) {
				return 'padding';
			}
			if ( 0 === strpos( $class_name, 'm' ) && ( 1 === strlen( $class_name ) || '-' === $class_name[1] || 't' === $class_name[1] || 'r' === $class_name[1] || 'b' === $class_name[1] || 'l' === $class_name[1] || 'x' === $class_name[1] || 'y' === $class_name[1] ) ) {
				return 'margin';
			}
			if ( 0 === strpos( $class_name, 'gap' ) ) {
				return 'gap';
			}
			return null;
		}

		// Typography classes.
		if ( 'typography' === $category ) {
			if ( 0 === strpos( $class_name, 'text-' ) || 0 === strpos( $class_name, 'leading-' ) ) {
				return 0 === strpos( $class_name, 'leading-' ) ? 'line-height' : 'font-size';
			}
			return null;
		}

		// Map remaining categories directly.
		$category_map = array(
			'display'     => 'display',
			'layout'      => 'layout',
			'sizing'      => 'sizing',
			'border'      => 'border',
			'filters'     => 'filters',
			'font-weight' => 'font-weight',
			'text-align'  => 'text-align',
			'tracking'    => 'tracking',
			'text-style'  => 'text-style',
			'shadow'      => 'shadow',
			'opacity'     => 'opacity',
			'overflow'    => 'overflow',
			'position'    => 'position',
			'visibility'  => 'visibility',
			'cursor'      => 'cursor',
			'list-style'  => 'list-style',
		);

		if ( isset( $category_map[ $category ] ) ) {
			return $category_map[ $category ];
		}

		return null;
	}
}
