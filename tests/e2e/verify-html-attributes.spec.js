const { test, expect } = require( '@playwright/test' );

const WP_URL = 'http://spectra-local-site.local:10019';

async function wpLogin( page ) {
	await page.goto( `${ WP_URL }/wp-login.php`, { timeout: 30000 } );
	await page.fill( '#user_login', 'root' );
	await page.fill( '#user_pass', 'root' );
	await page.click( '#wp-submit' );
	await page.waitForLoadState( 'load', { timeout: 60000 } );
}

async function getRestNonce( page ) {
	const res = await page.request.get(
		`${ WP_URL }/wp-admin/admin-ajax.php?action=rest-nonce`
	);
	return ( await res.text() ).trim();
}

/**
 * Open a blank page editor and dismiss any welcome modal.
 * @param {import('@playwright/test').Page} page
 */
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

/**
 * Insert a block via the block inserter by searching. Block is auto-selected after insert.
 * @param {import('@playwright/test').Page} page
 * @param {string}                          searchTerm
 */
async function insertBlock( page, searchTerm ) {
	const inserterBtn = page.locator(
		'button[aria-label="Block Inserter"], button[aria-label="Toggle block inserter"]'
	);
	await inserterBtn.first().click( { timeout: 10000 } );
	await page.waitForTimeout( 600 );

	const search = page.locator( 'input[placeholder="Search"], input[type="search"]' ).first();
	await search.fill( searchTerm );
	await page.waitForTimeout( 800 );

	await page.locator( '.block-editor-block-types-list__item, [role="option"]' ).first().click( { timeout: 8000 } );
	await page.waitForTimeout( 1000 );

	// Close inserter
	await inserterBtn.first().click( { timeout: 3000 } ).catch( () => {} );
	await page.waitForTimeout( 600 );
}

/**
 * Scroll every scrollable container in the sidebar to reveal below-fold content.
 * @param {import('@playwright/test').Page} page
 */
