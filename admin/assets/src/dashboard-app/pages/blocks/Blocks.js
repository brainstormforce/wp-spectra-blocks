import React from 'react'
import { Container } from '@bsf/force-ui'
import UnlockProFeatures from '@Common/components/UnlockProFeatures';

const Blocks = () => {
	return (
		<div className="bg-background-secondary">
			<Container className="md:p-8 sm:p-6 p-[0.7rem]" cols={ 12 } containerType="grid" gap="2xl">
				<Container.Item className="flex flex-col gap-8" colSpan={ { lg: 4, md: 12, sm: 12 } }>
					{ uag_react.pro_plugin_status !== 'Activated' && (
						<UnlockProFeatures smallCol={ true } />
					) }
				</Container.Item>
			</Container>
		</div>
	);
};

export default Blocks;
