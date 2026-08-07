import React, { useState } from 'react';
import { Container, Button, Badge, Text } from '@bsf/force-ui';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const ExtendWebsiteItem = ( { plugin } ) => {
	const { path, slug, siteUrl, icon, type, name, zipUrl, desc, isFree, status, settings_url } = plugin;
	const [ pluginData, setPluginData ] = useState( null );

	const translatedActivated = __( 'Activated', 'spectra-blocks' );
	const translatedActive = __( 'Activate', 'spectra-blocks' );

	const getAction = ( pluginStatus ) => {
		if ( pluginStatus === 'Activated' ) {
			return 'site_redirect';
		} else if ( pluginStatus === 'Installed' ) {
			return 'spectra_blocks_recommended_plugin_activate';
		}
		return 'spectra_blocks_recommended_plugin_install';
	};

	const handlePluginAction = ( e ) => {
		const action = e.currentTarget.dataset.action;
		const formData = new window.FormData();
		const currentPluginData = {
			init: e.currentTarget.dataset.init,
			type: e.currentTarget.dataset.type,
			slug: e.currentTarget.dataset.slug,
			name: e.currentTarget.dataset.pluginname,
		};

		switch ( action ) {
			case 'spectra_blocks_recommended_plugin_activate':
				// Confirmation only for theme activation
				if ( currentPluginData.type === 'theme' ) {
					// Show dialog for confirmation
					setPluginData( currentPluginData );
				} else {
					// Directly activate for non-theme plugins
					activatePlugin( currentPluginData );
				}
				break;

			case 'spectra_blocks_recommended_plugin_install':
				// Installation process without any confirmation
				formData.append(
					'action',
					currentPluginData.type === 'theme'
						? 'spectra_blocks_recommended_theme_install'
						: 'spectra_blocks_recommended_plugin_install'
				);
				formData.append( '_ajax_nonce', spectra_blocks_react.installer_nonce );
				formData.append( 'slug', currentPluginData.slug );

				e.target.innerText = __( 'Installing..', 'spectra-blocks' );

				apiFetch( {
					url: spectra_blocks_react.ajax_url,
					method: 'POST',
					body: formData,
				} ).then( ( data ) => {
					if ( data.success || data.errorCode === 'folder_exists' ) {
						e.target.innerText = __( 'Installed', 'spectra-blocks' );

						if ( currentPluginData.type === 'theme' ) {
							// Change button state to "Activate" after successful installation.
							const buttonElement = document.querySelector( `[data-slug="${ currentPluginData.slug }"]` );
							buttonElement.dataset.action = 'spectra_blocks_recommended_plugin_activate';
							e.target.innerText = translatedActive;
						} else {
							activatePlugin( currentPluginData );
						}
					} else {
						e.target.innerText = __( 'Install', 'spectra-blocks' );
					}
				} );
				break;

			case 'site_redirect':
				// Do nothing.
				break;

			default:
				// Do nothing.
				break;
		}
	};

	const activatePlugin = ( currentPluginData ) => {
		const formData = new window.FormData();
		formData.append( 'action', 'spectra_blocks_recommended_plugin_activate' );
		formData.append( 'nonce', spectra_blocks_react.installer_nonce );
		formData.append( 'plugin', currentPluginData.init );
		formData.append( 'type', currentPluginData.type );
		formData.append( 'slug', currentPluginData.slug );

		const buttonElement = document.querySelector( `[data-slug="${ currentPluginData.slug }"]` );
		const spanElement = buttonElement.querySelector( 'span' );

		spanElement.innerText = __( 'Activating..', 'spectra-blocks' );

		apiFetch( {
			url: spectra_blocks_react.ajax_url,
			method: 'POST',
			body: formData,
		} ).then( ( data ) => {
			if ( data.success ) {
				if ( spanElement ) {
					// Check if spanElement is not null.
					buttonElement.style.color = '#16A34A';
					buttonElement.dataset.action = 'site_redirect';
					buttonElement.classList.add( 'spectra-blocks-plugin-activated' );
					spanElement.innerText = translatedActivated;
					window.open( settings_url, '_blank' );
				}
			} else {
				const button = document.querySelector( `[data-slug="${ pluginData.slug }"]` );
				if ( button ) {
					// Check if buttonElement is not null.
					const span = button.querySelector( 'span' );
					if ( span ) {
						// Check if spanElement is not null.
						span.innerText = translatedActive;
					}
				}
			}
		} );
	};

	const getStatusLabel = ( pluginStatus ) => {
		if ( pluginStatus === 'Activated' ) {
			return translatedActivated;
		}

		if ( pluginStatus === 'Installed' ) {
			return translatedActive;
		}

		return pluginStatus;
	};

	const translatedName = name

	const translatedDesc = desc

	return (
		<Container align="center" containerType="flex" direction="column" justify="between">
			<h2 className="sr-only">{ name }</h2>

			<Container.Item className="flex items-center justify-between w-full p-1">
				{ icon() }
				<div className="flex items-center justify-between gap-2">
					{ isFree && <Badge label={ __( 'Free', 'spectra-blocks' ) } size="xs" type="pill" variant="green" /> }
					<Button
						size="xs"
						variant="link"
						className='cursor-pointer capitalize hover:no-underline focus:ring-0 focus:ring-transparent text-button-primary'
						onClick={ handlePluginAction } // Trigger action on click
						data-plugin={ zipUrl }
						data-type={ type }
						data-pluginname={ name }
						data-slug={ slug }
						data-site={ siteUrl }
						data-init={ path }
						data-action={ getAction( status ) }
						style={ {
							color: status === 'Activated' ? '#16A34A' : '#6005FF',
						} }
					>
						{ getStatusLabel( status ) }
					</Button>
				</div>
			</Container.Item>

			<Container.Item className="w-full flex flex-col gap-0.5 p-1">
				<Text as="h4" weight={500} className='text-sm text-text-primary'>
					{ translatedName }
				</Text>
				<Text size={12} weight={12} color="tertiary" className='text-sm text-text-secondary'>
					{ translatedDesc }
				</Text>
			</Container.Item>
		</Container>
	);
};

export default ExtendWebsiteItem;
