/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import './style.scss';
import edit from './edit';
import save from './save';
import metadata from './block.json';
import blockIcons from '@spectra-helpers/block-icons';

/**
 * Register the Icons Wrapper block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.icon(),
	edit,
	save,
} );