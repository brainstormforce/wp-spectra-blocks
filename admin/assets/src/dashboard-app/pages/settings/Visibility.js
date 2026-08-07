import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';
import Select from 'react-select';

import SettingsItem from './SettingsItem';
import { Switch } from '@bsf/force-ui';

import getApiData from '@Controls/getApiData';

const Visibility = () => {
	const dispatch = useDispatch();
	const [ pages, setPages ] = useState( [] );
	const [ isFetchPages, setFetchPages ] = useState( false );
	const visibilityMode = useSelector( ( state ) => state.visibilityMode );
	const visibilityPage = useSelector( ( state ) => state.visibilityPage );

	const enableComingSoonModeStatus = 'comingsoon' === visibilityMode;
	const enableMaintenanceModeStatus = 'maintenance' === visibilityMode;

	const updateVisibilityMode = ( mode ) => {
		const assetStatus = visibilityMode === mode ? 'disabled' : mode;

		dispatch( { type: 'UPDATE_VISIBILITY_MODE', payload: assetStatus } );

		const data = {
			security: spectra_blocks_react.visibility_mode_nonce,
			value: assetStatus,
		};

		getApiData( {
			url: spectra_blocks_react.ajax_url,
			action: 'spectra_blocks_visibility_mode',
			data,
		} ).then( () => {
			dispatch( {
				type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
				payload: __( 'Successfully saved!', 'spectra-blocks' ),
			} );
		} );
	};

	const updateSelectedPage = ( page ) => {
		dispatch( { type: 'UPDATE_VISIBILITY_PAGE', payload: page } );

		const data = {
			security: spectra_blocks_react.visibility_page_nonce,
			value: page.value,
		};

		getApiData( {
			url: spectra_blocks_react.ajax_url,
			action: 'spectra_blocks_visibility_page',
			data,
		} ).then( () => {
			dispatch( {
				type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
				payload: __( 'Successfully saved!', 'spectra-blocks' ),
			} );
		} );
	};

	const fetchPageHandler = ( keyword = '' ) => {
		const data = {
			security: spectra_blocks_react.fetch_pages_nonce,
			keyword,
		};
		setFetchPages( true );

		getApiData( {
			url: spectra_blocks_react.ajax_url,
			action: 'spectra_blocks_fetch_pages',
			data,
		} ).then( ( response ) => {
			setFetchPages( false );
			setPages( response.data );
		} );
	};

	const onChangeHandler = ( value ) => {
		const filterData = pages.filter( ( item ) =>
			item.label.toLowerCase().includes( value )
		);
		if ( filterData.length === 0 ) {
			fetchPageHandler( value );
		}
	};

	const customStyles = {
		control: ( provided ) => ( {
			...provided,
			cursor: 'pointer',
			fontSize: '0.875rem',
			lineHeight: '1.25rem',
			borderRadius: '0.375rem',
			color: '#64748b',
			borderColor: '#e2e8f0',
			boxShadow: 'none',
		} ),
		placeholder: ( provided ) => ( {
			...provided,
			color: '#94a3b8',
		} ),
		singleValue: ( provided ) => ( {
			...provided,
			color: '#64748b',
		} ),
	};

	const renderSelectComponent = () => (
		<Select
			isMulti={ false }
			placeholder={ __( 'Select the page you want', 'spectra-blocks' ) }
			defaultValue={ visibilityPage }
			onChange={ ( value ) => updateSelectedPage( value ) }
			onInputChange={ onChangeHandler }
			options={ pages }
			maxMenuHeight={ 140 }
			minMenuHeight={ 70 }
			isSearchable={ true }
			className="mt-4 w-9/12 h-10 transition"
			isLoading={ isFetchPages }
			onMenuOpen={ fetchPageHandler }
			theme={ ( theme ) => ( {
				...theme,
				colors: {
					...theme.colors,
					primary: '#94a3b8',
				},
			} ) }
			styles={ customStyles }
			components={ {
				IndicatorSeparator: () => null,
			} }
		/>
	);

	return (
		<>
			<SettingsItem
				title={ __( 'Enable Coming Soon Mode', 'spectra-blocks' ) }
				settingText={ __(
					'Show a Coming Soon page to visitors while you build. Search engines can still index the site normally, so use this before launch.',
					'spectra-blocks'
				) }
			>
				<Switch
					value={ 'comingsoon' === visibilityMode }
					onChange={ () => updateVisibilityMode( 'comingsoon' ) }
					size="md"
					className="spectra-blocks-remove-ring border-none"
				/>
			</SettingsItem>

			{ enableComingSoonModeStatus && renderSelectComponent() }

			<hr className="w-full border-b-0 border-x-0 border-t border-solid border-t-border-subtle" />

			<SettingsItem
				title={ __( 'Enable Maintenance Mode', 'spectra-blocks' ) }
				settingText={ __(
					"Maintenance Mode returns an HTTP 503 status code, signaling to search engines to revisit the website shortly. However, it's advisable not to utilize this mode for extended periods, ideally limiting its use to a few days.",
					'spectra-blocks'
				) }
			>
				<Switch
					value={ 'maintenance' === visibilityMode }
					onChange={ () => updateVisibilityMode( 'maintenance' ) }
					size="md"
					className="spectra-blocks-remove-ring border-none"
				/>
			</SettingsItem>

			{ enableMaintenanceModeStatus && renderSelectComponent() }
		</>
	);
};

export default Visibility;
