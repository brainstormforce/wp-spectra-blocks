<?php
/**
 * Legacy responsive-store support.
 *
 * Everything in this folder exists only to read content written before the store
 * adopted WordPress core's viewport vocabulary. It is deliberately removable:
 * the one current-path caller (`ResponsiveControls::normalize_render_attributes()`)
 * is guarded by `class_exists`, so removing legacy support is two steps and
 * nothing else (plus deleting the now-stale `use` import in the main class).
 *
 *   1. delete this folder
 *   2. delete the `LegacyStore` line from `ResponsiveControls::init()`
 *   3. delete `tests/phpunit/tests/Extensions/ResponsiveControls/Legacy/`, whose
 *      path mirrors this one so the tests go in the same step
 *
 * The editor half lives in `src/extensions/responsive-controls/legacy/` and is
 * removed the same way — delete the folder and its one `import './legacy';`.
 *
 * The `lg` / `md` / `sm` aliases this class used to republish for Spectra
 * Blocks Pro are gone: Pro reads canonical keys through its own
 * `ResponsiveControls::device_bucket()`, with a legacy fallback of its own.
 *
 * @package SpectraBlocks\Extensions\ResponsiveControls\Legacy
 * @since 1.0.7
 */

namespace SpectraBlocks\Extensions\ResponsiveControls\Legacy;

use SpectraBlocks\Extensions\ResponsiveControls\ViewportSupport;

/**
 * Reads pre-1.0.6 responsive stores and presents them in the current vocabulary.
 *
 * @since 1.0.7
 */
class LegacyStore {

	/**
	 * Legacy device keys mapped to the canonical viewport keys.
	 *
	 * Spectra stored breakpoints as `lg` / `md` / `sm` up to and including 1.0.5.
	 *
	 * Keep in sync with `LEGACY_DEVICE_MAP` in
	 * `src/extensions/responsive-controls/legacy/constants.js`.
	 *
	 * @var array<string, string>
	 * @since 1.0.7
	 */
	const DEVICE_MAP = array(
		'lg' => 'base',
		'md' => '@tablet',
		'sm' => '@mobile',
	);

	/**
	 * Whether a block belongs to the responsive-controls system at all.
	 *
	 * These filters run for every block on the page; a third-party block that
	 * happens to carry a `responsiveControls` attribute must not have it
	 * rewritten. Mirrors the prefixes and the one explicit inclusion the main
	 * extension allows.
	 *
	 * @since 1.0.7
	 * @param array<string, mixed> $block Block data.
	 * @return bool True for blocks the responsive system owns.
	 */
	private static function is_spectra_block( $block ) {
		$name = $block['blockName'] ?? '';

		if ( ! is_string( $name ) || '' === $name ) {
			return false;
		}

		return 0 === strpos( $name, 'spectra/' )
			|| 0 === strpos( $name, 'spectra-pro/' )
			|| 'core/image' === $name;
	}

