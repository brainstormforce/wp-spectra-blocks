/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useRefEffect } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { ENTER } from '@wordpress/keycodes';

/**
 * A Content block whose tag is phrasing, sitting directly inside a control a
 * converter built — a Container printed as a `<button>`, or a Tab (Div) — is
 * that control's label: the words of a button, a tab. A label edits like the
 * label of Spectra's own Button and Tab blocks, never like a paragraph: Enter
 * adds no block (Shift+Enter keeps its line break), and Backspace does not
 * merge or remove it.
 *
 * @since x.x.x
 */

/** The tags a Content block prints inline, so it can be a control's label. */
export const CONTROL_LABEL_TAGS = [ 'span', 'strong', 'em', 'small', 'mark', 'code', 'cite', 'time', 'label', 'del', 'ins', 'sub', 'sup', 'abbr', 'kbd', 'samp', 'var', 'output', 'q', 's', 'dfn', 'bdi', 'bdo' ];

const isControl = ( name, attributes ) =>
	'spectra/tabs-child-tab-trigger' === name || ( 'spectra/container' === name && 'button' === attributes?.htmlTag );

/**
 * Whether the Content block is a control's label.
 *
 * @since x.x.x
 * @param {string} clientId The Content block.
 * @param {string} tagName  Its tag.
 * @return {boolean} True inside a converted button or a Tab (Div).
 */
export const useIsControlLabel = ( clientId, tagName ) =>
	useSelect(
		( select ) => {
			if ( ! CONTROL_LABEL_TAGS.includes( tagName ) ) {
				return false;
			}
			const { getBlockRootClientId, getBlockName, getBlockAttributes } = select( blockEditorStore );
			const parentId = getBlockRootClientId( clientId );
			return !! parentId && isControl( getBlockName( parentId ), getBlockAttributes( parentId ) );
		},
		[ clientId, tagName ]
	);

/**
 * Enter in a label adds nothing: handled on the element, before the block
 * editor's writing flow would split the block (the same seam as
 * `useOnEnter`, which inserts a sibling for a paragraph). Shift+Enter is left
 * to RichText, which keeps a line break.
 *
 * @since x.x.x
 * @return {Function} A ref callback for the label's RichText element.
 */
export const useLabelEnter = () =>
	useRefEffect( ( element ) => {
		const onKeyDown = ( event ) => {
			if ( event.keyCode === ENTER && ! event.shiftKey ) {
				event.preventDefault();
			}
		};
		element.addEventListener( 'keydown', onKeyDown );
		return () => element.removeEventListener( 'keydown', onKeyDown );
	}, [] );
