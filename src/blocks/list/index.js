/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';
import './style.scss';
import blockIcons from '@spectra-helpers/block-icons';

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Register the block
 */
registerBlockType( metadata.name, {
	icon: blockIcons.list(),
	edit,
	save,
} );