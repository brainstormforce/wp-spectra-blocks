/**
 * E2E: GBS Class Flyout
 *
 * Verifies the "Manage Classes" inspector button (Pro-only) opens the popover
 * flyout, that inline CSS editing works, and that the CSS autocomplete
 * dropdown functions correctly inside the flyout's overflow-clipped container.
 *
 * Requirements: spectra-blocks-pro must be active in the test environment.
 */
const { test, expect } = require( '@playwright/test' );

const WP_URL    = 'http://spectra-local-site.local:10019';
const LOGIN_URL = `${ WP_URL }/wp-login.php`;

async function wpLogin( page ) {
	await page.goto( LOGIN_URL );
	await page.fill( '#user_login', 'root' );
	await page.fill( '#user_pass', 'root' );
	await page.click( '#wp-submit' );
	// Local by Flywheel redirect may strip the port — wait for either URL pattern,
	// then force-navigate back to the portted admin URL.
	await page.waitForURL( /wp-admin/, { timeout: 30000 } ).catch( () => {} );
	const url = page.url();
	if ( ! url.includes( ':10019' ) ) {
		await page.goto( `${ WP_URL }/wp-admin/` );
	}
}

async function openNewPost( page ) {
	await page.goto( `${ WP_URL }/wp-admin/post-new.php` );
	// Dismiss welcome modal if present.
	const welcomeClose = page.locator( '.components-modal__header button[aria-label="Close"]' ).first();
	if ( await welcomeClose.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
		await welcomeClose.click();
	}
	await page.waitForSelector( '.editor-visual-editor, .block-editor-writing-flow', { timeout: 20000 } );
}

async function waitForGBSGlobal( page ) {
	await page.waitForFunction(
		() => typeof window.__spectraGBSEditor?.open === 'function',
		{ timeout: 15000 }
	);
}

async function ensureSidebarOpen( page ) {
	const sidebar = page.locator( '.interface-interface-skeleton__sidebar' );
	if ( ! await sidebar.isVisible().catch( () => false ) ) {
		const settingsBtn = page.locator( 'button[aria-label="Settings"]' ).first();
		if ( await settingsBtn.isVisible().catch( () => false ) ) {
			await settingsBtn.click();
		}
	}
}

async function selectOrInsertBlock( page ) {
	// Insert a spectra/container block — core/paragraph is not an allowed GS block.
	await page.evaluate( () => {
		const { dispatch } = window.wp.data;
		const { createBlock } = window.wp.blocks;
		dispatch( 'core/block-editor' ).insertBlock( createBlock( 'spectra/container', {} ) );
	} );
	await page.waitForTimeout( 500 );
}

/**
 * Opens the flyout via the inspector "Manage Classes" button.
 * Requires Pro to be active (button only injected when isPro === true).
 * @param {import('@playwright/test').Page} page
 */
async function openFlyoutViaInspector( page ) {
	await waitForGBSGlobal( page );
	await selectOrInsertBlock( page );
	await ensureSidebarOpen( page );

	const btn = page.locator( '.spectra-manage-classes-inspector__btn' ).first();
	await expect( btn ).toBeVisible( { timeout: 8000 } );
	await btn.click();
	await page.waitForSelector( '.spectra-class-flyout', { timeout: 5000 } );
}

// ─── Cleanup helpers ─────────────────────────────────────────────────────────

/**
 * Delete all gs-pw-* test classes created by this suite.
 * Uses the REST API with WP cookie auth already established by wpLogin().
 * @param {import('@playwright/test').Page} page
 */
async function deleteTestClasses( page ) {
	try {
		// Single evaluate call: fetch nonce + list + delete all — minimises CDP round-trips.
		await page.evaluate( async ( wpUrl ) => {
			const ajaxUrl = `${ wpUrl }/wp-admin/admin-ajax.php`;
			const nonce = await fetch( ajaxUrl + '?action=rest-nonce' ).then( ( r ) => r.text() );
			if ( ! nonce ) {return;}

			const classPath = `${ wpUrl }/?rest_route=/spectra-blocks/v1/global-styles/custom-classes`;
			const data = await fetch( classPath, {
				headers: { 'X-WP-Nonce': nonce },
			} ).then( ( r ) => r.json() );

			const toDelete = Object.keys( data?.classes ?? {} ).filter( ( n ) => n.startsWith( 'gs-pw-' ) );
			await Promise.all(
				toDelete.map( ( name ) =>
					fetch( classPath, {
						method: 'POST',
						headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' },
						body: JSON.stringify( { class_name: name, is_destructive: true } ),
					} )
				)
			);
		}, WP_URL );
	} catch ( e ) {
		// Cleanup failure should not block tests.
	}
}

