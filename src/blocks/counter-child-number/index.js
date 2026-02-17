/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';
import './style.scss';
import blockIcons from '@spectra-helpers/block-icons';

/**
 * Register the Counter Number block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.counterChildNumber(),
	edit,
	save,
} );
