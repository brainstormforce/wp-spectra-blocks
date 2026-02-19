import React, { lazy, Suspense } from 'react';
import { useLocation } from 'react-router-dom';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import PageSkeleton from './PageSkeleton';

const Welcome    = lazy( () => import( /* webpackChunkName: "page-welcome"      */ '@DashboardApp/pages/welcome/Welcome' ) );
const Blocks     = lazy( () => import( /* webpackChunkName: "page-blocks"       */ '@DashboardApp/pages/blocks/Blocks' ) );
const Settings   = lazy( () => import( /* webpackChunkName: "page-settings"     */ '@DashboardApp/pages/settings/Settings' ) );
const AiFeatures = lazy( () => import( /* webpackChunkName: "page-ai-features"  */ '@DashboardApp/pages/ai-features/AiFeatures' ) );
const FreeVPro   = lazy( () => import( /* webpackChunkName: "page-free-vs-pro"  */ './pages/free-vs-pro/Comparison' ) );
const Learn      = lazy( () => import( /* webpackChunkName: "page-learn"        */ './pages/learn/Learn' ) );

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

	return (
		<Suspense fallback={ <PageSkeleton /> }>
			{ routePage }
		</Suspense>
	);
}

export default SettingsRoute;
