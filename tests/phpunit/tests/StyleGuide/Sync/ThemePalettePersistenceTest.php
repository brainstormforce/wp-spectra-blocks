<?php
/**
 * Tests for keeping the theme's global-styles palette free of persisted Spectra
 * colours.
 *
 * Two invariants, both on a throwaway block theme built in the temp dir:
 *   1. PUSH does NOT persist the Style Guide palette into `wp_global_styles` — the
 *      FSE store write was removed; SG colours live only in the SG option and
 *      render at runtime, so a push must leave the user global-styles post alone.
 *   2. The one-time {@see PaletteCleanup} strips Spectra-INJECTED slugs (managed
 *      roles, status, `sg-*` aliases, `spectra-*` shade ramps) from a bloated post
 *      while PRESERVING every slug the active theme.json declares — a theme colour
 *      must never be removed even when it overlaps the managed set.
 *
 * @package Spectra\Tests\StyleGuide\Sync
 * @since   x.x.x
 */

namespace SpectraBlocks\Tests\StyleGuide\Sync;

use SpectraBlocks\StyleGuide\Engine;
use SpectraBlocks\StyleGuide\Sync\ColorRoles;
use SpectraBlocks\StyleGuide\Sync\PaletteCleanup;
use SpectraBlocks\StyleGuide\Sync\SyncOrchestrator;
use WP_UnitTestCase;

/**
 * ThemePalettePersistenceTest test case.
 *
 * @since x.x.x
 */
class ThemePalettePersistenceTest extends WP_UnitTestCase {

	/**
	 * Fixture theme slug (a throwaway block theme written to the temp dir by
	 * {@see build_fixture_theme()} — nothing lives in the repo).
	 *
	 * @var string
	 */
	const THEME = 'sync-palette-persist';

	/**
	 * Saved Style Guide colors — deliberately different from every raw theme.json
	 * value so a real diff always exists.
	 *
	 * @var array<string, string>
	 */
	const SG_COLORS = array(
		'primary'    => '#123456',
		'secondary'  => '#234567',
		'accent'     => '#345678',
		'background' => '#fdfdfd',
		'surface'    => '#e0e0e0',
		'outline'    => '#c1c1c1',
		'neutral'    => '#818181',
		'body'       => '#212121',
		'heading'    => '#050505',
	);

	/**
	 * Raw theme.json palette the fixture theme ships — the theme's OWN slugs. Every
	 * one of these must survive the cleanup; `theme-own` is a slug the Style Guide
	 * never manages.
	 *
	 * @var array<string, string>
	 */
	const THEME_PALETTE = array(
		'primary'    => '#101010',
		'secondary'  => '#202020',
		'accent'     => '#303030',
		'background' => '#fefefe',
		'surface'    => '#ededed',
		'body'       => '#010101',
		'heading'    => '#020202',
		'outline'    => '#cccccc',
		'neutral'    => '#909090',
		'theme-own'  => '#badbad',
	);

