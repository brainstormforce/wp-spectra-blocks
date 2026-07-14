/**
 * Frontend Global Timer Manager for Countdown Blocks.
 *
 * This singleton manages all countdown timers globally on the frontend to improve
 * performance when multiple countdown blocks are present on a page.
 *
 * @since x.x.x
 */

/**
 * Internal dependencies.
 */
import { getUpdateInterval } from '@spectra-helpers/countdown';

/**
 * FrontendCountdownTimerManager class.
 *
 * This class manages all countdown timers on the frontend, allowing for efficient updates
 * and rendering of multiple countdown blocks without performance issues.
 *
 * @since x.x.x
 */
class FrontendCountdownTimerManager {
	constructor() {
		this.countdowns = new Map();
		this.intervalId = null;
		this.intervalDuration = 1000; // Default to 1 second.
		this.rafId = null;
	}

	/**
	 * Register a countdown instance.
	 *
	 * @since x.x.x
	 * @param {string} id     - Unique identifier for the countdown instance.
	 * @param {Object} config - Configuration for the countdown.
	 * @return {Function} Unregister function.
	 */
	register( id, config ) {
		const { element, endDateTime, showDays, showHours, showMinutes, showSeconds, labels, onExpired } = config;

		// Store the countdown data.
		this.countdowns.set( id, {
			element,
			endDateTime,
			showDays,
			showHours,
			showMinutes,
			showSeconds,
			labels,
			onExpired,
			lastValues: null,
		} );

		// Update interval duration based on all countdowns.
		this.updateIntervalDuration();

		// Start the timer if this is the first countdown.
		if ( this.countdowns.size === 1 ) {
			this.start();
		}

		// Return unregister function.
		return () => this.unregister( id );
	}

	/**
	 * Unregister a countdown instance.
	 *
	 * @since x.x.x
	 * @param {string} id - Unique identifier for the countdown instance.
	 */
	unregister( id ) {
		this.countdowns.delete( id );

		// Stop the timer if no more countdowns.
		if ( this.countdowns.size === 0 ) {
			this.stop();
		} else {
			// Update interval duration based on remaining countdowns.
			this.updateIntervalDuration();
		}
	}

	/**
	 * Update the interval duration based on all countdowns' needs.
	 *
	 * @since x.x.x
	 */
	updateIntervalDuration() {
		// Find the minimum required interval among all countdowns.
		let minInterval = 86400000; // Start with day interval (highest).

		for ( const [ , countdown ] of this.countdowns ) {
			const interval = getUpdateInterval(
				countdown.showDays,
				countdown.showHours,
				countdown.showMinutes,
				countdown.showSeconds
			);

			if ( interval < minInterval ) {
				minInterval = interval;
			}
		}

		// Ensure minimum 1-second updates for responsive frontend experience
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
		if ( this.intervalId ) {return;}

		// Execute immediately for all countdowns.
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
		if ( this.rafId ) {
			cancelAnimationFrame( this.rafId );
			this.rafId = null;
		}
	}

	/**
	 * Execute updates for all countdowns.
	 *
	 * @since x.x.x
	 */
	tick() {
		// Cancel any pending RAF.
		if ( this.rafId ) {
			cancelAnimationFrame( this.rafId );
		}

		// Use requestAnimationFrame to batch DOM updates.
		this.rafId = requestAnimationFrame( () => {
			const now = new Date().getTime();
			const expiredCountdowns = [];

			for ( const [ id, countdown ] of this.countdowns ) {
				try {
					const endTime = new Date( countdown.endDateTime ).getTime();
					const distance = endTime - now;

					if ( distance < 0 ) {
						// Countdown expired.
						expiredCountdowns.push( id );
						this.updateCountdownDOM( countdown, { days: 0, hours: 0, minutes: 0, seconds: 0 } );

						// Call expired callback if provided.
						if ( countdown.onExpired ) {
							countdown.onExpired();
						}
					} else {
						// Calculate time values.
						const time = this.calculateTime( distance, countdown );

						// Only update DOM if values changed.
						if ( this.hasValuesChanged( countdown.lastValues, time ) ) {
							this.updateCountdownDOM( countdown, time );
							countdown.lastValues = time;
						}
					}
				} catch ( error ) {
					// Silently handle errors to prevent breaking other timers.
				}
			}

			// Remove expired countdowns.
			expiredCountdowns.forEach( ( id ) => this.unregister( id ) );
		} );
	}

