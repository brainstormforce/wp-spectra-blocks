/**
 * `ToolsPanelItem`, told about the viewport it is being shown for.
 *
 * WHY THIS EXISTS
 *
 * Core decides three things from an item's `hasValue` callback: whether the
 * reset dot appears, whether "Reset all" is offered at all, and — for an item
 * without `isShownByDefault` — whether the control is in the panel body.
 *
 * Every one of Spectra's callbacks answers by reading a ROOT attribute:
 *
 *     hasValue={ () => !! minHeight }
 *
 * That was right while the editor rewrote root attributes to match the previewed
 * device. #732 removed that projection layer — correctly, because rewriting
 * stored attributes in order to display something corrupted content — and on
 * WordPress 7.1 per-viewport values now live in `style['@tablet']` and
 * `style['@mobile']`, with the root attributes carrying base alone.
 *
 * So a value the author set only for Tablet reads as "not set" while they are
 * looking at Tablet. Measured on 7.1, `spectra/icon` with
 * `style['@tablet'].size = 48px` and no base: the callback returns false at every
 * device, and core DISABLES the whole Dimensions "Reset all" —
 * `aria-disabled="true"` — so the value cannot be cleared from that panel.
 *
 * WHAT THIS DOES
 *
 * Two halves, because offering a reset and performing one are separate problems.
 *
 * 1. `hasValue` also answers for the previewed state, so core offers the reset.
 * 2. The reset, when it comes, clears that state — because core's own step only
 *    clears ROOT attributes, and the root was already empty.
 *
 * The second half has to happen HERE rather than in the extension's reset
 * handler. That handler runs from a `requestAnimationFrame` scheduled by a
 * capture-phase click listener, so it fires BEFORE core's `resetAll()` under
 * React's batching — measured: the handler saw an empty scope, then core
 * performed the reset a moment later. Doing the work inside the callbacks core
 * itself invokes removes the ordering question entirely.
 *
 * Rather than rewrite 47 bespoke callbacks across 20 blocks, this wraps the item
 * once. The item already declares the attributes it owns: `resetAllFilter`
 * returns an object keyed by exactly those. Reading the declaration the item
 * already makes keeps the two from drifting, which a second hand-written list
 * beside each callback would not.
 *
 * The wrapper only ever makes `hasValue` MORE true, and only ever clears keys an
 * item asked to clear. No item loses a reset it used to have.
 *
 * BELOW 7.1 this is inert: without core's viewport states there are no state
 * layers to consult, and the pre-7.1 projection layer keeps the root attributes
 * pointed at the previewed device, which is what the callbacks already read.
 *
 * @since 1.0.7
 */

/**
 * External dependencies.
 */
