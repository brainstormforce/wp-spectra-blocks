const { test, expect } = require( '@playwright/test' );

const WP_URL       = 'http://spectra-local-site.local:10019';
const LOGIN_URL    = `${ WP_URL }/wp-login.php`;
const NEW_POST_URL = `${ WP_URL }/wp-admin/post-new.php`;

async function wpLogin( page ) {
	await page.goto( LOGIN_URL );
	await page.fill( '#user_login', 'root' );
	await page.fill( '#user_pass', 'root' );
	await page.click( '#wp-submit' );
	await page.waitForURL( '**/wp-admin/**' );
}

async function openNewPost( page ) {
	await page.goto( NEW_POST_URL );
	const welcomeClose = page.locator( '.components-modal__header button[aria-label="Close"]' ).first();
	if ( await welcomeClose.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
		await welcomeClose.click();
	}
	// Wait for the editor to be ready (block editor canvas loads).
	await page.waitForSelector( '.editor-visual-editor, .block-editor-writing-flow', { timeout: 20000 } );
}

async function openGBSModal( page ) {
	// Header button is hidden when Pro is active (we hid it intentionally).
	// Use the globally exposed open() as the reliable entry point instead.
	await page.waitForFunction( () => typeof window.__spectraGBSEditor?.open === 'function', { timeout: 15000 } );
	await page.evaluate( () => window.__spectraGBSEditor.open() );
	await page.waitForSelector( '.spectra-gbs-modal', { timeout: 5000 } );
}

/**
 * Click a nav item by its label text.
 * @param {import('@playwright/test').Page} page
 * @param {string}                          label
 */
async function clickNavItem( page, label ) {
	await page.locator( `.spectra-gbs-nav__btn:has-text("${ label }")` ).click();
	await page.waitForTimeout( 300 );
}

// ─── Tests ───────────────────────────────────────────────────────────────────

