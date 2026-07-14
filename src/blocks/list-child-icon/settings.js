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
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalVStack as VStack,
	AnglePickerControl,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import IconPicker from '@spectra-components/icon-picker';
import InspectorColor from '@spectra-components/inspector-color';

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
		icon,
		flipForRTL,
		rotation
	} = attributes;

	// Render the settings.
	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Icon', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						icon: undefined,
						flipForRTL: undefined,
						accessibilityMode: undefined,
						accessibilityLabel: undefined,
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The icon attribute is set. Default: undefined.
				- The flipForRTL attribute is true. Default: false.
				*/}
				<ToolsPanelItem
					hasValue={ () => ( !! icon || !! flipForRTL ) }
					label={ __( 'Icon', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( {
						icon: undefined,
						flipForRTL: undefined,
					} ) }
					resetAllFilter={ () => ( {
						icon: undefined,
						flipForRTL: undefined,
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack spacing={ 4 }>
						<IconPicker
							value={ icon }
							onChange={ ( value ) => setAttributes( { icon: value } ) }
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							checked={ flipForRTL }
							label={ __( 'Flip Icon for Right-To-Left', 'spectra-blocks' ) }
							onChange={ () => setAttributes( { flipForRTL: ! flipForRTL } ) }
							help={ __( 'Enable this for your RTL visitors if you are using a direction-specific icon. Like "Arrow Right", "Chart Line", etc.', 'spectra-blocks' ) }
						/>
					</VStack>
				</ToolsPanelItem>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The rotation attribute is set. Default: undefined.
				*/}
				<ToolsPanelItem
					hasValue={ () => !! rotation }
					label={ __( 'Rotation', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { rotation: undefined } ) }
					resetAllFilter={ () => ( {
						rotation: undefined,
					} ) }
					panelId={ clientId }
				>
					<AnglePickerControl
						label={ __( 'Rotation', 'spectra-blocks' ) }
						onChange={ ( value ) => {
							setAttributes( { rotation: value } );
						} }
						value={ rotation }
					/>
				</ToolsPanelItem>
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
const DimensionSettings = memo( ( props ) => {

	// Destructure the requierd props.
	const {
		clientId,
		setAttributes,
		attributes: {
			iconSize,
		},
	} = props;

	// Get the core provided units, else add the fallback.	
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="dimensions">
			<ToolsPanelItem
				hasValue={ () => !! iconSize }
				label={ __( 'Icon Size', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { iconSize: undefined } ) }
				resetAllFilter={ () => ( {
					iconSize: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Icon Size', 'spectra-blocks' ) }
					labelPosition="top"
					value={ iconSize }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { iconSize: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
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
			textColor,
			textColorHover,
			backgroundColorHover,
			backgroundGradientHover,
		},
	} = props;

	return(
		<InspectorColor
			settings={ [
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Background Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () => setAttributes( { backgroundColorHover: undefined } ),
				},
				{
					colorValue: textColor,
					label: __( 'Icon', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColor: value } ),
					resetAllFilter: () => setAttributes( { textColor: undefined } ),
				},
				{
					colorValue: textColorHover,
					label: __( 'Icon Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
			] }
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
const Settings = ( props ) => {
	const {		
		context = {}
	} = props;
	
	const listType = context['spectra/list/listType'] || 'unordered';
	
	return (
		<>
			{listType === 'unordered' &&
				<> 
					<BlockSettings {...props} />
					<DimensionSettings {...props} />
				</>
			}
			<ColorSettings {...props} />
		</>
	);
};

export default memo( Settings );