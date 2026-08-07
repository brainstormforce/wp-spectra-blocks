/**
 * UI Audit: HTML Attributes Extension
 *
 * Captures screenshots at every interaction point for UX review.
 * Reference: GenerateBlocks-style Name/Value repeater (images shared by user).
 */
const { test } = require( '@playwright/test' );

const WP_URL = 'http://spectra-local-site.local:10019';
const OUT = '/tmp/ui-audit';

async function wpLogin( page ) {
	await page.goto( `${ WP_URL }/wp-login.php`, { timeout: 30000 } );
	await page.fill( '#user_login', 'root' );
	await page.fill( '#user_pass', 'root' );
	await page.click( '#wp-submit' );
	await page.waitForLoadState( 'load', { timeout: 60000 } );
}

async function openNewPage( page ) {
	await page.goto( `${ WP_URL }/wp-admin/post-new.php?post_type=page`, { timeout: 30000 } );
	await page.waitForTimeout( 3000 );
	await page
		.locator( 'button[aria-label="Close"], .edit-post-welcome-guide button' )
		.first()
		.click( { timeout: 2000 } )
		.catch( () => {} );
	await page.waitForTimeout( 500 );
}

async function insertBlock( page, searchTerm ) {
	const inserterBtn = page.locator(
		'button[aria-label="Block Inserter"], button[aria-label="Toggle block inserter"]'
	);
	await inserterBtn.first().click( { timeout: 10000 } );
	await page.waitForTimeout( 600 );
	const search = page.locator( 'input[placeholder="Search"], input[type="search"]' ).first();
	await search.fill( searchTerm );
	await page.waitForTimeout( 800 );
	await page
		.locator( '.block-editor-block-types-list__item, [role="option"]' )
		.first()
		.click( { timeout: 8000 } );
	await page.waitForTimeout( 1000 );
	await inserterBtn.first().click( { timeout: 3000 } ).catch( () => {} );
	await page.waitForTimeout( 600 );
}

async function scrollInspectorToBottom( page ) {
	await page.evaluate( () => {
		[ '.block-editor-block-inspector', '.interface-complementary-area' ].forEach( ( sel ) => {
			const el = document.querySelector( sel );
			if ( el ) {
				el.scrollTop = el.scrollHeight;
			}
		} );
		document.querySelectorAll( '[role="tabpanel"], .components-panel' ).forEach( ( el ) => {
			if ( el.scrollHeight > el.clientHeight ) {
				el.scrollTop = el.scrollHeight;
			}
		} );
	} );
	await page.waitForTimeout( 300 );
}

/**
 * Scroll the inspector until `locator` is visible, then screenshot the sidebar.
 * @param {import('@playwright/test').Page}    page
 * @param {import('@playwright/test').Locator} locator
 * @param {string}                             outName
 */
async function scrollToAndShot( page, locator, outName ) {
	await locator.scrollIntoViewIfNeeded( { timeout: 5000 } ).catch( () => {} );
	await page.waitForTimeout( 200 );
	const sidebar = page.locator( '.interface-complementary-area' ).first();
	await sidebar.screenshot( { path: `${ OUT }/${ outName }.png` } ).catch( async () => {
		await page.screenshot( { path: `${ OUT }/${ outName }.png` } );
	} );
}

/**
 * Type in the sentinel (always-visible empty row) — new pattern, no Add button.
 * @param {import('@playwright/test').Page} page
 * @param {string}                          name
 * @param {string}                          value
 */
async function fillSentinel( page, name, value ) {
	const nameField = page.locator( 'input[aria-label="Attribute name"]' ).last();
	await nameField.fill( name );
	await page.waitForTimeout( 200 );
	if ( value ) {
		const valueField = page.locator( 'input[aria-label="Attribute value"]' ).last();
		await valueField.fill( value );
	}
	await page.waitForTimeout( 200 );
}