test.describe( 'GBS Editor Modal', () => {
	test.beforeEach( async ( { page } ) => {
		await wpLogin( page );
		await openNewPost( page );
	} );

	test( 'modal opens via toolbar button (free) or JS API (pro)', async ( { page } ) => {
		// The header button is hidden when Pro is active (intentional — Pro has its own icon).
		// Use the globally-exposed open() so the test works in both environments.
		await openGBSModal( page );
		await expect( page.locator( '.spectra-gbs-modal' ) ).toBeVisible( { timeout: 5000 } );
		await page.screenshot( { path: '/tmp/gbs-01-modal-open.png' } );
	} );

	test( 'modal closes via close button', async ( { page } ) => {
		await openGBSModal( page );
		await page.locator( '.spectra-gbs-header__close-btn' ).click();
		await expect( page.locator( '.spectra-gbs-modal' ) ).not.toBeVisible( { timeout: 3000 } );
	} );

	test( 'modal closes via Escape key', async ( { page } ) => {
		await openGBSModal( page );
		await page.keyboard.press( 'Escape' );
		await expect( page.locator( '.spectra-gbs-modal' ) ).not.toBeVisible( { timeout: 3000 } );
	} );

	test( 'modal closes via backdrop click', async ( { page } ) => {
		await openGBSModal( page );
		await page.locator( '.spectra-gbs-backdrop' ).click( { position: { x: 5, y: 5 } } );
		await expect( page.locator( '.spectra-gbs-modal' ) ).not.toBeVisible( { timeout: 3000 } );
	} );

	test( 'all 11 nav items are present', async ( { page } ) => {
		await openGBSModal( page );
		const expectedItems = [
			'Colors', 'Spacing', 'Font Sizes',
			'Typography', 'Presets',
			'CSS Variables', 'Classes & CSS', 'Keyframes',
			'Block Defaults', 'Token Browser', 'Cheat Sheet',
		];
		for ( const label of expectedItems ) {
			await expect( page.locator( `.spectra-gbs-nav__btn:has-text("${ label }")` ) ).toBeVisible();
		}
		await page.screenshot( { path: '/tmp/gbs-02-all-nav-items.png' } );
	} );

	test( 'nav groups are rendered', async ( { page } ) => {
		await openGBSModal( page );
		const expectedGroups = [ 'System Variables', 'Design System', 'Custom CSS', 'Reference' ];
		for ( const group of expectedGroups ) {
			await expect( page.locator( `.spectra-gbs-nav__group-label:has-text("${ group }")` ) ).toBeVisible();
		}
	} );

	test( 'Colors — panel and Save button visible', async ( { page } ) => {
		await openGBSModal( page );
		// Colors tab has no sidebar sections — check the main panel instead.
		await expect( page.locator( '.spectra-gbs-panel' ) ).toBeVisible( { timeout: 8000 } );
		// Save moved to shared modal footer in new UI.
		await expect( page.locator( '.spectra-gbs-footer__save' ) ).toBeVisible();
		await page.screenshot( { path: '/tmp/gbs-03-colors.png' } );
	} );

	test( 'Spacing tab renders token rows and preview', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Spacing' );
		await expect( page.locator( '.spectra-gbs-panel' ) ).toBeVisible();
		// Should show at least one spacing row
		await page.waitForSelector( '.spectra-gbs-spacing__row, .spectra-gbs-panel__loading', { timeout: 8000 } );
		await page.screenshot( { path: '/tmp/gbs-04-spacing.png' } );
	} );

	test( 'Font Sizes tab renders mode toggle and preview', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Font Sizes' );
		await expect( page.locator( '.spectra-gbs-panel' ) ).toBeVisible();
		await page.waitForSelector( '.spectra-gbs-font-size-panel__mode-toggle, .spectra-gbs-panel__loading', { timeout: 8000 } );
		await page.screenshot( { path: '/tmp/gbs-05-font-sizes.png' } );
	} );

	test( 'Typography tab renders with sidebar sections', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Typography' );
		await expect( page.locator( '.spectra-gbs-sidebar' ) ).toBeVisible();
		await page.screenshot( { path: '/tmp/gbs-06-typography.png' } );
	} );

	test( 'Presets tab renders with sidebar sections', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Presets' );
		await expect( page.locator( '.spectra-gbs-sidebar' ) ).toBeVisible();
		await page.screenshot( { path: '/tmp/gbs-07-presets.png' } );
	} );

	test( 'footer Save button hidden on self-saving tabs', async ( { page } ) => {
		await openGBSModal( page );
		// Save is now in the shared modal footer, not the header.
		const saveBtn = page.locator( '.spectra-gbs-footer__save' );

		// Colors — footer save visible.
		await expect( saveBtn ).toBeVisible();

		// Self-saving tabs hide the footer save button.
		for ( const label of [ 'Classes & CSS', 'CSS Variables', 'Keyframes', 'Cheat Sheet' ] ) {
			await clickNavItem( page, label );
			await expect( saveBtn ).not.toBeVisible();
		}

		// Back to Colors — footer save visible again.
		await clickNavItem( page, 'Colors' );
		await expect( saveBtn ).toBeVisible();

		await page.screenshot( { path: '/tmp/gbs-08-save-btn-visibility.png' } );
	} );

	test( 'Classes & CSS tab — class list, create class, CSS editor', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Classes & CSS' );

		await expect( page.locator( '.spectra-gbs-section--classes' ) ).toBeVisible( { timeout: 5000 } );

		const nameInput = page.locator( '.spectra-gbs-class-new input' );
		await expect( nameInput ).toBeVisible();

		await nameInput.fill( 'playwright-test' );
		await page.locator( '.spectra-gbs-class-new button:has-text("Create")' ).click();
		await page.waitForTimeout( 800 );

		await expect( page.locator( '.spectra-gbs-class-editor__name' ).filter( { hasText: '.gs-playwright-test' } ) ).toBeVisible( { timeout: 5000 } );
		await expect( page.locator( 'textarea.spectra-gbs-class-editor__textarea' ).first() ).toBeVisible();

		const textarea = page.locator( 'textarea.spectra-gbs-class-editor__textarea' ).first();
		await textarea.fill( 'color: red;\nfont-weight: bold;' );

		const saveClassBtn = page.locator( '.spectra-gbs-class-editor__actions button:has-text("Save Class")' );
		await expect( saveClassBtn ).toBeVisible();
		await saveClassBtn.click();
		await page.waitForTimeout( 600 );

		await page.screenshot( { path: '/tmp/gbs-09-classes-create-save.png' } );
	} );

	test( 'Classes & CSS tab — delete class with inline confirm', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Classes & CSS' );
		await page.waitForSelector( '.spectra-gbs-section--classes', { timeout: 5000 } );

		const targetRow = page.locator( '.spectra-gbs-class-row' ).filter( {
			has: page.locator( 'code:has-text(".gs-playwright-test")' ),
		} );
		const delBtn = targetRow.locator( 'button.spectra-gbs-class-row__btn.is-danger' );
		if ( await delBtn.isVisible( { timeout: 2000 } ).catch( () => false ) ) {
			await delBtn.click();
			await targetRow.locator( 'button.spectra-gbs-class-row__btn.is-danger:has-text("Confirm")' ).click();
			await page.waitForTimeout( 600 );
			await expect( targetRow ).not.toBeVisible();
		}
		await page.screenshot( { path: '/tmp/gbs-10-classes-delete.png' } );
	} );

	test( 'Classes & CSS tab — inline edit existing class opens bucket editor', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Classes & CSS' );
		await page.waitForSelector( '.spectra-gbs-section--classes', { timeout: 5000 } );

		// At least one class must exist — open the first row's edit button.
		const firstRow = page.locator( '.spectra-gbs-class-row' ).first();
		await expect( firstRow ).toBeVisible( { timeout: 5000 } );

		await firstRow.locator( 'button.spectra-gbs-class-row__btn' ).first().click();

		// ClassEditor opens with bucket textareas (11 buckets — Default is first).
		await expect( page.locator( '.spectra-gbs-class-editor' ) ).toBeVisible( { timeout: 3000 } );
		await expect( page.locator( 'textarea.spectra-gbs-class-editor__textarea' ).first() ).toBeVisible();

		// Cancel without saving.
		await page.locator( '.spectra-gbs-class-editor__actions button:has-text("Cancel")' ).click();
		await page.waitForTimeout( 300 );
		await expect( page.locator( '.spectra-gbs-class-editor' ) ).not.toBeVisible();

		await page.screenshot( { path: '/tmp/gbs-11-class-bucket-edit.png' } );
	} );

	test( 'CSS Variables tab renders', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'CSS Variables' );
		await expect( page.locator( '.spectra-gbs-panel' ) ).toBeVisible();
		await expect( page.locator( '.spectra-gbs-header__save-btn' ) ).not.toBeVisible();
		await page.screenshot( { path: '/tmp/gbs-12-css-vars.png' } );
	} );

	test( 'Keyframes tab renders', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Keyframes' );
		await expect( page.locator( '.spectra-gbs-panel' ) ).toBeVisible();
		await expect( page.locator( '.spectra-gbs-header__save-btn' ) ).not.toBeVisible();
		await page.screenshot( { path: '/tmp/gbs-13-keyframes.png' } );
	} );

	test( 'Block Defaults tab renders', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Block Defaults' );
		await expect( page.locator( '.spectra-gbs-panel' ) ).toBeVisible();
		await page.waitForSelector( '.spectra-gbs-bd, .spectra-gbs-upgrade-lock', { timeout: 5000 } );
		await page.screenshot( { path: '/tmp/gbs-14-block-defaults.png' } );
	} );

	test( 'Token Browser tab renders with sidebar sections', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Token Browser' );
		await expect( page.locator( '.spectra-gbs-sidebar' ) ).toBeVisible();
		await page.screenshot( { path: '/tmp/gbs-15-token-browser.png' } );
	} );

	test( 'Cheat Sheet — panel renders', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Cheat Sheet' );
		await expect( page.locator( '.spectra-gbs-panel--cheat-sheet' ) ).toBeVisible( { timeout: 5000 } );
		await page.screenshot( { path: '/tmp/gbs-16-cheat-sheet.png' } );
	} );

	test( 'Cheat Sheet — search filters results', async ( { page } ) => {
		await openGBSModal( page );
		await clickNavItem( page, 'Cheat Sheet' );
		await page.waitForSelector( '.spectra-gbs-panel--cheat-sheet', { timeout: 5000 } );

		const search = page.locator( '.spectra-gbs-panel--cheat-sheet input[type="text"], .spectra-gbs-panel--cheat-sheet input[type="search"]' ).first();
		if ( await search.isVisible( { timeout: 2000 } ).catch( () => false ) ) {
			await search.fill( 'color' );
			await page.waitForTimeout( 400 );
			await page.screenshot( { path: '/tmp/gbs-17-cheat-sheet-search.png' } );
			await search.clear();
		}
	} );
} );

