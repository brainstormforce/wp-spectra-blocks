/**
 * Whether core's per-viewport editing is active right now.
 *
 * WHY THIS FILE EXISTS
 *
 * The write router needs to know two things to pick the layer an edit belongs
 * to: which device is previewed, and whether core's "Responsive styles" mode is
 * on. The first is public — `select( 'core/editor' ).getDeviceType()`. The
 * second is not, and that gap was a real bug: routing on the device alone sent
 * every root edit made while previewing Tablet into `style['@tablet']`, even
 * with Responsive styles OFF, where core itself writes the base layer. Core's
 * own Typography panel was silently re-routed — the user changed a font size
 * expecting a global change, saw nothing happen on the canvas, and gained a
 * tablet override they never asked for.
 *
 * WHY IT IS NOT A SELECTOR
 *
 * `isResponsiveEditing()` and `getSelectedBlockStyleState()` live in
 * `block-editor/src/store/private-selectors.js`, registered through
 * `registerPrivateSelectors` and reachable only via `unlock()` — which throws
 * for anything that is not a core package
 * (`private-apis/src/implementation.ts`). `useBlockStyleState` and
 * `BlockStyleStateProvider` are not exported from the package at all, and there
 * is no filter or editor setting that exposes the state. So the answer has to
 * be inferred.
 *
 * HOW IT IS INFERRED
 *
 * From slots core renders CONDITIONALLY. A fill placed in such a slot renders
 * only when core has decided to render the slot, so mounting a component there
 * and letting it report its own presence turns core's render decision into a
 * readable, reactive signal:
 *
 *   - `BlockControls` group `style-state` — a public group
 *     (`block-controls/groups.js`) whose slot `block-toolbar/index.js` renders
 *     only when `isResponsiveEditing() && hasViewportBlockStyleState( … )`.
 *     That is an exact mirror of the private selector.
 *
 *   - `InspectorControls` group `styles` — rendered by the Styles tab and by
 *     `StyleInspectorSlots`, but NOT by `StyleStateInspectorSlots`. Present
 *     exactly when the responsive view is absent.
 *
 * Both are combined, because neither is sound alone. The toolbar unmounts while
 * the user is typing (`isBlockInterfaceHidden`), which on its own would flip the
 * router back to the base layer mid-edit. The inspector probe cannot tell
 * "responsive view" from "inspector closed" or "Settings tab open" — harmless,
 * since a narrow device with the inspector shut has no visible control to
 * mis-route, but not something to rely on by itself.
 *
 * This replaces DOM sniffing for `.editor-preview-dropdown.is-responsive-editing`
 * (`coreResponsiveEditingActive()` in ./constants). That check still has a job —
 * it is synchronous and works outside React, which the hint in
 * control-injection.js needs — but it is not reactive, so a router that read it
 * would keep routing to the old layer until something else forced a re-render.
 *
 * NOTHING HERE APPLIES BELOW WORDPRESS 7.1
 *
 * Both probes are inert on older core, and the asymmetry between them is why
 * that has to be explicit rather than assumed:
 *
 *   - `InspectorControls` group `styles` has existed since Gutenberg #47105
 *     (January 2023), so on WordPress 6.6 that probe MOUNTS normally.
 *   - `BlockControls` group `style-state` arrived with #80037 (July 2026), so on
 *     6.6 it does not exist: the fill logs `Unknown BlockControls group` and
 *     renders nothing.
 *
 * Left ungated, the combination reads as "toolbar absent, normal view present"
 * — which this file interprets as "not editing a viewport state", forever. The
 * router would then send every edit to the base layer on every pre-7.1 site,
 * breaking Spectra's own device buttons completely, and log a warning per block
 * while doing it.
 *
 * So the capability is checked first, and below 7.1 the hook reports false and
 * renders no probes at all. The router has its own guard for the same reason
 * (see helpers.js): reporting false must not be mistaken for "responsive
 * editing is off", because on those versions the concept does not exist and the
 * device is the only signal there is.
 *
 * @since 1.0.7
 */

/**
 * External dependencies.
 */
import { useEffect, useState, useCallback } from '@wordpress/element';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies.
 */
import {
	coreResponsiveEditingActive,
	coreViewportStatesAreIndependent,
	BREAKPOINT_TYPE_MAP,
	DESKTOP,
} from './constants';

