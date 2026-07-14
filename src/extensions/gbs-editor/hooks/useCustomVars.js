/**
 * useCustomVars — load and save custom CSS variables.
 *
 * Variables are stored as { "--name": "value" } flat objects. The full map
 * is sent on every save (replace, not patch), so the caller keeps a local
 * draft and sends the whole thing on save.
 *
 * @since x.x.x
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const PATH = '/spectra-blocks/v1/global-styles/custom-vars';

/**
 * @since x.x.x
 * @return {{ variables: Object, loading: boolean, saving: boolean, save: Function }}
 */
export function useCustomVars() {
	const [ variables, setVariables ] = useState( {} );
	const [ loading, setLoading ]     = useState( true );
	const [ saving, setSaving ]       = useState( false );

	useEffect( () => {
		apiFetch( { path: PATH } )
			.then( ( d ) => { setVariables( d?.variables ?? {} ); setLoading( false ); } )
			.catch( () => setLoading( false ) );
	}, [] );

	const save = useCallback( async ( vars ) => {
		setSaving( true );
		try {
			const result = await apiFetch( { path: PATH, method: 'POST', data: { variables: vars } } );
			setVariables( result?.variables ?? vars );
		} finally {
			setSaving( false );
		}
	}, [] );

	return { variables, loading, saving, save };
}
