/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import blockIcons from '@spectra-helpers/block-icons';
import edit from './edit';
import metadata from './block.json';
import './style.scss';

/**
 * Register the Content block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.accordionChildren.headerContent(),
	edit,
} );
