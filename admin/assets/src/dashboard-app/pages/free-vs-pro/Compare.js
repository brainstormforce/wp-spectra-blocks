import React, { useEffect, useState, Fragment } from 'react';
import { Button, Container } from '@bsf/force-ui';
import { Check, X } from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';
import { sections } from './featuresData';
import { useDispatch } from 'react-redux';
import getApiData from '@Controls/getApiData';
import './Compare.scss';

const Compare = () => {
	const dispatch = useDispatch();

	const [ buttonText, setButtonText ] = useState();

	useEffect( () => {
		const currentButtonText = sprintf(
			/* translators: abbreviation for units */ __( '%s', 'spectra-blocks' ),
			getSpectraProTitle()
		);

		setButtonText( currentButtonText );
	}, [] );

	// Function to open the pricing page in a new tab when the "Upgrade Now" button is clicked.
	const onUpgradeNowClick = () => {
		window.open(
			spectra_blocks_admin_react.spectra_website?.freeVsPro,
			'_blank'
		);
	};

	const getSpectraProTitle = () => {
		return 'Installed' === spectra_blocks_react.pro_plugin_status
			? __( 'Activate Now', 'spectra-blocks' )
			: __( 'Get Spectra Blocks Pro Now', 'spectra-blocks' );
	};

	const activatePro = () => {
		const isThisNull = spectra_blocks_react.pro_plugin_status;

		if ( 'Install' !== isThisNull ) {
			// Create an object with the required data to activate the 'spectra' Pro feature
			const data = {
				security: spectra_blocks_react.pro_activate_nonce,
				value: 'spectra',
			};
			setButtonText( spectra_blocks_react.plugin_activating_text );
			// Call the getApiData function with the specified parameters
			const getApiFetchData = getApiData( {
				url: spectra_blocks_react.ajax_url,
				action: 'spectra_blocks_pro_activate',
				data,
			} );

			// Wait for the API call to complete, update the state to show a notification, and reload the page
			getApiFetchData.then( () => {
				dispatch( { type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION', payload: 'Spectra Pro Activated!' } );
				setTimeout( () => {
					window.location.reload();
				}, 500 );
			} );
		} else {
			onUpgradeNowClick();
		}
	};

	const renderIcon = ( isAvailable ) => ( isAvailable ? <Check color="#16A34A" size={20} /> : <X color="#DC2626" size={20} /> );

	const getLabel = ( item, type ) => {
		if ( item.id === 10 && item.content === __( 'Navigation Menu', 'spectra-blocks' ) ) {
			if ( type === 'pro' ) {
				return item.iconPro
					? __( 'Advanced', 'spectra-blocks' )
					: __( 'Basic', 'spectra-blocks' );
			}
			return item.iconPro
				? __( 'Basic', 'spectra-blocks' )
				: __( 'Advanced', 'spectra-blocks' );
		}
		return type === 'pro' ? renderIcon( item.iconFree ) : renderIcon( item.iconPro );
	};

	const renderItems = ( items ) =>
		items.map( ( item ) => (
			<div
				key={ item.id }
				className="flex fle-row p-3 items-center justify-between gap-4 border-0 border-b-0.5 border-solid border-b-border-subtle"
			>
				<p className="m-0 text-sm text-text-secondary font-normal grow">{ item.content }</p>
				<p className="m-0 min-w-[18%] w-min text-center text-xxs sm:text-sm">
					{ getLabel( item, 'pro' ) }
				</p>
				<p className="m-0 min-w-[18%] w-min text-center text-xxs sm:text-sm">
					{ getLabel( item, 'free' ) }
				</p>
			</div>
		) );

	return (
		<Container
			className="bg-white p-6 shadow-sm rounded-xl border border-solid border-border-subtle items-center text-text-primary"
			cols={12}
			containerType="grid"
			gap="2xl"
		>
			<Container.Item className="flex flex-col gap-4" colSpan={{ lg: 12, md: 12, sm: 12 }}>
			<div className="rounded-lg bg-white w-full border-border-subtle">
				<div className="flex flex-col sm:flex-row custom:flex-col sm:items-center items-start custom:items-start justify-between sm:gap-0 gap-5 pb-6">
						<div className="flex flex-col gap-1">
							<div className="m-0 text-xl font-semibold custom:pt-0 pt-0 text-text-primary">
						{ __( 'Spectra Blocks Free VS Pro', 'spectra-blocks' ) }
					</div>
							<p className="m-0 text-sm font-normal text-text-secondary">
						{ __(
							'Compare the features to find the best option for your website.',
							'spectra-blocks'
						) }
					</p>
				</div>
				<div className="flex items-center sm:p-1 p-0">
					{ spectra_blocks_react.pro_plugin_status !== 'Activated' && (
						<Button
							iconPosition="right"
							variant="primary" className="spectra-blocks-remove-ring"
							onClick={ () => activatePro() }
						>
							{ buttonText }
						</Button>
					) }
				</div>
			</div>
			<div className="flex flex-col pt-4">
				{ sections.map( ( section ) => (
					<Fragment key={ section.title }>
						<div className="w-full flex fle-row gap-4 p-3 items-center justify-between bg-[#F9FAFB] text-sm text-text-primary font-medium">
							<p className="m-0 grow">{ section.title }</p>
							<p className="m-0 min-w-[18%] w-min text-center">{ __( 'Free', 'spectra-blocks' ) }</p>
							<p className="m-0 min-w-[18%] w-min text-center">{ __( 'Pro', 'spectra-blocks' ) }</p>
						</div>

						{ renderItems( section.items ) }
					</Fragment>
				) ) }
				<div className="pt-6 m-auto">
					<Button
						className="m-0 inline-flex items-center justify-center gap-1 text-base font-semibold text-link-primary no-underline hover:underline spectra-blocks-remove-ring"
						variant="link"
						onClick={() => activatePro()}
					>
						<span className="inline-flex items-center gap-1">
						{buttonText}
						</span>
					</Button>
				</div>
			</div>
		</div>
			</Container.Item>
		</Container>
	);
};

export default Compare;
