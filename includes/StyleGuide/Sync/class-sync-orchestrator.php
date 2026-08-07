<?php
/**
 * Sync Orchestrator — wires both directions of color sync through the role
 * model, the per-theme mapping and the store adapters.
 *
 * It is the only piece that knows about roles AND colors AND stores at once:
 *   - PUSH  (SG → theme): resolve each role's Style Guide color, translate role
 *     → theme slug via the mapping, and patch the adapter by slug.
 *   - PULL  (theme → SG): read the theme palette, map an edited BRAND slug back
 *     to its chromatic and re-seed the Style Guide (Model B). Push-only roles
 *     are never pulled.
 *
 * Loop safety: the FSE adapter writes with a raw `$wpdb->update`, which does NOT
 * fire `save_post_wp_global_styles`, so a push never re-triggers a pull. The one
 * re-entrant path is pull → `save_config()` → push (intended: re-harmonize and
 * write back); a single static guard blocks pull from re-entering itself.
 *
 * @package Spectra\StyleGuide
 * @since   x.x.x
 */

namespace SpectraBlocks\StyleGuide\Sync;

use SpectraBlocks\StyleGuide\Engine;
use SpectraBlocks\StyleGuide\ColorModel;
use SpectraBlocks\StyleGuide\Sync\Astra\AstraPaletteAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SyncOrchestrator
 *
 * @since x.x.x
 */
class SyncOrchestrator {

	/**
	 * The Style Guide engine.
	 *
	 * @since x.x.x
	 * @var Engine
	 */
	private $engine;

