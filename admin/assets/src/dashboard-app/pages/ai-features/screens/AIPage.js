/**
 * The AI Features Page.
 */
import { useState, useEffect } from '@wordpress/element';
import { useSelector } from 'react-redux';
import { __ } from '@wordpress/i18n';

import getApiData from '@Controls/getApiData';

import { Button, Badge } from '@bsf/force-ui';
import QuickAccess from '@Common/components/QuickAccess';

import { CheckCircle2, ExternalLink } from 'lucide-react';
import {
	PlugIcon,
	SparklesIcon,
	FilePlusIcon,
	PaletteIcon,
	ActivityIcon,
	BroomIcon,
	LanguagesIcon,
	LayoutGridIcon,
} from '../elements/IconComponents';

const AI_FEATURES = [
	{
		icon: <LayoutGridIcon size={ 26 } />,
		title: __( 'Build a Fresh Website', 'spectra-blocks' ),
		description: __(
			'Describe your business and ZipAI builds a complete site with pages, copy, images, and headers, all as editable Spectra Blocks.',
			'spectra-blocks'
		),
	},
	{
		icon: <FilePlusIcon size={ 26 } />,
		title: __( 'Create a New Page', 'spectra-blocks' ),
		description: __(
			"Add pages by describing them. Every new page inherits your site's colors, spacing, and tone.",
			'spectra-blocks'
		),
	},
	{
		icon: <PaletteIcon size={ 26 } />,
		title: __( 'Edit Global Styles & CSS', 'spectra-blocks' ),
		description: __(
			'Every build ships with editable global styles, CSS, and scripts. Refine them right inside the editor.',
			'spectra-blocks'
		),
	},
	{
		icon: <ActivityIcon size={ 26 } />,
		title: __( 'Run a Site Health Check', 'spectra-blocks' ),
		description: __(
			'ZipAI scans your site for issues and suggests fixes automatically. Stay fast, secure, and up to date.',
			'spectra-blocks'
		),
	},
	{
		icon: <BroomIcon size={ 26 } />,
		title: __( 'Clean Up My Website', 'spectra-blocks' ),
		description: __(
			'Remove clutter, unused assets, and stale content in one request. Keep your site lean without manual audits.',
			'spectra-blocks'
		),
	},
	{
		icon: <LanguagesIcon size={ 26 } />,
		title: __( 'Chat in Your Own Language', 'spectra-blocks' ),
		description: __(
			"Build and edit in whatever language you're comfortable with. No translation step, no barrier.",
			'spectra-blocks'
		),
	},
];

const reloadWithZipAI = () => {
	const url = new URL( window.location.href );
	url.searchParams.set( 'zipwp_open_assistant', '1' );
	window.location.href = url.toString();
};

