/**
 * Post Block - Settings Component
 *
 * Renders the inspector controls sidebar panel for the Post block.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import GeneralSettings from './settings/general-settings';
import FilterSettings from './settings/filter-settings';
import GridSettings from './settings/grid-settings';
import CarouselSettings from './settings/carousel-settings';
import PaginationSettings from './settings/pagination-settings';

/**
 * Settings component for the Post block.
 *
 * Conditionally renders settings panels based on the selected layout type.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update block attributes.
 * @param {string}   props.clientId      Block client ID.
 * @return {Element} The settings panel.
 */
const Settings = ( props ) => {
	const { attributes, setAttributes, clientId } = props;
	const { layoutType } = attributes;

	return (
		<InspectorControls>
			<GeneralSettings attributes={ attributes } setAttributes={ setAttributes } clientId={ clientId } />
			{ ( layoutType === 'grid' || layoutType === 'masonry' ) && (
				<GridSettings attributes={ attributes } setAttributes={ setAttributes } clientId={ clientId } />
			) }
			{ layoutType === 'carousel' && (
				<CarouselSettings attributes={ attributes } setAttributes={ setAttributes } clientId={ clientId } />
			) }
			<FilterSettings attributes={ attributes } setAttributes={ setAttributes } clientId={ clientId } />
			{ ( layoutType === 'grid' || layoutType === 'masonry' ) && (
				<PaginationSettings attributes={ attributes } setAttributes={ setAttributes } clientId={ clientId } />
			) }
		</InspectorControls>
	);
};

export default Settings;
