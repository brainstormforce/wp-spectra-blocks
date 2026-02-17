/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import './style.scss';
import edit from './edit';
import metadata from './block.json';
import blockIcons from '@spectra-helpers/block-icons';

/**
 * Register the Icon block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.listIcon(),
	edit,
} );
