import React from 'react';
import { createRoot } from 'react-dom/client';
/* Main Component */
import '@Common/all-config.scss';
import SettingsWrap from '@DashboardApp/SettingsWrap';
import { Provider } from 'react-redux';
import globalDataStore from '@Admin/store/globalDataStore';
import setInitialState  from '@Utils/setInitialState';

const currentState = globalDataStore.getState();

if ( ! currentState.initialStateSetFlag ) {

	setInitialState( globalDataStore );
}

const container = document.getElementById( 'spectra-blocks-dashboard-app' );
const root = createRoot( container );
root.render(
	<Provider store={globalDataStore}>
		<SettingsWrap/>
	</Provider>
);