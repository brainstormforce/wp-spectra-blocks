/**
 * E2E: Block defaults toggle — disabling hides default-{block} chip in real-time.
 *
 * Verifies that:
 * 1. default-container chip is visible in the GS inspector when block defaults are ON.
 * 2. Toggling OFF via GBS modal removes the chip immediately (no editor save needed).
 * 3. Re-enabling the toggle brings the chip back.
 */
const { test, expect } = require( '@playwright/test' );

const WP_URL = 'http://spectra-local-site.local:10019';

async function wpLogin( page ) {
	await page.goto( `${ WP_URL }/wp-login.php` );
	await page.fill( '#user_login', 'root' );
	await page.fill( '#user_pass', 'root' );
	await page.click( '#wp-submit' );
	await page.waitForURL( /wp-admin/, { timeout: 30000 } ).catch( () => {} );
	if ( ! page.url().includes( ':10019' ) ) {
		await page.goto( `${ WP_URL }/wp-admin/` );
	}
}

async function openNewPost( page ) {
	await page.goto( `${ WP_URL }/wp-admin/post-new.php` );
	const welcomeClose = page.locator( '.components-modal__header button[aria-label="Close"]' ).first();
	if ( await welcomeClose.isVisible( { timeout: 3000 } ).catch( () => false ) ) {
		await welcomeClose.click();
	}
	await page.waitForSelector( '.editor-visual-editor, .block-editor-writing-flow', { timeout: 20000 } );
}

