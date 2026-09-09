/**
 * Legacy root-attribute mapping for the pre-7.1 editor.
 *
 * SNAPSHOT — verbatim from 1.0.6 (`origin/dev`), import paths aside.
 *
 * #732 rewrote the shared version of this to map into core's `style` and to
 * CLEAR the source root attribute in the same patch. On a WordPress without
 * viewport states that is wrong in a visible way: the classic editor reads
 * `responsiveControls`, so the value lands somewhere it cannot see while the
 * attribute it used to read is wiped, and the control empties out. (The front
 * end still renders it — PHP hydrates its store from `style` on every version —
 * so this is editor-state divergence rather than lost content, but it is still a
 * change to versions that are supposed to be left alone.)
 *
 * Scope is narrow: one block, two attributes. Kept anyway, because "the old path
 * is untouched" has to be true in general and not just where it is convenient.
 *
 * @since 1.0.7
 */

/**
 * External dependencies.
 */

/**
 * External dependencies.
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { BACKWARD_COMPATIBILITY_ATTRIBUTES } from './constants';
import { hasValue } from '../../utils/helpers';

/**
 * Higher-order component that handles backward compatibility of legacy attributes in the editor.
 *
 * When a block is loaded, it checks if legacy attributes exist at the root level
 * and if they are missing from all responsive breakpoints. If so, it maps
 * them to the 'lg' (desktop) breakpoint for backward compatibility.
 *
 * @since 1.0.7
 * @param {Function} BlockEdit Original block edit component.
 * @return {Function} Wrapped block edit component.
 */
export const withBackwardCompatibility = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { attributes, setAttributes, name } = props;

		// Check if this block has any registered legacy attributes for backward compatibility.
		const attributesToMaintain = BACKWARD_COMPATIBILITY_ATTRIBUTES[ name ];

		useEffect( () => {
			if ( ! attributesToMaintain || attributesToMaintain.length === 0 ) {
				return;
			}

			const { responsiveControls = {} } = attributes;
			let modified = false;
			let newLg = null;

			attributesToMaintain.forEach( ( attr ) => {
				const val = attributes[ attr ];
				// Only map if root attribute exists and has a meaningful value.
				if ( ! hasValue( val ) ) {
					return;
				}

				// Check if this attribute is already defined in ANY responsive device.
				const existsResponsively = [ 'lg', 'md', 'sm' ].some( ( device ) => {
					return hasValue( responsiveControls?.[ device ]?.[ attr ] );
				} );

				// If root exists but it's not used in any responsive device, map it to LG (Desktop).
				if ( ! existsResponsively ) {
					if ( ! newLg ) {
						newLg = { ...( responsiveControls.lg || {} ) };
					}
					newLg[ attr ] = val;
					modified = true;
				}
			} );

			if ( modified ) {
				setAttributes( {
					responsiveControls: {
						...responsiveControls,
						lg: newLg,
					},
				} );
			}
		}, [] ); // Run only once on block load.

		return <BlockEdit { ...props } />;
	};
}, 'withBackwardCompatibility' );
