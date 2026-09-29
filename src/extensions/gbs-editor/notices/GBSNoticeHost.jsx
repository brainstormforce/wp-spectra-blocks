/**
 * GBSNoticeHost — renders the Global Styles editor's notice stack.
 *
 * Mounted once by the modal shell. Deliberately holds no notice state of its
 * own: the store in `gbsNotices` outlives any individual panel, so a panel that
 * unmounts right after saving still gets its confirmation shown.
 *
 * The toast itself is `components/notice-popup`, which is the design the admin
 * dashboard has always used for its own confirmations, so a save in Global
 * Styles now looks like a save anywhere else in Spectra. This file keeps the
 * subscription and the host bookkeeping; the popup keeps the appearance.
 *
 * @since 1.0.10
 */

/**
 * WordPress dependencies.
 */
import { useEffect, useSyncExternalStore } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import NoticePopup from '@spectra-components/notice-popup';
import { gbsNotices } from './gbsNotices.js';

/**
 * GBSNoticeHost component.
 *
 * @since 1.0.10
 *
 * @return {Element|null} The notice stack, or nothing when empty.
 */
const GBSNoticeHost = () => {
	const notices = useSyncExternalStore(
		gbsNotices.subscribe,
		gbsNotices.getNotices
	);

	// Announce this host to the store, and tear down cleanly: deregistering the
	// last host clears pending timers so a closing modal can't fire a setState
	// into an unmounted tree (the leak the old per-panel setTimeouts had).
	useEffect( () => gbsNotices.registerHost(), [] );

	return (
		<NoticePopup
			notices={ notices }
			onDismiss={ gbsNotices.remove }
			label={ __( 'Global Styles notifications', 'spectra-blocks' ) }
			// Seated on the modal card rather than the browser window: this
			// feedback belongs to the dialog the user is working in, and pinning
			// it to the screen corner puts it outside the surface that raised it.
			anchor="container"
		/>
	);
};

export default GBSNoticeHost;
