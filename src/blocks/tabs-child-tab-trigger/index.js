/**
 * WordPress dependencies.
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
 * Block Registration.
 */
registerBlockType( metadata, {
	icon: blockIcons.tabsChildren.button(),
	edit,
	save,
} );
