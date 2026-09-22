// Contract test for the Zip AI page-notice logic. Run:
// `npm run test:contract` (plain node, no deps).
//
// The editor extension (`src/extensions/zip-ai-notice/index.js`) can't be unit
// tested cheaply — it's bound to the DOM and WordPress data. So the parts that
// CAN be pinned live in a framework-free module (`logic.mjs`) that BOTH the
// extension and this test import. Here we pin:
//
//   1. The client render gate — server `shouldRender` AND runtime gates.
//   2. Variant selection — Pro vs free, and whether the link opens the editor.
//   3. The dismiss request — REST path/method/meta payload, incl. the `pages`
//      fallback.
//   4. Cross-file coupling — identification is server-side via the canonical
//      check; meta keys match PHP; and the banner is the Spectra-branded design
//      (brand colour + Spectra icon + inline link), not a plain core notice.

import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

import {
	IMPORTED_META_KEY,
	DISMISS_OPTION_KEY,
	shouldRenderNotice,
	PRO_STATE,
	getCtaAction,
	buildDismissRequest,
} from '../../src/extensions/zip-ai-notice/logic.mjs';

const here = dirname( fileURLToPath( import.meta.url ) );
const plugin = resolve( here, '..', '..' );
const read = ( p ) => readFileSync( resolve( plugin, p ), 'utf8' );

let failures = 0;
const fail = ( msg ) => {
	failures++;
	console.error( `FAIL  ${ msg }` );
};
const ok = ( msg ) => console.log( `ok    ${ msg }` );
const assert = ( cond, msg ) => ( cond ? ok( msg ) : fail( msg ) );

// ---------------------------------------------------------------------------
// 1. Client render gate.
// ---------------------------------------------------------------------------
assert(
	shouldRenderNotice( { shouldRender: true, hidden: false, hasContainer: true } ) === true,
	'renders when the server says so and a container is mounted'
);
assert(
	shouldRenderNotice( { shouldRender: false, hidden: false, hasContainer: true } ) === false,
	'does not render when the server says no (not Zip AI-built / dismissed / Zip AI off)'
);
assert(
	shouldRenderNotice( { shouldRender: true, hidden: true, hasContainer: true } ) === false,
	'does not render after this-session dismissal'
);
assert(
	shouldRenderNotice( { shouldRender: true, hidden: false, hasContainer: false } ) === false,
	'does not render before the portal target mounts'
);
assert( shouldRenderNotice() === false, 'no input → does not render (no throw)' );

// ---------------------------------------------------------------------------
// 2. CTA action by Spectra Pro state (copy is identical for all).
// ---------------------------------------------------------------------------
assert( getCtaAction( PRO_STATE.ACTIVE ) === 'gbs-editor', 'active → open the Global Styles editor' );
assert( getCtaAction( PRO_STATE.INSTALLED ) === 'nudge-modal', 'installed → open the upgrade popup' );
assert( getCtaAction( PRO_STATE.NOT_INSTALLED ) === 'pricing', 'not installed → link to pricing' );
assert( getCtaAction( undefined ) === 'pricing', 'unknown state → safe pricing fallback' );

// ---------------------------------------------------------------------------
// 3. Dismiss request — site-wide option via the core settings endpoint.
// ---------------------------------------------------------------------------
const req = buildDismissRequest();
assert( req.path === '/wp/v2/settings', 'dismiss hits the site settings endpoint (not a page)' );
assert( req.method === 'POST', 'dismiss is a POST' );
assert(
	req.data && req.data[ DISMISS_OPTION_KEY ] === true,
	'dismiss sets the site-wide dismissed option = true'
);

// ---------------------------------------------------------------------------
// 4. Cross-file coupling.
// ---------------------------------------------------------------------------
const extensionManagerPhp = read( 'includes/class-extension-manager.php' );
const assetLoaderPhp = read( 'includes/class-asset-loader.php' );
const componentJs = read( 'src/extensions/zip-ai-notice/index.js' );
const scss = read( 'src/extensions/zip-ai-notice/style.scss' );

// Identification is server-side, reusing the ONE canonical check.
assert(
	assetLoaderPhp.includes( 'public static function is_zip_built_page' ),
	'AssetLoader::is_zip_built_page is public (shared canonical check)'
);
assert(
	extensionManagerPhp.includes( 'AssetLoader::is_zip_built_page' ),
	'PHP decides render via the canonical is_zip_built_page (no re-derivation)'
);
assert(
	extensionManagerPhp.includes( "'shouldRender'" ),
	'PHP computes and localizes the shouldRender decision'
);
assert(
	extensionManagerPhp.includes( "'state'" ) &&
		extensionManagerPhp.includes( "'not_installed'" ),
	'PHP localizes the 3-state Pro status for the CTA'
);
assert(
	assetLoaderPhp.includes( `'${ IMPORTED_META_KEY }'` ) ||
		assetLoaderPhp.includes( `= '${ IMPORTED_META_KEY }'` ),
	`the ERA importer marker key is intact (${ IMPORTED_META_KEY })`
);
assert(
	extensionManagerPhp.includes( 'register_setting(' ) &&
		extensionManagerPhp.includes( `'${ DISMISS_OPTION_KEY }'` ),
	`PHP registers the site-wide dismissal option (${ DISMISS_OPTION_KEY })`
);
assert(
	extensionManagerPhp.includes( `get_option( '${ DISMISS_OPTION_KEY }'` ),
	'PHP reads the site-wide option to gate shouldRender (not per-page meta)'
);
assert(
	! extensionManagerPhp.includes( 'register_post_meta' ),
	'per-page dismissal meta is gone (dismissal is site-wide now)'
);

// The banner is the branded design (matches the ERA mock), not a plain notice.
assert(
	componentJs.includes( 'shouldRender' ) && componentJs.includes( 'shouldRenderNotice' ),
	'component honours the server-side shouldRender decision'
);
assert(
	componentJs.includes( 'SpectraIcon' ),
	'banner renders the Spectra icon (brand mark)'
);
assert(
	componentJs.includes( 'createInterpolateElement' ),
	'banner uses an inline link inside the sentence (not an action button)'
);
assert(
	scss.includes( '#5644fd' ) && scss.includes( '&__icon' ),
	'brand colour (#5644fd) + icon tile styled per the mock'
);

// Unified copy + 3-state CTA wiring.
assert(
	componentJs.includes( 'manage your site settings through' ),
	'copy is the single unified sentence for all Pro states'
);
assert(
	componentJs.includes( '__spectraGBSEditorV2' ) &&
		componentJs.includes( 'spectraGSOpenModal' ),
	'active → opens the real Global Styles editor'
);
assert(
	componentJs.includes( '__spectraGbsProNudge' ),
	'installed → opens the gbs-pro-nudge upgrade modal'
);
const proNudgeJs = read( 'src/extensions/gbs-pro-nudge/index.js' );
assert(
	proNudgeJs.includes( 'window.__spectraGbsProNudge = nudgeSingleton' ),
	'gbs-pro-nudge exposes its modal opener for the notice to call'
);

// ---------------------------------------------------------------------------
if ( failures > 0 ) {
	console.error( `\n${ failures } contract check(s) failed.` );
	process.exit( 1 );
}
console.log( '\nZip AI notice logic contract OK.' );