// Delete stale test classes before the suite runs, and clean up after.
test.beforeAll( async ( { browser } ) => {
	test.setTimeout( 60000 );
	const ctx  = await browser.newContext();
	const page = await ctx.newPage();
	try {
		await wpLogin( page );
		await deleteTestClasses( page );
	} catch ( e ) { /* silent */ }
	await ctx.close();
} );

test.afterAll( async ( { browser } ) => {
	test.setTimeout( 60000 );
	const ctx  = await browser.newContext();
	const page = await ctx.newPage();
	try {
		await wpLogin( page );
		await deleteTestClasses( page );
	} catch ( e ) { /* silent */ }
	await ctx.close();
} );

// ─── Tests ────────────────────────────────────────────────────────────────────

test.describe( 'GBS Class Flyout', () => {
	test.beforeEach( async ( { page } ) => {
		await wpLogin( page );
		await openNewPost( page );
	} );

	test( 'window.__spectraGBSEditor exposes open()', async ( { page } ) => {
		await waitForGBSGlobal( page );
		const hasOpen = await page.evaluate(
			() => typeof window.__spectraGBSEditor?.open === 'function'
		);
		expect( hasOpen ).toBe( true );
	} );

	test( '"Manage Classes" inspector panel is present when a block is selected', async ( { page } ) => {
		await waitForGBSGlobal( page );
		await selectOrInsertBlock( page );
		await ensureSidebarOpen( page );

		const panel = page.locator( '.spectra-manage-classes-inspector' ).first();
		await expect( panel ).toBeVisible( { timeout: 8000 } );
	} );

	test( '"Manage Classes" button in inspector opens the flyout', async ( { page } ) => {
		await openFlyoutViaInspector( page );
		await expect( page.locator( '.spectra-class-flyout' ) ).toBeVisible( { timeout: 5000 } );
	} );

	test( 'flyout close button dismisses flyout', async ( { page } ) => {
		await openFlyoutViaInspector( page );
		await page.locator( '.spectra-class-flyout__close' ).click();
		await expect( page.locator( '.spectra-class-flyout' ) ).not.toBeVisible( { timeout: 3000 } );
	} );

	test( 'Escape key closes the flyout', async ( { page } ) => {
		await openFlyoutViaInspector( page );
		await page.keyboard.press( 'Escape' );
		await expect( page.locator( '.spectra-class-flyout' ) ).not.toBeVisible( { timeout: 3000 } );
	} );

	test( 'can add a new class via the flyout add row', async ( { page } ) => {
		await openFlyoutViaInspector( page );

		const input = page.locator( '.spectra-class-flyout__input' );
		await expect( input ).toBeFocused( { timeout: 3000 } );

		const testClass = `pw-${ Date.now() }`;
		await input.fill( testClass );
		await page.locator( '.spectra-class-flyout__add-btn' ).click();

		await expect(
			page.locator( `.spectra-class-flyout__item-name:has-text(".gs-${ testClass }")` )
		).toBeVisible( { timeout: 15000 } );
	} );

	test( 'clicking a class row expands the inline CSS textarea', async ( { page } ) => {
		await openFlyoutViaInspector( page );

		const uniqueName = `pw-expand-${ Date.now() }`;
		const input = page.locator( '.spectra-class-flyout__input' );
		await input.fill( uniqueName );
		await page.locator( '.spectra-class-flyout__add-btn' ).click();

		const header = page.locator( '.spectra-class-flyout__item-header' ).filter( {
			has: page.locator( `code:has-text(".gs-${ uniqueName }")` ),
		} ).first();
		await expect( header ).toBeVisible( { timeout: 8000 } );

		// New class auto-expands; collapse it first so we can test the expand click.
		await header.click();
		await page.waitForTimeout( 200 );
		await header.click();
		await expect( page.locator( '.spectra-class-flyout__item-textarea' ).first() ).toBeVisible( { timeout: 3000 } );
	} );

	test( 'inline CSS editor: Save button enables after typing, then collapses on save', async ( { page } ) => {
		await openFlyoutViaInspector( page );

		const uniqueName = `pw-save-${ Date.now() }`;
		const input = page.locator( '.spectra-class-flyout__input' );
		await input.fill( uniqueName );
		await page.locator( '.spectra-class-flyout__add-btn' ).click();

		const header = page.locator( '.spectra-class-flyout__item-header' ).filter( {
			has: page.locator( `code:has-text(".gs-${ uniqueName }")` ),
		} ).first();
		await expect( header ).toBeVisible( { timeout: 8000 } );
		// New class auto-expands — no need to click header; textarea is already visible.

		const textarea = page.locator( '.spectra-class-flyout__item-textarea' ).first();
		const saveBtn  = page.locator( '.spectra-class-flyout__item-save' ).first();

		await expect( textarea ).toBeVisible();
		// Template pre-filled → Save is enabled immediately (draft !== stored empty string).
		// Use a generous timeout: creation does 2 sequential REST calls (POST + GET),
		// then a second GET from the spectraGSClassesUpdated event.
		await expect( saveBtn ).toBeEnabled( { timeout: 10000 } );

		await textarea.fill( `.gs-${ uniqueName } { font-size: 18px; }` );
		await expect( saveBtn ).toBeEnabled();

		await saveBtn.click();

		await expect( textarea ).not.toBeVisible( { timeout: 5000 } );
	} );
} );

