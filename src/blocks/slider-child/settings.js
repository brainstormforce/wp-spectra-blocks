/**
 * External dependencies.
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies.
 */
import Background from '@spectra-components/background';
import InspectorColor from '@spectra-components/inspector-color';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';
import AdvancedGradientControlsGroup from '@spectra-components/advanced-gradient-control';

/**
 * Element Sub-settings: Background and Style settings for slider child.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block settings.
 */
const BlockStyle = memo( ( props ) => {
	const { clientId, setAttributes, attributes, context = {}, style } = props;
  
	const { 
		background,
		backgroundColor,
		dimRatio,
		backgroundGradient
	} = attributes;

    return (
		<InspectorControls group="styles">
			<Background
				{ ...{
					clientId,
					attributes,
					setAttributes,
					background: {
						label: 'background',
						value: background,
					},
					dimRatio,
					backgroundColor,
					backgroundGradient,
					context,
					style
				} }
			/>
		</InspectorControls>
    );
} );

/**
 * Element Sub-settings: Settings that are injected into Core's Color panel.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block color settings.
 */
const ColorSettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			backgroundColor,
			backgroundGradient,
		},
	} = props;

	const colorSettings = [
		{
			colorValue: backgroundColor,
			gradientValue: backgroundGradient,
			label: __( 'Background', 'ultimate-addons-for-gutenberg' ),
			onColorChange: ( value ) => setAttributes( { backgroundColor: value } ),
			onGradientChange: ( value ) => setAttributes( { backgroundGradient: value } ),
			resetAllFilter: () => setAttributes( {
				backgroundColor: undefined,
				backgroundGradient: undefined,
			} ),
		},
	];

	return <InspectorColor settings={ colorSettings } panelId={ clientId } />;
} );

/**
 * Advanced Gradient Settings Component
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered advanced gradient settings.
 */
const AdvancedGradientSettings = memo( ( props ) => {
	const { clientId, setAttributes, attributes } = props;

	const gradientConfigs = [
		{
			label: __( 'Advanced BG', 'ultimate-addons-for-gutenberg' ),
			valueAttr: 'advBgGradient',
			showTopBorder: true,
		},
	];

	return (
		<AdvancedGradientControlsGroup
			clientId={ clientId }
			setAttributes={ setAttributes }
			attributes={ attributes }
			gradients={ gradientConfigs }
			enableAttr="enableAdvGradients"
			enableLabel={ __( 'Enable Advanced Gradient', 'ultimate-addons-for-gutenberg' ) }
			helpText={ __( 'Advanced gradients will override the basic background colors/gradients when set.', 'ultimate-addons-for-gutenberg' ) }
			hideIndividualToggles={ true }
		/>
	);
} );

/**
 * Element Sub-settings: Overlay opacity settings.
 * 
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered block opacity styles.
 */
const OpacitySettings = memo( ( props ) => {
	const {
		clientId,
		setAttributes,
		attributes: {
			dimRatio
		},
	} = props;

	return (
		<InspectorControls group="color">
			<ToolsPanelItem
				hasValue={() => !!dimRatio}
				label={__( 'Overlay Opacity', 'ultimate-addons-for-gutenberg' )}
				onDeselect={() => setAttributes( { dimRatio: undefined } )}
				resetAllFilter={() => ( {
					dimRatio: undefined,
				} )}
				isShownByDefault
				panelId={clientId}
			>
				<DebouncedRangeControl
					__nextHasNoMarginBottom
					label={__( 'Overlay Opacity', 'ultimate-addons-for-gutenberg' )}
					value={dimRatio}
					onChange={( value ) => setAttributes( { dimRatio: value } )}
					min={0}
					max={100}
					step={5}
					debounceDelay={150}
				/>
			</ToolsPanelItem>
		</InspectorControls>
	);
} );

/**
 * The Editor settings for slider child.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} The rendered settings.
 */
const Settings = memo( ( props ) => (
	<>
		<ColorSettings { ...{ ...props } } />
		<AdvancedGradientSettings { ...{ ...props } } />
		<OpacitySettings { ...{ ...props } } />
		<BlockStyle { ...{ ...props } } />
	</>
) );

export default memo( Settings );