const { test, expect } = require( '@playwright/test' );
const { wpLogin, restPost, restDelete } = require( './helpers' );

const BASE_GRADIENT = 'linear-gradient(45deg,#ff0000 0%,#0000ff 100%)';

const buildBlockMarkup = () => {
	const attrs = JSON.stringify( {
		backgroundGradient: BASE_GRADIENT,
		responsiveControls: {
			md: { advBgGradientAngle: 90, advBgGradientLocation1: 10, advBgGradientLocation2: 80 },
			sm: { advBgGradientAngle: 270 },
		},
	} );
	return `<!-- wp:spectra/container ${ attrs } /-->`;
};

test.describe( 'container responsive gradient', () => {
	let pageId, pageUrl;

	test.beforeAll( async ( { browser } ) => {
		const page = await browser.newPage();
		await wpLogin( page );
		const result = await restPost( page, '/wp/v2/pages', {
			title: 'Responsive Gradient Test',
			status: 'publish',
			content: buildBlockMarkup(),
		} );
		pageId = result.id;
		pageUrl = result.link;
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

	const getBeforeBackground = ( page ) =>
		page.evaluate( () => {
			const el = document.querySelector( '.wp-block-spectra-container' );
			return el ? window.getComputedStyle( el, '::before' ).backgroundImage : null;
		} );

	test( 'desktop (1440px): base gradient 45deg rendered on ::before', async ( { page } ) => {
		await page.setViewportSize( { width: 1440, height: 900 } );
		await page.goto( pageUrl, { waitUntil: 'networkidle' } );
		const bg = await getBeforeBackground( page );
		expect( bg ).toContain( '45deg' );
	} );

	test( 'tablet (900px): angle override 90deg applied on ::before', async ( { page } ) => {
		await page.setViewportSize( { width: 900, height: 768 } );
		await page.goto( pageUrl, { waitUntil: 'networkidle' } );
		const bg = await getBeforeBackground( page );
		expect( bg ).toContain( '90deg' );
		expect( bg ).not.toContain( '45deg' );
	} );

	test( 'tablet (900px): location overrides 10% and 80% applied on ::before', async ( { page } ) => {
		await page.setViewportSize( { width: 900, height: 768 } );
		await page.goto( pageUrl, { waitUntil: 'networkidle' } );
		const bg = await getBeforeBackground( page );
		expect( bg ).toContain( '10%' );
		expect( bg ).toContain( '80%' );
	} );

	test( 'mobile (375px): angle override 270deg applied on ::before', async ( { page } ) => {
		await page.setViewportSize( { width: 375, height: 812 } );
		await page.goto( pageUrl, { waitUntil: 'networkidle' } );
		const bg = await getBeforeBackground( page );
		expect( bg ).toContain( '270deg' );
	} );
} );
