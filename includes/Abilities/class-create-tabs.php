<?php
/**
 * Create Tabs ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateTabs ability class.
 *
 * @since 1.0.0
 */
class CreateTabs extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-tabs';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Tabs', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra tabbed content block with a tab navigation bar and corresponding content panels. Each tab has a title and content.', 'spectra-blocks' );
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
			'required'   => array( 'tabs' ),
			'properties' => array_merge(
				array(
					'tabs'       => array(
						'type'        => 'array',
						'description' => __( 'Array of tab objects with title and content.', 'spectra-blocks' ),
						'items'       => array(
							'type'       => 'object',
							'required'   => array( 'title', 'content' ),
							'properties' => array(
								'title'   => array(
									'type'        => 'string',
									'description' => __( 'The tab button label.', 'spectra-blocks' ),
								),
								'content' => array(
									'type'        => 'string',
									'description' => __( 'The tab panel content (HTML or block markup).', 'spectra-blocks' ),
								),
							),
						),
					),
					'currentTab' => array(
						'type'        => 'integer',
						'description' => __( 'Index of the initially active tab (0-based).', 'spectra-blocks' ),
						'default'     => 0,
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
		if ( empty( $params['tabs'] ) || ! is_array( $params['tabs'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The tabs parameter is required and must be an array.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$current_tab = isset( $params['currentTab'] ) ? absint( $params['currentTab'] ) : 0;
		$tabs_attrs  = array(
			'variationSelected' => true,
			'currentTab'        => $current_tab,
		);

		// Build tab buttons for the wrapper.
		$tab_buttons = '';
		$tab_panels  = '';
		$index       = 0;

		foreach ( $params['tabs'] as $tab ) {
			if ( empty( $tab['title'] ) || empty( $tab['content'] ) ) {
				continue;
			}

			$title   = sanitize_text_field( $tab['title'] );
			$content = wp_kses_post( $tab['content'] );

			// Tab button.
			$btn_attrs = array(
				'text'       => $title,
				'currentTab' => $index,
			);

			$tab_buttons .= '<!-- wp:spectra/tabs-child-tab-button '
				. wp_json_encode( $btn_attrs ) . ' /-->' . "\n";

			// Tab panel with content.
			$panel_attrs = array(
				'currentTab' => $index,
			);

			$panel_content = '<!-- wp:paragraph -->'
				. "\n" . '<p>' . $content . '</p>' . "\n"
				. '<!-- /wp:paragraph -->';

			$tab_panels .= '<!-- wp:spectra/tabs-child-tabpanel '
				. wp_json_encode( $panel_attrs ) . ' -->'
				. "\n" . $panel_content . "\n"
				. '<!-- /wp:spectra/tabs-child-tabpanel -->' . "\n";

			++$index;
		}

		if ( empty( $tab_buttons ) ) {
			return new WP_Error(
				'spectra_blocks_invalid_param',
				__( 'At least one tab with title and content is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		// Wrap buttons in tab-wrapper.
		$tab_wrapper = '<!-- wp:spectra/tabs-child-tab-wrapper -->'
			. "\n" . $tab_buttons
			. '<!-- /wp:spectra/tabs-child-tab-wrapper -->';

		$tabs_attrs_json = ' ' . wp_json_encode( $tabs_attrs );
		$block_markup    = '<!-- wp:spectra/tabs' . $tabs_attrs_json . ' -->'
			. "\n" . $tab_wrapper . "\n" . $tab_panels
			. '<!-- /wp:spectra/tabs -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
