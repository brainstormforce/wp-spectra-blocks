import React from 'react';
import { BrowserRouter as Router, Route, Switch } from 'react-router-dom';
import AdminHeader from '@Common/components/AdminHeader';
import MainNav from './MainNav';
import SettingsRoute from './SettingsRoute';
import SettingsSavedNotification from './SettingsSavedNotification';
import SyncAdminMenu from './SyncAdminMenu';

const SettingsWrap = () => {

    return (
        <Router>
			<SyncAdminMenu/>
            <AdminHeader>
				<MainNav/>
			</AdminHeader>
			<SettingsSavedNotification/>
			<Switch>
				<Route path="/">
					<SettingsRoute />
				</Route>
			</Switch>
		</Router>
    );
};

export default SettingsWrap;
