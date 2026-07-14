import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Label, Tabs, Tooltip, toast } from '@bsf/force-ui';
import { Copy, Check, ExternalLink, Sparkles } from 'lucide-react';

const McpConnectionConfig = () => {
	const [ activeTab, setActiveTab ] = useState( 'spectra-only' );
	const [ copied, setCopied ] = useState( false );
	const [ promptCopied, setPromptCopied ] = useState( false );

	const restUrl = spectra_blocks_react?.rest_url || '';
	const username = spectra_blocks_react?.current_username || '';
	const applicationPasswordsUrl = spectra_blocks_react?.application_passwords_url || '';

	let siteHostname = '';
	try {
		siteHostname = new URL( restUrl ).hostname.replace( /[^a-zA-Z0-9-]/g, '-' );
	} catch ( e ) {
		siteHostname = 'site';
	}

	const isSpectraOnly = 'spectra-only' === activeTab;
	const serverName = isSpectraOnly ? `${ siteHostname }-spectra` : `${ siteHostname }-global`;
	const serverUrl = isSpectraOnly
		? `${ restUrl }spectra-blocks/v1/mcp`
		: `${ restUrl }mcp/mcp-adapter-default-server`;

	const mcpServerEntry = {
		command: 'npx',
		args: [ '-y', '@automattic/mcp-wordpress-remote@latest' ],
		env: {
			WP_API_URL: serverUrl,
			WP_API_USERNAME: username,
			WP_API_PASSWORD: 'your-application-password',
		},
	};

	const mcpConfig = JSON.stringify(
		{ mcpServers: { [ serverName ]: mcpServerEntry } },
		null,
		2
	);

	const serverEntryJson = JSON.stringify( { [ serverName ]: mcpServerEntry }, null, 2 );

	const aiPrompt = `Add this MCP server to my AI client so I can connect to my WordPress site:

${ serverEntryJson }

Steps:

1. Ask which AI client I use if unsure (Claude Code, Claude Desktop, Cursor, Windsurf, VS Code, ChatGPT, etc.) and what OS I'm on (macOS, Windows, Linux) — both are needed to locate the correct config file.

2. Ask for my Application Password upfront (before proceeding to any other steps). The placeholder in the JSON above says "your-application-password" — replace it with what I provide. If I don't have one, direct me to: ${ applicationPasswordsUrl }

3. If using ChatGPT: go to Settings → Connectors → Add Connector and paste this URL as the Connector URL:
   ${ serverUrl }
   Use HTTP Basic Auth with my WordPress username and an Application Password.
   If I don't have an Application Password, direct me to: ${ applicationPasswordsUrl }
   See also: https://developers.openai.com/apps-sdk/deploy/connect-chatgpt
   Skip steps 3–7.

4. Locate the correct MCP config file for my client and OS. Verify the path exists on my system — do not guess. If it doesn't exist, confirm the path with me before creating.

5. Merge into existing config if present — do NOT overwrite other MCP server entries. For VS Code, use "servers" key instead of "mcpServers".

6. Detect my Node.js setup by running: which npx && node --version && echo $PATH
   - If you can run terminal commands directly (e.g. Claude Code, Cursor, Windsurf, VS Code agent), run this yourself.
   - If you are sandboxed and cannot run terminal commands (e.g. Claude Desktop, ChatGPT), ask me to run it and paste the output back.
   - Requires Node.js v20 or v22. If below v20 or v23+, warn me and stop.
   - Set "command" to the full npx path from "which npx" — never use a path from your own environment.
   - Add "PATH" to "env" using the actual $PATH output — include the node bin directory, Homebrew paths (/opt/homebrew/bin, /usr/local/bin), and system paths. MCP servers don't inherit the user's shell PATH, so include everything needed for node, npx, and git.

7. If you're sandboxed (e.g., Claude Desktop) and can't write to the real filesystem, don't write to a sandbox path. Instead, output the final JSON in a code block with the file path so I can save it manually.

8. Verify the final JSON is valid and confirm what was added.`;

	const copyToClipboard = ( text ) => {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}
		const textarea = document.createElement( 'textarea' );
		textarea.value = text;
		textarea.style.position = 'fixed';
		textarea.style.opacity = '0';
		document.body.appendChild( textarea );
		textarea.select();
		document.execCommand( 'copy' );
		document.body.removeChild( textarea );
		return Promise.resolve();
	};

	const handleCopy = () => {
		copyToClipboard( mcpConfig ).then( () => {
			setCopied( true );
			toast.success( __( 'Copied to clipboard', 'spectra-blocks' ) );
			setTimeout( () => setCopied( false ), 2000 );
		} );
	};

	const handleCopyPrompt = () => {
		copyToClipboard( aiPrompt ).then( () => {
			setPromptCopied( true );
			toast.success( __( 'Prompt copied! Paste it into your AI client.', 'spectra-blocks' ) );
			setTimeout( () => setPromptCopied( false ), 2000 );
		} );
	};

	return (
		<div className="flex flex-col gap-4">
			<Label className="font-semibold text-base m-0" size="md">
				{ __( 'Connect Your AI Client', 'spectra-blocks' ) }
			</Label>
			<ol className="list-decimal list-outside flex flex-col gap-1.5 text-text-secondary text-sm my-0 ml-5">
				<li>
					{ __( 'Create an Application Password', 'spectra-blocks' ) }
					{ ' — ' }
					<a
						href={ applicationPasswordsUrl }
						target="_blank"
						rel="noopener noreferrer"
						className="cursor-pointer text-link-primary hover:text-link-primary-hover inline-flex items-center gap-1"
					>
						{ __( 'Open Application Passwords', 'spectra-blocks' ) }
						<ExternalLink size={ 14 } />
					</a>
				</li>
				<li>
					{ __( 'Add to your AI client:', 'spectra-blocks' ) }{ ' ' }
					<a
						href="https://developer.wordpress.org/news/2026/02/from-abilities-to-ai-agents-introducing-the-wordpress-mcp-adapter/"
						target="_blank"
						rel="noopener noreferrer"
						className="cursor-pointer text-link-primary hover:text-link-primary-hover inline-flex items-center gap-1"
					>
						{ __( 'Learn More', 'spectra-blocks' ) }
						<ExternalLink size={ 14 } />
					</a>
					<ul className="list-disc list-outside flex flex-col mt-2.5 ml-4">
						<li>
							<span className="font-medium">{ __( 'Claude Code', 'spectra-blocks' ) }</span>
							{ ': .mcp.json' }
						</li>
						<li>
							<span className="font-medium">{ __( 'Claude Desktop', 'spectra-blocks' ) }</span>
							{ ': claude_desktop_config.json' }
						</li>
						<li>
							<span className="font-medium">{ __( 'Cursor', 'spectra-blocks' ) }</span>
							{ ': .cursor/mcp.json' }
						</li>
						<li>
							<span className="font-medium">{ __( 'VS Code', 'spectra-blocks' ) }</span>
							{ ': .vscode/mcp.json' }
						</li>
						<li>
							<span className="font-medium">{ __( 'ChatGPT', 'spectra-blocks' ) }</span>
							{ ': ' }
							{ __(
								'go to Settings → Connectors → Add Connector, paste the endpoint URL, and use your WordPress username + Application Password.',
								'spectra-blocks'
							) }{ ' ' }
							<a
								href="https://developers.openai.com/apps-sdk/deploy/connect-chatgpt"
								target="_blank"
								rel="noopener noreferrer"
								className="cursor-pointer text-link-primary hover:text-link-primary-hover inline-flex items-center gap-1"
							>
								{ __( 'Learn More', 'spectra-blocks' ) }
								<ExternalLink size={ 14 } />
							</a>
						</li>
					</ul>
				</li>
				<li>
					{ __(
						'Replace "your-application-password" in the JSON with the password from Step 1.',
						'spectra-blocks'
					) }
				</li>
			</ol>
			<Tabs activeItem={ activeTab }>
				<Tabs.Group
					className="w-max whitespace-nowrap bg-background-secondary"
					activeItem={ activeTab }
					onChange={ ( { value } ) => {
						setActiveTab( value?.slug || value );
						setCopied( false );
						setPromptCopied( false );
					} }
					variant="rounded"
					size="xs"
				>
					<Tabs.Tab slug="spectra-only" text={ __( 'Spectra Blocks Only', 'spectra-blocks' ) } />
					<Tabs.Tab slug="global" text={ __( 'Global', 'spectra-blocks' ) } />
				</Tabs.Group>
			</Tabs>
			<div className="relative rounded-lg" style={ { backgroundColor: '#1c1331' } }>
				<div className="absolute top-3 right-3 flex items-center gap-1">
					<Tooltip content={ __( 'Copy setup prompt for AI client', 'spectra-blocks' ) } delay={ 50 } arrow>
						<button
							onClick={ handleCopyPrompt }
							className="flex items-center justify-center w-8 h-8 rounded-md transition-colors cursor-pointer bg-transparent border-0"
							style={ { color: '#a6adc8' } }
							onMouseEnter={ ( e ) => ( e.currentTarget.style.backgroundColor = '#2a1f42' ) }
							onMouseLeave={ ( e ) => ( e.currentTarget.style.backgroundColor = 'transparent' ) }
							aria-label={ __( 'Copy setup prompt for AI client', 'spectra-blocks' ) }
						>
							{ promptCopied ? <Check size={ 16 } style={ { color: '#a6e3a1' } } /> : <Sparkles size={ 16 } /> }
						</button>
					</Tooltip>
					<Tooltip content={ __( 'Copy JSON config', 'spectra-blocks' ) } delay={ 50 } arrow>
						<button
							onClick={ handleCopy }
							className="flex items-center justify-center w-8 h-8 rounded-md transition-colors cursor-pointer bg-transparent border-0"
							style={ { color: '#a6adc8' } }
							onMouseEnter={ ( e ) => ( e.currentTarget.style.backgroundColor = '#2a1f42' ) }
							onMouseLeave={ ( e ) => ( e.currentTarget.style.backgroundColor = 'transparent' ) }
							aria-label={ __( 'Copy JSON config', 'spectra-blocks' ) }
						>
							{ copied ? <Check size={ 16 } style={ { color: '#a6e3a1' } } /> : <Copy size={ 16 } /> }
						</button>
					</Tooltip>
				</div>
				<pre
					className="p-4 pr-24 overflow-x-auto text-sm leading-relaxed font-mono m-0"
					style={ { color: '#cdd6f4' } }
				>
					{ mcpConfig }
				</pre>
			</div>
		</div>
	);
};

export default McpConnectionConfig;
