<?php
/**
 * Create Content ability.
 *
 * Creates a spectra/content block (heading or paragraph).
 *
 * @package Spectra_Blocks
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Spectra_Blocks_Ability_Create_Content' ) ) {

	/**
	 * Class Spectra_Blocks_Ability_Create_Content.
	 */
	final class Spectra_Blocks_Ability_Create_Content extends Spectra_Blocks_Abstract_Ability {
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
			return 'spectra/create-content';
		}

		/**
		 * {@inheritdoc}
		 */
		protected function get_label() {
			return __( 'Create Content Block', 'spectra-blocks' );
		}

		/**
		 * {@inheritdoc}
		 */
		protected function get_description() {
			return __( 'Creates a spectra/content block — replaces core heading and paragraph. Supports tagName (h1-h6, p, div, span) and text alignment. Optionally inserts into a post.', 'spectra-blocks' );
		}

		/**
		 * {@inheritdoc}
		 */
		protected function get_category() {
			return 'spectra-content';
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
				'required'             => array( 'text' ),
				'properties'           => array(
					'text'       => array(
						'type'        => 'string',
						'description' => __( 'The text content.', 'spectra-blocks' ),
					),
					'tag_name'   => array(
						'type'    => 'string',
						'enum'    => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span' ),
						'default' => 'h2',
					),
					'text_align' => array(
						'type'    => 'string',
						'enum'    => array( 'left', 'center', 'right' ),
						'default' => 'left',
					),
					'post_id'    => array(
						'type'        => 'integer',
						'description' => __( 'Post/page ID to insert into. If omitted, returns markup only.', 'spectra-blocks' ),
					),
					'mode'       => array(
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
			$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span' );
			$tag_name     = isset( $input['tag_name'] ) && in_array( $input['tag_name'], $allowed_tags, true ) ? $input['tag_name'] : 'h2';
			$text         = wp_kses_post( $input['text'] );

			if ( '' === $text ) {
				return new WP_Error( 'spectra_blocks_invalid_text', __( 'Text content cannot be empty.', 'spectra-blocks' ), array( 'status' => 400 ) );
			}

			$text_align = isset( $input['text_align'] ) ? $input['text_align'] : 'left';
			$post_id    = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : null;
			$mode       = isset( $input['mode'] ) ? $input['mode'] : 'append';
			$block_id   = wp_generate_uuid4();

			$attrs = array(
				'block_id' => $block_id,
				'tagName'  => $tag_name,
				'text'     => $text,
				'style'    => array( 'typography' => array( 'textAlign' => $text_align ) ),
			);

			$attrs_json = wp_json_encode( $attrs );
			$markup     = "<!-- wp:spectra/content {$attrs_json} -->\n";
			$markup    .= "<{$tag_name} class=\"wp-block-spectra-content\">{$text}</{$tag_name}>\n";
			$markup    .= "<!-- /wp:spectra/content -->\n";

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
