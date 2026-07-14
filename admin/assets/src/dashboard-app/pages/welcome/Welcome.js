import { Container } from '@bsf/force-ui';

import UpgradeToPro from '@Common/components/UpgrateToPro';
import ExtendYourWebsite from '@Common/components/ExtendYourWebsite';
import BuildWebsite from '@Common/components/BuildWebsite';
import QuickAccess from '@Common/components/QuickAccess';

import HeroSection from './HeroSection/HeroSection';
import Blocks from './Blocks/Blocks';
import ProBlocks from './Blocks/ProBlocks';
import BlocksExtensions from './Blocks/BlocksExtensions';
import LicenseKey from '../settings/LicenseKey';
import ProFeatures from './ProFeatures/ProFeatures';

const Welcome = () => {
	return (
		<main className="bg-background-secondary min-h-[calc(100vh_-_8rem)]">
			<h1 className="sr-only"> Spectra Blocks </h1>
			<Container className="md:p-8 sm:p-6 p-[0.7rem]" cols={12} containerType="grid" gap="2xl">
				<Container.Item className="flex flex-col gap-8" colSpan={{ lg: 8, md: 12, sm: 12 }}>
					<HeroSection />

					{ 'Activated' === spectra_blocks_react.pro_plugin_status && <ProFeatures /> }

					<Blocks />

					<BlocksExtensions />

					<ProBlocks />

					<ExtendYourWebsite />
				</Container.Item>

				<Container.Item className="flex flex-col gap-8 lg:sticky lg:top-8 lg:self-start" colSpan={{ lg: 4, md: 12, sm: 12 }}>
					{'Activated' !== spectra_blocks_react.pro_plugin_status && <UpgradeToPro /> }

					<QuickAccess />

					<BuildWebsite />

					{ 'Activated' === spectra_blocks_react.pro_plugin_status && (
						<>
							<section className="flex gap-2 flex-col p-4 bg-white shadow-sm rounded-lg border border-solid border-border-subtle p-3 [&>div>.spectra-licensing-wrap]:max-w-full">
								<LicenseKey />
							</section>
						</>
					)}

				</Container.Item>
			</Container>
		</main>
	);
};

export default Welcome;
