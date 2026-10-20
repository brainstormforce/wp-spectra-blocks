/**
 * Post Date Loop Preview Extension.
 *
 * WordPress 7.1's core/post-date editor renders `attributes.datetime || now` and
 * never reads the `postId` context, so inside a query loop every post shows the
 * same (insert-time) date in the editor preview. This extension feeds each
 * core/post-date instance its real post date — resolved from the loop item's
 * context — through a non-persistent attribute update, so the editor preview
 * matches the per-post date rendered on the front end.
 *
 * Scoped to Spectra loop items via the `spectra/post/isLoopItem` context marker
 * (provided by the Post Template block), so standalone core/post-date blocks keep
 * their native editable-date behaviour.
 *
 * @since 1.0.12
 */

/**
 * WordPress dependencies.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

const LOOP_ITEM_CONTEXT = 'spectra/post/isLoopItem';

/**
 * Let core/post-date receive the Spectra loop-item marker from block context.
 *
 * @since 1.0.12
 *
 * @param {Object} settings Block settings.
 * @param {string} name     Block name.
 * @return {Object} Filtered block settings.
 */
const addLoopItemContext = ( settings, name ) => {
	if ( 'core/post-date' !== name ) {
		return settings;
	}

	const usesContext = settings.usesContext || [];

	if ( ! usesContext.includes( LOOP_ITEM_CONTEXT ) ) {
		settings.usesContext = [ ...usesContext, LOOP_ITEM_CONTEXT ];
	}

	return settings;
};

/**
 * Feed each core/post-date in a Spectra loop its real post date.
 *
 * @since 1.0.12
 */
const withSpectraLoopPostDate = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		if ( 'core/post-date' !== props.name ) {
			return <BlockEdit { ...props } />;
		}

		const { context, attributes, setAttributes } = props;
		const postId = context?.postId;
		const postType = context?.postType;
		const isLoopItem = context?.[ LOOP_ITEM_CONTEXT ];
		const { displayType, datetime } = attributes;

		const postDate = useSelect(
			( select ) => {
				if ( ! isLoopItem || ! postId || ! postType ) {
					return null;
				}

				const record = select( coreStore ).getEntityRecord(
					'postType',
					postType,
					postId
				);

				if ( ! record ) {
					return null;
				}

				return 'modified' === displayType ? record.modified : record.date;
			},
			[ isLoopItem, postId, postType, displayType ]
		);

		const { __unstableMarkNextChangeAsNotPersistent } =
			useDispatch( blockEditorStore );

		useEffect( () => {
			if ( postDate && postDate !== datetime ) {
				// Preview-only correction; must never dirty or persist the post.
				__unstableMarkNextChangeAsNotPersistent();
				setAttributes( { datetime: postDate } );
			}
		}, [ postDate, datetime, __unstableMarkNextChangeAsNotPersistent, setAttributes ] );

		return <BlockEdit { ...props } />;
	},
	'withSpectraLoopPostDate'
);

addFilter(
	'blocks.registerBlockType',
	'spectra/post-date-loop-preview/context',
	addLoopItemContext
);

addFilter(
	'editor.BlockEdit',
	'spectra/post-date-loop-preview/edit',
	withSpectraLoopPostDate
);
