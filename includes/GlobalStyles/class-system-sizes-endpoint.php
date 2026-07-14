<?php
/**
 * REST endpoint for font-size and spacing system size overrides.
 *
 * GET  /spectra-blocks/v1/global-styles/system-sizes
 *   → Returns defaults merged with stored overrides. Each entry includes
 *     `default_value`, `default_unit`, and `changed` so the UI can show
 *     the Reset button and the Save button can diff against defaults.
 *
 * POST /spectra-blocks/v1/global-styles/system-sizes
 *   → Accepts { fontsize?: object, spacing?: object } and replaces stored
 *     overrides for the supplied groups. Only keys that differ from their
 *     defaults are persisted; identical-to-default values are dropped so
 *     the option stays lean.
 *
 * @package Spectra\GlobalStyles
 * @since   1.0.0
 */

namespace SpectraBlocks\GlobalStyles;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SystemSizesEndpoint.
 *
 * @since 1.0.0
 */
class SystemSizesEndpoint {

	/**
	 * WordPress option key for stored overrides.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_KEY = 'spectra_blocks_system_sizes';

	/**
	 * Default font-size token values (from gs-variables.json).
	 *
	 * @since 1.0.0
	 * @var array<string, array{value: float, unit: string}>
	 */
	const FONTSIZE_DEFAULTS = array(
		'heading-1' => array(
			'value' => 2.25,
			'unit'  => 'rem',
		),
		'heading-2' => array(
			'value' => 1.875,
			'unit'  => 'rem',
		),
		'heading-3' => array(
			'value' => 1.5,
			'unit'  => 'rem',
		),
		'heading-4' => array(
			'value' => 1.25,
			'unit'  => 'rem',
		),
		'heading-5' => array(
			'value' => 1.125,
			'unit'  => 'rem',
		),
		'heading-6' => array(
			'value' => 1.0,
			'unit'  => 'rem',
		),
		'text-xs'   => array(
			'value' => 0.75,
			'unit'  => 'rem',
		),
		'text-sm'   => array(
			'value' => 0.875,
			'unit'  => 'rem',
		),
		'text-md'   => array(
			'value' => 1.0,
			'unit'  => 'rem',
		),
		'text-base' => array(
			'value' => 1.0,
			'unit'  => 'rem',
		),
		'text-lg'   => array(
			'value' => 1.25,
			'unit'  => 'rem',
		),
		'text-xl'   => array(
			'value' => 1.5,
			'unit'  => 'rem',
		),
		'text-xxl'  => array(
			'value' => 2.0,
			'unit'  => 'rem',
		),
	);

	/**
	 * Default spacing token values (from gs-variables.json).
	 *
	 * @since 1.0.0
	 * @var array<string, array{value: float, unit: string}>
	 */
	const SPACING_DEFAULTS = array(
		'space-xs'  => array(
			'value' => 0.5,
			'unit'  => 'rem',
		),
		'space-sm'  => array(
			'value' => 1.0,
			'unit'  => 'rem',
		),
		'space-md'  => array(
			'value' => 1.5,
			'unit'  => 'rem',
		),
		'space-lg'  => array(
			'value' => 2.0,
			'unit'  => 'rem',
		),
		'space-xl'  => array(
			'value' => 3.0,
			'unit'  => 'rem',
		),
		'space-xxl' => array(
			'value' => 5.0,
			'unit'  => 'rem',
		),
	);

	/**
	 * Allowed CSS units.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	const ALLOWED_UNITS = array( 'rem', 'em', 'px' );

	/**
	 * Register REST routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			Engine::REST_NAMESPACE,
			'/global-styles/system-sizes',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sizes' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_sizes' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'fontsize' => array(
							'type'    => array( 'object', 'array' ),
							'default' => array(),
						),
						'spacing'  => array(
							'type'    => array( 'object', 'array' ),
							'default' => array(),
						),
					),
				),
			)
		);
	}

	/**
	 * GET handler — returns defaults merged with stored overrides.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_REST_Response
	 */
	public function get_sizes(): WP_REST_Response {
		$stored = get_option( self::OPTION_KEY, array() );
		$stored = is_array( $stored ) ? $stored : array();

		return rest_ensure_response(
			array(
				'fontsize' => $this->merge_with_defaults(
					$stored['fontsize'] ?? array(),
					self::FONTSIZE_DEFAULTS
				),
				'spacing'  => $this->merge_with_defaults(
					$stored['spacing'] ?? array(),
					self::SPACING_DEFAULTS
				),
			)
		);
	}