	/**
	 * Re-entrancy guard for the reverse (pull) direction.
	 *
	 * @since x.x.x
	 * @var bool
	 */
	private static $syncing = false;

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
	 * Register both sync directions.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'spectra_style_guide_config_saved', array( $this, 'push_to_theme' ) );
		add_action( 'save_post_wp_global_styles', array( $this, 'pull_from_theme' ), 10, 2 );

		// Let each theme adapter register its own reverse-sync hooks (e.g. Astra's
		// `update_option_astra-settings`), so theme-specific option names live in the
		// theme module ({@see Sync\Astra}), not hardcoded in core.
		foreach ( $this->adapters() as $adapter ) {
			if ( method_exists( $adapter, 'register_reverse_hooks' ) ) {
				$adapter->register_reverse_hooks( $this );
			}
		}

		// Theme activation and plugin upgrades intentionally do NOT push. The Style
		// Guide writes into a theme ONLY on an explicit, confirmed save — so
		// activating a theme never rewrites its colours, and neither does an upgrade.
		// The one remaining push trigger is the `spectra_style_guide_config_saved`
		// action, which fires from Engine::save_config(), i.e. always post-save.
	}

	/**
	 * The store adapters to sync. Each gates itself via `is_supported()`, so only
	 * the active theme's store actually writes (FSE on block themes, Astra when
	 * Astra is active).
	 *
	 * @since x.x.x
	 *
	 * @return ColorSyncAdapter[]
	 */
	private function adapters(): array {
		return array(
			new FseGlobalStylesAdapter(),
			new AstraPaletteAdapter(),
		);
	}

	/**
	 * PUSH (SG → theme): patch each mapped role's color into the active theme.
	 *
	 * @since x.x.x
	 *
	 * @param array<string, mixed> $config Saved config (unused; reads live tokens).
	 * @return void
	 */
	public function push_to_theme( $config = array() ): void {
		unset( $config );

		// Never write a theme the user never opted into. With no saved Style Guide the
		// engine resolves to inherited/default colours; pushing those would rewrite the
		// theme's own palette. Defense-in-depth — the only wired trigger is the
		// post-save action, but this also guards any future/3rd-party caller.
		if ( ! $this->engine->has_saved_style_guide() ) {
			return;
		}

		$this->engine->maybe_compute();
		$tokens = $this->engine->get_token_registry();
		if ( null === $tokens ) {
			return;
		}

		// Generic role → theme slug → hex patch (used by FSE and any adapter that
		// does not provide its own theme-specific patch).
		$mapping     = MappingResolver::for_active_theme();
		$role_colors = $this->resolve_role_colors();
		$generic     = array();
		foreach ( $mapping->mapped_roles() as $role ) {
			$slug = $mapping->slug_for( $role );
			if ( null !== $slug && isset( $role_colors[ $role ] ) ) {
				$generic[ $slug ] = $role_colors[ $role ];
			}
		}

		foreach ( $this->adapters() as $adapter ) {
			if ( ! $adapter->is_supported() ) {
				continue;
			}
			// Do NOT persist the Style Guide palette into the FSE user global-styles
			// post (wp_global_styles). Spectra colours live only in the Style Guide
			// option and render at runtime (Pro-gated inject_palette + user-layer
			// overlay). Persisting them here bloated the theme post and made a
			// theme-side save store Spectra colours in the theme. Astra keeps its own
			// astra-settings push (a separate store, not wp_global_styles).
			if ( $adapter instanceof FseGlobalStylesAdapter ) {
				continue;
			}
			// A theme adapter may supply its own full patch (e.g. Astra's
			// flag-aware, multi-slot mapping); otherwise use the generic one.
			$patch = $adapter->resolve_patch( $tokens );
			if ( empty( $patch ) ) {
				$patch = $generic;
			}
			if ( empty( $patch ) ) {
				continue;
			}

			// Change check: skip the write (and cache flush) if nothing differs.
			$current  = $adapter->read();
			$has_diff = false;
			foreach ( $patch as $slug => $hex ) {
				if ( ! isset( $current[ $slug ] ) || strtolower( (string) $current[ $slug ] ) !== strtolower( (string) $hex ) ) {
					$has_diff = true;
					break;
				}
			}
			if ( $has_diff ) {
				$adapter->write( $patch );
			}
		}
	}

	/**
	 * PULL (FSE theme → SG): re-seed brand chromatics from a global-styles edit.
	 *
	 * `save_post_wp_global_styles` only fires when the user edits global styles,
	 * so it is safe to reseed whenever a brand slug differs from the Style Guide.
	 *
	 * @since x.x.x
	 *
	 * @param int           $post_id Saved wp_global_styles post ID.
	 * @param \WP_Post|null $post    Saved post object.
	 * @return void
	 */
	public function pull_from_theme( $post_id, $post = null ): void {

		// Only round-trip theme edits once the user has committed a Style Guide. While
		// unsaved the SG merely MIRRORS the theme (inherited defaults), so a theme-side
		// edit must not silently persist a config and flip the site into "saved".
		if ( self::$syncing || ! $this->engine->has_saved_style_guide() || ! class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			return;
		}
		// Only the ACTIVE theme's user global-styles post — resolved WITHOUT the
		// auto-create variant. `get_user_global_styles_post_id()` CREATES the post
		// when the tax_query lookup misses, and this hook fires from inside that
		// very `wp_insert_post()` (before core caches the new ID). With no
		// authenticated user (CLI/cron), `tax_input` silently fails, the fresh post
		// stays invisible to the lookup, and the create → save_post → create chain
		// recurses into unbounded `wp-global-styles-*-N` posts. The non-creating
		// read returns empty during that in-flight creation, so we simply bail —
		// correct anyway, since a just-created post holds only the empty envelope.
		$user_cpt = \WP_Theme_JSON_Resolver::get_user_data_from_wp_global_styles( wp_get_theme() );
		if ( empty( $user_cpt['ID'] ) || (int) $user_cpt['ID'] !== (int) $post_id ) {
			return;
		}

		$adapter = new FseGlobalStylesAdapter();
		if ( ! $adapter->is_supported() ) {
			return;
		}

		// Pull from the SAVED POST's own palette entries — never the adapter's
		// theme-merged read. This hook can only legitimately react to what the
		// user actually stored; merging theme.json underneath meant the
		// auto-created EMPTY envelope post (first Site Editor load, REST) would
		// "pull" raw theme colours over a saved Style Guide and overwrite it.
		// An envelope post with no palette entries has nothing to pull — bail.
		$saved = $post instanceof \WP_Post ? $post : get_post( (int) $post_id );
		if ( ! $saved instanceof \WP_Post ) {
			return;
		}
		$content = json_decode( $saved->post_content, true );
		$entries = ( is_array( $content ) && isset( $content['settings']['color']['palette']['theme'] ) && is_array( $content['settings']['color']['palette']['theme'] ) )
			? $content['settings']['color']['palette']['theme']
			: array();
		$by_slug = array();
		foreach ( $entries as $entry ) {
			if ( is_array( $entry ) && isset( $entry['slug'], $entry['color'] ) && is_string( $entry['slug'] ) && is_string( $entry['color'] ) ) {
				$by_slug[ $entry['slug'] ] = $entry['color'];
			}
		}
		if ( empty( $by_slug ) ) {
			return;
		}

		// Pull EVERY mapped role from its (current) mapped slug value — under the
		// v2 model each role's colour is stored directly (`colors[slug]`), so
		// neutrals round-trip exactly like brand roles. Full two-way.
		$mapping     = MappingResolver::for_active_theme();
		$token_hexes = array();
		foreach ( $mapping->mapped_roles() as $role ) {
			$slug  = $mapping->slug_for( $role );
			$token = ColorRoles::SG_TOKEN[ $role ] ?? null;
			if ( null !== $slug && null !== $token && isset( $by_slug[ $slug ] ) ) {
				$token_hexes[ $token ] = $by_slug[ $slug ];
			}
		}

		// Status colours + Style-Guide custom colours aren't mapped roles, but the
		// user can edit them in the Site Editor palette (they're injected as managed
		// slugs). Round-trip a genuine edit into the Style Guide override layer
		// (config['custom_colors']) instead of the theme post.
		$custom_hexes = $this->collect_custom_overrides_from_palette( $by_slug );

		$this->apply_reverse_colors( $token_hexes, $custom_hexes );

		// A Site Editor save captures the runtime-injected Style Guide swatches into
		// the post. Strip them back out so wp_global_styles never accumulates Spectra
		// colours — they live only in the Style Guide option and re-inject at runtime.
		// The theme's own colours are preserved; the raw write does not fire save_post.
		( new PaletteCleanup( $this->engine ) )->clean_post( (int) $post_id );
	}

	/**
	 * PULL (Astra → SG): round-trip an Astra color edit into the Style Guide.
	 *
	 * The seven managed Astra global colors are two-way (slots 7/8 are unmanaged —
	 * see {@see Astra\AstraPaletteAdapter::SEMANTIC_TOKENS}). The
	 * `update_option_astra-settings` hook fires for ANY Astra setting, so only
	 * slots whose colour actually CHANGED this save (old vs new) are acted on —
	 * unrelated Astra saves never touch the Style Guide.
	 *
	 * Each changed slot maps back via {@see Astra\AstraPaletteAdapter::reverse_map()}
	 * to a token, then {@see apply_reverse_colors()} writes the owning stored
	 * colour (`colors[slug]`).
	 *
	 * @since x.x.x
	 *
	 * @param mixed $old_value Previous astra-settings option value.
	 * @param mixed $value     New astra-settings option value.
	 * @return void
	 */
	public function pull_from_astra( $old_value = null, $value = null ): void {
		// While unsaved the SG only mirrors the theme; a Customizer colour edit must
		// not persist a config. Round-trip Astra edits only once a Style Guide exists.
		if ( self::$syncing || ! $this->engine->has_saved_style_guide() || AstraPaletteAdapter::is_writing() ) {
			return;
		}

		$adapter = new AstraPaletteAdapter();
		if ( ! $adapter->is_supported() ) {
			return;
		}

		$old = $adapter->read_from( $old_value );
		$new = $adapter->read_from( $value );
		if ( empty( $new ) ) {
			return;
		}

		// Map each CHANGED slot back to its Style Guide token; apply_reverse_colors()
		// then writes the owning stored colour.
		$token_hexes = array();
		foreach ( $adapter->reverse_map() as $index => $token ) {
			$slug = AstraPaletteAdapter::SLUG_PREFIX . $index;
			if ( ! isset( $new[ $slug ] ) ) {
				continue;
			}
			$old_hex = isset( $old[ $slug ] ) ? strtolower( (string) $old[ $slug ] ) : '';
			if ( strtolower( (string) $new[ $slug ] ) === $old_hex ) {
				continue; // Slot unchanged this save.
			}
			$token_hexes[ $token ] = $new[ $slug ];
		}

		$this->apply_reverse_colors( $token_hexes );
	}

	/**
	 * Apply reverse patches onto the Style Guide config and, if anything changed,
	 * save (which recomputes + re-pushes the harmonized palette). Shared by every
	 * reverse path (Astra pull, FSE palette pull, Spectra One element pull).
	 *
	 * Two layers:
	 *  - $token_hexes: SG token => hex. Each token maps back to the core role slug
	 *    that owns it ({@see ColorModel::slug_for_token}) and is written to
	 *    `config['colors']`. Tokens without an owning role are ignored.
	 *  - $custom_hexes: slug => hex for the Style-Guide OVERRIDE layer — the status
	 *    colours (success/error/warning/info) and user custom colours, which have no
	 *    `config['colors']` slot. Written to `config['custom_colors']`, preserving any
	 *    existing entry name. Callers pass ONLY genuinely-edited slugs here (diffed
	 *    against the effective value), so a plain re-save of an unchanged palette
	 *    writes nothing.
	 *
	 * @since x.x.x
	 *
	 * @param array<string, string> $token_hexes  SG token key => hex (core roles).
	 * @param array<string, string> $custom_hexes slug => hex (status + custom overrides).
	 * @return void
	 */
	public function apply_reverse_colors( array $token_hexes, array $custom_hexes = array() ): void {
		if ( empty( $token_hexes ) && empty( $custom_hexes ) ) {
			return;
		}

		$config  = $this->engine->get_config();
		$colors  = ( isset( $config['colors'] ) && is_array( $config['colors'] ) ) ? $config['colors'] : array();
		$custom  = ( isset( $config['custom_colors'] ) && is_array( $config['custom_colors'] ) ) ? $config['custom_colors'] : array();
		$changed = false;

		// Core roles → config['colors'].
		foreach ( $token_hexes as $token => $hex ) {
			$clean = sanitize_hex_color( (string) $hex );
			if ( ! $clean ) {
				continue;
			}
			$slug = ColorModel::slug_for_token( (string) $token );
			if ( null === $slug ) {
				continue;
			}

			$current = isset( $colors[ $slug ] ) ? strtolower( (string) $colors[ $slug ] ) : '';
			if ( strtolower( $clean ) !== $current ) {
				$colors[ $slug ] = $clean;
				$changed         = true;
			}
		}

		// Status colours + custom colours → config['custom_colors'] override layer.
		foreach ( $custom_hexes as $slug => $hex ) {
			$clean = sanitize_hex_color( (string) $hex );
			if ( ! $clean || ! is_string( $slug ) || '' === $slug ) {
				continue;
			}
			$existing_hex = '';
			$name         = '';
			if ( isset( $custom[ $slug ] ) ) {
				if ( is_array( $custom[ $slug ] ) ) {
					$existing_hex = isset( $custom[ $slug ]['hex'] ) ? (string) $custom[ $slug ]['hex'] : '';
					$name         = isset( $custom[ $slug ]['name'] ) ? (string) $custom[ $slug ]['name'] : '';
				} else {
					$existing_hex = (string) $custom[ $slug ];
				}
			}
			if ( strtolower( $clean ) === strtolower( $existing_hex ) ) {
				continue;
			}
			$entry = array( 'hex' => $clean );
			if ( '' !== $name ) {
				$entry['name'] = $name;
			}
			$custom[ $slug ] = $entry;
			$changed         = true;
		}

		if ( ! $changed ) {
			return;
		}

		$config['colors']        = $colors;
		$config['custom_colors'] = $custom;

		self::$syncing = true;
		$this->engine->save_config( $config );
		self::$syncing = false;
	}

	/**
	 * From a saved theme palette (slug => hex), collect the Style-Guide OVERRIDE
	 * slugs whose colour the user actually CHANGED — the four status colours, the
	 * extended "More color variables" (foreground / surface-2 / overlay), and any
	 * existing custom-colour slugs. These have no core-role slot, so a genuine edit
	 * round-trips into `custom_colors` (an AUTO variable thereby flips to CUSTOM).
	 *
	 * Each candidate is compared against the value the Style Guide currently produces
	 * for it — its resolved override (`semantic_overrides`, which is the custom value
	 * when pinned, else the derived AUTO default), else the computed token (status in
	 * auto mode) — so a re-save of an unchanged AUTO value yields nothing and the
	 * variable stays auto. Slugs the Style Guide doesn't manage (the theme's own
	 * colours, brand-new picker entries) are never captured.
	 *
	 * @since x.x.x
	 *
	 * @param array<string, string> $by_slug Saved theme palette, slug => hex.
	 * @return array<string, string> slug => hex for genuinely-edited overrides.
	 */
	private function collect_custom_overrides_from_palette( array $by_slug ): array {
		$config = $this->engine->get_config();
		// semantic_overrides resolves each managed override to its effective value:
		// the custom hex when pinned, else the derived AUTO default for the extended
		// vars. Its keys are every override the SG manages (extended vars + customs).
		$overrides = ( isset( $config['semantic_overrides'] ) && is_array( $config['semantic_overrides'] ) ) ? $config['semantic_overrides'] : array();

		// Status colours may be in AUTO mode (absent from $overrides), so include them
		// explicitly; their auto value is the computed token below.
		$candidates = array_unique( array_merge( array_keys( ColorModel::STATUS_COLORS ), array_keys( $overrides ) ) );

		$this->engine->maybe_compute();
		$tokens = $this->engine->get_token_registry();

		$out = array();
		foreach ( $candidates as $slug ) {
			if ( ! is_string( $slug ) || '' === $slug || ! isset( $by_slug[ $slug ] ) ) {
				continue;
			}
			$saved = sanitize_hex_color( (string) $by_slug[ $slug ] );
			if ( ! $saved ) {
				continue;
			}
			// Effective current value: the resolved override (custom-wins-else-derived),
			// else the computed token (status in auto mode, registered under its slug).
			if ( isset( $overrides[ $slug ] ) ) {
				$effective = (string) $overrides[ $slug ];
			} else {
				$effective = ( null !== $tokens ) ? (string) ( $tokens->get( $slug ) ?? '' ) : '';
			}
			if ( strtolower( $saved ) !== strtolower( $effective ) ) {
				$out[ $slug ] = $saved;
			}
		}

		return $out;
	}

	/**
	 * Whether a reverse (pull) sync is currently in progress.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	public static function is_syncing(): bool {
		return self::$syncing;
	}

	/**
	 * Resolve each role's Style Guide source color as role => hex.
	 *
	 * @since x.x.x
	 *
	 * @return array<string, string>
	 */
	private function resolve_role_colors(): array {
		$this->engine->maybe_compute();
		$tokens = $this->engine->get_token_registry();
		if ( null === $tokens ) {
			return array();
		}

		$out = array();
		foreach ( ColorRoles::all() as $role ) {
			$key = ColorRoles::sg_token( $role );
			if ( null === $key ) {
				continue;
			}
			$hex = $tokens->get( $key );
			if ( is_string( $hex ) && '' !== $hex ) {
				$out[ $role ] = $hex;
			}
		}
		return $out;
	}
}
