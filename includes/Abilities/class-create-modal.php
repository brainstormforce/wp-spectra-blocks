<?php
/**
 * Create Modal ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateModal ability class.
 *
 * @since 1.0.0
 */
class CreateModal extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-modal';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Modal', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra modal/popup block with a trigger element and popup content. Supports button, icon, or text triggers.', 'spectra-blocks' );
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
			'required'   => array( 'triggerText', 'popupContent' ),
			'properties' => array_merge(
				array(
					'triggerText'  => array(
						'type'        => 'string',
						'description' => __( 'Text for the trigger button/element.', 'spectra-blocks' ),
					),
					'popupContent' => array(
						'type'        => 'string',
						'description' => __( 'HTML or block markup for the popup content.', 'spectra-blocks' ),
					),
					'modalTrigger' => array(
						'type'        => 'string',
						'description' => __( 'Trigger type: button, icon, or text.', 'spectra-blocks' ),
						'enum'        => array( 'button', 'icon', 'text' ),
						'default'     => 'button',
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
		if ( empty( $params['triggerText'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The triggerText parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $params['popupContent'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The popupContent parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$trigger_text  = sanitize_text_field( $params['triggerText'] );
		$popup_content = wp_kses_post( $params['popupContent'] );

		$modal_trigger = 'button';
		if ( ! empty( $params['modalTrigger'] ) ) {
			$trigger       = sanitize_text_field( $params['modalTrigger'] );
			$allowed       = array( 'button', 'icon', 'text' );
			$modal_trigger = in_array( $trigger, $allowed, true ) ? $trigger : 'button';
		}

		$modal_id    = wp_generate_uuid4();
		$modal_attrs = array(
			'modalId'      => $modal_id,
			'modalTrigger' => $modal_trigger,
		);

		// Build trigger block based on type.
		$trigger_inner = '';
		switch ( $modal_trigger ) {
			case 'icon':
				$trigger_inner = '<!-- wp:spectra/modal-child-icon /-->';
				break;

			case 'text':
				$trigger_inner = '<!-- wp:spectra/modal-child-content '
					. wp_json_encode( array( 'text' => $trigger_text ) ) . ' /-->';
				break;

			case 'button':
			default:
				$trigger_inner = '<!-- wp:spectra/modal-child-button '
					. wp_json_encode( array( 'text' => $trigger_text ) ) . ' /-->';
				break;
		}

		$trigger_block = '<!-- wp:spectra/modal-child-trigger -->'
			. "\n" . $trigger_inner . "\n"
			. '<!-- /wp:spectra/modal-child-trigger -->';

		// Build popup content block.
		$popup_inner_content = '<!-- wp:paragraph -->'
			. "\n" . '<p>' . $popup_content . '</p>' . "\n"
			. '<!-- /wp:paragraph -->';

		$popup_content_block = '<!-- wp:spectra/modal-popup-content -->'
			. "\n" . $popup_inner_content . "\n"
			. '<!-- /wp:spectra/modal-popup-content -->';

		$close_icon = '<!-- wp:spectra/modal-child-popup-close-icon /-->';

		$popup_block = '<!-- wp:spectra/modal-popup -->'
			. "\n" . $close_icon . "\n" . $popup_content_block . "\n"
			. '<!-- /wp:spectra/modal-popup -->';

		$modal_attrs_json = ' ' . wp_json_encode( $modal_attrs );
		$block_markup     = '<!-- wp:spectra/modal' . $modal_attrs_json . ' -->'
			. "\n" . $trigger_block . "\n" . $popup_block . "\n"
			. '<!-- /wp:spectra/modal -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
