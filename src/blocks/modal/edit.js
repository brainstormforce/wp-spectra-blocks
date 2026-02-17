import { useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
/**
 * Internal dependencies.
 */
import Render from './render';
import Settings from './settings';
import RenderBlockPreview from '@spectra-components/render-block-preview';

/**
 * The 'Edit' function.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Edit = ( props ) => {
	// Destructure the required props.
	const {
		attributes: {
			isPreview,
			modalId,
		},
		isSelected,
		clientId,
		setAttributes
	} = props;
	
	// When loading, reset the currentTab to 0.
	useEffect( () => {
		setAttributes( { isVisible: true } );
		initializeSliderId();
	}, [ clientId ] );	

	/**
	 * Initialize slider ID.
	 */
	const initializeSliderId = () => {
		if ( ! modalId ) {
			setAttributes( {
				modalId: clientId.split( '-' )[ 0 ],
			} );
		}
	};

	useEffect( () => {
		// Replacement for componentDidUpdate.
		const blockDetails = applyFilters(
			`spectra-pro.modal.edit.jsdetails`,
			props
		);
		const loadModalBlockEditor = new CustomEvent( 'SpectraModalEditor', {
			// eslint-disable-line no-undef
			detail: blockDetails,
		} );
		document.dispatchEvent( loadModalBlockEditor );
	}, [ props.attributes ] );

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview blockName="modal"/>
	}

	return (
		<>
			{ isSelected && <Settings { ...props } /> }
			<Render { ...props } />
		</>
	);
}

export default Edit;