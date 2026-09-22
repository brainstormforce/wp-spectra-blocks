/**
 * Legacy responsive-store support — self-contained and removable.
 *
 * Registers the filters that serve pre-1.0.6 content: the parse-time
 * normalisation, and the store reader the current path asks through.
 * Importing this module is the only thing that switches legacy support on, so
 * removing it is two steps and nothing else:
 *
 *   1. delete this folder
 *   2. delete `import './legacy';` from `../index.js`
 *
 * The PHP half is `includes/Extensions/ResponsiveControls/Legacy/` and is removed
 * the same way — delete the folder and the one `LegacyStore` line in
 * `ResponsiveControls::init()`.
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
import { coreViewportStatesAreIndependent } from '../utils/constants';
import { isAllowedBlock } from '../utils/helpers';
import { migrateLegacyResponsiveStore } from './migrate-legacy';
import { withMarkupBackedBasePromotion } from './promote-base';
import { readLegacyBucket } from './read-bucket';

/**
 * Register the `responsiveControls` store attribute.
 *
 * Here rather than in the shared registration because this folder owns the
 * store, and because the attribute's lifetime is this folder's lifetime: delete
 * the folder and the attribute goes with it.
 *
 * UNCONDITIONAL, unlike the migration below. The store is the active storage on
 * a WordPress without core's viewport states, and on 7.1+ it is still read as a
 * legacy input by every post saved before the change. Registering it only on one
 * path would drop it from the other's content — the block parser discards
 * attributes a block type does not declare, so an unregistered store is not
 * ignored, it is ERASED from the post on load.
 *
 * That is also the removal condition, and it is stricter than either trigger on
 * its own: this may only go when the minimum WordPress has viewport states AND
 * no post still carries a store.
 *
 * @since 1.0.7
 */
addFilter(
	'blocks.registerBlockType',
	'spectra/responsive-controls/legacy/add-store-attribute',
	( settings, name ) => {
		if ( ! isAllowedBlock( { name } ) ) {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				responsiveControls: {
					type: 'object',
					default: {},
				},
			},
		};
	}
);

/**
 * Normalise the legacy store as a block is parsed.
 *
 * Runs on `blocks.getBlockAttributes`, so it happens before anything renders and
 * before core's own panels read the attributes. Parsing is not a change, so the
 * post is not marked dirty; the migrated shape persists the next time the user
 * saves for their own reasons.
 *
 * ONLY where core has viewport states. The migration's destination is
 * `style['@tablet']` / `style['@mobile']`, which a WordPress without them can
 * neither display nor render — so running it there would lift a site's values
 * into a shape its own editor cannot edit, and the next save would persist that.
 * Those versions keep reading `lg`/`md`/`sm` directly through the pre-7.1
 * editor, which is the same shape this migration reads FROM, so there is
 * nothing for them to migrate: the values are already where their editor looks.
 *
 * This is the "migrate only on 7.1+" rule, and it is enforced at the single
 * point where the migration is switched on rather than inside it.
 *
 * @since 1.0.7
 */
if ( coreViewportStatesAreIndependent() ) {
	addFilter(
		'blocks.getBlockAttributes',
		'spectra/responsive-controls/legacy/migrate-store',
		( attributes, blockType ) => migrateLegacyResponsiveStore( attributes, blockType?.name || '' )
	);
}

/**
 * Serve the legacy store to the current read path.
 *
 * The runtime never imports from this folder; it asks through this filter and
 * receives `{}` when legacy support is gone. That is what keeps the two-step
 * removal above honest — deleting the folder deletes the only implementation.
 *
 * @since 1.0.7
 */
addFilter(
	'spectra.responsive-controls.legacy-bucket',
	'spectra/responsive-controls/legacy/read-store',
	( bucket, responsiveControls, device ) => readLegacyBucket( responsiveControls, device )
);

/**
 * Promote a markup-backed block's authored base value, once it is mounted.
 *
 * The parse-time migration deliberately leaves the root attribute alone for
 * these blocks, because rewriting it there is what invalidates them (#908). The
 * value still has to arrive, so it is applied after validation instead — see
 * `promote-base.js`.
 *
 * Priority 12 puts this OUTSIDE `withResponsiveControls` (10) and
 * `withContainerVariationSync` (11), so it sees the block's real attributes
 * rather than the per-device projection those hand down.
 *
 * 7.1+ only, like the migration itself: below it the store is still the active
 * storage and the root is repainted per device by the pre-7.1 projection layer,
 * so promoting into it would fight that.
 *
 * @since 1.0.9
 */
if ( coreViewportStatesAreIndependent() ) {
	addFilter(
		'editor.BlockEdit',
		'spectra/responsive-controls/legacy/promote-markup-backed-base',
		withMarkupBackedBasePromotion,
		12
	);
}
