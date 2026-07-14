/**
 * useGBSComputed — fetch saved computed tokens and preview live changes.
 *
 * `refresh()` re-fetches the persisted compute result (called after save).
 * `preview(config)` POSTs a temporary config to the preview endpoint and
 * returns computed tokens without persisting — used for debounced live
 * shade previews while the user is picking a colour.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies.
 */
import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const COMPUTE_PATH  = '/spectra-blocks/v1/style-guide/compute';
const PREVIEW_PATH  = '/spectra-blocks/v1/style-guide/preview';
const PREVIEW_DELAY = 300; // ms debounce

/**
 * Hook for computed GBS tokens.
 *
 * @since x.x.x
 *
 * @return {{
 *   computed: Object|null,
 *   loading: boolean,
 *   refresh: Function,
 *   preview: Function,
 *   previewing: boolean,
 * }}
 */
export function useGBSComputed() {
	const [ computed, setComputed ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ previewing, setPreviewing ] = useState( false );
	const debounceRef = useRef( null );

	const fetchComputed = useCallback( () => {
		return apiFetch( { path: COMPUTE_PATH } ).then( ( data ) => {
			setComputed( data );
			setLoading( false );
		} );
	}, [] );

	useEffect( () => {
		fetchComputed();
	}, [ fetchComputed ] );

	/**
	 * Re-fetch persisted computed tokens (call after a successful save).
	 *
	 * @since x.x.x
	 *
	 * @return {Promise<void>}
	 */
	const refresh = useCallback( () => {
		setLoading( true );
		return fetchComputed();
	}, [ fetchComputed ] );

	/**
	 * Preview tokens for a temporary config without persisting.
	 * Calls are debounced so rapid picker drags don't flood the server.
	 *
	 * @since x.x.x
	 *
	 * @param {Object} config Partial config to preview.
	 * @return {void}
	 */
	const preview = useCallback( ( config ) => {
		if ( debounceRef.current ) {
			clearTimeout( debounceRef.current );
		}
		debounceRef.current = setTimeout( async () => {
			setPreviewing( true );
			try {
				const result = await apiFetch( {
					path: PREVIEW_PATH,
					method: 'POST',
					data: config,
				} );
				setComputed( ( prev ) => ( {
					...prev,
					tokens:  result.tokens,
					schemes: result.schemes,
					palette: result.palette,
					// Include css if the preview endpoint returns it so the
					// ColorsPanel live-injection effect fires on every preview.
					...( result.css ? { css: result.css } : {} ),
				} ) );
			} finally {
				setPreviewing( false );
			}
		}, PREVIEW_DELAY );
	}, [] );

	return { computed, loading, previewing, refresh, preview };
}
