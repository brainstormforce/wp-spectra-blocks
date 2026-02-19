/**
 * External dependencies.
 */
import {
	InspectorControls,
	useSettings,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalVStack as VStack,
	ToggleControl,
	Notice,
} from '@wordpress/components';
import { select, dispatch } from '@wordpress/data';
import { memo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import IconPicker from '@spectra-components/icon-picker';
import InspectorColor from '@spectra-components/inspector-color';
import { TabBlockControls } from '@spectra/tabs/helpers';

/**
 * Element Sub-settings: General settings.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered block settings.
 */
const BlockSettings = memo( ( props ) => {
	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: { icon, iconPosition, flipForRTL },
	} = props;

	// Effect to update the toggles of all the icons in every tab button.
	useEffect( () => {
		// Destructure the required functions.
		const { getBlocksByClientId } = select( blockEditorStore );
		const { updateBlockAttributes } = dispatch( blockEditorStore );

		// Get the tab wrapper and loop through it.
		const children = getBlocksByClientId( clientId )[ 0 ].innerBlocks;
		children.forEach( ( child ) => {
			// If this child is not a tab wrapper, abandon ship.
			if ( 'spectra/tabs-child-tab-wrapper' !== child.name ) {
				return;
			}

			// Get all the innerblocks of this tab wrapper, and loop through them.
			const tabWrapperBlocks = child.innerBlocks;
			tabWrapperBlocks.forEach( ( tabWrapperBlock ) => {
				// If this is a tab button block, update the toggle attribute for it.
				if (
					'spectra/tabs-child-tab-button' === tabWrapperBlock.name
				) {
					// Note that we're not using the inverted value here, since this effect is only triggered after the update has been done for this toggle.
					updateBlockAttributes( tabWrapperBlock.clientId, {
						flipForRTL,
					} );
				}
			} );
		} );
	}, [ flipForRTL ] );

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Tab Icon', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						icon: undefined,
						iconPosition: undefined,
						flipForRTL: false,
					} );
				} }
				panelId={ clientId }
			>
				{ /* This tool panel item will require reset when any of these conditions are met:
				- The icon attribute is set. Default: undefined.
				- The flipForRTL attribute is not false. Default: false.
				 */ }
				<ToolsPanelItem
					hasValue={ () => !! icon || !! flipForRTL }
					label={ __( 'Icon', 'spectra-blocks' ) }
					onDeselect={ () =>
						setAttributes( {
							icon: undefined,
							flipForRTL: false,
						} )
					}
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
							onChange={ ( value ) =>
								setAttributes( { icon: value } )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							checked={ flipForRTL }
							label={ __(
								'Flip Icon for Right-to-Left',
								'spectra-blocks'
							) }
							onChange={ () =>
								setAttributes( { flipForRTL: ! flipForRTL } )
							}
							help={ __(
								"Enable this for your RTL visitors if you are using a direction-specific icon. Like 'Arrow Right', 'Chart Line', etc. ",
								'spectra-blocks'
							) }
						/>
					</VStack>
				</ToolsPanelItem>
				{ icon && (
					<ToolsPanelItem
						hasValue={ () =>
							!! iconPosition && 'after' !== iconPosition
						}
						label={ __(
							'Position',
							'spectra-blocks'
						) }
						onDeselect={ () =>
							setAttributes( { iconPosition: undefined } )
						}
						resetAllFilter={ () => ( {
							iconPosition: undefined,
						} ) }
						isShownByDefault
						panelId={ clientId }
					>
						<ToggleGroupControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __(
								'Position',
								'spectra-blocks'
							) }
							value={ iconPosition || 'after' }
							onChange={ ( value ) =>
								setAttributes( { iconPosition: value } )
							}
							isBlock
						>
							<ToggleGroupControlOption
								value="before"
								label="Left"
							/>
							<ToggleGroupControlOption
								value="after"
								label="Right"
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
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered block settings.
 */
const DimensionSettings = memo( ( props ) => {
	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: { size },
	} = props;

	// Use the available spacing units, or our units if they're not available.
	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="dimensions">
			<ToolsPanelItem
				hasValue={ () => !! size }
				label={ __( 'Icon Size', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { size: undefined } ) }
				resetAllFilter={ () => ( { size: undefined } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Tab Icon Size', 'spectra-blocks' ) }
					labelPosition="top"
					value={ size }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { size: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
			<div style={ { gridColumn: 'span 2' } }>
				<Notice
					status="info"
					isDismissible={ false }
				>
					{ __(
						'Note: Tab icon size will not work for "div" based tabs. This size setting only applies to button based tabs.',
						'spectra-blocks'
					) }
				</Notice>
			</div>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Color panel.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered block settings.
 */
const ColorSettings = memo( ( props ) => {
	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: {
			textColorHover,
			textColorActive,
			textColorActiveHover,
			iconColor,
			iconColorHover,
			iconColorActive,
			iconColorActiveHover,
			textColorSecondary,
			textColorHoverSecondary,
			backgroundColor,
			backgroundColorHover,
			backgroundGradient,
			backgroundColorActive,
			backgroundColorActiveHover,
			backgroundGradientHover,
			backgroundGradientActive,
			backgroundGradientActiveHover,
			backgroundColorSecondary,
			backgroundColorHoverSecondary,
			backgroundGradientSecondary,
			backgroundGradientHoverSecondary,
			backgroundColorTertiary,
			backgroundGradientTertiary,
			borderColorHover,
			borderColorActive,
			borderColorActiveHover
		},
	} = props;

	return (
		<>
		<InspectorColor
			settings={ [
				// Tab Colors.
				{
					colorValue: textColorHover,
					label: __( 'Tab Text Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
				{
					colorValue: textColorActive,
					label: __( 'Active Tab Text', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorActive: value } ),
					resetAllFilter: () => setAttributes( { textColorActive: undefined } ),
				},
				{
					colorValue: textColorActiveHover,
					label: __( 'Active Tab Text Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorActiveHover: value } ),
					resetAllFilter: () => setAttributes( { textColorActiveHover: undefined } ),
				},
				// Tab Icon Colors.
				{
					colorValue: iconColor,
					label: __( 'Tab Icon', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { iconColor: value } ),
					resetAllFilter: () => setAttributes( { iconColor: undefined } ),
				},
				{
					colorValue: iconColorHover,
					label: __( 'Tab Icon Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { iconColorHover: value } ),
					resetAllFilter: () => setAttributes( { iconColorHover: undefined } ),
				},
				{
					colorValue: iconColorActive,
					label: __( 'Active Tab Icon', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { iconColorActive: value } ),
					resetAllFilter: () => setAttributes( { iconColorActive: undefined } ),
				},
				{
					colorValue: iconColorActiveHover,
					label: __( 'Active Tab Icon Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { iconColorActiveHover: value } ),
					resetAllFilter: () => setAttributes( { iconColorActiveHover: undefined } ),
				},
				// Tab Border Colors.
				{
					colorValue: borderColorHover,
					label: __( 'Tab Border Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { borderColorHover: value } ),
					resetAllFilter: () => setAttributes( { borderColorHover: undefined } ),
				},
				{
					colorValue: borderColorActive,
					label: __( 'Active Tab Border', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { borderColorActive: value } ),
					resetAllFilter: () => setAttributes( { borderColorActive: undefined } ),
				},
				{
					colorValue: borderColorActiveHover,
					label: __( 'Active Tab Border Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { borderColorActiveHover: value } ),
					resetAllFilter: () => setAttributes( { borderColorActiveHover: undefined } ),
				},
				{
					colorValue: backgroundColor,
					gradientValue: backgroundGradient,
					label: __( 'Tab Background', 'spectra-blocks' ),
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
					label: __( 'Tab Background Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorHover: undefined,
						backgroundGradientHover: undefined,
					} ),
				},
				{
					colorValue: backgroundColorActive,
					gradientValue: backgroundGradientActive,
					label: __( 'Active Tab Background', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorActive: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientActive: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorActive: undefined,
						backgroundGradientActive: undefined,
					} ),
				},
				{
					colorValue: backgroundColorActiveHover,
					gradientValue: backgroundGradientActiveHover,
					label: __( 'Active Tab BG Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorActiveHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientActiveHover: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorActiveHover: undefined,
						backgroundGradientActiveHover: undefined,
					} ),
				},
				// Tab Panel Colors.
				{
					colorValue: textColorSecondary,
					label: __( 'Panel Text', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorSecondary: value } ),
					resetAllFilter: () => setAttributes( { textColorSecondary: undefined } ),
				},
				{
					colorValue: textColorHoverSecondary,
					label: __( 'Panel Text Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHoverSecondary: value } ),
					resetAllFilter: () => setAttributes( { textColorHoverSecondary: undefined } ),
				},
				{
					colorValue: backgroundColorSecondary,
					gradientValue: backgroundGradientSecondary,
					label: __( 'Panel Background', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorSecondary: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientSecondary: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorSecondary: undefined,
						backgroundGradientSecondary: undefined,
					} ),
				},
				{
					colorValue: backgroundColorHoverSecondary,
					gradientValue: backgroundGradientHoverSecondary,
					label: __( 'Panel Background Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHoverSecondary: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHoverSecondary: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorHoverSecondary: undefined,
						backgroundGradientHoverSecondary: undefined,
					} ),
				},
				// Overall Block Colors.
				{
					colorValue: backgroundColorTertiary,
					gradientValue: backgroundGradientTertiary,
					label: __( 'Overall Background', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorTertiary: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientTertiary: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorTertiary: undefined,
						backgroundGradientTertiary: undefined,
					} ),
				},
			] }
			panelId={ clientId }
		/>
		<InspectorControls group="color">
			<div style={ { gridColumn: 'span 2' } }>
				<Notice
					status="info"
					isDismissible={ false }
				>
					{ __(
						'Note: Tab icon colors will not work for "div" based tabs. These color settings only apply to button based tabs.',
						'spectra-blocks'
					) }
				</Notice>
			</div>
		</InspectorControls>
		</>
	);
} );

/**
 * The Editor settings.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered settings.
 */
const Settings = ( props ) => (
	<>
		<TabBlockControls { ...{ ...props, removalType: 'tabs' } } />
		<BlockSettings { ...{ ...props } } />
		<DimensionSettings { ...{ ...props } } />
		<ColorSettings { ...{ ...props } } />
	</>
);

export default memo( Settings );
