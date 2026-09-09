import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useDispatch } from 'react-redux';
import { Input, Label, Switch, Button } from '@bsf/force-ui';
import { Lock } from 'lucide-react';

import apiFetch from '@wordpress/api-fetch';
import getApiData from '@Controls/getApiData';

const FIELD_DEFAULTS = {
	author_name: '',
	author_url: '',
	license_link: '',
	plugin_name: '',
	plugin_description: '',
	plugin_screenshot: '',
	plugin_icon: '',
	pro_plugin_name: '',
	pro_plugin_description: '',
	hide_branding: false,
};

const SectionHeading = ( { children } ) => (
	<h4 className="m-0 text-base font-semibold text-text-primary">
		{ children }
	</h4>
);

const TextField = ( {
	id,
	label,
	help,
	type = 'text',
	value,
	placeholder,
	disabled,
	onChange,
} ) => (
	<div className="flex flex-col gap-1.5 w-full">
		<Label htmlFor={ id } className="font-medium text-text-secondary" size="sm">
			{ label }
		</Label>
		<Input
			id={ id }
			name={ id }
			type={ type }
			size="md"
			value={ value }
			placeholder={ placeholder }
			disabled={ disabled }
			onChange={ disabled ? undefined : onChange }
		/>
		{ help && (
			<Label className="m-0 font-normal" size="xs" tag="p" variant="help">
				{ help }
			</Label>
		) }
	</div>
);

const TextareaField = ( { id, label, value, placeholder, disabled, onChange } ) => (
	<div className="flex flex-col gap-1.5 w-full">
		<Label htmlFor={ id } className="font-medium text-text-secondary" size="sm">
			{ label }
		</Label>
		<textarea
			id={ id }
			name={ id }
			value={ value }
			placeholder={ placeholder }
			disabled={ disabled }
			onChange={
				disabled ? undefined : ( event ) => onChange( event.target.value )
			}
			className="w-full min-h-24 rounded-md border border-solid border-border-subtle p-3 text-sm text-text-primary placeholder:text-text-tertiary focus:outline-none focus:border-border-strong disabled:cursor-not-allowed"
		/>
	</div>
);

// The full field set, shared between the locked (disabled) and unlocked states
// so the locked overlay shows the real form, exactly like Astra. The Pro Plugin
// Branding section is only rendered when Pro is active (`showPro`) — like Astra,
// which only shows a branding section for products that are present. When Pro is
// deactivated/uninstalled the overlay shows the free plugin fields only.
const WhiteLabelFields = ( { settings, updateField, disabled, showPro } ) => (
	<>
		<div className="flex flex-col gap-3">
			<SectionHeading>
				{ __( 'Agency Details', 'spectra-blocks' ) }
			</SectionHeading>
			<TextField
				id="wl-author-name"
				label={ __( 'Agency Author Name', 'spectra-blocks' ) }
				placeholder={ __( 'Your Agency', 'spectra-blocks' ) }
				value={ settings.author_name }
				disabled={ disabled }
				onChange={ updateField( 'author_name' ) }
			/>
			<TextField
				id="wl-author-url"
				type="url"
				label={ __( 'Agency Author URL', 'spectra-blocks' ) }
				placeholder={ __( 'https://youragency.com', 'spectra-blocks' ) }
				value={ settings.author_url }
				disabled={ disabled }
				onChange={ updateField( 'author_url' ) }
			/>
			<TextField
				id="wl-license-link"
				type="url"
				label={ __( 'Agency License Link', 'spectra-blocks' ) }
				placeholder={ __(
					'https://youragency.com/support',
					'spectra-blocks'
				) }
				help={ __(
					'This link is displayed in the license form when the purchase key is expired or not valid.',
					'spectra-blocks'
				) }
				value={ settings.license_link }
				disabled={ disabled }
				onChange={ updateField( 'license_link' ) }
			/>
		</div>

		<div className="flex flex-col gap-3">
			<SectionHeading>
				{ __( 'Plugin Branding', 'spectra-blocks' ) }
			</SectionHeading>
			<TextField
				id="wl-plugin-name"
				label={ __( 'Plugin Name', 'spectra-blocks' ) }
				placeholder={ __( 'Custom Plugin Name', 'spectra-blocks' ) }
				value={ settings.plugin_name }
				disabled={ disabled }
				onChange={ updateField( 'plugin_name' ) }
			/>
			<TextareaField
				id="wl-plugin-description"
				label={ __( 'Plugin Description', 'spectra-blocks' ) }
				placeholder={ __(
					'Custom plugin description',
					'spectra-blocks'
				) }
				value={ settings.plugin_description }
				disabled={ disabled }
				onChange={ updateField( 'plugin_description' ) }
			/>
			<TextField
				id="wl-plugin-screenshot"
				type="url"
				label={ __( 'Plugin Screenshot URL', 'spectra-blocks' ) }
				placeholder={ __(
					'https://yourdomain.com/screenshot.png',
					'spectra-blocks'
				) }
				help={ __(
					'The recommended image size is 1200px wide by 900px tall.',
					'spectra-blocks'
				) }
				value={ settings.plugin_screenshot }
				disabled={ disabled }
				onChange={ updateField( 'plugin_screenshot' ) }
			/>
			<TextField
				id="wl-plugin-icon"
				type="url"
				label={ __( 'Plugin Icon URL', 'spectra-blocks' ) }
				placeholder={ __(
					'https://yourdomain.com/icon.png',
					'spectra-blocks'
				) }
				help={ __(
					'The recommended icon should have a background so it adjusts properly on a white background too.',
					'spectra-blocks'
				) }
				value={ settings.plugin_icon }
				disabled={ disabled }
				onChange={ updateField( 'plugin_icon' ) }
			/>
		</div>

		{ showPro && (
			<div className="flex flex-col gap-3">
				<SectionHeading>
					{ __( 'Pro Plugin Branding', 'spectra-blocks' ) }
				</SectionHeading>
				<TextField
					id="wl-pro-plugin-name"
					label={ __( 'Plugin Name', 'spectra-blocks' ) }
					placeholder={ __( 'Custom Plugin Name', 'spectra-blocks' ) }
					value={ settings.pro_plugin_name }
					disabled={ disabled }
					onChange={ updateField( 'pro_plugin_name' ) }
				/>
				<TextareaField
					id="wl-pro-plugin-description"
					label={ __( 'Plugin Description', 'spectra-blocks' ) }
					placeholder={ __(
						'Custom plugin description',
						'spectra-blocks'
					) }
					value={ settings.pro_plugin_description }
					disabled={ disabled }
					onChange={ updateField( 'pro_plugin_description' ) }
				/>
			</div>
		) }
	</>
);

