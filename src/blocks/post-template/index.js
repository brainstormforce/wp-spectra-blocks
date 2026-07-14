/**
 * Block: Post Template
 *
 * Renders a repeatable template for displaying posts in grid, masonry, or carousel layouts.
 * Provides variation picker for predefined templates or custom templates.
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
import './editor.scss';
import edit from './edit';
import save from './save';
import json from './block.json';
import blockIcons from '@spectra-helpers/block-icons';

/**
 * Register the Post Template block.
 */
registerBlockType( json, {
	icon: blockIcons.postTemplate(),
	edit,
	save,
} );
