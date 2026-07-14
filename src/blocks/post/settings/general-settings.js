/**
 * Post Block - General Settings Panel
 *
 * Provides general configuration options for the Post block including layout type,
 * posts per page, and ordering.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	SelectControl,
} from '@wordpress/components';
import DebouncedRangeControl from '@spectra-components/debounced-range-control';

/**
 * Internal dependencies
 */
import { usePostTypes } from '../hooks/use-post-types';

/**
 * Get layout help text based on layout type.
 * Defined outside component to avoid recreation on each render.
 *
 * @param {string} layout The layout type.
 * @return {string} Help text for the layout.
 */
const getLayoutHelp = ( layout ) => {
	switch ( layout ) {
		case 'grid':
			return __(
				"Use the Template block's Layout controls in the toolbar to adjust columns and spacing.",
				'spectra-blocks'
			);
		case 'masonry':
			return __( 'Grid layout creates a Pinterest-style cascading grid.', 'spectra-blocks' );
		case 'carousel':
			return __( 'Carousel shows posts in a sliding carousel.', 'spectra-blocks' );
		default:
			return '';
	}
};

/**
 * General settings panel component.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update block attributes.
 * @param {string}   props.clientId      Block client ID.
 * @return {Element} The general settings panel.
 */
export default function GeneralSettings( { attributes, setAttributes, clientId } ) {
	const { layoutType, query, postsToShow = 6 } = attributes;

	const { perPage = 6, order = 'desc', orderBy = 'date', postType = 'post', sticky = '' } = query || {};

	// Use the custom hook for post types
	const { postTypesSelectOptions, postTypesTaxonomiesMap } = usePostTypes();

	const updateQuery = ( newQuery ) => {
		setAttributes( { query: { ...query, ...newQuery } } );
	};

	/**
	 * Handles the change of post type.
	 * Resets taxonomy filters when switching to a post type that doesn't support them.
	 *
	 * @param {string} newValue The new post type value.
	 */
	const onPostTypeChange = ( newValue ) => {
		const updateQueryData = { postType: newValue };

		// Reset taxonomy query if switching away from a post type
		if ( query.taxQuery ) {
			const supportedTaxonomies = postTypesTaxonomiesMap[ newValue ] || [];
			const updatedTaxQuery = Object.entries( query.taxQuery ).reduce(
				( accumulator, [ taxonomySlug, terms ] ) => {
					if ( supportedTaxonomies.includes( taxonomySlug ) ) {
						accumulator[ taxonomySlug ] = terms;
					}
					return accumulator;
				},
				{}
			);
			updateQueryData.taxQuery = Object.keys( updatedTaxQuery ).length > 0 ? updatedTaxQuery : undefined;
		}

		// Reset sticky if not 'post' type
		if ( newValue !== 'post' ) {
			updateQueryData.sticky = '';
		}

		updateQuery( updateQueryData );
	};

	// Determine loading state and help text
	const isLoadingPostTypes = ! postTypesSelectOptions || postTypesSelectOptions.length === 0;
	const postTypeControlLabel = __( 'Post Type', 'spectra-blocks' );
	const postTypeControlHelp = __(
		'Select the type of content to display: posts, pages, or custom post types.',
		'spectra-blocks'
	);

	// Render smart post type control based on available options
	let postTypeControl;
	if ( isLoadingPostTypes ) {
		// Show loading state
		postTypeControl = (
			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				options={ [ { label: __( 'Loading post types…', 'spectra-blocks' ), value: '' } ] }
				value=""
				label={ postTypeControlLabel }
				disabled
				help={ __( 'Loading available post types…', 'spectra-blocks' ) }
			/>
		);
	} else if ( postTypesSelectOptions.length > 2 ) {
		// Use SelectControl for 3+ post types
		postTypeControl = (
			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ postTypeControlLabel }
				value={ postType }
				options={ postTypesSelectOptions }
				onChange={ onPostTypeChange }
				help={ postTypeControlHelp }
			/>
		);
	} else {
		// Use ToggleGroupControl for 1-2 post types (faster UI)
		postTypeControl = (
			<ToggleGroupControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				isBlock
				value={ postType }
				label={ postTypeControlLabel }
				onChange={ onPostTypeChange }
				help={ postTypeControlHelp }
			>
				{ postTypesSelectOptions.map( ( option ) => (
					<ToggleGroupControlOption key={ option.value } value={ option.value } label={ option.label } />
				) ) }
			</ToggleGroupControl>
		);
	}

	return (
		<ToolsPanel
			label={ __( 'Settings', 'spectra-blocks' ) }
			resetAll={ () => {
				setAttributes( {
					layoutType: 'grid',
					postsToShow: 6,
					query: {
						...query,
						perPage: 6,
						order: 'desc',
						orderBy: 'date',
						postType: 'post',
						sticky: '',
					},
				} );
			} }
			panelId={ clientId }
		>
			<ToolsPanelItem
				hasValue={ () => layoutType !== 'grid' }
				label={ __( 'Layout Type', 'spectra-blocks' ) }
				onDeselect={ () => setAttributes( { layoutType: 'grid' } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<SelectControl
					label={ __( 'Layout Type', 'spectra-blocks' ) }
					value={ layoutType }
					options={ [
						{ value: 'grid', label: __( 'Grid', 'spectra-blocks' ) },
						{ value: 'masonry', label: __( 'Masonry', 'spectra-blocks' ) },
						{ value: 'carousel', label: __( 'Carousel', 'spectra-blocks' ) },
					] }
					onChange={ ( value ) => setAttributes( { layoutType: value } ) }
					help={ getLayoutHelp( layoutType ) }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={ () => postType !== 'post' }
				label={ postTypeControlLabel }
				onDeselect={ () => onPostTypeChange( 'post' ) }
				isShownByDefault
				panelId={ clientId }
			>
				{ postTypeControl }
			</ToolsPanelItem>

			{ /* if layout is not carousel */ }

			{ layoutType !== 'carousel' && (
				<ToolsPanelItem
					hasValue={ () => perPage !== 6 }
					label={ __( 'Posts Per Page', 'spectra-blocks' ) }
					onDeselect={ () => updateQuery( { perPage: 6 } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<DebouncedRangeControl
						label={ __( 'Posts Per Page', 'spectra-blocks' ) }
						value={ perPage }
						onChange={ ( value ) => updateQuery( { perPage: value } ) }
						min={ 1 }
						max={ 100 }
						step={ 1 }
					/>
				</ToolsPanelItem>
			) }

			{ layoutType === 'carousel' && (
				<ToolsPanelItem
					hasValue={ () => postsToShow !== 6 }
					label={ __( 'Posts to Show', 'spectra-blocks' ) }
					onDeselect={ () => setAttributes( { postsToShow: 6 } ) }
					isShownByDefault
					panelId={ clientId }
				>
					<DebouncedRangeControl
						label={ __( 'Posts to Show', 'spectra-blocks' ) }
						value={ postsToShow }
						onChange={ ( value ) => setAttributes( { postsToShow: value } ) }
						min={ 1 }
						max={ 100 }
						step={ 1 }
					/>
				</ToolsPanelItem>
			) }

			<ToolsPanelItem
				hasValue={ () => ( query.offset || 0 ) !== 0 }
				label={ __( 'Offset', 'spectra-blocks' ) }
				onDeselect={ () => updateQuery( { offset: 0 } ) }
				panelId={ clientId }
				isShownByDefault
			>
				<DebouncedRangeControl
					label={ __( 'Offset', 'spectra-blocks' ) }
					value={ query.offset ?? 0 }
					onChange={ ( value ) => updateQuery( { offset: value } ) }
					min={ 0 }
					max={ 100 }
					step={ 1 }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={ () => orderBy !== 'date' }
				label={ __( 'Order By', 'spectra-blocks' ) }
				onDeselect={ () => updateQuery( { orderBy: 'date' } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<SelectControl
					label={ __( 'Order By', 'spectra-blocks' ) }
					value={ orderBy }
					options={ [
						{ value: 'date', label: __( 'Date', 'spectra-blocks' ) },
						{ value: 'title', label: __( 'Title', 'spectra-blocks' ) },
						{ value: 'rand', label: __( 'Random', 'spectra-blocks' ) },
						{ value: 'menu_order', label: __( 'Menu Order', 'spectra-blocks' ) },
					] }
					onChange={ ( value ) => updateQuery( { orderBy: value } ) }
				/>
			</ToolsPanelItem>

			<ToolsPanelItem
				hasValue={ () => order !== 'desc' }
				label={ __( 'Order', 'spectra-blocks' ) }
				onDeselect={ () => updateQuery( { order: 'desc' } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<SelectControl
					label={ __( 'Order', 'spectra-blocks' ) }
					value={ order }
					options={ [
						{ value: 'desc', label: __( 'Descending', 'spectra-blocks' ) },
						{ value: 'asc', label: __( 'Ascending', 'spectra-blocks' ) },
					] }
					onChange={ ( value ) => updateQuery( { order: value } ) }
				/>
			</ToolsPanelItem>

			{ postType === 'post' && (
				<ToolsPanelItem
					hasValue={ () => !! sticky }
					label={ __( 'Sticky Posts', 'spectra-blocks' ) }
					onDeselect={ () => updateQuery( { sticky: '' } ) }
					panelId={ clientId }
					isShownByDefault
				>
					<SelectControl
						label={ __( 'Sticky Posts', 'spectra-blocks' ) }
						value={ sticky }
						options={ [
							{ value: '', label: __( 'Include', 'spectra-blocks' ) },
							{ value: 'exclude', label: __( 'Exclude', 'spectra-blocks' ) },
							{ value: 'ignore', label: __( 'Ignore', 'spectra-blocks' ) },
							{ value: 'only', label: __( 'Only Sticky', 'spectra-blocks' ) },
						] }
						onChange={ ( value ) => updateQuery( { sticky: value } ) }
						help={
							{
								'': __(
									'Sticky posts will be displayed at the top of the results.',
									'spectra-blocks'
								),
								// eslint-disable-next-line quote-props
								exclude: __(
									'Sticky posts will be excluded from results. Only regular posts will be shown.',
									'spectra-blocks'
								),
								// eslint-disable-next-line quote-props
								ignore: __(
									'Sticky posts will appear like regular posts, without special placement.',
									'spectra-blocks'
								),
								// eslint-disable-next-line quote-props
								only: __(
									'Only sticky posts will be shown. All regular posts will be excluded.',
									'spectra-blocks'
								),
							}[ sticky ]
						}
					/>
				</ToolsPanelItem>
			) }
		</ToolsPanel>
	);
}
