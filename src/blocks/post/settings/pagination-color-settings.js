/**
 * Post Block - Pagination Color Settings
 *
 * Provides color controls for pagination elements including Normal,
 * Hover, and Active states.
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
 * Pagination color settings component.
 *
 * @param {Object} props Component props.
 * @return {Element|null} The pagination color settings panel.
 */
const PaginationColorSettings = memo( ( props ) => {
	// Destructure the required props.
	const {
		clientId,
		setAttributes,
		attributes: {
			layoutType,
			paginationType,
			paginationColor,
			paginationBackgroundColor,
			paginationHoverColor,
			paginationBackgroundHoverColor,
			paginationActiveColor,
			paginationBackgroundActiveColor,
		},
	} = props;

	// Only show if pagination is enabled and NOT a carousel layout
	if ( paginationType === 'none' || layoutType === 'carousel' ) {
		return null;
	}

	// Build settings array based on pagination type
	const settings = [];

	// Add base color (text/loader color)
	settings.push( {
		colorValue: paginationColor,
		label:
			paginationType === 'infinite'
				? __( 'Loader Color', 'spectra-blocks' )
				: __( 'Pagination text', 'spectra-blocks' ),
		onColorChange: ( value ) => setAttributes( { paginationColor: value } ),
		resetAllFilter: () => setAttributes( { paginationColor: undefined } ),
	} );

	// Add background, hover, and active colors for non-infinite pagination
	if ( paginationType !== 'infinite' ) {
		settings.push(
			{
				colorValue: paginationBackgroundColor,
				label: __( 'Pagination Background', 'spectra-blocks' ),
				onColorChange: ( value ) => setAttributes( { paginationBackgroundColor: value } ),
				resetAllFilter: () => setAttributes( { paginationBackgroundColor: undefined } ),
			},
			{
				colorValue: paginationHoverColor,
				label: __( 'Pagination text Hover', 'spectra-blocks' ),
				onColorChange: ( value ) => setAttributes( { paginationHoverColor: value } ),
				resetAllFilter: () => setAttributes( { paginationHoverColor: undefined } ),
			},
			{
				colorValue: paginationBackgroundHoverColor,
				label: __( 'Pagination Background Hover', 'spectra-blocks' ),
				onColorChange: ( value ) => setAttributes( { paginationBackgroundHoverColor: value } ),
				resetAllFilter: () => setAttributes( { paginationBackgroundHoverColor: undefined } ),
			}
		);
	}

	// Add active colors only for standard pagination
	if ( paginationType === 'standard' ) {
		settings.push(
			{
				colorValue: paginationActiveColor,
				label: __( 'Pagination text Active', 'spectra-blocks' ),
				onColorChange: ( value ) => setAttributes( { paginationActiveColor: value } ),
				resetAllFilter: () => setAttributes( { paginationActiveColor: undefined } ),
			},
			{
				colorValue: paginationBackgroundActiveColor,
				label: __( 'Pagination Background Active', 'spectra-blocks' ),
				onColorChange: ( value ) => setAttributes( { paginationBackgroundActiveColor: value } ),
				resetAllFilter: () => setAttributes( { paginationBackgroundActiveColor: undefined } ),
			}
		);
	}

	return <InspectorColor settings={ settings } panelId={ clientId } />;
} );

export default PaginationColorSettings;
