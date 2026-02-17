/*
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';
import blockIcons from '@spectra-helpers/block-icons';

/*
 * Internal dependencies.
 */	
import edit from './edit';
import save from './save';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	icon: blockIcons.slider(),
	edit,
	save,
} );
