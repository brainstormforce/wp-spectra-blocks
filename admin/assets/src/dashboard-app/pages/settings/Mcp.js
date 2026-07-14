import { __ } from '@wordpress/i18n';
import { useSelector, useDispatch } from 'react-redux';
import getApiData from '@Controls/getApiData';
import { Container, Label, Switch, Alert } from '@bsf/force-ui';
import McpConnectionConfig from '@DashboardApp/pages/settings/McpConnectionConfig';

const Mcp = () => {
	const dispatch = useDispatch();

	const enableAbilities = useSelector( ( state ) => state.enableAbilities );
	const enableEditAbilities = useSelector( ( state ) => state.enableEditAbilities );
	const enableMcpServer = useSelector( ( state ) => state.enableMcpServer );

	const abilitiesOn = 'enabled' === enableAbilities;
	const editAbilitiesOn = 'enabled' === enableEditAbilities;
	const mcpServerOn = 'enabled' === enableMcpServer;

	const isMcpAdapterActive = !! spectra_blocks_react?.is_mcp_adapter_active;

	const persist = ( action, nonce, value ) => {
		return getApiData( {
			url: spectra_blocks_react.ajax_url,
			action,
			data: {
				security: nonce,
				value,
			},
		} ).then( () => {
			dispatch( { type: 'UPDATE_SETTINGS_SAVED_NOTIFICATION', payload: 'Successfully saved!' } );
		} );
	};

	const updateEnableAbilities = () => {
		const value = abilitiesOn ? 'disabled' : 'enabled';
		dispatch( { type: 'UPDATE_ENABLE_ABILITIES', payload: value } );
		persist( 'spectra_blocks_enable_abilities', spectra_blocks_react.enable_abilities_nonce, value );
	};

	const updateEnableEditAbilities = () => {
		const value = editAbilitiesOn ? 'disabled' : 'enabled';
		dispatch( { type: 'UPDATE_ENABLE_EDIT_ABILITIES', payload: value } );
		persist( 'spectra_blocks_enable_edit_abilities', spectra_blocks_react.enable_edit_abilities_nonce, value );
	};

	const updateEnableMcpServer = () => {
		const value = mcpServerOn ? 'disabled' : 'enabled';
		dispatch( { type: 'UPDATE_ENABLE_MCP_SERVER', payload: value } );
		persist( 'spectra_blocks_enable_mcp_server', spectra_blocks_react.enable_mcp_server_nonce, value );
	};

	const renderRow = ( { title, description, learnMoreHref, value, onChange, inactive } ) => (
		<div className={ `flex gap-4 ${ inactive ? 'opacity-40 pointer-events-none' : '' }` }>
			<div className="w-full flex flex-col gap-1">
				<Label className="font-semibold text-base m-0" size="md">
					{ title }
				</Label>
				<Label className="m-0" size="sm" tag="p" variant="help">
					{ description }
					{ learnMoreHref && (
						<>
							{ ' ' }
							<a
								href={ learnMoreHref }
								target="_blank"
								rel="noopener noreferrer"
								className="cursor-pointer text-link-primary hover:text-link-primary-hover"
							>
								{ __( 'Learn more', 'spectra-blocks' ) }
							</a>
						</>
					) }
				</Label>
			</div>
			<Switch value={ !! value } onChange={ onChange } size="md" className="uagb-remove-ring border-none" />
		</div>
	);

	return (
		<Container className="flex flex-col gap-6 w-full" direction="column">
			{ renderRow( {
				title: __( 'Enable Abilities', 'spectra-blocks' ),
				description: __(
					'Register Spectra Blocks abilities with the WordPress Abilities API. When enabled, AI clients can list, read, and interact with your Spectra blocks. When disabled, no abilities are registered and AI clients cannot perform any actions.',
					'spectra-blocks'
				),
				value: abilitiesOn,
				onChange: updateEnableAbilities,
				inactive: false,
			} ) }

			<div className="h-px bg-border-subtle" />

			{ renderRow( {
				title: __( 'Enable Edit Abilities', 'spectra-blocks' ),
				description: __(
					'When enabled, AI clients can create and configure Spectra Blocks blocks (containers, content, and more). When disabled, these abilities are unregistered and AI clients can only read data.',
					'spectra-blocks'
				),
				value: editAbilitiesOn,
				onChange: updateEnableEditAbilities,
				inactive: ! abilitiesOn,
			} ) }

			<div className="h-px bg-border-subtle" />

			{ renderRow( {
				title: __( 'Enable MCP Server', 'spectra-blocks' ),
				description: __(
					'Creates a dedicated Spectra Blocks MCP endpoint that AI clients like Claude can connect to. When disabled, the endpoint is removed and external AI clients cannot discover or call any Spectra Blocks abilities.',
					'spectra-blocks'
				),
				learnMoreHref: 'https://make.wordpress.org/ai/2025/07/17/mcp-adapter/',
				value: mcpServerOn,
				onChange: updateEnableMcpServer,
				inactive: ! abilitiesOn,
			} ) }

			{ abilitiesOn && mcpServerOn && ! isMcpAdapterActive && (
				<Alert
					variant="warning"
					design="stack"
					title={ __( 'MCP Adapter plugin not detected', 'spectra-blocks' ) }
					content={ __(
						'To connect AI clients to your site, the WP MCP Adapter plugin is required. It will be included in WordPress 7.0 by default. Until then, download it from GitHub, upload via Plugins > Add New > Upload, and activate.',
						'spectra-blocks'
					) }
					action={ {
						label: __( 'Download from GitHub', 'spectra-blocks' ),
						onClick: () => window.open( 'https://github.com/WordPress/mcp-adapter/releases', '_blank' ),
						type: 'link',
					} }
				/>
			) }

			{ abilitiesOn && mcpServerOn && <McpConnectionConfig /> }
		</Container>
	);
};

export default Mcp;
