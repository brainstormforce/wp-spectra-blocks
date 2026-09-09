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
import { __ } from '@wordpress/i18n';
import useLayoutInspectorGroup from '@spectra-hooks/useLayoutInspectorGroup';
import StylePanel from '@spectra-components/style-panel';
import ToolsPanelItem from '@spectra-components/tools-panel-item';

/**
 * Internal dependencies
 */
import DebouncedRangeControl from '@spectra-components/debounced-range-control';
import GeneralSettings from './settings/general-settings';
import FilterSettings from './settings/filter-settings';
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

	const isGrid = layoutType === 'grid' || layoutType === 'masonry';

	const { group: layoutGroup, isHosted: layoutHosted } = useLayoutInspectorGroup();

	return (
		<>
			{ /* Per-viewport layout controls live in a surviving core group so
			     they stay reachable on Tablet/Mobile in WP 7.1 style-state. */ }
			<InspectorControls group={ layoutGroup }>
				<StylePanel isHosted={ layoutHosted } label={ layoutType === 'carousel' ? __( 'Carousel Layout', 'spectra-blocks' ) : __( 'Grid Layout', 'spectra-blocks' ) } resetAll={ () => setAttributes( { columns: undefined, slidesPerView: undefined, spaceBetween: undefined } ) } panelId={ clientId } showHostedHeading={ false }>
				{ isGrid && (
					<ToolsPanelItem
						hasValue={ () => attributes.columns !== undefined }
						label={ __( 'Columns', 'spectra-blocks' ) }
						onDeselect={ () => setAttributes( { columns: undefined } ) }
						resetAllFilter={ () => ( { columns: undefined } ) }
						isShownByDefault
						panelId={ clientId }
					>
						<DebouncedRangeControl
							label={ __( 'Columns', 'spectra-blocks' ) }
							value={ attributes.columns ?? 3 }
							onChange={ ( value ) => setAttributes( { columns: value } ) }
							min={ 1 }
							max={ 6 }
							step={ 1 }
						/>
					</ToolsPanelItem>
				) }
				{ layoutType === 'carousel' && (
					<>
						<ToolsPanelItem
							hasValue={ () => !! attributes.slidesPerView }
							label={ __( 'Slides Per View', 'spectra-blocks' ) }
							onDeselect={ () => setAttributes( { slidesPerView: undefined } ) }
							resetAllFilter={ () => ( { slidesPerView: undefined } ) }
							isShownByDefault
							panelId={ clientId }
						>
							<DebouncedRangeControl
								label={ __( 'Slides Per View', 'spectra-blocks' ) }
								value={ attributes.slidesPerView ?? 3 }
								onChange={ ( value ) => setAttributes( { slidesPerView: value } ) }
								min={ 1 }
								max={ 6 }
								step={ 1 }
							/>
						</ToolsPanelItem>
						<ToolsPanelItem
							hasValue={ () => !! attributes.spaceBetween }
							label={ __( 'Space Between', 'spectra-blocks' ) }
							onDeselect={ () => setAttributes( { spaceBetween: undefined } ) }
							resetAllFilter={ () => ( { spaceBetween: undefined } ) }
							isShownByDefault
							panelId={ clientId }
						>
							<DebouncedRangeControl
								label={ __( 'Space Between', 'spectra-blocks' ) }
								value={ attributes.spaceBetween ?? 30 }
								onChange={ ( value ) => setAttributes( { spaceBetween: value } ) }
								min={ 1 }
								max={ 100 }
								step={ 1 }
							/>
						</ToolsPanelItem>
					</>
				) }
							</StylePanel>
			</InspectorControls>
			<InspectorControls>
				<GeneralSettings attributes={ attributes } setAttributes={ setAttributes } clientId={ clientId } />
				{ layoutType === 'carousel' && (
					<CarouselSettings attributes={ attributes } setAttributes={ setAttributes } clientId={ clientId } />
				) }
				<FilterSettings attributes={ attributes } setAttributes={ setAttributes } clientId={ clientId } />
				{ isGrid && (
					<PaginationSettings attributes={ attributes } setAttributes={ setAttributes } clientId={ clientId } />
				) }
			</InspectorControls>
		</>
	);
};

export default Settings;
