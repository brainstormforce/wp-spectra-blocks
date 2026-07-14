/**
 * Post Block - Carousel Color Settings
 *
 * Provides color controls for carousel navigation arrows.
 *
 * @since x.x.x
 */

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
 * Carousel color settings component.
 *
 * @param {Object} props Component props.
 * @return {Element|null} The carousel color settings panel.
 */
const CarouselColorSettings = memo( ( props ) => {
	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: { layoutType, navigation, pagination, arrowColor, arrowBackgroundColor, dotColor },
	} = props;

	// Only show for carousel layout with either navigation or pagination enabled
	if ( layoutType !== 'carousel' || ( ! navigation && ! pagination ) ) {
		return null;
	}

	// Build settings array based on enabled features
	const settings = [];

	if ( navigation ) {
		settings.push(
			{
				colorValue: arrowColor,
				label: __( 'Arrow Color', 'spectra-blocks' ),
				onColorChange: ( value ) => setAttributes( { arrowColor: value } ),
				resetAllFilter: () => setAttributes( { arrowColor: undefined } ),
			},
			{
				colorValue: arrowBackgroundColor,
				label: __( 'Arrow Background Color', 'spectra-blocks' ),
				onColorChange: ( value ) => setAttributes( { arrowBackgroundColor: value } ),
				resetAllFilter: () => setAttributes( { arrowBackgroundColor: undefined } ),
			}
		);
	}

	if ( pagination ) {
		settings.push( {
			colorValue: dotColor,
			label: __( 'Dot Color', 'spectra-blocks' ),
			onColorChange: ( value ) => setAttributes( { dotColor: value } ),
			resetAllFilter: () => setAttributes( { dotColor: undefined } ),
		} );
	}

	if ( settings.length === 0 ) {
		return null;
	}

	return <InspectorColor settings={ settings } panelId={ clientId } />;
} );

export default CarouselColorSettings;
