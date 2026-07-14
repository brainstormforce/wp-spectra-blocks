/**
 * useGBSConfig — load and save the Style Guide configuration via REST.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { dispatch, select } from '@wordpress/data';

const CONFIG_PATH = '/spectra-blocks/v1/style-guide/config';

/**
 * Push an updated theme palette into the live block-editor settings store so
 * the color picker reflects the new names/colors without requiring a page reload.
 *
 * @since x.x.x
 *
 * @param {Array<{slug: string, color: string, name: string}>} palette
 */
function pushPaletteToEditor( palette ) {
	if ( ! Array.isArray( palette ) || ! palette.length ) {
		return;
	}
	const blockEditorStore = 'core/block-editor';
	const settings = select( blockEditorStore ).getSettings();
	const features = settings.__experimentalFeatures ?? {};
	const current  = features.color?.palette?.theme;

	// Merge the recomputed colours/names into the existing theme palette IN PLACE
	// — update matching slugs, keep every other entry. A wholesale replace would
	// drop the semantic slugs, Astra global colours, and theme-native colours the
	// server seeded (this response only carries the Spectra shade palette), making
	// swatches vanish from the picker until reload.
	let nextTheme;
	if ( Array.isArray( current ) && current.length ) {
		const bySlug = {};
		palette.forEach( ( entry ) => {
			if ( entry?.slug ) {
				bySlug[ entry.slug ] = entry;
			}
		} );
		nextTheme = current.map( ( entry ) =>
			entry?.slug && bySlug[ entry.slug ]
				? { ...entry, ...bySlug[ entry.slug ] }
				: entry
		);
	} else {
		nextTheme = palette;
	}

	dispatch( blockEditorStore ).updateSettings( {
		__experimentalFeatures: {
			...features,
			color: {
				...( features.color ?? {} ),
				palette: {
					...( features.color?.palette ?? {} ),
					theme: nextTheme,
				},
			},
		},
	} );
}

/**
 * Hook that manages Style Guide config state.
 *
 * @since x.x.x
 *
 * @return {{
 *   config: Object|null,
 *   setConfig: Function,
 *   loading: boolean,
 *   saving: boolean,
 *   error: Error|null,
 *   save: Function,
 * }}
 */
export function useGBSConfig() {
	const [ config, setConfig ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		apiFetch( { path: CONFIG_PATH } )
			.then( ( data ) => {
				setConfig( data );
				setLoading( false );
			} )
			.catch( ( err ) => {
				setError( err );
				setLoading( false );
			} );
	}, [] );

	/**
	 * POST updated config to the REST endpoint.
	 *
	 * Returns the full response `{ success, config, tokens, schemes }`.
	 *
	 * @since x.x.x
	 *
	 * @param {Object} updates Partial config to merge and persist.
	 * @return {Promise<Object>}
	 */
	const save = useCallback( async ( updates ) => {
		setSaving( true );
		try {
			const result = await apiFetch( {
				path: CONFIG_PATH,
				method: 'POST',
				data: updates,
			} );
			setConfig( result.config );
			pushPaletteToEditor( result.palette );
			return result;
		} finally {
			setSaving( false );
		}
	}, [] );

	return { config, setConfig, loading, saving, error, save };
}
