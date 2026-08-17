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
// Debounce before a preview round-trips to the server. Every keystroke/drag
// resets it, so it is pure added latency on the LAST edit — keep it just high
// enough to swallow picker drags. The optimistic echo (StyleGuideContext)
// paints the picked colour instantly, so this only delays the derived tokens.
const PREVIEW_DELAY = 150; // ms debounce

// Session cache (module scope): the last computed result. Seeds the hook on
// re-mounts so reopening the Style Guide modal renders instantly from the
// previous state while a background refetch revalidates it.
let computedCache = null;

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
	// Stale-while-revalidate: seed from the session cache so a re-mount (the
	// modal reopening) renders instantly; the mount fetch below still runs and
	// replaces it with the fresh result.
	const [ computed, setComputed ] = useState( computedCache );
	const [ loading, setLoading ] = useState( ! computedCache );
	const [ previewing, setPreviewing ] = useState( false );
	const debounceRef = useRef( null );

	const fetchComputed = useCallback( () => {
		return apiFetch( { path: COMPUTE_PATH } ).then( ( data ) => {
			computedCache = data;
			setComputed( data );
			setLoading( false );
			return data;
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
	 * @return {Promise<Object>} Resolves to the saved computed result.
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