/**
 * The answer, shared by every block, published only by blocks that can see it.
 *
 * WHY THIS IS SHARED RATHER THAN PER-BLOCK
 *
 * The mode is a property of the EDITOR, but the probes can only observe it from
 * inside the SELECTED block: core gates both fills on the block-edit context —
 * `useBlockControlsFill()` returns null unless `mayDisplayControlsKey` is set,
 * and `InspectorControlsFill` bails on the same key. So in an unselected block
 * neither probe mounts, and the inspector term — a NEGATIVE test — cannot tell
 * "core dropped the slot" from "I am not the selected block".
 *
 * Read per-block, that made every unselected block conclude the mode was ON
 * whenever the device was narrow, which broke two things: the reset path took
 * whichever value happened to be written last (and unselected blocks outnumber
 * the selected one, so it usually took the wrong one), and unselected blocks
 * routed to `@tablet` while the block being edited routed to base.
 *
 * So exactly one block reports — the one whose probes can actually mount — and
 * every other block subscribes to that answer. `null` means no block has
 * reported yet, which is different from `false` and must stay distinguishable:
 * see `getResponsiveEditingActive()`.
 *
 * @type {?boolean}
 */
let sharedState = null;

/**
 * Subscribers waiting on `sharedState`.
 *
 * A plain module variable would be enough for the non-React reset path, but not
 * for the blocks that consume the value: they have to RE-RENDER when it changes,
 * or an unselected block keeps routing to the layer that was current when it
 * last rendered.
 *
 * @type {Set<Function>}
 */
const subscribers = new Set();

/**
 * Publish a new shared answer and wake every subscriber.
 *
 * @since 1.0.7
 * @param {boolean} value The new answer.
 * @return {void}
 */
const publishSharedState = ( value ) => {
	if ( value === sharedState ) {
		return;
	}

	sharedState = value;
	subscribers.forEach( ( notify ) => notify( value ) );
};

/**
 * Subscribe to the shared answer.
 *
 * @since 1.0.7
 * @param {Function} notify Called with the new value on every change.
 * @return {Function} Unsubscribe.
 */
const subscribeToSharedState = ( notify ) => {
	subscribers.add( notify );

	return () => {
		subscribers.delete( notify );
	};
};

/**
 * Whether per-viewport editing is active, for callers outside React.
 *
 * The live DOM is consulted FIRST. Core's "Responsive styles" toggle flips a
 * class on its View dropdown without changing any attribute, so no block
 * re-renders and the value the probes last published can lag behind what the
 * author is looking at. The one caller here is the reset handler, which runs at
 * click time — and at click time the class is the truth.
 *
 * Measured on 7.1: toggle the option on, switch device and back, reselect the
 * block, press Reset all at Desktop. The published value still said OFF while
 * the dropdown said ON, so the reset took the "clear every viewport" branch
 * meant for the option being off, and a block's tablet and mobile values were
 * deleted by a reset aimed at desktop.
 *
 * The published value remains the fallback for contexts that render no preview
 * dropdown at all, and `false` is still never assumed — that would claim the
 * base layer during a reset performed right after selecting a block in the
 * responsive view, which writes to the wrong layer.
 *
 * @since 1.0.7
 * @return {boolean} True while core is editing a viewport style state.
 */
export const getResponsiveEditingActive = () => {
	if ( ! coreViewportStatesAreIndependent() ) {
		return false;
	}

	if ( typeof document !== 'undefined' && document.querySelector( '.editor-preview-dropdown' ) ) {
		return coreResponsiveEditingActive();
	}

	return null === sharedState ? coreResponsiveEditingActive() : sharedState;
};

/**
 * Reports its own mount state to the parent. Renders no UI.
 *
 * @since 1.0.7
 * @param {Object}   props
 * @param {Function} props.onChange Called true on mount, false on unmount.
 * @return {null} Nothing.
 */
const Probe = ( { onChange } ) => {
	useEffect( () => {
		onChange( true );
		return () => onChange( false );
	}, [ onChange ] );

	return null;
};

/**
 * Track whether core is editing a viewport style state.
 *
 * @since 1.0.7
 * @param {string} deviceType Core device name, from `getDeviceType()`.
 * @param {string} clientId   The block's client id, used to tell whether this
 *                            block is the one whose probes can mount.
 * @return {{isResponsiveEditingActive: boolean, ResponsiveEditingProbes: Function}}
 *         The flag, and the probes the caller MUST render — the flag never
 *         becomes true without them.
 */
