<?php
/**
 * Spectra One FSE Theme Compatibility Layer.
 *
 * Maps Style Guide tokens to Spectra One's semantic color slugs so
 * the theme's existing patterns, templates, and styles use the
 * Style Guide colors automatically.
 *
 * This file is ONLY loaded when Spectra One is the active theme.
 * Keep all theme-specific code isolated here.
 *
 * @package Spectra\StyleGuide
 * @since   3.1.0
 */

namespace SpectraBlocks\StyleGuide\Sync\SpectraOne;

use SpectraBlocks\StyleGuide\Engine;
use SpectraBlocks\StyleGuide\Sync\SyncOrchestrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SpectraOneCompat
 *
 * @since 3.1.0
 */
class SpectraOneCompat {

	/**
	 * Site-Editor element colour settings → the Style Guide token they should
	 * round-trip into, keyed by the path (into the global-styles `styles` tree) that
	 * Spectra One's theme.json binds that element to. Used by the reverse element
	 * pull ({@see pull_element_colors}). Tokens match the palette slug each element
	 * is bound to, so element edits share a token with their slug (e.g. Text and
	 * Captions both → `neutral-5`); last-in wins on a shared token.
	 *
	 * @since 1.0.4
	 * @var array<int, array{path: string[], token: string}>
	 */
	const ELEMENT_MAP = array(
		array(
			'path'  => array( 'color', 'text' ),
			'token' => 'neutral-5',
		), // Text.
		array(
			'path'  => array( 'color', 'background' ),
			'token' => 'neutral-0',
		), // Background.
		array(
			'path'  => array( 'elements', 'link', 'color', 'text' ),
			'token' => 'primary',
		), // Link.
		array(
			'path'  => array( 'elements', 'link', ':hover', 'color', 'text' ),
			'token' => 'secondary',
		), // Link hover.
		array(
			'path'  => array( 'elements', 'heading', 'color', 'text' ),
			'token' => 'neutral-7',
		), // Heading.
		array(
			'path'  => array( 'elements', 'button', 'color', 'background' ),
			'token' => 'primary',
		), // Button background.
		array(
			'path'  => array( 'elements', 'button', 'color', 'text' ),
			'token' => 'neutral-0',
		), // Button text.
		array(
			'path'  => array( 'elements', 'button', ':hover', 'color', 'background' ),
			'token' => 'secondary',
		), // Button hover background.
		array(
			'path'  => array( 'elements', 'caption', 'color', 'text' ),
			'token' => 'neutral-5',
		), // Captions.
	);

	/**
	 * The Engine instance.
	 *
	 * @since 3.1.0
	 * @var Engine
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param Engine $engine The Style Guide engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Initialize compatibility hooks.
	 *
	 * @since 3.1.0
	 * @return void
	 */
	public function init(): void {
		// Only activate if Spectra One is the current theme.
		if ( ! $this->is_spectra_one_active() ) {
			return;
		}

		// NOTE: the runtime palette overwrite (override_theme_colors on
		// wp_theme_json_data_theme) was retired — the Style Guide now push-syncs
		// its palette into the theme's user global styles on save instead.

		// Deregister Spectra One's hardcoded button block styles and
		// re-register them with CSS variable values instead of hex.
		add_action( 'init', array( $this, 'fix_button_block_styles' ), 999 );

		// Reverse-sync Site-Editor ELEMENT colour edits (Text/Background/Link/
		// Heading/Button/Captions) into the Style Guide. Priority 20 = after the
		// orchestrator's palette pull (priority 10) on the same save.
		add_action( 'save_post_wp_global_styles', array( $this, 'pull_element_colors' ), 20, 2 );
	}

	/**
	 * Check if Spectra One is the active theme.
	 *
	 * @since 3.1.0
	 *
	 * @return bool True if Spectra One is active.
	 */
	private function is_spectra_one_active() {
		$theme = wp_get_theme();
		$name  = strtolower( $theme->get( 'TextDomain' ) );

		// Check both the theme slug and text domain.
		return ( 'spectra-one' === $name || 'spectra-one' === strtolower( $theme->get_stylesheet() ) );
	}

