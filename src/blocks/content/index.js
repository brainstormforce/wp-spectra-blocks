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
import transforms from './transforms';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 */
import './style.scss';

/**
 * Register the Content block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.content(),
	edit,
	transforms,
} );
