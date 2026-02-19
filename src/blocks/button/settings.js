/**
 * External dependencies.
 */
import { memo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useSettings,
} from '@wordpress/block-editor';
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalInputControl as InputControl,
	AnglePickerControl,
	ToggleControl,
	__experimentalVStack as VStack,
	ColorPalette,
	BaseControl,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import IconPicker from '@spectra-components/icon-picker';
import InspectorColor from '@spectra-components/inspector-color';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';
import BlockControlLink from '@spectra-components/block-control-link';

/**
 * Element Sub-settings: Border & Shadow settings.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BorderSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes: { shadowHover, borderHover } } = props;

	// Get color palette from theme settings.
	const [ colors ] = useSettings( 'color.palette' );

	// Default shadow values.
	const defaultShadow = {
		x: 0,
		y: 4,
		blur: 8,
		spread: 0,
		color: 'rgba(0,0,0,0.2)',
	};

	// Current shadow values with fallback to defaults.
	const shadow = shadowHover || defaultShadow;

	// Update shadow property.
	const updateShadow = ( property, value ) => {
		setAttributes( { 
			shadowHover: {
				...shadow,
				[property]: value,
			}
		} );
	};

	return (
		<InspectorControls group="border">
			<ToolsPanelItem
				hasValue={ () => !! borderHover }
				label={ __( 'Border Hover', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { borderHover: undefined } ) }
				resetAllFilter={ () => ( {
					borderHover: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<VStack spacing={ 4 }>
					<ToggleControl
						checked={ !! borderHover }
						label={ __( 'Enable Border Hover', 'spectra-blocks' ) }
						onChange={ ( value ) => {
							if ( value ) {
								setAttributes( { borderHover: { color: undefined } } );
							} else {
								setAttributes( { borderHover: undefined } );
							}
						} }
						help={ __( 'Enable border hover to customize the border color on hover.', 'spectra-blocks' ) }
					/>
					{ borderHover && (
						<VStack spacing={ 4 }>
							<BaseControl
								label={ __( 'Border Hover Color', 'spectra-blocks' ) }
								help={ __( 'Set the border color that appears when hovering over the button. The border width and style will match the normal border.', 'spectra-blocks' ) }
								id="wp-components-base-control-0"
							>
								<ColorPalette
									colors={ colors || [] }
									value={ borderHover?.color }
									onChange={ ( color ) => {
										setAttributes( { 
											borderHover: {
												...borderHover,
												color,
											}
										} );
									} }
									enableAlpha={ true }
									clearable={ true }
								/>
							</BaseControl>
						</VStack>
					) }
				</VStack>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () => !! shadowHover }
				label={ __( 'Shadow Hover', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { shadowHover: undefined } ) }
				resetAllFilter={ () => ( {
					shadowHover: undefined,
				} ) }
				isShownByDefault
				panelId={ clientId }
			>
				<VStack spacing={ 4 }>
					<ToggleControl
						checked={ !! shadowHover }
						label={ __( 'Enable Shadow Hover', 'spectra-blocks' ) }
						onChange={ ( value ) => {
							if ( value ) {
								setAttributes( { shadowHover: defaultShadow } );
							} else {
								setAttributes( { shadowHover: undefined } );
							}
						} }
						help={ __( 'Enable shadow hover to customize the shadow properties.', 'spectra-blocks' ) }
					/>
					{ shadowHover && (
						<VStack spacing={ 4 }>
							<ColorPalette
								colors={ colors || [] }
								value={ shadow.color }
								onChange={ ( value ) => updateShadow( 'color', value ) }
								enableAlpha={ true }
								clearable={ true }
							/>
							<DebouncedRangeControl
								label={ __( 'Shadow Hover X Offset', 'spectra-blocks' ) }
								value={ shadow.x }
								onChange={ value => updateShadow( 'x', value ) }
								min={ -50 }
								max={ 50 }
								debounceDelay={ 150 }
							/>
							<DebouncedRangeControl
								label={ __( 'Shadow Hover Y Offset', 'spectra-blocks' ) }
								value={ shadow.y }
								onChange={ value => updateShadow( 'y', value ) }
								min={ -50 }
								max={ 50 }
								debounceDelay={ 150 }
							/>
							<DebouncedRangeControl
								label={ __( 'Shadow Hover Blur Radius', 'spectra-blocks' ) }
								value={ shadow.blur }
								onChange={ value => updateShadow( 'blur', value ) }
								min={ 0 }
								max={ 100 }
								debounceDelay={ 150 }
							/>
							<DebouncedRangeControl
								label={ __( 'Shadow Hover Spread', 'spectra-blocks' ) }
								value={ shadow.spread }
								onChange={ value => updateShadow( 'spread', value ) }
								min={ -50 }
								max={ 50 }
								debounceDelay={ 150 }
							/>
						</VStack>
					) }
				</VStack>
			</ToolsPanelItem>
		</InspectorControls>
	);
} );

/**
 * Element Sub-settings: General settings, but based on the parent block.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BlockSettings = memo( ( props ) => {

	// Destructure the required props.
	const { clientId, setAttributes, attributes } = props;

	const {
		showText,
		text,
		icon,
		iconPosition,
		flipForRTL,
		rotation,
		linkURL,
		linkTarget,
		linkRel,
		showIconOnHover,
		hoverIcon,
		hoverIconPosition,
		hoverIconRotation,
		hoverIconFlipForRTL,
		hoverIconAriaLabel,
	} = attributes;

	// Updated showText to true if icon is removed.
	useEffect( () => {
		if ( ! icon ) {
			setAttributes( { showText: true } );
		}
	}, [ icon ] );

	// Function to strip tags for aria-label when showText is disabled
	const stripTags = ( str ) => {
		return typeof str === 'string' ? str.replace( /<[^>]*>/g, '' ) : '';
	};

	// Render the settings.
	return (
		<>
			<BlockControlLink
				attributes={ attributes }
				setAttributes={ setAttributes }
				url={ {
					label: 'linkURL',
					value: linkURL,
				} }
				target={ {
					label: 'linkTarget',
					value: linkTarget,
				} }
				rel={ {
					label: 'linkRel',
					value: linkRel,
				} }
			/>
			<InspectorControls group='settings'>
				<ToolsPanel
					label={ __( 'Button', 'spectra-blocks' ) }
					resetAll={ () => {
						setAttributes( {
							icon: undefined,
							showText: true,
							iconPosition: undefined,
							flipForRTL: false,
							rotation: undefined,
							showIconOnHover: false,
							hoverIcon: undefined,
							hoverIconPosition: 'right',
							hoverIconRotation: 0,
						} )
					} }
					panelId={ clientId }
				>
					{/* This tool panel item will require reset when any of these conditions are met:
					- The icon attribute is set. Default: undefined.
					- The showText attribute is not true. Default: true.
					- The flipForRTL attribute is true. Default: false.
					*/}
					<ToolsPanelItem
						hasValue={ () => ( !! icon || ! showText || !! flipForRTL ) }
						label={ __( 'Icon', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( {
							icon: undefined,
							showText: true,
							flipForRTL: false,
							rotation: undefined,
						} ) }
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
								label={ __( 'Icon', 'spectra-blocks' ) }
								value={ icon }
								onChange={ ( value ) => setAttributes( { icon: value } ) }
							/>
							<ToggleControl
								checked={ showText }
								label={ __( 'Show Text', 'spectra-blocks' ) }
								disabled={ !icon }
								onChange={ ( value ) => {
									// If the toggle is turned off, strip all tags from the text before proceeding.
									if ( !value ) {
										const updatedText = stripTags( text );
										setAttributes( {
											text: updatedText,
											showText: value,
										} );
									} else {
										setAttributes( { showText: value } );
									}
								} }
							/>
							{ ! showText && (
								<InputControl
									__next40pxDefaultSize
									label={ __( 'Aria Label', 'spectra-blocks' ) }
									value={ text }
									onChange={ ( value ) => setAttributes( { text: value } ) }
									help={ __( 'It\'s best to have an aria label if your button is just an icon.', 'spectra-blocks' ) }
								/>
							) }
							{ icon && (
								<ToggleControl
									__nextHasNoMarginBottom
									checked={ flipForRTL }
									label={ __( 'Flip Icon for Right-To-Left', 'spectra-blocks' ) }
									onChange={ () => setAttributes( { flipForRTL: ! flipForRTL } ) }
									help={ __( 'Enable this for your RTL visitors if you are using a direction-specific icon. Like \'Arrow Right\', \'Chart Line\', etc. ', 'spectra-blocks' ) }
								/>
							) }
						</VStack>
					</ToolsPanelItem>
					{/* This tool panel item will require reset when any of these conditions are met:
					- The rotation attribute is set. Default: undefined.
					*/}
					{ ( icon && showText ) && (
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
					) }
					{/* This tool panel item will require reset when any of these conditions are met:
					- The iconPosition attribute is set. Default: undefined.
					*/}
					{ ( icon && showText ) && (
						<ToolsPanelItem
							hasValue={ () => !! iconPosition }
							label={ __( 'Position', 'spectra-blocks' ) }
							onDeselect={ () => setAttributes( { iconPosition: undefined  } ) }
							resetAllFilter={ () => ( {
								iconPosition: undefined,
							} ) }
							isShownByDefault
							panelId={ clientId }
						>
							<ToggleGroupControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __( 'Position', 'spectra-blocks' ) }
								value={ iconPosition }
								onChange={ ( value ) => setAttributes( { iconPosition: value } ) }
								isBlock
							>
								<ToggleGroupControlOption value="before" label="Left" />
								<ToggleGroupControlOption value="after" label="Right" />
							</ToggleGroupControl>
						</ToolsPanelItem>
					) }

				</ToolsPanel>
				{/* Separate ToolsPanel for Hover Icon Settings */}
				<ToolsPanel
					label={ __( 'Hover Icon', 'spectra-blocks' ) }
					resetAll={ () => {
						setAttributes( {
							showIconOnHover: false,
							hoverIcon: undefined,
							hoverIconPosition: 'right',
							hoverIconRotation: 0,
							hoverIconFlipForRTL: false,
							hoverIconAriaLabel: undefined,
						} )
					} }
					panelId={ clientId + '-hover-icon' }
				>
					<ToolsPanelItem
						hasValue={ () => !! showIconOnHover }
						label={ __( 'Enable Hover Icon', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { showIconOnHover: false } ) }
						resetAllFilter={ () => ( { showIconOnHover: false } ) }
						isShownByDefault
						panelId={ clientId + '-hover-icon' }
					>
						<ToggleControl
							checked={ showIconOnHover }
							label={ __( 'Show icon on hover', 'spectra-blocks' ) }
							onChange={ ( value ) => setAttributes( { showIconOnHover: value } ) }
							help={ __( 'Shows an icon when the button is hovered.', 'spectra-blocks' ) }
						/>
					</ToolsPanelItem>
					{ showIconOnHover && (
						<ToolsPanelItem
							hasValue={ () => !! hoverIcon }
							label={ __( 'Icon Selection', 'spectra-blocks' ) }
							onDeselect={ () => setAttributes( { hoverIcon: undefined } ) }
							resetAllFilter={ () => ( { hoverIcon: undefined } ) }
							isShownByDefault
							panelId={ clientId + '-hover-icon' }
						>
							<IconPicker
								label={ __( 'Hover Icon', 'spectra-blocks' ) }
								value={ hoverIcon }
								onChange={ ( value ) => setAttributes( { hoverIcon: value } ) }
							/>
						</ToolsPanelItem>
					) }
					{ showIconOnHover && hoverIcon && showText && (
						<ToolsPanelItem
							hasValue={ () => !! hoverIconPosition }
							label={ __( 'Position', 'spectra-blocks' ) }
							onDeselect={ () => setAttributes( { hoverIconPosition: 'right' } ) }
							resetAllFilter={ () => ( { hoverIconPosition: 'right' } ) }
							isShownByDefault
							panelId={ clientId + '-hover-icon' }
						>
							<ToggleGroupControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __( 'Hover Icon Position', 'spectra-blocks' ) }
								value={ hoverIconPosition }
								onChange={ ( value ) => setAttributes( { hoverIconPosition: value } ) }
								isBlock
							>
								<ToggleGroupControlOption value="left" label={ __( 'Left', 'spectra-blocks' ) } />
								<ToggleGroupControlOption value="right" label={ __( 'Right', 'spectra-blocks' ) } />
							</ToggleGroupControl>
						</ToolsPanelItem>
					) }
					{ showIconOnHover && hoverIcon && (
						<ToolsPanelItem
							hasValue={ () => !! hoverIconRotation }
							label={ __( 'Rotation', 'spectra-blocks' ) }
							onDeselect={ () => setAttributes( { hoverIconRotation: 0 } ) }
							resetAllFilter={ () => ( { hoverIconRotation: 0 } ) }
							isShownByDefault
							panelId={ clientId + '-hover-icon' }
						>
							<AnglePickerControl
								label={ __( 'Hover Icon Rotation', 'spectra-blocks' ) }
								onChange={ ( value ) => {
									setAttributes( { hoverIconRotation: value } );
								} }
								value={ hoverIconRotation }
							/>
						</ToolsPanelItem>
					) }
					{ showIconOnHover && hoverIcon && (
						<ToolsPanelItem
							hasValue={ () => !! hoverIconFlipForRTL }
							label={ __( 'RTL Support', 'spectra-blocks' ) }
							onDeselect={ () => setAttributes( { hoverIconFlipForRTL: false } ) }
							resetAllFilter={ () => ( { hoverIconFlipForRTL: false } ) }
							isShownByDefault
							panelId={ clientId + '-hover-icon' }
						>
							<ToggleControl
								__nextHasNoMarginBottom
								checked={ hoverIconFlipForRTL }
								label={ __( 'Flip Hover Icon for Right-To-Left', 'spectra-blocks' ) }
								onChange={ () => setAttributes( { hoverIconFlipForRTL: ! hoverIconFlipForRTL } ) }
								help={ __( 'Enable this for your RTL visitors if you are using a direction-specific hover icon. Like \'Arrow Right\', \'Chart Line\', etc. ', 'spectra-blocks' ) }
							/>
						</ToolsPanelItem>
					) }
					{ showIconOnHover && hoverIcon && ! showText && (
						<ToolsPanelItem
							hasValue={ () => !! hoverIconAriaLabel }
							label={ __( 'Aria Label', 'spectra-blocks' ) }
							onDeselect={ () => setAttributes( { hoverIconAriaLabel: undefined } ) }
							resetAllFilter={ () => ( { hoverIconAriaLabel: undefined } ) }
							isShownByDefault
							panelId={ clientId + '-hover-icon' }
						>
							<InputControl
								label={ __( 'Hover Icon Aria Label', 'spectra-blocks' ) }
								value={ hoverIconAriaLabel || '' }
								onChange={ ( value ) => setAttributes( { hoverIconAriaLabel: value } ) }
								help={ __( 'Provide a descriptive label for the hover icon when text is disabled to improve accessibility.', 'spectra-blocks' ) }
								placeholder={ __( 'e.g., Learn more', 'spectra-blocks' ) }
							/>
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
		attributes: {
			icon,
			textColorHover,
			backgroundColorHover,
			iconColor,
			iconColorHover,
			showIconOnHover,
			backgroundGradientHover,
		},
	} = props;

	return (
		<>
			<InspectorColor
				settings={ [
					{
						colorValue: textColorHover,
						label: __( 'Text Hover', 'spectra-blocks' ),
						onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
						resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
					},
				] }
				panelId={ clientId }
			/>
			<InspectorColor
				settings={[
					{
						colorValue: backgroundColorHover,
						gradientValue: backgroundGradientHover,
						label: __( 'Background Hover', 'spectra-blocks' ),
						onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
						onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
						resetAllFilter: () => setAttributes( {
							backgroundColor: undefined,
							backgroundGradient: undefined,
						} ),
					},
				]}
				panelId={clientId}
			/>
			
			{/* Before showing the background color settings, add the icon color settings if required. */}
			{ icon && (
				<InspectorColor
					settings={ [
						{
							colorValue: iconColor,
							label: __( 'Icon', 'spectra-blocks' ),
							onColorChange: ( value ) => setAttributes( { iconColor: value } ),
							resetAllFilter: () => setAttributes( { iconColor: undefined } ),
						},
					] }
					panelId={ clientId }
				/>
			) }
			{/* Icon Hover Color Settings */}
			{ showIconOnHover && (	
				<InspectorColor
					settings={ [
						{
							colorValue: iconColorHover,
							label: __( 'Icon Hover', 'spectra-blocks' ),
							onColorChange: ( value ) => setAttributes( { iconColorHover: value } ),
							resetAllFilter: () => setAttributes( { iconColorHover: undefined } ),
						},
					] }
					panelId={ clientId }
				/>
			) }

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
		attributes: {
			size,
			gap,
		},
	} = props;

	// Fetch spacing units, fallback to default
	const [ availableUnits ] = useSettings( 'spacing.units' ) || [];
	const units = useCustomUnits( {
		availableUnits: availableUnits.length ? availableUnits : [ 'px', '%', 'vw', 'em', 'rem' ],
	} );

	return (
		<InspectorControls group="dimensions">
			<ToolsPanelItem
				hasValue={ () => !! size }
				label={ __( 'Icon size', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { size: undefined } ) }
				resetAllFilter={ () => ( {
					size: undefined,
				} ) }
				isShownByDefault
				panelId={clientId}
			>
				<UnitControl
				    __next40pxDefaultSize
					label={ __( 'Icon size', 'spectra-blocks' ) }
					labelPosition="top"
					value={ size }
					min={ 0 }
					onChange={ ( value ) => setAttributes( { size: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () => !! gap }
				label={ __( 'Text-Icon Gap', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { gap: undefined } ) }
				resetAllFilter={ () => ( {
					gap: undefined,
				} ) }
				isShownByDefault
				panelId={clientId}
			>
				<UnitControl
				    __next40pxDefaultSize
					label={ __( 'Text-Icon Gap', 'spectra-blocks' ) }
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

	const { attributes: {
		icon,
	} } = props;

	return (
		<>
			<BlockSettings { ...{ ...props } } />
			<ColorSettings { ...{ ...props } } />
			<BorderSettings { ...{ ...props } } />
			{ icon && ( <IconStyleSettings { ...{ ...props } } /> ) }
	    </>
    );
}

export default memo( Settings );
