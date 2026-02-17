/**
 * Backward Compatibility for Responsive Controls.
 *
 * This module provides on-the-fly support for legacy root-level attributes
 * to the new responsiveControls structure in the block editor for backward compatibility.
 *
 * @since x.x.x
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
import { hasValue } from './helpers';

/**
 * Higher-order component that handles backward compatibility of legacy attributes in the editor.
 *
 * When a block is loaded, it checks if legacy attributes exist at the root level
 * and if they are missing from all responsive breakpoints. If so, it maps
 * them to the 'lg' (desktop) breakpoint for backward compatibility.
 *
 * @since x.x.x
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
