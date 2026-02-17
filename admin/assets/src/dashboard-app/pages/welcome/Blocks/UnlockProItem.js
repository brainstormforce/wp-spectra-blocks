
import { Container, Badge, Button, Text, Tooltip } from '@bsf/force-ui';
import { Lock } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import RenderBlockPreview from '@Common/components/RenderBlockPreview';

const UnlockProItem = ( { blockInfo } ) => {
	const { icon, title, link } = blockInfo;

	// Function to get description based on title
	const getDescription = ( itemTitle ) => {
		switch ( itemTitle ) {
			case 'Dynamic Content':
				return __( 'Tailored content for individual users based on their preferences and behavior anywhere on your website.', 'ultimate-addons-for-gutenberg' );
			case 'Loop Builder':
				return __( 'Loop Builder allows displaying post types based on different query parameters and visual configurations.', 'ultimate-addons-for-gutenberg' );
			case 'Login':
				return __( 'This block lets you add Login functionality.', 'ultimate-addons-for-gutenberg' );
			case 'Register':
				return __( 'This block lets you add Registration functionality.', 'ultimate-addons-for-gutenberg' );
			
			default:
				return __( 'This option only available in Spectra Pro.', 'ultimate-addons-for-gutenberg' );
		}
	};

	return (
		'Activated' !== uag_react.pro_plugin_status ? (
			<>
				<Tooltip
					className="rounded-md p-4"
					title={
						<div className="flex items-center gap-2 text-base">
							{title}
							<Badge
								className="font-normal px-0.5 text-text-secondary bg-background-primary uppercase"
								label={__( 'Pro', 'ultimate-addons-for-gutenberg' )}
								size="xs"
								type="rounded"
								variant="inverse"
							/>
						</div>
					}
					content={
						<div className="mt-3 flex items-center gap-1 text-xs">
							<span>
								{getDescription( title )}{' '}
								<Button
									size="xs"
									tag="button"
									type="button"
									variant="link"
									className="outline-1 border-none cursor-pointer transition-colors duration-300 ease-in-out font-semibold focus:ring-toggle-on disabled:text-text-disabled rounded-md [&>svg]:size-5 outline-none bg-transparent p-0 border-0 inline-flex items-center gap-0 focus:ring-0 focus:ring-offset-0 focus:outline-none hover:no-underline [&>svg]:w-4 [&>svg]:h-4 text-xs text-[#EFD7F9] hover:text-[#EFD7F9]-hover focus:shadow-none upgrade-now-unlock-pro-item"
									onClick={() => window.open( uag_admin_react.spectra_website?.upsellModalAdmin, '_blank' )}
								>
									{__( 'Upgrade Now', 'ultimate-addons-for-gutenberg' )}
								</Button>
							</span>
						</div>
					}
					open
					placement="top"
					arrow
					interactive
				>
					<div
						className="no-underline flex flex-col gap-1 justify-between px-1"
					>
						<Content icon={icon} title={title} link={link}/>
					</div>
				</Tooltip>
			</>
		) : <Content icon={icon} title={title} link={link}/>
	);
};

const Content = ( { icon, title, link } ) => {
	return (
		<Container align="center" containerType="flex" direction="column" justify="between" gap="">
			<div className="flex items-center justify-between w-full mb-1 p-1">
				<div style={{ fontSize: '22px' }}>
					<RenderBlockPreview blockName={icon} />
				</div>
				<div className="flex items-center gap-x-2">
					{'Activated' !== uag_react.pro_plugin_status ? (
						<>
							<span className="font-medium flex items-center justify-center border border-solid box-border max-w-full transition-colors duration-150 ease-in-out py-0.5 px-1 text-xs h-5 rounded-full gap-0.5 bg-badge-background-yellow text-badge-color-yellow border-badge-border-yellow hover:bg-badge-hover-yellow">
								<Lock size={12} className="m-1 no-underline text-badge-color-yellow" />
								<Text as="span" size={12} color="yellow" className="truncate">
									{__( 'Pro', 'ultimate-addons-for-gutenberg' )}
								</Text>
							</span>
						</>
					) : (
						<Badge label={ __( 'Active', 'ultimate-addons-for-gutenberg' ) } size="xs" type="pill" variant="green" />
					)}
				</div>
			</div>

			<div className="flex flex-col w-full p-1">
				<Text size={14} weight={500}>
					{title}
				</Text>
				<div className="flex items-center justify-between w-full">
					<a
						href={`https://wpspectra.com/blocks-and-extensions/${link}`}
						target="_blank"
						rel="noreferrer"
						className="mt-1 text-text-tertiary no-underline"
					>
						<Text as="span" size={12} color="tertiary" className="truncate">
							{__( 'Documentation', 'ultimate-addons-for-gutenberg' )}
						</Text>
					</a>
				</div>
			</div>
		</Container>
	);
};

export default UnlockProItem;