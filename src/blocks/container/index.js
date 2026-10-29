/**
 * External dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { select } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import edit from './edit';
import save from './save';
import { isTopLevelContainer } from './render';
import metadata from './block.json';
import './style.scss';
import blockIcons from '@spectra-helpers/block-icons';

/**
 * Register the Container block.
 */
registerBlockType( metadata.name, {
	icon: blockIcons.container(),
	edit,
	save,
} );

/**
 * Offer only top/bottom margin on a top-level container.
 *
 * Its horizontal position belongs to its alignment: default and wide are
 * centred by `margin-inline: auto !important` (common.scss), and full width is
 * placed by the theme, so a left/right margin would do nothing on the front
 * end. A container inside any other block keeps all four sides. Margin stays
 * off wherever the theme turns it off.
 *
 * @since 1.0.13
 */
addFilter(
	'blockEditor.useSetting.before',
	'spectra/container/root-margin-sides',
	( value, path, clientId, blockName ) => {
		if ( 'spacing.margin' !== path || metadata.name !== blockName || ! clientId ) {
			return value;
		}

		const blockEditor = select( blockEditorStore );
		if ( ! isTopLevelContainer( blockEditor, clientId ) ) {
			return value;
		}

		const features = blockEditor.getSettings().__experimentalFeatures;
		const isMarginEnabled = features?.blocks?.[ blockName ]?.spacing?.margin ?? features?.spacing?.margin;

		return isMarginEnabled ? { sides: [ 'top', 'bottom' ] } : value;
	}
);
