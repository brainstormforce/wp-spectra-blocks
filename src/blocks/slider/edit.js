/**
 * External dependencies.
 */
import { useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
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

	const { __unstableMarkNextChangeAsNotPersistent } = useDispatch( 'core/block-editor' );

	// Set the slider ID.
	useEffect( () => {
		initializeSliderId();
	}, [ clientId ] );


	/**
	 * Initialize slider ID.
	 */
	const initializeSliderId = () => {
		if ( ! sliderId ) {
			// A derived value, not an edit: writing it persistently made every
			// post containing a slider open dirty. It still persists whenever
			// the user saves for their own reasons.
			__unstableMarkNextChangeAsNotPersistent();
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
