<?php
/**
 * Create Counter ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateCounter ability class.
 *
 * @since 1.0.0
 */
class CreateCounter extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-counter';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Counter', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra animated counter block that counts from a start number to an end number. Supports number, bar, and circle styles with optional prefix, suffix, and title.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-content';
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
			'required'   => array( 'endNumber' ),
			'properties' => array_merge(
				array(
					'endNumber'    => array(
						'type'        => 'integer',
						'description' => __( 'The target number to count to.', 'spectra-blocks' ),
					),
					'startNumber'  => array(
						'type'        => 'integer',
						'description' => __( 'The starting number.', 'spectra-blocks' ),
						'default'     => 0,
					),
					'counterStyle' => array(
						'type'        => 'string',
						'description' => __( 'Counter display style: number, bar, or circle.', 'spectra-blocks' ),
						'enum'        => array( 'number', 'bar', 'circle' ),
						'default'     => 'number',
					),
					'prefix'       => array(
						'type'        => 'string',
						'description' => __( 'Text prefix before the number (e.g. "$").', 'spectra-blocks' ),
					),
					'suffix'       => array(
						'type'        => 'string',
						'description' => __( 'Text suffix after the number (e.g. "%", "+").', 'spectra-blocks' ),
					),
					'title'        => array(
						'type'        => 'string',
						'description' => __( 'Optional title/label displayed with the counter.', 'spectra-blocks' ),
					),
				),
				$this->get_post_insertion_schema()
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
		return $this->get_block_markup_output_schema();
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array|WP_Error
	 */
	public function execute( array $params ) {
		if ( ! isset( $params['endNumber'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The endNumber parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$counter_attrs = array(
			'endNumber' => absint( $params['endNumber'] ),
		);

		if ( isset( $params['startNumber'] ) ) {
			$counter_attrs['startNumber'] = absint( $params['startNumber'] );
		}

		if ( ! empty( $params['counterStyle'] ) ) {
			$style          = sanitize_text_field( $params['counterStyle'] );
			$allowed_styles = array( 'number', 'bar', 'circle' );
			if ( in_array( $style, $allowed_styles, true ) ) {
				$counter_attrs['counterStyle'] = $style;
			}
		}

		if ( ! empty( $params['prefix'] ) ) {
			$counter_attrs['prefix'] = sanitize_text_field( $params['prefix'] );
		}

		if ( ! empty( $params['suffix'] ) ) {
			$counter_attrs['suffix'] = sanitize_text_field( $params['suffix'] );
		}

		// Build inner blocks based on counter style.
		$style    = $counter_attrs['counterStyle'] ?? 'number';
		$children = '';

		if ( 'bar' === $style ) {
			$children .= '<!-- wp:spectra/counter-child-progress-bar /-->' . "\n";
		}

		$children .= '<!-- wp:spectra/counter-child-number /-->' . "\n";

		// Add title as a paragraph if provided.
		if ( ! empty( $params['title'] ) ) {
			$title     = sanitize_text_field( $params['title'] );
			$children .= '<!-- wp:paragraph -->' . "\n"
				. '<p>' . esc_html( $title ) . '</p>' . "\n"
				. '<!-- /wp:paragraph -->' . "\n";
		}

		$counter_attrs_json = ' ' . wp_json_encode( $counter_attrs );
		$block_markup       = '<!-- wp:spectra/counter' . $counter_attrs_json . ' -->'
			. "\n" . $children
			. '<!-- /wp:spectra/counter -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
