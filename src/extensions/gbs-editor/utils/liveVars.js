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
 * The Astra slot map from a computed/preview payload, or an EMPTY map.
 *
 * Which slot carries which colour is decided at runtime by Astra's
 * `astra_4_8_9_compatibility()` flag, which the client cannot read — on a legacy
 * install slots 4/5 are swapped relative to a reorganized one. So the server ships
 * the resolved map as `astra_shade_map` on the computed/preview REST payloads
 * ({@see GlobalStylesBridge::astra_shade_map()}), and this is the only source of
 * truth the client has.
 *
 * Returns `{}` when that key is missing rather than falling back to a hardcoded
 * layout. This USED to guess the reorganized order, which fails silently and
 * badly: on a legacy install every guessed slot is wrong, so the live preview
 * writes Style Guide colours onto the wrong Astra swatches with nothing to signal
 * it. An empty map instead makes the callers no-ops — no alias CSS is emitted and
 * no swatch is realigned — so Astra simply keeps its own colours until the next
 * payload arrives. A missing preview is recoverable; a confidently wrong one is
 * not. The realistic trigger is a version skew where Pro ships ahead of the free
 * plugin and the field is absent from the response.
 *
 * @since x.x.x
 *
 * @param {Object|undefined} map `astra_shade_map` from a computed/preview payload.
 * @return {Object} slot index => shade token key, or `{}` when unavailable.
 */
const astraShadeMap = ( map ) =>
	map && 'object' === typeof map && Object.keys( map ).length ? map : {};

/**
 * Whether a palette entry's colour is already a `var(--<slug>)` reference to its
 * OWN variable.
 *
 * Astra registers its nine global colours as variable references rather than
 * literals, and the colour picker prints a swatch's raw value as its subtitle —
 * so the user sees the recognisable `--ast-global-color-0` under "Brand".
 * Replacing it with the resolved hex swapped that name for an opaque code.
 *
 * Rewriting is unnecessary: the same variable is redefined to the Style Guide
 * colour by {@see buildAstraAliasCSS}, so the reference already resolves to
 * exactly the hex we would have written. Mirrors the server-side guard in
 * GlobalStylesBridge::is_own_var_reference().
 *
 * @since x.x.x
 *
 * @param {*}      color Palette entry colour value.
 * @param {string} slug  Palette entry slug (e.g. `ast-global-color-0`).
 * @return {boolean} True when the value is a var() reference to its own variable.
 */
const isOwnVarReference = ( color, slug ) =>
	typeof color === 'string' &&
	'' !== color &&
	new RegExp( `var\\(\\s*--${ slug.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) }\\s*[,)]` ).test( color );

/**
 * The comparable hex behind a palette entry's colour value.
 *
 * A literal normalises directly; a `var(--<slug>)` self-reference resolves through
 * the supplied slot map to the hex it renders as. Without that, keeping the
 * reference would silently disable the dedupe below — a var() value normalises to
 * '' and so shields nothing, letting every Spectra swatch reappear alongside the
 * theme's own. Mirrors the server's GlobalStylesBridge::resolve_palette_hex().
 *
 * @since x.x.x
 *
 * @param {*}      color          Palette entry colour value.
 * @param {string} slug           Palette entry slug.
 * @param {Object} astraHexBySlug slug => hex for the Astra slots.
 * @return {string} Lower-case `#rrggbb`, or '' when not comparable.
 */
const resolvedHexKey = ( color, slug, astraHexBySlug ) => {
	const direct = hexKey( color );
	if ( direct ) {
		return direct;
	}
	return isOwnVarReference( color, slug ) ? hexKey( astraHexBySlug?.[ slug ] ) : '';
};

/**
 * Slugs that {@see syncEditorSwatches} has appended to the block-editor palette
 * this session. Tracked so a later call can REMOVE exactly the entries it added
 * (a deleted custom colour) without touching theme-owned swatches — an absent
 * slug is otherwise indistinguishable from a theme colour.
 *
 * @type {Set<string>}
 */
const appendedSwatchSlugs = new Set();

/**
 * Normalise a colour to a comparable `#rrggbb` key, or '' when it is not a plain
 * hex (CSS `var(--…)`, `transparent`, `currentColor`, empty). Mirrors the server's
 * normalize_hex_key so the live picker dedupe matches the REST/server one.
 *
 * @since x.x.x
 *
 * @param {*} color Raw colour value.
 * @return {string} Lower-case `#rrggbb`, or ''.
 */
