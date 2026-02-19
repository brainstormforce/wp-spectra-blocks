/**
 * External dependencies.
 */
import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import InspectorColor from '@spectra-components/inspector-color';

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
			numberColor,
			numberColorHover,
			backgroundColorHover,
			backgroundGradientHover,
		},
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: backgroundColorHover,
					gradientValue: backgroundGradientHover,
					label: __( 'Background Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { backgroundColorHover: value } ),
					onGradientChange: ( value ) => setAttributes( { backgroundGradientHover: value } ),
					resetAllFilter: () =>
						setAttributes( {
							backgroundColorHover: undefined,
							backgroundGradientHover: undefined,
						} ),
				},
				{
					colorValue: numberColor,
					label: __( 'Number', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { numberColor: value } ),
					resetAllFilter: () => setAttributes( { numberColor: undefined } ),
				},
				{
					colorValue: numberColorHover,
					label: __( 'Number Hover', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { numberColorHover: value } ),
					resetAllFilter: () => setAttributes( { numberColorHover: undefined } ),
				},
			] }
			panelId={ clientId }
		/>
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
const Settings = ( props ) => <ColorSettings { ...{ ...props } } />;

export default memo( Settings );
