/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';
import blockIcons from '@spectra-helpers/block-icons';

/**
 * Register the Counter Child Wrapper block.
 *
 * @since x.x.x
 */
registerBlockType( metadata.name, {
	icon: blockIcons.counterChildWrapper(),
	edit,
	save,
} );

