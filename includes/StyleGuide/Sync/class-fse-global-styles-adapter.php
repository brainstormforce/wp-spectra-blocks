<?php
/**
 * FSE Global Styles Adapter — reads/writes a pure-FSE theme's color palette in
 * the native `wp_global_styles` user post.
 *
 * Store mechanics only (no role knowledge). Writes obey the whole-palette rule:
 * the mapped slugs are recolored in place and every other entry — the theme's
 * own unmapped slugs, user-added `custom` colors, non-hex values like
 * `transparent` — is preserved. Uses a raw `$wpdb->update` (mirroring the font
 * sync) because `content_save_pre` would mangle the JSON's unicode escapes.
 *
 * @package Spectra\StyleGuide
 * @since   x.x.x
 */

namespace SpectraBlocks\StyleGuide\Sync;

use SpectraBlocks\StyleGuide\TokenRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FseGlobalStylesAdapter
 *
 * @since x.x.x
 */
class FseGlobalStylesAdapter implements ColorSyncAdapter {

	/**
	 * Whether the FSE global-styles store is the effective color store.
	 *
	 * Gated on `wp_is_block_theme()`: hybrid themes like Astra also expose
	 * `wp_global_styles`, but writes there are ignored in favor of their own
	 * option store, so only true block/FSE themes use this adapter.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	public function is_supported(): bool {
		return class_exists( '\WP_Theme_JSON_Resolver' ) && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/**
	 * Human-readable label.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function label(): string {
		return 'FSE Global Styles (wp_global_styles)';
	}

	/**
	 * FSE themes use the generic role → slug mapping, so no theme-specific patch.
	 *
	 * @since x.x.x
	 *
	 * @param TokenRegistry $tokens The computed Style Guide token registry.
	 * @return array<string, string> Always empty (fall back to generic).
	 */
	public function resolve_patch( TokenRegistry $tokens ): array {
		unset( $tokens );
		return array();
	}

	/**
	 * Read the effective theme palette as slug => color.
	 *
	 * Merges the theme.json `theme` palette (base) with the user post's `theme`
	 * overrides (user wins), so every theme-defined slug is present even before
	 * the user has customized anything.
	 *
	 * @since x.x.x
	 *
	 * @return array<string, string>
	 */
	public function read(): array {
		if ( ! $this->is_supported() ) {
			return array();
		}

		$by_slug = $this->theme_json_palette();

		$post = $this->get_user_post();
		if ( $post instanceof \WP_Post ) {
			$content = json_decode( $post->post_content, true );
			if ( is_array( $content ) ) {
				foreach ( $this->extract_theme_entries( $content ) as $entry ) {
					if ( is_array( $entry ) && isset( $entry['slug'], $entry['color'] ) && is_string( $entry['slug'] ) && is_string( $entry['color'] ) ) {
						$by_slug[ $entry['slug'] ] = $entry['color'];
					}
				}
			}
		}

		return $by_slug;
	}

