/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { memo } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';
import { AccordionItemBlockControls } from '@spectra/accordion/helper';

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
			openByDefault,
		},
	} = props;

	return (
		<InspectorControls group="settings">
			<ToolsPanel
				label={ __( 'Settings', 'spectra-blocks' ) }
				resetAll={ () => {
					setAttributes( { openByDefault: false } );
				} }
				panelId={ clientId }
			>
				<ToolsPanelItem
					hasValue={ () => !! openByDefault }
					label={ __( 'Open by Default', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { openByDefault: false } ) }
					resetAllFilter={ () => ( { openByDefault: false } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={ openByDefault }
						label={ __( 'Open by Default', 'spectra-blocks' ) }
						onChange={ ( value ) => setAttributes( { openByDefault: value } ) }
						help={ __( 'If enabled, this accordion item will be expanded on page load.', 'spectra-blocks' ) }
					/>
				</ToolsPanelItem>
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
			textColorSecondary,
			textColorHover,
			textColorHoverSecondary,
			backgroundColorSecondary,
			backgroundColorHover,
			backgroundColorHoverSecondary,
			backgroundGradientSecondary,
			backgroundGradientHover,
			backgroundGradientHoverSecondary,
		},
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: textColorHover,
					label: __( 'Text Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHover: value } ),
					resetAllFilter: () => setAttributes( { textColorHover: undefined } ),
				},
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Background Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorHover: undefined,
						backgroundGradientHover: undefined,
					} ),
				},
				{
					colorValue: textColorSecondary,
					label: __( 'Header', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorSecondary: value } ),
					resetAllFilter: () => setAttributes( { textColorSecondary: undefined } ),
				},
				{
					colorValue: textColorHoverSecondary,
					label: __( 'Header Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { textColorHoverSecondary: value } ),
					resetAllFilter: () => setAttributes( { textColorHoverSecondary: undefined } ),
				},
				{
					colorValue: backgroundColorSecondary,
					gradientValue: backgroundGradientSecondary,
					label: __( 'Header Background', 'spectra-blocks' ),
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
					label: __( 'Header BG Hover/Focus', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHoverSecondary: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHoverSecondary: value } ),
					resetAllFilter: () => setAttributes( {
						backgroundColorHoverSecondary: undefined,
						backgroundGradientHoverSecondary: undefined,
					} ),
				},
			] }
			panelId={ clientId }
		/>
	);
} );

/**
 * The Editor settings.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = ( props ) => (
	<>
		<AccordionItemBlockControls { ...props } />
		<BlockSettings { ...{ ...props } } />
		<ColorSettings { ...{ ...props } } />
	</>
);

export default memo( Settings );