// Centered upgrade card shown over the blurred form when Pro is not active.
// CTA mirrors the free plugin's existing "unlock pro" flow (UpgradeToPro.js):
//   - Pro installed but inactive  → activate it in place (AJAX) and reload.
//   - Pro not installed           → open the pricing/upgrade page.
const LockedOverlay = () => {
	const dispatch = useDispatch();
	const [ activating, setActivating ] = useState( false );

	// The overlay only shows when Pro is not active, so `spectra_pro_installed`
	// (file-exists) cleanly distinguishes present-but-inactive from not-installed:
	//   installed → "Activate Now" (activate in place); not installed → "Upgrade Now".
	const installedInactive = !! spectra_blocks_react.spectra_pro_installed;
	const pricingUrl =
		spectra_blocks_admin_react?.spectra_website?.uagDashboard ||
		'https://wpspectra.com/pricing/';

	const handleClick = () => {
		if ( ! installedInactive ) {
			// Pro not present → send to the pricing/upgrade page.
			window.open( pricingUrl, '_blank', 'noopener,noreferrer' );
			return;
		}

		// Pro present but inactive → activate it in place via the core REST
		// plugins endpoint, then reload so the form unlocks.
		setActivating( true );
		apiFetch( {
			path: '/wp/v2/plugins/spectra-blocks-pro/spectra-blocks-pro',
			method: 'POST',
			data: { status: 'active' },
		} )
			.then( () => {
				dispatch( {
					type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
					payload: __(
						'Spectra Blocks Pro activated!',
						'spectra-blocks'
					),
				} );
				setTimeout( () => window.location.reload(), 600 );
			} )
			.catch( () => {
				setActivating( false );
				dispatch( {
					type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
					payload: {
						message: __(
							'Unable to activate Spectra Blocks Pro. Please activate it from the Plugins page.',
							'spectra-blocks'
						),
						messageType: 'error',
					},
				} );
			} );
	};

	let ctaLabel = installedInactive
		? __( 'Activate Now', 'spectra-blocks' )
		: __( 'Upgrade Now', 'spectra-blocks' );
	if ( activating ) {
		ctaLabel = __( 'Activating…', 'spectra-blocks' );
	}

	return (
		<div className="absolute inset-0 z-10 flex items-center justify-center">
			<div className="flex flex-col items-center gap-3 text-center bg-background-primary rounded-xl shadow-lg border border-solid border-border-subtle p-6 max-w-sm">
				<span className="inline-flex p-2.5 rounded-full bg-background-secondary text-brand-primary-600">
					<Lock size={ 22 } />
				</span>
				<h3 className="m-0 text-lg font-semibold text-text-primary">
					{ __(
						'White Label Available in Spectra Blocks Pro',
						'spectra-blocks'
					) }
				</h3>
				<p className="m-0 text-sm text-text-secondary">
					{ __(
						'Remove Spectra Blocks branding and customize the dashboard with your agency details.',
						'spectra-blocks'
					) }
				</p>
				<Button
					variant="primary"
					size="md"
					onClick={ handleClick }
					disabled={ activating }
				>
					{ ctaLabel }
				</Button>
			</div>
		</div>
	);
};

