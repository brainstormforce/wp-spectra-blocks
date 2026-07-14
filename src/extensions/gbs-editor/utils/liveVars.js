/**
 * liveVars — utilities for injecting CSS into the live editor canvas.
 *
 * Targets both the host document and the Gutenberg editor iframe so changes
 * appear immediately in the block editor without a page reload.
 *
 * Matches the approach used by the Pro sidebar: inserts <style> tags AFTER
 * spectra-gs-dynamic-styles-inline-css so cascade order wins.
 *
 * @since x.x.x
 */

import apiFetch from '@wordpress/api-fetch';
import { dispatch, select } from '@wordpress/data';

/**
 * Mirrors the PHP Engine::$default_semantic_map.
 * Maps WP palette slugs → token keys in computed.tokens.
 *
 * Kept in sync with class-engine.php; used to derive --wp--preset--color--*
 * live without a page reload.
 *
 * @since x.x.x
 */
const SEMANTIC_MAP = {
	'primary':        'chromatic1-7',
	'secondary':      'chromatic1-5',
	'tertiary':       'chromatic1-2',
	'quaternary':     'chromatic2-2',
	'heading':        'neutral-7',
	'body':           'neutral-5',
	'background':     'neutral-0',
	'foreground':     'chromatic1-7',
	'surface':        'neutral-1',
	'outline':        'neutral-2',
	'neutral':        'neutral-4',
	'sg-accent':      'chromatic1-7',
	'sg-secondary':   'chromatic1-5',
	'sg-heading':     'neutral-7',
	'sg-body':        'neutral-5',
	'sg-surface':     'neutral-1',
	'sg-background':  'neutral-0',
	'sg-border':      'neutral-2',
	'sg-neutral':     'neutral-6',
	'sg-muted':       'neutral-4',
};

/**
 * Astra global-color slug index → Style Guide shade token.
 *
 * Kept in sync with GlobalStylesBridge::ASTRA_SHADE_MAP (class-global-styles-bridge.php).
 * Used to align the ast-global-color-{N} picker swatches with the colour that
 * actually gets applied, live — mirroring the server's align_astra_palette_swatches().
 *
 * @since x.x.x
 */
const ASTRA_SHADE_MAP = {
	0: 'chromatic1-7',
	1: 'chromatic1-5',
	2: 'neutral-7',
	3: 'neutral-5',
	4: 'neutral-1',
	5: 'neutral-0',
	6: 'neutral-2',
	7: 'neutral-6',
	8: 'neutral-4',
};

/**
 * Collect all documents that should receive live CSS updates.
 *
 * Includes the host document plus the Gutenberg editor iframe document.
 * The editor canvas is always in iframe[name="editor-canvas"] (WP 6.x+).
 *
 * @since x.x.x
 *
 * @return {Document[]}
 */
function getEditorDocuments() {
	const docs = [ document ];

	const canvas = document.querySelector( 'iframe[name="editor-canvas"]' );
	if ( canvas?.contentDocument ) {
		docs.push( canvas.contentDocument );
	}

	return docs;
}

/**
 * Inject or replace a <style> tag in all editor documents.
 *
 * The tag is inserted immediately after the Pro system stylesheet
 * (spectra-gs-dynamic-styles-inline-css) so its :root rules win the cascade.
 *
 * @since x.x.x
 *
 * @param {string} id  The id attribute for the style element.
 * @param {string} css Full CSS string to inject.
 * @return {void}
 */
export function injectStyleSheet( id, css ) {
	if ( ! css ) {return;}

	for ( const doc of getEditorDocuments() ) {
		let el = doc.getElementById( id );

		if ( el ) {
			el.textContent = css;
		} else {
			el = doc.createElement( 'style' );
			el.id = id;
			el.textContent = css;
			// Append to end of <body> so this always comes AFTER the WordPress
			// core global-styles-inline-css (which WP injects into the iframe body),
			// ensuring our :root overrides win the cascade.
			( doc.body ?? doc.head ?? doc.documentElement ).appendChild( el );
		}

		// Force the browser to repaint so changes are visible immediately.
		void el.offsetHeight;
	}
}

