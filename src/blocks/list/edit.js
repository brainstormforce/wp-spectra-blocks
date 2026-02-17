/**
 * Internal dependencies
 */
import RenderBlockPreview from '@spectra-components/render-block-preview';
import Render from './render';
import Settings from './settings';
import './editor.scss';

/**
 * The edit function for the List block.
 *
 * @param {Object} props Block props.
 * @return {JSX.Element} Block edit component.
 */
const Edit = ( props ) => {
	// Destructure the required props.
	const {
		isSelected,
		attributes: {
			isPreview,
		}
	} = props;

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview blockName="list"/>;
	}

	return (
		<>
			{ isSelected && <Settings { ...props } /> }
			<Render { ...{ ...props } } />
		</>
	);
};

export default Edit;