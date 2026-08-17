<?php
/**
 * Tests for the milestone-event behaviours restored to legacy parity:
 * the plugin_updated version detector and the settings_changed tracker.
 *
 * @package SpectraBlocks\Tests\Analytics
 */

/**
 * Unit tests for plugin_updated (version-change detection) and settings_changed.
 */
class EventTrackerTest extends WP_UnitTestCase {

	/**
	 * Reset the event queue and the options the tests touch.
	 */
	public function set_up() {
		parent::set_up();

		delete_option( \Spectra\Analytics\Events::PENDING_OPTION );
		delete_option( \Spectra\Analytics\Events::PUSHED_OPTION );
		delete_option( 'spectra_blocks_version' );
		delete_option( 'spectra_blocks_enable_gbs_extension' );
	}

	/**
	 * Find the first queued event with the given name.
	 *
	 * @param array<int, array<string, mixed>> $events Flushed events.
	 * @param string                           $name   Event name to find.
	 * @return array<string, mixed>|null
	 */
	private function find_event( $events, $name ) {
		foreach ( $events as $event ) {
			if ( isset( $event['event_name'] ) && $name === $event['event_name'] ) {
				return $event;
			}
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// plugin_updated — version-change detector
	// -------------------------------------------------------------------------

	/**
	 * A version bump fires plugin_updated with the previous version, and stamps the new one.
	 */
	public function test_plugin_updated_fires_on_version_bump() {
		update_option( 'spectra_blocks_version', '0.0.1' );

		Spectra_Blocks_Loader::maybe_track_version_update();

		$this->assertSame( SPECTRA_BLOCKS_VER, get_option( 'spectra_blocks_version' ), 'Version must be stamped to current.' );

		$event = $this->find_event( \Spectra\Analytics\Events::flush_pending(), 'plugin_updated' );
		$this->assertNotNull( $event, 'plugin_updated should fire on a version bump.' );
		$this->assertSame( '0.0.1', $event['properties']['from_version'] );
	}

	/**
	 * No event fires when the stored version already matches the current one.
	 */
	public function test_plugin_updated_not_fired_when_unchanged() {
		update_option( 'spectra_blocks_version', SPECTRA_BLOCKS_VER );

		Spectra_Blocks_Loader::maybe_track_version_update();

		$this->assertNull(
			$this->find_event( \Spectra\Analytics\Events::flush_pending(), 'plugin_updated' ),
			'plugin_updated must not fire when the version is unchanged.'
		);
	}

	/**
	 * First observation (no stored version) stamps the version without an event.
	 */
	public function test_first_observation_stamps_without_event() {
		// set_up already deleted the option; this is the "never recorded" state.
		Spectra_Blocks_Loader::maybe_track_version_update();

		$this->assertSame( SPECTRA_BLOCKS_VER, get_option( 'spectra_blocks_version' ) );
		$this->assertNull(
			$this->find_event( \Spectra\Analytics\Events::flush_pending(), 'plugin_updated' ),
			'No update event on first observation (from-version unknown).'
		);
	}

	// -------------------------------------------------------------------------
	// settings_changed — tracked-toggle detector
	// -------------------------------------------------------------------------

	/**
	 * Changing a tracked feature toggle fires settings_changed with the option key.
	 */
	public function test_settings_changed_fires_on_tracked_toggle() {
		// Baseline add() fires add_option_ (not tracked); the change below fires update_option_.
		update_option( 'spectra_blocks_enable_gbs_extension', 'enabled' );
		delete_option( \Spectra\Analytics\Events::PENDING_OPTION );

		update_option( 'spectra_blocks_enable_gbs_extension', 'disabled' );

		$event = $this->find_event( \Spectra\Analytics\Events::flush_pending(), 'settings_changed' );
		$this->assertNotNull( $event, 'settings_changed should fire when a tracked toggle changes.' );
		$this->assertSame( 'spectra_blocks_enable_gbs_extension', $event['event_value'] );
	}

	/**
	 * Changing a NON-tracked option must not fire settings_changed.
	 */
	public function test_settings_changed_ignores_untracked_option() {
		update_option( 'spectra_blocks_some_untracked_option', 'a' );
		delete_option( \Spectra\Analytics\Events::PENDING_OPTION );

		update_option( 'spectra_blocks_some_untracked_option', 'b' );

		$this->assertNull(
			$this->find_event( \Spectra\Analytics\Events::flush_pending(), 'settings_changed' ),
			'Untracked option changes must not fire settings_changed.'
		);
	}
}
