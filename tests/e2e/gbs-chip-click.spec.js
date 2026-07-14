/**
 * E2E: Click-to-edit class chip in inspector opens flyout pre-expanded.
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

test.describe( 'Chip click → flyout pre-expand', () => {
	test.beforeEach( async ( { page } ) => {
		await wpLogin( page );
		await openNewPost( page );
	} );

	test( 'clicking a class chip opens flyout with that class expanded', async ( { page } ) => {
		// Wait for GBS global to be available
		await page.waitForFunction( () => typeof window.__spectraGBSEditor?.open === 'function', { timeout: 15000 } );

		// Check Pro is active
		const isPro = await page.evaluate( () => window?.spectra_blocks_info?.spectra_pro_status === 'active' );
		if ( ! isPro ) {
			test.skip( 'spectra-blocks-pro not active' );
			return;
		}

		// Insert a spectra/container block
		await page.evaluate( () => {
			const { dispatch } = window.wp.data;
			const { createBlock } = window.wp.blocks;
			dispatch( 'core/block-editor' ).insertBlock( createBlock( 'spectra/container', {} ) );
		} );
		await page.waitForTimeout( 500 );

		// Open inspector sidebar if needed
		const sidebar = page.locator( '.interface-interface-skeleton__sidebar' );
		if ( ! await sidebar.isVisible().catch( () => false ) ) {
			const settingsBtn = page.locator( 'button[aria-label="Settings"]' ).first();
			if ( await settingsBtn.isVisible().catch( () => false ) ) {
				await settingsBtn.click();
			}
		}

		// Wait for the Global Styles panel
		await expect( page.locator( '.spectra-manage-classes-inspector' ) ).toBeVisible( { timeout: 8000 } );

		// Get existing classes via REST to find one we can apply
		const nonce = await page.evaluate( async () => {
			return fetch( `${ location.origin }/wp-admin/admin-ajax.php?action=rest-nonce` ).then( r => r.text() );
		} );

		const classesData = await page.evaluate( async ( n ) => {
			const res = await fetch( `/?rest_route=/spectra-blocks/v1/global-styles/custom-classes`, {
				headers: { 'X-WP-Nonce': n }
			} );
			return res.json();
		}, nonce );

		const availableClasses = Object.keys( classesData?.classes ?? {} );
		if ( availableClasses.length === 0 ) {
			// No classes exist — create one via the flyout first
			await page.locator( '.spectra-manage-classes-inspector__btn' ).click();
			await page.waitForSelector( '.spectra-class-flyout', { timeout: 5000 } );
			await page.screenshot( { path: '/tmp/chip-00-flyout-no-classes.png' } );

			const input = page.locator( '.spectra-class-flyout__input' );
			await input.fill( 'chip-test' );
			await page.locator( '.spectra-class-flyout__add-btn' ).click();
			await page.waitForTimeout( 1500 );
			await page.locator( '.spectra-class-flyout__close' ).click();
			await page.waitForTimeout( 300 );
		}

		// Apply a class to the block via the react-select in the inspector
		const targetClass = availableClasses[ 0 ] || 'gs-chip-test';
		await page.evaluate( ( cls ) => {
			// Apply class directly via block attributes (bypasses react-select UI complexity)
			const { select, dispatch } = window.wp.data;
			const selectedClientId = select( 'core/block-editor' ).getSelectedBlockClientId();
			if ( selectedClientId ) {
				dispatch( 'core/block-editor' ).updateBlockAttributes( selectedClientId, {
					spectraGSClasses: [ cls ],
					className: cls,
				} );
			}
		}, targetClass );
		await page.waitForTimeout( 600 );

		await page.screenshot( { path: '/tmp/chip-01-class-applied.png' } );

		// Now the chip should appear in the react-select multi-value
		const chip = page.locator( '.spectra-gs-chip-edit' ).first();
		await expect( chip ).toBeVisible( { timeout: 5000 } );

		const chipText = await chip.textContent();
		console.log( `Clicking chip: "${ chipText.trim() }"` );

		// Click the chip — should open flyout pre-expanded on that class
		await chip.click();
		await page.waitForTimeout( 800 );

		await page.screenshot( { path: '/tmp/chip-02-after-chip-click.png' } );

		// Flyout should be visible
		await expect( page.locator( '.spectra-class-flyout' ) ).toBeVisible( { timeout: 5000 } );

		await page.screenshot( { path: '/tmp/chip-03-flyout-open.png' } );

		// The class item matching targetClass should be expanded (has 'is-expanded' class)
		const targetItem = page.locator( '.spectra-class-flyout__item.is-expanded' );
		await expect( targetItem ).toBeVisible( { timeout: 5000 } );

		// The expanded textarea should be visible
		await expect( page.locator( '.spectra-class-flyout__item-textarea' ) ).toBeVisible( { timeout: 3000 } );

		await page.screenshot( { path: '/tmp/chip-04-class-expanded.png' } );

		// Verify the expanded item's name matches the clicked chip's class
		const expandedName = await page.locator( '.spectra-class-flyout__item.is-expanded .spectra-class-flyout__item-name' ).textContent();
		console.log( `Expanded class: "${ expandedName?.trim() }"` );
		expect( expandedName?.trim() ).toBe( `.${ targetClass }` );
	} );

	test( 'clicking a second chip when flyout is already open switches expanded class', async ( { page } ) => {
		await page.waitForFunction( () => typeof window.__spectraGBSEditor?.open === 'function', { timeout: 15000 } );
		const isPro = await page.evaluate( () => window?.spectra_blocks_info?.spectra_pro_status === 'active' );
		if ( ! isPro ) {
			test.skip( 'spectra-blocks-pro not active' );
			return;
		}

		// Get at least 2 classes
		const nonce = await page.evaluate( async () =>
			fetch( `${ location.origin }/wp-admin/admin-ajax.php?action=rest-nonce` ).then( r => r.text() )
		);
		const classesData = await page.evaluate( async ( n ) => {
			const res = await fetch( `/?rest_route=/spectra-blocks/v1/global-styles/custom-classes`, { headers: { 'X-WP-Nonce': n } } );
			return res.json();
		}, nonce );
		const classes = Object.keys( classesData?.classes ?? {} );
		if ( classes.length < 2 ) {
			test.skip( 'Need at least 2 classes for this test' );
			return;
		}

		// Insert block and apply 2 classes
		await page.evaluate( () => {
			const { dispatch } = window.wp.data;
			const { createBlock } = window.wp.blocks;
			dispatch( 'core/block-editor' ).insertBlock( createBlock( 'spectra/container', {} ) );
		} );
		await page.waitForTimeout( 500 );

		const sidebar = page.locator( '.interface-interface-skeleton__sidebar' );
		if ( ! await sidebar.isVisible().catch( () => false ) ) {
			const settingsBtn = page.locator( 'button[aria-label="Settings"]' ).first();
			if ( await settingsBtn.isVisible().catch( () => false ) ) {await settingsBtn.click();}
		}
		await expect( page.locator( '.spectra-manage-classes-inspector' ) ).toBeVisible( { timeout: 8000 } );

		await page.evaluate( ( cls ) => {
			const { select, dispatch } = window.wp.data;
			const id = select( 'core/block-editor' ).getSelectedBlockClientId();
			if ( id ) {
				dispatch( 'core/block-editor' ).updateBlockAttributes( id, {
					spectraGSClasses: cls,
					className: cls.join( ' ' ),
				} );
			}
		}, [ classes[ 0 ], classes[ 1 ] ] );
		await page.waitForTimeout( 600 );

		const chips = page.locator( '.spectra-gs-chip-edit' );
		await expect( chips ).toHaveCount( 2, { timeout: 5000 } );

		// Click first chip
		await chips.first().click();
		await page.waitForTimeout( 800 );
		await expect( page.locator( '.spectra-class-flyout' ) ).toBeVisible( { timeout: 5000 } );
		await page.screenshot( { path: '/tmp/chip-05-first-expanded.png' } );

		const firstName = await page.locator( '.spectra-class-flyout__item.is-expanded .spectra-class-flyout__item-name' ).textContent();
		expect( firstName?.trim() ).toBe( `.${ classes[ 0 ] }` );

		// Now click second chip — flyout should switch to second class
		await chips.nth( 1 ).click();
		await page.waitForTimeout( 800 );
		await page.screenshot( { path: '/tmp/chip-06-second-expanded.png' } );

		const secondName = await page.locator( '.spectra-class-flyout__item.is-expanded .spectra-class-flyout__item-name' ).textContent();
		expect( secondName?.trim() ).toBe( `.${ classes[ 1 ] }` );
	} );
} );
