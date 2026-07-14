/**
 * useSystemSizes — load, draft-edit, and commit font-size / spacing overrides.
 *
 * GET /spectra-blocks/v1/global-styles/system-sizes returns every key
 * merged with its default, so the hook always has a full dataset.
 *
 * Changes are draft-only until commitAll() is called. This keeps the
 * Save / Cancel footer consistent with the Colors panel — nothing is
 * persisted until the user explicitly clicks Save.
 *
 * @since x.x.x
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { injectStyleSheet } from '../utils/liveVars.js';

const PATH = '/spectra-blocks/v1/global-styles/system-sizes';

/**
 * Maps REST token keys → all CSS custom property names that need updating.
 *
 * @since x.x.x
 */
const FONTSIZE_VARS = {
	'heading-1': [ '--spectra-heading-1', '--heading--1' ],
	'heading-2': [ '--spectra-heading-2', '--heading--2' ],
	'heading-3': [ '--spectra-heading-3', '--heading--3' ],
	'heading-4': [ '--spectra-heading-4', '--heading--4' ],
	'heading-5': [ '--spectra-heading-5', '--heading--5' ],
	'heading-6': [ '--spectra-heading-6', '--heading--6' ],
	'text-xs':   [ '--spectra-text-xs',   '--text--xs' ],
	'text-sm':   [ '--spectra-text-sm',   '--text--sm' ],
	'text-md':   [ '--spectra-text-md',   '--text--md' ],
	'text-base': [ '--spectra-text-md',   '--text--md' ],
	'text-lg':   [ '--spectra-text-lg',   '--text--lg' ],
	'text-xl':   [ '--spectra-text-xl',   '--text--xl' ],
	'text-xxl':  [ '--spectra-text-2xl',  '--text--xxl' ],
	'text-2xl':  [ '--spectra-text-2xl',  '--text--xxl' ],
};

const SPACING_VARS = {
	'space-xs':  [ '--spectra-space-xs',  '--space--xs' ],
	'space-sm':  [ '--spectra-space-sm',  '--space--sm' ],
	'space-md':  [ '--spectra-space-md',  '--space--md' ],
	'space-lg':  [ '--spectra-space-lg',  '--space--lg' ],
	'space-xl':  [ '--spectra-space-xl',  '--space--xl' ],
	'space-xxl': [ '--spectra-space-2xl', '--space--xxl' ],
	'space-2xl': [ '--spectra-space-2xl', '--space--xxl' ],
};

/**
 * Build a :root { } CSS string from the full sizes dataset.
 *
 * @since x.x.x
 *
 * @param {Object} sizes Sizes object from the REST endpoint or local draft.
 * @return {string} CSS string, or empty string if nothing to inject.
 */
function buildSizesCSS( sizes ) {
	const declarations = [];

	for ( const [ key, data ] of Object.entries( sizes.fontsize ?? {} ) ) {
		const names = FONTSIZE_VARS[ key ];
		if ( names && data?.value !== null && data?.value !== undefined ) {
			const val = `${ data.value }${ data.unit ?? 'rem' }`;
			for ( const name of names ) {
				declarations.push( `${ name }:${ val }` );
			}
		}
	}

	for ( const [ key, data ] of Object.entries( sizes.spacing ?? {} ) ) {
		const names = SPACING_VARS[ key ];
		if ( names && data?.value !== null && data?.value !== undefined ) {
			const val = `${ data.value }${ data.unit ?? 'rem' }`;
			for ( const name of names ) {
				declarations.push( `${ name }:${ val }` );
			}
		}
	}

	return declarations.length ? `:root{${ declarations.join( ';' ) }}` : '';
}

/**
 * @since x.x.x
 *
 * @return {{
 *   sizes: Object|null,
 *   loading: boolean,
 *   saving: boolean,
 *   updateSize: Function,
 *   resetKey: Function,
 *   commitAll: Function,
 * }}
 */
export function useSystemSizes() {
	const [ sizes, setSizes ]     = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ]   = useState( false );

	const fetchSizes = useCallback( () => {
		setLoading( true );
		return apiFetch( { path: PATH } )
			.then( ( data ) => { setSizes( data ); setLoading( false ); } )
			.catch( () => setLoading( false ) );
	}, [] );

	useEffect( () => { fetchSizes(); }, [ fetchSizes ] );

	// Inject a <style> tag whenever sizes change (initial load or draft edit).
	useEffect( () => {
		if ( ! sizes ) {return;}
		const css = buildSizesCSS( sizes );
		if ( css ) {
			injectStyleSheet( 'spectra-gbs-live-sizes', css );
		}
	}, [ sizes ] );

	/**
	 * Update a single size in the local draft — no server write.
	 * Triggers live CSS injection via the sizes useEffect.
	 *
	 * @since x.x.x
	 *
	 * @param {'fontsize'|'spacing'} group Token group.
	 * @param {string}               key   Token key (e.g. 'heading-1').
	 * @param {number}               value Numeric value.
	 * @param {string}               unit  'rem' | 'em' | 'px'.
	 * @return {void}
	 */
	const updateSize = useCallback( ( group, key, value, unit ) => {
		setSizes( ( prev ) => ( {
			...prev,
			[ group ]: {
				...prev?.[ group ],
				[ key ]: { ...prev?.[ group ]?.[ key ], value, unit, changed: true },
			},
		} ) );
	}, [] );

	/**
	 * Reset a single key to its default value locally — no server write.
	 * The `default_value` / `default_unit` come from the GET response.
	 *
	 * @since x.x.x
	 *
	 * @param {'fontsize'|'spacing'} group Token group.
	 * @param {string}               key   Token key.
	 * @return {void}
	 */
	const resetKey = useCallback( ( group, key ) => {
		setSizes( ( prev ) => {
			const token = prev?.[ group ]?.[ key ];
			if ( ! token ) {return prev;}
			return {
				...prev,
				[ group ]: {
					...prev[ group ],
					[ key ]: {
						...token,
						value:   token.default_value ?? token.value,
						unit:    token.default_unit  ?? token.unit,
						changed: false,
					},
				},
			};
		} );
	}, [] );

	/**
	 * Commit the current draft state to the server.
	 * Called by the modal footer Save button via the registered handler.
	 *
	 * @since x.x.x
	 *
	 * @param {Object} currentSizes Current sizes state (from the calling panel).
	 * @return {Promise<void>}
	 */
	const commitAll = useCallback( async ( currentSizes ) => {
		if ( ! currentSizes ) {return;}
		setSaving( true );

		const strip = ( group ) =>
			Object.fromEntries(
				Object.entries( currentSizes?.[ group ] ?? {} ).map( ( [ k, v ] ) => [
					k,
					{ value: v.value, unit: v.unit },
				] )
			);

		try {
			await apiFetch( {
				path:   PATH,
				method: 'POST',
				data:   {
					fontsize: strip( 'fontsize' ),
					spacing:  strip( 'spacing' ),
				},
			} );
		} finally {
			setSaving( false );
		}
	}, [] );

	return { sizes, loading, saving, updateSize, resetKey, commitAll };
}
