/**
 * Block: Post
 *
 * A dynamic block for displaying posts in grid, masonry, or carousel layouts with
 * support for pagination, filtering, and custom queries.
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
import './style.scss';
import edit from './edit';
import save from './save';
import json from './block.json';
import blockIcons from '@spectra-helpers/block-icons';

/**
 * Register the Post block.
 */
registerBlockType( json, {
	icon: blockIcons.post(),
	edit,
	save,
} );
