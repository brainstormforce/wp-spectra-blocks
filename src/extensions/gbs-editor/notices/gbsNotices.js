/**
 * gbsNotices — one save-feedback channel for the whole Global Styles editor.
 *
 * Every panel used to invent its own confirmation: BlockDefaultsPanel held a
 * footer string for 3s behind a bare setTimeout, StyleGuideView held a "✓ Saved"
 * line inside a dialog that auto-closed 900ms later, and CustomVars / Classes /
 * Keyframes showed nothing at all. The first two pinned the message to a surface
 * that was already dismissing; the rest left the user re-clicking Save to find
 * out whether anything persisted.
 *
 * This module owns the notice list; `GBSNoticeHost` renders it. Two rules make
 * the "feedback on a closing surface" bug unrepresentable:
 *
 *   1. Notices live in a MODULE-LEVEL store, not in the panel that emitted them,
 *      so a panel unmounting mid-toast can neither leak a timer nor strand a
 *      message.
 *   2. When no host is mounted, or the modal has declared itself closing, the
 *      notice is routed to the editor's own snackbar on the underlying page
 *      instead — the surface that will still be there a second from now.
 *
 * @since 1.0.10
 */

/**
 * WordPress dependencies.
 */
import { dispatch } from '@wordpress/data';
// Side-effect import: registers the `core/notices` store and adds wp-notices to
// the generated asset dependency list, so the page-level fallback below is
// always available rather than only when some other script happened to pull it in.
import '@wordpress/notices';

/** How long a notice stays up before it dismisses itself. */
const AUTO_DISMISS_MS = 4000;

/** Notices currently on screen: `{ id, status, message }`. */
let notices = [];

/** Subscribed renderers. */
const listeners = new Set();

/** Pending auto-dismiss timers, keyed by notice id, so unmount can clear them. */
const timers = new Map();

/** Number of mounted hosts. Zero means nothing in-modal can show a notice. */
let hostCount = 0;

/**
 * True while the modal is tearing down. Set by the modal before it runs any
 * save/discard that precedes `onClose`, so feedback from that save lands on the
 * page instead of flashing on the dialog the user just dismissed.
 */
let closing = false;

let nextId = 1;

const emit = () => listeners.forEach( ( listener ) => listener() );

/**
 * Push a notice onto the editor's own snackbar queue on the underlying page.
 *
 * Used whenever there is no in-modal surface that will outlive the message.
 * Guarded: the notices store is registered by the side-effect import above, but
 * a host page that dequeues wp-notices should degrade to silence, not a crash.
 *
 * @since 1.0.10
 *
 * @param {string} status  'success' | 'error'.
 * @param {string} message Text to show.
 * @return {void}
 */
const toPageSnackbar = ( status, message ) => {
	dispatch( 'core/notices' )?.createNotice?.( status, message, {
		type: 'snackbar',
		isDismissible: true,
	} );
};

/**
 * Remove one notice and cancel its pending auto-dismiss.
 *
 * @since 1.0.10
 *
 * @param {number} id Notice id.
 * @return {void}
 */
const remove = ( id ) => {
	const timer = timers.get( id );
	if ( timer ) {
		clearTimeout( timer );
		timers.delete( id );
	}
	const next = notices.filter( ( n ) => n.id !== id );
	if ( next.length !== notices.length ) {
		notices = next;
		emit();
	}
};

/**
 * Emit a notice — in-modal when a host can hold it, on the page otherwise.
 *
 * @since 1.0.10
 *
 * @param {string} status  'success' | 'error'.
 * @param {string} message Text to show.
 * @return {void}
 */
const notify = ( status, message ) => {
	if ( ! message ) {
		return;
	}

	// No surface that will outlive the message → hand it to the page.
	if ( hostCount === 0 || closing ) {
		toPageSnackbar( status, message );
		return;
	}

	const id = nextId++;
	notices = [ ...notices, { id, status, message } ];
	emit();

	// Errors stay until dismissed — they usually need reading and acting on.
	if ( 'error' !== status ) {
		timers.set( id, setTimeout( () => remove( id ), AUTO_DISMISS_MS ) );
	}
};

export const gbsNotices = {
	/**
	 * Show a success confirmation.
	 *
	 * @since 1.0.10
	 *
	 * @param {string} message Text to show.
	 * @return {void}
	 */
	success: ( message ) => notify( 'success', message ),

	/**
	 * Show an error. Stays until dismissed.
	 *
	 * @since 1.0.10
	 *
	 * @param {string} message Text to show.
	 * @return {void}
	 */
	error: ( message ) => notify( 'error', message ),

	/**
	 * Dismiss one notice.
	 *
	 * @since 1.0.10
	 *
	 * @param {number} id Notice id.
	 * @return {void}
	 */
	remove,

	/**
	 * Drop every notice and cancel every pending timer.
	 *
	 * @since 1.0.10
	 *
	 * @return {void}
	 */
	clear: () => {
		timers.forEach( ( timer ) => clearTimeout( timer ) );
		timers.clear();
		if ( notices.length ) {
			notices = [];
			emit();
		}
	},

	/**
	 * Mark the modal as closing so subsequent notices go to the page snackbar.
	 *
	 * Call before awaiting a save that is followed by `onClose`. Cleared by
	 * `endClosing` when the close is abandoned (a failed save, "Keep editing").
	 *
	 * @since 1.0.10
	 *
	 * @return {void}
	 */
	beginClosing: () => {
		closing = true;
	},

	/**
	 * Cancel the closing flag — the modal is staying open after all.
	 *
	 * @since 1.0.10
	 *
	 * @return {void}
	 */
	endClosing: () => {
		closing = false;
	},

	/**
	 * Current notices.
	 *
	 * @since 1.0.10
	 *
	 * @return {Array} Notice list.
	 */
	getNotices: () => notices,

	/**
	 * Subscribe to store changes.
	 *
	 * @since 1.0.10
	 *
	 * @param {Function} listener Called on every change.
	 * @return {Function} Unsubscribe.
	 */
	subscribe: ( listener ) => {
		listeners.add( listener );
		return () => listeners.delete( listener );
	},

	/**
	 * Host lifecycle — called by `GBSNoticeHost` only.
	 *
	 * Registering resets the closing flag: a freshly mounted host means a modal
	 * that is opening, not one left mid-teardown by a previous session.
	 *
	 * @since 1.0.10
	 *
	 * @return {Function} Deregister, which also clears any notices left over.
	 */
	registerHost: () => {
		hostCount++;
		closing = false;
		return () => {
			hostCount = Math.max( 0, hostCount - 1 );
			if ( hostCount === 0 ) {
				closing = false;
				gbsNotices.clear();
			}
		};
	},
};

export default gbsNotices;
