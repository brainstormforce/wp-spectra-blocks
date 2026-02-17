/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import blockIcons from '@spectra-helpers/block-icons';
import './style.scss';
import edit from './edit';
import save from './save';
import metadata from './block.json';

/**
 * Register the Accordion block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.accordion(),
	edit,
	save,
} );
