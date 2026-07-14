/**
 * External dependencies.
 */
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalGrid as Grid,
	DateTimePicker,
	ToggleControl,
	TextControl,
	BaseControl,
} from '@wordpress/components';
import { getSettings as getDateSettings } from '@wordpress/date';
import { memo, useCallback, useMemo } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { formatDateForPicker } from '@spectra-helpers/countdown';
import InspectorColor from '@spectra-components/inspector-color';

/**
 * Element Sub-settings: General settings for the countdown timer.
 * Contains timer configuration including end date/time, display units, and ARIA settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const GeneralSettings = memo( ( props ) => {
	// Destructure the required props.
	const { clientId, setAttributes, attributes } = props;
	const {
		timerType,
		endDateTime,
		displayEndDateTime,
		showDays,
		showHours,
		showMinutes,
		showSeconds,
		ariaLiveType = 'off',
		overflow = 'visible',
	} = attributes;

	// Memoize timer settings to prevent unnecessary recalculations
	const timerSettings = useMemo( () => applyFilters( 'spectra.countdown.timer-settings', null, props ), [
		props.clientId,
		props.attributes,
	] );

	// Memoize reset attributes to prevent unnecessary object creation
	const resetAllAttributes = useMemo( () => applyFilters( 'spectra.countdown.reset-all-attributes', {} ), [] );

	// Validate that at least one unit is enabled.
	const validateUnits = useCallback(
		( newAttributes ) => {
			const {
				showDays: newShowDays,
				showHours: newShowHours,
				showMinutes: newShowMinutes,
				showSeconds: newShowSeconds,
			} = { ...attributes, ...newAttributes };

			if ( ! newShowDays && ! newShowHours && ! newShowMinutes && ! newShowSeconds ) {
				return { ...newAttributes, showDays: true };
			}

			return newAttributes;
		},
		[ attributes ]
	);

	// Memoize timezone settings to prevent unnecessary recalculations
	const timezone = useMemo( () => getDateSettings().timezone, [] );

	/**
	 * Updates the block's endDateTime attribute based on the new date input.
	 *
	 * Converts the provided date to a UTC ISO 8601 string and updates the endDateTime attribute.
	 *
	 * @param {string|Date} newDate The new date value to set, either as a string or Date object.
	 */
	const handleDateTimeChange = useCallback(
		( newDate ) => {
			// Ensure we have a valid date.
			if ( ! newDate ) {return;}

			// Format the date as a string if it's a Date object.
			const formattedDate = typeof newDate === 'string' ? newDate : formatDateForPicker( newDate );

			// Ensure the selected value is treated as UTC.
			const UTCValue = newDate + 'Z'; // Append 'Z' to indicate it's in UTC format.
			const d = new Date( UTCValue );

			// Adjust for WordPress site timezone offset by removing it from the date.
			d.setMilliseconds( d.getMilliseconds() - timezone.offset * 60 * 60 * 1000 );

			// Set the attributes: Store both UTC time and the local time for display.
			setAttributes( {
				endDateTime: d.toISOString(), // Store the adjusted UTC time.
				displayEndDateTime: formattedDate, // Store formatted time string for display in input field.
			} );
		},
		[ setAttributes, timezone ]
	);

	return (
		<InspectorControls group="settings">
			{ /*
			 * Main General Settings Panel
			 * Contains all fundamental countdown timer settings including:
			 * - End date/time selection
			 * - Time unit visibility toggles
			 * - ARIA live region settings
			 * Resets all general settings to their defaults when "Reset All" is clicked
			 */ }
			<ToolsPanel
				label={ __( 'General', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						timerType: 'date',
						endDateTime: undefined,
						displayEndDateTime: undefined,
						showDays: true,
						showHours: true,
						showMinutes: true,
						showSeconds: true,
						ariaLiveType: 'off',
						...resetAllAttributes,
						overflow: 'visible',
					} );
				} }
				panelId={ clientId }
			>
				{ /* Render timer settings from Pro version. */ }
				{ timerSettings }

				{ 'date' === timerType && (
					/*
					 * Timer End Date & Time Control
					 * Allows setting a future date/time for the countdown expiration
					 * Shows reset option only when value differs from default
					 */
					<ToolsPanelItem
						hasValue={ () => !! endDateTime }
						label={ __( 'Timer End Date & Time', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { endDateTime: undefined } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<BaseControl
							__nextHasNoMarginBottom
							id="wp-block-spectra-countdown-end-date-time"
							label={ __( 'End Date & Time', 'spectra-blocks' ) }
							help={ __(
								'Select a future date and time for the countdown.',
								'spectra-blocks'
							) }
						>
							<DateTimePicker
								currentDate={ displayEndDateTime ? new Date( displayEndDateTime ) : new Date() }
								onChange={ handleDateTimeChange }
								is12Hour={ true }
								__nextRemoveHelpButton
								__nextRemoveResetButton
							/>
						</BaseControl>
					</ToolsPanelItem>
				) }
				{ /*
				 * Show Days Toggle
				 * Controls visibility of days in countdown display
				 * Automatically ensures at least one time unit remains visible
				 */ }
				<ToolsPanelItem
					hasValue={ () => ! showDays }
					label={ __( 'Show Days', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( validateUnits( { showDays: true } ) ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleControl
						label={ __( 'Show Days', 'spectra-blocks' ) }
						checked={ showDays }
						onChange={ ( value ) => setAttributes( validateUnits( { showDays: value } ) ) }
					/>
				</ToolsPanelItem>
				{ /*
				 * Show Hours Toggle
				 * Controls visibility of hours in countdown display
				 * Automatically ensures at least one time unit remains visible
				 */ }
				<ToolsPanelItem
					hasValue={ () => ! showHours }
					label={ __( 'Show Hours', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( validateUnits( { showHours: true } ) ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleControl
						label={ __( 'Show Hours', 'spectra-blocks' ) }
						checked={ showHours }
						onChange={ ( value ) => setAttributes( validateUnits( { showHours: value } ) ) }
					/>
				</ToolsPanelItem>

				{ /*
				 * Show Minutes Toggle
				 * Controls visibility of minutes in countdown display
				 * Automatically ensures at least one time unit remains visible
				 */ }
				<ToolsPanelItem
					hasValue={ () => ! showMinutes }
					label={ __( 'Show Minutes', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( validateUnits( { showMinutes: true } ) ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleControl
						label={ __( 'Show Minutes', 'spectra-blocks' ) }
						checked={ showMinutes }
						onChange={ ( value ) => setAttributes( validateUnits( { showMinutes: value } ) ) }
					/>
				</ToolsPanelItem>

				{ /*
				 * Show Seconds Toggle
				 * Controls visibility of seconds in countdown display
				 * Automatically ensures at least one time unit remains visible
				 */ }
				<ToolsPanelItem
					hasValue={ () => ! showSeconds }
					label={ __( 'Show Seconds', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( validateUnits( { showSeconds: true } ) ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleControl
						label={ __( 'Show Seconds', 'spectra-blocks' ) }
						checked={ showSeconds }
						onChange={ ( value ) => setAttributes( validateUnits( { showSeconds: value } ) ) }
					/>
				</ToolsPanelItem>

				{ /*
				 * ARIA Live Type Control
				 * Determines how screen readers announce countdown updates
				 * Options: Off (no announcements), Polite (non-interruptive), Assertive (interruptive)
				 */ }
				<ToolsPanelItem
					hasValue={ () => ariaLiveType !== 'off' }
					label={ __( 'ARIA Live Type', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { ariaLiveType: 'off' } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						isBlock
						label={ __( 'ARIA Live Type', 'spectra-blocks' ) }
						help={ __(
							'Choose how screen readers announce countdown updates: Off (no announcement), Polite (announces calmly), or Assertive (announces immediately).',
							'spectra-blocks'
						) }
						value={ ariaLiveType || 'off' }
						onChange={ ( value ) => setAttributes( { ariaLiveType: value } ) }
					>
						<ToggleGroupControlOption label={ __( 'Off', 'spectra-blocks' ) } value="off" />
						<ToggleGroupControlOption
							label={ __( 'Polite', 'spectra-blocks' ) }
							value="polite"
						/>
						<ToggleGroupControlOption
							label={ __( 'Assertive', 'spectra-blocks' ) }
							value="assertive"
						/>
					</ToggleGroupControl>
				</ToolsPanelItem>

				{ /*
				 * Overflow Control
				 * Determines how content that exceeds the block's dimensions is displayed
				 * Options: Visible (default), Hidden, Auto
				 */ }
				<ToolsPanelItem
					hasValue={ () => overflow !== 'visible' }
					label={ __( 'Overflow', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { overflow: 'visible' } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Overflow', 'spectra-blocks' ) }
						help={ __(
							'Decide how extra content is shown: Visible (shows all), Hidden (cuts off), or Auto (adds scrollbars when needed).',
							'spectra-blocks'
						) }
						value={ overflow || 'visible' }
						onChange={ ( value ) => setAttributes( { overflow: value } ) }
						isBlock
					>
						<ToggleGroupControlOption value="visible" label="Visible" />
						<ToggleGroupControlOption value="hidden" label="Hidden" />
						<ToggleGroupControlOption value="auto" label="Auto" />
					</ToggleGroupControl>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Label configuration for the countdown timer.
 * Controls visibility and text for all time unit labels (singular and plural forms).
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const LabelSettings = memo( ( props ) => {
	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: {
			showLabels,
			daysLabel,
			dayLabel,
			hoursLabel,
			hourLabel,
			minutesLabel,
			minuteLabel,
			secondsLabel,
			secondLabel,
		},
	} = props;

	return (
		<InspectorControls group="settings">
			{ /*
			 * Labels Settings Panel
			 * Contains all label-related settings including:
			 * - Global label visibility toggle
			 * - Custom text for all time unit labels (singular and plural forms)
			 * Resets all label settings to defaults when "Reset All" is clicked
			 */ }
			<ToolsPanel
				label={ __( 'Labels', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						showLabels: true,
						daysLabel: __( 'Days', 'spectra-blocks' ),
						dayLabel: __( 'Day', 'spectra-blocks' ),
						hoursLabel: __( 'Hours', 'spectra-blocks' ),
						hourLabel: __( 'Hour', 'spectra-blocks' ),
						minutesLabel: __( 'Minutes', 'spectra-blocks' ),
						minuteLabel: __( 'Minute', 'spectra-blocks' ),
						secondsLabel: __( 'Seconds', 'spectra-blocks' ),
						secondLabel: __( 'Second', 'spectra-blocks' ),
					} );
				} }
				panelId={ clientId }
			>
				{ /*
				 * Show Labels Toggle
				 * Controls visibility of all time unit labels
				 * When disabled, hides all individual label controls
				 */ }
				<ToolsPanelItem
					hasValue={ () => ! showLabels }
					label={ __( 'Show Labels', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { showLabels: true } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleControl
						label={ __( 'Show Labels', 'spectra-blocks' ) }
						checked={ showLabels }
						onChange={ ( value ) => setAttributes( { showLabels: value } ) }
					/>
				</ToolsPanelItem>

				{ showLabels && (
					<>
						{ /* Days Label (Plural) Control */ }
						<ToolsPanelItem
							hasValue={ () => daysLabel !== __( 'Days', 'spectra-blocks' ) }
							label={ __( 'Days Label (Plural)', 'spectra-blocks' ) }
							onDeselect={ () =>
								setAttributes( { daysLabel: __( 'Days', 'spectra-blocks' ) } )
							}
							isShownByDefault
							panelId={ clientId }
						>
							<TextControl
								label={ __( 'Days Label (Plural)', 'spectra-blocks' ) }
								value={ daysLabel || __( 'Days', 'spectra-blocks' ) }
								onChange={ ( value ) => setAttributes( { daysLabel: value } ) }
							/>
						</ToolsPanelItem>
						{ /* Day Label (Singular) Control */ }
						<ToolsPanelItem
							hasValue={ () => dayLabel !== __( 'Day', 'spectra-blocks' ) }
							label={ __( 'Day Label (Singular)', 'spectra-blocks' ) }
							onDeselect={ () =>
								setAttributes( { dayLabel: __( 'Day', 'spectra-blocks' ) } )
							}
							isShownByDefault
							panelId={ clientId }
						>
							<TextControl
								label={ __( 'Day Label (Singular)', 'spectra-blocks' ) }
								value={ dayLabel || __( 'Day', 'spectra-blocks' ) }
								onChange={ ( value ) => setAttributes( { dayLabel: value } ) }
							/>
						</ToolsPanelItem>
						{ /* Hours Label (Plural) Control */ }
						<ToolsPanelItem
							hasValue={ () => hoursLabel !== __( 'Hours', 'spectra-blocks' ) }
							label={ __( 'Hours Label (Plural)', 'spectra-blocks' ) }
							onDeselect={ () =>
								setAttributes( { hoursLabel: __( 'Hours', 'spectra-blocks' ) } )
							}
							isShownByDefault
							panelId={ clientId }
						>
							<TextControl
								label={ __( 'Hours Label (Plural)', 'spectra-blocks' ) }
								value={ hoursLabel || __( 'Hours', 'spectra-blocks' ) }
								onChange={ ( value ) => setAttributes( { hoursLabel: value } ) }
							/>
						</ToolsPanelItem>
						{ /* Hour Label (Singular) Control */ }
						<ToolsPanelItem
							hasValue={ () => hourLabel !== __( 'Hour', 'spectra-blocks' ) }
							label={ __( 'Hour Label (Singular)', 'spectra-blocks' ) }
							onDeselect={ () =>
								setAttributes( { hourLabel: __( 'Hour', 'spectra-blocks' ) } )
							}
							isShownByDefault
							panelId={ clientId }
						>
							<TextControl
								label={ __( 'Hour Label (Singular)', 'spectra-blocks' ) }
								value={ hourLabel || __( 'Hour', 'spectra-blocks' ) }
								onChange={ ( value ) => setAttributes( { hourLabel: value } ) }
							/>
						</ToolsPanelItem>
						{ /* Minutes Label (Plural) Control */ }
						<ToolsPanelItem
							hasValue={ () => minutesLabel !== __( 'Minutes', 'spectra-blocks' ) }
							label={ __( 'Minutes Label (Plural)', 'spectra-blocks' ) }
							onDeselect={ () =>
								setAttributes( { minutesLabel: __( 'Minutes', 'spectra-blocks' ) } )
							}
							isShownByDefault
							panelId={ clientId }
						>
							<TextControl
								label={ __( 'Minutes Label (Plural)', 'spectra-blocks' ) }
								value={ minutesLabel || __( 'Minutes', 'spectra-blocks' ) }
								onChange={ ( value ) => setAttributes( { minutesLabel: value } ) }
							/>
						</ToolsPanelItem>
						{ /* Minute Label (Singular) Control */ }
						<ToolsPanelItem
							hasValue={ () => minuteLabel !== __( 'Minute', 'spectra-blocks' ) }
							label={ __( 'Minute Label (Singular)', 'spectra-blocks' ) }
							onDeselect={ () =>
								setAttributes( { minuteLabel: __( 'Minute', 'spectra-blocks' ) } )
							}
							isShownByDefault
							panelId={ clientId }
						>
							<TextControl
								label={ __( 'Minute Label (Singular)', 'spectra-blocks' ) }
								value={ minuteLabel || __( 'Minute', 'spectra-blocks' ) }
								onChange={ ( value ) => setAttributes( { minuteLabel: value } ) }
							/>
						</ToolsPanelItem>
						{ /* Seconds Label (Plural) Control */ }
						<ToolsPanelItem
							hasValue={ () => secondsLabel !== __( 'Seconds', 'spectra-blocks' ) }
							label={ __( 'Seconds Label (Plural)', 'spectra-blocks' ) }
							onDeselect={ () =>
								setAttributes( { secondsLabel: __( 'Seconds', 'spectra-blocks' ) } )
							}
							isShownByDefault
							panelId={ clientId }
						>
							<TextControl
								label={ __( 'Seconds Label (Plural)', 'spectra-blocks' ) }
								value={ secondsLabel || __( 'Seconds', 'spectra-blocks' ) }
								onChange={ ( value ) => setAttributes( { secondsLabel: value } ) }
							/>
						</ToolsPanelItem>
						{ /* Second Label (Singular) Control */ }
						<ToolsPanelItem
							hasValue={ () => secondLabel !== __( 'Second', 'spectra-blocks' ) }
							label={ __( 'Second Label (Singular)', 'spectra-blocks' ) }
							onDeselect={ () =>
								setAttributes( { secondLabel: __( 'Second', 'spectra-blocks' ) } )
							}
							isShownByDefault
							panelId={ clientId }
						>
							<TextControl
								label={ __( 'Second Label (Singular)', 'spectra-blocks' ) }
								value={ secondLabel || __( 'Second', 'spectra-blocks' ) }
								onChange={ ( value ) => setAttributes( { secondLabel: value } ) }
							/>
						</ToolsPanelItem>
					</>
				) }
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Separator configuration for the countdown timer.
 * Controls visibility and style of separators between time units.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const SeparatorSettings = memo( ( props ) => {
	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: { showSeparator, separatorType },
	} = props;

	return (
		<InspectorControls group="settings">
			{ /*
			 * Separator Settings Panel
			 * Contains all separator-related settings including:
			 * - Separator visibility toggle
			 * - Separator style selection (when visible)
			 * Resets all separator settings to defaults when "Reset All" is clicked
			 */ }
			<ToolsPanel
				label={ __( 'Separator', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						showSeparator: true,
						separatorType: ':',
					} );
				} }
				panelId={ clientId }
			>
				{ /*
				 * Show Separator Toggle
				 * Controls visibility of separators between time units
				 * When disabled, hides the separator type selector
				 */ }
				<ToolsPanelItem
					hasValue={ () => ! showSeparator }
					label={ __( 'Show Separator', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { showSeparator: true } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleControl
						label={ __( 'Show Separator', 'spectra-blocks' ) }
						checked={ showSeparator }
						onChange={ ( value ) => setAttributes( { showSeparator: value } ) }
					/>
				</ToolsPanelItem>
				{ showSeparator && (
					/*
					 * Separator Type Control
					 * Allows selecting between different separator styles:
					 * - Colon (:)
					 * - Line (|)
					 * - Slash (/)
					 * Only visible when separators are enabled
					 */
					<ToolsPanelItem
						hasValue={ () => separatorType !== ':' }
						label={ __( 'Separator Type', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { separatorType: ':' } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<ToggleGroupControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							isBlock
							label={ __( 'Separator Type', 'spectra-blocks' ) }
							value={ separatorType || ':' }
							onChange={ ( value ) => setAttributes( { separatorType: value } ) }
						>
							<ToggleGroupControlOption
								label={ __( 'Colon (:)', 'spectra-blocks' ) }
								value=":"
							/>
							<ToggleGroupControlOption
								label={ __( 'Line (|)', 'spectra-blocks' ) }
								value="|"
							/>
							<ToggleGroupControlOption
								label={ __( 'Slash (/)', 'spectra-blocks' ) }
								value="/"
							/>
						</ToggleGroupControl>
					</ToolsPanelItem>
				) }
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Dimensions panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block dimensions styles.
 */
const DimensionSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: { maxWidth, maxHeight, minWidth, minHeight, width, height, align },
	} = props;

	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( { availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ] } );

	return (
		<InspectorControls group="dimensions">
			{ align === undefined ? (
				<>
					{ /* This tool panel item will require reset when any of these conditions are met:
					- The width attribute is set. Default: undefined.
					- The height attribute is set. Default: auto.
					- The minWidth attribute is set. Default: undefined.
					- The minHeight attribute is set. Default: undefined.
					- The maxWidth attribute is set. Default: undefined.
					- The maxHeight attribute is set. Default: undefined.
					*/ }
					<ToolsPanelItem
						hasValue={ () =>
							!! width ||
							( !! height && height !== 'auto' ) ||
							!! minWidth ||
							!! minHeight ||
							!! maxWidth ||
							!! maxHeight
						}
						label={ __( 'Sizes', 'spectra-blocks' ) }
						as={ Grid }
						panelId={ clientId }
						isShownByDefault
						onDeselect={ () =>
							setAttributes( {
								width: undefined,
								height: 'auto',
								minWidth: undefined,
								minHeight: undefined,
								maxWidth: undefined,
								maxHeight: undefined,
							} )
						}
						resetAllFilter={ () => ( {
							width: undefined,
							height: 'auto',
							minWidth: undefined,
							minHeight: undefined,
							maxWidth: undefined,
							maxHeight: undefined,
						} ) }
					>
						<UnitControl
							__next40pxDefaultSize
							label="Width"
							value={ width }
							onChange={ ( value ) => setAttributes( { width: value } ) }
							units={ units }
						/>
						<UnitControl
							__next40pxDefaultSize
							label="Height"
							value={ height }
							onChange={ ( value ) => setAttributes( { height: value } ) }
							units={ units }
						/>
						<UnitControl
							__next40pxDefaultSize
							label="Min W"
							value={ minWidth }
							onChange={ ( value ) => setAttributes( { minWidth: value } ) }
							units={ units }
						/>
						<UnitControl
							__next40pxDefaultSize
							label="Min H"
							value={ minHeight }
							onChange={ ( value ) => setAttributes( { minHeight: value } ) }
							units={ units }
						/>
						<UnitControl
							__next40pxDefaultSize
							label="Max W"
							value={ maxWidth }
							onChange={ ( value ) => setAttributes( { maxWidth: value } ) }
							units={ units }
						/>
						<UnitControl
							__next40pxDefaultSize
							label="Max H"
							value={ maxHeight }
							onChange={ ( value ) => setAttributes( { maxHeight: value } ) }
							units={ units }
						/>
					</ToolsPanelItem>
				</>
			) : (
				<>
					{ /* This tool panel item will require reset when any of these conditions are met:
					- The height attribute is set. Default: auto.
					- The minHeight attribute is set. Default: undefined.
					- The maxHeight attribute is set. Default: undefined.
					*/ }
					<ToolsPanelItem
						hasValue={ () => ( !! height && height !== 'auto' ) || !! minHeight || !! maxHeight }
						label={ __( 'Sizes', 'spectra-blocks' ) }
						panelId={ clientId }
						isShownByDefault
						onDeselect={ () =>
							setAttributes( {
								height: 'auto',
								minHeight: undefined,
								maxHeight: undefined,
							} )
						}
						resetAllFilter={ () => ( {
							height: 'auto',
							minHeight: undefined,
							maxHeight: undefined,
						} ) }
					>
						<UnitControl
							__next40pxDefaultSize
							label="Height"
							value={ height }
							onChange={ ( value ) => setAttributes( { height: value } ) }
							units={ units }
						/>
						<UnitControl
							__next40pxDefaultSize
							label="Min Height"
							value={ minHeight }
							onChange={ ( value ) => setAttributes( { minHeight: value } ) }
							units={ units }
						/>
						<UnitControl
							__next40pxDefaultSize
							label="Max Height"
							value={ maxHeight }
							onChange={ ( value ) => setAttributes( { maxHeight: value } ) }
							units={ units }
						/>
					</ToolsPanelItem>
				</>
			) }
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Color configuration for the countdown timer.
 * Controls text and background colors (normal and hover states).
 * Injected into the core Color panel in the block editor.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const ColorSettings = memo( ( props ) => {
	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: { textColorHover, backgroundColorHover, backgroundGradientHover },
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: textColorHover,
					label: __( 'Text Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Background Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () =>
						setAttributes( {
							backgroundColorHover: undefined,
							backgroundGradientHover: undefined,
						} ),
				},
			] }
			panelId={ clientId }
		/>
	);
} );

/**
 * The Editor settings component that combines all settings panels.
 * Renders all sub-settings components in the block inspector.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = ( props ) => {
	// Expiry settings.
	const expirySettings = applyFilters( 'spectra.countdown.expiry-settings', null, props );

	return (
		<>
			<GeneralSettings { ...{ ...props } } />
			<SeparatorSettings { ...{ ...props } } />
			{ expirySettings }
			<LabelSettings { ...{ ...props } } />
			<ColorSettings { ...{ ...props } } />
			<DimensionSettings { ...{ ...props } } />
		</>
	);
};
export default memo( Settings );
