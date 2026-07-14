/**
 * Post Template Block - Variations
 *
 * Defines predefined template layouts including portrait and landscape
 * card designs with different arrangements of post elements.
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
 * Template variation choices for predefined layouts.
 *
 * Provides portrait and landscape card layouts plus a blank template
 * for custom designs.
 */
const variations = [
	{
		name: 'portrait',
		title: __( 'Portrait', 'spectra-blocks' ),
		icon: (
			<RenderBlockVariation
				{ ...{
					blockName: 'post-template',
					variationName: 'portrait',
				} }
			/>
		),
		attributes: {
			variationSelected: true,
			layout: {
				type: 'grid',
				columnCount: 3,
			},
		},
		scope: [ 'block' ],
		innerBlocks: [
			[
				'spectra/container',
				{
					variationSelected: true,
					overflow: 'hidden',
					height: '100%',
					backgroundColor: '#F6F6F6',
					layout: {
						type: 'default',
					},
					style: {
						spacing: {
							padding: '0',
							blockGap: '0',
						},
						border: {
							radius: '0.5rem',
						},
					},
					responsiveControls: {
						lg: {
							height: '100%',
							layout: {
								type: 'default',
							},
							style: {
								spacing: {
									padding: '0',
									blockGap: '0',
								},
								border: {
									radius: '0.5rem',
								},
							},
						},
					},
				},
				[
					[ 'core/post-featured-image', { isLink: true, aspectRatio: '16/9', width: '100%' } ],
					[
						'spectra/container',
						{
							variationSelected: true,
							width: '100%',
							layout: {
								type: 'flex',
								flexWrap: 'nowrap',
								justifyContent: 'stretch',
								verticalAlignment: 'top',
								orientation: 'vertical',
							},
							style: {
								spacing: {
									padding: 'var:preset|spacing|40',
									blockGap: 'var:preset|spacing|20',
								},
							},
							responsiveControls: {
								lg: {
									width: '100%',
									layout: {
										type: 'flex',
										flexWrap: 'nowrap',
										justifyContent: 'stretch',
										verticalAlignment: 'top',
										orientation: 'vertical',
									},
									style: {
										spacing: {
											padding: 'var:preset|spacing|40',
											blockGap: 'var:preset|spacing|20',
										},
									},
								},
							},
						},
						[
							[ 'core/post-terms', { term: 'category' } ],
							[ 'core/post-title', { isLink: true, fontSize: 'medium' } ],
							[ 'core/post-date' ],
							[ 'core/post-excerpt', { excerptLength: 20 } ],
							[
								'spectra/container',
								{
									variationSelected: true,
									layout: {
										type: 'flex',
										flexWrap: 'nowrap',
										justifyContent: 'left',
										verticalAlignment: 'center',
										orientation: 'horizontal',
									},
									style: {
										spacing: {
											padding: '0',
											blockGap: 'var:preset|spacing|20',
										},
									},
									responsiveControls: {
										lg: {
											layout: {
												type: 'flex',
												flexWrap: 'nowrap',
												justifyContent: 'left',
												verticalAlignment: 'center',
												orientation: 'horizontal',
											},
											style: {
												spacing: {
													padding: '0',
													blockGap: 'var:preset|spacing|20',
												},
											},
										},
									},
								},
								[
									[ 'core/avatar', { size: 48, style: { border: { radius: '999px' } } } ],
									[ 'core/post-author-name' ],
								],
							],
						],
					],
				],
			],
		],
	},
	{
		name: 'landscape',
		title: __( 'Landscape', 'spectra-blocks' ),
		icon: (
			<RenderBlockVariation
				{ ...{
					blockName: 'post-template',
					variationName: 'landscape',
				} }
			/>
		),
		attributes: {
			variationSelected: true,
			layout: {
				type: 'grid',
				columnCount: 2,
			},
		},
		scope: [ 'block' ],
		innerBlocks: [
			[
				'spectra/container',
				{
					variationSelected: true,
					height: '100%',
					fontSize: 'small',
					layout: {
						type: 'flex',
						flexWrap: 'nowrap',
						justifyContent: 'space-between',
						verticalAlignment: 'center',
						orientation: 'horizontal',
					},
					style: {
						spacing: {
							padding: '0',
							blockGap: 'var:preset|spacing|20',
						},
					},
					responsiveControls: {
						lg: {
							height: '100%',
							fontSize: 'small',
							layout: {
								type: 'flex',
								flexWrap: 'nowrap',
								justifyContent: 'space-between',
								verticalAlignment: 'center',
								orientation: 'horizontal',
							},
							style: {
								spacing: {
									padding: '0',
									blockGap: 'var:preset|spacing|20',
								},
							},
						},
					},
				},
				[
					[ 'core/post-featured-image', { isLink: true, aspectRatio: '2/3', width: '100%' } ],
					[
						'spectra/container',
						{
							variationSelected: true,
							width: '100%',
							layout: {
								type: 'flex',
								flexWrap: 'nowrap',
								justifyContent: 'left',
								verticalAlignment: 'top',
								orientation: 'vertical',
							},
							style: {
								spacing: {
									padding: '0',
									blockGap: 'var:preset|spacing|20',
								},
							},
							responsiveControls: {
								lg: {
									width: '100%',
									layout: {
										type: 'flex',
										flexWrap: 'nowrap',
										justifyContent: 'left',
										verticalAlignment: 'top',
										orientation: 'vertical',
									},
									style: {
										spacing: {
											padding: '0',
											blockGap: 'var:preset|spacing|20',
										},
									},
								},
							},
						},
						[
							[
								'spectra/container',
								{
									variationSelected: true,
									layout: {
										type: 'flex',
										flexWrap: 'nowrap',
										justifyContent: 'left',
										verticalAlignment: 'center',
										orientation: 'horizontal',
									},
									style: {
										spacing: {
											padding: '0',
											blockGap: 'var:preset|spacing|20',
										},
									},
									responsiveControls: {
										lg: {
											layout: {
												type: 'flex',
												flexWrap: 'nowrap',
												justifyContent: 'left',
												verticalAlignment: 'center',
												orientation: 'horizontal',
											},
											style: {
												spacing: {
													padding: '0',
													blockGap: 'var:preset|spacing|20',
												},
											},
										},
									},
								},
								[
									[ 'core/avatar', { size: 36, style: { border: { radius: '999px' } } } ],
									[ 'core/post-author-name' ],
								],
							],
							[ 'core/post-title', { isLink: true, fontSize: 'medium' } ],
							[ 'core/post-date' ],
							[
								'core/post-excerpt',
								{ excerptLength: 20, moreText: __( 'Read more', 'spectra-blocks' ) },
							],
						],
					],
				],
			],
		],
	},
	{
		name: 'create-from-scratch',
		title: __( 'Create from scratch', 'spectra-blocks' ),
		isDefault: true,
		icon: (
			<RenderBlockVariation
				{ ...{
					blockName: 'post-template',
					variationName: 'create-from-scratch',
				} }
			/>
		),
		attributes: {
			variationSelected: true,
		},
		scope: [ 'block' ],
		innerBlocks: [ [ 'core/post-title', { isLink: true, fontSize: 'medium' } ] ],
	},
];

export default variations;
