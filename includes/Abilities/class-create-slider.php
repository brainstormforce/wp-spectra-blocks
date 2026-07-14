<?php
/**
 * Create Slider ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateSlider ability class.
 *
 * @since 1.0.0
 */
class CreateSlider extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-slider';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Slider', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra slider/carousel block with multiple slides. Supports autoplay, navigation arrows, pagination dots, and configurable slides per view.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-layout';
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
			'required'   => array( 'slides' ),
			'properties' => array_merge(
				array(
					'slides'        => array(
						'type'        => 'array',
						'description' => __( 'Array of slide objects containing content.', 'spectra-blocks' ),
						'items'       => array(
							'type'       => 'object',
							'required'   => array( 'content' ),
							'properties' => array(
								'content' => array(
									'type'        => 'string',
									'description' => __( 'The slide content (HTML or block markup).', 'spectra-blocks' ),
								),
							),
						),
					),
					'slidesPerView' => array(
						'type'        => 'integer',
						'description' => __( 'Number of slides visible at once.', 'spectra-blocks' ),
						'default'     => 1,
					),
					'loop'          => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the slider loops continuously.', 'spectra-blocks' ),
						'default'     => false,
					),
					'autoplay'      => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the slider auto-advances.', 'spectra-blocks' ),
						'default'     => false,
					),
					'navigation'    => array(
						'type'        => 'boolean',
						'description' => __( 'Whether to show navigation arrows.', 'spectra-blocks' ),
						'default'     => true,
					),
					'pagination'    => array(
						'type'        => 'boolean',
						'description' => __( 'Whether to show pagination dots.', 'spectra-blocks' ),
						'default'     => true,
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
		if ( empty( $params['slides'] ) || ! is_array( $params['slides'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The slides parameter is required and must be an array.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$slider_id    = wp_generate_uuid4();
		$slider_attrs = array(
			'sliderId' => $slider_id,
		);

		if ( isset( $params['slidesPerView'] ) ) {
			$slider_attrs['slidesPerView'] = absint( $params['slidesPerView'] );
		}

		if ( isset( $params['loop'] ) ) {
			$slider_attrs['loop'] = (bool) $params['loop'];
		}

		if ( isset( $params['autoplay'] ) ) {
			$slider_attrs['autoplay'] = (bool) $params['autoplay'];
		}

		if ( isset( $params['navigation'] ) ) {
			$slider_attrs['displayArrows'] = (bool) $params['navigation'];
		}

		if ( isset( $params['pagination'] ) ) {
			$slider_attrs['displayDots'] = (bool) $params['pagination'];
		}

		$children    = '';
		$slide_count = 0;

		foreach ( $params['slides'] as $slide ) {
			if ( empty( $slide['content'] ) ) {
				continue;
			}

			$content = wp_kses_post( $slide['content'] );

			$slide_inner = '<!-- wp:paragraph -->'
				. "\n" . '<p>' . $content . '</p>' . "\n"
				. '<!-- /wp:paragraph -->';

			$children .= '<!-- wp:spectra/slider-child -->'
				. "\n" . $slide_inner . "\n"
				. '<!-- /wp:spectra/slider-child -->' . "\n";

			++$slide_count;
		}

		if ( empty( $children ) ) {
			return new WP_Error(
				'spectra_blocks_invalid_param',
				__( 'At least one slide with content is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$slider_attrs['slideCount'] = $slide_count;

		$slider_attrs_json = ' ' . wp_json_encode( $slider_attrs );
		$block_markup      = '<!-- wp:spectra/slider' . $slider_attrs_json . ' -->'
			. "\n" . $children
			. '<!-- /wp:spectra/slider -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
