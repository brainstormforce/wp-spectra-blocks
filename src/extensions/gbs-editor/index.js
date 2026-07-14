/**
 * GBS Editor — editor-side support for Global Block Styles.
 *
 * The standalone GBS modal ("popup one") was retired — the page-level editor
 * (spectra-blocks-pro `gbs-editor-v2`) is the single Global Styles UI. What
 * remains here is editor plumbing the reused panels still need:
 *   - keep the canvas CSS in sync as the Site Editor swaps the edited entity
 *     (SPA navigation drops the PHP-seeded per-page + sitewide styles), and
 *   - pre-fetch GBS variable names for the class editor's CSS autocomplete.
 * The reused panels/hooks/utils live under this folder and are imported by the
 * V2 editor.
 *
 * @since x.x.x
 */

import { useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

import { regeneratePageCSS, removeOtherPageSheets, regenerateSitewideCSS, refreshStyleGuidePalette } from './utils/liveVars.js';
import './style.scss';

// ─── Plugin component (renders nothing — editor-side effects only) ───────────────

const GBSEditorPlugin = () => {
	// The Site Editor is a SPA: navigating between pages swaps the edited
	// entity WITHOUT a full reload, so the per-page CSS that PHP enqueues on
	// load never re-runs and the canvas keeps the previous page's styling
	// (forcing a manual refresh). Track the edited page id and re-inject its
	// CSS into the canvas on every change. Falls back to core/editor's current
	// post for the classic page editor.
	const pageId = useSelect( ( select ) => {
		const site = select( 'core/edit-site' );
		const type = site?.getEditedPostType?.();
		if ( 'page' === type || 'post' === type ) {
			return Number( site.getEditedPostId?.() ) || 0;
		}
		return Number( select( 'core/editor' )?.getCurrentPostId?.() ) || 0;
	}, [] );

	useEffect( () => {
		if ( pageId <= 0 ) {
			return;
		}
		// Drop the previously-edited page's sheet(s) so its CSS can't bleed onto
		// this one, then fetch + render + inject this page's CSS into the canvas.
		removeOtherPageSheets( pageId );
		regeneratePageCSS( pageId );
	}, [ pageId ] );

	// Site-wide chrome (header/footer) — page-agnostic, handled independently of
	// the per-page CSS above. Re-apply it whenever the Site Editor swaps the
	// edited entity (page OR template part), since SPA navigation drops the
	// PHP-seeded canvas styles. Reads the PHP-localized CSS; no fetch.
	const editedKey = useSelect( ( select ) => {
		const site = select( 'core/edit-site' );
		const type = site?.getEditedPostType?.();
		if ( type ) {
			return `${ type }:${ Number( site.getEditedPostId?.() ) || 0 }`;
		}
		const pid = Number( select( 'core/editor' )?.getCurrentPostId?.() ) || 0;
		return pid ? `page:${ pid }` : '';
	}, [] );

	useEffect( () => {
		if ( editedKey ) {
			regenerateSitewideCSS();
			// Inject the option-only Style Guide palette into the canvas on load
			// (and on Site-Editor entity swaps) so blocks show the saved colors
			// without waiting for the Style Guide editor to be opened.
			refreshStyleGuidePalette();
		}
	}, [ editedKey ] );

	// Pre-fetch GBS CSS variable names for the class editor's CSS autocomplete.
	useEffect( () => {
		if ( window.__spectraGBSVarNames ) {
			return;
		}
		Promise.all( [
			apiFetch( { path: '/spectra-blocks/v1/style-guide/compute' } ),
			apiFetch( { path: '/spectra-blocks/v1/global-styles/custom-vars' } ),
			apiFetch( { path: '/spectra-blocks/v1/style-guide/config' } ),
		] ).then( ( [ computed, customVars, config ] ) => {
			// Semantic/preset palette the Style Guide OWNS → --wp--preset--color--<slug>
			// (primary, heading, body, surface, sg-*… plus any pinned semantic_overrides).
			// Surfaced FIRST so the class editor nudges authors to reference the aligned
			// role colours, not just the raw --spectra-* shade tokens (which drift from
			// the semantic layer the Style Guide manages).
			const presetSlugs = new Set( [
				...Object.keys( config?.semantic_map ?? {} ),
				...Object.keys( config?.semantic_overrides ?? {} ),
			] );
			const presetVars = [ ...presetSlugs ].map( ( slug ) => `--wp--preset--color--${ slug }` );
			const spectraVars = computed?.tokens
				? Object.keys( computed.tokens ).map( ( k ) => `--spectra-${ k }` )
				: [];
			const userVars = customVars
				? Object.keys( customVars ).filter( ( k ) => k.startsWith( '--' ) )
				: [];
			window.__spectraGBSVarNames = [ ...presetVars, ...spectraVars, ...userVars ];
		} ).catch( () => {} );
	}, [] );

	return null;
};

registerPlugin( 'spectra-gbs-editor', {
	render: GBSEditorPlugin,
} );
