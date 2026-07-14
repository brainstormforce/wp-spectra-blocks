<?php
/**
 * Spectra Blocks Analytics Events helper.
 *
 * Handles one-time milestone event tracking with two-tier deduplication.
 * Events are queued in a pending list and flushed into the bsf_core_stats
 * payload each analytics cycle.
 *
 * Ported from UAGB_Analytics_Events. Option keys are renamed with the
 * spectra_blocks_ prefix so a site running both UAGB and Spectra Blocks
 * does not collide.
 *
 * @package Spectra\Analytics
 */

namespace Spectra\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Class Events.
 *
 * @since 1.0.0
 */
class Events {

	/**
	 * Option key for pending events (full payloads, ephemeral).
	 *
	 * @var string
	 */
	const PENDING_OPTION = 'spectra_blocks_usage_events_pending';

	/**
	 * Option key for pushed event names (just strings, persistent).
	 *
	 * @var string
	 */
	const PUSHED_OPTION = 'spectra_blocks_usage_events_pushed';

	/**
	 * Queue a one-time event for the next analytics cycle.
	 *
	 * @since 1.0.0
	 * @param string $event_name  Unique event identifier.
	 * @param string $event_value Optional primary value (version, ID, mode).
	 * @param array  $properties  Optional key-value pairs for extra context.
	 * @return bool Whether the event was queued.
	 */
	public static function track( $event_name, $event_value = '', $properties = array() ) {
		if ( empty( $event_name ) || '' === trim( $event_name ) ) {
			return false;
		}

		if ( self::is_tracked( $event_name ) ) {
			return false;
		}

		$pending = get_option( self::PENDING_OPTION, array() );

		if ( ! is_array( $pending ) ) {
			$pending = array();
		}

		$sanitized_properties = array();
		if ( ! empty( $properties ) && is_array( $properties ) ) {
			foreach ( $properties as $key => $value ) {
				$sanitized_properties[ sanitize_key( $key ) ] = is_string( $value )
					? sanitize_text_field( $value )
					: (int) $value;
			}
		}

		$pending[] = array(
			'event_name'  => sanitize_text_field( $event_name ),
			'event_value' => sanitize_text_field( $event_value ),
			'properties'  => ! empty( $sanitized_properties ) ? $sanitized_properties : new \stdClass(),
			'date'        => wp_date( 'Y-m-d H:i:s' ),
		);

		update_option( self::PENDING_OPTION, $pending, false );

		return true;
	}

	/**
	 * Flush pending events for inclusion in the analytics payload.
	 *
	 * Moves event names to the pushed list (persistent dedup)
	 * and deletes the full event payloads.
	 *
	 * @since 1.0.0
	 * @return array Array of event objects to send, or empty array.
	 */
	public static function flush_pending() {
		$pending = get_option( self::PENDING_OPTION, array() );

		if ( ! is_array( $pending ) || empty( $pending ) ) {
			return array();
		}

		$pending = array_filter(
			$pending,
			static function ( $event ) {
				return is_array( $event )
					&& ! empty( $event['event_name'] )
					&& '' !== trim( $event['event_name'] );
			}
		);

		if ( empty( $pending ) ) {
			delete_option( self::PENDING_OPTION );
			return array();
		}

		$pushed = get_option( self::PUSHED_OPTION, array() );

		if ( ! is_array( $pushed ) ) {
			$pushed = array();
		}

		foreach ( $pending as $event ) {
			if ( ! in_array( $event['event_name'], $pushed, true ) ) {
				$pushed[] = $event['event_name'];
			}
		}

		update_option( self::PUSHED_OPTION, $pushed, false );
		delete_option( self::PENDING_OPTION );

		return array_values( $pending );
	}

	/**
	 * Check if an event has already been tracked (pending or pushed).
	 *
	 * @since 1.0.0
	 * @param string $event_name Event name to check.
	 * @return bool
	 */
	public static function is_tracked( $event_name ) {
		$pushed = get_option( self::PUSHED_OPTION, array() );

		if ( is_array( $pushed ) && in_array( $event_name, $pushed, true ) ) {
			return true;
		}

		$pending = get_option( self::PENDING_OPTION, array() );

		if ( is_array( $pending ) ) {
			foreach ( $pending as $event ) {
				if ( isset( $event['event_name'] ) && $event['event_name'] === $event_name ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Remove specific events from the pushed dedup list so they can be re-tracked.
	 *
	 * @since 1.0.0
	 * @param array $event_names Event names to remove. Empty array clears all.
	 * @return void
	 */
	public static function flush_pushed( $event_names = array() ) {
		if ( empty( $event_names ) ) {
			delete_option( self::PUSHED_OPTION );
			return;
		}

		$pushed = get_option( self::PUSHED_OPTION, array() );

		if ( ! is_array( $pushed ) ) {
			return;
		}

		$pushed = array_values( array_diff( $pushed, $event_names ) );

		update_option( self::PUSHED_OPTION, $pushed, false );
	}

	/**
	 * Clear a single event from both pushed and pending queues.
	 *
	 * @since 1.0.0
	 * @param string $event_name Event to clear.
	 * @return void
	 */
	public static function clear_event( $event_name ) {
		if ( empty( $event_name ) || '' === trim( $event_name ) ) {
			return;
		}

		self::flush_pushed( array( $event_name ) );

		$pending = get_option( self::PENDING_OPTION, array() );
		if ( is_array( $pending ) && ! empty( $pending ) ) {
			$pending = array_values(
				array_filter(
					$pending,
					static function ( $event ) use ( $event_name ) {
						return ! isset( $event['event_name'] ) || $event['event_name'] !== $event_name;
					}
				)
			);
			update_option( self::PENDING_OPTION, $pending, false );
		}
	}

	/**
	 * Refresh a state-based event with the latest cumulative data.
	 *
	 * Removes the event from both pushed and pending queues, then tracks it
	 * fresh. Ensures only one entry exists per event_name so the server
	 * always receives the latest state instead of accumulated duplicates.
	 *
	 * @since 1.0.0
	 * @param string $event_name  Event identifier.
	 * @param string $event_value Primary value (version, mode, status).
	 * @param array  $properties  Cumulative state as key-value pairs.
	 * @return bool Whether the event was re-queued.
	 */
	public static function retrack_event( $event_name, $event_value = '', $properties = array() ) {
		if ( empty( $event_name ) || '' === trim( $event_name ) ) {
			return false;
		}

		self::flush_pushed( array( $event_name ) );

		$pending = get_option( self::PENDING_OPTION, array() );
		if ( is_array( $pending ) && ! empty( $pending ) ) {
			$pending = array_values(
				array_filter(
					$pending,
					static function ( $event ) use ( $event_name ) {
						return ! isset( $event['event_name'] ) || $event['event_name'] !== $event_name;
					}
				)
			);
			update_option( self::PENDING_OPTION, $pending, false );
		}

		return self::track( $event_name, $event_value, $properties );
	}
}
