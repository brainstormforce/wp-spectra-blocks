<?php
/**
 * Palette Cleanup — a ONE-TIME, self-contained migration that strips Spectra
 * colours older builds persisted into `wp_global_styles`.
 *
 * Background: earlier versions PUSHED the full Style Guide palette (core roles,
 * status colours, `sg-*` aliases and whole `spectra-*` shade ramps) into the
 * theme's global-styles post. That push has since been removed, so no NEW bloat
 * accrues — but sites that ran an older build still carry the stale entries. This
 * class heals that data exactly once, then flags itself done so it never repeats.
 *
 * REMOVAL: this file is intentionally isolated so it can be deleted wholesale once
 * every site has upgraded past the build that introduced it. To retire it, delete
 * this file and the single `PaletteCleanup::register()` call in GlobalStylesBridge.
 * Nothing else references it.
 *
 * @package Spectra\StyleGuide
 * @since   x.x.x
 */

namespace SpectraBlocks\StyleGuide\Sync;

use SpectraBlocks\StyleGuide\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PaletteCleanup
 *
 * @since x.x.x
 */
class PaletteCleanup {

	/**
	 * Option flag marking that the one-time cleanup has run, so it never repeats
	 * on subsequent admin loads.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const FLAG = 'spectra_blocks_sg_palette_cleaned';

	/**
	 * The Style Guide engine (source of the currently-managed colour slugs).
	 *
	 * @since x.x.x
	 * @var Engine
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 *
	 * @param Engine $engine Style Guide engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Hook the one-time cleanup to `admin_init`.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_run' ) );
	}

	/**
	 * Run {@see run()} exactly once per site — the first admin request after this
	 * build activates or the plugin is upgraded — then set a flag so it never
	 * repeats.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function maybe_run(): void {
		if ( get_option( self::FLAG ) ) {
			return;
		}
		// Set the flag first so a fatal or timeout mid-cleanup can't loop it every
		// request; a partial clean is harmless (each post is idempotent to strip).
		update_option( self::FLAG, 1, false );
		$this->run();
	}

	/**
	 * Strip Spectra-injected colours from EVERY `wp_global_styles` post.
	 *
	 * Idempotent — safe to run more than once. Public so a WP-CLI command or manual
	 * re-run can invoke it directly.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function run(): void {
		if ( ! class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			return;
		}

		$posts = get_posts(
			array(
				'post_type'        => 'wp_global_styles',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		foreach ( $posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$content = json_decode( $post->post_content, true );
			$this->strip_post( (int) $post->ID, is_array( $content ) ? $content : array() );
		}
	}

	/**
	 * Strip the Spectra-injected colours from ONE wp_global_styles post — the
	 * save-time counterpart to {@see run()}. Called after a Site Editor save so the
	 * runtime-injected Style Guide swatches the editor captured into the post are
	 * removed again, leaving only the theme's own colours (the theme's swatches are
	 * preserved; see {@see strip_post()}). Idempotent; raw write, no save_post.
	 *
	 * @since x.x.x
	 *
	 * @param int $post_id wp_global_styles post ID.
	 * @return void
	 */
	public function clean_post( int $post_id ): void {
		if ( $post_id <= 0 ) {
			return;
		}
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		$content = json_decode( $post->post_content, true );
		$this->strip_post( $post_id, is_array( $content ) ? $content : array() );
	}

