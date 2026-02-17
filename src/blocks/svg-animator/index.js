/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import blockIcons from '@spectra-helpers/block-icons';
import './style.scss';
import edit from './edit';
import metadata from './block.json';

/**
 * Register the SVG Animator block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.svgAnimator(),
	edit,
} );
