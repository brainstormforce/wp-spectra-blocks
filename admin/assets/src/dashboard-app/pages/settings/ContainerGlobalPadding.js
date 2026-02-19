import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';
import React, { useEffect } from 'react';
import getApiData from '@Controls/getApiData';

import SettingsItem from './SettingsItem';
import { Input } from '@bsf/force-ui';


const ContainerGlobalPadding = () => {

	const containerGlobalPadding = useSelector( ( state ) => state.containerGlobalPadding );

	useEffect( () => {
		if ( 'default' === containerGlobalPadding ) {
			saveValue( 10, false );
		}
	}, [] );

	const dispatch = useDispatch();

	const saveValue = ( value, showNotice = true ) => {
		dispatch( { type: 'UPDATE_CONTAINER_GLOBAL_PADDING', payload: value } );

		// Create an object with the security and value properties
        const data = {
            security: spectra_blocks_react.container_global_padding_nonce,
            value,
        };
		// Call the getApiData function with the specified parameters
        const getApiFetchData = getApiData( {
            url: spectra_blocks_react.ajax_url,
            action: 'spectra_blocks_container_global_padding',
            data,
        } );
		// Wait for the API call to complete, then update the state to show a notification that the settings have been saved
        getApiFetchData.then( () => {
            if ( showNotice ) {
				dispatch( { type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION', payload: __( 'Successfully saved!', 'spectra-blocks' ) } );
			}
        } );
	};

	const updateContainerGlobalPadding = ( value ) => {
		saveValue( value );
	};

    return (
        <>
			<SettingsItem
				title={ __( 'Container Padding', 'spectra-blocks' ) }
				settingText={ __( 'This setting will apply default padding in the Container Block.', 'spectra-blocks' ) }
			>
				<Input
					defaultValue={ 1140 }
					id="default-width"
					className="settings-input"
					suffix={
						<span className="text-badge-color-gray p-0.5 text-center text-xs font-medium">
							PX
						</span>
					}
					type="number"
					value={ containerGlobalPadding }
					onChange={ updateContainerGlobalPadding }
					min={ 0 }
					max={ 100 }
				/>
			</SettingsItem>
			<hr className="w-full border-b-0 border-x-0 border-t border-solid border-t-border-subtle" />
		</>
    );
};

export default ContainerGlobalPadding;
