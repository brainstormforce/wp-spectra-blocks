/**
 * Internal dependencies.
 */
import Render from './render';
import RenderBlockPreview from '@spectra-components/render-block-preview';
import { AccordionItemBlockControls } from '@spectra/accordion/helper';

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
		attributes: {
			isPreview,
		}
	} = props;

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview childPath="accordion-children" blockName="details"/>;
	}

	return ( 
		<>
			<AccordionItemBlockControls { ...props } />
			<Render { ...props } />
		</>
	);
}

export default Edit;