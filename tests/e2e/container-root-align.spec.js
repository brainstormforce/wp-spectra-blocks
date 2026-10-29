/**
 * Regression test for #552 — Container Align missing in the Site Editor at root level.
 *
 * The root-container alignment control used core's BlockAlignmentToolbar, which is gated by
 * useAvailableAlignments(). At the Site Editor (FSE) template root that set is empty, so the
 * control rendered nothing — leaving the block stuck at align="full" with no way to change it.
 * The fix renders a layout-independent alignment dropdown instead, so Align is available in the
 * Site Editor just as it is in the post/page editor.
 */
const { test, expect } = require( '@playwright/test' );
const { wpLogin } = require( './helpers' );

const BASE = 'http://spectra-local-site.local:10019';

// Insert a root-level container that is already past the layout picker, select it, return its state.
async function insertRootContainer( page ) {
	return page.evaluate( async () => {
		const block = wp.blocks.createBlock( 'spectra/container', { variationSelected: true } );
		wp.data.dispatch( 'core/block-editor' ).insertBlock( block, undefined, undefined, true );
		await new Promise( ( r ) => setTimeout( r, 1500 ) );
		const sel = wp.data.select( 'core/block-editor' );
		const a = sel.getBlockAttributes( block.clientId );
		return { clientId: block.clientId, align: a.align, isRootParent: a.isBlockRootParent, parents: sel.getBlockParents( block.clientId ) };
	} );
}

function toolbarLabels( page ) {
	return page.evaluate( () => {
		const bar = document.querySelector( '.block-editor-block-toolbar' );
		return bar ? Array.from( bar.querySelectorAll( 'button,[role="button"]' ) )
			.map( ( b ) => ( b.getAttribute( 'aria-label' ) || b.textContent || '' ).trim() ).filter( Boolean ) : null;
	} );
}

async function activateTheme( browser, slug ) {
	const page = await browser.newPage();
	await wpLogin( page );
	await page.goto( `${ BASE }/wp-admin/themes.php`, { waitUntil: 'load' } );
	const a = page.locator( `.theme[data-slug="${ slug }"] a.activate` ).first();
	if ( await a.count() ) {
		await a.click();
		await page.waitForLoadState( 'load' );
	}
	await page.close();
}

test.describe( '#552 — root container Align is layout-independent', () => {
	test.beforeAll( async ( { browser } ) => activateTheme( browser, 'spectra-one' ) );
	test.afterAll( async ( { browser } ) => activateTheme( browser, 'astra' ) );

	test( 'post editor: exactly one Align control, and it changes the alignment', async ( { page } ) => {
		await wpLogin( page );
		await page.goto( `${ BASE }/wp-admin/post-new.php?post_type=page`, { waitUntil: 'load' } );
		await page.keyboard.press( 'Escape' ).catch( () => {} );
		await page.waitForSelector( 'iframe[name="editor-canvas"]', { timeout: 30000 } );
		await page.waitForTimeout( 2000 );

		const r = await insertRootContainer( page );
		const labels = await toolbarLabels( page );
		expect( labels ).toContain( 'Align' );
		expect( labels.filter( ( l ) => l === 'Align' ) ).toHaveLength( 1 ); // no duplicate control

		// Functional: default is full → switch to None via the dropdown.
		await page.locator( '.block-editor-block-toolbar button[aria-label="Align"]' ).click( { force: true } );
		await page.locator( '.components-dropdown-menu__menu button', { hasText: 'None' } ).first().click( { force: true } );
		await expect.poll( () => page.evaluate( ( id ) =>
			wp.data.select( 'core/block-editor' ).getBlockAttributes( id ).align, r.clientId ) ).toBeFalsy();
	} );

	test( 'site editor: Align control is present at the template root', async ( { page } ) => {
		await wpLogin( page );
		await page.goto( `${ BASE }/wp-admin/site-editor.php?canvas=edit&p=%2Fwp_template%2Fspectra-one%2F%2Findex`, { waitUntil: 'load' } );
		await page.keyboard.press( 'Escape' ).catch( () => {} );
		await page.waitForSelector( 'iframe[name="editor-canvas"]', { timeout: 40000 } );
		await page.waitForTimeout( 3000 );

		const r = await insertRootContainer( page );
		const labels = await toolbarLabels( page );
		expect( r.isRootParent, 'container is at template root' ).toBe( true );
		expect( r.parents ).toHaveLength( 0 );
		expect( labels, 'Align must render at the FSE template root (this is #552)' ).toContain( 'Align' );
	} );
} );
