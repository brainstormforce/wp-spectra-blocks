/**
 * External dependencies.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import { calculateRemainingTime } from '@spectra-helpers/countdown';
import frontendTimerManager from './frontend-timer-manager';

/**
 * Creates an object with days, hours, minutes, seconds, and labels based on the `time` object and `labels` object.
 *
 * @since x.x.x
 *
 * @param {Object} time Time object with days, hours, minutes, seconds, and isExpired properties.
 * @param {Object} labels Labels object with dayLabel, daysLabel, hourLabel, hoursLabel, minuteLabel, minutesLabel, secondLabel, and secondsLabel properties.
 * @return {Object} Countdown object with days, hours, minutes, seconds, isExpired, daysLabel, hoursLabel, minutesLabel, and secondsLabel properties.
 */
const createCountdownObject = ( time, labels ) => ( {
	days: time.days,
	hours: time.hours,
	minutes: time.minutes,
	seconds: time.seconds,
	isExpired: time.isExpired,
	daysLabel: time.days === 1 ? labels.dayLabel : labels.daysLabel,
	hoursLabel: time.hours === 1 ? labels.hourLabel : labels.hoursLabel,
	minutesLabel: time.minutes === 1 ? labels.minuteLabel : labels.minutesLabel,
	secondsLabel: time.seconds === 1 ? labels.secondLabel : labels.secondsLabel,
} );

/**
 * Updates the DOM of a countdown block to reflect the current state of the countdown.
 *
 * @since x.x.x
 *
 * @param {Element} ref - The root element of the countdown block.
 * @param {Object} countdown - The object with the current state of the countdown, as returned by createCountdownObject.
 * @return {void}
 */
const updateCountdownDOM = ( ref, countdown ) => {
	const timeUnits = [
		{ type: 'day', value: 'days', label: 'daysLabel' },
		{ type: 'hour', value: 'hours', label: 'hoursLabel' },
		{ type: 'minute', value: 'minutes', label: 'minutesLabel' },
		{ type: 'second', value: 'seconds', label: 'secondsLabel' },
	];

	timeUnits.forEach( ( unit ) => {
		const unitSelector = `.wp-block-spectra-countdown-child-${ unit.type }`;
		const unitElement = ref.querySelector( unitSelector );
		if ( ! unitElement ) return;

		const finalNumber = countdown[ unit.value ] || 0;
		const formattedNumber = String( finalNumber ).padStart( 2, '0' );

		const numberElement = unitElement.querySelector( '.wp-block-spectra-countdown-child-number' );
		// Only update the DOM if the number has changed.
		if ( numberElement && numberElement?.textContent !== formattedNumber ) {
			numberElement.textContent = formattedNumber;
		}

		const label = countdown[ unit.label ];
		const labelElement = unitElement.querySelector( '.wp-block-spectra-countdown-child-label' );
		// Only update the DOM if the label has changed.
		if ( labelElement && labelElement?.textContent !== label ) {
			labelElement.textContent = label;
		}
	} );
};

/**
 * Create the Countdown store.
 *
 * @since x.x.x
 */
store( 'spectra/countdown', {
	callbacks: {
		/**
		 * Initializes the countdown block.
		 *
		 * Sets up an interval that recalculates and updates the countdown block's DOM
		 * every `intervalDuration` milliseconds until the end date time is reached.
		 *
		 * @return {void}
		 */
		initialize: () => {
			const context = getContext();
			const { endDateTime, showDays, showHours, showMinutes, showSeconds, labels, timerType = 'date' } = context;

			// Get the element reference.
			const { ref } = getElement();

			// Fire the initialized event.
			const initializedEvent = new CustomEvent( 'spectra:countdown:initialized', {
				detail: {
					context,
				},
				bubbles: true,
			} );
			ref.dispatchEvent( initializedEvent );

			if ( timerType !== 'date' ) {
				// If the timer type is not 'date', we don't need to set up a countdown.
				return;
			}

			// Validate endDateTime.
			if ( ! endDateTime || isNaN( new Date( endDateTime ).getTime() ) ) {
				return;
			}

			// Create unique ID for this countdown instance
			const countdownId = `countdown-${ ref.getAttribute( 'data-wp-key' ) || Math.random().toString( 36 ).substr( 2, 9 ) }`;
			
			// Clear any existing registration
			if ( context.unregister ) {
				context.unregister();
			}

			// Initial time calculation
			const initialTime = calculateRemainingTime( endDateTime, showDays, showHours, showMinutes, showSeconds );
			const initialCountdown = createCountdownObject( initialTime, labels );
			context.countdown = initialCountdown;
			updateCountdownDOM( ref, initialCountdown );

			// Register with global timer manager
			context.unregister = frontendTimerManager.register( countdownId, {
				element: ref,
				endDateTime,
				showDays,
				showHours,
				showMinutes,
				showSeconds,
				labels,
				onExpired: () => {
					// Fire the expired event
					const expiredEvent = new CustomEvent( 'spectra:countdown:expired', {
						detail: {
							context,
						},
						bubbles: true,
					} );
					ref.dispatchEvent( expiredEvent );
				}
			} );
			
			// Clean up on element removal
			const observer = new MutationObserver( ( mutations ) => {
				for ( const mutation of mutations ) {
					for ( const node of mutation.removedNodes ) {
						if ( node === ref || node.contains( ref ) ) {
							if ( context.unregister ) {
								context.unregister();
							}
							observer.disconnect();
							return;
						}
					}
				}
			} );
			observer.observe( document.body, { childList: true, subtree: true } );
		},
	},
} );
