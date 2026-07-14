<?php
/**
 * List Available Blocks ability.
 *
 * Returns a list of all Spectra blocks registered by the plugin.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * ListAvailableBlocks ability class.
 *
 * @since 1.0.0
 */
class ListAvailableBlocks extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/list-available-blocks';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'List Available Spectra Blocks', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Returns a list of all Spectra blocks registered by the plugin, optionally filtered by type (all, parent, or child).', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-discovery';
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
				'type' => array(
					'type'        => 'string',
					'description' => __( 'Filter by block type: all, parent, or child.', 'spectra-blocks' ),
					'enum'        => array( 'all', 'parent', 'child' ),
					'default'     => 'all',
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
				'blocks' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'name'        => array( 'type' => 'string' ),
							'title'       => array( 'type' => 'string' ),
							'description' => array( 'type' => 'string' ),
							'category'    => array( 'type' => 'string' ),
							'parent'      => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'keywords'    => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
						),
					),
				),
				'count'  => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Input parameters.
	 * @return array List of blocks.
	 */
	public function execute( array $params ): array {
		$type       = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'all';
		$allowed    = array( 'all', 'parent', 'child' );
		$type       = in_array( $type, $allowed, true ) ? $type : 'all';
		$registry   = \WP_Block_Type_Registry::get_instance();
		$all_blocks = $registry->get_all_registered();
		$blocks     = array();

		foreach ( $all_blocks as $block_type ) {
			// Include spectra/ (free) and spectra-pro/ (pro, when active) blocks.
			$is_free = strpos( $block_type->name, 'spectra/' ) === 0;
			$is_pro  = $this->is_pro_active() && strpos( $block_type->name, 'spectra-pro/' ) === 0;
			if ( ! $is_free && ! $is_pro ) {
				continue;
			}

			$is_child = ! empty( $block_type->parent );

			if ( 'parent' === $type && $is_child ) {
				continue;
			}

			if ( 'child' === $type && ! $is_child ) {
				continue;
			}

			$blocks[] = array(
				'name'        => $block_type->name,
				'title'       => $block_type->title ?? '',
				'description' => $block_type->description ?? '',
				'category'    => $block_type->category ?? '',
				'parent'      => $block_type->parent ?? array(),
				'keywords'    => $block_type->keywords ?? array(),
			);
		}

		// Sort blocks alphabetically by name.
		usort(
			$blocks,
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		return array(
			'blocks' => $blocks,
			'count'  => count( $blocks ),
		);
	}
}
