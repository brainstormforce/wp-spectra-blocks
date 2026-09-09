/**
 * The pre-7.1 editor implementation — self-contained and removable.
 *
 * WordPress without viewport states cannot store or display per-device values
 * the way 7.1 does, so those sites keep the implementation that shipped in
 * 1.0.6: values in Spectra's own `responsiveControls` attribute keyed
 * `lg` / `md` / `sm`, and the block's live attributes rewritten on a device
 * switch so core's panels display the previewed device.
 *
 * REMOVAL — when the plugin's minimum WordPress becomes 7.1, this is two steps
 * and nothing else:
 *
 *   1. delete this folder
 *   2. delete the `registerClassicEditor()` call in `../../index.js`
 *
 * Note the trigger. It is NOT the same as the one for the folder above this
 * (`legacy/`), which serves content written before 1.0.6 and goes away when no
 * such content matters any more. The two live under `legacy/` together for
 * convenience, but they die on different days:
 *
 *   legacy/                → removable when pre-1.0.6 CONTENT no longer matters
 *   legacy/classic-editor/ → removable when the minimum WORDPRESS is 7.1
 *
 * They also load on opposite conditions: the store reader above runs everywhere
 * (it normalises old content for whichever renderer is active), while this
 * folder runs only where core has no viewport states. Deleting the wrong half
 * breaks a different set of sites, so check which trigger you are satisfying.
 *
 * @since 1.0.7
 */

/**
 * External dependencies.
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import { withResponsiveControls, withDeviceViewUpdate } from './helpers';
import { withBackwardCompatibility } from './backward-compatibility';

/**
 * Register the pre-7.1 editor filters.
 *
 * Called from `../../index.js` only when core has no viewport states, so on 7.1
 * and later nothing in this folder is ever registered — importing the module is
 * not enough to switch it on, which keeps the two implementations from running
 * at the same time.
 *
 * The priorities match 1.0.6 exactly. `withDeviceViewUpdate` runs at 11, after
 * the write router at the default 10, because the projection has to see the
 * attributes the router just produced.
 *
 * @since 1.0.7
 * @return {void}
 */
export const registerClassicEditor = () => {
	addFilter(
		'editor.BlockEdit',
		'spectra/responsive-controls/with-responsive-controls',
		withResponsiveControls
	);

	addFilter(
		'editor.BlockEdit',
		'spectra/responsive-controls/with-device-view-update',
		withDeviceViewUpdate,
		11
	);

	/*
	 * 1.0.6's root-attribute mapper, at 1.0.6's priority. The shared one is
	 * registered by `../../index.js` for the viewport path only: #732 changed it
	 * to write into `style` and clear the source attribute, which on a core
	 * without viewport states empties the control instead of filling it.
	 */
	addFilter(
		'editor.BlockEdit',
		'spectra/responsive-controls/with-backward-compatibility',
		withBackwardCompatibility,
		9
	);
};
