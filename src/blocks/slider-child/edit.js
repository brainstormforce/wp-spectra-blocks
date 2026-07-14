/**
 * Internal dependencies.
 */
import Render from './render';
import Settings from './settings';
import RenderBlockPreview from '@spectra-components/render-block-preview';
import { SliderChildBlockControls } from '../slider/helper';
import './editor.scss';

/**
 * The 'Edit' function.
 *
 * @param {Object} props The element props.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Edit = ( props ) => {
	const {
		isSelected,
		attributes: {
			isPreview,
		},
	} = props;

	// If this is an example, return the preview image.
	if ( isPreview ) {
		return <RenderBlockPreview childPath="slider-children" blockName="slide"/>;
	}

	return (
		<>
			{ isSelected && <SliderChildBlockControls { ...props } /> }
			<Settings { ...props } />
			<Render { ...props } />
		</>
	);
};

export default Edit;
