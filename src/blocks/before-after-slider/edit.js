/**
 * Internal dependencies.
 */
import RenderBlockPreview from '@spectra-components/render-block-preview';
import Render from './render';
import Settings from './settings';
import './editor.scss';

/**
 * The edit function for the Before After Slider block.
 *
 * @param {Object} props Block props.
 * @return {JSX.Element} Block edit component.
 */
const Edit = ( props ) => {
	const {
		isSelected,
		attributes: {
			isPreview,
		},
	} = props;

	if ( isPreview ) {
		return <RenderBlockPreview blockName="before-after-slider" />;
	}

	return (
		<>
			{ isSelected && <Settings { ...{ ...props } } /> }
			<Render { ...{ ...props } } />
		</>
	);
};

export default Edit;
