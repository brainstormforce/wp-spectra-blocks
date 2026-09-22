/**
 * Promote a markup-backed block's authored base value, after validation.
 *
 * On legacy content the ROOT attribute is a scratch projection: the pre-1.0.6
 * editor wrote whichever device was being previewed straight into it, so a block
 * last touched on Mobile carries the mobile width at its root while the authored
 * desktop value sits in the store's base bucket. Real content looks like this:
 *
 *     width: '150px'                                  <- the MOBILE value
 *     responsiveControls: { lg: { width: '700px' },   <- the real desktop value
 *                           md: { width: '400px' },
 *                           sm: { width: '150px' } }
 *
 * The parse-time migration cannot correct that. WordPress validates stored HTML
 * against `save( attributes-after-filters )`, and `core/image` serialises its
 * width into the markup — so rewriting the root there turns every such block
 * into "this block contains unexpected or invalid content", and Attempt Block
 * Recovery then persists whatever the rewrite produced (#908).
 *
 * Doing it HERE avoids that completely. The block is already mounted, so
 * validation has run and passed against the markup as saved; changing the
 * attribute afterwards is an ordinary edit, not a validation input. The editor
 * immediately shows and renders the authored desktop value, and the markup
 * catches up the next time the post is saved.
 *
 * Marked non-persistent, so it creates no undo level and does not present the
 * author with unsaved changes they did not make. The front end does not wait for
 * that save: `LegacyStore::promote_markup_backed_base()` applies the same value
 * at render.
 *
 * ONLY PROMOTES. A key the base bucket does not hold is left exactly as it is —
 * clearing the root because a narrower bucket happened to define it is how the
 * first attempt at #908 lost people's image sizes.
 *
 * @since 1.0.9
 */

/**
 * External dependencies.
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import { BLOCK_RESPONSIVE_KEYS, savesAttributesToMarkup } from '../utils/constants';

/**
 * Promote the base bucket's flat values into the root attributes.
 *
 * @since 1.0.9
 */
export const withMarkupBackedBasePromotion = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, clientId } = props;
		const { __unstableMarkNextChangeAsNotPersistent, updateBlockAttributes } =
			useDispatch( 'core/block-editor' );

		/*
		 * The STORED attributes, not `props.attributes`.
		 *
		 * `withResponsiveControls` hands its inner tree a per-device projection
		 * of the attributes, so reading the props here would compare the root
		 * against whatever the previewed device resolves to and promote nothing.
		 * This HOC is registered above it for the same reason; the select is the
		 * belt to that braces.
		 */
		const stored = useSelect(
			( select ) => select( 'core/block-editor' ).getBlockAttributes( clientId ),
			[ clientId ]
		);

		useEffect( () => {
			if ( ! savesAttributesToMarkup( name ) ) {
				return;
			}

			const store = stored?.responsiveControls;

			if ( ! store || ! Object.keys( store ).length ) {
				return;
			}

			// Through the same filter the runtime reads by, so `lg` resolves.
			const base = applyFilters( 'spectra.responsive-controls.legacy-bucket', {}, store, 'base' );

			if ( ! base || ! Object.keys( base ).length ) {
				return;
			}

			const flatKeys = applyFilters(
				'spectra.responsive-controls.block-responsive-keys',
				BLOCK_RESPONSIVE_KEYS[ name ] || [],
				name
			);

			/*
			 * The narrower buckets, used to recognise a scratch root.
			 *
			 * The store is write-once history: nothing updates it after the
			 * upgrade. So "the root differs from the base bucket" is NOT enough
			 * to promote on — once the author sets their own width the two
			 * differ forever, and promoting on every load silently reverts them
			 * to the legacy value each time the post is reopened.
			 *
			 * What identifies a scratch root is that it still carries the value
			 * of one of the NARROWER devices, which is what the pre-1.0.6 editor
			 * projected into it. An authored width matches none of them.
			 */
			const narrower = [ '@tablet', '@mobile' ].map( ( device ) =>
				applyFilters( 'spectra.responsive-controls.legacy-bucket', {}, store, device )
			);

			const changes = {};

			flatKeys.forEach( ( key ) => {
				// Promote only. Never clear, and never restate what is already there.
				if ( undefined === base[ key ] || '' === base[ key ] ) {
					return;
				}

				if ( stored[ key ] === base[ key ] ) {
					return;
				}

				/*
				 * A base value written by the CURRENT editor settles it on its
				 * own: the device-routed write path records one in `style`
				 * alongside the root, and the legacy store never gains one.
				 */
				if ( undefined !== stored?.style?.[ key ] ) {
					return;
				}

				// Only a root that still looks like a projection is replaced.
				if ( ! narrower.some( ( bucket ) => bucket?.[ key ] === stored[ key ] ) ) {
					return;
				}

				changes[ key ] = base[ key ];
			} );

			if ( ! Object.keys( changes ).length ) {
				return;
			}

			__unstableMarkNextChangeAsNotPersistent();
			updateBlockAttributes( clientId, changes );
			// Mount only: the promotion makes the root equal the base, so a
			// second pass finds nothing to do.
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [] );

		return <BlockEdit { ...props } />;
	};
}, 'withMarkupBackedBasePromotion' );
