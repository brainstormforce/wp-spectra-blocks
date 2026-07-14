/**
 * External dependencies.
 */
import { useBlockProps, useInnerBlocksProps, BlockContextProvider } from '@wordpress/block-editor';
import { getSettings as getDateSettings } from '@wordpress/date';
import { memo, useCallback, useEffect, useMemo, useState, useRef } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useCountdownStyles } from './use-countdown-styles';
import TEMPLATE from './template';
import { areTimesEqual, calculateRemainingTime, formatDateForPicker } from '@spectra-helpers/countdown';
import timerManager from './timer-manager';

/**
 * The Editor Block render.
 *
 * @since x.x.x
 * @param {Object} props The element props.
 * @return {Element} The rendered block.
 */
const Render = ( props ) => {
	const { attributes, setAttributes, clientId } = props;

	const { timezone } = getDateSettings();

	// Destructure relevant attributes with default values.
	const {
		timerType = 'date',
		endDateTime,
		showDays = true,
		showHours = true,
		showMinutes = true,
		showSeconds = true,
		ariaLiveType = 'off',
		showLabels = true,
		daysLabel,
		dayLabel,
		hoursLabel,
		hourLabel,
		minutesLabel,
		minuteLabel,
		secondsLabel,
		secondLabel,
		showSeparator = true,
		separatorType = ':',
		editorInnerBlocksPreview = false, // Determines whether the preview is live or expired.
		layout = {},
		style: attributeStyle = {},
		width,
		height,
		minWidth,
		minHeight,
		maxWidth,
		maxHeight,
		overflow,
		timerEndAction
	} = attributes;

	// Apply default values for labels if not set.
	const finalDaysLabel = daysLabel || __( 'Days', 'spectra-blocks' );
	const finalDayLabel = dayLabel || __( 'Day', 'spectra-blocks' );
	const finalHoursLabel = hoursLabel || __( 'Hours', 'spectra-blocks' );
	const finalHourLabel = hourLabel || __( 'Hour', 'spectra-blocks' );
	const finalMinutesLabel = minutesLabel || __( 'Minutes', 'spectra-blocks' );
	const finalMinuteLabel = minuteLabel || __( 'Minute', 'spectra-blocks' );
	const finalSecondsLabel = secondsLabel || __( 'Seconds', 'spectra-blocks' );
	const finalSecondLabel = secondLabel || __( 'Second', 'spectra-blocks' );

	// Get layout settings for conditional styling.
	const layoutType = layout?.type ?? 'flex';
	const blockGap = attributeStyle?.spacing?.blockGap ?? null;

	// Set a default countdown time if endDateTime is not already set.
	useEffect( () => {
		if ( endDateTime ) {return;}

		// Get the current actual time (UTC).
		const actualTime = new Date();

		// For setting the time in input fields as per the selected timezone offset in WordPress settings (local time).
		const displayTime = new Date();

		// Set the default end time to 7 days later (both for countdown calculations and for displaying in input fields).
		actualTime.setMilliseconds( actualTime.getMilliseconds() + 7 * 24 * 60 * 60 * 1000 );

		// For the display time, we adjust for the local and WP timezone offset.
		displayTime.setMilliseconds( displayTime.getMilliseconds() + 7 * 24 * 60 * 60 * 1000 );

		// Adjust display time to account for local timezone and WordPress site offset.
		displayTime.setMilliseconds(
			displayTime.getMilliseconds() +
				displayTime.getTimezoneOffset() * 60 * 1000 +
				timezone.offset * 60 * 60 * 1000
		);

		// Save the calculated times to attributes.
		setAttributes( {
			endDateTime: actualTime.toISOString(), // Store UTC time for countdown calculation.
			displayEndDateTime: formatDateForPicker( displayTime ), // Store formatted time string for display in input field.
		} );
	}, [ endDateTime ] );

	/**
	 * Dependencies to trigger countdown time calculation.
	 */
	const baseDeps = {
		timerType,
		endDateTime,
		showDays,
		showHours,
		showMinutes,
		showSeconds,
	};

	/**
	 * Filterable dependency list for the calculateTime hook.
	 *
	 * @type {Object} baseDeps The default dependency object.
	 * @type {Object} attributes The current block attributes.
	 * @return {Object} Filtered dependency object used in useCallback.
	 */
	const filteredDeps = applyFilters( 'spectra.countdown.calculateTime.dependencies', baseDeps, attributes );

	// Convert the filtered dependency object into an array.
	const depArray = Object.values( filteredDeps );

	// Memoized time calculation with filter.
	const calculateTime = useCallback( () => {
		const calculatedTime = calculateRemainingTime( endDateTime, showDays, showHours, showMinutes, showSeconds );

		/**
		 * Filter the calculated countdown time.
		 *
		 * Allows modification of the countdown time object in the Pro version or other extensions.
		 * The filter receives the calculated time object, block attributes, and the calculateRemainingTime function.
		 *
		 * @param {Object}   calculatedTime         The calculated time object { days, hours, minutes, seconds, isExpired }.
		 * @param {Object}   attributes             The block attributes.
		 * @param {Function} calculateRemainingTime The function to calculate remaining time.
		 * @return {Object} The modified time object.
		 */
		return applyFilters( 'spectra.countdown.calculateTime', calculatedTime, attributes, calculateRemainingTime );
	}, depArray );

	// Local state for countdown values.
	const [ time, setTime ] = useState( calculateTime );

	// Use a ref to store the block's unique ID for timer subscription
	const blockIdRef = useRef( null );
	if ( ! blockIdRef.current ) {
		blockIdRef.current = `countdown-${ Math.random().toString( 36 ).substr( 2, 9 ) }`;
	}

	// Memoize timer callback to prevent unnecessary re-subscriptions
	const timerCallback = useCallback( () => {
		setTime( ( prev ) => {
			const newTime = calculateTime();
			// Only update if time values actually changed.
			return areTimesEqual( prev, newTime ) ? prev : newTime;
		} );
	}, [ calculateTime ] );

	// Setup subscription to global timer manager
	useEffect( () => {
		if ( ! endDateTime ) {return;}

		// Subscribe to the global timer manager
		const unsubscribe = timerManager.subscribe( blockIdRef.current, timerCallback, {
			showDays,
			showHours,
			showMinutes,
			showSeconds,
		} );

		// Cleanup subscription on unmount or dependency change
		return unsubscribe;
	}, [ endDateTime, timerCallback, showDays, showHours, showMinutes, showSeconds ] );

	// Split context into static and dynamic parts for better performance
	const staticContext = useMemo(
		() => ( {
			// Visibility toggles.
			showDays,
			showHours,
			showMinutes,
			showSeconds,
			showLabels,

			// Labels
			daysLabel: finalDaysLabel,
			dayLabel: finalDayLabel,
			hoursLabel: finalHoursLabel,
			hourLabel: finalHourLabel,
			minutesLabel: finalMinutesLabel,
			minuteLabel: finalMinuteLabel,
			secondsLabel: finalSecondsLabel,
			secondLabel: finalSecondLabel,

			// Separator configuration.
			showSeparator,
			separatorType,

			// Accessibility and editor settings.
			ariaLiveType,
			editorInnerBlocksPreview,
		} ),
		[
			showDays,
			showHours,
			showMinutes,
			showSeconds,
			showLabels,
			finalDaysLabel,
			finalDayLabel,
			finalHoursLabel,
			finalHourLabel,
			finalMinutesLabel,
			finalMinuteLabel,
			finalSecondsLabel,
			finalSecondLabel,
			showSeparator,
			separatorType,
			ariaLiveType,
			editorInnerBlocksPreview,
		]
	);

	// Prepare context for inner blocks (child components).
	const blockContext = useMemo(
		() => ( {
			countdown: {
				// Merge in static context first
				...staticContext,

				// Current time values.
				days: time.days,
				hours: time.hours,
				minutes: time.minutes,
				seconds: time.seconds,
				isExpired: time.isExpired,

				// Smart label selection (singular/plural) - these override the static context labels.
				daysLabel: time.days === 1 && staticContext.dayLabel ? staticContext.dayLabel : staticContext.daysLabel,
				hoursLabel:
					time.hours === 1 && staticContext.hourLabel ? staticContext.hourLabel : staticContext.hoursLabel,
				minutesLabel:
					time.minutes === 1 && staticContext.minuteLabel
						? staticContext.minuteLabel
						: staticContext.minutesLabel,
				secondsLabel:
					time.seconds === 1 && staticContext.secondLabel
						? staticContext.secondLabel
						: staticContext.secondsLabel,
			},
		} ),
		[ time, staticContext ]
	);

	// Configuration for the useSpectraStyles hook.
	const config = [
		{ key: 'textColor' },
		{ key: 'textColorHover' },
		{ key: 'backgroundColor' },
		{ key: 'backgroundColorHover' },
		{ key: 'backgroundGradient' },
		{ key: 'backgroundGradientHover' },
	];

	// Add conditional class for flow(default)/constrained layouts when blockGap is not set.
	const additionalClasses = [];
	if ( ( 'default' === layoutType || 'constrained' === layoutType ) && blockGap === null ) {
		additionalClasses.push( 'countdown-is-layout-flow-constrained' );
	}

	// Generate styles and class names using optimized hook.
	const { style, classNames } = useCountdownStyles( attributes, config, additionalClasses );

	// Use the block props with dimension styles applied directly like container block.
	// Note: aria-live is added to individual countdown number elements (via context in countdown-child-number/controller.php)
	// following v2's pattern where each time unit announces independently when it changes.
	const blockProps = useBlockProps( {
		// eslint-disable-next-line quote-props
		className: spectraClassNames( classNames ),
		// eslint-disable-next-line quote-props
		style: {
			width,
			height,
			minWidth,
			minHeight,
			maxWidth,
			maxHeight,
			position: 'relative',
			overflow,
			...style,
		},
		'aria-label': ariaLiveType !== 'off' ? __( 'Countdown timer', 'spectra-blocks' ) : undefined,
	} );
	// Manage addition/removal of Expiry Wrapper based on timerEndAction.
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );
	const innerBlocks = useSelect( ( select ) =>
		select( 'core/block-editor' ).getBlock( clientId ).innerBlocks
	);

	useEffect( () => {
		const hasExpiryWrapper = innerBlocks.some(
			( block ) => block.name === 'spectra/countdown-child-expiry-wrapper'
		);

		if ( timerEndAction === 'replace' && !hasExpiryWrapper ) {
			// Create the new block.
			const newBlock = createBlock( 'spectra/countdown-child-expiry-wrapper', {
				lock: { move: true, remove: true }
			} );

			// Append it to existing blocks.
			replaceInnerBlocks( clientId, [ ...innerBlocks, newBlock ] );
		}
		else if ( timerEndAction !== 'replace' && hasExpiryWrapper ) {
			// Logic to remove it if the setting is turned off.
			const filteredBlocks = innerBlocks.filter(
				( block ) => block.name !== 'spectra/countdown-child-expiry-wrapper'
			);
			replaceInnerBlocks( clientId, filteredBlocks );
		}
	}, [ timerEndAction ] );

	// Use the inner blocks props.
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		renderAppender: false,
	} );

	// Ensure at least one unit is shown.
	const isValid = showDays || showHours || showMinutes || showSeconds;
	if ( ! isValid ) {
		return (
			<div { ...blockProps }>
				{ __(
					'Please enable at least one time unit (Days, Hours, Minutes, or Seconds).',
					'spectra-blocks'
				) }
			</div>
		);
	}

	// Main render with context provider for inner blocks.
	return (
		<BlockContextProvider value={ blockContext }>
			<div { ...innerBlocksProps } />
		</BlockContextProvider>
	);
};

export default memo( Render );
