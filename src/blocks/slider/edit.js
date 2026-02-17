/**
 * External dependencies.
 */
import { useEffect } from '@wordpress/element';
/**
 * Internal dependencies.
 */
import Settings from './settings';
import Render from './render';
import './editor.scss';
import RenderBlockPreview from '@spectra-components/render-block-preview';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 * 
 * @since x.x.x
 * @param {Object} props - The component props.
 * @return {Object} The component.
 */ 
const Edit = ( props ) => {
	const {
		clientId,
		setAttributes,
		isSelected,
		attributes: {
			isPreview,
			sliderId,
		}
	} = props;

	// Set the slider ID.
	useEffect( () => {
		initializeSliderId();
	}, [ clientId ] );


	/**
	 * Initialize slider ID.
	 */
	const initializeSliderId = () => {
		if ( ! sliderId ) {
			setAttributes( {
				sliderId: clientId.split( '-' )[ 0 ],
			} );
		}
	};

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview blockName="slider"/>;
	}

	return (
		<>
			{ isSelected && <Settings { ...props } /> }
			 <Render { ...props }/>
		</>
	);
};

export default Edit;
