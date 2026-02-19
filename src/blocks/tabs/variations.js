/**
 * External dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { RenderBlockVariation } from '@spectra-components/variation-picker';

/**
 * Template option choices for the predefined Tabs layouts.
 *
 * @constant
 * @type {Array}
 */
const variations = [
	{
		name: 'horizontal',
		title: __( 'Horizontal Tabs', 'spectra-blocks' ),
		description: __(
			'Align tabs horizontally above the tabpanels',
			'spectra-blocks'
		),
		isDefault: true,
		attributes: {
			variationSelected: true,
			layout: {
				type: 'flex',
				flexWrap: 'nowrap',
				orientation: 'vertical',
				justifyContent: 'stretch',
				verticalAlignment: null,
			},
		},
		scope: [ 'block' ],
		icon: (
			<RenderBlockVariation
				{ ...{
					blockName: 'tabs',
					variationName: 'horizontal',
				} }
			/>
		),
		innerBlocks: [
			[
				'spectra/tabs-child-tab-wrapper',
				{},
				[
					[
						'spectra/tabs-child-tab-button',
						{ placeholder: 'Tab 1' },
					],
					[
						'spectra/tabs-child-tab-button',
						{ placeholder: 'Tab 2' },
					],
					[
						'spectra/tabs-child-tab-button',
						{ placeholder: 'Tab 3' },
					],
				],
			],
			[ 'spectra/tabs-child-tabpanel', {} ],
			[ 'spectra/tabs-child-tabpanel', {} ],
			[ 'spectra/tabs-child-tabpanel', {} ],
		],
	},
	{
		name: 'vertical',
		title: __( 'Vertical Tabs', 'spectra-blocks' ),
		description: __(
			'Align tabs vertically before the tabpanels',
			'spectra-blocks'
		),
		attributes: {
			variationSelected: true,
			layout: {
				type: 'flex',
				flexWrap: 'nowrap',
				orientation: 'horizontal',
				justifyContent: 'left',
				verticalAlignment: 'stretch',
			},
		},
		scope: [ 'block' ],
		icon: (
			<RenderBlockVariation
				{ ...{
					blockName: 'tabs',
					variationName: 'vertical',
				} }
			/>
		),
		innerBlocks: [
			[
				'spectra/tabs-child-tab-wrapper',
				{
					layout: {
						type: 'flex',
						flexWrap: 'nowrap',
						justifyContent: 'stretch',
						verticalAlignment: 'top',
						orientation: 'vertical',
					},
					style: {
						layout: {
							selfStretch: 'fixed',
							flexSize: '200px',
						},
					},
				},
				[
					[
						'spectra/tabs-child-tab-button',
						{ placeholder: 'Tab 1' },
					],
					[
						'spectra/tabs-child-tab-button',
						{ placeholder: 'Tab 2' },
					],
					[
						'spectra/tabs-child-tab-button',
						{ placeholder: 'Tab 3' },
					],
				],
			],
			[
				'spectra/tabs-child-tabpanel',
				{
					style: {
						layout: {
							selfStretch: 'fixed',
							flexSize: '100%',
						},
					},
				},
			],
			[
				'spectra/tabs-child-tabpanel',
				{
					style: {
						layout: {
							selfStretch: 'fixed',
							flexSize: '100%',
						},
					},
				},
			],
			[
				'spectra/tabs-child-tabpanel',
				{
					style: {
						layout: {
							selfStretch: 'fixed',
							flexSize: '100%',
						},
					},
				},
			],
		],
	},
];

export default variations;
