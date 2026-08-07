import { Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

const NoBlocksFound = ( { handleClearFilters } ) => (
	<div className="bg-background-primary flex justify-center items-center w-full h-[160px] rounded-lg">
		<div className="flex flex-col gap-2 items-center">
			<div className="font-medium text-text-primary text-lg">{ __( 'No blocks found', 'spectra-blocks' ) }</div>

			<div className="text-sm text-text-secondary">{ __( 'Try changing the filters or search term', 'spectra-blocks' ) }</div>

			<Button
				className="mt-4"
				size="sm"
				tag="button"
				type="button"
				variant="primary"
				onClick={ () => handleClearFilters() }
			>
				{ __( 'Show all blocks', 'spectra-blocks' ) }
			</Button>
		</div>
	</div>
);

export default NoBlocksFound;
