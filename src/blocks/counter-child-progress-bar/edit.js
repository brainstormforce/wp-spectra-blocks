/**
 * Internal dependencies
 */
import Render from './render';
import Settings from './settings';


/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object} props All the props passed to this function from the parent.
 * @since x.x.x
 * @return {Element} Element to render.
 */
const Edit = ( props ) => {
	const { isSelected } = props;

	return (
		<>
			{ isSelected && <Settings { ...props } /> }
			<Render { ...props } />
		</>
	);
};

export default Edit;
