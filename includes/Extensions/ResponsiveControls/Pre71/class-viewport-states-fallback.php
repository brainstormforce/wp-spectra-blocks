<?php
/**
 * Pre-7.1 front-end support for core's viewport states.
 *
 * WordPress 7.1 renders `style['@tablet']` / `style['@mobile']` itself. Below
 * it that renderer does not exist, so the groups this extension leaves to core
 * — colour, dimensions, … — were silently dropped from the front end. This
 * unit supplies exactly that missing half and nothing else.
 *
 * It is deliberately removable, and never registers on 7.1. Removing pre-7.1
 * support is two steps:
 *
 *   1. delete this folder
 *   2. delete the guarded `ViewportStatesFallback::init()` call in
 *      `ResponsiveControls::init()`
 *   3. delete the now-stale `use` import at the top of that class
 *
 * The editor half lives in `src/extensions/responsive-controls/pre-71/` and is
 * removed the same way — delete the folder and its one import plus call.
 *
 * This folder holds a second, independent unit: `StorePrecedence`, whose own
 * header lists its call site. Deleting the folder must take that call and its
 * `use` import with it, or the main class keeps a guarded reference to a class
 * that no longer exists.
 *
 * **Removal condition:** the plugin's minimum supported WordPress is 7.1. That
 * is a different trigger from `../Legacy/`, which serves pre-1.0.6 CONTENT; the
 * two units are independent and must not be merged.
 *
 * @package SpectraBlocks\Extensions\ResponsiveControls\Pre71
 * @since 1.0.7
 */

namespace SpectraBlocks\Extensions\ResponsiveControls\Pre71;

use SpectraBlocks\Extensions\ResponsiveControls;
use SpectraBlocks\Extensions\ResponsiveControls\ViewportSupport;

/**
 * Renders viewport states where core cannot.
 *
 * @since 1.0.7
 */
class ViewportStatesFallback {

	/**
	 * Blocks whose states have already been emitted this request.
	 *
	 * @var array<string, bool>
	 * @since 1.0.7
	 */
	private static $emitted = array();

	/**
	 * Register the filter, unless core already renders states.
	 *
	 * @since 1.0.7
	 * @return void
	 */
	public static function init() {
		if ( ViewportSupport::has_viewport_states() ) {
			return;
		}

		add_filter( 'render_block', array( __CLASS__, 'render' ), 11, 2 );
	}

	/**
	 * Emit the viewport states core cannot render on this version.
	 *
	 * Core's `wp_render_block_states_support()` — the renderer that turns
	 * `style['@tablet']` / `style['@mobile']` into banded CSS — shipped in
	 * 7.1. Below it, the groups this extension does NOT generate itself
	 * (colour, dimensions, …) were silently dropped from the front end: a
	 * per-device text colour saved fine and never rendered.
	 *
	 * This is the missing half, active only where core's renderer does not
	 * exist. By the time `render_block` runs, `remove_conflicting_core_attributes()`
	 * has already stripped the groups Spectra's own generator emits, so what
	 * remains in each state is exactly the core-owned remainder. Each state is
	 * compiled with the style engine and emitted inside the canonical band
	 * with `!important` — the same precedence core's 7.1 renderer uses,
	 * because the base value sits inline on the element.
	 *
	 * @since 1.0.7
	 *
	 * @param string       $block_content The block's rendered HTML.
	 * @param array<mixed> $block         The parsed block.
	 * @return string The HTML, preceded by a style tag when states rendered.
	 */
	public static function render( $block_content, $block ) {
		$responsive   = ResponsiveControls::instance();
		$style_handle = 'spectra-responsive-styles';

		if ( ! $responsive instanceof ResponsiveControls ) {
			return $block_content;
		}

		$block_name = $block['blockName'] ?? '';

		if ( ! $responsive->should_apply_responsive_controls( array( 'blockName' => $block_name ) ) ) {
			return $block_content;
		}

		$style      = $block['attrs']['style'] ?? null;
		$spectra_id = $block['attrs']['spectraId'] ?? '';

		if ( ! is_array( $style ) || '' === $spectra_id ) {
			return $block_content;
		}

		/*
		 * The same bands the generator uses, so this fallback and the rest of a
		 * block agree — including when a theme declares its own
		 * `settings.viewport`, which overrides what Spectra publishes.
		 */
		$bands = $responsive->get_media_queries();

		unset( $bands['base'] );

		$css = '';

		foreach ( $bands as $state => $media ) {
			$state_style = $style[ $state ] ?? null;

			if ( ! is_array( $state_style ) || array() === $state_style ) {
				continue;
			}

			/*
			 * The engine returns an EMPTY array — no `declarations` key at all —
			 * when a state carries nothing it can compile, which is the norm
			 * here: a state often holds only Spectra's own flat keys (`size`,
			 * `enableTextShadow`, …) or groups already stripped for the
			 * generator. Read it defensively; the stubs claim the offset always
			 * exists, but runtime disagrees.
			 */
			$compiled = wp_style_engine_get_styles( $state_style );

			/*
			 * Cast through an array so the missing key is a runtime fact rather
			 * than an assumption: the engine returns an EMPTY array — no
			 * `declarations` key at all — when a state carries nothing it can
			 * compile, which is the norm here, because a state often holds only
			 * Spectra's own flat keys (`size`, `enableTextShadow`, …) or groups
			 * already stripped for the generator. The stubs type the offset as
			 * always present; runtime disagrees, and reading it directly warned
			 * on every front-end render.
			 */
			$compiled_parts = (array) $compiled;
			$declarations   = array();

			foreach ( $compiled_parts as $part_key => $part_value ) {
				if ( 'declarations' === $part_key && is_array( $part_value ) ) {
					$declarations = $part_value;
				}
			}

			if ( array() === $declarations ) {
				continue;
			}

			$rule = '';

			foreach ( $declarations as $property => $value ) {
				$rule .= $property . ':' . $value . ' !important;';
			}

			$css .= '@media ' . $media . '{[data-spectra-id=\'' . esc_attr( $spectra_id ) . '\']{' . $rule . '}}';
		}

		if ( '' === $css ) {
			return $block_content;
		}

		// Same delivery as the responsive generator — one pipeline to the page.
		$dedupe_key = 'states-' . $spectra_id;

		if ( ! isset( self::$emitted[ $dedupe_key ] ) ) {
			wp_enqueue_style( $style_handle );
			wp_add_inline_style( $style_handle, ResponsiveControls::sanitize_inline_css( $css ) );
			self::$emitted[ $dedupe_key ] = true;
		}

		return $block_content;
	}
}
