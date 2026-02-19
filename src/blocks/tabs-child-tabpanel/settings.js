/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';
import { memo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';
import { TabBlockControls } from '@spectra/tabs/helpers';

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
						'spectra-blocks'
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
		<TabBlockControls { ...{ ...props, removalType: 'tabpanel' } } />
		<ColorSettings { ...{ ...props } } />
	</>
);

export default memo( Settings );
