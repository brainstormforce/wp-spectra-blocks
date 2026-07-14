const WP_URL = 'http://spectra-local-site.local:10019';

async function wpLogin( page ) {
	await page.goto( `${ WP_URL }/wp-login.php`, { timeout: 30000 } );
	await page.fill( '#user_login', 'root' );
	await page.fill( '#user_pass', 'root' );
	await page.click( '#wp-submit' );
	await page.waitForLoadState( 'load', { timeout: 60000 } );
}

async function getRestNonce( page ) {
	const res = await page.request.get(
		`${ WP_URL }/wp-admin/admin-ajax.php?action=rest-nonce`
	);
	return ( await res.text() ).trim();
}

async function restPost( page, restRoute, data ) {
	const nonce = await getRestNonce( page );
	const res = await page.request.post( `${ WP_URL }/?rest_route=${ restRoute }`, {
		headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
		data,
	} );
	return res.json();
}

async function restDelete( page, restRoute ) {
	const nonce = await getRestNonce( page );
	await page.request.delete( `${ WP_URL }/?rest_route=${ restRoute }`, {
		headers: { 'X-WP-Nonce': nonce },
	} );
}

async function createCarouselPage( page ) {
	const postBlock =
		'<!-- wp:spectra/post {"layoutType":"carousel","navigation":true} -->' +
		'<!-- wp:spectra/post-template /-->' +
		'<!-- /wp:spectra/post -->';
	const body = await restPost( page, '/wp/v2/pages', {
		title: 'Test Post Carousel',
		status: 'publish',
		content: postBlock,
	} );
	return { id: body.id, url: body.link };
}

module.exports = { WP_URL, wpLogin, getRestNonce, restPost, restDelete, createCarouselPage };
