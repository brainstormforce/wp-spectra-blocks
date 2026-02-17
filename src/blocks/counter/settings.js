/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import {
	TextControl,
	RangeControl,
	__experimentalNumberControl as NumberControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import InspectorColor from '@spectra-components/inspector-color';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';

/**
 * Element Sub-settings: General settings.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BlockSettings = memo( ( props ) => {
	// Destructure the required props.
	const { clientId, setAttributes, attributes } = props;

	const {
		counterStyle,
		startNumber,
		endNumber,
		totalNumber,
		animationDuration,
		prefix,
		suffix,
		thousandSeparator,
		decimalPlaces,
	} = attributes;

	const isCircularOrBar = counterStyle === 'circular' || counterStyle === 'bar';

	return (
		<>
			<InspectorControls group="settings">
				<ToolsPanel
					label={ __( 'General', 'ultimate-addons-for-gutenberg' ) }
					resetAll={ () => {
						setAttributes( {
							counterStyle: undefined,
							startNumber: undefined,
							endNumber: undefined,
							totalNumber: undefined,
						} );
					} }
			panelId={ clientId }
		>
			{/* This tool panel item will require reset when any of these conditions are met:
			- Counter style is set to any value. Default: 'simple'.
			- When reset, counter style returns to simple layout.
			*/}
			<ToolsPanelItem
				hasValue={ () => !! counterStyle }
				label={ __( 'Counter Style', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { counterStyle: undefined } ) }
				resetAllFilter={ () => ( { counterStyle: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
						<ToggleGroupControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							isBlock
							label={ __( 'Counter Style', 'ultimate-addons-for-gutenberg' ) }
							value={ counterStyle || 'simple' }
							onChange={ ( value ) => setAttributes( { counterStyle: value } ) }
						>
							<ToggleGroupControlOption
								label={ __( 'Simple', 'ultimate-addons-for-gutenberg' ) }
								value="simple"
							/>
							<ToggleGroupControlOption
								label={ __( 'Circular', 'ultimate-addons-for-gutenberg' ) }
								value="circular"
							/>
							<ToggleGroupControlOption
								label={ __( 'Bar', 'ultimate-addons-for-gutenberg' ) }
								value="bar"
							/>
						</ToggleGroupControl>
			</ToolsPanelItem>

			{/* This tool panel item will require reset when any of these conditions are met:
			- Starting number is set to any value. Default: 0.
			- When reset, starting number returns to 0.
			*/}
			<ToolsPanelItem
				hasValue={ () => startNumber !== undefined }
				label={ __( 'Starting Number', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { startNumber: undefined } ) }
				resetAllFilter={ () => ( { startNumber: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
					<NumberControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Starting Number', 'ultimate-addons-for-gutenberg' ) }
						min={ 0 }
						value={ startNumber }
						onChange={ ( value ) => {
							// NumberControl handles validation automatically
							// Ensure we save a proper number value
							const numValue = value === '' || value === undefined ? undefined : Number( value );
							setAttributes( { startNumber: numValue } );
						} }
						step={ 0.1 }
						help={ __( 'Only positive values are allowed.', 'ultimate-addons-for-gutenberg' ) }
					/>
			</ToolsPanelItem>

			{/* This tool panel item will require reset when any of these conditions are met:
			- Ending number is set to any value. Default: 100.
			- When reset, ending number returns to 100.
			*/}
			<ToolsPanelItem
				hasValue={ () => endNumber !== undefined }
				label={ __( 'Ending Number', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { endNumber: undefined } ) }
				resetAllFilter={ () => ( { endNumber: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
					<NumberControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Ending Number', 'ultimate-addons-for-gutenberg' ) }
						min={ 0 }
						value={ endNumber }
						onChange={ ( value ) => {
							// NumberControl handles validation automatically
							// Ensure we save a proper number value
							const numValue = value === '' || value === undefined ? undefined : Number( value );
							setAttributes( { endNumber: numValue } );
						} }
						step={ 0.1 }
						help={ __( 'Only positive values are allowed.', 'ultimate-addons-for-gutenberg' ) }
					/>
			</ToolsPanelItem>

			{/* This tool panel item will require reset when any of these conditions are met:
			- Total number is set to any value. Default: 100.
			- When reset, total number returns to 100.
			- Only visible for circular and bar counter styles.
			*/}
			{ isCircularOrBar && (
				<ToolsPanelItem
					hasValue={ () => totalNumber !== undefined }
					label={ __( 'Total Number', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { totalNumber: undefined } ) }
					resetAllFilter={ () => ( { totalNumber: undefined } ) }
					isShownByDefault
					panelId={ clientId }
				>
						<NumberControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Total Number', 'ultimate-addons-for-gutenberg' ) }
							min={ 0 }
							value={ totalNumber }
							onChange={ ( value ) => {
								// NumberControl handles validation automatically
								// Ensure we save a proper number value
								const numValue = value === '' || value === undefined ? undefined : Number( value );
								setAttributes( { totalNumber: numValue } );
							} }
							step={ 0.1 }
							help={ __( 'Total Number should be more than or equal to the Ending Number. Only positive values are allowed.', 'ultimate-addons-for-gutenberg' ) }
						/>
					</ToolsPanelItem>
				) }
				</ToolsPanel>

				<ToolsPanel
					label={ __( 'Counter Animation', 'ultimate-addons-for-gutenberg' ) }
			resetAll={ () => {
				setAttributes( {
					animationDuration: undefined,
				} );
			} }
			panelId={ clientId }
		>
			{/* This tool panel item will require reset when any of these conditions are met:
			- Animation duration is set to any value. Default: 2000ms.
			- When reset, animation duration returns to 2000ms.
			*/}
			<ToolsPanelItem
				hasValue={ () => !! animationDuration }
				label={ __( 'Duration', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { animationDuration: undefined } ) }
				resetAllFilter={ () => ( { animationDuration: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Duration (ms)', 'ultimate-addons-for-gutenberg' ) }
						value={ animationDuration }
						onChange={ ( value ) => setAttributes( { animationDuration: value } ) }
						min={ 500 }
						max={ 10000 }
						step={ 100 }
					/>
			</ToolsPanelItem>

				</ToolsPanel>

			<ToolsPanel
				label={ __( 'Number Format', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						prefix: '',
						suffix: '',
						thousandSeparator: undefined,
						decimalPlaces: 0,
					} );
				} }
			panelId={ clientId }
		>
				{/* This tool panel item will require reset when any of these conditions are met:
				- Prefix is set to any value. Default: undefined (empty).
				- When reset, prefix returns to empty.
				*/}
				<ToolsPanelItem
					hasValue={ () => prefix && prefix !== '' }
					label={ __( 'Prefix', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { prefix: '' } ) }
					resetAllFilter={ () => ( { prefix: '' } ) }
					isShownByDefault
					panelId={ clientId }
				>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Prefix', 'ultimate-addons-for-gutenberg' ) }
							value={ prefix || '' }
							onChange={ ( value ) => setAttributes( { prefix: value || '' } ) }
							placeholder={ __( 'e.g., $, #, +', 'ultimate-addons-for-gutenberg' ) }
						/>
				</ToolsPanelItem>

			{/* This tool panel item will require reset when any of these conditions are met:
			- Suffix is set to any value. Default: ''.
			- When reset, suffix returns to empty string.
			*/}
			<ToolsPanelItem
				hasValue={ () => suffix && suffix !== '' }
				label={ __( 'Suffix', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { suffix: '' } ) }
				resetAllFilter={ () => ( { suffix: '' } ) }
				isShownByDefault
				panelId={ clientId }
			>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Suffix', 'ultimate-addons-for-gutenberg' ) }
						value={ suffix || '' }
						onChange={ ( value ) => setAttributes( { suffix: value || '' } ) }
						placeholder={ __( 'e.g., %, +, K, M', 'ultimate-addons-for-gutenberg' ) }
					/>
			</ToolsPanelItem>

			{/* This tool panel item will require reset when any of these conditions are met:
			- Thousand separator is set to any value different from default. Default: ','.
			- When reset, thousand separator returns to comma.
			*/}
			<ToolsPanelItem
				hasValue={ () => !! thousandSeparator }
				label={ __( 'Thousand Separator', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { thousandSeparator: undefined } ) }
				resetAllFilter={ () => ( { thousandSeparator: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Thousand Separator', 'ultimate-addons-for-gutenberg' ) }
						value={ thousandSeparator || '' }
						onChange={ ( value ) => setAttributes( { thousandSeparator: value || undefined } ) }
						placeholder={ __( 'e.g., , or . or space', 'ultimate-addons-for-gutenberg' ) }
					/>
				</ToolsPanelItem>

			{/* This tool panel item will require reset when any of these conditions are met:
			- Decimal places is set to any value different from default. Default: 0.
			- When reset, decimal places returns to 0 (no decimals).
			*/}
			<ToolsPanelItem
				hasValue={ () => decimalPlaces !== 0 }
				label={ __( 'Decimal Places', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { decimalPlaces: 0 } ) }
				resetAllFilter={ () => ( { decimalPlaces: 0 } ) }
				isShownByDefault
				panelId={ clientId }
			>
					<DebouncedRangeControl
						label={ __( 'Decimal Places', 'ultimate-addons-for-gutenberg' ) }
						value={ decimalPlaces }
						onChange={ ( value ) => setAttributes( { decimalPlaces: value } ) }
						min={ 0 }
						max={ 5 }
						step={ 1 }
						debounceTime={ 500 }
					/>
				</ToolsPanelItem>
				</ToolsPanel>
			</InspectorControls>
		</>
	);
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Dimensions panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const DimensionSettings = memo( ( props ) => {
	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: {
			prefixRightMargin,
			suffixLeftMargin,
		},
	} = props;

	return (
		<InspectorControls group="dimensions">
			{/* This tool panel item will require reset when any of these conditions are met:
			- Prefix right margin is set. Default: 0.
			- When reset, prefix right margin returns to 0.
			- Supports responsive values (Desktop/Tablet/Mobile).
			*/}
			<ToolsPanelItem
				hasValue={ () => prefixRightMargin !== undefined && prefixRightMargin !== 0 }
				label={ __( 'Prefix Right Margin', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { prefixRightMargin: undefined } ) }
				resetAllFilter={ () => ( { prefixRightMargin: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Prefix Right Margin', 'ultimate-addons-for-gutenberg' ) }
					value={ prefixRightMargin ?? 0 }
					onChange={ ( value ) => setAttributes( { prefixRightMargin: value } ) }
					min={ 0 }
					max={ 100 }
					step={ 1 }
				/>
			</ToolsPanelItem>

			{/* This tool panel item will require reset when any of these conditions are met:
			- Suffix left margin is set. Default: 0.
			- When reset, suffix left margin returns to 0.
			- Supports responsive values (Desktop/Tablet/Mobile).
			*/}
			<ToolsPanelItem
				hasValue={ () => suffixLeftMargin !== undefined && suffixLeftMargin !== 0 }
				label={ __( 'Suffix Left Margin', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { suffixLeftMargin: undefined } ) }
				resetAllFilter={ () => ( { suffixLeftMargin: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Suffix Left Margin', 'ultimate-addons-for-gutenberg' ) }
					value={ suffixLeftMargin ?? 0 }
					onChange={ ( value ) => setAttributes( { suffixLeftMargin: value } ) }
					min={ 0 }
					max={ 100 }
					step={ 1 }
				/>
			</ToolsPanelItem>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Progress settings for circular and bar styles.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const ProgressSettings = memo( ( props ) => {
	// Destructure the required props.
	const { clientId, setAttributes, attributes } = props;

	const {
		counterStyle,
		progressSize,
		progressStrokeWidth,
	} = attributes;

	// Get the core provided units, else add the fallback (hooks must be called before any conditional returns).
	const units = useCustomUnits( {
		availableUnits: [ 'px' ],
	} );

	// Only show progress settings for circular and bar styles.
	if ( counterStyle === 'simple' || counterStyle === 'bar' ) {
		return null;
	}

	return (
		<InspectorControls group="styles">
			<ToolsPanel
				label={ __( 'Circular Progress', 'ultimate-addons-for-gutenberg' ) }
			resetAll={ () => {
				setAttributes( {
					progressColor: undefined,
					progressBackgroundColor: undefined,
					progressSize: undefined,
					progressStrokeWidth: undefined,
				} );
			} }
			panelId={ clientId }
		>
			{/* This tool panel item will require reset when any of these conditions are met:
			- Progress size is set to any value. Default: '300px'.
			- When reset, progress size returns to 300px.
			- Only visible for circular counter style.
			*/}
			{ counterStyle === 'circular' && (
				<ToolsPanelItem
					hasValue={ () => !! progressSize }
					label={ __( 'Size', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { progressSize: undefined } ) }
					resetAllFilter={ () => ( { progressSize: undefined } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<UnitControl
						__next40pxDefaultSize
						label={ __( 'Size', 'ultimate-addons-for-gutenberg' ) }
						labelPosition="top"
						value={ progressSize }
						min={ 0 }
						onChange={ ( value ) => setAttributes( { progressSize: value } ) }
						units={ units }
					/>
			</ToolsPanelItem>
		) }


			{/* This tool panel item will require reset when any of these conditions are met:
			- Stroke width is set to any value. Default: 8.
			- When reset, stroke width returns to 8px.
			- Only visible for circular counter style.
			*/}
			{ counterStyle === 'circular' && (
				<ToolsPanelItem
					hasValue={ () => !! progressStrokeWidth }
					label={ __( 'Stroke Width', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { progressStrokeWidth: undefined } ) }
					resetAllFilter={ () => ( { progressStrokeWidth: undefined } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Stroke Width', 'ultimate-addons-for-gutenberg' ) }
						value={ progressStrokeWidth }
						onChange={ ( value ) => setAttributes( { progressStrokeWidth: value } ) }
						min={ 4 }
						max={ 100 }
						step={ 1 }
					/>
				</ToolsPanelItem>
			) }
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Color panel.
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
		attributes: {
			counterStyle,
			progressColor,
		progressBackgroundColor,
		prefixColor,
		suffixColor,
	},
} = props;

	const colorSettings = [
		{
			colorValue: prefixColor,
			label: __( 'Prefix Color', 'ultimate-addons-for-gutenberg' ),
			onColorChange: ( value ) => setAttributes( { prefixColor: value } ),
			resetAllFilter: () => setAttributes( { prefixColor: undefined } ),
		},
		{
			colorValue: suffixColor,
			label: __( 'Suffix Color', 'ultimate-addons-for-gutenberg' ),
			onColorChange: ( value ) => setAttributes( { suffixColor: value } ),
			resetAllFilter: () => setAttributes( { suffixColor: undefined } ),
		},
	];

	// Add progress color settings for circular and bar styles
	if ( counterStyle !== 'simple' ) {
		colorSettings.push(
			{
				colorValue: progressColor,
				label: __( 'Progress Color', 'ultimate-addons-for-gutenberg' ),
				onColorChange: ( value ) => setAttributes( { progressColor: value } ),
				resetAllFilter: () => setAttributes( { progressColor: undefined } ),
			},
			{
				colorValue: progressBackgroundColor,
				label: __( 'Progress Background', 'ultimate-addons-for-gutenberg' ),
				onColorChange: ( value ) => setAttributes( { progressBackgroundColor: value } ),
				resetAllFilter: () => setAttributes( { progressBackgroundColor: undefined } ),
			}
		);
	}

	// Add progress bar text color setting for bar style only

	return (
		<InspectorColor
			settings={ colorSettings }
			panelId={ clientId }
		/>
	);
} );

/**
 * The Editor settings for this block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = memo( ( props ) => {
	return (
		<>
			<BlockSettings { ...props } />
			<DimensionSettings { ...props } />
			<ProgressSettings { ...props } />
			<ColorSettings { ...props } />
		</>
	);
} );

export default Settings;
