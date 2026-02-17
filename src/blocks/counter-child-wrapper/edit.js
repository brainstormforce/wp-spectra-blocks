/**
 * External dependencies
 */
import { memo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Render from './render';

/**
 * The edit function for the Counter Child Wrapper block.
 *
 * @param {Object} props Block props.
 * @return {JSX.Element} Block edit component.
 */
const Edit = ( props ) => {
	return <Render { ...props } />;
};

export default memo( Edit );

