/**
 * Regression test for #776 — Slider pagination dots do not navigate when Enable Loop is on.
 *
 * A lint autofix corrupted the vendored Swiper bundle (var i,r,n -> let i,r,n) in the
 * pagination bullet click handler, throwing a temporal-dead-zone ReferenceError
 * ("Cannot access 'i' before initialization") that only fires inside the loop branch.
 * With the official Swiper 12.1.3 bundle restored, clicking a dot must navigate and
 * must not throw.
 */
const { test, expect } = require( '@playwright/test' );
const { wpLogin, restPost, restDelete } = require( './helpers' );

const SLIDER_CONTENT =
	'<!-- wp:spectra/slider {"loop":true,"pagination":true,"navigation":true} -->' +
	'<!-- wp:spectra/slider-child --><!-- wp:paragraph --><p>Slide One</p><!-- /wp:paragraph --><!-- /wp:spectra/slider-child -->' +
	'<!-- wp:spectra/slider-child --><!-- wp:paragraph --><p>Slide Two</p><!-- /wp:paragraph --><!-- /wp:spectra/slider-child -->' +
	'<!-- wp:spectra/slider-child --><!-- wp:paragraph --><p>Slide Three</p><!-- /wp:paragraph --><!-- /wp:spectra/slider-child -->' +
	'<!-- /wp:spectra/slider -->';

test.describe( 'Slider loop pagination (#776)', () => {
	let pageId;

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await wpLogin( page );
		const body = await restPost( page, '/wp/v2/pages', {
			title: 'Slider Loop Pagination Test',
			status: 'publish',
			content: SLIDER_CONTENT,
		} );
		pageId = body.id;
		await page.close();
	} );

	test.afterAll( async ( { browser } ) => {
		if ( ! pageId ) {
			return;
		}
		const page = await browser.newPage();
		await wpLogin( page );
		await restDelete( page, `/wp/v2/pages/${ pageId }?force=true` );
		await page.close();
	} );

	test( 'clicking a pagination dot navigates with loop enabled and throws no error', async ( {
		page,
	} ) => {
		const pageErrors = [];
		page.on( 'pageerror', ( err ) => pageErrors.push( err.message ) );

		await page.goto( `/?page_id=${ pageId }`, { waitUntil: 'load' } );

		// Swiper generates the bullets at runtime once it initialises.
		const bullets = page.locator( '.swiper-pagination-bullet' );
		await expect( bullets.first() ).toBeVisible( { timeout: 15000 } );
		await expect( bullets ).toHaveCount( 3 );

		const activeSlide = page.locator( '.swiper-slide-active .slide-content' );
		await expect( activeSlide ).toHaveText( /Slide One/, { timeout: 15000 } );

		// Click the third dot — should navigate to "Slide Three".
		await bullets.nth( 2 ).click();

		await expect( activeSlide ).toHaveText( /Slide Three/, { timeout: 15000 } );

		// The corrupted bundle threw exactly this on the click handler.
		const tdz = pageErrors.filter( ( m ) =>
			/Cannot access 'i' before initialization/.test( m )
		);
		expect( tdz, `Unexpected page errors: ${ pageErrors.join( ' | ' ) }` ).toHaveLength( 0 );
	} );
} );
