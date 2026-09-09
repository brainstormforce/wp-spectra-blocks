<?php
/**
 * ResponsiveControls Extension.
 *
 * Provides responsive control functionality for Spectra blocks, allowing
 * different styling for mobile, tablet, and desktop devices. This extension
 * handles the generation of responsive CSS and ensures proper fallback
 * between device sizes following WordPress core patterns.
 *
 * @package Spectra\Extensions
 * @since 3.0.0
 */

namespace SpectraBlocks\Extensions;

use SpectraBlocks\Extensions\ResponsiveControls\ResponsiveAttributeCSS;
use SpectraBlocks\Extensions\ResponsiveControls\Legacy\LegacyStore;
use SpectraBlocks\Extensions\ResponsiveControls\Pre71\StorePrecedence;
use SpectraBlocks\Extensions\ResponsiveControls\Pre71\ViewportStatesFallback;
use SpectraBlocks\Extensions\ResponsiveControls\ViewportSupport;
use SpectraBlocks\Helpers\Core;
use SpectraBlocks\Traits\Singleton;
use WP_HTML_Tag_Processor;

/**
 * Class to manage responsive controls for Spectra blocks.
 *
 * This class handles:
 * - Device-specific styling (mobile, tablet, desktop)
 * - Responsive CSS generation with proper fallback hierarchy
 * - Layout management across different screen sizes
 * - Conflict resolution with WordPress core styling
 *
 * @since 3.0.0
 */
class ResponsiveControls {

	use Singleton;

	/**
	 * Whether the current rendering context is a pattern preview.
	 * Set by preview functions to avoid debug_backtrace() on every block render.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public static $is_pattern_preview = false;

	/**
	 * Lists of blocks and prefixes for responsive control filtering.
	 *
	 * @var array $excluded_blocks Block names that should not have responsive controls applied.
	 * @since 3.0.0
	 */
	private $excluded_blocks = array();

	/**
	 * Lists of blocks and prefixes for responsive control filtering.
	 *
	 * @var array $allowed_prefixes Block name prefixes that identify blocks that should have responsive controls.
	 * @since 3.0.0
	 */
	private $allowed_prefixes = array( 'spectra/', 'spectra-pro/' );

	/**
	 * Lists of blocks and prefixes for responsive control filtering.
	 *
	 * @var array $supported_blocks Specific block names that should have responsive controls applied.
	 * @since 3.0.0
	 */
	private $supported_blocks = array(
		'core/image',
	);

	/**
	 * Store device keys mapped to their location inside the `style` attribute.
	 *
	 * An empty string means the ROOT of `style`, which is where the base layer
	 * lives. That is core's own arrangement — its device map is
	 * `{ Desktop: 'default', Tablet: '@tablet', Mobile: '@mobile' }`, so Desktop IS
	 * the root and there is no `@desktop` state. Core never reads or writes one.
	 *
	 * Spectra used to keep the base in a `@desktop` key of its own, because the root
	 * was the surface the editor projected the selected device into and a base
	 * stored there would be overwritten on every device switch. That projection is
	 * gone, so the root is free to be what core already treats it as.
	 *
	 * Keep in sync with `DEVICE_TO_STYLE_STATE` in style-store.js.
	 *
	 * @var array<string, string>
	 * @since 1.0.7
	 */
	const DEVICE_TO_STYLE_STATE = array(
		'base'    => '',
		'@tablet' => '@tablet',
		'@mobile' => '@mobile',
	);

	/**
	 * Style groups that live at the top level of a store bucket, not under `style`.
	 *
	 * Inside a `style` state object every value is keyed by name at one level.
	 * In the store bucket these sit beside the block-specific keys instead of
	 * under `style` — which is where `process_responsive_attributes()`, the CSS
	 * generator and the block controllers all read them from. Writing them under
	 * `style` left them invisible to every one of them: the block fell back to
	 * its default layout at every breakpoint, and a preset border colour or font
	 * size was dropped entirely.
	 *
	 * This is `$responsive_keys`, and WordPress core draws the same line —
	 * `layout`, `fontSize`, `fontFamily` and `borderColor` are each their own
	 * block attribute and are never nested inside `style`.
	 *
	 * Keep in sync with `BUCKET_TOP_LEVEL_STYLE_KEYS` in constants.js.
	 *
	 * @var array<string>
	 * @since 1.0.7
	 */
	const BUCKET_TOP_LEVEL_STYLE_KEYS = array( 'layout', 'fontSize', 'fontFamily', 'borderColor' );

	/**
	 * Core's child-layout keys — how a block sits inside its PARENT's flex or
	 * grid container. Core stores them in the same `style.layout` object as the
	 * container layout and splits them on read (`wp_get_layout_child_values()`
	 * vs `wp_get_layout_container_values()`). The store keeps them apart too:
	 * container keys at the bucket's top level for `generate_layout_css()`,
	 * child keys under `style.layout` for `generate_style_layout_css()`.
	 *
	 * Keep in sync with `CORE_CHILD_LAYOUT_KEYS` in `utils/style-store.js`.
	 *
	 * @var array<string>
	 * @since 1.0.7
	 */
	const CORE_CHILD_LAYOUT_KEYS = array( 'selfStretch', 'flexSize', 'columnStart', 'columnSpan', 'rowStart', 'rowSpan' );

	/**
	 * WordPress core's default viewport breakpoints.
	 *
	 * Spectra does NOT publish breakpoints of its own — it reads whatever
	 * WordPress has in force so that a block's Spectra-generated CSS and its
	 * core-generated CSS always band identically. These values exist only as
	 * the fallback for WordPress versions older than 7.1, where there is no
	 * `settings.viewport` to read; they are copied from
	 * `WP_Theme_JSON::DEFAULT_VIEWPORT_BREAKPOINTS` so both eras agree.
	 *
	 * A site that wants different breakpoints sets `settings.viewport` in its
	 * theme.json (or filters it) — WordPress's own mechanism, which this
	 * extension follows.
	 *
	 * @var array<string, string>
	 * @since 1.0.7
	 */
	const DEFAULT_VIEWPORT_BREAKPOINTS = array(
		'mobile' => '480px',
		'tablet' => '782px',
	);

	/**
	 * Media queries for different screen sizes.
	 *
	 * @var array<string, string>|null Resolved lazily by get_media_queries().
	 * @since 3.0.0
	 */
	private $media_queries = null;

	/**
	 * The media queries this generator emits.
	 *
	 * Spectra does not decide the breakpoints — WordPress does. A single block is
	 * rendered by both halves at once (this extension emits spacing, typography,
	 * border, shadow and layout; core emits the rest), so if the two disagreed on
	 * where a band starts, one block would get core's tablet colour together with
	 * Spectra's mobile spacing. Delegating to core's own resolver is what makes
	 * that impossible rather than merely unlikely: same breakpoints, same
	 * sanitization, same boundary arithmetic, including whatever a theme declares
	 * in `settings.viewport`.
	 *
	 * `base` is the DESKTOP layer and deliberately carries no media query, so it
	 * applies at every width and the narrower bands override it — exactly how
	 * core treats its `default` viewport. Gating it behind a `min-width` left
	 * widths covered by nothing, and a viewport landing in such a gap — routine
	 * once browser zoom or a fractional device pixel ratio puts the CSS width on
	 * a non-integer — lost the block's styling entirely.
	 *
	 * ORDER MATTERS. These rules all share one selector, so specificity cannot
	 * separate them and source order decides. Base is emitted first; the banded
	 * overrides follow and win inside their ranges. Moving `base` later would
	 * have it override both.
	 *
	 * @since 1.0.7
	 * @return array<string, string> Media query conditions keyed by device, base first.
	 */
	public function get_media_queries() {
		if ( is_array( $this->media_queries ) ) {
			return $this->media_queries;
		}

		$bands = array( 'base' => '' ) + $this->resolve_viewport_bands();

		// Mobile last: see `resolve_viewport_bands()` on the shared edge.
		uksort(
			$bands,
			static function ( $a, $b ) {
				$order = array(
					'base'     => 0,
					'@desktop' => 1,
					'@tablet'  => 2,
					'@mobile'  => 3,
				);

				return ( $order[ $a ] ?? 9 ) <=> ( $order[ $b ] ?? 9 );
			}
		);

		$this->media_queries = $bands;

		return $this->media_queries;
	}

	/**
	 * Make generated CSS safe to place inside a `<style>` element.
	 *
	 * NOT `wp_strip_all_tags()`. That function is an HTML tag stripper, and CSS
	 * legitimately contains `<`: core's own viewport media queries use range
	 * syntax, so `@media (480px < width <= 782px) { ... }` reads as an unclosed
	 * tag and everything from the `<` to the next `>` is deleted — which silently
	 * removed every tablet and mobile rule from the page while the generator
	 * itself was producing them correctly. The same trap already cost this
	 * codebase its SVG data URLs; see `GlobalStyles\Sanitizer::sanitize_value()`.
	 *
	 * The only thing that actually needs neutralising in a style element is a
	 * sequence that could close it early, so that is what this removes.
	 *
	 * @since 1.0.7
	 * @param string $css Generated CSS.
	 * @return string CSS that cannot terminate its own style element.
	 */
	public static function sanitize_inline_css( $css ) {
		return str_ireplace( array( '</', '<!--' ), '', (string) $css );
	}

	/**
	 * Viewport bands including desktop, for features that switch on device rather
	 * than override a base value.
	 *
	 * The style generator has no use for a desktop band — its base layer already
	 * applies everywhere. Device VISIBILITY does: hiding a block on desktop means
	 * a rule that fires above the tablet ceiling and nowhere else. These bands
	 * come from the same resolver, so "hide on mobile" and "restyle on mobile"
	 * can never disagree about where mobile ends.
	 *
	 * @since 1.0.7
	 * @return array<string, string> Media conditions keyed by `@mobile`/`@tablet`/`@desktop`.
	 */
	public function get_device_media_queries() {
		return $this->resolve_viewport_bands( true );
	}

	/**
	 * Viewport bands as bare media conditions, keyed by core's state names.
	 *
	 * WordPress 7.1+ answers this itself, so the breakpoints, the `px`/`em`/`rem`
	 * validation, the "tablet must exceed mobile" rule and the "fall back to the
	 * defaults when nothing valid is declared" rule are all core's, not a second
	 * implementation that can drift from them. Core returns fully-formed
	 * `@media (...)` strings; callers here wrap the condition themselves, so the
	 * prefix is stripped.
	 *
	 * @since 1.0.7
	 * @param bool $include_desktop Whether to include the desktop band.
	 * @return array<string, string> Media conditions keyed by `@mobile`/`@tablet`/`@desktop`.
	 */
	private function resolve_viewport_bands( $include_desktop = false ) {
		$viewport = $this->resolved_viewport();

		if ( is_callable( array( '\WP_Theme_JSON', 'get_viewport_media_queries' ) ) ) {
			// The bundled stubs predate WordPress 7.1, where this method was added,
			// so static analysis cannot see it; the is_callable() guard above is
			// what makes the call safe at runtime. Ignored in phpstan.neon.
			$core  = \WP_Theme_JSON::get_viewport_media_queries( $viewport, array( 'include_desktop' => $include_desktop ) );
			$bands = array();

			foreach ( (array) $core as $state => $query ) {
				$bands[ $state ] = trim( (string) preg_replace( '/^@media\s*/', '', (string) $query ) );
			}

			return $bands;
		}

		return $this->viewport_bands_fallback( $viewport, $include_desktop );
	}

	/**
	 * The viewport breakpoints WordPress has in force, or null when it has none.
	 *
	 * `settings.viewport` (mobile / tablet upper bounds) from the merged
	 * theme.json — a theme's own values, else WordPress's defaults. Below 7.1
	 * the setting does not exist and this returns null, which every caller
	 * turns into `DEFAULT_VIEWPORT_BREAKPOINTS`.
	 *
	 * @since 1.0.7
	 * @return array<string, string>|null Keyed `mobile` / `tablet`, or null.
	 */
	private function resolved_viewport() {
		$viewport = null;

		if ( function_exists( 'wp_get_global_settings' ) ) {
			$setting = wp_get_global_settings( array( 'viewport' ) );

			/*
			 * `wp_get_global_settings()` returns the ENTIRE settings tree when the
			 * requested path is absent, which is the normal case — no theme
			 * declares `settings.viewport`. Passing that tree on happens to
			 * survive, because core's sanitizer finds no valid breakpoint in it
			 * and falls back to the defaults, but it is the right answer by
			 * accident. Narrow it to the two keys that are actually breakpoints.
			 */
			$viewport = is_array( $setting )
				? array_intersect_key( $setting, array_flip( array( 'mobile', 'tablet' ) ) )
				: null;
		}

		return $viewport;
	}

	/**
	 * The band upper bounds in CSS pixels: mobile ends at `mobile`, tablet at `tablet`.
	 *
	 * The same source the media queries come from, as numbers, for code that
	 * cannot consume a media query: Swiper's `breakpoints` keys, the localised
	 * editor values. Non-pixel breakpoints are converted at 16px/em. Falls back
	 * to `DEFAULT_VIEWPORT_BREAKPOINTS` exactly as the queries do, so a number
	 * and the query it accompanies never describe different widths.
	 *
	 * @since 1.0.7
	 * @return array{mobile: float, tablet: float} Upper bounds in px.
	 */
	public function get_viewport_breakpoint_pixels() {
		$viewport = $this->resolved_viewport();
		$pixels   = array();

		foreach ( array( 'mobile', 'tablet' ) as $device ) {
			$value = is_array( $viewport ) ? ( $viewport[ $device ] ?? null ) : null;
			$px    = self::breakpoint_in_pixels( $value );

			if ( null === $px ) {
				$px = self::breakpoint_in_pixels( self::DEFAULT_VIEWPORT_BREAKPOINTS[ $device ] );
			}

			$pixels[ $device ] = (float) $px;
		}

		if ( $pixels['tablet'] <= $pixels['mobile'] ) {
			$pixels['tablet'] = (float) self::breakpoint_in_pixels( self::DEFAULT_VIEWPORT_BREAKPOINTS['tablet'] );
		}

		return $pixels;
	}

	/**
	 * The smallest whole pixel width of each band, for `min-width` consumers.
	 *
	 * Swiper's `breakpoints` are inclusive `min-width` keys. A band starts at
	 * the first whole pixel above its neighbour's upper bound — `481px` after a
	 * `480px` mobile bound, `768px` after `767.98px` — which is where the
	 * generated CSS starts it too.
	 *
	 * @since 1.0.7
	 * @return array{mobile: int, tablet: int, desktop: int} Min widths in px.
	 */
	public function get_viewport_min_widths() {
		$pixels = $this->get_viewport_breakpoint_pixels();

		return array(
			'mobile'  => 0,
			'tablet'  => (int) floor( $pixels['mobile'] ) + 1,
			'desktop' => (int) floor( $pixels['tablet'] ) + 1,
		);
	}

	/**
	 * Core's viewport banding, reproduced for WordPress older than 7.1.
	 *
	 * Mirrors `WP_Theme_JSON::sanitize_viewport_settings()`: only `px`/`em`/`rem`
	 * lengths count, a `tablet` that does not exceed `mobile` is dropped, a lone
	 * valid breakpoint becomes a single `max-width` band, and nothing valid means
	 * the defaults. The bands are emitted in classic `min-width`/`max-width` form
	 * rather than core's `(mobile < width <= tablet)` range syntax, which older
	 * browsers ignore outright — on these versions there is no core output to
	 * match, so the wider-support form costs nothing.
	 *
	 * The lower edge of each band sits a hair ABOVE the breakpoint
	 * (`min-width: 480.02px`), so the bands are disjoint the way core's ranges
	 * are (`480px < width`). An earlier build let them touch at exactly the
	 * breakpoint, reasoning that overlap was safe because the caller emits mobile
	 * last and so wins that single width. That holds for merged property rules,
	 * where both bands set the same property — but not for rules keyed on a
	 * device class with `!important`: those target different selectors, nothing
	 * competes, and both apply. Measured on 7.0.4: at exactly 782 px both
	 * "hide on desktop" and "hide on tablet" blocks were hidden, and a
	 * container's desktop `orientationReverse` reversed inside the tablet band.
	 * The 0.02 px step is the same one the plugin's pre-7.1 stylesheet used
	 * (`767.98px` / `1023.98px`); a viewport width landing inside it is not
	 * something browsers produce for integer or half-pixel zoom levels.
	 *
	 * @since 1.0.7
	 * @param mixed $viewport        Raw `settings.viewport` value, if any.
	 * @param bool  $include_desktop Whether to include the desktop band.
	 * @return array<string, string> Media conditions keyed by state.
	 */
	private function viewport_bands_fallback( $viewport, $include_desktop ) {
		$breakpoints = self::DEFAULT_VIEWPORT_BREAKPOINTS;

		if ( is_array( $viewport ) ) {
			$valid = array();

			foreach ( array_keys( self::DEFAULT_VIEWPORT_BREAKPOINTS ) as $device ) {
				$pixels = self::breakpoint_in_pixels( $viewport[ $device ] ?? null );

				if ( null !== $pixels ) {
					$valid[ $device ] = array(
						'value'  => trim( (string) $viewport[ $device ] ),
						'pixels' => $pixels,
					);
				}
			}

			if ( array() !== $valid ) {
				if ( ! isset( $valid['mobile'] ) ) {
					$breakpoints = array( 'tablet' => $valid['tablet']['value'] );
				} elseif ( ! isset( $valid['tablet'] ) || $valid['tablet']['pixels'] <= $valid['mobile']['pixels'] ) {
					$breakpoints = array( 'mobile' => $valid['mobile']['value'] );
				} else {
					$breakpoints = array(
						'mobile' => $valid['mobile']['value'],
						'tablet' => $valid['tablet']['value'],
					);
				}
			}
		}

		$mobile = $breakpoints['mobile'] ?? null;
		$tablet = $breakpoints['tablet'] ?? null;
		$bands  = array();

		if ( null !== $tablet ) {
			$bands['@tablet'] = null !== $mobile
				? '(min-width: ' . self::exclusive_lower_edge( $mobile ) . ') and (max-width: ' . $tablet . ')'
				: '(max-width: ' . $tablet . ')';
		}

		if ( null !== $mobile ) {
			$bands['@mobile'] = '(max-width: ' . $mobile . ')';
		}

		if ( $include_desktop ) {
			$floor = $tablet ?? $mobile;

			if ( null !== $floor ) {
				$bands['@desktop'] = '(min-width: ' . self::exclusive_lower_edge( $floor ) . ')';
			}
		}

		return $bands;
	}

	/**
	 * The `min-width` that starts a band just above a breakpoint another band ends at.
	 *
	 * Core's range syntax expresses this as `480px < width`; the classic form has
	 * no strict inequality, so the edge moves up by 0.02 px — added directly for
	 * pixel values, through `calc()` for `em` / `rem` so the unit is preserved.
	 *
	 * @since 1.0.7
	 * @param string $breakpoint A validated `px` / `em` / `rem` length.
	 * @return string The length the next band starts at.
	 */
	private static function exclusive_lower_edge( $breakpoint ) {
		$breakpoint = trim( $breakpoint );

		if ( 1 === preg_match( '/^(\d+|\d*\.\d+)px$/', $breakpoint, $matches ) ) {
			// Format without trailing zeros so `480px` becomes `480.02px`, not `480.020000px`.
			return rtrim( rtrim( number_format( (float) $matches[1] + 0.02, 2, '.', '' ), '0' ), '.' ) . 'px';
		}

		return 'calc(' . $breakpoint . ' + 0.02px)';
	}

	/**
	 * A breakpoint length in pixels, or null when it is not one core would accept.
	 *
	 * Mirrors core's validation and its 16px base for `em`/`rem`. The pixel value
	 * only orders `mobile` against `tablet`; emitted bands keep the original unit.
	 *
	 * @since 1.0.7
	 * @param mixed $value Candidate breakpoint value.
	 * @return float|null Length in pixels, or null when invalid.
	 */
	private static function breakpoint_in_pixels( $value ) {
		if ( ! is_string( $value ) || 1 !== preg_match( '/^(?:\d+|\d*\.\d+)(px|em|rem)$/', trim( $value ), $matches ) ) {
			return null;
		}

		$number = (float) trim( $value );

		return 'px' === $matches[1] ? $number : $number * 16;
	}


