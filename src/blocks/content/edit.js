/**
 * Internal dependencies.
 */
import RenderBlockPreview from '@spectra-components/render-block-preview';
import Settings from './settings';
import Render from './render';
import { usePasteHandler } from '@spectra-helpers/paste-handler';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
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

	// Initialize paste handler to prevent duplication.
	usePasteHandler( props );

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview blockName="content"/>;
	}

	return (
		<>
			{ isSelected && <Settings { ...props } /> }
			<Render { ...props } />
		</>
	);
};

export default Edit;