// ─── CSS Autocomplete: shared helpers ────────────────────────────────────────

/**
 * Open the GBS modal and switch to the Classes & CSS tab.
 * @param {import('@playwright/test').Page} page
 */
async function openModalClassesTab( page ) {
	await page.waitForFunction(
		() => typeof window.__spectraGBSEditor?.open === 'function',
		{ timeout: 15000 }
	);
	await page.evaluate( () => window.__spectraGBSEditor.open() );
	await page.waitForSelector( '.spectra-gbs-modal', { timeout: 5000 } );
	await page.locator( '.spectra-gbs-nav__btn' ).filter( { hasText: 'Classes' } ).click();
	await page.waitForTimeout( 300 );
}

/**
 * Create a class in the modal class panel and return the CSSAutocomplete textarea.
 * @param {import('@playwright/test').Page} page
 * @param {string}                          uniqueName
 */
async function createModalClassAndGetTextarea( page, uniqueName ) {
	const newInput = page.locator( '.spectra-classes-panel__new-input' );
	await newInput.fill( uniqueName );
	await page.locator( '.spectra-classes-panel__new-btn' ).click();

	// Wait for the editor header to show the NEW class name — this confirms
	// that setSelected(finalName) has fired and the ClassEditor has remounted.
	// Without this, when existing classes are present the ClassEditor for the
	// first existing class is already visible, and the textarea locator resolves
	// to the wrong element before handleAdd's async setSelected completes.
	const expectedName = `gs-${ uniqueName }`;
	await expect(
		page.locator( '.spectra-classes-editor__name' ).filter( { hasText: `.${ expectedName }` } )
	).toBeVisible( { timeout: 8000 } );

	const textarea = page.locator( '.spectra-classes-editor__textarea' ).first();
	await expect( textarea ).toBeVisible( { timeout: 3000 } );
	// fill('') establishes Playwright's internal focus state so that subsequent
	// pressSequentially calls reliably fire React's synthetic onChange. React
	// commits the setDraft('') update before this await resolves.
	await textarea.fill( '' );
	return textarea;
}

/**
 * Type CSS text into a CSSAutocomplete textarea and trigger ghost text.
 *
 * React 18 controlled textareas in this Gutenberg modal context require
 * real keyboard events (pressSequentially) to fire synthetic onChange reliably.
 * A preceding fill('') in createModalClassAndGetTextarea resets focus state so
 * the first character is picked up. waitForTimeout(300) gives React time to
 * commit the ghost-text mirror re-render.
 * @param {import('@playwright/test').Page}    page
 * @param {import('@playwright/test').Locator} textarea
 * @param {string}                             text
 */
