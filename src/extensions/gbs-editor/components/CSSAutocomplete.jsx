/**
 * CSSAutocomplete — textarea wrapper with inline ghost text suggestion.
 *
 * While the user types, the best-matching suggestion is shown as pale ghost
 * text after the cursor. Pressing Tab or Enter accepts it; Escape dismisses.
 *
 * @since x.x.x
 */

import {
	useState,
	useRef,
	useCallback,
	useEffect,
	forwardRef,
} from '@wordpress/element';
import { getCSSCompletions } from '../utils/cssCompletions.js';

function useMergedRef( forwardedRef ) {
	const innerRef = useRef( null );
	const setRef = useCallback(
		( node ) => {
			innerRef.current = node;
			if ( typeof forwardedRef === 'function' ) {
				forwardedRef( node );
			} else if ( forwardedRef ) {
				forwardedRef.current = node;
			}
		},
		[ forwardedRef ]
	);
	return [ innerRef, setRef ];
}

/**
 * @since x.x.x
 */
const CSSAutocomplete = forwardRef(
	(
		{
			value,
			onChange,
			onKeyDown: outerKeyDown,
			className,
			isKeyframe = false,
			...rest
		},
		forwardedRef
	) => {
		const [ textareaRef, setTextareaRef ] = useMergedRef( forwardedRef );
		const mirrorRef = useRef( null );
		const triggerRef = useRef( null );

		// active = { ghost, suggestion, cursorEnd } | null
		const [ active, setActive ] = useState( null );

		// Live ref so the capture-phase handler always reads the current active state
		// without a stale closure.
		const liveRef = useRef( null );
		liveRef.current = active;

		const dismiss = useCallback( () => {
			setActive( null );
			triggerRef.current = null;
		}, [] );

		const MIRROR_PROPS = [
			'fontFamily',
			'fontSize',
			'fontWeight',
			'letterSpacing',
			'lineHeight',
			'paddingTop',
			'paddingRight',
			'paddingBottom',
			'paddingLeft',
			'borderTopWidth',
			'borderRightWidth',
			'borderBottomWidth',
			'borderLeftWidth',
			'boxSizing',
		];

		const copyMetrics = useCallback( ( mirror, textarea ) => {
			const s = window.getComputedStyle( textarea );
			MIRROR_PROPS.forEach( ( p ) => {
				mirror.style[ p ] = s[ p ];
			} );
		}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

		// Mirror callback ref — copy textarea text-metrics on mount so mirror text
		// occupies identical pixel positions, then sync initial scroll offset.
		const setMirrorRef = useCallback(
			( node ) => {
				mirrorRef.current = node;
				if ( node && textareaRef.current ) {
					copyMetrics( node, textareaRef.current );
					node.scrollTop = textareaRef.current.scrollTop;
				}
			},
			[ copyMetrics ]
		); // eslint-disable-line react-hooks/exhaustive-deps

		// Re-copy metrics when the textarea is resized (e.g. user drags handle).
		useEffect( () => {
			const el = textareaRef.current;
			if ( ! el ) {
				return;
			}
			const ro = new window.ResizeObserver( () => {
				if ( mirrorRef.current ) {
					copyMetrics( mirrorRef.current, el );
				}
			} );
			ro.observe( el );
			return () => ro.disconnect();
		}, [ copyMetrics ] ); // eslint-disable-line react-hooks/exhaustive-deps

		// Native capture-phase Tab/Enter interceptor registered once on the textarea
		// DOM node so it fires before any ancestor capture listeners (e.g. WP
		// Dropdown's focus-trap), preventing them from stealing focus before the
		// suggestion is applied.
		const applyActiveRef = useRef( null );
		useEffect( () => {
			const el = textareaRef.current;
			if ( ! el ) {
				return;
			}
			const handler = ( e ) => {
				if ( e.key !== 'Tab' && e.key !== 'Enter' ) {
					return;
				}
				const state = liveRef.current;
				if ( state ) {
					e.preventDefault();
					e.stopPropagation();
					e.stopImmediatePropagation();
					applyActiveRef.current?.( state );
				}
			};
			el.addEventListener( 'keydown', handler, true );
			return () => el.removeEventListener( 'keydown', handler, true );
		}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

		const computeGhost = useCallback(
			( text, cursor ) => {
				const gbsVars = window.__spectraGBSVarNames || [];
				const lineStart = text.lastIndexOf( '\n', cursor - 1 ) + 1;
				const lineText = text.slice( lineStart, cursor );
				const curInLine = cursor - lineStart;

				const result = getCSSCompletions(
					lineText,
					curInLine,
					gbsVars,
					isKeyframe
				);

				if ( result.suggestions.length === 0 ) {
					dismiss();
					return;
				}

				const triggerStart = lineStart + result.trigger.start;
				const triggerEnd = lineStart + result.trigger.end;

				triggerRef.current = { start: triggerStart, end: triggerEnd };

				// Use the first suggestion whose insert text starts with what's already typed.
				const typedSoFar = text.slice( triggerStart, cursor );
				const match = result.suggestions.find( ( s ) =>
					s.insert.startsWith( typedSoFar )
				);

				if ( ! match ) {
					dismiss();
					return;
				}

				const ghostText = match.insert.slice( typedSoFar.length );
				if ( ! ghostText ) {
					dismiss();
					return;
				}

				setActive( {
					ghost: ghostText,
					suggestion: match,
					cursorEnd: cursor,
				} );
			},
			[ isKeyframe, dismiss ]
		);

		const handleChange = ( e ) => {
			onChange( e );
			const { value: text, selectionStart: cursor } = e.target;
			computeGhost( text, cursor );
		};

		const applyActive = useCallback(
			( state ) => {
				const trigger = triggerRef.current;
				if ( ! trigger ) {
					return;
				}

				const before = value.slice( 0, trigger.start );
				const after = value.slice( trigger.end );
				const newValue = before + state.suggestion.insert + after;
				const newCursor =
					trigger.start + state.suggestion.insert.length;

				onChange( { target: { value: newValue } } );
				dismiss();

				window.requestAnimationFrame( () => {
					if ( textareaRef.current ) {
						textareaRef.current.focus();
						textareaRef.current.setSelectionRange(
							newCursor,
							newCursor
						);
					}
				} );
			},
			[ value, onChange, textareaRef, dismiss ]
		);
		applyActiveRef.current = applyActive;

		const handleKeyDown = ( e ) => {
			if ( active ) {
				if ( e.key === 'Tab' || e.key === 'Enter' ) {
					e.preventDefault();
					// Stop propagation so WP's modal focus-trap doesn't move focus
					// away from the textarea immediately after accepting a suggestion.
					e.stopPropagation();
					applyActive( active );
					return;
				}
				if ( e.key === 'Escape' ) {
					// Stop propagation so the GBS modal doesn't close while a ghost
					// suggestion is active.
					e.stopPropagation();
					dismiss();
					return;
				}
			}
			outerKeyDown?.( e );
		};

		// Delay dismiss on blur so a click elsewhere doesn't race with applyActive.
		const handleBlur = () => setTimeout( dismiss, 150 );

		// Dismiss when the cursor moves to a position other than where the ghost ends
		// (e.g. user clicks mid-line).
		const handleSelect = () => {
			if ( active && textareaRef.current ) {
				if ( textareaRef.current.selectionStart !== active.cursorEnd ) {
					dismiss();
				}
			}
		};

		// Keep mirror scroll in sync with textarea scroll.
		const syncMirrorScroll = () => {
			if ( mirrorRef.current && textareaRef.current ) {
				mirrorRef.current.scrollTop = textareaRef.current.scrollTop;
			}
		};

		return (
			<div className="spectra-css-ac">
				<textarea
					ref={ setTextareaRef }
					className={ className }
					value={ value }
					onChange={ handleChange }
					onKeyDown={ handleKeyDown }
					onBlur={ handleBlur }
					onSelect={ handleSelect }
					onScroll={ syncMirrorScroll }
					{ ...rest }
				/>
				{ active && (
					<div
						ref={ setMirrorRef }
						className="spectra-css-ac__mirror"
						aria-hidden="true"
					>
						{ /* Transparent spacer — occupies the same space as typed text so
					     the ghost span starts exactly at the cursor position. */ }
						<span>{ value.slice( 0, active.cursorEnd ) }</span>
						<span className="spectra-css-ac__ghost-text">
							{ active.ghost }
						</span>
						<span>{ value.slice( active.cursorEnd ) }</span>
					</div>
				) }
			</div>
		);
	}
);

CSSAutocomplete.displayName = 'CSSAutocomplete';

export default CSSAutocomplete;
