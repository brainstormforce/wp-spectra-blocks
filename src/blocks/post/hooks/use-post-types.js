/**
 * Post Block - usePostTypes Hook
 *
 * Custom hook for fetching and memoizing post types with associated taxonomies.
 * Provides optimized performance by pre-computing mappings and select options.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Retrieves post types along with associated taxonomies mapping.
 *
 * This hook fetches all viewable post types (excluding internal WordPress types)
 * and returns pre-computed data structures for efficient use in UI controls.
 *
 * @since x.x.x
 *
 * @return {Object} Object containing:
 *                  - postTypesSelectOptions: Array of {label, value} pairs for SelectControl
 *                  - postTypesTaxonomiesMap: Map of post type slugs to their taxonomies
 */
export const usePostTypes = () => {
	// Fetch available post types using WordPress data store.
	const postTypes = useSelect( ( select ) => {
		const { getPostTypes } = select( coreStore );
		const excludedPostTypes = [ 'attachment', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation' ];

		// Filter out non-viewable post types and excluded types.
		const filteredPostTypes = getPostTypes( { per_page: -1 } )?.filter(
			( { viewable, slug } ) => viewable && ! excludedPostTypes.includes( slug )
		);
		return filteredPostTypes;
	}, [] );

	/**
	 * Creates a memoized mapping of post types to their associated taxonomies.
	 *
	 * @since x.x.x
	 *
	 * @return {Object} Map of post type slugs to their respective taxonomies.
	 */
	const postTypesTaxonomiesMap = useMemo( () => {
		if ( ! postTypes?.length ) {
			return {};
		}

		// Build taxonomy map for each post type
		return postTypes.reduce( ( accumulator, type ) => {
			accumulator[ type.slug ] = type.taxonomies;
			return accumulator;
		}, {} );
	}, [ postTypes ] );

	/**
	 * Generates options for use in a `<SelectControl>` dropdown.
	 *
	 * @since x.x.x
	 *
	 * @return {Array} Array of post type label-value pairs.
	 */
	const postTypesSelectOptions = useMemo(
		() =>
			( postTypes || [] ).map( ( { name, slug } ) => ( {
				label: name,
				value: slug,
			} ) ),
		[ postTypes ]
	);

	return {
		postTypesTaxonomiesMap,
		postTypesSelectOptions,
	};
};
