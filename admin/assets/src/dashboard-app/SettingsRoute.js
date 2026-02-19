import React from 'react';
import { useLocation } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import Welcome from '@DashboardApp/pages/welcome/Welcome';
import Blocks from '@DashboardApp/pages/blocks/Blocks';
import Settings from '@DashboardApp/pages/settings/Settings';
import AiFeatures from '@DashboardApp/pages/ai-features/AiFeatures';
import FreeVPro from './pages/free-vs-pro/Comparison';
import Learn from './pages/learn/Learn';

function SettingsRoute() {
	const query = new URLSearchParams( useLocation().search );
	const page = query.get( 'page' );
	const path = query.get( 'path' );
	const currentEvent = query.get( 'event' );

	let routePage = <p>{ __( 'Default route fallback', 'spectra-blocks' ) }</p>;

	if ( spectra_blocks_react.home_slug === page ) {

		// Get the Admin Sidebar Element from Wordpress.
		const adminMenu = document.getElementById( 'toplevel_page_spectra' )?.querySelector( '.wp-submenu' );

		// Remove the 'current' class from the old active elements.
		const oldCurrent = adminMenu?.querySelectorAll( '.current' );
		oldCurrent?.forEach( currentElement => {
			currentElement.classList.remove( 'current' );
		} );

		// Add the 'current' class to the new active elements.
		const newCurrent = adminMenu?.querySelector( `[href="admin.php?page=${ page }${ path ? `&path=${ path }` : ''}"]` );
		newCurrent?.classList?.add( 'current' );
		newCurrent?.parentElement?.classList?.add( 'current' );

		if ( 'getting-started' === currentEvent ) {
			routePage = <Welcome/>;
		} else {
			switch ( path ) {
				case 'blocks':
					routePage = <Blocks/>;
					break;
				case 'settings':
					routePage = <Settings/>;
					break;
				case 'ai-features':
					// Only render the AI Features page if Zip AI data was successfully localized.
					routePage = spectra_blocks_react?.zip_ai_admin_nonce ? <AiFeatures /> : <Welcome/>;
					break;
				case 'free-vs-pro':
					routePage = <FreeVPro />;
					break;
				case 'learn':
					routePage = <Learn />;
					break;
				case '':
				case undefined:
				case null:
					// The default case for the homepage specifically.
					routePage = <Welcome/>;
					break;
				default:
					// If the router page is anything other than the existing pages, filter the welcome page to see if there's any other required page.
					routePage = applyFilters( 'spectra.adminRoute', <Welcome/>, path );
			}
		}
	}

	return <>{ routePage }</>;
}

export default SettingsRoute;
