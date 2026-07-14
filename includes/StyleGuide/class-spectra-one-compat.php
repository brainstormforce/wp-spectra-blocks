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

namespace SpectraBlocks\StyleGuide;

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
	 * The Engine instance.
	 *
	 * @since 3.1.0
	 * @var Engine
	 */
	private $engine;

	/**
	 * Get the color map from the Engine's semantic map (single source of truth).
	 *
	 * Falls back to sane defaults if the Engine isn't available yet.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, string> Map of theme_slug => token_key.
	 */
	private function get_color_map() {
		$config       = $this->engine->get_config();
		$semantic_map = isset( $config['semantic_map'] ) && is_array( $config['semantic_map'] ) ? $config['semantic_map'] : array();

		if ( ! empty( $semantic_map ) ) {
			/* @var array<string, string> $semantic_map */
			return $semantic_map;
		}

		// Fallback defaults (must match Engine::get_config defaults).
		return array(
			'primary'       => 'chromatic1-7',
			'secondary'     => 'chromatic1-5',
			'tertiary'      => 'chromatic1-2',
			'quaternary'    => 'chromatic2-2',
			'heading'       => 'neutral-7',
			'body'          => 'neutral-5',
			'background'    => 'neutral-0',
			'foreground'    => 'chromatic1-7',
			'surface'       => 'neutral-1',
			'outline'       => 'neutral-2',
			'neutral'       => 'neutral-4',

			// sg-* entries: future-proofing for when Spectra One ships sg-* palette slugs.
			'sg-accent'     => 'chromatic1-7',
			'sg-secondary'  => 'chromatic1-5',
			'sg-heading'    => 'neutral-7',
			'sg-body'       => 'neutral-5',
			'sg-surface'    => 'neutral-1',
			'sg-background' => 'neutral-0',
			'sg-border'     => 'neutral-2',
			'sg-neutral'    => 'neutral-6',
			'sg-muted'      => 'neutral-4',
		);
	}

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

		// Override theme palette colors with Style Guide values.
		// Priority 30 = after the main GlobalStylesBridge (priority 20).
		add_filter( 'wp_theme_json_data_theme', array( $this, 'override_theme_colors' ), 30 );

		// Deregister Spectra One's hardcoded button block styles and
		// re-register them with CSS variable values instead of hex.
		add_action( 'init', array( $this, 'fix_button_block_styles' ), 999 );
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
	 * Override Spectra One's semantic colors with Style Guide computed values.
	 *
	 * This replaces the theme's palette entries (primary, secondary, etc.)
	 * with the hex values from our computed tokens, so the theme's patterns
	 * and templates automatically use the Style Guide colors.
	 *
	 * @since 3.1.0
	 *
	 * @param \WP_Theme_JSON_Data $theme_json The theme JSON data.
	 * @return \WP_Theme_JSON_Data Modified theme JSON data.
	 */
	public function override_theme_colors( $theme_json ) {
		$tokens = $this->engine->get_token_registry();

		if ( null === $tokens ) {
			return $theme_json;
		}

		$data = $theme_json->get_data();

		// Get existing theme palette (includes spectra entries from GlobalStylesBridge at priority 20).
		$palette = array();
		if ( isset( $data['settings']['color']['palette']['theme'] ) ) {
			$palette = $data['settings']['color']['palette']['theme'];
		}

		// Override each mapped color (reads from Engine's semantic map).
		$color_map = $this->get_color_map();
		foreach ( $color_map as $theme_slug => $token_key ) {
			$hex = $tokens->get( $token_key );

			if ( null === $hex ) {
				continue;
			}

			// Find and update the theme's palette entry.
			$found = false;
			foreach ( $palette as &$entry ) {
				if ( isset( $entry['slug'] ) && $entry['slug'] === $theme_slug ) {
					$entry['color'] = $hex;
					$entry['name']  = TokenRegistry::format_slug_label( $theme_slug );
					$found          = true;
					break;
				}
			}
			unset( $entry );

			// If the theme doesn't have this slug, add it.
			if ( ! $found ) {
				$palette[] = array(
					'slug'  => $theme_slug,
					'color' => $hex,
					'name'  => TokenRegistry::format_slug_label( $theme_slug ),
				);
			}
		}

		// Write back.
		$new_data = array(
			'version'  => 2,
			'settings' => array(
				'color' => array(
					'palette' => array(
						'theme' => $palette,
					),
				),
			),
		);

		return $theme_json->update_with( $new_data );
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
		// Deregister the theme's hardcoded styles.
		unregister_block_style( 'core/button', 'swt-button-inverse' );
		unregister_block_style( 'core/button', 'swt-button-secondary' );

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
}