const AIPage = () => {
	const pluginStatus    = spectra_blocks_react?.zip_ai_plugin_status || 'Install';
	const isAuthorized    = !! spectra_blocks_react?.zip_ai_is_authorized;
	const isPluginActive  = 'Activated' === pluginStatus;

	const [ installStatus, setInstallStatus ]     = useState( null );
	const [ isPluginInstalled, setIsPluginInstalled ] = useState( isPluginActive );
	const [ isSidebarOpen, setIsSidebarOpen ]     = useState( false );

	// Always observe the ZIP-AI container on mount so we catch sidebars opened by
	// the bridge host (e.g. via ?zipwp_open_assistant=1 on first-install page reload).
	useEffect( () => {
		const container = document.getElementById( 'zip-ai-assistant-container' );
		if ( ! container ) {
			return;
		}

		const syncState = () => {
			const style = window.getComputedStyle( container );
			const open  = style.display !== 'none' && style.visibility !== 'hidden' && ! container.hidden;
			setIsSidebarOpen( open );
		};

		const observer = new MutationObserver( syncState );
		observer.observe( container, { attributes: true, attributeFilter: [ 'class', 'style', 'hidden' ] } );

		// Bridge host opens the panel after 300 ms — check again at 400 ms to catch it.
		const timer = setTimeout( syncState, 400 );

		return () => {
			observer.disconnect();
			clearTimeout( timer );
		};
	}, [] );

	// Opens the ZIP-AI sidebar. If the bridge is on the page, toggle directly;
	// otherwise reload with ?zipwp_open_assistant=1 so the plugin scripts load.
	const openZipAI = () => {
		if ( window.zipwpMcpBridge?.togglePanel ) {
			window.zipwpMcpBridge.togglePanel();
			return;
		}
		const url = new URL( window.location.href );
		url.searchParams.set( 'zipwp_open_assistant', '1' );
		window.location.href = url.toString();
	};

	const initialStateSetFlag   = useSelector( ( state ) => state.initialStateSetFlag );
	const currentZipAiStatus    = useSelector( ( state ) => state.zipAiModules );
	const { ai_assistant: zipAiAssistant, ai_design_copilot: zipAiDesignCopilot } = currentZipAiStatus;

	const checkModuleStatus = ( theModule ) => {
		switch ( theModule?.status ) {
			case 'enabled':
				return true;
			case 'disabled':
				return false;
			default:
				return undefined;
		}
	};

	const zipAiAssistantStatus     = checkModuleStatus( zipAiAssistant );
	const zipAiDesignCopilotStatus = checkModuleStatus( zipAiDesignCopilot );

	// Once module state is loaded, mark loading complete (used by parent if needed).
	void initialStateSetFlag;
	void zipAiAssistantStatus;
	void zipAiDesignCopilotStatus;

	const connectZipAI = async () => {
		if ( 'Install' === pluginStatus ) {
			setInstallStatus( 'installing' );
			let installRes;
			try {
				installRes = await getApiData( {
					url: spectra_blocks_react.ajax_url,
					action: 'spectra_blocks_install_zip_ai',
					data: { security: spectra_blocks_react.install_zip_ai_nonce },
				} );
			} catch {
				setInstallStatus( null );
				return;
			}
			if ( ! installRes?.success ) {
				setInstallStatus( null );
				return;
			}
			if ( 'already_active' === installRes.data?.status ) {
				reloadWithZipAI();
				return;
			}
		}

		setInstallStatus( 'activating' );
		let activateRes;
		try {
			activateRes = await getApiData( {
				url: spectra_blocks_react.ajax_url,
				action: 'spectra_blocks_activate_zip_ai',
				data: { security: spectra_blocks_react.activate_zip_ai_nonce },
			} );
		} catch {
			setInstallStatus( null );
			return;
		}
		if ( ! activateRes?.success ) {
			setInstallStatus( null );
			return;
		}
		setIsPluginInstalled( true );
		reloadWithZipAI();
	};

	const getConnectButtonLabel = () => {
		if ( 'installing' === installStatus ) { return __( 'Installing…', 'spectra-blocks' ); }
		if ( 'activating' === installStatus ) { return __( 'Activating…', 'spectra-blocks' ); }
		return __( 'Get Started with AI', 'spectra-blocks' );
	};

	return (
		<main className="bg-background-secondary min-h-[calc(100vh_-_8rem)]">
			<div className="md:p-8 sm:p-6 p-[0.7rem]">
			<div className="max-w-[1320px] mx-auto flex flex-col gap-7">

				{ /* ── 1. HERO ──────────────────────────────────────────────────── */ }
				<div className="bg-background-primary rounded-xl border border-solid border-border-subtle overflow-hidden">
					<div className="grid grid-cols-1 lg:grid-cols-2 gap-10 p-6 sm:p-8 lg:p-12 items-center">
						<div className="flex flex-col gap-4">
							<div className="flex items-center gap-1.5 text-brand-primary-600">
								<SparklesIcon size={ 20 } />
								<span className="text-[15px] font-semibold">
									{ __( 'Welcome to ZipAI', 'spectra-blocks' ) }
								</span>
							</div>
							<h2 className="text-4xl font-semibold text-text-primary m-0 leading-snug">
								{ __( 'Your AI Site Builder, Right Inside WordPress', 'spectra-blocks' ) }
							</h2>
							<p className="text-base text-text-secondary m-0 mb-7">
								{ __(
									'ZipAI is powered by ZipWP, our AI platform, and builds with Spectra Blocks. Everything it generates lands in your editor as real, fully editable blocks.',
									'spectra-blocks'
								) }
							</p>
							<div className="flex flex-wrap gap-5 items-center">
								{ isPluginInstalled ? (
									<Button
										className="spectra-blocks-remove-ring"
										variant="primary"
										disabled={ isSidebarOpen }
										onClick={ openZipAI }
									>
										{ __( 'Launch ZIP-AI', 'spectra-blocks' ) }
									</Button>
								) : (
									<Button
										className="spectra-blocks-remove-ring"
										variant="primary"
										disabled={ installStatus !== null }
										onClick={ connectZipAI }
									>
										{ getConnectButtonLabel() }
									</Button>
								) }
								<Button
									className="spectra-blocks-remove-ring !text-[15px]"
									variant="ghost"
									icon={ <ExternalLink /> }
									iconPosition="right"
									onClick={ () => window.open( 'https://wpspectra.com/docs/', '_blank' ) }
								>
									{ __( 'Learn More', 'spectra-blocks' ) }
								</Button>
							</div>
						</div>

						{ /* Hero image panel */ }
						<div className="flex justify-center">
							<img
								src={ `${ spectra_blocks_react?.plugin_url || '' }assets/admin/images/ai-features-hero.jpg` }
								alt={ __( 'AI Features Preview', 'spectra-blocks' ) }
								className="w-full max-w-[560px] h-auto block rounded-[14px]"
							/>
						</div>
					</div>
				</div>

				{ /* ── 2. ZIPWP ACCOUNT ─────────────────────────────────────────── */ }
				<div className="bg-background-primary rounded-xl border border-solid border-border-subtle py-7 px-8">
					<div className="flex flex-col sm:flex-row items-start sm:justify-between gap-4">
						<div className="flex flex-col gap-1.5 flex-1 min-w-0">
							<div className="flex items-center gap-3 flex-wrap">
								<PlugIcon size={ 22 } className="text-brand-primary-600 shrink-0" />
								<span className="font-semibold text-xl text-text-primary">
									{ __( 'ZipWP Account', 'spectra-blocks' ) }
								</span>
								{ isAuthorized ? (
									<Badge
										icon={ <CheckCircle2 size={ 11 } /> }
										label={ __( 'Active', 'spectra-blocks' ) }
										size="sm"
										type="pill"
										variant="green"
										className="!text-[13px]"
									/>
								) : (
									<Badge
										label={ __( 'Inactive', 'spectra-blocks' ) }
										size="sm"
										type="pill"
										variant="primary"
									/>
								) }
							</div>
							<p className="text-[15px] text-text-secondary m-0">
								{ __(
									'ZipAI runs on your free ZipWP account. Connect once inside the ZIP-AI panel to unlock AI site building, page generation, and credits.',
									'spectra-blocks'
								) }
							</p>
						</div>

						<div className="flex items-center gap-3 shrink-0 pt-1">
							<Button
								className="spectra-blocks-remove-ring !text-brand-primary-600 !font-semibold !text-[15px]"
								variant="outline"
								icon={ <ExternalLink /> }
								iconPosition="right"
								onClick={ () => window.open( 'https://app.zipwp.com/account-settings', '_blank' ) }
							>
								{ __( 'Manage Account', 'spectra-blocks' ) }
							</Button>
						</div>
					</div>
				</div>

				{ /* ── 3. AI-POWERED FEATURES ───────────────────────────────────── */ }
				<div className="bg-background-primary rounded-xl border border-solid border-border-subtle p-10 flex flex-col gap-4">
					<div>
						<span className="font-semibold text-2xl text-text-primary">
							{ __( 'AI-Powered Features', 'spectra-blocks' ) }
						</span>
					</div>
					<div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
						{ AI_FEATURES.map( ( feature, index ) => (
							<div
								key={ index }
								className="flex flex-col gap-2 p-7 rounded-lg border border-solid border-border-subtle bg-field-primary-background"
							>
								<div className="text-brand-primary-600">{ feature.icon }</div>
								<span className="font-semibold text-xl text-text-primary">{ feature.title }</span>
								<span className="text-[15px] text-text-secondary leading-relaxed">{ feature.description }</span>
							</div>
						) ) }
					</div>
				</div>

				{ /* ── 4. QUICK ACCESS ──────────────────────────────────────────── */ }
				<QuickAccess />

			</div>
			</div>
		</main>
	);
};

export default AIPage;
