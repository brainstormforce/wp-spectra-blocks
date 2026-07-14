/**
 * CSS completion data and engine for the GBS inline CSS editors.
 *
 * @since x.x.x
 */

const CSS_PROPERTIES = [
	'align-content', 'align-items', 'align-self', 'all', 'animation', 'animation-delay',
	'animation-direction', 'animation-duration', 'animation-fill-mode',
	'animation-iteration-count', 'animation-name', 'animation-play-state',
	'animation-timing-function', 'aspect-ratio', 'backdrop-filter',
	'background', 'background-attachment', 'background-blend-mode', 'background-clip',
	'background-color', 'background-image', 'background-origin', 'background-position',
	'background-repeat', 'background-size',
	'border', 'border-bottom', 'border-bottom-color', 'border-bottom-left-radius',
	'border-bottom-right-radius', 'border-bottom-style', 'border-bottom-width',
	'border-collapse', 'border-color', 'border-image', 'border-left', 'border-left-color',
	'border-left-style', 'border-left-width', 'border-radius', 'border-right',
	'border-right-color', 'border-right-style', 'border-right-width', 'border-spacing',
	'border-style', 'border-top', 'border-top-color', 'border-top-left-radius',
	'border-top-right-radius', 'border-top-style', 'border-top-width', 'border-width',
	'bottom', 'box-shadow', 'box-sizing',
	'clip-path', 'color', 'column-count', 'column-gap', 'column-rule', 'columns',
	'content', 'cursor',
	'direction', 'display',
	'filter', 'flex', 'flex-basis', 'flex-direction', 'flex-flow', 'flex-grow',
	'flex-shrink', 'flex-wrap', 'float', 'font', 'font-family', 'font-feature-settings',
	'font-kerning', 'font-size', 'font-stretch', 'font-style', 'font-variant', 'font-weight',
	'gap', 'grid', 'grid-area', 'grid-auto-columns', 'grid-auto-flow', 'grid-auto-rows',
	'grid-column', 'grid-column-end', 'grid-column-start', 'grid-row', 'grid-row-end',
	'grid-row-start', 'grid-template', 'grid-template-areas', 'grid-template-columns',
	'grid-template-rows',
	'height', 'hyphens',
	'isolation',
	'justify-content', 'justify-items', 'justify-self',
	'left', 'letter-spacing', 'line-height', 'list-style', 'list-style-position',
	'list-style-type',
	'margin', 'margin-block', 'margin-bottom', 'margin-inline', 'margin-left',
	'margin-right', 'margin-top', 'max-height', 'max-width', 'min-height', 'min-width',
	'mix-blend-mode',
	'object-fit', 'object-position', 'opacity', 'order', 'outline', 'outline-color',
	'outline-offset', 'outline-style', 'outline-width', 'overflow', 'overflow-x', 'overflow-y',
	'overscroll-behavior',
	'padding', 'padding-block', 'padding-bottom', 'padding-inline', 'padding-left',
	'padding-right', 'padding-top', 'place-content', 'place-items', 'place-self',
	'pointer-events', 'position',
	'resize', 'right', 'row-gap',
	'scroll-behavior', 'scroll-margin', 'scroll-padding',
	'text-align', 'text-decoration', 'text-decoration-color', 'text-decoration-line',
	'text-indent', 'text-overflow', 'text-shadow', 'text-transform', 'top', 'transform',
	'transform-origin', 'transform-style', 'transition', 'transition-delay',
	'transition-duration', 'transition-property', 'transition-timing-function',
	'user-select',
	'vertical-align', 'visibility',
	'white-space', 'width', 'will-change', 'word-break', 'word-spacing', 'word-wrap',
	'writing-mode',
	'z-index',
];

