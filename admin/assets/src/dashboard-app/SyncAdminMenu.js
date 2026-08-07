import { useEffect } from '@wordpress/element';
import { useLocation } from 'react-router-dom';

/**
 * Keeps the WordPress admin sidebar in sync with client side navigation.
 *
 * The dashboard runs on BrowserRouter, so moving between tabs never reloads the
 * page. WordPress renders the sidebar once, server side, which leaves the
 * "current" highlight stuck on whichever submenu item was active on first load.
 * Clicking Learn or Settings therefore updated the tab bar but left Dashboard
 * looking selected in the sidebar.
 *
 * Each submenu link is matched on its own query string rather than on its label,
 * so items added by Spectra Blocks Pro and the conditional Free vs Pro item are
 * handled without needing to be listed here.
 *
 * @since 1.0.4
 * @return {null} Renders nothing.
 */
const SyncAdminMenu = () => {
	const location = useLocation();

	useEffect( () => {
		const links = document.querySelectorAll(
			'#adminmenu .wp-submenu a[href*="page=spectra-blocks"]'
		);

		if ( ! links.length ) {
			return;
		}

		const currentPath =
			new URLSearchParams( location.search ).get( 'path' ) || '';

		links.forEach( ( link ) => {
			const href = link.getAttribute( 'href' ) || '';
			const linkQuery = new URLSearchParams(
				href.split( '?' )[ 1 ] || ''
			);

			// Only the page and path decide the active item. Deeper params such as
			// the settings pane keep Settings highlighted rather than clearing it.
			const isActive =
				'spectra-blocks' === linkQuery.get( 'page' ) &&
				( linkQuery.get( 'path' ) || '' ) === currentPath;

			// Toggle rather than overwrite, so WordPress classes already on the
			// element, such as wp-first-item, survive.
			link.classList.toggle( 'current', isActive );

			if ( link.parentElement ) {
				link.parentElement.classList.toggle( 'current', isActive );
			}

			if ( isActive ) {
				link.setAttribute( 'aria-current', 'page' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
	}, [ location.search ] );

	return null;
};

export default SyncAdminMenu;
