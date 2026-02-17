/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import blockIcons from '@spectra-helpers/block-icons';
import edit from './edit';
import save from './save';
import metadata from './block.json';
import './style.scss';

/**
 * Register the Modal block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.modalChildren.modalTrigger(),
	edit,
	save,
} );