/**
 * Remove stale per-page custom-CSS <style> sheets from all editor documents,
 * keeping only the current page's.
 *
 * The Site Editor swaps the edited page without a full reload, so the
 * previously-edited page's sheet would otherwise linger in the canvas and
 * bleed onto the next page. Covers BOTH id shapes: the JS-injected
 * `spectra-gen-custom-css-<id>` and the PHP-enqueued inline
 * `spectra-gen-custom-css-<id>-inline-css`.
 *
 * @since x.x.x
 *
 * @param {number} keepId The post ID whose sheet(s) should be preserved.
 * @return {void}
 */
export function removeOtherPageSheets( keepId ) {
	const keep = [
		`spectra-gen-custom-css-${ keepId }`,
		`spectra-gen-custom-css-${ keepId }-inline-css`,
	];
	for ( const doc of getEditorDocuments() ) {
		for ( const el of doc.querySelectorAll( '[id^="spectra-gen-custom-css-"]' ) ) {
			if ( ! keep.includes( el.id ) ) {
				el.remove();
			}
		}
	}
}

/**
 * Build a :root {} block that defines --wp--preset--color--* vars for every
 * semantic slot in SEMANTIC_MAP, resolved from the current computed.tokens.
 *
 * This mirrors what GlobalStylesBridge::sync_user_layer_palette() writes to
 * the database so the editor canvas sees the updated values live.
 *
 * @since x.x.x
 *
 * @param {Object} tokens Flat token map from computed.tokens.
 * @return {string} CSS string, or empty string if tokens is empty.
 */
export function buildWPPresetCSS( tokens ) {
	if ( ! tokens ) {return '';}
	const decls = [];
	for ( const [ slug, tokenKey ] of Object.entries( SEMANTIC_MAP ) ) {
		const hex = tokens[ tokenKey ];
		if ( hex ) {
			decls.push( `--wp--preset--color--${ slug }:${ hex }` );
		}
	}
	return decls.length ? `:root{${ decls.join( ';' ) }}` : '';
}

/**
 * Build the full WP preset palette CSS the canvas needs — every slug the server
 * `inject_palette()` registers: shade slugs (`spectra-chromatic*`, `neutral*`)
 * from `computed.palette`, PLUS semantic slugs (`primary`, `sg-accent`, …)
 * resolved from the config's `semantic_map` × `computed.tokens`.
 *
 * Scoped to `:root, .editor-styles-wrapper` so it wins inside the editor iframe,
 * where WordPress re-defines these vars on `.editor-styles-wrapper` (a plain
 * `:root` override is shadowed there).
 *
 * @since x.x.x
 *
 * @param {Array}  palette     computed.palette (get_wp_palette shade entries).
 * @param {Object} tokens      computed.tokens (shade-key → hex).
 * @param {Object} semanticMap config.semantic_map (slug → shade-key).
 * @param {Object} overrides   config.semantic_overrides (slug → hex) — explicit
 *                             per-slug pins that WIN over the shade-derived value,
 *                             mirroring the server inject_palette().
 * @return {string} CSS string, or empty when nothing to emit.
 */
export function buildPresetPaletteCSS( palette, tokens, semanticMap, overrides ) {
	const map = {};
	if ( Array.isArray( palette ) ) {
		palette.forEach( ( entry ) => {
			if ( entry?.slug && entry?.color ) {
				map[ entry.slug ] = entry.color;
			}
		} );
	}
	if ( semanticMap && typeof semanticMap === 'object' && tokens ) {
		Object.entries( semanticMap ).forEach( ( [ slug, tokenKey ] ) => {
			if ( tokens[ tokenKey ] ) {
				map[ slug ] = tokens[ tokenKey ];
			}
		} );
	}
	// Explicit per-slug overrides win over the shade-derived value (same rule the
	// server's inject_palette / maybe_override_managed_user_palette applies). Without
	// this the canvas re-derives every slug and clobbers imported/pinned colours.
	if ( overrides && typeof overrides === 'object' ) {
		Object.entries( overrides ).forEach( ( [ slug, hex ] ) => {
			if ( hex ) {
				map[ slug ] = hex;
			}
		} );
	}
	const decls = Object.entries( map ).map(
		( [ slug, hex ] ) => `--wp--preset--color--${ slug }:${ hex }`
	);
	return decls.length ? `:root,.editor-styles-wrapper{${ decls.join( ';' ) }}` : '';
}

