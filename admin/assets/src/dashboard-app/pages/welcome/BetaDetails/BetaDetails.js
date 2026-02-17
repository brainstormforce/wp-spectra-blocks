import { __ } from '@wordpress/i18n';
import { Container, Title, Button } from '@bsf/force-ui';
import { TestTubeDiagonal } from 'lucide-react';

const BetaDetails = () => {
	const betaVersion = uag_admin_react?.plugin_ver || 'Beta';

	return (
		<Container className="bg-white p-6 shadow-sm rounded-lg border border-solid border-border-subtle relative">
			<div className="absolute top-6 right-6 text-sm text-text-tertiary">
				{ __( 'Beta Version: ', 'ultimate-addons-for-gutenberg' ) }
				<span className="font-semibold text-text-primary">{ betaVersion }</span>
			</div>
			
			<Container.Item className="flex flex-1 flex-col gap-2">
				<div className="text-brand-primary-600 flex space-x-1">
					<TestTubeDiagonal size={ 14 } />
					<div className="font-semibold text-xs">
						{ __( 'Beta Testing Program', 'ultimate-addons-for-gutenberg' ) }
					</div>
				</div>

				<div>
					<Title className="text-text-primary mb-1" tag="h1" title="Thank You for Trying Spectra Beta!" />
					<p className="text-sm text-text-secondary m-0">
						{ __(
							'You\'re helping us build a better Spectra by testing new features before they\'re released. Your feedback is invaluable to us!',
							'ultimate-addons-for-gutenberg'
						) }
					</p>
				</div>

				<div className="flex gap-3 mt-2">
					<a
						href="https://wpspectra.com/spectra-v3-beta-feedback/"
						target="_blank"
						rel="noreferrer"
						className="no-underline"
					>
						<Button variant="primary" className="uagb-remove-ring">
							{ __( 'Help Us Improve', 'ultimate-addons-for-gutenberg' ) }
						</Button>
					</a>
				</div>
			</Container.Item>
		</Container>
	);
};

export default BetaDetails;