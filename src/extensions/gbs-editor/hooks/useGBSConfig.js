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

// Session cache (module scope): the last fetched config. Seeds the hook on
// re-mounts so reopening the Style Guide modal renders instantly from the
// previous state while a background refetch revalidates it.
let configCache = null;

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
	// Stale-while-revalidate: seed from the session cache so a re-mount (the
	// modal reopening) renders instantly; the mount fetch below still runs and
	// replaces it with the fresh result.
	const [ config, setConfig ] = useState( configCache );
	const [ loading, setLoading ] = useState( ! configCache );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		apiFetch( { path: CONFIG_PATH } )
			.then( ( data ) => {
				configCache = data;
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
			configCache = result.config;
			setConfig( result.config );
			pushPaletteToEditor( result.palette );
			return result;
		} finally {
			setSaving( false );
		}
	}, [] );

	return { config, setConfig, loading, saving, error, save };
}
