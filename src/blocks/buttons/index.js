/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 */
import './style.scss';

/**
 * Internal dependencies.
 */
import blockIcons from '@spectra-helpers/block-icons';
import edit from './edit';
import save from './save';
import metadata from './block.json';

/**
 * Register the Buttons block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.buttons(),
	edit,
	save,
} );