	/**
	 * Deregister Spectra One's hardcoded button block styles and
	 * re-register with CSS variable-based colors.
	 *
	 * This runs at init priority 999 (after the theme registers at default priority).
	 * unregister_block_style removes the theme's inline CSS entirely,
	 * then we re-register with the same name but using var() references.
	 *
	 * @since 3.1.0
	 * @return void
	 */
	public function fix_button_block_styles(): void {
		// Deregister the theme's hardcoded styles. Guard each call: in some admin /
		// block-editor requests this runs before (or without) the theme's own
		// registration, and unregister_block_style() emits a "does not contain a
		// style named" _doing_it_wrong notice when the style is absent. The
		// re-register below overwrites the registry entry regardless, so a missing
		// style here is harmless — just skip the redundant unregister.
		$registry = \WP_Block_Styles_Registry::get_instance();
		foreach ( array( 'swt-button-inverse', 'swt-button-secondary' ) as $style_name ) {
			if ( $registry->is_registered( 'core/button', $style_name ) ) {
				unregister_block_style( 'core/button', $style_name );
			}
		}

		// Re-register with CSS variables instead of hardcoded hex.
		register_block_style(
			'core/button',
			array(
				'name'         => 'swt-button-inverse',
				'label'        => __( 'Inverse', 'spectra-blocks' ),
				'inline_style' => '
					div.is-style-swt-button-inverse .wp-element-button {
						color: var(--wp--preset--color--primary);
						background: var(--wp--preset--color--background);
						border: 1.5px solid var(--wp--preset--color--primary);
					}
					div.is-style-swt-button-inverse .wp-element-button:hover {
						color: var(--wp--preset--color--background);
						background: var(--wp--preset--color--primary);
						border-color: var(--wp--preset--color--primary);
					}
				',
			)
		);

		register_block_style(
			'core/button',
			array(
				'name'         => 'swt-button-secondary',
				'label'        => __( 'Secondary', 'spectra-blocks' ),
				'inline_style' => '
					div.is-style-swt-button-secondary .wp-element-button {
						color: var(--wp--preset--color--body);
						background: var(--wp--preset--color--surface);
					}
					div.is-style-swt-button-secondary .wp-element-button:hover {
						color: var(--wp--preset--color--heading);
						background: var(--wp--preset--color--outline);
					}
				',
			)
		);
	}

	/**
	 * Reverse-sync Site-Editor ELEMENT colour edits into the Style Guide.
	 *
	 * Reads the saved user global-styles post's `styles` tree; for each element in
	 * {@see ELEMENT_MAP} whose value is a literal hex (a custom colour, not a
	 * `var(--wp--preset--color--…)` reference to an existing swatch), maps it back to
	 * its Style Guide token and applies it via the shared reverse applier (brand →
	 * reseed chromatic, neutral → pin on divergence). Then clears those element
	 * overrides so they inherit the palette `var()` again — keeping the Style Guide
	 * the single source of truth.
	 *
	 * @since 1.0.4
	 *
	 * @param int           $post_id Saved wp_global_styles post ID.
	 * @param \WP_Post|null $post    Saved post object (unused).
	 * @return void
	 */
	public function pull_element_colors( $post_id, $post = null ): void {
		unset( $post );

		// While unsaved the SG only mirrors the theme; a Spectra One element-colour
		// edit must not persist a config. Round-trip only once a Style Guide exists.
		if ( SyncOrchestrator::is_syncing() || ! $this->engine->has_saved_style_guide() || ! class_exists( '\WP_Theme_JSON_Resolver' ) ) {
			return;
		}
		// Only the ACTIVE theme's user global-styles post — resolved WITHOUT the
		// auto-create variant, which recurses when this hook fires from inside the
		// post's own creation ({@see SyncOrchestrator::pull_from_theme()} for the
		// full failure chain).
		$user_cpt = \WP_Theme_JSON_Resolver::get_user_data_from_wp_global_styles( wp_get_theme() );
		if ( empty( $user_cpt['ID'] ) || (int) $user_cpt['ID'] !== (int) $post_id ) {
			return;
		}

		$saved = get_post( (int) $post_id );
		if ( ! $saved instanceof \WP_Post ) {
			return;
		}
		$content = json_decode( $saved->post_content, true );
		if ( ! is_array( $content ) ) {
			return;
		}
		$styles = ( isset( $content['styles'] ) && is_array( $content['styles'] ) ) ? $content['styles'] : array();

		// Collect literal-hex element overrides → SG tokens (last wins on a shared token).
		$token_hexes = array();
		$clear_paths = array();
		foreach ( self::ELEMENT_MAP as $entry ) {
			$value = $this->get_style_value( $styles, $entry['path'] );
			if ( ! is_string( $value ) ) {
				continue;
			}
			$hex = sanitize_hex_color( $value ); // var()/preset refs aren't hex → skipped.
			if ( ! $hex ) {
				continue;
			}
			$token_hexes[ $entry['token'] ] = $hex;
			$clear_paths[]                  = $entry['path'];
		}

		if ( empty( $token_hexes ) ) {
			return;
		}

		// Round-trip into the Style Guide (reseeds brand chromatics / pins neutrals).
		( new SyncOrchestrator( $this->engine ) )->apply_reverse_colors( $token_hexes );

		// apply_reverse_colors() may have re-pushed the palette into THIS post, so
		// re-read before stripping the element overrides (else we'd clobber it).
		$this->clear_element_overrides( (int) $post_id, $clear_paths );
	}

