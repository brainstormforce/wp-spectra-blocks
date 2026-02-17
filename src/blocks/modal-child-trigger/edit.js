/**
 * Internal dependencies.
 */
import Render from './render';
import './editor.scss';
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
		}
	} = props;

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview childPath="modal-children" blockName="trigger"/>
	}

	return (
		<>
			<Render { ...props } />
		</>
	);
}

export default Edit;