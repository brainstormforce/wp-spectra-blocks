/**
 * Post Block - Variations
 *
 * Defines the available layout variations for the Post block.
 *
 * @since x.x.x
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { RenderBlockVariation } from '@spectra-components/variation-picker';

/**
 * Inner blocks template shared by all variations.
 */
const innerBlocksTemplate = [ [ 'spectra/post-template' ], [ 'spectra/post-no-results' ] ];

/**
 * Block variations for different post layouts.
 */
const variations = [
	{
		name: 'grid',
		title: __( 'Grid', 'spectra-blocks' ),
		icon: (
			<RenderBlockVariation
				{ ...{
					blockName: 'post',
					variationName: 'grid',
				} }
			/>
		),
		isDefault: true,
		attributes: {
			variationSelected: true,
			layoutType: 'grid',
		},
		innerBlocks: innerBlocksTemplate,
		scope: [ 'block' ],
	},
	{
		name: 'masonry',
		title: __( 'Masonry', 'spectra-blocks' ),
		icon: (
			<RenderBlockVariation
				{ ...{
					blockName: 'post',
					variationName: 'masonry',
				} }
			/>
		),
		attributes: {
			variationSelected: true,
			layoutType: 'masonry',
		},
		innerBlocks: innerBlocksTemplate,
		scope: [ 'block' ],
	},
	{
		name: 'carousel',
		title: __( 'Carousel', 'spectra-blocks' ),
		icon: (
			<RenderBlockVariation
				{ ...{
					blockName: 'post',
					variationName: 'carousel',
				} }
			/>
		),
		attributes: {
			variationSelected: true,
			layoutType: 'carousel',
			align: '',
		},
		innerBlocks: innerBlocksTemplate,
		scope: [ 'block' ],
	},
];

export default variations;