function hexKey( color ) {
	if ( typeof color !== 'string' ) {
		return '';
	}
	const c = color.trim().toLowerCase();
	if ( /^#[0-9a-f]{6}$/.test( c ) ) {
		return c;
	}
	const m = c.match( /^#([0-9a-f])([0-9a-f])([0-9a-f])$/ );
	if ( m ) {
		return `#${ m[ 1 ] }${ m[ 1 ] }${ m[ 2 ] }${ m[ 2 ] }${ m[ 3 ] }${ m[ 3 ] }`;
	}
	return '';
}

/**
 * Colour-based swatch dedupe — the client-side twin of the server's
 * strip_picker_duplicate_entries(): drop sg-* aliases, and drop a swatch THIS live
 * sync appended (tracked in appendedSwatchSlugs) when it duplicates the colour of an
 * AUTHORITATIVE swatch (a theme/server entry we did not append). Authoritative
 * swatches are never removed, so two distinct theme roles that share a hex (e.g.
 * heading + a shuffled foreground) both survive. Non-hex values and unique colours
 * are always kept.
 *
 * @since x.x.x
 *
 * @param {Array}  palette        Palette entries.
 * @param {Object} astraHexBySlug slug => hex for the Astra slots, so a `var()`
 *                                self-reference still resolves to a comparable
 *                                colour ({@see resolvedHexKey}).
 * @return {Array} Deduped palette (an appended swatch loses to an authoritative one).
 */
function dedupeSwatchesThemeWins( palette, astraHexBySlug = {} ) {
	// AUTHORITATIVE swatches are every entry this live sync did NOT append itself —
	// the theme's own colours plus the server-injected ones. They are NEVER dropped,
	// so two distinct theme roles that happen to share a hex (e.g. `heading` and a
	// shuffled `foreground`) both survive — the earlier colour-only rule wrongly
	// collapsed them and lost the second role's swatch. Only a swatch WE appended
	// that duplicates an authoritative colour (the mapped/synced case, e.g. Astra's
	// `primary` vs `ast-global-color-0`) is dropped. Mirrors the server's
	// strip_picker_duplicate_entries, which keeps every theme.json role.
	const authorityColors = new Set();
	palette.forEach( ( entry ) => {
		const slug = entry?.slug || '';
		if ( /^sg-/.test( slug ) || appendedSwatchSlugs.has( slug ) ) {
			return;
		}
		const key = resolvedHexKey( entry?.color, slug, astraHexBySlug );
		if ( key ) {
			authorityColors.add( key );
		}
	} );

	const kept = [];
	palette.forEach( ( entry ) => {
		const slug = entry?.slug || '';
		if ( /^sg-/.test( slug ) ) {
			return; // drop sg-* aliases outright
		}
		if ( ! appendedSwatchSlugs.has( slug ) ) {
			kept.push( entry ); // authoritative (theme/server) swatch — always keep
			return;
		}
		const key = hexKey( entry?.color );
		if ( key && authorityColors.has( key ) ) {
			return; // our appended swatch duplicates an authoritative colour → drop
		}
		kept.push( entry );
	} );
	return kept;
}

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
 * Remove a live-injected <style> tag from all editor documents (host + iframe).
 *
 * The counterpart to {@see injectStyleSheet}: injectStyleSheet cannot neutralise
 * a sheet by writing an empty string (it early-returns on falsy CSS), so tearing
 * a live overlay back down needs an explicit removal. Used when discarding
 * unsaved Style Guide edits, to drop the preview overlays and let the canvas fall
 * back to the server-rendered saved palette.
 *
 * @since x.x.x
 *
 * @param {string} id The id of the style element to remove.
 * @return {void}
 */
export function removeStyleSheet( id ) {
	for ( const doc of getEditorDocuments() ) {
		doc.getElementById( id )?.remove();
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
 * Refresh the live-preview overlay after any out-of-band page-CSS save.
 *
 * Our `spectra-gen-custom-css-<id>` overlay (no suffix) only gets rebuilt by
 * OUR own code (page load, SPA nav, this panel's own edits) — a caller that
 * saves through `/global-styles/save` some other way (e.g. the ZIP AI
 * editor's chat-driven style edits) never triggers a rebuild, so the overlay
 * goes stale and — since it deliberately sits later in `<body>` to win the
 * cascade — silently shadows the fresh CSS the other caller just wrote (found
 * live, 2026-07-14: a style edit "succeeded" with no visible change).
 *
 * `apiFetch` middleware is the standard WP way to observe REST calls without
 * the caller needing to know we exist. Reuses `regeneratePageCSS()` as-is —
 * it already fetches + renders + rebuilds the overlay correctly.
 *
 * `apiFetch.use()` appends a permanent global middleware — register once. The
 * `useEffect([])` caller fires per mount (entity swap / StrictMode), so a
 * module-scoped flag guards against stacking duplicates.
 *
 * @since x.x.x
 *
 * @return {void}
 */
let externalPageCssWatcherRegistered = false;

export function watchExternalPageCssSaves() {
	if ( externalPageCssWatcherRegistered ) {
		return;
	}
	externalPageCssWatcherRegistered = true;
	apiFetch.use( ( options, next ) => {
		const isPageSave = options.method === 'POST'
			&& typeof options.path === 'string'
			&& options.path.indexOf( '/spectra-blocks/v1/global-styles/save' ) !== -1
			&& options.data?.scope === 'page';
		if ( ! isPageSave ) {
			return next( options );
		}
		return next( options ).then( ( result ) => {
			regeneratePageCSS( options.data.post_id );
			return result;
		} );
	} );
}

/**
 * Build the full WP preset palette CSS the canvas needs — every slug the server
 * `inject_palette()` registers: semantic slugs (`primary`, `sg-accent`, …)
 * resolved from the config's `semantic_map` × `computed.tokens`, plus any
 * entries in `computed.palette` (empty by default — raw tokens are no longer
 * published as presets).
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
 * the resolved Astra slot map — mirroring the server's get_astra_compat_css() /
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
 * @param {Object} tokens   computed.tokens (shade-key => hex).
 * @param {Object} shadeMap computed.astra_shade_map — the server-resolved slot map.
 *                          Omit and no aliases are emitted — the client cannot
 *                          know Astra's slot order on its own, and guessing it
 *                          writes colours onto the wrong swatches.
 * @return {string} CSS, or '' when there is nothing to emit.
 */
export function buildAstraAliasCSS( tokens, shadeMap ) {
	if ( ! tokens || typeof tokens !== 'object' ) {
		return '';
	}
	const decls = [];
	Object.entries( astraShadeMap( shadeMap ) ).forEach( ( [ index, tokenKey ] ) => {
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

		// UNSAVED gate: with no saved Style Guide the compute endpoint resolves to
		// inherited/default colours, and injecting those restyles a canvas the user
		// never configured (e.g. `foreground` snapping to the derived heading value
		// while the front end keeps the theme's own colour). Every server-side
		// injector applies the same rule (inject_palette et al.); this on-load path
		// must too. The Style Guide EDIT paths (ColorsPanel / StyleGuideContext)
		// keep their live preview injection — they don't come through here.
		if ( ! config?.saved ) {
			return;
		}

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
		// swatch showing one colour and the applied block another. Pro-gated:
		// surfacing Style Guide swatches in the picker is a Pro capability, and the
		// server strips them when Pro is off — re-appending here would leak them back.
		if ( config?.pro ) {
			syncEditorSwatches( computed, config );
		}
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
 * @param {number|string} postId The current post ID. No-ops when it does not resolve to a
 *                               positive integer — FSE template IDs are strings (e.g.
 *                               "astra//home") and have no page-scoped CSS to render.
 * @return {Promise<void>}
 */
export async function regeneratePageCSS( postId ) {
	const pageId = Number( postId ) || 0;
	if ( pageId <= 0 ) { return; }
	try {
		const saveData = await apiFetch( {
			path: `/spectra-blocks/v1/global-styles/save?scope=page&post_id=${ pageId }`,
		} );
		const payload = saveData?.payload;
		if ( ! payload || Object.keys( payload ).length === 0 ) { return; }
		const renderData = await apiFetch( {
			path: '/spectra-blocks/v1/global-styles/render',
			method: 'POST',
			data: { payload, post_id: pageId, scope: 'page' },
		} );
		const css = renderData?.css;
		if ( css ) {
			injectStyleSheet( `spectra-gen-custom-css-${ pageId }`, css );
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
 *   - semantic slugs     ← config.semantic_map × computed.tokens (+ overrides)
 *   - ast-global-color-N ← computed.astra_shade_map × computed.tokens
 *
 * Existing entries are updated in place (matched by slug), Style-Guide-owned
 * slugs missing from the palette (e.g. a just-added custom colour) are appended,
 * and previously-appended slugs that leave the config (a removed custom colour)
 * are pruned. Theme-owned swatches are never added or removed, so the picker
 * layout stays stable.
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

	// Per-slug overrides. Prefer the live `custom_colors` (present on the pro
	// editor's draft config) over the server-derived `semantic_overrides` — the
	// derived key only refreshes on fetch, so it lags a just-added custom colour.
	const customColors = config?.custom_colors;
	if ( customColors && typeof customColors === 'object' ) {
		Object.entries( customColors ).forEach( ( [ slug, def ] ) => {
			const hex = def?.hex ?? def;
			if ( hex ) {
				map[ slug ] = hex;
			}
		} );
	} else if ( config?.semantic_overrides && typeof config.semantic_overrides === 'object' ) {
		Object.entries( config.semantic_overrides ).forEach( ( [ slug, hex ] ) => {
			if ( hex ) {
				map[ slug ] = hex;
			}
		} );
	}

	Object.entries( astraShadeMap( computed?.astra_shade_map ) ).forEach( ( [ index, tokenKey ] ) => {
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

	// REMOVE entries this function previously appended that are no longer in the
	// config (a deleted custom colour) — mirrors the ADD below so the picker drops
	// the swatch live, without a reload. Only self-appended slugs are pruned, so
	// theme-owned swatches are never touched.
	let next = themePalette.filter( ( entry ) => {
		if ( entry?.slug && appendedSwatchSlugs.has( entry.slug ) && ! map[ entry.slug ] ) {
			appendedSwatchSlugs.delete( entry.slug );
			changed = true;
			return false;
		}
		return true;
	} );

	next = next.map( ( entry ) => {
		if (
			entry?.slug &&
			map[ entry.slug ] &&
			map[ entry.slug ] !== entry.color &&
			! isOwnVarReference( entry.color, entry.slug )
		) {
			changed = true;
			return { ...entry, color: map[ entry.slug ] };
		}
		return entry;
	} );

	// APPEND slugs the palette doesn't carry yet — a just-added custom colour has
	// no entry to update, so without this it only shows up after a reload (when
	// the server's inject_palette() re-registers the palette). Astra alias slugs
	// are excluded: those belong to the theme's own palette and stay update-only.
	// sg-* alias slugs are excluded too — they are render-compat aliases, never
	// pickable swatches; the server strips them from every picker surface
	// (strip_sg_alias_swatches_from_rest / remove_sg_alias_swatches), so
	// re-appending them here would bring the duplicate swatches back live.
	// Duplicate colours are handled by the colour dedupe below (theme swatch wins),
	// so appending is only gated on slug + name here.
	const present      = new Set( next.map( ( entry ) => entry?.slug ) );
	const presentNames = new Set(
		next.map( ( entry ) => ( typeof entry?.name === 'string' ? entry.name.trim().toLowerCase() : '' ) ).filter( Boolean )
	);
	Object.entries( map ).forEach( ( [ slug, hex ] ) => {
		if ( present.has( slug ) || /^ast-global-color-\d+$/.test( slug ) || /^sg-/.test( slug ) ) {
			return;
		}
		const name = customColors?.[ slug ]?.name
			|| slug.replace( /^sg-/, '' ).replace( /-+/g, ' ' ).replace( /\b\w/g, ( c ) => c.toUpperCase() );
		const nameKey = name.trim().toLowerCase();
		if ( nameKey && presentNames.has( nameKey ) ) {
			return;
		}
		presentNames.add( nameKey );
		next.push( { slug, color: hex, name } );
		appendedSwatchSlugs.add( slug );
		changed = true;
	} );

	// Final dedupe (mirrors the server's strip_picker_duplicate_entries): drop a
	// swatch WE appended that duplicates an authoritative (theme/server) colour —
	// keeping the theme swatch — while NEVER removing an authoritative swatch, so two
	// distinct theme roles that share a hex (e.g. heading + a shuffled foreground)
	// both stay. Runs on the whole palette, so it also clears any dupe left over from
	// an earlier sync in this session.
	// `map` carries ast-global-color-N => hex, which lets the dedupe resolve the
	// var() references the Astra entries keep (see resolvedHexKey).
	const deduped = dedupeSwatchesThemeWins( next, map );
	if ( deduped.length !== next.length ) {
		next    = deduped;
		changed = true;
	}

	// Dispatch only when a swatch actually moved or was added — avoids a needless
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
