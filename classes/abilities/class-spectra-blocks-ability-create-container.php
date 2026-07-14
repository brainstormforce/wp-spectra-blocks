<?php
/**
 * Create Container ability.
 *
 * Creates a spectra/container block with configurable layout and inner blocks.
 *
 * @package Spectra_Blocks
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Spectra_Blocks_Ability_Create_Container' ) ) {

	/**
	 * Class Spectra_Blocks_Ability_Create_Container.
	 */
	final class Spectra_Blocks_Ability_Create_Container extends Spectra_Blocks_Abstract_Ability {
		use Spectra_Blocks_Insert_Into_Post_Trait;

		/**
		 * Gate: write ability.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected $gated = 'spectra_blocks_enable_edit_abilities';

		/**
		 * {@inheritdoc}
		 */
		protected function get_name() {
			return 'spectra/create-container';
		}

		/**
		 * {@inheritdoc}
		 */
		protected function get_label() {
			return __( 'Create Container Block', 'spectra-blocks' );
		}

		/**
		 * {@inheritdoc}
		 */
		protected function get_description() {
			return __( 'Creates a spectra/container block with configurable flex layout, direction, alignment, and gap. Optionally inserts it into a post. Inner blocks can be provided as serialized block markup.', 'spectra-blocks' );
		}

		/**
		 * {@inheritdoc}
		 */
		protected function get_category() {
			return 'spectra-layout';
		}

		/**
		 * {@inheritdoc}
		 */
		protected function get_annotations() {
			return array(
				'readonly'      => false,
				'destructive'   => true,
				'idempotent'    => false,
				'openWorldHint' => false,
			);
		}

		/**
		 * {@inheritdoc}
		 */
		protected function get_input_schema() {
			return array(
				'type'                 => 'object',
				'properties'           => array(
					'direction'       => array(
						'type'    => 'string',
						'enum'    => array( 'row', 'column' ),
						'default' => 'column',
					),
					'align_items'     => array(
						'type'    => 'string',
						'enum'    => array( 'flex-start', 'center', 'flex-end', 'stretch' ),
						'default' => 'center',
					),
					'justify_content' => array(
						'type'    => 'string',
						'enum'    => array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' ),
						'default' => 'flex-start',
					),
					'gap'             => array(
						'type'        => 'string',
						'description' => __( 'Gap between children, e.g. "16px" or "1rem".', 'spectra-blocks' ),
						'default'     => '16px',
					),
					'inner_blocks'    => array(
						'type'        => 'string',
						'description' => __( 'Raw block markup to place inside the container. If omitted, an empty container is created.', 'spectra-blocks' ),
						'default'     => '',
					),
					'post_id'         => array(
						'type'        => 'integer',
						'description' => __( 'Post/page ID to insert into. If omitted, returns markup only.', 'spectra-blocks' ),
					),
					'mode'            => array(
						'type'    => 'string',
						'enum'    => array( 'replace', 'append', 'prepend' ),
						'default' => 'append',
					),
				),
				'additionalProperties' => false,
			);
		}

		/**
		 * {@inheritdoc}
		 */
		protected function get_output_schema() {
			return array(
				'type'       => 'object',
				'properties' => array(
					'block_markup' => array( 'type' => 'string' ),
					'post_id'      => array( 'type' => 'integer' ),
				),
			);
		}

		/**
		 * {@inheritdoc}
		 *
		 * @param array $input Ability input data.
		 */
		public function execute( array $input ) {
			$direction       = isset( $input['direction'] ) ? $input['direction'] : 'column';
			$align_items     = isset( $input['align_items'] ) ? $input['align_items'] : 'center';
			$justify_content = isset( $input['justify_content'] ) ? $input['justify_content'] : 'flex-start';
			$gap             = isset( $input['gap'] ) ? sanitize_text_field( $input['gap'] ) : '16px';
			$inner_blocks    = isset( $input['inner_blocks'] ) ? $input['inner_blocks'] : '';
			$post_id         = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : null;
			$mode            = isset( $input['mode'] ) ? $input['mode'] : 'append';
			$block_id        = wp_generate_uuid4();

			$attrs = array(
				'block_id'              => $block_id,
				'directionDesktop'      => $direction,
				'alignItemsDesktop'     => $align_items,
				'justifyContentDesktop' => $justify_content,
				'rowGapDesktop'         => $gap,
				'columnGapDesktop'      => $gap,
			);

			$attrs_json = wp_json_encode( $attrs, JSON_HEX_TAG );
			$markup     = "<!-- wp:spectra/container {$attrs_json} -->\n";
			$markup    .= '<div class="wp-block-spectra-container">';
			if ( $inner_blocks ) {
				$markup .= "\n" . serialize_blocks( parse_blocks( $inner_blocks ) ) . "\n";
			}
			$markup .= "</div>\n";
			$markup .= "<!-- /wp:spectra/container -->\n";

			if ( $post_id ) {
				$result = $this->insert_into_post( $post_id, $markup, $mode );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}

			return array(
				'block_markup' => $markup,
				'post_id'      => $post_id ? $post_id : 0,
			);
		}
	}
}
