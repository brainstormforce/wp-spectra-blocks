/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import blockIcons from '@spectra-helpers/block-icons';
import edit from './edit';
import metadata from './block.json';
import transforms from './transforms';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 */
import './style.scss';

/**
 * Register the Content block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.content(),
	edit,
	transforms,
	// Join text across a block boundary (Shift+Arrow selection, Backspace at the
	// start of a block) instead of selecting whole blocks. Without a `merge`
	// handler the editor treats the selection as unmergeable and highlights full
	// blocks. Only `text` is merged, so block one's tag and attributes are kept —
	// same behaviour as core/paragraph. See #1165.
	merge( attributes, attributesToMerge ) {
		return {
			text: ( attributes.text || '' ) + ( attributesToMerge.text || '' ),
		};
	},
} );