test.describe( 'UI Audit — HTML Attributes Extension', () => {

	test( '01 — Inspector: Text block selected (default state)', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );
		// Screenshot the top of the inspector (General section with HTML TAG).
		const general = page.locator( 'text=General' ).first();
		await scrollToAndShot( page, general, '01-text-default-state' );
	} );

	test( '02 — Tag Attributes: Tag changed to <time>', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		await page.locator( '.components-select-control__input' ).first().selectOption( 'time' );
		await page.waitForTimeout( 600 );

		// Scroll to Tag Attributes panel and screenshot.
		const tagPanel = page.locator( 'text=Tag Attributes' ).first();
		await scrollToAndShot( page, tagPanel, '02-tag-attributes-time' );
	} );

	test( '03 — Tag Attributes: datetime field filled in', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		await page.locator( '.components-select-control__input' ).first().selectOption( 'time' );
		await page.waitForTimeout( 600 );

		const datetimeInput = page
			.locator( '.components-base-control:has-text("Date/Time") input' )
			.first();
		await datetimeInput.fill( '2024-06-15' );
		await page.waitForTimeout( 300 );

		await scrollToAndShot( page, datetimeInput, '03-tag-attributes-datetime-filled' );
	} );

	test( '04 — Advanced panel: collapsed state (as-found)', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		await scrollInspectorToBottom( page );
		// Screenshot the bottom of the inspector where Advanced is collapsed.
		const advancedHeader = page.locator( 'text=Advanced' ).last();
		await scrollToAndShot( page, advancedHeader, '04-advanced-collapsed' );
	} );

	test( '05 — Settings tab: Custom Attributes section (empty state)', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		// Custom Attributes is in the Settings tab now — no Advanced expansion needed.
		await scrollInspectorToBottom( page );
		const attrLabel = page.locator( 'text=Custom Attributes' ).first();
		await scrollToAndShot( page, attrLabel, '05-custom-attrs-empty' );
	} );

	test( '06 — Custom Attributes: sentinel row (type-to-add UI)', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		await scrollInspectorToBottom( page );
		const sentinel = page.locator( 'input[aria-label="Attribute name"]' ).last();
		await scrollToAndShot( page, sentinel, '06-sentinel-row-empty' );
	} );

	test( '07 — Custom Attributes: row filled (data-aos / fade-up)', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		await scrollInspectorToBottom( page );
		await fillSentinel( page, 'data-aos', 'fade-up' );

		const attrLabel = page.locator( 'text=Custom Attributes' ).first();
		await scrollToAndShot( page, attrLabel, '07-row-filled-data-aos' );
	} );

	test( '08 — Custom Attributes: multiple rows', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		await scrollInspectorToBottom( page );
		await fillSentinel( page, 'data-aos', 'fade-up' );
		await fillSentinel( page, 'data-aos-duration', '800' );
		await fillSentinel( page, 'data-track', 'hero-cta' );

		const attrLabel = page.locator( 'text=Custom Attributes' ).first();
		await scrollToAndShot( page, attrLabel, '08-multiple-rows' );
	} );

	test( '09 — Security: onclick attribute warning', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		await scrollInspectorToBottom( page );
		await fillSentinel( page, 'onclick', '' );
		await page.waitForTimeout( 400 );

		const attrLabel = page.locator( 'text=Custom Attributes' ).first();
		await scrollToAndShot( page, attrLabel, '09-onclick-warning' );
	} );

	test( '10 — Container block: nav tag ARIA Label', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Container' );

		const selects = page.locator( 'select.components-select-control__input' );
		const count = await selects.count();
		for ( let i = 0; i < count; i++ ) {
			const s = selects.nth( i );
			const opts = await s
				.evaluate( ( el ) => Array.from( el.options ).map( ( o ) => o.value ) )
				.catch( () => [] );
			if ( opts.includes( 'nav' ) ) {
				await s.selectOption( 'nav' );
				break;
			}
		}
		await page.waitForTimeout( 600 );

		const tagPanel = page.locator( 'text=Tag Attributes' ).first();
		await scrollToAndShot( page, tagPanel, '10-container-nav-aria-label' );
	} );

	test( '11 — Full workflow: time tag + custom attrs (Settings tab)', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		await page.locator( '.components-select-control__input' ).first().selectOption( 'time' );
		await page.waitForTimeout( 600 );

		await page
			.locator( '.components-base-control:has-text("Date/Time") input' )
			.first()
			.fill( '2024-06-15' );
		await page.waitForTimeout( 300 );

		// Custom Attributes is in the Settings tab — scroll down, no Advanced expansion.
		await scrollInspectorToBottom( page );
		await fillSentinel( page, 'data-aos', 'fade-up' );
		await page.waitForTimeout( 300 );

		const attrLabel = page.locator( 'text=Custom Attributes' ).first();
		await scrollToAndShot( page, attrLabel, '11-full-workflow-attrs' );
	} );
} );
