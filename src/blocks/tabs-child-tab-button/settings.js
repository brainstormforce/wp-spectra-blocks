/**
 * External dependencies.
 */
import { memo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useSettings,
	BlockControls,
} from '@wordpress/block-editor';
import {
	__experimentalInputControl as InputControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	AnglePickerControl,
	ToggleControl,
	__experimentalVStack as VStack,
	ToolbarGroup,
	ToolbarButton,
} from '@wordpress/components';
import { createBlock } from '@wordpress/blocks';
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import IconPicker from '@spectra-components/icon-picker';
import InspectorColor from '@spectra-components/inspector-color';
import { TabBlockControls } from '@spectra-blocks/tabs/helpers';
import { helperIcons } from '@spectra-helpers/block-icons';

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
		context: {
			'spectra/tabs/icon': tabsIcon,
			'spectra/tabs/iconPosition': tabsIconPosition,
		},
		attributes: {
			text,
			showText,
			icon,
			iconPosition,
			flipForRTL,
			rotation,
		},
	} = props;

	const iconInUse = icon || tabsIcon;

	// Update showText to true if the icon is removed.
	useEffect( () => {
		if ( ! iconInUse ) {
			setAttributes( { showText: true } );
		}
	}, [ icon, tabsIcon ] );

	// Render the settings.
	return (
		<>
			<InspectorControls group="settings">
				<ToolsPanel
					label={ __( 'Tab', 'ultimate-addons-for-gutenberg' ) }
					resetAll={ () => {
						setAttributes( {
							icon: undefined,
							showText: true,
							iconPosition: undefined,
							flipForRTL: false,
							rotation: undefined,
						} );
					} }
					panelId={ clientId }
				>
					{ /* This tool panel item will require reset when any of these conditions are met:
					- The icon attribute is set. Default: undefined.
					- The showText attribute is not true. Default: true.
					- The flipForRTL attribute is true. Default: false.
					*/ }
					<ToolsPanelItem
						hasValue={ () =>
							!! icon || ! showText || !! flipForRTL
						}
						label={ __( 'Icon', 'ultimate-addons-for-gutenberg' ) }
						onDeselect={ () =>
							setAttributes( {
								icon: undefined,
								showText: true,
								flipForRTL: false,
								rotation: undefined,
							} )
						}
						resetAllFilter={ () => ( {
							icon: undefined,
							showText: true,
							flipForRTL: false,
							rotation: undefined,
						} ) }
						isShownByDefault
						panelId={ clientId }
					>
						<VStack spacing={ 4 }>
							<IconPicker
								label={ __(
									'Icon',
									'ultimate-addons-for-gutenberg'
								) }
								value={ icon }
								onChange={ ( value ) =>
									setAttributes( { icon: value } )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={ flipForRTL }
								disabled={ ! iconInUse }
								label={ __(
									'Flip Icon for Right-To-Left',
									'ultimate-addons-for-gutenberg'
								) }
								onChange={ () =>
									setAttributes( {
										flipForRTL: ! flipForRTL,
									} )
								}
								help={ __(
									"Enable this for your RTL visitors if you are using a direction-specific icon. Like 'Arrow Right', 'Chart Line', etc. ",
									'ultimate-addons-for-gutenberg'
								) }
							/>
							<ToggleControl
								checked={ showText }
								label={ __(
									'Show Text',
									'ultimate-addons-for-gutenberg'
								) }
								disabled={ ! iconInUse }
								onChange={ () =>
									setAttributes( { showText: ! showText } )
								}
							/>
							{ ! showText && (
								<InputControl
									__next40pxDefaultSize
									label={ __(
										'Aria Label',
										'ultimate-addons-for-gutenberg'
									) }
									value={ text }
									onChange={ ( value ) =>
										setAttributes( { text: value } )
									}
									help={ __(
										"It's best to have an aria label if your tab is just an icon.",
										'ultimate-addons-for-gutenberg'
									) }
								/>
							) }
						</VStack>
					</ToolsPanelItem>
					{ /* This tool panel item will require reset when any of these conditions are met:
					- The rotation attribute is set. Default: undefined.
					*/ }
					{ icon && (
						<ToolsPanelItem
							hasValue={ () => !! rotation }
							label={ __(
								'Rotation',
								'ultimate-addons-for-gutenberg'
							) }
							onDeselect={ () =>
								setAttributes( { rotation: undefined } )
							}
							resetAllFilter={ () => ( {
								rotation: undefined,
							} ) }
							panelId={ clientId }
						>
							<AnglePickerControl
								label={ __(
									'Rotation',
									'ultimate-addons-for-gutenberg'
								) }
								onChange={ ( value ) => {
									setAttributes( { rotation: value } );
								} }
								value={ rotation }
							/>
						</ToolsPanelItem>
					) }
					{ /* This tool panel item will require reset when any of these conditions are met:
					- The iconPosition attribute is set, and is not set to after, which is the default in style. Default: undefined.
					*/ }
					{ iconInUse && showText && (
						<ToolsPanelItem
							hasValue={ () =>
								!! iconPosition && 'after' !== iconPosition
							}
							label={ __(
								'Position',
								'ultimate-addons-for-gutenberg'
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
									'ultimate-addons-for-gutenberg'
								) }
								value={
									iconPosition || tabsIconPosition || 'after'
								}
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
		</>
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
		context: { 'spectra/tabs/icon': tabsIcon },
		attributes: {
			icon,
			textColorHover,
			textColorActive,
			textColorActiveHover,
			iconColor,
			iconColorHover,
			iconColorActive,
			iconColorActiveHover,
			backgroundColorHover,
			backgroundColorActive,
			backgroundColorActiveHover,
			backgroundGradientHover,
			backgroundGradientActive,
			backgroundGradientActiveHover,
			borderColorHover,
			borderColorActive,
			borderColorActiveHover,
		},
	} = props;

	return (
		<>
			<InspectorColor
				settings={ [
					{
						colorValue: textColorHover,
						label: __(
							'Text Hover',
							'ultimate-addons-for-gutenberg'
						),
						onColorChange: ( value ) =>
							setAttributes( { textColorHover: value } ),
						resetAllFilter: () =>
							setAttributes( { textColorHover: undefined } ),
					},
					{
						colorValue: backgroundColorHover,
						gradientValue: backgroundGradientHover,
						label: __(
							'Background Hover',
							'ultimate-addons-for-gutenberg'
						),
						onColorChange: ( value ) =>
							setAttributes( { backgroundColorHover: value } ),
						onGradientChange: ( value ) =>
							setAttributes( { backgroundGradientHover: value } ),
						resetAllFilter: () =>
							setAttributes( {
								backgroundColorHover: undefined,
								backgroundGradientHover: undefined,
							} ),
					},
					{
						colorValue: textColorActive,
						label: __(
							'Active Text',
							'ultimate-addons-for-gutenberg'
						),
						onColorChange: ( value ) =>
							setAttributes( { textColorActive: value } ),
						resetAllFilter: () =>
							setAttributes( { textColorActive: undefined } ),
					},
					{
						colorValue: textColorActiveHover,
						label: __(
							'Active Text Hover/Focus',
							'ultimate-addons-for-gutenberg'
						),
						onColorChange: ( value ) =>
							setAttributes( { textColorActiveHover: value } ),
						resetAllFilter: () =>
							setAttributes( {
								textColorActiveHover: undefined,
							} ),
					},
				] }
				panelId={ clientId }
			/>
			<InspectorColor
				settings={[
					{
						colorValue: backgroundColorActive,
						gradientValue: backgroundGradientActive,
						label: __(
							'Active Background',
							'ultimate-addons-for-gutenberg'
						),
						onColorChange: ( value ) =>
							setAttributes( { backgroundColorActive: value } ),
						onGradientChange: ( value ) =>
							setAttributes( {
								backgroundGradientActive: value,
							} ),
						resetAllFilter: () =>
							setAttributes( {
								backgroundColorActive: undefined,
								backgroundGradientActive: undefined,
							} ),
					},
					{
						colorValue: backgroundColorActiveHover,
						gradientValue: backgroundGradientActiveHover,
						label: __(
							'Active BG Hover/Focus',
							'ultimate-addons-for-gutenberg'
						),
						onColorChange: ( value ) =>
							setAttributes( {
								backgroundColorActiveHover: value,
							} ),
						onGradientChange: ( value ) =>
							setAttributes( {
								backgroundGradientActiveHover: value,
							} ),
						resetAllFilter: () =>
							setAttributes( {
								backgroundColorActiveHover: undefined,
								backgroundGradientActiveHover: undefined,
							} ),
					},
				]}
				panelId={clientId}
			/>
			{ ( icon || tabsIcon ) && (
				<InspectorColor
					settings={ [
						{
							colorValue: iconColor,
							label: __(
								'Icon',
								'ultimate-addons-for-gutenberg'
							),
							onColorChange: ( value ) =>
								setAttributes( { iconColor: value } ),
							resetAllFilter: () =>
								setAttributes( { iconColor: undefined } ),
						},
						{
							colorValue: iconColorHover,
							label: __(
								'Icon Hover',
								'ultimate-addons-for-gutenberg'
							),
							onColorChange: ( value ) =>
								setAttributes( { iconColorHover: value } ),
							resetAllFilter: () =>
								setAttributes( { iconColorHover: undefined } ),
						},
						{
							colorValue: iconColorActive,
							label: __(
								'Active Icon',
								'ultimate-addons-for-gutenberg'
							),
							onColorChange: ( value ) =>
								setAttributes( { iconColorActive: value } ),
							resetAllFilter: () =>
								setAttributes( { iconColorActive: undefined } ),
						},
						{
							colorValue: iconColorActiveHover,
							label: __(
								'Active Icon Hover/Focus',
								'ultimate-addons-for-gutenberg'
							),
							onColorChange: ( value ) =>
								setAttributes( {
									iconColorActiveHover: value,
								} ),
							resetAllFilter: () =>
								setAttributes( {
									iconColorActiveHover: undefined,
								} ),
						},
					] }
					panelId={ clientId }
				/>
			) }
			<InspectorColor
				settings={ [
					{
						colorValue: borderColorActive,
						label: __( 'Active Border', 'ultimate-addons-for-gutenberg' ),
						onColorChange: ( value ) => setAttributes( { borderColorActive: value } ),
						resetAllFilter: () => setAttributes( { borderColorActive: undefined } ),
					},
					{
						colorValue: borderColorActiveHover,
						label: __( 'Active Border Hover/Focus', 'ultimate-addons-for-gutenberg' ),
						onColorChange: ( value ) => setAttributes( { borderColorActiveHover: value } ),
						resetAllFilter: () => setAttributes( { borderColorActiveHover: undefined } ),
					},
					{
						colorValue: borderColorHover,
						label: __( 'Border Hover', 'ultimate-addons-for-gutenberg' ),
						onColorChange: ( value ) => setAttributes( { borderColorHover: value } ),
						resetAllFilter: () => setAttributes( { borderColorHover: undefined } ),
					},
				] }
				panelId={ clientId }
			/>
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
const IconStyleSettings = memo( ( props ) => {
	// Destructure the requierd props.
	const {
		clientId,
		setAttributes,
		attributes: { size, gap },
	} = props;

	// Fetch spacing units, fallback to default
	const [ availableUnits ] = useSettings( 'spacing.units' ) || [];
	const units = useCustomUnits( {
		availableUnits: availableUnits.length
			? availableUnits
			: [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="dimensions">
			<ToolsPanelItem
				hasValue={ () => !! size }
				label={ __( 'Icon size', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { size: undefined } ) }
				resetAllFilter={ () => ( {
					size: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Icon size', 'ultimate-addons-for-gutenberg' ) }
					labelPosition="top"
					value={ size }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { size: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () => !! gap }
				label={ __( 'Text-Icon Gap', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { gap: undefined } ) }
				resetAllFilter={ () => ( {
					gap: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __(
						'Text-Icon Gap',
						'ultimate-addons-for-gutenberg'
					) }
					labelPosition="top"
					value={ gap }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { gap: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
		</InspectorControls>
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
		context: { 'spectra/tabs/icon': tabsIcon },
		attributes: { icon },
	} = props;

	const { clientId } = props;
	const { getBlock } = select( 'core/block-editor' );
	const { replaceBlock } = dispatch( 'core/block-editor' );

	/**
	 * Transform the current tab button to a tab trigger.
	 */
	const transformToTabTrigger = () => {
		const currentBlock = getBlock( clientId );
		const { 
			icon: currentIcon, 
			iconPosition,
			text, 
			style,
			showText, 
			borderColor,
			textColor,
			styleColorText,
			textColorHover,
			textColorActive,
			textColorActiveHover,
			backgroundColor,
			backgroundColorHover,
			backgroundColorActive,
			backgroundColorActiveHover,
			backgroundGradient,
			backgroundGradientHover,
			backgroundGradientActive,
			backgroundGradientActiveHover,
			borderColorHover,
			borderColorActive,
			borderColorActiveHover 
		} = currentBlock.attributes;

		// Create inner blocks based on content
		const innerBlocks = [];

		// Helper function to create icon block
		const createIconBlock = () => {
			const iconBlock = createBlock( 'spectra/icons', {} );
			const iconChild = createBlock( 'spectra/icon', {
				icon: currentIcon,
			} );
			iconBlock.innerBlocks = [ iconChild ];
			return iconBlock;
		};

		// Helper function to create content block
		const createContentBlock = () => {
			return createBlock( 'spectra/content', {
				text,
				tagName: 'span',
			} );
		};

		// Add blocks based on conditions
		if ( showText && text && currentIcon ) {
			// Both text and icon
			if ( iconPosition === 'before' ) {
				innerBlocks.push( createIconBlock() );
				innerBlocks.push( createContentBlock() );
			} else {
				// Default to 'after' if iconPosition is 'after' or not set
				innerBlocks.push( createContentBlock() );
				innerBlocks.push( createIconBlock() );
			}
		} else if ( showText && text ) {
			// Only text
			innerBlocks.push( createContentBlock() );
		} else if ( currentIcon ) {
			// Only icon
			innerBlocks.push( createIconBlock() );
		}

		// Create a new tab trigger block
		const newTabTrigger = createBlock(
			'spectra/tabs-child-tab-trigger',
			{
				style: {
					...style,
				},
				borderColor,
				textColor,
				styleColorText,
				textColorHover,
				textColorActive,
				textColorActiveHover,
				backgroundColor,
				backgroundColorHover,
				backgroundColorActive,
				backgroundColorActiveHover,
				backgroundGradient,
				backgroundGradientHover,
				backgroundGradientActive,
				backgroundGradientActiveHover,
				borderColorHover,
				borderColorActive,
				borderColorActiveHover
			},
			innerBlocks
		);

		// Replace the current block with the new tab trigger
		replaceBlock( clientId, newTabTrigger );
	};

	return (
		<>
			<TabBlockControls { ...{ ...props, removalType: 'tab' } } />
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon={ helperIcons.tabDivToButtonSwitch() }
						label={ __(
							'Transform to Trigger Container',
							'ultimate-addons-for-gutenberg'
						) }
						onClick={ transformToTabTrigger }
					/>
				</ToolbarGroup>
			</BlockControls>
			<BlockSettings { ...{ ...props } } />
			<ColorSettings { ...{ ...props } } />
			{ ( icon || tabsIcon ) && (
				<IconStyleSettings { ...{ ...props } } />
			) }
		</>
	);
};

export default memo( Settings );
