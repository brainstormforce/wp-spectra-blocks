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

	/*
	 * The inspector fills mount with the block, not with the variation.
	 *
	 * `InspectorControls` is a slot fill, and a slot orders its fills by the
	 * order they registered. The extensions that wrap this block (Global
	 * Styles, Animation, Display Conditions, Z-Index, Motion Effects) register
	 * theirs the moment the block is selected. While the variation picker was
	 * returned early, this block's own fills did not exist yet, so choosing a
	 * variation registered them LAST and every panel the block owns — Carousel
	 * Layout, Settings, Carousel, Filters — appeared below the extension
	 * panels. Measured on 7.0.4: Carousel Layout landed 1576px down a 767px
	 * sidebar, out of sight, until a Settings/Styles tab switch remounted every
	 * fill and restored the intended order.
	 *
	 * Keeping the settings components mounted across the variation choice keeps
	 * their registration — and so their position — from the start. Only the
	 * canvas swaps between the picker and the rendered block.
	 */
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
			{ variationSelected ? (
				<Render { ...props } />
			) : (
				<VariationPicker
					{ ...props }
					label={ __( 'Select Layout', 'spectra-blocks' ) }
					instructions={ __( 'Choose a predefined layout for your posts.', 'spectra-blocks' ) }
					variations={ variations }
				/>
			) }
		</>
	);
};

export default Edit;
