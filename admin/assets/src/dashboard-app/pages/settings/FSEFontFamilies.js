import { __ } from '@wordpress/i18n';
import { Container, Label } from '@bsf/force-ui';

const FSEFontFamilies = () => {
	return (
		<Container
			align="center"
			className="mb-0.5 w-full flex items-start justify-between flex-col"
		>
			<Container.Item className="space-y-1 lg:max-w-[480px]">
				<Label
					className="font-semibold mb-1"
					htmlFor="default-width"
					size="md"
				>
					{ __( 'Font Families', 'spectra-blocks' ) }
				</Label>
				<Label
					className="m-0 font-normal"
					size="sm"
					tag="p"
					variant="help"
				>
					{ __(
						'This settings relies on the global typography settings provided by your WordPress theme. If you\'re using a Full Site Editing (FSE) theme, fonts are managed through the Site Editor under Styles → Typography.',
						'spectra-blocks'
					) }
				</Label>
			</Container.Item>
			<a
				href={
					spectra_blocks_admin_react.admin_base_url +
					'site-editor.php?p=%2Fstyles&section=%2Ftypography'
				}
				target="_blank"
				rel="noreferrer"
				className="text-spectra focus:text-spectra-hover active:text-spectra-hover hover:text-spectra-hover"
			>
				{ __(
					'Appearance → Editor → Styles → Typography → Fonts',
					'spectra-blocks'
				) }
			</a>
		</Container>
	);
};

export default FSEFontFamilies;
