/**
 * Internal dependencies.
 */
import { RenderBlockVariation } from '@spectra-components/variation-picker';

/**
 * Template option choices for predefined form layouts.
 *
 * @constant
 * @type {Array}
 */
export const variations = [
	{
		name: 'one-column',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'one-column',
		} } />,
		attributes: {
			variationSelected: true,
			layout: {
				type: 'flex',
				orientation: 'vertical',
				flexWrap: 'nowrap',
				justifyContent: 'left',
				verticalAlignment: 'center',
			},
		},
		scope: [ 'block' ],
	},
	{
		name: 'two-column',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'two-column',
		} } />,
		attributes: {
			variationSelected: true,
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type: 'grid',
				columnCount: 2,
				minimumColumnWidth: null,
			},
		},
		isDefault: true,
		innerBlocks: [
			[ 'spectra/container' ],
			[ 'spectra/container' ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'three-column',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'three-column',
		} } />,
		attributes: {
			variationSelected: true,
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type: 'grid',
				columnCount: 3,
				minimumColumnWidth: null,
			},
		},
		innerBlocks: [
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[ 'spectra/container' ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'four-column',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'four-column',
		} } />,
		attributes: {
			variationSelected: true,
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type: 'grid',
				columnCount: 4,
				minimumColumnWidth: null,
			},
		},
		innerBlocks: [
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[ 'spectra/container' ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'two-column-two-row',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'two-column-two-row',
		} } />,
		attributes: {
			variationSelected: true,
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type: 'grid',
				columnCount: 2,
				minimumColumnWidth: null,
			},
		},
		innerBlocks: [
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[ 'spectra/container' ],

		],
		scope: ['block'],
	},
	{
		name: 'four-column-25-75',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'four-column-25-75',
		} } />,
		attributes: {
			variationSelected: true,
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type: 'grid',
				columnCount: 4,
				minimumColumnWidth: null,
			}
		},
		innerBlocks: [
			[ 'spectra/container' ],
			[
				'spectra/container',
				{
					style: {
						layout: {
							columnSpan: 3,
							rowSpan: 1
						}
					},
					layout: {
						type: 'grid',
						columnCount: 1,
						minimumColumnWidth: null
					}
				}
			],
		],
		scope: ['block'],
	},
	{
		name: 'three-column-two-row',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'three-column-two-row',
		} } />,
		attributes: {
			variationSelected: true,
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type: 'grid',
				columnCount: 3,
				minimumColumnWidth: null,
			}
		},
		innerBlocks: [
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[ 'spectra/container' ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'four-column-25-50-25',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'four-column-25-50-25',
		} } />,
		attributes: {
			variationSelected: true,
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type: 'grid',
				columnCount: 4,
				minimumColumnWidth: null
			}
		},
		innerBlocks: [
			[ 'spectra/container' ],
			[ 'spectra/container',
				{
					style: {
						layout: {
							columnSpan: 2,
							rowSpan: 1,

						}
					}
				}
			],
			[ 'spectra/container' ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'four-column-75-25',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'four-column-75-25',
		} } />,
		attributes: {
			variationSelected: true,
			orientation: 'vertical',
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type: 'grid',
				columnCount: 4,
				minimumColumnWidth: null,
			}
		},
		innerBlocks: [
			[
				'spectra/container',
				{
					variationSelected: true,
					style: {
						layout: {
							columnSpan: 3,
							rowSpan: 1
						}
					},
					layout: {
						type: 'grid',
						columnCount: 1,
						minimumColumnWidth: null
					}
				}
			],
			[ 'spectra/container' ]
		],
		scope: ['block'],
	},
	{
		name: 'four-column-75-25-25-75',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'four-column-75-25-25-75',
		} } />,
		attributes: {
			variationSelected:true,
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type:'grid',
				columnCount:4,
				minimumColumnWidth:null
			}
		},
		innerBlocks: [
			[ 'spectra/container',
				{
					style:{
						layout:{
							columnSpan:3,
							rowSpan:1
						}
					}
			    }
			],
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[ 'spectra/container',
				{
					style:{
						layout:{
							columnSpan:3,
							rowSpan:1
						}
					}
			    }
			],
		],
		scope: [ 'block' ],
	},
	{
		name: 'two-column-50-50-100',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'two-column-50-50-100',
		} } />,
		attributes: {
			variationSelected: true,
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type: 'grid',
				columnCount: 2,
				minimumColumnWidth: null,
			},
		},
		innerBlocks: [
			[ 'spectra/container' ],
			[ 'spectra/container' ],
			[
				'spectra/container',
				{
					style: {
						layout: {
							columnSpan: 2,
						},
					},
				},
			],
		],
		scope: [ 'block' ],
	},
	{
		name: 'four-column-25-75-75-25',
		icon: <RenderBlockVariation { ...{
			blockName: 'container',
			variationName: 'four-column-25-75-75-25',
		} } />,
		attributes: {
			variationSelected:true,
			style: {
				spacing: {
					blockGap: 'var:preset|spacing|20',
				},
			},
			layout: {
				type:'grid',
				columnCount:4,
				minimumColumnWidth:null
			}
		},
		innerBlocks: [
			[ 'spectra/container',
				{
					style:{
						layout:{
							columnSpan:1,
							rowSpan:1
						}
					}
			    }
			],
			[ 'spectra/container',
				{
					style:{
						layout:{
							columnSpan:3,
							rowSpan:1
						}
					}
			    }
			],
			[ 'spectra/container',
				{
					style:{
						layout:{
							columnSpan:3,
							rowSpan:1
						}
					}
			    }
			],
			[ 'spectra/container',
				{
					style:{
						layout:{
							columnSpan:1,
							rowSpan:1
						}
					}
			    }
			]
		],
		scope: [ 'block' ],
	},
];
