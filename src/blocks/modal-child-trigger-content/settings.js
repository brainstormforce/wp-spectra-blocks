/**
 * External dependencies.
 */
import { getBlockSupport } from '@wordpress/blocks';
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	ToggleControl,
} from '@wordpress/components';
import { memo } from '@wordpress/element';
import { __, isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
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
				label={ __( 'General', 'spectra-blocks' ) }
				resetAll={ () => {} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- The tagName attribute is set. Default: undefined.
				- The tagName is set to something other than the defaultTagName.
				--- Note that this control should always show the selected value as the default tag - even when reset. This is for easier user understandability.
				*/}
				<ToolsPanelItem
					hasValue={ () => !! tagName && defaultTagName !== tagName }
					label={ __( 'Tag', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { tagName: defaultTagName } ) }
					resetAllFilter={ () => ( {
						tagName: defaultTagName,
					} ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Tag', 'spectra-blocks' ) }
						value={ tagName || defaultTagName }
						onChange={ ( value ) => setAttributes( { tagName: value } ) }
						isBlock
						isAdaptiveWidth
						aria-label={ __( 'Select HTML tag', 'spectra-blocks' ) }
					>
						<ToggleGroupControlOption value="h1" label="H1" />
						<ToggleGroupControlOption value="h2" label="H2" />
						<ToggleGroupControlOption value="h3" label="H3" />
						<ToggleGroupControlOption value="h4" label="H4" />
						<ToggleGroupControlOption value="h5" label="H5" />
						<ToggleGroupControlOption value="h6" label="H6" />
						<ToggleGroupControlOption value="p" label="P" />
						<ToggleGroupControlOption value="div" label="Div" />
						<ToggleGroupControlOption value="span" label="Span" />
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
					label: __( 'Text Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Background Hover', 'spectra-blocks' ),
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
		dropCapHelpText = __( 'Not available for span tag.', 'spectra-blocks' );
		break;
	case hasDropCapDisabled:
		dropCapHelpText = __( 'Not available for aligned text.', 'spectra-blocks' );
		break;
	case dropCap:
		dropCapHelpText = __( 'Showing large initial letter.', 'spectra-blocks' );
		break;
	default:
		dropCapHelpText = __( 'Show a large initial letter.', 'spectra-blocks' );
}

return (
	<>
		{ isDropCapFeatureEnabled && (
			<InspectorControls group="typography">
				<ToolsPanelItem
					hasValue={ () => !! dropCap }
					label={ __( 'Drop cap', 'spectra-blocks' ) }
					aria-label={ __( 'Drop cap', 'spectra-blocks' ) }
					isShownByDefault={ isDropCapControlEnabledByDefault }
					onDeselect={ () => setAttributes( { dropCap: undefined } ) }
					resetAllFilter={ () => ( { dropCap: undefined } ) }
					panelId={ clientId }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Drop cap', 'spectra-blocks' ) }
						checked={ !! dropCap }
						onChange={ () => setAttributes( { dropCap: ! dropCap } ) }
						help={ dropCapHelpText }
						disabled={ hasDropCapDisabled }
						aria-label={ __( 'Drop cap', 'spectra-blocks' ) }
					/>
				</ToolsPanelItem>
			</InspectorControls>
		) }
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
		<BlockSettings { ...{ ...props } } />	
		<ColorSettings { ...{ ...props } } />
		<TypographySettings { ...{ ...props } } />
	</>
);

export default memo( Settings );