	/**
	 * Calculate time values from distance.
	 *
	 * @since x.x.x
	 * @param {number} distance - Time distance in milliseconds.
	 * @param {Object} config   - Countdown configuration.
	 * @return {Object} Time values.
	 */
	calculateTime( distance, config ) {
		const { showDays, showHours, showMinutes, showSeconds } = config;

		let remainingTime = distance;
		const time = { days: 0, hours: 0, minutes: 0, seconds: 0 };

		if ( showDays ) {
			time.days = Math.floor( remainingTime / ( 1000 * 60 * 60 * 24 ) );
			remainingTime %= 1000 * 60 * 60 * 24;
		}

		if ( showHours ) {
			time.hours = Math.floor( remainingTime / ( 1000 * 60 * 60 ) );
			remainingTime %= 1000 * 60 * 60;
		}

		if ( showMinutes ) {
			time.minutes = Math.floor( remainingTime / ( 1000 * 60 ) );
			remainingTime %= 1000 * 60;
		}

		if ( showSeconds ) {
			time.seconds = Math.floor( remainingTime / 1000 );
		}

		return time;
	}

	/**
	 * Check if time values have changed.
	 *
	 * @since x.x.x
	 * @param {Object} oldValues - Previous time values.
	 * @param {Object} newValues - New time values.
	 * @return {boolean} Whether values changed.
	 */
	hasValuesChanged( oldValues, newValues ) {
		if ( ! oldValues ) {return true;}

		return (
			oldValues.days !== newValues.days ||
			oldValues.hours !== newValues.hours ||
			oldValues.minutes !== newValues.minutes ||
			oldValues.seconds !== newValues.seconds
		);
	}

	/**
	 * Update countdown DOM efficiently.
	 *
	 * @since x.x.x
	 * @param {Object} countdown - Countdown configuration.
	 * @param {Object} time      - Time values.
	 */
	updateCountdownDOM( countdown, time ) {
		const { element, labels } = countdown;

		const timeUnits = [
			{ type: 'day', value: time.days, label: time.days === 1 ? labels.dayLabel : labels.daysLabel },
			{ type: 'hour', value: time.hours, label: time.hours === 1 ? labels.hourLabel : labels.hoursLabel },
			{
				type: 'minute',
				value: time.minutes,
				label: time.minutes === 1 ? labels.minuteLabel : labels.minutesLabel,
			},
			{
				type: 'second',
				value: time.seconds,
				label: time.seconds === 1 ? labels.secondLabel : labels.secondsLabel,
			},
		];

		timeUnits.forEach( ( unit ) => {
			const unitSelector = `.wp-block-spectra-countdown-child-${ unit.type }`;
			const unitElement = element.querySelector( unitSelector );
			if ( ! unitElement ) {return;}

			const formattedNumber = String( unit.value ).padStart( 2, '0' );

			const numberElement = unitElement.querySelector( '.wp-block-spectra-countdown-child-number' );
			if ( numberElement && numberElement.textContent !== formattedNumber ) {
				numberElement.textContent = formattedNumber;
			}

			const labelElement = unitElement.querySelector( '.wp-block-spectra-countdown-child-label' );
			if ( labelElement && labelElement.textContent !== unit.label ) {
				labelElement.textContent = unit.label;
			}
		} );
	}
}

// Create and export singleton instance.
const frontendTimerManager = new FrontendCountdownTimerManager();

// Expose globally for pro plugin usage.
if ( typeof window !== 'undefined' ) {
	window.spectraFrontendCountdownTimerManager = frontendTimerManager;
}

export default frontendTimerManager;