	/**
	 * Set up: build a throwaway block theme in the temp dir, activate it, map its slugs.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// Admin context, as in the Site Editor: the resolver's auto-created user
		// post only receives its wp_theme term when the user can assign terms.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		register_theme_directory( $this->build_fixture_theme() );
		wp_clean_themes_cache();
		switch_theme( self::THEME );

		if ( self::THEME !== get_stylesheet() || ! wp_is_block_theme() ) {
			$this->markTestSkipped( 'Fixture block theme could not be activated.' );
		}

		if ( class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			\WP_Theme_JSON_Resolver::clean_cached_data();
		}

		// The fixture theme has no curated profile — supply its role → slug map.
		add_filter(
			'spectra_style_guide_theme_color_mapping',
			static function ( $map, $stylesheet ) {
				if ( self::THEME !== $stylesheet ) {
					return $map;
				}
				return array(
					ColorRoles::PRIMARY         => 'primary',
					ColorRoles::SECONDARY       => 'secondary',
					ColorRoles::ACCENT          => 'accent',
					ColorRoles::PAGE_BACKGROUND => 'background',
					ColorRoles::SURFACE         => 'surface',
					ColorRoles::BODY_TEXT       => 'body',
					ColorRoles::HEADING_TEXT    => 'heading',
					ColorRoles::BORDER          => 'outline',
					ColorRoles::MUTED           => 'neutral',
				);
			},
			10,
			2
		);
	}

	/**
	 * Write the minimal block-theme fixture (style.css + theme.json +
	 * templates/index.html) under the temp dir and return its themes root.
	 * Idempotent across tests; nothing is committed to the repo.
	 *
	 * @return string Theme directory root to register.
	 */
	private function build_fixture_theme(): string {
		$root  = untrailingslashit( get_temp_dir() ) . '/spectra-palette-fixture-themes';
		$theme = $root . '/' . self::THEME;
		wp_mkdir_p( $theme . '/templates' );

		$palette = array();
		foreach ( self::THEME_PALETTE as $slug => $hex ) {
			$palette[] = array(
				'slug'  => $slug,
				'color' => $hex,
				'name'  => ucfirst( $slug ),
			);
		}
		$theme_json = array(
			'version'  => 3,
			'settings' => array( 'color' => array( 'palette' => $palette ) ),
		);

		file_put_contents( $theme . '/style.css', "/*\nTheme Name: Sync Palette Persist Fixture\nVersion: 1.0.0\nText Domain: " . self::THEME . "\n*/\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture in temp dir.
		file_put_contents( $theme . '/theme.json', (string) wp_json_encode( $theme_json ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture in temp dir.
		file_put_contents( $theme . '/templates/index.html', "<!-- wp:post-content /-->\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture in temp dir.

		return $root;
	}

	/**
	 * Save a Style Guide config and reset the engine's caches.
	 *
	 * @return Engine
	 */
	private function save_style_guide(): Engine {
		update_option(
			Engine::OPTION_KEY,
			array(
				'version'       => 2,
				'colors'        => self::SG_COLORS,
				'custom_colors' => array(),
			),
			false
		);

		$engine = Engine::get_instance();
		$engine->flush_caches();

		return $engine;
	}

	/**
	 * Register a filter mimicking the Style Guide's runtime palette overwrite: the
	 * filtered theme data carries the SG colors plus a runtime-only swatch, exactly
	 * what GlobalStylesBridge produces. None of it may reach the persisted store.
	 *
	 * @return void
	 */
	private function register_runtime_overwrite(): void {
		add_filter(
			'wp_theme_json_data_theme',
			static function ( $theme_json ) {
				$palette = array();
				foreach ( self::SG_COLORS as $slug => $hex ) {
					$palette[] = array(
						'slug'  => $slug,
						'color' => $hex,
						'name'  => ucfirst( $slug ),
					);
				}
				$palette[] = array(
					'slug'  => 'sg-runtime-only',
					'color' => '#0000ff',
					'name'  => 'SG Runtime Only',
				);

				$theme_json->update_with(
					array(
						'version'  => \WP_Theme_JSON::LATEST_SCHEMA,
						'settings' => array(
							'color' => array(
								'palette' => $palette,
							),
						),
					)
				);
				return $theme_json;
			},
			30
		);

		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
		}
	}

	/**
	 * Write a `slug => hex` palette straight into the user global-styles post with a
	 * raw `$wpdb` update — mirroring FseGlobalStylesAdapter, and crucially NOT firing
	 * `save_post`, so seeding can never trigger a pull/cleanup hook.
	 *
	 * @param array<string, string> $slug_hex Palette entries to store.
	 * @return int User global-styles post ID.
	 */
	private function seed_user_post( array $slug_hex ): int {
		$post_id = (int) \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();

		$theme = array();
		foreach ( $slug_hex as $slug => $hex ) {
			$theme[] = array(
				'slug'  => $slug,
				'color' => $hex,
				'name'  => ucfirst( $slug ),
			);
		}
		$content = array(
			'version'                     => 2,
			'isGlobalStylesUserThemeJSON' => true,
			'settings'                    => array(
				'color' => array(
					'palette' => array( 'theme' => $theme ),
				),
			),
		);

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- raw write avoids save_post in the test seed.
		$wpdb->update( $wpdb->posts, array( 'post_content' => (string) wp_json_encode( $content ) ), array( 'ID' => $post_id ), array( '%s' ), array( '%d' ) );
		clean_post_cache( $post_id );

		return $post_id;
	}

	/**
	 * The decoded palette entries stored in the user global-styles post.
	 *
	 * @return array<string, string> slug => hex (empty when nothing persisted).
	 */
	private function stored_palette(): array {
		$post_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
		$post    = $post_id ? get_post( $post_id ) : null;
		if ( ! $post instanceof \WP_Post ) {
			return array();
		}
		$content = json_decode( $post->post_content, true );
		$entries = $content['settings']['color']['palette']['theme'] ?? array();
		$out     = array();
		foreach ( (array) $entries as $entry ) {
			if ( is_array( $entry ) && isset( $entry['slug'], $entry['color'] ) ) {
				$out[ $entry['slug'] ] = $entry['color'];
			}
		}
		return $out;
	}

	/**
	 * A push must NOT persist the Style Guide palette into `wp_global_styles`: the
	 * FSE store write was removed. Even with the runtime overwrite active (the
	 * filtered palette carries the SG colours), the user post stays empty.
	 *
	 * @return void
	 */
	public function test_push_does_not_persist_palette_to_wp_global_styles(): void {
		$engine = $this->save_style_guide();
		$this->register_runtime_overwrite();

		$this->assertArrayNotHasKey( 'primary', $this->stored_palette(), 'Precondition: user post must start without a synced palette.' );

		( new SyncOrchestrator( $engine ) )->push_to_theme();

		$stored = $this->stored_palette();
		$this->assertArrayNotHasKey( 'primary', $stored, 'Push must NOT write Style Guide colours into wp_global_styles (FSE push disabled).' );
		$this->assertArrayNotHasKey( 'heading', $stored, 'No managed role may be persisted by a push.' );
		$this->assertArrayNotHasKey( 'sg-runtime-only', $stored, 'Runtime-only swatches must never be persisted.' );
	}

	/**
	 * The cleanup strips Spectra-injected slugs from a bloated user post while
	 * preserving every theme.json-declared slug — a theme colour is never removed,
	 * even when it overlaps the managed set (primary/secondary/…).
	 *
	 * @return void
	 */
	public function test_cleanup_strips_spectra_injections_but_keeps_theme_colours(): void {
		$engine  = $this->save_style_guide();
		$managed = $engine->get_managed_color_slugs();

		// Bloat a user post the way an older build would have: theme's own slugs +
		// an unrelated non-Spectra slug + Spectra prefix injections + every managed
		// slug (some overlap the theme's own).
		$seed = self::THEME_PALETTE;
		$seed['unmanaged-extra']   = '#abcabc';
		$seed['sg-body']           = '#111111';
		$seed['spectra-neutral-0'] = '#222222';
		foreach ( $managed as $slug ) {
			if ( ! isset( $seed[ $slug ] ) ) {
				$seed[ $slug ] = '#999999';
			}
		}
		$this->seed_user_post( $seed );

		( new PaletteCleanup( $engine ) )->run();

		$stored = $this->stored_palette();

		// 1) Every theme-native slug is preserved — the core guarantee.
		foreach ( array_keys( self::THEME_PALETTE ) as $slug ) {
			$this->assertArrayHasKey( $slug, $stored, "Theme-native '{$slug}' must never be removed by the cleanup." );
		}

		// 2) A non-Spectra, unmanaged slug is left untouched (no over-removal).
		$this->assertArrayHasKey( 'unmanaged-extra', $stored, 'A non-Spectra slug must not be removed.' );

		// 3) Spectra prefix injections are removed.
		$this->assertArrayNotHasKey( 'sg-body', $stored, 'sg-* aliases must be stripped.' );
		$this->assertArrayNotHasKey( 'spectra-neutral-0', $stored, 'spectra-* shade tokens must be stripped.' );

		// 4) Managed slugs that are NOT theme-native are removed.
		$theme_native = array_keys( self::THEME_PALETTE );
		foreach ( $managed as $slug ) {
			if ( in_array( $slug, $theme_native, true ) ) {
				continue;
			}
			$this->assertArrayNotHasKey( $slug, $stored, "Spectra-managed '{$slug}' (not theme-native) must be stripped." );
		}
	}
}
