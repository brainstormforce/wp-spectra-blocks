/**
 * External dependencies.
 */
import {
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	InspectorControls,
} from '@wordpress/block-editor';

/**
 * When using this control, make sure to pass the Panel ID and the Settings.
 * Refer to the Gutenberg repository for the required props.
 * https://github.com/WordPress/gutenberg/tree/trunk/packages/block-editor/src/components/colors-gradients
 *
 * @param {Object} props The control props.
 */
const InspectorColor = ( props ) => {
	const { settings } = props;
	const colorGradientSettings = useMultipleOriginColorsAndGradients();

	// If the settings are passed, add an alpha channel to every color fetched from this control. Power to the users.
	const settingsWithAlphaChannel = settings?.map( setting => ( {
		...setting,
		enableAlpha: true,
		clearable: true,
	} ) ) ?? [];

	return (
			<InspectorControls group="color">
				<ColorGradientSettingsDropdown
					__experimentalIsRenderedInSidebar
					disableCustomColors={ false }
					disableCustomGradients={ false }
					{ ...colorGradientSettings }
					{ ...props }
					settings={ settingsWithAlphaChannel }
				/>
			</InspectorControls>
	);
};

export default InspectorColor;