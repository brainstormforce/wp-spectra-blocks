<?php
/**
 * Get Active Colors ability.
 *
 * Reads the site's current Style Guide colour palette — the nine core roles from
 * the v2 {@see \SpectraBlocks\StyleGuide\ColorModel} — plus whether a palette has
 * been saved and ready-to-inject live-preview CSS. This is the READ half of the
 * ERA "change my site colors" flow (the WRITE half is
 * {@see \SpectraBlocks\Abilities\SetActiveColors}); the deprecated DNA-presets
 * catalogue it replaces has been removed.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use SpectraBlocks\StyleGuide\Engine;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Get Active Colors ability.
 *
 * @since 1.0.6
 */
class GetActiveColors extends AbstractAbility {

	/**
	 * Get the MCP annotations.
	 *
	 * @since 1.0.6
	 * @return array<string, mixed>
	 */
	public function get_annotations(): array {
		return array(
			'readonly'    => true,
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
		return 'spectra-blocks/get-active-colors';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.6
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Get Active Colors', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.6
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Read the site\'s current Style Guide colour palette: the core roles (primary, secondary, accent, heading, body, neutral, outline, surface, background, foreground), whether a palette has been saved, and ready-to-inject live-preview CSS.', 'spectra-blocks' );
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
	 * Only users who may manage the Style Guide can read it — matches the Style
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
	 * Input schema — takes no arguments.
	 *
	 * The top-level `default` (an empty object) is what makes a no-input call work:
	 * `WP_Ability::normalize_input( null )` returns this default when the caller
	 * sends nothing, so a browser GET on the Abilities run-route (query params are
	 * strings and cannot carry an object) validates against `type: object` instead
	 * of failing on a `null`/`"{}"` input.
	 *
	 * @since 1.0.6
	 * @return array<string, mixed>
	 */
	public function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(),
			'additionalProperties' => false,
			'default'              => array(),
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
				'colors'        => array(
					'type'        => 'object',
					'description' => __( 'The core colour roles (slug => hex).', 'spectra-blocks' ),
				),
				'custom_colors' => array(
					'type'        => 'object',
					'description' => __( 'The stored custom colour layer (slug => { hex, name }) — user pins/overrides outside the core roles. Round-trippable: post it back via set-active-colors\' `custom_colors` to replace the layer.', 'spectra-blocks' ),
				),
				'saved'         => array(
					'type'        => 'boolean',
					'description' => __( 'True when the user has saved a palette (false = inherited theme colours).', 'spectra-blocks' ),
				),
				'preview_css'   => array(
					'type'        => 'string',
					'description' => __( 'Ready-to-inject CSS for a live editor preview of the active palette (includes the derived surface-2 / overlay tones).', 'spectra-blocks' ),
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
		unset( $params );

		$engine = Engine::get_instance();
		$config = $engine->get_config();
		$colors = $engine->active_colors();

		// The two keys actually stored in the DB row are `colors` and
		// `custom_colors` — return the stored shape, not a derived view. The
		// custom layer (slug => { hex, name }) is the user's pins/overrides outside
		// the core roles and is exactly what set-active-colors' `custom_colors`
		// full-replaces, so a consumer can round-trip it. The derived tones
		// (surface-2 / overlay) are NOT returned here — they still reach the canvas
		// via `preview_css`.
		$custom_colors = ( isset( $config['custom_colors'] ) && is_array( $config['custom_colors'] ) )
			? $config['custom_colors']
			: array();

		return array(
			'colors'        => $colors,
			'custom_colors' => $custom_colors,
			'saved'         => $engine->has_saved_style_guide(),
			'preview_css'   => $engine->preview_css_for( $colors ),
		);
	}
}
