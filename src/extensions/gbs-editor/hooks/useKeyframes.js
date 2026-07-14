/**
 * useKeyframes — CRUD for named CSS @keyframes animations.
 *
 * Each keyframe is stored as { name, data: { css: "0% {...} 100% {...}" } }.
 *
 * @since x.x.x
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const PATH = '/spectra-blocks/v1/global-styles/keyframes';

/**
 * @since x.x.x
 * @return {{ keyframes: Object, loading: boolean, saving: boolean, saveKeyframe: Function, deleteKeyframe: Function }}
 */
export function useKeyframes() {
	const [ keyframes, setKeyframes ] = useState( {} );
	const [ loading, setLoading ]     = useState( true );
	const [ saving, setSaving ]       = useState( false );

	const fetch = useCallback( () => {
		setLoading( true );
		return apiFetch( { path: PATH } )
			.then( ( d ) => { setKeyframes( d?.keyframes ?? {} ); setLoading( false ); } )
			.catch( () => setLoading( false ) );
	}, [] );

	useEffect( () => { fetch(); }, [ fetch ] );

	const saveKeyframe = useCallback( async ( name, keyframeData ) => {
		setSaving( true );
		try {
			await apiFetch( {
				path: PATH,
				method: 'POST',
				data: { keyframe_name: name, keyframe_data: keyframeData, is_destructive: false },
			} );
			await fetch();
		} finally {
			setSaving( false );
		}
	}, [ fetch ] );

	const deleteKeyframe = useCallback( async ( name ) => {
		setSaving( true );
		try {
			await apiFetch( {
				path: PATH,
				method: 'POST',
				data: { keyframe_name: name, keyframe_data: {}, is_destructive: true },
			} );
			await fetch();
		} finally {
			setSaving( false );
		}
	}, [ fetch ] );

	return { keyframes, loading, saving, saveKeyframe, deleteKeyframe };
}