	/**
	 * Register the legacy hooks.
	 *
	 * The rename runs at priority 3, ahead of the current path's own
	 * `render_block_data` work, so everything downstream only ever sees one
	 * vocabulary.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public static function init() {
		add_filter( 'render_block_data', array( __CLASS__, 'normalize_device_keys' ), 3, 1 );
		add_filter( 'render_block_data', array( __CLASS__, 'hydrate_markup_backed_states' ), 6, 1 );
		add_filter( 'render_block', array( __CLASS__, 'promote_markup_backed_base' ), 10, 2 );
	}

	/**
	 * Rewrite legacy `lg` / `md` / `sm` keys into the canonical viewport keys.
	 *
	 * Content is normalised as it is read rather than migrated in the database, so
	 * existing posts render correctly without being re-saved and downgrading stays
	 * safe. Where a block somehow carries both shapes for one breakpoint the
	 * canonical value wins, because it is the one the current editor wrote.
	 *
	 * @since 1.0.7
	 * @param array<string, mixed> $block Block data.
	 * @return array<string, mixed> Block data with canonical device keys.
	 */
	public static function normalize_device_keys( $block ) {
		if ( ! self::is_spectra_block( $block ) ) {
			return $block;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		$store = isset( $attrs['responsiveControls'] ) && is_array( $attrs['responsiveControls'] )
			? $attrs['responsiveControls']
			: array();

		if ( empty( $store ) ) {
			return $block;
		}

		$changed = false;

		foreach ( self::DEVICE_MAP as $legacy => $canonical ) {
			if ( ! array_key_exists( $legacy, $store ) ) {
				continue;
			}

			$legacy_value    = is_array( $store[ $legacy ] ) ? $store[ $legacy ] : array();
			$canonical_value = isset( $store[ $canonical ] ) && is_array( $store[ $canonical ] )
				? $store[ $canonical ]
				: array();

			$store[ $canonical ] = array_replace_recursive( $legacy_value, $canonical_value );
			unset( $store[ $legacy ] );
			$changed = true;
		}

		/*
		 * Bake the legacy cascade. The old generator resolved mobile as
		 * `sm -> md -> lg`, so a tablet value applied on phones whenever mobile
		 * was unset. The current generator follows core's model — each viewport
		 * over base only — which would change how that content renders. Copying
		 * the tablet bucket under the mobile one (mobile wins where both are
		 * set) preserves the authored rendering while the data itself moves to
		 * core semantics. Only blocks that actually carried legacy keys are
		 * baked; content authored since is already core-shaped.
		 */
		if ( $changed && isset( $store['@tablet'] ) && is_array( $store['@tablet'] ) && ! empty( $store['@tablet'] ) ) {
			$mobile           = isset( $store['@mobile'] ) && is_array( $store['@mobile'] ) ? $store['@mobile'] : array();
			$store['@mobile'] = array_replace_recursive( $store['@tablet'], $mobile );
		}

		if ( $changed ) {
			$attrs['responsiveControls'] = $store;
			$block['attrs']              = $attrs;
		}

		return $block;
	}

	/**
	 * Core's per-state path for each flat responsive key on a markup-backed block.
	 *
	 * Core keeps a `core/image`'s per-viewport dimensions NESTED inside the
	 * state — `style['@tablet']['dimensions']['width']` — and reads nothing from
	 * a flat `style['@tablet']['width']`. Verified on 7.1: the nested shape is
	 * banded into a media query scoped to a render-time `wp-states-…` class,
	 * the flat shape produces no CSS at all.
	 *
	 * Keep in sync with `MARKUP_BACKED_STATE_PATHS` in
	 * `src/extensions/responsive-controls/legacy/migrate-legacy.js`.
	 *
	 * @var array<string, array<int, string>>
	 * @since 1.0.9
	 */
	const MARKUP_BACKED_STATE_PATHS = array(
		'width'       => array( 'dimensions', 'width' ),
		'height'      => array( 'dimensions', 'height' ),
		'aspectRatio' => array( 'dimensions', 'aspectRatio' ),
		'scale'       => array( 'dimensions', 'objectFit' ),
	);

	/**
	 * The inline CSS property each flat base key is written as.
	 *
	 * The base has no state to live in — it is the `style` attribute on the
	 * rendered `<img>` — so promoting it means editing CSS, and two of these
	 * are not named the way the attribute is.
	 *
	 * Keep in sync with `BLOCK_RESPONSIVE_KEYS['core/image']` in
	 * `src/extensions/responsive-controls/utils/constants.js`, which is what the
	 * editor half promotes; a key present there and missing here renders in the
	 * editor and not on the front end.
	 *
	 * @var array<string, string>
	 * @since 1.0.9
	 */
	const MARKUP_BACKED_BASE_PROPERTIES = array(
		'width'       => 'width',
		'height'      => 'height',
		'aspectRatio' => 'aspect-ratio',
		'scale'       => 'object-fit',
	);

	/**
	 * Whether this block's `save()` writes its attributes into post markup.
	 *
	 * Mirrors `savesAttributesToMarkup()` in
	 * `src/extensions/responsive-controls/utils/constants.js`: Spectra's own
	 * blocks are server-rendered, anything else in scope (`core/image`) is not.
	 * An unknown name answers true, the safe side.
	 *
	 * @since 1.0.9
	 * @param string $name The block name.
	 * @return bool True when the block's markup carries its attributes.
	 */
	private static function saves_attributes_to_markup( $name ) {
		return 0 !== strpos( (string) $name, 'spectra/' )
			&& 0 !== strpos( (string) $name, 'spectra-pro/' );
	}

	/**
	 * Render the legacy store's narrower breakpoints through core's own states.
	 *
	 * A markup-backed block is not migrated at parse — its base layer is the
	 * saved markup and rewriting it fails block validation (#908). That leaves
	 * its narrower values with no renderer on 7.1: this extension stops painting
	 * image dimensions there (`paints_core_image_dimensions()` is false, because
	 * core bands viewport states itself), and core has no state to band. The
	 * tablet and mobile sizes are sitting in the store and nothing reads them.
	 *
	 * The editor half writes the same states on parse, but parsing does not
	 * dirty the post, so that only reaches the database when the author next
	 * saves. Doing it here as well means an upgraded site renders correctly
	 * immediately, with no re-save and no database change.
	 *
	 * Only the STATES are written. The base layer is left exactly as authored,
	 * so the inline width in the markup still wins at desktop and nothing about
	 * the saved HTML changes. An authored state always wins; the store only
	 * fills gaps.
	 *
	 * 7.1+ only. Below it, `remove_core_image_inline_dimensions()` strips the
	 * inline dimensions and this extension paints every breakpoint from the
	 * store — writing states there would hand the same values to a second
	 * renderer.
	 *
	 * @since 1.0.9
	 * @param array<string, mixed> $block Block data.
	 * @return array<string, mixed> Block data, carrying the migrated states.
	 */
	public static function hydrate_markup_backed_states( $block ) {
		if ( ! ViewportSupport::renders_states() ) {
			return $block;
		}

		$name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';

		if ( ! self::is_spectra_block( $block ) || ! self::saves_attributes_to_markup( $name ) ) {
			return $block;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$store = isset( $attrs['responsiveControls'] ) && is_array( $attrs['responsiveControls'] )
			? $attrs['responsiveControls']
			: array();

		if ( empty( $store ) ) {
			return $block;
		}

		$style   = isset( $attrs['style'] ) && is_array( $attrs['style'] ) ? $attrs['style'] : array();
		$changed = false;

		foreach ( array( '@tablet', '@mobile' ) as $state ) {
			$bucket = isset( $store[ $state ] ) && is_array( $store[ $state ] ) ? $store[ $state ] : array();

			if ( empty( $bucket ) ) {
				continue;
			}

			foreach ( self::MARKUP_BACKED_STATE_PATHS as $key => $path ) {
				if ( ! isset( $bucket[ $key ] ) || '' === $bucket[ $key ] ) {
					continue;
				}

				// An authored state wins, at either the nested or the flat position.
				if ( isset( $style[ $state ][ $path[0] ][ $path[1] ] ) || isset( $style[ $state ][ $key ] ) ) {
					continue;
				}

				$style[ $state ][ $path[0] ][ $path[1] ] = $bucket[ $key ];

				$changed = true;
			}
		}

		if ( ! $changed ) {
			return $block;
		}

		$attrs['style'] = $style;
		$block['attrs'] = $attrs;

		return $block;
	}

	/**
	 * Apply a markup-backed block's authored base value to the rendered HTML.
	 *
	 * On legacy content the root attribute is a scratch projection of whichever
	 * device was previewed at the last save, so a block last touched on Mobile
	 * ships the mobile width in its markup while the authored desktop value sits
	 * in the store's base bucket:
	 *
	 *     <img style="width:150px">                     the MOBILE value
	 *     responsiveControls: { lg: { width: 700px } }  the real desktop value
	 *
	 * `core/image` is a static block — its HTML comes from the saved markup, not
	 * from a render of its attributes — so correcting this means editing the
	 * rendered tag. `remove_core_image_inline_dimensions()` already rewrites the
	 * same attribute below 7.1; this is the 7.1 counterpart, narrowed to blocks
	 * that still carry a legacy store.
	 *
	 * The editor half promotes the same value into the attribute once the block
	 * is mounted, so the markup catches up on the next save and this stops
	 * finding anything to do. Until then, a page nobody has opened still renders
	 * what its author intended.
	 *
	 * ONLY PROMOTES, and only where the two actually disagree. A key the base
	 * bucket does not hold is left exactly as the markup has it.
	 *
	 * @since 1.0.9
	 * @param string               $block_content The rendered block HTML.
	 * @param array<string, mixed> $block         The block, post-`render_block_data`.
	 * @return string The block HTML, carrying the authored base dimensions.
	 */
	public static function promote_markup_backed_base( $block_content, $block ) {
		if ( ! ViewportSupport::renders_states() ) {
			return $block_content;
		}

		$name = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : '';

		if ( ! self::is_spectra_block( $block ) || ! self::saves_attributes_to_markup( $name ) ) {
			return $block_content;
		}

		if ( false === strpos( $block_content, '<img' ) ) {
			return $block_content;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$store = isset( $attrs['responsiveControls'] ) && is_array( $attrs['responsiveControls'] )
			? $attrs['responsiveControls']
			: array();

		if ( empty( $store ) ) {
			return $block_content;
		}

		// `normalize_device_keys()` has already run at priority 3, so the base
		// bucket is canonical by here; the legacy key is read as a fallback for
		// content that somehow skipped it.
		$base = array();

		if ( isset( $store['base'] ) && is_array( $store['base'] ) ) {
			$base = $store['base'];
		} elseif ( isset( $store['lg'] ) && is_array( $store['lg'] ) ) {
			$base = $store['lg'];
		}

		$promotable = array_filter(
			array_intersect_key( $base, self::MARKUP_BACKED_BASE_PROPERTIES ),
			static function ( $value ) {
				return '' !== trim( (string) $value );
			}
		);

		if ( empty( $promotable ) ) {
			return $block_content;
		}

		/*
		 * The narrower buckets, used to recognise a scratch root.
		 *
		 * The store is write-once history: nothing updates it after the upgrade.
		 * So "the markup differs from the base bucket" is NOT enough to promote
		 * on — once the author sets their own width the two differ forever, and
		 * promoting on every render would silently override them for good.
		 *
		 * What identifies a scratch value is that the markup still carries one
		 * of the NARROWER devices' values, which is what the pre-1.0.6 editor
		 * projected into it. An authored width matches none of them.
		 */
		$narrower = array();

		foreach ( array(
			'@tablet' => 'md',
			'@mobile' => 'sm',
		) as $canonical => $legacy ) {
			if ( isset( $store[ $canonical ] ) && is_array( $store[ $canonical ] ) ) {
				$narrower[] = $store[ $canonical ];
			} elseif ( isset( $store[ $legacy ] ) && is_array( $store[ $legacy ] ) ) {
				$narrower[] = $store[ $legacy ];
			}
		}

		if ( empty( $narrower ) ) {
			return $block_content;
		}

		$processor = new \WP_HTML_Tag_Processor( $block_content );

		if ( ! $processor->next_tag( array( 'tag_name' => 'img' ) ) ) {
			return $block_content;
		}

		$style = (string) $processor->get_attribute( 'style' );

		foreach ( self::MARKUP_BACKED_BASE_PROPERTIES as $key => $property ) {
			if ( empty( $base[ $key ] ) ) {
				continue;
			}

			// Only a value that still looks like a projection is replaced.
			$current = array();

			if ( preg_match( '/(^|;)\s*' . $property . '\s*:\s*([^;]+)/i', $style, $current ) ) {
				$current = trim( $current[2] );
			} else {
				$current = '';
			}

			$is_scratch = false;

			foreach ( $narrower as $bucket ) {
				if ( isset( $bucket[ $key ] ) && trim( (string) $bucket[ $key ] ) === $current ) {
					$is_scratch = true;
					break;
				}
			}

			if ( ! $is_scratch ) {
				continue;
			}

			$value   = $base[ $key ];
			$pattern = '/(^|;)\s*' . $property . '\s*:[^;]*/i';

			$style = preg_match( $pattern, $style )
				? (string) preg_replace( $pattern, '$1' . $property . ':' . $value, $style, 1 )
				: rtrim( $style, '; ' ) . ( '' === trim( $style ) ? '' : ';' ) . $property . ':' . $value;
		}

		$processor->set_attribute( 'style', $style );

		return $processor->get_updated_html();
	}
}