async function scrollInspectorToBottom( page ) {
	await page.evaluate( () => {
		[
			'.block-editor-block-inspector',
			'.interface-complementary-area',
		].forEach( ( sel ) => {
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

test.describe( 'HTML Attributes Extension', () => {
	let frontendUrl;

	test.beforeAll( async ( { browser } ) => {
		const ctx = await browser.newContext();
		const pg = await ctx.newPage();
		await wpLogin( pg );
		const nonce = await getRestNonce( pg );

		const res = await pg.request.post( `${ WP_URL }/?rest_route=/wp/v2/pages`, {
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
			data: {
				title: 'HTML Attributes Frontend Test',
				content:
					'<!-- wp:spectra/content {"tagName":"time","text":"Test content","htmlAttributes":[{"attribute":"datetime","value":"2024-01-15"},{"attribute":"data-test","value":"hello"}]} -->' +
					'<time class="wp-block-spectra-content">Test content</time>' +
					'<!-- /wp:spectra/content -->' +
					'<!-- wp:spectra/container {"htmlTag":"nav","htmlAttributes":[{"attribute":"aria-label","value":"Main navigation test"}]} -->' +
					'<nav class="wp-block-spectra-container spectra-block-container"></nav>' +
					'<!-- /wp:spectra/container -->',
				status: 'publish',
			},
		} );
		const json = await res.json();
		frontendUrl = json.link;
		console.log( 'Frontend test page:', frontendUrl );
		await ctx.close();
	} );

	test( '1. Custom Attributes panel visible on spectra/content block (Settings tab)', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		await page.screenshot( { path: '/tmp/ss-1a-after-insert.png' } );

		// Custom Attributes is now in the Settings tab (group="settings"), not Advanced.
		// No need to expand the Advanced panel — scroll the inspector to find it.
		await scrollInspectorToBottom( page );
		await page.screenshot( { path: '/tmp/ss-1b-scrolled.png' } );

		const panel = page.locator( 'text=Custom Attributes' ).first();
		await expect( panel ).toBeVisible( { timeout: 10000 } );
		console.log( '✅ Custom Attributes panel visible in Settings tab' );
	} );

	test( '2. Contextual Date/Time field appears inside General panel for <time> tag', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		await page.screenshot( { path: '/tmp/ss-2a-after-insert.png' } );

		// Change HTML TAG to "time" via the first SelectControl in the inspector.
		const htmlTagSelect = page.locator( '.components-select-control__input' ).first();
		await htmlTagSelect.selectOption( 'time' ).catch( async () => {
			await page
				.locator( '.components-base-control:has-text("HTML Tag") select' )
				.first()
				.selectOption( 'time', { timeout: 5000 } );
		} );
		await page.waitForTimeout( 800 );

		await page.screenshot( { path: '/tmp/ss-2b-time-selected.png' } );

		// Date/Time field is now a ToolsPanelItem inside the General panel — no separate Tag Attributes panel.
		const datetimeField = page
			.locator( 'label:has-text("Date/Time"), .components-base-control:has-text("Date/Time")' )
			.first();
		await expect( datetimeField ).toBeVisible( { timeout: 8000 } );

		await page.screenshot( { path: '/tmp/ss-2c-datetime-visible.png' } );
		console.log( '✅ Date/Time field visible inside General panel for <time>' );
	} );

	test( '3. Container block shows ARIA Label for <nav> tag inside Container panel', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Container' );

		await page.screenshot( { path: '/tmp/ss-3a-container-inserted.png' } );

		// Container shows a variation (layout) picker on first insert — inside the editor iframe.
		const editorCanvas = page.frameLocator(
			'iframe[title="Editor canvas"], iframe[name="editor-canvas"], iframe.editor-canvas__iframe'
		);
		await editorCanvas
			.locator( '.block-editor-block-variation-picker__variations button, button[class*="block-variation"]' )
			.first()
			.click( { timeout: 8000 } )
			.catch( async () => {
				await editorCanvas.locator( '.block-editor-block-variation-picker button' ).first().click( { timeout: 5000 } ).catch( () => {} );
			} );
		await page.waitForTimeout( 1000 );

		// Find the HTML tag select in the container inspector and set it to "nav".
		let tagChanged = false;
		const tagSelects = page.locator( 'select.components-select-control__input' );
		const count = await tagSelects.count();
		for ( let i = 0; i < count; i++ ) {
			const sel = tagSelects.nth( i );
			const opts = await sel.evaluate( ( el ) =>
				Array.from( el.options ).map( ( o ) => o.value )
			).catch( () => [] );
			if ( opts.includes( 'nav' ) ) {
				await sel.selectOption( 'nav' );
				tagChanged = true;
				break;
			}
		}

		if ( ! tagChanged ) {
			await scrollInspectorToBottom( page );
			const allSelects = page.locator( 'select' );
			const total = await allSelects.count();
			for ( let i = 0; i < total; i++ ) {
				const sel = allSelects.nth( i );
				const opts = await sel.evaluate( ( el ) =>
					Array.from( el.options ).map( ( o ) => o.value )
				).catch( () => [] );
				if ( opts.includes( 'nav' ) ) {
					await sel.selectOption( 'nav' );
					tagChanged = true;
					break;
				}
			}
		}

		await page.waitForTimeout( 800 );
		await page.screenshot( { path: '/tmp/ss-3b-nav-set.png' } );

		// ARIA Label is now a ToolsPanelItem inside the Container panel — no separate Tag Attributes panel.
		const ariaField = page
			.locator( 'label:has-text("ARIA Label"), .components-base-control:has-text("ARIA Label")' )
			.first();
		await expect( ariaField ).toBeVisible( { timeout: 8000 } );

		await page.screenshot( { path: '/tmp/ss-3c-aria-visible.png' } );
		console.log( '✅ ARIA Label field visible inside Container panel for <nav>' );
	} );

	test( '4. onclick attribute shows warning in Custom Attributes section', async ( { page } ) => {
		await wpLogin( page );
		await openNewPage( page );
		await insertBlock( page, 'Text' );

		// Custom Attributes is in Settings tab, no Advanced panel expansion needed.
		await scrollInspectorToBottom( page );
		await page.screenshot( { path: '/tmp/ss-4a-scrolled.png' } );

		const panelLabel = page.locator( 'text=Custom Attributes' ).first();
		await expect( panelLabel ).toBeVisible( { timeout: 10000 } );

		// Click "Add Attribute" to get a new row, then type into it.
		const addBtn = page.locator( 'button:has-text("Add Attribute")' ).first();
		await addBtn.click( { timeout: 5000 } );
		await page.waitForTimeout( 300 );
		const nameField = page.locator( 'input[aria-label="Attribute name"]' ).last();
		await nameField.fill( 'onclick' );
		await page.waitForTimeout( 500 );

		await page.screenshot( { path: '/tmp/ss-4b-onclick-warning.png' } );

		const warning = page.locator( 'text=Event handler' ).first();
		await expect( warning ).toBeVisible( { timeout: 5000 } );
		console.log( '✅ onclick attribute shows warning notice' );
	} );

	test( '5. Frontend renders attributes on block wrappers', async ( { page } ) => {
		await page.goto( frontendUrl, { timeout: 30000 } );
		await page.waitForLoadState( 'networkidle', { timeout: 15000 } );
		await page.screenshot( { path: '/tmp/ss-5-frontend.png' } );

		const html = await page.content();

		expect( html.includes( 'datetime="2024-01-15"' ) ).toBe( true );
		console.log( '✅ datetime="2024-01-15" found' );

		expect( html.includes( 'data-test="hello"' ) ).toBe( true );
		console.log( '✅ data-test="hello" found' );

		expect( html.includes( 'aria-label="Main navigation test"' ) ).toBe( true );
		console.log( '✅ aria-label="Main navigation test" found' );
	} );
} );
