/**
 * useCustomClasses — CRUD for user-defined gs-* custom classes.
 *
 * @since x.x.x
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const PATH = '/spectra-blocks/v1/global-styles/custom-classes';

/**
 * @param {Object} initialClasses
 * @param {number} postId         Optional post ID — when > 0, page-scoped ERA classes are merged in.
 * @since x.x.x
 *
 * @return {{
 *   classes: Object,
 *   loading: boolean,
 *   saving: boolean,
 *   saveClass: Function,
 *   deleteClass: Function,
 * }}
 */
export function useCustomClasses( initialClasses = null, postId = 0 ) {
	const [ classes, setClasses ] = useState( initialClasses ?? {} );
	const [ loading, setLoading ] = useState( initialClasses === null );
	const [ saving, setSaving ] = useState( false );

	// Tracks class names present in page meta — saves for these go to post meta
	// so edits persist on the page, regardless of whether they also exist in global.
	const pageClassNamesRef = useRef( new Set() );

	const fetchClasses = useCallback( () => {
		setLoading( true );

		if ( postId <= 0 ) {
			return apiFetch( { path: PATH } )
				.then( ( data ) => {
					setClasses( data?.classes ?? {} );
					pageClassNamesRef.current = new Set();
					setLoading( false );
				} )
				.catch( () => setLoading( false ) );
		}

		// Fetch merged classes (for display) and the raw page-meta payload in parallel.
		// The page payload gives the authoritative set of page-scoped keys — a class that
		// leaked into the global option too is still correctly treated as page-only here.
		return Promise.all( [
			apiFetch( { path: `${ PATH }?post_id=${ postId }` } ),
			apiFetch( { path: `/spectra-blocks/v1/global-styles/save?scope=page&post_id=${ postId }` } ),
		] )
			.then( ( [ mergedData, pageData ] ) => {
				const pageClasses = pageData?.payload?.classes ?? {};
				pageClassNamesRef.current = new Set( Object.keys( pageClasses ) );
				setClasses( mergedData?.classes ?? {} );
				setLoading( false );
			} )
			.catch( () => setLoading( false ) );
	}, [ postId ] );

	useEffect( () => {
		fetchClasses();
	}, [ fetchClasses ] );

	// Re-fetch when another part of the UI (flyout, Pro dropdown, external REST call)
	// signals that the class list changed.
	useEffect( () => {
		const onUpdate = () => fetchClasses();
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
				const isPageClass = postId > 0 && pageClassNamesRef.current.has( className );
				const data =
					typeof styles === 'string'
						? { class_name: className, raw_css: styles }
						: { class_name: className, styles };
				if ( isPageClass ) {
					data.post_id = postId;
				}
				await apiFetch( { path: PATH, method: 'POST', data } );
				await fetchClasses();
			} finally {
				setSaving( false );
			}
		},
		[ fetchClasses, postId ]
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
				const isPageClass = postId > 0 && pageClassNamesRef.current.has( className );
				const data = { class_name: className, is_destructive: true };
				if ( isPageClass ) {
					data.post_id = postId;
				}
				await apiFetch( { path: PATH, method: 'POST', data } );
				await fetchClasses();
			} finally {
				setSaving( false );
			}
		},
		[ fetchClasses, postId ]
	);

	return { classes, loading, saving, saveClass, deleteClass };
}