	/**
	 * Handle for the responsive styles stylesheet.
	 *
	 * Used to register and enqueue the stylesheet that contains
	 * all responsive CSS rules generated by this extension.
	 *
	 * @var string WordPress stylesheet handle.
	 * @since 3.0.0
	 */
	private $style_handle = 'spectra-responsive-styles';

	/**
	 * Generator fingerprint for the per-block CSS cache key.
	 *
	 * The cached transient stores GENERATED OUTPUT, so the key must
	 * change whenever the generating code changes — otherwise a code
	 * fix keeps serving stale CSS until SPECTRA_BLOCKS_VER rotates
	 * (which never happens between dev builds). Bump on ANY change to
	 * Responsive_Attribute_CSS / generate_responsive_css output.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	const CSS_GENERATOR_VERSION = '29';

	/**
	 * Add inline responsive CSS only once per request.
	 *
	 * Stores a list of inline CSS rules already added to avoid duplicates.
	 *
	 * @var array List of inline CSS rules already added.
	 * @since 3.0.0
	 */
	private $inline_css_added = array();

	/**
	 * Track if default CSS has been added for each block type.
	 *
	 * Prevents duplicate default CSS from being added multiple times
	 * for blocks that appear multiple times on a page.
	 *
	 * @var array List of block types that have had default CSS added.
	 * @since 3.0.0
	 */
	private $default_css_added = array();

	/**
	 * Expiration time for cached responsive CSS.
	 *
	 * Cached CSS is stored in a transient and reused across requests
	 * until this expiration time is reached.
	 *
	 * @var int Seconds to keep the cached CSS.
	 * @since 3.0.0
	 */
	private $cache_expiration = DAY_IN_SECONDS;

	/**
	 * Device fallback hierarchy for responsive values.
	 *
	 * Defines the order in which devices should fall back to get values.
	 * If a value is not set for the current device, it will try the next device in the array.
	 *
	 * @var array<string, array<string>> Device => fallback order array.
	 * @since 3.0.0
	 */
	private $device_fallback_order = array(
		'@mobile' => array( '@mobile', 'base' ), // Mobile: mobile over base — core's model, no tablet inheritance.
		'@tablet' => array( '@tablet', 'base' ), // Tablet: tablet over base.
		'base'    => array( 'base' ),            // Desktop: the base layer itself.
	);

	/**
	 * List of attribute keys that should be processed responsively.
	 *
	 * These keys represent block attributes that can have different values
	 * across different device sizes (mobile, tablet, desktop). The responsive
	 * controls system will track and manage these attributes separately for
	 * each breakpoint.
	 *
	 * @var array<string> List of attribute keys to process responsively.
	 * @since 3.0.0
	 */
	private $responsive_keys = array( 'layout', 'fontSize', 'fontFamily', 'borderColor' );

	/**
	 * Extra root attributes to bridge, beyond the ones the block declares.
	 *
	 * `backward_compatibility_block_attributes()` bridges every attribute the
	 * block registers with `ResponsiveAttributeCSS`, so nothing in
	 * `ATTR_DEFINITIONS` needs listing here. This is only for root attributes
	 * the per-device CSS pipeline reads WITHOUT declaring them there — the
	 * separator's style and alignment, which its renderer takes straight from
	 * the store.
	 *
	 * @var array<string, array<string>> Block name => List of attributes.
	 * @since 1.0.0
	 */
	private $backward_compatibility_attributes = array(
		'spectra/separator' => array(
			'separatorStyle',
			'separatorAlign',
		),
	);

	/**
	 * Core WordPress style properties that conflict with responsive controls.
	 *
	 * These properties need to be removed from block attributes to prevent
	 * conflicts with our responsive control system.
	 *
	 * @var array<string> List of core style property names.
	 * @since 3.0.0
	 */
	private $style_responsive_keys = array(
		'spacing',    // Padding, margin, blockGap properties that are critical for layout containers.
		'typography', // Font family, size, weight, line height properties.
		'border',     // Border styles, width, radius properties.
		'shadow',     // Box shadow effect properties.
		'layout',     // Layout properties that conflict with responsive controls.
	);

	/**
	 * Core WordPress block attributes that conflict with responsive controls.
	 *
	 * Individual block attributes that need to be removed when responsive
	 * controls are active for the same properties.
	 *
	 * @var array<string> List of core attribute names.
	 * @since 3.0.0
	 */
	private $core_attributes = array(
		'fontSize',    // Typography font size attribute that conflicts with responsive controls.
		'fontFamily',  // Typography font family attribute that conflicts with responsive controls.
		'layout',      // Layout controls attribute (flexbox, grid, etc.) that conflicts with responsive controls.
		'borderColor', // Border color attribute that conflicts with responsive controls.
	);

