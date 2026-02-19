/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';
import blockIcons from '@spectra-helpers/block-icons';
import './style.scss';

registerBlockType( metadata.name, {
	icon: blockIcons.counterChildProgressBar(),
	edit,
	save,
} );
