/**
 * Milliseconds for each time unit.
 *
 * @since x.x.x
 */
const MILLISECONDS = {
	day: 86400000, // 1000*60*60*24
	hour: 3600000, // 1000*60*60
	minute: 60000, // 1000*60
	second: 1000,
};

/**
 * Format a Date object to a string in the format YYYY-MM-DD HH:MM:SS.
 * This format is what DateTimePicker expects.
 *
 * @since x.x.x
 *
 * @param {Date} dateObj The Date object to format.
 * @return {string} Formatted date string.
 */
export const formatDateForPicker = ( dateObj ) => {
	if ( ! dateObj || ! ( dateObj instanceof Date ) ) {
		return '';
	}

	const year = dateObj.getFullYear();
	const month = String( dateObj.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( dateObj.getDate() ).padStart( 2, '0' );
	const hours = String( dateObj.getHours() ).padStart( 2, '0' );
	const minutes = String( dateObj.getMinutes() ).padStart( 2, '0' );
	const seconds = String( dateObj.getSeconds() ).padStart( 2, '0' );

	return `${ year }-${ month }-${ day } ${ hours }:${ minutes }:${ seconds }`;
};

/**
 * Return an object with time units set to 0 and isExpired to true.
 *
 * @since x.x.x
 *
 * @param {boolean} showDays Show days.
 * @param {boolean} showHours Show hours.
 * @param {boolean} showMinutes Show minutes.
 * @param {boolean} showSeconds Show seconds.
 * @return {Object} Time units with zeros and isExpired flag.
 */
const zeroTime = ( showDays, showHours, showMinutes, showSeconds ) => ( {
	...( showDays && { days: 0 } ),
	...( showHours && { hours: 0 } ),
	...( showMinutes && { minutes: 0 } ),
	...( showSeconds && { seconds: 0 } ),
	isExpired: true,
} );

/**
 * Calculate time units from milliseconds.
 *
 * @since x.x.x
 *
 * @param {number} ms Milliseconds remaining in countdown
 * @param {boolean} showDays Whether to calculate days
 * @param {boolean} showHours Whether to calculate hours
 * @param {boolean} showMinutes Whether to calculate minutes
 * @param {boolean} showSeconds Whether to calculate seconds
 * @return {Object} Object containing:
 *                  - Calculated time units (only those enabled)
 *                  - isExpired flag (always false here - set by parent function)
 */
const calculateTimeUnits = ( ms, showDays, showHours, showMinutes, showSeconds ) => {
	// Initialize empty time object.
	const time = {};

	// Working copy of milliseconds to avoid mutating input.
	let remaining = ms;

	// Calculate days if enabled (largest unit first).
	if ( showDays ) {
		time.days = Math.floor( remaining / MILLISECONDS.day );
		remaining %= MILLISECONDS.day;
	}

	// Calculate hours if enabled.
	if ( showHours ) {
		time.hours = Math.floor( remaining / MILLISECONDS.hour );
		remaining %= MILLISECONDS.hour;
	}

	// Calculate minutes if enabled.
	if ( showMinutes ) {
		time.minutes = Math.floor( remaining / MILLISECONDS.minute );
		remaining %= MILLISECONDS.minute;
	}

	// Calculate seconds if enabled (no modulus needed as last unit).
	if ( showSeconds ) {
		time.seconds = Math.floor( remaining / MILLISECONDS.second );
	}

	// Return all calculated units plus isExpired flag
	// Note: isExpired is set false here - parent function handles expiration logic.
	return { ...time, isExpired: false };
};

/**
 * Calculate remaining time from current time to endDateTime.
 *
 * @since x.x.x
 *
 * @param {string} endDateTime ISO 8601 date string.
 * @param {boolean} showDays Show days.
 * @param {boolean} showHours Show hours.
 * @param {boolean} showMinutes Show minutes.
 * 	@param {boolean} showSeconds Show seconds.
 * @return {Object} Remaining time parts and expiration status.
 */
export const calculateRemainingTime = (
	endDateTime,
	showDays = true,
	showHours = true,
	showMinutes = true,
	showSeconds = true
) => {
	// 1. Parse end date strictly in UTC.
	const endDate = new Date( endDateTime );
	if ( isNaN( endDate.getTime() ) ) {
		return zeroTime( showDays, showHours, showMinutes, showSeconds );
	}

	// 2. Get current time in UTC milliseconds.
	const nowUTC = Date.now();

	// 3. Calculate the raw time difference in UTC.
	const timeLeft = endDate.getTime() - nowUTC;

	// 4. Handle expiration.
	if ( timeLeft <= 0 ) {
		return zeroTime( showDays, showHours, showMinutes, showSeconds );
	}

	// 5. Calculate time components.
	return calculateTimeUnits( timeLeft, showDays, showHours, showMinutes, showSeconds );
};

/**
 * Get the update interval based on the lowest enabled time unit.
 *
 * @since x.x.x
 *
 * @param {boolean} showDays Show days.
 * @param {boolean} showHours Show hours.
 * @param {boolean} showMinutes Show minutes.
 * @param {boolean} showSeconds Show seconds.
 * @return {number} Interval in milliseconds.
 */
export const getUpdateInterval = ( showDays, showHours, showMinutes, showSeconds ) => {
	if ( showSeconds ) return 1000; // Every second.
	if ( showMinutes ) return 60000; // Every minute.
	if ( showHours ) return 3600000; // Every hour.
	return 86400000; // Every day (default).
};

/**
 * Check if two time objects are equal.
 *
 * @since x.x.x
 *
 * @param {Object} a First time object.
 * @param {Object} b Second time object.
 * @return {boolean} True if equal.
 */
export const areTimesEqual = ( a, b ) => {
	return (
		a.days === b.days &&
		a.hours === b.hours &&
		a.minutes === b.minutes &&
		a.seconds === b.seconds &&
		a.isExpired === b.isExpired
	);
};
