/**
 * Post Block - Filter Settings
 *
 * Provides controls for filtering posts by taxonomy, author, search term, etc.
 *
 * @since x.x.x
 */

import { __ } from '@wordpress/i18n';
import {
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
	TextControl,
	FormTokenField,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useState, useMemo, useCallback } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';

export default function FilterSettings( { attributes, setAttributes, clientId } ) {
	const { query } = attributes;

	const { postType = 'post', author, search, exclude, excludeCurrentPost = false } = query || {};
	const [ excludeInput, setExcludeInput ] = useState( exclude ? exclude.join( ',' ) : '' );

	// Fetch Taxonomies for the selected Post Type
	const taxonomies = useSelect(
		( select ) => {
			const { getTaxonomies } = select( coreStore );
			const filtered = getTaxonomies( { type: postType, per_page: -1 } );
			return filtered ? filtered.filter( ( tax ) => tax.visibility?.publicly_queryable ) : [];
		},
		[ postType ]
	);

	const updateQuery = ( newQuery ) => {
		setAttributes( { query: { ...query, ...newQuery } } );
	};

	return (
		<ToolsPanel
			label={ __( 'Filters', 'spectra-blocks' ) }
			resetAll={ () => {
				updateQuery( {
					taxQuery: null,
					author: [],
					search: '',
					exclude: [],
					excludeCurrentPost: false,
				} );
				setExcludeInput( '' );
			} }
			panelId={ clientId }
		>
			{ /* Exclude Current Post */ }
			<ToolsPanelItem
				hasValue={ () => excludeCurrentPost }
				label={ __( 'Exclude Current Post', 'spectra-blocks' ) }
				onDeselect={ () => updateQuery( { excludeCurrentPost: false } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<ToggleControl
					label={ __( 'Exclude Current Post', 'spectra-blocks' ) }
					checked={ excludeCurrentPost }
					onChange={ () => updateQuery( { excludeCurrentPost: ! excludeCurrentPost } ) }
					help={ __( 'Exclude the post being viewed from the query.', 'spectra-blocks' ) }
				/>
			</ToolsPanelItem>

			{ /* Search Control */ }
			<ToolsPanelItem
				hasValue={ () => !! search }
				label={ __( 'Search', 'spectra-blocks' ) }
				onDeselect={ () => updateQuery( { search: '' } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<TextControl
					label={ __( 'Search Keyword', 'spectra-blocks' ) }
					value={ search }
					onChange={ ( value ) => updateQuery( { search: value } ) }
				/>
			</ToolsPanelItem>

			{ /* Author Filter */ }
			<ToolsPanelItem
				hasValue={ () => author && author.length > 0 }
				label={ __( 'Authors', 'spectra-blocks' ) }
				onDeselect={ () => updateQuery( { author: [] } ) }
				isShownByDefault
				panelId={ clientId }
			>
				<AuthorControl value={ author } onChange={ ( value ) => updateQuery( { author: value } ) } />
			</ToolsPanelItem>

			{ /* Taxonomy Filters */ }
			{ taxonomies.map( ( tax ) => (
				<TaxonomyControl
					key={ tax.slug }
					taxonomy={ tax }
					query={ query }
					updateQuery={ updateQuery }
					clientId={ clientId }
				/>
			) ) }

			{ /* Exclude Posts */ }
			<ToolsPanelItem
				hasValue={ () => exclude && exclude.length > 0 }
				label={ __( 'Exclude Posts', 'spectra-blocks' ) }
				onDeselect={ () => {
					setExcludeInput( '' );
					updateQuery( { exclude: [] } );
				} }
				isShownByDefault
				panelId={ clientId }
			>
				<TextControl
					label={ __( 'Exclude Post IDs', 'spectra-blocks' ) }
					value={ excludeInput }
					onChange={ ( value ) => {
						setExcludeInput( value );
						const ids = value
							.split( ',' )
							.map( ( id ) => id.trim() )
							.filter( ( id ) => id !== '' && ! isNaN( id ) )
							.map( Number );
						updateQuery( { exclude: ids } );
					} }
					help={ __( 'Enter comma-separated post IDs to exclude.', 'spectra-blocks' ) }
				/>
			</ToolsPanelItem>
		</ToolsPanel>
	);
}

const AuthorControl = ( { value, onChange } ) => {
	const authorsList = useSelect( ( select ) => {
		const { getUsers } = select( coreStore );
		return getUsers( { who: 'authors', per_page: -1, _fields: 'id,name' } );
	}, [] );

	// Normalize value to array of strings (FormTokenField requirements)
	// Value coming in is likely array of IDs
	const currentIDs = value || [];

	// Prepare display values for FormTokenField: objects { id, value: name }
	const displayValue = useMemo( () => {
		if ( ! authorsList ) {return [];}
		return currentIDs
			.map( ( id ) => {
				const author = authorsList.find( ( u ) => u.id === id );
				return author ? { id, value: author.name } : null;
			} )
			.filter( Boolean );
	}, [ authorsList, currentIDs ] );

	const suggestions = useMemo(
		() => ( authorsList ? authorsList.map( ( author ) => author.name ) : [] ),
		[ authorsList ]
	);

	const onTokensChange = useCallback(
		( tokens ) => {
			if ( ! authorsList ) {return;}
			const newIDs = tokens
				.map( ( token ) => {
					if ( typeof token === 'object' ) {return token.id;}
					// If string, find ID by name
					const author = authorsList.find( ( u ) => u.name === token );
					return author ? author.id : null;
				} )
				.filter( ( id ) => id !== null );

			onChange( newIDs );
		},
		[ authorsList, onChange ]
	);

	if ( ! authorsList ) {return null;}

	return (
		<FormTokenField
			label={ __( 'Authors', 'spectra-blocks' ) }
			value={ displayValue }
			suggestions={ suggestions }
			onChange={ onTokensChange }
			__experimentalShowHowTo={ false }
			__experimentalExpandOnFocus
		/>
	);
};

const TaxonomyControl = ( { taxonomy, query, updateQuery, clientId } ) => {
	const { slug, name } = taxonomy;
	const taxQuery = query.taxQuery || {};
	const currentTermIDs = taxQuery[ slug ] || [];

	// Stabilize currentTermIDs to prevent unnecessary re-renders
	const currentTermIDsKey = currentTermIDs.join( ',' );

	const [ search, setSearch ] = useState( '' );
	const debouncedSearch = useDebounce( setSearch, 250 );

	const { searchResults } = useSelect(
		( select ) => {
			const { getEntityRecords } = select( coreStore );

			const queryArgs = {
				per_page: 20,
				orderby: 'name',
				order: 'asc',
				exclude: currentTermIDs,
				_fields: 'id,name',
				context: 'view',
			};

			if ( search ) {
				queryArgs.search = search;
			}

			const results = getEntityRecords( 'taxonomy', slug, queryArgs );
			return { searchResults: results || [] };
		},
		[ search, slug, currentTermIDsKey ]
	);

	// Also fetch selected terms to display them correctly
	const selectedTerms = useSelect(
		( select ) => {
			if ( ! currentTermIDs.length ) {return [];}

			const { getEntityRecords } = select( coreStore );

			return (
				getEntityRecords( 'taxonomy', slug, {
					include: currentTermIDs,
					per_page: currentTermIDs.length,
					_fields: 'id,name',
				} ) || []
			);
		},
		[ slug, currentTermIDsKey ]
	);

	const displayValue = useMemo(
		() => selectedTerms.map( ( term ) => ( { id: term.id, value: term.name } ) ),
		[ selectedTerms ]
	);
	const suggestions = useMemo( () => searchResults.map( ( term ) => term.name ), [ searchResults ] );

	const onTokensChange = useCallback(
		( tokens ) => {
			const newIDs = new Set();
			tokens.forEach( ( token ) => {
				if ( typeof token === 'object' ) {
					newIDs.add( token.id );
				} else {
					// Try to find in search results or selected terms
					const match =
						searchResults.find( ( t ) => t.name === token ) ||
						selectedTerms.find( ( t ) => t.name === token );
					if ( match ) {newIDs.add( match.id );}
				}
			} );

			const newTaxQuery = { ...taxQuery };
			if ( newIDs.size > 0 ) {
				newTaxQuery[ slug ] = Array.from( newIDs );
			} else {
				delete newTaxQuery[ slug ];
			}

			// If taxQuery is empty object, maybe set to null? relying on cleanup logic elsewhere
			// but providing full object is safer.
			updateQuery( { taxQuery: Object.keys( newTaxQuery ).length ? newTaxQuery : null } );
		},
		[ searchResults, selectedTerms, taxQuery, slug, updateQuery ]
	);

	return (
		<ToolsPanelItem
			hasValue={ () => currentTermIDs.length > 0 }
			label={ name }
			onDeselect={ () => {
				const newTaxQuery = { ...taxQuery };
				delete newTaxQuery[ slug ];
				updateQuery( { taxQuery: newTaxQuery } );
			} }
			isShownByDefault
			panelId={ clientId }
		>
			<FormTokenField
				label={ name }
				value={ displayValue }
				suggestions={ suggestions }
				onInputChange={ debouncedSearch }
				onChange={ onTokensChange }
				displayTransform={ decodeEntities }
				__experimentalShowHowTo={ false }
				__experimentalExpandOnFocus
			/>
		</ToolsPanelItem>
	);
};
