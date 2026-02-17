import { __ } from '@wordpress/i18n';
import React, { useState, useEffect } from 'react';
import { Container } from '@bsf/force-ui';
import BlockItem from '@Common/components/BlockItem';

const Blocks = () => {
	const [ allBlocksData, setAllBlocksData ] = useState( null ); // Initialize state.

	useEffect( () => {
		const blocksData = convertToWidgetsArray( blocks );
		setAllBlocksData( blocksData );
	}, [] );

	const blocks = [
		{
			title: __( 'Container', 'ultimate-addons-for-gutenberg' ),
			slug: 'container',
			icon: 'container',
			is_pro: false,
			is_active: true,
			link: 'container-v3'
		},
		{
			title: __( 'Button', 'ultimate-addons-for-gutenberg' ),
			slug: 'button',
			icon: 'button',
			is_pro: false,
			is_active: true,
			link: 'button-v3'
		},
		{
			title: __( 'Text', 'ultimate-addons-for-gutenberg' ),
			slug: 'text',
			icon: 'content',
			is_pro: false,
			is_active: true,
			link: 'text-v3'
		},
		{
			title: __( 'Icon', 'ultimate-addons-for-gutenberg' ),
			slug: 'icon',
			icon: 'icon',
			is_pro: false,
			is_active: true,
			link: 'icon-v3'
		},
		{
			title: __( 'Accordions', 'ultimate-addons-for-gutenberg' ),
			slug: 'accordions',
			icon: 'accordion',
			is_pro: false,
			is_active: true,
			link: 'accordion-v3'
		},
		{
			title: __( 'Countdown', 'ultimate-addons-for-gutenberg' ),
			slug: 'countdown',
			icon: 'countdown',
			is_pro: false,
			is_active: true,
			link: 'countdown-v3'
		},
		{
			title: __( 'Slider', 'ultimate-addons-for-gutenberg' ),
			slug: 'slider',
			icon: 'slider',
			is_pro: false,
			is_active: true,
			link: 'slider-v3'
		},
		{
			title: __( 'Tabs', 'ultimate-addons-for-gutenberg' ),
			slug: 'tabs',
			icon: 'tabs',
			is_pro: false,
			is_active: true,
			link: 'tabs-v3'
		},
		{
			title: __( 'Google Map', 'ultimate-addons-for-gutenberg' ),
			slug: 'google-map',
			icon: 'google-map',
			is_pro: false,
			is_active: true,
			link: 'google-maps-v3'
		},
		{
			title: __( 'Modal', 'ultimate-addons-for-gutenberg' ),
			slug: 'modal',
			icon: 'modal',
			is_pro: false,
			is_active: true,
			link: 'modal-v3'
		},
		{
			title: __( 'List', 'ultimate-addons-for-gutenberg' ),
			slug: 'list',
			icon: 'list',
			is_pro: false,
			is_active: true,
			link: 'list-v3'
		},
		{
			title: __( 'Separator', 'ultimate-addons-for-gutenberg' ),
			slug: 'separator',
			icon: 'separator',
			is_pro: false,
			is_active: true,
			link: 'separator-v3'
		},
		{
			title: __( 'SVG Animator', 'ultimate-addons-for-gutenberg' ),
			slug: 'svg-animator',
			icon: 'svg-animator',
			is_pro: false,
			is_active: true,
			link: 'svg-animator-v3'
		},
		{
			title: __( 'Before After Slider', 'ultimate-addons-for-gutenberg' ),
			slug: 'before-after-slider',
			icon: 'before-after-slider',
			is_pro: false,
			is_active: true,
			link: 'before-after-slider-v3'
		},
	];

	function convertToWidgetsArray( data ) {
		const blocksArray = [];

		for ( const key in data ) {
			if ( data.hasOwnProperty( key ) ) {
				const block = data[ key ];

				blocksArray.push( {
					id: key, // Using the key as 'widgetTitle'
					slug: block.slug,
					title: block.title,
					icon: block.icon,
					is_pro: block.is_pro,
					is_active: block.is_active !== undefined ? block.is_active : true, // Check if is_activate is set
					link: block.link
				} );
			}
		}

		return blocksArray;
	}

	return (
		<div className="rounded-lg bg-white w-full border border-solid border-border-subtle">
			<div
				className="flex items-center justify-between p-4"
				style={ {
					paddingBottom: '0',
				} }
			>
				<p className="m-0 text-sm font-semibold text-text-primary">
					{ __( 'Free blocks', 'ultimate-addons-for-gutenberg' ) }
				</p>
			</div>
			<div className="flex flex-col rounded-lg p-4">
				<Container
					className="p-1 bg-background-secondary rounded-lg gap-1"
					containerType="grid"
					cols={{ sm: 2, md: 3, lg: 4 }}
				>
					{ allBlocksData?.slice( 0, 16 ).map( ( block ) => (
						<Container.Item
							key={ block.id }
							alignSelf="auto"
							className="text-wrap card-border rounded-md bg-background-primary p-2 hover:border-border-subtle block-item hover:shadow-sm "
						>
							<BlockItem block={ block } key={ block.id } updateCounter={ 0 } />
						</Container.Item>
					) ) }
				</Container>
			</div>
		</div>
	);
};

export default Blocks;