async function insertAndSelectContainer( page ) {
	await page.evaluate( () => {
		const { dispatch } = window.wp.data;
		const { createBlock } = window.wp.blocks;
		dispatch( 'core/block-editor' ).insertBlock( createBlock( 'spectra/container', {} ) );
	} );
	await page.waitForTimeout( 500 );
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

async function openGBSModal( page ) {
	await page.waitForFunction( () => typeof window.__spectraGBSEditor?.open === 'function', { timeout: 15000 } );
	await page.evaluate( () => window.__spectraGBSEditor.open() );
	await page.waitForSelector( '.spectra-gbs-modal', { timeout: 5000 } );
}

async function goToBlockDefaultsTab( page ) {
	await page.locator( '.spectra-gbs-nav__btn:has-text("Block defaults")' ).click();
	await page.waitForTimeout( 300 );
}

async function closeGBSModal( page ) {
	await page.locator( '.spectra-gbs-header__close-btn' ).click();
	await page.waitForSelector( '.spectra-gbs-modal', { state: 'hidden', timeout: 5000 } );
}

async function isDefaultContainerChipVisible( page ) {
	const chip = page.locator( '.spectra-gs-chip-edit:has-text("default-container")' );
	return chip.isVisible( { timeout: 3000 } ).catch( () => false );
}

async function getStoreBlockDefaultsEnabled( page ) {
	return page.evaluate( () => {
		return window.wp?.data?.select( 'spectra-pro/global-styles' )?.getBlockDefaultsEnabled?.() ?? null;
	} );
}

/**
 * Clicks the toggle and waits until the WP data store reflects the expected value.
 * Polling the store avoids racing against the is-busy CSS class (AJAX can complete
 * before the class is even painted).
 *
 * @param {import('@playwright/test').Page}    page
 * @param {import('@playwright/test').Locator} toggle   The toggle button locator.
 * @param {boolean}                            expected The expected post-click store value.
 */
async function clickToggleAndWait( page, toggle, expected ) {
	await toggle.click();
	await page.waitForFunction(
		( exp ) =>
			window.wp?.data?.select( 'spectra-pro/global-styles' )?.getBlockDefaultsEnabled?.() === exp,
		expected,
		{ timeout: 10000 }
	);
}

// ─── Tests ───────────────────────────────────────────────────────────────────

test.describe( 'GBS Block Defaults Toggle', () => {
	test.beforeEach( async ( { page } ) => {
		await wpLogin( page );
		await openNewPost( page );
	} );

	test( 'disabling block defaults hides default-container chip; re-enabling restores it', async ( { page } ) => {
		// Skip if Pro is not active (store won't exist)
		const isPro = await page.evaluate( () => window?.spectra_blocks_info?.spectra_pro_status === 'active' );
		if ( ! isPro ) {
			test.skip( 'spectra-blocks-pro not active — skipping block defaults test' );
			return;
		}

		// ── Step 1: ensure block defaults are ON before starting ─────────────────
		// If they were left OFF from a prior failed run, turn them back on via AJAX
		// and also sync the WP data store (which was already initialized at page load).
		const ajaxNonce = await page.evaluate( () => window.spectra_editor_gs?.ajax_nonce );
		await page.evaluate( async ( nonce ) => {
			const fd = new FormData();
			fd.append( 'action', 'spectra_pro_gs_block_defaults_enabled' );
			fd.append( 'security', nonce );
			fd.append( 'enabled', '1' );
			await fetch( '/wp-admin/admin-ajax.php', { method: 'POST', body: fd } );
			// Sync window global and the already-initialized WP data store.
			if ( window.spectra_editor_gs ) { window.spectra_editor_gs.block_defaults_enabled = true; }
			window.wp?.data?.dispatch( 'spectra-pro/global-styles' )?.setBlockDefaultsEnabled?.( true );
		}, ajaxNonce );

		// ── Step 2: insert container and open inspector ───────────────────────────
		await insertAndSelectContainer( page );
		await ensureSidebarOpen( page );
		await expect( page.locator( '.spectra-manage-classes-inspector' ) ).toBeVisible( { timeout: 8000 } );

		// ── Step 3: verify chip is visible with defaults ON ───────────────────────
		await page.screenshot( { path: '/tmp/bd-01-chip-visible.png' } );
		const chipVisibleBefore = await isDefaultContainerChipVisible( page );
		expect( chipVisibleBefore ).toBe( true );

		// ── Step 4: open GBS modal → Block Defaults tab ───────────────────────────
		await openGBSModal( page );
		await goToBlockDefaultsTab( page );
		await page.screenshot( { path: '/tmp/bd-02-modal-block-defaults.png' } );

		// ── Step 5: verify toggle is ON ───────────────────────────────────────────
		const toggle = page.locator( '.spectra-gbs-bd__toggle-row button[role="switch"]' );
		await expect( toggle ).toBeVisible( { timeout: 5000 } );
		const isCheckedBefore = await toggle.getAttribute( 'aria-checked' );
		expect( isCheckedBefore ).toBe( 'true' );

		// ── Step 6: disable block defaults ───────────────────────────────────────
		// clickToggleAndWait polls the store until it reflects the new value,
		// avoiding a race with the is-busy CSS class (AJAX can finish before paint).
		await clickToggleAndWait( page, toggle, false );
		await page.screenshot( { path: '/tmp/bd-03-toggle-off.png' } );

		// Toggle should now report aria-checked=false
		const isCheckedAfterDisable = await toggle.getAttribute( 'aria-checked' );
		expect( isCheckedAfterDisable ).toBe( 'false' );

		// WP data store confirms the change
		const storeValueAfterDisable = await getStoreBlockDefaultsEnabled( page );
		expect( storeValueAfterDisable ).toBe( false );

		// ── Step 7: close modal without editor save ───────────────────────────────
		await closeGBSModal( page );

		// ── Step 8: chip should be gone in the inspector (real-time, no reload) ───
		await page.waitForTimeout( 400 ); // allow React re-render
		await page.screenshot( { path: '/tmp/bd-04-chip-hidden.png' } );
		const chipVisibleAfterDisable = await isDefaultContainerChipVisible( page );
		expect( chipVisibleAfterDisable ).toBe( false );

		// ── Step 9: re-enable and verify chip returns ─────────────────────────────
		await openGBSModal( page );
		await goToBlockDefaultsTab( page );

		const toggleAfterReopen = page.locator( '.spectra-gbs-bd__toggle-row button[role="switch"]' );
		await clickToggleAndWait( page, toggleAfterReopen, true );
		await page.screenshot( { path: '/tmp/bd-05-toggle-on.png' } );

		await closeGBSModal( page );
		await page.waitForTimeout( 400 );
		await page.screenshot( { path: '/tmp/bd-06-chip-restored.png' } );

		const chipVisibleAfterReEnable = await isDefaultContainerChipVisible( page );
		expect( chipVisibleAfterReEnable ).toBe( true );
	} );

	test( 'WP data store reflects toggle state immediately', async ( { page } ) => {
		const isPro = await page.evaluate( () => window?.spectra_blocks_info?.spectra_pro_status === 'active' );
		if ( ! isPro ) {
			test.skip( 'spectra-blocks-pro not active' );
			return;
		}

		// Ensure defaults are ON (AJAX + sync the already-initialized WP data store)
		const ajaxNonce = await page.evaluate( () => window.spectra_editor_gs?.ajax_nonce );
		await page.evaluate( async ( n ) => {
			const fd = new FormData();
			fd.append( 'action', 'spectra_pro_gs_block_defaults_enabled' );
			fd.append( 'security', n );
			fd.append( 'enabled', '1' );
			await fetch( '/wp-admin/admin-ajax.php', { method: 'POST', body: fd } );
			if ( window.spectra_editor_gs ) { window.spectra_editor_gs.block_defaults_enabled = true; }
			window.wp?.data?.dispatch( 'spectra-pro/global-styles' )?.setBlockDefaultsEnabled?.( true );
		}, ajaxNonce );

		const initialValue = await getStoreBlockDefaultsEnabled( page );
		expect( initialValue ).toBe( true );

		// Open modal → Block Defaults → toggle OFF
		await openGBSModal( page );
		await goToBlockDefaultsTab( page );

		const toggle = page.locator( '.spectra-gbs-bd__toggle-row button[role="switch"]' );
		await clickToggleAndWait( page, toggle, false );

		const disabledValue = await getStoreBlockDefaultsEnabled( page );
		expect( disabledValue ).toBe( false );

		// Restore
		await clickToggleAndWait( page, toggle, true );
		const restoredValue = await getStoreBlockDefaultsEnabled( page );
		expect( restoredValue ).toBe( true );

		await closeGBSModal( page );
	} );
} );