import { __experimentalToolsPanelItem as CoreToolsPanelItem } from '@wordpress/components';
import { useCallback, useRef } from '@wordpress/element';
import { select as dataSelect, useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies.
 *
 * `constants` only, deliberately. `utils/helpers.js` imports the extension's
 * entry point, so reaching it from a COMPONENT closes a cycle and the bundle
 * throws `Cannot access 'B' before initialization` — the trap documented on
 * `helpers/responsive-preview.js`.
 */
import {
	BREAKPOINT_TYPE_MAP,
	coreResponsiveEditingActive,
	coreViewportStatesAreIndependent,
} from '../../extensions/responsive-controls/utils/constants';

/**
 * The attributes an item resets, from the declaration it already makes.
 *
 * @since 1.0.7
 * @param {Function} resetAllFilter The item's `resetAllFilter` prop.
 * @return {Array} Attribute names, empty when the item declares none.
 */
const ownedAttributes = ( resetAllFilter ) => {
	if ( typeof resetAllFilter !== 'function' ) {
		return [];
	}

	try {
		// Called with an empty object: the callbacks in these blocks ignore their
		// argument, and one that spreads it still yields the keys it sets.
		const cleared = resetAllFilter( {} );

		return cleared && typeof cleared === 'object' ? Object.keys( cleared ) : [];
	} catch ( e ) {
		// A filter that cannot run tells us nothing, which loses the augmentation
		// for this item rather than breaking the panel.
		return [];
	}
};

/**
 * The `style` state key for the viewport being edited, or nothing.
 *
 * The same pairing the write router uses: the device alone is not enough. With
 * "Responsive styles" off core binds its panels to the base layer whatever
 * device is previewed, so there is no state in play and the root attribute is
 * the whole answer.
 *
 * @since 1.0.7
 * @param {string} deviceType The previewed device.
 * @return {string|undefined} `@tablet`, `@mobile`, or undefined for base.
 */
const editedStateKey = ( deviceType ) => {
	if ( ! coreViewportStatesAreIndependent() || ! coreResponsiveEditingActive() ) {
		return undefined;
	}

	const breakpoint = BREAKPOINT_TYPE_MAP[ deviceType ] || 'base';

	return 'base' === breakpoint ? undefined : breakpoint;
};

/**
 * `ToolsPanelItem` that sees, and resets, the previewed viewport.
 *
 * Drop-in for core's: every prop is passed through, with `hasValue`,
 * `onDeselect` and `resetAllFilter` wrapped.
 *
 * @since 1.0.7
 * @param {Object} props The core component's props.
 * @return {Element} The wrapped item.
 */
const ToolsPanelItem = ( props ) => {
	const { hasValue, resetAllFilter, onDeselect, panelId } = props;

	const deviceType = useSelect( ( select ) => select( 'core/editor' )?.getDeviceType?.(), [] );

	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	/*
	 * Read imperatively, and keep the previewed device in a ref.
	 *
	 * `onDeselect` and `resetAllFilter` are handed to core, which REGISTERS them
	 * in an effect keyed on their identity. A callback whose identity changed
	 * whenever the store did made core unregister and re-register constantly, and
	 * a reset landing in that window found no filter registered at all — measured:
	 * with the first version of this wrapper, "Reset all" cleared nothing, not
	 * even the base layer core owns.
	 *
	 * So the wrappers below depend only on the item's own callback, and everything
	 * volatile is read at call time instead.
	 */
	const deviceRef = useRef( deviceType );
	deviceRef.current = deviceType;

	const panelIdRef = useRef( panelId );
	panelIdRef.current = panelId;

	const readStyle = useCallback(
		() => panelIdRef.current
			? dataSelect( 'core/block-editor' ).getBlockAttributes( panelIdRef.current )?.style
			: undefined,
		[]
	);

	const augmentedHasValue = useCallback(
		() => {
			// The item's own answer always wins when it is yes.
			if ( hasValue?.() ) {
				return true;
			}

			const stateKey = editedStateKey( deviceRef.current );

			if ( ! stateKey ) {
				return false;
			}

			const state = readStyle()?.[ stateKey ];

			if ( ! state || typeof state !== 'object' ) {
				return false;
			}

			return ownedAttributes( resetAllFilter ).some(
				( attribute ) => undefined !== state[ attribute ]
			);
		},
		[ hasValue, resetAllFilter, readStyle ]
	);

	/**
	 * Drop this item's attributes from the state being edited.
	 *
	 * Reads current attributes at call time, so several items resetting in turn
	 * during "Reset all" each build on the last rather than overwriting it.
	 *
	 * Not marked non-persistent: unlike the scratch writes elsewhere in this
	 * extension, this IS the author's edit — the change they asked for when they
	 * pressed reset — and it belongs in the undo stack with core's own step.
	 *
	 * @since 1.0.7
	 * @return {void}
	 */
	const clearEditedState = useCallback(
		() => {
			const stateKey = editedStateKey( deviceRef.current );
			const clientId = panelIdRef.current;

			if ( ! stateKey || ! clientId ) {
				return;
			}

			const style = readStyle();
			const state = style?.[ stateKey ];

			if ( ! state || typeof state !== 'object' ) {
				return;
			}

			const owned = ownedAttributes( resetAllFilter ).filter(
				( attribute ) => undefined !== state[ attribute ]
			);

			if ( ! owned.length ) {
				return;
			}

			const nextState = { ...state };
			owned.forEach( ( attribute ) => {
				delete nextState[ attribute ];
			} );

			const nextStyle = { ...style };

			// An emptied state is removed rather than left as `{}`, matching what
			// the extension's own reset handler does with a state it has drained.
			if ( Object.keys( nextState ).length ) {
				nextStyle[ stateKey ] = nextState;
			} else {
				delete nextStyle[ stateKey ];
			}

			updateBlockAttributes( clientId, { style: nextStyle } );
		},
		[ readStyle, resetAllFilter, updateBlockAttributes ]
	);

	/*
	 * Core performs a reset through one of these two. Both keep the original
	 * behaviour — the item's own callback is invoked with its own arguments and
	 * its result passed straight back — and add the state clearing beside it.
	 */
	/*
	 * Deferred, not immediate.
	 *
	 * Core calls `resetAllFilter` while REDUCING every item's filter into the
	 * attributes it is about to set. Dispatching a store update from inside that
	 * reduce re-renders mid-computation, and core's own reset then does nothing at
	 * all — measured: with an immediate dispatch, "Reset all" left even the base
	 * layer untouched, a regression on behaviour that already worked.
	 *
	 * A frame later core has finished and committed. Each item schedules its own
	 * clear, and because each reads current attributes when it runs, several items
	 * resetting together build on one another rather than overwriting.
	 */
	const deferClear = useCallback(
		() => {
			if ( typeof window === 'undefined' ) {
				return;
			}

			window.requestAnimationFrame( () => clearEditedState() );
		},
		[ clearEditedState ]
	);

	const wrappedOnDeselect = useCallback(
		( ...args ) => {
			const result = onDeselect?.( ...args );

			deferClear();

			return result;
		},
		[ onDeselect, deferClear ]
	);

	const wrappedResetAllFilter = useCallback(
		( ...args ) => {
			const result = resetAllFilter?.( ...args );

			deferClear();

			return result;
		},
		[ resetAllFilter, deferClear ]
	);

	return (
		<CoreToolsPanelItem
			{ ...props }
			hasValue={ augmentedHasValue }
			{ ...( onDeselect ? { onDeselect: wrappedOnDeselect } : {} ) }
			{ ...( resetAllFilter ? { resetAllFilter: wrappedResetAllFilter } : {} ) }
		/>
	);
};

export default ToolsPanelItem;