async function typeCSS( page, textarea, text ) {
	await textarea.click();
	await page.waitForTimeout( 100 );
	await textarea.pressSequentially( text, { delay: 80 } );
	await page.waitForTimeout( 300 );
}

/**
 * Open flyout, create a class (auto-expands), return the CSSAutocomplete textarea.
 * @param {import('@playwright/test').Page} page
 */
async function openFlyoutWithExpandedClass( page ) {
	await openFlyoutViaInspector( page );

	const uniqueName = `pw-ac-f-${ Date.now() }`;
	await page.locator( '.spectra-class-flyout__input' ).fill( uniqueName );
	await page.locator( '.spectra-class-flyout__add-btn' ).click();

	const textarea = page.locator( '.spectra-class-flyout__item-textarea' ).first();
	await expect( textarea ).toBeVisible( { timeout: 8000 } );

	// After saveClass resolves it dispatches spectraGSClassesUpdated, which
	// triggers a second fetchClasses() → loading=true → ClassItem unmounts →
	// loading=false → ClassItem remounts with draft reset to the template.
	// We must wait for this full loading cycle to settle before calling fill('')
	// to clear the template; otherwise the remount undoes our clear.
	const loadingSpinner = page.locator( '.spectra-class-flyout__loading' );
	await loadingSpinner.waitFor( { state: 'visible', timeout: 2000 } ).catch( () => {} );
	await loadingSpinner.waitFor( { state: 'hidden', timeout: 5000 } ).catch( () => {} );

	await expect( textarea ).toBeVisible( { timeout: 3000 } );
	await textarea.fill( '' );
	return textarea;
}

// ─── CSS Autocomplete in modal (free, always available) ───────────────────────

