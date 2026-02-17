/**
 * External dependencies.
 */
import { getBlockSupport } from '@wordpress/blocks';
import { InspectorControls, useSettings, useSetting } from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalVStack as VStack,
	ColorPalette,
} from '@wordpress/components';
import { memo } from '@wordpress/element';
import { __, isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';
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
		attributes: {
			tagName,
		}
	} = props;

	const defaultTagName = 'p';
	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'General', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						tagName: defaultTagName,
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The tagName attribute is set. Default: undefined.
				- The tagName is set to something other than the defaultTagName.
				--- Note that this control should always show the selected value as the default tag - even when reset. This is for easier user understandability.
				*/}
				<ToolsPanelItem
					hasValue={ () => !! tagName && defaultTagName !== tagName }
					label={ __( 'Tag', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { tagName: defaultTagName } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'HTML Tag', 'ultimate-addons-for-gutenberg' ) }
						value={ tagName || defaultTagName }
						onChange={ ( value ) => setAttributes( { tagName: value } ) }
						isBlock
					>
						<ToggleGroupControlOption
							value="h1"
							label={ __( 'H1', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h1( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="h2"
							label={ __( 'H2', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h2( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="h3"
							label={ __( 'H3', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h3( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="h4"
							label={ __( 'H4', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h4( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="h5"
							label={ __( 'H5', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h5( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="h6"
							label={ __( 'H6', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.h6( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="p"
							label={ __( 'P', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.p( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="div"
							label={ __( 'D', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.div( false ) }
						</ToggleGroupControlOption>
						<ToggleGroupControlOption
							value="span"
							label={ __( 'S', 'ultimate-addons-for-gutenberg' ) }
							showTooltip
						>
							{ helperIcons.content.span( false ) }
						</ToggleGroupControlOption>
					</ToggleGroupControl>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * The color settings.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered settings.
 */
const ColorSettings = memo( ( props ) => {
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
 * The typography settings.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered settings.
 */
const TypographySettings = memo( ( props ) => {
	const {
		clientId,
		name,
		setAttributes,
		attributes: { tagName, dropCap },
	} = props;

	// The text alignment.
	const align = props.attributes?.style?.typography?.textAlign || '';

	// Whether the drop cap control is enabled by default.
	const isDropCapControlEnabledByDefault = getBlockSupport( name, 'typography.defaultControls.dropCap', false );

	// Whether the drop cap feature is enabled.
	const [ isDropCapFeatureEnabled ] = useSettings( 'typography.dropCap' );

	// Whether the drop cap control is enabled.
	const hasDropCapDisabled = align === ( isRTL() ? 'left' : 'right' ) || align === 'center' || tagName === 'span';

	// Help text for the drop cap control.
	let dropCapHelpText;
	switch ( tagName ) {
		case 'span':
			dropCapHelpText = __( 'Not available for span tag.', 'ultimate-addons-for-gutenberg' );
			break;
		case hasDropCapDisabled:
			dropCapHelpText = __( 'Not available for aligned text.', 'ultimate-addons-for-gutenberg' );
			break;
		case dropCap:
			dropCapHelpText = __( 'Showing large initial letter.', 'ultimate-addons-for-gutenberg' );
			break;
		default:
			dropCapHelpText = __( 'Show a large initial letter.', 'ultimate-addons-for-gutenberg' );
	}

	return (
		<>
			{ isDropCapFeatureEnabled && (
				<InspectorControls group="typography">
					<ToolsPanelItem
						hasValue={ () => !! dropCap }
						label={ __( 'Drop cap', 'ultimate-addons-for-gutenberg' ) }
						aria-label={ __( 'Drop cap', 'ultimate-addons-for-gutenberg' ) }
						isShownByDefault={ isDropCapControlEnabledByDefault }
						onDeselect={ () => setAttributes( { dropCap: undefined } ) }
						panelId={ clientId }
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Drop cap', 'ultimate-addons-for-gutenberg' ) }
							checked={ !! dropCap }
							onChange={ () => setAttributes( { dropCap: ! dropCap } ) }
							help={ dropCapHelpText }
							disabled={ hasDropCapDisabled }
							aria-label={ __( 'Drop cap', 'ultimate-addons-for-gutenberg' ) }
						/>
					</ToolsPanelItem>
				</InspectorControls>
			) }
		</>
	);
} );

/**
 * The text shadow settings.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} The rendered settings.
 */
const TextShadowSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: { 
			textShadowColor, 
			textShadowBlur, 
			textShadowOffsetX, 
			textShadowOffsetY,
			enableTextShadow
		},
	} = props;

	// Get theme colors for the color palette
	const colors = useSetting( 'color.palette' ) || [];

	// Check if text shadow is enabled or if any shadow properties are set
	const hasTextShadow = !! enableTextShadow || !! textShadowColor || !! textShadowOffsetX || !! textShadowOffsetY || !! textShadowBlur;

	return (
		<InspectorControls group="border">
			{/* This tool panel item will require reset when any of these conditions are met:
			 * - The enableTextShadow attribute is set. Default: undefined.
			 * - The textShadowColor attribute is set. Default: undefined.
			 * - The textShadowOffsetX attribute is set. Default: undefined.
			 * - The textShadowOffsetY attribute is set. Default: undefined.
			 * - The textShadowBlur attribute is set. Default: undefined.
			 * Note that the shadow offset controls are disabled until text shadow is enabled.
			 */}
			<ToolsPanelItem
				hasValue={ () => hasTextShadow }
				label={ __( 'Text Shadow', 'ultimate-addons-for-gutenberg' ) }
				onDeselect={ () => setAttributes( { 
					enableTextShadow: undefined,
					textShadowColor: undefined,
					textShadowOffsetX: undefined, 
					textShadowOffsetY: undefined,
					textShadowBlur: undefined 
				} ) }
				resetAllFilter={ () =>
					setAttributes( {
						enableTextShadow: undefined,
						textShadowColor: undefined,
						textShadowOffsetX: undefined,
						textShadowOffsetY: undefined,
						textShadowBlur: undefined,
					} )
				}
				isShownByDefault={ true }
				panelId={ clientId }
			>
				<VStack spacing={ 4 }>
					{/* Toggle to enable/disable text shadow */}
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Enable Text Shadow', 'ultimate-addons-for-gutenberg' ) }
						help={ __( 'Enable text shadow to customize the shadow properties.', 'ultimate-addons-for-gutenberg' ) }
						checked={ !! enableTextShadow }
						onChange={ ( value ) => {
							setAttributes( { enableTextShadow: value } );
							// Clear shadow properties when disabled.
							if ( ! value ) {
								setAttributes( {
									textShadowColor: undefined,
									textShadowOffsetX: undefined,
									textShadowOffsetY: undefined,
									textShadowBlur: undefined
								} );
							}
						} }
					/>

					{/* Show shadow controls only when enabled */}
					{ enableTextShadow && (
						<VStack spacing={ 3 }>
						{/* Color picker for text shadow */}
							<ColorPalette
								colors={ colors }
								value={ textShadowColor }
								onChange={ ( value ) => setAttributes( { textShadowColor: value } ) }
								clearable={ true }
								asButtons={ true }
								disableCustomColors={ false }
							/>

						{/* X Offset slider */}
							<DebouncedRangeControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Text Shadow X Offset', 'ultimate-addons-for-gutenberg' ) }
								value={ textShadowOffsetX }
								onChange={ ( value ) => setAttributes( { textShadowOffsetX: value } ) }
								marks={[
									{
										label: '-20',
										value: -20
									},
									{
										label: '-10',
										value: -10
									},
									{
										label: '0',
										value: 0
									},
									{
										label: '10',
										value: 10
									},
									{
										label: '20',
										value: 20
									}
								]}
								min={ -20 }
								max={ 20 }
								resetFallbackValue={ undefined }
								debounceDelay={ 100 }
							/>

						{/* Y Offset slider */}
						<DebouncedRangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Text Shadow Y Offset', 'ultimate-addons-for-gutenberg' ) }
							value={ textShadowOffsetY }
							onChange={ ( value ) => setAttributes( { textShadowOffsetY: value } ) }
							marks={[
								{
									label: '-20',
									value: -20
								},
								{
									label: '-10',
									value: -10
								},
								{
									label: '0',
									value: 0
								},
								{
									label: '10',
									value: 10
								},
								{
									label: '20',
									value: 20
								}
							]}
							min={ -20 }
							max={ 20 }
							resetFallbackValue={ undefined }
							debounceDelay={ 100 }
						/>

						{/* Blur radius slider */}
						<DebouncedRangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Text Shadow Blur Radius', 'ultimate-addons-for-gutenberg' ) }
							value={ textShadowBlur }
							onChange={ ( value ) => setAttributes( { textShadowBlur: value } ) }
							min={ 0 }
							max={ 20 }
							resetFallbackValue={ undefined }
							debounceDelay={ 100 }
						/>
						</VStack>
					) }
				</VStack>
			</ToolsPanelItem>
		</InspectorControls>
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
		<BlockSettings { ...{ ...props } } />
		<ColorSettings { ...{ ...props } } />
		<TypographySettings { ...{ ...props } } />
		<TextShadowSettings { ...{ ...props } } />
	</>
);

export default memo( Settings );