/**
 * Build the Astra global-colour alias CSS (`--ast-global-color-{N}` +
 * `--wp--preset--color--ast-global-color-{N}`) from the computed tokens, using
 * ASTRA_SHADE_MAP — mirroring the server's get_astra_compat_css() /
 * inject_astra_compat_editor_styles().
 *
 * Elements that resolve through the theme's `--ast-global-color-*` (e.g. the
 * button background) don't move when only the `--spectra-*` tokens and semantic
 * `--wp--preset--color--*` vars are injected; this closes that gap so a live
 * Style Guide colour edit updates them in the canvas without a save + reload.
 *
 * Injected only from the EDIT paths (ColorsPanel / StyleGuideContext) — never
 * from the on-load path — so it doesn't reintroduce the remap on a plain load
 * when no Style Guide is saved.
 *
 * @since x.x.x
 *
 * @param {Object} tokens computed.tokens (shade-key => hex).
 * @return {string} CSS, or '' when there is nothing to emit.
 */
export function buildAstraAliasCSS( tokens ) {
	if ( ! tokens || typeof tokens !== 'object' ) {
		return '';
	}
	const decls = [];
	Object.entries( ASTRA_SHADE_MAP ).forEach( ( [ index, tokenKey ] ) => {
		const hex = tokens[ tokenKey ];
		if ( hex ) {
			decls.push( `--ast-global-color-${ index }:${ hex }` );
			decls.push( `--wp--preset--color--ast-global-color-${ index }:${ hex }` );
		}
	} );
	return decls.length ? `:root,.editor-styles-wrapper{${ decls.join( ';' ) }}` : '';
}

/**
 * Fetch the saved Style Guide palette and inject it into the canvas.
 *
 * Runs on editor load so the canvas reflects the option-only palette even
 * before the Style Guide editor is opened. Needed because, with no CPT
 * migration, the editor canvas otherwise renders the stale `wp_global_styles`
 * palette until the Style Guide component mounts and injects the live values.
 *
 * @since x.x.x
 *
 * @return {Promise<void>}
 */
export async function refreshStyleGuidePalette() {
	try {
		const [ config, computed ] = await Promise.all( [
			apiFetch( { path: '/spectra-blocks/v1/style-guide/config' } ),
			apiFetch( { path: '/spectra-blocks/v1/style-guide/compute' } ),
		] );
		if ( computed?.css ) {
			injectStyleSheet( 'spectra-gbs-v2-live-color-vars', computed.css );
		}
		const css = buildPresetPaletteCSS( computed?.palette, computed?.tokens, config?.semantic_map, config?.semantic_overrides );
		if ( css ) {
			injectStyleSheet( 'spectra-gbs-v2-live-wp-preset-colors', css );
		}
		// Align the colour-picker swatches with the same fresh computation. Runs on
		// EVERY editor (not just when the Style Guide panel is mounted), because
		// WordPress can serve a stale cached theme.json palette to the picker while
		// the canvas already renders the fresh injected vars above — leaving the
		// swatch showing one colour and the applied block another.
		syncEditorSwatches( computed, config );
	} catch ( _err ) {
		// Non-fatal — canvas falls back to the server-rendered palette.
	}
}

/**
 * Regenerate Pro's dynamic user-classes stylesheet and inject it into the
 * live editor canvas so class/variable/keyframe edits appear immediately.
 *
 * Calls the `spectra_pro_gs_regenerate_editor_css` AJAX action (Pro plugin).
 * No-ops silently when Pro is not active (`window.spectra_editor_gs` absent).
 *
 * @since x.x.x
 *
 * @return {Promise<void>}
 */
