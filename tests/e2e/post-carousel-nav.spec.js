const { test, expect } = require( '@playwright/test' );
const { wpLogin, createCarouselPage, restDelete } = require( './helpers' );

test.use( { viewport: { width: 1440, height: 900 } } );

test.describe( 'post carousel navigation buttons', () => {
	let carouselPageId, carouselPageUrl;

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await wpLogin( page );
		( { id: carouselPageId, url: carouselPageUrl } = await createCarouselPage( page ) );
		await page.close();
	} );

	test.afterAll( async ( { browser } ) => {
		if ( carouselPageId ) {
			const page = await browser.newPage();
			await wpLogin( page );
			await restDelete( page, `/wp/v2/pages/${ carouselPageId }?force=true` );
			await page.close();
		}
	} );

	test( 'nav buttons should not render literal "none" text', async ( { page } ) => {
		await wpLogin( page );
		await page.goto( carouselPageUrl );
		await page.waitForSelector( '.swiper-button-next, .swiper-button-prev', { timeout: 10000 } );

		const nextContent = await page.evaluate( () => {
			const btn = document.querySelector( '.swiper-button-next' );
			return btn ? window.getComputedStyle( btn, '::after' ).content : null;
		} );
		const prevContent = await page.evaluate( () => {
			const btn = document.querySelector( '.swiper-button-prev' );
			return btn ? window.getComputedStyle( btn, '::after' ).content : null;
		} );

		expect( nextContent ).not.toBe( '"none"' );
		expect( prevContent ).not.toBe( '"none"' );
	} );
} );