	/**
	 * Read a value out of a `styles` sub-tree by key path, or null if absent.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, mixed> $styles The `styles` array.
	 * @param string[]             $path   Key path (e.g. ['elements','link','color','text']).
	 * @return mixed
	 */
	private function get_style_value( array $styles, array $path ) {
		$node = $styles;
		foreach ( $path as $key ) {
			if ( ! is_array( $node ) || ! isset( $node[ $key ] ) ) {
				return null;
			}
			$node = $node[ $key ];
		}
		return $node;
	}

	/**
	 * Strip the given element-override paths from the post's `styles` and write back
	 * via a raw `$wpdb->update` (mirrors FseGlobalStylesAdapter: avoids
	 * `content_save_pre` mangling and does NOT fire `save_post`, so no sync loop).
	 * Empty parent nodes are pruned.
	 *
	 * @since 1.0.4
	 *
	 * @param int                            $post_id User global-styles post ID.
	 * @param array<int, array<int, string>> $paths Paths (relative to `styles`) to unset.
	 * @return void
	 */
	private function clear_element_overrides( int $post_id, array $paths ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		$content = json_decode( $post->post_content, true );
		if ( ! is_array( $content ) || ! isset( $content['styles'] ) || ! is_array( $content['styles'] ) ) {
			return;
		}

		$changed = false;
		foreach ( $paths as $path ) {
			if ( $this->unset_style_path( $content['styles'], $path ) ) {
				$changed = true;
			}
		}
		if ( ! $changed ) {
			return;
		}

		$encoded = wp_json_encode( $content, JSON_UNESCAPED_UNICODE );
		if ( false === $encoded ) {
			return;
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- raw update mirrors FseGlobalStylesAdapter to avoid content_save_pre + a save_post loop.
		$wpdb->update(
			$wpdb->posts,
			array( 'post_content' => $encoded ),
			array( 'ID' => $post_id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $post_id );
		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
		}
	}

	/**
	 * Recursively unset a key path from an array, pruning emptied parents.
	 *
	 * @since 1.0.4
	 *
	 * @param array<string, mixed> $arr  Array to mutate (by reference).
	 * @param string[]             $path Key path to unset.
	 * @return bool True if a value was removed.
	 */
	private function unset_style_path( array &$arr, array $path ): bool {
		$key = $path[0];
		if ( ! array_key_exists( $key, $arr ) ) {
			return false;
		}
		if ( 1 === count( $path ) ) {
			unset( $arr[ $key ] );
			return true;
		}
		if ( ! is_array( $arr[ $key ] ) ) {
			return false;
		}
		$removed = $this->unset_style_path( $arr[ $key ], array_slice( $path, 1 ) );
		if ( $removed && empty( $arr[ $key ] ) ) {
			unset( $arr[ $key ] ); // Prune emptied parent.
		}
		return $removed;
	}
}
