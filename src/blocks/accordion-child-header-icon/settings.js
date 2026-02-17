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
	TabPanel,
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
 * @return {Element} The rendered settings.
 */
export const BlockSettings = ( props ) => {
	
	const {
		clientId,
		setAttributes,
		attributes: {
			icon,
			iconSecondary,
			flipForRTL,
			flipForRTLSecondary,
			rotation,
		}
	} = props;

	// The tabs for the settings.
	const tabs = [
		{
			name: 'collapsed',
			title: __( 'Collapsed', 'ultimate-addons-for-gutenberg' ),
		},
		{
			name: 'expanded',
			title: __( 'Expanded', 'ultimate-addons-for-gutenberg' ),
		},
	];

	const tabAttributes = {
		collapsed: {
			iconLabel: 'icon',
			iconValue: icon,
			flipForRTLLabel: 'flipForRTL',
			flipForRTLValue: flipForRTL,
		},
		expanded: {
			iconLabel: 'iconSecondary',
			iconValue: iconSecondary,
			flipForRTLLabel: 'flipForRTLSecondary',
			flipForRTLValue: flipForRTLSecondary,
		},
	};

	// The Settings Tab settings.
	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Icon', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						icon: undefined,
						iconSecondary: undefined,
						flipForRTL: true,
						flipForRTLSecondary: true,
						rotation: undefined,
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The icon attribute is set. Default: undefined.
				- The iconSecondary attribute is set. Default: undefined.
				- The flipForRTL attribute is not true. Default: true.
				- The flipForRTLSecondary attribute is not true. Default: true.
				--- Note that for the accordion icons, flip for RTL is intentionally true by default for good UI.
				*/}
				<ToolsPanelItem
					hasValue={ () => ( !! icon || !! iconSecondary || ! flipForRTL || ! flipForRTLSecondary ) }
					label={ __( 'Icon', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( {
						icon: undefined,
						iconSecondary: undefined,
						flipForRTL: true,
						flipForRTLSecondary: true,
					} ) }
					resetAllFilter={ () => ( {
						icon: undefined,
						iconSecondary: undefined,
						flipForRTL: true,
						flipForRTLSecondary: true,
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<VStack spacing={ 4 }>
						<TabPanel tabs={ tabs }>
							{ ( tab ) => (
								<VStack spacing={ 4 }>
									<IconPicker
										value={ tabAttributes[ tab.name ].iconValue }
										onChange={ ( value ) => setAttributes( { [ tabAttributes[ tab.name ].iconLabel ]: value } ) }
									/>
									<ToggleControl
										__nextHasNoMarginBottom
										checked={ tabAttributes[ tab.name ].flipForRTLValue }
										label={ __( 'Flip Icon for Right-To-Left', 'ultimate-addons-for-gutenberg' ) }
										onChange={ () => setAttributes( { [ tabAttributes[ tab.name ].flipForRTLLabel ]: ! tabAttributes[ tab.name ].flipForRTLValue } ) }
										help={ __( 'Enable this for your RTL visitors if you are using a direction-specific icon. Like \'Arrow Right\', \'Chart Line\', etc. ', 'ultimate-addons-for-gutenberg' ) }
									/>
								</VStack>
							) }
						</TabPanel>
					</VStack>
				</ToolsPanelItem>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The rotation attribute is set. Default: undefined.
				 */}
				<ToolsPanelItem
					hasValue={ () => !! rotation }
					label={ __( 'Rotation', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { rotation: undefined } ) }
					resetAllFilter={ () => ( {
						rotation: undefined,
					} ) }
					panelId={ clientId }
				>
					<AnglePickerControl
						label={ __( 'Rotation', 'ultimate-addons-for-gutenberg' ) }
						onChange={ ( value ) => {
							setAttributes( { rotation: value } );
						} }
						value={ rotation || 0 }
					/>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
};

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
			size,
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
				hasValue={ () => !! size }
				label={ __( 'Size', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { size: undefined } ) }
				resetAllFilter={ () => ( {
					size: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Size', 'ultimate-addons-for-gutenberg' ) }
					labelPosition="top"
					value={ size }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { size: value } ) }
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
			textColorHover,
			backgroundColorHover,
			backgroundGradientHover,
		},
	} = props;

	return(
		<InspectorColor
			settings={ [
				{
					colorValue: textColorHover,
					label: __( 'Icon Hover/Focus', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Background Hover/Focus', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () => setAttributes( { backgroundColorHover: undefined } ),
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
const Settings = ( props ) => (
	<>
		<BlockSettings { ...{ ...props } } />
		<DimensionSettings { ...{ ...props } } />
		<ColorSettings { ...{ ...props } } />
	</>
);

export default memo( Settings );