	/**
	 * Default layout configurations for specific Spectra blocks.
	 *
	 * This array defines the default layout settings that should be applied
	 * to specific blocks when no custom layout has been defined by the user.
	 * Each block type can have its own predefined layout structure to ensure
	 * consistent appearance and behavior.
	 *
	 * @var array<string, array> Block name => Default layout configuration.
	 * @since 3.0.0
	 */
	private $blocks_default_layout = array(
		'spectra/accordion'               => array(
			'layout' => array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'orientation'    => 'vertical',
				'justifyContent' => 'stretch',
			),
		),
		'spectra/accordion-child-item'    => array(
			'layout' => array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'orientation'    => 'vertical',
				'justifyContent' => 'stretch',
			),
		),
		'spectra/accordion-child-header'  => array(
			'layout' => array(
				'type'              => 'flex',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'left',
				'verticalAlignment' => 'center',
			),
		),
		'spectra/accordion-child-details' => array(
			'layout' => array(
				'type'              => 'flex',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'left',
				'verticalAlignment' => 'center',
				'orientation'       => 'vertical',
			),
		),
		'spectra/buttons'                 => array(
			'layout' => array(
				'type' => 'flex',
			),
		),
		'spectra/container'               => array(
			'layout' => array(
				'type'              => 'flex',
				'orientation'       => 'vertical',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'left',
				'verticalAlignment' => 'center',
			),
		),
		'spectra/icons'                   => array(
			'layout' => array(
				'type'              => 'flex',
				'orientation'       => 'horizontal',
				'flexWrap'          => 'wrap',
				'justifyContent'    => 'left',
				'verticalAlignment' => 'center',
			),
		),
		'spectra/tabs'                    => array(
			'layout' => array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'orientation'    => 'vertical',
				'justifyContent' => 'stretch',
			),
		),
		'spectra/tabs-child-tab-wrapper'  => array(
			'layout' => array(
				'type'              => 'flex',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'left',
				'verticalAlignment' => 'center',
			),
		),
		'spectra/tabs-child-tabpanel'     => array(
			'layout' => array(
				'type'              => 'flex',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'left',
				'verticalAlignment' => 'top',
				'orientation'       => 'vertical',
			),
		),
		'spectra/countdown'               => array(
			'layout' => array(
				'type'              => 'flex',
				'flexWrap'          => 'nowrap',
				'orientation'       => 'horizontal',
				'justifyContent'    => 'left',
				'verticalAlignment' => 'top',
			),
		),
		'spectra/countdown-child-day'     => array(
			'layout' => array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'orientation'    => 'vertical',
				'justifyContent' => 'center',
			),
		),
		'spectra/countdown-child-hour'    => array(
			'layout' => array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'orientation'    => 'vertical',
				'justifyContent' => 'center',
			),
		),
		'spectra/countdown-child-minute'  => array(
			'layout' => array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'orientation'    => 'vertical',
				'justifyContent' => 'center',
			),
		),
		'spectra/countdown-child-second'  => array(
			'layout' => array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'orientation'    => 'vertical',
				'justifyContent' => 'center',
			),
		),
		'spectra/slider-child'            => array(
			'layout' => array(
				'type'              => 'flex',
				'orientation'       => 'vertical',
				'justifyContent'    => 'center',
				'verticalAlignment' => 'center',
				'flexWrap'          => 'wrap',
			),
		),
		'spectra/list'                    => array(
			'layout' => array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'orientation'    => 'vertical',
				'justifyContent' => 'stretch',
			),
		),
		'spectra/list-child-item'         => array(
			'layout' => array(
				'type'              => 'flex',
				'flexWrap'          => 'nowrap',
				'justifyContent'    => 'left',
				'verticalAlignment' => 'center',
			),
		),
		'spectra/modal-child-trigger'     => array(
			'layout' => array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'justifyContent' => 'left',
			),
		),
		'spectra/modal-child-popup'       => array(
			'layout' => array(
				'type' => 'flex',
			),
		),
		'spectra/counter'                 => array(
			'layout' => array(
				'type'              => 'flex',
				'flexWrap'          => 'nowrap',
				'orientation'       => 'vertical',
				'justifyContent'    => 'center',
				'verticalAlignment' => 'center',
			),
		),
		'spectra/counter-child-wrapper'   => array(
			'layout' => array(
				'type'              => 'flex',
				'flexWrap'          => 'nowrap',
				'orientation'       => 'vertical',
				'justifyContent'    => 'center',
				'verticalAlignment' => 'center',
			),
		),
		'spectra/post'                    => array(
			'layout' => array(
				'type'           => 'flex',
				'flexWrap'       => 'nowrap',
				'orientation'    => 'vertical',
				'justifyContent' => 'stretch',
			),
		),
	);

	/**
	 * Hooks into WordPress to register responsive stylesheets and
	 * to process responsive block attributes.
	 *
	 * Sets up all necessary hooks for:
	 * - Registering responsive stylesheet
	 * - Processing responsive attributes during block rendering
	 * - Adding unique block identifiers for CSS targeting
	 * - Managing layout support to prevent conflicts with core
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function init() {
		// Hook into the CSS cache filter to respect the disable cache option.
		add_filter( 'spectra_blocks_enable_css_cache', array( $this, 'maybe_disable_css_cache' ) );

		// Register responsive stylesheet early to ensure it's available for all blocks.
		add_action( 'init', array( $this, 'register_responsive_style' ) );

		// Publish the resolved viewport bands to front-end scripts, before anything enqueues.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_viewport_bands_script' ), 5 );

		// Register and enqueue responsive videos script for frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_responsive_videos_script' ) );

		// Enqueue responsive control injection assets for admin.
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_assets' ) );

		// Process responsive attributes during block rendering to generate CSS.
		add_filter( 'render_block_data', array( $this, 'process_responsive_attributes' ), 10, 1 );

		add_filter( 'render_block_data', array( $this, 'build_store_from_style' ), 4, 1 );

		/*
		 * Pre-1.0.6 content support. Self-contained in
		 * `ResponsiveControls/Legacy/` — delete that folder and this line to remove
		 * legacy compatibility entirely. Nothing on the current path calls into it.
		 */
		LegacyStore::init();

		add_filter( 'render_block_data', array( $this, 'backward_compatibility_block_attributes' ), 5, 1 );

		// Add unique block identifier for CSS targeting and generate responsive styles.
		add_filter( 'render_block', array( $this, 'process_block_and_add_responsive_styles' ), 10, 2 );
		// Pre-7.1 front-end support for core's viewport states. Self-contained
		// in `ResponsiveControls/Pre71/` and inert on 7.1 — see that folder.
		if ( class_exists( ViewportStatesFallback::class ) ) {
			ViewportStatesFallback::init();
		}

		// Remove layout support flag to prevent core from injecting layout classes that conflict.
		remove_filter( 'render_block', 'wp_render_layout_support_flag', 10 );

		// Conditionally suppress layout support for Spectra blocks only to avoid conflicts.
		add_filter( 'render_block', array( $this, 'maybe_skip_layout_support' ), 10, 2 );

		// Handle duplicate spectraIds after post save.
		add_action( 'save_post', array( $this, 'ensure_unique_spectra_ids' ), 20, 2 );
	}

	/**
	 * Check if CSS cache should be disabled based on admin setting.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $enable_cache Whether caching is enabled.
	 * @return bool False if cache is disabled in settings, original value otherwise.
	 */
	public function maybe_disable_css_cache( $enable_cache ) {
		$disable_cache = \Spectra_Blocks_Settings::get( 'disable_css_cache', 'disabled' );

		if ( 'enabled' === $disable_cache ) {
			return false;
		}

		return $enable_cache;
	}

	/**
	 * Registers a responsive stylesheet for all block responsive controls.
	 *
	 * Creates an empty stylesheet that will be dynamically populated with media query-based
	 * CSS rules for responsive block controls. This centralized approach prevents multiple
	 * style enqueues and ensures proper dependency management.
	 *
	 * Note: Uses `wp_register_style()` with `false` source to create a virtual stylesheet
	 * that will be populated with inline styles later. This is registered early in init
	 * to ensure availability for all blocks.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public function register_responsive_style() {
		// Only register on frontend requests (excluding AJAX and admin).
		if ( wp_doing_ajax() || is_admin() ) {
			return;
		}

		// Check if style is already registered to avoid duplicates.
		if ( wp_style_is( $this->style_handle, 'registered' ) ) {
			return;
		}

		/**
		 * Register virtual stylesheet:
		 * - Source: false (indicates inline styles will be added later).
		 * - No dependencies by default.
		 * - Versioned for cache control.
		 */
		wp_register_style(
			$this->style_handle,       // Stylesheet handle.
			false,                     // No source file (inline styles only).
			array(),                   // No dependencies.
			SPECTRA_BLOCKS_VER                   // Version for cache busting.
		);
	}

	/**
	 * Enqueue responsive videos script for frontend.
	 *
	 * Conditionally enqueues the responsive videos JavaScript file only when
	 * container or slider blocks with responsive video backgrounds are present.
	 * This handles dynamic video source switching based on viewport size.
	 *
	 * @since 3.0.0
	 * @param bool $force Enqueue even when `has_block()` finds nothing, for
	 *                    markup rendered from outside the post content.
	 * @return void
	 */
	public function enqueue_responsive_videos_script( $force = false ) {
		// Only enqueue on frontend requests (excluding AJAX and admin).
		if ( wp_doing_ajax() || is_admin() ) {
			return;
		}

		/*
		 * Only enqueue if container, slider, or modal blocks are present.
		 *
		 * `$force` exists for the markup `has_block()` cannot see. A popup lives
		 * in its own post and is pre-rendered into the page from
		 * `PopupBuilder::enqueue_popup_scripts()`, so this test is false for a
		 * page that shows one — and without the script a deferred video has
		 * nobody to attach its source and start it. A popup with an image at
		 * Desktop and a video at Mobile therefore rendered the `<video>` with
		 * the right `data-responsive-videos`, and it sat at `readyState: 0`
		 * for ever. The caller passes true only when the pre-rendered markup
		 * actually carries a per-device video.
		 */
		if ( ! $force && ! has_block( 'spectra/container' ) && ! has_block( 'spectra/slider' ) && ! has_block( 'spectra/modal' ) && ! has_block( 'spectra/slider-child' ) ) {
			return;
		}

		// The dependency must exist before it is named: this runs from any
		// enqueue path (including block renders outside `wp_enqueue_scripts`),
		// and WordPress 6.9.1+ flags a dependency that is not registered.
		$this->register_viewport_bands_script();

		wp_enqueue_script(
			'spectra-responsive-videos',
			SPECTRA_BLOCKS_URL . 'assets/js/responsive-videos.js',
			array( self::VIEWPORT_BANDS_HANDLE ),
			filemtime( SPECTRA_BLOCKS_DIR . 'assets/js/responsive-videos.js' ),
			true
		);
	}

	/**
	 * Script handle that carries the resolved viewport bands to the front end.
	 *
	 * @since 1.0.7
	 * @var string
	 */
	public const VIEWPORT_BANDS_HANDLE = 'spectra-blocks-viewport-bands';

	/**
	 * The viewport bands as media-query strings, keyed for scripts.
	 *
	 * Every per-device decision the front end makes in CSS comes out of
	 * `resolve_viewport_bands()` — the banded attributes, the `uag-hide-*`
	 * classes, orientation reverse. A script that decides "which device is
	 * this" from `window.innerWidth` against its own numbers can disagree with
	 * that CSS wherever the two sets of numbers differ, and on WordPress 7.1
	 * they do differ by default: core resolves the bands (480/782 unless a
	 * theme or #797 declares otherwise) while the scripts carried Spectra's
	 * historical 768/1024. A popup marked "hide on tablet" was hidden by CSS at
	 * 481–782px and by its own script at 769–1024px.
	 *
	 * This hands scripts the same queries the CSS uses, so `matchMedia()` on
	 * them agrees with the stylesheet by construction — whatever the numbers
	 * are, wherever they come from.
	 *
	 * @since 1.0.7
	 * @return array{desktop: string, tablet: string, mobile: string} Media queries without the `@media` prefix.
	 */
	public function get_viewport_bands_for_script() {
		$bands = $this->get_device_media_queries();

		return array(
			'desktop' => (string) ( $bands['@desktop'] ?? '' ),
			'tablet'  => (string) ( $bands['@tablet'] ?? '' ),
			'mobile'  => (string) ( $bands['@mobile'] ?? '' ),
		);
	}

	/**
	 * Register the inline script that publishes the bands.
	 *
	 * Registered, not enqueued: it prints only when a script that needs it
	 * lists it as a dependency (the responsive video handlers here and in Pro,
	 * Pro's motion effects), or when a renderer enqueues it explicitly (Pro's
	 * popup builder). It has no file of its own; the data is the script.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public function register_viewport_bands_script() {
		if ( wp_script_is( self::VIEWPORT_BANDS_HANDLE, 'registered' ) ) {
			return;
		}

		wp_register_script( self::VIEWPORT_BANDS_HANDLE, false, array(), SPECTRA_BLOCKS_VER, true );

		wp_add_inline_script(
			self::VIEWPORT_BANDS_HANDLE,
			'window.spectraBlocksViewportBands = ' . wp_json_encode( $this->get_viewport_bands_for_script() ) . ';',
			'before'
		);
	}

	/**
	 * Enqueues responsive control injection assets for block editor.
	 *
	 * Enqueues the CSS needed for the responsive control injection system
	 * that adds device buttons to existing Gutenberg controls.
	 *
	 * @since 3.0.0.1
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'spectra-blocks-extensions-responsive-controls' );
	}

	/**
	 * `render_block_data` bridge into `hydrate_store_from_style()`.
	 *
	 * Runs right after the legacy rename, so the store every later filter and
	 * the CSS generator read is already canonical and hydrated from `style`.
	 *
	 * @since 1.0.7
	 * @param array<string, mixed> $block Block data.
	 * @return array<string, mixed> Block data with a hydrated store.
	 */
	public function build_store_from_style( $block ) {
		// Same gate the rest of the pipeline uses. Without it every core block on
		// the page would have a Spectra store built onto its attributes — wasted
		// work, and an attribute other extensions can see that means nothing.
		if ( ! $this->should_apply_responsive_controls( $block ) ) {
			return $block;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		// Runs for every block, not only those carrying a store: a block authored
		// after the move to `style` has no `responsiveControls` at all.
		$block_name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';

		$this->hydrate_store_from_style( $attrs, $block_name );

		$block['attrs'] = $attrs;

		return $block;
	}


	/**
	 * Every key that can appear directly inside a `style` state object.
	 *
	 * The union of the shared style groups and the top-level responsive
	 * attributes. Iterating only `$style_responsive_keys` never visited
	 * `fontSize`, `fontFamily` or `borderColor`, so they were neither read from
	 * nor written to a state.
	 *
	 * @since 1.0.7
	 * @return array<string> Keys a state object may hold.
	 */
	private function state_keys() {
		return array_values( array_unique( array_merge( $this->style_responsive_keys, self::BUCKET_TOP_LEVEL_STYLE_KEYS ) ) );
	}

	/**
	 * Build the internal responsive store from the block's `style` attribute.
	 *
	 * `style` is where per-breakpoint values now live. It is WordPress core's
	 * own attribute, registered on every block through block supports, and on
	 * 7.1+ core already understands `style['@tablet']` / `style['@mobile']`.
	 * Keeping one object means the editor writes to one place, core renders
	 * what it recognises, and Spectra renders the rest from the same source —
	 * instead of two stores that have to be reconciled on every read.
	 *
	 * Core ignores keys it does not know rather than rejecting them, so
	 * Spectra-only values (`size`, `gap`, the container's overlay family, …)
	 * ride along inside the same state objects. Verified on 7.1-RC4: such keys
	 * survive parse, re-serialise byte-identically, and persist through
	 * `wp_insert_post`.
	 *
	 * `responsiveControls` is still read, because every post saved before this
	 * change has one. It is a legacy input only — `style` wins wherever both
	 * describe the same property, since that is what the current editor wrote.
	 * The store is rebuilt rather than removed so the CSS generator, the block
	 * controllers and the Style Guide bridge keep their existing shape; the
	 * change is to where the data comes from, not how it is generated.
	 *
	 * @since 1.0.7
	 * @param array<string, mixed> $attrs      Block attributes, modified by reference.
	 * @param string               $block_name The block name.
	 * @return void
	 */
	private function hydrate_store_from_style( &$attrs, $block_name ) {
		$style = isset( $attrs['style'] ) && is_array( $attrs['style'] ) ? $attrs['style'] : array();

		if ( empty( $style ) ) {
			return;
		}

		$store = isset( $attrs['responsiveControls'] ) && is_array( $attrs['responsiveControls'] )
			? $attrs['responsiveControls']
			: array();

		/*
		 * Snapshot the store before hydration. On content saved before the move
		 * to `style`, the root of `style` is a scratch surface — a projection of
		 * whichever device was selected at the last save — so a group this store
		 * holds at ANY breakpoint marks the root's copy of that group as scratch,
		 * never as an authored base value. A mobile-only legacy padding would
		 * otherwise be promoted to the base layer and render on desktop. Content
		 * re-saved by the current editor arrives with an empty store, so every
		 * root group there fills the base as authored.
		 */
		$authored = $store;

		// Flat, block-specific keys (`size`, `gap`, `minWidth`, …) sit directly
		// on the state object; the shared style groups nest under it by name.
		$flat_keys = ResponsiveAttributeCSS::get_responsive_attributes( $block_name );

		foreach ( self::DEVICE_TO_STYLE_STATE as $device => $state ) {
			// An empty state key means the base layer, which is the root of `style`.
			if ( '' === $state ) {
				$source = $style;
			} else {
				$source = isset( $style[ $state ] ) && is_array( $style[ $state ] ) ? $style[ $state ] : array();
			}

			$bucket = isset( $store[ $device ] ) && is_array( $store[ $device ] ) ? $store[ $device ] : array();

			// A malformed store can carry `style` as a string; writing a group
			// under a string offset is fatal. Drop it rather than crash.
			if ( isset( $bucket['style'] ) && ! is_array( $bucket['style'] ) ) {
				unset( $bucket['style'] );
			}

			/*
			 * A `@tablet` / `@mobile` state outranks whatever the store already
			 * holds. The base is different: its source is the ROOT of `style`, and
			 * on content saved before the move the root is a scratch surface holding
			 * whichever device happened to be selected — frequently the mobile
			 * value. There it must fill gaps only, so a legacy `lg` bucket still
			 * wins. On content authored since, the bucket is empty at this point and
			 * filling gaps yields the root, so the two cases converge.
			 */
			$fill_gaps_only = '' === $state;

			/*
			 * Below 7.1 a state gets the same treatment — see `Pre71\StorePrecedence`,
			 * which explains why and is removable with the rest of that folder. The
			 * guard is what makes it removable; on 7.1 the call answers false.
			 */
			if ( ! $fill_gaps_only && class_exists( StorePrecedence::class ) ) {
				$fill_gaps_only = StorePrecedence::state_fills_gaps_only();
			}

			foreach ( $this->state_keys() as $group ) {
				if ( ! isset( $source[ $group ] ) ) {
					continue;
				}

				if ( 'layout' === $group ) {
					$this->hydrate_layout_group( $bucket, $source[ $group ], $fill_gaps_only, $authored );
					continue;
				}

				if ( in_array( $group, self::BUCKET_TOP_LEVEL_STYLE_KEYS, true ) ) {
					if ( ! $fill_gaps_only || ! $this->store_has_key( $authored, $group ) ) {
						$bucket[ $group ] = $source[ $group ];
					}
					continue;
				}

				if ( ! $fill_gaps_only || ! $this->store_has_key( $authored, $group, true ) ) {
					$bucket['style'][ $group ] = $source[ $group ];
				}
			}

			foreach ( $flat_keys as $key ) {
				if ( ! isset( $source[ $key ] ) ) {
					continue;
				}

				if ( ! $fill_gaps_only || ! $this->store_has_key( $authored, $key ) ) {
					$bucket[ $key ] = $source[ $key ];
				}
			}

			/*
			 * `layout` is core's own block attribute, so it sits beside `style`
			 * rather than inside it and the loops above cannot see it. It is the
			 * last resort for the base layer: a block whose layout was only ever
			 * written there — by core's Layout panel — has nowhere else to recover
			 * it from, and without this it silently falls back to its default.
			 */
			if ( 'base' === $device && ! isset( $bucket['layout'] ) && isset( $attrs['layout'] ) && is_array( $attrs['layout'] ) ) {
				$bucket['layout'] = $attrs['layout'];
			}

			if ( ! empty( $bucket ) ) {
				$store[ $device ] = $bucket;
			}
		}

		if ( ! empty( $store ) ) {
			$attrs['responsiveControls'] = $store;
		}
	}

	/**
	 * Fold one state's `layout` object into the store, split the way core is.
	 *
	 * Core stores two different things in `style[state].layout`: the block's
	 * own container layout, and the child keys describing how the block sits
	 * inside its PARENT's flex or grid container. The store separates them —
	 * container keys at the bucket's top level for `generate_layout_css()`,
	 * child keys under `style.layout` for `generate_style_layout_css()`.
	 * Dumping the object whole into the container slot dropped every child
	 * value (the `flex-grow` of a "Grow" button, grid column/row spans) and
	 * could shadow the `attrs['layout']` recovery with an object holding no
	 * `type` at all, degrading a flex container to flow layout.
	 *
	 * @since 1.0.7
	 * @param array<string, mixed> $bucket         Device bucket, modified by reference.
	 * @param mixed                $layout         The state's `layout` value.
	 * @param bool                 $fill_gaps_only Whether the store outranks this source.
	 * @param array<string, mixed> $authored       The store as it was before hydration.
	 * @return void
	 */
	private function hydrate_layout_group( &$bucket, $layout, $fill_gaps_only, $authored ) {
		if ( ! is_array( $layout ) || empty( $layout ) ) {
			return;
		}

		$child     = array_intersect_key( $layout, array_flip( self::CORE_CHILD_LAYOUT_KEYS ) );
		$container = array_diff_key( $layout, $child );

		if ( ! empty( $container ) && ( ! $fill_gaps_only || ! $this->store_has_key( $authored, 'layout' ) ) ) {
			$bucket['layout'] = $container;
		}

		if ( ! empty( $child ) && ( ! $fill_gaps_only || ! $this->store_has_key( $authored, 'layout', true ) ) ) {
			$style_bucket           = isset( $bucket['style'] ) && is_array( $bucket['style'] ) ? $bucket['style'] : array();
			$style_bucket['layout'] = $child;
			$bucket['style']        = $style_bucket;
		}
	}

	/**
	 * Whether any device bucket of the pre-hydration store carries a key.
	 *
	 * Used when the base layer is filled from the root of `style`: a key the
	 * legacy store holds at ANY breakpoint marks the root's copy as a scratch
	 * projection rather than an authored base value, so the root must not be
	 * promoted. See the snapshot note in `hydrate_store_from_style()`.
	 *
	 * @since 1.0.7
	 * @param array<string, mixed> $store  The store as it was before hydration.
	 * @param string               $key    Group or flat key to look for.
	 * @param bool                 $nested Whether the key nests under the bucket's `style`.
	 * @return bool True when any device bucket holds the key.
	 */
	private function store_has_key( $store, $key, $nested = false ) {
		foreach ( array_keys( self::DEVICE_TO_STYLE_STATE ) as $device ) {
			$bucket = isset( $store[ $device ] ) && is_array( $store[ $device ] ) ? $store[ $device ] : array();

			if ( $nested ) {
				$style = isset( $bucket['style'] ) && is_array( $bucket['style'] ) ? $bucket['style'] : array();

				if ( isset( $style[ $key ] ) ) {
					return true;
				}
				continue;
			}

			if ( isset( $bucket[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Process responsive attributes for a block.
	 *
	 * This is the main entry point for the responsive controls system.
	 * It performs the following operations:
	 * 1. Checks if the block should be processed
	 * 2. Ensures the block has a unique ID
	 * 3. Removes conflicting core attributes
	 * 4. Generates responsive CSS
	 * 5. Adds the CSS to the responsive stylesheet
	 *
	 * @since 3.0.0
	 *
	 * @param array $block Block data from render_block_data filter.
	 * @return array Modified block data with processed responsive attributes.
	 */
	public function process_responsive_attributes( $block ) {
		// Skip processing if this isn't a Spectra block with responsive controls.
		if ( ! $this->should_apply_responsive_controls( $block ) ) {
			return $block;
		}

		// Get block attributes or initialize empty array if none exist.
		$attrs                    = $block['attrs'] ?? array();
		$responsive_controls      = $attrs['responsiveControls'] ?? array();
		$responsive_controls_base = $responsive_controls['base'] ?? array();

		// If no responsive controls exist yet, map standard attributes to responsive format.
		if ( empty( $responsive_controls ) ) {
			foreach ( array_merge( array( 'style' ), $this->responsive_keys ) as $key ) {
				// Copy each responsive key from block attributes to responsive controls.
				if ( isset( $attrs[ $key ] ) ) {
					// For style attribute, only copy specific responsive properties.
					if ( 'style' === $key && is_array( $attrs[ $key ] ) ) {

						// If responsive controls (Lg) style is empty, copy all properties.
						if ( empty( $responsive_controls_base['style'] ) ) {
							$responsive_controls_base['style'] = $attrs[ $key ];
						} else {
							$responsive_controls_base[ $key ] = array();
							foreach ( $this->style_responsive_keys as $style_key ) {
								if ( isset( $attrs[ $key ][ $style_key ] ) ) {
									// For border property, copy all properties dynamically.
									if ( 'border' === $style_key && is_array( $attrs[ $key ][ $style_key ] ) ) {
										$responsive_controls_base[ $key ][ $style_key ] = $attrs[ $key ][ $style_key ];
									} else {
										$responsive_controls_base[ $key ][ $style_key ] = $attrs[ $key ][ $style_key ];
									}
								}
							}
						}
					} else {
						$responsive_controls_base[ $key ] = $attrs[ $key ];
					}
				}
			}

			// Map block-specific attributes to responsive format.
			$block_specific_attrs = ResponsiveAttributeCSS::get_responsive_attributes( $block['blockName'] );
			foreach ( $block_specific_attrs as $attr ) {
				if ( isset( $attrs[ $attr ] ) ) {
					$responsive_controls_base[ $attr ] = $attrs[ $attr ];
				}
			}
		}

		/**
		 * Apply filters to modify the default layout for responsive controls.
		 *
		 * @since 3.0.0
		 *
		 * @param array $this->blocks_default_layout Default layout configurations for blocks.
		 * @return array Modified default layout configurations.
		 */
		$this->blocks_default_layout = apply_filters( 'spectra_blocks_responsive_default_layout', $this->blocks_default_layout );

		// `layout: { type: "default" }` is an explicit opt-out from per-block layout
		// CSS — set by callers (e.g. SaaS pipelines) that want className-driven layout
		// to be the sole source of truth, with no plugin-emitted flex/grid defaults
		// competing for specificity. Block-UI users never write `type: "default"` via
		// the layout panel (the panel writes `flex` / `grid` / `constrained`), so this
		// branch only triggers for callers that have explicitly opted out.
		//
		// @since 1.0.0
		$top_level_layout    = $block['attrs']['layout'] ?? array();
		$is_explicit_default = isset( $top_level_layout['type'] ) && 'default' === $top_level_layout['type'];

		// If no layout is defined, use the default layout for the block.
		if (
			empty( $responsive_controls_base['layout'] )
			&& ! $is_explicit_default
			&& isset( $this->blocks_default_layout[ $block['blockName'] ] )
		) {
			$responsive_controls_base['layout'] = $this->blocks_default_layout[ $block['blockName'] ]['layout'];
		}

		// Preserve the SaaS opt-out marker into the store's base layer so it survives
		// `remove_conflicting_core_attributes()` (which strips `layout` from $attrs because
		// it's listed in $core_attributes). Without this carry-over the downstream
		// `generate_responsive_css()` short-circuit can't see the marker and the WP-core
		// `wp_get_layout_style()` block-gap (~1em margin between siblings) leaks through.
		if ( $is_explicit_default && empty( $responsive_controls_base['layout'] ) ) {
			$responsive_controls_base['layout'] = $top_level_layout;
		}

		// Correctly assign updated lg-specific controls back into the block.
		$block['attrs']['responsiveControls']['base'] = $responsive_controls_base;

		// Remove any core attributes that would conflict with our responsive controls.
		$this->remove_conflicting_core_attributes( $block['attrs'], $block['blockName'] ?? '' );

		return $block;
	}

	/**
	 * Backward compatibility for values a block keeps in its ROOT attributes.
	 *
	 * Content authored before the per-device store existed holds its values as
	 * plain root attributes — `{"sliderHeight":"500px"}` — with no
	 * `responsiveControls` and no `style` viewport states. Nothing rewrites post
	 * content on upgrade (see `LegacyStore`: "content is normalised as it is
	 * read rather than migrated in the database"), so the render path has to
	 * read that shape, and `get_device_attributes()` resolves each device from
	 * the store alone. A root-only value therefore never reached the CSS
	 * pipeline and the generator fell back to the attribute default: a slider
	 * authored at 500px rendered `height: auto`, arrows moved from 30px to 1px,
	 * a Content block's text shadow produced no rule at all. Measured against
	 * 1.0.6, which read root attributes directly.
	 *
	 * This used to be driven by a hand-written list of four blocks and six
	 * attributes, extended one bug report at a time — which is why a slider's
	 * `background` survived while `sliderHeight` on the same block did not. The
	 * list is now the block's OWN declaration: every attribute it registers with
	 * `ResponsiveAttributeCSS` is bridged, so all 95 unbridged keys across 22
	 * blocks are covered by one rule instead of 22 map entries. The same rule
	 * already existed for the preview path in
	 * `convert_attrs_to_responsive_controls()`; this is the render path catching
	 * up with it.
	 *
	 * The per-ATTRIBUTE guard below is what makes it safe, and it must stay per
	 * attribute rather than "only when the store is empty" — the shape the
	 * preview helper uses. On 7.1 the root attribute is the editor's routing
	 * scratch, holding whatever device was edited last, so promoting it when
	 * some viewport already authored the key would write a mobile keystroke into
	 * the desktop layer. Asking per key means a viewport override always wins
	 * and the root is consulted only where no device has an opinion.
	 *
	 * Ordering matters and is already correct: this runs on `render_block_data`
	 * at priority 5, after `LegacyStore::normalize_device_keys()` at 3 and
	 * `build_store_from_style()` at 4, so the guard sees the `lg`/`md`/`sm`
	 * buckets and core's viewport states before it decides.
	 *
	 * @since 1.0.0
	 * @param array $block Block data.
	 * @return array Modified block data.
	 */
	public function backward_compatibility_block_attributes( $block ) {
		$block_name = $block['blockName'] ?? '';

		if ( '' === $block_name ) {
			return $block;
		}

		/*
		 * What the block itself says is responsive, plus the few keys its
		 * renderer reads without declaring. `get_responsive_attributes()` runs
		 * through the `spectra_blocks_responsive_attr_definitions` filter, so a
		 * Pro block's attributes are bridged by the same pass.
		 */
		$attributes_to_maintain = array_unique(
			array_merge(
				ResponsiveAttributeCSS::get_responsive_attributes( $block_name ),
				$this->backward_compatibility_attributes[ $block_name ] ?? array()
			)
		);

		if ( empty( $attributes_to_maintain ) ) {
			return $block;
		}

		$attrs    = $block['attrs'] ?? array();
		$modified = false;

		foreach ( $attributes_to_maintain as $attr ) {
			// Only map if root attribute exists.
			if ( ! isset( $attrs[ $attr ] ) ) {
				continue;
			}

			// Check if this attribute is already defined in ANY responsive device.
			$exists_responsively = false;
			foreach ( array( '@mobile', '@tablet', 'base' ) as $device ) {
				if ( isset( $attrs['responsiveControls'][ $device ][ $attr ] ) ) {
					$exists_responsively = true;
					break;
				}
			}

			// If root exists but it's not used in any responsive device, map it to LG (Desktop).
			if ( ! $exists_responsively ) {
				if ( ! isset( $attrs['responsiveControls'] ) ) {
					$attrs['responsiveControls'] = array();
				}
				if ( ! isset( $attrs['responsiveControls']['base'] ) ) {
					$attrs['responsiveControls']['base'] = array();
				}
				$attrs['responsiveControls']['base'][ $attr ] = $attrs[ $attr ];
				$modified                                     = true;
			}
		}

		if ( $modified ) {
			$block['attrs'] = $attrs;
		}

		return $block;
	}

	/**
	 * Processes block content and adds responsive styles.
	 *
	 * This method:
	 * 1. Injects a data-spectra-id attribute into the block's HTML for CSS targeting
	 * 2. Generates responsive CSS for all breakpoints
	 * 3. Adds the generated CSS to the stylesheet
	 * 4. Returns the modified block content
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content The rendered block HTML content.
	 * @param array  $block The complete block data.
	 * @return string The modified block content with ID attribute and responsive styles.
	 */
	public function process_block_and_add_responsive_styles( $block_content, $block ) {
		// Skip processing if this isn't a Spectra block with responsive controls.
		if ( ! $this->should_apply_responsive_controls( $block ) ) {
			return $block_content;
		}

		// Get block attributes or return early if none exist.
		$attrs = $block['attrs'] ?? array();
		if ( empty( $attrs ) ) {
			return $block_content;
		}

		// Ensure the block has a unique ID for CSS targeting.
		$this->ensure_block_has_id( $attrs );

		$spectra_id          = Core::sanitize_spectra_id( $attrs['spectraId'] ?? '' );
		$responsive_controls = $attrs['responsiveControls'] ?? array();
		$block_name          = $block['blockName'] ?? '';

		// Skip processing if no ID or no responsive controls.
		if ( empty( $spectra_id ) || empty( $responsive_controls ) ) {
			return $block_content;
		}

		// Use WordPress HTML Tag Processor to safely modify the HTML.
		$processor = new WP_HTML_Tag_Processor( $block_content );

		// Find the first HTML tag and add our custom data attribute.
		if ( $processor->next_tag() ) {
			$processor->set_attribute(
				'data-spectra-id',
				esc_attr( $spectra_id )
			);

			// Get the updated HTML with our attribute added.
			$block_content = $processor->get_updated_html();
		}

		if ( 'core/image' === $block_name && ! empty( $responsive_controls ) ) {
			/*
			 * The figure's inline MARGIN is core's base output for a property
			 * this extension owns, so it has to go on every version.
			 *
			 * `remove_conflicting_core_attributes()` strips `spacing` out of
			 * the viewport states precisely so core's states renderer will not
			 * re-emit it, which leaves core rendering NO banded margin at all —
			 * verified on 7.1, where the only banded margin rules on the page
			 * came from the theme. This extension emits all three bands
			 * instead, and an inline declaration outranks every one of them
			 * whatever their specificity, so the desktop value applied at every
			 * width. `core/image` is a static block, so its inline style is
			 * baked into the saved markup and stripping attributes on
			 * `render_block_data` cannot reach it — only the rendered HTML can.
			 *
			 * This ran below 7.1 already, through the dimensions gate below;
			 * that gate is false on 7.1, which is how the margin strip came to
			 * be switched off there along with it.
			 */
			$block_content = $this->remove_core_image_inline_spacing( $block_content );

			/*
			 * The img's inline style is core's DIMENSION output — width,
			 * height, aspect ratio, object fit. Where core renders viewport
			 * states it bands those itself with `!important` and this extension
			 * paints none of them, so the base it wrote must stay. See
			 * `paints_core_image_dimensions()`.
			 */
			if ( $this->paints_core_image_dimensions() ) {
				$block_content = $this->remove_core_image_inline_dimensions( $block_content );
			}
		}

		// Only generate and add inline CSS once per unique spectraId.
		if ( ! isset( $this->inline_css_added[ $spectra_id ] ) ) {
			$responsive_css = $this->get_cached_responsive_css( $spectra_id, $responsive_controls, $block_name, $attrs );

			// Generate orientation reverse CSS for container blocks.
			$orientation_reverse_css = '';
			if ( 'spectra/container' === $block_name ) {
				$orientation_reverse_css = $this->generate_orientation_reverse_css( $spectra_id, $attrs );
			}

			// Combine all CSS.
			$combined_css = trim( $responsive_css . "\n" . $orientation_reverse_css );

			// If CSS was generated, add it to the stylesheet.
			if ( ! empty( $combined_css ) ) {
				// Enqueue the stylesheet if not already done.
				wp_enqueue_style( $this->style_handle );

				// Add our generated CSS as inline styles.
				wp_add_inline_style( $this->style_handle, self::sanitize_inline_css( $combined_css ) );

				// Mark as added to avoid duplicates.
				$this->inline_css_added[ $spectra_id ] = true;
			}
		}

		// Return the modified block content with our data attribute.
		return $block_content;
	}

	/**
	 * Conditionally skips layout support injection for Spectra blocks only.
	 *
	 * Since we removed the core layout support filter globally,
	 * we need to re-apply it for non-Spectra blocks to maintain
	 * compatibility with other plugins and themes.
	 *
	 * @since 3.0.0
	 * @param string $block_content The rendered block HTML.
	 * @param array  $block         The block data.
	 * @return string Filtered block HTML with appropriate layout support.
	 */
	public function maybe_skip_layout_support( $block_content, $block ) {
		// Skip layout support for Spectra blocks — with one exception.
		//
		// `spectra/container` reads `layout.type` and `layout.orientation`
		// from its `$attributes['layout']` object, but the plugin's own code
		// never emits CSS for `layout.flexWrap`, `layout.justifyContent`, or
		// `layout.verticalAlignment` — the exact sub-attributes exposed by
		// the inspector's Layout panel and set by the E2E regression tests.
		// WP core's `wp_render_layout_support_flag` DOES emit that CSS
		// correctly when the block declares `supports.layout` (which
		// container's block.json does). Let it run for container so those
		// attributes actually affect the frontend.
		//
		// All other Spectra blocks continue to skip — their layout classes
		// are either irrelevant or managed by their own controller/view
		// pipelines.
		$block_name = $block['blockName'] ?? '';
		if (
			Core::is_spectra_block( array( 'name' => $block_name ) )
			&& 'spectra/container' !== $block_name
		) {
			return $block_content;
		}

		// Apply WordPress default layout rendering (spectra/container + core + other blocks).
		return wp_render_layout_support_flag( $block_content, $block );
	}

	/**
	 * Ensure all Spectra blocks have unique spectraIds after post save.
	 *
	 * This method runs after a post is saved and checks for duplicate
	 * spectraIds across all blocks. If duplicates are found, it generates
	 * new unique IDs and updates the post content.
	 *
	 * @since 3.0.0
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post object.
	 * @return void
	 */
	public function ensure_unique_spectra_ids( $post_id, $post ) {
		// Skip autosaves, revisions, and posts without content.
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || empty( $post->post_content ) ) {
			return;
		}

		// Skip during WordPress customizer saves to prevent JSON encoding conflicts.
		// The customizer has its own save process and calling wp_update_post during
		// it can cause wp_send_json_error with empty messages.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification already handled by WordPress before save_post hook.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_POST['action'] ) && 'customize_save' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) {
			return;
		}

		// Skip if no Spectra blocks in content.
		if ( ! has_blocks( $post->post_content ) || strpos( $post->post_content, 'spectraId' ) === false ) {
			return;
		}

		// Parse blocks from content.
		$blocks = parse_blocks( $post->post_content );
		if ( empty( $blocks ) ) {
			return;
		}

		// Track seen IDs and process blocks.
		$seen_ids         = array();
		$modified         = false;
		$processed_blocks = $this->process_blocks_for_unique_ids( $blocks, $seen_ids, $modified );

		// If any IDs were modified, update the post.
		if ( $modified ) {
			// Remove the action to prevent infinite loop.
			remove_action( 'save_post', array( $this, 'ensure_unique_spectra_ids' ), 20 );

			// Update post content with new block structure.
			// Instead of using serialize_blocks which can strip backslashes,
			// we'll use wp_slash to ensure proper escaping for the database.
			$serialized_content = serialize_blocks( $processed_blocks );

			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_slash( $serialized_content ),
				)
			);

			// Re-add the action.
			add_action( 'save_post', array( $this, 'ensure_unique_spectra_ids' ), 20, 2 );
		}
	}

	/**
	 * Determines if responsive controls should be applied to a block.
	 *
	 * This function checks if a block should have responsive controls applied
	 * based on its name. It verifies that the block is not in the excluded list
	 * and either has an allowed prefix or is explicitly supported.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block The block to check.
	 * @return bool Whether responsive controls should be applied.
	 */
	public function should_apply_responsive_controls( $block ) {
		// Skip if block has no name.
		if ( ! isset( $block['blockName'] ) || empty( $block['blockName'] ) ) {
			return false;
		}

		$block_name = $block['blockName'];

		/**
		 * Filters the blocks excluded from responsive controls.
		 *
		 * Mirror of the JS `spectra.excludedResponsiveControlsBlocks` filter —
		 * a block added there gets editor UI but no front-end CSS unless it is
		 * excluded/included here as well.
		 *
		 * @since 1.0.7
		 * @param array<string> $excluded_blocks Excluded block names.
		 */
		$excluded_blocks = apply_filters( 'spectra_blocks_responsive_excluded_blocks', $this->excluded_blocks );

		// Skip excluded blocks.
		if ( is_array( $excluded_blocks ) && in_array( $block_name, $excluded_blocks, true ) ) {
			return false;
		}

		// Check if block has allowed prefix.
		foreach ( $this->allowed_prefixes as $prefix ) {
			if ( strpos( $block_name, $prefix ) === 0 ) {
				return true;
			}
		}

		/**
		 * Filters the blocks explicitly supported by responsive controls.
		 *
		 * Mirror of the JS `spectra.supportedResponsiveControlsBlocks` filter.
		 *
		 * @since 1.0.7
		 * @param array<string> $supported_blocks Supported block names.
		 */
		$supported_blocks = apply_filters( 'spectra_blocks_responsive_supported_blocks', $this->supported_blocks );

		// Check if block is explicitly supported.
		return is_array( $supported_blocks ) && in_array( $block_name, $supported_blocks, true );
	}

	/**
	 * Prepare raw block attributes for CSS generation outside the render pipeline.
	 *
	 * The render path normalises legacy device keys and folds core's viewport
	 * states into the store through `render_block_data`. Consumers that parse
	 * content directly — pattern previews, the comprehensive CSS generator —
	 * skip those filters, so their attributes may still carry `lg` / `md` /
	 * `sm` keys or hold their values only inside `style`. This applies the
	 * same two steps to a raw attribute array.
	 *
	 * @since 1.0.7
	 * @param array<string, mixed> $attrs      Block attributes.
	 * @param string               $block_name The block name.
	 * @return array<string, mixed> Attributes with a hydrated canonical store.
	 */
	public function normalize_render_attributes( $attrs, $block_name ) {
		if ( ! is_array( $attrs ) ) {
			return array();
		}

		// Guarded so deleting the Legacy folder needs no change here.
		if ( class_exists( LegacyStore::class ) ) {
			$block = LegacyStore::normalize_device_keys(
				array(
					'blockName' => $block_name,
					'attrs'     => $attrs,
				)
			);
			$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : $attrs;
		}

		$this->hydrate_store_from_style( $attrs, $block_name );

		return $attrs;
	}

	/**
	 * Remove conflicting core attributes to prevent style conflicts.
	 *
	 * Core WordPress attributes and style properties can conflict with
	 * our responsive controls. This method removes them from the block
	 * attributes to ensure our responsive styles take precedence.
	 *
	 * @since 3.0.0
	 * @param array  $attrs Block attributes passed by reference.
	 * @param string $block_name The name of the block.
	 * @return void
	 */
	private function remove_conflicting_core_attributes( &$attrs, $block_name ) {
		// Remove conflicting style properties if they exist.
		if ( isset( $attrs['style'] ) && is_array( $attrs['style'] ) ) {
			// Only remove style properties that are in the style_responsive_keys list.
			foreach ( $this->style_responsive_keys as $property ) {
				// Remove all properties including border (which is now handled dynamically).
				unset( $attrs['style'][ $property ] );
			}

			/*
			 * The viewport states hold the same groups, and on 7.1 core's own
			 * states renderer re-emits them with `!important` — duplicating
			 * every declaration this extension generates and defeating the
			 * deliberate differences: the left/right margins stripped from
			 * full and wide containers came back through core, and a flex
			 * block's textAlign (mapped to justify-content here) received a
			 * competing `text-align !important`. The store was hydrated from
			 * these states earlier in the pipeline, so the values are already
			 * captured; only the groups this extension renders are stripped —
			 * everything else (color, background, dimensions, layout) stays
			 * core's to render.
			 */
			foreach ( array( '@tablet', '@mobile' ) as $state ) {
				if ( ! isset( $attrs['style'][ $state ] ) || ! is_array( $attrs['style'][ $state ] ) ) {
					continue;
				}

				foreach ( $this->style_responsive_keys as $property ) {
					if ( 'layout' === $property ) {
						continue;
					}
					unset( $attrs['style'][ $state ][ $property ] );
				}

				if ( empty( $attrs['style'][ $state ] ) ) {
					unset( $attrs['style'][ $state ] );
				}
			}

			// Remove the entire style attribute if it's now empty.
			if ( empty( $attrs['style'] ) ) {
				unset( $attrs['style'] );
			}
		}

		// Remove conflicting individual attributes that are in the responsive_keys list.
		//
		// `spectra/container` is the exception: its Layout panel exposes
		// `layout.flexWrap`, `layout.justifyContent`, `layout.verticalAlignment`
		// but the plugin never emits CSS for them itself — WP core's
		// `wp_render_layout_support_flag` does, and it reads directly from
		// `$block['attrs']['layout']`. If we strip `layout` here (this method
		// fires on `render_block_data`, before `render_block`), the layout
		// support filter sees `attrs.layout` missing and falls back to the
		// block.json `supports.layout.default` — which is why E2E tests that
		// set `flexWrap: 'wrap'` still saw `flex-wrap: nowrap` on the frontend.
		// Keep `layout` intact for container so the filter has the real config.
		foreach ( $this->core_attributes as $attribute ) {
			if ( in_array( $attribute, $this->responsive_keys, true ) ) {
				if ( 'layout' === $attribute && 'spectra/container' === $block_name ) {
					continue;
				}
				unset( $attrs[ $attribute ] );
			}
		}

		// Remove block specific attributes.
		$block_specific_attrs = ResponsiveAttributeCSS::get_responsive_attributes( $block_name );
		foreach ( $block_specific_attrs as $attribute ) {
			unset( $attrs[ $attribute ] );
		}
	}

	/**
	 * Ensures block has a unique ID for CSS targeting.
	 *
	 * This method checks if the block has a `spectraId`. If not, it generates one
	 * using `wp_generate_uuid4()` and disables CSS cache to ensure new styles are applied.
	 *
	 * @since 3.0.0
	 * @param array $attrs Block attributes passed by reference.
	 * @return void
	 */
	private function ensure_block_has_id( &$attrs ) {
		if ( empty( $attrs['spectraId'] ) ) {
			$attrs['spectraId'] = 'spectra-' . wp_generate_uuid4();

			// Disable caching for dynamically generated IDs to ensure fresh CSS.
			if ( ! has_filter( 'spectra_blocks_enable_css_cache', '__return_false' ) ) {
				add_filter( 'spectra_blocks_enable_css_cache', '__return_false' );
			}
		}
	}

	/**
	 * Retrieve or generate cached responsive CSS for a block instance.
	 *
	 * Uses a stable cache key (`spectra_blocks_responsive_css_{$spectra_id}`) and stores
	 * a hash fingerprint of `responsive_controls` alongside the CSS. If the hash
	 * matches, the cached CSS is returned. Otherwise, new CSS is generated and cached.
	 *
	 * @since 3.0.0
	 *
	 * @param string $spectra_id Unique ID of the block instance.
	 * @param array  $responsive_controls Responsive controls data.
	 * @param string $block_name The name of the block.
	 * @param array  $attrs Block attributes.
	 * @return string Cached or newly generated CSS.
	 */
	private function get_cached_responsive_css( $spectra_id, $responsive_controls, $block_name, $attrs ) {
		/**
		 * Filter whether to enable caching for responsive CSS.
		 *
		 * @since 3.0.0
		 *
		 * @param bool $enable_cache Whether to enable caching. Default is true.
		 * @return bool True to enable caching, false to disable.
		 */
		// Cross-plugin extension points — spectra_ prefix is intentional; spectra-blocks-pro hooks into these filters.
		$enable_cache = apply_filters( 'spectra_blocks_enable_css_cache', true );

		// Generate cache key. Uses the `spectra_blocks_` option prefix (from
		// dev) and includes the generator fingerprint so code changes
		// invalidate cached output (the plugin version alone does not rotate
		// between dev builds).
		$cache_key = 'spectra_blocks_responsive_css_' . $spectra_id . '_' . SPECTRA_BLOCKS_VER . '_g' . self::CSS_GENERATOR_VERSION;

		/*
		 * Generate hash fingerprint including block name for proper cache
		 * invalidation. The bands are part of it because they are no longer
		 * fixed: switching to a theme that declares its own `settings.viewport`
		 * changes the media queries without touching a single attribute, and
		 * cached output would otherwise keep the previous theme's breakpoints.
		 */
		$cache_data    = array(
			'controls'   => $responsive_controls,
			'block_name' => $block_name,
			'bands'      => $this->get_media_queries(),
		);
		$controls_hash = md5( wp_json_encode( $cache_data ) );

		// Attempt to retrieve cached data.
		$cached = get_transient( $cache_key );

		// If cache is enabled and hash matches, return cached CSS.
		if ( $enable_cache && is_array( $cached ) && isset( $cached['hash'], $cached['css'] ) && $cached['hash'] === $controls_hash ) {
			return $cached['css'];
		}

		// Cache miss or outdated hash: regenerate CSS and store it.
		$css = $this->generate_responsive_css( $spectra_id, $responsive_controls, $block_name, $attrs );

		// Store the hash and CSS in the cache if caching is enabled and CSS is not empty.
		if ( $enable_cache && ! empty( $css ) ) {
			set_transient(
				$cache_key,
				array(
					'hash' => $controls_hash,
					'css'  => $css,
				),
				$this->cache_expiration
			);
		}

		return $css;
	}

	/**
	 * Generate responsive CSS for all breakpoints.
	 *
	 * Creates media queries for each device size and generates
	 * the appropriate CSS for each breakpoint based on the
	 * responsive controls data.
	 *
	 * @since 3.0.0
	 *
	 * @param string $spectra_id         The unique block instance ID for CSS targeting.
	 * @param array  $responsive_controls The responsive controls data from block attributes.
	 * @param string $block_name         The name of the block.
	 * @param array  $attrs              Block attributes.
	 * @return string The complete generated CSS for all breakpoints.
	 */
	public function generate_responsive_css( $spectra_id, $responsive_controls, $block_name, $attrs ) {
		$styles = array();

		// Create CSS selector using the block's unique ID for targeting.
		// Use higher specificity selector to override core styles without !important.
		// Using double class selector for increased specificity.

		// Handle core blocks differently - they use 'wp-block-{type}' not 'wp-block-core-{type}'.
		if ( strpos( $block_name, 'core/' ) === 0 ) {
			$block_type  = str_replace( 'core/', '', $block_name );
			$block_class = '.wp-block-' . $block_type;
		} else {
			$block_class = '.wp-block-' . str_replace( '/', '-', $block_name );
		}

		// Triple class: specificity 0,4,0 — beats the container child rule at 0,3,0.
		$selector = "{$block_class}{$block_class}{$block_class}[data-spectra-id='{$spectra_id}']";

		/**
		 * Filter to modify the responsive CSS selector for a block.
		 *
		 * This filter allows developers to customize the CSS selector used for
		 * responsive styling of Spectra blocks. The selector is used to target
		 * the specific block instance for CSS rules generation.
		 *
		 * The default selector has specificity 0,4,0 (3 classes + 1 attribute):
		 * `.wp-block-spectra-container.wp-block-spectra-container.wp-block-spectra-container[data-spectra-id='spectra-123']`
		 *
		 * Example usage:
		 * ```php
		 * add_filter( 'spectra_blocks_responsive_css_selector', function( $selector, $block_name, $spectra_id ) {
		 *     // Use lower specificity for theme compatibility
		 *     if ( 'spectra/container' === $block_name ) {
		 *         return ".wp-block-spectra-container[data-spectra-id='{$spectra_id}']";
		 *     }
		 *
		 *     // Target blocks within specific contexts
		 *     if ( is_single() && 'spectra/button' === $block_name ) {
		 *         return ".single-post {$selector}";
		 *     }
		 *
		 *     return $selector;
		 * }, 10, 3 );
		 * ```
		 *
		 * @since 3.0.0
		 *
		 * @param string $selector   The CSS selector for the block.
		 * @param string $block_name The name of the block (e.g., 'spectra/container').
		 * @param string $spectra_id The unique ID of the block instance.
		 * @return string Modified CSS selector.
		 */
		// Cross-plugin extension point — spectra_ prefix is intentional; spectra-blocks-pro hooks into this filter.
		$selector = apply_filters( 'spectra_blocks_responsive_css_selector', $selector, $block_name, $spectra_id );

		// Pattern preview context is signalled by the static flag, set by preview
		// functions before they enter the render pipeline. This avoids a costly
		// debug_backtrace() call on every block render.
		$is_pattern_preview = self::$is_pattern_preview;

		// Use appropriate base selector based on context.
		$base_selector = $is_pattern_preview ? '.st-block-container' : 'body';

		// For pattern preview, use more robust selectors that don't rely on complex nesting.
		if ( $is_pattern_preview ) {
			// Simplified selectors for pattern preview context.
			$layout_specificity_selector     = "{$block_class}[data-spectra-id='{$spectra_id}']";
			$background_specificity_selector = "{$block_class}[data-spectra-id='{$spectra_id}']";
			$child_reset_selector            = "{$block_class}[data-spectra-id='{$spectra_id}']";

			// Override main selector for pattern preview to be less specific.
			$selector = "{$block_class}[data-spectra-id='{$spectra_id}']";

			/**
			 * Filter to modify the responsive CSS selector for a block.
			 * Applied AFTER pattern preview detection to allow overriding preview selectors.
			 *
			 * @since 3.0.0
			 *
			 * @param string $selector   The CSS selector for the block.
			 * @param string $block_name The name of the block (e.g., 'spectra/container').
			 * @param string $spectra_id The unique ID of the block instance.
			 * @return string Modified CSS selector.
			 */
			// Cross-plugin extension point — spectra_ prefix is intentional; spectra-blocks-pro hooks into this filter.
			$selector = apply_filters( 'spectra_blocks_responsive_css_selector', $selector, $block_name, $spectra_id );

			// Special handling for slider-child in pattern preview.
			if ( 'spectra/slider-child' === $block_name ) {
				$layout_specificity_selector = "{$block_class}[data-spectra-id='{$spectra_id}'] .slide-content";
				$child_reset_selector        = "{$block_class}[data-spectra-id='{$spectra_id}'] .slide-content";
			}

			// Special handling for counter in pattern preview.
			if ( 'spectra/counter' === $block_name ) {
				$counter_main_preview        = "{$block_class}[data-spectra-id='{$spectra_id}']";
				$counter_content_preview     = "{$block_class}[data-spectra-id='{$spectra_id}'] .spectra-counter-content-wrapper";
				$layout_specificity_selector = $counter_main_preview . ', ' . $counter_content_preview;
				$child_reset_selector        = $counter_main_preview . ', ' . $counter_content_preview;
			}
		} else {
			// Layout CSS must out-rank the theme/core generic flex-layout defaults
			// — notably core's block-gap `:root :where(.is-layout-flex) { gap }` at
			// (0,1,0) — otherwise a block's own `gap` (and other layout props) are
			// silently clamped to the theme default. Keep `{$block_class}` OUTSIDE
			// `:where()` so it contributes a real class, and wrap only the
			// `[data-spectra-id]` (page-scope, no specificity). Result: (0,1,1) —
			// above the (0,1,0) core/theme defaults, still below the block-attribute
			// CSS at (0,4,0) so a block's own attribute CSS keeps winning.
			//
			// Trade-off: single-class utility atomics (Global Styles / Tailwind) at
			// (0,1,0) no longer override per-block layout; a utility that must win
			// should be authored at higher specificity.
			//
			// @since 1.0.0.
			$layout_specificity_selector = "{$base_selector} {$block_class}:where([data-spectra-id='{$spectra_id}'])";

			// Special handling for slider-child: target the inner .slide-content div for layout CSS.
			if ( 'spectra/slider-child' === $block_name ) {
				$layout_specificity_selector = "{$base_selector} :where({$block_class}[data-spectra-id='{$spectra_id}']) .slide-content";
			}

			// Special handling for counter: target both main block and content wrapper for layout CSS.
			if ( 'spectra/counter' === $block_name ) {
				// Target both the main block container and the content wrapper. Keep
				// `{$block_class}` OUTSIDE `:where()` for (0,1,1) — matching the main
				// layout selector above — so the counter's alignment (justifyContent)
				// and font-size out-rank the theme/core generic defaults instead of
				// being clamped at (0,0,1).
				$counter_main_selector       = "{$base_selector} {$block_class}:where([data-spectra-id='{$spectra_id}'])";
				$counter_content_selector    = "{$base_selector} {$block_class}:where([data-spectra-id='{$spectra_id}']) .spectra-counter-content-wrapper";
				$layout_specificity_selector = $counter_main_selector . ', ' . $counter_content_selector;
			}

			// Use lower specificity 0-1-0 selector for background CSS to allow Global Styles to override.
			$background_specificity_selector = "{$block_class}:where([data-spectra-id='{$spectra_id}'])";

			// Use 0-1-1 specificity to override Astra theme's 0-1-1 via CSS cascade order.
			// Using universal selector + :where() + attribute to work with any HTML tag (div, section, etc.).
			$child_reset_selector = "{$base_selector} *:where({$block_class})[data-spectra-id='{$spectra_id}']";

			// For slider-child, also update child reset selector to target content inside .slide-content.
			if ( 'spectra/slider-child' === $block_name ) {
				$child_reset_selector = "{$base_selector} *:where({$block_class})[data-spectra-id='{$spectra_id}'] .slide-content";
			}

			// For counter, also update child reset selector to target both main block and content wrapper.
			if ( 'spectra/counter' === $block_name ) {
				$counter_main_reset    = "{$base_selector} *:where({$block_class})[data-spectra-id='{$spectra_id}']";
				$counter_content_reset = "{$base_selector} *:where({$block_class})[data-spectra-id='{$spectra_id}'] .spectra-counter-content-wrapper";
				$child_reset_selector  = $counter_main_reset . ', ' . $counter_content_reset;
			}
		}

		// SaaS opt-out marker: when the top-level layout attr is explicitly { type: 'default' },
		// the author wants className-driven layout (Tailwind) and no plugin-emitted layout CSS.
		// Skipping the layout CSS pass prevents WP core's wp_get_layout_style() from injecting
		// `> * { margin-block-start: <block-gap> }` rules on every direct child, which otherwise
		// pushes sibling buttons/blocks down by ~1em and breaks intended Tailwind spacing.
		// Block-UI users set layout.type to 'flex'/'grid'/'constrained' — never 'default' — so
		// this short-circuit is a no-op for them.
		//
		// `attrs.layout` is stripped earlier by remove_conflicting_core_attributes(), so the
		// marker actually arrives via the store's base layout (preserved in
		// process_responsive_attributes()). We check both locations defensively.
		$top_level_layout  = $attrs['layout'] ?? ( $responsive_controls['base']['layout'] ?? array() );
		$is_default_layout = is_array( $top_level_layout ) && isset( $top_level_layout['type'] ) && 'default' === $top_level_layout['type'];

		// A full/wide-aligned container's horizontal position is owned by the
		// theme's alignment break-out (e.g. Astra's `.entry-content > .alignfull`
		// negative margins). Our per-block CSS carries a high-specificity selector,
		// so emitting a horizontal `margin` here (even the authored 0) overrides
		// that break-out and the section stops bleeding to the edges — it shifts in
		// and overflows. Drop only the LEFT/RIGHT margins for aligned containers so
		// the theme handles horizontal alignment; vertical margins are unaffected.
		// Specificity is left untouched, so Global Styles compatibility is intact.
		$block_align    = $attrs['align'] ?? '';
		$strip_x_margin = 'spectra/container' === $block_name && in_array( $block_align, array( 'full', 'wide' ), true );

		// Generate CSS for each device breakpoint (mobile, tablet, desktop).
		foreach ( $this->get_media_queries() as $device => $media ) {
			// Get compiled styles for this device with proper fallback.
			$device_styles = $this->get_device_styles( $responsive_controls, $device, $block_name );

			if ( $strip_x_margin && isset( $device_styles['spacing']['margin'] ) ) {
				$margin = $device_styles['spacing']['margin'];
				if ( is_array( $margin ) ) {
					unset( $margin['left'], $margin['right'] );
					if ( empty( $margin ) ) {
						unset( $device_styles['spacing']['margin'] );
					} else {
						$device_styles['spacing']['margin'] = $margin;
					}
				} else {
					// Shorthand string applies to all sides — keep only top/bottom.
					$device_styles['spacing']['margin'] = array(
						'top'    => $margin,
						'bottom' => $margin,
					);
				}
			}

			// Generate layout-specific CSS for this device.
			$layout_css = $is_default_layout
				? ''
				: $this->generate_layout_css( $responsive_controls, $device, $layout_specificity_selector, $child_reset_selector );

			// Generate style-layout-specific CSS for this device.
			$style_layout_css = $this->generate_style_layout_css( $responsive_controls, $device, $layout_specificity_selector );

			// Extract text alignment for special handling.
			$text_align = $device_styles['typography']['textAlign'] ?? '';

			// Extract justify-content for flex blocks.
			$justify_content = $device_styles['spectra_flex']['justifyContent'] ?? '';

			/*
			 * Use WordPress Style Engine to generate standard CSS.
			 *
			 * ONE chain, deliberately. The popup arm used to stand in a chain of its
			 * own a few lines above this one, and the `core/image` chain below
			 * reassigned `$css_array` unconditionally — so a popup's redirected
			 * spacing was computed and then thrown away, and its padding went to the
			 * block element after all. That element is the full-viewport overlay,
			 * which `style.scss` zeroes with `padding: 0 !important`, so the visible
			 * box fell back to the 32px default of
			 * `var( --spectra-popup-padding, 32px )` on every device.
			 */
			if ( 'spectra/popup-builder' === $block_name && isset( $device_styles['spacing'] ) ) {
				/*
				 * The popup's spacing belongs to the visible box, not to the overlay
				 * the block element is. Everything else stays on the block.
				 */
				$popup_spacing_styles = array( 'spacing' => $device_styles['spacing'] );
				$other_styles         = array_diff_key( $device_styles, array( 'spacing' => '' ) );

				$popup_spacing_css_array = wp_style_engine_get_styles(
					$popup_spacing_styles,
					array( 'selector' => $selector . ' .spectra-popup-builder__container' )
				);
				$popup_spacing_css       = is_array( $popup_spacing_css_array ) ? $popup_spacing_css_array['css'] ?? '' : '';

				$other_css = '';
				if ( ! empty( $other_styles ) ) {
					$other_css_array = wp_style_engine_get_styles(
						$other_styles,
						array( 'selector' => $selector )
					);
					$other_css       = is_array( $other_css_array ) ? $other_css_array['css'] ?? '' : '';
				}

				$combined_css = trim( $popup_spacing_css . ' ' . $other_css );
				$css_array    = ! empty( $combined_css ) ? array( 'css' => $combined_css ) : false;
			} elseif ( 'core/image' === $block_name && isset( $device_styles['border'] ) ) {
				// For core/image blocks, separate border styles from other styles.
				// Separate border styles and other styles.
				$border_styles = array( 'border' => $device_styles['border'] );
				$other_styles  = array_diff_key( $device_styles, array( 'border' => '' ) );

				// Generate border CSS with img selector.
				$border_css = '';
				if ( ! empty( $border_styles ) ) {
					$border_css_array = wp_style_engine_get_styles(
						$border_styles,
						array( 'selector' => $selector . ' img' )
					);
					$border_css       = is_array( $border_css_array ) ? $border_css_array['css'] ?? '' : '';
				}

				// Generate other styles CSS with figure selector.
				$other_css = '';
				if ( ! empty( $other_styles ) ) {
					$other_css_array = wp_style_engine_get_styles(
						$other_styles,
						array( 'selector' => $selector )
					);
					$other_css       = is_array( $other_css_array ) ? $other_css_array['css'] ?? '' : '';
				}

				// Combine both CSS strings.
				$combined_css = trim( $border_css . ' ' . $other_css );
				$css_array    = ! empty( $combined_css ) ? array( 'css' => $combined_css ) : false;
			} else {
				$css_array = wp_style_engine_get_styles(
					$device_styles,
					array( 'selector' => $selector )
				);
			}

			// Get the device-specific attributes for this breakpoint with fallback.
			$device_attrs = $this->get_device_attributes( $block_name, $responsive_controls, $device );

			// Generate block-specific attribute CSS using ResponsiveAttributeCSS.
			// Use low-specificity selector for background CSS, high-specificity for others.
			$attr_css = 'core/image' === $block_name && ! $this->paints_core_image_dimensions()
				? ''
				: ResponsiveAttributeCSS::generate_css( $block_name, $device_attrs, $selector, $background_specificity_selector, $attrs );

			// Add overflow handling for containers with border-radius and backgrounds.
			$overflow_css = '';
			if ( 'spectra/container' === $block_name || 'spectra/slider' === $block_name || 'spectra/slider-child' === $block_name ) {
				// Check if this device has border-radius.
				$has_border_radius = isset( $device_styles['border']['radius'] ) &&
									! empty( $device_styles['border']['radius'] );

				// Check if this device has video or image background.
				$has_background = false;
				if ( isset( $device_attrs['background'] ) && is_array( $device_attrs['background'] ) ) {
					$bg_type = $device_attrs['background']['type'] ?? '';
					if ( in_array( $bg_type, array( 'image', 'video' ), true ) ) {
						$has_background = true;
					}
				}

				// Apply overflow clip when has background AND border-radius (matching style.scss).
				// This ensures video/image backgrounds are properly clipped by border-radius.
				if ( $has_border_radius && $has_background ) {
					$overflow_css = "{$selector}{overflow:hidden;overflow:clip;}";
				}
			}

			/*
			 * The style engine resolves `shadow` into a `box-shadow`
			 * declaration but does not serialise it into its `css` string —
			 * the same omission it makes for `column-count`. Since `shadow` is
			 * one of the groups stripped from the block's attributes so only
			 * one renderer emits it, reading `css` alone dropped drop shadows
			 * entirely, at every breakpoint. Take the declaration the engine
			 * produced (it resolves `var:preset|shadow|…` for us) and emit the
			 * rule alongside the other raw declarations.
			 */
			$shadow_css = '';

			if ( isset( $device_styles['shadow'] ) && $this->has_actual_value( $device_styles['shadow'] ) ) {
				$shadow_styles = wp_style_engine_get_styles( array( 'shadow' => $device_styles['shadow'] ) );
				$box_shadow    = $shadow_styles['declarations']['box-shadow'] ?? '';

				if ( '' !== $box_shadow && ( ! isset( $css_array['css'] ) || false === strpos( (string) $css_array['css'], 'box-shadow' ) ) ) {
					$shadow_css = $selector . '{box-shadow:' . $box_shadow . ';}';
				}
			}

			// Build complete CSS for this device, including media queries.
			$css = $this->build_css_for_device(
				$css_array,
				$layout_css,
				$style_layout_css,
				$text_align,
				$justify_content,
				trim( $attr_css . ' ' . $overflow_css . ' ' . $shadow_css ),
				$selector,
				$media
			);

			// Add to styles array if CSS was generated.
			if ( $css ) {
				$styles[] = $css;
			}
		}

		// Combine all device CSS into a single string.
		$css = implode( ' ', $styles );

		/**
		 * Filter to modify the complete responsive CSS for a block instance.
		 *
		 * This filter allows developers to modify the complete generated responsive CSS
		 * for a Spectra block instance after all processing is complete. The CSS includes
		 * all media queries, layout styles, typography, spacing, borders, and block-specific
		 * attributes for all breakpoints (mobile, tablet, desktop).
		 *
		 * The CSS is fully processed and ready to be injected into the page. Use this filter
		 * to add custom CSS rules, modify existing rules, or completely replace the CSS.
		 *
		 * Example usage:
		 * ```php
		 * add_filter( 'spectra_blocks_responsive_css', function( $css, $spectra_id, $block_name ) {
		 *     // Fix z-index issues for modal blocks
		 *     if ( 'spectra/modal' === $block_name ) {
		 *         $css = str_replace( 'z-index: 999', 'z-index: 9999', $css );
		 *     }
		 *
		 *     // Add dark mode compatibility
		 *     if ( 'spectra/container' === $block_name ) {
		 *         $selector = "[data-spectra-id='{$spectra_id}']";
		 *         $css .= " @media (prefers-color-scheme: dark) { {$selector} { filter: invert(1); } }";
		 *     }
		 *
		 *     // Override styles for specific post types
		 *     if ( is_singular( 'product' ) && 'spectra/button' === $block_name ) {
		 *         $css .= " body.single-product [data-spectra-id='{$spectra_id}'] { border-radius: 0 !important; }";
		 *     }
		 *
		 *     return $css;
		 * }, 10, 3 );
		 * ```
		 *
		 * @since 3.0.0
		 *
		 * @param string $css        The complete generated CSS for the block instance, including media queries.
		 * @param string $spectra_id The unique ID of the block instance (e.g., 'spectra-123abc').
		 * @param string $block_name The name of the block (e.g., 'spectra/container').
		 * @return string Modified CSS string that will be injected into the page.
		 */
		// Cross-plugin extension point — spectra_ prefix is intentional; spectra-blocks-pro hooks into this filter.
		$css = apply_filters( 'spectra_blocks_responsive_css', $css, $spectra_id, $block_name );

		return $css;
	}

	/**
	 * Whether Spectra paints core/image's dimensions (width, height, aspect
	 * ratio, object-fit) per device.
	 *
	 * Where WordPress renders viewport states itself (7.1+), the image's
	 * per-device dimensions live in core's own `style['@tablet'].dimensions`
	 * shape and core emits them, banded, with `!important`. Spectra's flat
	 * `width` / `height` / `aspectRatio` / `scale` keys never receive a
	 * per-device value there, so painting them wrote the DESKTOP dimensions into
	 * the tablet and mobile bands — a duplicate of core's work that was also
	 * wrong, and only invisible because core's `!important` won. On those
	 * installs Spectra paints nothing for the image and leaves core's inline
	 * base style alone; on installs without viewport states the flat keys are
	 * the only per-device store and Spectra paints them exactly as before.
	 *
	 * @since 1.0.7
	 * @return bool True when Spectra owns the image's dimension CSS.
	 */
	private function paints_core_image_dimensions() {
		return ! ViewportSupport::renders_states();
	}

	/**
	 * Get device-specific attributes with proper fallback.
	 *
	 * Extracts attributes for a specific device following the fallback hierarchy:
	 * - Mobile: try mobile -> tablet -> desktop
	 * - Tablet: try tablet -> desktop
	 * - Desktop: desktop only
	 *
	 * Also handles mutually exclusive attributes to ensure that conflicting
	 * styles are not applied simultaneously.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_name          The name of the block.
	 * @param array  $responsive_controls The responsive controls data.
	 * @param string $device              The target device ('@mobile', '@tablet', 'base').
	 * @return array Device-specific attributes with fallback values.
	 */
	private function get_device_attributes( $block_name, $responsive_controls, $device ) {
		// Get all responsive attributes for the block.
		$block_attrs = ResponsiveAttributeCSS::get_responsive_attributes( $block_name );

		// Get fallback device order for the target device.
		$fallback_devices = $this->device_fallback_order[ $device ] ?? array( 'base' );
		$device_attrs     = array();

		// Resolve normal attributes.
		foreach ( $block_attrs as $attr ) {
			// Skip text shadow attributes for content block - they're handled as a group.
			if ( 'spectra/content' === $block_name && in_array( $attr, array( 'textShadowColor', 'textShadowBlur', 'textShadowOffsetX', 'textShadowOffsetY' ), true ) ) {
				continue;
			}

			// Special handling for background attribute - we need to process it even if null.
			// to ensure video wrapper visibility is controlled properly across breakpoints.
			if ( 'background' === $attr ) {
				// Always set background attribute for each device, even if null.
				// This ensures format_background is called to generate display CSS.
				$device_attrs[ $attr ] = null;

				// Build background with inner property fallback support.
				$merged_background = array();

				// Define background inner properties that should have individual fallback.
				$background_inner_properties = array(
					'type',
					'media',
					'useOverlay',
					'backgroundSize',
					'backgroundWidth',
					'backgroundRepeat',
					'backgroundPosition',
					'backgroundAttachment',
					'positionMode',
					'positionCentered',
					'positionX',
					'positionY',
				);

				// Process each inner property with its own fallback.
				foreach ( $background_inner_properties as $prop ) {
					foreach ( $fallback_devices as $fallback_device ) {
						if ( isset( $responsive_controls[ $fallback_device ]['background'][ $prop ] ) ) {
							$merged_background[ $prop ] = $responsive_controls[ $fallback_device ]['background'][ $prop ];
							break;
						}
					}
				}

				// If we have any background properties, use the merged result.
				if ( ! empty( $merged_background ) ) {
					$device_attrs[ $attr ] = $merged_background;
				}
			} elseif ( 'spectra/content' === $block_name && 'enableTextShadow' === $attr ) {
				// Special handling for text shadow enable flag.
				// Process enableTextShadow with proper fallback, respecting explicit disable.
				foreach ( $fallback_devices as $fallback_device ) {
					if ( isset( $responsive_controls[ $fallback_device ]['enableTextShadow'] ) ) {
						$device_attrs[ $attr ] = $responsive_controls[ $fallback_device ]['enableTextShadow'];

						// If text shadow is enabled, collect all text shadow attributes.
						if ( $device_attrs[ $attr ] ) {
							$text_shadow_attrs = array( 'textShadowColor', 'textShadowBlur', 'textShadowOffsetX', 'textShadowOffsetY' );
							foreach ( $text_shadow_attrs as $shadow_attr ) {
								// Skip if we already processed this attribute.
								if ( isset( $device_attrs[ $shadow_attr ] ) ) {
									continue;
								}

								// Find the first available value in the fallback chain.
								foreach ( $fallback_devices as $fb_device ) {
									if ( isset( $responsive_controls[ $fb_device ][ $shadow_attr ] ) ) {
										$device_attrs[ $shadow_attr ] = $responsive_controls[ $fb_device ][ $shadow_attr ];
										break;
									}
								}
							}
						}
						break;
					}
				}
			} else {
				// Normal attribute resolution with fallback.
				foreach ( $fallback_devices as $fallback_device ) {
					if ( isset( $responsive_controls[ $fallback_device ][ $attr ] ) && $this->has_actual_value( $responsive_controls[ $fallback_device ][ $attr ] ) ) {
						$device_attrs[ $attr ] = $responsive_controls[ $fallback_device ][ $attr ];
						break;
					}
				}
			}
		}

		return $device_attrs;
	}

	/**
	 * Build CSS for a specific device.
	 *
	 * Combines CSS from multiple sources:
	 * - Style engine output
	 * - Layout CSS
	 * - Style layout CSS
	 * - Text alignment
	 * - Block-specific attribute CSS
	 *
	 * And wraps it in a media query if a breakpoint is provided.
	 *
	 * @since 3.0.0
	 * @param array|false $css_array Style engine output from wp_style_engine_get_styles().
	 * @param string      $layout_css Generated layout CSS for this device.
	 * @param string      $style_layout_css Additional layout CSS from style controls.
	 * @param string      $text_align Text align value for this device.
	 * @param string      $justify_content Justify content value for flex blocks.
	 * @param string      $attr_css Block-specific attribute CSS.
	 * @param string      $selector CSS selector for targeting the block.
	 * @param string      $media Media query for this device.
	 * @return string Complete CSS for this device, wrapped in media query if needed.
	 */
	private function build_css_for_device( $css_array, $layout_css, $style_layout_css, $text_align, $justify_content, $attr_css, $selector, $media ) {
		// Start with empty CSS string.
		$css = '';

		// Add CSS from WordPress style engine if available.
		if ( is_array( $css_array ) && ! empty( $css_array['css'] ) ) {
			$css = $css_array['css'];
		}

		// Add text alignment CSS if available.
		if ( ! empty( $text_align ) ) {
			$css .= "{$selector}{text-align:{$text_align};}";
		}

		// Add justify-content CSS for flex blocks if available.
		if ( ! empty( $justify_content ) ) {
			$css .= "{$selector}{justify-content:{$justify_content};}";
		}

		// Add layout CSS if available.
		if ( ! empty( $layout_css ) ) {
			$css .= ' ' . $layout_css;
		}

		// Add additional style layout CSS if available.
		if ( ! empty( $style_layout_css ) ) {
			$css .= ' ' . $style_layout_css;
		}

		// Add block-specific attribute CSS if available.
		if ( ! empty( $attr_css ) ) {
			$css .= ' ' . $attr_css;
		}

		// Return empty string if no CSS was generated.
		if ( empty( $css ) ) {
			return '';
		}

		// Wrap in media query if needed.
		return $media
		? "@media {$media} {{$css}}"
		: $css;
	}

	/**
	 * Check if a value is set (not null or empty string).
	 *
	 * Special handling for:
	 * - Arrays (recursively checks if any value is set)
	 * - Zero values (treats 0, '0', '0px', etc. as valid values)
	 * - Empty strings and null (treats as not set)
	 *
	 * @since 3.0.0
	 * @param mixed $value The value to check.
	 * @return bool True if value is set and usable, false otherwise.
	 */
	private function has_actual_value( $value ) {
		// Null and empty string are not valid values.
		if ( null === $value || '' === $value ) {
			return false;
		}

		// For arrays, check if any value inside is valid.
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( $this->has_actual_value( $item ) ) {
					return true;
				}
			}
			return false;
		}

		// Special case for zero values with units (0px, 0em, etc.).
		if ( is_string( $value ) && preg_match( '/^0(px|em|rem|%|vw|vh)?$/', $value ) ) {
			return true;
		}

		// Zero as integer or string is a valid value.
		if ( 0 === $value || '0' === $value ) {
			return true;
		}

		// All other non-empty values are valid.
		return true;
	}

	/**
	 * Get processed styles for a specific device with proper Gutenberg fallback hierarchy.
	 *
	 * Implements individual property fallback (not group fallback) exactly like Gutenberg core:
	 * - Mobile (sm): tries mobile -> tablet -> desktop for EACH property
	 * - Tablet (md): tries tablet -> desktop for EACH property
	 * - Desktop (lg): desktop only for EACH property
	 *
	 * This ensures proper inheritance: if mobile border color is not set,
	 * it will use tablet border color, then desktop border color as fallback.
	 *
	 * Handles all Gutenberg spacing properties including blockGap for layout containers.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $responsive_controls Complete responsive controls data from block attributes.
	 * @param string $device              Target device key ('@mobile', '@tablet', 'base').
	 * @param string $block_name          The block name for flex text alignment handling.
	 * @return array Processed style array ready for WordPress Style Engine.
	 */
	private function get_device_styles( $responsive_controls, $device, $block_name = '' ) {
		// Get fallback device order for the target device.
		$fallback_devices = $this->device_fallback_order[ $device ] ?? array( 'base' );

		// Initialize the final compiled styles.
		$compiled_styles = array();

		// Process each style category with proper fallback.
		$this->process_border_styles( $responsive_controls, $fallback_devices, $compiled_styles );
		$this->process_typography_styles( $responsive_controls, $fallback_devices, $compiled_styles, $block_name );
		$this->process_spacing_styles( $responsive_controls, $fallback_devices, $compiled_styles );
		$this->process_shadow_styles( $responsive_controls, $fallback_devices, $compiled_styles );

		return $compiled_styles;
	}

	/**
	 * Process border styles (width, style, radius) with fallback.
	 *
	 * Handles single borders (borderColor + style.border.width/style/color) and
	 * mixed borders (style.border.top/right/bottom/left) with proper inheritance.
	 * A breakpoint inherits as-is from parent regardless of type mismatch.
	 *
	 * @since 3.0.0
	 * @param array $responsive_controls Complete responsive controls data.
	 * @param array $fallback_devices Device fallback order.
	 * @param array $compiled_styles Compiled styles passed by reference.
	 * @return void
	 */
	private function process_border_styles( $responsive_controls, $fallback_devices, &$compiled_styles ) {
		// Get current device (first in fallback order).
		$current_device = $fallback_devices[0];
		$current_data   = $responsive_controls[ $current_device ] ?? array();

		// Check what the current breakpoint has.
		// Border radius is excluded as it should inherit independently.
		$current_has_single_border = isset( $current_data['borderColor'] ) ||
			( isset( $current_data['style']['border'] ) && (
				isset( $current_data['style']['border']['width'] ) ||
				isset( $current_data['style']['border']['style'] ) ||
				isset( $current_data['style']['border']['color'] )
			) );

		$current_has_mixed_border = isset( $current_data['style']['border'] ) && (
			isset( $current_data['style']['border']['top'] ) ||
			isset( $current_data['style']['border']['right'] ) ||
			isset( $current_data['style']['border']['bottom'] ) ||
			isset( $current_data['style']['border']['left'] )
		);

		if ( $current_has_single_border || $current_has_mixed_border ) {
			// Apply current's border config.
			if ( isset( $current_data['borderColor'] ) ) {
				$compiled_styles['border']['color'] = "var(--wp--preset--color--{$current_data['borderColor']})";
			}

			if ( isset( $current_data['style']['border'] ) ) {
				$this->apply_border_data( $current_data['style']['border'], $compiled_styles );
			}

			/*
			 * Fill the properties this breakpoint did not declare from the fallback
			 * chain, rather than treating border as one indivisible unit.
			 *
			 * Typography and spacing already resolve property by property — each has
			 * its own walk down the chain — and core does the same, merging a viewport
			 * over the base with `array_replace()`. Border was the one group that did
			 * not: declaring any border property here took the whole config
			 * "exclusively", so a breakpoint that set only `width` silently dropped the
			 * base's colour. Authoring a 10px tablet width on a block whose colour came
			 * from the base left tablet with no border colour at all.
			 *
			 * Only for the single-border shape. When this breakpoint uses per-side
			 * borders the two shapes cannot be interleaved, so its config stands alone.
			 */
			if ( ! $current_has_mixed_border ) {
				foreach ( array( 'color', 'width', 'style' ) as $border_property ) {
					if ( isset( $compiled_styles['border'][ $border_property ] ) ) {
						continue;
					}

					foreach ( array_slice( $fallback_devices, 1 ) as $parent_device ) {
						$parent_data = $responsive_controls[ $parent_device ] ?? array();

						// The preset attribute only ever carries a colour.
						if ( 'color' === $border_property && isset( $parent_data['borderColor'] ) ) {
							$compiled_styles['border']['color'] = "var(--wp--preset--color--{$parent_data['borderColor']})";
							break;
						}

						$parent_border = isset( $parent_data['style']['border'] ) && is_array( $parent_data['style']['border'] )
							? $parent_data['style']['border']
							: array();

						if ( isset( $parent_border[ $border_property ] ) && $this->has_actual_value( $parent_border[ $border_property ] ) ) {
							$this->apply_border_data( array( $border_property => $parent_border[ $border_property ] ), $compiled_styles );
							break;
						}
					}
				}
			}
		} else {
			// Current has no border config - inherit from parent breakpoint.
			foreach ( array_slice( $fallback_devices, 1 ) as $parent_device ) {
				$parent_data = $responsive_controls[ $parent_device ] ?? array();

				// Check if parent has any border config (excluding radius).
				$parent_has_border = isset( $parent_data['borderColor'] ) ||
					( isset( $parent_data['style']['border'] ) &&
						( isset( $parent_data['style']['border']['width'] ) ||
							isset( $parent_data['style']['border']['style'] ) ||
							isset( $parent_data['style']['border']['color'] ) ||
							isset( $parent_data['style']['border']['top'] ) ||
							isset( $parent_data['style']['border']['right'] ) ||
							isset( $parent_data['style']['border']['bottom'] ) ||
							isset( $parent_data['style']['border']['left'] ) ) );

				if ( $parent_has_border ) {
					// Inherit whatever the parent has.
					if ( isset( $parent_data['borderColor'] ) ) {
						$compiled_styles['border']['color'] = "var(--wp--preset--color--{$parent_data['borderColor']})";
					}

					if ( isset( $parent_data['style']['border'] ) ) {
						$this->apply_border_data( $parent_data['style']['border'], $compiled_styles );
					}
					break; // Stop after finding first parent with border config.
				}
			}
		}

		// Handle border radius inheritance separately - it always inherits if not set.
		if ( ! isset( $current_data['style']['border']['radius'] ) ) {
			foreach ( array_slice( $fallback_devices, 1 ) as $parent_device ) {
				$parent_data = $responsive_controls[ $parent_device ] ?? array();

				if ( isset( $parent_data['style']['border']['radius'] ) ) {
					$radius_value = $parent_data['style']['border']['radius'];
					// Convert array format to string format for WordPress Style Engine.
					if ( is_array( $radius_value ) && ! empty( $radius_value ) ) {
						$radius_parts = array();
						$corners      = array( 'topLeft', 'topRight', 'bottomRight', 'bottomLeft' );
						foreach ( $corners as $corner ) {
							$corner_value = isset( $radius_value[ $corner ] ) ? $radius_value[ $corner ] : '';
							// Ensure we have a string value, not an array.
							if ( is_array( $corner_value ) ) {
								$corner_value = '0'; // Default fallback if malformed data.
							}
							$radius_parts[] = '' !== $corner_value ? $corner_value : '0';
						}
						$compiled_styles['border']['radius'] = implode( ' ', $radius_parts );
					} else {
						$compiled_styles['border']['radius'] = $radius_value;
					}
					break;
				}
			}
		} else {
			// Current device has radius set.
			$radius_value = $current_data['style']['border']['radius'];
			// Convert array format to string format for WordPress Style Engine.
			if ( is_array( $radius_value ) && ! empty( $radius_value ) ) {
				$radius_parts = array();
				$corners      = array( 'topLeft', 'topRight', 'bottomRight', 'bottomLeft' );
				foreach ( $corners as $corner ) {
					$corner_value = isset( $radius_value[ $corner ] ) ? $radius_value[ $corner ] : '';
					// Ensure we have a string value, not an array.
					if ( is_array( $corner_value ) ) {
						$corner_value = '0'; // Default fallback if malformed data.
					}
					$radius_parts[] = '' !== $corner_value ? $corner_value : '0';
				}
				$compiled_styles['border']['radius'] = implode( ' ', $radius_parts );
			} else {
				$compiled_styles['border']['radius'] = $radius_value;
			}
		}

		// Handle border styles intelligently.
		$this->ensure_border_styles( $compiled_styles );
	}

	/**
	 * Apply border data to compiled styles.
	 *
	 * Helper method to process border data and add it to the compiled styles array.
	 * Handles both single border properties (width, style, color, radius) and
	 * mixed border properties (top, right, bottom, left).
	 *
	 * @since 3.0.0.1
	 * @param array $border_data Border data from responsive controls.
	 * @param array $compiled_styles Compiled styles passed by reference.
	 * @return void
	 */
	private function apply_border_data( $border_data, &$compiled_styles ) {
		if ( ! is_array( $border_data ) ) {
			return;
		}

		$sides = array( 'top', 'right', 'bottom', 'left' );

		foreach ( $border_data as $property => $value ) {
			// Skip if no actual value.
			if ( ! $this->has_actual_value( $value ) ) {
				continue;
			}

			// Handle individual sides.
			if ( in_array( $property, $sides, true ) && is_array( $value ) ) {
				if ( ! isset( $compiled_styles['border'][ $property ] ) ) {
					$compiled_styles['border'][ $property ] = array();
				}

				foreach ( $value as $side_prop => $side_value ) {
					if ( $this->has_actual_value( $side_value ) ) {
						// Handle preset color conversion. for sides.
						if ( 'color' === $side_prop && is_string( $side_value ) ) {
							if ( strpos( $side_value, 'var:preset|color|' ) === 0 ) {
								$color_slug = str_replace( 'var:preset|color|', '', $side_value );
								$compiled_styles['border'][ $property ][ $side_prop ] = "var(--wp--preset--color--{$color_slug})";
							} else {
								$compiled_styles['border'][ $property ][ $side_prop ] = $side_value;
							}
						} elseif ( 'radius' === $side_prop && is_array( $side_value ) && ! empty( $side_value ) ) {
							// Handle border side radius array format conversion.
							$radius_parts = array();
							$corners      = array( 'topLeft', 'topRight', 'bottomRight', 'bottomLeft' );
							foreach ( $corners as $corner ) {
								$radius_parts[] = isset( $side_value[ $corner ] ) && '' !== $side_value[ $corner ] ? $side_value[ $corner ] : '0';
							}
							$compiled_styles['border'][ $property ][ $side_prop ] = implode( ' ', $radius_parts );
						} else {
							// Validate that width and style properties are strings, not arrays.
							if ( in_array( $side_prop, array( 'width', 'style' ), true ) && is_array( $side_value ) ) {
								// Skip invalid array values for width, style, and color properties.
								continue;
							}
							$compiled_styles['border'][ $property ][ $side_prop ] = $side_value;
						}
					}
				}
			} elseif ( 'color' === $property && is_string( $value ) ) {
				// Handle general properties (width, style, color, radius).
				// Handle preset color conversion.
				if ( strpos( $value, 'var:preset|color|' ) === 0 ) {
					$color_slug                         = str_replace( 'var:preset|color|', '', $value );
					$compiled_styles['border']['color'] = "var(--wp--preset--color--{$color_slug})";
				} else {
					$compiled_styles['border']['color'] = $value;
				}
			} elseif ( 'radius' === $property && is_array( $value ) && ! empty( $value ) ) {
				// Handle border radius array format conversion.
				$radius_parts = array();
				$corners      = array( 'topLeft', 'topRight', 'bottomRight', 'bottomLeft' );
				foreach ( $corners as $corner ) {
					$radius_parts[] = isset( $value[ $corner ] ) && '' !== $value[ $corner ] ? $value[ $corner ] : '0';
				}
				$compiled_styles['border']['radius'] = implode( ' ', $radius_parts );
			} else {
				// For other properties (width, style), only accept string values, not arrays.
				if ( in_array( $property, array( 'width', 'style' ), true ) && is_array( $value ) ) {
					// Skip invalid array format for width and style.
					continue;
				}
				$compiled_styles['border'][ $property ] = $value;
			}
		}
	}

	/**
	 * Ensures border styles are properly set for visibility.
	 *
	 * This method handles various scenarios:
	 * - General border width without style
	 * - Individual side widths without styles
	 * - Mixed scenarios with some sides having styles and others not
	 * - Preserves explicitly set 'none' or 'hidden' styles
	 * - Validates and fixes invalid array values in border properties
	 *
	 * @since 3.0.0
	 * @param array $compiled_styles Compiled styles passed by reference.
	 * @return void
	 */
	private function ensure_border_styles( &$compiled_styles ) {
		if ( ! isset( $compiled_styles['border'] ) || ! is_array( $compiled_styles['border'] ) ) {
			return;
		}

		$sides = array( 'top', 'right', 'bottom', 'left' );

		// Remove any invalid array values in individual side properties.
		// This serves as a safety net in case invalid data bypasses earlier validation.
		foreach ( $sides as $side ) {
			if ( isset( $compiled_styles['border'][ $side ] ) && is_array( $compiled_styles['border'][ $side ] ) ) {
				foreach ( array( 'width', 'style', 'color' ) as $prop ) {
					if ( isset( $compiled_styles['border'][ $side ][ $prop ] ) && is_array( $compiled_styles['border'][ $side ][ $prop ] ) ) {
						// Invalid array value - remove to prevent WordPress Style Engine errors.
						unset( $compiled_styles['border'][ $side ][ $prop ] );
					}
				}

				// If the side now has no valid properties, remove it entirely.
				if ( empty( $compiled_styles['border'][ $side ] ) ) {
					unset( $compiled_styles['border'][ $side ] );
				}
			}
		}

		// First, check if we need a general border style.
		// Add style if either width or color is present but style is missing.
		if ( ( ( ! empty( $compiled_styles['border']['width'] ) && $this->has_actual_value( $compiled_styles['border']['width'] ) ) ||
			( ! empty( $compiled_styles['border']['color'] ) && $this->has_actual_value( $compiled_styles['border']['color'] ) ) ) &&
		empty( $compiled_styles['border']['style'] ) ) {
			$compiled_styles['border']['style'] = 'solid';
		}

		// Then, handle individual sides that need styles.
		foreach ( $sides as $side ) {
			if ( isset( $compiled_styles['border'][ $side ] ) && is_array( $compiled_styles['border'][ $side ] ) ) {
				$side_data = $compiled_styles['border'][ $side ];

				// If this side has width or color but no style, it needs a style.
				if ( ( ( isset( $side_data['width'] ) && $this->has_actual_value( $side_data['width'] ) ) ||
					( isset( $side_data['color'] ) && $this->has_actual_value( $side_data['color'] ) ) ) &&
				empty( $side_data['style'] ) ) {
					// First check if there's a general style to inherit.
					if ( ! empty( $compiled_styles['border']['style'] ) ) {
						$compiled_styles['border'][ $side ]['style'] = $compiled_styles['border']['style'];
					} else {
						// No general style, so set solid for this side.
						$compiled_styles['border'][ $side ]['style'] = 'solid';
					}
				}
			}
		}

		// Handle color inheritance for individual sides.
		// If a side has width but no color, it should inherit from general border color if available.
		if ( ! empty( $compiled_styles['border']['color'] ) ) {
			foreach ( $sides as $side ) {
				if ( isset( $compiled_styles['border'][ $side ] ) &&
				is_array( $compiled_styles['border'][ $side ] ) &&
				isset( $compiled_styles['border'][ $side ]['width'] ) &&
				$this->has_actual_value( $compiled_styles['border'][ $side ]['width'] ) &&
				empty( $compiled_styles['border'][ $side ]['color'] ) ) {
					$compiled_styles['border'][ $side ]['color'] = $compiled_styles['border']['color'];
				}
			}
		}

		// Final cleanup: ensure single and mixed borders don't coexist.
		$has_single_border = isset( $compiled_styles['border']['color'] ) ||
			isset( $compiled_styles['border']['width'] ) ||
			isset( $compiled_styles['border']['style'] );

		$has_mixed_border = false;
		foreach ( $sides as $side ) {
			if ( isset( $compiled_styles['border'][ $side ] ) && is_array( $compiled_styles['border'][ $side ] ) ) {
				$has_mixed_border = true;
				break;
			}
		}

		// If both formats coexist, prioritize individual sides and remove general properties.
		if ( $has_single_border && $has_mixed_border ) {
			unset( $compiled_styles['border']['width'] );
			unset( $compiled_styles['border']['style'] );
			// Keep color for potential inheritance by sides.
		}
	}

	/**
	 * Get list of blocks that use flexbox and need justifyContent instead of textAlign.
	 *
	 * @since 3.0.0
	 * @param string $block_name The block name to check.
	 * @return bool True if block uses flexbox for text alignment.
	 */
	private function is_flex_text_align_block( $block_name ) {
		/**
		 * Filter the list of blocks that use flexbox and need justifyContent for text alignment.
		 * Pro plugins can extend this list for their button-like blocks.
		 *
		 * @since 3.0.0
		 * @param array $flex_blocks List of block names that use flexbox for text alignment.
		 * @return array Filtered list of flex text alignment blocks.
		 */
		$flex_blocks = apply_filters(
			'spectra_blocks_flex_text_align_blocks',
			array(
				// Core Spectra blocks with button-like flex behavior.
				'spectra/button',                      // Button block.
				'spectra/modal-child-button',         // Modal  child trigger button.
				'spectra/tabs-child-tab-button',       // Individual tab button.

			// Pro blocks will be added via filter in Spectra Pro plugin itself.
			)
		);

		return in_array( $block_name, $flex_blocks, true );
	}

	/**
	 * Process typography with proper Gutenberg fallback hierarchy.
	 *
	 * Handles fontSize and fontFamily with individual property fallback.
	 * Checks both root level fontSize and nested style.typography.fontSize.
	 * Each typography property falls back independently.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $responsive_controls Complete responsive controls data.
	 * @param array  $fallback_devices    Device fallback order.
	 * @param array  $compiled_styles     Compiled styles passed by reference.
	 * @param string $block_name          The block name for flex text alignment handling.
	 * @return void
	 */
	private function process_typography_styles( $responsive_controls, $fallback_devices, &$compiled_styles, $block_name = '' ) {
		// Only process if typography is in the style_responsive_keys list.
		if ( ! in_array( 'typography', $this->style_responsive_keys, true ) ) {
			return;
		}

		// Process font size with fallback.
		foreach ( $fallback_devices as $device ) {
			$font_size = null;

			// Check for preset font size first.
			if (
				isset( $responsive_controls[ $device ]['fontSize'] ) &&
				$this->has_actual_value( $responsive_controls[ $device ]['fontSize'] )
			) {
				$font_size = "var(--wp--preset--font-size--{$responsive_controls[$device]['fontSize']})";
			} elseif ( // Then check for custom font size.
				isset( $responsive_controls[ $device ]['style']['typography']['fontSize'] ) &&
				$this->has_actual_value( $responsive_controls[ $device ]['style']['typography']['fontSize'] )
			) {
				$font_size = $responsive_controls[ $device ]['style']['typography']['fontSize'];
			}

			// If we found a font size, use it and stop looking.
			if ( $font_size ) {
				$compiled_styles['typography']['fontSize'] = $font_size;
				break;
			}
		}

		// Process font family with fallback: preset first, then a custom family.
		foreach ( $fallback_devices as $device ) {
			if (
				isset( $responsive_controls[ $device ]['fontFamily'] ) &&
				$this->has_actual_value( $responsive_controls[ $device ]['fontFamily'] )
			) {
				$compiled_styles['typography']['fontFamily'] =
					"var(--wp--preset--font-family--{$responsive_controls[$device]['fontFamily']})";
				break;
			}

			if (
				isset( $responsive_controls[ $device ]['style']['typography']['fontFamily'] ) &&
				$this->has_actual_value( $responsive_controls[ $device ]['style']['typography']['fontFamily'] )
			) {
				$compiled_styles['typography']['fontFamily'] =
					$responsive_controls[ $device ]['style']['typography']['fontFamily'];
				break;
			}
		}

		// Process other typography properties with fallback.
		$typography_properties = array(
			'fontWeight',
			'fontStyle',
			'lineHeight',
			'letterSpacing',
			'textDecoration',
			'textTransform',
			'textAlign',
			'textColumns',
			'textIndent',
			'writingMode',
		);

		foreach ( $typography_properties as $property ) {
			foreach ( $fallback_devices as $device ) {
				if (
					isset( $responsive_controls[ $device ]['style']['typography'][ $property ] ) &&
					$this->has_actual_value( $responsive_controls[ $device ]['style']['typography'][ $property ] )
				) {
					// Special handling for textAlign in flex blocks - store for manual CSS generation.
					if ( 'textAlign' === $property && $this->is_flex_text_align_block( $block_name ) ) {
						$text_align_value = $responsive_controls[ $device ]['style']['typography'][ $property ];

						// Map textAlign values to justifyContent values.
						$justify_content_value = $text_align_value;
						if ( 'left' === $text_align_value ) {
							$justify_content_value = 'flex-start';
						} elseif ( 'right' === $text_align_value ) {
							$justify_content_value = 'flex-end';
						}

						// Store in a custom section for manual CSS generation.
						$compiled_styles['spectra_flex']['justifyContent'] = $justify_content_value;
					} else {
						$value = $responsive_controls[ $device ]['style']['typography'][ $property ];

						/*
						 * Core's UI stores textColumns as a number, and the style
						 * engine's CSS compiler silently drops non-string values —
						 * it resolves `column-count` into the declarations but not
						 * into the css string. Hand it over as a string.
						 */
						$compiled_styles['typography'][ $property ] = is_scalar( $value ) ? (string) $value : $value;
					}
					break;
				}
			}
		}

		// Set default center alignment for flex blocks if no alignment was set.
		if ( $this->is_flex_text_align_block( $block_name ) && ! isset( $compiled_styles['spectra_flex']['justifyContent'] ) ) {
			$compiled_styles['spectra_flex']['justifyContent'] = 'center';
		}
	}

	/**
	 * Process spacing with proper Gutenberg fallback hierarchy.
	 *
	 * Handles padding, margin, AND blockGap with individual property fallback.
	 * Each spacing property (padding.top, margin.left, blockGap, etc.) falls back independently.
	 *
	 * @since 3.0.0
	 *
	 * @param array $responsive_controls Complete responsive controls data.
	 * @param array $fallback_devices    Device fallback order.
	 * @param array $compiled_styles    Compiled styles passed by reference.
	 * @return void
	 */
	private function process_spacing_styles( $responsive_controls, $fallback_devices, &$compiled_styles ) {
		// Only process if spacing is in the style_responsive_keys list.
		if ( ! in_array( 'spacing', $this->style_responsive_keys, true ) ) {
			return;
		}

		// Process padding and margin properties.
		$this->process_spacing_properties( $responsive_controls, $fallback_devices, $compiled_styles );

		// Process block gap separately.
		$this->process_block_gap( $responsive_controls, $fallback_devices, $compiled_styles );
	}

	/**
	 * Process spacing properties (padding/margin) with fallback.
	 *
	 * Handles both shorthand values (single value for all sides)
	 * and individual side values (top, right, bottom, left).
	 *
	 * Prioritizes shorthand values over individual sides for consistency.
	 *
	 * @since 3.0.0
	 * @param array $responsive_controls Responsive controls data.
	 * @param array $fallback_devices Fallback device order.
	 * @param array $compiled_styles Compiled styles passed by reference.
	 * @return void
	 */
	private function process_spacing_properties( $responsive_controls, $fallback_devices, &$compiled_styles ) {
		$spacing_types = array(
			'padding' => array( 'top', 'right', 'bottom', 'left' ),
			'margin'  => array( 'top', 'right', 'bottom', 'left' ),
		);

		foreach ( $spacing_types as $type => $sides ) {
			// Check for shorthand first (e.g., padding: 10px).
			foreach ( $fallback_devices as $device ) {
				$value = $responsive_controls[ $device ]['style']['spacing'][ $type ] ?? null;

				if ( isset( $value ) && is_string( $value ) && $this->has_actual_value( $value ) ) {
					$compiled_styles['spacing'][ $type ] = $value;
					break;
				}
			}

			// Process individual sides if no shorthand was found.
			if ( ! isset( $compiled_styles['spacing'][ $type ] ) ) {
				foreach ( $sides as $side ) {
					foreach ( $fallback_devices as $device ) {
						$value = $responsive_controls[ $device ]['style']['spacing'][ $type ][ $side ] ?? null;

						if ( isset( $value ) && $this->has_actual_value( $value ) ) {
							$compiled_styles['spacing'][ $type ][ $side ] = $value;
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Process block gap with proper fallback hierarchy.
	 *
	 * Block gap is used for spacing between child elements
	 * in container blocks (columns, stack, etc).
	 *
	 * @since 3.0.0
	 * @param array $responsive_controls Responsive controls data.
	 * @param array $fallback_devices Fallback device order.
	 * @param array $compiled_styles Compiled styles passed by reference.
	 * @return void
	 */
	private function process_block_gap( $responsive_controls, $fallback_devices, &$compiled_styles ) {
		foreach ( $fallback_devices as $device ) {
			$gap = $responsive_controls[ $device ]['style']['spacing']['blockGap'] ?? null;

			if ( isset( $gap ) && $this->has_actual_value( $gap ) ) {
				$compiled_styles['spacing']['blockGap'] = $gap;
				break;
			}
		}
	}

	/**
	 * Process shadow styles with proper fallback hierarchy.
	 *
	 * Box shadow effects can be applied responsively and
	 * will follow the device fallback order.
	 *
	 * @since 3.0.0
	 * @param array $responsive_controls Responsive controls data.
	 * @param array $fallback_devices Fallback device order.
	 * @param array $compiled_styles Compiled styles passed by reference.
	 * @return void
	 */
	private function process_shadow_styles( $responsive_controls, $fallback_devices, &$compiled_styles ) {
		// Only process if shadow is in the style_responsive_keys list.
		if ( ! in_array( 'shadow', $this->style_responsive_keys, true ) ) {
			return;
		}

		foreach ( $fallback_devices as $device ) {
			$shadow = $responsive_controls[ $device ]['style']['shadow'] ?? null;

			if ( isset( $shadow ) && $this->has_actual_value( $shadow ) ) {
				$compiled_styles['shadow'] = $shadow;
				break;
			}
		}
	}

	/**
	 * Generate layout CSS as a string for a specific device.
	 *
	 * Layout CSS includes:
	 * - Display type (flex, grid, block)
	 * - Alignment properties
	 * - Gap settings
	 * - Content width constraints
	 *
	 * @since 3.0.0
	 * @param array  $responsive_controls The responsive controls data.
	 * @param string $device              The target device key ('@mobile', '@tablet', 'base').
	 * @param string $selector            The CSS selector for the block.
	 * @param string $child_reset_selector The selector for child margin resets.
	 * @return string The generated layout CSS string.
	 */
	private function generate_layout_css( $responsive_controls, $device, $selector, $child_reset_selector ) {
		// Get fallback device order for the target device.
		$fallback_devices = $this->device_fallback_order[ $device ] ?? array( 'base' );
		$layout_css       = '';

		$gap = null;
		foreach ( $fallback_devices as $fallback_device ) {
			$gap = $responsive_controls[ $fallback_device ]['style']['spacing']['blockGap'] ?? null;

			if ( $this->has_actual_value( $gap ) ) {
				break;
			}
		}

		/*
		 * Resolve the layout by MERGING the fallback chain, least specific first,
		 * rather than taking the first definition whole.
		 *
		 * Core's Layout panel writes only the properties it owns. On a block whose
		 * `supports.layout` sets `allowSwitching: false` there is no type control,
		 * so the panel never writes `type` — core supplies it from
		 * `supports.layout.default` and merges what the user authored over it.
		 *
		 * Taking one breakpoint's object whole reproduced none of that. A tablet
		 * layout of `{ justifyContent: 'center' }` arrived with no `type`, so
		 * `generate_custom_layout_css()` fell through to its `default` branch:
		 * the justification was never emitted AND the container was given
		 * `display: block`, dropping out of flex entirely below desktop.
		 *
		 * Merging also makes the documented cascade true for layout — a tablet
		 * that sets only `flexWrap` keeps the desktop justification instead of
		 * discarding it. The base bucket always carries a `type`, because
		 * `process_responsive_attributes()` seeds it from `$blocks_default_layout`
		 * when the store has none.
		 */
		$layout = array();
		foreach ( array_reverse( $fallback_devices ) as $fallback_device ) {
			$device_layout = $responsive_controls[ $fallback_device ]['layout'] ?? null;

			if ( ! is_array( $device_layout ) || ! $this->has_actual_value( $device_layout ) ) {
				continue;
			}

			$layout = array_merge( $layout, $device_layout );
		}

		/*
		 * A blockGap with no layout at all still has to render. Two blocks
		 * (`spectra/modal`, `spectra/post-no-results`) support blockGap without
		 * layout support, and core's own layout rendering is skipped for
		 * processed blocks — so a per-device gap on them reached no renderer.
		 * Core treats a missing type as flow; synthesising that here lets
		 * `wp_get_layout_style()` emit its flow-gap rules.
		 */
		if ( empty( $layout ) && $this->has_actual_value( $gap ) ) {
			$layout = array( 'type' => 'default' );
		}

		if ( ! empty( $layout ) ) {
			// Generate layout CSS using WordPress core function with a temporary ID.
			// We use a temporary ID to avoid conflicts with WordPress core selectors.
			$layout_css = wp_get_layout_style( $selector . '-temp-id', $layout, true, $gap );

			// Replace the temporary ID with the actual selector.
			$core_css = str_replace( $selector . '-temp-id', $selector, $layout_css );

			// Generate our custom layout CSS to supplement core CSS.
			$custom_css = $this->generate_custom_layout_css( $selector, $layout, $gap, $core_css, $child_reset_selector );

			// Merge core and custom CSS without duplicates.
			$layout_css = $this->merge_layout_css( $core_css, $custom_css );
		}

		// Always return a string, even if empty.
		return $layout_css;
	}

	/**
	 * Generate custom layout CSS for a specific layout type.
	 *
	 * Provides specialized CSS for different layout types:
	 * - grid: CSS Grid layout
	 * - flex: Flexbox layout
	 * - constrained: Content width constraints
	 * - flow/default: Standard block flow
	 *
	 * Note: This function generates CSS that is specific to the given layout type,
	 * but it does not generate CSS that is specific to the given device.
	 * The device-specific CSS is handled by the generate_layout_css function.
	 *
	 * @since 3.0.0
	 * @param string $selector The CSS selector for the block.
	 * @param array  $layout   The layout data.
	 * @param string $gap      The block gap value.
	 * @param string $core_css The core CSS generated by WordPress.
	 * @param string $child_reset_selector The selector for child margin resets.
	 * @return string The generated custom layout CSS string.
	 */
	private function generate_custom_layout_css( $selector, $layout, $gap, $core_css, $child_reset_selector ) {
		$css  = '';
		$type = $layout['type'] ?? 'default';

		// Common alignment styles for flow and constrained layouts.
		$alignment_styles = "
			{$selector} > .alignleft { float: left; margin-inline-start: 0; margin-inline-end: 2em; }
			{$selector} > .alignright { float: right; margin-inline-start: 2em; margin-inline-end: 0; }
			{$selector} > .aligncenter { margin-left: auto !important; margin-right: auto !important; }
		";

		// Common spacing styles for flow and constrained layouts.
		// Using 0-1-1 specificity - wins over Astra's 0-1-1 due to CSS cascade order.
		$gap_value      = $gap ?? 'var(--wp--style--block-gap, 1em)';
		$spacing_styles = "
			{$child_reset_selector} > :first-child { margin-block-start: 0; }
			{$child_reset_selector} > :last-child { margin-block-end: 0; }
			{$child_reset_selector} > * { margin-block-start: {$gap_value}; margin-block-end: 0; }
		";

		// Generate CSS based on layout type.
		switch ( $type ) {
			case 'grid':
				// CSS Grid layout.
				$css .= "{$selector} { display: grid;";

				// Add gap with fallback.
				$css .= ' gap: ' . ( $gap ?? 'var(--wp--style--block-gap, 1em)' ) . ';';

				$css .= ' }';

				// Grid children should have no margin - use 0-1-1 (wins via cascade order).
				$css .= " {$child_reset_selector} > * { margin: 0; }";
				break;

			case 'flex':
				// Flexbox layout with configurable properties.
				$flex_wrap          = $layout['flexWrap'] ?? $layout['wrap'] ?? 'wrap';
				$justify            = $layout['justifyContent'] ?? 'flex-start';
				$orientation        = $layout['orientation'] ?? 'horizontal';
				$vertical_alignment = $layout['verticalAlignment'] ?? null;

				// Set direction based on orientation.
				$direction = ( 'vertical' === $orientation ) ? 'column' : 'row';

				/*
				 * Which CSS property each control drives depends on the orientation,
				 * because the flex axes swap. Mirror WordPress core exactly — see the
				 * option maps in `wp_get_layout_style()`:
				 *
				 *   horizontal  justifyContent -> justify-content   verticalAlignment -> align-items
				 *   vertical    justifyContent -> align-items       verticalAlignment -> justify-content
				 *
				 * The value sets differ too. `space-between` is only offered on the main
				 * axis and `stretch` only on the cross axis, so which control accepts
				 * which keyword flips with the orientation. Mapping them from a single
				 * table produced `align-items: space-between` — not a valid value, so
				 * browsers dropped the declaration and "Space Between" silently did
				 * nothing on a vertical block.
				 */
				$justify_options = array(
					'left'   => 'flex-start',
					'right'  => 'flex-end',
					'center' => 'center',
				);

				$align_options = array(
					'top'    => 'flex-start',
					'center' => 'center',
					'bottom' => 'flex-end',
				);

				if ( 'row' === $direction ) {
					$justify_options['space-between'] = 'space-between';
					$align_options['stretch']         = 'stretch';
				} else {
					$justify_options['stretch']     = 'stretch';
					$align_options['space-between'] = 'space-between';
				}

				$justify_value = $justify_options[ $justify ] ?? 'flex-start';

				/*
				 * Only an explicitly authored verticalAlignment produces a declaration,
				 * as in core. The horizontal branch keeps its long-standing
				 * `align-items: center` default so existing rows are unaffected; the
				 * vertical branch emits nothing, because defaulting there would start
				 * distributing children along the main axis on every existing block.
				 */
				$align_value = null !== $vertical_alignment
					? ( $align_options[ $vertical_alignment ] ?? null )
					: null;

				// Start building the flex container CSS.
				$css .= "{$selector} { display: flex;";

				// Add gap with fallback.
				$css .= ' gap: ' . ( $gap ?? 'var(--wp--style--block-gap, 1em)' ) . ';';

				// Add flex properties.
				$css .= " flex-wrap: {$flex_wrap};";

				if ( 'column' === $direction ) {
					$css .= ' flex-direction: column;';
					// Axes are swapped: justification is the cross axis, alignment the main one.
					$css .= " align-items: {$justify_value};";

					if ( null !== $align_value ) {
						$css .= " justify-content: {$align_value};";
					}
				} else {
					$css .= ' flex-direction: row;';
					$css .= " justify-content: {$justify_value};";
					$css .= ' align-items: ' . ( $align_value ?? 'center' ) . ';';
				}

				$css .= ' }';

				// Flex children should have no margin - use 0-1-1 (wins via cascade order).
				$css .= " {$child_reset_selector} > * { margin: 0; }";
				break;

			case 'constrained':
				// Constrained width layout.
				$css .= "{$selector} { display: block; }";
				$css .= $alignment_styles;

				// Get content and wide sizes from layout settings.
				$content_size    = isset( $layout['contentSize'] ) ? $layout['contentSize'] : 'var(--wp--style--global--content-size)';
				$wide_size       = isset( $layout['wideSize'] ) ? $layout['wideSize'] : 'var(--wp--style--global--wide-size)';
				$justify_content = isset( $layout['justifyContent'] ) ? $layout['justifyContent'] : 'center';
				$margin_left     = 'left' === $justify_content ? '0' : 'auto';
				$margin_right    = 'right' === $justify_content ? '0' : 'auto';

				// Constrained-specific styles for content width.
				// Exclude shape dividers and video backgrounds from constrained layout margins.
				$css .= "{$selector} > :where(:not(.alignleft):not(.alignright):not(.alignfull):not(.spectra-container__shape):not(.spectra-background-video__wrapper)) { max-width: {$content_size}; margin-left: {$margin_left} !important; margin-right: {$margin_right} !important; }";
				$css .= "{$selector} > .alignwide { max-width: {$wide_size}; }";

				$css .= $spacing_styles;
				break;

			case 'default':
			case 'flow':
				// Default block flow layout.
				$css .= "{$selector} { display: block; }";
				$css .= $alignment_styles;
				$css .= $spacing_styles;
				break;
		}

		return $css;
	}

	/**
	 * Merge core and custom CSS for a single device layout.
	 *
	 * Combines CSS from WordPress core layout functions with
	 * our custom layout CSS, avoiding duplicates.
	 *
	 * @since 3.0.0
	 * @param string $core_css CSS generated by WordPress core.
	 * @param string $custom_css Custom CSS generated by this class.
	 * @return string Merged CSS string without duplicates.
	 */
	private function merge_layout_css( $core_css, $custom_css ) {
		// If core CSS is empty, just return custom CSS.
		if ( empty( $core_css ) ) {
			return $custom_css;
		}

		// If custom CSS is empty, just return core CSS.
		if ( empty( $custom_css ) ) {
			return $core_css;
		}

		// For simple cases, just concatenate the CSS strings.
		return $core_css . ' ' . $custom_css;
	}

	/**
	 * Generates CSS for grid layout positioning within style attributes.
	 *
	 * This method processes style.layout properties to generate CSS for grid positioning,
	 * including column and row placement. It follows the device fallback hierarchy
	 * to ensure proper responsive behavior.
	 *
	 * @since 3.0.0
	 * @param array  $responsive_controls Responsive controls data containing style.layout properties.
	 * @param string $device              Device type to generate CSS for ('base', '@tablet', '@mobile').
	 * @param string $selector            CSS selector to target with the generated CSS.
	 * @return string Generated CSS for grid positioning.
	 */
	private function generate_style_layout_css( $responsive_controls, $device, $selector ) {
		// Only process if layout is in the style_responsive_keys list.
		if ( ! in_array( 'layout', $this->style_responsive_keys, true ) ) {
			return '';
		}

		// Get fallback device order for the target device.
		$fallback_devices = $this->device_fallback_order[ $device ] ?? array( 'base' );
		$layout_css       = '';

		// Find the first layout definition in the fallback chain.
		foreach ( $fallback_devices as $fallback_device ) {
			// Get layout from style.layout property.
			$layout = $responsive_controls[ $fallback_device ]['style']['layout'] ?? array();

			// Skip if no layout is defined for this device.
			if ( empty( $layout ) ) {
				continue;
			}

			// Initialize array to hold CSS declarations.
			$css_declarations = array();

			// Process self-stretch property — core's child-layout "Width": Fit / Fill / Fixed.
			$self_stretch = isset( $layout['selfStretch'] ) ? $layout['selfStretch'] : null;

			/*
			 * Each choice sets BOTH flex properties. A band used to emit only the
			 * property its own choice needed — `flex-basis` for Fixed, `flex-grow`
			 * for Fill, nothing at all for Fit — so a breakpoint that changed the
			 * choice inherited the wider band's other property: Fixed 120px on
			 * Desktop and Fit on Mobile still rendered 120px on phones, because
			 * the mobile band said nothing. Fit now resets both, Fill resets the
			 * basis, Fixed resets the grow.
			 */
			if ( in_array( $self_stretch, array( 'fixed', 'fixedNoShrink' ), true ) && isset( $layout['flexSize'] ) ) {
				// WordPress 7.1's Width control writes `fixedNoShrink` for "Fixed"
				// (core's layout support: basis + no shrink); `fixed` is the older
				// value. Only `fixed` was recognised, so a width fixed in the 7.1
				// editor rendered as Fit on the front end.
				$css_declarations['flex-basis']  = $layout['flexSize'];
				$css_declarations['flex-grow']   = '0';
				$css_declarations['flex-shrink'] = 'fixedNoShrink' === $self_stretch ? '0' : '1';
				$css_declarations['box-sizing']  = 'border-box';
			} elseif ( 'fill' === $self_stretch ) {
				$css_declarations['flex-grow']   = '1';
				$css_declarations['flex-shrink'] = '1';
				$css_declarations['flex-basis']  = 'auto';
			} elseif ( 'fit' === $self_stretch ) {
				$css_declarations['flex-grow']   = '0';
				$css_declarations['flex-shrink'] = '1';
				$css_declarations['flex-basis']  = 'auto';
			}

			// Process grid column positioning.
			$column_start = isset( $layout['columnStart'] ) ? $layout['columnStart'] : null;
			$column_span  = isset( $layout['columnSpan'] ) ? $layout['columnSpan'] : null;

			// Set grid-column property based on available values.
			if ( $column_start && $column_span ) {
				// Both start position and span are defined.
				$css_declarations['grid-column'] = "$column_start / span $column_span";
			} elseif ( $column_start ) {
				// Only start position is defined.
				$css_declarations['grid-column'] = "$column_start";
			} elseif ( $column_span ) {
				// Only span is defined.
				$css_declarations['grid-column'] = "span $column_span";
			}

			// Process grid row positioning.
			$row_start = isset( $layout['rowStart'] ) ? $layout['rowStart'] : null;
			$row_span  = isset( $layout['rowSpan'] ) ? $layout['rowSpan'] : null;

			// Set grid-row property based on available values.
			if ( $row_start && $row_span ) {
				// Both start position and span are defined.
				$css_declarations['grid-row'] = "$row_start / span $row_span";
			} elseif ( $row_start ) {
				// Only start position is defined.
				$css_declarations['grid-row'] = "$row_start";
			} elseif ( $row_span ) {
				// Only span is defined.
				$css_declarations['grid-row'] = "span $row_span";
			}

			// Generate CSS if we have any declarations.
			if ( ! empty( $css_declarations ) ) {
				// Use WordPress Style Engine to generate valid CSS.
				$layout_css = wp_style_engine_get_stylesheet_from_css_rules(
					array(
						array(
							// Core reads this with `?? null`, so an empty group is the
							// same as none at runtime; it is stated because the style
							// engine's signature declares the key, and D1 narrowed the
							// declarations to literal types precise enough for static
							// analysis to check the rule shape against it.
							'rules_group'  => '',
							'selector'     => $selector,
							'declarations' => $css_declarations,
						),
					),
					array(
						'prettify' => false, // Keep CSS compact.
					)
				);
			}

			// Stop after finding the first valid layout in the fallback chain.
			break;
		}

		// Always return a string, even if empty.
		return $layout_css;
	}

	/**
	 * Recursively process blocks to ensure unique spectraIds.
	 *
	 * @since 3.0.0
	 * @param array $blocks The blocks to process.
	 * @param array $seen_ids Reference to array tracking seen IDs.
	 * @param bool  $modified Reference to flag indicating if any IDs were changed.
	 * @return array The processed blocks with unique IDs.
	 */
	private function process_blocks_for_unique_ids( $blocks, &$seen_ids, &$modified ) {
		foreach ( $blocks as &$block ) {
			// Skip if not a Spectra block.
			if ( ! isset( $block['blockName'] ) || ! $this->should_apply_responsive_controls( $block ) ) {
				// Still process inner blocks if they exist.
				if ( ! empty( $block['innerBlocks'] ) ) {
					$block['innerBlocks'] = $this->process_blocks_for_unique_ids( $block['innerBlocks'], $seen_ids, $modified );
				}
				continue;
			}

			// Check if this block has a spectraId.
			if ( isset( $block['attrs']['spectraId'] ) ) {
				$current_id = $block['attrs']['spectraId'];

				// If we've seen this ID before, generate a new one.
				if ( isset( $seen_ids[ $current_id ] ) ) {
					$new_id                      = $this->generate_unique_spectra_id();
					$block['attrs']['spectraId'] = $new_id;
					$seen_ids[ $new_id ]         = true;
					$modified                    = true;
				} else {
					// Mark this ID as seen.
					$seen_ids[ $current_id ] = true;
				}
			} else {
				// No ID exists, generate one.
				$new_id                      = $this->generate_unique_spectra_id();
				$block['attrs']['spectraId'] = $new_id;
				$seen_ids[ $new_id ]         = true;
				$modified                    = true;
			}

			// Process inner blocks recursively.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->process_blocks_for_unique_ids( $block['innerBlocks'], $seen_ids, $modified );
			}
		}

		return $blocks;
	}

	/**
	 * Generate a unique spectraId.
	 *
	 * @since 3.0.0
	 * @return string A unique ID for the block.
	 */
	private function generate_unique_spectra_id() {
		return 'spectra-' . wp_generate_uuid4();
	}

	/**
	 * Remove core's inline spacing from a core/image figure.
	 *
	 * The margin this extension emits, banded, is defeated by the base value
	 * core writes inline — see the note at the call site. Only the spacing
	 * declarations are touched; anything else core put in that attribute stays.
	 *
	 * @since 3.0.0
	 * @param string $block_content The block's HTML content.
	 * @return string Modified HTML content with the inline margin removed.
	 */
	private function remove_core_image_inline_spacing( $block_content ) {
		// Use WordPress HTML Tag Processor to safely modify elements.
		$processor = new WP_HTML_Tag_Processor( $block_content );

		// Remove spacing properties from figure elements.
		while ( $processor->next_tag( 'figure' ) ) {
			$style_attr = $processor->get_attribute( 'style' );
			if ( empty( $style_attr ) ) {
				continue;
			}

			// Parse the style attribute.
			$styles   = $this->parse_inline_styles( $style_attr );
			$modified = false;

			// Remove spacing properties that conflict with our responsive controls.
			$spacing_properties_to_remove = array(
				'margin',
				'margin-top',
				'margin-right',
				'margin-bottom',
				'margin-left',
			);

			foreach ( $spacing_properties_to_remove as $property ) {
				if ( isset( $styles[ $property ] ) ) {
					unset( $styles[ $property ] );
					$modified = true;
				}
			}

			// Update the style attribute if we removed any properties.
			if ( $modified ) {
				if ( empty( $styles ) ) {
					// Remove the entire style attribute if no styles remain.
					$processor->remove_attribute( 'style' );
				} else {
					// Rebuild the style attribute with remaining styles.
					$new_style = $this->build_inline_styles( $styles );
					$processor->set_attribute( 'style', $new_style );
				}
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Remove core's inline dimensions from a core/image img element.
	 *
	 * Called only where this extension paints the image's dimensions itself,
	 * which is where core renders no viewport states of its own. See
	 * `paints_core_image_dimensions()`.
	 *
	 * @since 3.0.0
	 * @param string $block_content The block's HTML content.
	 * @return string Modified HTML content with the img's inline style removed.
	 */
	private function remove_core_image_inline_dimensions( $block_content ) {
		$processor = new WP_HTML_Tag_Processor( $block_content );

		while ( $processor->next_tag( 'img' ) ) {
			// Simply remove the entire style attribute from img elements.
			$processor->remove_attribute( 'style' );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Parse inline CSS styles into an associative array.
	 *
	 * @since 3.0.0
	 * @param string $style_attr The style attribute value.
	 * @return array Associative array of CSS property => value pairs.
	 */
	private function parse_inline_styles( $style_attr ) {
		$styles = array();

		// Split by semicolon and process each declaration.
		$declarations = explode( ';', $style_attr );

		foreach ( $declarations as $declaration ) {
			$declaration = trim( $declaration );
			if ( empty( $declaration ) ) {
				continue;
			}

			// Split by first colon to separate property and value.
			$colon_pos = strpos( $declaration, ':' );
			if ( false === $colon_pos ) {
				continue;
			}

			$property = trim( substr( $declaration, 0, $colon_pos ) );
			$value    = trim( substr( $declaration, $colon_pos + 1 ) );

			if ( ! empty( $property ) && ! empty( $value ) ) {
				$styles[ $property ] = $value;
			}
		}

		return $styles;
	}

	/**
	 * Build inline CSS styles from an associative array.
	 *
	 * @since 3.0.0
	 * @param array $styles Associative array of CSS property => value pairs.
	 * @return string CSS style string.
	 */
	private function build_inline_styles( $styles ) {
		$declarations = array();

		foreach ( $styles as $property => $value ) {
			$safe_property = sanitize_html_class( $property );
			$safe_value    = wp_strip_all_tags( preg_replace( '/[;<>{}]/', '', $value ) ); // phpcs:ignore WordPress.PHP.PrecisionCheck.FoundNonStrict -- strip CSS injection chars before WP sanitizer.

			if ( ! empty( $safe_property ) && '' !== $safe_value ) {
				$declarations[] = $safe_property . ':' . $safe_value;
			}
		}

		return implode( ';', $declarations );
	}

	/**
	 * Generate orientation reverse CSS for container blocks.
	 *
	 * Creates device-specific CSS rules for orientation reverse functionality
	 * with proper media queries and orientation-aware flex-direction values.
	 *
	 * @since 3.0.0
	 *
	 * @param string $spectra_id The unique block instance ID for CSS targeting.
	 * @param array  $attrs      The block attributes including responsiveControls.
	 * @return string Generated CSS or empty string if no orientation reverse is needed.
	 */
	private function generate_orientation_reverse_css( $spectra_id, $attrs ) {
		$responsive_controls = $attrs['responsiveControls'] ?? array();
		$layout              = $attrs['layout'] ?? array();
		$orientation_reverse = $attrs['orientationReverse'] ?? false;

		// Check if any breakpoint has orientation reverse enabled.
		$has_orientation_reverse = $orientation_reverse;
		if ( ! $has_orientation_reverse && ! empty( $responsive_controls ) ) {
			foreach ( array( 'base', '@tablet', '@mobile' ) as $device ) {
				if ( isset( $responsive_controls[ $device ]['orientationReverse'] ) && $responsive_controls[ $device ]['orientationReverse'] ) {
					$has_orientation_reverse = true;
					break;
				}
			}
		}

		if ( ! $has_orientation_reverse ) {
			return '';
		}

		$css_rules = array();
		$selector  = ".wp-block-spectra-container.wp-block-spectra-container[data-spectra-id='{$spectra_id}']";

		// Collect orientation data for each device from responsive controls.
		$orientation_devices = array();
		$default_orientation = $layout['orientation'] ?? 'horizontal';

		foreach ( array( 'base', '@tablet', '@mobile' ) as $device ) {
			if ( isset( $responsive_controls[ $device ]['layout']['orientation'] ) ) {
				$orientation_devices[ $device ] = $responsive_controls[ $device ]['layout']['orientation'];
			}
		}

		// Desktop orientation.
		$desktop_orientation = $orientation_devices['base'] ?? $default_orientation;

		// Calculate inherited orientation reverse values with proper inheritance chain.
		// Desktop: Use base attribute or explicit desktop setting.
		$desktop_reverse = $orientation_reverse || ( isset( $responsive_controls['base']['orientationReverse'] ) && ! empty( $responsive_controls['base']['orientationReverse'] ) );

		// Tablet: Check if explicitly set (including false), otherwise inherit from desktop.
		if ( array_key_exists( 'orientationReverse', $responsive_controls['@tablet'] ?? array() ) ) {
			// Explicit tablet setting exists (could be true or false) - use it.
			$tablet_reverse = ! empty( $responsive_controls['@tablet']['orientationReverse'] );
		} else {
			// No explicit tablet setting - inherit from desktop.
			$tablet_reverse = $desktop_reverse;
		}

		// Mobile: check if explicitly set (including false), otherwise inherit
		// from the BASE layer — core's model, no tablet inheritance. Legacy
		// content that relied on the old cascade is covered by the baked
		// cascade in LegacyStore, which copies the tablet bucket under mobile.
		if ( array_key_exists( 'orientationReverse', $responsive_controls['@mobile'] ?? array() ) ) {
			// Explicit mobile setting exists (could be true or false) - use it.
			$mobile_reverse = ! empty( $responsive_controls['@mobile']['orientationReverse'] );
		} else {
			// No explicit mobile setting - inherit from the base layer.
			$mobile_reverse = $desktop_reverse;
		}

		/*
		 * These rules are keyed on a device CLASS (`.is-vertical-tablet`), not on
		 * the base-plus-overrides layering the rest of the generator uses, so each
		 * device needs its own band — including desktop, which is why this is the
		 * one place here that asks for `@desktop`.
		 *
		 * The bands come from the same resolver as every other rule this class
		 * emits. They used to be written out by hand as 1024+ / 768-1023.98 /
		 * <=767.98, and once Spectra stopped imposing its own breakpoints that
		 * left a container reversing at widths where none of its other responsive
		 * styling applied: at 700px the block's spacing and typography resolved as
		 * tablet while its flex-direction resolved as mobile. The hand-written
		 * desktop floor was a second bug on its own — nothing at all matched
		 * between the tablet ceiling and 1024px.
		 */
		$bands = $this->get_device_media_queries();

		$devices = array(
			'@desktop' => array(
				'reverse'     => $desktop_reverse,
				'orientation' => $desktop_orientation,
				'selectors'   => array( '.is-%s-desktop' ),
			),
			'@tablet'  => array(
				'reverse'     => $tablet_reverse,
				'orientation' => $orientation_devices['@tablet'] ?? $desktop_orientation,
				'selectors'   => array( '.is-%s-tablet', '.is-%s-tablet-from-desktop' ),
			),
			'@mobile'  => array(
				'reverse'     => $mobile_reverse,
				'orientation' => $orientation_devices['@mobile'] ?? $desktop_orientation,
				'selectors'   => array( '.is-%s-mobile', '.is-%s-mobile-from-tablet', '.is-%s-mobile-from-desktop' ),
			),
		);

		foreach ( $devices as $state => $device ) {
			/*
			 * A band can legitimately be missing: a theme declaring only one
			 * breakpoint leaves core with only that state, and inventing a band
			 * here would put this rule outside every range the rest of the block
			 * renders in.
			 */
			if ( empty( $device['reverse'] ) || empty( $bands[ $state ] ) ) {
				continue;
			}

			$flex_direction = ( 'vertical' === $device['orientation'] ) ? 'column-reverse' : 'row-reverse';
			$targets        = array();

			foreach ( $device['selectors'] as $pattern ) {
				$targets[] = '  ' . $selector . sprintf( $pattern, $device['orientation'] );
			}

			$css_rules[] = '@media ' . $bands[ $state ] . ' {';
			$css_rules[] = implode( ",\n", $targets ) . ' {';
			$css_rules[] = "    flex-direction: {$flex_direction} !important;";
			$css_rules[] = '  }';
			$css_rules[] = '}';
		}

		return implode( "\n", $css_rules );
	}

	/**
	 * Get the default layout configuration for a specific block.
	 *
	 * Returns the default layout attributes that should be applied to blocks
	 * that don't have layout information defined. This is primarily used by
	 * the preview CSS generation system.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_name The name of the block (e.g., 'spectra/icons').
	 * @return array Default layout configuration or empty array if none defined.
	 */
	public function get_block_default_layout( $block_name ) {
		return $this->blocks_default_layout[ $block_name ] ?? array();
	}
}
