const { test, expect } = require( '@playwright/test' );
const { WP_URL, wpLogin } = require( './helpers' );

const AI_FEATURES_URL = `${ WP_URL }/wp-admin/admin.php?page=spectra-blocks&path=ai-features`;

async function setPluginStatus( page, overrides ) {
	await page.addInitScript( ( data ) => {
		let _val;
		Object.defineProperty( window, 'spectra_blocks_react', {
			configurable: true,
			get() {
				return _val;
			},
			set( v ) {
				_val = Object.assign( {}, v, data );
			},
		} );
	}, overrides );
}

async function mockConnectAjax( page, { installStatus = 'installed', activateStatus = 'activated' } = {} ) {
	await page.route( ( url ) => url.href.includes( 'admin-ajax.php' ), async ( route ) => {
		const body = ( await route.request().postData() ) ?? '';

		if ( body.includes( 'spectra_blocks_install_zip_ai' ) ) {
			const ok = installStatus !== 'error';
			await new Promise( ( r ) => setTimeout( r, 150 ) );
			return route.fulfill( {
				contentType: 'application/json',
				body: JSON.stringify(
					ok
						? { success: true, data: { status: installStatus } }
						: { success: false, data: { message: 'Install failed' } }
				),
			} );
		}

		if ( body.includes( 'spectra_blocks_activate_zip_ai' ) ) {
			const ok = activateStatus !== 'error';
			await new Promise( ( r ) => setTimeout( r, 150 ) );
			return route.fulfill( {
				contentType: 'application/json',
				body: JSON.stringify(
					ok
						? { success: true, data: { status: activateStatus } }
						: { success: false, data: { message: 'Activate failed' } }
				),
			} );
		}

		return route.continue();
	} );
}

async function interceptZipWPRedirect( page ) {
	let redirectedTo = '';
	await page.route( 'https://app.zipwp.com/**', ( route ) => {
		redirectedTo = route.request().url();
		route.abort();
	} );
	return () => redirectedTo;
}

test.describe( 'AI Features connect flow', () => {
	test.beforeEach( async ( { page } ) => {
		await wpLogin( page );
	} );

	test( 'fresh install: Installing → Activating → Connecting to ZipWP → redirects', async ( { page } ) => {
		await setPluginStatus( page, {
			zip_ai_plugin_status: 'Install',
			zip_ai_is_authorized: false,
			zip_ai_status: 'inactive',
		} );
		await mockConnectAjax( page, { installStatus: 'installed', activateStatus: 'activated' } );
		const getRedirect = await interceptZipWPRedirect( page );

		await page.goto( AI_FEATURES_URL, { waitUntil: 'networkidle' } );

		// 1. Initial state.
		await expect( page.getByRole( 'button', { name: /get started/i } ) ).toBeVisible();
		await page.getByRole( 'button', { name: /get started/i } ).click();

		// 2. Installing…
		await expect( page.getByRole( 'button', { name: /installing/i } ) ).toBeVisible( { timeout: 3000 } );

		// 3. Activating…
		await expect( page.getByRole( 'button', { name: /activating/i } ) ).toBeVisible( { timeout: 3000 } );

		// 4. Connecting to ZipWP…
		await expect( page.getByRole( 'button', { name: /connecting to zipwp/i } ) ).toBeVisible( { timeout: 3000 } );

		// 5. Redirect fired to ZipWP auth.
		await expect.poll( getRedirect, { timeout: 3000 } ).toContain( 'zipwp.com' );
	} );

	test( 'plugin installed but inactive: Activating → Connecting to ZipWP → redirects', async ( { page } ) => {
		await setPluginStatus( page, {
			zip_ai_plugin_status: 'Installed',
			zip_ai_is_authorized: false,
			zip_ai_status: 'inactive',
		} );
		await mockConnectAjax( page, { activateStatus: 'activated' } );
		const getRedirect = await interceptZipWPRedirect( page );

		await page.goto( AI_FEATURES_URL, { waitUntil: 'networkidle' } );

		// "Installed" state shows "Activate AI Features".
		await expect( page.getByRole( 'button', { name: /activate ai features/i } ) ).toBeVisible();
		await page.getByRole( 'button', { name: /activate ai features/i } ).click();

		// Skips Installing — goes straight to Activating.
		await expect( page.getByRole( 'button', { name: /activating/i } ) ).toBeVisible( { timeout: 3000 } );
		await expect( page.getByRole( 'button', { name: /connecting to zipwp/i } ) ).toBeVisible( { timeout: 3000 } );
		await expect.poll( getRedirect, { timeout: 3000 } ).toContain( 'zipwp.com' );
	} );

	test( 'plugin already active: Connecting to ZipWP → redirects (no AJAX steps)', async ( { page } ) => {
		await setPluginStatus( page, {
			zip_ai_plugin_status: 'Activated',
			zip_ai_is_authorized: false,
			zip_ai_status: 'inactive',
		} );
		const getRedirect = await interceptZipWPRedirect( page );

		await page.goto( AI_FEATURES_URL, { waitUntil: 'networkidle' } );

		await expect( page.getByRole( 'button', { name: /get started/i } ) ).toBeVisible();
		await page.getByRole( 'button', { name: /get started/i } ).click();

		// No install/activate AJAX — goes straight to Connecting to ZipWP.
		await expect( page.getByRole( 'button', { name: /connecting to zipwp/i } ) ).toBeVisible( { timeout: 2000 } );
		await expect.poll( getRedirect, { timeout: 3000 } ).toContain( 'zipwp.com' );
	} );

	test( 'already authorized: shows credits, no connect button', async ( { page } ) => {
		await setPluginStatus( page, {
			zip_ai_plugin_status: 'Activated',
			zip_ai_is_authorized: true,
			zip_ai_status: 'connected',
			zip_ai_credit_details: { used: 120, total: 500 },
		} );

		await page.goto( AI_FEATURES_URL, { waitUntil: 'networkidle' } );

		await expect( page.getByText( '120' ) ).toBeVisible( { timeout: 5000 } );
		await expect( page.getByText( '500' ) ).toBeVisible();
		await expect( page.getByRole( 'button', { name: /get started/i } ) ).toHaveCount( 0 );
	} );
} );
