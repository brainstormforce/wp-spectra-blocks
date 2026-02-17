/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import blockIcons from '@spectra-helpers/block-icons';
import './style.scss';
import edit from './edit';
import save from './save';
import metadata from './block.json';

/**
 * Every block starts by registering a new block type definition.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.tabs(),
	edit,
	save,
} );