	/**
	 * POST handler — saves overrides for the supplied groups.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function save_sizes( WP_REST_Request $request ): WP_REST_Response {
		$fontsize_input = $request->get_param( 'fontsize' );
		$spacing_input  = $request->get_param( 'spacing' );

		$stored = get_option( self::OPTION_KEY, array() );
		$stored = is_array( $stored ) ? $stored : array();

		if ( is_array( $fontsize_input ) && ! empty( $fontsize_input ) ) {
			$stored['fontsize'] = $this->sanitize_group( $fontsize_input, self::FONTSIZE_DEFAULTS );
		}

		if ( is_array( $spacing_input ) && ! empty( $spacing_input ) ) {
			$stored['spacing'] = $this->sanitize_group( $spacing_input, self::SPACING_DEFAULTS );
		}

		update_option( self::OPTION_KEY, $stored );

		return rest_ensure_response(
			array(
				'fontsize' => $this->merge_with_defaults(
					$stored['fontsize'] ?? array(),
					self::FONTSIZE_DEFAULTS
				),
				'spacing'  => $this->merge_with_defaults(
					$stored['spacing'] ?? array(),
					self::SPACING_DEFAULTS
				),
			)
		);
	}

	/**
	 * Merge stored overrides into the defaults, annotating each entry.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>                             $stored   Stored overrides (may be partial).
	 * @param array<string, array{value: float, unit: string}> $defaults Full defaults map.
	 * @return array<string, array{value: float, unit: string, default_value: float, default_unit: string, changed: bool}>
	 */
	private function merge_with_defaults( array $stored, array $defaults ): array {
		$result = array();

		foreach ( $defaults as $key => $default ) {
			$override = isset( $stored[ $key ] ) && is_array( $stored[ $key ] ) ? $stored[ $key ] : null;

			$value = ( null !== $override && is_numeric( $override['value'] ?? null ) )
				? (float) $override['value']
				: (float) $default['value'];

			$unit = ( null !== $override && in_array( $override['unit'] ?? '', self::ALLOWED_UNITS, true ) )
				? $override['unit']
				: $default['unit'];

			$result[ $key ] = array(
				'value'         => $value,
				'unit'          => $unit,
				'default_value' => (float) $default['value'],
				'default_unit'  => $default['unit'],
				'changed'       => null !== $override,
			);
		}

		return $result;
	}

	/**
	 * Sanitize and filter a submitted size group to only store non-default overrides.
	 *
	 * Keys not in defaults are silently ignored. Values matching the default are
	 * dropped from storage so the option stays lean (Reset results in a clean state).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>                             $input    Submitted key→{value,unit} map.
	 * @param array<string, array{value: float, unit: string}> $defaults Defaults to validate against.
	 * @return array<string, array{value: float, unit: string}>
	 */
	private function sanitize_group( array $input, array $defaults ): array {
		$result = array();

		foreach ( $defaults as $key => $default ) {
			$entry = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : null;

			if ( null === $entry ) {
				continue;
			}

			$value = is_numeric( $entry['value'] ?? null )
				? (float) $entry['value']
				: (float) $default['value'];

			$unit = in_array( $entry['unit'] ?? '', self::ALLOWED_UNITS, true )
				? $entry['unit']
				: $default['unit'];

			// Drop if identical to default — keeps the option lean.
			if ( $value === (float) $default['value'] && $unit === $default['unit'] ) {
				continue;
			}

			$result[ $key ] = array(
				'value' => $value,
				'unit'  => $unit,
			);
		}

		return $result;
	}

	/**
	 * Capability check — matches the other Global Styles CRUD routes.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( 'edit_theme_options' );
	}
}
