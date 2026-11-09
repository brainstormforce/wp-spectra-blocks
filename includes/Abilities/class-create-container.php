<?php
/**
 * Create Container ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * CreateContainer ability class.
 *
 * @since 1.0.0
 */
class CreateContainer extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-container';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Container', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra container/section block that can wrap other blocks. Supports custom HTML tags, dimensions, and inner content.', 'spectra-blocks' );
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
			'properties' => array_merge(
				array(
					'htmlTag'    => array(
						'type'        => 'string',
						'description' => __( 'HTML element tag: div, section, header, footer, main, article, aside, or nav.', 'spectra-blocks' ),
						'enum'        => array( 'div', 'section', 'header', 'footer', 'main', 'article', 'aside', 'nav' ),
						'default'     => 'div',
					),
					'width'      => array(
						'type'        => 'string',
						'description' => __( 'Container width (CSS value, e.g. "100%", "1200px").', 'spectra-blocks' ),
					),
					'minHeight'  => array(
						'type'        => 'string',
						'description' => __( 'Container minimum height (CSS value, e.g. "400px").', 'spectra-blocks' ),
					),
					'direction'  => array(
						'type'        => 'string',
						'description' => __( 'Flex direction of the children: "row" (horizontal) or "column" (vertical).', 'spectra-blocks' ),
						'enum'        => array( 'row', 'column' ),
					),
					'justify'    => array(
						'type'        => 'string',
						'description' => __( 'Main-axis alignment of the children.', 'spectra-blocks' ),
						'enum'        => array( 'left', 'center', 'right', 'space-between' ),
					),
					'alignItems' => array(
						'type'        => 'string',
						'description' => __( 'Cross-axis alignment of the children: "top", "center", or "bottom".', 'spectra-blocks' ),
						'enum'        => array( 'top', 'center', 'bottom' ),
					),
					'gap'        => array(
						'type'        => 'string',
						'description' => __( 'Gap between children (CSS value, e.g. "20px", "1rem").', 'spectra-blocks' ),
					),
					'content'    => array(
						'type'        => 'string',
						'description' => __( 'Inner block markup to place inside the container. Should be serialized block comments.', 'spectra-blocks' ),
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
	 * @return array|\WP_Error
	 */
	public function execute( array $params ) {
		// Pre-populate the attributes the container's editor assigns on mount
		// (unique id, root-parent flag, full align). Without them the block writes
		// these on open and the post opens with unsaved changes (#10). A top-level
		// container inserted into a post is a root parent, which core aligns full.
		$attrs = array(
			'variationSelected' => true,
			'isBlockRootParent' => true,
			'align'             => 'full',
			'spectraId'         => 'spectra-' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 12 ),
		);

		if ( ! empty( $params['htmlTag'] ) ) {
			$tag          = sanitize_text_field( $params['htmlTag'] );
			$allowed_tags = array( 'div', 'section', 'header', 'footer', 'main', 'article', 'aside', 'nav' );
			if ( in_array( $tag, $allowed_tags, true ) ) {
				$attrs['htmlTag'] = $tag;
			}
		}

		if ( ! empty( $params['width'] ) ) {
			$attrs['width'] = sanitize_text_field( $params['width'] );
		}

		if ( ! empty( $params['minHeight'] ) ) {
			$attrs['minHeight'] = sanitize_text_field( $params['minHeight'] );
		}

		// Flex layout: direction, alignment and gap live on the core `layout` attribute
		// (plus style.spacing.blockGap for the gap), matching what the block editor writes.
		$layout = array(
			'type'              => 'flex',
			'flexWrap'          => 'nowrap',
			'orientation'       => 'vertical',
			'justifyContent'    => 'left',
			'verticalAlignment' => 'center',
		);
		if ( ! empty( $params['direction'] ) && in_array( $params['direction'], array( 'row', 'column' ), true ) ) {
			$layout['orientation'] = 'row' === $params['direction'] ? 'horizontal' : 'vertical';
		}
		if ( ! empty( $params['justify'] ) && in_array( $params['justify'], array( 'left', 'center', 'right', 'space-between' ), true ) ) {
			$layout['justifyContent'] = $params['justify'];
		}
		if ( ! empty( $params['alignItems'] ) && in_array( $params['alignItems'], array( 'top', 'center', 'bottom' ), true ) ) {
			$layout['verticalAlignment'] = $params['alignItems'];
		}
		$attrs['layout'] = $layout;

		if ( ! empty( $params['gap'] ) ) {
			$attrs['style'] = array(
				'spacing' => array(
					'blockGap' => sanitize_text_field( $params['gap'] ),
				),
			);
		}

		$inner_content = '';
		if ( ! empty( $params['content'] ) ) {
			$inner_content = wp_kses_post( $params['content'] );
		}

		$attrs_json   = ' ' . wp_json_encode( $attrs );
		$block_markup = '<!-- wp:spectra/container' . $attrs_json . ' -->'
			. "\n" . $inner_content . "\n"
			. '<!-- /wp:spectra/container -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