	/**
	 * Remove the Spectra-injected colour slugs from a single wp_global_styles post's
	 * `palette.theme`, leaving only the theme's own colours.
	 *
	 * Writes back with a raw `$wpdb` update — like {@see FseGlobalStylesAdapter::write}
	 * — which does NOT fire `save_post`, so it can't re-enter the pull hook. A no-op
	 * when the post carries no Spectra-injected slugs.
	 *
	 * @since x.x.x
	 *
	 * @param int                  $post_id wp_global_styles post ID.
	 * @param array<string, mixed> $content Decoded post content.
	 * @return void
	 */
	private function strip_post( int $post_id, array $content ): void {
		if ( $post_id <= 0 ) {
			return;
		}

		// Descend with is_array guards at each level (WP may serialise an empty
		// settings/color/palette node as a stdClass, so a chained array access would
		// fatal). Bail unless a real theme palette array is present.
		$settings = ( isset( $content['settings'] ) && is_array( $content['settings'] ) ) ? $content['settings'] : null;
		$color    = ( null !== $settings && isset( $settings['color'] ) && is_array( $settings['color'] ) ) ? $settings['color'] : null;
		$palette  = ( null !== $color && isset( $color['palette'] ) && is_array( $color['palette'] ) ) ? $color['palette'] : null;
		$before   = ( null !== $palette && isset( $palette['theme'] ) && is_array( $palette['theme'] ) ) ? $palette['theme'] : null;
		if ( null === $settings || null === $color || null === $palette || null === $before ) {
			return;
		}

		$managed = array_flip( $this->engine->get_managed_color_slugs() );

		// Slugs the ACTIVE THEME declares in its own theme.json — these are NEVER
		// removed, even when they overlap the managed set. Many core roles
		// (primary/secondary/heading/body/background/surface/foreground/outline/
		// neutral) ARE the theme's own palette slugs, so stripping by managed-set
		// alone would delete the theme's colours. Guarding on theme.json ensures the
		// cleanup only removes what Spectra ADDED (accent, status, custom, sg-*, the
		// spectra-* shade ramps) and leaves every theme-native colour intact.
		$theme_native = $this->theme_palette_slugs();

		// Drop only Spectra-INJECTED, non-theme entries: a currently-managed slug OR
		// any `spectra-*` shade token (`spectra-neutral-*`, `spectra-chromatic-*-*`,
		// `spectra-white`) OR any `sg-*` alias — UNLESS the theme itself declares the
		// slug. The prefix checks also clear LEGACY bloat older builds persisted
		// (full shade ramps + `sg-secondary`/`sg-neutral`).
		$after = array_values(
			array_filter(
				$before,
				static function ( $entry ) use ( $managed, $theme_native ) {
					if ( ! is_array( $entry ) || ! isset( $entry['slug'] ) || ! is_string( $entry['slug'] ) ) {
						return true;
					}
					$slug = $entry['slug'];
					if ( isset( $theme_native[ $slug ] ) ) {
						return true; // Theme's own colour — always keep.
					}
					$is_spectra = isset( $managed[ $slug ] )
						|| 0 === strpos( $slug, 'spectra-' )
						|| 0 === strpos( $slug, 'sg-' );
					return ! $is_spectra;
				}
			)
		);

		if ( count( $after ) === count( $before ) ) {
			return; // Nothing Spectra-injected to strip.
		}

		// Reassemble through the narrowed arrays (avoids mixed-offset access).
		$palette['theme']    = $after;
		$color['palette']    = $palette;
		$settings['color']   = $color;
		$content['settings'] = $settings;

		$encoded = wp_json_encode( $content, JSON_UNESCAPED_UNICODE );
		if ( false === $encoded ) {
			return;
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- raw write avoids content_save_pre mangling + save_post recursion.
		$wpdb->update( $wpdb->posts, array( 'post_content' => $encoded ), array( 'ID' => $post_id ), array( '%s' ), array( '%d' ) );
		clean_post_cache( $post_id );
		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
		}
	}

	/**
	 * The active theme's palette slugs, as a `slug => true` lookup, read from the
	 * RAW theme.json files (parent then child).
	 *
	 * Reading the raw files — not the resolver — keeps this independent of the
	 * Style Guide's own runtime palette filters, so a theme colour can never be
	 * mistaken for a Spectra injection. Statically cached per request.
	 *
	 * @since x.x.x
	 *
	 * @return array<string, true>
	 */
	private function theme_palette_slugs(): array {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$slugs = array();
		$dirs  = array_unique( array( get_template_directory(), get_stylesheet_directory() ) );
		foreach ( $dirs as $dir ) {
			$file = $dir . '/theme.json';
			if ( ! is_readable( $file ) ) {
				continue;
			}
			$data = wp_json_file_decode( $file, array( 'associative' => true ) );
			if ( ! is_array( $data ) ) {
				continue;
			}
			$settings = ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) ? $data['settings'] : array();
			$color    = ( isset( $settings['color'] ) && is_array( $settings['color'] ) ) ? $settings['color'] : array();
			$palette  = ( isset( $color['palette'] ) && is_array( $color['palette'] ) ) ? $color['palette'] : array();
			foreach ( $palette as $entry ) {
				if ( is_array( $entry ) && isset( $entry['slug'] ) && is_string( $entry['slug'] ) ) {
					$slugs[ $entry['slug'] ] = true;
				}
			}
		}

		$cache = $slugs;
		return $cache;
	}
}
