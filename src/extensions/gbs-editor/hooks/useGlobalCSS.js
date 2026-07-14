/**
 * useGlobalCSS — load and save freeform custom CSS via REST.
 *
 * @since x.x.x
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const PATH = '/spectra-blocks/v1/global-styles/custom-css';

/**
 * @since x.x.x
 *
 * @return {{ css: string, setCss: Function, loading: boolean, saving: boolean, save: Function }}
 */
export function useGlobalCSS() {
	const [ css, setCss ]         = useState( '' );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ]   = useState( false );

	useEffect( () => {
		apiFetch( { path: PATH } )
			.then( ( data ) => {
				setCss( data?.css ?? '' );
				setLoading( false );
			} )
			.catch( () => setLoading( false ) );
	}, [] );

	const save = useCallback( async ( newCss ) => {
		setSaving( true );
		try {
			const result = await apiFetch( { path: PATH, method: 'POST', data: { css: newCss } } );
			setCss( result?.css ?? newCss );
		} finally {
			setSaving( false );
		}
	}, [] );

	return { css, setCss, loading, saving, save };
}
