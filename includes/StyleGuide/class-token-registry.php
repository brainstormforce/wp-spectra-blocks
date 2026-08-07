<?php
/**
 * Token Registry — single source of truth for all CSS variable names and values.
 *
 * @package Spectra\StyleGuide
 * @since   1.0.0
 */

namespace SpectraBlocks\StyleGuide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TokenRegistry
 *
 * @since 1.0.0
 */
class TokenRegistry {

	/**
	 * CSS variable prefix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const PREFIX = 'spectra';

	/**
	 * All registered tokens: name => value.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private $tokens = array();

	/**
	 * Register a token.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Token name without prefix (e.g., 'neutral-0').
	 * @param string $value CSS value (hex, rgba, etc.).
	 * @return void
	 */
	public function set( $name, $value ): void {
		$this->tokens[ $name ] = $value;
	}

	/**
	 * Get a single token value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Token name without prefix.
	 * @return string|null Token value or null if not found.
	 */
	public function get( $name ) {
		return isset( $this->tokens[ $name ] ) ? $this->tokens[ $name ] : null;
	}

	/**
	 * Get all registered tokens.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> All tokens as name => value.
	 */
	public function get_all() {
		return $this->tokens;
	}

	/**
	 * Generate the full CSS string for :root declaration.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS custom properties block.
	 */
	public function get_css_string() {
		if ( empty( $this->tokens ) ) {
			return '';
		}

		$lines = array();

		foreach ( $this->tokens as $name => $value ) {
			$lines[] = sprintf(
				"\t--%s-%s: %s;",
				self::PREFIX,
				esc_attr( $name ),
				esc_attr( $value )
			);
		}

		return ":root {\n" . implode( "\n", $lines ) . "\n}\n";
	}

	/**
	 * Get legacy variable mappings from old naming to new token names.
	 *
	 * Maps old --color--primary, --color--secondary etc. to the new semantic
	 * --spectra-<slug> token names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Legacy variable name => new variable reference.
	 */
	public function get_legacy_mapping() {
		$mapping = array();

		// Map the old '--color--primary'/'--color--secondary' aliases to the brand
		// seed tokens, now keyed by their semantic slug (primary/secondary). Ramps
		// are gone — only the seed token exists per colour.
		$chromatic_to_legacy = array(
			'primary'   => 'primary',
			'secondary' => 'secondary',
		);

		foreach ( $chromatic_to_legacy as $base_key => $legacy_name ) {
			if ( null !== $this->get( $base_key ) ) {
				$mapping[ "--color--{$legacy_name}" ] = 'var(--' . self::PREFIX . "-{$base_key})";
			}
		}

		// Map 'base' to neutral-7.
		if ( null !== $this->get( 'neutral-7' ) ) {
			$mapping['--color--base'] = 'var(--' . self::PREFIX . '-neutral-7)';
		}

		return $mapping;
	}

	/**
	 * Get the full CSS string including legacy variable mappings.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS with both new and legacy variables.
	 */
	public function get_css_string_with_legacy() {
		if ( empty( $this->tokens ) ) {
			return '';
		}

		$lines = array();

		// New tokens.
		foreach ( $this->tokens as $name => $value ) {
			$lines[] = sprintf(
				"\t--%s-%s: %s;",
				self::PREFIX,
				esc_attr( $name ),
				esc_attr( $value )
			);
		}

		// Legacy mappings.
		$legacy = $this->get_legacy_mapping();
		if ( ! empty( $legacy ) ) {
			$lines[] = '';
			$lines[] = "\t/* Legacy Spectra Pro variable mappings (deprecated) */";
			foreach ( $legacy as $old_name => $new_ref ) {
				$lines[] = sprintf( "\t%s: %s;", esc_attr( $old_name ), esc_attr( $new_ref ) );
			}
		}

		return ":root {\n" . implode( "\n", $lines ) . "\n}\n";
	}

	/**
	 * Format tokens as a WordPress theme.json color palette array.
	 *
	 * Intentionally EMPTY: raw tokens (chromatic seeds, neutral stops, constants)
	 * are no longer published as picker presets. The colour picker carries only
	 * the semantic layer (the 9 roles, status/foreground, sg-* aliases) plus the
	 * user's custom colours — both injected by GlobalStylesBridge from the
	 * semantic map and `custom_colors`, matching exactly what the Style Guide UI
	 * shows. The `--spectra-*` CSS vars themselves keep being emitted via
	 * {@see get_css_string()} for the utility-class layer.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{slug: string, color: string, name: string}> Array of palette entries.
	 */
	public function get_wp_palette() {
		return array();
	}

	/**
	 * Format a palette slug into a human-readable label.
	 *
	 * Strips the `sg-` prefix if present, then converts the remaining
	 * hyphen-separated slug into title-cased words (e.g. `sg-secondary` → `Secondary`,
	 * `background` → `Background`, `sg-mid-dark` → `Mid Dark`).
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Palette slug (e.g. 'sg-secondary', 'primary').
	 * @return string Human-readable label.
	 */
	public static function format_slug_label( $slug ) {
		if ( 0 === strpos( $slug, 'sg-' ) ) {
			$label = ucwords( str_replace( '-', ' ', substr( $slug, 3 ) ) );
			return "{$label} ({$slug})";
		}
		return ucwords( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * Clear all tokens.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clear(): void {
		$this->tokens = array();
	}
}
