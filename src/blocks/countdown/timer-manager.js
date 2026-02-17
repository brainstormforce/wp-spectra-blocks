/**
 * Global Timer Manager for Countdown Blocks.
 *
 * This singleton manages all countdown timers globally to improve performance
 * when multiple countdown blocks are present on a page.
 *
 * @since x.x.x
 */

/**
 * Internal dependencies.
 */
import { getUpdateInterval } from '@spectra-helpers/countdown';

/**
 * CountdownTimerManager class.
 *
 * This class manages all countdown timers, allowing for efficient updates
 * and rendering of multiple countdown blocks without performance issues.
 *
 * @since x.x.x
 */
class CountdownTimerManager {
	constructor() {
		this.subscribers = new Map();
		this.intervalId = null;
		this.intervalDuration = 1000; // Default to 1 second.
	}

	/**
	 * Subscribe a countdown instance to the global timer.
	 *
	 * @since x.x.x
	 * @param {string} id - Unique identifier for the countdown instance.
	 * @param {Function} callback - Function to call on each tick.
	 * @param {Object} config - Configuration for the countdown.
	 * @return {Function} Unsubscribe function.
	 */
	subscribe( id, callback, config = {} ) {
		// Check if subscriber already exists to avoid unnecessary updates
		const existingSubscriber = this.subscribers.get( id );
		const hasChanges = ! existingSubscriber || 
			existingSubscriber.callback !== callback ||
			JSON.stringify( existingSubscriber.config ) !== JSON.stringify( config );

		if ( ! hasChanges ) {
			return () => this.unsubscribe( id );
		}

		// Store the subscriber
		this.subscribers.set( id, { callback, config } );

		// Update interval duration based on all subscribers
		this.updateIntervalDuration();

		// Start the timer if this is the first subscriber
		if ( this.subscribers.size === 1 ) {
			this.start();
		}

		// Return unsubscribe function
		return () => this.unsubscribe( id );
	}

	/**
	 * Unsubscribe a countdown instance from the global timer.
	 *
	 * @since x.x.x
	 * @param {string} id - Unique identifier for the countdown instance.
	 */
	unsubscribe( id ) {
		this.subscribers.delete( id );

		// Stop the timer if no more subscribers.
		if ( this.subscribers.size === 0 ) {
			this.stop();
		} else {
			// Update interval duration based on remaining subscribers.
			this.updateIntervalDuration();
		}
	}

	/**
	 * Update the interval duration based on all subscribers' needs.
	 *
	 * @since x.x.x
	 */
	updateIntervalDuration() {
		// Find the minimum required interval among all subscribers.
		let minInterval = 86400000; // Start with day interval (highest).

		for ( const [ , { config } ] of this.subscribers ) {
			const interval = getUpdateInterval(
				config.showDays,
				config.showHours,
				config.showMinutes,
				config.showSeconds
			);

			if ( interval < minInterval ) {
				minInterval = interval;
			}
		}

		// Ensure minimum 1-second updates for responsive editor experience
		// This provides better UX while still optimizing when possible
		minInterval = Math.min( minInterval, 1000 );

		// If duration changed and timer is running, restart with new duration.
		if ( minInterval !== this.intervalDuration && this.intervalId ) {
			this.intervalDuration = minInterval;
			this.stop();
			this.start();
		} else {
			this.intervalDuration = minInterval;
		}
	}

	/**
	 * Start the global timer.
	 *
	 * @since x.x.x
	 */
	start() {
		if ( this.intervalId ) return;

		// Execute immediately for all subscribers.
		this.tick();

		// Set up the interval.
		this.intervalId = setInterval( () => this.tick(), this.intervalDuration );
	}

	/**
	 * Stop the global timer.
	 *
	 * @since x.x.x
	 */
	stop() {
		if ( this.intervalId ) {
			clearInterval( this.intervalId );
			this.intervalId = null;
		}
	}

	/**
	 * Execute callbacks for all subscribers.
	 *
	 * @since x.x.x
	 */
	tick() {
		// Early exit if no subscribers
		if ( this.subscribers.size === 0 ) return;

		// Use requestAnimationFrame to batch DOM updates.
		requestAnimationFrame( () => {
			// Create a batched array of callbacks to avoid iterator overhead
			const callbacks = [];
			for ( const [ , { callback } ] of this.subscribers ) {
				callbacks.push( callback );
			}

			// Execute all callbacks with minimal overhead
			for ( let i = 0; i < callbacks.length; i++ ) {
				try {
					callbacks[ i ]();
				} catch ( error ) {
					// Silently handle errors to prevent breaking other timers.
				}
			}
		} );
	}
}

// Create and export singleton instance.
const timerManager = new CountdownTimerManager();

export default timerManager;