test.describe( 'GBS Editor — CSS Autocomplete in modal', () => {
	test.beforeEach( async ( { page } ) => {
		await wpLogin( page );
		await openNewPost( page );
		await openModalClassesTab( page );
	} );

	test( 'typing a CSS prefix shows ghost text', async ( { page } ) => {
		const textarea = await createModalClassAndGetTextarea( page, `pw-ac-m-${ Date.now() }` );
		await typeCSS( page, textarea, 'col' );

		const ghost = page.locator( '.spectra-css-ac__ghost-text' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
		const ghostText = await ghost.textContent();
		expect( ghostText.length ).toBeGreaterThan( 0 );
	} );

	test( 'ghost text completes the typed prefix into a valid CSS property', async ( { page } ) => {
		const textarea = await createModalClassAndGetTextarea( page, `pw-ac-m-${ Date.now() }` );
		await typeCSS( page, textarea, 'col' );

		const ghost = page.locator( '.spectra-css-ac__ghost-text' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
		const ghostText = await ghost.textContent();
		// 'col' + ghost should produce a recognised CSS property (e.g. 'color: ').
		expect( ( 'col' + ghostText ).toLowerCase() ).toMatch( /^col[a-z-]+/ );
	} );

	test( 'Tab accepts ghost text and inserts the full suggestion', async ( { page } ) => {
		const textarea = await createModalClassAndGetTextarea( page, `pw-ac-m-${ Date.now() }` );
		await typeCSS( page, textarea, 'col' );

		const ghost = page.locator( '.spectra-css-ac__ghost-text' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
		const ghostText = await ghost.textContent();

		await textarea.press( 'Tab' );

		await expect( ghost ).not.toBeVisible( { timeout: 2000 } );
		const value = await textarea.inputValue();
		// Value should contain the prefix plus the accepted ghost suffix.
		expect( value ).toContain( 'col' );
		expect( value.length ).toBeGreaterThan( 'col'.length );
		expect( value ).toContain( ghostText );
	} );

	test( 'Enter accepts ghost text and inserts the full suggestion', async ( { page } ) => {
		const textarea = await createModalClassAndGetTextarea( page, `pw-ac-m-${ Date.now() }` );
		await typeCSS( page, textarea, 'col' );

		const ghost = page.locator( '.spectra-css-ac__ghost-text' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
		const ghostText = await ghost.textContent();

		await textarea.press( 'Enter' );

		await expect( ghost ).not.toBeVisible( { timeout: 2000 } );
		const value = await textarea.inputValue();
		expect( value ).toContain( ghostText );
	} );

	test( 'Escape dismisses ghost text without accepting', async ( { page } ) => {
		const textarea = await createModalClassAndGetTextarea( page, `pw-ac-m-${ Date.now() }` );
		await typeCSS( page, textarea, 'col' );

		const ghost = page.locator( '.spectra-css-ac__ghost-text' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
		await textarea.press( 'Escape' );
		await expect( ghost ).not.toBeVisible( { timeout: 2000 } );

		// Raw prefix must still be in the textarea (nothing was accepted).
		const value = await textarea.inputValue();
		expect( value ).toBe( 'col' );
	} );

	test( 'value suggestions appear after typing a property and colon', async ( { page } ) => {
		const textarea = await createModalClassAndGetTextarea( page, `pw-ac-m-${ Date.now() }` );
		await typeCSS( page, textarea, 'display: f' );

		const ghost = page.locator( '.spectra-css-ac__ghost-text' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
		const ghostText = await ghost.textContent();
		// Ghost should complete a value starting with 'f' (e.g. 'lex' for 'flex').
		expect( ( 'f' + ghostText ).toLowerCase() ).toMatch( /^f[a-z-]+/ );
	} );

	test( '@-rule ghost text appears when typing @me', async ( { page } ) => {
		const textarea = await createModalClassAndGetTextarea( page, `pw-ac-m-${ Date.now() }` );
		await typeCSS( page, textarea, '@me' );

		const ghost = page.locator( '.spectra-css-ac__ghost-text' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
		const ghostText = await ghost.textContent();
		// '@me' + ghost should start with '@media'.
		expect( ( '@me' + ghostText ).toLowerCase() ).toMatch( /^@media/ );
	} );

	test( 'ghost text disappears after accepting and re-typing shows fresh ghost', async ( { page } ) => {
		const textarea = await createModalClassAndGetTextarea( page, `pw-ac-m-${ Date.now() }` );
		await typeCSS( page, textarea, 'col' );

		const ghost = page.locator( '.spectra-css-ac__ghost-text' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
		await textarea.press( 'Tab' );
		await expect( ghost ).not.toBeVisible( { timeout: 2000 } );

		// Re-type with a different prefix — value suggestions for position.
		await typeCSS( page, textarea, 'position: re' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
	} );
} );

// ─── CSS Autocomplete portal in flyout (spectra-blocks-pro required) ──────────

test.describe( 'GBS Class Flyout — CSS Autocomplete (Pro)', () => {
	let isPro = false;

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		try {
			await wpLogin( page );
			await page.goto( `${ WP_URL }/wp-admin/post-new.php` );
			await page.waitForFunction(
				() => typeof window.spectra_blocks_info !== 'undefined',
				{ timeout: 15000 }
			);
			isPro = await page.evaluate(
				() => window.spectra_blocks_info?.spectra_pro_status === 'active'
			);
		} finally {
			await page.close();
		}
	} );

	test.beforeEach( async ( { page } ) => {
		test.skip( ! isPro, 'spectra-blocks-pro is not active — activate it to run flyout tests' ); // eslint-disable-line
		await wpLogin( page );
		await openNewPost( page );
	} );

	test( 'flyout textarea: typing shows ghost text', async ( { page } ) => {
		const textarea = await openFlyoutWithExpandedClass( page );
		await typeCSS( page, textarea, 'col' );

		const ghost = page.locator( '.spectra-css-ac__ghost-text' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
		const ghostText = await ghost.textContent();
		expect( ghostText.length ).toBeGreaterThan( 0 );
	} );

	test( 'flyout: Tab accepts ghost suggestion inside the overflow-clipped popover', async ( { page } ) => {
		const textarea = await openFlyoutWithExpandedClass( page );
		await typeCSS( page, textarea, 'col' );

		const ghost = page.locator( '.spectra-css-ac__ghost-text' );
		await expect( ghost ).toBeVisible( { timeout: 5000 } );
		const ghostText = await ghost.textContent();

		await textarea.press( 'Tab' );

		await expect( ghost ).not.toBeVisible( { timeout: 2000 } );
		const value = await textarea.inputValue();
		expect( value ).toContain( ghostText );
	} );
} );
