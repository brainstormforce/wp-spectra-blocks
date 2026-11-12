/**
 * HTML Attributes Extension.
 *
 * Registers two layers of HTML attribute control across all Spectra blocks:
 *   Layer 1 — contextual tag fields shown when a semantic tag is selected.
 *   Layer 2 — free-form custom attribute repeater on every block.
 *
 * PHP rendering is handled by SpectraBlocks\Extensions\HtmlAttributes via
 * the render_block filter using WP_HTML_Tag_Processor.
 *
 * @since x.x.x
 */

import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

import './editor.scss';
import addHtmlAttributesControls from './controls';
import { extendBlockAttributes } from './utils/helpers';

/**
 * Extends block attributes with the htmlAttributes array attribute.
 *
 * @since x.x.x
 */
addFilter(
	'blocks.registerBlockType',
	'spectra/html-attributes/add-attributes',
	extendBlockAttributes
);

/**
 * Injects Tag Attributes and Custom Attributes panels into the block inspector.
 *
 * @since x.x.x
 */
addFilter(
	'editor.BlockEdit',
	'spectra/html-attributes/add-controls',
	addHtmlAttributesControls
);

/**
 * Registers block-specific contextual attribute fields for Spectra blocks whose
 * primary element is not driven by a tag switcher.
 *
 * @since x.x.x
 */
addFilter(
	'spectra.blockContextualFields',
	'spectra-blocks/html-attributes-block-fields',
	( fields, blockName ) => {
		switch ( blockName ) {
			case 'spectra/button':
				// href, target, rel, aria-label are already exposed in the button block's
				// own settings panel. Only download is missing from the block UI.
				return [ ...fields, {
					attribute: 'download',
					label: __( 'Download', 'spectra-blocks' ),
					type: 'toggle',
					help: __( 'Prompts the browser to download the linked file instead of navigating to it', 'spectra-blocks' ),
				} ];

			case 'spectra/buttons':
				return [
					...fields,
					{
						attribute: 'role',
						label: __( 'ARIA Role', 'spectra-blocks' ),
						type: 'text',
						help: __( 'e.g. group — identifies this as a button group for assistive technology', 'spectra-blocks' ),
					},
					{
						attribute: 'aria-label',
						label: __( 'ARIA Label', 'spectra-blocks' ),
						type: 'text',
						help: __( 'Accessible name for the button group', 'spectra-blocks' ),
					},
				];

			case 'spectra/tabs':
				return [ ...fields, {
					attribute: 'aria-label',
					label: __( 'ARIA Label', 'spectra-blocks' ),
					type: 'text',
					help: __( 'Accessible name for the tabbed interface — required when a page has multiple tab sets', 'spectra-blocks' ),
				} ];

			case 'spectra/accordion':
				return [ ...fields, {
					attribute: 'aria-label',
					label: __( 'ARIA Label', 'spectra-blocks' ),
					type: 'text',
					help: __( 'Accessible name for the accordion region', 'spectra-blocks' ),
				} ];

			case 'spectra/slider':
				return [ ...fields, {
					attribute: 'aria-label',
					label: __( 'ARIA Label', 'spectra-blocks' ),
					type: 'text',
					help: __( 'Accessible name for the slider/carousel region', 'spectra-blocks' ),
				} ];

			default:
				return fields;
		}
	}
);
