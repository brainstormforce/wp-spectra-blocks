/**
 * useCustomClasses — CRUD for user-defined gs-* custom classes.
 *
 * @since x.x.x
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const PATH = '/spectra-blocks/v1/global-styles/custom-classes';

/**
 * @param {Object}        initialClasses
 * @param {number|string} postId         Optional post ID — when > 0, page-scoped ERA classes are
 *                                       merged in. Non-numeric IDs (FSE template strings) resolve
 *                                       to 0, i.e. global scope.
 * @since x.x.x
 *
 * @return {{
 *   classes: Object,
 *   loading: boolean,
 *   loaded: boolean,
 *   saving: boolean,
 *   saveClass: Function,
 *   deleteClass: Function,
 * }}
 */
export function useCustomClasses( initialClasses = null, postId = 0 ) {
	// FSE templates surface string IDs (e.g. "astra//home") which the integer
	// post_id REST params reject — degrade those to 0 (global scope).
	const resolvedPostId = Number( postId ) || 0;
	const [ classes, setClasses ] = useState( initialClasses ?? {} );
	const [ loading, setLoading ] = useState( initialClasses === null );
	// True once a fetch has SUCCEEDED (or seed classes were supplied). Consumers use
	// this to tell "loaded and genuinely empty" apart from "never loaded" — a failed
	// fetch leaves `classes` at {} with `loaded` false, so a caller can keep using
	// its own fallback data instead of rendering an empty list.
	const [ loaded, setLoaded ] = useState( initialClasses !== null );
	const [ saving, setSaving ] = useState( false );

	// Tracks class names present in page meta — saves for these go to post meta
	// so edits persist on the page, regardless of whether they also exist in global.
	const pageClassNamesRef = useRef( new Set() );

	// Which postId scope the current `classes` and `pageClassNamesRef` belong to — null
	// until the first successful load. Read inside fetchClasses as a ref so it stays out
	// of the callback's deps (which would re-create it and re-trigger the fetch effect).
	const loadedScopeRef = useRef( initialClasses !== null ? resolvedPostId : null );

	// Identifies this hook instance in the cross-instance update event, so the
	// instance that performed the save/delete (and already re-fetched) can
	// ignore its own broadcast instead of fetching twice.
	const instanceIdRef = useRef( {} );

	/**
	 * Broadcast that the class list changed so every other useCustomClasses
	 * instance (block inspector dropdown, flyout, panel) re-fetches. Other
	 * dispatchers (e.g. BlockDefaultsPanel) fire the same event without a
	 * source, which all instances honour.
	 */
	const notifyClassesUpdated = useCallback( () => {
		window.dispatchEvent(
			new CustomEvent( 'spectraGSClassesUpdated', {
				detail: { source: instanceIdRef.current },
			} )
		);
	}, [] );

	const fetchClasses = useCallback( () => {
		// Block the UI only when we hold nothing usable for THIS scope. A background
		// refresh of the same scope (another instance's save/delete broadcast) must not
		// flip `loading`, or consumers that render a spinner while loading — e.g.
		// CustomClassesPanel — would tear down an open class editor and lose its draft.
		// A scope CHANGE does re-block: the cached list and pageClassNamesRef describe
		// the previous post, and saving against a stale page-class set would misroute a
		// page-scoped class to global.
		if ( loadedScopeRef.current !== resolvedPostId ) {
			setLoading( true );
			setLoaded( false );
		}

		const succeed = () => {
			loadedScopeRef.current = resolvedPostId;
			setLoaded( true );
			setLoading( false );
		};

		if ( resolvedPostId <= 0 ) {
			return apiFetch( { path: PATH } )
				.then( ( data ) => {
					setClasses( data?.classes ?? {} );
					pageClassNamesRef.current = new Set();
					succeed();
				} )
				.catch( () => setLoading( false ) );
		}

		// Fetch merged classes (for display) and the raw page-meta payload in parallel.
		// The page payload gives the authoritative set of page-scoped keys — a class that
		// leaked into the global option too is still correctly treated as page-only here.
		return Promise.all( [
			apiFetch( { path: `${ PATH }?post_id=${ resolvedPostId }` } ),
			apiFetch( { path: `/spectra-blocks/v1/global-styles/save?scope=page&post_id=${ resolvedPostId }` } ),
		] )
			.then( ( [ mergedData, pageData ] ) => {
				const pageClasses = pageData?.payload?.classes ?? {};
				pageClassNamesRef.current = new Set( Object.keys( pageClasses ) );
				setClasses( mergedData?.classes ?? {} );
				succeed();
			} )
			.catch( () => setLoading( false ) );
	}, [ resolvedPostId ] );

	useEffect( () => {
		fetchClasses();
	}, [ fetchClasses ] );

	// Re-fetch when another part of the UI (flyout, Pro dropdown, external REST call)
	// signals that the class list changed. Our own broadcasts are skipped — the
	// mutating instance already re-fetched inline.
	useEffect( () => {
		const onUpdate = ( event ) => {
			if ( event?.detail?.source === instanceIdRef.current ) {
				return;
			}
			fetchClasses();
		};
		window.addEventListener( 'spectraGSClassesUpdated', onUpdate );
		return () =>
			window.removeEventListener( 'spectraGSClassesUpdated', onUpdate );
	}, [ fetchClasses ] );

	/**
	 * Create or update a class.
	 *
	 * Page-only classes (sourced from post meta) are written back to post meta
	 * via the post_id param so the update persists. Global classes go to the
	 * site-wide option as before.
	 *
	 * Styles format: { default: { property: value, ... }, hover: { ... } }
	 *
	 * @since x.x.x
	 *
	 * @param {string} className gs-* name.
	 * @param {Object} styles    Flat property→value dict per state bucket.
	 * @return {Promise<void>}
	 */
	const saveClass = useCallback(
		async ( className, styles ) => {
			setSaving( true );
			try {
				const isPageClass = resolvedPostId > 0 && pageClassNamesRef.current.has( className );
				const data =
					typeof styles === 'string'
						? { class_name: className, raw_css: styles }
						: { class_name: className, styles };
				if ( isPageClass ) {
					data.post_id = resolvedPostId;
				}
				await apiFetch( { path: PATH, method: 'POST', data } );
				await fetchClasses();
				notifyClassesUpdated();
			} finally {
				setSaving( false );
			}
		},
		[ fetchClasses, notifyClassesUpdated, resolvedPostId ]
	);

	/**
	 * Delete a class.
	 *
	 * @since x.x.x
	 *
	 * @param {string} className gs-* name to remove.
	 * @return {Promise<void>}
	 */
	const deleteClass = useCallback(
		async ( className ) => {
			setSaving( true );
			try {
				const isPageClass = resolvedPostId > 0 && pageClassNamesRef.current.has( className );
				const data = { class_name: className, is_destructive: true };
				if ( isPageClass ) {
					data.post_id = resolvedPostId;
				}
				await apiFetch( { path: PATH, method: 'POST', data } );
				await fetchClasses();
				notifyClassesUpdated();
			} finally {
				setSaving( false );
			}
		},
		[ fetchClasses, notifyClassesUpdated, resolvedPostId ]
	);

	return { classes, loading, loaded, saving, saveClass, deleteClass };
}
