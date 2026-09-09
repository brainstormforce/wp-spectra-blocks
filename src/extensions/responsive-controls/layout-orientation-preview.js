/**
 * The flex direction a viewport's layout orientation implies, in the canvas.
 *
 * WordPress 7.1 stores a block's per-viewport layout in
 * `style['@tablet'].layout` and paints it from `withLayoutStyles`, which reads
 * exactly that. The band it emits, however, is incomplete for one case.
 *
 * Core's flex layout emits `flex-direction: column` for a VERTICAL orientation
 * and nothing at all for a HORIZONTAL one, because `row` is the CSS initial
 * value. That is sound for a single rule, and wrong for a band that has to undo
 * a base rule: a block laid out vertically at base and horizontally at Tablet
 * got a tablet band carrying only `align-items` and `justify-content`, so the
 * base's `flex-direction: column` still applied and the canvas stayed vertical
 * at every device. Measured on 7.1 with the slider's slide:
 *
 *     base rule                     .wp-container-…-is-layout-0 { flex-direction: column; … }
 *     band (480px < width <= 782px) .wp-container-…-is-layout-0 { align-items: center; justify-content: center; }
 *
 * The front end is unaffected — Spectra paints its own per-band layout CSS
 * there, one complete rule per band — so this is a canvas-only correction:
 * whatever orientation a state declares, the direction it implies is stated
 * outright, which is what undoing the base requires.
 *
 * Editor-only, and 7.1+ only: below that core has no viewport states and the
 * classic path keeps the root `layout` attribute pointed at the previewed
 * device, so core's own rule is already the right one.
 *
 * @since 1.0.7
 */

/**
 * WordPress dependencies.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import { coreViewportStatesAreIndependent } from './utils/constants';

/**
 * The `flex-direction` each layout orientation means.
 *
 * @type {Object}
 */
const ORIENTATION_DIRECTION = Object.freeze( {
	vertical: 'column',
	horizontal: 'row',
} );

/**
 * The direction a viewport state resolves to, reverse included.
 *
 * Orientation and `orientationReverse` are separate attributes that describe
 * one thing — which way the flex box runs — so they have to be resolved
 * together, per state, over the base. Each is read state-first, then base,
 * which is core's own model for a viewport state and what the front-end
 * generator does.
 *
 * `horizontal` is the fallback because that is core's default for a flex
 * layout, and the front-end generator defaults to it too: a state that turns
 * reverse on while no orientation is declared anywhere still has a direction
 * to reverse.
 *
 * @since x.x.x
 * @param {Object} style The block's `style` attribute.
 * @param {string} state The viewport state key.
 * @return {string} A `flex-direction` value.
 */
const stateDirection = ( style, state ) => {
	const orientation =
		style[ state ]?.layout?.orientation ??
		style?.layout?.orientation ??
		'horizontal';

	const direction = ORIENTATION_DIRECTION[ orientation ];

	if ( ! direction ) {
		return '';
	}

	const reverse = style[ state ]?.orientationReverse ?? style?.orientationReverse;

	return reverse ? `${ direction }-reverse` : direction;
};

/**
 * Banded `flex-direction` rules for the states that declare an orientation.
 *
 * The selector matches core's layout container in the canvas, which sits at one
 * of three depths depending on where the block calls `useInnerBlocksProps()`:
 *
 *   - the block element itself — Countdown, and most blocks;
 *   - a direct child it renders itself — the slider's `.slide-content`;
 *   - a GRANDCHILD, under core's own `.block-editor-inner-blocks` wrapper —
 *     `spectra/container`, whose flex box is
 *     `[data-block] > .block-editor-inner-blocks > .block-editor-block-list__layout`.
 *     That wrapper is `display: contents`, so it is invisible to layout and easy
 *     to miss: with only the first two branches a Container's `@tablet`
 *     orientation never reached the canvas at all. Measured on a page of ten
 *     Containers — justification and alignment banded correctly while the
 *     direction stayed at the base value, so a horizontal Tablet layout kept
 *     rendering as a vertical stack.
 *
 * All three carry `block-editor-block-list__layout`, so all three are covered.
 *
 * `:not([data-block])` on the descendant half is what keeps a parent's rule off
 * its CHILDREN. Scoping to the block's own `data-block` is not enough: in the
 * canvas a child block's wrapper is a direct child of its parent's element and
 * carries `block-editor-block-list__layout` too, so the parent's band matched
 * every child. Measured on the Countdown, whose `@tablet` layout is horizontal:
 * each unit — a vertical stack of number over label — was turned into a row at
 * Tablet, so the label sat beside the number instead of under it, and the whole
 * unit widened. Only a child BLOCK carries `data-block`; the inner containers
 * this half exists for do not, so excluding it separates the two cleanly.
 *
 * @since 1.0.7
 * @param {Object} style    The block's `style` attribute.
 * @param {string} clientId The block's client id.
 * @return {string} CSS, or '' when no state declares an orientation.
 */
