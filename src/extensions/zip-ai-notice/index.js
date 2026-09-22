/**
 * Zip AI page notice — editor banner shown on pages built by Zip AI.
 *
 * Whether to show it is decided server-side (Zip AI active + the page is Zip
 * AI-built + not already dismissed) and handed over as `shouldRender`; see
 * `includes/class-extension-manager.php::get_zipai_notice_data`. When true this
 * renders a Spectra-branded banner into the editor's notice region (portalled
 * to the top of the editor content, where WordPress' own notices sit).
 *
 * The copy is the same for everyone. Only the "Spectra Blocks Global Styles"
 * CTA differs, by the Spectra Pro `state`:
 *   - not_installed → link out to the Spectra Pro pricing page.
 *   - installed     → open the upgrade popup (the gbs-pro-nudge modal).
 *   - active        → open the real Global Styles editor (same as the sidebar
 *                     "Manage Global Styles" setting).
 *
 * Dismissal is per-page: closing the banner persists the
 * `_spectra_zipai_notice_dismissed` post meta, so the server won't ask it to
 * render again for that page.
 *
 * @since 1.0.9
 */

import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { registerPlugin } from '@wordpress/plugins';
import {
	createPortal,
	createInterpolateElement,
	useState,
	useEffect,
} from '@wordpress/element';

import SpectraIcon from '../gbs-editor/components/shared/SpectraIcon.jsx';
import {
	getCtaAction,
	shouldRenderNotice,
	buildDismissRequest,
} from './logic.mjs';
import './style.scss';

// Spectra Pro pricing page — fallback when PHP-localized data is unavailable.
const FALLBACK_UPGRADE_URL =
	'https://wpspectra.com/pricing/?utm_source=free-plugin&utm_medium=block-editor&utm_campaign=zipai-page-notice';

/**
 * Server-decided state plus the data the CTA needs.
 *
 * @return {{shouldRender: boolean, state: string, gbsUpgradeUrl: string}} Notice state.
 */
const getNoticeData = () => {
	const data = window.spectra_blocks_zipai_notice || {};
	return {
		shouldRender: !! data.shouldRender,
		state: data.state || 'not_installed',
		gbsUpgradeUrl: data.gbsUpgradeUrl || FALLBACK_UPGRADE_URL,
	};
};

/**
 * Close (X) glyph, matching the core notice dismiss control.
 *
 * @return {Element} SVG element.
 */
const CloseIcon = () => (
	<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
		<path d="M18 6 6 18" />
		<path d="m6 6 12 12" />
	</svg>
);

/**
 * Open the real Global Styles editor in place (Pro active). Prefers the V2
 * editor's imperative handle, falling back to the legacy open event the bridge
 * listens for — the same path the sidebar "Manage Global Styles" setting uses.
 *
 * @return {void}
 */
const openGlobalStyles = () => {
	if ( window.__spectraGBSEditorV2?.open ) {
		window.__spectraGBSEditorV2.open( 'styleguide' );
		return;
	}
	window.dispatchEvent( new CustomEvent( 'spectraGSOpenModal' ) );
};

/**
 * Open the Spectra Pro upgrade popup (Pro installed-but-inactive) — the same
 * modal the gbs-pro-nudge inspector's "Manage Global Styles" button opens.
 *
 * @return {void}
 */
const openUpgradeModal = () => {
	window.__spectraGbsProNudge?.openModal?.();
};

/**
 * The anchor props for the "Spectra Blocks Global Styles" link, by CTA action.
 * `pricing` is a real outbound link; the other two are in-editor handlers.
 *
 * @param {'gbs-editor'|'nudge-modal'|'pricing'} action        CTA action.
 * @param {string}                               gbsUpgradeUrl Pricing URL.
 * @return {Object} Props to spread onto the `<a>`.
 */
const ctaLinkProps = ( action, gbsUpgradeUrl ) => {
	if ( 'pricing' === action ) {
		return { href: gbsUpgradeUrl, target: '_blank', rel: 'noreferrer' };
	}
	const handler = 'gbs-editor' === action ? openGlobalStyles : openUpgradeModal;
	return {
		href: '#',
		onClick: ( event ) => {
			event.preventDefault();
			handler();
		},
	};
};

/**
 * Resolve (and lazily create) the DOM node the banner portals into — the first
 * child of the editor content region, so it sits above the canvas exactly where
 * core notices render. The editor mounts asynchronously, so retry briefly until
 * the host exists.
 *
 * @return {HTMLElement|null} Portal target, or null until the editor mounts.
 */
const useNoticePortal = () => {
	const [ container, setContainer ] = useState( null );

	useEffect( () => {
		let node = null;
		const attach = () => {
			const host = document.querySelector( '.interface-interface-skeleton__content' );
			if ( ! host ) {
				return false;
			}
			node = document.getElementById( 'spectra-zipai-notice-root' );
			if ( ! node ) {
				node = document.createElement( 'div' );
				node.id = 'spectra-zipai-notice-root';
				host.insertBefore( node, host.firstChild );
			}
			setContainer( node );
			return true;
		};

		if ( attach() ) {
			return undefined;
		}
		const timer = setInterval( () => {
			if ( attach() ) {
				clearInterval( timer );
			}
		}, 300 );
		return () => clearInterval( timer );
	}, [] );

	return container;
};

/**
 * The banner plugin — renders the Spectra-branded notice when the server says
 * so and a portal target is available.
 *
 * @return {Element|null} The portalled banner, or null.
 */
const ZipAiNotice = () => {
	const { shouldRender, state, gbsUpgradeUrl } = getNoticeData();
	const [ hidden, setHidden ] = useState( false );
	const container = useNoticePortal();

	if ( ! shouldRenderNotice( { shouldRender, hidden, hasContainer: !! container } ) ) {
		return null;
	}

	// Dismissal is site-wide and permanent — hide locally now, then persist the
	// option so the server stops rendering it everywhere on subsequent loads.
	const dismiss = () => {
		setHidden( true );
		apiFetch( buildDismissRequest() ).catch( () => {} );
	};

	const linkText = __( 'Spectra Blocks Global Styles', 'spectra-blocks' );
	const message = createInterpolateElement(
		__(
			'This page was built with Zip AI. Continue editing with Zip AI chat, or manage your site settings through <a>Spectra Blocks Global Styles</a> Feature.',
			'spectra-blocks'
		),
		{
			// eslint-disable-next-line jsx-a11y/anchor-has-content
			a: <a { ...ctaLinkProps( getCtaAction( state ), gbsUpgradeUrl ) } aria-label={ linkText } />,
		}
	);

	const banner = (
		<div className="spectra-zipai-notice" role="status">
			<span className="spectra-zipai-notice__icon" aria-hidden="true">
				<SpectraIcon size={ 20 } color="#ffffff" />
			</span>
			<p className="spectra-zipai-notice__text">{ message }</p>
			<button
				type="button"
				className="spectra-zipai-notice__dismiss"
				onClick={ dismiss }
				aria-label={ __( 'Dismiss this notice', 'spectra-blocks' ) }
			>
				<CloseIcon />
			</button>
		</div>
	);

	return createPortal( banner, container );
};

registerPlugin( 'spectra-zip-ai-notice', {
	render: ZipAiNotice,
} );
