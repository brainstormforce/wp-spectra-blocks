/**
 * Post Block - Carousel Dimension Settings
 *
 * Provides dimension controls for carousel navigation arrows and pagination dots.
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
import DebouncedRangeControl from '@spectra-components/debounced-range-control';

/**
 * Carousel dimension settings component.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update block attributes.
 * @param {string}   props.clientId      Block client ID.
 * @return {Element} The carousel dimension settings panel.
 */
export default function CarouselDimensionSettings( { attributes, setAttributes, clientId } ) {
	const { layoutType, navigation, pagination, arrowSize, arrowDistance, paginationTopMargin } = attributes;

	const [ availableUnits ] = useSettings( 'spacing.units' );
	const units = useCustomUnits( { availableUnits: availableUnits || [ 'px', '%', 'em', 'rem' ] } );

	// Only show for carousel layout
	if ( layoutType !== 'carousel' ) {
		return null;
	}

	return (
		<InspectorControls group="dimensions">
			{ navigation && (
				<>
					<ToolsPanelItem
						hasValue={ () => !! arrowSize }
						label={ __( 'Arrow Size', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { arrowSize: undefined } ) }
						resetAllFilter={ () => ( { arrowSize: undefined } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<UnitControl
							__next40pxDefaultSize
							label={ __( 'Arrow Size', 'spectra-blocks' ) }
							value={ arrowSize }
							onChange={ ( value ) => setAttributes( { arrowSize: value } ) }
							units={ units }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => !! arrowDistance }
						label={ __( 'Arrow Distance', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { arrowDistance: undefined } ) }
						resetAllFilter={ () => ( { arrowDistance: undefined } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<DebouncedRangeControl
							label={ __( 'Arrow Distance', 'spectra-blocks' ) }
							value={ arrowDistance ? parseInt( arrowDistance ) : undefined }
							onChange={ ( value ) => {
								setAttributes( { arrowDistance: value === undefined ? undefined : `${ value }px` } );
							} }
							min={ -200 }
							max={ 200 }
							step={ 1 }
							help={ __(
								'Adjust the distance of navigation arrows from the edges. Negative values move arrows outside.',
								'spectra-blocks'
							) }
						/>
					</ToolsPanelItem>
				</>
			) }

			{ pagination && (
				<ToolsPanelItem
					hasValue={ () => !! paginationTopMargin }
					label={ __( 'Dots Position', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { paginationTopMargin: undefined } ) }
					resetAllFilter={ () => ( { paginationTopMargin: undefined } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<DebouncedRangeControl
						label={ __( 'Dots Position', 'spectra-blocks' ) }
						value={ paginationTopMargin ? parseInt( paginationTopMargin ) : undefined }
						onChange={ ( value ) => {
							setAttributes( { paginationTopMargin: value === undefined ? undefined : `${ value }px` } );
						} }
						min={ -200 }
						max={ 200 }
						step={ 1 }
						help={ __( 'Negative values move dots down, positive values move up.', 'spectra-blocks' ) }
					/>
				</ToolsPanelItem>
			) }
		</InspectorControls>
	);
}