export async function regenerateEditorCSS() {
	const ajaxUrl = window.spectra_editor_gs?.ajax_url;
	const nonce   = window.spectra_editor_gs?.ajax_nonce;
	if ( ! ajaxUrl ) {return;}

	const body = new FormData();
	body.append( 'action',   'spectra_pro_gs_regenerate_editor_css' );
	body.append( 'security', nonce ?? '' );

	try {
		const response = await fetch( ajaxUrl, { method: 'POST', body } );
		if ( ! response.ok ) {return;}
		const data = await response.json();
		const css  = data?.data?.css ?? data?.css ?? '';
		if ( ! css ) {return;}

		injectStyleSheet( 'spectra-gs-dynamic-user-classes', css );
	} catch ( _err ) {
		// Non-fatal — CSS refreshes on next page load.
	}
}

/**
 * Re-fetch computed token CSS and inject it into the live editor canvas.
 *
 * Used after preset/config saves that change CSS variable values but don't go
 * through ColorsPanel's computed watcher (which is the only panel that has
 * the injection side-effect wired up by default).
 *
 * @since x.x.x
 *
 * @return {Promise<void>}
 */
export async function refreshComputedCSS() {
	try {
		const data = await apiFetch( { path: '/spectra-blocks/v1/style-guide/compute' } );
		if ( data?.css ) {
			injectStyleSheet( 'spectra-gbs-live-color-vars', data.css );
		}
		if ( data?.tokens ) {
			const css = buildWPPresetCSS( data.tokens );
			if ( css ) {
				injectStyleSheet( 'spectra-gbs-live-wp-preset-colors', css );
			}
		}
	} catch ( _err ) {
		// Non-fatal.
	}
}

/**
 * Inject custom CSS variables as a :root {} block into the live editor canvas.
 *
 * The server emits these at page load via wp_add_inline_style, but edits made
 * through CustomVarsPanel are not reflected until the next reload without this.
 *
 * @since x.x.x
 *
 * @return {Promise<void>}
 */
export async function refreshCustomVarsCSS() {
	try {
		const data = await apiFetch( { path: '/spectra-blocks/v1/global-styles/custom-vars' } );
		const vars    = data?.variables ?? {};
		const entries = Object.entries( vars );
		const css     = entries.length
			? `:root{${ entries.map( ( [ k, v ] ) => `${ k }:${ v }` ).join( ';' ) }}`
			: ':root{}';
		injectStyleSheet( 'spectra-gbs-live-custom-vars', css );
	} catch ( _err ) {
		// Non-fatal.
	}
}

/**
 * Fetch the ERA page-scoped CSS payload and inject the rendered CSS into the
 * live editor canvas.
 *
 * ERA custom classes are stored in post meta — they are NOT included in
 * regenerateEditorCSS() which only reads the global option via Pro AJAX.
 * This covers that gap so page-scoped class changes appear immediately.
 *
 * @since x.x.x
 *
 * @param {number} postId The current post ID. No-ops when postId <= 0.
 * @return {Promise<void>}
 */
export async function regeneratePageCSS( postId ) {
	if ( postId <= 0 ) { return; }
	try {
		const saveData = await apiFetch( {
			path: `/spectra-blocks/v1/global-styles/save?scope=page&post_id=${ postId }`,
		} );
		const payload = saveData?.payload;
		if ( ! payload || Object.keys( payload ).length === 0 ) { return; }
		const renderData = await apiFetch( {
			path: '/spectra-blocks/v1/global-styles/render',
			method: 'POST',
			data: { payload, post_id: postId, scope: 'page' },
		} );
		const css = renderData?.css;
		if ( css ) {
			injectStyleSheet( `spectra-gen-custom-css-${ postId }`, css );
		}
	} catch ( _err ) {
		// Non-fatal.
	}
}