export const useResponsiveEditing = ( deviceType = DESKTOP, clientId ) => {
	const [ toolbarShowsStyleState, setToolbarShowsStyleState ] = useState( false );
	const [ inspectorShowsNormalView, setInspectorShowsNormalView ] = useState( false );

	// Stable identities, so the probes' effects do not re-run every render.
	const onToolbarProbe = useCallback( ( value ) => {
		setToolbarShowsStyleState( value );
	}, [] );

	const onInspectorProbe = useCallback( ( value ) => {
		setInspectorShowsNormalView( value );
	}, [] );

	/*
	 * The device guard belongs in the second term, not the first.
	 *
	 * The toolbar probe is a positive signal and stands on its own — it mirrors
	 * core's condition exactly. The inspector probe is a NEGATIVE one, and
	 * absence has more than one cause: the responsive view, a closed inspector,
	 * or the Settings tab. Requiring a narrow device rules out the reading that
	 * would otherwise be wrong on Desktop, where there is no viewport state to
	 * edit in the first place.
	 */
	const isNarrowDevice = 'base' !== ( BREAKPOINT_TYPE_MAP[ deviceType ] ?? 'base' );

	// Below 7.1 there is no viewport-state mode to detect, and the probes cannot
	// detect it — see the note at the top of this file.
	const isSupported = coreViewportStatesAreIndependent();

	/*
	 * Whether THIS block is the one that can observe.
	 *
	 * Core gates both probe fills on the block-edit context, so they mount only
	 * in the selected block (or the first of a multi-selection, which is what
	 * core itself treats as controls-bearing). Everywhere else the probes report
	 * nothing, and "nothing" is indistinguishable from "core dropped the slot" —
	 * the reading that made unselected blocks believe the mode was on.
	 */
	const canObserve = useSelect(
		( select ) => {
			if ( ! clientId ) {
				return false;
			}

			const store = select( 'core/block-editor' );

			return Boolean(
				store?.isBlockSelected?.( clientId ) ||
					store?.isFirstMultiSelectedBlock?.( clientId )
			);
		},
		[ clientId ]
	);

	/*
	 * What the live View dropdown says, where there is one. Core flips a class
	 * on it as "Responsive styles" toggles, so it is the truth at any instant —
	 * the same source the reset path reads first (`getResponsiveEditingActive`).
	 * `null` where the editor renders no dropdown (some embedded editors), in
	 * which case the probes stand alone as before.
	 */
	const dropdownSaysActive =
		typeof document !== 'undefined' && document.querySelector( '.editor-preview-dropdown' )
			? coreResponsiveEditingActive()
			: null;

	/*
	 * What this block's own probes say. Meaningful only while it can observe.
	 *
	 * The inspector probe is a negative signal, and with the sidebar CLOSED it
	 * is absent for a reason that has nothing to do with the responsive view —
	 * read alone, a narrow device with the sidebar closed looked like the mode
	 * being on, and an edit made from the toolbar with the option off routed to
	 * `@tablet` instead of base. The dropdown settles it: the negative reading
	 * only counts while the dropdown does not say the option is off.
	 */
	const observed =
		toolbarShowsStyleState ||
		( isNarrowDevice && ! inspectorShowsNormalView && false !== dropdownSaysActive );

	// The published answer, for blocks that cannot observe for themselves.
	const [ shared, setShared ] = useState( sharedState );

	useEffect( () => subscribeToSharedState( setShared ), [] );

	/*
	 * Publish from the observing block only, and through an effect rather than
	 * inline, so a render React discards cannot leave behind a value no mounted
	 * component agrees with. This is also what the non-React reset path reads,
	 * so it now sees the selected block's answer instead of whichever block
	 * happened to render last.
	 */
	useEffect( () => {
		if ( canObserve ) {
			publishSharedState( observed );
		}
	}, [ canObserve, observed ] );

	/*
	 * An observer trusts itself; everyone else trusts the observer. Before any
	 * block has reported, fall back to the DOM check rather than to `false` —
	 * `false` would claim the base layer on the first edit after a reload in the
	 * responsive view, which is the mistake that writes to the wrong layer.
	 */
	let resolved;

	if ( canObserve ) {
		resolved = observed;
	} else if ( false === dropdownSaysActive ) {
		// The dropdown says the option is off; a published "on" is stale.
		resolved = false;
	} else if ( null !== shared ) {
		resolved = shared;
	} else {
		resolved = coreResponsiveEditingActive();
	}

	const isResponsiveEditingActive = isSupported && resolved;

	const ResponsiveEditingProbes = useCallback(
		() => {
			// No probes below 7.1: `style-state` is not a registered
			// BlockControls group there, and filling it would log a warning on
			// every block for a signal that cannot exist anyway.
			if ( ! isSupported ) {
				return null;
			}

			return (
				<>
					<BlockControls group="style-state">
						<Probe onChange={ onToolbarProbe } />
					</BlockControls>
					<InspectorControls group="styles">
						<Probe onChange={ onInspectorProbe } />
					</InspectorControls>
				</>
			);
		},
		[ isSupported, onToolbarProbe, onInspectorProbe ]
	);

	return { isResponsiveEditingActive, ResponsiveEditingProbes };
};
