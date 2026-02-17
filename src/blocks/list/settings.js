/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import {
	SelectControl,
	ToggleControl,
	TextControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalVStack as VStack,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	AnglePickerControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import InspectorColor from '@spectra-components/inspector-color';
import IconPicker from '@spectra-components/icon-picker';

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
		listType,
		reversed,
		listStyle,
		start,
		icon,
		flipForRTL,
		rotation,
	} = attributes;

	return (
		<>
			<InspectorControls group="settings">
				<ToolsPanel
					label={ __( 'List', 'ultimate-addons-for-gutenberg' ) }
					resetAll={ () => {
						setAttributes( {
							alignment: 'vertical',
							listType: 'unordered',
							reversed: undefined,
							listStyle: undefined,
							start: undefined,
						} );
					} }
					panelId={ clientId }
				>
					<ToolsPanelItem
						hasValue={ () => !!listType }
						label={ __( 'List Type', 'ultimate-addons-for-gutenberg' ) }
						onDeselect={ () => setAttributes( {
							listType: 'unordered',
						} ) }
						resetAllFilter={ () => ( {
							listType: 'unordered',
						} ) }
						isShownByDefault
						panelId={ clientId }
					>
						<SelectControl
							label={ __( 'List Type', 'ultimate-addons-for-gutenberg' ) }
							value={ listType }
							options={ [
								{ label: __( 'Unordered', 'ultimate-addons-for-gutenberg' ), value: 'unordered' },
								{ label: __( 'Ordered', 'ultimate-addons-for-gutenberg' ), value: 'ordered' },
							] }
							onChange={ ( value ) => setAttributes( { listType: value } ) }
						/>
					</ToolsPanelItem>										
					{ listType === 'ordered' && (
						<>
							<ToolsPanelItem
								hasValue={ () => !!reversed }
								label={ __( 'Reverse order', 'ultimate-addons-for-gutenberg' ) }
								onDeselect={ () => setAttributes( {
									reversed: undefined,
								} ) }
								resetAllFilter={ () => ( {
									reversed: undefined,
								} ) }
								isShownByDefault
								panelId={ clientId }
							>
								<ToggleControl
									__nextHasNoMarginBottom
									label={ __( 'Reverse order', 'ultimate-addons-for-gutenberg' ) }
									checked={ reversed || false }
									onChange={ ( value ) => setAttributes( { 
										// Unset the attribute if not reversed
										reversed: value || undefined 
									} ) }
								/>
							</ToolsPanelItem>
							<ToolsPanelItem
								hasValue={ () => !!start }
								label={ __( 'Start value', 'ultimate-addons-for-gutenberg' ) }
								onDeselect={ () => setAttributes( {
									start: undefined,
								} ) }
								resetAllFilter={ () => ( {
									start: undefined,
								} ) }
								isShownByDefault
								panelId={ clientId }
							>
								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Start value', 'ultimate-addons-for-gutenberg' ) }
									type="number"
									value={ Number.isInteger( start ) ? start.toString( 10 ) : '' }
									onChange={( value ) => {
										const int = parseInt( value, 10 );
										setAttributes( {
											// It should be possible to unset the value with an empty string
											start: isNaN( int ) ? undefined : int,
										} );
									} }
									step="1"
								/>
							</ToolsPanelItem>
							
							<ToolsPanelItem
								hasValue={ () => !!listStyle }
								label={ __( 'List Style', 'ultimate-addons-for-gutenberg' ) }
								onDeselect={ () => setAttributes( {
									listStyle: undefined,
								} ) }
								resetAllFilter={ () => ( {
									listStyle: undefined,
								} ) }
								isShownByDefault
								panelId={ clientId }
							>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'List Style', 'ultimate-addons-for-gutenberg' ) }
									value={ listStyle || 'decimal' }
									options={ [
										{ label: __( 'Numbers', 'ultimate-addons-for-gutenberg' ), value: 'decimal' },
										{ label: __( 'Uppercase letters', 'ultimate-addons-for-gutenberg' ), value: 'upper-alpha' },
										{ label: __( 'Lowercase letters', 'ultimate-addons-for-gutenberg' ), value: 'lower-alpha' },
										{ label: __( 'Uppercase Roman numerals', 'ultimate-addons-for-gutenberg' ), value: 'upper-roman' },
										{ label: __( 'Lowercase Roman numerals', 'ultimate-addons-for-gutenberg' ), value: 'lower-roman' },
										{ label: __( 'Decimal with leading zero', 'ultimate-addons-for-gutenberg' ), value: 'decimal-leading-zero' },
									] }
									onChange={ ( value ) => setAttributes( { listStyle: value } ) }
								/>
							</ToolsPanelItem>
						</>
					)}
				</ToolsPanel>				
				{ listType === 'unordered' && (
					<ToolsPanel
						label={ __( 'Icon', 'ultimate-addons-for-gutenberg' ) }
						resetAll={ () => {
							setAttributes( {
								icon: undefined,
								flipForRTL: false,
								accessibilityMode: undefined,
								accessibilityLabel: undefined,
							} );
						} }
						panelId={ clientId }
					>
						<ToolsPanelItem
							hasValue={ () => ( !!icon || !!flipForRTL ) }
							label={ __( 'Icon', 'ultimate-addons-for-gutenberg' ) }
							onDeselect={ () => setAttributes( {
								icon: undefined,
								flipForRTL: false,
							} ) }
							resetAllFilter={ () => ( {
								icon: undefined,
								flipForRTL: false,
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
									label={ __( 'Flip Icon for Right-To-Left', 'ultimate-addons-for-gutenberg' ) }
									onChange={ () => setAttributes( { flipForRTL: !flipForRTL } ) }
									help={ __( 'Enable this for your RTL visitors if you are using a direction-specific icon. Like \'Arrow Right\', \'Chart Line\', etc. ', 'ultimate-addons-for-gutenberg' ) }
								/>
							</VStack>
						</ToolsPanelItem>
						
						<ToolsPanelItem
							hasValue={ () => !!rotation }
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
								value={ rotation }
							/>
						</ToolsPanelItem>
					</ToolsPanel>
				) }
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
				label={ __( 'Icon Size', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { iconSize: undefined } ) }
				resetAllFilter={ () => ( {
					iconSize: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Icon Size', 'ultimate-addons-for-gutenberg' ) }
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
			textColorHover,
			backgroundColorHover,
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
 * The Editor settings for this block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = ( props ) => {
	const {		
		attributes : {
			listType
		}
	} = props;
	
	return (
		<>
			<BlockSettings { ...{ ...props } } />
			<ColorSettings { ...{ ...props } } />
			{ listType === 'unordered' && (
				<DimensionSettings { ...{ ...props } } />
			) }
		</>
	);
};

export default memo( Settings );