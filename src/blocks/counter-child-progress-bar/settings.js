/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import {
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import InspectorColor from '@spectra-components/inspector-color';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';

/**
 * General settings for the Progress Bar block.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const GeneralSettings = memo( ( props ) => {
	const {
		clientId,
		attributes,
		setAttributes,
	} = props;

	const {
		barHeight,
		barBorderRadius,
	} = attributes;

	// Get the core provided units, else add the fallback
	const units = useCustomUnits( {
		availableUnits: [ 'px' ],
	} );

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Progress Bar', 'ultimate-addons-for-gutenberg' ) }
				resetAll={ () => {
					setAttributes( {
						barHeight: undefined,
						barBorderRadius: undefined,
					} );
				} }
				panelId={ clientId }
			>
				{/* This tool panel item will require reset when any of these conditions are met:
				- Bar height is set to any value. Default: '32px'.
				- When reset, bar height returns to 32px.
				*/}
				<ToolsPanelItem
					hasValue={ () => !! barHeight }
					label={ __( 'Height', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { barHeight: undefined } ) }
					resetAllFilter={ () => ( { barHeight: undefined } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<UnitControl
						__next40pxDefaultSize
						label={ __( 'Height', 'ultimate-addons-for-gutenberg' ) }
						labelPosition="top"
						value={ barHeight }
						min={ 0 }
						onChange={ ( value ) => setAttributes( { barHeight: value } ) }
						units={ units }
					/>
				</ToolsPanelItem>

				{/* This tool panel item will require reset when any of these conditions are met:
				- Bar border radius is set to any value. Default: undefined.
				- When reset, bar border radius returns to undefined.
				*/}
				<ToolsPanelItem
					hasValue={ () => barBorderRadius !== undefined }
					label={ __( 'Border Radius', 'ultimate-addons-for-gutenberg' ) }
					onDeselect={ () => setAttributes( { barBorderRadius: undefined } ) }
					resetAllFilter={ () => ( { barBorderRadius: undefined } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<DebouncedRangeControl
						label={ __( 'Border Radius', 'ultimate-addons-for-gutenberg' ) }
						value={ barBorderRadius }
						onChange={ ( value ) => setAttributes( { barBorderRadius: value } ) }
						min={ 0 }
						max={ 50 }
						step={ 1 }
					/>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
} );

/**
 * Color settings for the Progress Bar block.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const ColorSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			prefixColor,
			suffixColor,
		},
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: prefixColor,
					label: __( 'Prefix Color', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { prefixColor: value } ),
					resetAllFilter: () => setAttributes( { prefixColor: undefined } ),
				},
				{
					colorValue: suffixColor,
					label: __( 'Suffix Color', 'ultimate-addons-for-gutenberg' ),
					onColorChange: ( value ) => setAttributes( { suffixColor: value } ),
					resetAllFilter: () => setAttributes( { suffixColor: undefined } ),
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
const Settings = memo( ( props ) => {
	const {
		context: {
			'spectra/counter/counterStyle': counterStyle = 'simple',
		} = {},
	} = props;

	// Don't show settings for simple counter style since progress bar is not displayed
	if ( counterStyle === 'simple' || counterStyle === 'circular' ) {
		return null;
	}

	return (
		<>
			<GeneralSettings { ...props } />
			<ColorSettings { ...props } />
		</>
	);
} );

export default Settings;
