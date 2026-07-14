/**
 * Post Template Block - Edit Component
 *
 * Handles the editor interface for the Post Template block, including variation selection
 * for predefined or custom post templates.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { VariationPicker } from '@spectra-components/variation-picker';
import RenderBlockPreview from '@spectra-components/render-block-preview';
import Render from './render';
import variations from './variations';

/**
 * Edit component for the Post Template block.
 *
 * Displays a variation picker if no template is selected, otherwise renders
 * the block preview with the selected template.
 *
 * @param {Object} props            Component props.
 * @param {Object} props.attributes Block attributes.
 * @return {Element} The edit component.
 */
const Edit = ( props ) => {
	const { attributes } = props;
	const { variationSelected, isPreview } = attributes;

	if ( isPreview ) {
		return <RenderBlockPreview blockName="template" childPath="post-children" />;
	}

	if ( ! variationSelected ) {
		return (
			<VariationPicker
				{ ...props }
				label={ __( 'Select Template', 'spectra-blocks' ) }
				instructions={ __( 'Choose a template style for your post item.', 'spectra-blocks' ) }
				variations={ variations }
			/>
		);
	}

	return <Render { ...props } />;
};

export default Edit;
