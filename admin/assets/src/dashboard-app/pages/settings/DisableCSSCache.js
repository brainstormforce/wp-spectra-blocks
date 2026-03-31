import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';
import getApiData from '@Controls/getApiData';

import SettingsItem from './SettingsItem';
import { Switch } from '@bsf/force-ui';

const DisableCSSCache = () => {
	const dispatch = useDispatch();

	const disableCSSCache = useSelector( ( state ) => state.disableCSSCache );
	const disableCSSCacheStatus = 'enabled' === disableCSSCache ? true : false;

	const updateDisableCSSCacheStatus = () => {
		let cacheStatus;
		if ( disableCSSCache === 'enabled' ) {
			cacheStatus = 'disabled';
		} else {
			cacheStatus = 'enabled';
		}

		dispatch( { type: 'UPDATE_DISABLE_CSS_CACHE', payload: cacheStatus } );

		// Create an object with the security and value properties
		const data = {
			security: spectra_blocks_react.disable_css_cache_nonce,
			value: cacheStatus,
		};
		// Call the getApiData function with the specified parameters
		const getApiFetchData = getApiData( {
			url: spectra_blocks_react.ajax_url,
			action: 'spectra_blocks_disable_css_cache',
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
				title={ __( 'Disable Cached Styles', 'spectra-blocks' ) }
				settingText={ __(
					'Not recommended for production sites as it may affect performance. Enable only during development or if you\'re experiencing issues with styles not loading or caching conflicts. When active, styles regenerate on every page load instead of using cached versions.',
					'spectra-blocks'
				) }
			>
				<Switch
					value={ disableCSSCacheStatus }
					onChange={ updateDisableCSSCacheStatus }
					size="md"
					className="spectra-blocks-remove-ring"
				/>
			</SettingsItem>
			<hr className="w-full border-b-0 border-x-0 border-t border-solid border-t-border-subtle" />
		</>
	);
};

export default DisableCSSCache;
