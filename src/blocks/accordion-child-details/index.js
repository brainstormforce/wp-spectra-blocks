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
 * Register the Accordion Details innerblock.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.accordionChildren.details(),
	edit,
	save,
} );
