<?php
/**
 * Apply Display Conditions ability.
 *
 * Applies display condition settings to a block in a post.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * ApplyDisplayConditions ability class.
 *
 * @since 1.0.0
 */
class ApplyDisplayConditions extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/apply-display-conditions';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Apply Display Conditions to Block', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Applies display conditions (login state, user role, browser, OS, day of week) to a block in a post. Blocks are completely removed from page output server-side when conditions are met.', 'spectra-blocks' );
	}

	/**
	 * Get the ability category.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_category(): string {
		return 'spectra-blocks-extensions';
	}

	/**
	 * Get ability annotations for REST discovery.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_annotations(): array {
		return array(
			'readonly'    => false,
			'destructive' => false,
			'idempotent'  => true,
		);
	}

	/**
	 * Get the input schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_input_schema(): array {
		return array(
			'type'       => 'object',
			'required'   => array( 'post_id', 'block_index' ),
			'properties' => array(
				'post_id'           => array(
					'type'        => 'integer',
					'description' => __( 'The post ID containing the block.', 'spectra-blocks' ),
				),
				'block_index'       => array(
					'type'        => 'integer',
					'description' => __( 'The 0-based block index.', 'spectra-blocks' ),
				),
				'hideWhenLoggedIn'  => array(
					'type'        => 'boolean',
					'description' => __( 'Hide the block from logged-in users.', 'spectra-blocks' ),
					'default'     => false,
				),
				'hideWhenLoggedOut' => array(
					'type'        => 'boolean',
					'description' => __( 'Hide the block from logged-out users.', 'spectra-blocks' ),
					'default'     => false,
				),
				'hideForRole'       => array(
					'type'        => 'string',
					'description' => __( 'Hide the block for users with this role slug (e.g. "editor", "subscriber").', 'spectra-blocks' ),
				),
				'hideForBrowser'    => array(
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
						'enum' => array( 'firefox', 'chrome', 'opera', 'safari', 'edge' ),
					),
					'description' => __( 'Hide block for specific browsers.', 'spectra-blocks' ),
				),
				'hideForOS'         => array(
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
						'enum' => array( 'iphone', 'android', 'windows', 'open_bsd', 'sun_os', 'linux', 'mac_os' ),
					),
					'description' => __( 'Hide block for specific operating systems.', 'spectra-blocks' ),
				),
				'hideOnDays'        => array(
					'type'        => 'array',
					'description' => __( 'Hide the block on these days of the week (monday, tuesday, wednesday, thursday, friday, saturday, sunday).', 'spectra-blocks' ),
					'items'       => array( 'type' => 'string' ),
				),
			),
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success'            => array( 'type' => 'boolean' ),
				'block_name'         => array( 'type' => 'string' ),
				'display_conditions' => array( 'type' => 'object' ),
			),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $params Input parameters.
	 * @return array<string, mixed>|WP_Error Result or error.
	 */
	public function execute( array $params ) {
		if ( empty( $params['post_id'] ) || ! isset( $params['block_index'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The post_id and block_index parameters are required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$post_id     = absint( is_scalar( $params['post_id'] ) ? (int) $params['post_id'] : 0 );
		$block_index = is_scalar( $params['block_index'] ) ? (int) $params['block_index'] : 0;

		$post = $this->get_validated_post( $post_id );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$all_blocks = parse_blocks( $post->post_content );
		$raw_index  = $this->find_block_raw_index( $all_blocks, $block_index );

		if ( is_wp_error( $raw_index ) ) {
			return $raw_index;
		}

		$existing_conditions = $all_blocks[ $raw_index ]['attrs']['displayConditions'] ?? array(
			'hideWhenLoggedIn'  => false,
			'hideWhenLoggedOut' => false,
			'hideForRole'       => '',
			'hideForBrowser'    => array(),
			'hideForOS'         => array(),
			'hideOnDays'        => array(),
		);

		if ( isset( $params['hideWhenLoggedIn'] ) ) {
			$existing_conditions['hideWhenLoggedIn'] = (bool) $params['hideWhenLoggedIn'];
		}

		if ( isset( $params['hideWhenLoggedOut'] ) ) {
			$existing_conditions['hideWhenLoggedOut'] = (bool) $params['hideWhenLoggedOut'];
		}

		if ( isset( $params['hideForRole'] ) ) {
			$existing_conditions['hideForRole'] = sanitize_key( is_scalar( $params['hideForRole'] ) ? (string) $params['hideForRole'] : '' );
		}

		if ( isset( $params['hideForBrowser'] ) && is_array( $params['hideForBrowser'] ) ) {
			$allowed_browsers = array( 'firefox', 'chrome', 'opera', 'safari', 'edge' );
			$browsers         = array();

			foreach ( $params['hideForBrowser'] as $browser ) {
				$browser = sanitize_text_field( $browser );
				if ( in_array( $browser, $allowed_browsers, true ) ) {
					$browsers[] = $browser;
				}
			}

			$existing_conditions['hideForBrowser'] = $browsers;
		}

		if ( isset( $params['hideForOS'] ) && is_array( $params['hideForOS'] ) ) {
			$allowed_os = array( 'iphone', 'android', 'windows', 'open_bsd', 'sun_os', 'linux', 'mac_os' );
			$os_list    = array();

			foreach ( $params['hideForOS'] as $os ) {
				$os = sanitize_text_field( $os );
				if ( in_array( $os, $allowed_os, true ) ) {
					$os_list[] = $os;
				}
			}

			$existing_conditions['hideForOS'] = $os_list;
		}

		if ( isset( $params['hideOnDays'] ) && is_array( $params['hideOnDays'] ) ) {
			$allowed_days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
			$days         = array();

			foreach ( $params['hideOnDays'] as $day ) {
				$day = sanitize_text_field( $day );
				if ( in_array( $day, $allowed_days, true ) ) {
					$days[] = $day;
				}
			}

			$existing_conditions['hideOnDays'] = $days;
		}

		$block_name                        = $all_blocks[ $raw_index ]['blockName'];
		$all_blocks[ $raw_index ]['attrs'] = array_merge(
			$all_blocks[ $raw_index ]['attrs'] ?? array(),
			array( 'displayConditions' => $existing_conditions )
		);

		$result = $this->update_post_blocks( $post_id, $all_blocks );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'            => true,
			'block_name'         => $block_name,
			'display_conditions' => $existing_conditions,
		);
	}
}