const CSS_VALUES = {
	'display': [ 'flex', 'grid', 'block', 'inline-block', 'inline', 'inline-flex', 'inline-grid', 'none', 'contents', 'table', 'table-cell', 'list-item' ],
	'position': [ 'relative', 'absolute', 'fixed', 'sticky', 'static' ],
	'flex-direction': [ 'row', 'column', 'row-reverse', 'column-reverse' ],
	'flex-wrap': [ 'nowrap', 'wrap', 'wrap-reverse' ],
	'justify-content': [ 'flex-start', 'flex-end', 'center', 'space-between', 'space-around', 'space-evenly', 'start', 'end', 'normal', 'stretch' ],
	'justify-items': [ 'auto', 'normal', 'stretch', 'start', 'end', 'flex-start', 'flex-end', 'center', 'baseline' ],
	'justify-self': [ 'auto', 'normal', 'stretch', 'start', 'end', 'flex-start', 'flex-end', 'center', 'baseline' ],
	'align-items': [ 'flex-start', 'flex-end', 'center', 'stretch', 'baseline', 'start', 'end', 'normal' ],
	'align-content': [ 'flex-start', 'flex-end', 'center', 'space-between', 'space-around', 'space-evenly', 'stretch', 'normal' ],
	'align-self': [ 'auto', 'flex-start', 'flex-end', 'center', 'stretch', 'baseline' ],
	'overflow': [ 'auto', 'hidden', 'scroll', 'visible', 'clip' ],
	'overflow-x': [ 'auto', 'hidden', 'scroll', 'visible', 'clip' ],
	'overflow-y': [ 'auto', 'hidden', 'scroll', 'visible', 'clip' ],
	'text-align': [ 'left', 'right', 'center', 'justify', 'start', 'end' ],
	'text-transform': [ 'none', 'uppercase', 'lowercase', 'capitalize' ],
	'text-decoration': [ 'none', 'underline', 'line-through', 'overline' ],
	'text-decoration-line': [ 'none', 'underline', 'line-through', 'overline' ],
	'text-overflow': [ 'clip', 'ellipsis' ],
	'font-weight': [ '100', '200', '300', '400', '500', '600', '700', '800', '900', 'bold', 'bolder', 'lighter', 'normal' ],
	'font-style': [ 'normal', 'italic', 'oblique' ],
	'font-stretch': [ 'normal', 'condensed', 'expanded', 'extra-condensed', 'extra-expanded', 'semi-condensed', 'semi-expanded' ],
	'font-variant': [ 'normal', 'small-caps' ],
	'cursor': [ 'auto', 'default', 'pointer', 'move', 'text', 'wait', 'progress', 'help', 'not-allowed', 'grab', 'grabbing', 'crosshair', 'zoom-in', 'zoom-out', 'none', 'ew-resize', 'ns-resize' ],
	'visibility': [ 'visible', 'hidden', 'collapse' ],
	'pointer-events': [ 'none', 'auto', 'all' ],
	'object-fit': [ 'cover', 'contain', 'fill', 'none', 'scale-down' ],
	'box-sizing': [ 'border-box', 'content-box' ],
	'white-space': [ 'normal', 'nowrap', 'pre', 'pre-wrap', 'pre-line', 'break-spaces' ],
	'word-break': [ 'normal', 'break-all', 'keep-all', 'break-word' ],
	'resize': [ 'none', 'both', 'horizontal', 'vertical' ],
	'float': [ 'none', 'left', 'right', 'inline-start', 'inline-end' ],
	'background-repeat': [ 'no-repeat', 'repeat', 'repeat-x', 'repeat-y', 'space', 'round' ],
	'background-size': [ 'auto', 'cover', 'contain' ],
	'background-attachment': [ 'scroll', 'fixed', 'local' ],
	'background-clip': [ 'border-box', 'padding-box', 'content-box', 'text' ],
	'background-blend-mode': [ 'normal', 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'color-dodge', 'soft-light', 'difference' ],
	'mix-blend-mode': [ 'normal', 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'color-dodge', 'soft-light', 'difference' ],
	'animation-timing-function': [ 'ease', 'linear', 'ease-in', 'ease-out', 'ease-in-out', 'step-start', 'step-end', 'cubic-bezier(0.4, 0, 0.2, 1)', 'cubic-bezier(0, 0, 0.2, 1)' ],
	'transition-timing-function': [ 'ease', 'linear', 'ease-in', 'ease-out', 'ease-in-out', 'step-start', 'step-end', 'cubic-bezier(0.4, 0, 0.2, 1)' ],
	'animation-fill-mode': [ 'none', 'forwards', 'backwards', 'both' ],
	'animation-play-state': [ 'running', 'paused' ],
	'animation-direction': [ 'normal', 'reverse', 'alternate', 'alternate-reverse' ],
	'animation-iteration-count': [ 'infinite', '1', '2', '3' ],
	'vertical-align': [ 'baseline', 'top', 'middle', 'bottom', 'text-top', 'text-bottom' ],
	'transform-style': [ 'flat', 'preserve-3d' ],
	'scroll-behavior': [ 'auto', 'smooth' ],
	'user-select': [ 'none', 'auto', 'text', 'all' ],
	'isolation': [ 'auto', 'isolate' ],
	'will-change': [ 'auto', 'transform', 'opacity', 'scroll-position' ],
	'writing-mode': [ 'horizontal-tb', 'vertical-rl', 'vertical-lr' ],
	'direction': [ 'ltr', 'rtl' ],
	'hyphens': [ 'none', 'manual', 'auto' ],
	'list-style-type': [ 'none', 'disc', 'circle', 'square', 'decimal', 'lower-alpha', 'upper-alpha', 'lower-roman', 'upper-roman' ],
	'list-style-position': [ 'inside', 'outside' ],
	'border-style': [ 'none', 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset', 'hidden' ],
	'border-collapse': [ 'separate', 'collapse' ],
	'overscroll-behavior': [ 'auto', 'contain', 'none' ],
};

// Common CSS functions that appear after ":" in a value position
const CSS_FUNCTIONS = [
	{ label: 'var()', insert: 'var(--', detail: 'CSS variable' },
	{ label: 'calc()', insert: 'calc()', detail: 'Math expression' },
	{ label: 'clamp()', insert: 'clamp(, , )', detail: 'Clamp between min/max' },
	{ label: 'min()', insert: 'min(, )', detail: 'Min of values' },
	{ label: 'max()', insert: 'max(, )', detail: 'Max of values' },
	{ label: 'rgb()', insert: 'rgb(, , )', detail: 'RGB color' },
	{ label: 'rgba()', insert: 'rgba(, , , )', detail: 'RGBA color' },
	{ label: 'hsl()', insert: 'hsl(, %, %)', detail: 'HSL color' },
	{ label: 'linear-gradient()', insert: 'linear-gradient(to right, , )', detail: 'Gradient' },
	{ label: 'radial-gradient()', insert: 'radial-gradient(circle, , )', detail: 'Radial gradient' },
	{ label: 'translate()', insert: 'translate(, )', detail: 'Transform' },
	{ label: 'translateX()', insert: 'translateX()', detail: 'Transform' },
	{ label: 'translateY()', insert: 'translateY()', detail: 'Transform' },
	{ label: 'scale()', insert: 'scale()', detail: 'Transform' },
	{ label: 'rotate()', insert: 'rotate(deg)', detail: 'Transform' },
	{ label: 'skewX()', insert: 'skewX(deg)', detail: 'Transform' },
	{ label: 'skewY()', insert: 'skewY(deg)', detail: 'Transform' },
];

const AT_RULES = [
	{ label: '@keyframes', insert: '@keyframes name {\n  from {\n    \n  }\n  to {\n    \n  }\n}', detail: 'Animation keyframes' },
	{ label: '@media', insert: '@media (max-width: 768px) {\n  \n}', detail: 'Media query' },
	{ label: '@media (min-width)', insert: '@media (min-width: 768px) {\n  \n}', detail: 'Min-width query' },
	{ label: '@media (max-width)', insert: '@media (max-width: 768px) {\n  \n}', detail: 'Max-width query' },
	{ label: '@media (prefers-color-scheme: dark)', insert: '@media (prefers-color-scheme: dark) {\n  \n}', detail: 'Dark mode query' },
	{ label: '@supports', insert: '@supports (display: grid) {\n  \n}', detail: 'Feature query' },
	{ label: '@import', insert: "@import url('');", detail: 'Import stylesheet' },
	{ label: '@font-face', insert: "@font-face {\n  font-family: 'MyFont';\n  src: url('font.woff2') format('woff2');\n}", detail: 'Custom font' },
	{ label: '@layer', insert: '@layer name {\n  \n}', detail: 'Cascade layer' },
];

const KEYFRAME_STOPS = [
	{ label: 'from', insert: 'from {\n  \n}', detail: 'Start state' },
	{ label: 'to', insert: 'to {\n  \n}', detail: 'End state' },
	{ label: '0%', insert: '0% {\n  \n}', detail: 'Keyframe stop' },
	{ label: '25%', insert: '25% {\n  \n}', detail: 'Keyframe stop' },
	{ label: '50%', insert: '50% {\n  \n}', detail: 'Keyframe stop' },
	{ label: '75%', insert: '75% {\n  \n}', detail: 'Keyframe stop' },
	{ label: '100%', insert: '100% {\n  \n}', detail: 'Keyframe stop' },
];

/**
 * Returns CSS completions for the given line context.
 *
 * @param {string}   lineText   Text of the current line up to the cursor.
 * @param {number}   cursorPos  Cursor position within lineText.
 * @param {string[]} gbsVars    Array of `--spectra-*` var names.
 * @param {boolean}  isKeyframe True when editing a keyframe body (stops only).
 * @return {{ suggestions: Array, trigger: {start:number, end:number}|null }}
 */
export function getCSSCompletions( lineText, cursorPos, gbsVars = [], isKeyframe = false ) {
	const textBefore = lineText.slice( 0, cursorPos );

	// ── 1. var(-- context ────────────────────────────────────────────────────────
	const varInMatch = textBefore.match( /var\((--[a-zA-Z0-9-]*)$/ );
	if ( varInMatch ) {
		const prefix = varInMatch[ 1 ];
		const start  = textBefore.length - prefix.length;
		const end    = start + prefix.length;
		const filtered = gbsVars.filter( ( v ) => v.startsWith( prefix ) ).slice( 0, 10 );
		if ( filtered.length > 0 ) {
			return {
				suggestions: filtered.map( ( v ) => ( { label: v, insert: v + ')', detail: 'GBS variable' } ) ),
				trigger: { start, end },
			};
		}
	}

	// ── 2. Bare -- typing — suggest GBS vars ─────────────────────────────────────
	const dashMatch = textBefore.match( /(--[a-zA-Z0-9-]*)$/ );
	if ( dashMatch && dashMatch[ 1 ].length >= 2 && gbsVars.length > 0 ) {
		const prefix = dashMatch[ 1 ];
		const start  = textBefore.length - prefix.length;
		const end    = start + prefix.length;
		const filtered = gbsVars.filter( ( v ) => v.startsWith( prefix ) ).slice( 0, 10 );
		if ( filtered.length > 0 ) {
			return {
				suggestions: filtered.map( ( v ) => ( { label: v, insert: v, detail: 'GBS variable' } ) ),
				trigger: { start, end },
			};
		}
	}

	// ── 3. @-rule context ─────────────────────────────────────────────────────────
	const atMatch = textBefore.match( /(@[a-zA-Z-]*)$/ );
	if ( atMatch ) {
		const prefix = atMatch[ 1 ];
		const start  = textBefore.length - prefix.length;
		const end    = start + prefix.length;
		const filtered = AT_RULES.filter( ( r ) => r.label.startsWith( prefix ) );
		if ( filtered.length > 0 ) {
			return { suggestions: filtered, trigger: { start, end } };
		}
	}

	// ── 4. CSS value context (after "property: ") ─────────────────────────────────
	const valueMatch = textBefore.match( /\b([a-z-]{2,})\s*:\s*([a-zA-Z0-9-]*)$/ );
	if ( valueMatch ) {
		const property = valueMatch[ 1 ];
		const prefix   = valueMatch[ 2 ];
		const start    = textBefore.length - prefix.length;
		const end      = start + prefix.length;
		const values   = CSS_VALUES[ property ] || [];
		let filtered;
		if ( prefix ) {
			filtered = values.filter( ( v ) => v.startsWith( prefix ) );
		} else {
			// Cursor is right after ":", show all values + function shortcuts
			filtered = values;
		}
		filtered = filtered.slice( 0, 8 );
		if ( filtered.length > 0 ) {
			return {
				suggestions: filtered.map( ( v ) => ( { label: v, insert: v, detail: property } ) ),
				trigger: { start, end },
			};
		}
		// If no values but cursor is right after `:`, suggest CSS functions
		if ( ! prefix && CSS_FUNCTIONS.length > 0 ) {
			return {
				suggestions: CSS_FUNCTIONS.slice( 0, 8 ),
				trigger: { start, end },
			};
		}
	}

	// ── 5a. Keyframe percentage stops (isKeyframe mode, line starts with digit) ───
	if ( isKeyframe ) {
		const pctMatch = textBefore.match( /^(\d+)$/ );
		if ( pctMatch ) {
			const prefix = pctMatch[ 1 ];
			const start  = 0;
			const end    = prefix.length;
			const filtered = KEYFRAME_STOPS.filter( ( s ) => s.label.startsWith( prefix ) );
			if ( filtered.length > 0 ) {
				return { suggestions: filtered, trigger: { start, end } };
			}
		}
	}

	// ── 5b. CSS property context (at start of declaration / after { or ;) ─────────
	const propMatch = textBefore.match( /(?:[{;]\s*|^\s*)([a-z-]{2,})$/ );
	if ( propMatch ) {
		const prefix = propMatch[ 1 ];
		const start  = textBefore.length - prefix.length;
		const end    = start + prefix.length;

		// In keyframe mode, merge stops (word-starts) with properties
		let stopSuggestions = [];
		if ( isKeyframe ) {
			stopSuggestions = KEYFRAME_STOPS.filter( ( s ) => s.label.startsWith( prefix ) );
		}

		const propSuggestions = CSS_PROPERTIES
			.filter( ( p ) => p.startsWith( prefix ) )
			.slice( 0, 8 - stopSuggestions.length )
			.map( ( p ) => ( { label: p + ': ', insert: p + ': ', detail: 'property' } ) );

		const combined = [ ...stopSuggestions, ...propSuggestions ];
		if ( combined.length > 0 ) {
			return { suggestions: combined.slice( 0, 8 ), trigger: { start, end } };
		}
	}

	return { suggestions: [], trigger: null };
}