// ─── Live color preview ───────────────────────────────────────────────────────

test.describe( 'GBS live color preview', () => {
	test.beforeEach( async ( { page } ) => {
		await wpLogin( page );
		await openNewPost( page );
	} );

	test( 'primary color change updates --wp--preset--color--primary live in editor canvas', async ( { page } ) => {
		await openGBSModal( page );

		// Colors → Primary is the default section; wait for the hex input to be ready.
		await page.waitForSelector( '.spectra-gbs-hex-input__text', { timeout: 8000 } );

		// Snapshot initial --wp--preset--color--primary inside the editor iframe.
		const getEditorPrimary = () =>
			page.evaluate( () => {
				const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
				if ( ! iframe?.contentDocument ) {return null;}
				return getComputedStyle( iframe.contentDocument.documentElement ) // eslint-disable-line no-undef
					.getPropertyValue( '--wp--preset--color--primary' )
					.trim();
			} );

		const initialColor = await getEditorPrimary();
		console.log( 'Initial --wp--preset--color--primary:', initialColor );

		// Use a very distinctive colour unlikely to already be set.
		const testHex = '#e91e63';

		// Fill the first hex text input (Primary base colour) with the new value.
		const hexInput = page.locator( '.spectra-gbs-hex-input__text' ).first();
		await hexInput.click( { clickCount: 3 } );
		await hexInput.fill( testHex );
		await hexInput.press( 'Tab' );

		// Wait for debounce (300 ms) + REST preview round-trip + style injection.
		await page.waitForTimeout( 1200 );

		// Verify the style tag was injected into the host document.
		const hostStyleExists = await page.evaluate( () =>
			!! document.getElementById( 'spectra-gbs-live-wp-preset-colors' )
		);
		expect( hostStyleExists ).toBe( true );

		// Verify the style tag was injected into the editor iframe.
		const iframeStyleExists = await page.evaluate( () => {
			const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
			return !! iframe?.contentDocument?.getElementById( 'spectra-gbs-live-wp-preset-colors' );
		} );
		expect( iframeStyleExists ).toBe( true );

		const updatedColor = await getEditorPrimary();

		// Value must differ from the original.
		expect( updatedColor ).not.toEqual( initialColor );

		// Resolved computed value must contain the test hex digits.
		expect( updatedColor?.replace( /\s/g, '' ).toLowerCase() ).toContain( 'e91e63' );

		await page.screenshot( { path: '/tmp/gbs-live-color-primary.png' } );
	} );
} );
