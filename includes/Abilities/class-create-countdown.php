<?php
/**
 * Create Countdown ability.
 *
 * @package Spectra\Abilities
 */

namespace SpectraBlocks\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * CreateCountdown ability class.
 *
 * @since 1.0.0
 */
class CreateCountdown extends AbstractAbility {

	/**
	 * Get the ability name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'spectra-blocks/create-countdown';
	}

	/**
	 * Get the ability label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Create Countdown', 'spectra-blocks' );
	}

	/**
	 * Get the ability description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Creates a Spectra countdown timer block that counts down to a specified date and time. Shows days, hours, minutes, and seconds with optional labels.', 'spectra-blocks' );
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
			'required'   => array( 'endDateTime' ),
			'properties' => array_merge(
				array(
					'endDateTime' => array(
						'type'        => 'string',
						'description' => __( 'Target date and time in ISO 8601 format (e.g. "2025-12-31T23:59:59").', 'spectra-blocks' ),
					),
					'showDays'    => array(
						'type'        => 'boolean',
						'description' => __( 'Whether to show the days unit.', 'spectra-blocks' ),
						'default'     => true,
					),
					'showHours'   => array(
						'type'        => 'boolean',
						'description' => __( 'Whether to show the hours unit.', 'spectra-blocks' ),
						'default'     => true,
					),
					'showMinutes' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether to show the minutes unit.', 'spectra-blocks' ),
						'default'     => true,
					),
					'showSeconds' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether to show the seconds unit.', 'spectra-blocks' ),
						'default'     => true,
					),
					'labels'      => array(
						'type'        => 'object',
						'description' => __( 'Custom labels for each time unit.', 'spectra-blocks' ),
						'properties'  => array(
							'days'    => array( 'type' => 'string' ),
							'hours'   => array( 'type' => 'string' ),
							'minutes' => array( 'type' => 'string' ),
							'seconds' => array( 'type' => 'string' ),
						),
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
		if ( empty( $params['endDateTime'] ) ) {
			return new WP_Error(
				'spectra_blocks_missing_param',
				__( 'The endDateTime parameter is required.', 'spectra-blocks' ),
				array( 'status' => 400 )
			);
		}

		$countdown_attrs = array(
			'endDateTime' => sanitize_text_field( $params['endDateTime'] ),
		);

		$show_days    = $params['showDays'] ?? true;
		$show_hours   = $params['showHours'] ?? true;
		$show_minutes = $params['showMinutes'] ?? true;
		$show_seconds = $params['showSeconds'] ?? true;

		$countdown_attrs['showDays']    = (bool) $show_days;
		$countdown_attrs['showHours']   = (bool) $show_hours;
		$countdown_attrs['showMinutes'] = (bool) $show_minutes;
		$countdown_attrs['showSeconds'] = (bool) $show_seconds;

		// Custom labels.
		if ( ! empty( $params['labels'] ) && is_array( $params['labels'] ) ) {
			$label_map = array(
				'days'    => array( 'dayLabel', 'daysLabel' ),
				'hours'   => array( 'hourLabel', 'hoursLabel' ),
				'minutes' => array( 'minuteLabel', 'minutesLabel' ),
				'seconds' => array( 'secondLabel', 'secondsLabel' ),
			);

			foreach ( $label_map as $key => $attr_keys ) {
				if ( ! empty( $params['labels'][ $key ] ) ) {
					$label = sanitize_text_field( $params['labels'][ $key ] );
					// Set both singular and plural to the same value.
					$countdown_attrs[ $attr_keys[0] ] = $label;
					$countdown_attrs[ $attr_keys[1] ] = $label;
				}
			}
		}

		// Build inner child blocks.
		$children = '';
		$units    = array(
			'day'    => $show_days,
			'hour'   => $show_hours,
			'minute' => $show_minutes,
			'second' => $show_seconds,
		);

		$unit_index = 0;

		foreach ( $units as $unit => $show ) {
			if ( ! $show ) {
				continue;
			}

			// Add separator between units (not before the first one).
			if ( $unit_index > 0 ) {
				$children .= '<!-- wp:spectra/countdown-child-separator /-->' . "\n";
			}

			// Each unit block contains a number child and a label child.
			$number_block = '<!-- wp:spectra/countdown-child-number /-->';
			$label_block  = '<!-- wp:spectra/countdown-child-label /-->';

			$children .= '<!-- wp:spectra/countdown-child-' . $unit . ' -->'
				. "\n" . $number_block . "\n" . $label_block . "\n"
				. '<!-- /wp:spectra/countdown-child-' . $unit . ' -->' . "\n";

			++$unit_index;
		}

		$countdown_attrs_json = ' ' . wp_json_encode( $countdown_attrs );
		$block_markup         = '<!-- wp:spectra/countdown' . $countdown_attrs_json . ' -->'
			. "\n" . $children
			. '<!-- /wp:spectra/countdown -->';

		return $this->maybe_insert_and_return( $block_markup, $params );
	}
}
