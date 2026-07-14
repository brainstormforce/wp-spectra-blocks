<?php
/**
 * List Selected Fonts ability.
 *
 * Returns the Google Fonts currently selected in Spectra settings.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use SpectraBlocks\FontManager;

defined( 'ABSPATH' ) || exit;

/**
 * ListSelectedFonts ability class.
 *
 * @since 1.0.0
 */
class ListSelectedFonts extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/list-selected-fonts';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'List Selected Spectra Fonts', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Returns the Google Fonts currently selected and loaded by Spectra, including font family details and loading status.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-configuration';
	}

	/**
	 * Get ability annotations for REST discovery.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_annotations(): array {
		return array(
			'readonly'    => true,
			'destructive' => false,
			'idempotent'  => true,
		);
	}

	/**
	 * Get the input schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => new \stdClass(),
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'fonts'          => array(
					'type'        => 'array',
					'description' => __( 'Selected font families with their settings.', 'spectra-blocks' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name'       => array( 'type' => 'string' ),
							'slug'       => array( 'type' => 'string' ),
							'fontFamily' => array( 'type' => 'string' ),
						),
					),
				),
				'count'          => array( 'type' => 'integer' ),
				'load_locally'   => array(
					'type'        => 'boolean',
					'description' => __( 'Whether fonts are loaded locally instead of from Google CDN.', 'spectra-blocks' ),
				),
				'global_loading' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether global font loading is enabled.', 'spectra-blocks' ),
				),
			),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array Font data.
	 */
	public function execute( array $params ): array {
		$font_manager   = FontManager::instance();
		$cached_fonts   = $font_manager->get_cached_google_fonts();
		$load_locally   = FontManager::is_enabled_load_locally();
		$global_loading = 'enabled' === get_option( 'spectra_blocks_load_select_font_globally', 'disabled' );

		$fonts = array();
		foreach ( $cached_fonts as $font ) {
			$fonts[] = array(
				'name'       => $font['name'] ?? '',
				'slug'       => $font['slug'] ?? '',
				'fontFamily' => $font['fontFamily'] ?? '',
			);
		}

		return array(
			'fonts'          => $fonts,
			'count'          => count( $fonts ),
			'load_locally'   => $load_locally,
			'global_loading' => $global_loading,
		);
	}
}
