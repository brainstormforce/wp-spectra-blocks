/**
 * Internal dependencies.
 */
import Settings from './settings';
import Render from './render';
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
		isSelected,
		attributes: { isPreview },
	} = props;

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview childPath="countdown-children" blockName="day" />;
	}

	return (
		<>
			{ isSelected && <Settings { ...{ ...props } } /> }
			<Render { ...props } />
		</>
	);
};

export default Edit;
