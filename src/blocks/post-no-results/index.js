/**
 * Block: Post No Results
 *
 * Displays content when no posts are found in the Post block query.
 * Hidden when posts exist unless the block is selected in the editor.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import json from './block.json';
import blockIcons from '@spectra-helpers/block-icons';

/**
 * Register the Post No Results block.
 */
registerBlockType( json, {
	icon: blockIcons.postNoResults(),
	edit,
	save,
} );
