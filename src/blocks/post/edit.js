/**
 * Post Block - Edit Component
 *
 * Handles the editor interface for the Post block, including variation selection
 * and settings panels for layout customization.
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
import Settings from './settings';
import CarouselDimensionSettings from './settings/carousel-dimension-settings';
import CarouselColorSettings from './settings/carousel-color-settings';
import PaginationColorSettings from './settings/pagination-color-settings';
import GridDimensionSettings from './settings/grid-dimension-settings';
import Render from './render';
import variations from './variations';

/**
 * Edit component for the Post block.
 *
 * Displays a variation picker if no layout is selected, otherwise renders
 * the block settings and preview.
 *
 * @param {Object}  props            Component props.
 * @param {Object}  props.attributes Block attributes.
 * @param {boolean} props.isSelected Whether the block is currently selected.
 * @return {Element} The edit component.
 */
const Edit = ( props ) => {
	const { attributes, isSelected } = props;

	const { variationSelected, isPreview } = attributes;

	if ( isPreview ) {
		return <RenderBlockPreview blockName="post" />;
	}

	if ( ! variationSelected ) {
		return (
			<VariationPicker
				{ ...props }
				label={ __( 'Select Layout', 'spectra-blocks' ) }
				instructions={ __( 'Choose a predefined layout for your posts.', 'spectra-blocks' ) }
				variations={ variations }
			/>
		);
	}

	return (
		<>
			{ isSelected && (
				<>
					<Settings { ...props } />
					<CarouselDimensionSettings { ...props } />
					<GridDimensionSettings { ...props } />
					<CarouselColorSettings { ...props } />
					<PaginationColorSettings { ...props } />
				</>
			) }
			<Render { ...props } />
		</>
	);
};

export default Edit;
