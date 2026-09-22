/**
 * Backward Compatibility for Responsive Controls.
 *
 * This module provides on-the-fly support for legacy root-level attributes
 * to the new responsiveControls structure in the block editor for backward compatibility.
 *
 * @since 1.0.9
 */

/**
 * External dependencies.
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { BACKWARD_COMPATIBILITY_ATTRIBUTES } from './constants';
import { hasValue, isObject } from './helpers';

/**
 * Higher-order component that handles backward compatibility of legacy attributes in the editor.
 *
 * When a block is loaded, it checks if legacy attributes exist at the root level
 * and if they are missing from all responsive breakpoints. If so, it maps
 * them to the 'base' layer for backward compatibility.
 *
 * @since 1.0.9
 * @param {Function} BlockEdit Original block edit component.
 * @return {Function} Wrapped block edit component.
 */
export const withBackwardCompatibility = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { attributes, setAttributes, name } = props;

		// Check if this block has any registered legacy attributes for backward compatibility.
		const attributesToMaintain = BACKWARD_COMPATIBILITY_ATTRIBUTES[ name ];

		// From the component's own registry — the global `dispatch()` would mark
		// the MAIN editor's next change inside a nested registry (pattern
		// previews, Style Book) and silently merge an unrelated undo step.
		const { __unstableMarkNextChangeAsNotPersistent } = useDispatch( 'core/block-editor' );

		useEffect( () => {
			if ( ! attributesToMaintain || attributesToMaintain.length === 0 ) {
				return;
			}

			const { responsiveControls = {}, style } = attributes;
			const mapped = {};

			attributesToMaintain.forEach( ( attr ) => {
				const val = attributes[ attr ];
				// Only map if root attribute exists and has a meaningful value.
				if ( ! hasValue( val ) ) {
					return;
				}

				/*
				 * Already present anywhere in the responsive system? `style` is
				 * where values live now — the parse-time migration empties the
				 * store, so checking only the store re-mapped the attribute on
				 * EVERY open: the write refilled the store, the next parse
				 * emptied it again, and the post opened dirty forever.
				 *
				 * That holds for every block this list can currently name. A
				 * markup-backed block (`savesAttributesToMarkup()`) keeps its
				 * store on purpose — only its narrower states are migrated, and
				 * its base is promoted after validation rather than into
				 * `style` — so should a `core/*` entry ever join
				 * `BACKWARD_COMPATIBILITY_ATTRIBUTES`, `inStore` below would
				 * stay true where the others go false.
				 */
				const inStyle = [ style, style?.[ '@tablet' ], style?.[ '@mobile' ] ].some(
					( layer ) => hasValue( layer?.[ attr ] )
				);
				const inStore = [ 'base', '@tablet', '@mobile' ].some( ( device ) =>
					hasValue( responsiveControls?.[ device ]?.[ attr ] )
				);

				if ( ! inStyle && ! inStore ) {
					mapped[ attr ] = val;
				}
			} );

			if ( ! Object.keys( mapped ).length ) {
				return;
			}

			/*
			 * Map into the base layer where the system reads it now — the root
			 * of `style`, not the retired store — and clear the source
			 * attributes in the same patch, so a value the user later resets
			 * cannot resurrect from the untouched root on the next open. Like
			 * the parse-time migration, this is a representation change rather
			 * than an edit: it is not marked persistent, so opening the post
			 * stays clean, and the shape persists whenever the user saves for
			 * their own reasons.
			 */
			const cleared = {};
			Object.keys( mapped ).forEach( ( attr ) => {
				cleared[ attr ] = undefined;
			} );

			__unstableMarkNextChangeAsNotPersistent();
			setAttributes( {
				...cleared,
				style: { ...( isObject( style ) ? style : {} ), ...mapped },
			} );
		}, [] ); // Run only once on block load.

		return <BlockEdit { ...props } />;
	};
}, 'withBackwardCompatibility' );
