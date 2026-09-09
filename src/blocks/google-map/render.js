/**
 * External dependencies.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { spectraClassNames } from '@spectra-helpers';
import { useSpectraStyles } from '@spectra-hooks';
import { getResponsivePreviewCss } from '@spectra-helpers/responsive-preview';

/**
 * The render function for the Google Map block.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
/**
 * The map's height, painted on the block element.
 *
 * Named so the per-device preview emitter can re-derive it per band — see
 * `helpers/responsive-preview.js`. The default matches the destructured one, so a
 * band and the base cannot disagree.
 *
 * @since 1.0.7
 * @param {Object} attrs The block's attributes, or a band's merge of them.
 * @return {Object} A React style object.
 */
export const getMapHeightStyles = ( attrs = {} ) => ( { height: attrs.height || '400px' } );

const Render = memo( ( props ) => {
	const {
		attributes,
		clientId,
	} = props;

	const {
		address,
		enableSatelliteView,
		language,
		zoom,
	} = attributes;

	// Get block selection state.
	const isSelected = useSelect( ( select ) => {
		return clientId ? select( 'core/block-editor' ).isBlockSelected( clientId ) : false;
	}, [ clientId ] );

	const { selectBlock } = useDispatch( 'core/block-editor' );

	// Generate styles for the block.
	const blockStyles = useSpectraStyles( {
		blockName: 'google-map',
		attributes,
		clientId,
	} );

	// Per-device preview for the canvas — see `helpers/responsive-preview.js`.
	const responsivePreviewCss = getResponsivePreviewCss( {
		clientId,
		attributes,
		blockName: 'spectra/google-map',
		producers: [ getMapHeightStyles ],
	} );

	// Generate block props.
	const blockProps = useBlockProps( {
		className: spectraClassNames( [
			'spectra-google-map',
		] ),
		style: {
			...blockStyles,
			...getMapHeightStyles( attributes ),
		},
	} );

	// Generate the Google Maps embed URL
	const mapUrl = useMemo( () => {
		const encoded_address = encodeURI( address );
		const lang_par = language ? language : 'en';
		const mapType = enableSatelliteView ? 'k' : 'm';

		return `https://maps.google.com/maps?q=${ encoded_address }&z=${ zoom }&hl=${ lang_par }&t=${ mapType }&output=embed&iwloc=near`;
	}, [ address, enableSatelliteView, language, zoom ] );

	// If no address is provided, show placeholder
	if ( ! address || address.trim() === '' ) {
		return (
			<div { ...blockProps }>
				<div 
					className="spectra-google-map__placeholder"
					style={ { 
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
						backgroundColor: '#f0f0f0',
						border: '2px dashed #ccc',
						color: '#666',
					} }
				>
					<p>{ __( 'Please enter an address to display the map.', 'spectra-blocks' ) }</p>
				</div>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			{ responsivePreviewCss && <style>{ responsivePreviewCss }</style> }
			<embed
				className="spectra-google-map__iframe"
				title={ __( 'Google Map for', 'spectra-blocks' ) + address }
				src={ mapUrl }
				width="100%"
				height="100%"
				allowFullScreen=""
				loading="lazy"
				referrerPolicy="no-referrer-when-downgrade"
				style={ {
					pointerEvents: isSelected ? 'auto' : 'none'
				} }
			></embed>
			{ ! isSelected && clientId && (
				<div 
					className="spectra-google-map__overlay"
					onClick={ () => selectBlock( clientId ) }
					aria-hidden="true"
					role="presentation"
				/>
			) }
		</div>
	);
} );

export default Render;