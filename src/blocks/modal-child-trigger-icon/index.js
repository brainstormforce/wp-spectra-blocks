/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import './style.scss';
import blockIcons from '@spectra-helpers/block-icons';
import edit from './edit';
import metadata from './block.json';

/**
 * Register the Icon block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.icon(),
	edit,
} );
