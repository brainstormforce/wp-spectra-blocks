import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';
import getApiData from '@Controls/getApiData';

import SettingsItem from './SettingsItem';
import { Switch } from '@bsf/force-ui';

const EnableLegacyDesignLibrary = () => {

    const dispatch = useDispatch();

    const enableLegacyDesignLibrary = useSelector( ( state ) => state.enableLegacyDesignLibrary );
    const enableLegacyDesignLibraryStatus = 'disabled' === enableLegacyDesignLibrary ? false : true;

    const updateEnableLegacyDesignLibraryStatus = () => {

        let assetStatus;
		if ( enableLegacyDesignLibrary === 'disabled' ) {
            assetStatus = 'enabled';
		} else {
            assetStatus = 'disabled';
		}

        dispatch( { type: 'UPDATE_ENABLE_LEGACY_DESIGN_LIBRARY', payload: assetStatus } );

		// Create an object with the security and value properties
        const data = {
            security: uag_react.enable_legacy_design_library_nonce,
            value: assetStatus,
        };
        // Call the getApiData function with the specified parameters
        const getApiFetchData = getApiData( {
            url: uag_react.ajax_url,
            action: 'uag_enable_legacy_design_library',
            data,
        } );
        // Wait for the API call to complete, then update the state to show a notification that the settings have been saved
        getApiFetchData.then( () => {
			dispatch( { type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION', payload: __( 'Successfully saved!', 'ultimate-addons-for-gutenberg' ) } );
        } );
    };

    return (
        <>
			<SettingsItem
				title={ __( 'Enable Legacy Content', 'ultimate-addons-for-gutenberg' ) }
				settingText={ __( 'Access legacy Kits, Patterns, and Blocks from earlier versions of Spectra.', 'ultimate-addons-for-gutenberg' ) }
			>
				<Switch
					value={ enableLegacyDesignLibraryStatus }
					onChange={ updateEnableLegacyDesignLibraryStatus }
					size="md"
                    className="uagb-remove-ring border-none"
				/>
			</SettingsItem>
			<hr className="w-full border-b-0 border-x-0 border-t border-solid border-t-border-subtle" />
		</>
    );
};

export default EnableLegacyDesignLibrary;