const WhiteLabel = () => {
	const dispatch = useDispatch();

	// Pro injects `white_label_nonce` into the localized data only when it is
	// active AND licensed. Its presence is the unlock signal for the real form.
	const isUnlocked = !! spectra_blocks_react.white_label_nonce;

	const [ settings, setSettings ] = useState( {
		...FIELD_DEFAULTS,
		...( spectra_blocks_react.white_label_settings || {} ),
	} );
	const [ isSaving, setIsSaving ] = useState( false );

	const nonce = spectra_blocks_react.white_label_nonce;

	const updateField = ( key ) => ( value ) => {
		setSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const notify = ( message, messageType = 'success' ) => {
		dispatch( {
			type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
			payload:
				'success' === messageType
					? message
					: { message, messageType },
		} );
	};

	const saveSettings = () => {
		setIsSaving( true );

		const data = {
			security: nonce,
			plugin_name: settings.plugin_name,
			plugin_description: settings.plugin_description,
			plugin_screenshot: settings.plugin_screenshot,
			plugin_icon: settings.plugin_icon,
			author_name: settings.author_name,
			author_url: settings.author_url,
			license_link: settings.license_link,
			pro_plugin_name: settings.pro_plugin_name,
			pro_plugin_description: settings.pro_plugin_description,
			hide_branding: settings.hide_branding ? 'true' : 'false',
		};

		getApiData( {
			url: spectra_blocks_react.ajax_url,
			action: 'spectra_blocks_save_white_label',
			data,
		} )
			.then( ( response ) => {
				if ( response?.success ) {
					notify( __( 'White label settings saved.', 'spectra-blocks' ) );
					// Reload so the applied branding (menu, header, plugin list)
					// refreshes immediately — same behaviour as Astra.
					setTimeout( () => window.location.reload(), 800 );
					return;
				}
				notify(
					response?.data?.message ||
						__( 'Unable to save settings.', 'spectra-blocks' ),
					'error'
				);
				setIsSaving( false );
			} )
			.catch( () => {
				notify(
					__( 'Unable to save settings.', 'spectra-blocks' ),
					'error'
				);
				setIsSaving( false );
			} );
	};

	const toggleBranding = ( value ) => {
		setSettings( ( prev ) => ( { ...prev, hide_branding: value } ) );

		getApiData( {
			url: spectra_blocks_react.ajax_url,
			action: 'spectra_blocks_toggle_white_label',
			data: {
				security: nonce,
				hide_branding: value ? 'true' : 'false',
			},
		} ).catch( () => {
			notify(
				__( 'Unable to update white label status.', 'spectra-blocks' ),
				'error'
			);
		} );
	};

	// Enable White Label header toggle (locked shows a lock icon, like Astra).
	const EnableRow = () => (
		<div className="flex items-start justify-between gap-4">
			<div className="space-y-1 lg:max-w-[520px]">
				<Label className="font-semibold flex items-center gap-1.5" size="md">
					{ ! isUnlocked && (
						<Lock size={ 16 } className="text-text-tertiary" />
					) }
					{ __( 'Enable White Label', 'spectra-blocks' ) }
				</Label>
				<Label
					className="m-0 font-normal"
					size="sm"
					tag="p"
					variant="help"
				>
					{ __(
						'Remove any links to the Spectra Blocks website and change the identity in the dashboard. Mostly used by agencies and developers building websites for clients.',
						'spectra-blocks'
					) }
				</Label>
			</div>
			<Switch
				value={ isUnlocked ? settings.hide_branding : false }
				onChange={ isUnlocked ? toggleBranding : undefined }
				disabled={ ! isUnlocked }
				size="md"
				className="spectra-blocks-remove-ring border-none"
			/>
		</div>
	);

	if ( ! isUnlocked ) {
		return (
			<div className="ast-whitelabel-wrap relative flex flex-col gap-8">
				<div className="flex flex-col gap-8 pointer-events-none select-none opacity-60 blur-[2px]">
					<EnableRow />
					<WhiteLabelFields
						settings={ FIELD_DEFAULTS }
						updateField={ () => () => {} }
						disabled
						showPro={ false }
					/>
				</div>
				<LockedOverlay />
			</div>
		);
	}

	return (
		<div className="ast-whitelabel-wrap flex flex-col gap-8">
			<EnableRow />
			<WhiteLabelFields
				settings={ settings }
				updateField={ updateField }
				disabled={ false }
				showPro
			/>
			<div className="flex justify-end">
				<Button
					variant="primary"
					size="md"
					onClick={ saveSettings }
					disabled={ isSaving }
				>
					{ isSaving
						? __( 'Saving…', 'spectra-blocks' )
						: __( 'Save Settings', 'spectra-blocks' ) }
				</Button>
			</div>
		</div>
	);
};

export default WhiteLabel;
