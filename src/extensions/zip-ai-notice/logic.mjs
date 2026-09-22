/**
 * Zip AI page notice — pure decision logic.
 *
 * Framework-free so the same functions the editor extension runs at runtime can
 * be exercised by a plain-node contract test (no DOM, no WordPress mocks). The
 * component in `index.js` imports these; the test in
 * `tests/contract/zip-ai-notice-logic-contract.mjs` asserts them.
 *
 * The heavy render decision (Zip AI active + the page is Zip AI-built + not
 * dismissed) is made SERVER-SIDE and handed to the client as a single
 * `shouldRender` boolean — see
 * `includes/class-extension-manager.php::get_zipai_notice_data`. The client
 * only layers on the two runtime gates below.
 *
 * @since 1.0.9
 */

/**
 * The ERA importer's marker meta, read server-side by
 * `AssetLoader::is_zip_built_page` to decide whether a page is Zip AI-built.
 * Kept here so the contract test can pin the coupling.
 */
export const IMPORTED_META_KEY = '_zipai_imported';

/**
 * Site-wide dismissal option. Dismissal is per-SITE, not per-page: closing the
 * notice once hides it everywhere, permanently. Registered as a setting in
 * `includes/class-extension-manager.php` and persisted through the core
 * `/wp/v2/settings` endpoint. MUST match the registered option name.
 */
export const DISMISS_OPTION_KEY = 'spectra_blocks_zipai_notice_dismissed';

/**
 * Whether the banner should render now. The server already decided the durable
 * part (`shouldRender`); this adds the runtime gates: not dismissed this
 * session, and a portal target is mounted.
 *
 * @param {Object}  state              Render inputs.
 * @param {boolean} state.shouldRender Server decision (Zip AI + zip-built + not dismissed).
 * @param {boolean} state.hidden       Dismissed this session (local state).
 * @param {boolean} state.hasContainer Portal target is mounted.
 * @return {boolean} True when the banner should render.
 */
export function shouldRenderNotice( { shouldRender, hidden, hasContainer } = {} ) {
	return Boolean( shouldRender ) && ! hidden && Boolean( hasContainer );
}

/** Spectra Pro install/activation states (mirror the PHP `state`). */
export const PRO_STATE = {
	NOT_INSTALLED: 'not_installed',
	INSTALLED: 'installed',
	ACTIVE: 'active',
};

/**
 * The "Spectra Blocks Global Styles" CTA behaviour, by Spectra Pro state. The
 * copy is identical for everyone — only the action differs:
 *   - `active`        → `gbs-editor`  : open the real Global Styles editor
 *                                       (same as the sidebar "Manage Global Styles").
 *   - `installed`     → `nudge-modal` : open the upgrade popup (gbs-pro-nudge modal).
 *   - `not_installed` → `pricing`     : link out to the Spectra Pro pricing page.
 *
 * Anything unrecognised falls back to `pricing` (the safe, non-JS default).
 *
 * @param {string} state Spectra Pro state.
 * @return {'gbs-editor'|'nudge-modal'|'pricing'} CTA action.
 */
export function getCtaAction( state ) {
	if ( PRO_STATE.ACTIVE === state ) {
		return 'gbs-editor';
	}
	if ( PRO_STATE.INSTALLED === state ) {
		return 'nudge-modal';
	}
	return 'pricing';
}

/**
 * The REST request that persists the site-wide dismissal through the core
 * settings endpoint — one write, no page context.
 *
 * @return {{path: string, method: string, data: Object}} apiFetch options.
 */
export function buildDismissRequest() {
	return {
		path: '/wp/v2/settings',
		method: 'POST',
		data: { [ DISMISS_OPTION_KEY ]: true },
	};
}
