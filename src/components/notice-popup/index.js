/**
 * NoticePopup — the toast used across Spectra's admin surfaces.
 *
 * The visual design is the one the admin dashboard has always used for
 * "Cache Cleared Successfully!" and friends
 * (`admin/assets/src/dashboard-app/SettingsSavedNotification.js`): a white card
 * pinned to the top right, an icon coloured by status, the message, and a close
 * button.
 *
 * Only the UI was taken across. The admin version is bound to that app's Redux
 * store and draws on `@headlessui/react`, `@heroicons/react` and `react-redux`,
 * none of which exist in the editor bundle — importing it would have pulled a
 * second state library into the editor for the sake of a toast. This component
 * is presentational: it holds no state, owns no timers, and knows nothing about
 * where its notices come from, so any surface can render it against whatever
 * store it already has.
 *
 * Kept in `src/components/` rather than beside its first caller so the next
 * surface that needs a toast has one to reach for.
 *
 * @since 1.0.10
 */

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * The icon each status is announced with.
 *
 * These are the Heroicons the admin dashboard renders, transcribed rather than
 * imported: `@heroicons/react` is a dependency of the dashboard bundle only, so
 * the editor cannot `import` it without taking on a second icon library. The
 * paths and stroke settings below are copied verbatim from
 * `@heroicons/react/outline` so the two surfaces draw the identical shape — a
 * rounded circle enclosing the glyph, not a bare tick.
 *
 * `size` mirrors the admin's Tailwind classes: `h-6 w-6` for success, `h-4 w-4`
 * for warning and error.
 *
 * @since 1.0.10
 * @type {Object}
 */
const STATUS_ICONS = {
	success: {
		// Heroicons outline `CheckCircleIcon`.
		path: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
		size: 24,
	},
	warning: {
		// Heroicons outline `ExclamationCircleIcon`.
		path: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
		size: 16,
	},
	error: {
		// Heroicons outline `XCircleIcon`.
		path: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
		size: 16,
	},
};

/**
 * The status glyph.
 *
 * Stroked rather than filled, so the colour comes from the item's `color` and
 * the circle keeps the open, rounded look the dashboard has.
 *
 * @since 1.0.10
 *
 * @param {Object} props        Component props.
 * @param {string} props.status Notice status. Falls back to `success`.
 * @return {Element} The icon.
 */
const StatusIcon = ( { status } ) => {
	const { path, size } = STATUS_ICONS[ status ] || STATUS_ICONS.success;

	return (
		<svg
			className="spectra-notice-popup__icon"
			xmlns="http://www.w3.org/2000/svg"
			width={ size }
			height={ size }
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth={ 2 }
			strokeLinecap="round"
			strokeLinejoin="round"
			aria-hidden="true"
			focusable="false"
		>
			<path d={ path } />
		</svg>
	);
};

/**
 * NoticePopup component.
 *
 * `anchor` picks what the stack is pinned to:
 *
 *   - `viewport` (default) — fixed to the browser window's top right, which is
 *     where the admin dashboard puts its own toasts. Right for a page-level
 *     surface.
 *   - `container` — absolutely placed in the nearest positioned ancestor, so a
 *     modal can keep its feedback inside its own card instead of throwing it
 *     out to the corner of the screen. The offsets are left to that ancestor
 *     (see the CSS variables in `style.scss`), since only it knows what its
 *     chrome occupies.
 *
 * @since 1.0.10
 *
 * @param {Object}   props           Component props.
 * @param {Array}    props.notices   Notices to show: `{ id, status, message }`.
 * @param {Function} props.onDismiss Called with a notice id when its close button is used.
 * @param {string}   props.label     Accessible name for the region. Optional.
 * @param {string}   props.anchor    `viewport` (default) or `container`.
 * @return {Element|null} The stack, or nothing when there is nothing to show.
 */
const NoticePopup = ( { notices = [], onDismiss, label, anchor = 'viewport' } ) => {
	if ( ! notices.length ) {
		return null;
	}

	return (
		<div
			className={ `spectra-notice-popup is-anchored-${ anchor }` }
			data-component="NoticePopup"
			role="region"
			aria-live="assertive"
			aria-label={ label || __( 'Notifications', 'spectra-blocks' ) }
		>
			{ notices.map( ( notice ) => (
				<div
					key={ notice.id }
					className={ `spectra-notice-popup__item is-${ notice.status }` }
					role={ 'error' === notice.status ? 'alert' : 'status' }
				>
					<StatusIcon status={ notice.status } />

					<span className="spectra-notice-popup__text">{ notice.message }</span>

					{ onDismiss && (
						<button
							type="button"
							className="spectra-notice-popup__dismiss"
							onClick={ () => onDismiss( notice.id ) }
							aria-label={ __( 'Dismiss notification', 'spectra-blocks' ) }
						>
							{ /* Heroicons solid `XIcon`, as the dashboard's close button uses. */ }
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="24"
								height="24"
								viewBox="0 0 20 20"
								fill="currentColor"
								aria-hidden="true"
								focusable="false"
							>
								<path
									fillRule="evenodd"
									clipRule="evenodd"
									d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
								/>
							</svg>
						</button>
					) }
				</div>
			) ) }
		</div>
	);
};

export default NoticePopup;
