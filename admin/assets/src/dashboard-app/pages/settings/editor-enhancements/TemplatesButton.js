import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';
import getApiData from '@Controls/getApiData';

import SettingsItem from '../SettingsItem';
import { Switch } from '@bsf/force-ui';

const TemplatesButton = () => {
	const dispatch = useDispatch();

	const enableTemplates = useSelector( ( state ) => state.enableTemplates );
	const enableTemplatesStatus = 'no' === enableTemplates ? false : true;

	const updateEnableTemplatesStatus = () => {
		let assetStatus;
		if ( enableTemplates === 'no' ) {
			assetStatus = 'yes';
		} else {
			assetStatus = 'no';
		}

		dispatch( { type: 'UPDATE_TEMPLATES_BUTTON', payload: assetStatus } );

		const action = 'spectra_blocks_enable_templates_button',
			nonce = spectra_blocks_react.enable_templates_button_nonce;

		// Create an object with the security and value properties
		const data = {
			security: nonce,
			value: assetStatus,
		};
		// Call the getApiData function with the specified parameters
		const getApiFetchData = getApiData( {
			url: spectra_blocks_react.ajax_url,
			action,
			data,
		} );
		// Wait for the API call to complete, then update the state to show a notification that the settings have been saved
		getApiFetchData.then( () => {
			dispatch( { type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION', payload: 'Successfully saved!' } );
		} );
	};

	return (
		<>
			<SettingsItem
				title={ __( 'Enable Templates Button', 'spectra-blocks' ) }
				settingText={ __(
					'Show the Templates button in the editor so you can browse ready-made patterns and page templates.',
					'spectra-blocks'
				) }
			>
				<Switch
					value={ enableTemplatesStatus }
					onChange={ updateEnableTemplatesStatus }
					size="md"
					className="spectra-blocks-remove-ring border-none"
				/>
			</SettingsItem>
		</>
	);
};

export default TemplatesButton;
