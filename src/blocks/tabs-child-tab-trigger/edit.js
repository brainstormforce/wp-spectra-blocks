/**
 * WordPress dependencies.
 */
import { memo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import RenderBlockPreview from '@spectra-components/render-block-preview';
import Settings from './settings';
import Render from './render';
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @since x.x.x
 *
 * @param {Object} props The element props.
 * @return {Element} Element to render.
 */
const Edit = ( props ) => {
	const {
		isSelected,
		attributes: { isPreview },
	} = props;

	if ( isPreview ) {
		return (
			<RenderBlockPreview
				childPath="tabs-children"
				blockName="tab-trigger"
			/>
		);
	}

	return (
		<>
			{ isSelected && <Settings { ...props } /> }
			<Render { ...props } />
		</>
	);
};

export default memo( Edit );