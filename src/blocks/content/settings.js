/**
 * External dependencies.
 */
import { getBlockSupport } from '@wordpress/blocks';
import { InspectorControls, useSettings, useSetting } from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	ToggleControl,
	SelectControl,
	__experimentalVStack as VStack,
	ColorPalette,
} from '@wordpress/components';
import ToolsPanelItem from '@spectra-components/tools-panel-item';
import { memo } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { __, _n, sprintf, isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';
import { TAG_CONFIG, DEFAULT_TAG_NAME } from './render';
import { ContextualTagItems, CustomAttributesSection, normalizeAttrs } from '../../extensions/html-attributes/controls';
import { getContextualAttrNames } from '../../extensions/html-attributes/utils/helpers';

// Derive SelectControl options from TAG_CONFIG (SSOT).
const TAG_OPTIONS = Object.entries( TAG_CONFIG ).map( ( [ value, { label } ] ) => ( { value, label } ) );

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
			htmlAttributes: rawAttrs = [],
		},
	} = props;

	const defaultTagName = DEFAULT_TAG_NAME;
	const htmlAttributes = normalizeAttrs( rawAttrs );
	const activeTag = ( tagName || defaultTagName ).toLowerCase();
	const { createNotice } = useDispatch( 'core/notices' );

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'General', 'spectra-blocks' ) }
				resetAll={ () => {
					const contextualNames = getContextualAttrNames( activeTag );
					setAttributes( {
						tagName: defaultTagName,
						htmlAttributes: htmlAttributes.filter( ( item ) => ! contextualNames.has( item.attribute ) ),
					} );
				} }
				panelId={ clientId }
			>
				<ToolsPanelItem
					hasValue={ () => !! tagName && defaultTagName !== tagName }
					label={ __( 'Tag', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { tagName: defaultTagName } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'HTML Tag', 'spectra-blocks' ) }
						value={ tagName || defaultTagName }
						variant="default"
						options={ TAG_OPTIONS }
						onChange={ ( newValue ) => {
							const oldContextual = getContextualAttrNames( activeTag );
							const newContextual = getContextualAttrNames( newValue.toLowerCase() );
							const droppedCount = htmlAttributes.filter(
								( item ) => oldContextual.has( item.attribute ) &&
									! newContextual.has( item.attribute ) &&
									item.value !== ''
							).length;
							setAttributes( {
								tagName: newValue,
								htmlAttributes: htmlAttributes.filter(
									( item ) => ! oldContextual.has( item.attribute ) || newContextual.has( item.attribute )
								),
							} );
							if ( droppedCount > 0 ) {
								createNotice(
									'info',
									sprintf(
										/* translators: 1: number of attributes, 2: previous HTML tag name */
										_n(
											'%1$d tag attribute cleared after switching from <%2$s>.',
											'%1$d tag attributes cleared after switching from <%2$s>.',
											droppedCount,
											'spectra-blocks'
										),
										droppedCount,
										activeTag
									),
									{ isDismissible: true, type: 'snackbar' }
								);
							}
						} }
						help={ __( 'Select the appropriate HTML element for semantic markup and accessibility.', 'spectra-blocks' ) }
					/>
				</ToolsPanelItem>
				<ContextualTagItems
					tag={ activeTag }
					htmlAttributes={ htmlAttributes }
					clientId={ clientId }
					setAttributes={ setAttributes }
				/>
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
				label={ __( 'Text Shadow', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { 
					enableTextShadow: undefined,
					textShadowColor: undefined,
					textShadowOffsetX: undefined, 
					textShadowOffsetY: undefined,
					textShadowBlur: undefined 
				} ) }
				resetAllFilter={ () => ( {
					enableTextShadow: undefined,
					textShadowColor: undefined,
					textShadowOffsetX: undefined,
					textShadowOffsetY: undefined,
					textShadowBlur: undefined,
				} ) }
				isShownByDefault={ true }
				panelId={ clientId }
			>
				<VStack spacing={ 4 }>
					{/* Toggle to enable/disable text shadow */}
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Enable Text Shadow', 'spectra-blocks' ) }
						help={ __( 'Enable text shadow to customize the shadow properties.', 'spectra-blocks' ) }
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
								label={ __( 'Text Shadow X Offset', 'spectra-blocks' ) }
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
							label={ __( 'Text Shadow Y Offset', 'spectra-blocks' ) }
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
							label={ __( 'Text Shadow Blur Radius', 'spectra-blocks' ) }
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
const Settings = ( props ) => {
	const { attributes: { tagName, htmlAttributes: rawAttrs = [] } = {}, setAttributes } = props;
	const htmlAttributes = normalizeAttrs( rawAttrs );
	const activeTag = ( tagName || '' ).toLowerCase();
	return (
		<>
			<BlockSettings { ...{ ...props } } />
			<CustomAttributesSection
				tag={ activeTag }
				htmlAttributes={ htmlAttributes }
				setAttributes={ setAttributes }
			/>
			<ColorSettings { ...{ ...props } } />
			<TypographySettings { ...{ ...props } } />
			<TextShadowSettings { ...{ ...props } } />
		</>
	);
};

export default memo( Settings );
