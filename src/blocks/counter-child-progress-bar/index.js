/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import blockIcons from '@spectra-helpers/block-icons';
import './style.scss';

registerBlockType( 'spectra/counter-child-progress-bar', {
	icon: blockIcons.counterChildProgressBar(),
	edit,
	save,
} );
