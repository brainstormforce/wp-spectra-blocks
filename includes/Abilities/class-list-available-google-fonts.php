<?php
/**
 * List Available Google Fonts ability.
 *
 * Returns available Google Fonts from the WordPress Font Library with search filtering.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use SpectraBlocks\FontManager;

defined( 'ABSPATH' ) || exit;

/**
 * ListAvailableGoogleFonts ability class.
 *
 * @since 1.0.0
 */
class ListAvailableGoogleFonts extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/list-available-google-fonts';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'List Available Google Fonts', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Returns available Google Fonts from the WordPress Font Library. Supports search filtering and pagination since the full collection contains 1600+ fonts.', 'spectra-blocks' );
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
			'properties' => array(
				'search'   => array(
					'type'        => 'string',
					'description' => __( 'Search term to filter fonts by name (e.g. "Inter", "Roboto").', 'spectra-blocks' ),
				),
				'page'     => array(
					'type'        => 'integer',
					'description' => __( 'Page number for pagination.', 'spectra-blocks' ),
					'default'     => 1,
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => __( 'Number of fonts per page (max 100).', 'spectra-blocks' ),
					'default'     => 50,
				),
			),
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
				'fonts'       => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'name'       => array( 'type' => 'string' ),
							'slug'       => array( 'type' => 'string' ),
							'fontFamily' => array( 'type' => 'string' ),
						),
					),
				),
				'total'       => array( 'type' => 'integer' ),
				'total_pages' => array( 'type' => 'integer' ),
				'page'        => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array Font list.
	 */
	public function execute( array $params ): array {
		$search   = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
		$page     = isset( $params['page'] ) ? max( 1, absint( $params['page'] ) ) : 1;
		$per_page = isset( $params['per_page'] ) ? min( 100, max( 1, absint( $params['per_page'] ) ) ) : 50;

		$all_fonts = FontManager::get_google_font_families();

		// Extract and normalize font data.
		$fonts = array();
		foreach ( $all_fonts as $font ) {
			$settings = $font['font_family_settings'] ?? $font;
			$name     = $settings['name'] ?? '';

			if ( empty( $name ) ) {
				continue;
			}

			// Apply search filter.
			if ( ! empty( $search ) && stripos( $name, $search ) === false ) {
				continue;
			}

			$fonts[] = array(
				'name'       => $name,
				'slug'       => $settings['slug'] ?? sanitize_title( $name ),
				'fontFamily' => $settings['fontFamily'] ?? $name,
			);
		}

		// Sort alphabetically.
		usort(
			$fonts,
			function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		$total       = count( $fonts );
		$total_pages = (int) ceil( $total / $per_page );
		$offset      = ( $page - 1 ) * $per_page;
		$fonts       = array_slice( $fonts, $offset, $per_page );

		return array(
			'fonts'       => $fonts,
			'total'       => $total,
			'total_pages' => $total_pages,
			'page'        => $page,
		);
	}
}
