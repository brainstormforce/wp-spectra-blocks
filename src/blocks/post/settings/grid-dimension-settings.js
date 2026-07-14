/**
 * Post Block - Grid Dimension Settings
 *
 * Provides dimension controls for grid and masonry layouts including column and row gaps.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import {
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
} from '@wordpress/components';

/**
 * Grid dimension settings component.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update block attributes.
 * @param {string}   props.clientId      Block client ID.
 * @return {Element} The grid dimension settings panel.
 */
export default function GridDimensionSettings( { attributes, setAttributes, clientId } ) {
	const { layoutType, columnGap, rowGap } = attributes;

	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( { availableUnits: availableUnits || [ 'px', '%', 'vw', 'em', 'rem' ] } );

	// Only show for grid and masonry layouts
	if ( layoutType === 'carousel' ) {
		return null;
	}

	return (
		<InspectorControls group="dimensions">
			<ToolsPanelItem
				hasValue={ () => columnGap !== '20px' }
				label={ __( 'Column Gap', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { columnGap: '20px' } ) }
				resetAllFilter={ () => ( { columnGap: '20px' } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Column Gap', 'spectra-blocks' ) }
					value={ columnGap }
					onChange={ ( value ) => setAttributes( { columnGap: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={ () => rowGap !== '20px' }
				label={ __( 'Row Gap', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { rowGap: '20px' } ) }
				resetAllFilter={ () => ( { rowGap: '20px' } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<UnitControl
					__next40pxDefaultSize
					label={ __( 'Row Gap', 'spectra-blocks' ) }
					value={ rowGap }
					onChange={ ( value ) => setAttributes( { rowGap: value } ) }
					units={ units }
				/>
			</ToolsPanelItem>
		</InspectorControls>
	);
}
