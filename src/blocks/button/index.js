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
import metadata from './block.json';

/**
 * Register the Button block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.button(),
	edit,
} );
