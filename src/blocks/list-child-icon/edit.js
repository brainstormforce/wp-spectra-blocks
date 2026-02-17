/**
 * WordPress dependencies
 */
import { memo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Render from './render';
import Settings from './settings';
import './editor.scss';

/**
 * The edit function for the List Icon block.
 *
 * @param {Object} props Block props.
 * @return {JSX.Element} Block edit component.
 */
const Edit = ( props ) => {
	const { isSelected } = props;
		
	return (
		<>
			{ isSelected && <Settings { ...props } />}
			<Render { ...props } />
		</>
	);
};

export default memo( Edit );