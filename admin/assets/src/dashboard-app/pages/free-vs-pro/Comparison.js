import QuickAccess from '@Common/components/QuickAccess';
import UpgradeToPro from '@Common/components/UpgrateToPro';
import { __ } from '@wordpress/i18n';

import { Container } from '@bsf/force-ui';
import Compare from './Compare';

const FreeVPro = () => {
	return (
		<div className="bg-background-secondary w-full">
			<Container className="md:p-8 sm:p-6 p-[0.7rem]" cols={12} containerType="grid" gap="2xl">
				<Container.Item className="flex flex-col gap-8" colSpan={{ lg: 8, md: 12, sm: 12 }}>
					<Compare />
					<UpgradeToPro
						className="hidden lg:flex !flex-row"
						items={[
							__( 'Advanced blocks', 'spectra-blocks' ),
							__( 'Advanced animations', 'spectra-blocks' ),
							__( 'Premium pre-built templates', 'spectra-blocks' ),
							__( 'Priority support and updates', 'spectra-blocks' ),
							__( 'Global Design Consistency', 'spectra-blocks' ),
						]}
						columnView
					/>
				</Container.Item>

				<Container.Item className="flex flex-col gap-8 lg:sticky lg:top-8 lg:self-start" colSpan={{ lg: 4, md: 12, sm: 12 }}>
					{ spectra_blocks_react.pro_plugin_status !== 'Activated' && (
						<UpgradeToPro />
					) }
					<QuickAccess />
				</Container.Item>
			</Container>
		</div>
	);
};

export default FreeVPro;
