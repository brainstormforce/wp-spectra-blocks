import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch } from 'react-redux';
import ReactHtmlParser from 'react-html-parser';
import getApiData from '@Controls/getApiData';
import { Key, Unlock } from 'lucide-react';
import { Input, Label, Text } from '@bsf/force-ui';

const LicenseKey = () => {
	const dispatch = useDispatch();
		const [ regenerateAssetsState, setRegenerateAssetsState ] = useState( false );
		const [ licenseKey, setlicenseKey ] = useState( '' );
	
		const licenseStatus = spectra_blocks_react.license_status;
	const licenseTriggerName = licenseStatus ? 'bsf_deactivate_license' : 'bsf_activate_license';
		const licenseTitle = licenseStatus
			? __( 'Deactivate', 'spectra-blocks' )
			: __( 'Activate', 'spectra-blocks' );
		const licensePlaceholder = licenseStatus
			? __( 'Your license is active.', 'spectra-blocks' )
			: __( 'Paste your license key here', 'spectra-blocks' );
		const licenseMessage = licenseStatus
			? __( 'License successfully validated!', 'spectra-blocks' )
			: __( 'Please enter your valid license key below to activate Spectra Pro!', 'spectra-blocks' );
	
		const activateHandler = () => {
			if ( ! licenseKey ) {
				dispatch( {
					type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
					payload: {
						message: __( 'Please enter a valid license key!', 'spectra-blocks' ),
						messageType: 'error',
					},
				} );
				return;
			}
			setRegenerateAssetsState( 'loading' );
	
			const data = {
				action: 'spectra_blocks_license_activation',
				security: spectra_blocks_react.license_activation_nonce,
				key: licenseKey,
			};
	
			const getApiFetchData = getApiData( {
				url: spectra_blocks_react.ajax_url,
				action: 'spectra_blocks_license_activation',
				data,
			} );
	
			getApiFetchData.then( ( responseData ) => {
				if ( responseData.success ) {
					dispatch( {
						type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
						payload: __( 'License successfully validated!', 'spectra-blocks' ),
					} );
					location.reload();
				} else {
					dispatch( {
						type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
						payload: {
							message: responseData?.data?.message,
							messageType: 'error',
						},
					} );
					setlicenseKey( '' );
				}
				setRegenerateAssetsState( false );
			} );
		};
	
		const deactivateHandler = () => {
			setRegenerateAssetsState( 'loading' );
			const data = {
				security: spectra_blocks_react.license_deactivation_nonce,
			};
	
			const getApiFetchData = getApiData( {
				url: spectra_blocks_react.ajax_url,
				action: 'spectra_blocks_license_deactivation',
				data,
			} );
	
			getApiFetchData.then( ( responseData ) => {
				if ( responseData.success ) {
					dispatch( {
						type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
						payload: __( 'License successfully deactivated!', 'spectra-blocks' ),
					} );
					location.reload();
				} else {
					dispatch( {
						type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION',
						payload: {
							message: responseData?.data?.message,
							messageType: 'error',
						},
					} );
				}
				setRegenerateAssetsState( false );
			} );
		};

	return (
		<div className="ast-licensing-wrap w-full max-w-md">
			<form
				method="post"
				id="bsf-astra-license-form"
				className="flex flex-col gap-2 form-wrap bsf-license-register-astra-addon form-submited-astra-addon"
			>
				<span className="inline-flex w-max p-2 rounded-[4px] border border-solid border-border-subtle">
					<Unlock size={24} strokeWidth={2} />
				</span>

				<div className="flex flex-col gap-1 pb-2">
					<h3 className="m-0 flex items-center text-2xl font-semibold text-text-primary">
						{__( 'Your License', 'spectra-blocks' )}
					</h3>

					{!licenseStatus && (
						<div className="text-sm text-text-secondary">
							<Text className="mb-1" color="secondary">
								{ ReactHtmlParser(
									sprintf(
										// translators: %1$s: first anchor tag start, %2$s: first anchor tag end, %3$s second anchor tag start, %4$s second anchor tag end.
										__(
											"Activate %1$sSpectra Pro%2$s to get professional support and automatic updates from your WordPress dashboard. If you don't have a license, you can %3$sget it here »%4$s",
											'spectra-blocks'
										),
										'<a href="https://wpspectra.com/pricing" class="text-spectra focus:text-spectra-hover active:text-spectra-hover hover:text-spectra-hover" target="_blank" rel="noreferrer">',
										'</a>',
										'<a class="text-spectra focus:text-spectra-hover active:text-spectra-hover hover:text-spectra-hover" target="_blank" href="https://store.brainstormforce.com/" rel="noreferrer">',
										'</a>'
									)
								) }
							</Text>

							<Label htmlFor="bsf_license_manager[license_key]" className="text-text-secondary font-normal">
								{licenseMessage}{' '}
								{licenseStatus ? (
									<span className="font-medium">
										{' '}
										{'Spectra Pro'}
									</span>
								) : (
									''
								)}
							</Label>
						</div>
					)}

					{licenseStatus && (
						<Text size={14}>
							{ ReactHtmlParser(
								sprintf(
																									/* translators: %s: product name */
								__( 'You are using %s.', 'spectra-blocks' ),
									`<b>Spectra Pro</b>`
								)
							)}
						</Text>
					)}
				</div>

				<div className="flex flex-col sm:flex-row gap-2 text-text-tertiary">
					<div className="relative grow">
						
						<input type="hidden" id="bsf_graupi_nonce" name="bsf_graupi_nonce" value={spectra_blocks_react.license_activation_nonce} />
						<input
							type="hidden"
							name="_wp_http_referer"
							value={window.location.href.replace( window.location.origin, '' )}
						/>
						<input type="hidden" name={licenseTriggerName} value="" />

						{
							// Added this because to avoid value={} attr to license input field as this won't make input typeable.
							licenseStatus && (
								<input
									type="hidden"
									id="bsf_license_manager[license_key]"
									name="bsf_license_manager[license_key]"
									value={licensePlaceholder}
								/>
							)
						}
						<Input
							type="text"
							id={licenseStatus ? 'spectra_pro_license_key' : 'bsf_license_manager[license_key]'}
							name={licenseStatus ? 'spectra_pro_license_key' : 'bsf_license_manager[license_key]'}
							size="md"
							defaultValue={licenseStatus ? licensePlaceholder : licenseKey}
							placeholder={licensePlaceholder}
							readOnly={licenseStatus ? true : false}
							disabled={licenseStatus ? true : false}
							onChange={( value ) => setlicenseKey( value )}
							prefix={<Key size={24} strokeWidth={3} className='text-text-tertiary' />}
						/>
					</div>
					{!licenseStatus && (
						<button
							type="button"
							className="inline-flex cursor-pointer items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-spectra transition focus:bg-spectra-hover hover:bg-spectra-hover focus:outline-none"
							onClick={activateHandler}
						>
							{licenseTitle}
							{'loading' === regenerateAssetsState && (
								<svg
									className="animate-spin -mr-1 ml-3 h-5 w-5 text-white"
									xmlns="http://www.w3.org/2000/svg"
									fill="none"
									viewBox="0 0 24 24"
								>
									<circle
										className="opacity-25"
										cx="12"
										cy="12"
										r="10"
										stroke="currentColor"
										strokeWidth="4"
									></circle>
									<path
										className="opacity-75"
										fill="currentColor"
										d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
									></path>
								</svg>
							)}
						</button>
					)}
					{licenseStatus && (
						<button
							type="button"
							className="inline-flex cursor-pointer items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-spectra transition focus:bg-spectra-hover hover:bg-spectra-hover focus:outline-none"
							onClick={deactivateHandler}
						>
							{licenseTitle}
							{'loading' === regenerateAssetsState && (
								<svg
									className="animate-spin -mr-1 ml-3 h-5 w-5 text-white"
									xmlns="http://www.w3.org/2000/svg"
									fill="none"
									viewBox="0 0 24 24"
								>
									<circle
										className="opacity-25"
										cx="12"
										cy="12"
										r="10"
										stroke="currentColor"
										strokeWidth="4"
									></circle>
									<path
										className="opacity-75"
										fill="currentColor"
										d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
									></path>
								</svg>
							)}
						</button>
					)}
				</div>
					<Text
						as="a"
						href={`https://store.brainstormforce.com/${licenseStatus ? 'upgrades' : 'support'}`}
						target="_blank"
						className="w-max text-sm font-semibold text-button-primary no-underline"
					>
						{licenseStatus ? __( 'Need more license?', 'spectra-blocks' ) : __( 'Need Help?', 'spectra-blocks' )}
					</Text>
			</form>
		</div>
	);
};

export default LicenseKey;