	/**
	 * Patch mapped slugs into the user post's `theme` palette, preserving all
	 * other entries, then flush the global-styles caches.
	 *
	 * @since x.x.x
	 *
	 * @param array<string, string> $patch slug => hex.
	 * @return bool
	 */
	public function write( array $patch ): bool {
		if ( ! $this->is_supported() || empty( $patch ) ) {
			return false;
		}

		$post = $this->get_user_post();
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		$content = json_decode( $post->post_content, true );
		if ( ! is_array( $content ) ) {
			$content = array();
		}

		// Seed the base array: existing user overrides if present, else the
		// theme.json palette so a fresh post still writes the full palette.
		$existing = $this->extract_theme_entries( $content );
		if ( empty( $existing ) ) {
			foreach ( $this->theme_json_palette() as $slug => $hex ) {
				$existing[] = array(
					'slug'  => $slug,
					'color' => $hex,
					'name'  => TokenRegistry::format_slug_label( $slug ),
				);
			}
		}

		// Patch by slug in place; preserve foreign entries; append mapped slugs
		// that aren't present yet.
		$merged = array();
		$seen   = array();
		foreach ( $existing as $entry ) {
			$slug = ( is_array( $entry ) && isset( $entry['slug'] ) && is_string( $entry['slug'] ) ) ? $entry['slug'] : '';
			if ( '' !== $slug && isset( $patch[ $slug ] ) ) {
				$entry['color'] = $patch[ $slug ];
				if ( empty( $entry['name'] ) ) {
					$entry['name'] = TokenRegistry::format_slug_label( $slug );
				}
				$seen[ $slug ] = true;
			}
			$merged[] = $entry;
		}
		foreach ( $patch as $slug => $hex ) {
			if ( ! isset( $seen[ $slug ] ) ) {
				$merged[] = array(
					'slug'  => (string) $slug,
					'color' => (string) $hex,
					'name'  => TokenRegistry::format_slug_label( (string) $slug ),
				);
			}
		}

		// Keep the native user global-styles envelope intact.
		$content['version']                     = isset( $content['version'] ) ? $content['version'] : 3;
		$content['isGlobalStylesUserThemeJSON'] = true;
		if ( ! isset( $content['settings'] ) || ! is_array( $content['settings'] ) ) {
			$content['settings'] = array();
		}
		if ( ! isset( $content['settings']['color'] ) || ! is_array( $content['settings']['color'] ) ) {
			$content['settings']['color'] = array();
		}
		if ( ! isset( $content['settings']['color']['palette'] ) || ! is_array( $content['settings']['color']['palette'] ) ) {
			$content['settings']['color']['palette'] = array();
		}
		$content['settings']['color']['palette']['theme'] = $merged;

		$encoded = wp_json_encode( $content, JSON_UNESCAPED_UNICODE );
		if ( false === $encoded ) {
			return false;
		}

		// Raw update — content_save_pre would mangle the JSON unicode escapes.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- intentional bypass of wp_update_post to avoid content_save_pre.
		$wpdb->update(
			$wpdb->posts,
			array( 'post_content' => $encoded ),
			array( 'ID' => $post->ID ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $post->ID );
		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
		}

		return true;
	}

	/**
	 * The active theme's user global-styles post (get-or-create).
	 *
	 * @since x.x.x
	 *
	 * @return \WP_Post|null
	 */
	private function get_user_post(): ?\WP_Post {
		$post_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
		$post    = $post_id ? get_post( $post_id ) : null;
		return $post instanceof \WP_Post ? $post : null;
	}

	/**
	 * The `theme`-origin entries from decoded user post content.
	 *
	 * @since x.x.x
	 *
	 * @param array<string, mixed> $content Decoded wp_global_styles post content.
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_theme_entries( array $content ): array {
		$settings = ( isset( $content['settings'] ) && is_array( $content['settings'] ) ) ? $content['settings'] : array();
		$color    = ( isset( $settings['color'] ) && is_array( $settings['color'] ) ) ? $settings['color'] : array();
		$palette  = ( isset( $color['palette'] ) && is_array( $color['palette'] ) ) ? $color['palette'] : array();
		$theme    = ( isset( $palette['theme'] ) && is_array( $palette['theme'] ) ) ? $palette['theme'] : array();
		return $theme;
	}

	/**
	 * The theme.json `theme` palette as slug => color, read from the RAW files.
	 *
	 * Deliberately does NOT go through `WP_Theme_JSON_Resolver::get_theme_data()`:
	 * the resolver's output passes through the `wp_theme_json_data_theme` filters,
	 * where the Style Guide's own runtime overrides (GlobalStylesBridge palette
	 * injection, Spectra One compat) feed the SG colours back into this read. That
	 * made the push self-observing — the change-check always saw the store as
	 * "already in sync" and skipped the write, so a re-save of an unchanged palette
	 * could never backfill an empty user global-styles post. The raw files are also
	 * the correct seed for a fresh post: runtime-only entries (`sg-*`, status
	 * colours) must never be persisted as theme data.
	 *
	 * Parses parent theme.json first, then child (child wins), so child-theme
	 * palette overrides are honoured.
	 *
	 * @since x.x.x
	 *
	 * @return array<string, string>
	 */
	private function theme_json_palette(): array {
		$out = array();

		$dirs = array_unique( array( get_template_directory(), get_stylesheet_directory() ) );
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
				if ( is_array( $entry ) && isset( $entry['slug'], $entry['color'] ) && is_string( $entry['slug'] ) && is_string( $entry['color'] ) ) {
					$out[ $entry['slug'] ] = $entry['color'];
				}
			}
		}
		return $out;
	}
}
