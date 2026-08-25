<?php
/**
 * Set Active Colors ability.
 *
 * Applies a colour palette to the site's Style Guide — the WRITE half of the ERA
 * "change my site colors" flow (the READ half is
 * {@see \SpectraBlocks\Abilities\GetActiveColors}). Accepts a partial map of the
 * nine core roles from the v2 {@see \SpectraBlocks\StyleGuide\ColorModel}, merges
 * it over the current palette, persists, and recomputes — the same pipeline as a
 * site-scoped POST /style-guide/config. The theme sync (FSE / Astra) runs on save,
 * so the palette takes effect site-wide.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use SpectraBlocks\StyleGuide\ColorModel;
use SpectraBlocks\StyleGuide\Engine;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Set Active Colors ability.
 *
 * @since 1.0.6
 */
class SetActiveColors extends AbstractAbility {

	/**
	 * Get the MCP annotations.
	 *
	 * Idempotent in RESULT: applying the same palette twice leaves the same stored
	 * state (each call does re-save, re-fire `spectra_style_guide_config_saved` and
	 * re-push to the theme). Not destructive — it merges over the existing palette
	 * rather than replacing unrelated data.
	 *
	 * @since 1.0.6
	 * @return array<string, mixed>
	 */
	public function get_annotations(): array {
		return array(
			'readonly'    => false,
			'destructive' => false,
			'idempotent'  => true,
		);
	}

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.6
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/set-active-colors';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.6
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Set Active Colors', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.6
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Apply a colour palette to the site Style Guide. Provide a partial map of the core roles (primary, secondary, accent, heading, body, neutral, outline, surface, background, foreground); provided roles merge over the current palette. A bare hex is accepted (normalised to #rrggbb); roles with an unusable value are returned in `ignored`, and non-core slugs in `unknown`. Persists and syncs to the active theme.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.6
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-configuration';
	}

	/**
	 * Only users who may manage the Style Guide can write it — matches the Style
	 * Guide REST surface ({@see Engine::rest_permission_check}).
	 *
	 * @since 1.0.6
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( current_user_can( 'edit_theme_options' ) ) {
			return true;
		}

		return new WP_Error(
			'spectra_blocks_rest_forbidden',
			__( 'You do not have permission to perform this action.', 'spectra-blocks' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Input schema.
	 *
	 * @since 1.0.6
	 * @return array<string, mixed>
	 */
	public function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'colors'        => array(
					'type'        => 'object',
					'description' => __( 'Partial map of core colour roles (slug => hex) to apply. Only known roles are stored; a partial map merges over the current palette.', 'spectra-blocks' ),
				),
				'custom_colors' => array(
					'type'        => 'object',
					'description' => __( 'Optional custom colour layer (slug => { hex }). FULL-REPLACES the current custom layer; omit to leave it untouched. Do NOT feed back the `semantic_overrides` from get-active-colors here — those are DERIVED tones; pinning them stops them tracking the palette.', 'spectra-blocks' ),
				),
			),
			'required'             => array( 'colors' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Output schema.
	 *
	 * @since 1.0.6
	 * @return array<string, mixed>
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success'     => array(
					'type'        => 'boolean',
					'description' => __( 'True when the palette was applied.', 'spectra-blocks' ),
				),
				'colors'      => array(
					'type'        => 'object',
					'description' => __( 'The core colour roles after the merge (slug => hex).', 'spectra-blocks' ),
				),
				'ignored'     => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Core role slugs whose supplied value was not a valid hex and was skipped.', 'spectra-blocks' ),
				),
				'unknown'     => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => __( 'Supplied slugs that are not core roles (typos / invented names), skipped — they belong in custom_colors.', 'spectra-blocks' ),
				),
				'preview_css' => array(
					'type'        => 'string',
					'description' => __( 'Ready-to-inject CSS for a live editor preview of the applied palette.', 'spectra-blocks' ),
				),
			),
		);
	}

	/**
	 * Execute.
	 *
	 * @since 1.0.6
	 * @param array<string, mixed> $params Validated input.
	 * @return array<string, mixed>|WP_Error
	 */
	public function execute( array $params ) {
		$colors = isset( $params['colors'] ) && is_array( $params['colors'] ) ? $params['colors'] : array();

		// Keep only valid hex values for known core roles — mirrors the Engine's own
		// merge, but lets us reject an empty/garbage payload BEFORE writing and tell
		// the caller (usually a model) which roles were dropped. A bare 3/6-digit hex
		// is normalised to `#rrggbb` first (sanitize_hex_color requires the leading
		// `#`), so `{"primary":"ff0000"}` applies instead of silently no-op'ing.
		$clean   = array();
		$ignored = array();
		foreach ( ColorModel::core_slugs() as $slug ) {
			if ( ! array_key_exists( $slug, $colors ) ) {
				continue;
			}
			$raw = $colors[ $slug ];
			$hex = is_string( $raw ) ? sanitize_hex_color( self::normalize_hex( $raw ) ) : null;
			if ( $hex ) {
				$clean[ $slug ] = $hex;
			} else {
				$ignored[] = $slug;
			}
		}

		// Slugs the caller sent that aren't core roles — typos or invented names
		// (a misspelt slug is the more common model error). Reported separately from
		// `ignored` (known role, unusable value) so the caller can tell them apart.
		// A payload of ONLY unknown slugs still 400s below (nothing valid to apply).
		$unknown = array_values( array_diff( array_keys( $colors ), ColorModel::core_slugs() ) );

		if ( empty( $clean ) ) {
			return new WP_Error(
				'spectra_blocks_invalid_colors',
				__( 'No valid colour roles were provided.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		// The Engine full-replaces the custom layer when this is present and
		// sanitizes it (sanitize_custom_colors); null leaves it untouched.
		$custom = ( isset( $params['custom_colors'] ) && is_array( $params['custom_colors'] ) ) ? $params['custom_colors'] : null;

		$result = Engine::get_instance()->apply_colors( $clean, $custom );

		return array(
			'success'     => true,
			'colors'      => $result['colors'],
			'ignored'     => $ignored,
			'unknown'     => $unknown,
			'preview_css' => $result['preview_css'],
		);
	}

	/**
	 * Prefix a bare 3/6-digit hex with `#` so it passes `sanitize_hex_color()`.
	 * Anything already prefixed, or not a bare hex, is returned unchanged (and then
	 * dropped downstream if still invalid).
	 *
	 * @since 1.0.6
	 * @param string $raw Raw colour value.
	 * @return string Normalised value.
	 */
	private static function normalize_hex( string $raw ): string {
		$raw = trim( $raw );
		if ( '' !== $raw && '#' !== $raw[0] && preg_match( '/^([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $raw ) ) {
			return '#' . $raw;
		}
		return $raw;
	}
}
