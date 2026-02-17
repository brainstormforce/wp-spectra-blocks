/**
 * External dependencies.
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useSettings,
} from '@wordpress/block-editor';
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
    __experimentalToolsPanel as ToolsPanel,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';
import Background from '@spectra-components/background';
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
	const {
		clientId,
		setAttributes,
		attributes: {
             containerWidth,
             containerHeight,
			 contentHeight,
		},
	} = props;

    	// Get the core provided units, else add the fallback.	
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="settings">
            <ToolsPanel
				label={ __( 'Content', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						containerWidth: undefined,
						containerHeight: undefined,
						contentHeight: undefined,
					} );
				} }
				panelId={ clientId }
			>
				<ToolsPanelItem
					hasValue={ () => !! containerWidth }
					label={ __( 'width', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { containerWidth: undefined } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<UnitControl
						__next40pxDefaultSize
						label={ __( 'Width', 'ultimate-addons-for-gutenberg' ) }
						labelPosition="top"
						value={ containerWidth }
						min={ 0 }
						onChange={ ( value ) => setAttributes( { containerWidth: value } ) }
						units={ units }
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					hasValue={ () => !! contentHeight }
					label={ __( 'Content Height', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { contentHeight: undefined } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Content Height', 'ultimate-addons-for-gutenberg' ) }
						value={ contentHeight }
						onChange={ ( value ) => setAttributes( { contentHeight: value } ) }
						isBlock
					>
						<ToggleGroupControlOption value="auto" label="Auto" />
						<ToggleGroupControlOption value="custom" label="Custom" />
					</ToggleGroupControl>
				</ToolsPanelItem>
				{ 'auto' !== contentHeight && (
				<ToolsPanelItem
					hasValue={ () => !! containerHeight }
					label={ __( 'Height', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { containerHeight: undefined } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<UnitControl
						__next40pxDefaultSize
						label={ __( 'Height', 'ultimate-addons-for-gutenberg' ) }
						labelPosition="top"
						value={ containerHeight }
						min={ 0 }
						onChange={ ( value ) => setAttributes( { containerHeight: value } ) }
						units={ units }
					/>
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
 * @return {Element} The rendered block settings.
 */
const BackgroundSettings = memo( ( props ) => {

	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes,
		context,
		style
	} = props;

	const {
		background,
		backgroundColor,
		backgroundColorHover,
		backgroundGradient,
		backgroundGradientHover,
		dimRatio
	} = attributes;

	return (
		<InspectorControls group="styles">
			<Background
				{ ...{
					clientId,
					attributes,
					setAttributes,
					background: {
						label: 'background',
						value: background,
					},
					backgroundColor,
					backgroundColorHover,
					backgroundGradient,
					backgroundGradientHover,
					context,
					dimRatio,
					style
				} }
			/>
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
			textColorHover,
			backgroundColor,
			backgroundColorHover,
			backgroundGradient,
			backgroundGradientHover,
		},
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: textColorHover,
					label: __( 'Text Hover', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
				{
					colorValue: backgroundColor,
					gradientValue: backgroundGradient,
					label: __( 'Background', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { backgroundColor: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradient: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColor: undefined,
						backgroundGradient: undefined,
					} ),
				},
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Background Hover', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () => setAttributes( {
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
 * Element Sub-settings: Settings that are injected into Core's Color panel.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block opacity styles.
 */
const OpacitySettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			dimRatio
		},
	} = props;

	return (
		<InspectorControls group="color">
			<ToolsPanelItem
				hasValue={() => !!dimRatio}
				label={__( 'Overlay Opacity', 'ultimate-addons-for-gutenberg' )}
				onDeselect={() => setAttributes( { dimRatio: undefined } )}
				resetAllFilter={() => ( {
					dimRatio: undefined,
				} )}
				isShownByDefault
				panelId={clientId}
			>
				<DebouncedRangeControl
					__nextHasNoMarginBottom
					label={__( 'Overlay Opacity', 'ultimate-addons-for-gutenberg' )}
					value={dimRatio}
					onChange={( value ) => setAttributes( { dimRatio: value } )}
					min={0}
					max={100}
					step={5}
					debounceDelay={150}
				/>
			</ToolsPanelItem>
		</InspectorControls>
	);
} );

/**
 * The Editor settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = ( props ) => (
	<>
		<BlockSettings { ...{ ...props } } />
		<BackgroundSettings { ...{ ...props } } />
		<ColorSettings { ...{ ...props } } />
		<OpacitySettings {...{ ...props }} />
	</>
);

export default memo( Settings );
