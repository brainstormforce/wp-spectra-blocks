/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { memo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import InspectorColor from '@spectra-components/inspector-color';

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
			prefixColor,
			suffixColor,
		},
	} = props;

	return (
		<InspectorColor
			settings={ [
				{
					colorValue: prefixColor,
					label: __( 'Prefix Color', 'spectra-blocks' ),
					onColorChange: ( value ) => setAttributes( { prefixColor: value } ),
					resetAllFilter: () => setAttributes( { prefixColor: undefined } ),
				},
				{
					colorValue: suffixColor,
					label: __( 'Suffix Color', 'spectra-blocks' ),
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
	return (
		<>
			<ColorSettings { ...props } />
		</>
	);
} );

export default Settings;
