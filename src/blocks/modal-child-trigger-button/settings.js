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
	ToggleControl,
	__experimentalVStack as VStack,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import IconPicker from '@spectra-components/icon-picker';
import InspectorColor from '@spectra-components/inspector-color';

/**
 * Element Sub-settings: General settings, but based on the parent block.
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
			showText,
			text,
			icon,
			iconPosition,
			flipForRTL,
			ariaLabel,
		},
	} = props;

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
		<InspectorControls group='settings'>
			<ToolsPanel
				label={ __( 'Button', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( {
						icon: undefined,
						showText: true,
						iconPosition: undefined,
						flipForRTL: false,
						ariaLabel: undefined,
					} )
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The icon attribute is set. Default: undefined.
				- The showText attribute is not true. Default: true.
				- The flipForRTL attribute is true. Default: false.
				- The ariaLabel attribute is set. Default: undefined.
				*/}
				<ToolsPanelItem
					hasValue={ () => ( !! icon || ! showText || !! flipForRTL || !! ariaLabel ) }
					label={ __( 'Icon', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( {
						icon: undefined,
						showText: true,
						flipForRTL: false,
						ariaLabel: undefined,
					} ) }
					resetAllFilter={ () => ( {
						icon: undefined,
						showText: true,
						flipForRTL: false,
						ariaLabel: undefined,
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
							disabled={ ! icon }
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
								value={ ariaLabel }
								onChange={ ( value ) => setAttributes( { ariaLabel: value } ) }
								help={ __( 'Required for accessibility when button has only an icon.', 'spectra-blocks' ) }
							/>
						) }
						{ icon && (
							<ToggleControl
								__nextHasNoMarginBottom
								checked={ flipForRTL }
								label={ __( 'Flip Icon for Right-To-Left', 'spectra-blocks' ) }
								onChange={ () => setAttributes( { flipForRTL: ! flipForRTL } ) }
								help={ __( 'Enable this for your RTL visitors if you are using a direction-specific icon. Like "Arrow Right", "Chart Line", etc.', 'spectra-blocks' ) }
							/>
						) }
					</VStack>
				</ToolsPanelItem>
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
			icon,
			textColorHover,
			backgroundColorHover,
			iconColor,
			iconColorHover,
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
			{ icon && ( <IconStyleSettings { ...{ ...props } } /> ) }
	    </>
    );
}

export default memo( Settings );