const orientationBandCss = ( style, clientId ) => {
	if ( ! style || 'object' !== typeof style || ! clientId ) {
		return '';
	}

	const queries = window?.spectra_blocks_info?.viewport_media_queries || {};
	let css = '';

	Object.entries( queries ).forEach( ( [ state, query ] ) => {
		// `base` has no query — core's unbanded rule is already correct there.
		if ( ! query || ! style[ state ] ) {
			return;
		}

		/*
		 * A state earns a band by declaring EITHER half of the direction.
		 *
		 * `orientationReverse` used to be left out of this file entirely, and
		 * the canvas took its reverse from the block's
		 * `spectra-orientation-reverse` class instead — which is not banded, and
		 * picks its axis from the BASE orientation. So a Tablet state holding
		 * `layout.orientation: horizontal` with `orientationReverse: true`
		 * rendered two ways and neither was the front end's: with core's
		 * "Responsive styles" off the class was absent and the canvas showed
		 * `row`, not reversed at all; with it on the class applied and the
		 * canvas showed `column-reverse`, reversed along the base's vertical
		 * axis. The front end showed `row-reverse` at that width, correctly.
		 *
		 * Resolving both halves per state fixes it in either mode, and a state
		 * that turns reverse OFF now gets a band that says so, instead of being
		 * left to a class rule that only knows the base.
		 */
		const declaresOrientation = undefined !== style[ state ]?.layout?.orientation;
		const declaresReverse = undefined !== style[ state ]?.orientationReverse;

		if ( ! declaresOrientation && ! declaresReverse ) {
			return;
		}

		const direction = stateDirection( style, state );

		if ( ! direction ) {
			return;
		}

		/*
		 * `!important`, like the front-end generator's own reverse rules. The
		 * class rule this has to beat carries four classes; without it a state
		 * turning reverse off, or reversing along a different axis than the
		 * base, lost to it.
		 */
		css +=
			`@media ${ query }{` +
			`[data-block="${ clientId }"].block-editor-block-list__layout,` +
			`[data-block="${ clientId }"]>.block-editor-block-list__layout:not([data-block]),` +
			`[data-block="${ clientId }"]>.block-editor-inner-blocks>.block-editor-block-list__layout:not([data-block])` +
			`{flex-direction:${ direction } !important;}}`;
	} );

	return css;
};

/**
 * Filters the block list block to state the direction its orientation implies.
 *
 * @since 1.0.7
 * @param {Function} BlockListBlock The original component.
 * @return {Function} The wrapped component.
 */
const withLayoutOrientationPreview = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		const { attributes, clientId } = props;
		const css = useMemo(
			() => orientationBandCss( attributes?.style, clientId ),
			[ attributes?.style, clientId ]
		);

		if ( ! css ) {
			return <BlockListBlock { ...props } />;
		}

		return (
			<>
				<style>{ css }</style>
				<BlockListBlock { ...props } />
			</>
		);
	},
	'withLayoutOrientationPreview'
);

if ( coreViewportStatesAreIndependent() ) {
	addFilter(
		'editor.BlockListBlock',
		'spectra/responsive-controls/layout-orientation-preview',
		withLayoutOrientationPreview
	);
}
