/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './style.scss';
import blockIcons from '@spectra-helpers/block-icons';
import edit from './edit';
import save from './save';
import metadata from './block.json';

/**
 * Register the tabpanel block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.tabsChildren.tabpanel(),
	edit,
	save,
} );