/**
 * Re-inject the SITE-WIDE (chrome) GBS CSS into the editor canvas.
 *
 * The chrome (header/footer + shared classes) is page-agnostic, so it is NOT
 * fetched here — PHP renders it once (editor-scoped) and hands it over as
 * `window.spectraBlocksGenSitewideCss` (see Engine::localize_sitewide_editor_css).
 * PHP seeds the INITIAL canvas via enqueue_block_assets, but the Site Editor
 * drops that seed on SPA navigation; this re-applies it to the swapped canvas.
 *
 * @since x.x.x
 *
 * @return {void}
 */
export function regenerateSitewideCSS() {
	const css = window.spectraBlocksGenSitewideCss;
	if ( typeof css === 'string' && css ) {
		injectStyleSheet( 'spectra-gen-sitewide-css-live', css );
	}
}

/**
 * Update the block-editor colour-picker swatches IN PLACE so the palette shown
 * while editing tracks the colour that actually gets applied — without a reload.
 *
 * The server renders the correct swatches on load (inject_palette +
 * align_astra_palette_swatches), but Style Guide colour edits recompute tokens
 * live and would otherwise leave the picker showing the pre-edit colours. This
 * mirrors the server palette assembly against the freshly-computed tokens:
 *   - shade slugs        ← computed.palette
 *   - semantic slugs     ← config.semantic_map × computed.tokens (+ overrides)
 *   - ast-global-color-N ← ASTRA_SHADE_MAP × computed.tokens
 *
 * Only existing entries are updated (matched by slug); nothing is added,
 * removed, or reordered, so the picker layout stays stable.
 *
 * @since x.x.x
 *
 * @param {Object} computed The style-guide/compute (or preview) result — { palette, tokens }.
 * @param {Object} config   The style-guide/config — { semantic_map, semantic_overrides }.
 * @return {void}
 */
export function syncEditorSwatches( computed, config ) {
	if ( ! computed ) {
		return;
	}

	const tokens = computed.tokens ?? {};

	// slug → colour, mirroring the server's merged theme palette.
	const map = {};

	if ( Array.isArray( computed.palette ) ) {
		computed.palette.forEach( ( entry ) => {
			if ( entry?.slug && entry?.color ) {
				map[ entry.slug ] = entry.color;
			}
		} );
	}

	const semanticMap = config?.semantic_map;
	if ( semanticMap && typeof semanticMap === 'object' ) {
		Object.entries( semanticMap ).forEach( ( [ slug, tokenKey ] ) => {
			if ( tokens[ tokenKey ] ) {
				map[ slug ] = tokens[ tokenKey ];
			}
		} );
	}

	const overrides = config?.semantic_overrides;
	if ( overrides && typeof overrides === 'object' ) {
		Object.entries( overrides ).forEach( ( [ slug, hex ] ) => {
			if ( hex ) {
				map[ slug ] = hex;
			}
		} );
	}

	Object.entries( ASTRA_SHADE_MAP ).forEach( ( [ index, tokenKey ] ) => {
		if ( tokens[ tokenKey ] ) {
			map[ `ast-global-color-${ index }` ] = tokens[ tokenKey ];
		}
	} );

	if ( ! Object.keys( map ).length ) {
		return;
	}

	const store = select( 'core/block-editor' );
	if ( ! store?.getSettings ) {
		return;
	}

	const settings     = store.getSettings();
	const features     = settings.__experimentalFeatures ?? {};
	const themePalette = features.color?.palette?.theme;
	if ( ! Array.isArray( themePalette ) || ! themePalette.length ) {
		return;
	}

	let changed = false;
	const next  = themePalette.map( ( entry ) => {
		if ( entry?.slug && map[ entry.slug ] && map[ entry.slug ] !== entry.color ) {
			changed = true;
			return { ...entry, color: map[ entry.slug ] };
		}
		return entry;
	} );

	// Dispatch only when a swatch colour actually moved — avoids a needless
	// settings churn (and re-render) on computes that changed no swatch.
	if ( changed ) {
		dispatch( 'core/block-editor' ).updateSettings( {
			__experimentalFeatures: {
				...features,
				color: {
					...( features.color ?? {} ),
					palette: {
						...( features.color?.palette ?? {} ),
						theme: next,
					},
				},
			},
		} );
	}
}
