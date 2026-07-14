const { test, expect } = require( '@playwright/test' );
const { wpLogin, restPost, restDelete, createCarouselPage } = require( './helpers' );

test.use( { viewport: { width: 1440, height: 900 } } );

test.describe( 'post carousel functionality', () => {
	let carouselPageId, carouselPageUrl;
	const seededPostIds = [];

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await wpLogin( page );

		// Seed at least 2 posts so Swiper loop mode has real slides to navigate.
		for ( let i = 0; i < 2; i++ ) {
			const post = await restPost( page, '/wp/v2/posts', {
				title: `Carousel Test Post ${ i + 1 }`,
				status: 'publish',
			} );
			seededPostIds.push( post.id );
		}

		( { id: carouselPageId, url: carouselPageUrl } = await createCarouselPage( page ) );
		await page.close();
	} );

	test.afterAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await wpLogin( page );
		if ( carouselPageId ) {
			await restDelete( page, `/wp/v2/pages/${ carouselPageId }?force=true` );
		}
		for ( const id of seededPostIds ) {
			await restDelete( page, `/wp/v2/posts/${ id }?force=true` );
		}
		await page.close();
	} );

	test( 'carousel initializes Swiper and navigation advances slides', async ( { page } ) => {
		await wpLogin( page );
		await page.goto( carouselPageUrl );
		await page.waitForSelector( '.swiper.swiper-initialized', { timeout: 15000 } );

		const slideCount = await page.locator( '.swiper-slide:not(.swiper-slide-duplicate)' ).count();
		expect( slideCount ).toBeGreaterThan( 0 );

		await expect( page.locator( '.swiper-button-next' ) ).toBeVisible();
		await expect( page.locator( '.swiper-button-prev' ) ).toBeVisible();

		const indexBefore = await page.evaluate( () => {
			const container = document.querySelector( '.wp-block-spectra-post' );
			return container?.swiperInstance?.realIndex ?? -1;
		} );

		await page.evaluate( () => document.querySelector( '.swiper-button-next' )?.click() );

		// Wait for Swiper animation to complete rather than sleeping a fixed amount.
		await page.waitForFunction(
			() => {
				const container = document.querySelector( '.wp-block-spectra-post' );
				const swiper = container?.swiperInstance;
				return swiper && ! swiper.animating;
			},
			{ timeout: 3000 }
		);

		const indexAfter = await page.evaluate( () => {
			const container = document.querySelector( '.wp-block-spectra-post' );
			return container?.swiperInstance?.realIndex ?? -1;
		} );

		expect( indexAfter ).not.toBe( indexBefore );
	} );
} );
